<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs
INGROUP:  MokoDoliTraining
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /CONTRIBUTING.md
VERSION:  01.00.03
BRIEF:    Contribution guidelines for MokoDoliTraining.
-->

# Contributing

## Branching

All changes go to `dev`. Open a PR against `dev` — never target `main` directly.  
`main` receives merges from `dev` at release points only.

## Standards

Every file must carry a MokoStandards header. See `docs/policy/file-header-standards.md` for the required fields and per-language syntax.

PHP rules: K&R braces, tabs, max 50-line functions, no bare `except`, no SQL string concatenation.

Markdown files must end with `## Metadata` and `## Revision History` tables.

## SQL changes

When adding or removing rows from `mokotraining.sql`:

1. All new rows must use `ON DUPLICATE KEY UPDATE` — no bare `INSERT`.
2. Rowids must stay within the reserved training ranges (50+, 60–66, 90–95 for third parties).
3. Regenerate `manifest.json` after any change.
4. Update `mokotraining_reset.sql` to include any new rowids.
5. Document the change in `CHANGELOG.md` under `[Unreleased]`.

## Commit messages

Use imperative mood, present tense. Keep subject under 72 characters.  
Reference issue numbers where applicable: `Fix facturedet column mismatch (#12)`.

## Metadata

| Field | Value |
|---|---|
| Document Type | Contributing Guide |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining — all versions |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /CONTRIBUTING.md |
| Version | 01.00.00 |
| Status | Active |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |
