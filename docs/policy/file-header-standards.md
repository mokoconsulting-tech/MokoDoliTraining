<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Policy
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/policy/file-header-standards.md
VERSION:  01.00.03
BRIEF:    MokoStandards file header requirements for MokoDoliTraining.
-->

## File Header Standards

Every file in this repository must open with a copyright header. Two tiers apply.

### Minimal header

For config, language files, simple utilities, and files under ~100 lines:

```
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later
```

### Full header

For complex code, admin pages, and all files listed below. Append the GPL warranty disclaimer after the SPDX identifier:

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

Always use Full for:

- All `index.php` directory protection files
- All `README.md` files (warranty text in body, not comment)
- `core/modules/mod*.class.php`

### FILE INFORMATION block

Append after the copyright text in every header:

```
DEFGROUP: MokoDoliTraining.[Subgroup]
INGROUP:  MokoDoliTraining[.Parent]
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /path/to/file.ext
VERSION:  XX.YY.ZZ
BRIEF:    One-line description (max 80 chars)
```

Optional fields: `NOTE`, `AUTHOR`, `DEPRECATED`.

### Per-language comment syntax

| Language | Style |
|---|---|
| PHP | `/* ... */` block comment |
| Markdown | `<!-- ... -->` comment block |
| SQL | `-- ` line comments |
| INI / lang | `; ` line comments |

### Enforcement

See `docs/policy/enforcement-levels.md`. FILE INFORMATION block fields are REQUIRED (level 3).  
SPDX identifier and copyright line are FORCED (level 4) — never remove.

## Metadata

| Field | Value |
|---|---|
| Document Type | Policy |
| Domain | MokoStandards |
| Applies To | MokoDoliTraining — all files |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /docs/policy/file-header-standards.md |
| Version | 01.00.00 |
| Status | Active |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |
