<?php
/**
 * deploy-putty.php -- Sync src/ to remote server using PuTTY psftp/pscp.
 *
 * Usage:
 *   php scripts/deploy-putty.php [--dry-run]
 *
 * Reads connection details from src/sftp-config.json.
 * Key path can be overridden with MOKODOLI_SSH_KEY env var.
 * Set MOKODOLI_NO_DEPLOY=1 to skip entirely.
 *
 * Dry-run mode: lists every file that would be uploaded or deleted,
 * with file sizes, but makes no remote changes.
 */

// ── Config ────────────────────────────────────────────────────────────────────

$dry_run  = in_array('--dry-run', $argv ?? [], true);
$repo_root = dirname(__DIR__);
$src_dir   = $repo_root . '/src';
$config    = $src_dir . '/sftp-config.json';

if (getenv('MOKODOLI_NO_DEPLOY') === '1') {
    echo "[deploy] MOKODOLI_NO_DEPLOY=1 -- skipping.\n";
    exit(0);
}

if (!file_exists($config)) {
    echo "[deploy] sftp-config.json not found -- skipping.\n";
    exit(0);
}

// ── Parse sftp-config.json ────────────────────────────────────────────────────
// Config uses JS-style // comments so strip them before json_decode
$raw = file_get_contents($config);
$raw = preg_replace('!//[^\n]*!', '', $raw);           // strip // comments
$raw = preg_replace(',/\*.*?\*/,s', '', $raw);          // strip /* */ comments
$raw = preg_replace('/,\s*([\]}])/s', '$1', $raw);     // strip trailing commas
$cfg = json_decode($raw, true);

if (!$cfg) {
    echo "[deploy] Could not parse sftp-config.json.\n";
    exit(1);
}

$host        = $cfg['host']        ?? '';
$user        = $cfg['user']        ?? '';
$port        = $cfg['port']        ?? '22';
$remote_path = rtrim($cfg['remote_path'] ?? '', '/') . '/';
$ssh_key     = getenv('MOKODOLI_SSH_KEY') ?: ($cfg['ssh_key_file'] ?? '');

// Normalise key path separators for the OS
$ssh_key = str_replace('/', DIRECTORY_SEPARATOR, $ssh_key);

// ── Validate ──────────────────────────────────────────────────────────────────
$missing = [];
if (!$host)        $missing[] = 'host';
if (!$user)        $missing[] = 'user';
if (!$remote_path) $missing[] = 'remote_path';
if ($missing) {
    echo "[deploy] Missing in sftp-config.json: " . implode(', ', $missing) . "\n";
    exit(1);
}

if (!file_exists($ssh_key)) {
    echo "[deploy] SSH key not found: $ssh_key\n";
    echo "[deploy] Set MOKODOLI_SSH_KEY env var to override.\n";
    exit(1);
}

// ── Collect local src/ files (respecting sftp-config ignore patterns) ─────────
$ignore_patterns = $cfg['ignore_regexes'] ?? [];
// Always ignore these even if not in config
$ignore_patterns[] = 'sftp-config';
$ignore_patterns[] = '\.git';

function is_ignored(string $rel, array $patterns): bool
{
    foreach ($patterns as $pat) {
        if (@preg_match('/' . $pat . '/i', $rel)) {
            return true;
        }
    }
    return false;
}

function collect_files(string $dir, string $base): array
{
    $files = [];
    $iter  = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if ($file->isDir()) continue;
        $rel = ltrim(str_replace($base, '', $file->getPathname()), '/\\');
        $rel = str_replace('\\', '/', $rel); // always forward slashes
        $files[] = ['rel' => $rel, 'abs' => $file->getPathname(), 'size' => $file->getSize()];
    }
    return $files;
}

$all_files  = collect_files($src_dir, $src_dir);
$sync_files = array_filter($all_files, fn($f) => !is_ignored($f['rel'], $ignore_patterns));
$sync_files = array_values($sync_files);

