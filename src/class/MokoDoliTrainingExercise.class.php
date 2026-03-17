<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Exercise
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/class/MokoDoliTrainingExercise.class.php
 * VERSION:  01.00.00
 * BRIEF:    Exercise catalog and group-based exercise resolver.
 *           Each exercise maps to one training group (70-73) and defines
 *           step-by-step walkthrough instructions for the tour engine.
 *
 * Groups:   70 = Basic  |  71 = Sales  |  72 = Marketing  |  73 = Design & Dev
 * Tour JS reads these definitions from localStorage (set by exercise.php).
 */

class MokoDoliTrainingExercise
{
	// ── Training role group registry ──────────────────────────────────────────
	const GROUPS = [
		70 => ['name' => 'Basic',        'color' => '#6c757d', 'badge' => 'badge-status0'],
		71 => ['name' => 'Sales',        'color' => '#28a745', 'badge' => 'badge-status4'],
		72 => ['name' => 'Marketing',    'color' => '#007bff', 'badge' => 'badge-status1'],
		73 => ['name' => 'Design & Dev', 'color' => '#6f42c1', 'badge' => 'badge-status3'],
	];

	// ── Demo department group registry ────────────────────────────────────────
	// Maps business department groups (74-77, seeded in demo mode) to their
	// equivalent training groups (70-73) for exercise resolution.
	const DEMO_GROUPS = [
		74 => ['name' => 'Management',     'color' => '#343a40', 'badge' => 'badge-status8', 'maps_to' => 70],
		75 => ['name' => 'Sales Dept',     'color' => '#28a745', 'badge' => 'badge-status4', 'maps_to' => 71],
		76 => ['name' => 'Marketing Dept', 'color' => '#007bff', 'badge' => 'badge-status1', 'maps_to' => 72],
		77 => ['name' => 'Engineering',    'color' => '#6f42c1', 'badge' => 'badge-status3', 'maps_to' => 73],
	];

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Return all exercises, optionally filtered to one or more group IDs.
	 *
	 * @param int[]|null $groups  Group IDs to include; null = all groups
	 * @return array[]
	 */
	public static function getAll(?array $groups = null): array
	{
		$catalog = self::catalog();
		if ($groups === null) return $catalog;
		return array_values(array_filter($catalog, fn($ex) => in_array($ex['group'], $groups, true)));
	}

	/**
	 * Return exercises relevant to a specific Dolibarr user based on their
	 * training group membership (groups 70-73 in llx_usergroup_user).
	 * Falls back to Basic (70) if the user has no training group.
	 *
	 * @param object $db       Dolibarr DB object
	 * @param int    $user_id  Dolibarr user rowid
	 * @param int    $entity   Entity
	 * @return array[]
	 */
	public static function getForUser($db, int $user_id, int $entity = 1): array
	{
		// Check both training groups (70-73) and demo groups (74-77)
		$all_group_ids = array_merge(array_keys(self::GROUPS), array_keys(self::DEMO_GROUPS));
		$ids_sql = implode(',', $all_group_ids);

		$sql = "SELECT fk_usergroup FROM `" . MAIN_DB_PREFIX . "usergroup_user`"
			. " WHERE entity = " . (int) $entity
			. " AND fk_user = " . (int) $user_id
			. " AND fk_usergroup IN (" . $ids_sql . ")";

		$res    = $db->query($sql);
		$groups = [];
		if ($res) {
			while ($row = $db->fetch_object($res)) {
				$gid = (int) $row->fk_usergroup;
				// Resolve demo group → training group equivalent
				$groups[] = self::DEMO_GROUPS[$gid]['maps_to'] ?? $gid;
			}
		}

		// Default to Basic if no matching group membership
		if (empty($groups)) $groups = [70];

		return self::getAll(array_unique($groups));
	}

	/**
	 * Find a single exercise by ID.
	 *
	 * @param string $id  Exercise ID (e.g. 'sales_01')
	 * @return array|null
	 */
	public static function find(string $id): ?array
	{
		foreach (self::catalog() as $ex) {
			if ($ex['id'] === $id) return $ex;
		}
		return null;
	}

	/**
	 * Return exercises for a specific group (used by classes.php trainer section).
	 *
	 * @param int $group_id  Training group rowid (70-73)
	 * @return array[]
	 */
	public static function getByGroup(int $group_id): array
	{
		return self::getAll([$group_id]);
	}

	// ── Exercise catalog ──────────────────────────────────────────────────────
	//
	// Each exercise:
	//   id            unique key used in URLs and localStorage
	//   group         training group rowid (70-73)
	//   title         display name shown in the tour card header
	//   summary       one-line description shown on the exercise list card
	//   estimate      rough time estimate shown on the list card
	//   steps[]       ordered steps:
	//     path        substring matched against window.location.pathname
	//     nav_url     full path to navigate to if not on the right page
	//     selector    CSS selector to spotlight (null = no highlight)
	//     title       step heading inside the tour card
	//     body        instruction text for the trainee
	//     trainer_note  additional note visible only when trainer_mode=true

	private static function catalog(): array
	{
		return [

			// ================================================================
			// BASIC (group 70) — orientation, read-only exploration
			// ================================================================

			[
				'id'       => 'basic_01',
				'group'    => 70,
				'title'    => 'Finding Your Way Around',
				'summary'  => 'Tour the top menu, left navigation, and dashboard widgets.',
				'estimate' => '8 min',
				'steps'    => [
					[
						'path'         => '/index.php',
						'nav_url'      => '/index.php',
						'selector'     => '#topmenu-home-dropdown',
						'title'        => 'The Top Menu',
						'body'         => 'The icons along the top are your main navigation. Each icon leads to a module: Third Parties, Billing, Projects, and more. Hover over them to see their names.',
						'trainer_note' => 'Ask trainees to identify 3 modules they think they\'ll use most. Good icebreaker.',
					],
					[
						'path'         => '/index.php',
						'nav_url'      => '/index.php',
						'selector'     => '.div-table-responsive',
						'title'        => 'Your Dashboard',
						'body'         => 'The dashboard shows your recent activity and pending tasks. After you start working in Dolibarr, this becomes your daily starting point — open proposals, unpaid invoices, overdue tasks.',
						'trainer_note' => 'Point out that the dashboard is configurable. Widgets can be added/removed per user preferences.',
					],
					[
						'path'         => '/index.php',
						'nav_url'      => '/index.php',
						'selector'     => null,
						'title'        => 'Quick Search',
						'body'         => 'Use the search bar at the top to find any record instantly. Try typing a client name or invoice number. Dolibarr searches across all modules simultaneously.',
						'trainer_note' => 'Demonstrate live search with "Pinnacle" to show it finds both the company and related records.',
					],
					[
						'path'         => '/societe/list.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'The Third Parties List',
						'body'         => 'Navigate to Third Parties using the top menu. This is the master list of all companies and organisations you work with — clients, suppliers, and prospects. Every key workflow starts here.',
						'trainer_note' => 'Explain the difference between client=1 (prospect), client=2 (active client), and fournisseur=1 (supplier).',
					],
				],
			],

			[
				'id'       => 'basic_02',
				'group'    => 70,
				'title'    => 'Exploring a Client Record',
				'summary'  => 'Open a third party card and navigate its tabs — contacts, proposals, projects.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/societe/list.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Find Pinnacle Goods Co.',
						'body'         => 'Use the search field above the list to find "Pinnacle Goods". This is one of our active training clients. Click their name to open their record.',
						'trainer_note' => 'Have trainees search themselves rather than clicking. Building search habits early saves time later.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/card.php?socid=50',
						'selector'     => '.fiche',
						'title'        => 'The Client Card',
						'body'         => 'The client card shows everything about this company: address, phone, email, commercial status, and any internal notes. The coloured badge tells you if they\'re a prospect, active client, or supplier.',
						'trainer_note' => 'Note the "note_private" field — useful for internal reminders about client preferences or history.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/card.php?socid=50',
						'selector'     => '.tabBar',
						'title'        => 'Navigating the Tabs',
						'body'         => 'The tabs at the top of the card link to everything related to this client: their contacts, proposals, orders, invoices, contracts, projects, and documents. Click "Contacts" to see who you\'ve been dealing with.',
						'trainer_note' => 'Each tab is module-dependent — if a module isn\'t activated, its tab won\'t appear. Useful for scoped training environments.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/card.php?socid=50',
						'selector'     => null,
						'title'        => 'Client History at a Glance',
						'body'         => 'Scroll down to see the summary section — open proposals, unpaid invoices, active projects. Before any client meeting, a 30-second scan of this card tells you everything you need to know about the relationship.',
						'trainer_note' => 'This "360-degree view" is one of Dolibarr\'s biggest selling points. Compare to doing this manually across spreadsheets.',
					],
				],
			],

			[
				'id'       => 'basic_03',
				'group'    => 70,
				'title'    => 'Browsing the Product Catalogue',
				'summary'  => 'Find products and services, open a product sheet, understand pricing.',
				'estimate' => '8 min',
				'steps'    => [
					[
						'path'         => '/product/list.php',
						'nav_url'      => '/product/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'The Product & Services Catalogue',
						'body'         => 'Navigate to Products via the top menu. Every item you quote, sell, or purchase should be in this catalogue. Products have stock levels; services are intangible and don\'t carry stock.',
						'trainer_note' => 'Point out the "type" column (0=product, 1=service). Our training data has both types for different exercises.',
					],
					[
						'path'         => '/product/card.php',
						'nav_url'      => '/product/card.php?id=50',
						'selector'     => '.fiche',
						'title'        => 'A Product Sheet',
						'body'         => 'Click any product to open its sheet. You\'ll see the reference code, description, sales price, purchase price, VAT rate, and stock level. This information feeds into every proposal, order, and invoice that uses this item.',
						'trainer_note' => 'Discuss how reference codes appear on client-facing documents (proposals, invoices). Consistent codes matter for professional appearance.',
					],
					[
						'path'         => '/product/card.php',
						'nav_url'      => '/product/card.php?id=50',
						'selector'     => '.tabBar',
						'title'        => 'Product Tabs',
						'body'         => 'Like client records, products have tabs: price lists, suppliers, stock movement history, and which proposals/orders include this item. The "Statistics" tab shows sales trends over time.',
						'trainer_note' => 'The "Suppliers" tab is essential for procurement workflows — links product to a specific supplier\'s purchase price.',
					],
					[
						'path'         => '/product/list.php',
						'nav_url'      => '/product/list.php',
						'selector'     => '.search-component-line',
						'title'        => 'Searching the Catalogue',
						'body'         => 'Back on the list, notice the filter row at the top. You can filter by product type, category, or search by reference or label. When a catalogue grows to hundreds of items, these filters become essential.',
						'trainer_note' => 'The category filter links to the Categories module — covered in the Marketing exercises. Good cross-reference point.',
					],
				],
			],

