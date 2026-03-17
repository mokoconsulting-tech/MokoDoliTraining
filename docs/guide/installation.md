<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Guide
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/guide/installation.md
VERSION:  01.00.00
BRIEF:    Installation guide for the MokoDoliTraining module.
-->

## Installation

### Requirements

- Dolibarr v23+
- MokoCRM module active
- MySQL 8.0+ or MariaDB 10.5+
- Dolibarr admin access

### Deploy

Copy `src/` into `htdocs/custom/` and rename the folder to `mokodolitraining`:

```bash
cp -r src/ /path/to/dolibarr/htdocs/custom/mokodolitraining
```

For development, use a symlink instead:

```bash
ln -s "$(pwd)/src" /path/to/dolibarr/htdocs/custom/mokodolitraining
```

### Enable in Dolibarr

1. Log in as an administrator.
2. Go to **Setup > Modules/Applications**.
3. Locate **MokoDoliTraining** under the *Moko Consulting* family.
4. Click **Enable**.

Dolibarr will refuse to enable the module if MokoCRM is not already active.

### Verify

After enabling, navigate to **Setup > MokoDoliTraining**. The admin page should load with the manifest table visible and the Seed / Reset buttons present.

## Metadata

| Field | Value |
|---|---|
| Document Type | Guide |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining v1.x |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /docs/guide/installation.md |
| Version | 01.00.00 |
| Status | Draft |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |
