<?php
/**
 * deploy-resolve.php -- Resolve connection details and auth method for deploy-dev workflow.
 *
 * Reads environment variables set by the GitHub Actions job env block,
 * writes resolved outputs to $GITHUB_OUTPUT, writes the SSH key to disk
 * if key auth is chosen, and prints GitHub Actions notice/warning annotations.
 *
 * Env vars consumed:
 *   DEV_FTP_HOST        -- host or host:port
 *   DEV_FTP_USERNAME    -- SFTP username
 *   DEV_FTP_PASSWORD    -- password / key passphrase (optional)
 *   DEV_FTP_KEY         -- SSH private key in OpenSSH format (optional)
 *   DEV_FTP_PATH        -- base remote path
 *   DEV_FTP_PATH_SUFFIX -- suffix appended to DEV_FTP_PATH
 *   DRY_RUN             -- 'true' | 'false'
 *   GITHUB_OUTPUT       -- path to the GitHub Actions output file
 *   HOME                -- runner home directory (for ~/.ssh/)
 */

/**
 * Run a command as an argument array (no shell — no injection risk).
 * Returns [stdout, exit_code].
 */
function run_cmd(array $args): array
{
    $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($args, $desc, $pipes);
    if (!is_resource($proc)) {
        return ['', 1];
    }
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($proc);
    return [$stdout, $exit];
}

$host_raw  = getenv('DEV_FTP_HOST') ?: '';
$password  = getenv('DEV_FTP_PASSWORD') ?: '';
$ftp_key   = getenv('DEV_FTP_KEY') ?: '';
$path      = getenv('DEV_FTP_PATH') ?: '';
$suffix    = getenv('DEV_FTP_PATH_SUFFIX') ?: '';
$dry_run   = getenv('DRY_RUN') === 'true';
$gh_output = getenv('GITHUB_OUTPUT') ?: '/dev/null';
$home      = getenv('HOME') ?: '/root';

// ── Host / port ─────────────────────────────────────────────────────────────
if (str_contains($host_raw, ':')) {
    [$host, $port] = explode(':', $host_raw, 2);
} else {
    $host = $host_raw;
    $port = '22';
}

// ── Remote path ──────────────────────────────────────────────────────────────
$remote = rtrim($path, '/') . '/' . ltrim($suffix, '/');

// ── Dry-run flag ─────────────────────────────────────────────────────────────
$dry_flag = $dry_run ? '--dry-run' : '';
if ($dry_run) {
    echo "::notice::DRY RUN — no files will be transferred\n";
}

// ── Auth resolution ──────────────────────────────────────────────────────────
$auth_method = 'password';
$ssh_dir     = $home . '/.ssh';
$key_path    = $ssh_dir . '/deploy_key';

if ($ftp_key !== '') {
    if (!is_dir($ssh_dir)) {
        mkdir($ssh_dir, 0700, true);
    }
    file_put_contents($key_path, $ftp_key . "\n");
    chmod($key_path, 0600);

    if ($password !== '') {
        // Attempt to strip key passphrase using password — no shell, args as array
        [$out, $exit] = run_cmd([
            'ssh-keygen', '-p',
            '-P', $password,
            '-N', '',
            '-f', $key_path,
        ]);
        $decrypted = str_contains((string) $out, 'Your identification has been saved');

        if ($decrypted) {
            echo "::notice::Key passphrase decrypted — using key auth\n";
            $auth_method = 'key';
        } else {
            echo "::warning::Key passphrase decryption failed — falling back to password auth\n";
            unlink($key_path);
            $auth_method = 'password';
        }
    } else {
        echo "::notice::No password provided — using key auth as-is\n";
        $auth_method = 'key';
    }
}

// Add host to known_hosts when using key auth
if ($auth_method === 'key') {
    run_cmd(['ssh-keyscan', '-p', $port, $host]);
    // Append separately since proc_open can't redirect to file directly
    [$scan_out] = run_cmd(['ssh-keyscan', '-p', $port, $host]);
    if ($scan_out !== '') {
        file_put_contents($home . '/.ssh/known_hosts', $scan_out, FILE_APPEND);
    }
}

// ── Write GITHUB_OUTPUT ───────────────────────────────────────────────────────
$outputs = [
    'host'        => $host,
    'port'        => $port,
    'remote'      => $remote,
    'dry_flag'    => $dry_flag,
    'auth_method' => $auth_method,
];

$lines = '';
foreach ($outputs as $name => $value) {
    $lines .= "{$name}={$value}\n";
}
file_put_contents($gh_output, $lines, FILE_APPEND);

echo "Resolved: host={$host} port={$port} auth={$auth_method} remote={$remote}\n";
