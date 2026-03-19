<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.API
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/api/manifest.md
VERSION:  development
BRIEF:    Schema and usage reference for manifest.json.
-->

## `manifest.json` — Schema and Usage

**File:** `src/sql/manifest.json`

### Purpose

`manifest.json` is the authoritative record of every row inserted by `mokotraining.sql`. It is used by:

- `mokotraining_reset.sql` — to target exact rowids in DELETE statements
- `modMokoDoliTraining::getManifest()` — to expose the index to the admin UI
- The admin page manifest table — so facilitators can audit what is loaded

### Schema

```json
{
  "module": "MokoDoliTraining",
  "id": 185068,
  "tables": {
    "llx_table_name": [50, 51, 52],
    "llx_another_table": [60, 61]
  }
}
```

| Field | Type | Description |
|---|---|---|
| `module` | string | Module name |
| `id` | int | Dolibarr module ID |
| `tables` | object | Keys are table names; values are sorted int arrays of rowids |

### Stats (current seed)

- Tables tracked: 49
- Total rows: 740

### Regeneration

After any change to `mokotraining.sql`, regenerate the manifest:

```bash
python3 docs/scripts/gen_manifest.py src/sql/mokotraining.sql > src/sql/manifest.json
```

Then update `mokotraining_reset.sql` to include any new rowids, and document the change in `CHANGELOG.md`.

### Junction tables

`llx_categorie_societe` and `llx_categorie_product` have no `rowid` column. Their manifest entries list `fk_categorie` values rather than rowids. The reset script handles these with `DELETE WHERE fk_categorie IN (...)`.

## Metadata

| Field | Value |
|---|---|
| Document Type | API Reference |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining v1.x |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /docs/api/manifest.md |
| Version | 01.00.00 |
| Status | Draft |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |
