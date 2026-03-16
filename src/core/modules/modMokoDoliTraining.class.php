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
 * VERSION:  development
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
		$this->version              = '1.0.0';
		$this->requires_dolibarr    = '23.0.0';
		$this->compatible           = '24.0.0';
		$this->url_last_version     = 'https://raw.githubusercontent.com/mokoconsulting-tech/MokoDoliTraining/main/module_version.txt';
		$this->const_name           = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto                = 'technic';

		$this->module_parts = [
			'triggers'         => 1,
			'login'            => 0,
			'substitutions'    => 0,
			'menus'            => 1,
			'tpl'              => 0,
			'barcode'          => 0,
			'models'           => 0,
			'theme'            => 0,
			// Tour engine — loaded on every page; zero-cost no-op when no tour active
			'css'              => ['/mokodolitraining/css/mokodolitraining-tour.css'],
			'js'               => ['/mokodolitraining/js/mokodolitraining-tour.js'],
			'hooks'            => [],
			'moduleforexternal'=> 0,
		];

		// ── Module menu ──────────────────────────────────────────────────────
		// "Exercises" top-menu entry — visible to all authenticated users with
		// read permission so trainees can launch walkthroughs directly.
		$this->menu[0] = [
			'fk_menu'  => 0,
			'type'     => 'top',
			'titre'    => $langs->trans('TabExercises'),
			'mainmenu' => 'mokodolitraining',
			'leftmenu' => 'mokodolitraining_exercises',
			'url'      => '/mokodolitraining/admin/exercise.php',
			'langs'    => 'mokodolitraining@mokodolitraining',
			'position' => 101,
			'enabled'  => '$conf->mokodolitraining->enabled',
			'perms'    => '$user->hasRight("mokodolitraining","read") || !empty($user->admin)',
			'target'   => '',
			'user'     => 2, // 2 = non-admin users (admins always see it)
		];

		$this->config_page_url = ['setup.php@mokodolitraining'];
		$this->hidden          = false;
		$this->depends         = [];
		$this->requiredby      = [];
		$this->conflictwith    = [];
		$this->langfiles       = ['mokodolitraining@mokodolitraining'];
		$this->tabs            = [];

		// ── Tables ───────────────────────────────────────────────────────────
		// Registers this module's tables for Dolibarr's DB diagnostic tools.
		$this->tables = [
			'mokodolitraining_log',
			'mokodolitraining_manifest',
			'mokodolitraining_class',
			'mokodolitraining_class_user',
			'mokodolitraining_user_track',
		];

		// ── Rights ───────────────────────────────────────────────────────────
		// IDs: module_numero(185068) * 100 + index → 18506801..18506804
		// Accessed as $user->hasRight('mokodolitraining', 'read|reset|manage|teach')
		$this->rights = [
			// [unique_id, label, type, default, right_key]
			[18506801, $langs->transnoentities('PermRead'),   'r', 0, 'read'],
			[18506802, $langs->transnoentities('PermReset'),  'w', 0, 'reset'],
			[18506803, $langs->transnoentities('PermManage'), 'w', 0, 'manage'],
			[18506804, $langs->transnoentities('PermTeach'),  'w', 0, 'teach'],
		];

		// ── Constants ────────────────────────────────────────────────────────
		$this->const = [
			// Dataset state
			['MOKODOLITRAINING_VERSION',        'chaine', '1.0.0',    'Installed dataset version',                   0, 'current'],
			['MOKODOLITRAINING_SEEDED',         'chaine', '0',        '1 when dataset is currently loaded',          0, 'current'],
			['MOKODOLITRAINING_SEED_MODE',      'chaine', 'training', 'Active seed mode: training or demo',          0, 'current'],
			['MOKODOLITRAINING_SEED_DATE',      'chaine', '',         'Timestamp of last successful seed',           0, 'current'],
			['MOKODOLITRAINING_RESET_DATE',     'chaine', '',         'Timestamp of last reset or rollback',         0, 'current'],
			// Backup rowids (DB-stored, no filesystem paths)
			['MOKODOLITRAINING_ROLLBACK_ROWID', 'chaine', '',         'DB rowid of latest rollback backup',          0, 'current'],
			['MOKODOLITRAINING_SNAPSHOT_ROWID', 'chaine', '',         'DB rowid of latest snapshot backup',          0, 'current'],
			// Operational config
			['MOKODOLITRAINING_MAX_BACKUPS',    'chaine', '10',       'Max backup records retained per label type',  0, 'current'],
			['MOKODOLITRAINING_LOG_RETENTION',  'chaine', '90',       'Audit log retention in days',                 0, 'current'],
			// T18 dynamic seed IDs (stored at seed time, used by resyncManifest)
			['MOKODOLITRAINING_T18_ORDER_IDS',  'chaine', '',         'JSON array of seeded llx_commande rowids',    0, 'current'],
			['MOKODOLITRAINING_T18_DET_IDS',    'chaine', '',         'JSON array of seeded llx_commandedet rowids', 0, 'current'],
		];

		// ── Cron ─────────────────────────────────────────────────────────────
		$this->cronjobs = [
			[
				'label'         => 'MokoDoliTraining - Reset to snapshot',
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
		if (!empty($rb['rowid'])) {
			dolibarr_set_const($this->db, 'MOKODOLITRAINING_ROLLBACK_ROWID', (string) $rb['rowid'], 'chaine', 0, '', (int) $conf->entity);
		}
		$audit->log((int) ($user->id ?? 0), 'auto_snapshot', empty($rb['errors']) ? 'ok' : 'partial',
			$rb['rows'] ?? 0, 0, 0, 'rowid:' . ($rb['rowid'] ?? 0), $rb['checksum'] ?? '', $rb['errors'] ?? [],
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
		$rb_rowid = $backup->getLatest('rollback') ?: (int) getDolGlobalString('MOKODOLITRAINING_ROLLBACK_ROWID');
		if ($rb_rowid && $backup->acquireLock()) {
			$backup->runReset();
			$res = $backup->restoreById($rb_rowid);
			$audit->log($uid, 'uninstall_rollback', empty($res['errors']) ? 'ok' : 'partial',
				$res['ok'], 0, 0, 'rowid:' . $rb_rowid, errors: $res['errors'], entity: $entity);
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
	 * Uses dolGetModulesDirs() to discover module descriptors with correct casing.
	 */
	private function _reactivateAllModules(): void
	{
		require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

		$modulesdir = dolGetModulesDirs();

		foreach ($modulesdir as $dir) {
			$files = @scandir($dir);
			if (!$files) continue;

			foreach ($files as $file) {
				if (!preg_match('/^(mod.+)\.class\.php$/i', $file, $m)) continue;

				$mod_name  = $m[1];
				$mod_upper = preg_replace('/^mod/i', '', $mod_name);
				$const     = 'MAIN_MODULE_' . strtoupper($mod_upper);

				// Skip ourselves to avoid recursion
				if (strtolower($mod_name) === 'modmokodolitraining') continue;

				// Only re-init modules that are currently active
				if (!getDolGlobalString($const)) continue;

				require_once $dir . $file;
				if (!class_exists($mod_name)) continue;

				$mod = new $mod_name($this->db);
				if (method_exists($mod, '_init')) {
					$mod->_init([], '');
				}
			}
		}
	}
}
