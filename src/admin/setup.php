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
 * VERSION:  01.00.00
 * BRIEF:    Admin setup page: install, reset, rollback, settings, manifest viewer.
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

$msgs   = [];
$errors = [];

// ── CSRF check for all POST actions ──────────────────────────────────────────
$post_actions = ['install', 'reset_snapshot', 'rollback', 'save_settings'];
if (in_array($action, $post_actions, true) && !verifCsrfToken()) {
	accessforbidden('Invalid token');
}

// ── Action: save settings ─────────────────────────────────────────────────────
if ($action === 'save_settings') {
	$max = max(2, (int) GETPOST('max_backups', 'int'));
	$ret = max(7, (int) GETPOST('log_retention', 'int'));

	dolibarr_set_const($db, 'MOKODOLITRAINING_MAX_BACKUPS',   (string) $max, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'MOKODOLITRAINING_LOG_RETENTION', (string) $ret, 'chaine', 0, '', $conf->entity);

	$audit->log((int) $user->id, 'integrity_check', 'ok', note: "Settings saved: max_backups={$max} log_retention={$ret}", entity: (int) $conf->entity);
	$msgs[] = $langs->trans('ResultSettingsSaved');
}

// ── Action: install ───────────────────────────────────────────────────────────
if ($action === 'install') {
	if ($backup->isLocked()) {
		$errors[] = $langs->trans('BackupLockWait');
	} elseif ($backup->acquireLock()) {
		$t0 = hrtime(true);

		// 1. Rollback backup — pre-seed state
		$rb = $backup->createBackup('rollback');
		$errors = array_merge($errors, $rb['errors']);
		if ($rb['path']) {
			dolibarr_set_const($db, 'MOKODOLITRAINING_ROLLBACK_FILE', $rb['path'], 'chaine', 0, '', $conf->entity);
			$msgs[] = sprintf($langs->trans('ResultRollbackCreated'), basename($rb['path']), $rb['rows']);
			$audit->log((int) $user->id, 'backup_create', empty($rb['errors']) ? 'ok' : 'partial',
				$rb['rows'], 0, 0, $rb['path'], $rb['checksum'], $rb['errors'], entity: (int) $conf->entity);
		}

		// 2. Seed
		$seed = $backup->runSeed();
		$errors = array_merge($errors, $seed['errors']);
		$msgs[] = sprintf($langs->trans('ResultSeedOk'), $seed['ok']);
		$audit->log((int) $user->id, 'seed', empty($seed['errors']) ? 'ok' : 'partial',
			0, $seed['ok'], 0, errors: $seed['errors'], entity: (int) $conf->entity);

		// 3. Snapshot — post-seed state
		$snap = $backup->createBackup('snapshot');
		$errors = array_merge($errors, $snap['errors']);
		if ($snap['path']) {
			dolibarr_set_const($db, 'MOKODOLITRAINING_SNAPSHOT_FILE', $snap['path'], 'chaine', 0, '', $conf->entity);
			$msgs[] = sprintf($langs->trans('ResultSnapshotCreated'), basename($snap['path']), $snap['rows']);
			$audit->log((int) $user->id, 'backup_create', empty($snap['errors']) ? 'ok' : 'partial',
				$snap['rows'], 0, 0, $snap['path'], $snap['checksum'], $snap['errors'], entity: (int) $conf->entity);
		}

		$ms = (int) ((hrtime(true) - $t0) / 1e6);
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEEDED',    '1',         'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEED_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);

		$status = empty($errors) ? 'ok' : 'partial';
		$audit->log((int) $user->id, 'install', $status, 0, 0, $ms, errors: $errors, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
}

// ── Action: reset to snapshot ─────────────────────────────────────────────────
if ($action === 'reset_snapshot') {
	$snap_path = $backup->getLatest('snapshot') ?: getDolGlobalString('MOKODOLITRAINING_SNAPSHOT_FILE');

	if (!$snap_path || !file_exists($snap_path)) {
		$errors[] = 'No snapshot backup found. Run Install first.';
	} elseif ($backup->isLocked()) {
		$errors[] = $langs->trans('BackupLockWait');
	} elseif ($backup->acquireLock()) {
		$t0 = hrtime(true);

		$del = $backup->runReset();
		$errors = array_merge($errors, $del['errors']);
		$msgs[] = sprintf($langs->trans('ResultResetOk'), $del['ok']);

		$res = $backup->restoreFromFile($snap_path);
		$errors = array_merge($errors, $res['errors']);
		$msgs[] = sprintf($langs->trans('ResultRestoreOk'), $res['ok']);

		$ms     = (int) ((hrtime(true) - $t0) / 1e6);
		$status = empty($errors) ? 'ok' : 'partial';
		dolibarr_set_const($db, 'MOKODOLITRAINING_RESET_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'reset_snapshot', $status, $res['ok'], 0, $ms, $snap_path, errors: $errors, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
}

// ── Action: rollback ──────────────────────────────────────────────────────────
if ($action === 'rollback') {
	$rb_path = $backup->getLatest('rollback') ?: getDolGlobalString('MOKODOLITRAINING_ROLLBACK_FILE');

	if (!$rb_path || !file_exists($rb_path)) {
		$errors[] = 'No rollback backup found. Run Install first.';
	} elseif ($backup->isLocked()) {
		$errors[] = $langs->trans('BackupLockWait');
	} elseif ($backup->acquireLock()) {
		$t0 = hrtime(true);

		$del = $backup->runReset();
		$errors = array_merge($errors, $del['errors']);
		$msgs[] = sprintf($langs->trans('ResultResetOk'), $del['ok']);

		$res = $backup->restoreFromFile($rb_path);
		$errors = array_merge($errors, $res['errors']);
		$msgs[] = sprintf($langs->trans('ResultRestoreOk'), $res['ok']);

		$ms     = (int) ((hrtime(true) - $t0) / 1e6);
		$status = empty($errors) ? 'ok' : 'partial';
		dolibarr_set_const($db, 'MOKODOLITRAINING_SEEDED',     '0',         'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MOKODOLITRAINING_RESET_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'rollback', $status, $res['ok'], 0, $ms, $rb_path, errors: $errors, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
}

// ── State ─────────────────────────────────────────────────────────────────────
$is_seeded   = getDolGlobalString('MOKODOLITRAINING_SEEDED') === '1';
$seed_date   = getDolGlobalString('MOKODOLITRAINING_SEED_DATE');
$reset_date  = getDolGlobalString('MOKODOLITRAINING_RESET_DATE');
$rb_file     = $backup->getLatest('rollback');
$snap_file   = $backup->getLatest('snapshot');
$max_backups = (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10);
$log_ret     = (int) (getDolGlobalString('MOKODOLITRAINING_LOG_RETENTION') ?: 90);
$summary     = modMokoDoliTraining::getManifestSummary();
$manifest    = modMokoDoliTraining::getManifest();
$is_locked   = $backup->isLocked();

// ── Output ────────────────────────────────────────────────────────────────────
llxHeader('', $langs->trans('PageTitleSetup'));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');

// Tabs
print mokodolitraining_admin_tabs('setup');

// Notices
if ($is_locked) {
	print '<div class="warning">' . $langs->trans('StatusLocked') . '</div>';
}
foreach ($msgs as $m) {
	print '<div class="ok">' . dol_htmlentities($m) . '</div>';
}
foreach ($errors as $e) {
	print '<div class="error">' . dol_htmlentities($e) . '</div>';
}

// ── Status dashboard ──────────────────────────────────────────────────────────
print '<br>';
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans('TabSetup') . '</th>';
print '<th>' . $langs->trans('LabelLastSeeded') . '</th>';
print '<th>' . $langs->trans('LabelLastReset') . '</th>';
print '<th>' . $langs->trans('LabelRollbackBackup') . '</th>';
print '<th>' . $langs->trans('LabelSnapshotBackup') . '</th>';
print '<th>' . $langs->trans('LabelTracked') . '</th>';
print '</tr><tr class="oddeven">';

printf('<td>%s</td>',
	$is_seeded
		? '<span class="badge badge-status4">' . $langs->trans('StatusSeeded') . '</span>'
		: '<span class="badge badge-status0">' . $langs->trans('StatusNotSeeded') . '</span>'
);
print '<td>' . ($seed_date  ? dol_htmlentities($seed_date)  : '<em>' . $langs->trans('LabelNone') . '</em>') . '</td>';
print '<td>' . ($reset_date ? dol_htmlentities($reset_date) : '<em>' . $langs->trans('LabelNone') . '</em>') . '</td>';

foreach ([$rb_file, $snap_file] as $bk) {
	print '<td>' . ($bk
		? '<span class="badge badge-status1" title="' . dol_htmlentities($bk) . '">' . dol_htmlentities(basename($bk)) . '</span>'
		: '<span class="badge badge-status8">' . $langs->trans('LabelNone') . '</span>'
	) . '</td>';
}

printf('<td>%d %s / %d %s</td>',
	$summary['tables'], $langs->trans('LabelTables'),
	$summary['rows'],   $langs->trans('LabelRows')
);
print '</tr></table></div>';

// ── Action buttons ─────────────────────────────────────────────────────────────
print '<br>';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<div class="tabsAction">';

printf(
	'<button type="submit" name="action" value="install" class="butAction"%s onclick="return confirm(\'%s\');">%s</button>',
	$is_locked ? ' disabled' : '',
	dol_escape_js($langs->trans('ConfirmInstall')),
	$langs->trans('ActionInstall')
);

printf(
	'&nbsp;<button type="submit" name="action" value="reset_snapshot" class="butAction"%s onclick="return confirm(\'%s\');">%s</button>',
	($is_locked || !$snap_file) ? ' disabled' : '',
	dol_escape_js($langs->trans('ConfirmResetSnapshot')),
	$langs->trans('ActionResetSnapshot')
);

printf(
	'&nbsp;<button type="submit" name="action" value="rollback" class="butActionDelete"%s onclick="return confirm(\'%s\');">%s</button>',
	($is_locked || !$rb_file) ? ' disabled' : '',
	dol_escape_js($langs->trans('ConfirmRollback')),
	$langs->trans('ActionRollback')
);

print '</div></form>';

// ── Settings ───────────────────────────────────────────────────────────────────
print '<br><h3>' . $langs->trans('SettingsTitle') . '</h3>';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre"><th colspan="3">' . $langs->trans('SettingsTitle') . '</th></tr>';

// Max backups
printf('<tr class="oddeven"><td class="fieldrequired">%s</td><td><input type="number" name="max_backups" value="%d" min="2" max="50" class="width50"> <span class="opacitymedium">%s</span></td></tr>',
	$langs->trans('SettingMaxBackups'),
	$max_backups,
	$langs->trans('SettingMaxBackupsHelp')
);

// Log retention
printf('<tr class="oddeven"><td class="fieldrequired">%s</td><td><input type="number" name="log_retention" value="%d" min="7" max="3650" class="width50"> <span class="opacitymedium">%s</span></td></tr>',
	$langs->trans('SettingLogRetention'),
	$log_ret,
	$langs->trans('SettingLogRetentionHelp')
);

print '</table>';
print '<div class="tabsAction">';
printf('<button type="submit" name="action" value="save_settings" class="butAction">%s</button>', $langs->trans('ActionSaveSettings'));
print '</div></form>';

// ── Manifest ───────────────────────────────────────────────────────────────────
print '<br><h3>' . $langs->trans('ManifestTitle') . '</h3>';
if (empty($manifest)) {
	print '<p><em>' . $langs->trans('ManifestEmpty') . '</em></p>';
} else {
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	printf('<tr class="liste_titre"><th>%s</th><th>%s</th><th>%s</th></tr>',
		$langs->trans('ManifestTable'), $langs->trans('ManifestRowCount'), $langs->trans('ManifestRowIds'));
	$i = 0;
	foreach ($manifest as $tbl => $ids) {
		printf('<tr class="%s"><td><code>%s</code></td><td>%d</td><td style="font-size:0.82em;word-break:break-all;">%s</td></tr>',
			($i++ % 2 === 0) ? 'even' : 'odd',
			dol_htmlentities($tbl),
			count($ids),
			dol_htmlentities(implode(', ', $ids))
		);
	}
	print '</table></div>';
}

llxFooter();
$db->close();
