<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
This file is part of a Moko Consulting project.
SPDX-License-Identifier: GPL-3.0-or-later

DEFGROUP: MokoDoliTraining.Docs.Guide
INGROUP:  MokoDoliTraining.Docs
REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH:     /docs/guide/training-exercises.md
VERSION:  01.01.00
BRIEF:    Hands-on training exercises for Dolibarr v23 using the MokoDoliTraining demo dataset.
-->

## Dolibarr CRM Training Exercises

This guide covers hands-on exercises in the Dolibarr training environment. Exercises are organized by workflow category. Each exercise references demo client records and walks through a complete process as it would occur in a real client engagement.

All data in the training environment is fictional. No real client information is present.

---

## Training Users

Seven dedicated training accounts are pre-loaded. Each is assigned to a group that reflects a real team role. Use the account that matches the role you are practicing for each exercise.

**Default password for each login is the same as its username.** Each account's password is set to the same string as the login (for example, the password for `trainee01` is `trainee01`, and the password for `trainer` is `trainer`). Log in using that password. **DO NOT CHANGE YOUR PASSWORD UNLESS INSTRUCTED TO DO SO.**

| Login | Password | Group | Access Level | Use For |
|-------|----------|-------|-------------|---------|
| trainee01 | trainee01 | Admin | Full access -- all modules | Exercises requiring full admin view; exercise 12.1 role A |
| trainee02 | trainee02 | Project Manager | Projects, proposals, time; no invoices | PM-role exercises; exercise 12.1 role B |
| trainee03 | trainee03 | Contractor | Own tasks and time only; no invoices, no third parties | Contractor-role exercises; exercise 12.1 role C |
| trainee04 | trainee04 | Finance | Invoices and expense approval; no project editing | Finance-role exercises; exercise 12.1 role D |
| trainee05 | trainee05 | Contractor | Same as trainee03 -- second contractor for team exercises | Concurrent time-logging exercises |
| trainee06 | trainee06 | Project Manager | Same as trainee02 -- second PM for concurrent exercises | Parallel proposal and project work |
| trainer | trainer | Admin | Full access | Facilitator account -- do not use for trainee exercises |

**Quick login guide by exercise:**

- Exercises 1-11: log in as **trainee01** (full access needed to create and validate records)
- Exercise 10.1 (submit expense as contractor): log in as **trainee03** or **trainee05**
- Exercise 10.2 (approve expense): log in as **trainee01** or **trainee04**
- Exercise 12.1 (explore role boundaries): use **trainee02**, **trainee03**, **trainee04** in sequence
- Exercise 12.2 (review group permissions): log in as **trainee01**

> **Note on core module rights:** Dolibarr manages rights for core modules (third parties, projects, proposals, invoices, contracts) through its internal PHP permission system, not the database. If a trainee account cannot access a module it should, ask the facilitator to verify group rights under Setup > Users and Groups > Group Rights.

---

## Client Reference

| ID | Client | Industry | Status | Primary Scenario |
|----|--------|----------|--------|-----------------|
| CU-M001 | Pinnacle Goods Co. | E-commerce / retail | Active -- build in progress | Time logging, billing from tasks |
| CU-M002 | Riverstone Community Group | Nonprofit / community | Active -- design phase | Standard project workflow |
| CU-M003 | Brightpath Nonprofit Solutions | Nonprofit / grant consulting | Active -- retainer | Retainer billing history |
| CU-M004 | Apex Digital Agency | Digital agency | Active -- discovery complete | Proposal and proposal line items |
| CU-M005 | Ironwood Charter School | Education / nonprofit | Active | General reference |
| CU-M006 | Clearwater Media Group | Media / publishing | Active -- WaaS retainer | Recurring invoice setup |
| CU-M007 | Harborview Wellness Clinic | Healthcare | Prospect | Prospect nurture activities |
| CU-M008 | Summit Trade Co. | B2B / distribution | Prospect | Prospect nurture activities |
| CU-M009 | Redwood Legal Group | Legal / professional services | Prospect -- intake in progress | Exercise set 1: New client intake |
| CU-M010 | Bellwether Bakehouse | Food / hospitality | Client -- proposal revised | Exercise set 2: Proposal rejected and revised |
| CU-M011 | Maple Ridge Property Group | Real estate / property mgmt | Client -- project closed | Exercise set 3: Project close and offboarding |
| CU-M012 | Thornton Hardware and Supply | Local retail / B2B | Client -- overdue account | Exercise set 4: Overdue invoice and collections |
| CU-M013 | Halcyon Health Partners | Health and fitness / wellness | Client -- retainer active | Exercise set 5: Retainer and recurring billing |
| CU-M014 | Vantage Point Logistics | Logistics / supply chain | Client -- scope change mid-project | Exercise set 6: Change order and scope creep |
| VE-M001 | DemoHost Pro | Hosting vendor | Vendor | Vendor payments |
| VE-M002 | PrintBridge Supply Co. | Print / swag vendor | Vendor | Procurement cycle |

---

## Physical Products Reference

The product catalog includes 16 physical goods (stockable products) sourced from PrintBridge Supply Co. All are stocked in the demo warehouse (DEMO-WH-01) with initial quantities pre-loaded.

**Print:**

| Ref | Name | Sale Price | Cost | Stock |
|-----|------|-----------|------|-------|
| PRINT-BIZCARD-500 | Business Cards -- 500 qty | $89.00 | $55.00 | 12 boxes |
| PRINT-BROCHURE-100 | Tri-Fold Brochures -- 100 qty | $149.00 | $89.00 | 8 boxes |
| PRINT-RACKCARD-250 | Rack Cards -- 250 qty | $119.00 | $72.00 | 15 packs |
| PRINT-BANNER-2X4 | Vinyl Banner -- 2x4 ft | $79.00 | $45.00 | 6 units |
| PRINT-POSTCARD-250 | Postcards -- 4x6, 250 qty | $99.00 | $55.00 | 20 packs |
| PRINT-STICKER-100 | Die-Cut Stickers -- 100 qty | $69.00 | $38.00 | 30 sheets |
| PRINT-LETTERHEAD-250 | Branded Letterhead -- 250 sheets | $129.00 | $72.00 | 10 reams |

**Swag / Branded Merchandise:**

| Ref | Name | Sale Price | Cost | Stock |
|-----|------|-----------|------|-------|
| SWAG-USB-10PK | Branded USB Drives -- 10-Pack (8 GB) | $89.00 | $52.00 | 5 packs |
| SWAG-NOTEBOOK | Branded Softcover Notebook -- A5 | $12.00 | $7.50 | 24 units |
| SWAG-TOTE | Branded Canvas Tote Bag | $14.50 | $8.00 | 18 units |
| SWAG-MUG | Branded Ceramic Mug -- 11 oz | $18.00 | $9.50 | 36 units |
| SWAG-PEN-12PK | Branded Ballpoint Pens -- 12-Pack | $36.00 | $18.00 | 10 packs |
| SWAG-BOTTLE | Branded Stainless Water Bottle -- 20 oz | $32.00 | $16.50 | 15 units |
| SWAG-HAT | Branded Structured Cap -- One Size | $24.00 | $11.00 | 20 units |
| SWAG-SHIRT | Branded Unisex Tee -- S/M/L/XL | $22.00 | $8.75 | 25 units |

All physical products have weight and dimension data populated (weight in lb, dimensions in inches). Supplier cost basis is stored under PrintBridge Supply Co. in the product record.

Use these products in exercises covering: line items on proposals and invoices, purchase orders to PrintBridge, stock receipt movements, and margin analysis.

---

## Workflow Category 1 -- New Client Intake

> **Objective:** Practice the full path from first contact to signed proposal and deposit invoice.

### Exercise 1.1 -- Log a First Contact

**Client:** Redwood Legal Group (CU-M009)

Redwood Legal Group reached out via email asking about a new website for their firm. You have been asked to log the contact and create a follow-up task.

Steps:
1. Open Third Parties and locate Redwood Legal Group.
2. Navigate to the Agenda tab on their record.
3. Create a new CRM event: Type = Email, Direction = Inbound, Date = today. Note: "Initial inquiry -- law firm website. Wants 5-page site plus contact form and attorney bios section."
4. Assign the event to yourself (jmiller).
5. Create a follow-up task on the same record: due in 3 business days, title "Send intro questionnaire -- Redwood Legal."
6. Save both records.

What to check: the CRM event appears on the Redwood Legal timeline. The follow-up task appears in your task list on the home dashboard.

---

### Exercise 1.2 -- Create and Send a Proposal

**Client:** Redwood Legal Group (CU-M009)

