<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs
INGROUP:  MokoDoliTraining
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /CHANGELOG.md
VERSION:  01.00.00
BRIEF:    Version history for MokoDoliTraining.
-->

# Changelog

All notable changes to MokoDoliTraining are documented here.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Versioning follows [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

### Added
- Initial module scaffold (ID 185068)
- Full Dolibarr v23 seed dataset — 49 tables, 740 rows
- `mokotraining_reset.sql` — 49 DELETE statements in safe FK order
- `manifest.json` — rowid index for all seeded tables
- Admin setup page with one-click Seed and Reset actions
- `$this->const` registration for `MOKODOLITRAINING_SEEDED`, `_SEED_DATE`, `_RESET_DATE`, `_VERSION`
- MokoCRM dependency enforcement
- `accessforbidden()` index.php in all subdirectories
- End-user README (`src/README.md`)
- Developer README (`README.md`)
- Full MokoStandards docs suite (`docs/`)

## Metadata

| Field | Value |
|---|---|
| Document Type | Changelog |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining — all versions |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /CHANGELOG.md |
| Version | 01.00.00 |
| Status | Active |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial entry | — |

### Changed (Enterprise Expansion)

#### New files
- `src/admin/backups.php` — Backup manager: list, verify SHA-256 integrity, restore from specific file, delete, download
- `src/admin/logs.php` — Audit log viewer: filterable table, operation stats, configurable purge
- `src/admin/download.php` — Secure backup download handler with integrity check and directory traversal protection
- `src/cron/MokoDoliTrainingCron.class.php` — Dolibarr cron job for backup rotation and log purge
- `src/core/triggers/interface_99_modMokoDoliTraining_...Trigger.class.php` — Dolibarr trigger for user event audit during training sessions
- `src/lib/mokodolitraining.lib.php` — Shared helpers: tab builder, formatters, class loader, constants accessor
- `src/class/MokoDoliTrainingAudit.class.php` — Audit log writer/reader for `llx_mokodolitraining_log`
- `src/sql/llx_mokodolitraining_log.sql` — Audit log DB table (auto-created on module init)

#### Updated files
- `src/admin/setup.php` — CSRF tokens, process lock guard, timing metrics, settings form (max_backups, log_retention), tabbed layout
- `src/class/MokoDoliTrainingBackup.class.php` — File locking, SHA-256 checksum embed + sidecar, integrity verification before restore, retention enforcement per label, GB-scale size formatting
- `src/core/modules/modMokoDoliTraining.class.php` — 8 constants, cron registration, trigger flag, backup dir init on module enable
- `src/langs/en_US/mokodolitraining.lang` — 80+ strings covering all pages and actions

#### New constants
| Constant | Purpose |
|---|---|
| `MOKODOLITRAINING_MAX_BACKUPS` | Max backup files per type (default 10) |
| `MOKODOLITRAINING_LOG_RETENTION` | Audit log retention days (default 90) |
