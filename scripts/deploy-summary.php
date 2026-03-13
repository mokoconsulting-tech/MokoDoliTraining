<?php
/**
 * deploy-summary.php -- Format lftp mirror output as a GitHub Actions job summary.
 *
 * Reads lftp verbose output from stdin or a file, parses the operations,
 * and writes a Markdown summary to $GITHUB_STEP_SUMMARY.
 *
 * Usage:
 *   php scripts/deploy-summary.php <lftp-output-file> <dry_run> <remote_path> <host>
 *
 * Arguments:
 *   $argv[1] -- path to file containing lftp verbose output
 *   $argv[2] -- 'true' | 'false' (dry run mode)
 *   $argv[3] -- remote path synced to
 *   $argv[4] -- host
 */

$log_file  = $argv[1] ?? '';
$dry_run   = ($argv[2] ?? 'false') === 'true';
$remote    = $argv[3] ?? '(unknown)';
$host      = $argv[4] ?? '(unknown)';
$summary_file = getenv('GITHUB_STEP_SUMMARY') ?: '/dev/null';

$raw = $log_file && file_exists($log_file) ? file_get_contents($log_file) : '';
$lines = array_filter(explode("\n", $raw));

// Parse lftp mirror --verbose output into categorised operations
$uploads  = [];  // `Transferring file` or `put`
$deletes  = [];  // `Removing file` or `rm`
$skipped  = [];  // `not modified` or up-to-date
$dirs     = [];  // `Making directory`
$errors   = [];  // lines containing error/fail

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;

    if (preg_match('/^(?:get|put|Transferring file)\s+`?(.+?)\'?$/i', $line, $m)) {
        $uploads[] = $m[1];
    } elseif (preg_match('/^(?:rm|Removing(?: file)?)\s+`?(.+?)\'?$/i', $line, $m)) {
        $deletes[] = $m[1];
    } elseif (preg_match('/Making directory\s+`?(.+?)\'?$/i', $line, $m)) {
        $dirs[] = $m[1];
    } elseif (preg_match('/not modified|up.to.date|already exists/i', $line)) {
        $skipped[] = $line;
    } elseif (preg_match('/error|fail|refused|denied|cannot/i', $line)) {
        $errors[] = $line;
    }
}

$mode   = $dry_run ? '**DRY RUN** — no files were transferred' : 'Deploy complete';
$status = empty($errors) ? ':white_check_mark:' : ':x:';

$md  = "## {$status} Deploy to Dev — Summary\n\n";
$md .= "> {$mode}\n\n";
$md .= "| Setting | Value |\n";
$md .= "|---------|-------|\n";
$md .= "| Host | `{$host}` |\n";
$md .= "| Remote path | `{$remote}` |\n";
$md .= "| Mode | " . ($dry_run ? 'Dry run' : 'Live') . " |\n";
$md .= "| Uploaded / would upload | " . count($uploads) . " |\n";
$md .= "| Deleted / would delete | " . count($deletes) . " |\n";
$md .= "| Directories created | " . count($dirs) . " |\n";
$md .= "| Already up-to-date | " . count($skipped) . " |\n";
$md .= "| Errors | " . count($errors) . " |\n\n";

if ($uploads) {
    $md .= "<details>\n<summary>Files uploaded (" . count($uploads) . ")</summary>\n\n";
    foreach ($uploads as $f) $md .= "- `{$f}`\n";
    $md .= "\n</details>\n\n";
}

if ($deletes) {
    $md .= "<details>\n<summary>Files deleted (" . count($deletes) . ")</summary>\n\n";
    foreach ($deletes as $f) $md .= "- `{$f}`\n";
    $md .= "\n</details>\n\n";
}

if ($dirs) {
    $md .= "<details>\n<summary>Directories created (" . count($dirs) . ")</summary>\n\n";
    foreach ($dirs as $d) $md .= "- `{$d}`\n";
    $md .= "\n</details>\n\n";
}

if ($errors) {
    $md .= "### :warning: Errors\n\n```\n";
    foreach ($errors as $e) $md .= $e . "\n";
    $md .= "```\n\n";
}

if (empty($uploads) && empty($deletes) && empty($dirs) && empty($errors)) {
    $md .= "> :information_source: No file changes detected — remote is already in sync.\n\n";
}

file_put_contents($summary_file, $md, FILE_APPEND);
echo $md;
