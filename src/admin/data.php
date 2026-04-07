<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Admin
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/admin/data.php
 * VERSION:  01.00.03
 * BRIEF:    Training Data tab: manifest table, SQL file info, dataset overview.
 */

$res = 0;
if (!$res && file_exists('../main.inc.php'))          $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))       $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))    $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/mokodolitraining/lib/mokodolitraining.lib.php');

if (!$user->admin) accessforbidden();

$langs->load('mokodolitraining@mokodolitraining');

// ── Data ──────────────────────────────────────────────────────────────────────
$manifest   = mokodolitraining_get_manifest();
$summary    = mokodolitraining_get_manifest_summary();
$seed_path  = mokodolitraining_get_seed_sql_path();
$reset_path = mokodolitraining_get_reset_sql_path();

$seed_size  = file_exists($seed_path)  ? filesize($seed_path)  : 0;
$reset_size = file_exists($reset_path) ? filesize($reset_path) : 0;
$seed_mtime = file_exists($seed_path)  ? filemtime($seed_path) : 0;

// Count rows per manifest table for the bar chart column
$max_count = 1;
foreach ($manifest as $ids) {
	if (count($ids) > $max_count) $max_count = count($ids);
}

// ── Output ────────────────────────────────────────────────────────────────────
llxHeader('', $langs->trans('PageTitleData'));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');

dol_htmloutput_events();

print dol_get_fiche_head(mokodolitraining_admin_tabs(), 'data', '', -1, 'technic');

// ── SQL file info ─────────────────────────────────────────────────────────────
print '<table class="noborder centpercent" style="margin-bottom:20px">';
print '<tr class="liste_titre">';
printf('<th>%s</th><th>%s</th><th class="right">%s</th><th>%s</th>',
	$langs->trans('DataSqlFile'),
	$langs->trans('DataSqlPurpose'),
	$langs->trans('DataSqlSize'),
	$langs->trans('DataSqlModified')
);
print '</tr>';

foreach ([
	[$seed_path,  $langs->trans('DataSqlSeedPurpose'),  $seed_size,  $seed_mtime],
	[$reset_path, $langs->trans('DataSqlResetPurpose'), $reset_size, 0],
] as [$path, $purpose, $size, $mtime]) {
	$exists = file_exists($path);
	printf(
		'<tr class="oddeven">'
		. '<td><code class="small">%s</code></td>'
		. '<td class="opacitymedium">%s</td>'
		. '<td class="right">%s</td>'
		. '<td>%s</td>'
		. '</tr>',
		dol_htmlentities(basename($path)),
		$purpose,
		$exists ? mokodolitraining_format_bytes($size) : '<span class="badge badge-status8">' . $langs->trans('DataFileMissing') . '</span>',
		($exists && $mtime) ? dol_print_date($mtime, 'dayhour') : ($exists ? '<span class="opacitymedium">-</span>' : '')
	);
}

print '</table>';

// ── Manifest table ────────────────────────────────────────────────────────────
if (empty($manifest)) {
	print '<div class="opacitymedium">' . img_picto('', 'info', 'class="pictofixedwidth"') . $langs->trans('ManifestEmpty') . '</div>';
} else {
	printf(
		'<div class="opacitymedium small" style="margin-bottom:8px">%s: <b>%d</b> %s, <b>%d</b> %s</div>',
		$langs->trans('DataManifestSummary'),
		$summary['tables'], $langs->trans('LabelTables'),
		$summary['rows'],   $langs->trans('LabelRows')
	);

	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	printf(
		'<tr class="liste_titre">'
		. '<th>%s</th>'
		. '<th class="right" style="width:60px">%s</th>'
		. '<th style="width:140px">%s</th>'
		. '<th>%s</th>'
		. '</tr>',
		$langs->trans('ManifestTable'),
		$langs->trans('ManifestRowCount'),
		$langs->trans('DataRowBar'),
		$langs->trans('ManifestRowIds')
	);

	foreach ($manifest as $tbl => $ids) {
		$cnt     = count($ids);
		$pct     = (int) round(($cnt / $max_count) * 100);
		$bar_clr = $pct >= 75 ? '#28a745' : ($pct >= 40 ? '#17a2b8' : '#6c757d');
		$bar     = '<div style="background:#e9ecef;border-radius:3px;height:10px;width:120px">'
			. '<div style="background:' . $bar_clr . ';width:' . $pct . '%;height:10px;border-radius:3px"></div>'
			. '</div>';

		printf(
			'<tr class="oddeven">'
			. '<td><code>%s</code></td>'
			. '<td class="right"><b>%d</b></td>'
			. '<td>%s</td>'
			. '<td class="small opacitymedium" style="word-break:break-all">%s</td>'
			. '</tr>',
			dol_htmlentities($tbl),
			$cnt,
			$bar,
			dol_htmlentities(implode(', ', $ids))
		);
	}

	print '</table></div>';
}

print dol_fiche_end();

llxFooter();
$db->close();
