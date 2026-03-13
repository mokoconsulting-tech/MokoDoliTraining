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
 * PATH:     /core/modules/modMokoDoliTraining.class.php
 * VERSION:  01.00.00
 * BRIEF:    Dolibarr module descriptor for MokoDoliTraining demo/training dataset.
 * NOTE:     Module ID 185068. Tracks all inserted rowids for reset and teardown.
 */

/**
 * \defgroup   mokodolitraining    MokoDoliTraining
 * \brief       Demo and training dataset manager for Dolibarr.
 */

/**
 * Class modMokoDoliTraining
 *
 * Module descriptor for the MokoDoliTraining demo dataset.
 * Tracks all rowids inserted by mokotraining.sql and provides
 * reset and teardown SQL via the admin interface.
 */
class modMokoDoliTraining extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Module identifiers
		$this->numero          = 185068;
		$this->rights_class    = 'mokodolitraining';
		$this->family          = 'mokoconsulting';
		$this->familyinfo      = [
			'mokoconsulting' => [
				'position' => '01',
				'label'    => $langs->trans('Moko Consulting'),
			],
		];
		$this->module_position = 500;
		$this->name            = preg_replace('/^mod/i', '', get_class($this));
		$this->description     = 'Demo and training dataset manager for Dolibarr v23+. Tracks all inserted rowids and provides one-click reset.';
		$this->editor_name           = 'Moko Consulting';
		$this->editor_url            = 'https://mokoconsulting.tech';
		$this->editor_squarred_logo  = 'favicon_256@mokodolitraining';
		$this->version         = '1.0.0';
		$this->const_name      = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto           = 'technic';

		// Module parts
		$this->module_parts = [
			'triggers'   => 0,
			'login'      => 0,
			'substitutions' => 0,
			'menus'      => 0,
			'tpl'        => 0,
			'barcode'    => 0,
			'models'     => 0,
			'theme'      => 0,
			'css'        => [],
			'js'         => [],
			'hooks'      => [],
			'moduleforexternal' => 0,
		];

		// Admin page
		$this->config_page_url = ['setup.php@mokodolitraining'];

		// Dependencies
		$this->hidden          = false;
		$this->depends         = [];
		$this->requiredby      = [];
		$this->conflictwith    = [];
		$this->langfiles       = ['mokodolitraining@mokodolitraining'];

		// Permissions
		$this->rights          = [];
		$this->menus           = [];
	}

	/**
	 * Return the rowid manifest for all tables inserted by mokotraining.sql.
	 *
	 * Keys are table names (without llx_ prefix by convention but stored with it).
	 * Values are sorted arrays of integer rowids.
	 *
	 * @return array<string,int[]>
	 */
	public static function getManifest(): array
	{
		$manifest_path = dirname(__DIR__, 2) . '/sql/manifest.json';
		if (!file_exists($manifest_path)) {
			return [];
		}
		$raw = json_decode(file_get_contents($manifest_path), true);
		return is_array($raw['tables'] ?? null) ? $raw['tables'] : [];
	}

	/**
	 * Return the path to the seed SQL file.
	 *
	 * @return string
	 */
	public static function getSeedSqlPath(): string
	{
		return dirname(__DIR__, 2) . '/sql/mokotraining.sql';
	}

	/**
	 * Return the path to the reset SQL file.
	 *
	 * @return string
	 */
	public static function getResetSqlPath(): string
	{
		return dirname(__DIR__, 2) . '/sql/mokotraining_reset.sql';
	}

	/**
	 * Return a summary of total tracked rows across all tables.
	 *
	 * @return array{tables: int, rows: int}
	 */
	public static function getManifestSummary(): array
	{
		$manifest = self::getManifest();
		return [
			'tables' => count($manifest),
			'rows'   => array_sum(array_map('count', $manifest)),
		];
	}

	/**
	 * Function called when module is enabled.
	 *
	 * @param string $options Options when enabling module ('', 'noboxes', 'newboxdefonly')
	 * @return int             1 if OK, 0 if KO
	 */
	public function init($options = ''): int
	{
		$result = $this->_load_tables('/mokodolitraining/sql/');
		return $result < 0 ? 0 : 1;
	}

	/**
	 * Function called when module is disabled.
	 *
	 * @param string $options Options when disabling module
	 * @return int             1 if OK, 0 if KO
	 */
	public function remove($options = ''): int
	{
		return 1;
	}
}
