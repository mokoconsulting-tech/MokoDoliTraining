<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Guide
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/guide/training-exercises.md
VERSION:  development
BRIEF:    Guided tour exercise catalog for MokoDoliTraining. Divided by training group.
          Each section maps to the MokoDoliTrainingExercise::catalog() exercise IDs.
-->

## MokoDoliTraining — Exercise Catalog

This document describes all guided-tour exercises available in the MokoDoliTraining module. Exercises are delivered as step-by-step walkthroughs powered by the tour engine (`mokodolitraining-tour.js`). Each step highlights the relevant Dolibarr UI element and provides context for both trainees and trainers.

Exercises are grouped by training role. Trainees see only the exercises for their assigned group. Trainers see all groups and can launch any exercise in trainer mode (which displays additional facilitator notes at each step).

All demo data is fictional. No real client information is present.

---

## How Tours Work

1. Navigate to the **Exercises** top menu item (visible to all users with `read` permission).
2. Click **Start Exercise** next to the exercise you want to run.
3. A floating card appears in the bottom-right corner of every Dolibarr page.
4. Follow the instructions. If a step requires a different page, the tour automatically navigates you there.
5. Click **Next** to advance, **Back** to return, or **×** to stop.
6. A green toast notification confirms when you complete the final step.

Trainers can click **Demo as Trainer** to start the same exercise with facilitator notes visible at each step.

---

## Training Accounts

Seven dedicated training accounts are pre-loaded. Each is assigned to a group reflecting a real team role.

**Default password for each login is identical to the username** (e.g. `trainee01` / `trainee01`). Do not change passwords unless instructed.

| Login | Group | Access Level | Primary Use |
|-------|-------|-------------|-------------|
| trainee01 | Basic | Read-only orientation | Group 70 exercises |
| trainee02 | Sales | Commercial workflows | Group 71 exercises |
| trainee03 | Marketing | Relationship and contact mgmt | Group 72 exercises |
| trainee04 | Design & Dev | Projects, tickets, technical | Group 73 exercises |
| trainee05 | Basic | Duplicate for paired exercises | Group 70 exercises |
| trainee06 | Sales | Duplicate for paired exercises | Group 71 exercises |
| trainer | Admin | Full access | Facilitator — all groups |

---

## Demo Client Reference

| ID | Client | Industry | Status | Used In |
|----|--------|----------|--------|---------|
| 50 | Pinnacle Goods Co. | E-commerce | Active | basic_02, sales_03, devdesign_02 |
| 51 | Redwood Legal Group | Legal | Prospect | sales_01, sales_02, sales_08 |
| 52 | Brightpath Nonprofit | Nonprofit | Active retainer | sales_08 |
| 60 | Bellwether Bakehouse | Food/hospitality | Active | sales_03, sales_06 |
| 61 | Maple Ridge Property | Real estate | Closed | marketing_04 |
| 62 | Thornton Hardware | B2B retail | Overdue account | sales_05, sales_09, devdesign_04 |
| 90 | Halcyon Health Partners | Healthcare | Active retainer | marketing_01, marketing_02, devdesign_06 |
| 91 | Vantage Point Logistics | Logistics | Active | marketing_03, devdesign_01–03, devdesign_05, devdesign_07, devdesign_09 |
| 92 | Cascade Analytics | Technology | Prospect | marketing_05 |
| 93 | Thornton Hardware | B2B | Active | sales_05 |
| 94 | BrightSpark Design Studio | Agency | Supplier | sales_07, devdesign_08, devdesign_09 |
| 95 | Vantage Point Logistics | Logistics | Active | marketing_03 |

---

## Group 70 — Basic

**Target trainee:** Any new Dolibarr user. Read-only orientation covering navigation, core record types, documents, and reporting.

**Prerequisites:** None. Start here if you have never used Dolibarr before.

---

### basic_01 — Finding Your Way Around

