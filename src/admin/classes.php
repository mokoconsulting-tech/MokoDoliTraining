<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Admin
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/admin/classes.php
 * VERSION:  development
 * BRIEF:    Training class manager: create sessions, enroll trainees into existing
 *           Dolibarr accounts, assign usergroup permissions, mass-suspend on close.
 *
 * Permissions:
 *   read   - view list and class details
 *   teach  - view and manage own classes (trainer = current user)
 *   manage - full CRUD on all classes
 */

$res = 0;
if (!$res && file_exists('../main.inc.php'))          $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))       $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))    $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/mokodolitraining/lib/mokodolitraining.lib.php');
dol_include_once('/mokodolitraining/class/MokoDoliTrainingClass.class.php');
dol_include_once('/mokodolitraining/class/MokoDoliTrainingAudit.class.php');

$can_manage = mokodolitraining_has_perm($user, 'manage');
$can_teach  = mokodolitraining_has_perm($user, 'teach');
$can_read   = mokodolitraining_has_perm($user, 'read');

if (!$can_manage && !$can_teach && !$can_read) {
	accessforbidden();
}

$langs->load('mokodolitraining@mokodolitraining');

$action   = GETPOST('action',   'aZ09');
$class_id = (int) GETPOST('id',       'int');
$fk_user  = (int) GETPOST('fk_user',  'int');

// Pagination / search params (list view)
$page          = max(0, (int) GETPOST('page',          'int'));
$search_status = GETPOST('search_status', 'int');   // '' = all
$search_q      = GETPOST('search_q',      'alphanohtml');

const PAGE_SIZE = 20;

$mgr   = new MokoDoliTrainingClass($db);
$audit = new MokoDoliTrainingAudit($db);
$entity = (int) $conf->entity;
$uid    = (int) $user->id;

// =============================================================================
// Permission helpers
// =============================================================================

/**
 * True if the current user may write to a specific class.
 * managers can touch any class; teachers can only touch their own.
 */
function _mdt_can_write_class(?array $class, bool $can_manage, bool $can_teach, int $uid): bool
{
	if ($can_manage) return true;
	if ($can_teach && $class !== null && (int) $class['fk_user_trainer'] === $uid) return true;
	return false;
}

// =============================================================================
// Actions (all writes require a CSRF token)
// =============================================================================

// ── CSV export (GET — must come before HTML output) ──────────────────────────
if ($action === 'export_roster' && $class_id && ($can_manage || $can_teach || $can_read)) {
	$class = $mgr->fetch($class_id, $entity);
	if ($class && (_mdt_can_write_class($class, $can_manage, $can_teach, $uid) || $can_read)) {
		$csv      = $mgr->exportRosterCsv($class_id);
		$filename = 'roster_' . preg_replace('/[^a-z0-9_-]/i', '_', $class['ref'] ?? $class_id) . '.csv';
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: no-cache');
		echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
		echo $csv;
		exit;
	}
}

// ── Create class ─────────────────────────────────────────────────────────────
if ($can_manage && $action === 'create_class' && !empty($_POST)) {
	if (!checkToken()) accessforbidden();

	$vErrors = [];
	$new_id  = $mgr->create([
		'ref'             => GETPOST('ref',             'alphanohtml'),
		'label'           => GETPOST('label',           'alphanohtml'),
		'date_start'      => GETPOST('date_start',      'alpha'),
		'date_end'        => GETPOST('date_end',        'alpha'),
		'fk_usergroup'    => GETPOST('fk_usergroup',    'int'),
		'fk_user_trainer' => GETPOST('fk_user_trainer', 'int'),
		'nb_max'          => GETPOST('nb_max',          'int'),
		'note_public'     => GETPOST('note_public',     'restricthtml'),
		'note_private'    => GETPOST('note_private',    'restricthtml'),
	], $uid, $entity, $vErrors);

	if ($new_id > 0) {
		$audit->log($uid, 'class_create', 'ok', 0, 0, 0, '', '', [],
			note: 'ref=' . GETPOST('ref', 'alphanohtml'), entity: $entity);
		setEventMessages($langs->trans('ClassCreated'), null, 'mesgs');
		session_write_close();
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $new_id);
		exit;
	}

	// Validation/DB error — translate lang keys and flash
	$msgs = array_map(function (string $k) use ($langs): string {
		return $langs->trans($k) !== $k ? $langs->trans($k) : $k;
	}, $vErrors);
	setEventMessages($langs->trans('ClassCreateFailed'), null, 'errors');
	if ($msgs) setEventMessages(null, $msgs, 'errors');
	session_write_close();
	header('Location: ' . $_SERVER['PHP_SELF'] . '?action=new_class');
	exit;
}

