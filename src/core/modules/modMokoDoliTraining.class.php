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
		$this->family               = 'mokoconsulting';
		$this->familyinfo           = [
			'mokoconsulting' => ['position' => '00', 'label' => $langs->trans('Moko Consulting')],
		];
		$this->module_position      = 02;
		$this->name                 = preg_replace('/^mod/i', '', get_class($this));
		$this->description          = $langs->trans('MokoDoliTrainingDescription');
		$this->editor_name          = 'Moko Consulting';
		$this->editor_url           = 'https://mokoconsulting.tech';
		$this->editor_squarred_logo = 'favicon_256@mokodolitraining';
		$this->version              = '1.0.0';
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
		$this->depends         = ['modMokoCRM'];
		$this->requiredby      = [];
		$this->conflictwith    = [];
		$this->langfiles       = ['mokodolitraining@mokodolitraining'];
		$this->rights          = [];
		$this->menus           = [];
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
				'label'        => 'MokoDoliTraining - Backup rotation and log purge',
				'jobtype'      => 'method',
				'class'        => '/mokodolitraining/cron/MokoDoliTrainingCron.class.php',
				'objectname'   => 'MokoDoliTrainingCron',
				'method'       => 'rotateAndPurge',
				'parameters'   => '',
				'comment'      => 'Enforces backup retention limits and purges old audit log entries.',
				'frequency'    => 1,
				'unitfrequency'=> 86400,
				'priority'     => 50,
				'datestart'    => 0,
				'dateend'      => 0,
				'autodelete'   => 0,
			],
		];
	}

	// ── Static helpers ────────────────────────────────────────────────────────

	public static function getManifest(): array
	{
		$path = dirname(__DIR__, 2) . '/sql/manifest.json';
		if (!file_exists($path)) return [];
		$raw = json_decode(file_get_contents($path), true);
		return is_array($raw['tables'] ?? null) ? $raw['tables'] : [];
	}

	public static function getSeedSqlPath(): string
	{
		return dirname(__DIR__, 2) . '/sql/mokotraining.sql';
	}

	public static function getResetSqlPath(): string
	{
		return dirname(__DIR__, 2) . '/sql/mokotraining_reset.sql';
	}

	public static function getManifestSummary(): array
	{
		$m = self::getManifest();
		return ['tables' => count($m), 'rows' => array_sum(array_map('count', $m))];
	}

	// ── Lifecycle ─────────────────────────────────────────────────────────────

	public function init($options = ''): int
	{
		$result = $this->_load_tables('/mokodolitraining/sql/');
		if ($result < 0) return 0;

		// Ensure backup directory exists and is protected
		$backup_dir = dirname(__DIR__, 2) . '/backup';
		if (!is_dir($backup_dir)) {
			mkdir($backup_dir, 0750, true);
		}
		$htaccess = $backup_dir . '/.htaccess';
		if (!file_exists($htaccess)) {
			file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
		}

		return 1;
	}

	public function remove($options = ''): int
	{
		return 1;
	}
}
