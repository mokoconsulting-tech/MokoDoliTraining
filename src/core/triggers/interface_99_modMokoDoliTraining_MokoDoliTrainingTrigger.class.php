<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Triggers
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/core/triggers/interface_99_modMokoDoliTraining_MokoDoliTrainingTrigger.class.php
 * VERSION:  development
 * BRIEF:    Auto-tracks every record created while training data is seeded.
 *           Also logs USER_SETPASSWORD / USER_MODIFY / USER_DELETE safety notices.
 * NOTE:     Priority 99 ensures this fires last, after all core triggers.
 *           Tracking only runs when MOKODOLITRAINING_SEEDED = 1.
 */

class interface_99_modMokoDoliTraining_MokoDoliTrainingTrigger extends CommonTrigger
{
	public function __construct($db)
	{
		$this->db          = $db;
		$this->name        = 'MOKODOLITRAINING_TRIGGER';
		$this->family      = 'demo';
		$this->description = 'MokoDoliTraining: auto-tracks created records and audit hooks.';
		$this->version     = 'development';
		$this->picto       = 'technic';
	}

	public function getName(): string    { return $this->name;        }
	public function getDesc(): string    { return $this->description; }
	public function getVersion(): string { return $this->version;     }

	// ── CREATE event map ──────────────────────────────────────────────────────
	//
	// Maps Dolibarr trigger action name → tracking config:
	//   'table'    : llx_-prefixed main table to track the parent ID in
	//   'children' : child tables to also track automatically at create time
	//                Each entry: [child_table, relationship]
	//                  relationship='rowid'  → child shares the same rowid as parent
	//                                         (extrafields pattern: track [$parent_id])
	//                  relationship='fk_X'  → SELECT rowid FROM child WHERE fk_X = $parent_id
	//
	// Add new events here as additional training exercises are introduced.

	private const ACTION_MAP = [
		// ── Third parties ─────────────────────────────────────────────────────
		'THIRDPARTY_CREATE' => ['table' => 'llx_societe', 'children' => [
			['llx_societe_extrafields', 'rowid'],
		]],

		// ── Contacts ──────────────────────────────────────────────────────────
		'CONTACT_CREATE' => ['table' => 'llx_socpeople', 'children' => [
			['llx_socpeople_extrafields', 'rowid'],
		]],

		// ── Products / services ───────────────────────────────────────────────
		'PRODUCT_CREATE' => ['table' => 'llx_product', 'children' => [
			['llx_product_extrafields', 'rowid'],
		]],

		// ── Categories ────────────────────────────────────────────────────────
		'CATEGORY_CREATE' => ['table' => 'llx_categorie', 'children' => []],

		// ── Proposals ─────────────────────────────────────────────────────────
		'PROPAL_CREATE' => ['table' => 'llx_propal', 'children' => [
			['llx_propaldet',         'fk_propal'],
			['llx_propal_extrafields', 'rowid'],
		]],

		// ── Customer orders ───────────────────────────────────────────────────
		'ORDER_CREATE' => ['table' => 'llx_commande', 'children' => [
			['llx_commandedet', 'fk_commande'],
		]],

		// ── Customer invoices ─────────────────────────────────────────────────
		'BILL_CREATE' => ['table' => 'llx_facture', 'children' => [
			['llx_facturedet',          'fk_facture'],
			['llx_facture_extrafields', 'rowid'],
		]],

		// ── Recurring invoice templates ───────────────────────────────────────
		'FACTURE_REC_CREATE' => ['table' => 'llx_facture_rec', 'children' => [
			['llx_facturedet_rec', 'fk_facture'],
		]],

		// ── Customer payments ─────────────────────────────────────────────────
		'PAYMENT_CUSTOMER_CREATE' => ['table' => 'llx_paiement', 'children' => [
			['llx_paiement_facture', 'fk_paiement'],
		]],

		// ── Supplier orders ───────────────────────────────────────────────────
		'SUPPLIER_ORDER_CREATE' => ['table' => 'llx_commande_fournisseur', 'children' => [
			['llx_commande_fournisseurdet', 'fk_commande'],
		]],

		// ── Supplier invoices ─────────────────────────────────────────────────
		'SUPPLIER_INVOICE_CREATE' => ['table' => 'llx_facture_fourn', 'children' => []],

		// ── Field interventions ───────────────────────────────────────────────
		'FICHINTER_CREATE' => ['table' => 'llx_fichinter', 'children' => [
			['llx_fichinterdet', 'fk_fichinter'],
		]],

		// ── Contracts ─────────────────────────────────────────────────────────
		'CONTRACT_CREATE' => ['table' => 'llx_contrat', 'children' => [
			['llx_contratdet',         'fk_contrat'],
			['llx_contrat_extrafields', 'rowid'],
		]],

		// ── Tickets / support ─────────────────────────────────────────────────
		'TICKET_CREATE' => ['table' => 'llx_ticket', 'children' => [
			['llx_ticket_extrafields', 'rowid'],
		]],

		// ── Expense reports ───────────────────────────────────────────────────
		'EXPENSEREPORT_CREATE' => ['table' => 'llx_expensereport', 'children' => [
			['llx_expensereport_det', 'fk_expensereport'],
		]],

		// ── CRM / agenda events ───────────────────────────────────────────────
		'ACTION_CREATE' => ['table' => 'llx_actioncomm', 'children' => []],

		// ── Projects & tasks ──────────────────────────────────────────────────
		'PROJECT_CREATE' => ['table' => 'llx_projet', 'children' => [
			['llx_projet_extrafields', 'rowid'],
		]],
		'TASK_CREATE' => ['table' => 'llx_projet_task', 'children' => []],

		// ── Users & groups ────────────────────────────────────────────────────
		'USER_CREATE'  => ['table' => 'llx_user',      'children' => [
			['llx_user_extrafields', 'rowid'],
		]],
		'GROUP_CREATE' => ['table' => 'llx_usergroup', 'children' => []],

		// ── Bank accounts & warehouses ────────────────────────────────────────
		'BANK_ACCOUNT_ADD'  => ['table' => 'llx_bank_account', 'children' => []],
		'WAREHOUSE_CREATE'  => ['table' => 'llx_entrepot',     'children' => []],
	];

