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
 * VERSION:  01.00.00
 * BRIEF:    Secure backup download handler. Streams SQL content from DB by rowid.
 */

$res = 0;
if (!$res && file_exists('../main.inc.php'))          $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))       $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))    $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';
if (!$res) die('Include of main fails');

dol_include_once('/mokodolitraining/lib/mokodolitraining.lib.php');
dol_include_once('/mokodolitraining/class/MokoDoliTrainingBackup.class.php');
dol_include_once('/mokodolitraining/class/MokoDoliTrainingAudit.class.php');

if (!mokodolitraining_has_perm($user, 'manage')) {
	accessforbidden();
}

// ── Input validation ──────────────────────────────────────────────────────────
$rowid = GETPOSTINT('rowid');

if (!$rowid || $rowid <= 0) {
	http_response_code(400);
	print 'Invalid rowid.';
	exit;
}

$max    = max(2, (int) (getDolGlobalString('MOKODOLITRAINING_MAX_BACKUPS') ?: 10));
$backup = new MokoDoliTrainingBackup($db, $max);
$row    = $backup->loadRow($rowid);

if (!$row || empty($row['content'])) {
	http_response_code(404);
	print 'Backup not found.';
	exit;
}

// ── Integrity check before serving ────────────────────────────────────────────
$integrity = $backup->verifyIntegrity($rowid);
if (!$integrity['ok']) {
	http_response_code(409);
	print 'Integrity check failed: ' . htmlspecialchars($integrity['reason']) . '. Content will not be served.';
	exit;
}

// ── Audit ─────────────────────────────────────────────────────────────────────
$audit = new MokoDoliTrainingAudit($db);
$audit->log(
	fk_user: (int) $user->id,
	action:  'backup_download',
	status:  'ok',
	note:    'Download rowid: ' . $rowid . ' ref: ' . $row['ref'],
	entity:  (int) $conf->entity
);

// ── Stream content ────────────────────────────────────────────────────────────
$content = $row['content'];
$dl_name = 'backup_' . preg_replace('/[^a-z0-9_\-]/i', '_', $row['ref']) . '.sql';

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $dl_name . '"');
header('Content-Length: ' . mb_strlen($content, '8bit'));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$db->close();
print $content;
exit;
