# Trainee Group Permissions

MokoDoliTraining seeds four **training-role groups** that instructors assign to a class when creating it.
Every trainee enrolled in the class is automatically added to the selected group and removed on unenrollment or class close.

| Group ID | Name | Intended audience |
|---|---|---|
| 70 | Basic | First-session orientation; read-only access across modules |
| 71 | Sales | Commercial workflows: proposals, orders, invoices, CRM |
| 72 | Marketing | Relationship management: contacts, categories, agenda |
| 73 | Design & Dev | Technical workflows: projects, tasks, tickets, interventions |

---

## Permissions matrix

> **Legend**
> - ✓ — granted
> - — — not granted
> - `SQL` — seeded automatically via `mokotraining.sql`
> - `UI` — must be configured in **Setup → Users & Groups → Group Rights** after first login as Admin (core module rights cannot be seeded with stable row IDs across Dolibarr installations)

### Core module rights (configure via UI)

| Module | Permission | Basic | Sales | Marketing | Dev/Design |
|---|---|:---:|:---:|:---:|:---:|
| Third Parties | Read | ✓ `UI` | ✓ `UI` | ✓ `UI` | ✓ `UI` |
| Third Parties | Write/create | — | ✓ `UI` | ✓ `UI` | — |
| Third Parties | Delete | — | — | — | — |
| Contacts | Read | ✓ `UI` | ✓ `UI` | ✓ `UI` | — |
| Contacts | Write/create | — | ✓ `UI` | ✓ `UI` | — |
| Products & Services | Read | ✓ `UI` | ✓ `UI` | ✓ `UI` | ✓ `UI` |
| Products & Services | Write/create | — | — | — | ✓ `UI` |
| Categories | Read | ✓ `UI` | — | ✓ `UI` | — |
| Categories | Write/create | — | — | ✓ `UI` | — |
| Proposals | Read | — | ✓ `UI` | ✓ `UI` | — |
| Proposals | Write/create | — | ✓ `UI` | — | — |
| Customer Orders | Read | — | ✓ `UI` | — | — |
| Customer Orders | Write/create | — | ✓ `UI` | — | — |
| Customer Invoices | Read | — | ✓ `UI` | — | — |
| Customer Invoices | Write/create | — | — | — | — |
| Supplier Orders | Read | — | — | — | — |
| Supplier Invoices | Read | — | — | — | — |
| Projects | Read | ✓ `UI` | — | — | ✓ `UI` |
| Projects | Write/create | — | — | — | ✓ `UI` |
| Tasks | Read | ✓ `UI` | — | — | ✓ `UI` |
| Tasks | Write/create | — | — | — | ✓ `UI` |
| Contracts | Read | — | — | — | ✓ `UI` |
| Tickets | Read | — | — | — | ✓ `UI` |
| Tickets | Write/create | — | — | — | ✓ `UI` |
| Interventions | Read | — | — | — | ✓ `UI` |
| Interventions | Write/create | — | — | — | ✓ `UI` |

### rights_def-registered rights (seeded via SQL)

These are seeded automatically in `mokotraining.sql` Section T2.5 and tracked in the manifest.
The fk_id values reference `llx_rights_def` rows registered by their respective Dolibarr modules.

| Module | Permission | fk_id | Basic | Sales | Marketing | Dev/Design |
|---|---|---|:---:|:---:|:---:|:---:|
| Agenda | Read own events | 2401 | ✓ `SQL` | ✓ `SQL` | ✓ `SQL` | ✓ `SQL` |
| Agenda | Read all events | 2402 | — | ✓ `SQL` | ✓ `SQL` | ✓ `SQL` |
| Agenda | Write / create events | 2403 | — | ✓ `SQL` | — | ✓ `SQL` |
| Expense Reports | Read own reports | 771 | — | ✓ `SQL` | ✓ `SQL` | ✓ `SQL` |
| Expense Reports | Submit own reports | 772 | — | ✓ `SQL` | ✓ `SQL` | ✓ `SQL` |

---

## Configuration guide (UI steps)

After running **Install Training Records** and logging in as `jmiller` (Admin):

1. Go to **Setup → Users & Groups**
2. Click the **Groups** tab
3. For each group (Basic / Sales / Marketing / Design & Dev), click **Edit Rights**
4. Enable the core module rights listed above in the "configure via UI" rows
5. Save

These settings persist across **Reset to Snapshot** and **Reset to Rollback** operations because those operations only delete rows in tables tracked by the manifest — `llx_usergroup_rights` rows inserted by Dolibarr at runtime (not in the manifest) are preserved.

> **Note for instructors**: The group a trainee belongs to determines what they can see and do in Dolibarr. Assign the group that matches the training session's focus area when creating the class.