			[
				'id'       => 'basic_04',
				'group'    => 70,
				'title'    => 'The CRM Agenda',
				'summary'  => 'View logged events, understand how client interactions are tracked.',
				'estimate' => '7 min',
				'steps'    => [
					[
						'path'         => '/comm/action/list.php',
						'nav_url'      => '/comm/action/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'All Client Interactions',
						'body'         => 'The CRM Agenda captures every client interaction: calls, meetings, emails, and follow-up tasks. This creates an audit trail of the relationship — anyone on the team can see what was discussed and what was promised.',
						'trainer_note' => 'Contrast with "just calling and not logging it" — ask trainees how they\'ve lost context with clients in the past.',
					],
					[
						'path'         => '/comm/action/list.php',
						'nav_url'      => '/comm/action/list.php',
						'selector'     => '.search-component-line',
						'title'        => 'Filtering Events',
						'body'         => 'Use the filters to view only your own events, events for a specific client, or events of a certain type (phone call, meeting, email). The "Pending" filter shows actions that haven\'t been marked as done yet.',
						'trainer_note' => 'Show how a sales manager would use this to review team activity — filter by user + date range.',
					],
					[
						'path'         => '/comm/action/card.php',
						'nav_url'      => '/comm/action/list.php',
						'selector'     => '.fiche',
						'title'        => 'Inside an Event Record',
						'body'         => 'Click any event to open it. You\'ll see the type, the client it\'s linked to, the contact involved, the date, and a note field. The "Done / Not done" status drives your pending follow-up list.',
						'trainer_note' => 'Have trainees open the most recent event and identify: who was it with, what type was it, and is it marked done?',
					],
				],
			],

			// ================================================================
			// SALES (group 71) — commercial workflows
			// ================================================================

			[
				'id'       => 'sales_01',
				'group'    => 71,
				'title'    => 'Creating Your First Proposal',
				'summary'  => 'Draft a commercial proposal for Redwood Legal Group from scratch.',
				'estimate' => '15 min',
				'steps'    => [
					[
						'path'         => '/comm/propal/list.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => '.butAction',
						'title'        => 'Start a New Proposal',
						'body'         => 'Navigate to Commercial → Proposals. Click "New Proposal". A proposal lets you present pricing, scope, and terms to a prospect before they commit. It\'s the opening document of most sales cycles.',
						'trainer_note' => 'Explain that a proposal auto-assigns a reference number only after validation — draft proposals have a temporary "PROV" prefix.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'Select the Client',
						'body'         => 'In the "Third Party" field, type "Redwood" and select Redwood Legal Group from the dropdown. Redwood is a training client — a legal services firm currently in your intake pipeline.',
						'trainer_note' => 'If the third party isn\'t found, the client record may need creating first. Good segue into the create-third-party workflow.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/card.php?action=create',
						'selector'     => 'input[name="date"]',
						'title'        => 'Set the Proposal Date',
						'body'         => 'Set today as the proposal date. The validity end date defaults to 30 days out — adjust this to match your actual commercial terms. Expired proposals can\'t be validated.',
						'trainer_note' => 'Validity dates drive pipeline hygiene. Discuss how expired/unclosed proposals skew your sales funnel metrics.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/card.php?action=create',
						'selector'     => '#addlinepanel',
						'title'        => 'Add a Line Item',
						'body'         => 'Scroll down to the lines section. Click "Add a product or service" and search the catalogue for "Website Discovery". Set the quantity to 1. Add a second line for "Project Management" at 10 hours.',
						'trainer_note' => 'Show how the total auto-calculates including VAT. Discuss the difference between catalogue items and free-text lines.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/card.php?action=create',
						'selector'     => null,
						'title'        => 'Add a Note and Save',
						'body'         => 'Add a public note: "Proposal for initial discovery phase as discussed in our meeting of [today\'s date]." Click Save. The proposal is now in Draft status — it exists but hasn\'t been sent to the client.',
						'trainer_note' => 'Public notes appear on the PDF sent to the client. Private notes are internal only. Both are searchable.',
					],
				],
			],