You have completed the intake questionnaire with Redwood. You are ready to write a proposal for a 5-page Joomla site, design phase, and launch.

Steps:
1. On the Redwood Legal record, navigate to Proposals and create a new proposal.
2. Ref will auto-number. Set validity date to 30 days from today.
3. Add line items from the product catalog: WEB-DISC (Discovery Phase), WEB-DSGN (Design Phase), WEB-BILD (Build Phase), WEB-LNCH (Launch Phase), VENDOR-HOST-12MO (Hosting Annual), VENDOR-DOMAIN (Domain Registration).
4. Add a public note: "Thank you for considering us. This proposal covers your new law firm website from discovery through launch."
5. Save as draft. Verify total matches expected sum.
6. Validate the proposal (status changes to Validated).
7. Use the Send by Email action. Note the email intercept -- in the training environment all outbound mail is redirected to a single address configured by the facilitator.

What to check: proposal status is Validated, not Draft. The email intercept confirmation appears. The proposal is now visible on the Redwood Legal record under the Proposals tab.

---

### Exercise 1.3 -- Mark a Proposal Signed and Generate Deposit Invoice

**Client:** Redwood Legal Group (CU-M009)

Redwood has signed and returned the proposal. You need to update the record and generate the 50% deposit invoice.

Steps:
1. Open the Redwood Legal proposal.
2. Set status to Signed. Enter today as the signed date in the extrafield.
3. From the proposal, use Create Invoice to generate a deposit invoice.
4. Edit the invoice line item to reflect 50% of the proposal total. Update label to "50% Deposit -- Law Firm Website."
5. Set payment terms to Due Upon Receipt.
6. Validate the invoice.
7. Create a new CRM event on the Redwood record: Type = Note, content "Proposal signed. Deposit invoice sent."

What to check: proposal status shows Signed. Invoice is in Validated state. The invoice is linked to the proposal in the Linked Documents panel.

---

## Workflow Category 2 -- Proposal Revision

> **Objective:** Practice handling a declined proposal, revising scope, and re-presenting.

### Exercise 2.1 -- Review a Cancelled Proposal

**Client:** Bellwether Bakehouse (CU-M010)

Bellwether Bakehouse originally requested a full e-commerce site including online ordering and a loyalty program. After reviewing the proposal, the owner felt the scope was beyond their current budget. The original proposal was cancelled.

Steps:
1. Open Bellwether Bakehouse and navigate to their Proposals tab.
2. Locate the cancelled proposal (status = Cancelled). Note the original line items and total.
3. Open the CRM activity log for Bellwether. Read the note explaining the client's feedback.
4. Discuss: what does a cancelled proposal record tell you vs a declined one? How does this affect reporting?

What to check: understanding of proposal status states and when each is appropriate.

---

### Exercise 2.2 -- Create a Revised Proposal

**Client:** Bellwether Bakehouse (CU-M010)

After a follow-up call, Bellwether agreed to a descoped version -- a Joomla brochure site (no e-commerce) plus a basic brand refresh. You need to write the revised proposal.

Steps:
1. From the Bellwether record, create a new proposal.
2. Add line items: WEB-DISC, WEB-DSGN, WEB-BILD, WEB-LNCH, DESIGN-LOGO, VENDOR-HOST-12MO, VENDOR-DOMAIN.
3. In the private notes field, write: "Revised scope -- no e-commerce. Client confirmed budget ceiling of $3,000. Adjusted from PROP-2026-XXXX."
4. Add the original proposal reference in the Discount Reason extrafield.
5. Validate and mark Signed.
6. Log a CRM note: "Revised proposal signed. Client confirmed start date pending deposit."

What to check: the revised proposal has a different line-item total from the cancelled one. Both proposals are visible in Bellwether's history.

---

### Exercise 2.3 -- Compare Proposal Versions

**Client:** Bellwether Bakehouse (CU-M010)

Steps:
1. Open both proposals side by side (separate tabs or windows).
2. Identify: what line items were removed? What is the total difference?
3. In Dolibarr, where would you record the reason for the scope reduction?
4. If the client later wants to add e-commerce back, what is the correct workflow -- amend the existing proposal or create a new one?

Facilitator note: this exercise is discussion-based. There is no single correct Dolibarr action -- it is about understanding the proposal record as a client history artifact.

---

## Workflow Category 3 -- Project Execution and Time Logging

> **Objective:** Practice assigning tasks, logging time, and billing from logged hours.

