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
 * VERSION:  development
 * BRIEF:    Backup, snapshot, restore, integrity, and retention manager.
 *
 * NOTE: All backup SQL content is stored in llx_mokodolitraining_backup.
 *       No backup files are written to disk — only a .lock file is used
 *       during backup/restore operations to prevent concurrency.
 *       Full (rollback) backups delegate to Utils::dumpDatabase (mysqlnobin)
 *       and capture the output into the DB. Snapshot backups are
 *       manifest-scoped INSERT...ON DUPLICATE KEY UPDATE statements.
 */

class MokoDoliTrainingBackup
{
	private $db;
	private string $lock_file;
	private int    $max_backups;

	// Tables that use a non-rowid lookup column when building snapshot SELECT.
	// Keys use bare names (no prefix) — loadManifest() strips the prefix.
	// Must stay in sync with MokoDoliTrainingSeed::TABLE_PK.
	const JUNCTION = [
		'actioncomm'        => 'id',
		'categorie_societe' => 'fk_categorie',
		'categorie_product' => 'fk_categorie',
	];

	const LOCK_TTL = 300; // seconds before a stale .lock file is broken

	public function __construct($db, int $max_backups = 10)
	{
		$this->db          = $db;
		$lock_dir          = dirname(__DIR__) . '/backup';
		$this->lock_file   = $lock_dir . '/.lock';
		$this->max_backups = max(2, $max_backups);
	}

	// ── Lock ──────────────────────────────────────────────────────────────────

	public function acquireLock(): bool
	{
		$lock_dir = dirname($this->lock_file);
		if (!is_dir($lock_dir)) {
			dol_mkdir($lock_dir);
		}
		if (file_exists($this->lock_file)) {
			$age = time() - (int) file_get_contents($this->lock_file);
			if ($age < self::LOCK_TTL) return false;
			@unlink($this->lock_file);
		}
		$fh = @fopen($this->lock_file, 'x');
		if ($fh === false) return false;
		fwrite($fh, (string) time());
		fclose($fh);
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

	// ── Full backup via Utils::dumpDatabase (mysqlnobin) ──────────────────────
	//
	// Captures Utils output into the DB table instead of a filesystem file.
	// The temp .sql file from Dolibarr is read, stored in content, then deleted.

	public function createFullBackup(string $label): array
	{
		global $conf;
		$errors = [];

		require_once DOL_DOCUMENT_ROOT . '/core/class/utils.class.php';
		$utils = new Utils($this->db);

		$ts      = gmdate('Ymd_His');
		$ref     = $label . '_' . $ts;
		$sql_tmp = $ref . '.sql';
		$dol_dir = $conf->admin->dir_output . '/backup';

		$ret = $utils->dumpDatabase('none', 'mysqlnobin', 0, $sql_tmp, 0, 0);
		if ($ret < 0) {
			$errors[] = 'Utils::dumpDatabase failed: ' . $utils->error;
			return ['rowid' => 0, 'rows' => 0, 'checksum' => '', 'errors' => $errors];
		}

		$src = $dol_dir . '/' . $sql_tmp;
		if (!file_exists($src)) {
			$errors[] = 'Dump file not found after dumpDatabase: ' . $src;
			return ['rowid' => 0, 'rows' => 0, 'checksum' => '', 'errors' => $errors];
		}

		$body = file_get_contents($src);
		@unlink($src);

		$header  = implode("\n", [
			'-- MokoDoliTraining FULL backup: ' . $label . ' | ' . gmdate('c'),
			'-- Module ID: 185068 | Utils::dumpDatabase (mysqlnobin) | DO NOT EDIT',
			'-- SHA256: {CHECKSUM}',
			'',
		]);
		$content  = $header . $body;
		$checksum = hash('sha256', $content);
		$content  = str_replace('{CHECKSUM}', $checksum, $content);

		$rows = $this->countSqlRows($content);
		$rowid = $this->storeInDb($label, $ref, $content, $checksum, $rows, (int) ($conf->entity ?? 1));
		if (!$rowid) {
			$errors[] = 'Failed to store backup in database.';
			return ['rowid' => 0, 'rows' => $rows, 'checksum' => $checksum, 'errors' => $errors];
		}

		$this->enforceRetentionDb($label, (int) ($conf->entity ?? 1));

		return ['rowid' => $rowid, 'rows' => $rows, 'checksum' => $checksum, 'errors' => $errors];
	}

	// ── Manifest-scoped snapshot (INSERT ... ON DUPLICATE KEY UPDATE) ──────────

	public function createBackup(string $label): array
	{
		global $conf;
		$errors   = [];
		$entity   = (int) ($conf->entity ?? 1);
		$manifest = $this->loadManifest($entity);

		if (empty($manifest)) {
			return ['rowid' => 0, 'rows' => 0, 'checksum' => '', 'errors' => [
				'Manifest is empty — run Install first to seed training data.',
			]];
		}

		$ts   = gmdate('Ymd_His');
		$ref  = $label . '_' . $ts;

		$sql_lines = [
			'-- MokoDoliTraining snapshot backup: ' . $label . ' | ' . gmdate('c'),
			'-- Module ID: 185068 | Manifest-scoped rows only | DO NOT EDIT',
			'-- SHA256: {CHECKSUM}',
			'SET FOREIGN_KEY_CHECKS = 0;',
			'',
		];

		$total = 0;

		foreach ($manifest as $tbl => $rowids) {
			$bare   = preg_replace('/^' . preg_quote(MAIN_DB_PREFIX, '/') . '/', '', $tbl);
			$pk     = self::JUNCTION[$bare] ?? 'rowid';
			$result = $this->dumpTableRows($tbl, $rowids, $pk);
			$errors = array_merge($errors, $result['errors']);
			if (!empty($result['sql'])) {
				$sql_lines[] = '-- TABLE: ' . $tbl . ' (' . $result['count'] . ' rows)';
				$sql_lines[] = $result['sql'];
				$sql_lines[] = '';
				$total      += $result['count'];
			}
		}

		$sql_lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
		$sql_lines[] = '-- END SNAPSHOT: ' . $total . ' rows / ' . count($manifest) . ' tables / ' . gmdate('c');

		$content  = implode("\n", $sql_lines);
		$checksum = hash('sha256', $content);
		$content  = str_replace('{CHECKSUM}', $checksum, $content);

		$rowid = $this->storeInDb($label, $ref, $content, $checksum, $total, $entity);
		if (!$rowid) {
			$errors[] = 'Failed to store snapshot in database.';
			return ['rowid' => 0, 'rows' => $total, 'checksum' => $checksum, 'errors' => $errors];
		}

		$this->enforceRetentionDb($label, $entity);

		return ['rowid' => $rowid, 'rows' => $total, 'checksum' => $checksum, 'errors' => $errors];
	}

	// ── Integrity ─────────────────────────────────────────────────────────────

	public function verifyIntegrity(int $rowid): array
	{
		$row = $this->loadRow($rowid);
		if (!$row) {
			return ['ok' => false, 'reason' => 'Backup record not found (rowid ' . $rowid . ')'];
		}

		$content  = $row->content ?? '';
		$stored   = $row->checksum ?? null;

		if (!$stored) {
			return ['ok' => false, 'reason' => 'No checksum stored for this backup'];
		}

		// Recompute: replace embedded checksum placeholder and re-hash
		$verify_src = preg_replace('/^-- SHA256: [a-f0-9]{64}/m', '-- SHA256: {CHECKSUM}', $content);
		$actual     = hash('sha256', $verify_src);

		if ($actual !== $stored) {
			return [
				'ok'     => false,
				'reason' => 'Checksum mismatch — backup may be corrupted',
				'stored' => $stored,
				'actual' => $actual,
			];
		}

		return ['ok' => true, 'checksum' => $actual];
	}

	// ── Restore ───────────────────────────────────────────────────────────────

	public function restoreById(int $rowid): array
	{
		$integrity = $this->verifyIntegrity($rowid);
		if (!$integrity['ok']) {
			return ['ok' => 0, 'errors' => ['Integrity check failed: ' . $integrity['reason']]];
		}
		$row = $this->loadRow($rowid);
		return $this->execSqlContent($row->content ?? '');
	}

	// ── SQL execution ─────────────────────────────────────────────────────────

	public function execSqlContent(string $content): array
	{
		$ok       = 0;
		$errors   = [];
		$stmt     = '';
		$in_delim = false;

		$this->db->begin();

		foreach (explode("\n", $content) as $raw_line) {
			$t = trim($raw_line);

			if ($t === '')                           continue;
			if (str_starts_with($t, '--'))           continue;
			if (preg_match('/^DELIMITER\s+/i', $t))  { $in_delim = !$in_delim; continue; }
			if ($in_delim)                           continue;

			$stmt .= ' ' . $t;

			if (str_ends_with(rtrim($t), ';')) {
				$clean = trim($stmt);
				if (MAIN_DB_PREFIX !== 'llx_') {
					$clean = str_replace('llx_', MAIN_DB_PREFIX, $clean);
				}
				if ($clean !== '') {
					$res = $this->db->query($clean);
					if ($res === false) {
						$errors[] = $this->db->lasterror() . ' -- ' . substr($clean, 0, 120);
					} else {
						$ok++;
					}
				}
				$stmt = '';
			}
		}

		if (!empty($errors)) {
			$this->db->rollback();
		} else {
			$this->db->commit();
		}

		return ['ok' => $ok, 'errors' => $errors];
	}

	public function runSeed(string $mode = 'training', int $entity = 1): array
	{
		dol_include_once('/mokodolitraining/class/MokoDoliTrainingSeed.class.php');
		$seed = new MokoDoliTrainingSeed($this->db);
		$r1   = $seed->seedStatic($entity, $mode);
		$r2   = $seed->seedOrders($entity);
		return [
			'ok'     => ($r1['ok'] && $r2['ok']) ? 1 : 0,
			'errors' => array_merge($r1['errors'] ?? [], $r2['errors'] ?? []),
		];
	}

	public function runReset(int $entity = 1): array
	{
		dol_include_once('/mokodolitraining/class/MokoDoliTrainingSeed.class.php');
		$seed = new MokoDoliTrainingSeed($this->db);
		return $seed->reset($entity);
	}

	// ── Listing / lookup ──────────────────────────────────────────────────────

	public function listBackups(int $entity = 0): array
	{
		global $conf;
		$e    = $entity ?: (int) ($conf->entity ?? 1);
		$btbl = MAIN_DB_PREFIX . 'mokodolitraining_backup';
		$sql  = "SELECT rowid, entity, label, ref, datec, checksum, row_count, byte_size, fk_user_creat, status"
			  . " FROM `{$btbl}` WHERE entity = {$e} ORDER BY datec DESC, rowid DESC";
		$res  = $this->db->query($sql);
		if (!$res) return [];
		$out = [];
		while ($row = $this->db->fetch_object($res)) {
			$out[] = [
				'rowid'    => (int) $row->rowid,
				'label'    => $row->label,
				'ref'      => $row->ref,
				'datec'    => $row->datec,
				'rows'     => (int) $row->row_count,
				'size'     => (int) $row->byte_size,
				'checksum' => $row->checksum,
				'status'   => (int) $row->status,
			];
		}
		return $out;
	}

	public function getLatest(string $label, int $entity = 0): ?int
	{
		foreach ($this->listBackups($entity) as $b) {
			if ($b['label'] === $label) return $b['rowid'];
		}
		return null;
	}

	public function deleteById(int $rowid): bool
	{
		$btbl = MAIN_DB_PREFIX . 'mokodolitraining_backup';
		return (bool) $this->db->query("DELETE FROM `{$btbl}` WHERE rowid = " . (int) $rowid);
	}

	public function purgeByLabel(string $label, int $keep = 0, int $entity = 0): int
	{
		global $conf;
		$e    = $entity ?: (int) ($conf->entity ?? 1);
		$btbl = MAIN_DB_PREFIX . 'mokodolitraining_backup';
		$sql  = "SELECT rowid FROM `{$btbl}` WHERE entity = {$e} AND label = '" . $this->db->escape($label) . "'"
			  . " ORDER BY datec DESC, rowid DESC";
		$res  = $this->db->query($sql);
		if (!$res) return 0;
		$ids = [];
		while ($row = $this->db->fetch_object($res)) { $ids[] = (int) $row->rowid; }
		$purged = 0;
		foreach (array_slice($ids, $keep) as $id) {
			if ($this->deleteById($id)) $purged++;
		}
		return $purged;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function storeInDb(string $label, string $ref, string $content, string $checksum, int $rows, int $entity): int
	{
		global $user;
		$btbl  = MAIN_DB_PREFIX . 'mokodolitraining_backup';
		$size  = strlen($content);
		$uid   = isset($user->id) ? (int) $user->id : 0;
		$sql   = "INSERT INTO `{$btbl}` (entity, label, ref, datec, content, checksum, row_count, byte_size, fk_user_creat, status)"
			   . " VALUES ({$entity}, '" . $this->db->escape($label) . "', '" . $this->db->escape($ref) . "',"
			   . " NOW(), '" . $this->db->escape($content) . "', '" . $this->db->escape($checksum) . "',"
			   . " {$rows}, {$size}, {$uid}, 0)";
		if (!$this->db->query($sql)) return 0;
		return (int) $this->db->last_insert_id();
	}

	public function loadRow(int $rowid): ?object
	{
		$btbl = MAIN_DB_PREFIX . 'mokodolitraining_backup';
		$res  = $this->db->query("SELECT * FROM `{$btbl}` WHERE rowid = " . (int) $rowid);
		if (!$res) return null;
		$row = $this->db->fetch_object($res);
		return $row ?: null;
	}

	private function dumpTableRows(string $tbl, array $rowids, string $pk): array
	{
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) {
			return ['sql' => '', 'count' => 0, 'errors' => ["Invalid table name: $tbl"]];
		}

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

		$col_list      = '`' . implode('`,`', $cols) . '`';
		$update_clause = implode(',', array_map(fn($c) => "`$c`=VALUES(`$c`)", $cols));

		$res = $this->db->query("SELECT $col_list FROM `$tbl` WHERE `$pk` IN ($id_list)");
		if (!$res) {
			return ['sql' => '', 'count' => 0, 'errors' => ["SELECT failed: $tbl -- " . $this->db->lasterror()]];
		}

		$sql_lines = [];
		$count     = 0;
		while ($obj = $this->db->fetch_object($res)) {
			$row  = (array) $obj;
			$vals = [];
			foreach ($cols as $col) {
				$v      = $row[$col] ?? null;
				$vals[] = ($v === null) ? 'NULL' : "'" . $this->db->escape((string) $v) . "'";
			}
			$sql_lines[] = "INSERT INTO `$tbl` ($col_list) VALUES ("
				. implode(',', $vals)
				. ") ON DUPLICATE KEY UPDATE $update_clause;";
			$count++;
		}

		return ['sql' => implode("\n", $sql_lines), 'count' => $count, 'errors' => []];
	}

	private function countSqlRows(string $content): int
	{
		$count = 0;
		foreach (explode("\n", $content) as $line) {
			if (stripos(ltrim($line), 'INSERT INTO') === 0) {
				$count += substr_count($line, '),(') + 1;
			}
		}
		return $count;
	}

	private function enforceRetentionDb(string $label, int $entity): void
	{
		$this->purgeByLabel($label, $this->max_backups, $entity);
	}

	private function loadManifest(int $entity = 1): array
	{
		$mtbl = MAIN_DB_PREFIX . 'mokodolitraining_manifest';
		$sql  = "SELECT table_name, record_id FROM `{$mtbl}` WHERE entity = " . (int) $entity
			  . " ORDER BY table_name, record_id";
		$res  = $this->db->query($sql);
		if (!$res) return [];

		$tables = [];
		while ($row = $this->db->fetch_object($res)) {
			$tbl = (MAIN_DB_PREFIX !== 'llx_')
				? preg_replace('/^llx_/', MAIN_DB_PREFIX, $row->table_name)
				: $row->table_name;
			$tables[$tbl][] = (int) $row->record_id;
		}
		return $tables;
	}
}