**Estimate:** 8 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/index.php` | Top menu bar | Module navigation icons |
| 2 | `/index.php` | Dashboard widgets | Dashboard customisation and pending actions |
| 3 | `/index.php` | Search bar | Global search across all modules |
| 4 | `/societe/list.php` | Third parties table | Master client/supplier list |

**Trainer note:** Ask trainees to identify 3 modules relevant to their role. Good icebreaker activity.

---

### basic_02 — Exploring a Client Record

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/societe/list.php` | Table | Searching the third party list |
| 2 | `/societe/card.php?socid=50` | Record card | Client card fields: address, status, notes |
| 3 | `/societe/card.php?socid=50` | Tab bar | Navigating related records via tabs |
| 4 | `/societe/card.php?socid=50` | Summary panel | 360-degree client view |

**Demo client:** Pinnacle Goods Co. (rowid 50)

---

### basic_03 — Browsing the Product Catalogue

**Estimate:** 8 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/product/list.php` | Table | Product vs service distinction |
| 2 | `/product/card.php?id=50` | Product sheet | Reference, price, VAT, stock |
| 3 | `/product/card.php?id=50` | Tab bar | Price lists, suppliers, statistics |
| 4 | `/product/list.php` | Filter row | Filtering the catalogue |

---

### basic_04 — The CRM Agenda

**Estimate:** 7 min | **Steps:** 3

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/comm/action/list.php` | Table | CRM event types and purpose |
| 2 | `/comm/action/list.php` | Filter row | Filtering by user, client, type, status |
| 3 | `/comm/action/card.php` | Record card | Event fields: type, client, contact, done |

---

### basic_05 — Finding and Downloading Documents

**Estimate:** 7 min | **Steps:** 3

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/compta/facture/list.php` | Table | Generated PDFs on validated records |
| 2 | `/compta/facture/card.php` | Document panel | Downloading a PDF |
| 3 | `/ecm/index.php` | Document library | Central ECM file store |

---

### basic_06 — Reading Reports and Statistics

**Estimate:** 8 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/compta/stats/index.php` | Statistics chart | Revenue trends over time |
| 2 | `/compta/stats/index.php` | Year/month filter | Filtering statistics by period |
| 3 | `/comm/propal/stats/index.php` | Proposal stats | Proposal win rate and conversion funnel |
| 4 | `/projet/stats/index.php` | Project stats | Hours logged and per-user summaries |

---

### basic_07 — Checking Stock Levels

**Estimate:** 8 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/product/stock/list.php` | Table | Stock levels by product |
| 2 | `/product/stock/entrepot.php` | Warehouse list | Warehouse structure |
| 3 | `/product/stock/mouvement.php` | Movement history | Audit trail of stock changes |
| 4 | `/product/card.php?id=50` | Stock tab | Per-warehouse breakdown on a product |

---

### basic_08 — User Accounts and Group Overview

**Estimate:** 8 min | **Steps:** 3

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/user/list.php` | User table | User vs contact distinction |
| 2 | `/user/card.php` | Permission / Group tabs | Rights granted directly vs via group |
| 3 | `/user/group/list.php` | Group list | Role-based group structure |

---

## Group 71 — Sales

**Target trainee:** Sales, account management, billing staff.

**Prerequisites:** Complete Group 70 (Basic) first, or have prior experience with the Third Parties and Products modules.

---

### sales_01 — Creating Your First Proposal

**Estimate:** 15 min | **Steps:** 5

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/comm/propal/list.php` | New button | Purpose of proposals in the sales cycle |
| 2 | `/comm/propal/card.php?action=create` | Third party field | Linking a proposal to a client |
| 3 | `/comm/propal/card.php?action=create` | Date fields | Proposal date and validity end date |
| 4 | `/comm/propal/card.php?action=create` | Line items panel | Adding catalogue items and free-text lines |
| 5 | `/comm/propal/card.php?action=create` | Note field | Public vs private notes on proposals |

**Demo client:** Redwood Legal Group

---

### sales_02 — Validating and Sending a Proposal

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/comm/propal/list.php` | Table | Finding draft proposals |
| 2 | `/comm/propal/card.php` | Validate button | Locking a proposal (permanent ref assigned) |
| 3 | `/comm/propal/card.php` | PDF preview | What the client receives |
| 4 | `/comm/propal/card.php` | Send by email | Email action and CRM auto-logging |

---

