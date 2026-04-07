<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Admin
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/admin/download.php
 * VERSION:  01.00.02
 * BRIEF:    Secure backup file download handler. Admin-only. No directory traversal.
 */

$res = 0;
if (!$res && file_exists('../main.inc.php'))          $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))       $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))    $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

dol_include_once('/mokodolitraining/class/MokoDoliTrainingBackup.class.php');
dol_include_once('/mokodolitraining/class/MokoDoliTrainingAudit.class.php');

if (!$user->admin) {
	accessforbidden();
}

// ── Input validation ──────────────────────────────────────────────────────────
$raw_name = GETPOST('filename', 'alpha');
$filename = basename($raw_name); // strip any path components

if (!$filename || !preg_match('/^(rollback|snapshot)_\d{8}_\d{6}\.php$/', $filename)) {
	http_response_code(400);
	print 'Invalid filename.';
	exit;
}

$max     = max(2, (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10));
$backup  = new MokoDoliTrainingBackup($db, $max);
$path    = $backup->getBackupByName($filename);

if (!$path || !file_exists($path)) {
	http_response_code(404);
	print 'Backup file not found.';
	exit;
}

// ── Integrity check before serving ────────────────────────────────────────────
$integrity = $backup->verifyIntegrity($path);
if (!$integrity['ok']) {
	http_response_code(409);
	print 'Integrity check failed: ' . htmlspecialchars($integrity['reason']) . '. File will not be served.';
	exit;
}

// ── Audit ─────────────────────────────────────────────────────────────────────
$audit = new MokoDoliTrainingAudit($db);
$audit->log(
	fk_user: (int) $user->id,
	action:  'backup_download',
	status:  'ok',
	backup_file: $path,
	note:    'Download: ' . $filename,
	entity:  (int) $conf->entity
);

// ── Stream file ────────────────────────────────────────────────────────────────
// Strip PHP guard header line, deliver clean SQL to the browser
$raw   = file_get_contents($path);
$clean = preg_replace('/^<\?php[^\n]*\?>\s*/s', '', $raw, 1);
// Rename download to .sql so the browser/editor handles it correctly
$dl_name = preg_replace('/\.php$/', '.sql', $filename);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $dl_name . '"');
header('Content-Length: ' . mb_strlen($clean, '8bit'));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$db->close();
print $clean;
exit;
