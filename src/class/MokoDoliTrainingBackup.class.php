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
 * VERSION:  01.00.04
 * BRIEF:    Backup, snapshot, restore, integrity, and retention manager.
 *
 * NOTE: Backup files use the .php extension and open with a die() guard so
 *       they cannot be downloaded via HTTP even without .htaccess.
 *       Full (rollback) backups delegate to Utils::dumpDatabase (mysqlnobin):
 *       DROP TABLE IF EXISTS + CREATE TABLE + batched INSERT per table.
 *       Snapshot backups are manifest-scoped INSERT...ON DUPLICATE KEY UPDATE.
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

	// PHP die() header prepended to every backup file.
	// Prevents HTTP download even if .htaccess is missing or misconfigured.
	const PHP_GUARD = "<?php die('No direct access.'); ?>\n";

	// Junction tables that use fk_categorie instead of rowid as manifest PK.
	// Keys use bare names (no prefix) -- loadManifest() strips the prefix.
	const JUNCTION = [
		'categorie_societe' => 'fk_categorie',
		'categorie_product' => 'fk_categorie',
	];

	const LOCK_TTL = 300; // seconds before a stale .lock file is broken

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
			dol_mkdir($this->backup_dir);
		}
		// Break stale locks older than LOCK_TTL
		if (file_exists($this->lock_file)) {
			$age = time() - (int) file_get_contents($this->lock_file);
			if ($age < self::LOCK_TTL) return false;
			@unlink($this->lock_file);
		}
		// Atomic create -- 'x' flag fails if file already exists
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
	// Dolibarr's mysqlnobin path outputs:
	//   DROP TABLE IF EXISTS `tbl`;
	//   CREATE TABLE `tbl` (...);
	//   LOCK TABLES `tbl` WRITE;
	//   INSERT INTO `tbl` VALUES (...),(...);
	//   UNLOCK TABLES;
	//
	// We delegate to Utils::dumpDatabase(), then:
	//   1. Prepend PHP_GUARD (die header -- HTTP download protection)
	//   2. Prepend our module comment header with SHA256 placeholder
	//   3. Append body from Utils output
	//   4. Compute and embed SHA256; write .php.sha256 sidecar
	//   5. Remove the original .sql from Dolibarr's backup dir

	public function createFullBackup(string $label): array
	{
		global $conf;
		$errors = [];

		if (!is_dir($this->backup_dir)) {
			if (!mkdir($this->backup_dir, 0750, true)) {
				return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => [
					'Could not create backup directory: ' . $this->backup_dir,
				]];
			}
		}

		if (!is_writable($this->backup_dir)) {
			return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => [
				'Backup directory is not writable: ' . $this->backup_dir,
			]];
		}

		require_once DOL_DOCUMENT_ROOT . '/core/class/utils.class.php';
		$utils = new Utils($this->db);

		$ts          = gmdate('Ymd_His');
		$sql_file    = $label . '_' . $ts . '.sql';   // temp name for Utils output
		$dest_file   = $label . '_' . $ts . '.php';   // final PHP-wrapped file
		$dol_dir     = $conf->admin->dir_output . '/backup';
		$dest        = $this->backup_dir . '/' . $dest_file;

		// dumpDatabase(compression, type, usedefault, file, keeplastnfiles, execmethod)
		// usedefault=0 -- do not read GET/POST; keeplastnfiles=0 -- we handle retention.
		$ret = $utils->dumpDatabase('none', 'mysqlnobin', 0, $sql_file, 0, 0);

		if ($ret < 0) {
			$errors[] = 'Utils::dumpDatabase failed: ' . $utils->error;
			return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => $errors];
		}

		$src = $dol_dir . '/' . $sql_file;
		if (!file_exists($src)) {
			$errors[] = 'Dump file not found after dumpDatabase: ' . $src;
			return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => $errors];
		}

		$body = file_get_contents($src);

		// Build the full file: PHP guard + module header + Dolibarr dump body
		$header = implode("\n", [
			'-- MokoDoliTraining FULL backup: ' . $label . ' | ' . gmdate('c'),
			'-- Module ID: 185068 | Utils::dumpDatabase (mysqlnobin) | DO NOT EDIT',
			'-- SHA256: {CHECKSUM}',
			'',
		]);

		$content  = self::PHP_GUARD . $header . $body;
		$checksum = hash('sha256', $content);
		$content  = str_replace('{CHECKSUM}', $checksum, $content);

		if (file_put_contents($dest, $content) === false) {
			$errors[] = 'Failed to write backup file: ' . $dest;
			@unlink($src);
			return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => $errors];
		}

		if (file_put_contents($dest . '.sha256', $checksum) === false) {
			$errors[] = 'Failed to write checksum sidecar: ' . $dest . '.sha256';
		}

		@unlink($src); // remove Utils temp output; we own the final file now

		$rows = $this->countDumpRows($dest);
		$this->enforceRetention($label);

		return ['path' => $dest, 'rows' => $rows, 'checksum' => $checksum, 'errors' => $errors];
	}

	// Approximate row count from the PHP-wrapped dump (for display only)
	private function countDumpRows(string $path): int
	{
		$count = 0;
		$fh    = fopen($path, 'r');
		if (!$fh) return 0;
		while (($line = fgets($fh)) !== false) {
			if (stripos($line, 'INSERT INTO') === 0) {
				$count += substr_count($line, '),(') + 1;
			}
		}
		fclose($fh);
		return $count;
	}

	// ── Manifest-scoped snapshot (INSERT ... ON DUPLICATE KEY UPDATE) ──────────

	public function createBackup(string $label): array
	{
		$errors = [];

		if (!is_dir($this->backup_dir)) {
			if (!mkdir($this->backup_dir, 0750, true)) {
				return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => [
					'Could not create backup directory: ' . $this->backup_dir,
				]];
			}
		}

		if (!is_writable($this->backup_dir)) {
			return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => [
				'Backup directory is not writable: ' . $this->backup_dir,
			]];
		}

		$manifest = $this->loadManifest();
		if (empty($manifest)) {
			return ['path' => '', 'rows' => 0, 'checksum' => '', 'errors' => [
				'Manifest not found or empty: ' . $this->manifest_path,
			]];
		}

		$ts   = gmdate('Ymd_His');
		$path = $this->backup_dir . '/' . $label . '_' . $ts . '.php';

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

		$content  = self::PHP_GUARD . implode("\n", $sql_lines);
		$checksum = hash('sha256', $content);
		$content  = str_replace('{CHECKSUM}', $checksum, $content);

		if (file_put_contents($path, $content) === false) {
			$errors[] = 'Failed to write snapshot file: ' . $path;
			return ['path' => '', 'rows' => $total, 'checksum' => '', 'errors' => $errors];
		}

		if (file_put_contents($path . '.sha256', $checksum) === false) {
			$errors[] = 'Failed to write checksum sidecar: ' . $path . '.sha256';
		}

		$this->enforceRetention($label);

		return ['path' => $path, 'rows' => $total, 'checksum' => $checksum, 'errors' => $errors];
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

	// ── Integrity ─────────────────────────────────────────────────────────────

	public function verifyIntegrity(string $path): array
	{
		if (!file_exists($path)) {
			return ['ok' => false, 'reason' => 'File not found'];
		}

		$content  = file_get_contents($path);
		$sha_file = $path . '.sha256';
		$stored   = file_exists($sha_file) ? trim(file_get_contents($sha_file)) : null;

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
				'reason' => 'Checksum mismatch -- file may be corrupted or tampered',
				'stored' => $expected,
				'actual' => $actual,
			];
		}

		return ['ok' => true, 'checksum' => $actual];
	}

	// ── Restore ───────────────────────────────────────────────────────────────

	public function restoreFromFile(string $path): array
	{
		$integrity = $this->verifyIntegrity($path);
		if (!$integrity['ok']) {
			return ['ok' => 0, 'errors' => ['Integrity check failed: ' . $integrity['reason']]];
		}
		return $this->execSqlFile($path);
	}

	// ── SQL execution ─────────────────────────────────────────────────────────
	//
	// Reads a .php backup file and executes every SQL statement it contains.
	// The PHP_GUARD line is skipped automatically -- it is not valid SQL and
	// does not end with a semicolon, so the accumulator never fires on it.
	// SQL comments (--) are also skipped.
	// Conditional comments from Utils::dumpDatabase are sent to db->query()
	// as-is; MySQL executes them when server version >= NNN.

	public function execSqlFile(string $path): array
	{
		if (!file_exists($path)) {
			return ['ok' => 0, 'errors' => ['File not found: ' . basename($path)]];
		}

		$ok       = 0;
		$errors   = [];
		$stmt     = '';
		$in_delim = false;

		$this->db->begin();

		foreach (file($path) as $raw_line) {
			$t = trim($raw_line);

			if ($t === '')                           continue; // blank
			if (str_starts_with($t, '--'))           continue; // SQL comment
			if (str_starts_with($t, '<?php'))        continue; // PHP die guard
			if (preg_match('/^DELIMITER\s+/i', $t))  { $in_delim = !$in_delim; continue; }
			if ($in_delim)                           continue;

			$stmt .= ' ' . $t;

			if (str_ends_with(rtrim($t), ';')) {
				$clean = trim($stmt);
				// Replace default llx_ prefix with configured MAIN_DB_PREFIX
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

	public function runSeed(): array  { return $this->execSqlFile($this->seed_sql);  }
	public function runReset(): array { return $this->execSqlFile($this->reset_sql); }

	// ── Retention ─────────────────────────────────────────────────────────────

	private function enforceRetention(string $label): void
	{
		$files = glob($this->backup_dir . '/' . $label . '_*.php') ?: [];
		$files = array_filter($files, fn($f) => !str_ends_with($f, '.sha256'));
		usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
		foreach (array_slice($files, $this->max_backups) as $old) {
			@unlink($old);
			@unlink($old . '.sha256');
		}
	}

	public function purgeByType(string $type, int $keep = 0): int
	{
		$files = glob($this->backup_dir . '/' . $type . '_*.php') ?: [];
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

	// ── Listing / lookup ──────────────────────────────────────────────────────

	public function listBackups(): array
	{
		if (!is_dir($this->backup_dir)) return [];
		$files = glob($this->backup_dir . '/*.php') ?: [];
		// Exclude the stub index.php and any .sha256 sidecar files
		$files = array_filter($files, fn($f) => (
			!str_ends_with($f, '.sha256')
			&& basename($f) !== 'index.php'
		));
		$out = [];
		foreach ($files as $f) {
			$name = basename($f);
			preg_match('/^(rollback|snapshot)_(\d{8}_\d{6})\.php$/', $name, $m);
			$sha_path = $f . '.sha256';
			$out[] = [
				'path'     => $f,
				'name'     => $name,
				'type'     => $m[1] ?? 'unknown',
				'ts'       => isset($m[2])
					? substr($m[2], 0, 4) . '-' . substr($m[2], 4, 2) . '-' . substr($m[2], 6, 2)
					  . 'T' . substr($m[2], 9, 2) . ':' . substr($m[2], 11, 2) . ':' . substr($m[2], 13, 2) . 'Z'
					: '',
				'size'     => filesize($f),
				'mtime'    => filemtime($f),
				'checksum' => file_exists($sha_path) ? trim(file_get_contents($sha_path)) : null,
				'has_sha'  => file_exists($sha_path),
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
		return (file_exists($path) && str_ends_with($path, '.php')) ? $path : null;
	}

	public function getBackupDir(): string
	{
		return $this->backup_dir;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function loadManifest(): array
	{
		if (!file_exists($this->manifest_path)) return [];
		$raw = json_decode(file_get_contents($this->manifest_path), true);
		$tables = is_array($raw['tables'] ?? null) ? $raw['tables'] : [];

		// Replace llx_ prefix in manifest keys with the configured MAIN_DB_PREFIX
		$prefix = MAIN_DB_PREFIX;
		if ($prefix !== 'llx_') {
			$out = [];
			foreach ($tables as $tbl => $rowids) {
				$out[preg_replace('/^llx_/', $prefix, $tbl)] = $rowids;
			}
			return $out;
		}
		return $tables;
	}
}
