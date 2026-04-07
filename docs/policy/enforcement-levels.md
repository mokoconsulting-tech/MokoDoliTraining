<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Policy
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/policy/enforcement-levels.md
VERSION:  01.00.03
BRIEF:    MokoStandards enforcement tier definitions for MokoDoliTraining.
-->

## Enforcement Levels

MokoStandards uses six enforcement tiers. Apply these when reviewing or generating any file in this repository.

| Level | Name | Behaviour |
|---|---|---|
| 6 | NOT_ALLOWED | Blocked — never permitted in any file |
| 5 | NOT_SUGGESTED | Discouraged — warn if present, do not introduce |
| 4 | FORCED | Must always be present — never remove or omit |
| 3 | REQUIRED | Must exist — treat absence as an error |
| 2 | SUGGESTED | Should exist — warn if absent |
| 1 | OPTIONAL | Include when useful |

### Applied to this repository

| Rule | Level |
|---|---|
| Copyright line + SPDX identifier | 4 — FORCED |
| FILE INFORMATION block (all fields) | 3 — REQUIRED |
| `## Metadata` table in every `.md` | 3 — REQUIRED |
| `## Revision History` table in every `.md` | 3 — REQUIRED |
| GPL warranty text in Full-tier files | 3 — REQUIRED |
| `ON DUPLICATE KEY UPDATE` on all INSERTs | 3 — REQUIRED |
| Rowids within reserved training ranges | 3 — REQUIRED |
| Em dashes (`--` in prose) | 6 — NOT_ALLOWED |
| Bare `except:` / `catch` blocks | 6 — NOT_ALLOWED |
| SQL string concatenation | 6 — NOT_ALLOWED |
| Real client data in demo files | 6 — NOT_ALLOWED |
| Hardcoded credentials or tokens | 6 — NOT_ALLOWED |
| Google-style docstrings (Python) | 2 — SUGGESTED |
| Type hints (Python) | 3 — REQUIRED |

## Metadata

| Field | Value |
|---|---|
| Document Type | Policy |
| Domain | MokoStandards |
| Applies To | MokoDoliTraining — all files |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /docs/policy/enforcement-levels.md |
| Version | 01.00.00 |
| Status | Active |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |

### Backup system

| Rule | Level |
|---|---|
| Rollback backup must exist before any seed operation | 3 — REQUIRED |
| Snapshot backup must be created immediately after seed | 3 — REQUIRED |
| Backup files stored in `src/backup/` with .htaccess + index.php | 3 — REQUIRED |
| Backup files must never contain real client data | 6 — NOT_ALLOWED |
| Backup SQL must use `ON DUPLICATE KEY UPDATE` (not bare INSERT) | 4 — FORCED |