// ── Edit class ───────────────────────────────────────────────────────────────
if ($action === 'edit_class' && $class_id && !empty($_POST)) {
	if (!checkToken()) accessforbidden();

	$class = $mgr->fetch($class_id, $entity);
	if (!_mdt_can_write_class($class, $can_manage, $can_teach, $uid)) accessforbidden();

	$vErrors = [];
	$r = $mgr->update($class_id, [
		'ref'             => GETPOST('ref',             'alphanohtml'),
		'label'           => GETPOST('label',           'alphanohtml'),
		'date_start'      => GETPOST('date_start',      'alpha'),
		'date_end'        => GETPOST('date_end',        'alpha'),
		'fk_usergroup'    => GETPOST('fk_usergroup',    'int'),
		'fk_user_trainer' => GETPOST('fk_user_trainer', 'int'),
		'nb_max'          => GETPOST('nb_max',          'int'),
		'note_public'     => GETPOST('note_public',     'restricthtml'),
		'note_private'    => GETPOST('note_private',    'restricthtml'),
	], $entity, $vErrors);

	if ($r > 0) {
		$audit->log($uid, 'class_update', 'ok', 0, 0, 0, '', '', [],
			note: 'id=' . $class_id, entity: $entity);
		setEventMessages($langs->trans('ClassUpdated'), null, 'mesgs');
	} else {
		$msgs = array_map(function (string $k) use ($langs): string {
			return $langs->trans($k) !== $k ? $langs->trans($k) : $k;
		}, $vErrors);
		setEventMessages($langs->trans('ClassUpdateFailed'), null, 'errors');
		if ($msgs) setEventMessages(null, $msgs, 'errors');
	}
	session_write_close();
	header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $class_id);
	exit;
}

// ── Delete class ─────────────────────────────────────────────────────────────
if ($can_manage && $action === 'delete_class' && $class_id) {
	if (!checkToken()) accessforbidden();
	$r = $mgr->delete($class_id, $entity);
	if ($r > 0) {
		$audit->log($uid, 'class_delete', 'ok', 0, 0, 0, '', '', [],
			note: 'id=' . $class_id, entity: $entity);
		setEventMessages($langs->trans('ClassDeleted'), null, 'mesgs');
	} else {
		setEventMessages($langs->trans('ClassDeleteFailed'), null, 'errors');
	}
	session_write_close();
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// ── Activate class ───────────────────────────────────────────────────────────
if ($action === 'activate_class' && $class_id) {
	if (!checkToken()) accessforbidden();
	$class = $mgr->fetch($class_id, $entity);
	if (!_mdt_can_write_class($class, $can_manage, $can_teach, $uid)) accessforbidden();

	$r = $mgr->activateClass($class_id, $entity);
	if (empty($r['errors'])) {
		$audit->log($uid, 'class_activate', 'ok', $r['ok'], 0, 0, '', '', [],
			note: 'id=' . $class_id, entity: $entity);
		setEventMessages(sprintf($langs->trans('ClassActivated'), $r['ok']), null, 'mesgs');
	} else {
		$audit->log($uid, 'class_activate', 'error', $r['ok'], 0, 0, '', '', $r['errors'],
			note: 'id=' . $class_id, entity: $entity);
		setEventMessages($langs->trans('ClassActivateFailed'), null, 'errors');
		if ($r['errors']) setEventMessages(null, $r['errors'], 'errors');
	}
	session_write_close();
	header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $class_id);
	exit;
}

// ── Close class ──────────────────────────────────────────────────────────────
if ($action === 'close_class' && $class_id) {
	if (!checkToken()) accessforbidden();
	$class = $mgr->fetch($class_id, $entity);
	if (!_mdt_can_write_class($class, $can_manage, $can_teach, $uid)) accessforbidden();

	$r = $mgr->closeClass($class_id, $entity);
	if (empty($r['errors'])) {
		$audit->log($uid, 'class_close', 'ok', $r['ok'], 0, 0, '', '', [],
			note: 'id=' . $class_id . ' skipped=' . $r['skipped'], entity: $entity);
		setEventMessages(
			sprintf($langs->trans('ClassClosed'), $r['ok'], $r['skipped']),
			null, 'mesgs'
		);
	} else {
		$audit->log($uid, 'class_close', 'error', $r['ok'], 0, 0, '', '', $r['errors'],
			note: 'id=' . $class_id, entity: $entity);
		setEventMessages($langs->trans('ClassCloseFailed'), null, 'errors');
		if ($r['errors']) setEventMessages(null, $r['errors'], 'errors');
	}
	session_write_close();
	header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $class_id);
	exit;
}

// ── Enroll ───────────────────────────────────────────────────────────────────
if ($action === 'enroll' && $class_id && $fk_user) {
	if (!checkToken()) accessforbidden();
	$class = $mgr->fetch($class_id, $entity);
	if (!_mdt_can_write_class($class, $can_manage, $can_teach, $uid)) accessforbidden();

	$r = $mgr->enroll($class_id, $fk_user, $uid, $entity);
	if ($r['ok']) {
		$audit->log($uid, 'enroll', 'ok', 1, 0, 0, '', '', [],
			note: 'class=' . $class_id . ' user=' . $fk_user, entity: $entity);
		setEventMessages($langs->trans('EnrollOk'), null, 'mesgs');
	} else {
		$audit->log($uid, 'enroll', 'error', 0, 0, 0, '', '', [$r['error']],
			note: 'class=' . $class_id . ' user=' . $fk_user, entity: $entity);
		setEventMessages($r['error'], null, 'errors');
	}
	session_write_close();
	header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $class_id);
	exit;
}

