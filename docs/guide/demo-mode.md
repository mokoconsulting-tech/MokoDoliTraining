<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Guide
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/guide/demo-mode.md
VERSION:  01.00.00
BRIEF:    Explains Training mode vs Demo mode: personas, groups, switching, and exercise compatibility.
-->

## Demo Mode

MokoDoliTraining supports two seed modes that determine which user accounts and groups are created when the module is installed.

| Mode | Use Case | Users Created | Groups Created |
|------|----------|--------------|---------------|
| **Training** (default) | Dolibarr training sessions with named trainee roles | trainee01–06, trainer | Basic, Sales, Marketing, Design & Dev (IDs 70–73) |
| **Demo** | Product demonstrations, prospect evaluations | Business personas (alice.martin, etc.) | Management, Sales Dept, Marketing Dept, Engineering (IDs 74–77) |

---

## Selecting a Mode at Install

The mode selector appears inline with the Install button on the module setup page (`Setup → MokoDoliTraining → Setup`).

1. Navigate to **Setup → MokoDoliTraining → Setup**.
2. Click the **Mode** dropdown next to the **Install Records** button.
3. Select **Training** or **Demo**.
4. Click **Install Records**.

The selected mode is stored as the `MOKODOLITRAINING_SEED_MODE` constant. It is shown as a badge in the status table on the setup page.

> **Note:** Switching modes requires a full reset (rollback to pre-install snapshot) followed by a new install in the desired mode. This removes all seeded data including orders, proposals, and invoices.

---

## Training Mode

**Intended audience:** IT trainers, Dolibarr consultants running onboarding sessions.

### Accounts

| Login | Role | Group (ID) | Default Password |
|-------|------|-----------|-----------------|
| trainee01 | Basic trainee | Basic (70) | `trainee01` |
| trainee02 | Sales trainee | Sales (71) | `trainee02` |
| trainee03 | Marketing trainee | Marketing (72) | `trainee03` |
| trainee04 | Dev/Design trainee | Design & Dev (73) | `trainee04` |
| trainee05 | Basic trainee (pair) | Basic (70) | `trainee05` |
| trainee06 | Sales trainee (pair) | Sales (71) | `trainee06` |
| trainer | Facilitator | Admin | `trainer` |

### Groups

| Group Name | ID | Purpose |
|-----------|-----|---------|
| Basic | 70 | Read-only navigation and orientation rights |
| Sales | 71 | Commercial, billing, proposals, orders |
| Marketing | 72 | CRM, contacts, categories, exports |
| Design & Dev | 73 | Projects, tasks, tickets, contracts, interventions |

---

## Demo Mode

**Intended audience:** Sales demonstrations, prospect evaluations, trade show kiosks.

### Accounts

| Login | Display Name | Department | Group (ID) | Password |
|-------|-------------|-----------|-----------|---------|
| alice.martin | Alice Martin | Management | Management (74) | `Demo1234!` |
| bob.chen | Bob Chen | Sales | Sales Dept (75) | `Demo1234!` |
| claire.dupont | Claire Dupont | Sales | Sales Dept (75) | `Demo1234!` |
| david.miller | David Miller | Marketing | Marketing Dept (76) | `Demo1234!` |
| emma.jones | Emma Jones | Marketing | Marketing Dept (76) | `Demo1234!` |
| frank.nguyen | Frank Nguyen | Engineering | Engineering (77) | `Demo1234!` |
| grace.kim | Grace Kim | Engineering | Engineering (77) | `Demo1234!` |

### Department Groups

| Group Name | ID | Maps to Training Group | Rights |
|-----------|-----|----------------------|--------|
| Management | 74 | Basic (70) | Broad read access, dashboard |
| Sales Dept | 75 | Sales (71) | Commercial, billing, proposals |
| Marketing Dept | 76 | Marketing (72) | CRM, contacts, categories |
| Engineering | 77 | Design & Dev (73) | Projects, tasks, tickets |

### Exercise Compatibility

Demo mode is fully compatible with the guided exercise catalog. The `MokoDoliTrainingExercise` class resolves department groups (74–77) to their equivalent training groups (70–73) via the `DEMO_GROUPS['maps_to']` mapping. Exercises appear and behave identically — only the account names and group labels differ.

---

## How Mode Affects the Setup Page

- The **Status** table shows `SeedModeDemo` or `SeedModeTraining` badge next to the seeded state.
- The **Mode** dropdown is only active before install. Once data is seeded it is read-only.
- `MOKODOLITRAINING_SEED_MODE` is cleared automatically on uninstall/rollback.

---

## Resetting Between Modes

1. On the setup page, click **Rollback** to restore the pre-install snapshot.
   This removes all seeded users, groups, clients, orders, and invoices.
2. Confirm the rollback in the confirmation dialog.
3. Once rolled back, the Install button becomes active again.
4. Select the new mode in the dropdown and click **Install Records**.

After the reset, `MOKODOLITRAINING_SEED_MODE` is cleared. The next install sets it to the chosen mode.

---

## Manifest Sync After Snapshot Restore

Demo mode IDs (users 70–76, groups 74–77, rights 110–126) are tracked in the manifest at seed time. After a `reset_snapshot` action (restore to the seeded snapshot rather than pre-install), `resyncManifest()` automatically re-tracks these IDs when the mode is `demo`, ensuring subsequent resets clean them up correctly. No manual action is required.

---

## Metadata

| Field | Value |
|---|---|
| Document Type | Guide |
| Domain | MokoDoliTraining |
| Applies To | MokoDoliTraining v1.0.0+ |
| Audience | Module administrators, trainers, sales engineers |
| Owner | Moko Consulting |
| Path | /docs/guide/demo-mode.md |
| VERSION | 01.00.00 |
| Status | Active |
| Last Reviewed | 2026-03-16 |

## Revision History

| Date | Author | Change |
|---|---|---|
| 2026-03-16 | claude | Initial document |
