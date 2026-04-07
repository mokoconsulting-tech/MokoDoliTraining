<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Guide
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/guide/seed-reset.md
VERSION:  01.00.02
BRIEF:    Guide for seeding and resetting the MokoDoliTraining dataset.
-->

## Seed and Reset

### Seeding

Go to **Setup > MokoDoliTraining** and click **Seed Training Data**.

The seed operation executes `src/sql/mokotraining.sql` against the active database. Every statement uses `ON DUPLICATE KEY UPDATE`, so running the seed more than once is safe — existing rows are refreshed, not duplicated.

On success, the page reports the number of statements executed and sets the `MOKODOLITRAINING_SEEDED` constant to `1` and records the timestamp in `MOKODOLITRAINING_SEED_DATE`.

### Resetting

Click **Reset Training Data** on the same page.

The reset operation executes `src/sql/mokotraining_reset.sql`, which contains 49 `DELETE` statements ordered from child tables to parent tables with `FOREIGN_KEY_CHECKS = 0` wrapping the block. All rowids listed in `manifest.json` are targeted.

On success, `MOKODOLITRAINING_SEEDED` is set to `0` and `MOKODOLITRAINING_RESET_DATE` is updated.

### CLI alternative

Both operations can be run directly via the MySQL CLI:

```bash
# Seed
mysql -u root -p dolibarr < src/sql/mokotraining.sql

# Reset
mysql -u root -p dolibarr < src/sql/mokotraining_reset.sql
```

### Manifest

`src/sql/manifest.json` lists every table and rowid inserted by the seed. It is used by the reset script and by the admin page manifest viewer. Regenerate it whenever `mokotraining.sql` changes.

## Metadata

| Field | Value |
|---|---|
| Document Type | Guide |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining v1.x |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /docs/guide/seed-reset.md |
| Version | 01.00.00 |
| Status | Draft |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |
