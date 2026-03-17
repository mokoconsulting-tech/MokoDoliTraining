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
 * VERSION:  01.00.00
 * BRIEF:    Training Data tab: manifest table and dataset overview.
 */

$res = 0;
if (!$res && file_exists('../main.inc.php'))          $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))       $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))    $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/mokodolitraining/lib/mokodolitraining.lib.php');

// data tab is visible to any permission level (read, reset, or manage)
if (!mokodolitraining_has_perm($user, 'read')
	&& !mokodolitraining_has_perm($user, 'reset')
	&& !mokodolitraining_has_perm($user, 'manage')) {
	accessforbidden();
}

$langs->load('mokodolitraining@mokodolitraining');

// ── Data ──────────────────────────────────────────────────────────────────────
$manifest = mokodolitraining_get_manifest();
$summary  = mokodolitraining_get_manifest_summary();

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