			[
				'id'       => 'sales_02',
				'group'    => 71,
				'title'    => 'Validating and Sending a Proposal',
				'summary'  => 'Lock a draft proposal, generate the PDF, and email it to the client.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/comm/propal/list.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Find Your Draft Proposal',
						'body'         => 'In the proposal list, find the Redwood Legal Group proposal you created. It shows as "Draft" in grey. Filter by status "Draft" if the list is long. Click to open it.',
						'trainer_note' => 'The draft status means the proposal number is still provisional. Point out the "PROV-" prefix that\'ll change on validation.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => '.butAction',
						'title'        => 'Validate the Proposal',
						'body'         => 'Click "Validate". Dolibarr assigns a permanent reference number and locks the line items so the document is legally stable. You can still add notes after validation, but not change prices or lines.',
						'trainer_note' => 'Discuss why validation is separate from saving. It\'s an intentional gate — prevents accidental changes to a document the client has already seen.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => null,
						'title'        => 'Preview the PDF',
						'body'         => 'After validation, a PDF is generated automatically. Click the PDF icon to preview what the client will receive. Check that your company details, the client address, line items, and totals all look correct before sending.',
						'trainer_note' => 'PDF templates are configurable. If the layout looks off, the issue is usually in the document template settings.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => '.butAction',
						'title'        => 'Send by Email',
						'body'         => 'Click "Send by Email". Dolibarr opens a pre-filled email with the PDF attached. The To field auto-fills from the client contact email. Review, adjust the message, and click Send. The proposal status changes to "Sent".',
						'trainer_note' => 'The email is logged as a CRM event automatically — the client interaction history updates without extra steps.',
					],
				],
			],

			[
				'id'       => 'sales_03',
				'group'    => 71,
				'title'    => 'Converting a Proposal to an Order',
				'summary'  => 'Accept a proposal and generate a customer order from it automatically.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/comm/propal/list.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Find an Accepted Proposal',
						'body'         => 'Open the Bellwether Bakehouse proposal. In our training scenario, their brochure site proposal has been verbally accepted. Look for it in the "Sent" or "Validated" status filter.',
						'trainer_note' => 'Bellwether\'s story: original e-commerce scope was cancelled, replaced with a revised brochure site proposal. Good discussion point about scope changes.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => '.butAction',
						'title'        => 'Mark as Accepted',
						'body'         => 'Click "Set as Accepted". This records the client\'s agreement in the system. The proposal is now in "Accepted" status — it can\'t be further edited, but it hasn\'t generated an order yet.',
						'trainer_note' => 'Some businesses skip this step and go straight to "Create Order". The accepted status is useful when you need a signed quote before generating the order document.',
					],
					[
						'path'         => '/comm/propal/card.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => '.butAction',
						'title'        => 'Create the Customer Order',
						'body'         => 'Click "Create an Order". Dolibarr copies all line items from the proposal into a new customer order. Review the order preview — it should match the proposal exactly. Click Confirm to create it.',
						'trainer_note' => 'The order reference number is separate from the proposal number. If your client asks "what\'s my order number?", it\'s the commande ref, not the propal ref.',
					],
					[
						'path'         => '/commande/card.php',
						'nav_url'      => '/commande/list.php',
						'selector'     => '.fiche',
						'title'        => 'The Customer Order',
						'body'         => 'The order is created and the proposal is linked to it. From the order, you can manage delivery, generate invoices, and track fulfilment. Notice the proposal reference is shown in the order\'s linked documents section.',
						'trainer_note' => 'A single proposal can generate multiple orders (useful for phased projects). Demonstrate by looking at the linked documents section.',
					],
				],
			],

			[
				'id'       => 'sales_04',
				'group'    => 71,
				'title'    => 'Raising a Customer Invoice',
				'summary'  => 'Create an invoice from a customer order, validate it, and check its status.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/commande/card.php',
						'nav_url'      => '/commande/list.php',
						'selector'     => '.butAction',
						'title'        => 'Invoice Directly from an Order',
						'body'         => 'Open a validated customer order. Click "Create an Invoice". This is the fastest path — all lines, quantities, and prices copy across automatically. You only need to set the invoice date.',
						'trainer_note' => 'Contrast with manual invoice creation (Billing → New Invoice). From-order invoicing is preferred because it maintains the document chain: proposal → order → invoice.',
					],
					[
						'path'         => '/compta/facture/card.php',
						'nav_url'      => '/compta/facture/card.php?action=create',
						'selector'     => 'input[name="datef"]',
						'title'        => 'Set the Invoice Date',
						'body'         => 'The invoice date is the date of issue — it appears on the PDF and determines the VAT period. Set it to today. Payment terms (net 30, net 60, etc.) are set in the payment conditions field.',
						'trainer_note' => 'Invoice date vs due date matters for cash flow reporting. In France and many EU countries, it also affects VAT return periods.',
					],
					[
						'path'         => '/compta/facture/card.php',
						'nav_url'      => '/compta/facture/card.php?action=create',
						'selector'     => null,
						'title'        => 'Validate the Invoice',
						'body'         => 'Click Validate. An invoice number is assigned. Once validated, the invoice is legally issued — you cannot delete it, only add a credit note if there\'s an error. It\'s now "Unpaid" and appears in accounts receivable.',
						'trainer_note' => 'Stress that validation is irreversible. This is intentional for audit trail integrity. Credit notes (avoir) are the correction mechanism.',
					],
					[
						'path'         => '/compta/facture/card.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => '.fiche',
						'title'        => 'Invoice Validated',
						'body'         => 'The invoice shows the client details, line items, totals, and the due date. A PDF has been generated. You can email it from here, download it, or print it. The next exercise covers recording the payment when it arrives.',
						'trainer_note' => 'Show the difference between the invoice PDF (client-facing) and the payment voucher generated after payment is recorded.',
					],
				],
			],

			[
				'id'       => 'sales_05',
				'group'    => 71,
				'title'    => 'Recording a Customer Payment',
				'summary'  => 'Log a payment against Thornton Hardware\'s overdue invoice and clear the balance.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/compta/facture/list.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Find the Overdue Invoice',
						'body'         => 'Filter the invoice list by status "Unpaid" or search for Thornton Hardware. In our training scenario, Thornton has a deposit invoice that\'s been unpaid for 45 days. Open their invoice.',
						'trainer_note' => 'The "Late" badge appears automatically past the due date. Discuss how the overdue alert feeds into debtor chasing workflows.',
					],
					[
						'path'         => '/compta/facture/card.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => '.butAction',
						'title'        => 'Record the Payment',
						'body'         => 'Click "Record a Payment". Enter the full invoice amount (no partial this time), today\'s date, and the payment method — select "Bank Transfer". Link it to your bank account in the bank account field.',
						'trainer_note' => 'Partial payments are supported — if the client paid 50%, enter that amount. The invoice stays "Partially Paid" until the balance is cleared.',
					],
					[
						'path'         => '/compta/paiement/card.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => '.fiche',
						'title'        => 'Payment Recorded',
						'body'         => 'The payment record shows which invoice(s) it covers, the amount, method, and date. The linked invoice is now marked "Paid". Thornton\'s account is clear.',
						'trainer_note' => 'One payment can clear multiple invoices — click "Add another invoice" in the payment form if the client paid several at once.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/card.php?socid=93',
						'selector'     => null,
						'title'        => 'Verify on the Client Record',
						'body'         => 'Navigate to Thornton Hardware\'s client card. The invoice tabs should now show a zero outstanding balance. The payment event also appears in their CRM history. The full financial picture is always visible from one place.',
						'trainer_note' => 'This is the "complete the loop" moment — show how the client card reflects the payment. Good demonstration of the integrated data model.',
					],
				],
			],

			// ================================================================
			// MARKETING (group 72) — relationship and contact management
			// ================================================================

			[
				'id'       => 'marketing_01',
				'group'    => 72,
				'title'    => 'Adding a New Contact',
				'summary'  => 'Create a contact record and link it to an existing third party.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/contact/list.php',
						'nav_url'      => '/contact/list.php',
						'selector'     => '.butAction',
						'title'        => 'Navigate to Contacts',
						'body'         => 'Contacts are the individual people at your client and prospect companies. Navigate to Third Parties → Contacts. Every contact you\'ll ever email or call should have a record here.',
						'trainer_note' => 'Contacts are separate from companies in Dolibarr. A company can have many contacts. Contacts can also be independent (no company link).',
					],
					[
						'path'         => '/contact/card.php',
						'nav_url'      => '/contact/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'Link to a Company',
						'body'         => 'Always link a new contact to their company first. In the "Third Party" field, search for "Halcyon Health Partners". Halcyon is our health practice training client — you\'re adding their new practice coordinator.',
						'trainer_note' => 'The company link is optional but strongly recommended. Unlinked contacts become "orphan" records that are hard to find in client context.',
					],
					[
						'path'         => '/contact/card.php',
						'nav_url'      => '/contact/card.php?action=create',
						'selector'     => 'input[name="lastname"]',
						'title'        => 'Fill in the Details',
						'body'         => 'Enter: Last name "Patel", first name "Rohan", job title "Practice Coordinator", email rohan.patel@halcyonhealth.example, phone 555-0199. Complete records prevent duplication and make merge/export much cleaner.',
						'trainer_note' => 'Discuss the civility/title field — in formal industries (legal, finance) this matters for letters and proposals.',
					],
					[
						'path'         => '/contact/card.php',
						'nav_url'      => '/contact/list.php',
						'selector'     => null,
						'title'        => 'Contact Created',
						'body'         => 'Save the contact. Rohan Patel now appears on Halcyon Health\'s client card under the Contacts tab. When you create a proposal or send an email to Halcyon, you can address it directly to Rohan.',
						'trainer_note' => 'Show the contact on the company card. Point out that contacts can be added to proposals, invoices, and event records individually.',
					],
				],
			],

			[
				'id'       => 'marketing_02',
				'group'    => 72,
				'title'    => 'Logging a CRM Event',
				'summary'  => 'Record a client interaction and schedule a follow-up task.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/comm/action/list.php',
						'nav_url'      => '/comm/action/list.php',
						'selector'     => '.butAction',
						'title'        => 'Log an Interaction',
						'body'         => 'Navigate to CRM → Agenda. Click "New Event". Every meaningful client interaction — call, meeting, email — should be logged. This builds an unbroken history that any team member can read.',
						'trainer_note' => 'Emphasise that this isn\'t surveillance — it\'s institutional memory. If someone leaves the company, the CRM still has the context.',
					],
					[
						'path'         => '/comm/action/card.php',
						'nav_url'      => '/comm/action/card.php?action=create',
						'selector'     => 'select[name="actioncode"]',
						'title'        => 'Choose the Event Type',
						'body'         => 'Select "Phone call" as the action type. The available types depend on your Dolibarr configuration. Standard types include: phone call, meeting, email sent, email received, and custom task types.',
						'trainer_note' => 'Action types are configurable in Setup → Dictionaries → Event Types. You can add custom types for your business (e.g. "Site Visit", "Demo").',
					],
					[
						'path'         => '/comm/action/card.php',
						'nav_url'      => '/comm/action/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'Link to Client and Contact',
						'body'         => 'Link the event to "Halcyon Health Partners" and, in the contact field, select Linh Nguyen (Practice Manager). Add a note: "Called to follow up on retainer renewal. Linh confirmed renewal for Q3. Contract to follow." Mark as Done.',
						'trainer_note' => 'The note field supports markdown-like formatting in some themes. Encourage specific, actionable notes rather than vague entries like "called client".',
					],
					[
						'path'         => '/comm/action/card.php',
						'nav_url'      => '/comm/action/list.php',
						'selector'     => null,
						'title'        => 'Event Saved',
						'body'         => 'Save the event. It\'s now visible on Halcyon\'s client card and in your agenda view. Switch to the calendar view (click "Month" or "Week") to see it in timeline context. Filter by client or team member to focus on specific histories.',
						'trainer_note' => 'The calendar view is useful for reviewing all touchpoints in a month. Sales managers use this weekly to spot gaps in outreach.',
					],
				],
			],

			[
				'id'       => 'marketing_03',
				'group'    => 72,
				'title'    => 'Tagging Records with Categories',
				'summary'  => 'Apply categories to third parties and use them to filter lists.',
				'estimate' => '8 min',
				'steps'    => [
					[
						'path'         => '/categories/list.php',
						'nav_url'      => '/categories/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'The Category System',
						'body'         => 'Navigate to Products → Categories (or Third Parties → Categories, depending on the menu). Categories are labels you attach to records for grouping and filtering. They\'re multi-use: one client can have multiple categories.',
						'trainer_note' => 'Dolibarr has separate category trees per object type (third parties, contacts, products, etc.). Make sure you\'re in the Third Parties category tree.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/card.php?socid=95',
						'selector'     => null,
						'title'        => 'Tag Vantage Point Logistics',
						'body'         => 'Open the Vantage Point Logistics client record. In the categories field (often in a panel on the right or at the bottom of the card), add the category "Logistics" and "B2B". Save the record.',
						'trainer_note' => 'Categories are added/removed without versioning — there\'s no audit of who added them. If your team needs to track category changes, use the note fields instead.',
					],
					[
						'path'         => '/societe/list.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => '.search-component-line',
						'title'        => 'Filter by Category',
						'body'         => 'Go back to the Third Parties list. Use the Category filter in the search row to select "Logistics". Only clients tagged with that category appear. This is the foundation of targeted outreach — email marketing, mail merge, and campaign segmentation.',
						'trainer_note' => 'Combine category filters with status filters for precise segments: "Logistics clients who are active (client=2)" narrows a 200-company list to 8.',
					],
				],
			],

			[
				'id'       => 'marketing_04',
				'group'    => 72,
				'title'    => 'Updating Client Information',
				'summary'  => 'Edit a third party record and understand what gets logged in Dolibarr.',
				'estimate' => '8 min',
				'steps'    => [
					[
						'path'         => '/societe/list.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Finding Records to Update',
						'body'         => 'Client data changes constantly — companies relocate, phone numbers change, new offices open. Find "Maple Ridge Property Group" in the list. This property management firm has just moved offices.',
						'trainer_note' => 'Regular data hygiene sessions (monthly or quarterly) are good practice. Bounce email detection and returned mail are common triggers.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/card.php?socid=92',
						'selector'     => '.butAction',
						'title'        => 'Enter Edit Mode',
						'body'         => 'Click "Edit" (or the pencil icon). All fields become editable. Update the address to "500 New Premises Rd, 37044 Demo City". Also update the phone to 555-0299. Click Save.',
						'trainer_note' => 'If editing is greyed out, check permissions. Only users with societe/write rights can modify records — trainees may need permission configured first.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/card.php?socid=92',
						'selector'     => '.tabBar',
						'title'        => 'Checking the History',
						'body'         => 'Changes are saved immediately. To see who changed what and when, look for an "Events / History" tab or click "Show all events" in the CRM section of the card. Dolibarr logs every modification automatically.',
						'trainer_note' => 'The modification log is in llx_actioncomm (system events). This is read-only — you can\'t delete individual change entries, which is important for compliance.',
					],
				],
			],

			// ================================================================
			// DESIGN & DEV (group 73) — project and technical workflows
			// ================================================================

			[
				'id'       => 'devdesign_01',
				'group'    => 73,
				'title'    => 'Setting Up a Project',
				'summary'  => 'Create a new project linked to Vantage Point Logistics and configure it for billing.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/projet/list.php',
						'nav_url'      => '/projet/list.php',
						'selector'     => '.butAction',
						'title'        => 'New Project',
						'body'         => 'Navigate to Projects. Click "New Project". Projects are the central organising unit for all work: tasks, time, expenses, and milestones live inside a project. One client → one or more projects.',
						'trainer_note' => 'Some businesses use one project per client; others create per-engagement projects. Discuss the trade-offs in terms of reporting granularity.',
					],
					[
						'path'         => '/projet/card.php',
						'nav_url'      => '/projet/card.php?action=create',
						'selector'     => 'input[name="ref"]',
						'title'        => 'Project Reference',
						'body'         => 'Enter the reference "WEB-2026-003". This appears on time reports, invoices, and expense reports linked to the project. Use a consistent naming convention — your future self and your accountant will thank you.',
						'trainer_note' => 'Show how reference codes appear on client invoices generated from the project. Consistent codes make auditing straightforward.',
					],
					[
						'path'         => '/projet/card.php',
						'nav_url'      => '/projet/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'Link to the Client',
						'body'         => 'Link the project to "Vantage Point Logistics". Set the title to "Supply Chain Portal — Phase 2". Enable "Time tracking" and "Billable" checkboxes — this means time logged on tasks can be billed directly to the client.',
						'trainer_note' => 'Billable vs non-billable projects is a critical distinction. Non-billable time (internal meetings, training, prep) should still be tracked for capacity planning.',
					],
					[
						'path'         => '/projet/card.php',
						'nav_url'      => '/projet/list.php',
						'selector'     => '.tabBar',
						'title'        => 'Project Created — Explore the Tabs',
						'body'         => 'The project is saved. Explore the tabs: "Tasks" (upcoming), "Time Spent" (logged hours), "Documents" (attached files), "Notes" (internal). The "Finance" tab shows billing totals once time is invoiced.',
						'trainer_note' => 'The Finance tab is the project manager\'s best friend — it shows budget vs actual in real time. Great demo for any PM-focused audience.',
					],
				],
			],

			[
				'id'       => 'devdesign_02',
				'group'    => 73,
				'title'    => 'Breaking Work into Tasks',
				'summary'  => 'Add tasks to a project, assign them, and understand the task hierarchy.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/projet/tasks/index.php',
						'nav_url'      => '/projet/list.php',
						'selector'     => '.butAction',
						'title'        => 'Add the First Task',
						'body'         => 'Open the Vantage Point project and click the "Tasks" tab. Click "New Task". Tasks are the individual work items — features, design deliverables, bug fixes, meetings. Every hour of billable work should trace back to a task.',
						'trainer_note' => 'Tasks can have sub-tasks in Dolibarr (parent/child). Good for breaking epics into stories. Show the "parent task" field.',
					],
					[
						'path'         => '/projet/tasks/task.php',
						'nav_url'      => '/projet/tasks/index.php',
						'selector'     => 'input[name="label"]',
						'title'        => 'Task Details',
						'body'         => 'Set the label to "UX Wireframes — Dashboard". Add a description: "Design low-fidelity wireframes for the main supply chain dashboard screen. 3 iterations max." Assign it to yourself and set a planned duration of 8 hours.',
						'trainer_note' => 'Planned duration vs actual time is key for project health metrics. This gap is what project managers measure week to week.',
					],
					[
						'path'         => '/projet/tasks/index.php',
						'nav_url'      => '/projet/tasks/index.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Add More Tasks',
						'body'         => 'Add two more tasks: "Backend API Spec" (12h, assign to a backend dev) and "QA Testing Setup" (4h). The task list gives you a kanban-like view of all work. Use the status column to track progress: Not started → In progress → Completed.',
						'trainer_note' => 'Dolibarr tasks don\'t have a Kanban board natively — but the task list + status column serves the same purpose. Third-party plugins add Kanban views.',
					],
					[
						'path'         => '/projet/tasks/task.php',
						'nav_url'      => '/projet/tasks/index.php',
						'selector'     => null,
						'title'        => 'Task Dependencies',
						'body'         => 'Click into the "Backend API Spec" task. Notice you can link tasks to other tasks as predecessors — QA Testing can only start after the API spec is done. This dependency mapping helps avoid blockers going unnoticed.',
						'trainer_note' => 'Dependencies aren\'t enforced by Dolibarr (it\'s informational only). Real enforcement needs a PM tool. But the visibility alone prevents most issues.',
					],
				],
			],

			[
				'id'       => 'devdesign_03',
				'group'    => 73,
				'title'    => 'Logging Time on a Task',
				'summary'  => 'Record worked hours on a task and see how they appear on project reports.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/projet/tasks/index.php',
						'nav_url'      => '/projet/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Choose a Task to Log Time On',
						'body'         => 'Open the Vantage Point project and go to the Tasks tab. Click on the "UX Wireframes" task. Time tracking is how you capture effort — both for billing and for retrospective estimation accuracy.',
						'trainer_note' => 'Encourage logging time daily, not in bulk at end of week. Memory fades fast. Daily logging takes 2 minutes; end-of-week reconstruction takes 30.',
					],
					[
						'path'         => '/projet/tasks/task.php',
						'nav_url'      => '/projet/tasks/index.php',
						'selector'     => '.tabBar',
						'title'        => 'Switch to the Time Tab',
						'body'         => 'Click the "Time Spent" tab on the task. This is where all time entries for this specific task live. You\'ll see a form to add a new entry and the history of previous entries.',
						'trainer_note' => 'Time can also be logged via the global time entry form (Projects → Log Time) which lets you enter time across multiple tasks at once — faster for daily logging.',
					],
					[
						'path'         => '/projet/tasks/task.php',
						'nav_url'      => '/projet/tasks/index.php',
						'selector'     => 'input[name="date"]',
						'title'        => 'Log Your Hours',
						'body'         => 'Enter today\'s date, 3 hours (3h 0m), and the note "Completed low-fidelity wireframes for dashboard screen. Ready for client review." Click Add. The entry is immediately visible in the task\'s time log.',
						'trainer_note' => 'The note field is what appears on timesheets and client-facing time reports. Encourage clear, professional notes — assume the client may read them.',
					],
					[
						'path'         => '/projet/card.php',
						'nav_url'      => '/projet/list.php',
						'selector'     => null,
						'title'        => 'See it on the Project',
						'body'         => 'Navigate back to the Vantage Point project card. Under "Time Spent", your 3 hours now appear. The project total updates automatically. The Finance tab will show these hours against the project budget once billing is configured.',
						'trainer_note' => 'Show how the planned hours (8h) vs logged hours (3h) on the task feeds the progress percentage on the project overview.',
					],
				],
			],

			[
				'id'       => 'devdesign_04',
				'group'    => 73,
				'title'    => 'Handling a Support Ticket',
				'summary'  => 'Create a ticket for Thornton Hardware, assign it, and walk it through to resolution.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/ticket/list.php',
						'nav_url'      => '/ticket/list.php',
						'selector'     => '.butAction',
						'title'        => 'New Support Ticket',
						'body'         => 'Navigate to Support → Tickets. Click "New Ticket". Tickets are used for tracking issues, feature requests, and support queries raised by clients. Every client request that requires action should have a ticket.',
						'trainer_note' => 'Compare the Dolibarr ticket system to the email inbox alternative. Tickets have assignees, statuses, priorities, and histories — emails have none of those by default.',
					],
					[
						'path'         => '/ticket/card.php',
						'nav_url'      => '/ticket/card.php?action=create',
						'selector'     => 'select[name="type_code"]',
						'title'        => 'Set the Ticket Type',
						'body'         => 'Choose "Bug" as the type. Other types include "Assistance" and "Feature Request". Types help route tickets to the right team and feed into support analytics — bug counts vs feature requests vs how-to questions.',
						'trainer_note' => 'Ticket types are configurable. Many teams add custom types like "Deployment", "Security", or "Infrastructure" to match their workflow.',
					],
					[
						'path'         => '/ticket/card.php',
						'nav_url'      => '/ticket/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'Link to the Client',
						'body'         => 'Link the ticket to "Thornton Hardware & Supply". Set the subject: "Login page throwing 500 error after last deployment". Set priority to "High". Add a description with the error message and reproduction steps.',
						'trainer_note' => 'Reproduction steps are the most valuable part of a bug ticket. Ask trainees: "What information would you need to fix this without calling the client back?"',
					],
					[
						'path'         => '/ticket/card.php',
						'nav_url'      => '/ticket/list.php',
						'selector'     => '.butAction',
						'title'        => 'Assign and Investigate',
						'body'         => 'Save the ticket. It\'s now in "Open" status. Click "Assign to Me". Change the status to "In Progress". Add a public message: "Acknowledged — investigating the deployment logs now. Will update within 2 hours."',
						'trainer_note' => 'Public messages are visible to clients via the ticket portal (if configured). Private messages are internal only. The distinction matters for professional communication.',
					],
					[
						'path'         => '/ticket/card.php',
						'nav_url'      => '/ticket/list.php',
						'selector'     => null,
						'title'        => 'Resolve the Ticket',
						'body'         => 'Add a final message: "Root cause: missing environment variable in production config. Fixed and redeployed. Login page confirmed working." Change status to "Resolved". The ticket timeline now shows the full history from open to close.',
						'trainer_note' => 'Closed tickets are searchable — they\'re your knowledge base. Next time the same issue appears, search tickets before investigating from scratch.',
					],
				],
			],

			[
				'id'       => 'devdesign_05',
				'group'    => 73,
				'title'    => 'Submitting an Expense Report',
				'summary'  => 'Log project-related expenses, link them to a project, and submit for approval.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/expensereport/list.php',
						'nav_url'      => '/expensereport/list.php',
						'selector'     => '.butAction',
						'title'        => 'New Expense Report',
						'body'         => 'Navigate to HR → Expense Reports. Click "New Expense Report". Expense reports collect all costs you\'ve incurred on behalf of a project — travel, software licences, materials, client entertainment.',
						'trainer_note' => 'Frame expense reporting as self-service accounting: accurate expense claims mean projects are properly costed and you get reimbursed promptly.',
					],
					[
						'path'         => '/expensereport/card.php',
						'nav_url'      => '/expensereport/card.php?action=create',
						'selector'     => 'select[name="fk_project"]',
						'title'        => 'Link to the Project',
						'body'         => 'In the Project field, select the Vantage Point project. Linking expenses to a project is critical — it feeds into project profitability reports. Unlinked expenses can\'t be billed to clients.',
						'trainer_note' => 'Expenses linked to billable projects can be passed-through to clients directly from the project Finance tab. Powerful for consulting billing models.',
					],
					[
						'path'         => '/expensereport/card.php',
						'nav_url'      => '/expensereport/card.php?action=create',
						'selector'     => '.butAction',
						'title'        => 'Add Expense Lines',
						'body'         => 'Click "Add a line". Enter: Date = today, Type = "Travel", Amount = 45.00 (taxi to client site), Description = "Site visit to Vantage Point HQ for kickoff meeting". Add a second line for €15.00 "Parking".',
						'trainer_note' => 'Expense types are configurable and affect accounting codes. Make sure expense types align with your chart of accounts categories.',
					],
					[
						'path'         => '/expensereport/card.php',
						'nav_url'      => '/expensereport/list.php',
						'selector'     => null,
						'title'        => 'Submit for Approval',
						'body'         => 'Click "Submit" (or "Send for validation"). The report goes to your manager for approval. You\'ll receive a notification when it\'s approved. Once approved, finance processes the reimbursement and the costs are posted to the project.',
						'trainer_note' => 'The approval workflow is configurable — some businesses auto-approve small amounts. Show how the approve/reject decision appears in the manager\'s notification feed.',
					],
				],
			],

			// ================================================================
			// BASIC (group 70) — continued
			// ================================================================

			[
				'id'       => 'basic_05',
				'group'    => 70,
				'title'    => 'Finding and Downloading Documents',
				'summary'  => 'Locate a generated PDF, browse the document library, and download a file.',
				'estimate' => '7 min',
				'steps'    => [
					[
						'path'         => '/compta/facture/list.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Every Record Generates Documents',
						'body'         => 'Navigate to Billing → Invoices. Every validated invoice has a PDF generated automatically. The PDF icon in the list column shows which invoices have documents ready. Click any invoice to open it.',
						'trainer_note' => 'Generated PDFs live in the Dolibarr documents directory (usually /documents/). Backup policies should cover this directory alongside the database.',
					],
					[
						'path'         => '/compta/facture/card.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => null,
						'title'        => 'Download the Invoice PDF',
						'body'         => 'On the invoice card, look for the linked file in the "Documents" section or the PDF icon in the action bar. Click to download. The file is named with the invoice reference — keep this filename unchanged if you archive it externally.',
						'trainer_note' => 'Dolibarr regenerates the PDF if you click the regenerate button. Useful when the template changes — existing docs aren\'t updated automatically.',
					],
					[
						'path'         => '/ecm/index.php',
						'nav_url'      => '/ecm/index.php',
						'selector'     => 'table.tagtable',
						'title'        => 'The Document Library (ECM)',
						'body'         => 'Navigate to Tools → Document Library (ECM). This is the central file store — not just generated PDFs, but any files you upload: contracts, photos, specifications. Files can be attached to records across all modules.',
						'trainer_note' => 'ECM = Electronic Content Management. Dolibarr\'s ECM is basic but functional. For heavy document management, an integration with a dedicated DMS is common.',
					],
				],
			],

			[
				'id'       => 'basic_06',
				'group'    => 70,
				'title'    => 'Reading Reports and Statistics',
				'summary'  => 'Explore the turnover chart, activity stats, and how to filter report views.',
				'estimate' => '8 min',
				'steps'    => [
					[
						'path'         => '/compta/stats/index.php',
						'nav_url'      => '/compta/stats/index.php',
						'selector'     => null,
						'title'        => 'Billing Statistics Overview',
						'body'         => 'Navigate to Billing → Statistics. This page shows revenue trends over time: monthly invoice totals, payment rates, and outstanding amounts. The charts are the quickest way to see whether the business is growing, stable, or declining.',
						'trainer_note' => 'Statistics are real-time — they reflect the training data loaded into the system. Good opportunity to point out what the training dataset represents.',
					],
					[
						'path'         => '/compta/stats/index.php',
						'nav_url'      => '/compta/stats/index.php',
						'selector'     => 'select[name="year"]',
						'title'        => 'Filtering by Period',
						'body'         => 'Use the year and month filters to narrow the view. Statistics always cover the currently selected period. If you\'re reviewing last quarter\'s performance, set the year and use the month range. Changing the entity filter shows data for a specific subsidiary.',
						'trainer_note' => 'Discuss the difference between statistics (aggregated summaries) and audit logs (per-transaction details). Both have their place in financial review.',
					],
					[
						'path'         => '/comm/propal/stats/index.php',
						'nav_url'      => '/comm/propal/stats/index.php',
						'selector'     => null,
						'title'        => 'Proposal Statistics',
						'body'         => 'Navigate to Commercial → Proposals → Statistics. Here you can see your proposal win rate: how many proposals were accepted vs signed vs lost. This conversion funnel is one of the most valuable metrics for any sales-focused business.',
						'trainer_note' => 'Win rate varies hugely by industry. Ask trainees what a "good" win rate looks like for their business. Typical B2B: 20–40%, qualified leads: 40–60%.',
					],
					[
						'path'         => '/projet/stats/index.php',
						'nav_url'      => '/projet/stats/index.php',
						'selector'     => null,
						'title'        => 'Project & Time Statistics',
						'body'         => 'Navigate to Projects → Statistics. This shows total hours logged, hours per project, and per-user summaries. Teams that track time here can calculate average project cost and identify where time overruns commonly occur.',
						'trainer_note' => 'Projects stats + billing stats together give a true profitability picture: revenue generated vs time cost of delivery. This is the core of a time-based services business.',
					],
				],
			],

			// ================================================================
			// SALES (group 71) — continued
			// ================================================================

			[
				'id'       => 'sales_06',
				'group'    => 71,
				'title'    => 'Issuing a Credit Note',
				'summary'  => 'Create a credit note (avoir) to correct or reverse a validated invoice.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/compta/facture/list.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Find the Invoice to Credit',
						'body'         => 'Navigate to Billing → Invoices. Find the Bellwether Bakehouse invoice for the cancelled e-commerce project. In our training scenario, this project was cancelled after billing — the client is owed a full credit.',
						'trainer_note' => 'Validated invoices cannot be deleted in Dolibarr (for audit reasons). The credit note is the only legal correction mechanism. This is standard accounting practice.',
					],
					[
						'path'         => '/compta/facture/card.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => '.butAction',
						'title'        => 'Create a Credit Note',
						'body'         => 'Open the Bellwether invoice and click "Create a Credit Note" (or "Avoir"). Dolibarr creates a new document with negative amounts matching the original invoice. Review the lines — they should be identical to the original but negative.',
						'trainer_note' => 'A credit note reduces the client\'s outstanding balance. If fully credited, the original invoice and the credit note net to zero. Both remain in the ledger as separate documents.',
					],
					[
						'path'         => '/compta/facture/card.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => null,
						'title'        => 'Add a Reason and Validate',
						'body'         => 'Add a note in the public description: "Credit note for project cancellation — client reference CR-BELL-001." Validate the credit note. It\'s now a formal accounting document. Link it back to the original invoice using the "Pay" action to offset the balance.',
						'trainer_note' => 'Always include a reason in the note. Credit notes are audited — accountants and clients both need context. Reference the original invoice number in the note.',
					],
					[
						'path'         => '/compta/facture/card.php',
						'nav_url'      => '/compta/facture/list.php',
						'selector'     => null,
						'title'        => 'Verify the Balance',
						'body'         => 'Navigate to Bellwether Bakehouse\'s client card and check their invoice summary. The original invoice and the credit note should net to zero — the client\'s outstanding balance is cleared. All documents are preserved in the audit trail.',
						'trainer_note' => 'Credit notes can also be partial — covering only part of the invoice amount. Useful for partial returns or corrections of specific line items.',
					],
				],
			],

			[
				'id'       => 'sales_07',
				'group'    => 71,
				'title'    => 'Processing a Supplier Invoice',
				'summary'  => 'Record a purchase invoice from a supplier and link it to your expenses.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/fourn/facture/list.php',
						'nav_url'      => '/fourn/facture/list.php',
						'selector'     => '.butAction',
						'title'        => 'Supplier Invoices — the Other Side',
						'body'         => 'Navigate to Purchases → Supplier Invoices. While you send invoices to your clients, your suppliers send invoices to you. These are your costs — software subscriptions, subcontractors, office supplies. Recording them keeps your accounts payable up to date.',
						'trainer_note' => 'Accounts payable accuracy is as important as receivable. Late supplier payments damage relationships; tracking invoices here prevents things from slipping.',
					],
					[
						'path'         => '/fourn/facture/card.php',
						'nav_url'      => '/fourn/facture/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'Record a New Supplier Invoice',
						'body'         => 'Click "New Supplier Invoice". In the supplier field, search for "BrightSpark Design Studio" — one of our training subcontractors. Set the supplier reference to "BST-2026-0412" (this is their invoice number, not yours). Set the date to today.',
						'trainer_note' => 'Always record the supplier\'s own invoice number in the ref field. This is what their accounts team will quote when you call about a payment query.',
					],
					[
						'path'         => '/fourn/facture/card.php',
						'nav_url'      => '/fourn/facture/card.php?action=create',
						'selector'     => '#addlinepanel',
						'title'        => 'Add the Invoice Lines',
						'body'         => 'Add a line: Description "Graphic design services — Q1 brochure", quantity 1, unit price 1,200.00, VAT 20%. This represents BrightSpark\'s charge for design work they completed. The VAT here is input tax — recoverable if you\'re VAT registered.',
						'trainer_note' => 'Input VAT (on purchases) offsets output VAT (on sales) in VAT returns. Discuss why recording supplier invoices accurately matters for VAT compliance.',
					],
					[
						'path'         => '/fourn/facture/card.php',
						'nav_url'      => '/fourn/facture/list.php',
						'selector'     => null,
						'title'        => 'Validate and Mark for Payment',
						'body'         => 'Validate the invoice. It\'s now in "Unpaid" status in your accounts payable. When you pay BrightSpark, record the payment here (same flow as recording a client payment, but in the opposite direction). The supplier\'s balance clears automatically.',
						'trainer_note' => 'Supplier invoices can also be linked to purchase orders — creating the full purchase chain: PO → reception → supplier invoice → payment.',
					],
				],
			],

			[
				'id'       => 'sales_08',
				'group'    => 71,
				'title'    => 'Setting Up a Recurring Invoice',
				'summary'  => 'Create an invoice template that auto-generates on a monthly schedule.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/compta/facture/invoicetemplate_list.php',
						'nav_url'      => '/compta/facture/invoicetemplate_list.php',
						'selector'     => '.butAction',
						'title'        => 'Recurring Invoice Templates',
						'body'         => 'Navigate to Billing → Recurring Invoice Templates. For clients on monthly retainers or subscriptions, Dolibarr can generate invoices automatically on a schedule. This eliminates repetitive manual work and ensures billing never gets missed.',
						'trainer_note' => 'Recurring invoices are one of the highest-ROI features for service businesses. A 10-client retainer business saves 30–60 minutes per billing cycle.',
					],
					[
						'path'         => '/compta/facture/invoicetemplate_list.php',
						'nav_url'      => '/compta/facture/invoicetemplate_list.php',
						'selector'     => null,
						'title'        => 'Review an Existing Template',
						'body'         => 'The training data includes a recurring template for Halcyon Health Partners (monthly retainer). Open it. You\'ll see the same structure as a regular invoice — client, lines, amounts — plus frequency settings: every month, quarterly, etc.',
						'trainer_note' => 'Templates don\'t auto-trigger — they require a cron job or manual action in Dolibarr to generate the invoice. Check that the cron is configured in your production setup.',
					],
					[
						'path'         => '/compta/facture/invoicetemplate_list.php',
						'nav_url'      => '/compta/facture/invoicetemplate_list.php',
						'selector'     => '.butAction',
						'title'        => 'Create a New Template',
						'body'         => 'Click "New Recurring Template". Link to Redwood Legal Group. Add a line: "Monthly Support Retainer" — 1 unit at 800.00 + VAT. Set frequency to "Monthly" and the next generation date to the 1st of next month.',
						'trainer_note' => 'The "date when to stop" field is important — set it to the contract end date to avoid billing after contract expiry. Many businesses forget this and generate erroneous invoices.',
					],
					[
						'path'         => '/compta/facture/invoicetemplate_list.php',
						'nav_url'      => '/compta/facture/invoicetemplate_list.php',
						'selector'     => null,
						'title'        => 'Template Saved',
						'body'         => 'The template is now active. On the next generation date, Dolibarr will create a draft invoice from this template — ready for your review before validation. Some businesses auto-validate; others prefer to review first. Configure the behaviour in module settings.',
						'trainer_note' => 'Auto-validate vs review-first is a business process choice. High-trust long-term clients = auto; new clients or variable billing = always review first.',
					],
				],
			],

			// ================================================================
			// MARKETING (group 72) — continued
			// ================================================================

			[
				'id'       => 'marketing_05',
				'group'    => 72,
				'title'    => 'Managing a Prospect Pipeline',
				'summary'  => 'Move prospects through the pipeline from lead to active client.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/societe/list.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => '.search-component-line',
						'title'        => 'Identify Your Prospects',
						'body'         => 'In the Third Parties list, filter by "Prospect" status. These are companies you\'re targeting but haven\'t yet sold to. In Dolibarr, the client_status field separates prospects (0), active clients (1), and inactive clients (2).',
						'trainer_note' => 'Show the filter combination: Prospect + Category "Inbound Lead" — this is your fresh lead list. Compare it with Prospect + Category "Cold Outreach" for a different segment.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => '.fiche',
						'title'        => 'Open a Prospect Record',
						'body'         => 'Open the Cascade Analytics prospect card. This company has been in discussions for 3 months. Their card shows activity — CRM events, proposals sent. The goal of a prospect record is to give the full context without opening three systems.',
						'trainer_note' => 'The "Commercial Status" field on the third party card is a lightweight pipeline stage indicator. More granular funnel tracking typically uses the Opportunities module.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => '.butAction',
						'title'        => 'Update Commercial Status',
						'body'         => 'Cascade Analytics has verbally agreed to move forward. Edit the card and change their commercial status from "Prospect" to "Active Client" (client field = 1). Add a private note: "Verbal commitment 15 March. Proposal to follow this week."',
						'trainer_note' => 'In practice this change is triggered by a signed proposal or order. Discuss when each team changes the status — early vs late in the cycle — and the reporting impact.',
					],
					[
						'path'         => '/comm/propal/list.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Pipeline via Proposals',
						'body'         => 'Navigate to Proposals and filter by status "Sent". This is your real pipeline — proposals out in the world waiting for a decision. The "Validity end" column shows which deals are about to expire. Sort by amount to prioritise your follow-up calls.',
						'trainer_note' => 'The proposal list sorted by validity date is a sales manager\'s daily cheat sheet. Expired proposals represent deals at risk — follow up immediately or close them.',
					],
					[
						'path'         => '/comm/propal/list.php',
						'nav_url'      => '/comm/propal/list.php',
						'selector'     => null,
						'title'        => 'Closing a Lost Deal',
						'body'         => 'Open one of the "Sent" proposals. If a deal is lost, click "Set as Refused/Closed". Always add a note with the loss reason: "Client chose lower-cost competitor", "Budget cut", "No response after 3 follow-ups". Loss reasons feed into future strategy.',
						'trainer_note' => 'Loss reason analysis is gold for improving win rates. If 60% of losses are "price", that\'s a pricing conversation. If it\'s "no response", that\'s a qualification issue.',
					],
				],
			],

			[
				'id'       => 'marketing_06',
				'group'    => 72,
				'title'    => 'Exporting Data and Running Reports',
				'summary'  => 'Export a filtered contact list and generate a turnover summary.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/societe/list.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => '.search-component-line',
						'title'        => 'Filter Before You Export',
						'body'         => 'Always filter your list before exporting. In Third Parties, filter to show only "Active Clients" in the "Logistics" category. You\'re preparing an outreach list for a logistics-focused campaign. A targeted export is more useful than a full dump.',
						'trainer_note' => 'Data hygiene matters here — missing emails, outdated addresses, or duplicate records will undermine any campaign. Mention the importance of regular data cleaning.',
					],
					[
						'path'         => '/societe/list.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => 'a[href*="export"]',
						'title'        => 'Export to CSV',
						'body'         => 'With your filtered list showing, look for the "Export" button or CSV icon (often at the bottom of the list). Click it. Dolibarr exports the visible columns for the filtered records. The CSV opens in Excel or Sheets for mail merge or CRM import.',
						'trainer_note' => 'Exported data contains personal information — emphasise GDPR/data protection obligations. Exports should go to authorised recipients only and be deleted after use.',
					],
					[
						'path'         => '/compta/stats/index.php',
						'nav_url'      => '/compta/stats/index.php',
						'selector'     => null,
						'title'        => 'Monthly Turnover Report',
						'body'         => 'Navigate to Billing → Statistics. The turnover chart shows revenue per month. Compare the last 12 months. Identify the highest and lowest performing months — this is the starting point for revenue forecasting and resource planning.',
						'trainer_note' => 'Seasonal patterns are immediately visible here. If you see a consistent dip every August, you know to push harder in June/July. Pattern recognition is a key data literacy skill.',
					],
					[
						'path'         => '/compta/stats/facture.php',
						'nav_url'      => '/compta/stats/facture.php',
						'selector'     => null,
						'title'        => 'Invoice Status Breakdown',
						'body'         => 'Look for the invoice statistics breakdown showing paid vs unpaid totals. This is your collections health — what percentage of invoiced revenue has actually been collected? A high unpaid ratio signals cash flow risk that needs addressing.',
						'trainer_note' => 'Days Sales Outstanding (DSO) = the average time to collect. If DSO is 60 days but your terms are 30, you have a collections process problem, not a sales problem.',
					],
				],
			],

			// ================================================================
			// DESIGN & DEV (group 73) — continued
			// ================================================================

			[
				'id'       => 'devdesign_06',
				'group'    => 73,
				'title'    => 'Creating a Service Contract',
				'summary'  => 'Set up a maintenance contract for Halcyon Health Partners with line services.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/contrat/list.php',
						'nav_url'      => '/contrat/list.php',
						'selector'     => '.butAction',
						'title'        => 'What are Contracts For?',
						'body'         => 'Navigate to Commercial → Contracts. Contracts represent long-term service agreements — maintenance, support retainers, SLA-based services. Unlike proposals (one-time), contracts are ongoing and track service delivery over time.',
						'trainer_note' => 'Contracts are especially useful for managed service providers and health/legal/finance sectors with regulated SLAs. A contract links to tickets, tasks, and time to give a full service picture.',
					],
					[
						'path'         => '/contrat/card.php',
						'nav_url'      => '/contrat/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'New Contract — Link the Client',
						'body'         => 'Click "New Contract". Link it to "Halcyon Health Partners". Set the reference to "MAINT-HAL-2026". Set a start date and end date 12 months out. The contract description: "Annual platform support and maintenance — response SLA 4 hours."',
						'trainer_note' => 'Contract references often follow a convention (type-client-year). Consistent naming makes contract listings sortable and auditable.',
					],
					[
						'path'         => '/contrat/card.php',
						'nav_url'      => '/contrat/card.php?action=create',
						'selector'     => '#addlinepanel',
						'title'        => 'Add Contract Lines (Services)',
						'body'         => 'Add a service line: "Monthly platform monitoring" — 1 unit, 350/month. Add a second line: "On-call emergency response" — included (0.00). Contract lines define the scope of what\'s covered. They can be activated independently and tracked for delivery.',
						'trainer_note' => 'Each contract line can be "activated" to start its service period independently. This is useful when phased services start at different dates within the same contract.',
					],
					[
						'path'         => '/contrat/card.php',
						'nav_url'      => '/contrat/list.php',
						'selector'     => null,
						'title'        => 'Activate the Contract',
						'body'         => 'Validate the contract. The status changes to "Active". The contract now appears on Halcyon\'s client card. Any time logged against tickets or tasks for Halcyon can reference this contract. Invoices can be generated directly from it.',
						'trainer_note' => 'When contract end date approaches, Dolibarr can trigger a reminder (configurable in notifications). Renewal workflows are usually triggered 30–60 days before expiry.',
					],
				],
			],

			[
				'id'       => 'devdesign_07',
				'group'    => 73,
				'title'    => 'Logging a Field Intervention',
				'summary'  => 'Record an on-site visit as a field intervention and attach time and materials.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/fichinter/list.php',
						'nav_url'      => '/fichinter/list.php',
						'selector'     => '.butAction',
						'title'        => 'What is a Field Intervention?',
						'body'         => 'Navigate to Services → Interventions. An intervention (fiche d\'intervention) is a formal record of on-site work: a server installation, a repair visit, a site survey. It captures who went, what was done, when, and for how long.',
						'trainer_note' => 'Field interventions are essential in regulated industries (healthcare, utilities, facilities management) where on-site work must be documented and signed off by the client.',
					],
					[
						'path'         => '/fichinter/card.php',
						'nav_url'      => '/fichinter/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'New Intervention',
						'body'         => 'Click "New Intervention". Link to "Vantage Point Logistics". Set the description: "On-site server rack installation and network cabling for the new logistics hub." Set the date to today and your name as the technician.',
						'trainer_note' => 'Interventions can be linked to a contract (service agreement) or a ticket (issue-based work). Linking creates a complete service chain from issue → visit → resolution.',
					],
					[
						'path'         => '/fichinter/card.php',
						'nav_url'      => '/fichinter/card.php?action=create',
						'selector'     => '#addlinepanel',
						'title'        => 'Log Time Lines',
						'body'         => 'Add intervention lines: "Travel to site" — 1h, "Rack installation and cabling" — 4h, "Configuration and testing" — 2h. Each line has a duration and description. The total forms the billable time for this visit.',
						'trainer_note' => 'Intervention lines are time-based (like project tasks) not product-based. They feed into billing differently from products — usually as a time-and-materials invoice.',
					],
					[
						'path'         => '/fichinter/card.php',
						'nav_url'      => '/fichinter/list.php',
						'selector'     => null,
						'title'        => 'Validate the Intervention',
						'body'         => 'Validate the intervention. It\'s now a formal signed document. In practice, the client would sign the printed intervention sheet on-site to confirm the work. A PDF is generated — this becomes your proof of delivery for billing and dispute resolution.',
						'trainer_note' => 'Some businesses require client e-signature on interventions. This is a legal requirement in some sectors. Dolibarr doesn\'t natively have e-sign, but the PDF workflow supports physical signatures.',
					],
				],
			],

			[
				'id'       => 'devdesign_08',
				'group'    => 73,
				'title'    => 'Raising a Purchase Order',
				'summary'  => 'Create a supplier purchase order for equipment needed for a client project.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/fourn/commande/list.php',
						'nav_url'      => '/fourn/commande/list.php',
						'selector'     => '.butAction',
						'title'        => 'Purchase Orders — Controlling Spend',
						'body'         => 'Navigate to Purchases → Supplier Orders. A purchase order (PO) is a formal commitment to a supplier before you receive anything. It locks the price, quantity, and delivery terms. Good PO discipline prevents "invoice surprises" and aids budget control.',
						'trainer_note' => 'POs create the accounts payable estimate — finance can see committed spend before the invoice arrives. This is critical for cash flow forecasting in hardware-heavy projects.',
					],
					[
						'path'         => '/fourn/commande/card.php',
						'nav_url'      => '/fourn/commande/card.php?action=create',
						'selector'     => 'input[name="socid"]',
						'title'        => 'Create the Purchase Order',
						'body'         => 'Click "New Supplier Order". Select "BrightSpark Design Studio" as the supplier. Link to the Vantage Point project (if the field exists). Set the order date to today and add a reference: "PO-VPL-2026-001".',
						'trainer_note' => 'Linking a PO to a project enables project cost tracking. Total project cost = labour (time) + expenses + purchase orders. All three feed the project Finance tab.',
					],
					[
						'path'         => '/fourn/commande/card.php',
						'nav_url'      => '/fourn/commande/card.php?action=create',
						'selector'     => '#addlinepanel',
						'title'        => 'Add the Ordered Items',
						'body'         => 'Add a line from the product catalogue: "UI Design Kit Licence" — 1 unit at 299.00. Add a second line: "24h Rush Design Review" — 1 service at 450.00. These are the items you\'re committing to buy from BrightSpark for the portal project.',
						'trainer_note' => 'PO lines can reference catalogue products (preferred — keeps unit prices consistent) or be free-text lines for one-off purchases. Catalogue items maintain a price history per supplier.',
					],
					[
						'path'         => '/fourn/commande/card.php',
						'nav_url'      => '/fourn/commande/list.php',
						'selector'     => null,
						'title'        => 'Approve and Send',
						'body'         => 'Validate the PO. Click "Send to Supplier" to email a PDF copy to BrightSpark. The PO status changes to "Submitted". When the goods/services arrive, you\'ll receive them against this PO — triggering the supplier invoice match.',
						'trainer_note' => 'The three-way match (PO → reception → invoice) is a core internal control. Deviations (invoice amount doesn\'t match PO) should trigger a review before payment is approved.',
					],
				],
			],

			// ================================================================
			// BASIC (group 70) — continued
			// ================================================================

			[
				'id'       => 'basic_07',
				'group'    => 70,
				'title'    => 'Checking Stock Levels',
				'summary'  => 'Browse the warehouse, view stock movements, and understand how inventory works.',
				'estimate' => '8 min',
				'steps'    => [
					[
						'path'         => '/product/stock/list.php',
						'nav_url'      => '/product/stock/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Stock by Product',
						'body'         => 'Navigate to Products → Stock. This view shows every product and its current stock level across all warehouses. Products with a stock level below the minimum trigger a low-stock warning. Your procurement team uses this to plan reorder runs.',
						'trainer_note' => 'Stock movements in the training data reflect sales (stock decreases) and supplier reception (stock increases). Show how the current levels result from the transactions already in the system.',
					],
					[
						'path'         => '/product/stock/entrepot.php',
						'nav_url'      => '/product/stock/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Browsing a Warehouse',
						'body'         => 'Navigate to Products → Warehouses. The training data includes one warehouse. Open it to see all products stored there with their current quantities. A warehouse in Dolibarr can be a physical location, a storage zone, or even a virtual "consignment" location.',
						'trainer_note' => 'Multi-warehouse setups are common in distribution businesses. Each warehouse tracks stock independently — inter-warehouse transfers are recorded as stock movements.',
					],
					[
						'path'         => '/product/stock/mouvement.php',
						'nav_url'      => '/product/stock/mouvement.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Stock Movement History',
						'body'         => 'Navigate to Products → Movements. Every stock change is logged here: sales reductions, purchase additions, manual corrections. This is the audit trail of your inventory — you can always trace why a stock level is what it is.',
						'trainer_note' => 'Manual stock corrections (reason code "inventory") are how physical counts are reconciled with the system. Ask trainees: what happens if you discover 10 units are missing during a stocktake?',
					],
					[
						'path'         => '/product/card.php',
						'nav_url'      => '/product/card.php?id=50',
						'selector'     => '.tabBar',
						'title'        => 'Stock Tab on a Product',
						'body'         => 'Open a product and click the "Stock" tab. You\'ll see the per-warehouse breakdown and the full movement history for this specific item. This is the fastest way to answer "where is product X and how many do we have?" without leaving the product record.',
						'trainer_note' => 'The "virtual stock" figure accounts for pending orders (stock committed to customers but not yet shipped). Real stock minus virtual stock = truly available stock.',
					],
				],
			],

			[
				'id'       => 'basic_08',
				'group'    => 70,
				'title'    => 'User Accounts and Group Overview',
				'summary'  => 'Understand how Dolibarr user accounts and permission groups control access.',
				'estimate' => '8 min',
				'steps'    => [
					[
						'path'         => '/user/list.php',
						'nav_url'      => '/user/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'The Users List',
						'body'         => 'Navigate to Setup → Users (or HR → Users depending on your menu). Every person who logs into Dolibarr has a user record here. User accounts are separate from contact records — a user can log in; a contact cannot.',
						'trainer_note' => 'The training dataset includes seeded users to simulate a realistic team. Walk trainees through the user list and map them to the training scenario org chart.',
					],
					[
						'path'         => '/user/card.php',
						'nav_url'      => '/user/list.php',
						'selector'     => '.tabBar',
						'title'        => 'Opening a User Card',
						'body'         => 'Click any user (not yourself) to open their card. The "Permissions" tab shows which rights have been granted. The "Groups" tab shows which groups they belong to. Rights can be granted directly or inherited from groups.',
						'trainer_note' => 'Best practice: grant rights via groups, not individually. Individual rights create a maintenance nightmare when roles change. "Sales team" group = all sales rights assigned in one place.',
					],
					[
						'path'         => '/user/group/list.php',
						'nav_url'      => '/user/group/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Permission Groups',
						'body'         => 'Navigate to Setup → Groups. Groups bundle rights together. When a new team member joins, you add them to the relevant group rather than configuring individual rights. The training dataset includes role-based groups: Basic, Sales, Marketing, Design & Dev.',
						'trainer_note' => 'The four training groups in this system are simplified. Real deployments typically have 8–15 groups for different roles (junior sales, senior sales, manager, etc.).',
					],
				],
			],

			// ================================================================
			// SALES (group 71) — continued
			// ================================================================

			[
				'id'       => 'sales_09',
				'group'    => 71,
				'title'    => 'Creating a Delivery Order',
				'summary'  => 'Ship goods against a customer order and record the delivery.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/commande/card.php',
						'nav_url'      => '/commande/list.php',
						'selector'     => '.butAction',
						'title'        => 'Generate Shipping from an Order',
						'body'         => 'Open a validated customer order that includes physical products (check the training orders for Pinnacle Goods or Thornton Hardware). Click "Create a Shipping". A delivery order (expédition) documents exactly what was sent, when, and by whom.',
						'trainer_note' => 'Delivery orders are required in many B2B and regulated industries as proof of delivery. They can be signed by the recipient and form part of the document chain for payment.',
					],
					[
						'path'         => '/expedition/card.php',
						'nav_url'      => '/commande/list.php',
						'selector'     => 'input[name="date_delivery"]',
						'title'        => 'Set Delivery Details',
						'body'         => 'Set the shipping date to today and the tracking number if you have one. Review the quantities — Dolibarr pre-fills from the order. If you\'re doing a partial delivery, reduce the quantity on any lines you\'re not shipping yet.',
						'trainer_note' => 'Partial deliveries are common in product businesses. Each partial delivery generates its own delivery order. The order stays open until all items are delivered or the remaining balance is manually closed.',
					],
					[
						'path'         => '/expedition/card.php',
						'nav_url'      => '/expedition/list.php',
						'selector'     => '.butAction',
						'title'        => 'Validate the Shipping',
						'body'         => 'Validate the delivery order. Stock levels for the shipped products automatically decrease. The customer order status updates to reflect delivery. A PDF delivery note is generated — print or email this to the customer as their receipt.',
						'trainer_note' => 'The stock decrease happens at shipping validation, not at order creation. This is the correct accounting treatment — stock is "committed" (virtual) at order, "consumed" at shipment.',
					],
					[
						'path'         => '/expedition/card.php',
						'nav_url'      => '/commande/list.php',
						'selector'     => null,
						'title'        => 'Order and Delivery Linked',
						'body'         => 'Navigate back to the customer order. The delivery order appears in the linked documents section. You can now invoice from this order (the delivery provides proof that services/goods were rendered). The complete chain: proposal → order → delivery → invoice.',
						'trainer_note' => 'Some businesses invoice before delivery; others after. Delivery-first invoicing is more defensible in disputes — you have documentary proof of delivery before requesting payment.',
					],
				],
			],

			[
				'id'       => 'sales_10',
				'group'    => 71,
				'title'    => 'Bank Account Reconciliation',
				'summary'  => 'Match bank statement transactions to Dolibarr payment records.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/compta/bank/list.php',
						'nav_url'      => '/compta/bank/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Bank Accounts in Dolibarr',
						'body'         => 'Navigate to Banking → Bank Accounts. Dolibarr maintains a record of your bank accounts and their transactions — customer payments received, supplier payments made, and manual entries. The balance here should eventually match your actual bank statement.',
						'trainer_note' => 'Dolibarr bank accounts are "book accounts" — the recorded balance in the software. Reconciliation is the process of confirming that Dolibarr\'s records match the bank\'s statement.',
					],
					[
						'path'         => '/compta/bank/bankentries_list.php',
						'nav_url'      => '/compta/bank/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Bank Transaction Ledger',
						'body'         => 'Open the main training bank account. You\'ll see a list of all transactions — debits and credits. Each line shows the date, description, amount, and whether it\'s been reconciled. Unreconciled entries have no tick in the "R" column.',
						'trainer_note' => 'The "R" column (reconciliation) is the key. Ticking an entry confirms it appeared on your actual bank statement. Unticked = in Dolibarr but not yet verified against the bank.',
					],
					[
						'path'         => '/compta/bank/bankentries_list.php',
						'nav_url'      => '/compta/bank/list.php',
						'selector'     => null,
						'title'        => 'Reconcile an Entry',
						'body'         => 'Find an entry that corresponds to a payment you\'ve confirmed in your bank statement. Click the reconcile checkbox (or the date field) to mark it as reconciled. Work through all entries for the current period — the goal is a clean reconciliation with no unexplained differences.',
						'trainer_note' => 'Unexplained differences after reconciliation indicate: (1) a transaction in the bank not yet entered in Dolibarr, (2) a Dolibarr entry not yet cleared by the bank, or (3) a data entry error.',
					],
					[
						'path'         => '/compta/bank/bankentries_list.php',
						'nav_url'      => '/compta/bank/list.php',
						'selector'     => null,
						'title'        => 'Running Balance Check',
						'body'         => 'After reconciling, compare Dolibarr\'s closing balance to your bank statement\'s closing balance. They should match. If they don\'t, the difference amount and sign will help you identify the missing entry. Monthly reconciliation prevents small errors from compounding.',
						'trainer_note' => 'Monthly reconciliation is a fundamental financial control. It catches fraud, errors, and missing entries before year-end when corrections become expensive.',
					],
				],
			],

			// ================================================================
			// MARKETING (group 72) — continued
			// ================================================================

			[
				'id'       => 'marketing_07',
				'group'    => 72,
				'title'    => 'Sending a Mass Email Campaign',
				'summary'  => 'Build a targeted mailing list and send a campaign to a contact segment.',
				'estimate' => '12 min',
				'steps'    => [
					[
						'path'         => '/comm/mailing/list.php',
						'nav_url'      => '/comm/mailing/list.php',
						'selector'     => '.butAction',
						'title'        => 'Email Campaigns in Dolibarr',
						'body'         => 'Navigate to Tools → Mass Emailing (or Marketing → Mailings). Dolibarr\'s built-in mailing module lets you send targeted campaigns to filtered lists of contacts or third parties — no external tool required for smaller campaigns.',
						'trainer_note' => 'For large campaigns (10k+), a dedicated tool (Mailchimp, Brevo) is usually better. But for targeted segments of 50–500 clients, Dolibarr\'s native mailing is fast and keeps the data in-system.',
					],
					[
						'path'         => '/comm/mailing/card.php',
						'nav_url'      => '/comm/mailing/card.php?action=create',
						'selector'     => 'input[name="title"]',
						'title'        => 'Create a New Mailing',
						'body'         => 'Click "New Mailing". Set the title: "Q2 Logistics Client Update". Set the email subject: "Important updates to your supply chain services — Q2 2026". From address: your company email. This is the campaign shell before you add recipients and content.',
						'trainer_note' => 'Mailing title is internal only; subject line is what recipients see. Subject lines should be specific and relevant — generic subjects get ignored and increase unsubscribe rates.',
					],
					[
						'path'         => '/comm/mailing/card.php',
						'nav_url'      => '/comm/mailing/card.php',
						'selector'     => '.tabBar',
						'title'        => 'Add Recipients from a Segment',
						'body'         => 'Go to the "Recipients" tab. Click "Add Recipients". Filter contacts linked to third parties in the "Logistics" category. Add all of them. The recipient count updates. Review the list — remove any contacts who have opted out or are marked as "Do not contact".',
						'trainer_note' => 'Always check the "no_email" flag before sending. Dolibarr won\'t prevent you from adding opted-out contacts to a list — the responsibility is yours. GDPR consent management matters here.',
					],
					[
						'path'         => '/comm/mailing/card.php',
						'nav_url'      => '/comm/mailing/card.php',
						'selector'     => null,
						'title'        => 'Write the Content and Send',
						'body'         => 'Go to the "Content" tab. Write your email body using the editor. You can use substitution variables like {FIRSTNAME}, {LASTNAME}, {COMPANY} to personalise each email. Validate the mailing, then click "Send". Dolibarr processes the queue and sends to all recipients.',
						'trainer_note' => 'Send a test email to yourself first (there\'s usually a "Test" button). Check rendering on mobile — most business emails are read on phones. Always include an unsubscribe mechanism.',
					],
				],
			],

			[
				'id'       => 'marketing_08',
				'group'    => 72,
				'title'    => 'Tracking Third Party Memberships',
				'summary'  => 'Enrol a contact in a membership subscription and track renewal.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/adherents/list.php',
						'nav_url'      => '/adherents/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'The Membership Module',
						'body'         => 'Navigate to Members (Adherents). This module tracks subscriptions, club memberships, association fees, or loyalty programmes — any ongoing relationship where someone pays a recurring fee for a status or benefit. It\'s separate from invoicing but can link to it.',
						'trainer_note' => 'The Adherents module is used heavily by associations, clubs, and subscription-based services. If your business has a partner programme or tiered access model, this module handles the administration.',
					],
					[
						'path'         => '/adherents/card.php',
						'nav_url'      => '/adherents/card.php?action=create',
						'selector'     => 'select[name="typeid"]',
						'title'        => 'Create a Membership Record',
						'body'         => 'Click "New Member". Select the membership type (e.g. "Partner Network" or "Premium Access"). Link to an existing contact or fill in the details. Set the start date and the subscription fee. You can link the fee directly to an invoice.',
						'trainer_note' => 'Membership types are configured in Setup → Dictionaries → Membership Types. Each type can have a default fee, duration, and renewal behaviour.',
					],
					[
						'path'         => '/adherents/card.php',
						'nav_url'      => '/adherents/list.php',
						'selector'     => null,
						'title'        => 'Renewal Tracking',
						'body'         => 'Save the membership. The expiry date and renewal status appear on the member card. Dolibarr can alert you when memberships approach expiry — go to the member list and filter by "Expiring this month" to see who needs renewal outreach.',
						'trainer_note' => 'Membership renewals are a reliable revenue stream if followed up proactively. The filter-by-expiry list is this module\'s killer feature for retention management.',
					],
				],
			],

			// ================================================================
			// DESIGN & DEV (group 73) — continued
			// ================================================================

			[
				'id'       => 'devdesign_09',
				'group'    => 73,
				'title'    => 'Receiving Goods from a Supplier',
				'summary'  => 'Record a goods reception against a purchase order and update stock.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/reception/list.php',
						'nav_url'      => '/reception/list.php',
						'selector'     => '.butAction',
						'title'        => 'What is a Goods Reception?',
						'body'         => 'Navigate to Purchases → Receptions. A reception records physical goods arriving from a supplier. It\'s linked to a purchase order and triggers a stock increase. Without recording reception, your PO stays "pending" and stock levels don\'t update.',
						'trainer_note' => 'The PO → reception → invoice three-way match is the gold standard of purchase control. Many financial audits specifically check for this chain — missing links indicate control weaknesses.',
					],
					[
						'path'         => '/reception/card.php',
						'nav_url'      => '/fourn/commande/list.php',
						'selector'     => '.butAction',
						'title'        => 'Create Reception from the PO',
						'body'         => 'Navigate to Purchases → Supplier Orders. Open the BrightSpark PO you created earlier. Click "Create Reception". Dolibarr pre-fills the reception with the ordered items and quantities. If the delivery is partial, adjust quantities down to what actually arrived.',
						'trainer_note' => 'Partial reception is common — suppliers ship in batches. Each partial reception creates its own record linked to the same PO. The PO tracks outstanding quantities automatically.',
					],
					[
						'path'         => '/reception/card.php',
						'nav_url'      => '/reception/list.php',
						'selector'     => 'select[name="fk_entrepot"]',
						'title'        => 'Select the Destination Warehouse',
						'body'         => 'Set the reception date and select the warehouse where goods will be stored. Click "Validate Reception". Dolibarr records the incoming stock movement and increases the product quantities in the chosen warehouse.',
						'trainer_note' => 'If goods are received damaged, record the actual received quantity (less the damaged items) and note the discrepancy. Follow up with the supplier for a credit note for the missing/damaged items.',
					],
					[
						'path'         => '/product/stock/list.php',
						'nav_url'      => '/product/stock/list.php',
						'selector'     => 'table.tagtable',
						'title'        => 'Verify the Stock Increase',
						'body'         => 'Navigate to Products → Stock. Find the items you just received — the quantities should have increased. You can also check the movement history (Products → Movements) and see the reception entry with the PO reference attached.',
						'trainer_note' => 'Stock valuation method (FIFO, average cost, etc.) affects how the incoming stock value is calculated. This is usually configured at the account settings level and visible in the Finance tab.',
					],
				],
			],

			[
				'id'       => 'devdesign_10',
				'group'    => 73,
				'title'    => 'Managing Custom Extra Fields',
				'summary'  => 'Add a custom field to third parties and see how it appears throughout the module.',
				'estimate' => '10 min',
				'steps'    => [
					[
						'path'         => '/admin/extrafields.php',
						'nav_url'      => '/admin/extrafields.php?attrname=societe',
						'selector'     => '.butAction',
						'title'        => 'What are Extra Fields?',
						'body'         => 'Navigate to Setup → Extra Fields. Dolibarr lets you add custom fields to most objects — third parties, contacts, products, invoices. These appear directly on the record card and are searchable in lists. No coding required.',
						'trainer_note' => 'Extra fields (extrafields) are stored in dedicated tables (llx_societe_extrafields, etc.). They\'re indexed and searchable — not just cosmetic labels. Training data includes several pre-seeded extra fields.',
					],
					[
						'path'         => '/admin/extrafields.php',
						'nav_url'      => '/admin/extrafields.php?attrname=societe',
						'selector'     => 'select[name="type"]',
						'title'        => 'Add a Text Extra Field',
						'body'         => 'On the Third Parties extra fields page, click "Add". Set the attribute code to "preferred_contact_time", label to "Preferred Contact Time", type to "varchar (string)". This field will let sales reps record when each client prefers to be called.',
						'trainer_note' => 'Choose field types carefully — varchar for short text, text for long notes, integer for numbers, date for dates, select for dropdowns. Wrong type makes reporting and searching harder.',
					],
					[
						'path'         => '/societe/card.php',
						'nav_url'      => '/societe/card.php?socid=50',
						'selector'     => null,
						'title'        => 'See it on the Record',
						'body'         => 'Open any client card. Your new "Preferred Contact Time" field appears in the extra fields section (usually at the bottom). Click "Edit" and enter "9–11am (avoid Fridays)". Save. This custom data is now searchable, exportable, and appears on every third party record.',
						'trainer_note' => 'Encourage teams to agree on a set of extra fields before going live. Too many unused fields clutter the interface. Start with 3–5 high-value fields and expand as needed.',
					],
					[
						'path'         => '/societe/list.php',
						'nav_url'      => '/societe/list.php',
						'selector'     => '.search-component-line',
						'title'        => 'Search and Export Custom Fields',
						'body'         => 'Navigate to the Third Parties list. Extra field columns can be added to the list view via the "Configure list columns" option. Once visible, you can filter by your custom field value — e.g. show all clients who prefer morning calls. This combination of customisation + search is extremely powerful.',
						'trainer_note' => 'Custom fields in exports become columns in the CSV. This means your business\'s unique data model travels with every export — no need to add columns manually in Excel.',
					],
				],
			],

		]; // end catalog
	}
}
