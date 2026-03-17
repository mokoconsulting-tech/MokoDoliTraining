<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Guide
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/guide/backup-recovery.md
VERSION:  01.00.00
BRIEF:    Backup types, file layout, HTTP protection, restore procedures, retention.
-->

## Backup and Recovery

### Backup types

MokoDoliTraining maintains two categories of backup file.

**Rollback backup** (`rollback_*.php`) is a full-database dump. It is created in two situations:

- Automatically when the module activates (before the activation snapshot).
- When the facilitator clicks **Install Training Records** (before the seed runs).

A rollback backup contains every table and every row in the Dolibarr database at the moment it was taken. It is the authoritative recovery point for returning the environment to a pre-training state. The dump is produced by Dolibarr's own `Utils::dumpDatabase()` with `type=mysqlnobin` (PHP-based, no `mysqldump` binary required), which outputs `DROP TABLE IF EXISTS` + `CREATE TABLE` + batched `INSERT INTO` per table.

**Snapshot backup** (`snapshot_*.php`) is a manifest-scoped dump. It is created when **Install Training Records** completes. A snapshot contains only the rows defined in `manifest.json` -- the exact training dataset in its freshly seeded state, using `INSERT ... ON DUPLICATE KEY UPDATE`. It does not include DDL. It is used by **Reset to Snapshot** to quickly restore training data between sessions without touching any other tables.

### File layout

All backup files are stored in `src/backup/`:

```
src/backup/
  .htaccess                           Apache deny-all (HTTP access blocked)
  index.php                           Directory stub (returns 403)
  .lock                               Created during operations; auto-deleted on completion
  rollback_20260313_120000.php        Full-database backup (PHP-wrapped)
  rollback_20260313_120000.php.sha256
  snapshot_20260313_120100.php        Manifest-scoped backup (PHP-wrapped)
  snapshot_20260313_120100.php.sha256
```

The filename format is `{type}_{YYYYMMDD}_{HHMMSS}.php`. The `.php.sha256` sidecar holds the SHA-256 hash of the complete file content (including the PHP guard line) and is used to verify file integrity before any restore.

### HTTP access protection (PHP wrapper)

Every backup file opens with a PHP die guard:

```php
<?php die('No direct access.'); ?>
-- MokoDoliTraining FULL backup: rollback | 2026-03-13T12:00:00+00:00
...
```

If a backup file is accessed via HTTP (for example by a misconfigured web server that ignores `.htaccess`), PHP executes the file and terminates immediately with no output. The SQL content is never sent to the browser.

This is a defence-in-depth measure. The `.htaccess` deny-all rule is the primary control; the PHP guard is the fallback. Together they ensure backup files are not accessible over HTTP under any normal web server configuration.

When the backup class reads a file internally (via `file()` or `file_get_contents()`), the PHP guard line is skipped. In `execSqlFile()`, any line starting with `<?php` is ignored. In the download handler (`admin/download.php`), the PHP guard is stripped before the file is sent to the browser, and the downloaded file is renamed from `.php` to `.sql` so it opens correctly in SQL editors.

### Backup file format

The guard line is followed by a SQL header comment block:

```sql
-- MokoDoliTraining FULL backup: rollback | 2026-03-13T12:00:00+00:00
-- Module ID: 185068 | Utils::dumpDatabase (mysqlnobin) | DO NOT EDIT
-- SHA256: a3f1c2d4...
```

For full (rollback) backups, the body is standard mysqldump-style SQL:

```sql
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;

DROP TABLE IF EXISTS `llx_societe`;
CREATE TABLE `llx_societe` (...);
LOCK TABLES `llx_societe` WRITE;
INSERT INTO `llx_societe` (...) VALUES (...),(...);
UNLOCK TABLES;
...
```

For snapshot backups, the body is manifest-scoped DML only -- no DDL:

```sql
SET FOREIGN_KEY_CHECKS = 0;
-- TABLE: llx_societe (14 rows)
INSERT INTO `llx_societe` (...) VALUES (...) ON DUPLICATE KEY UPDATE ...;
...
SET FOREIGN_KEY_CHECKS = 1;
```

### Integrity verification

Every backup has a SHA-256 checksum embedded in the comment header and stored in the `.php.sha256` sidecar. Before any restore, the module:

1. Reads the sidecar checksum (falls back to the embedded checksum if the sidecar is missing).
2. Replaces the embedded checksum line with `-- SHA256: {CHECKSUM}` to reconstruct the pre-hash state.
3. Computes `sha256(file_content)` and compares it to the stored value.
4. Refuses to restore if the values do not match.

