<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

DEFGROUP: MokoDoliTraining.Docs
INGROUP:  MokoDoliTraining
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /README.md
VERSION:  01.00.00
BRIEF:    Developer README for the MokoDoliTraining repository.
-->

# MokoDoliTraining — Developer README

Dolibarr v23 demo and training dataset module. Module ID `185068`.  
Maintained by [Moko Consulting](https://mokoconsulting.tech) · GPL-3.0-or-later

---

## Repository layout

```
MokoDoliTraining/
├── src/                          Dolibarr module root (deploy this as mokodolitraining/)
│   ├── admin/setup.php           Admin seed/reset UI
│   ├── core/modules/
│   │   └── modMokoDoliTraining.class.php
│   ├── langs/en_US/
│   │   └── mokodolitraining.lang
│   ├── sql/
│   │   ├── mokotraining.sql      Full seed dataset (~2,500 lines)
│   │   ├── mokotraining_reset.sql 49 DELETE statements in FK order
│   │   └── manifest.json         Rowid index — 49 tables, 740 rows
│   ├── index.php                 Directory protection (accessforbidden)
│   └── README.md                 End-user guide
├── docs/                         MokoStandards documentation suite
│   ├── index.md
│   ├── guide/
│   │   ├── installation.md
│   │   └── seed-reset.md
│   ├── api/
│   │   ├── module-class.md
│   │   └── manifest.md
│   └── policy/
│       ├── file-header-standards.md
│       └── enforcement-levels.md
├── CHANGELOG.md
├── CONTRIBUTING.md
└── README.md                     ← you are here
```

---

## Development setup

```bash
git clone https://github.com/mokoconsulting-tech/MokoDoliTraining.git
cd MokoDoliTraining

# Symlink src/ into a local Dolibarr custom/ for live editing
ln -s "$(pwd)/src" /path/to/dolibarr/htdocs/custom/mokodolitraining
```

Requires Dolibarr v23+, MySQL 8.0+, and the MokoCRM module active.

---

## Branching

| Branch | Purpose |
|---|---|
| `main` | Stable releases |
| `dev` | Active development — PRs target here |

---

## Versioning

Semantic versioning: `MAJOR.MINOR.PATCH` — zero-padded in file headers (`01.00.00`).  
Current module version: `development` (set in `modMokoDoliTraining.class.php`).

---

## SQL dataset

The seed file (`src/sql/mokotraining.sql`) contains 88 `INSERT ... ON DUPLICATE KEY UPDATE` blocks covering all Dolibarr core tables. All rows use rowids ≥ 50 or explicit training ranges (60–66, 90–95) to avoid colliding with Dolibarr's auto-generated data.

To regenerate `manifest.json` after modifying the seed file, run:

```bash
python3 docs/scripts/gen_manifest.py src/sql/mokotraining.sql > src/sql/manifest.json
```

---

## Standards

All files follow [MokoStandards](https://github.com/mokoconsulting-tech/MokoStandards):

- GPL-3.0-or-later headers on every file
- FILE INFORMATION block (`DEFGROUP`, `INGROUP`, `REPO`, `PATH`, `VERSION`, `BRIEF`)
- PHP: K&R braces, tabs, max 50-line functions
- Markdown: `## Metadata` + `## Revision History` tables in every `.md`
- No em dashes

See `docs/policy/` for full enforcement details.

---

## License

Copyright (C) 2026 Moko Consulting \<hello@mokoconsulting.tech\>

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

## Metadata

| Field | Value |
|---|---|
| Document Type | Developer README |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining — all versions |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /README.md |
| Version | 01.00.00 |
| Status | Draft |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |
