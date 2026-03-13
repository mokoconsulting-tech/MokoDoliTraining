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
 * VERSION:  01.00.00
 * BRIEF:    Audit log viewer with filter, stats, and purge controls.
 */

$res = 0;
if (!$res && file_exists('../main.inc.php'))          $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))       $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))    $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/mokodolitraining/src/lib/mokodolitraining.lib.php');

if (!$user->admin) accessforbidden();

$langs->load('mokodolitraining@mokodolitraining');
$action = GETPOST('action', 'aZ09');
$limit  = max(10, min(200, (int) (GETPOST('limit', 'int') ?: 50)));

['audit' => $audit] = mokodolitraining_load_classes($db);

$msgs   = [];
$errors = [];

// ── CSRF guard ────────────────────────────────────────────────────────────────
if ($action === 'purge_logs' && !verifCsrfToken()) {
	accessforbidden('Invalid token');
}

// ── Action: purge old logs ─────────────────────────────────────────────────────
if ($action === 'purge_logs') {
	$days   = max(7, (int) (getDolGlobalString('MOKODOLITRAINING_LOG_RETENTION') ?: 90));
	$purged = $audit->purgeOlderThan($days, (int) $conf->entity);
	$msgs[] = sprintf($langs->trans('LogPurgedOk'), $purged);
	$audit->log((int) $user->id, 'backup_purge', 'ok', note: "Manual purge: {$purged} rows removed (>{$days} days)", entity: (int) $conf->entity);
}

// ── Data ───────────────────────────────────────────────────────────────────────
$log_rows = $audit->getRecent($limit, (int) $conf->entity);
$stats    = $audit->getStats((int) $conf->entity);
$ret_days = (int) (getDolGlobalString('MOKODOLITRAINING_LOG_RETENTION') ?: 90);

// ── Output ─────────────────────────────────────────────────────────────────────
llxHeader('', $langs->trans('PageTitleLogs'));
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');
print mokodolitraining_admin_tabs('logs');

foreach ($msgs   as $m) print '<div class="ok">'    . dol_htmlentities($m) . '</div>';
foreach ($errors as $e) print '<div class="error">' . dol_htmlentities($e) . '</div>';

// ── Stats summary ─────────────────────────────────────────────────────────────
if (!empty($stats)) {
	print '<br><h3>' . $langs->trans('LogStatsTitle') . '</h3>';
	print '<div class="div-table-responsive">';
	print '<table class="noborder">';
	print '<tr class="liste_titre"><th>' . $langs->trans('LogAction') . '</th><th>'
		. $langs->trans('LogStatus') . '</th><th>Count</th></tr>';
	foreach ($stats as $i => $s) {
		printf('<tr class="%s"><td>%s</td><td>%s</td><td>%d</td></tr>',
			($i % 2 === 0) ? 'even' : 'odd',
			dol_htmlentities($s['action']),
			mokodolitraining_badge_status($s['status']),
			(int) $s['cnt']
		);
	}
	print '</table></div>';
}

// ── Purge control ──────────────────────────────────────────────────────────────
print '<br>';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" style="display:inline">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
printf('<button type="submit" name="action" value="purge_logs" class="butActionDelete"'
	. ' onclick="return confirm(\'%s\');">%s (> %d days)</button>',
	dol_escape_js($langs->trans('ConfirmPurgeLogs')),
	$langs->trans('ActionPurgeLogs'),
	$ret_days
);
print '</form>';

// ── Log table ─────────────────────────────────────────────────────────────────
print '<br><h3>' . $langs->trans('TabAuditLog') . ' <small>(last ' . (int) $limit . ')</small></h3>';

// Limit selector
print '<form method="GET" action="' . $_SERVER['PHP_SELF'] . '" style="margin-bottom:8px">';
print 'Show: <select name="limit" onchange="this.form.submit()">';
foreach ([25, 50, 100, 200] as $l) {
	printf('<option value="%d"%s>%d</option>', $l, ($l === $limit ? ' selected' : ''), $l);
}
print '</select></form>';

if (empty($log_rows)) {
	print '<p><em>' . $langs->trans('LogEmpty') . '</em></p>';
} else {
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	printf('<tr class="liste_titre"><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>',
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

	foreach ($log_rows as $i => $row) {
		$cls    = ($i % 2 === 0) ? 'even' : 'odd';
		$errors_disp = $row['errors']
			? '<span class="badge badge-status8" title="' . dol_htmlentities($row['errors']) . '">Errors</span>'
			: '';
		printf(
			'<tr class="%s"><td style="white-space:nowrap">%s</td><td>%s</td><td><code>%s</code></td><td>%s</td><td>%d</td><td>%s</td><td style="font-size:0.8em">%s</td><td>%s</td><td style="font-size:0.85em">%s</td></tr>',
			$cls,
			dol_htmlentities($row['datec']),
			dol_htmlentities($row['login'] ?: 'cron'),
			dol_htmlentities($row['action']),
			mokodolitraining_badge_status($row['status']),
			(int) $row['rows_affected'],
			$row['duration_ms'] ? (int) $row['duration_ms'] . ' ms' : '',
			$row['backup_file'] ? dol_htmlentities($row['backup_file']) : '',
			$errors_disp,
			dol_htmlentities((string) ($row['note'] ?? ''))
		);
	}
	print '</table></div>';
}

llxFooter();
$db->close();