### Exercise 3.1 -- Assign Tasks to Team Members

**Client:** Pinnacle Goods Co. -- PROJ-0001

The Pinnacle Goods project is in Phase 3 (Build). Several tasks need to be assigned.

Steps:
1. Open Projects and navigate to PROJ-0001 Pinnacle Goods.
2. Open Phase 3 -- Build and locate the following tasks: Joomla Install and Cassiopeia Config, VirtueMart E-Commerce Setup.
3. Assign Joomla Install to mwebb. Assign VirtueMart Setup to mwebb and bstewart.
4. Set a due date on each task that matches the task's planned end date.
5. Add a note on the VirtueMart task: "bstewart handles product catalog import; mwebb handles payment gateway."

What to check: tasks show the correct assignees. The tasks appear in each user's task list.

---

### Exercise 3.2 -- Log Time Against a Task

**Client:** Pinnacle Goods Co. -- PROJ-0001

You (jmiller) have just spent 2 hours on a client call reviewing the homepage mockup. Log this time.

Steps:
1. Navigate to PROJ-0001, Phase 2 -- Design, task "Client UAT and Feedback."
2. Open the task and navigate to the Time Spent tab.
3. Log 2 hours (7200 seconds). Date = today. Description = "Client review call -- homepage and product page mockups. Approved with minor text changes."
4. Mark as billable.
5. Save.

Repeat: log 4.5 hours for mwebb on the VirtueMart E-Commerce Setup task. Description = "Initial VirtueMart install and category structure setup." Billable.

What to check: time entries appear on the task's time log. Total logged hours update on the project summary. The project time report now shows logged hours by user.

---

### Exercise 3.3 -- Generate an Invoice from Logged Time

**Client:** Pinnacle Goods Co. -- PROJ-0001

Pinnacle's deposit invoice covers a fixed 50% down. The contract also allows billing for time logged beyond scope. You need to generate a supplemental invoice from the time logged this month.

Steps:
1. From PROJ-0001, use Generate Invoice from Time to create a draft invoice.
2. Review the line items -- each billable time entry should appear.
3. Consolidate the line items by task group (optional -- discuss with facilitator whether to consolidate or itemize).
4. Add invoice header note: "Supplemental billing -- March 2026 build hours per engagement agreement."
5. Validate the invoice.

What to check: the invoice is linked to PROJ-0001 in the Linked Documents panel. The time entries on the project are now marked as invoiced.

---

## Workflow Category 4 -- Full Invoice Lifecycle

> **Objective:** Trace an invoice from creation through payment, including edge cases.

### Exercise 4.1 -- Process a Payment on an Outstanding Invoice

**Client:** Pinnacle Goods Co.

INV-2026-0001 (Pinnacle deposit, $2,006.50) is Validated but unpaid. A partial payment of $500 has been received via Stripe.

Steps:
1. Open INV-2026-0001.
2. Use Record Payment. Amount = $500.00. Date = today. Payment method = Stripe. Reference = DEMO-STRIPE-001.
3. Note the invoice status -- it should remain Validated (partially paid), not flip to Paid.
4. View the payment entry on the invoice and on the bank account ledger.

What to check: invoice shows partial payment. Remaining balance is $1,506.50. Bank account DEMO-CHK shows the credit.

---

### Exercise 4.2 -- Apply a Late Fee

**Client:** Thornton Hardware and Supply (CU-M012)

Thornton Hardware's deposit invoice is 45 days overdue. Per the engagement agreement, a $35 late fee applies after 30 days. A CRM note already shows two unanswered follow-ups.

Steps:
1. Open Thornton Hardware's record and locate the overdue invoice.
2. Review the invoice aging -- note the days past due.
3. Create a new invoice for the late fee: add line item FEE-LATE ($35.00). Note: "Late payment fee per section 4.2 of service agreement. Original invoice [ref] due [date]."
4. Validate the late fee invoice.
5. Log a CRM event on Thornton's record: Type = Phone Call, note "Left voicemail re: overdue invoice and late fee. Requested payment by [date + 7 days]."

What to check: Thornton now has two open invoices. The CRM timeline shows the collections activity in sequence.

---

### Exercise 4.3 -- Record a Full Payment and Close an Invoice

**Client:** Brightpath Nonprofit Solutions

INV-2026-0002 (Brightpath Jan retainer, $399) is already marked paid in the system. Examine how this was processed.

