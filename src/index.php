<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Root
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /index.php
 * VERSION:  01.00.00
 * BRIEF:    Directory protection — redirects to Dolibarr root.
 */
$res = 0;
if (!$res && file_exists('../main.inc.php'))        $res = @include '../main.inc.php';
if (!$res && file_exists('../../main.inc.php'))     $res = @include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php'))  $res = @include '../../../main.inc.php';
if (!$res) die('Include of main fails');
header('Location: ' . DOL_URL_ROOT);
exit;
