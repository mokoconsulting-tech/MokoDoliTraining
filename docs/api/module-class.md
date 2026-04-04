<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.API
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/api/module-class.md
VERSION:  01.00.01
BRIEF:    API reference for modMokoDoliTraining class.
-->

## `modMokoDoliTraining` — API Reference

**File:** `src/core/modules/modMokoDoliTraining.class.php`  
**Extends:** `DolibarrModules`  
**Module ID:** `185068`

### Constructor properties

| Property | Type | Value |
|---|---|---|
| `numero` | int | 185068 |
| `rights_class` | string | `mokodolitraining` |
| `family` | string | `mokoconsulting` |
| `module_position` | int | 2 |
| `version` | string | `development` |
| `depends` | array | `['modMokoCRM']` |
| `picto` | string | `technic` |
| `editor_squarred_logo` | string | `favicon_256@mokodolitraining` |

### Constants registered (`$this->const`)

| Constant | Type | Default | Description |
|---|---|---|---|
| `MOKODOLITRAINING_VERSION` | chaine | `1.0.0` | Dataset version |
| `MOKODOLITRAINING_SEEDED` | chaine | `0` | `1` if seed has been run |
| `MOKODOLITRAINING_SEED_DATE` | chaine | `` | Timestamp of last seed |
| `MOKODOLITRAINING_RESET_DATE` | chaine | `` | Timestamp of last reset |

### Static methods

#### `getManifest(): array`

Reads `src/sql/manifest.json` and returns the `tables` key as an associative array of `table_name => int[]` rowid lists. Returns `[]` if the file does not exist.

#### `getSeedSqlPath(): string`

Returns the absolute filesystem path to `src/sql/mokotraining.sql`.

#### `getResetSqlPath(): string`

Returns the absolute filesystem path to `src/sql/mokotraining_reset.sql`.

#### `getManifestSummary(): array`

Returns `['tables' => int, 'rows' => int]` — a count of tracked tables and total tracked rows.

### Instance methods

#### `init(string $options = ''): int`

Called by Dolibarr when the module is enabled. Loads tables via `_load_tables()` and seeds the `MOKODOLITRAINING_SEEDED` constant. Returns `1` on success, `0` on failure.

#### `remove(string $options = ''): int`

Called by Dolibarr when the module is disabled. Always returns `1`. Training data is not auto-deleted on disable — use the Reset action from the admin page.

## Metadata

| Field | Value |
|---|---|
| Document Type | API Reference |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining v1.x |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /docs/api/module-class.md |
| Version | 01.00.00 |
| Status | Draft |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |
