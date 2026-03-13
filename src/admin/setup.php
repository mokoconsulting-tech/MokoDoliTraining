<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Admin
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /admin/setup.php
 * VERSION:  01.00.00
 * BRIEF:    Admin setup page — seed, reset, and manifest viewer for MokoDoliTraining.
 */

// Dolibarr environment bootstrap
$res = 0;
if (!$res && file_exists('../main.inc.php'))                        $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))                     $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))                  $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../../../main.inc.php'))               $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/mokodolitraining/core/modules/modMokoDoliTraining.class.php');

if (!$user->admin) {
	accessforbidden();
}

$langs->load('mokodolitraining@mokodolitraining');

$action = GETPOST('action', 'aZ09');

// ── Action handlers ──────────────────────────────────────────────────────────

/**
 * Execute a SQL file against the current Dolibarr database.
 *
 * Reads the file line by line, accumulates statements terminated by ';',
 * and executes each one. DELIMITER blocks are skipped (handled by MySQL CLI).
 *
 * @param  DoliDB $db        Dolibarr database handler
 * @param  string $sql_path  Absolute path to .sql file
 * @return array{ok: int, errors: string[]}
 */
function mokodolitraining_exec_sql_file($db, string $sql_path): array
{
	$ok = 0;
	$errors = [];
	$stmt = '';
	$in_delimiter = false;

	foreach (file($sql_path) as $line) {
		$trimmed = trim($line);
		if (str_starts_with($trimmed, '--') || $trimmed === '') continue;
		if (preg_match('/^DELIMITER\s+/i', $trimmed)) { $in_delimiter = !$in_delimiter; continue; }
		if ($in_delimiter) continue;

		$stmt .= ' ' . $trimmed;
		if (str_ends_with(rtrim($trimmed), ';')) {
			$result = $db->query(trim($stmt));
			if ($result === false) {
				$errors[] = $db->lasterror() . ' — ' . substr(trim($stmt), 0, 120);
			} else {
				$ok++;
			}
			$stmt = '';
		}
	}
	return ['ok' => $ok, 'errors' => $errors];
}

$messages = [];
$error_messages = [];

if ($action === 'seed') {
	$sql_path = modMokoDoliTraining::getSeedSqlPath();
	if (!file_exists($sql_path)) {
		$error_messages[] = 'Seed SQL not found: ' . $sql_path;
	} else {
		$result = mokodolitraining_exec_sql_file($db, $sql_path);
		$messages[] = 'Seed complete — ' . $result['ok'] . ' statements executed.';
		$error_messages = array_merge($error_messages, $result['errors']);
	}
} elseif ($action === 'reset') {
	$sql_path = modMokoDoliTraining::getResetSqlPath();
	if (!file_exists($sql_path)) {
		$error_messages[] = 'Reset SQL not found: ' . $sql_path;
	} else {
		$result = mokodolitraining_exec_sql_file($db, $sql_path);
		$messages[] = 'Reset complete — ' . $result['ok'] . ' DELETE statements executed.';
		$error_messages = array_merge($error_messages, $result['errors']);
	}
}

// ── Page output ──────────────────────────────────────────────────────────────

$page_name = 'MokoDoliTraining';
llxHeader('', $page_name);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">'
	. $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($page_name . ' — Training Dataset Manager', $linkback, 'technic');

// Status messages
foreach ($messages as $msg) {
	print '<div class="ok">' . dol_htmlentities($msg) . '</div>';
}
foreach ($error_messages as $err) {
	print '<div class="error">' . dol_htmlentities($err) . '</div>';
}

// Manifest summary
$summary  = modMokoDoliTraining::getManifestSummary();
$manifest = modMokoDoliTraining::getManifest();

print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>Module</th><th>Tables tracked</th><th>Total rows</th></tr>';
print '<tr class="oddeven">';
print '<td>MokoDoliTraining</td>';
print '<td>' . $summary['tables'] . '</td>';
print '<td>' . $summary['rows'] . '</td>';
print '</tr>';
print '</table>';

// Action buttons
print '<br>';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<div class="tabsAction">';
print '<input type="submit" name="action" value="seed" class="butAction"'
	. ' onclick="return confirm(\'Load all training data? Existing rows will be updated via ON DUPLICATE KEY.\');"'
	. ' title="Run mokotraining.sql">';
print '&nbsp;&nbsp;';
print '<input type="submit" name="action" value="reset" class="butActionDelete"'
	. ' onclick="return confirm(\'Delete ALL training data rows? This cannot be undone.\');"'
	. ' title="Run mokotraining_reset.sql">';
print '</div>';
print '</form>';

// Manifest table
print '<br><h3>Row Manifest</h3>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>Table</th><th>Row count</th><th>Rowids</th></tr>';
$i = 0;
foreach ($manifest as $tbl => $row_ids) {
	$cls = ($i % 2 === 0) ? 'even' : 'odd';
	$ids_str = implode(', ', $row_ids);
	print '<tr class="' . $cls . '">';
	print '<td><code>' . dol_htmlentities($tbl) . '</code></td>';
	print '<td>' . count($row_ids) . '</td>';
	print '<td style="font-size:0.85em;word-break:break-all;">' . dol_htmlentities($ids_str) . '</td>';
	print '</tr>';
	$i++;
}
print '</table>';

llxFooter();
$db->close();
