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
 * VERSION:  01.00.00
 * BRIEF:    Shared helpers: tab builder, formatters, class loader, constants accessor.
 */

/**
 * Render the admin tab bar and return it as an HTML string.
 *
 * @param string $active  Key of the currently active tab
 * @return string         HTML tab bar
 */
function mokodolitraining_admin_tabs(string $active): string
{
	$tabs = [
		'setup'   => ['label' => 'Setup',     'url' => dol_buildpath('/mokodolitraining/src/admin/setup.php', 1)],
		'backups' => ['label' => 'Backups',   'url' => dol_buildpath('/mokodolitraining/src/admin/backups.php', 1)],
		'logs'    => ['label' => 'Audit Log', 'url' => dol_buildpath('/mokodolitraining/src/admin/logs.php', 1)],
	];
	$out = '<div class="tabs"><ul>';
	foreach ($tabs as $key => $t) {
		$cls = ($key === $active) ? ' class="tabactive"' : '';
		$out .= '<li' . $cls . '><a href="' . $t['url'] . '">' . dol_escape_htmltag($t['label']) . '</a></li>';
	}
	return $out . '</ul></div>';
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
 * Reads MOKODOLITRAINING_MAX_BACKUPS from Dolibarr constants.
 *
 * @param object $db  Dolibarr DB object
 * @return array{backup: MokoDoliTrainingBackup, audit: MokoDoliTrainingAudit}
 */
function mokodolitraining_load_classes($db): array
{
	dol_include_once('/mokodolitraining/src/core/modules/modMokoDoliTraining.class.php');
	dol_include_once('/mokodolitraining/src/class/MokoDoliTrainingBackup.class.php');
	dol_include_once('/mokodolitraining/src/class/MokoDoliTrainingAudit.class.php');

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
