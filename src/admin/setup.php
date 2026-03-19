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
 * VERSION:  development
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

$langs->load('mokodolitraining@mokodolitraining');

// Any of the three rights (or superadmin) grants access to this page.
// Individual actions are further gated below by their required level.
if (!mokodolitraining_has_perm($user, 'read')
	&& !mokodolitraining_has_perm($user, 'reset')
	&& !mokodolitraining_has_perm($user, 'manage')) {
	accessforbidden();
}

$can_manage = mokodolitraining_has_perm($user, 'manage'); // install, settings, rollback
$can_reset  = mokodolitraining_has_perm($user, 'reset');  // reset to snapshot
$can_read   = mokodolitraining_has_perm($user, 'read');   // view only

$action = GETPOST('action', 'aZ09');

['backup' => $backup, 'audit' => $audit] = mokodolitraining_load_classes($db);
dol_include_once('/mokodolitraining/class/MokoDoliTrainingSeed.class.php');
$seeder = new MokoDoliTrainingSeed($db);


// ── Action: save settings ─────────────────────────────────────────────────────
if ($action === 'save_settings' && !$can_manage) accessforbidden('', true, null, 403);
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
if ($action === 'install' && !$can_manage) accessforbidden('', true, null, 403);
if ($action === 'install') {
	$seed_mode = GETPOST('seed_mode', 'aZ09');
	if (!in_array($seed_mode, ['training', 'demo'], true)) $seed_mode = 'training';

	if ($backup->isLocked()) {
		setEventMessages($langs->trans('BackupLockWait'), null, 'warnings');
	} elseif ($backup->acquireLock()) {
		$t0   = hrtime(true);
		$errs = [];
		$eid  = (int) $conf->entity;

		$rb = $backup->createFullBackup('rollback');
		$errs = array_merge($errs, $rb['errors']);
		if ($rb['rowid']) {
			dolibarr_set_const($db, 'MOKODOLITRAINING_ROLLBACK_ROWID', (string) $rb['rowid'], 'chaine', 0, '', $eid);
			setEventMessages(sprintf($langs->trans('ResultRollbackCreated'), $rb['rowid'], $rb['rows']), null, 'mesgs');
			$audit->log((int) $user->id, 'backup_create', empty($rb['errors']) ? 'ok' : 'partial',
				$rb['rows'], 0, 0, 'rowid:' . $rb['rowid'], $rb['checksum'], $rb['errors'], entity: $eid);
		}

		// runSeed() calls seedStatic($entity, $mode) + seedOrders($entity) — no separate calls needed
		$seed = $backup->runSeed($seed_mode, $eid);
		$errs = array_merge($errs, $seed['errors']);
		setEventMessages(sprintf($langs->trans('ResultSeedOk'), $seed['ok']), null, 'mesgs');
		$audit->log((int) $user->id, 'seed', empty($seed['errors']) ? 'ok' : 'partial',
			0, $seed['ok'], 0, note: "mode={$seed_mode}", errors: $seed['errors'], entity: $eid);

		$snap = $backup->createBackup('snapshot');
		$errs = array_merge($errs, $snap['errors']);
		if ($snap['rowid']) {
			dolibarr_set_const($db, 'MOKODOLITRAINING_SNAPSHOT_ROWID', (string) $snap['rowid'], 'chaine', 0, '', $eid);
			setEventMessages(sprintf($langs->trans('ResultSnapshotCreated'), $snap['rowid'], $snap['rows']), null, 'mesgs');
			$audit->log((int) $user->id, 'backup_create', empty($snap['errors']) ? 'ok' : 'partial',
				$snap['rows'], 0, 0, 'rowid:' . $snap['rowid'], $snap['checksum'], $snap['errors'], entity: $eid);
		}

		if ($errs) setEventMessages(null, $errs, 'errors');
		$ms = (int) ((hrtime(true) - $t0) / 1e6);
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEEDED',    '1',          'chaine', 0, '', $eid);
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEED_MODE', $seed_mode,   'chaine', 0, '', $eid);
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEED_DATE', gmdate('c'),  'chaine', 0, '', $eid);
		$audit->log((int) $user->id, 'install', empty($errs) ? 'ok' : 'partial', 0, 0, $ms, errors: $errs, entity: $eid);
		$backup->releaseLock();
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Action: reset to snapshot ─────────────────────────────────────────────────
if ($action === 'reset_snapshot' && !$can_reset && !$can_manage) accessforbidden('', true, null, 403);
if ($action === 'reset_snapshot') {
	$snap_rowid = $backup->getLatest('snapshot') ?: (int) getDolGlobalString('MOKODOLITRAINING_SNAPSHOT_ROWID');
	if (!$snap_rowid) {
		setEventMessages('No snapshot backup found. Run Install first.', null, 'errors');
	} elseif ($backup->isLocked()) {
		setEventMessages($langs->trans('BackupLockWait'), null, 'warnings');
	} elseif ($backup->acquireLock()) {
		$t0 = hrtime(true);
		$errs = [];
		$del  = $seeder->reset((int) $conf->entity);  // delete all manifest-tracked rows
		$errs = array_merge($errs, $del['errors']);
		setEventMessages(sprintf($langs->trans('ResultResetOk'), $del['ok']), null, 'mesgs');
		$res2 = $backup->restoreById($snap_rowid);
		$errs = array_merge($errs, $res2['errors']);
		// Re-sync manifest to post-seed baseline so trigger can track new trainee records
		$seeder->resyncManifest((int) $conf->entity);
		setEventMessages(sprintf($langs->trans('ResultRestoreOk'), $res2['ok']), null, 'mesgs');
		if ($errs) setEventMessages(null, $errs, 'errors');
		dolibarr_set_const($db, 'MOKODOLITRAINING_RESET_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'reset_snapshot', empty($errs) ? 'ok' : 'partial',
			$res2['ok'], 0, (int) ((hrtime(true) - $t0) / 1e6), 'rowid:' . $snap_rowid, errors: $errs, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Action: rollback ──────────────────────────────────────────────────────────
if ($action === 'rollback' && !$can_manage) accessforbidden('', true, null, 403);
if ($action === 'rollback') {
	$rb_rowid = $backup->getLatest('rollback') ?: (int) getDolGlobalString('MOKODOLITRAINING_ROLLBACK_ROWID');
	if (!$rb_rowid) {
		setEventMessages('No rollback backup found. Run Install first.', null, 'errors');
	} elseif ($backup->isLocked()) {
		setEventMessages($langs->trans('BackupLockWait'), null, 'warnings');
	} elseif ($backup->acquireLock()) {
		$t0 = hrtime(true);
		$errs = [];
		$del  = $seeder->reset((int) $conf->entity);  // delete all manifest-tracked rows
		$errs = array_merge($errs, $del['errors']);
		setEventMessages(sprintf($langs->trans('ResultResetOk'), $del['ok']), null, 'mesgs');
		$res2 = $backup->restoreById($rb_rowid);
		$errs = array_merge($errs, $res2['errors']);
		setEventMessages(sprintf($langs->trans('ResultRestoreOk'), $res2['ok']), null, 'mesgs');
		if ($errs) setEventMessages(null, $errs, 'errors');
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEEDED',     '0',         'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MOKODOLITRAINING_RESET_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'rollback', empty($errs) ? 'ok' : 'partial',
			$res2['ok'], 0, (int) ((hrtime(true) - $t0) / 1e6), 'rowid:' . $rb_rowid, errors: $errs, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── State ─────────────────────────────────────────────────────────────────────
$is_seeded   = getDolGlobalString('MOKODOLITRAINING_SEEDED') === '1';
$seed_mode   = getDolGlobalString('MOKODOLITRAINING_SEED_MODE') ?: 'training';
$seed_date   = getDolGlobalString('MOKODOLITRAINING_SEED_DATE');
$reset_date  = getDolGlobalString('MOKODOLITRAINING_RESET_DATE');
$rb_rowid    = $backup->getLatest('rollback');
$snap_rowid  = $backup->getLatest('snapshot');
$max_backups = (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10);
$log_ret     = (int) (getDolGlobalString('MOKODOLITRAINING_LOG_RETENTION') ?: 90);
$is_locked   = $backup->isLocked();
$summary     = mokodolitraining_get_manifest_summary();
$all_backups = $backup->listBackups();
$recent_logs = $audit->getRecent(5, (int) $conf->entity);

// Backup health: verify both key backups
$rb_health   = null; // null=none, true=ok, false=fail
$snap_health = null;
foreach ([['rollback', &$rb_health, $rb_rowid], ['snapshot', &$snap_health, $snap_rowid]] as [, &$h, $rid]) {
	if ($rid) {
		$v = $backup->verifyIntegrity($rid);
		$h = $v['ok'];
	}
}

// ── Output ────────────────────────────────────────────────────────────────────
llxHeader('', $langs->trans('PageTitleSetup'));

$linkback = $can_manage
	? '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans('BackToModuleList') . '</a>'
	: '';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');

if ($is_locked) print info_admin($langs->trans('StatusLocked'), 0, 0, 1);
dol_htmloutput_events();

// Tabs: only manage users navigate the full admin section
$admin_tabs = $can_manage ? mokodolitraining_admin_tabs() : [];
print dol_get_fiche_head($admin_tabs, 'setup', '', -1, 'technic');

// ── Stat counter boxes ────────────────────────────────────────────────────────
$box_w = $can_manage ? '25%' : '50%';
print '<div class="div-table-responsive-no-min" style="margin-bottom:20px">';
print '<table class="nobordernopadding centpercent"><tr>';

// Dataset status
$ds_color   = $is_seeded ? '#28a745' : '#6c757d';
$ds_lbl     = $is_seeded ? $langs->trans('StatusSeeded') : $langs->trans('StatusNotSeeded');
$ds_icon    = $is_seeded ? 'check' : 'help';
$mode_badge = ($seed_mode === 'demo')
	? '<span class="badge badge-status1" style="font-size:0.55em;vertical-align:middle;margin-left:6px">' . $langs->trans('SeedModeDemo') . '</span>'
	: '<span class="badge badge-status4" style="font-size:0.55em;vertical-align:middle;margin-left:6px">' . $langs->trans('SeedModeTraining') . '</span>';
printf(
	'<td style="width:%s;padding:4px">'
	. '<div class="box-flex-item box-flex-item-with-label" style="border-left:4px solid %s;padding:12px 16px">'
	. '<div class="box-flex-item-label opacitymedium">%s</div>'
	. '<div style="font-size:1.4em;font-weight:bold">%s %s%s</div>'
	. '</div></td>',
	$box_w, $ds_color,
	$langs->trans('DashDatasetStatus'),
	img_picto('', $ds_icon),
	$ds_lbl,
	$is_seeded ? $mode_badge : ''
);

// Tracked rows
printf(
	'<td style="width:%s;padding:4px">'
	. '<div class="box-flex-item box-flex-item-with-label" style="border-left:4px solid #17a2b8;padding:12px 16px">'
	. '<div class="box-flex-item-label opacitymedium">%s</div>'
	. '<div style="font-size:1.4em;font-weight:bold">%d <span class="opacitymedium" style="font-size:0.6em">%s / %d %s</span></div>'
	. '</div></td>',
	$box_w,
	$langs->trans('DashTrackedRows'),
	$summary['rows'],
	$langs->trans('LabelRows'),
	$summary['tables'],
	$langs->trans('LabelTables')
);

if ($can_manage) {
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
	$h_rb   = is_null($rb_health)   ? ['--', '#6c757d', 'help']    : ($rb_health   ? [$langs->trans('DashHealthOk'), '#28a745', 'check'] : [$langs->trans('DashHealthFail'), '#dc3545', 'error']);
	$h_snap = is_null($snap_health) ? ['--', '#6c757d', 'help']    : ($snap_health ? [$langs->trans('DashHealthOk'), '#28a745', 'check'] : [$langs->trans('DashHealthFail'), '#dc3545', 'error']);
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
}

print '</tr></table></div>';

// ── Key dates + file info ─────────────────────────────────────────────────────
print '<table class="noborder centpercent" style="margin-bottom:20px">';
print '<tr class="liste_titre">';
printf('<th>%s</th><th>%s</th>', $langs->trans('LabelLastSeeded'), $langs->trans('LabelLastReset'));
if ($can_manage) {
	printf('<th>%s</th><th>%s</th>', $langs->trans('LabelRollbackBackup'), $langs->trans('LabelSnapshotBackup'));
}
print '</tr><tr class="oddeven">';

$none = '<span class="opacitymedium">' . $langs->trans('LabelNone') . '</span>';

printf('<td>%s</td>', $seed_date  ? dol_print_date(dol_stringtotime($seed_date),  'dayhour') : $none);
printf('<td>%s</td>', $reset_date ? dol_print_date(dol_stringtotime($reset_date), 'dayhour') : $none);

if ($can_manage) {
	foreach ([$rb_rowid, $snap_rowid] as $rid) {
		if ($rid) {
			printf('<td>' . img_picto('', 'download', 'class="pictofixedwidth"') . '<span class="small opacitymedium">rowid:%d</span></td>',
				(int) $rid);
		} else {
			print '<td>' . $none . '</td>';
		}
	}
}

print '</tr></table>';

// ── Recent activity (manage only) ────────────────────────────────────────────
if ($can_manage):
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
endif; // can_manage recent activity

// ── Settings form (manage only) ───────────────────────────────────────────────
if ($can_manage):
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

// Show $dolibarr_main_demo status only when in demo mode — has no effect in training mode
if ($user->admin && $seed_mode === 'demo') {
	global $dolibarr_main_demo;
	$demo_conf_set = !empty($dolibarr_main_demo);
	$demo_conf_val = $demo_conf_set
		? '<code>' . dol_htmlentities($dolibarr_main_demo) . '</code>'
		: '<span class="opacitymedium">' . $langs->trans('SettingDemoCredNotSet') . '</span>';
	printf(
		'<tr class="oddeven">'
		. '<td class="titlefield">%s %s</td>'
		. '<td>%s</td>'
		. '<td class="opacitymedium">%s</td></tr>',
		$langs->trans('SettingDemoCred'),
		img_picto($langs->trans('SuperAdminOnly'), 'lock', 'class="pictofixedwidth opacitymedium"'),
		$demo_conf_val,
		$langs->trans('SettingDemoCredHelp')
	);
}

print '</table>';
endif; // can_manage settings form

print dol_fiche_end();

// ── tabsAction ────────────────────────────────────────────────────────────────
print '<div class="tabsAction">';

if ($can_manage) {
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" style="display:inline">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<button type="submit" name="action" value="save_settings" class="butAction">'
		. img_picto('', 'save', 'class="pictofixedwidth"')
		. $langs->trans('ActionSaveSettings') . '</button>';

	// Mode selector — shown inline before the install button
	printf(
		'<span style="margin-left:8px;margin-right:4px">'
		. '<label class="opacitymedium" style="font-size:13px">%s</label> '
		. '<select name="seed_mode" class="flat" style="height:28px;padding:2px 6px;font-size:13px">'
		. '<option value="training"%s>%s</option>'
		. '<option value="demo"%s>%s</option>'
		. '</select>'
		. '</span>',
		$langs->trans('SeedModeLabel'),
		($seed_mode !== 'demo') ? ' selected' : '',
		$langs->trans('SeedModeTrainingFull'),
		($seed_mode === 'demo') ? ' selected' : '',
		$langs->trans('SeedModeDemoFull')
	);

	printf(
		'<button type="submit" name="action" value="install" class="butAction"%s onclick="return confirm(\'%s\');">%s%s</button>',
		$is_locked ? ' disabled' : '',
		dol_escape_js($langs->trans('ConfirmInstall')),
		img_picto('', 'technic', 'class="pictofixedwidth"'),
		$langs->trans('ActionInstall')
	);
	print '</form>';
}

if ($can_reset || $can_manage) {
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" style="display:inline">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	printf(
		'<button type="submit" name="action" value="reset_snapshot" class="butAction"%s onclick="return confirm(\'%s\');">%s%s</button>',
		($is_locked || !$snap_rowid) ? ' disabled' : '',
		dol_escape_js($langs->trans('ConfirmResetSnapshot')),
		img_picto('', 'refresh', 'class="pictofixedwidth"'),
		$langs->trans('ActionResetSnapshot')
	);
	print '</form>';
}

if ($can_manage) {
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" style="display:inline">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	printf(
		'<button type="submit" name="action" value="rollback" class="butActionDelete"%s onclick="return confirm(\'%s\');">%s%s</button>',
		($is_locked || !$rb_rowid) ? ' disabled' : '',
		dol_escape_js($langs->trans('ConfirmRollback')),
		img_picto('', 'error', 'class="pictofixedwidth"'),
		$langs->trans('ActionRollback')
	);
	print '</form>';
}

print '</div>';

llxFooter();
$db->close();
