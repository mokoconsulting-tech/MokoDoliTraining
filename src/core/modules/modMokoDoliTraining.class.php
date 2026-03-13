<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * DEFGROUP: MokoDoliTraining.Module
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/core/modules/modMokoDoliTraining.class.php
 * VERSION:  01.00.00
 * BRIEF:    Dolibarr module descriptor for MokoDoliTraining.
 * NOTE:     Module ID 185068. Registers cron, triggers, and 8 constants.
 */

class modMokoDoliTraining extends DolibarrModules
{
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		$this->numero               = 185068;
		$this->rights_class         = 'mokodolitraining';
		$this->module_position      = 02;
		$this->name                 = preg_replace('/^mod/i', '', get_class($this));
		$this->description          = $langs->trans('MokoDoliTrainingDescription');
		$this->family = 'mokoconsulting';
		$this->familyinfo = array(
			'mokoconsulting' => array(
				'position' => '00',
				'label' => $langs->trans("Moko Consulting")
			)
		);
		$this->editor_name = 'Moko Consulting';
		$this->editor_url = 'https://mokoconsulting.tech';
		$this->editor_squarred_logo = 'favicon_256.png@' . $this->rights_class;
		$this->version              = 'development';
		$this->const_name           = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto                = 'technic';

		$this->module_parts = [
			'triggers'         => 1,
			'login'            => 0,
			'substitutions'    => 0,
			'menus'            => 0,
			'tpl'              => 0,
			'barcode'          => 0,
			'models'           => 0,
			'theme'            => 0,
			'css'              => [],
			'js'               => [],
			'hooks'            => [],
			'moduleforexternal'=> 0,
		];

		$this->config_page_url = ['setup.php@mokodolitraining'];
		$this->hidden          = false;
		$this->depends         = [];
		$this->requiredby      = [];
		$this->conflictwith    = [];
		$this->langfiles       = ['mokodolitraining@mokodolitraining'];
		$this->rights          = [];
		$this->tabs            = [];

		// ── Constants ────────────────────────────────────────────────────────
		$this->const = [
			// Dataset state
			['MOKODOLITRAINING_VERSION',       'chaine', '1.0.0', 'Installed dataset version',                 0, 'current'],
			['MOKODOLITRAINING_SEEDED',        'chaine', '0',     '1 when training data is currently loaded',  0, 'current'],
			['MOKODOLITRAINING_SEED_DATE',     'chaine', '',      'Timestamp of last successful seed',         0, 'current'],
			['MOKODOLITRAINING_RESET_DATE',    'chaine', '',      'Timestamp of last reset or rollback',       0, 'current'],
			// Backup paths
			['MOKODOLITRAINING_ROLLBACK_FILE', 'chaine', '',      'Absolute path to latest rollback backup',   0, 'current'],
			['MOKODOLITRAINING_SNAPSHOT_FILE', 'chaine', '',      'Absolute path to latest snapshot backup',   0, 'current'],
			// Operational config
			['MOKODOLITRAINING_MAX_BACKUPS',   'chaine', '10',    'Max backup files retained per label type',  0, 'current'],
			['MOKODOLITRAINING_LOG_RETENTION', 'chaine', '90',    'Audit log retention in days',               0, 'current'],
		];

