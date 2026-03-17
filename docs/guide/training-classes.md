<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Guide
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/guide/training-classes.md
VERSION:  01.00.00
BRIEF:    Training Classes — creating classes, enrolling trainees, managing rosters, and exporting.
-->

## Training Classes

The Training Classes feature lets trainers organise trainees into named cohorts (classes), track enrolment, and manage trainee accounts directly from the Dolibarr interface.

---

## What is a Training Class?

A **Training Class** (`llx_mokodolitraining_class`) is a named cohort with:

- A reference number (auto-assigned, format `CLS-YYYY-NNNN`)
- A label and optional description
- A start and end date
- An assigned trainer (Dolibarr user)
- An optional user group link (restricts exercise access to a specific training group)
- A maximum enrolment cap (`nb_max`)
- A status: **Draft → Active → Closed**

Each class can have multiple **enrolments** (`llx_mokodolitraining_class_user`), one per trainee. Enrolment status tracks: **Active → Suspended → Completed**.

---

## Lifecycle

```
Draft ──► Active ──► Closed
  │                     ▲
  └────────────────────►┘  (skip to closed if needed)
```

- **Draft**: Class is being configured. Trainees can be enrolled but the class is not yet running.
- **Active**: Training is in progress. Exercises are accessible.
- **Closed**: Training complete. Records are read-only. Rosters can still be exported.

---

## Creating a Class

1. Navigate to **MokoDoliTraining → Classes** (top menu → MokoDoliTraining → Classes, or directly via `admin/classes.php`).
2. Click **New Class**.
3. Fill in:
   - **Label** — e.g. "March 2026 Onboarding — Sales Team"
   - **Start / End Date** — training window
   - **Trainer** — select the facilitator's Dolibarr user account
   - **Group** — optional; links to a training group (70–73) to scope which exercises trainees see
   - **Max Enrolments** — leave blank for unlimited
4. Click **Save**. The class is created in **Draft** status.

---

## Enrolling Trainees

From the class record:

1. Click the **Trainees** tab.
2. Enter the Dolibarr user ID or name in the enrolment field.
3. Click **Enrol**.
4. The trainee appears in the roster with status **Active**.

### Creating a New Trainee Account

If the trainee does not yet have a Dolibarr account:

1. Click **Create Trainee Account** on the class page.
2. Enter: first name, last name, email, and desired login.
3. Click **Create**. A Dolibarr user account is created and automatically enrolled in this class.
4. The new account is assigned to the class's linked group (if set), or to the Basic group (70) by default.
5. The initial password matches the login. Trainees should change it at first login.

> All trainee account creation is logged in the audit log (`action: trainee_create`).

---

## Activating a Class

Once enrolment is complete:

1. Click **Activate** on the class card.
2. Status changes to **Active**.
3. Trainees can now log in and access the Exercises menu.

---

## Monitoring Progress

The **Trainees** tab shows enrolment status per trainee. Statuses:

| Status | Meaning |
|--------|---------|
| Active | Trainee is participating |
| Suspended | Temporarily paused (e.g. sick leave) |
| Completed | Trainee has finished the class |

Update individual enrolment status by clicking the status badge next to a trainee's name.

---

## Closing a Class

1. Once all trainees are marked **Completed**, click **Close** on the class card.
2. Status changes to **Closed**.
3. The class is read-only. No new enrolments can be added.
4. The class remains visible in the list for historical reference.

---

## Roster Export (CSV)

Export a full trainee roster from any class:

1. Open the class record.
2. Click **Export CSV**.
3. The download contains: login, full name, email, enrolment date, enrolment status, group.

The CSV filename is `roster_CLS-YYYY-NNNN.csv`. Use it for attendance records, compliance audits, or importing into an LMS.

---

## Permissions

| Right | Who Needs It |
|-------|-------------|
| `mokodolitraining/class/read` | View classes and rosters |
| `mokodolitraining/class/write` | Create, edit, activate, close classes |
| `mokodolitraining/class/delete` | Delete draft classes |

Trainers should have `write` permission. Read-only observers (e.g. HR) can use `read` only.

---

## Audit Log

All class actions are logged in the module audit log (`Setup → MokoDoliTraining → Logs`):

| Action | Trigger |
|--------|---------|
| `class_create` | New class saved |
| `class_update` | Class edited |
| `class_activate` | Status set to Active |
| `class_close` | Status set to Closed |
| `class_delete` | Draft class deleted |
| `enroll` | Trainee enrolled |
| `unenroll` | Trainee removed from class |
| `trainee_create` | New Dolibarr user created via class |

---

## Relationship to Exercises

A class links to a **training group** (optional). If set, the exercises available to enrolled trainees are filtered to that group. If no group is set, trainees see exercises based on their own group membership.

In **demo mode**, department groups (74–77) map to their equivalent training groups for exercise resolution — classes work identically in both modes.

---

## Metadata

| Field | Value |
|---|---|
| Document Type | Guide |
| Domain | MokoDoliTraining |
| Applies To | MokoDoliTraining v1.0.0+ |
| Audience | Training facilitators, HR administrators |
| Owner | Moko Consulting |
| Path | /docs/guide/training-classes.md |
| VERSION | 01.00.00 |
| Status | Active |
| Last Reviewed | 2026-03-16 |

## Revision History

| Date | Author | Change |
|---|---|---|
| 2026-03-16 | claude | Initial document |
