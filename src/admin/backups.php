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
 * VERSION:  01.00.00
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

$msgs   = [];
$errors = [];

// ── CSRF guard ────────────────────────────────────────────────────────────────
$post_actions = ['verify', 'delete', 'restore_file'];
if (in_array($action, $post_actions, true) && !verifCsrfToken()) {
	accessforbidden('Invalid token');
}

// ── Action: verify ────────────────────────────────────────────────────────────
if ($action === 'verify' && $filename) {
	$path   = $backup->getBackupByName($filename);
	if (!$path) {
		$errors[] = $langs->trans('BackupDownloadFail');
	} else {
		$result = $backup->verifyIntegrity($path);
		if ($result['ok']) {
			$msgs[] = sprintf($langs->trans('ResultIntegrityOk'), dol_htmlentities($filename));
			$audit->log((int) $user->id, 'integrity_check', 'ok', note: 'Verified: ' . $filename, entity: (int) $conf->entity);
		} else {
			$errors[] = sprintf($langs->trans('ResultIntegrityFail'), dol_htmlentities($filename), dol_htmlentities($result['reason']));
			$audit->log((int) $user->id, 'integrity_check', 'error', note: $filename . ': ' . $result['reason'], entity: (int) $conf->entity);
		}
	}
}

// ── Action: delete ────────────────────────────────────────────────────────────
if ($action === 'delete' && $filename) {
	$path = $backup->getBackupByName($filename);
	if (!$path) {
		$errors[] = $langs->trans('BackupDownloadFail');
	} else {
		@unlink($path);
		@unlink($path . '.sha256');
		if (!file_exists($path)) {
			$msgs[] = $langs->trans('BackupDeleteOk');
			$audit->log((int) $user->id, 'backup_delete', 'ok', note: 'Deleted: ' . $filename, entity: (int) $conf->entity);
		} else {
			$errors[] = $langs->trans('BackupDeleteFail');
		}
	}
}

// ── Action: restore from specific backup ──────────────────────────────────────
if ($action === 'restore_file' && $filename) {
	$path = $backup->getBackupByName($filename);
	if (!$path) {
		$errors[] = $langs->trans('BackupDownloadFail');
	} elseif ($backup->isLocked()) {
		$errors[] = $langs->trans('BackupLockWait');
	} elseif ($backup->acquireLock()) {
		$t0  = hrtime(true);
		$del = $backup->runReset();
		$errors = array_merge($errors, $del['errors']);

		$res = $backup->restoreFromFile($path);
		$errors = array_merge($errors, $res['errors']);

		$ms     = (int) ((hrtime(true) - $t0) / 1e6);
		$status = empty($errors) ? 'ok' : 'partial';

		if ($status === 'ok') {
			$msgs[] = sprintf($langs->trans('ResultRestoreOk'), $res['ok']);
		} else {
			$errors[] = $langs->trans('BackupRestoreFail');
		}

		dolibarr_set_const($db, 'MOKODOLITRAINING_RESET_DATE', gmdate('c'), 'chaine', 0, '', $conf->entity);
		$audit->log((int) $user->id, 'backup_restore', $status, $res['ok'], 0, $ms, $path, errors: $errors, entity: (int) $conf->entity);
		$backup->releaseLock();
	}
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

// ── Output ─────────────────────────────────────────────────────────────────────
llxHeader('', $langs->trans('PageTitleBackups'));
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');
print mokodolitraining_admin_tabs('backups');

if ($is_locked) print '<div class="warning">' . $langs->trans('StatusLocked') . '</div>';
foreach ($msgs   as $m) print '<div class="ok">'    . dol_htmlentities($m) . '</div>';
foreach ($errors as $e) print '<div class="error">' . dol_htmlentities($e) . '</div>';

print '<br>';

if (empty($all_backups)) {
	print '<p><em>' . $langs->trans('BackupNoneYet') . '</em></p>';
} else {
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	printf('<tr class="liste_titre"><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>',
		$langs->trans('BackupFile'),
		$langs->trans('BackupType'),
		$langs->trans('BackupTimestamp'),
		$langs->trans('BackupSize'),
		$langs->trans('BackupChecksum'),
		$langs->trans('BackupActions')
	);

	foreach ($all_backups as $i => $b) {
		$cls   = ($i % 2 === 0) ? 'even' : 'odd';
		$badge = $b['type'] === 'rollback' ? 'badge-status8' : 'badge-status4';
		$tok   = newToken();
		$enc   = urlencode($b['name']);

		// Inline forms for each row action
		$form_verify  = '<form method="POST" action="' . $self . '" style="display:inline">'
			. '<input type="hidden" name="token" value="' . $tok . '">'
			. '<input type="hidden" name="filename" value="' . dol_htmlentities($b['name']) . '">'
			. '<button type="submit" name="action" value="verify" class="butActionSmall">'
			. $langs->trans('ActionVerify') . '</button></form>';

		$form_restore = '<form method="POST" action="' . $self . '" style="display:inline">'
			. '<input type="hidden" name="token" value="' . $tok . '">'
			. '<input type="hidden" name="filename" value="' . dol_htmlentities($b['name']) . '">'
			. '<button type="submit" name="action" value="restore_file" class="butActionSmall"'
			. ($is_locked ? ' disabled' : '')
			. ' onclick="return confirm(\'' . dol_escape_js($langs->trans('ConfirmRestoreBackup')) . '\');">'
			. $langs->trans('ActionRestoreThis') . '</button></form>';

		$form_delete  = '<form method="POST" action="' . $self . '" style="display:inline">'
			. '<input type="hidden" name="token" value="' . $tok . '">'
			. '<input type="hidden" name="filename" value="' . dol_htmlentities($b['name']) . '">'
			. '<button type="submit" name="action" value="delete" class="butActionDeleteSmall"'
			. ' onclick="return confirm(\'' . dol_escape_js($langs->trans('ConfirmDeleteBackup')) . '\');">'
			. $langs->trans('ActionDelete') . '</button></form>';

		$dl_url       = $self . '?action=download&filename=' . $enc . '&token=' . urlencode($tok);
		$link_dl      = '<a href="' . $dl_url . '" class="butActionSmall">' . $langs->trans('ActionDownload') . '</a>';

		$checksum_disp = $b['checksum']
			? '<code title="' . dol_htmlentities($b['checksum']) . '">' . substr($b['checksum'], 0, 8) . '...</code>'
			: '<em>' . $langs->trans('LabelNone') . '</em>';

		printf(
			'<tr class="%s"><td><code>%s</code></td><td><span class="badge %s">%s</span></td><td>%s</td><td>%s</td><td>%s</td><td style="white-space:nowrap">%s %s %s %s</td></tr>',
			$cls,
			dol_htmlentities($b['name']),
			$badge, $langs->trans('BackupType' . ucfirst($b['type'])),
			dol_htmlentities($b['ts']),
			mokodolitraining_format_bytes($b['size']),
			$checksum_disp,
			$form_verify, $form_restore, $link_dl, $form_delete
		);
	}

	print '</table></div>';
}

llxFooter();
$db->close();