Steps:
1. Open INV-2026-0002.
2. Review the payment record -- note the payment method (ACH), reference number, and bank account it posted to.
3. Navigate to the bank account DEMO-CHK and find the corresponding bank line entry.
4. Discuss: what is the difference between an invoice status of Paid vs Validated? What action triggers the status change?

Facilitator note: this is a review exercise. The payment was pre-loaded to show a completed state. Focus on understanding how Dolibarr links payment to invoice to bank ledger.

---

## Workflow Category 5 -- Overdue Accounts and Collections

> **Objective:** Practice identifying, documenting, and resolving overdue accounts.

### Exercise 5.1 -- Run an Aging Report

Steps:
1. Navigate to Invoices > Statistics or the Overdue Invoices list.
2. Identify all invoices past their payment due date.
3. For each overdue invoice, note: client name, invoice ref, original due date, days overdue, amount.
4. Which client has the oldest overdue balance? What is the total overdue balance across all clients?

What to check: Thornton Hardware appears at the top of the aging list. At least one other invoice should show as past due.

---

### Exercise 5.2 -- Collections Activity Log

**Client:** Thornton Hardware and Supply (CU-M012)

Steps:
1. Open Thornton Hardware's CRM activity log.
2. Review the existing follow-up notes that were pre-loaded.
3. Add a new CRM event: Type = Email, note "Sent formal collections notice. Attached copy of original invoice and late fee invoice. Requested full payment within 7 days or will pause engagement."
4. Flag Thornton Hardware's record with the appropriate prospect/client status to indicate account on hold.

---

### Exercise 5.3 -- Record a Partial Payment on an Overdue Invoice

**Client:** Thornton Hardware and Supply (CU-M012)

Thornton has sent a partial payment of $400.

Steps:
1. Open the original overdue invoice on Thornton's account.
2. Record the partial payment: $400.00, check, reference DEMO-CHK-THW-001, today's date.
3. Note the remaining balance.
4. Log a CRM note: "Partial payment received $400. Remaining balance [amount]. Agreed to pay remainder by [date + 14 days]."

---

## Workflow Category 6 -- Retainer and Recurring Billing

> **Objective:** Practice setting up and managing recurring billing and multi-month retainer history.

### Exercise 6.1 -- Review a Retainer Contract and Billing History

**Client:** Brightpath Nonprofit Solutions (CU-M003)

Brightpath has been on a monthly consulting retainer since January. Three months of invoices should exist.

Steps:
1. Open Brightpath's record and navigate to Contracts.
2. Open CONT-2026-0001. Review the service lines -- note the monthly fee and the grant writing component.
3. Navigate to Brightpath's Invoices tab. Identify the three retainer invoices. What are their statuses?
4. Which months are paid? Which is outstanding?
5. Navigate to the Payments tab and verify the payment records match the paid invoices.

What to check: understanding of how a contract relates to recurring invoices. How to trace a payment chain across multiple months.

---

### Exercise 6.2 -- Process the Outstanding Retainer Invoice

**Client:** Brightpath Nonprofit Solutions (CU-M003)

The March retainer invoice is outstanding. Process the payment.

Steps:
1. Open the March retainer invoice for Brightpath.
2. Record payment: $399.00, ACH, reference DEMO-ACH-BRT-003, today's date.
3. Confirm the invoice status updates to Paid.
4. Confirm the bank ledger entry appears on DEMO-CHK.

---

### Exercise 6.3 -- Review a Recurring Invoice Template

**Client:** Clearwater Media Group (CU-M006)

Clearwater is on a WaaS retainer with two recurring line items (service + hosting pass-through). A recurring invoice template exists in the system.

Steps:
1. Navigate to Invoices > Recurring Invoices (Templates).
2. Open the Clearwater recurring template. Review the recurrence settings -- frequency, next generation date, and line items.
3. Discuss: what happens when Dolibarr generates the next invoice from this template? Does it auto-send, or does it require manual validation?
4. Compare the template to the actual invoices already generated for Clearwater. Are the amounts consistent?

---

### Exercise 6.4 -- Set Up a New Recurring Invoice Template

**Client:** Halcyon Health Partners (CU-M013)

Halcyon Health Partners has signed a monthly digital marketing consulting retainer at $399/month. Create the recurring invoice template.