// ── Unenroll ─────────────────────────────────────────────────────────────────
if ($action === 'unenroll' && $class_id && $fk_user) {
	if (!checkToken()) accessforbidden();
	$class = $mgr->fetch($class_id, $entity);
	if (!_mdt_can_write_class($class, $can_manage, $can_teach, $uid)) accessforbidden();

	$r = $mgr->unenroll($class_id, $fk_user, $entity);
	if ($r['ok']) {
		$audit->log($uid, 'unenroll', 'ok', 1, 0, 0, '', '', [],
			note: 'class=' . $class_id . ' user=' . $fk_user, entity: $entity);
		setEventMessages($langs->trans('UnenrollOk'), null, 'mesgs');
	} else {
		$audit->log($uid, 'unenroll', 'error', 0, 0, 0, '', '', [$r['error']],
			note: 'class=' . $class_id . ' user=' . $fk_user, entity: $entity);
		setEventMessages($r['error'], null, 'errors');
	}
	session_write_close();
	header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $class_id);
	exit;
}

// ── Create trainee account ───────────────────────────────────────────────────
if ($action === 'create_trainee' && $class_id && !empty($_POST)) {
	if (!checkToken()) accessforbidden();
	$class = $mgr->fetch($class_id, $entity);
	if (!_mdt_can_write_class($class, $can_manage, $can_teach, $uid)) accessforbidden();

	$r = $mgr->createTrainee(
		$class_id,
		GETPOST('trainee_login',     'alphanohtml'),
		GETPOST('trainee_firstname', 'alphanohtml'),
		GETPOST('trainee_lastname',  'alphanohtml'),
		GETPOST('trainee_email',     'email'),
		GETPOST('trainee_pass',      'none'),
		$uid,
		$entity
	);

	if ($r['ok']) {
		$audit->log($uid, 'trainee_create', 'ok', 1, 0, 0, '', '', [],
			note: 'class=' . $class_id . ' login=' . GETPOST('trainee_login', 'alphanohtml'), entity: $entity);
		setEventMessages($langs->trans('TraineeCreated'), null, 'mesgs');
	} else {
		$key = $r['error'];
		$msg = ($langs->trans($key) !== $key) ? $langs->trans($key) : $key;
		setEventMessages($langs->trans('TraineeCreateFailed') . ': ' . $msg, null, 'errors');
	}
	session_write_close();
	header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $class_id);
	exit;
}

// =============================================================================
// Output
// =============================================================================

llxHeader('', $langs->trans('PageTitleClasses'));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');
dol_htmloutput_events();
print dol_get_fiche_head(mokodolitraining_admin_tabs(), 'classes', '', -1, 'technic');

$self = $_SERVER['PHP_SELF'];

