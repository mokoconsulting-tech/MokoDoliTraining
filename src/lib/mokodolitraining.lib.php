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
 * VERSION:  development
 * BRIEF:    Shared helpers: tab builder, formatters, class loader, constants accessor.
 */

/**
 * Check whether the current user has a specific MokoDoliTraining permission
 * OR is a Dolibarr superadmin (who implicitly has all rights).
 *
 * @param object $user  Dolibarr $user global
 * @param string $perm  Permission key: 'read', 'reset', or 'manage'
 * @return bool
 */
function mokodolitraining_has_perm($user, string $perm): bool
{
	if (!empty($user->admin)) return true;
	return (bool) $user->hasRight('mokodolitraining', $perm);
}

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
		[
			0 => dol_buildpath('/mokodolitraining/admin/classes.php', 1),
			1 => $langs->trans('TabClasses'),
			2 => 'classes',
		],
		[
			0 => dol_buildpath('/mokodolitraining/admin/exercise.php', 1),
			1 => $langs->trans('TabExercises'),
			2 => 'exercises',
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
 * Load and return the training dataset manifest from the DB manifest table.
 *
 * @return array<string, int[]>  table_name → [record_ids]
 */
function mokodolitraining_get_manifest(): array
{
	global $db, $conf;
	if (empty($db)) return [];
	$entity = (int) ($conf->entity ?? 1);
	$mtbl   = MAIN_DB_PREFIX . 'mokodolitraining_manifest';
	$sql    = "SELECT table_name, record_id FROM `{$mtbl}` WHERE entity = {$entity} ORDER BY table_name, record_id";
	$res    = $db->query($sql);
	if (!$res) return [];
	$out = [];
	while ($row = $db->fetch_object($res)) {
		$out[$row->table_name][] = (int) $row->record_id;
	}
	return $out;
}

/**
 * Return table and row count summary from the DB manifest.
 *
 * @return array{tables: int, rows: int}
 */
function mokodolitraining_get_manifest_summary(): array
{
	$m = mokodolitraining_get_manifest();
	return ['tables' => count($m), 'rows' => array_sum(array_map('count', $m))];
}

/**
 * HTML badge for a training class status integer.
 * 0=Draft  1=Active  2=Closed
 *
 * @param int    $status
 * @param object $langs  Dolibarr $langs global
 * @return string
 */
function mokodolitraining_badge_class_status(int $status, $langs): string
{
	$map = [
		0 => ['badge-status0', 'ClassStatusDraft'],
		1 => ['badge-status4', 'ClassStatusActive'],
		2 => ['badge-status8', 'ClassStatusClosed'],
	];
	[$cls, $key] = $map[$status] ?? ['badge-status0', 'ClassStatusDraft'];
	return '<span class="badge ' . $cls . '">' . $langs->trans($key) . '</span>';
}

/**
 * HTML badge for a trainee enrollment status integer.
 * 0=Suspended  1=Active  2=Completed
 *
 * @param int    $status
 * @param object $langs  Dolibarr $langs global
 * @return string
 */
function mokodolitraining_badge_enroll_status(int $status, $langs): string
{
	$map = [
		0 => ['badge-status8', 'EnrollSuspended'],
		1 => ['badge-status4', 'EnrollActive'],
		2 => ['badge-status1', 'EnrollCompleted'],
	];
	[$cls, $key] = $map[$status] ?? ['badge-status0', 'EnrollActive'];
	return '<span class="badge ' . $cls . '">' . $langs->trans($key) . '</span>';
}