Steps:
1. Navigate to Invoices > New Recurring Invoice.
2. Set client to Halcyon Health Partners.
3. Add line item: CONS-RETAINER, quantity 1.
4. Set recurrence: Monthly, generate on the 1st of each month, start next month.
5. Set payment terms: Due Upon Receipt.
6. Save the template.
7. Verify the template appears in the Recurring Invoices list.

---

## Workflow Category 7 -- Change Orders and Scope Creep

> **Objective:** Practice handling mid-project scope changes through the correct document workflow.

### Exercise 7.1 -- Identify a Scope Change Request

**Client:** Vantage Point Logistics (CU-M014)

Vantage Point's web build is in Phase 3. The client has requested adding a customer portal with login functionality -- this was not in the original scope.

Steps:
1. Open Vantage Point Logistics and navigate to the active project.
2. Find the CRM note documenting the original scope change request.
3. Review the original signed proposal to confirm the portal was not included.
4. Discuss: what are the options -- new proposal, amendment, change order invoice? What does your organization's standard workflow recommend?

Facilitator note: best practice is to create a new proposal (marked as amendment/change order) referencing the original, rather than editing the signed original. This keeps the document trail clean.

---

### Exercise 7.2 -- Create a Change Order Proposal

**Client:** Vantage Point Logistics (CU-M014)

Steps:
1. Create a new proposal for Vantage Point.
2. Add line items for the additional work: WEB-BILD (custom quantity/price for the portal component), FEE-RUSH (if applicable).
3. In the private notes, reference the original proposal number and describe the additional scope.
4. In the Proposal Type extrafield, set to web-build. In the Discount Reason field, note "Change order -- customer portal addition per client request [date]."
5. Validate and mark Signed.
6. Create a deposit invoice for 50% of the change order value.

What to check: the client now has two signed proposals -- the original engagement and the change order. Both are visible in the Proposals tab history.

---

### Exercise 7.3 -- Apply a Rush Surcharge

**Client:** Vantage Point Logistics (CU-M014)

The portal must be delivered in 10 days to meet a client trade show deadline. A rush fee applies.

Steps:
1. Open the change order proposal or create a standalone invoice.
2. Add line item FEE-RUSH ($150.00). Note: "Priority delivery surcharge -- portal feature, 10-day delivery window per client request."
3. Validate and send.
4. Log a CRM note documenting the client's acknowledgment of the rush fee.

---

## Workflow Category 8 -- Project Close and Client Offboarding

> **Objective:** Practice completing the document chain at project end.

### Exercise 8.1 -- Review a Completed Project Record

**Client:** Maple Ridge Property Group (CU-M011)

Maple Ridge's website project is complete. The full document chain is in place -- proposal, deposit invoice, balance invoice, both paid, project closed.

Steps:
1. Open Maple Ridge Property Group.
2. Navigate to each tab in turn: Proposals, Invoices, Contracts, Projects, CRM Activity.
3. Trace the full document chain: which proposal led to which invoices? Are both invoices paid? Is the project status Closed?
4. Review the final CRM note documenting the handoff.

What to check: understanding of what a complete, closed client record looks like. This is the target state for every fixed-scope engagement.

---

### Exercise 8.2 -- Generate a Balance Invoice

**Client:** Apex Digital Agency (CU-M004)

Apex's project is nearing launch. The deposit invoice (50%) was paid. You need to generate the balance invoice for the remaining 50%.

Steps:
1. Open Apex Digital Agency's invoice history.
2. Note the deposit invoice amount and what has been paid.
3. Create a new invoice. Add a single line item: label "Balance Due -- Corporate Site + Brand Identity Project." Amount = 50% of original proposal total.
4. Reference the original proposal number in the invoice note.
5. Set due date to 7 days from today (payment due at go-live).
6. Validate and mark as sent.

---

### Exercise 8.3 -- Close a Project

**Client:** Maple Ridge Property Group (CU-M011)

Review the already-closed Maple Ridge project to understand the close process.

Steps:
1. Open the Maple Ridge project.
2. Confirm: all tasks are at 100% progress or closed, all invoices are paid, the project status is Closed.
3. Discuss: what would happen if you tried to log time against a closed project? What if an invoice was still open?
4. In a live environment, what supporting actions would your organization take at project close -- file delivery, email handoff, feedback request?

---

## Workflow Category 9 -- Vendor and Procurement Cycle

> **Objective:** Practice the vendor side of Dolibarr -- purchase orders, supplier invoices, and payments.

