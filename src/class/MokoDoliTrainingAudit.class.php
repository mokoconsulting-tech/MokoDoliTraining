<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Audit
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/class/MokoDoliTrainingAudit.class.php
 * VERSION:  01.00.04
 * BRIEF:    Audit log writer and reader for MokoDoliTraining operations.
 */

class MokoDoliTrainingAudit
{
	private $db;

	const ACTIONS = [
		'install'            => 'Install Training Records',
		'seed'               => 'Seed',
		'reset_snapshot'     => 'Reset to Snapshot',
		'rollback'           => 'Rollback',
		'backup_create'      => 'Backup Created',
		'backup_restore'     => 'Backup Restored',
		'backup_delete'      => 'Backup Deleted',
		'backup_download'    => 'Backup Downloaded',
		'backup_purge'       => 'Backup Purge',
		'integrity_check'    => 'Integrity Check',
		'settings_save'      => 'Settings Saved',
		'auto_snapshot'      => 'Auto Snapshot',
		'uninstall_rollback' => 'Uninstall Rollback',
	];

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function log(
		int    $fk_user,
		string $action,
		string $status,
		int    $rows_affected = 0,
		int    $statements    = 0,
		int    $duration_ms   = 0,
		string $backup_file   = '',
		string $checksum      = '',
		array  $errors        = [],
		string $note          = '',
		int    $entity        = 1
	): bool {
		$sql = "INSERT INTO `" . MAIN_DB_PREFIX . "mokodolitraining_log`
			(`entity`,`datec`,`fk_user`,`action`,`status`,`rows_affected`,`statements`,
			 `backup_file`,`checksum`,`duration_ms`,`errors`,`note`)
			VALUES (
				" . (int) $entity . ",
				'" . $this->db->escape(gmdate('Y-m-d H:i:s')) . "',
				" . (int) $fk_user . ",
				'" . $this->db->escape($action) . "',
				'" . $this->db->escape($status) . "',
				" . (int) $rows_affected . ",
				" . (int) $statements . ",
				" . ($backup_file ? "'" . $this->db->escape(basename($backup_file)) . "'" : 'NULL') . ",
				" . ($checksum    ? "'" . $this->db->escape($checksum) . "'"            : 'NULL') . ",
				" . (int) $duration_ms . ",
				" . (!empty($errors) ? "'" . $this->db->escape(implode(' | ', $errors)) . "'" : 'NULL') . ",
				" . ($note ? "'" . $this->db->escape($note) . "'" : 'NULL') . "
			)";
		return (bool) $this->db->query($sql);
	}

	public function getRecent(int $limit = 50, int $entity = 1): array
	{
		$res = $this->db->query(
			"SELECT l.*, u.login
			 FROM `" . MAIN_DB_PREFIX . "mokodolitraining_log` l
			 LEFT JOIN `" . MAIN_DB_PREFIX . "user` u ON u.rowid = l.fk_user
			 WHERE l.entity = " . (int) $entity . "
			 ORDER BY l.datec DESC
			 LIMIT " . (int) $limit
		);
		if (!$res) return [];
		$rows = [];
		while ($obj = $this->db->fetch_object($res)) {
			$rows[] = (array) $obj;
		}
		return $rows;
	}

	public function purgeOlderThan(int $days, int $entity = 1): int
	{
		$cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
		$res = $this->db->query(
			"DELETE FROM `" . MAIN_DB_PREFIX . "mokodolitraining_log`
			 WHERE entity = " . (int) $entity . "
			 AND datec < '" . $this->db->escape($cutoff) . "'"
		);
		return $res ? $this->db->affected_rows($res) : 0;
	}

	public function getStats(int $entity = 1): array
	{
		$res = $this->db->query(
			"SELECT action, status, COUNT(*) as cnt
			 FROM `" . MAIN_DB_PREFIX . "mokodolitraining_log`
			 WHERE entity = " . (int) $entity . "
			 GROUP BY action, status"
		);
		if (!$res) return [];
		$stats = [];
		while ($obj = $this->db->fetch_object($res)) {
			$stats[] = (array) $obj;
		}
		return $stats;
	}
}