### sales_03 — Converting a Proposal to an Order

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/comm/propal/list.php` | Table | Finding the accepted proposal |
| 2 | `/comm/propal/card.php` | Accept button | Recording client agreement |
| 3 | `/comm/propal/card.php` | Create order button | Generating order from proposal |
| 4 | `/commande/card.php` | Order card | Order reference vs proposal reference |

**Demo client:** Bellwether Bakehouse

---

### sales_04 — Raising a Customer Invoice

**Estimate:** 12 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/commande/card.php` | Create invoice button | Invoice-from-order workflow |
| 2 | `/compta/facture/card.php?action=create` | Invoice date field | Invoice date vs due date |
| 3 | `/compta/facture/card.php?action=create` | Validate button | Irreversibility of invoice validation |
| 4 | `/compta/facture/card.php` | Invoice card | Accounts receivable status |

---

### sales_05 — Recording a Customer Payment

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/compta/facture/list.php` | Table | Unpaid/overdue invoice list |
| 2 | `/compta/facture/card.php` | Record payment button | Payment fields: amount, method, bank account |
| 3 | `/compta/paiement/card.php` | Payment record | One payment clearing multiple invoices |
| 4 | `/societe/card.php?socid=93` | Client card | Verified zero balance on client record |

**Demo client:** Thornton Hardware & Supply

---

### sales_06 — Issuing a Credit Note

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/compta/facture/list.php` | Table | Finding the invoice to credit |
| 2 | `/compta/facture/card.php` | Create credit note button | Credit notes as the correction mechanism |
| 3 | `/compta/facture/card.php` | Note field | Adding a reason and validating |
| 4 | `/compta/facture/card.php` | Balance display | Net-zero result on client account |

**Demo client:** Bellwether Bakehouse (cancelled e-commerce project)

---

### sales_07 — Processing a Supplier Invoice

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/fourn/facture/list.php` | Table | Accounts payable overview |
| 2 | `/fourn/facture/card.php?action=create` | Supplier field | Recording supplier's invoice reference |
| 3 | `/fourn/facture/card.php?action=create` | Line items | Input VAT and its recovery |
| 4 | `/fourn/facture/card.php` | Validated invoice | Accounts payable status |

**Demo supplier:** BrightSpark Design Studio

---

### sales_08 — Setting Up a Recurring Invoice

**Estimate:** 12 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/compta/facture/invoicetemplate_list.php` | Template list | Purpose of recurring templates |
| 2 | `/compta/facture/invoicetemplate_list.php` | Existing template | Frequency settings on a template |
| 3 | `/compta/facture/invoicetemplate_list.php` | New template form | Creating a template for Redwood Legal Group |
| 4 | `/compta/facture/invoicetemplate_list.php` | Template saved | Auto-validate vs review-first behaviour |

---

### sales_09 — Creating a Delivery Order

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/commande/card.php` | Create shipping button | Delivery order from customer order |
| 2 | `/expedition/card.php` | Date and quantity fields | Partial delivery support |
| 3 | `/expedition/card.php` | Validate button | Stock decrease at shipping |
| 4 | `/commande/card.php` | Linked documents | Complete chain: proposal → order → delivery → invoice |

---

### sales_10 — Bank Account Reconciliation

**Estimate:** 12 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/compta/bank/list.php` | Bank account list | Book accounts vs actual bank |
| 2 | `/compta/bank/bankentries_list.php` | Transaction ledger | Reconciliation column (R flag) |
| 3 | `/compta/bank/bankentries_list.php` | Reconcile checkbox | Marking entries as confirmed |
| 4 | `/compta/bank/bankentries_list.php` | Running balance | Closing balance check |

---

## Group 72 — Marketing

**Target trainee:** Marketing, CRM, business development staff.

**Prerequisites:** Complete Group 70 (Basic) first.

---

### marketing_01 — Adding a New Contact

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/contact/list.php` | New button | Contacts vs companies |
| 2 | `/contact/card.php?action=create` | Third party field | Linking contact to a company |
| 3 | `/contact/card.php?action=create` | Name/job fields | Complete contact records |
| 4 | `/contact/card.php` | Saved contact | Contact visible on client card |

**Demo client:** Halcyon Health Partners — adding Rohan Patel, Practice Coordinator

---

### marketing_02 — Logging a CRM Event

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/comm/action/list.php` | New event button | CRM logging as institutional memory |
| 2 | `/comm/action/card.php?action=create` | Type selector | Event types and custom types |
| 3 | `/comm/action/card.php?action=create` | Client/contact fields | Linking event to specific contact |
| 4 | `/comm/action/card.php` | Calendar view | Agenda view and manager review workflow |

