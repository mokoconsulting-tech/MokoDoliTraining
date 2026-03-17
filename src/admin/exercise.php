<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Admin
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/admin/exercise.php
 * VERSION:  01.00.00
 * BRIEF:    Exercise launcher page. Trainees see exercises for their training
 *           group. Trainers (teach|manage) see all groups and can launch any
 *           exercise in normal or trainer-notes mode.
 *
 * Actions handled:
 *   start_tour  — serialises exercise JSON to localStorage via inline JS
 *                 then redirects the user to the first step's page.
 *   stop_tour   — clears localStorage tour state and returns to this page.
 *
 * Permissions:
 *   read    — trainees: see own group exercises and start them
 *   teach   — trainers: see all groups, launch with trainer notes
 *   manage  — full access (same as teach for this page)
 */

$res = 0;
if (!$res && file_exists('../main.inc.php'))          $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))       $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))    $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/mokodolitraining/lib/mokodolitraining.lib.php');
dol_include_once('/mokodolitraining/class/MokoDoliTrainingExercise.class.php');

$can_manage = mokodolitraining_has_perm($user, 'manage');
$can_teach  = mokodolitraining_has_perm($user, 'teach');
$can_read   = mokodolitraining_has_perm($user, 'read');
$is_trainer = $can_manage || $can_teach;

if (!$can_manage && !$can_teach && !$can_read) {
	accessforbidden();
}

$langs->load('mokodolitraining@mokodolitraining');

$action  = GETPOST('action', 'aZ09');
$ex_id   = GETPOST('exercise_id', 'alphanohtml');
$mode    = GETPOST('mode', 'aZ09'); // 'trainer' or ''
$entity  = (int) $conf->entity;
$uid     = (int) $user->id;

// =============================================================================
// Action: start_tour
// Output a minimal redirect page that writes tour state to localStorage and
// navigates to the first step's URL. Uses llxHeader/llxFooter so module
// JS/CSS (including mokodolitraining-tour.js) is guaranteed to be loaded.
// =============================================================================

if ($action === 'start_tour' && !empty($ex_id)) {
	$exercise = MokoDoliTrainingExercise::find($ex_id);

	if ($exercise) {
		$trainer_mode = $is_trainer && ($mode === 'trainer');
		$dol_root     = rtrim(DOL_URL_ROOT, '/');
		$first_url    = $dol_root . $exercise['steps'][0]['nav_url'];

		$state = [
			'exercise' => $exercise,
			'step'     => 0,
			'trainer'  => $trainer_mode,
			'dolRoot'  => $dol_root,
		];

		// json_encode(json_encode($state)) produces a JS string literal that
		// can be passed directly to localStorage.setItem(). The inner
		// json_encode serialises the state; the outer wraps it as a JS string.
		$state_json_literal = json_encode(json_encode($state, JSON_UNESCAPED_UNICODE));
		$redirect_literal   = json_encode($first_url);

		llxHeader('', $langs->trans('ExerciseStarting'));
		print '<script>';
		print 'localStorage.setItem(' . json_encode(/** key */ 'mdt_tour') . ', ' . $state_json_literal . ');';
		print 'window.location.replace(' . $redirect_literal . ');';
		print '</script>';
		llxFooter();
		exit;
	}
}

// =============================================================================
// Action: stop_tour — clears localStorage via JS and stays on this page
// =============================================================================

if ($action === 'stop_tour') {
	// Clearing is done by the tour JS itself; we just redirect back cleanly.
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

// =============================================================================
// Build exercise list
// =============================================================================

$seed_mode = getDolGlobalString('MOKODOLITRAINING_SEED_MODE', 'training');

if ($is_trainer) {
	// Trainers see every training group's exercises, organised by group
	$exercises_by_group = [];
	foreach (MokoDoliTrainingExercise::GROUPS as $gid => $ginfo) {
		$exercises_by_group[$gid] = MokoDoliTrainingExercise::getByGroup($gid);
	}
	$my_exercises = null; // not needed for trainer view
} else {
	// Trainees see only their own group(s)
	$my_exercises       = MokoDoliTrainingExercise::getForUser($db, $uid, $entity);
	$exercises_by_group = null;
}

// =============================================================================
// Output
// =============================================================================

llxHeader('', $langs->trans('PageTitleExercises'));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre('MokoDoliTraining', $linkback, 'technic');
dol_htmloutput_events();
print dol_get_fiche_head(mokodolitraining_admin_tabs(), 'exercises', '', -1, 'technic');

$self = $_SERVER['PHP_SELF'];

// ── Active tour banner ────────────────────────────────────────────────────────
print '<script>';
print '(function(){';
print '  var s = null;';
print '  try { s = JSON.parse(localStorage.getItem("mdt_tour")); } catch(e){}';
print '  if (s && s.exercise) {';
print '    var banner = document.createElement("div");';
print '    banner.className = "info";';
print '    banner.style.cssText = "padding:10px 16px;margin-bottom:12px;display:flex;align-items:center;gap:12px";';
// Use textContent for user-controlled exercise title
print '    var msg = document.createElement("span");';
print '    msg.textContent = "\u25b6 Tour in progress: " + (s.exercise.title || "");';
print '    var stopLink = document.createElement("a");';
print '    stopLink.href = "#";';
print '    stopLink.textContent = "Stop tour";';
print '    stopLink.addEventListener("click", function(e) {';
print '      e.preventDefault();';
print '      try { localStorage.removeItem("mdt_tour"); } catch(ex){}';
print '      location.reload();';
print '    });';
print '    banner.appendChild(msg);';
print '    banner.appendChild(stopLink);';
print '    var ref = document.querySelector(".fichehalfleft, .fiche, h3, table");';
print '    if (ref && ref.parentNode) ref.parentNode.insertBefore(banner, ref);';
print '  }';
print '})();';
print '</script>';

// ── Inline card styles (scoped to this page) ──────────────────────────────────
print '<style>';
print '.mdt-exercise-grid{display:flex;flex-wrap:wrap;gap:16px;margin:16px 0}';
print '.mdt-ex-card{background:#fff;border:1px solid #dee2e6;border-radius:10px;';
print 'padding:16px;min-width:260px;max-width:340px;flex:1;display:flex;flex-direction:column;gap:8px}';
print '.mdt-ex-card-header{display:flex;align-items:center;gap:8px}';
print '.mdt-ex-card h4{margin:0;font-size:15px;font-weight:700}';
print '.mdt-ex-meta{font-size:12px;color:#6c757d;display:flex;gap:10px}';
print '.mdt-ex-summary{font-size:13px;color:#444;line-height:1.5;flex:1}';
print '.mdt-ex-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}';
print '.mdt-group-section{margin-bottom:28px}';
print '.mdt-group-label{display:inline-block;padding:3px 10px;border-radius:4px;';
print 'color:#fff;font-size:12px;font-weight:700;margin-bottom:4px}';
print '</style>';

// =============================================================================
// TRAINER VIEW — all groups, each with "Start" and "Demo as Trainer" buttons
// =============================================================================

if ($is_trainer) {
	$mode_badge = ($seed_mode === 'demo')
		? '<span class="badge badge-status1" style="margin-left:6px">' . $langs->trans('SeedModeDemo') . '</span>'
		: '<span class="badge badge-status4" style="margin-left:6px">' . $langs->trans('SeedModeTraining') . '</span>';
	printf('<p class="opacitymedium small">%s%s</p>', $langs->trans('ExerciseTrainerDesc'), $mode_badge);

	foreach ($exercises_by_group as $gid => $exercises) {
		if (empty($exercises)) continue;
		$ginfo = MokoDoliTrainingExercise::GROUPS[$gid];

		print '<div class="mdt-group-section">';
		printf(
			'<span class="mdt-group-label" style="background:%s">%s</span>',
			dol_htmlentities($ginfo['color']),
			dol_htmlentities($ginfo['name'])
		);
		print '<div class="mdt-exercise-grid">';

		foreach ($exercises as $ex) {
			$step_count = count($ex['steps']);
			printf(
				'<div class="mdt-ex-card">'
				. '<div class="mdt-ex-card-header">'
				. '<div>'
				. '<h4>%s</h4>'
				. '<div class="mdt-ex-meta"><span>&#128337; %s</span><span>%d steps</span></div>'
				. '</div>'
				. '</div>'
				. '<p class="mdt-ex-summary">%s</p>'
				. '<div class="mdt-ex-actions">',
				dol_htmlentities($ex['title']),
				dol_htmlentities($ex['estimate']),
				$step_count,
				dol_htmlentities($ex['summary'])
			);

			// "Start as Trainee" button (no trainer notes)
			printf(
				'<a href="%s?action=start_tour&exercise_id=%s" class="butActionNew">%s</a>',
				$self,
				urlencode($ex['id']),
				$langs->trans('ExerciseStart')
			);

			// "Demo as Trainer" button (shows trainer notes)
			printf(
				'<a href="%s?action=start_tour&exercise_id=%s&mode=trainer" class="butAction">%s</a>',
				$self,
				urlencode($ex['id']),
				$langs->trans('ExerciseStartTrainer')
			);

			print '</div></div>';
		}

		print '</div></div>';
	}

// =============================================================================
// TRAINEE VIEW — only their group exercises
// =============================================================================

} else {
	if (empty($my_exercises)) {
		print '<div class="opacitymedium">' . img_picto('', 'info', 'class="pictofixedwidth"');
		print $langs->trans('ExerciseNoneForGroup') . '</div>';
	} else {
		printf('<p class="opacitymedium small">%s</p>', $langs->trans('ExerciseTraineeDesc'));

		// Group exercises by their group (user may be in multiple training groups)
		$by_group = [];
		foreach ($my_exercises as $ex) {
			$by_group[$ex['group']][] = $ex;
		}

		foreach ($by_group as $gid => $exercises) {
			$ginfo = MokoDoliTrainingExercise::GROUPS[$gid] ?? ['name' => 'Group ' . $gid, 'color' => '#999'];

			print '<div class="mdt-group-section">';
			printf(
				'<span class="mdt-group-label" style="background:%s">%s</span>',
				dol_htmlentities($ginfo['color']),
				dol_htmlentities($ginfo['name'])
			);
			print '<div class="mdt-exercise-grid">';

			foreach ($exercises as $ex) {
				$step_count = count($ex['steps']);
				printf(
					'<div class="mdt-ex-card">'
					. '<div class="mdt-ex-card-header">'
					. '<div>'
					. '<h4>%s</h4>'
					. '<div class="mdt-ex-meta"><span>&#128337; %s</span><span>%d steps</span></div>'
					. '</div>'
					. '</div>'
					. '<p class="mdt-ex-summary">%s</p>'
					. '<div class="mdt-ex-actions">',
					dol_htmlentities($ex['title']),
					dol_htmlentities($ex['estimate']),
					$step_count,
					dol_htmlentities($ex['summary'])
				);

				printf(
					'<a href="%s?action=start_tour&exercise_id=%s" class="butAction">%s</a>',
					$self,
					urlencode($ex['id']),
					$langs->trans('ExerciseStart')
				);

				print '</div></div>';
			}

			print '</div></div>';
		}
	}
}

print dol_get_fiche_end();

llxFooter();
$db->close();
