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
PATH:     /src/README.md
VERSION:  01.00.02
BRIEF:    End-user guide for the MokoDoliTraining Dolibarr module.
-->

# MokoDoliTraining

**Demo and training dataset manager for Dolibarr v23+**  
*Developed by [Moko Consulting](https://mokoconsulting.tech)*

---

## What this module does

MokoDoliTraining loads a complete set of fictional demo data into your Dolibarr instance — clients, contacts, proposals, invoices, contracts, projects, time logs, expense reports, tickets, and more — so trainees can work through realistic exercises without touching real records.

All inserted rows are tracked by rowid. The module admin page lets you **seed** or **reset** the dataset with a single click at any time.

---

## Requirements

- Dolibarr v23 or later
- **MokoCRM module must be active** before enabling this module
- MySQL 8.0+ or MariaDB 10.5+
- Admin access to Dolibarr

---

## Installation

1. Copy the `src/` folder into your Dolibarr `htdocs/custom/` directory and rename it `mokodolitraining`.
2. Log in to Dolibarr as an administrator.
3. Go to **Setup → Modules/Applications**.
4. Find **MokoDoliTraining** under the *Moko Consulting* family and enable it.

---

## Seeding training data

1. Go to **Setup → MokoDoliTraining**.
2. Click **Seed Training Data**.
3. The page will confirm how many SQL statements ran successfully.

The seed operation uses `ON DUPLICATE KEY UPDATE`, so it is safe to run more than once — existing rows are refreshed, not duplicated.

---

## Resetting training data

1. Go to **Setup → MokoDoliTraining**.
2. Click **Reset Training Data**.
3. All training rows are deleted in safe foreign-key order.

After a reset, trainees can re-seed and start the exercises from a clean slate.

---

## What data is included

| Category | Records |
|---|---|
| Staff users | 7 (rowids 50–55, 1) |
| Training users | 7 (rowids 60–66, login = password) |
| User groups | 4 (Admin, Project Manager, Contractor, Finance) |
| Third parties | 18 clients, prospects, and vendors |
| Products | 41 services, physical goods, and fees |
| Proposals | 12 |
| Invoices | 16 |
| Contracts | 4 |
| Projects | 9 |
| Project tasks | 90+ |
| Time entries | 17 |
| Payments | 8 |
| Expense reports | 2 |
| Tickets | 10 |
| CRM activities | 21 |
| Categories | 13 |

Training data uses `.example` domains and fictional company names throughout. No real client information is included.

---

## Default trainee credentials

| Login | Password | Group |
|---|---|---|
| trainee01 | trainee01 | Admin |
| trainee02 | trainee02 | Project Manager |
| trainee03 | trainee03 | Contractor |
| trainee04 | trainee04 | Finance |
| trainee05 | trainee05 | Contractor |
| trainee06 | trainee06 | Project Manager |
| trainer | trainer | Admin (facilitator) |

**Do not change trainee passwords** unless instructed — the exercise guide references these credentials by name.

---

## Manifest

The row manifest is stored in `sql/manifest.json`. It lists every table and rowid written by the seed script. The admin page displays this manifest in a searchable table so facilitators can confirm which records are present at any time.

---

## Support

Contact [hello@mokoconsulting.tech](mailto:hello@mokoconsulting.tech) or open an issue on the repository.

## Metadata

| Field | Value |
|---|---|
| Document Type | End-User Guide |
| Domain | Dolibarr Module |
| Applies To | MokoDoliTraining v1.x |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /src/README.md |
| Version | 01.00.00 |
| Status | Draft |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial draft | — |