---

### marketing_03 — Tagging Records with Categories

**Estimate:** 8 min | **Steps:** 3

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/categories/list.php` | Category tree | Category types by object |
| 2 | `/societe/card.php?socid=95` | Category field | Applying tags to a client record |
| 3 | `/societe/list.php` | Category filter | Filtering by category for segmentation |

**Demo client:** Vantage Point Logistics

---

### marketing_04 — Updating Client Information

**Estimate:** 8 min | **Steps:** 3

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/societe/list.php` | Table | Finding records to update |
| 2 | `/societe/card.php?socid=92` | Edit button | Editing address and contact data |
| 3 | `/societe/card.php?socid=92` | History tab | Modification log and compliance |

**Demo client:** Maple Ridge Property Group

---

### marketing_05 — Managing a Prospect Pipeline

**Estimate:** 12 min | **Steps:** 5

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/societe/list.php` | Prospect filter | Prospect vs client status field |
| 2 | `/societe/card.php` | Client card | 360° view of a prospect in discussion |
| 3 | `/societe/card.php` | Edit/status field | Converting prospect to active client |
| 4 | `/comm/propal/list.php` | Sent proposals | Pipeline via proposal validity dates |
| 5 | `/comm/propal/list.php` | Close action | Logging loss reason for reporting |

**Demo client:** Cascade Analytics

---

### marketing_06 — Exporting Data and Running Reports

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/societe/list.php` | Filter row | Pre-filtering before export |
| 2 | `/societe/list.php` | Export button | CSV export and GDPR obligations |
| 3 | `/compta/stats/index.php` | Turnover chart | Monthly revenue patterns |
| 4 | `/compta/stats/facture.php` | Invoice breakdown | Paid vs unpaid — collections health |

---

### marketing_07 — Sending a Mass Email Campaign

**Estimate:** 12 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/comm/mailing/list.php` | Mailing list | Native mailing vs external tools |
| 2 | `/comm/mailing/card.php?action=create` | Title/subject fields | Mailing shell creation |
| 3 | `/comm/mailing/card.php` | Recipients tab | Segment filtering and opt-out checks |
| 4 | `/comm/mailing/card.php` | Content tab | Substitution variables and send |

---

### marketing_08 — Tracking Third Party Memberships

**Estimate:** 10 min | **Steps:** 3

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/adherents/list.php` | Member list | Membership module purpose |
| 2 | `/adherents/card.php?action=create` | Type selector | Creating a membership record |
| 3 | `/adherents/list.php` | Expiry filter | Renewal tracking and retention |

---

## Group 73 — Design & Dev

**Target trainee:** Project managers, developers, designers, technical staff.

**Prerequisites:** Complete Group 70 (Basic) first, plus Groups 71 and 72 are recommended for full context.

---

### devdesign_01 — Setting Up a Project

**Estimate:** 12 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/projet/list.php` | New button | Project as organising unit |
| 2 | `/projet/card.php?action=create` | Reference field | Project reference conventions |
| 3 | `/projet/card.php?action=create` | Client + billing fields | Billable vs non-billable projects |
| 4 | `/projet/card.php` | Tab bar | Tasks, Time Spent, Finance overview |

**Demo client:** Vantage Point Logistics — Supply Chain Portal Phase 2

---

### devdesign_02 — Breaking Work into Tasks

**Estimate:** 12 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/projet/tasks/index.php` | New task button | Task as billable work unit |
| 2 | `/projet/tasks/task.php` | Task detail fields | Label, description, planned duration |
| 3 | `/projet/tasks/index.php` | Task table | Status column for progress tracking |
| 4 | `/projet/tasks/task.php` | Dependencies | Predecessor/successor task links |

---