### Exercise 9.1 -- Review a Supplier Invoice

**Vendor:** DemoHost Pro (VE-M001)

DemoHost Pro has sent the annual hosting renewal invoice for Pinnacle Goods.

Steps:
1. Navigate to Purchases > Supplier Invoices.
2. Open the DemoHost Pro invoice. Review the amount, due date, and linked products.
3. Note how this links back to the Pinnacle Goods project (the hosting cost is a pass-through billed to the client as VENDOR-HOST-12MO).
4. Discuss: how does your organization reconcile the supplier cost against the client charge? Where would you see the margin?

---

### Exercise 9.2 -- Process a Supplier Payment

**Vendor:** DemoHost Pro (VE-M001)

Steps:
1. Open the DemoHost Pro supplier invoice.
2. Record payment: full amount, bank transfer, reference DEMO-PAY-DHOST-001.
3. Confirm the payment appears on DEMO-CHK as a debit.

---

### Exercise 9.3 -- Create a Purchase Order

**Vendor:** PrintBridge Supply Co. (VE-M002)

Pinnacle Goods has approved a merch package for an upcoming trade show. You need to issue a PO to PrintBridge covering business cards, branded tees, and water bottles.

Steps:
1. Navigate to Purchases > Purchase Orders > New.
2. Set supplier to PrintBridge Supply Co.
3. Add line items: PRINT-BIZCARD-500 qty 2, SWAG-SHIRT qty 24 (assorted sizes), SWAG-BOTTLE qty 12.
4. Add a note referencing the Pinnacle Goods project (PROJ-0001).
5. Validate the PO.
6. Review the unit cost on each line -- note the margin between supplier cost and your sale price to Pinnacle.
7. Discuss: when PrintBridge delivers and invoices, what is the next step in Dolibarr to match the PO to the supplier invoice?

What to check: PO appears under Purchases > Purchase Orders. Each line shows the PrintBridge supplier ref (PB-BIZCARD-500, PB-TEE-UNISEX, PB-BOTTLE-20OZ). The note links the order to Pinnacle Goods.

---

## Workflow Category 10 -- Expense Reporting

> **Objective:** Practice submitting and approving expense reports.

### Exercise 10.1 -- Submit an Expense Report

**Role:** Log in as **trainee03** (Contractor group -- same permission set as tkim)

Steps:
1. Navigate to Expenses > My Expenses > New Expense Report.
2. Set date range: current month.
3. Add two expense lines: design software license renewal ($54.99, category Software/Subscriptions) and client meeting mileage (round trip, 22 miles, calculated at IRS rate, category Travel).
4. Submit the report for approval.

What to check: the report appears in the pending approval queue for jmiller (Admin).

---

### Exercise 10.2 -- Approve an Expense Report

**Role:** Log in as **trainee01** or **trainee04** (Admin or Finance group)

Steps:
1. Navigate to Expenses > Expense Reports to Approve.
2. Open trainee03's pending report (submitted in exercise 10.1). You can also open the pre-loaded tkim report (EX-2026-0002) to review a pending report without completing 10.1 first.
3. Review both line items.
4. Approve the report.
5. Record the reimbursement payment (bank transfer to tkim).

---

## Workflow Category 11 -- Prospect Nurture

> **Objective:** Practice managing prospects -- logging activity, creating follow-ups, and converting to client.

### Exercise 11.1 -- Work a Prospect Record

**Client:** Harborview Wellness Clinic (CU-M007)

Harborview is a warm prospect who came in through a referral. Two prior CRM entries are already logged. They have not yet received a proposal.

Steps:
1. Open Harborview Wellness Clinic.
2. Review the existing CRM activity log -- what has happened so far?
3. Log a new CRM event: today's date, Type = Phone Call, note "30-min discovery call. Interested in new patient portal and SEO. Requested proposal by end of month. Decision maker: clinic director."
4. Create a follow-up task: "Prepare Harborview proposal," due in 5 business days, assigned to jmiller.

---

### Exercise 11.2 -- Convert a Prospect to a Client

**Client:** Harborview Wellness Clinic (CU-M007)

After the proposal is signed, Harborview's status needs to be updated.

Steps:
1. Discuss: in Dolibarr, what field on the Third Party record determines whether it is a Prospect, Client, or both? (Answer: the Client field -- 0=none, 1=prospect, 2=client.)
2. Once Harborview signs a proposal, update their Client field from Prospect (1) to Client (2).
3. Note how this changes their appearance in filtered list views.

---

## Workflow Category 12 -- User Permissions and Role Separation

> **Objective:** Understand how Dolibarr user groups and rights scope access per role.

### Exercise 12.1 -- Explore Role Boundaries

**Logins needed:** trainee02, trainee03, trainee04 (in sequence)

Steps:
1. Log in as **trainee02** (Project Manager group).
2. Attempt to open Invoices. What do you see?
3. Navigate to Projects -- open PROJ-0001. Can you edit it?
4. Attempt to create a new proposal. Does the option appear?
5. Log out and log in as **trainee03** (Contractor group).
6. Attempt to open Invoices. What do you see?
7. Navigate to your task list. Can you log time?
8. Attempt to create a new third party record. What happens?
9. Log out and log in as **trainee04** (Finance group).
10. Open Invoices -- what access level do you have?
11. Open Projects -- what access level do you have?
12. Log out and return to **trainee01** (Admin).

Expected results: trainee02 (PM) can access projects and proposals, cannot access invoices. trainee03 (Contractor) can log time on assigned tasks, cannot access invoices or third party creation. trainee04 (Finance) can view and process invoices, cannot edit projects or proposals.

---

### Exercise 12.2 -- Review Group Permissions

**Role:** Log in as **trainee01** (Admin)

Steps:
1. Navigate to Users > User Groups.
2. Open each group (Admin, Project Manager, Contractor, Finance).
3. Review the permissions assigned to each group.
4. Discuss: if a contractor needed to view (but not edit) invoices for the projects they work on, how would you handle this? Options: add a specific right, create a new group, or grant individually?

---

## Facilitator Reference -- Document Chain by Client

The table below shows the full expected document chain for each active client after all demo data is loaded. Use this to verify the environment is complete before running exercises.

| Client | Proposal | Status | Invoice(s) | Status | Contract | Project |
|--------|----------|--------|------------|--------|----------|---------|
| Pinnacle Goods | PROP-2026-0001 | Signed | INV-2026-0001 (deposit, partial) | Validated | -- | PROJ-0001 active |
| Riverstone | PROP-2026-0003 | Signed | INV-2026-0003 (deposit) | Validated | -- | PROJ-0002 active |
| Brightpath | PROP-2026-0002 | Billed | INV-2026-0002 Jan + Feb + Mar | Jan paid, Feb paid, Mar open | CONT-2026-0001 | PROJ-0003 active |
| Apex Digital | PROP-2026-0004 | Signed | INV-2026-0004 (deposit) | Validated | -- | PROJ-0004 active |
| Clearwater | -- | -- | WaaS invoices x2 | Paid | CONT-2026-0002 | -- |
| Bellwether Bakehouse | Original (cancelled) + Revised (signed) | Signed | Deposit | Validated | -- | Active |
| Maple Ridge | PROP-2026-00XX | Billed | Deposit (paid) + Balance (paid) | Both paid | -- | Closed |
| Thornton Hardware | PROP-2026-00XX | Signed | Deposit (overdue) + Late fee | Open | -- | Active |
| Halcyon Health | PROP-2026-00XX | Signed | Retainer x3 months | 2 paid, 1 open | CONT active | -- |
| Vantage Point | Original (signed) + Change order (signed) | Signed | Deposit + Rush fee | Validated | -- | Active |
| Redwood Legal | Draft proposal | Draft | -- | -- | -- | -- |

---

## Metadata

| Field | Value |
|---|---|
| Document Type | Guide |
| Domain | Dolibarr Training |
| Applies To | MokoDoliTraining v1.x, Dolibarr v23+ |
| Audience | Training group -- Dolibarr v23 dev environment |
| Environment | dolibarr_training (demo data only) |
| Jurisdiction | Internal |
| Owner | Moko Consulting |
| Repo | https://github.com/mokoconsulting-tech/MokoDoliTraining |
| Path | /docs/guide/training-exercises.md |
| Version | 01.01.00 |
| Status | Draft |
| Last Reviewed | 2026-03-13 |
| Reviewed By | jmiller |

## Revision History

| Date | Author | Change | Notes |
|---|---|---|---|
| 2026-03-13 | jmiller | Initial incorporation from mokokcrm-training-exercises.md v1.1.0 | Reformatted to MokoStandards; removed em dashes; added file header, Metadata, Revision History |