	// ── User-creation actions tracked to user_track ───────────────────────────
	// USER_CREATE  : new account — always log while seeded (captures login + who created it)
	// USER_SETPASSWORD: password set — log login+instructor for credential reference
	// source='module'   if created through the training module's create_trainee UI
	// source='external' if created elsewhere (trigger catches it as a warning)

	private const USER_TRACK_ACTIONS = ['USER_CREATE', 'USER_SETPASSWORD'];

	// ── Safety-notice actions ─────────────────────────────────────────────────
	// Logged to the audit trail regardless of whether tracking is active.

	private const SAFETY_ACTIONS = [
		'USER_SETPASSWORD',
		'USER_MODIFY',
		'USER_DELETE',
	];

	/**
	 * Fired by Dolibarr when tracked events occur.
	 *
	 * @param string $action  Dolibarr action constant
	 * @param object $object  Object triggering the event
	 * @param object $user    Current user
	 * @param object $langs   Lang object
	 * @param object $conf    Config object
	 * @return int 0 = success, negative = error
	 */
	public function runTrigger($action, $object, $user, $langs, $conf): int
	{
		if (empty($conf->mokodolitraining->enabled)) return 0;

		$entity = (int) $conf->entity;

		// ── Safety audit (always, regardless of seeded state) ─────────────────
		if (in_array($action, self::SAFETY_ACTIONS, true)) {
			dol_include_once('/mokodolitraining/class/MokoDoliTrainingAudit.class.php');
			$audit     = new MokoDoliTrainingAudit($this->db);
			$target_id = (int) ($object->id ?? $object->rowid ?? 0);
			$audit->log(
				fk_user:       (int) $user->id,
				action:        'integrity_check',
				status:        'ok',
				rows_affected: 0,
				note:          $action . ' on user rowid=' . $target_id . ' while training data is seeded',
				entity:        $entity
			);
		}

		// ── Username tracking (only when seeded) ─────────────────────────────
		if (getDolGlobalString('MOKODOLITRAINING_SEEDED') && in_array($action, self::USER_TRACK_ACTIONS, true)) {
			$login    = $object->login ?? '';
			$uid_subj = (int) ($object->id ?? $object->rowid ?? 0);
			// Session flag set by MokoDoliTrainingClass::createTrainee() to mark
			// this creation as coming through the module's own UI.
			$source   = !empty($_SESSION['mdt_creating_trainee']) ? 'module' : 'external';
			$fk_class = !empty($_SESSION['mdt_creating_trainee']) ? (int) $_SESSION['mdt_creating_trainee'] : null;

			if ($login !== '' && $uid_subj > 0) {
				$mtbl = MAIN_DB_PREFIX . 'mokodolitraining_user_track';
				$esc  = $this->db->escape($login);
				$fc   = ($fk_class !== null) ? (int) $fk_class : 'NULL';
				$src  = $this->db->escape($source);
				$this->db->query(
					"INSERT INTO `{$mtbl}` (entity, fk_user, login, set_by, datec, fk_class, source)"
					. " VALUES ({$entity}, {$uid_subj}, '{$esc}', " . (int) $user->id
					. ", NOW(), {$fc}, '{$src}')"
				);
			}
		}

		// ── Auto-track CREATE events (only when seeded) ───────────────────────
		if (!getDolGlobalString('MOKODOLITRAINING_SEEDED')) return 0;
		if (!isset(self::ACTION_MAP[$action])) return 0;

		$id = (int) ($object->id ?? $object->rowid ?? 0);
		if ($id <= 0) return 0;

		$map = self::ACTION_MAP[$action];

		dol_include_once('/mokodolitraining/class/MokoDoliTrainingSeed.class.php');
		$seeder = new MokoDoliTrainingSeed($this->db);

		// Track the primary record
		$seeder->track($map['table'], [$id], $entity);

		// Track child rows created alongside the parent
		foreach ($map['children'] as [$child_table, $rel]) {
			if ($rel === 'rowid') {
				// Extrafields pattern: child shares the same rowid as the parent
				$seeder->track($child_table, [$id], $entity);
			} else {
				// FK pattern: SELECT rowid FROM child WHERE fk_col = $id
				$prefix_tbl = (MAIN_DB_PREFIX !== 'llx_')
					? preg_replace('/^llx_/', MAIN_DB_PREFIX, $child_table)
					: $child_table;
				$sql = "SELECT rowid FROM `{$prefix_tbl}` WHERE `{$rel}` = " . (int) $id;
				$res = $this->db->query($sql);
				if ($res) {
					$child_ids = [];
					while ($row = $this->db->fetch_object($res)) {
						$child_ids[] = (int) $row->rowid;
					}
					if ($child_ids) {
						$seeder->track($child_table, $child_ids, $entity);
					}
				}
			}
		}

		return 0;
	}
}
