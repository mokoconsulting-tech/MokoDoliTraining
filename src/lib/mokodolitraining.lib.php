<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Lib
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/lib/mokodolitraining.lib.php
 * VERSION:  01.00.02
 * BRIEF:    Shared helpers: tab builder, formatters, class loader, constants accessor.
 */

/**
 * Build and return the Dolibarr tab head array for MokoDoliTraining admin pages.
 * Pass the result directly to dol_get_fiche_head().
 *
 * @return array  Array of tab definitions for dol_get_fiche_head()
 */
function mokodolitraining_admin_tabs(): array
{
	global $langs;
	return [
		[
			0 => dol_buildpath('/mokodolitraining/admin/setup.php', 1),
			1 => $langs->trans('TabSetup'),
			2 => 'setup',
		],
		[
			0 => dol_buildpath('/mokodolitraining/admin/data.php', 1),
			1 => $langs->trans('TabTrainingData'),
			2 => 'data',
		],
		[
			0 => dol_buildpath('/mokodolitraining/admin/backups.php', 1),
			1 => $langs->trans('TabBackups'),
			2 => 'backups',
		],
		[
			0 => dol_buildpath('/mokodolitraining/admin/logs.php', 1),
			1 => $langs->trans('TabLogs'),
			2 => 'logs',
		],
	];
}

/**
 * Human-readable file size string.
 *
 * @param int $bytes
 * @return string
 */
function mokodolitraining_format_bytes(int $bytes): string
{
	if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
	if ($bytes >= 1048576)    return round($bytes / 1048576,    2) . ' MB';
	if ($bytes >= 1024)       return round($bytes / 1024,       1) . ' KB';
	return $bytes . ' B';
}

/**
 * HTML badge for a status string.
 *
 * @param string $status  ok|partial|error
 * @return string
 */
function mokodolitraining_badge_status(string $status): string
{
	$map = ['ok' => 'badge-status4', 'partial' => 'badge-status1', 'error' => 'badge-status8'];
	$cls = $map[$status] ?? 'badge-status0';
	return '<span class="badge ' . $cls . '">' . dol_htmlentities(ucfirst($status)) . '</span>';
}

/**
 * Instantiate and return Backup and Audit classes.
 *
 * @param object $db  Dolibarr DB object
 * @return array{backup: MokoDoliTrainingBackup, audit: MokoDoliTrainingAudit}
 */
function mokodolitraining_load_classes($db): array
{
	dol_include_once('/mokodolitraining/class/MokoDoliTrainingBackup.class.php');
	dol_include_once('/mokodolitraining/class/MokoDoliTrainingAudit.class.php');

	$max = max(2, (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10));
	return [
		'backup' => new MokoDoliTrainingBackup($db, $max),
		'audit'  => new MokoDoliTrainingAudit($db),
	];
}

/**
 * Fetch a Dolibarr constant safely, returning a default if empty.
 *
 * @param string $name     Constant name
 * @param mixed  $default  Value if constant is empty
 * @return mixed
 */
function mokodolitraining_const(string $name, $default = '')
{
	$val = getDolGlobalString($name);
	return ($val !== '' && $val !== null) ? $val : $default;
}

/**
 * Load and return the training dataset manifest tables array.
 *
 * @return array
 */
function mokodolitraining_get_manifest(): array
{
	$path = dirname(__DIR__) . '/sql/manifest.json';
	if (!file_exists($path)) return [];
	$raw = json_decode(file_get_contents($path), true);
	return is_array($raw['tables'] ?? null) ? $raw['tables'] : [];
}

/**
 * Return table and row count summary from the manifest.
 *
 * @return array{tables: int, rows: int}
 */
function mokodolitraining_get_manifest_summary(): array
{
	$m = mokodolitraining_get_manifest();
	return ['tables' => count($m), 'rows' => array_sum(array_map('count', $m))];
}

/**
 * Return absolute path to the seed SQL file.
 *
 * @return string
 */
function mokodolitraining_get_seed_sql_path(): string
{
	return dirname(__DIR__) . '/sql/mokotraining.sql';
}

/**
 * Return absolute path to the reset SQL file.
 *
 * @return string
 */
function mokodolitraining_get_reset_sql_path(): string
{
	return dirname(__DIR__) . '/sql/mokotraining_reset.sql';
}
