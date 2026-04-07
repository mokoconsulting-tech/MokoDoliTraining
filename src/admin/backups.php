<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Admin
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/admin/backups.php
 * VERSION:  01.00.04
 * BRIEF:    Backup manager: list, verify integrity, restore from specific backup, delete.
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
$action   = GETPOST('action',   'aZ09');
$filename = GETPOST('filename', 'alpha');

['backup' => $backup, 'audit' => $audit] = mokodolitraining_load_classes($db);


// ── Action: verify ────────────────────────────────────────────────────────────
if ($action === 'verify' && $filename) {
	$path = $backup->getBackupByName($filename);
	if (!$path) {
		setEventMessages($langs->trans('BackupDownloadFail'), null, 'errors');
	} else {
		$result = $backup->verifyIntegrity($path);
		if ($result['ok']) {
			setEventMessages(sprintf($langs->trans('ResultIntegrityOk'), dol_htmlentities($filename)), null, 'mesgs');
			$audit->log((int) $user->id, 'integrity_check', 'ok', note: 'Verified: ' . $filename, entity: (int) $conf->entity);
		} else {
			setEventMessages(sprintf($langs->trans('ResultIntegrityFail'), $filename, $result['reason']), null, 'errors');
			$audit->log((int) $user->id, 'integrity_check', 'error', note: $filename . ': ' . $result['reason'], entity: (int) $conf->entity);
		}
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Action: delete ────────────────────────────────────────────────────────────
if ($action === 'delete' && $filename) {
	$path = $backup->getBackupByName($filename);
	if (!$path) {
		setEventMessages($langs->trans('BackupDownloadFail'), null, 'errors');
	} else {
		@unlink($path);
		@unlink($path . '.sha256');
		if (!file_exists($path)) {
			setEventMessages($langs->trans('BackupDeleteOk'), null, 'mesgs');
			$audit->log((int) $user->id, 'backup_delete', 'ok', note: 'Deleted: ' . $filename, entity: (int) $conf->entity);
		} else {
			setEventMessages($langs->trans('BackupDeleteFail'), null, 'errors');
		}
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Action: restore from specific backup ──────────────────────────────────────
if ($action === 'restore_file' && $filename) {
	$path = $backup->getBackupByName($filename);
	if (!$path) {
		setEventMessages($langs->trans('BackupDownloadFail'), null, 'errors');
	} elseif ($backup->isLocked()) {
		setEventMessages($langs->trans('BackupLockWait'), null, 'warnings');
	} elseif ($backup->acquireLock()) {
		$t0   = hrtime(true);
		$errs = [];

		$del = $backup->runReset();
		$errs = array_merge($errs, $del['errors']);

		$res = $backup->restoreFromFile($path);
		$errs = array_merge($errs, $res['errors']);

		$ms     = (int) ((hrtime(true) - $t0) / 1e6);
		$status = empty($errs) ? 'ok' : 'partial';

		if ($status === 'ok') {
			setEventMessages(sprintf($langs->trans('ResultRestoreOk'), $res['ok']), null, 'mesgs');
		} else {
			setEventMessages($langs->trans('BackupRestoreFail'), null, 'errors');
			if ($errs) setEventMessages(null, $errs, 'errors');
		}

		dolibarr_set_const($db, 'MOKODOLITRAINING_RESET_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'backup_restore', $status, $res['ok'], 0, $ms, $path, errors: $errs, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Action: download (redirect to handler) ────────────────────────────────────
if ($action === 'download' && $filename) {
	$dl_url = dol_buildpath('/mokodolitraining/admin/download.php', 1)
		. '?filename=' . urlencode($filename) . '&token=' . urlencode(newToken());
	header('Location: ' . $dl_url);
	exit;
}

// ── State ─────────────────────────────────────────────────────────────────────
$all_backups = $backup->listBackups();
$is_locked   = $backup->isLocked();
$self        = $_SERVER['PHP_SELF'];

// ── Output ────────────────────────────────────────────────────────────────────
llxHeader('', $langs->trans('PageTitleBackups'));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');

if ($is_locked) print info_admin($langs->trans('StatusLocked'), 0, 0, 1);
dol_htmloutput_events();

print dol_get_fiche_head(mokodolitraining_admin_tabs(), 'backups', '', -1, 'technic');

if (empty($all_backups)) {
	print '<div class="opacitymedium">' . img_picto('', 'info', 'class="pictofixedwidth"') . $langs->trans('BackupNoneYet') . '</div>';
} else {
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	printf(
		'<tr class="liste_titre">'
		. '<th>%s</th><th>%s</th><th class="center">%s</th>'
		. '<th class="right">%s</th><th>%s</th><th class="center">%s</th>'
		. '</tr>',
		$langs->trans('BackupFile'),
		$langs->trans('BackupType'),
		$langs->trans('BackupTimestamp'),
		$langs->trans('BackupSize'),
		$langs->trans('BackupChecksum'),
		$langs->trans('BackupActions')
	);

	foreach ($all_backups as $b) {
		$tok = newToken();
		$enc = urlencode($b['name']);

		// type badge
		$is_rollback = ($b['type'] === 'rollback');
		$badge_cls   = $is_rollback ? 'badge-status8' : 'badge-status4';
		$badge_lbl   = $is_rollback ? $langs->trans('BackupTypeRollback') : $langs->trans('BackupTypeSnapshot');

		// checksum display
		$cs_disp = $b['checksum']
			? '<span class="small" title="' . dol_htmlentities($b['checksum']) . '"><code>' . substr($b['checksum'], 0, 8) . '&hellip;</code></span>'
			: '<span class="opacitymedium">-</span>';

		// formatted timestamp
		$ts_disp = $b['ts']
			? dol_print_date(dol_stringtotime($b['ts']), 'dayhour')
			: '<span class="opacitymedium">-</span>';

		// action links using img_picto
		$form_base = '<form method="POST" action="' . $self . '" style="display:inline">'
			. '<input type="hidden" name="token" value="' . $tok . '">'
			. '<input type="hidden" name="filename" value="' . dol_htmlentities($b['name']) . '">';

		$btn_verify = $form_base
			. '<button type="submit" name="action" value="verify" class="reposition" title="' . $langs->trans('ActionVerify') . '">'
			. img_picto($langs->trans('ActionVerify'), 'check') . '</button></form>';

		$btn_restore = $form_base
			. '<button type="submit" name="action" value="restore_file" class="reposition"'
			. ($is_locked ? ' disabled' : '')
			. ' onclick="return confirm(\'' . dol_escape_js($langs->trans('ConfirmRestoreBackup')) . '\');"'
			. ' title="' . $langs->trans('ActionRestoreThis') . '">'
			. img_picto($langs->trans('ActionRestoreThis'), 'refresh') . '</button></form>';

		$btn_delete = $form_base
			. '<button type="submit" name="action" value="delete" class="reposition"'
			. ' onclick="return confirm(\'' . dol_escape_js($langs->trans('ConfirmDeleteBackup')) . '\');"'
			. ' title="' . $langs->trans('ActionDelete') . '">'
			. img_picto($langs->trans('ActionDelete'), 'delete') . '</button></form>';

		$dl_url = $self . '?action=download&filename=' . $enc . '&token=' . urlencode($tok);
		$btn_dl = '<a href="' . $dl_url . '" title="' . $langs->trans('ActionDownload') . '" class="reposition">'
			. img_picto($langs->trans('ActionDownload'), 'download') . '</a>';

		printf(
			'<tr class="oddeven">'
			. '<td class="small"><code>%s</code></td>'
			. '<td><span class="badge %s">%s</span></td>'
			. '<td class="center nowrap">%s</td>'
			. '<td class="right nowrap">%s</td>'
			. '<td>%s</td>'
			. '<td class="center nowrap" style="width:100px">%s %s %s %s</td>'
			. '</tr>',
			dol_htmlentities($b['name']),
			$badge_cls, $badge_lbl,
			$ts_disp,
			mokodolitraining_format_bytes($b['size']),
			$cs_disp,
			$btn_verify, $btn_restore, $btn_dl, $btn_delete
		);
	}

	print '</table></div>';
}

print dol_fiche_end();

llxFooter();
$db->close();