### devdesign_03 — Logging Time on a Task

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/projet/tasks/index.php` | Task table | Selecting a task to log time on |
| 2 | `/projet/tasks/task.php` | Time tab | Per-task time log structure |
| 3 | `/projet/tasks/task.php` | Time entry form | Date, hours, billable note fields |
| 4 | `/projet/card.php` | Time Spent section | Project-level hour accumulation |

---

### devdesign_04 — Handling a Support Ticket

**Estimate:** 12 min | **Steps:** 5

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/ticket/list.php` | New ticket button | Tickets vs email-based support |
| 2 | `/ticket/card.php?action=create` | Type selector | Ticket types and routing |
| 3 | `/ticket/card.php?action=create` | Client/priority fields | Linking ticket to client and setting urgency |
| 4 | `/ticket/card.php` | Assign/status buttons | Public vs private messages |
| 5 | `/ticket/card.php` | Resolve action | Ticket as knowledge base entry |

**Demo client:** Thornton Hardware & Supply — login 500 error

---

### devdesign_05 — Submitting an Expense Report

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/expensereport/list.php` | New button | Expense report purpose |
| 2 | `/expensereport/card.php?action=create` | Project field | Linking expenses to a project |
| 3 | `/expensereport/card.php?action=create` | Add line button | Expense types and accounting codes |
| 4 | `/expensereport/card.php` | Submit button | Approval workflow |

---

### devdesign_06 — Creating a Service Contract

**Estimate:** 12 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/contrat/list.php` | New button | Contracts vs one-time proposals |
| 2 | `/contrat/card.php?action=create` | Client/date fields | Contract reference conventions |
| 3 | `/contrat/card.php?action=create` | Service lines | Independent line activation |
| 4 | `/contrat/card.php` | Activate button | Active contract on client card |

**Demo client:** Halcyon Health Partners — MAINT-HAL-2026

---

### devdesign_07 — Logging a Field Intervention

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/fichinter/list.php` | New button | Field intervention purpose |
| 2 | `/fichinter/card.php?action=create` | Client/description fields | Linking to client and contract |
| 3 | `/fichinter/card.php?action=create` | Time lines panel | Duration-based service logging |
| 4 | `/fichinter/card.php` | Validate button | PDF proof of delivery |

**Demo client:** Vantage Point Logistics — server rack installation

---

### devdesign_08 — Raising a Purchase Order

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/fourn/commande/list.php` | New button | POs for spend control |
| 2 | `/fourn/commande/card.php?action=create` | Supplier/project fields | PO linked to project |
| 3 | `/fourn/commande/card.php?action=create` | Line items panel | Catalogue vs free-text PO lines |
| 4 | `/fourn/commande/card.php` | Approve/send buttons | Three-way match: PO → reception → invoice |

**Demo supplier:** BrightSpark Design Studio

---