// ── View: class detail + enrollment ──────────────────────────────────────────
if ($class_id && $action !== 'new_class') {
	$class = $mgr->fetch($class_id, $entity);
	if (!$class) {
		print '<div class="error">' . $langs->trans('ClassNotFound') . '</div>';
		print dol_fiche_end();
		llxFooter();
		$db->close();
		exit;
	}

	$can_write = _mdt_can_write_class($class, $can_manage, $can_teach, $uid);
	$is_editing = ($action === 'edit');

	// ── Class detail header ───────────────────────────────────────────────
	if ($is_editing && $can_write) {
		print '<form method="POST" action="' . $self . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="edit_class">';
		print '<input type="hidden" name="id" value="' . $class_id . '">';
		print _mokodolitraining_class_form($mgr, $class, $langs);
		print '<div class="tabsAction">';
		print '<button type="submit" class="butAction">' . $langs->trans('Save') . '</button>';
		printf('<a href="%s?id=%d" class="butActionDelete">%s</a>', $self, $class_id, $langs->trans('Cancel'));
		print '</div></form>';
	} else {
		// Read-only info card
		print '<table class="border centpercent tableforfield">';
		printf('<tr><td class="titlefield">%s</td><td><b>%s</b></td></tr>',
			$langs->trans('ClassRef'), dol_htmlentities($class['ref']));
		printf('<tr><td>%s</td><td>%s</td></tr>',
			$langs->trans('ClassLabel'), dol_htmlentities($class['label']));
		printf('<tr><td>%s</td><td>%s → %s</td></tr>',
			$langs->trans('ClassDates'),
			$class['date_start'] ? dol_print_date(dol_stringtotime($class['date_start']), 'day') : '—',
			$class['date_end']   ? dol_print_date(dol_stringtotime($class['date_end']),   'day') : '—'
		);
		printf('<tr><td>%s</td><td>%s</td></tr>',
			$langs->trans('ClassTrainer'),
			dol_htmlentities(trim($class['trainer_firstname'] . ' ' . $class['trainer_lastname'])
				?: $class['trainer_login'] ?? '—')
		);
		printf('<tr><td>%s</td><td>%s</td></tr>',
			$langs->trans('ClassUsergroup'),
			$class['group_nom']
				? dol_htmlentities($class['group_nom'])
				: '<em class="opacitymedium">' . $langs->trans('None') . '</em>'
		);
		printf('<tr><td>%s</td><td>%s</td></tr>',
			$langs->trans('ClassStatus'),
			mokodolitraining_badge_class_status((int) $class['status'], $langs)
		);
		if ((int) $class['nb_max'] > 0) {
			$stats = $mgr->getStats($class_id);
			printf('<tr><td>%s</td><td>%d / %d</td></tr>',
				$langs->trans('ClassMaxTrainees'),
				$stats['active'],
				(int) $class['nb_max']
			);
		}
		print '</table>';

		// Action buttons
		if ($can_write) {
			print '<div class="tabsAction">';
			$tok = newToken();

			printf('<a href="%s?id=%d&action=edit" class="butAction">%s</a>',
				$self, $class_id, $langs->trans('Edit'));

			if ((int) $class['status'] === MokoDoliTrainingClass::CLASS_CLOSED) {
				printf(
					'<form method="POST" action="%s" style="display:inline">'
					. '<input type="hidden" name="token" value="%s">'
					. '<input type="hidden" name="id" value="%d">'
					. '<button type="submit" name="action" value="activate_class" class="butAction">%s</button>'
					. '</form>',
					$self, $tok, $class_id, $langs->trans('ClassActionActivate')
				);
			} else {
				printf(
					'<form method="POST" action="%s" style="display:inline">'
					. '<input type="hidden" name="token" value="%s">'
					. '<input type="hidden" name="id" value="%d">'
					. '<button type="submit" name="action" value="close_class" class="butActionDelete"'
					. ' onclick="return confirm(\'%s\')">%s</button>'
					. '</form>',
					$self, $tok, $class_id,
					dol_escape_js($langs->trans('ConfirmCloseClass')),
					$langs->trans('ClassActionClose')
				);
			}

			if ($can_manage) {
				printf(
					'<form method="POST" action="%s" style="display:inline">'
					. '<input type="hidden" name="token" value="%s">'
					. '<input type="hidden" name="id" value="%d">'
					. '<button type="submit" name="action" value="delete_class" class="butActionDelete"'
					. ' onclick="return confirm(\'%s\')">%s</button>'
					. '</form>',
					$self, $tok, $class_id,
					dol_escape_js($langs->trans('ConfirmDeleteClass')),
					$langs->trans('ActionDelete')
				);
			}

			// CSV export
			printf('<a href="%s?id=%d&action=export_roster" class="butActionNew">%s</a>',
				$self, $class_id, $langs->trans('ActionExportCsv'));

			printf('<a href="%s" class="butActionRefused">%s</a>',
				$self, $langs->trans('BackToList'));

			print '</div>';
		} elseif ($can_read) {
			// Read-only users: export + back
			print '<div class="tabsAction">';
			printf('<a href="%s?id=%d&action=export_roster" class="butActionNew">%s</a>',
				$self, $class_id, $langs->trans('ActionExportCsv'));
			printf('<a href="%s" class="butActionRefused">%s</a>',
				$self, $langs->trans('BackToList'));
			print '</div>';
		}
	}

	// ── Enrolled trainees ─────────────────────────────────────────────────
	print '<h3 style="margin-top:20px">' . $langs->trans('ClassTrainees') . '</h3>';

	$enrollments = $mgr->getEnrollments($class_id);

	if (empty($enrollments)) {
		print '<div class="opacitymedium">'
			. img_picto('', 'info', 'class="pictofixedwidth"')
			. $langs->trans('ClassNoTrainees') . '</div>';
	} else {
		print '<div class="div-table-responsive"><table class="noborder centpercent">';
		printf(
			'<tr class="liste_titre">'
			. '<th>%s</th><th>%s</th><th class="center">%s</th>'
			. '<th class="center">%s</th><th>%s</th><th class="center">%s</th>'
			. '</tr>',
			$langs->trans('Login'),
			$langs->trans('Name'),
			$langs->trans('ClassEnrollDate'),
			$langs->trans('ClassUserStatus'),
			$langs->trans('ClassEnrolledBy'),
			$langs->trans('ClassActions')
		);

		foreach ($enrollments as $e) {
			$enroll_badge = mokodolitraining_badge_enroll_status((int) $e['enroll_status'], $langs);
			$user_badge   = (int) $e['user_statut'] === 1
				? '<span class="badge badge-status4">' . $langs->trans('UserActive') . '</span>'
				: '<span class="badge badge-status8">' . $langs->trans('UserSuspended') . '</span>';

			$full_name = trim(dol_htmlentities($e['firstname'] . ' ' . $e['lastname'])) ?: '—';

			$actions = '';
			if ($can_write) {
				$tok = newToken();
				$actions = sprintf(
					'<form method="POST" action="%s" style="display:inline">'
					. '<input type="hidden" name="token" value="%s">'
					. '<input type="hidden" name="id" value="%d">'
					. '<input type="hidden" name="fk_user" value="%d">'
					. '<button type="submit" name="action" value="unenroll" class="reposition"'
					. ' onclick="return confirm(\'%s\')" title="%s">%s</button>'
					. '</form>',
					$self, $tok, $class_id, (int) $e['fk_user'],
					dol_escape_js($langs->trans('ConfirmUnenroll')),
					$langs->trans('Unenroll'),
					img_picto($langs->trans('Unenroll'), 'delete')
				);
			}

			printf(
				'<tr class="oddeven">'
				. '<td><code>%s</code></td>'
				. '<td>%s</td>'
				. '<td class="center nowrap small">%s</td>'
				. '<td class="center">%s&nbsp;%s</td>'
				. '<td class="small opacitymedium">%s</td>'
				. '<td class="center">%s</td>'
				. '</tr>',
				dol_htmlentities($e['login']),
				$full_name,
				$e['enroll_date'] ? dol_print_date(dol_stringtotime($e['enroll_date']), 'dayhour') : '—',
				$user_badge, $enroll_badge,
				dol_htmlentities($e['enroller_login'] ?? '—'),
				$actions
			);
		}
		print '</table></div>';
	}

	// ── Enroll existing user ──────────────────────────────────────────────
	if ($can_write && (int) $class['status'] !== MokoDoliTrainingClass::CLASS_CLOSED) {
		$available = $mgr->getAvailableUsers($class_id, $entity);

		print '<div style="margin-top:16px">';
		print '<h4>' . $langs->trans('ClassEnrollUser') . '</h4>';

		if (empty($available)) {
			print '<p class="opacitymedium">' . $langs->trans('ClassNoUsersToEnroll') . '</p>';
		} else {
			printf(
				'<form method="POST" action="%s" style="display:inline-flex;gap:8px;align-items:center">',
				$self
			);
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="enroll">';
			print '<input type="hidden" name="id" value="' . $class_id . '">';

			print '<select name="fk_user" class="flat" style="min-width:220px">';
			foreach ($available as $u) {
				$display = dol_htmlentities(
					trim($u['firstname'] . ' ' . $u['lastname']) ?: $u['login']
				) . ' (' . dol_htmlentities($u['login']) . ')';
				printf('<option value="%d">%s</option>', (int) $u['rowid'], $display);
			}
			print '</select>';
			printf('<button type="submit" class="butAction">%s</button>', $langs->trans('Enroll'));
			print '</form>';
		}
		print '</div>';
	}

	// ── Create trainee account (instructor path) ───────────────────────────
	if ($can_write && (int) $class['status'] !== MokoDoliTrainingClass::CLASS_CLOSED) {
		print '<div style="margin-top:24px;border-top:1px solid var(--colortextlink,#ccc);padding-top:16px">';
		print '<h4>' . $langs->trans('TraineeCreateTitle') . '</h4>';
		print '<p class="opacitymedium small">' . $langs->trans('TraineeCreateDesc') . '</p>';

		printf('<form method="POST" action="%s">', $self);
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="create_trainee">';
		print '<input type="hidden" name="id" value="' . $class_id . '">';

		print '<table class="nobordernopadding" style="max-width:520px">';
		$fields = [
			['trainee_login',     $langs->trans('TraineeLogin'),     'text',     true,  ''],
			['trainee_pass',      $langs->trans('TraineePassword'),  'password', true,  ''],
			['trainee_firstname', $langs->trans('TraineeFirstname'), 'text',     false, ''],
			['trainee_lastname',  $langs->trans('TraineeLastname'),  'text',     false, ''],
			['trainee_email',     $langs->trans('TraineeEmail'),     'email',    false, ''],
		];
		foreach ($fields as [$name, $label, $type, $required, $default]) {
			$req = $required ? ' <span class="fieldrequired">*</span>' : '';
			printf(
				'<tr><td class="titlefield" style="padding:4px 8px 4px 0;white-space:nowrap">%s%s</td>'
				. '<td><input type="%s" name="%s" value="%s" class="flat" style="width:240px" autocomplete="off"></td>'
				. '</tr>',
				dol_htmlentities($label), $req, $type, $name,
				dol_htmlentities(GETPOSTISSET($name) ? GETPOST($name, 'alphanohtml') : $default)
			);
		}
		print '</table>';
		printf('<div style="margin-top:8px"><button type="submit" class="butAction">%s</button></div>',
			$langs->trans('TraineeCreate'));
		print '</form></div>';
	}

	// ── Username tracking log ──────────────────────────────────────────────
	$tracked = $mgr->getTrackedUsers($entity, $class_id);
	if (!empty($tracked)) {
		print '<div style="margin-top:24px;border-top:1px solid var(--colortextlink,#ccc);padding-top:16px">';
		print '<h4>' . $langs->trans('TraineeTrackTitle') . '</h4>';
		print '<div class="div-table-responsive"><table class="noborder centpercent">';
		printf(
			'<tr class="liste_titre">'
			. '<th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th>'
			. '</tr>',
			$langs->trans('TraineeLogin'),
			$langs->trans('TraineeSetBy'),
			$langs->trans('TraineeSetDate'),
			$langs->trans('TraineeSource'),
			$langs->trans('TraineeSourceLabel')
		);
		foreach ($tracked as $t) {
			$src_badge = ($t['source'] === 'module')
				? '<span class="badge badge-status4">' . $langs->trans('TraineeSourceModule') . '</span>'
				: '<span class="badge badge-status8">' . $langs->trans('TraineeSourceExternal') . '</span>';
			printf(
				'<tr class="oddeven">'
				. '<td><code>%s</code></td>'
				. '<td class="small opacitymedium">%s</td>'
				. '<td class="small nowrap">%s</td>'
				. '<td>%s</td>'
				. '<td class="small opacitymedium">%s</td>'
				. '</tr>',
				dol_htmlentities($t['login']),
				dol_htmlentities($t['setter_login'] ?? '—'),
				$t['datec'] ? dol_print_date(dol_stringtotime($t['datec']), 'dayhour') : '—',
				$src_badge,
				$t['source'] === 'external' ? $langs->trans('TraineeSourceExternalNote') : ''
			);
		}
		print '</table></div></div>';
	}

	// ── Exercises for this class ───────────────────────────────────────────
	if ($can_teach || $can_manage) {
		dol_include_once('/mokodolitraining/class/MokoDoliTrainingExercise.class.php');

		$class_group = (int) ($class['fk_usergroup'] ?? 0);
		print '<div style="margin-top:24px;border-top:1px solid var(--colortextlink,#ccc);padding-top:16px">';
		print '<h4>' . $langs->trans('ExerciseClassSection') . '</h4>';

		if ($class_group <= 0 || !isset(MokoDoliTrainingExercise::GROUPS[$class_group])) {
			print '<div class="opacitymedium">'
				. img_picto('', 'warning', 'class="pictofixedwidth"')
				. $langs->trans('ExerciseNoGroup') . '</div>';
		} else {
			$group_exercises = MokoDoliTrainingExercise::getByGroup($class_group);
			$group_info      = MokoDoliTrainingExercise::GROUPS[$class_group];
			$exercise_url    = dol_buildpath('/mokodolitraining/admin/exercise.php', 1);
			$dol_root        = dol_htmlentities(rtrim(DOL_MAIN_URL_ROOT, '/'));

			print '<p class="opacitymedium" style="margin-bottom:12px">' . $langs->trans('ExerciseClassDesc') . '</p>';

			if (empty($group_exercises)) {
				print '<div class="opacitymedium">' . $langs->trans('ExerciseNoneForGroup') . '</div>';
			} else {
				print '<div class="div-table-responsive"><table class="noborder centpercent">';
				printf(
					'<tr class="liste_titre"><th>%s</th><th>%s</th><th class="right">%s</th></tr>',
					dol_htmlentities($group_info['name'] . ' — ' . $langs->trans('TabExercises')),
					$langs->trans('LabelStatus'),
					$langs->trans('ClassActions')
				);
				foreach ($group_exercises as $ex) {
					printf(
						'<tr class="oddeven">'
						. '<td><strong>%s</strong><br><span class="opacitymedium small">%s</span></td>'
						. '<td class="small"><span class="badge badge-status0">%s</span></td>'
						. '<td class="right nowrap">'
						. '<a href="%s?action=start_tour&exercise_id=%s&mode=normal" class="butActionNew small">%s</a> '
						. '<a href="%s?action=start_tour&exercise_id=%s&mode=trainer" class="butAction small">%s</a>'
						. '</td></tr>',
						dol_htmlentities($ex['title']),
						dol_htmlentities($ex['summary']),
						dol_htmlentities($ex['estimate']),
						$exercise_url, dol_htmlentities($ex['id']),
						$langs->trans('ExerciseStart'),
						$exercise_url, dol_htmlentities($ex['id']),
						$langs->trans('ExerciseStartTrainer')
					);
				}
				print '</table></div>';
			}
		}
		print '</div>';
	}

// ── View: create form ─────────────────────────────────────────────────────────
} elseif ($action === 'new_class' && $can_manage) {
	print '<h3>' . $langs->trans('ClassNew') . '</h3>';
	print '<form method="POST" action="' . $self . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="create_class">';
	// Pre-fill ref with an auto-generated suggestion
	$suggested_ref = $mgr->generateRef($entity);
	print _mokodolitraining_class_form($mgr, ['ref' => $suggested_ref], $langs);
	print '<div class="tabsAction">';
	printf('<button type="submit" class="butAction">%s</button>', $langs->trans('Create'));
	printf('<a href="%s" class="butActionRefused">%s</a>', $self, $langs->trans('Cancel'));
	print '</div></form>';

// ── View: class list ──────────────────────────────────────────────────────────
} else {
	// Build filters (teachers see only their own classes)
	$filters = [];
	if ($search_status !== '') $filters['status'] = (int) $search_status;
	if ($search_q !== '')       $filters['search'] = $search_q;
	if ($can_teach && !$can_manage) $filters['trainer_id'] = $uid;

	$total   = $mgr->countAll($entity, $filters);
	$classes = $mgr->fetchAll($entity, $filters, PAGE_SIZE, $page * PAGE_SIZE);

	// ── Search / filter bar ───────────────────────────────────────────────
	print '<form method="GET" action="' . $self . '" style="margin-bottom:12px">';
	print '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">';
	printf('<input type="text" name="search_q" class="flat" placeholder="%s" value="%s" style="min-width:180px">',
		$langs->trans('Search'), dol_htmlentities($search_q));

	print '<select name="search_status" class="flat">';
	printf('<option value="">— %s —</option>', $langs->trans('ClassStatus'));
	$status_opts = [
		MokoDoliTrainingClass::CLASS_DRAFT  => $langs->trans('ClassStatusDraft'),
		MokoDoliTrainingClass::CLASS_ACTIVE => $langs->trans('ClassStatusActive'),
		MokoDoliTrainingClass::CLASS_CLOSED => $langs->trans('ClassStatusClosed'),
	];
	foreach ($status_opts as $val => $lbl) {
		$sel = ($search_status !== '' && (int) $search_status === $val) ? ' selected' : '';
		printf('<option value="%d"%s>%s</option>', $val, $sel, dol_htmlentities($lbl));
	}
	print '</select>';

	printf('<button type="submit" class="butActionSmall">%s</button>', $langs->trans('Search'));
	if ($search_q !== '' || $search_status !== '') {
		printf('<a href="%s" class="butActionSmallDelete">%s</a>', $self, $langs->trans('Reset'));
	}
	print '</div></form>';

	if ($can_manage) {
		printf('<div style="margin-bottom:12px"><a href="%s?action=new_class" class="butAction">%s</a></div>',
			$self, $langs->trans('ClassNew'));
	}

	if (empty($classes)) {
		print '<div class="opacitymedium">'
			. img_picto('', 'info', 'class="pictofixedwidth"')
			. $langs->trans('ClassNone') . '</div>';
	} else {
		print '<div class="div-table-responsive"><table class="noborder centpercent">';
		printf(
			'<tr class="liste_titre">'
			. '<th>%s</th><th>%s</th><th class="center">%s</th>'
			. '<th class="center">%s</th><th>%s</th><th>%s</th>'
			. '<th class="center">%s</th><th class="center">%s</th>'
			. '</tr>',
			$langs->trans('ClassRef'),
			$langs->trans('ClassLabel'),
			$langs->trans('ClassStatus'),
			$langs->trans('ClassTrainees'),
			$langs->trans('ClassDates'),
			$langs->trans('ClassTrainer'),
			$langs->trans('ClassUsergroup'),
			$langs->trans('ClassActions')
		);

		foreach ($classes as $c) {
			$tok        = newToken();
			$can_modify = _mdt_can_write_class($c, $can_manage, $can_teach, $uid);

			$trainer_name = trim(($c['trainer_firstname'] ?? '') . ' ' . ($c['trainer_lastname'] ?? ''))
				?: ($c['trainer_login'] ?? '—');

			$date_range = '';
			if ($c['date_start'] || $c['date_end']) {
				$date_range = ($c['date_start'] ? dol_print_date(dol_stringtotime($c['date_start']), 'day') : '—')
					. ' → '
					. ($c['date_end'] ? dol_print_date(dol_stringtotime($c['date_end']), 'day') : '—');
			}

			$cap = (int) $c['nb_max'] > 0
				? (int) $c['nb_enrolled'] . ' / ' . (int) $c['nb_max']
				: (int) $c['nb_enrolled'];

			$actions = sprintf('<a href="%s?id=%d" title="%s">%s</a>&nbsp;',
				$self, (int) $c['rowid'],
				$langs->trans('View'),
				img_picto($langs->trans('View'), 'eye')
			);

			if ($can_modify) {
				if ((int) $c['status'] === MokoDoliTrainingClass::CLASS_CLOSED) {
					$actions .= sprintf(
						'<form method="POST" action="%s" style="display:inline">'
						. '<input type="hidden" name="token" value="%s">'
						. '<input type="hidden" name="id" value="%d">'
						. '<button type="submit" name="action" value="activate_class"'
						. ' class="reposition" title="%s">%s</button></form>&nbsp;',
						$self, $tok, (int) $c['rowid'],
						$langs->trans('ClassActionActivate'),
						img_picto($langs->trans('ClassActionActivate'), 'refresh')
					);
				} else {
					$actions .= sprintf(
						'<form method="POST" action="%s" style="display:inline">'
						. '<input type="hidden" name="token" value="%s">'
						. '<input type="hidden" name="id" value="%d">'
						. '<button type="submit" name="action" value="close_class"'
						. ' class="reposition" title="%s"'
						. ' onclick="return confirm(\'%s\')">%s</button></form>&nbsp;',
						$self, $tok, (int) $c['rowid'],
						$langs->trans('ClassActionClose'),
						dol_escape_js($langs->trans('ConfirmCloseClass')),
						img_picto($langs->trans('ClassActionClose'), 'lock')
					);
				}
			}

			printf(
				'<tr class="oddeven">'
				. '<td><a href="%s?id=%d"><code>%s</code></a></td>'
				. '<td>%s</td>'
				. '<td class="center">%s</td>'
				. '<td class="center">%s</td>'
				. '<td class="nowrap small">%s</td>'
				. '<td class="small">%s</td>'
				. '<td class="center small">%s</td>'
				. '<td class="center nowrap">%s</td>'
				. '</tr>',
				$self, (int) $c['rowid'],
				dol_htmlentities($c['ref']),
				dol_htmlentities($c['label']),
				mokodolitraining_badge_class_status((int) $c['status'], $langs),
				$cap,
				$date_range ?: '<span class="opacitymedium">—</span>',
				dol_htmlentities($trainer_name),
				$c['group_nom'] ? dol_htmlentities($c['group_nom']) : '<span class="opacitymedium">—</span>',
				$actions
			);
		}
		print '</table></div>';

		// ── Pagination ────────────────────────────────────────────────────
		if ($total > PAGE_SIZE) {
			$pages     = (int) ceil($total / PAGE_SIZE);
			$qs_search = ($search_q      !== '') ? '&search_q='      . urlencode($search_q)      : '';
			$qs_status = ($search_status !== '') ? '&search_status=' . (int) $search_status       : '';

			print '<div style="margin-top:10px;text-align:center">';
			for ($p = 0; $p < $pages; $p++) {
				$active = ($p === $page) ? ' style="font-weight:bold"' : '';
				printf('<a href="%s?page=%d%s%s"%s>&nbsp;%d&nbsp;</a>',
					$self, $p, $qs_search, $qs_status, $active, $p + 1);
			}
			printf('<span class="opacitymedium small">&nbsp;(%d %s)</span>',
				$total, $langs->trans('ClassTotalCount'));
			print '</div>';
		}
	}
}