The checksum covers the full file including the PHP guard line. If the guard line is altered the checksum fails.

To manually verify a backup file outside Dolibarr:

```bash
stored=$(cat backup/rollback_20260313_120000.php.sha256)
actual=$(sed 's/^-- SHA256: [a-f0-9]\{64\}/-- SHA256: {CHECKSUM}/' \
    backup/rollback_20260313_120000.php | sha256sum | cut -d' ' -f1)

[ "$stored" = "$actual" ] && echo "OK" || echo "MISMATCH"
```

### Retention policy

The module enforces a per-type retention limit (default: 10 files). When a new backup is created and the count exceeds the limit, the oldest files of that type are deleted. The `.php.sha256` sidecar is always deleted with its parent.

Change the limit under **Setup > Settings > Maximum backups per type**. The minimum enforced value is 2.

### Recovery procedures

#### Procedure 1: Restore training data between sessions

1. Navigate to **Setup > MokoDoliTraining**.
2. Click **Reset to Snapshot**.
3. Confirm the prompt.
4. Wait for the success messages: reset OK + restore OK.
5. Verify the dashboard **Status** badge shows *Seeded*.

#### Procedure 2: Full environment rollback

1. Navigate to **Setup > MokoDoliTraining**.
2. Click **Rollback to Pre-Install State**.
3. Confirm the prompt.
4. Wait for the success messages.
5. Verify the dashboard **Status** badge shows *Not Seeded*.
6. Run **Install Training Records** again to reload the training dataset.

#### Procedure 3: Restore from a specific backup file

1. Navigate to the **Backups** tab.
2. Locate the target backup in the list.
3. Click the verify icon (checkmark) to confirm integrity.
4. If integrity passes, click the restore icon (refresh).
5. Confirm the prompt.

#### Procedure 4: CLI recovery (admin UI unavailable)

Backup files contain a PHP guard line that must be stripped before piping to MySQL:

```bash
cd /path/to/dolibarr/htdocs/custom/mokodolitraining

# Strip PHP guard and restore
tail -n +2 backup/rollback_20260313_120000.php \
    | mysql -u root -p dolibarr

# Or reset training rows first, then restore snapshot
mysql -u root -p dolibarr < sql/mokotraining_reset.sql
tail -n +2 backup/snapshot_20260313_120100.php \
    | mysql -u root -p dolibarr
```

After CLI recovery, update the seeded state constant:

```sql
UPDATE llx_const SET value = '0'
WHERE name = 'MOKODOLITRAINING_SEEDED' AND entity = 1;

UPDATE llx_const SET value = NOW()
WHERE name = 'MOKODOLITRAINING_RESET_DATE' AND entity = 1;
```

### Downloading backups

The **Backups** tab provides a download button for each file. The download handler (`admin/download.php`):

1. Validates the filename against the expected pattern (`rollback_` or `snapshot_` + timestamp + `.php`).
2. Runs an integrity check before serving.
3. Strips the PHP guard line from the file content.
4. Renames the download from `.php` to `.sql` so it opens correctly in SQL editors.

The file delivered to the browser is clean SQL with no PHP wrapper.

### Locking

The module uses a `.lock` file to prevent concurrent operations. A lock older than 300 seconds is considered stale and broken automatically.

To clear a stale lock manually:

```bash
rm htdocs/custom/mokodolitraining/backup/.lock
```

After clearing the lock, verify the database is in a consistent state before running another operation. Check the **Audit Log** tab for the interrupted operation's partial log entry.

### Security

Backup files contain full database content including user credentials (hashed), contact records, and configuration values.

Three layers of access control protect them:

1. **PHP guard** -- `<?php die('No direct access.'); ?>` at the top of every backup file. Terminates execution if accessed via HTTP.
2. **`.htaccess`** -- Apache deny-all rule blocks direct HTTP access to the `backup/` directory.
3. **`index.php` stub** -- Returns 403 for any directory listing attempt.

For Nginx, add an equivalent location block since Nginx does not process `.htaccess` files:

```nginx
location ~* /mokodolitraining/backup/ {
    deny all;
    return 403;
}
```

## Metadata

| Field | Value |
|---|---|
| Document Type | Guide |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining v1.x |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /docs/guide/backup-recovery.md |
| Version | 01.01.00 |
| Status | Active |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | -- |
| 2026-03-13 | jmiller | v01.01.00 | PHP wrapper pattern; .php extension; CLI tail -n +2 strip; download handler docs; Nginx note |
