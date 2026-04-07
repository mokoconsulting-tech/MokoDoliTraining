<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Cron
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/cron/MokoDoliTrainingCron.class.php
 * VERSION:  01.00.02
 * BRIEF:    Scheduled job: enforce backup retention and purge old audit logs.
 */

class MokoDoliTrainingCron
{
	public $db;
	public string $error  = '';
	public string $output = '';

	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Reset training data and restore from the latest snapshot backup.
	 * Called by the Dolibarr cron engine to keep demo instances clean.
	 *
	 * @return int 0 on success, 1 on error
	 */
	public function resetToSnapshot(): int
	{
		global $conf;

		dol_include_once('/mokodolitraining/class/MokoDoliTrainingBackup.class.php');
		dol_include_once('/mokodolitraining/class/MokoDoliTrainingAudit.class.php');

		$max_backups = max(2, (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10));

		$backup = new MokoDoliTrainingBackup($this->db, $max_backups);
		$audit  = new MokoDoliTrainingAudit($this->db);
		$entity = (int) $conf->entity;

		$snapshot = $backup->getLatest('snapshot');
		if (!$snapshot) {
			$this->error  = 'No snapshot backup found.';
			$this->output = $this->error;
			$audit->log(0, 'reset_snapshot', 'error', note: $this->error, entity: $entity);
			return 1;
		}

		if (!$backup->acquireLock()) {
			$this->error  = 'Could not acquire lock -- another operation is in progress.';
			$this->output = $this->error;
			return 1;
		}

		$t0     = hrtime(true);
		$reset  = $backup->runReset();
		$res    = $backup->restoreFromFile($snapshot);
		$ms     = (int) ((hrtime(true) - $t0) / 1e6);
		$errors = array_merge($reset['errors'] ?? [], $res['errors'] ?? []);

		$status = empty($errors) ? 'ok' : 'partial';
		dolibarr_set_const($this->db, 'MOKODOLITRAINING_RESET_DATE', dol_now(), 'chaine', 0, '', $entity);

		$audit->log(
			fk_user:      0,
			action:       'reset_snapshot',
			status:       $status,
			rows_affected: $res['ok'] ?? 0,
			duration_ms:  $ms,
			backup_file:  $snapshot,
			errors:       $errors,
			entity:       $entity
		);

		$backup->releaseLock();

		$this->output = "Reset to snapshot: {$status} -- " . ($res['ok'] ?? 0) . " statements, {$ms}ms.";
		if (!empty($errors)) {
			$this->output .= ' Errors: ' . implode(' | ', $errors);
		}
		return empty($errors) ? 0 : 1;
	}

	/**
	 * Entry point called by the Dolibarr cron engine.
	 * Enforces backup retention for rollback and snapshot types,
	 * then purges audit log entries older than the configured retention period.
	 *
	 * @return int 0 on success, 1 on error
	 */
	public function rotateAndPurge(): int
	{
		global $conf;

		dol_include_once('/mokodolitraining/class/MokoDoliTrainingBackup.class.php');
		dol_include_once('/mokodolitraining/class/MokoDoliTrainingAudit.class.php');

		$max_backups = max(2, (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10));
		$log_days    = max(7,  (int) (getDolGlobalString('MOKODOLITRAINING_LOG_RETENTION') ?: 90));

		$backup = new MokoDoliTrainingBackup($this->db, $max_backups);
		$audit  = new MokoDoliTrainingAudit($this->db);

		$lines   = [];
		$has_err = false;

		// Rotate rollback backups
		$purged_rb = $backup->purgeByType('rollback', $max_backups);
		$lines[] = "Rollback rotation: {$purged_rb} file(s) removed (keeping {$max_backups}).";

		// Rotate snapshot backups
		$purged_sn = $backup->purgeByType('snapshot', $max_backups);
		$lines[] = "Snapshot rotation: {$purged_sn} file(s) removed (keeping {$max_backups}).";

		// Purge old audit log rows
		$purged_log = $audit->purgeOlderThan($log_days, (int) $conf->entity);
		$lines[] = "Audit log: {$purged_log} row(s) purged (retention: {$log_days} days).";

		// Log this cron run
		$audit->log(
			fk_user: 0,
			action:  'backup_purge',
			status:  $has_err ? 'partial' : 'ok',
			note:    implode(' | ', $lines),
			entity:  (int) $conf->entity
		);

		$this->output = implode("\n", $lines);
		return $has_err ? 1 : 0;
	}
}
