<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Admin
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/admin/logs.php
 * VERSION:  01.02.00
 * BRIEF:    Audit log viewer with filter, stats, and purge controls.
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
$action = GETPOST('action', 'aZ09');
$limit  = max(10, min(200, (int) (GETPOST('limit', 'int') ?: 50)));

['audit' => $audit] = mokodolitraining_load_classes($db);


// ── Action: purge old logs ────────────────────────────────────────────────────
if ($action === 'purge_logs') {
	$days   = max(7, (int) (getDolGlobalString('MOKODOLITRAINING_LOG_RETENTION') ?: 90));
	$purged = $audit->purgeOlderThan($days, (int) $conf->entity);
	setEventMessages(sprintf($langs->trans('LogPurgedOk'), $purged), null, 'mesgs');
	$audit->log((int) $user->id, 'backup_purge', 'ok', note: "Manual purge: {$purged} rows removed (>{$days} days)", entity: (int) $conf->entity);
	header('Location: ' . $_SERVER['PHP_SELF'] . '?limit=' . (int) $limit);
	exit;
}

// ── Data ──────────────────────────────────────────────────────────────────────
$log_rows = $audit->getRecent($limit, (int) $conf->entity);
$stats    = $audit->getStats((int) $conf->entity);
$ret_days = (int) (getDolGlobalString('MOKODOLITRAINING_LOG_RETENTION') ?: 90);

// ── Output ────────────────────────────────────────────────────────────────────
llxHeader('', $langs->trans('PageTitleLogs'));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');

dol_htmloutput_events();

print dol_get_fiche_head(mokodolitraining_admin_tabs(), 'logs', '', -1, 'technic');

// ── Stats summary ─────────────────────────────────────────────────────────────
if (!empty($stats)) {
	print '<div class="div-table-responsive-no-min" style="margin-bottom:16px">';
	print '<table class="noborder">';
	printf(
		'<tr class="liste_titre"><th>%s</th><th>%s</th><th class="right">%s</th></tr>',
		$langs->trans('LogAction'),
		$langs->trans('LogStatus'),
		$langs->trans('LogCount')
	);
	foreach ($stats as $s) {
		printf(
			'<tr class="oddeven"><td><code>%s</code></td><td>%s</td><td class="right"><b>%d</b></td></tr>',
			dol_htmlentities($s['action']),
			mokodolitraining_badge_status($s['status']),
			(int) $s['cnt']
		);
	}
	print '</table></div>';
}

// ── Row limit selector (GET) ──────────────────────────────────────────────────
print '<div style="margin-bottom:10px">';
print '<form method="GET" action="' . $_SERVER['PHP_SELF'] . '" style="display:inline">';
print $langs->trans('Show') . '&nbsp;';
print '<select name="limit" class="flat" onchange="this.form.submit()" style="width:auto">';
foreach ([25, 50, 100, 200] as $l) {
	printf('<option value="%d"%s>%d</option>', $l, ($l === $limit ? ' selected' : ''), $l);
}
print '</select>&nbsp;<noscript><input type="submit" value="' . $langs->trans('Refresh') . '" class="button smallpaddingimp"></noscript>';
print '</form>';
print '</div>';

// ── Log table ─────────────────────────────────────────────────────────────────
if (empty($log_rows)) {
	print '<div class="opacitymedium">' . img_picto('', 'info', 'class="pictofixedwidth"') . $langs->trans('LogEmpty') . '</div>';
} else {
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	printf(
		'<tr class="liste_titre">'
		. '<th>%s</th><th>%s</th><th>%s</th><th>%s</th>'
		. '<th class="right">%s</th><th class="right">%s</th>'
		. '<th>%s</th><th class="center">%s</th><th>%s</th>'
		. '</tr>',
		$langs->trans('LogDate'),
		$langs->trans('LogUser'),
		$langs->trans('LogAction'),
		$langs->trans('LogStatus'),
		$langs->trans('LogRows'),
		$langs->trans('LogDuration'),
		$langs->trans('LogBackupFile'),
		$langs->trans('LogErrors'),
		$langs->trans('LogNote')
	);

	foreach ($log_rows as $row) {
		// format date
		$date_disp = $row['datec']
			? dol_print_date(dol_stringtotime($row['datec']), 'dayhour')
			: '<span class="opacitymedium">-</span>';

		// error badge
		$err_disp = $row['errors']
			? '<span class="badge badge-status8" title="' . dol_htmlentities($row['errors']) . '">'
				. img_picto('', 'warning', 'class="pictofixedwidth"') . $langs->trans('LogHasErrors') . '</span>'
			: img_picto('', 'check', 'class="opacitymedium"');

		// backup file -- basename with full path in title
		$file_disp = $row['backup_file']
			? '<span class="small" title="' . dol_htmlentities($row['backup_file']) . '">'
				. img_picto('', 'download', 'class="pictofixedwidth opacitymedium"')
				. dol_htmlentities(basename($row['backup_file'])) . '</span>'
			: '<span class="opacitymedium">-</span>';

		// duration
		$dur_disp = $row['duration_ms']
			? (int) $row['duration_ms'] . '&nbsp;ms'
			: '<span class="opacitymedium">-</span>';

		printf(
			'<tr class="oddeven">'
			. '<td class="nowrap small">%s</td>'
			. '<td>%s</td>'
			. '<td><code class="small">%s</code></td>'
			. '<td>%s</td>'
			. '<td class="right">%d</td>'
			. '<td class="right nowrap small">%s</td>'
			. '<td class="small">%s</td>'
			. '<td class="center">%s</td>'
			. '<td class="small opacitymedium">%s</td>'
			. '</tr>',
			$date_disp,
			dol_htmlentities($row['login'] ?: 'cron'),
			dol_htmlentities($row['action']),
			mokodolitraining_badge_status($row['status']),
			(int) $row['rows_affected'],
			$dur_disp,
			$file_disp,
			$err_disp,
			dol_htmlentities((string) ($row['note'] ?? ''))
		);
	}
	print '</table></div>';
}

print dol_fiche_end();

// ── tabsAction -- purge outside fiche ─────────────────────────────────────────
print '<div class="tabsAction">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" style="display:inline">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
printf(
	'<button type="submit" name="action" value="purge_logs" class="butActionDelete"'
	. ' onclick="return confirm(\'%s\');">%s%s (&gt;&nbsp;%d&nbsp;%s)</button>',
	dol_escape_js($langs->trans('ConfirmPurgeLogs')),
	img_picto('', 'delete', 'class="pictofixedwidth"'),
	$langs->trans('ActionPurgeLogs'),
	$ret_days,
	$langs->trans('LabelDays')
);
print '</form></div>';

llxFooter();
$db->close();
