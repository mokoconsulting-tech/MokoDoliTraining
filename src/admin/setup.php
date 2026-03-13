<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Admin
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/admin/setup.php
 * VERSION:  01.03.00
 * BRIEF:    Dashboard: status, backup health, recent activity, settings, dataset actions.
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

['backup' => $backup, 'audit' => $audit] = mokodolitraining_load_classes($db);


// ── Action: save settings ─────────────────────────────────────────────────────
if ($action === 'save_settings') {
	$max = max(2, (int) GETPOST('max_backups', 'int'));
	$ret = max(7, (int) GETPOST('log_retention', 'int'));
	dolibarr_set_const($db, 'MOKODOLITRAINING_MAX_BACKUPS',   (string) $max, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'MOKODOLITRAINING_LOG_RETENTION', (string) $ret, 'chaine', 0, '', $conf->entity);
	$audit->log((int) $user->id, 'settings_save', 'ok', note: "max_backups={$max} log_retention={$ret}", entity: (int) $conf->entity);
	setEventMessages($langs->trans('ResultSettingsSaved'), null, 'mesgs');
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Action: install ───────────────────────────────────────────────────────────
if ($action === 'install') {
	if ($backup->isLocked()) {
		setEventMessages($langs->trans('BackupLockWait'), null, 'warnings');
	} elseif ($backup->acquireLock()) {
		$t0 = hrtime(true);
		$errs = [];

		$rb = $backup->createFullBackup('rollback');
		$errs = array_merge($errs, $rb['errors']);
		if ($rb['path']) {
			dolibarr_set_const($db, 'MOKODOLITRAINING_ROLLBACK_FILE', $rb['path'], 'chaine', 0, '', $conf->entity);
			setEventMessages(sprintf($langs->trans('ResultRollbackCreated'), basename($rb['path']), $rb['rows']), null, 'mesgs');
			$audit->log((int) $user->id, 'backup_create', empty($rb['errors']) ? 'ok' : 'partial',
				$rb['rows'], 0, 0, $rb['path'], $rb['checksum'], $rb['errors'], entity: (int) $conf->entity);
		}

		$seed = $backup->runSeed();
		$errs = array_merge($errs, $seed['errors']);
		setEventMessages(sprintf($langs->trans('ResultSeedOk'), $seed['ok']), null, 'mesgs');
		$audit->log((int) $user->id, 'seed', empty($seed['errors']) ? 'ok' : 'partial',
			0, $seed['ok'], 0, errors: $seed['errors'], entity: (int) $conf->entity);

		$snap = $backup->createBackup('snapshot');
		$errs = array_merge($errs, $snap['errors']);
		if ($snap['path']) {
			dolibarr_set_const($db, 'MOKODOLITRAINING_SNAPSHOT_FILE', $snap['path'], 'chaine', 0, '', $conf->entity);
			setEventMessages(sprintf($langs->trans('ResultSnapshotCreated'), basename($snap['path']), $snap['rows']), null, 'mesgs');
			$audit->log((int) $user->id, 'backup_create', empty($snap['errors']) ? 'ok' : 'partial',
				$snap['rows'], 0, 0, $snap['path'], $snap['checksum'], $snap['errors'], entity: (int) $conf->entity);
		}

		if ($errs) setEventMessages(null, $errs, 'errors');
		$ms = (int) ((hrtime(true) - $t0) / 1e6);
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEEDED',    '1',         'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEED_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'install', empty($errs) ? 'ok' : 'partial', 0, 0, $ms, errors: $errs, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Action: reset to snapshot ─────────────────────────────────────────────────
if ($action === 'reset_snapshot') {
	$snap_path = $backup->getLatest('snapshot') ?: getDolGlobalString('MOKODOLITRAINING_SNAPSHOT_FILE');
	if (!$snap_path || !file_exists($snap_path)) {
		setEventMessages('No snapshot backup found. Run Install first.', null, 'errors');
	} elseif ($backup->isLocked()) {
		setEventMessages($langs->trans('BackupLockWait'), null, 'warnings');
	} elseif ($backup->acquireLock()) {
		$t0 = hrtime(true);
		$errs = [];
		$del  = $backup->runReset();
		$errs = array_merge($errs, $del['errors']);
		setEventMessages(sprintf($langs->trans('ResultResetOk'), $del['ok']), null, 'mesgs');
		$res2 = $backup->restoreFromFile($snap_path);
		$errs = array_merge($errs, $res2['errors']);
		setEventMessages(sprintf($langs->trans('ResultRestoreOk'), $res2['ok']), null, 'mesgs');
		if ($errs) setEventMessages(null, $errs, 'errors');
		dolibarr_set_const($db, 'MOKODOLITRAINING_RESET_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'reset_snapshot', empty($errs) ? 'ok' : 'partial',
			$res2['ok'], 0, (int) ((hrtime(true) - $t0) / 1e6), $snap_path, errors: $errs, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Action: rollback ──────────────────────────────────────────────────────────
if ($action === 'rollback') {
	$rb_path = $backup->getLatest('rollback') ?: getDolGlobalString('MOKODOLITRAINING_ROLLBACK_FILE');
	if (!$rb_path || !file_exists($rb_path)) {
		setEventMessages('No rollback backup found. Run Install first.', null, 'errors');
	} elseif ($backup->isLocked()) {
		setEventMessages($langs->trans('BackupLockWait'), null, 'warnings');
	} elseif ($backup->acquireLock()) {
		$t0 = hrtime(true);
		$errs = [];
		$del  = $backup->runReset();
		$errs = array_merge($errs, $del['errors']);
		setEventMessages(sprintf($langs->trans('ResultResetOk'), $del['ok']), null, 'mesgs');
		$res2 = $backup->restoreFromFile($rb_path);
		$errs = array_merge($errs, $res2['errors']);
		setEventMessages(sprintf($langs->trans('ResultRestoreOk'), $res2['ok']), null, 'mesgs');
		if ($errs) setEventMessages(null, $errs, 'errors');
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEEDED',     '0',         'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MOKODOLITRAINING_RESET_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'rollback', empty($errs) ? 'ok' : 'partial',
			$res2['ok'], 0, (int) ((hrtime(true) - $t0) / 1e6), $rb_path, errors: $errs, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── State ─────────────────────────────────────────────────────────────────────
$is_seeded   = getDolGlobalString('MOKODOLITRAINING_SEEDED') === '1';
$seed_date   = getDolGlobalString('MOKODOLITRAINING_SEED_DATE');
$reset_date  = getDolGlobalString('MOKODOLITRAINING_RESET_DATE');
$rb_file     = $backup->getLatest('rollback');
$snap_file   = $backup->getLatest('snapshot');
$max_backups = (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10);
$log_ret     = (int) (getDolGlobalString('MOKODOLITRAINING_LOG_RETENTION') ?: 90);
$is_locked   = $backup->isLocked();
$summary     = mokodolitraining_get_manifest_summary();
$all_backups = $backup->listBackups();
$recent_logs = $audit->getRecent(5, (int) $conf->entity);

// Backup health: verify both key backups
$rb_health   = null; // null=none, true=ok, false=fail
$snap_health = null;
foreach ([['rollback', &$rb_health, $rb_file], ['snapshot', &$snap_health, $snap_file]] as [, &$h, $f]) {
	if ($f && file_exists($f)) {
		$v = $backup->verifyIntegrity($f);
		$h = $v['ok'];
	}
}

// ── Output ────────────────────────────────────────────────────────────────────
llxHeader('', $langs->trans('PageTitleSetup'));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');

if ($is_locked) print info_admin($langs->trans('StatusLocked'), 0, 0, 1);
dol_htmloutput_events();

print dol_get_fiche_head(mokodolitraining_admin_tabs(), 'setup', '', -1, 'technic');

// ── Stat counter boxes ────────────────────────────────────────────────────────
print '<div class="div-table-responsive-no-min" style="margin-bottom:20px">';
print '<table class="nobordernopadding centpercent"><tr>';

// Dataset status
$ds_color = $is_seeded ? '#28a745' : '#6c757d';
$ds_lbl   = $is_seeded ? $langs->trans('StatusSeeded') : $langs->trans('StatusNotSeeded');
$ds_icon  = $is_seeded ? 'check' : 'help';
printf(
	'<td style="width:25%%;padding:4px">'
	. '<div class="box-flex-item box-flex-item-with-label" style="border-left:4px solid %s;padding:12px 16px">'
	. '<div class="box-flex-item-label opacitymedium">%s</div>'
	. '<div style="font-size:1.4em;font-weight:bold">%s %s</div>'
	. '</div></td>',
	$ds_color,
	$langs->trans('DashDatasetStatus'),
	img_picto('', $ds_icon),
	$ds_lbl
);

// Tracked rows
printf(
	'<td style="width:25%%;padding:4px">'
	. '<div class="box-flex-item box-flex-item-with-label" style="border-left:4px solid #17a2b8;padding:12px 16px">'
	. '<div class="box-flex-item-label opacitymedium">%s</div>'
	. '<div style="font-size:1.4em;font-weight:bold">%d <span class="opacitymedium" style="font-size:0.6em">%s / %d %s</span></div>'
	. '</div></td>',
	$langs->trans('DashTrackedRows'),
	$summary['rows'],
	$langs->trans('LabelRows'),
	$summary['tables'],
	$langs->trans('LabelTables')
);

// Backup count
$bk_count = count($all_backups);
$bk_color = $bk_count > 0 ? '#007bff' : '#6c757d';
printf(
	'<td style="width:25%%;padding:4px">'
	. '<div class="box-flex-item box-flex-item-with-label" style="border-left:4px solid %s;padding:12px 16px">'
	. '<div class="box-flex-item-label opacitymedium">%s</div>'
	. '<div style="font-size:1.4em;font-weight:bold">%d <span class="opacitymedium" style="font-size:0.6em">/ %d %s</span></div>'
	. '</div></td>',
	$bk_color,
	$langs->trans('DashBackupCount'),
	$bk_count,
	$max_backups,
	$langs->trans('DashMax')
);

// Backup health
$h_rb   = is_null($rb_health)   ? ['--',      '#6c757d', 'help']    : ($rb_health   ? [$langs->trans('DashHealthOk'), '#28a745', 'check'] : [$langs->trans('DashHealthFail'), '#dc3545', 'error']);
$h_snap = is_null($snap_health) ? ['--',      '#6c757d', 'help']    : ($snap_health ? [$langs->trans('DashHealthOk'), '#28a745', 'check'] : [$langs->trans('DashHealthFail'), '#dc3545', 'error']);
printf(
	'<td style="width:25%%;padding:4px">'
	. '<div class="box-flex-item box-flex-item-with-label" style="border-left:4px solid %s;padding:12px 16px">'
	. '<div class="box-flex-item-label opacitymedium">%s</div>'
	. '<div style="font-size:1em;font-weight:bold">'
	. '%s %s &nbsp;|&nbsp; %s %s'
	. '</div></div></td>',
	($rb_health === false || $snap_health === false) ? '#dc3545' : '#28a745',
	$langs->trans('DashBackupHealth'),
	img_picto('', $h_rb[2]),   $langs->trans('DashRollback') . ': ' . $h_rb[0],
	img_picto('', $h_snap[2]), $langs->trans('DashSnapshot')  . ': ' . $h_snap[0]
);

print '</tr></table></div>';

// ── Key dates + file info ─────────────────────────────────────────────────────
print '<table class="noborder centpercent" style="margin-bottom:20px">';
print '<tr class="liste_titre">';
printf('<th>%s</th><th>%s</th><th>%s</th><th>%s</th>',
	$langs->trans('LabelLastSeeded'),
	$langs->trans('LabelLastReset'),
	$langs->trans('LabelRollbackBackup'),
	$langs->trans('LabelSnapshotBackup')
);
print '</tr><tr class="oddeven">';

$none = '<span class="opacitymedium">' . $langs->trans('LabelNone') . '</span>';

printf('<td>%s</td>',
	$seed_date  ? dol_print_date(dol_stringtotime($seed_date),  'dayhour') : $none);
printf('<td>%s</td>',
	$reset_date ? dol_print_date(dol_stringtotime($reset_date), 'dayhour') : $none);

foreach ([$rb_file, $snap_file] as $f) {
	if ($f) {
		$sz   = file_exists($f) ? ' <span class="opacitymedium small">(' . mokodolitraining_format_bytes(filesize($f)) . ')</span>' : '';
		printf('<td>' . img_picto('', 'download', 'class="pictofixedwidth"') . '<span title="%s">%s</span>%s</td>',
			dol_htmlentities($f), dol_htmlentities(basename($f)), $sz);
	} else {
		print '<td>' . $none . '</td>';
	}
}

print '</tr></table>';

// ── Recent activity ───────────────────────────────────────────────────────────
print '<div style="margin-bottom:20px">';
print '<h4 class="liste_titre" style="margin-top:0;padding:6px 0;border-bottom:1px solid var(--colortextlink,#666)">'
	. img_picto('', 'clock', 'class="pictofixedwidth"')
	. $langs->trans('DashRecentActivity') . '</h4>';

if (empty($recent_logs)) {
	print '<span class="opacitymedium">' . $langs->trans('LogEmpty') . '</span>';
} else {
	print '<table class="noborder centpercent">';
	printf('<tr class="liste_titre"><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th class="right">%s</th><th>%s</th></tr>',
		$langs->trans('LogDate'),
		$langs->trans('LogUser'),
		$langs->trans('LogAction'),
		$langs->trans('LogStatus'),
		$langs->trans('LogRows'),
		$langs->trans('LogNote')
	);
	foreach ($recent_logs as $row) {
		printf(
			'<tr class="oddeven">'
			. '<td class="nowrap small">%s</td>'
			. '<td class="small">%s</td>'
			. '<td><code class="small">%s</code></td>'
			. '<td>%s</td>'
			. '<td class="right small">%d</td>'
			. '<td class="small opacitymedium">%s</td>'
			. '</tr>',
			$row['datec'] ? dol_print_date(dol_stringtotime($row['datec']), 'dayhour') : '-',
			dol_htmlentities($row['login'] ?: 'cron'),
			dol_htmlentities($row['action']),
			mokodolitraining_badge_status($row['status']),
			(int) $row['rows_affected'],
			dol_htmlentities((string) ($row['note'] ?? ''))
		);
	}
	print '</table>';
	$logs_url = dol_buildpath('/mokodolitraining/admin/logs.php', 1);
	print '<div style="text-align:right;margin-top:4px"><a href="' . $logs_url . '" class="small">'
		. $langs->trans('DashViewAllLogs') . ' &rsaquo;</a></div>';
}
print '</div>';

// ── Settings form ─────────────────────────────────────────────────────────────
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="3">' . $langs->trans('SettingsTitle') . '</th></tr>';
printf(
	'<tr class="oddeven">'
	. '<td class="titlefield fieldrequired">%s</td>'
	. '<td><input type="number" name="max_backups" value="%d" min="2" max="50" class="width50 flat"></td>'
	. '<td class="opacitymedium">%s</td></tr>',
	$langs->trans('SettingMaxBackups'), $max_backups, $langs->trans('SettingMaxBackupsHelp')
);
printf(
	'<tr class="oddeven">'
	. '<td class="titlefield fieldrequired">%s</td>'
	. '<td><input type="number" name="log_retention" value="%d" min="7" max="3650" class="width50 flat"></td>'
	. '<td class="opacitymedium">%s</td></tr>',
	$langs->trans('SettingLogRetention'), $log_ret, $langs->trans('SettingLogRetentionHelp')
);
print '</table>';
print dol_fiche_end();

// ── tabsAction ────────────────────────────────────────────────────────────────
print '<div class="tabsAction">';

print '<button type="submit" name="action" value="save_settings" class="butAction">'
	. img_picto('', 'save', 'class="pictofixedwidth"')
	. $langs->trans('ActionSaveSettings') . '</button>';
print '</form>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" style="display:inline">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
printf(
	'<button type="submit" name="action" value="install" class="butAction"%s onclick="return confirm(\'%s\');">%s%s</button>',
	$is_locked ? ' disabled' : '',
	dol_escape_js($langs->trans('ConfirmInstall')),
	img_picto('', 'technic', 'class="pictofixedwidth"'),
	$langs->trans('ActionInstall')
);
printf(
	'<button type="submit" name="action" value="reset_snapshot" class="butAction"%s onclick="return confirm(\'%s\');">%s%s</button>',
	($is_locked || !$snap_file) ? ' disabled' : '',
	dol_escape_js($langs->trans('ConfirmResetSnapshot')),
	img_picto('', 'refresh', 'class="pictofixedwidth"'),
	$langs->trans('ActionResetSnapshot')
);
printf(
	'<button type="submit" name="action" value="rollback" class="butActionDelete"%s onclick="return confirm(\'%s\');">%s%s</button>',
	($is_locked || !$rb_file) ? ' disabled' : '',
	dol_escape_js($langs->trans('ConfirmRollback')),
	img_picto('', 'error', 'class="pictofixedwidth"'),
	$langs->trans('ActionRollback')
);
print '</form></div>';

llxFooter();
$db->close();