### devdesign_09 — Receiving Goods from a Supplier

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/reception/list.php` | New button | Goods reception purpose |
| 2 | `/fourn/commande/card.php` | Create reception button | Reception created from PO |
| 3 | `/reception/card.php` | Warehouse selector | Partial reception handling |
| 4 | `/product/stock/list.php` | Stock table | Stock increase confirmed |

---

### devdesign_10 — Managing Custom Extra Fields

**Estimate:** 10 min | **Steps:** 4

| # | Page | Spotlight | What You Learn |
|---|------|-----------|---------------|
| 1 | `/admin/extrafields.php?attrname=societe` | New field button | Extra fields vs core fields |
| 2 | `/admin/extrafields.php?attrname=societe` | Type selector | Choosing the right field type |
| 3 | `/societe/card.php?socid=50` | Extra fields section | Custom field on a live record |
| 4 | `/societe/list.php` | Column configuration | Adding custom field to list view |

---

## Classic Written Exercises

The following step-by-step written exercises supplement the guided tours. They cover the same workflows with more detailed instructions and discussion prompts. Use them for facilitated classroom sessions or self-study between walkthroughs.

### Category 1 — New Client Intake

**Client:** Redwood Legal Group (CU-M009)

#### Exercise 1.1 — Log a First Contact

1. Open Third Parties and locate Redwood Legal Group.
2. Navigate to the Agenda tab on their record.
3. Create a CRM event: Type = Email, Direction = Inbound, Date = today. Note: "Initial inquiry — law firm website. Wants 5-page site plus contact form and attorney bios section."
4. Create a follow-up task: due in 3 business days, title "Send intro questionnaire — Redwood Legal."

Expected result: CRM event on Redwood Legal timeline; follow-up task in dashboard task list.

#### Exercise 1.2 — Create and Send a Proposal

1. On the Redwood Legal record, navigate to Proposals and create a new proposal.
2. Add line items: WEB-DISC, WEB-DSGN, WEB-BILD, WEB-LNCH, VENDOR-HOST-12MO, VENDOR-DOMAIN.
3. Validate and send by email. Note the email intercept in the training environment.

Expected result: proposal status is Validated; visible on Redwood Legal record under Proposals tab.

#### Exercise 1.3 — Generate Deposit Invoice

1. Set proposal status to Signed.
2. Create invoice from proposal. Edit to reflect 50% deposit. Label: "50% Deposit — Law Firm Website."
3. Set payment terms to Due Upon Receipt. Validate.
4. Log CRM note: "Proposal signed. Deposit invoice sent."

Expected result: invoice in Validated state, linked to proposal in Linked Documents panel.

---

### Category 2 — Proposal Revision

**Client:** Bellwether Bakehouse (CU-M010)

#### Exercise 2.1 — Review a Cancelled Proposal

1. Open Bellwether Bakehouse → Proposals tab.
2. Locate the cancelled proposal. Note original line items and total.
3. Review the CRM activity log for context on why it was cancelled.

Discussion: cancelled vs declined vs refused — what are the reporting differences?

#### Exercise 2.2 — Create a Revised Proposal

1. Create a new proposal for Bellwether (descoped: no e-commerce).
2. Add: WEB-DISC, WEB-DSGN, WEB-BILD, WEB-LNCH, DESIGN-LOGO, VENDOR-HOST-12MO, VENDOR-DOMAIN.
3. Private note: "Revised scope. Client confirmed budget ceiling. Adjusted from [original ref]."
4. Validate and mark Signed. Log CRM note.

---

### Category 3 — Project Execution and Time Logging

**Client:** Pinnacle Goods Co. — PROJ-0001

#### Exercise 3.1 — Assign Tasks

1. Open PROJ-0001 → Phase 3 (Build).
2. Assign Joomla Install to mwebb. Assign VirtueMart Setup to mwebb and bstewart.
3. Set due dates matching planned end dates.

#### Exercise 3.2 — Log Time

1. Navigate to Phase 2 — Design → "Client UAT and Feedback" task.
2. Log 2 hours. Description: "Client review call — homepage and product page mockups. Approved with minor text changes." Mark billable.
3. Also log 4.5 hours for mwebb on VirtueMart Setup. Mark billable.

#### Exercise 3.3 — Invoice from Logged Time

1. From PROJ-0001, Generate Invoice from Time.
2. Review line items. Add header note: "Supplemental billing — March 2026 build hours."
3. Validate invoice.

Expected result: invoice linked to PROJ-0001; time entries marked as invoiced.

---

### Category 4 — Invoice Lifecycle

#### Exercise 4.1 — Partial Payment

**Client:** Pinnacle Goods Co. — INV-2026-0001

1. Open INV-2026-0001.
2. Record payment: $500.00, Stripe, ref DEMO-STRIPE-001.
3. Invoice remains partially paid. Verify remaining balance = $1,506.50.

#### Exercise 4.2 — Late Fee

**Client:** Thornton Hardware (CU-M012)

1. Open overdue invoice. Note days past due.
2. Create new invoice: FEE-LATE ($35.00). Note: "Late payment fee per section 4.2 of service agreement."
3. Log CRM event: collections call, left voicemail.

#### Exercise 4.3 — Review a Paid Invoice

**Client:** Brightpath Nonprofit — INV-2026-0002

1. Open INV-2026-0002.
2. Review payment record: method (ACH), reference, bank account.
3. Find the corresponding bank line entry on DEMO-CHK.

Discussion: what action changes invoice status from Validated to Paid?

---

### Category 5 — Overdue Accounts

#### Exercise 5.1 — Aging Report

1. Navigate to Invoices → Statistics or Overdue Invoices list.
2. Identify all invoices past due. Note: client, ref, due date, days overdue, amount.
3. Who has the oldest overdue balance? What is the total overdue amount?

#### Exercise 5.2 — Collections Activity Log

**Client:** Thornton Hardware (CU-M012)

1. Open CRM activity log. Review existing follow-up notes.
2. Add new event: Email, "Sent formal collections notice. Requested full payment within 7 days."
3. Flag Thornton's record as on hold.

#### Exercise 5.3 — Partial Payment on Overdue Invoice

1. Open Thornton's overdue invoice.
2. Record $400.00 partial payment. Note remaining balance.
3. Log CRM note: "Partial payment received $400. Remainder agreed by [date + 14 days]."

---

### Category 6 — Retainer and Recurring Billing

#### Exercise 6.1 — Review Retainer History

**Client:** Brightpath Nonprofit (CU-M003)

1. Open Brightpath → Contracts → CONT-2026-0001.
2. Review service lines: monthly fee and grant writing component.
3. Navigate to Invoices tab. Which months are paid? Which is outstanding?

#### Exercise 6.2 — Process Outstanding Retainer

1. Open March retainer invoice for Brightpath.
2. Record $399.00, ACH, ref DEMO-ACH-BRT-003.

#### Exercise 6.3 — Review Recurring Template

**Client:** Clearwater Media Group (CU-M006)

1. Navigate to Invoices → Recurring Invoices.
2. Open Clearwater template. Review frequency, next generation date, line items.
3. Discuss: auto-send vs manual validation — when is each appropriate?

#### Exercise 6.4 — Create a Recurring Template

**Client:** Halcyon Health Partners (CU-M013)

1. New Recurring Invoice. Client = Halcyon Health Partners.
2. Line: CONS-RETAINER, qty 1.
3. Monthly, generate on 1st, start next month. Payment terms: Due Upon Receipt. Save.

---

### Category 7 — Change Orders

**Client:** Vantage Point Logistics (CU-M014)

#### Exercise 7.1 — Identify Scope Change

1. Open Vantage Point → active project.
2. Find CRM note documenting the scope change request.
3. Review original signed proposal to confirm portal was not in scope.

Discussion: new proposal vs amendment vs change order invoice — which does your organisation use?

#### Exercise 7.2 — Create Change Order Proposal

1. New proposal for Vantage Point.
2. Lines: WEB-BILD (portal component), FEE-RUSH if applicable.
3. Private note referencing original proposal. Validate and mark Signed.
4. Create 50% deposit invoice.

#### Exercise 7.3 — Apply Rush Surcharge

1. Open change order proposal or create standalone invoice.
2. Add FEE-RUSH ($150.00). Note: "Priority delivery surcharge — 10-day window per client request."
3. Log CRM note confirming client acknowledgment.

---

### Category 8 — Project Close

#### Exercise 8.1 — Review Completed Project

**Client:** Maple Ridge Property Group (CU-M011)

1. Open Maple Ridge → each tab: Proposals, Invoices, Contracts, Projects, CRM Activity.
2. Trace document chain. Are both invoices paid? Is project Closed?

#### Exercise 8.2 — Generate Balance Invoice

**Client:** Apex Digital Agency (CU-M004)

1. Open Apex invoice history. Note deposit amount paid.
2. New invoice: "Balance Due — Corporate Site + Brand Identity." Amount = 50% of original proposal.
3. Reference original proposal. Due in 7 days. Validate and mark sent.

#### Exercise 8.3 — Close a Project

**Client:** Maple Ridge Property Group (CU-M011)

1. Confirm all tasks are closed, all invoices are paid, project status is Closed.
2. Discuss: what would happen if you tried to log time against a closed project?

---

### Category 9 — Vendor and Procurement

#### Exercise 9.1 — Review a Supplier Invoice

**Vendor:** DemoHost Pro (VE-M001)

1. Purchases → Supplier Invoices → open DemoHost Pro invoice.
2. Note amount, due date, linked products.
3. Trace cost to client charge (VENDOR-HOST-12MO on Pinnacle Goods invoices).

#### Exercise 9.2 — Process Supplier Payment

1. Open DemoHost Pro invoice. Record payment: full amount, bank transfer, ref DEMO-PAY-DHOST-001.

#### Exercise 9.3 — Create a Purchase Order

**Vendor:** PrintBridge Supply Co. (VE-M002)

1. Purchases → Purchase Orders → New. Supplier = PrintBridge.
2. Add: PRINT-BIZCARD-500 qty 2, SWAG-SHIRT qty 24, SWAG-BOTTLE qty 12.
3. Add note referencing PROJ-0001. Validate.

---

### Category 10 — Expense Reporting

#### Exercise 10.1 — Submit an Expense Report

Log in as **trainee03** (Contractor group).

1. Expenses → My Expenses → New Expense Report.
2. Add two lines: design software renewal ($54.99, Software/Subscriptions); client meeting mileage (22 miles, Travel).
3. Submit for approval.

#### Exercise 10.2 — Approve an Expense Report

Log in as **trainee01** or **trainee04** (Admin or Finance group).

1. Expenses → Expense Reports to Approve.
2. Open trainee03's pending report. Review lines. Approve.
3. Record reimbursement payment (bank transfer).

---

### Category 11 — Prospect Nurture

**Client:** Harborview Wellness Clinic (CU-M007)

#### Exercise 11.1 — Work a Prospect Record

1. Open Harborview. Review existing CRM activity.
2. Log new event: Type = Phone Call, "30-min discovery call. Patient portal + SEO. Requested proposal by end of month."
3. Create follow-up task: "Prepare Harborview proposal," due 5 business days.

#### Exercise 11.2 — Convert Prospect to Client

1. Discuss: which field on Third Party determines Prospect vs Client? (Client field: 0 = none, 1 = prospect, 2 = client.)
2. Once Harborview signs a proposal, update Client field from 1 → 2.

---

### Category 12 — User Permissions and Role Separation

#### Exercise 12.1 — Explore Role Boundaries

Log in as **trainee02**, **trainee03**, **trainee04** in sequence.

| Account | Group | Can access invoices? | Can access projects? | Can create third parties? |
|---------|-------|---------------------|---------------------|--------------------------|
| trainee02 | Sales | No | Yes (edit) | Yes |
| trainee03 | Basic | No | No | No |
| trainee04 | Design & Dev | No | Yes (edit) | Yes |

#### Exercise 12.2 — Review Group Permissions

Log in as **trainer** (Admin).

1. Setup → Groups. Open each group.
2. Review permissions per group.
3. Discussion: if a Basic trainee needs read-only invoice access, how would you implement it?

---

## Facilitator Reference — Document Chain by Client

| Client | Proposal | Invoice(s) | Contract | Project |
|--------|----------|-----------|---------|---------|
| Pinnacle Goods | PROP-2026-0001 Signed | INV-2026-0001 partial payment | — | PROJ-0001 active |
| Riverstone | PROP-2026-0003 Signed | INV-2026-0003 deposit validated | — | PROJ-0002 active |
| Brightpath | PROP-2026-0002 Billed | Jan + Feb paid, Mar open | CONT-2026-0001 active | PROJ-0003 active |
| Apex Digital | PROP-2026-0004 Signed | INV-2026-0004 deposit | — | PROJ-0004 active |
| Clearwater | — | WaaS invoices x2 paid | CONT-2026-0002 active | — |
| Bellwether | Original cancelled + Revised signed | Deposit validated | — | Active |
| Maple Ridge | Signed + billed | Deposit + Balance both paid | — | Closed |
| Thornton Hardware | Signed | Deposit overdue + Late fee open | — | Active |
| Halcyon Health | Signed | Retainer x3: 2 paid, 1 open | CONT active | — |
| Vantage Point | Original + Change order both signed | Deposit + Rush fee validated | — | Active |
| Redwood Legal | Draft | — | — | — |

---

## Metadata

| Field | Value |
|---|---|
| Document Type | Guide |
| Domain | Dolibarr Training |
| Applies To | MokoDoliTraining development, Dolibarr v23+ |
| Audience | Training facilitators and trainees |
| Environment | MokoDoliTraining demo data |
| Owner | Moko Consulting |
| Path | /docs/guide/training-exercises.md |
| VERSION | development |
| Status | Active |
| Last Reviewed | 2026-03-15 |

## Revision History

| Date | Author | Change |
|---|---|---|
| 2026-03-13 | jmiller | Initial incorporation from mokokcrm-training-exercises.md v1.1.0 |
| 2026-03-15 | claude | Restructured by group and exercise ID; added all 26 tour exercises with step tables; added new workflow categories 7–12 |
