<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Backup
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/class/MokoDoliTrainingBackup.class.php
 * VERSION:  01.00.00
 * BRIEF:    Backup, snapshot, restore, integrity, and retention manager.
 */

class MokoDoliTrainingBackup
{
	private $db;
	private string $backup_dir;
	private string $manifest_path;
	private string $seed_sql;
	private string $reset_sql;
	private string $lock_file;
	private int    $max_backups;

	const JUNCTION = [
		'llx_categorie_societe' => 'fk_categorie',
		'llx_categorie_product' => 'fk_categorie',
	];

	const LOCK_TTL = 300; // seconds before a stale lock is broken

	public function __construct($db, int $max_backups = 10)
	{
		$this->db            = $db;
		$base                = dirname(__DIR__);
		$this->backup_dir    = $base . '/backup';
		$this->manifest_path = $base . '/sql/manifest.json';
		$this->seed_sql      = $base . '/sql/mokotraining.sql';
		$this->reset_sql     = $base . '/sql/mokotraining_reset.sql';
		$this->lock_file     = $base . '/backup/.lock';
		$this->max_backups   = max(2, $max_backups);
	}

	// ── Lock ──────────────────────────────────────────────────────────────────

	public function acquireLock(): bool
	{
		if (!is_dir($this->backup_dir)) {
			mkdir($this->backup_dir, 0750, true);
		}
		if (file_exists($this->lock_file)) {
			$age = time() - (int) file_get_contents($this->lock_file);
			if ($age < self::LOCK_TTL) return false;
			unlink($this->lock_file);
		}
		file_put_contents($this->lock_file, (string) time());
		return true;
	}

	public function releaseLock(): void
	{
		if (file_exists($this->lock_file)) {
			unlink($this->lock_file);
		}
	}

	public function isLocked(): bool
	{
		if (!file_exists($this->lock_file)) return false;
		$age = time() - (int) file_get_contents($this->lock_file);
		return $age < self::LOCK_TTL;
	}

	// ── Backup ────────────────────────────────────────────────────────────────

	public function createBackup(string $label): array
	{
		if (!is_dir($this->backup_dir)) {
			mkdir($this->backup_dir, 0750, true);
		}

		$manifest = $this->loadManifest();
		if (empty($manifest)) {
			return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => ['Manifest not found or empty']];
		}

		$ts   = gmdate('Ymd_His');
		$path = $this->backup_dir . '/' . $label . '_' . $ts . '.sql';

		$lines = [
			'-- MokoDoliTraining backup: ' . $label . ' | ' . gmdate('c'),
			'-- Module ID: 185068 | DO NOT EDIT',
			'-- SHA256: {CHECKSUM}',
			'SET FOREIGN_KEY_CHECKS = 0;',
			'',
		];

		$total  = 0;
		$errors = [];

		foreach ($manifest as $tbl => $rowids) {
			$pk     = self::JUNCTION[$tbl] ?? 'rowid';
			$result = $this->dumpTable($tbl, $rowids, $pk);
			$errors = array_merge($errors, $result['errors']);
			if (!empty($result['sql'])) {
				$lines[] = '-- TABLE: ' . $tbl . ' (' . $result['count'] . ' rows)';
				$lines[] = $result['sql'];
				$lines[] = '';
				$total  += $result['count'];
			}
		}

		$lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
		$lines[] = '-- END BACKUP: ' . $total . ' rows / ' . count($manifest) . ' tables / ' . gmdate('c');

		$content  = implode("\n", $lines);
		$checksum = hash('sha256', $content);
		$content  = str_replace('{CHECKSUM}', $checksum, $content);

		file_put_contents($path, $content);
		file_put_contents($path . '.sha256', $checksum);

		$this->enforceRetention($label);