// ── Print header ──────────────────────────────────────────────────────────────
$mode = $dry_run ? 'DRY RUN' : 'LIVE';
echo str_repeat('─', 70) . "\n";
echo "[deploy] Mode       : $mode\n";
echo "[deploy] Host       : $user@$host:$port\n";
echo "[deploy] Remote     : $remote_path\n";
echo "[deploy] Key        : $ssh_key\n";
echo "[deploy] Files found: " . count($sync_files) . " in src/\n";
echo str_repeat('─', 70) . "\n";

if ($dry_run) {
    // ── Dry-run: list all files with sizes ────────────────────────────────────
    $total_bytes = 0;
    $by_dir = [];
    foreach ($sync_files as $f) {
        $dir = dirname($f['rel']);
        $by_dir[$dir][] = $f;
        $total_bytes += $f['size'];
    }
    ksort($by_dir);

    $col1 = 55;
    printf("  %-{$col1}s  %10s\n", 'File', 'Size');
    echo '  ' . str_repeat('-', $col1 + 12) . "\n";
    foreach ($by_dir as $dir => $files) {
        foreach ($files as $f) {
            printf("  %-{$col1}s  %10s\n",
                $f['rel'],
                number_format($f['size']) . ' B'
            );
        }
    }
    echo '  ' . str_repeat('-', $col1 + 12) . "\n";
    printf("  %-{$col1}s  %10s\n", count($sync_files) . ' files total', number_format($total_bytes) . ' B');
    echo "\n[deploy] Dry-run complete -- no files transferred.\n";
    echo "[deploy] Re-run without --dry-run to deploy.\n";
    exit(0);
}

// ── Live: build psftp batch file and execute ──────────────────────────────────
$batch = tempnam(sys_get_temp_dir(), 'deploy_');
$fh    = fopen($batch, 'w');

fwrite($fh, "cd $remote_path\n");

// Track directories we've already created this session
$made_dirs = [];
$uploaded  = 0;

foreach ($sync_files as $f) {
    $remote_dir = dirname($f['rel']);
    if ($remote_dir !== '.' && !isset($made_dirs[$remote_dir])) {
        fwrite($fh, "mkdir $remote_dir\n");
        $made_dirs[$remote_dir] = true;
    }
    // psftp put: local remote
    $local  = str_replace('/', DIRECTORY_SEPARATOR, $f['abs']);
    fwrite($fh, "put \"$local\" \"" . $f['rel'] . "\"\n");
    echo "[deploy]   PUT " . $f['rel'] . "\n";
    $uploaded++;
}

fwrite($fh, "quit\n");
fclose($fh);

// Build psftp command using proc_open (no shell injection)
$cmd = ['psftp', "$user@$host", '-P', (string)$port, '-i', $ssh_key, '-batch', '-b', $batch];
echo "[deploy] Connecting...\n";

$desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open($cmd, $desc, $pipes);

if (!is_resource($proc)) {
    echo "[deploy] Failed to start psftp.\n";
    unlink($batch);
    exit(1);
}

$out = stream_get_contents($pipes[1]);
$err = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit_code = proc_close($proc);
unlink($batch);

// Filter noisy psftp status lines
foreach (explode("\n", $out) as $line) {
    $line = trim($line);
    if ($line === '') continue;
    if (preg_match('/^(Remote working directory|Using keyboard|psftp>)/i', $line)) continue;
    echo "[psftp] $line\n";
}
if ($err) {
    foreach (explode("\n", $err) as $line) {
        if (trim($line)) echo "[psftp-err] " . trim($line) . "\n";
    }
}

if ($exit_code !== 0) {
    echo "[deploy] psftp exited with code $exit_code.\n";
    exit($exit_code);
}

echo str_repeat('─', 70) . "\n";
echo "[deploy] Done: $uploaded files uploaded to $host\n";