print dol_fiche_end();
llxFooter();
$db->close();

// =============================================================================
// View helpers (local to this page)
// =============================================================================

/**
 * Render the class create/edit form fields.
 * $data is the existing class array (or partial array for create with pre-filled ref).
 */
function _mokodolitraining_class_form(MokoDoliTrainingClass $mgr, array $data, $langs): string
{
	$groups   = $mgr->getGroups();
	$trainers = $mgr->getTrainers();

	$ref    = dol_htmlentities($data['ref']   ?? '');
	$lbl    = dol_htmlentities($data['label'] ?? '');
	$ds     = $data['date_start'] ?? '';
	$de     = $data['date_end']   ?? '';
	$nb     = (int) ($data['nb_max'] ?? 0);
	$np     = dol_htmlentities($data['note_public']  ?? '');
	$npr    = dol_htmlentities($data['note_private'] ?? '');
	$cur_ug = (int) ($data['fk_usergroup']    ?? 0);
	$cur_tr = (int) ($data['fk_user_trainer'] ?? 0);

	$html = '<table class="border centpercent tableforfield">';

	// Ref
	$html .= sprintf(
		'<tr><td class="titlefield fieldrequired">%s</td><td>'
		. '<input type="text" name="ref" class="flat minwidth200" maxlength="30" value="%s" required>'
		. '</td></tr>',
		$langs->trans('ClassRef'), $ref
	);

	// Label
	$html .= sprintf(
		'<tr><td class="fieldrequired">%s</td><td>'
		. '<input type="text" name="label" class="flat minwidth300" maxlength="255" value="%s" required>'
		. '</td></tr>',
		$langs->trans('ClassLabel'), $lbl
	);

	// Date range
	$html .= sprintf(
		'<tr><td>%s</td><td>'
		. '<input type="date" name="date_start" class="flat" value="%s">'
		. '&nbsp;→&nbsp;'
		. '<input type="date" name="date_end" class="flat" value="%s">'
		. '</td></tr>',
		$langs->trans('ClassDates'), $ds, $de
	);

	// Trainer
	$html .= sprintf('<tr><td>%s</td><td><select name="fk_user_trainer" class="flat minwidth200">',
		$langs->trans('ClassTrainer'));
	$html .= '<option value="0">— ' . $langs->trans('None') . ' —</option>';
	foreach ($trainers as $t) {
		$sel  = ((int) $t['rowid'] === $cur_tr) ? ' selected' : '';
		$name = dol_htmlentities(trim($t['firstname'] . ' ' . $t['lastname']) ?: $t['login']);
		$html .= sprintf('<option value="%d"%s>%s (%s)</option>',
			(int) $t['rowid'], $sel, $name, dol_htmlentities($t['login']));
	}
	$html .= '</select></td></tr>';

	// Usergroup
	$html .= sprintf('<tr><td>%s</td><td><select name="fk_usergroup" class="flat minwidth200">',
		$langs->trans('ClassUsergroup'));
	$html .= '<option value="">— ' . $langs->trans('None') . ' —</option>';
	foreach ($groups as $g) {
		$sel  = ((int) $g['rowid'] === $cur_ug) ? ' selected' : '';
		$html .= sprintf('<option value="%d"%s>%s</option>',
			(int) $g['rowid'], $sel, dol_htmlentities($g['nom']));
	}
	$html .= '</select>'
		. '<span class="opacitymedium small">&nbsp;' . $langs->trans('ClassUsergroupHelp') . '</span>'
		. '</td></tr>';

	// Max trainees
	$html .= sprintf(
		'<tr><td>%s</td><td>'
		. '<input type="number" name="nb_max" class="flat width75" min="0" value="%d">'
		. '&nbsp;<span class="opacitymedium small">%s</span>'
		. '</td></tr>',
		$langs->trans('ClassMaxTrainees'), $nb, $langs->trans('ClassMaxHelp')
	);

	// Public note
	$html .= sprintf(
		'<tr><td>%s</td><td>'
		. '<textarea name="note_public" class="flat" rows="2" style="width:100%%">%s</textarea>'
		. '</td></tr>',
		$langs->trans('NotePublic'), $np
	);

	// Private note
	$html .= sprintf(
		'<tr><td>%s</td><td>'
		. '<textarea name="note_private" class="flat" rows="2" style="width:100%%">%s</textarea>'
		. '</td></tr>',
		$langs->trans('NotePrivate'), $npr
	);

	$html .= '</table>';
	return $html;
}