		return ['path' => $path, 'rows' => $total, 'checksum' => $checksum, 'errors' => $errors];
	}

	private function dumpTable(string $tbl, array $rowids, string $pk): array
	{
		$id_list = implode(',', array_map('intval', $rowids));

		$res = $this->db->query("SHOW COLUMNS FROM `$tbl`");
		if (!$res) {
			return ['sql' => '', 'count' => 0, 'errors' => ["SHOW COLUMNS failed: $tbl"]];
		}
		$cols = [];
		while ($col = $this->db->fetch_object($res)) {
			$cols[] = $col->Field;
		}
		if (empty($cols)) {
			return ['sql' => '', 'count' => 0, 'errors' => []];
		}

		$col_list     = '`' . implode('`,`', $cols) . '`';
		$update_clause = implode(',', array_map(fn($c) => "`$c`=VALUES(`$c`)", $cols));

		$res = $this->db->query("SELECT $col_list FROM `$tbl` WHERE `$pk` IN ($id_list)");
		if (!$res) {
			return ['sql' => '', 'count' => 0, 'errors' => ["SELECT failed: $tbl — " . $this->db->lasterror()]];
		}

		$sql_lines = [];
		$count     = 0;
		while ($obj = $this->db->fetch_object($res)) {
			$row  = (array) $obj;
			$vals = [];
			foreach ($cols as $col) {
				$v = $row[$col] ?? null;
				$vals[] = ($v === null)
					? 'NULL'
					: "'" . $this->db->escape((string) $v) . "'";
			}
			$sql_lines[] = "INSERT INTO `$tbl` ($col_list) VALUES ("
				. implode(',', $vals)
				. ") ON DUPLICATE KEY UPDATE $update_clause;";
			$count++;
		}

		return ['sql' => implode("\n", $sql_lines), 'count' => $count, 'errors' => []];
	}

	// ── Integrity ─────────────────────────────────────────────────────────────

	public function verifyIntegrity(string $path): array
	{
		if (!file_exists($path)) {
			return ['ok' => false, 'reason' => 'File not found'];
		}

		$content      = file_get_contents($path);
		$sha_file     = $path . '.sha256';
		$stored       = file_exists($sha_file)
			? trim(file_get_contents($sha_file))
			: null;

		// Extract embedded checksum and blank it out for recomputation
		preg_match('/^-- SHA256: ([a-f0-9]{64})/m', $content, $m);
		$embedded = $m[1] ?? null;

		if (!$stored && !$embedded) {
			return ['ok' => false, 'reason' => 'No checksum available'];
		}

		$expected   = $stored ?? $embedded;
		$verify_src = preg_replace('/^-- SHA256: [a-f0-9]{64}/m', '-- SHA256: {CHECKSUM}', $content);
		$actual     = hash('sha256', $verify_src);

		if ($actual !== $expected) {
			return [
				'ok'     => false,
				'reason' => 'Checksum mismatch — file may be corrupted or tampered',
				'stored' => $expected,
				'actual' => $actual,
			];
		}

		return ['ok' => true, 'checksum' => $actual];
	}

	// ── Retention ─────────────────────────────────────────────────────────────

	private function enforceRetention(string $label): void
	{
		$files = glob($this->backup_dir . '/' . $label . '_*.sql') ?: [];
		$files = array_filter($files, fn($f) => !str_ends_with($f, '.sha256'));
		usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

		foreach (array_slice($files, $this->max_backups) as $old) {
			@unlink($old);
			@unlink($old . '.sha256');
		}
	}

	public function purgeByType(string $type, int $keep = 0): int
	{
		$files = glob($this->backup_dir . '/' . $type . '_*.sql') ?: [];
		$files = array_filter($files, fn($f) => !str_ends_with($f, '.sha256'));
		usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
		$purged = 0;
		foreach (array_slice($files, $keep) as $f) {
			@unlink($f);
			@unlink($f . '.sha256');
			$purged++;
		}
		return $purged;
	}

	// ── SQL execution ─────────────────────────────────────────────────────────

	public function execSqlFile(string $path): array
	{
		if (!file_exists($path)) {
			return ['ok' => 0, 'errors' => ['File not found: ' . basename($path)]];
		}

		$ok     = 0;
		$errors = [];
		$stmt   = '';
		$in_del = false;

		foreach (file($path) as $line) {
			$t = trim($line);
			if ($t === '' || str_starts_with($t, '--')) continue;
			if (preg_match('/^DELIMITER\s+/i', $t)) { $in_del = !$in_del; continue; }
			if ($in_del) continue;

			$stmt .= ' ' . $t;
			if (str_ends_with(rtrim($t), ';')) {
				$res = $this->db->query(trim($stmt));
				if ($res === false) {
					$errors[] = $this->db->lasterror() . ' — ' . substr(trim($stmt), 0, 100);
				} else {
					$ok++;
				}
				$stmt = '';
			}
		}
		return ['ok' => $ok, 'errors' => $errors];
	}

	public function runSeed(): array   { return $this->execSqlFile($this->seed_sql);  }
	public function runReset(): array  { return $this->execSqlFile($this->reset_sql); }

	public function restoreFromFile(string $path): array
	{
		$integrity = $this->verifyIntegrity($path);
		if (!$integrity['ok']) {
			return ['ok' => 0, 'errors' => ['Integrity check failed: ' . $integrity['reason']]];
		}
		return $this->execSqlFile($path);
	}

	// ── Listing / lookup ──────────────────────────────────────────────────────

	public function listBackups(): array
	{
		if (!is_dir($this->backup_dir)) return [];
		$files = glob($this->backup_dir . '/*.sql') ?: [];
		$files = array_filter($files, fn($f) => !str_ends_with($f, '.sha256'));
		$out   = [];
		foreach ($files as $f) {
			$name = basename($f);
			preg_match('/^(rollback|snapshot)_(\d{8}_\d{6})\.sql$/', $name, $m);
			$sha_path  = $f . '.sha256';
			$integrity = file_exists($sha_path) ? trim(file_get_contents($sha_path)) : null;
			$out[] = [
				'path'      => $f,
				'name'      => $name,
				'type'      => $m[1] ?? 'unknown',
				'ts'        => isset($m[2])
					? substr($m[2], 0, 4) . '-' . substr($m[2], 4, 2) . '-' . substr($m[2], 6, 2)
					  . 'T' . substr($m[2], 9, 2) . ':' . substr($m[2], 11, 2) . ':' . substr($m[2], 13, 2) . 'Z'
					: '',
				'size'      => filesize($f),
				'mtime'     => filemtime($f),
				'checksum'  => $integrity,
				'has_sha'   => file_exists($sha_path),
			];
		}
		usort($out, fn($a, $b) => $b['mtime'] - $a['mtime']);
		return $out;
	}

	public function getLatest(string $type): ?string
	{
		foreach ($this->listBackups() as $b) {
			if ($b['type'] === $type) return $b['path'];
		}
		return null;
	}

	public function getBackupByName(string $name): ?string
	{
		$path = $this->backup_dir . '/' . basename($name);
		return (file_exists($path) && str_ends_with($path, '.sql')) ? $path : null;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function loadManifest(): array
	{
		if (!file_exists($this->manifest_path)) return [];
		$raw = json_decode(file_get_contents($this->manifest_path), true);
		return is_array($raw['tables'] ?? null) ? $raw['tables'] : [];
	}

	public function getBackupDir(): string
	{
		return $this->backup_dir;
	}
}