		// ── Cron ─────────────────────────────────────────────────────────────
		$this->cronjobs = [
			[
				'label'         => 'MokoDoliTraining - Reset to training snapshot',
				'jobtype'       => 'method',
				'class'         => '/mokodolitraining/cron/MokoDoliTrainingCron.class.php',
				'objectname'    => 'MokoDoliTrainingCron',
				'method'        => 'resetToSnapshot',
				'parameters'    => '',
				'comment'       => 'Deletes all training rows and restores from the latest snapshot backup. Run on a schedule to keep the demo instance in a clean state.',
				'frequency'     => 1,
				'unitfrequency' => 86400,
				'priority'      => 50,
				'datestart'     => 0,
				'dateend'       => 0,
				'autodelete'    => 0,
				'status'        => 0,
			],
			[
				'label'         => 'MokoDoliTraining - Backup rotation and log purge',
				'jobtype'       => 'method',
				'class'         => '/mokodolitraining/cron/MokoDoliTrainingCron.class.php',
				'objectname'    => 'MokoDoliTrainingCron',
				'method'        => 'rotateAndPurge',
				'parameters'    => '',
				'comment'       => 'Enforces backup retention limits and purges old audit log entries.',
				'frequency'     => 1,
				'unitfrequency' => 86400,
				'priority'      => 60,
				'datestart'     => 0,
				'dateend'       => 0,
				'autodelete'    => 0,
				'status'        => 0,
			],
		];
	}

	// ── Lifecycle ─────────────────────────────────────────────────────────────

	public function init($options = ''): int
	{
		// Create tables first
		$this->_load_tables('/custom/mokodolitraining/sql/');

		// Ensure backup directory exists and is protected
		$backup_dir = dirname(__DIR__, 2) . '/backup';
		if (!is_dir($backup_dir)) {
			mkdir($backup_dir, 0750, true);
		}
		$htaccess = $backup_dir . '/.htaccess';
		if (!file_exists($htaccess)) {
			file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
		}

		// Re-activate all modules BEFORE _init() so any schema alterations
		// triggered by other modules' init() run first. This ensures all
		// tables are in their correct state before we take the rollback snapshot.
		$this->_reactivateAllModules();

		// Take a pre-seed rollback snapshot now that schema is fully built.
		$this->_autoSnapshot();

		// Run our own activation chain (registers constants, triggers, menus)
		$result = $this->_init([], $options);
		if ($result <= 0) return $result;

		return 1;
	}

	/**
	 * Take an automatic rollback snapshot on install so the database state
	 * is captured before any training data is seeded.
	 */
	private function _autoSnapshot(): void
	{
		global $conf, $user;

		require_once dirname(__DIR__, 2) . '/class/MokoDoliTrainingBackup.class.php';
		require_once dirname(__DIR__, 2) . '/class/MokoDoliTrainingAudit.class.php';

		$max    = max(2, (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10));
		$backup = new MokoDoliTrainingBackup($this->db, $max);
		$audit  = new MokoDoliTrainingAudit($this->db);

		if ($backup->isLocked() || !$backup->acquireLock()) return;

		$rb = $backup->createFullBackup('rollback');
		if (!empty($rb['path'])) {
			dolibarr_set_const($this->db, 'MOKODOLITRAINING_ROLLBACK_FILE', $rb['path'], 'chaine', 0, '', (int) $conf->entity);
		}
		$audit->log((int) ($user->id ?? 0), 'auto_snapshot', empty($rb['errors']) ? 'ok' : 'partial',
			$rb['rows'] ?? 0, 0, 0, $rb['path'] ?? '', $rb['checksum'] ?? '', $rb['errors'] ?? [],
			entity: (int) $conf->entity);

		$backup->releaseLock();
	}

	public function remove($options = ''): int
	{
		global $conf, $user;

		require_once DOL_DOCUMENT_ROOT . '/custom/mokodolitraining/class/MokoDoliTrainingBackup.class.php';
		require_once DOL_DOCUMENT_ROOT . '/custom/mokodolitraining/class/MokoDoliTrainingAudit.class.php';

		$max    = max(2, (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10));
		$backup = new MokoDoliTrainingBackup($this->db, $max);
		$audit  = new MokoDoliTrainingAudit($this->db);
		$uid    = isset($user) ? (int) $user->id : 0;
		$entity = isset($conf) ? (int) $conf->entity : 1;

		// 1. Restore rollback backup to return DB to pre-training state
		$rb_path = $backup->getLatest('rollback') ?: getDolGlobalString('MOKODOLITRAINING_ROLLBACK_FILE');
		if ($rb_path && file_exists($rb_path) && $backup->acquireLock()) {
			$backup->runReset();
			$res = $backup->restoreFromFile($rb_path);
			$audit->log($uid, 'uninstall_rollback', empty($res['errors']) ? 'ok' : 'partial',
				$res['ok'], 0, 0, $rb_path, errors: $res['errors'], entity: $entity);
			$backup->releaseLock();
		}

		// 2. Drop module log table
		$this->db->query('DROP TABLE IF EXISTS ' . MAIN_DB_PREFIX . 'mokodolitraining_log');

		// 3. Standard Dolibarr uninstall (removes constants, menus, rights)
		return $this->_remove([], $options);
	}

	/**
	 * Re-run init() on every currently active module to restore their menu
	 * entries after Dolibarr wipes the menu table during any module activation.
	 */
	private function _reactivateAllModules(): void
	{
		global $conf;

		$res = $this->db->query(
			"SELECT name FROM llx_const
			 WHERE name LIKE 'MAIN_MODULE_%'
			 AND value = '1'
			 AND entity IN (0, " . (int) $conf->entity . ")"
		);
		if (!$res) return;

		$htdocs = DOL_DOCUMENT_ROOT;

		while ($obj = $this->db->fetch_object($res)) {
			// Derive module name from constant: MAIN_MODULE_FOO -> modFoo
			$mod_upper = preg_replace('/^MAIN_MODULE_/', '', $obj->name);
			$mod_name  = 'mod' . ucfirst(strtolower($mod_upper));

			// Skip ourselves to avoid recursion
			if (strtolower($mod_name) === 'modmokodolitraining') continue;

			// Search core then custom for the descriptor file
			$candidates = [
				$htdocs . '/core/modules/' . $mod_name . '.class.php',
				$htdocs . '/custom/' . strtolower($mod_upper) . '/core/modules/' . $mod_name . '.class.php',
			];

			$found = '';
			foreach ($candidates as $c) {
				if (file_exists($c)) { $found = $c; break; }
			}
			if (!$found) continue;

			require_once $found;
			if (!class_exists($mod_name)) continue;

			$mod = new $mod_name($this->db);
			if (method_exists($mod, '_init')) {
				$mod->_init([], '');
			}
		}
	}
}
