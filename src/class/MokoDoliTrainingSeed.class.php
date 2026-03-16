<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Seed
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/class/MokoDoliTrainingSeed.class.php
 * VERSION:  development
 * BRIEF:    Training data seeder: inserts training rows, tracks IDs in
 *           llx_mokodolitraining_manifest, and provides programmatic reset.
 *
 * NOTE: All seed data is injected programmatically via seedStatic() and seedOrders().
 *       No SQL files are used for seeding.
 */

class MokoDoliTrainingSeed
{
	private $db;

	const TABLE_PK = [
		'llx_actioncomm'        => 'id',
		'llx_categorie_product' => 'fk_categorie',
		'llx_categorie_societe' => 'fk_categorie',
	];

	const DELETE_ORDER = [
		'llx_element_time',
		'llx_element_element',
		'llx_actioncomm',
		'llx_ticket_extrafields',
		'llx_ticket',
		'llx_facture_extrafields',
		'llx_facturedet',
		'llx_paiement_facture',
		'llx_paiement',
		'llx_facture',
		'llx_facturedet_rec',
		'llx_facture_rec',
		'llx_propaldet',
		'llx_propal_extrafields',
		'llx_propal',
		'llx_commandedet',
		'llx_commande',
		'llx_contratdet',
		'llx_contrat_extrafields',
		'llx_contrat',
		'llx_fichinterdet',
		'llx_fichinter',
		'llx_expensereport_det',
		'llx_expensereport',
		'llx_commande_fournisseurdet',
		'llx_commande_fournisseur',
		'llx_facture_fourn',
		'llx_product_fournisseur_price',
		'llx_stock_mouvement',
		'llx_product_stock',
		'llx_entrepot',
		'llx_bank_account',
		'llx_projet_task',
		'llx_projet_extrafields',
		'llx_projet',
		'llx_categorie_product',
		'llx_categorie_societe',
		'llx_categorie',
		'llx_societe_contacts',
		'llx_socpeople_extrafields',
		'llx_socpeople',
		'llx_societe_extrafields',
		'llx_societe',
		'llx_product_extrafields',
		'llx_product',
		'llx_usergroup_rights',
		'llx_usergroup_user',
		'llx_usergroup',
		'llx_user_extrafields',
		'llx_user',
		'llx_mailing',
		'llx_adherent',
		'llx_adherent_type',
		'llx_extrafields',
	];

	const STATIC_MANIFEST = [
		'llx_element_time'                => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66],
		'llx_element_element'             => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65],
		'llx_actioncomm'                  => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70],
		'llx_ticket_extrafields'          => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
		'llx_ticket'                      => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
		'llx_facture_extrafields'         => [50, 51, 52, 53, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71],
		'llx_facturedet'                  => [50, 51, 52, 53, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71],
		'llx_paiement_facture'            => [50, 51, 60, 61, 62, 63, 64, 65],
		'llx_paiement'                    => [50, 51, 60, 61, 62, 63, 64, 65],
		'llx_facture'                     => [50, 51, 52, 53, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71],
		'llx_facturedet_rec'              => [50, 51, 52, 53],
		'llx_facture_rec'                 => [50, 51, 52],
		'llx_propaldet'                   => [50, 51, 52, 53, 54, 55, 56, 57, 58, 60, 61, 70, 71, 72, 73, 74, 75, 76, 80, 81, 82, 83, 84, 85, 86, 100, 101, 102, 103, 104, 105, 106, 107, 108, 110, 111, 112, 113, 114, 115, 116, 117, 120, 121, 122, 123, 124, 125, 126, 127, 130, 131, 132, 133, 134, 135, 136, 140, 150, 151, 152, 153, 154, 155, 156, 160, 161, 162, 163, 164],
		'llx_propal_extrafields'          => [50, 51, 52, 53, 60, 61, 62, 63, 64, 65, 66, 67],
		'llx_propal'                      => [50, 51, 52, 53, 60, 61, 62, 63, 64, 65, 66, 67],
		'llx_contratdet'                  => [50, 51, 52, 53, 60, 61],
		'llx_contrat_extrafields'         => [50, 51, 60, 61],
		'llx_contrat'                     => [50, 51, 60, 61],
		'llx_fichinterdet'                => [50, 51, 52],
		'llx_fichinter'                   => [50, 51, 52],
		'llx_expensereport_det'           => [50, 51, 52, 53, 54, 55, 56],
		'llx_expensereport'               => [50, 51],
		'llx_commande_fournisseurdet'     => [50],
		'llx_commande_fournisseur'        => [50],
		'llx_facture_fourn'               => [50, 51],
		'llx_product_fournisseur_price'   => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64],
		'llx_stock_mouvement'             => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61],
		'llx_product_stock'               => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64],
		'llx_entrepot'                    => [50],
		'llx_bank_account'                => [50],
		'llx_projet_task'                 => [100, 101, 102, 103, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 130, 131, 132, 133, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 300, 301, 302, 303, 304, 310, 311, 312, 313, 314, 320, 321, 322, 323, 324, 330, 331, 332, 333, 340, 341, 342],
		'llx_projet_extrafields'          => [50, 51, 52, 53, 60, 61, 62, 63, 64],
		'llx_projet'                      => [50, 51, 52, 53, 60, 61, 62, 63, 64],
		'llx_categorie_product'           => [55, 56, 57, 58, 59],
		'llx_categorie_societe'           => [50, 51, 52, 53],
		'llx_categorie'                   => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62],
		'llx_societe_contacts'            => [100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117],
		'llx_socpeople_extrafields'       => [90, 91, 92, 93, 94, 95, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117],
		'llx_socpeople'                   => [50, 51, 52, 53, 54, 55, 60, 61, 62, 63, 64, 65, 90, 91, 92, 93, 94, 95, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117],
		'llx_societe_extrafields'         => [50, 51, 52, 53, 54, 55, 60, 61, 62, 63, 64, 65, 90, 91, 92, 93, 94, 95],
		'llx_societe'                     => [50, 51, 52, 53, 54, 55, 60, 61, 62, 63, 64, 65, 90, 91, 92, 93, 94, 95],
		'llx_product_extrafields'         => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94],
		'llx_product'                     => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94],
		'llx_usergroup_rights'            => [50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105],
		'llx_usergroup_user'              => [50, 51, 52, 53, 54, 55, 56, 60, 61, 62, 63, 64, 65, 66],
		'llx_usergroup'                   => [50, 51, 52, 53, 70, 71, 72, 73],
		'llx_user_extrafields'            => [1, 50, 51, 52, 53, 54, 55, 60, 61, 62, 63, 64, 65, 66],
		'llx_user'                        => [50, 51, 52, 53, 54, 55, 60, 61, 62, 63, 64, 65, 66],
		'llx_extrafields'                 => [200, 201, 202, 203, 204, 205],
		'llx_adherent'                    => [50, 51, 52, 53],
		'llx_adherent_type'               => [50, 51],
		'llx_mailing'                     => [50, 51],
	];

	public function __construct($db)
	{
		$this->db = $db;
	}

	// ── ID detection ──────────────────────────────────────────────────────────

	public function nextId(string $bare_table, int $floor = 50): int
	{
		$tbl = MAIN_DB_PREFIX . preg_replace('/[^a-z0-9_]/i', '', $bare_table);
		$sql = "SELECT GREATEST({$floor}, COALESCE(MAX(rowid), " . ($floor - 1) . ") + 1) AS nid FROM `{$tbl}`";
		$res = $this->db->query($sql);
		if (!$res) return $floor;
		$row = $this->db->fetch_object($res);
		return (int) $row->nid;
	}

	// ── DB Manifest ───────────────────────────────────────────────────────────

	public function track(string $table, array $ids, int $entity = 1): void
	{
		if (empty($ids)) return;
		$store_name = (MAIN_DB_PREFIX !== 'llx_')
			? preg_replace('/^' . preg_quote(MAIN_DB_PREFIX, '/') . '/', 'llx_', $table)
			: $table;
		$mtbl = MAIN_DB_PREFIX . 'mokodolitraining_manifest';
		foreach ($ids as $id) {
			$sql = "INSERT IGNORE INTO `{$mtbl}` (entity, table_name, record_id)"
				. " VALUES (" . (int) $entity . ", '" . $this->db->escape($store_name) . "', " . (int) $id . ")";
			$this->db->query($sql);
		}
	}

	public function getManifest(int $entity = 1): array
	{
		$mtbl = MAIN_DB_PREFIX . 'mokodolitraining_manifest';
		$sql  = "SELECT table_name, record_id FROM `{$mtbl}` WHERE entity = " . (int) $entity
			  . " ORDER BY table_name, record_id";
		$res  = $this->db->query($sql);
		if (!$res) return [];
		$out = [];
		while ($row = $this->db->fetch_object($res)) {
			$tbl = (MAIN_DB_PREFIX !== 'llx_')
				? preg_replace('/^llx_/', MAIN_DB_PREFIX, $row->table_name)
				: $row->table_name;
			$out[$tbl][] = (int) $row->record_id;
		}
		return $out;
	}

	public function clearManifest(int $entity = 1): bool
	{
		$mtbl = MAIN_DB_PREFIX . 'mokodolitraining_manifest';
		$sql  = "DELETE FROM `{$mtbl}` WHERE entity = " . (int) $entity;
		return (bool) $this->db->query($sql);
	}

	public function getManifestSummary(int $entity = 1): array
	{
		$m = $this->getManifest($entity);
		return ['tables' => count($m), 'rows' => array_sum(array_map('count', $m))];
	}

	public function populateStaticManifest(int $entity = 1): array
	{
		$errors = [];
		foreach (self::STATIC_MANIFEST as $table => $ids) {
			$this->track($table, $ids, $entity);
		}
		return ['errors' => $errors];
	}

	// ── Programmatic reset ────────────────────────────────────────────────────

	public function reset(int $entity = 1): array
	{
		$manifest = $this->getManifest($entity);
		if (empty($manifest)) {
			return ['ok' => 0, 'errors' => []];
		}
		$ok        = 0;
		$errors    = [];
		$remaining = $manifest;

		$this->db->query('SET FOREIGN_KEY_CHECKS = 0');

		foreach (self::DELETE_ORDER as $llx_table) {
			$tbl = (MAIN_DB_PREFIX !== 'llx_')
				? preg_replace('/^llx_/', MAIN_DB_PREFIX, $llx_table)
				: $llx_table;
			if (!isset($remaining[$tbl])) continue;
			$ids     = $remaining[$tbl];
			$pk      = self::TABLE_PK[$llx_table] ?? 'rowid';
			$id_list = implode(',', array_map('intval', $ids));
			$sql     = "DELETE FROM `{$tbl}` WHERE `{$pk}` IN ({$id_list})";
			if ($this->db->query($sql) !== false) {
				$ok += $this->db->affected_rows($sql);
			} else {
				$errors[] = "Delete {$tbl}: " . $this->db->lasterror();
			}
			unset($remaining[$tbl]);
		}

		foreach ($remaining as $tbl => $ids) {
			$llx_table = (MAIN_DB_PREFIX !== 'llx_')
				? preg_replace('/^' . preg_quote(MAIN_DB_PREFIX, '/') . '/', 'llx_', $tbl)
				: $tbl;
			$pk      = self::TABLE_PK[$llx_table] ?? 'rowid';
			$id_list = implode(',', array_map('intval', $ids));
			$sql     = "DELETE FROM `{$tbl}` WHERE `{$pk}` IN ({$id_list})";
			if ($this->db->query($sql) !== false) {
				$ok += $this->db->affected_rows($sql);
			} else {
				$errors[] = "Delete {$tbl}: " . $this->db->lasterror();
			}
		}

		$this->db->query('SET FOREIGN_KEY_CHECKS = 1');
		if (empty($errors)) {
			$this->clearManifest($entity);
		}
		return ['ok' => $ok, 'errors' => $errors];
	}

	// ── Post-restore manifest sync ───────────────────────────────────────────

	public function resyncManifest(int $entity = 1): void
	{
		$mode = getDolGlobalString('MOKODOLITRAINING_SEED_MODE') ?: 'training';
		$this->clearManifest($entity);
		$this->populateStaticManifest($entity);
		if ($mode === 'demo') {
			$this->track('llx_user',             [70,71,72,73,74,75,76], $entity);
			$this->track('llx_user_extrafields', [70,71,72,73,74,75,76], $entity);
			$this->track('llx_usergroup_user',   [70,71,72,73,74,75,76], $entity);
			$this->track('llx_usergroup',        [74,75,76,77], $entity);
			$this->track('llx_usergroup_rights', [110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126], $entity);
		}
		$raw_orders = getDolGlobalString('MOKODOLITRAINING_T18_ORDER_IDS');
		$raw_dets   = getDolGlobalString('MOKODOLITRAINING_T18_DET_IDS');
		$order_ids  = ($raw_orders !== '') ? (array) json_decode($raw_orders, true) : [];
		$det_ids    = ($raw_dets   !== '') ? (array) json_decode($raw_dets,   true) : [];
		if ($order_ids) $this->track('llx_commande',    $order_ids, $entity);
		if ($det_ids)   $this->track('llx_commandedet', $det_ids,   $entity);
	}

	// ── SQL execution helpers ─────────────────────────────────────────────────

	private function exec(string $sql, array &$errors): void
	{
		if (MAIN_DB_PREFIX !== 'llx_') {
			$sql = str_replace('llx_', MAIN_DB_PREFIX, $sql);
		}
		if (!$this->db->query($sql)) {
			$errors[] = $this->db->lasterror();
		}
	}

	private function addColIfAbsent(string $table, string $col, string $def, array &$errors): void
	{
		$t   = (MAIN_DB_PREFIX !== 'llx_') ? str_replace('llx_', MAIN_DB_PREFIX, $table) : $table;
		$res = $this->db->query("SHOW COLUMNS FROM `{$t}` LIKE '" . $this->db->escape($col) . "'");
		if (!$res || $this->db->num_rows($res) === 0) {
			if (!$this->db->query("ALTER TABLE `{$t}` ADD COLUMN `{$col}` {$def}")) {
				$errors[] = "ALTER {$t}.{$col}: " . $this->db->lasterror();
			}
		}
	}

	// ── Static seed entry point ───────────────────────────────────────────────

	/**
	 * Seed all static training or demo data.
	 *
	 * @param int    $entity  Dolibarr entity
	 * @param string $mode    'training' (default) or 'demo'
	 */
	public function seedStatic(int $entity = 1, string $mode = 'training'): array
	{
		$errors = [];
		$e      = $entity;
		$this->seedConstants($e, $errors);
		$this->seedBaseUsers($e, $errors);
		$this->seedBaseThirdParties($e, $errors);
		$this->seedBaseContacts($e, $errors);
		$this->seedAdditionalContacts($e, $errors);
		$this->seedProducts($e, $errors);
		$this->seedExtrafieldDefs($e, $errors);
		$this->seedExtrafieldData($e, $errors);
		$this->seedBaseProjects($e, $errors);
		$this->seedProjectTasks($e, $errors);
		$this->seedBaseProposals($e, $errors);
		$this->seedBaseInvoices($e, $errors);
		$this->seedBaseContracts($e, $errors);
		$this->seedBankAndPayments($e, $errors);
		$this->seedInterventions($e, $errors);
		$this->seedTickets($e, $errors);
		// ── Mode-specific: users and groups ───────────────────────────────────
		if ($mode === 'demo') {
			$this->seedDemoUsers($e, $errors);
			$this->seedDemoGroups($e, $errors);
		} else {
			$this->seedTrainingUsers($e, $errors);
			$this->seedTrainingGroups($e, $errors);
		}
		$this->seedTrainingThirdParties($e, $errors);
		$this->seedTrainingProjects($e, $errors);
		$this->seedTrainingProposals($e, $errors);
		$this->seedTrainingInvoices($e, $errors);
		$this->seedTrainingContracts($e, $errors);
		$this->seedTrainingPayments($e, $errors);
		$this->seedTrainingRecurring($e, $errors);
		$this->seedVendorCycle($e, $errors);
		$this->seedExpenseReports($e, $errors);
		$this->seedCrmActivity($e, $errors);
		$this->seedTimeTracking($e, $errors);
		$this->seedLinkedDocs($e, $errors);
		$this->seedCategories($e, $errors);
		$this->seedMemberships($e, $errors);
		$this->seedMailings($e, $errors);
		$this->populateStaticManifest($entity);
		return ['ok' => empty($errors) ? 1 : 0, 'errors' => $errors];
	}

	// ── S1–S5: Company constants ──────────────────────────────────────────────

	private function seedConstants(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_const` (`name`,`entity`,`value`,`type`,`visible`,`note`,`tms`) VALUES
('MAIN_INFO_SOCIETE_NOM',1,'Demo Consulting Co.','chaine',1,NULL,NOW()),
('MAIN_INFO_SOCIETE_ADDRESS',1,'123 Demo Street','chaine',1,NULL,NOW()),
('MAIN_INFO_SOCIETE_TOWN',1,'Demo City','chaine',1,NULL,NOW()),
('MAIN_INFO_SOCIETE_STATE',1,'TN','chaine',1,NULL,NOW()),
('MAIN_INFO_SOCIETE_ZIP',1,'37040','chaine',1,NULL,NOW()),
('MAIN_INFO_SOCIETE_COUNTRY',1,'840','chaine',1,NULL,NOW()),
('MAIN_INFO_SOCIETE_COUNTRY_CODE',1,'US','chaine',1,NULL,NOW()),
('MAIN_INFO_SOCIETE_MAIL',1,'admin@democonsulting.example','chaine',1,NULL,NOW()),
('MAIN_INFO_SOCIETE_WEB',1,'https://democonsulting.example','chaine',1,NULL,NOW()),
('MAIN_APPLICATION_TITLE',1,'MokoCRM','chaine',1,NULL,NOW()),
('MAIN_LANG_DEFAULT',1,'en_US','chaine',0,NULL,NOW()),
('MAIN_CURRENCY_DEFAULT',1,'USD','chaine',1,NULL,NOW()),
('MAIN_DEFAULT_COUNTRY',1,'US','chaine',0,NULL,NOW()),
('MAIN_DATE_FORMAT',1,'%m/%d/%Y','chaine',0,NULL,NOW()),
('MAIN_DATE_FORMAT_DAYONLY',1,'%m/%d','chaine',0,NULL,NOW()),
('MAIN_SEPARATOR_DECIMAL',1,'.','chaine',0,NULL,NOW()),
('MAIN_SEPARATOR_THOUSANDS',1,',','chaine',0,NULL,NOW()),
('MAIN_START_WEEK',1,'0','chaine',0,NULL,NOW()),
('MAIN_USE_PHP_SETLOCALE',1,'0','chaine',0,NULL,NOW()),
('MAIN_MODULE_TAXNONE',1,'1','chaine',0,NULL,NOW())
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),`tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_const` (`name`,`entity`,`value`,`type`,`visible`,`note`,`tms`) VALUES
('SOCIETE_CODECLIENT_ADDON',1,'mod_codeclient_leopard','chaine',0,NULL,NOW()),
('PROPOSAL_ADDON',1,'mod_propale_marbre','chaine',0,NULL,NOW()),
('INVOICE_ADDON',1,'mod_facture_terre','chaine',0,NULL,NOW()),
('CONTRACT_ADDON',1,'mod_contract_serpis','chaine',0,NULL,NOW()),
('PROJECT_ADDON',1,'mod_project_simple','chaine',0,NULL,NOW()),
('FICHINTER_ADDON',1,'mod_fichinter_mercure','chaine',0,NULL,NOW()),
('PROPALE_ADDON_PDF',1,'crabe','chaine',0,NULL,NOW()),
('FACTURE_ADDON_PDF',1,'crabe','chaine',0,NULL,NOW()),
('CONTRACT_ADDON_PDF',1,'contract','chaine',0,NULL,NOW())
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),`tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_const` (`name`,`entity`,`value`,`type`,`visible`,`note`,`tms`) VALUES
('MAIN_MODULE_SOCIETE',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_PRODUCT',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_PROPALE',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_PROJET',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_FACTURE',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_CONTRAT',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_FICHEINTER',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_BANQUE',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_CATEGORIE',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_AGENDA',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_EXPORT',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_IMPORT',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_NOTIFICATION',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_WORKFLOW',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_COMMANDE',1,'1','chaine',0,NULL,NOW()),
('MAIN_MODULE_CLICKTODIAL',1,'0','chaine',0,'DEV disabled',NOW()),
('MAIN_MODULE_DON',1,'0','chaine',0,'DEV disabled',NOW()),
('MAIN_MODULE_MAILING',1,'0','chaine',0,'DEV disabled',NOW()),
('MAIN_MODULE_MULTICURRENCY',1,'0','chaine',0,'DEV disabled',NOW()),
('MAIN_MODULE_OPENSURVEY',1,'0','chaine',0,'DEV disabled',NOW()),
('MAIN_MODULE_MOKOCRM',1,'0','chaine',0,'DEV disabled',NOW()),
('MAIN_MODULE_MOKODOLITOOLS',1,'0','chaine',0,'DEV disabled',NOW())
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),`tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_const` (`name`,`entity`,`value`,`type`,`visible`,`note`,`tms`) VALUES
('MAIN_MAIL_SENDTO_FORCETO',1,'dev@mokoconsulting.tech','chaine',0,'DEV — remove in prod',NOW()),
('MAIN_MAIL_EMAIL_FROM',1,'dev@mokoconsulting.tech','chaine',0,'DEV — remove in prod',NOW()),
('MAIN_MAIL_ERRORS_TO',1,'dev@mokoconsulting.tech','chaine',0,'DEV — remove in prod',NOW()),
('MAIN_MAIL_REPLYTO',1,'dev@mokoconsulting.tech','chaine',0,'DEV — remove in prod',NOW()),
('MAILING_DISABLE_SENDINGBYEMAIL',1,'1','chaine',0,'DEV — remove in prod',NOW())
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),`tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_const` (`name`,`entity`,`value`,`type`,`visible`,`note`,`tms`) VALUES
('PROJECT_TASK_INCLUDE_TASKS_IN_SUBTASKS',1,'1','chaine',0,NULL,NOW()),
('PROJECT_TASK_DEPTH',1,'3','chaine',0,NULL,NOW()),
('PROJECT_SHOW_TASKPROGRESS',1,'1','chaine',0,NULL,NOW()),
('PROJECT_BILL_TIME_SPENT',1,'1','chaine',0,NULL,NOW())
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),`tms`=NOW()
SQL, $errors);
	}

	// ── S10: Product catalog ─────────────────────────────────────────────────

	private function seedProducts(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_product`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`label`,`description`,`fk_product_type`,`stockable_product`,
   `price`,`price_ttc`,`price_min`,`price_min_ttc`,`price_base_type`,`tva_tx`,`tosell`,`tobuy`,`duration`,`fk_user_author`)
VALUES
(50,NOW(),NOW(),'CONS-INT',1,'Consulting — Initial Session','Free discovery session to assess project fit and scope. Credit applies toward a signed engagement within 30 days.',1,0,0.00,0.00,0.00,0.00,'HT',0,1,0,'',1),
(51,NOW(),NOW(),'CONS-HALF-DAY',1,'Consulting — Half-Day Block (4 hr)','Four-hour focused consulting block. Deliverable: session notes and a written summary of recommendations. Remote or on-site.',1,0,300.00,300.00,0.00,0.00,'HT',0,1,0,'',1),
(52,NOW(),NOW(),'CONS-FULL-DAY',1,'Consulting — Full-Day Block (8 hr)','Eight-hour deep-dive consulting block. Includes structured agenda, working sessions, and end-of-day debrief document.',1,0,550.00,550.00,0.00,0.00,'HT',0,1,0,'',1),
(53,NOW(),NOW(),'CONS-RETAINER',1,'Consulting Retainer — Monthly','Ongoing monthly advisory engagement. Includes up to 6 hrs strategic consultation, async Q&A, and a monthly summary report.',1,0,399.00,399.00,0.00,0.00,'HT',0,1,0,'1m',1),
(54,NOW(),NOW(),'CONS-GRANT-REVIEW',1,'Grant Application Review','Expert review of a submitted or draft grant application. Deliverable: annotated draft with scoring notes and revision recommendations.',1,0,199.00,199.00,0.00,0.00,'HT',0,1,0,'',1),
(55,NOW(),NOW(),'CONS-GRANT-WRITE',1,'Grant Writing — Full Application','Full grant application from research through final submission. Includes funder research, narrative writing, budget narrative, and one revision round.',1,0,750.00,750.00,0.00,0.00,'HT',0,1,0,'',1),
(56,NOW(),NOW(),'CONS-DIGITAL-AUDIT',1,'Digital Presence Audit','Audit of website, social channels, email, and online reputation. Deliverable: scored findings report with prioritized action items.',1,0,349.00,349.00,0.00,0.00,'HT',0,1,0,'',1),
(57,NOW(),NOW(),'CONS-PROCESS-MAP',1,'Process Mapping & Documentation','Structured documentation of an existing workflow or operational process. Deliverable: annotated diagram set and written procedures document.',1,0,499.00,499.00,0.00,0.00,'HT',0,1,0,'',1),
(58,NOW(),NOW(),'WEB-DISC',1,'Website — Discovery Phase','Project kickoff and planning phase. Deliverables: sitemap, content plan, technical requirements document, and timeline.',1,0,350.00,350.00,0.00,0.00,'HT',0,1,0,'',1),
(59,NOW(),NOW(),'WEB-DSGN',1,'Website — Design Phase','Visual design and UX phase. Deliverables: wireframes, high-fidelity mockups for up to 5 page templates, style guide. Two revision rounds included.',1,0,600.00,600.00,0.00,0.00,'HT',0,1,0,'',1),
(60,NOW(),NOW(),'WEB-BILD',1,'Website — Build Phase','CMS development to approved design. Includes responsive build, plugin/extension configuration, and accessibility pass.',1,0,900.00,900.00,0.00,0.00,'HT',0,1,0,'',1),
(61,NOW(),NOW(),'WEB-LNCH',1,'Website — Launch Phase','Pre-launch QA, performance review, DNS cutover, go-live monitoring, and client handoff documentation.',1,0,350.00,350.00,0.00,0.00,'HT',0,1,0,'',1),
(62,NOW(),NOW(),'WEB-PKG-STD',1,'Website Package — Standard (up to 10 pages)','Bundled project rate covering Discovery, Design, Build, and Launch phases for informational sites up to 10 pages.',1,0,2800.00,2800.00,0.00,0.00,'HT',0,1,0,'',1),
(63,NOW(),NOW(),'WEB-WAAS',1,'Website as a Service — Annual','Fully managed website service. Includes hosting, CMS licensing, security patches, uptime monitoring, and up to 2 hrs/month content updates.',1,0,1500.00,1500.00,0.00,0.00,'HT',0,1,0,'1y',1),
(64,NOW(),NOW(),'WEB-MAINT',1,'Website Maintenance — Monthly','Monthly care plan. Includes CMS/plugin updates, security scans, uptime monitoring, and up to 1 hr content or copy edits.',1,0,99.00,99.00,0.00,0.00,'HT',0,1,0,'1m',1),
(65,NOW(),NOW(),'WEB-ECOMM',1,'E-Commerce Setup','Full e-commerce configuration including payment gateway integration, shipping rules, tax configuration, and initial product catalog import (up to 50 SKUs).',1,0,1200.00,1200.00,0.00,0.00,'HT',0,1,0,'',1),
(66,NOW(),NOW(),'WEB-COMMUNITY',1,'Community Portal Build','Member-driven web portal. Includes member directory, events calendar, discussion forum, and basic merch integration.',1,0,1400.00,1400.00,0.00,0.00,'HT',0,1,0,'',1),
(67,NOW(),NOW(),'DESIGN-LOGO',1,'Logo Design — Full Package','Primary logomark, alternate horizontal and stacked marks, color palette (HEX/RGB/CMYK), font pairing, and usage guidelines. Three concept directions, two revision rounds.',1,0,599.00,599.00,0.00,0.00,'HT',0,1,0,'',1),
(68,NOW(),NOW(),'DESIGN-BRAND',1,'Brand Identity Package','Full brand system including logo suite, color palette, typography scale, iconography guidelines, and a printed-ready brand standards PDF.',1,0,999.00,999.00,0.00,0.00,'HT',0,1,0,'',1),
(69,NOW(),NOW(),'DESIGN-SOCIAL',1,'Social Media Template Set','Ten branded social templates covering standard post, story/reel, cover, and event formats. Delivered in Canva and as export-ready PNG/PSD files.',1,0,299.00,299.00,0.00,0.00,'HT',0,1,0,'',1),
(70,NOW(),NOW(),'VENDOR-HOST-12MO',1,'Web Hosting — Annual Pass-Through','Shared web hosting, 12-month term. Billed at cost; no markup. Renewal reminder issued 45 days prior.',1,0,144.00,144.00,0.00,0.00,'HT',0,1,0,'1y',1),
(71,NOW(),NOW(),'VENDOR-DOMAIN',1,'Domain Registration — Annual','Annual domain registration pass-through, any standard TLD. Billed at cost.',1,0,21.99,21.99,11.99,11.99,'HT',0,1,0,'1y',1),
(72,NOW(),NOW(),'VENDOR-PLATFORM',1,'Platform Subscription — Monthly Pass-Through','Third-party SaaS or platform subscription billed as pass-through. Adjust price to match vendor invoice.',1,0,31.00,31.00,0.00,0.00,'HT',0,1,0,'1m',1),
(73,NOW(),NOW(),'FEE-REVISION',1,'Additional Revision Round','One additional revision round on any deliverable beyond what is included in scope. Applies per deliverable. Non-refundable.',1,0,75.00,75.00,0.00,0.00,'HT',0,1,0,'',1),
(74,NOW(),NOW(),'FEE-RUSH',1,'Rush Turnaround — 24-Hour Surcharge','Priority same-day or next-day delivery on eligible deliverables. Subject to availability. Non-refundable.',1,0,150.00,150.00,0.00,0.00,'HT',0,1,0,'',1),
(75,NOW(),NOW(),'FEE-LATE',1,'Late Payment Fee','Applied to unpaid invoices after the applicable grace period per service agreement.',1,0,35.00,35.00,0.00,0.00,'HT',0,1,0,'',1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_product`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`label`,`description`,`fk_product_type`,`stockable_product`,
   `price`,`price_ttc`,`price_min`,`price_min_ttc`,`price_base_type`,`tva_tx`,`tosell`,`tobuy`,`duration`,`fk_user_author`,
   `weight`,`weight_units`,`length`,`length_units`,`width`,`width_units`,`height`,`height_units`)
VALUES
(80,NOW(),NOW(),'PRINT-BIZCARD-500',1,'Business Cards — 500 qty','Offset-printed business cards, 16pt coated stock, full color both sides, rounded corners. Quantity: 500. Client provides approved artwork file.',0,1,89.00,89.00,0.00,0.00,'HT',0,1,0,'',1,0.56,99,3.50,99,2.00,99,NULL,NULL),
(81,NOW(),NOW(),'PRINT-BROCHURE-100',1,'Tri-Fold Brochures — 100 qty','Full-color tri-fold brochures, 100 lb gloss text stock, UV coating. Folded size 3.67 × 8.5 in.',0,1,149.00,149.00,0.00,0.00,'HT',0,1,0,'',1,2.20,99,3.67,99,8.50,99,NULL,NULL),
(82,NOW(),NOW(),'PRINT-RACKCARD-250',1,'Rack Cards — 250 qty','Single-sided rack cards, 14pt coated stock, full color. Standard rack card format. Client provides approved artwork.',0,1,119.00,119.00,0.00,0.00,'HT',0,1,0,'',1,1.40,99,4.00,99,9.00,99,NULL,NULL),
(83,NOW(),NOW(),'PRINT-BANNER-2X4',1,'Vinyl Banner — 2 × 4 ft','13 oz scrim vinyl banner, full-color UV print, hemmed edges with metal grommets at all four corners. Single-sided. Client provides approved artwork.',0,1,79.00,79.00,0.00,0.00,'HT',0,1,0,'',1,1.20,99,48.00,99,24.00,99,NULL,NULL),
(84,NOW(),NOW(),'SWAG-USB-10PK',1,'Branded USB Drives — 10-Pack (8 GB)','Swivel USB 2.0 drives, 8 GB, custom laser-engraved logo on cap. Packed individually in polybags. Minimum order 10 units.',0,1,89.00,89.00,0.00,0.00,'HT',0,1,0,'',1,0.44,99,2.56,99,0.75,99,0.44,99),
(85,NOW(),NOW(),'SWAG-NOTEBOOK',1,'Branded Softcover Notebook — A5','Soft-touch matte laminate cover, 80 ruled sheets (160 pages), elastic closure band, ribbon bookmark, inner pocket. Debossed or foil-stamp logo on cover.',0,1,12.00,12.00,8.00,8.00,'HT',0,1,0,'',1,0.57,99,8.27,99,5.83,99,0.55,99),
(86,NOW(),NOW(),'SWAG-TOTE',1,'Branded Canvas Tote Bag','12 oz natural canvas tote, self-fabric handles, flat bottom. 1-color screen print on front panel. Gusset allows bag to stand open. One size.',0,1,14.50,14.50,9.00,9.00,'HT',0,1,0,'',1,0.38,99,15.00,99,5.00,99,16.00,99),
(87,NOW(),NOW(),'PRINT-POSTCARD-250',1,'Postcards — 4×6, 250 qty','4×6 in postcards, 16pt coated stock, full-color front, matte reverse. UV coating front. Client provides approved artwork.',0,1,99.00,99.00,0.00,0.00,'HT',0,1,0,'',1,1.10,99,6.00,99,4.00,99,NULL,NULL),
(88,NOW(),NOW(),'PRINT-STICKER-100',1,'Die-Cut Stickers — 100 qty','Custom die-cut vinyl stickers, full-color digital print, outdoor-rated UV laminate. Contour cut to artwork shape. Waterproof and scratch-resistant.',0,1,69.00,69.00,0.00,0.00,'HT',0,1,0,'',1,0.18,99,3.00,99,3.00,99,NULL,NULL),
(89,NOW(),NOW(),'PRINT-LETTERHEAD-250',1,'Branded Letterhead — 250 sheets','8.5 × 11 in premium letterhead, 24 lb bond paper, full-color offset print. Designed to match brand identity package.',0,1,129.00,129.00,0.00,0.00,'HT',0,1,0,'',1,1.80,99,11.00,99,8.50,99,NULL,NULL),
(90,NOW(),NOW(),'SWAG-MUG',1,'Branded Ceramic Mug — 11 oz','11 oz white ceramic mug, dishwasher-safe, full-wrap or single-side custom imprint. Sublimation print for full-color photo-quality finish.',0,1,18.00,18.00,10.00,10.00,'HT',0,1,0,'',1,0.88,99,3.75,99,3.00,99,4.75,99),
(91,NOW(),NOW(),'SWAG-PEN-12PK',1,'Branded Ballpoint Pens — 12-Pack','Click-action retractable ballpoint pens, black ink, medium tip. Barrel laser-engraved with client logo and tagline.',0,1,36.00,36.00,22.00,22.00,'HT',0,1,0,'',1,0.52,99,5.63,99,0.50,99,0.50,99),
(92,NOW(),NOW(),'SWAG-BOTTLE',1,'Branded Stainless Water Bottle — 20 oz','20 oz double-wall vacuum-insulated stainless steel bottle. Keeps beverages cold 24h / hot 12h. Leak-proof twist cap.',0,1,32.00,32.00,20.00,20.00,'HT',0,1,0,'',1,0.68,99,7.50,99,2.75,99,10.75,99),
(93,NOW(),NOW(),'SWAG-HAT',1,'Branded Structured Cap — One Size','Six-panel structured baseball cap, mid-profile, adjustable velcro strap. 100% chino cotton twill. Embroidered logo on front panel.',0,1,24.00,24.00,14.00,14.00,'HT',0,1,0,'',1,0.25,99,11.00,99,9.00,99,5.50,99),
(94,NOW(),NOW(),'SWAG-SHIRT',1,'Branded Unisex Tee — S/M/L/XL','Unisex 4.2 oz 100% ring-spun cotton tee, preshrunk, seamless collar. Screen-printed logo on left chest.',0,1,22.00,22.00,12.00,12.00,'HT',0,1,0,'',1,0.45,99,28.00,99,20.00,99,NULL,NULL)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_product_extrafields` (`rowid`,`tms`,`fk_object`) VALUES
(50,NOW(),50),(51,NOW(),51),(52,NOW(),52),(53,NOW(),53),(54,NOW(),54),(55,NOW(),55),
(56,NOW(),56),(57,NOW(),57),(58,NOW(),58),(59,NOW(),59),(60,NOW(),60),(61,NOW(),61),
(62,NOW(),62),(63,NOW(),63),(64,NOW(),64),(65,NOW(),65),(66,NOW(),66),(67,NOW(),67),
(68,NOW(),68),(69,NOW(),69),(70,NOW(),70),(71,NOW(),71),(72,NOW(),72),(73,NOW(),73),
(74,NOW(),74),(75,NOW(),75),
(80,NOW(),80),(81,NOW(),81),(82,NOW(),82),(83,NOW(),83),(84,NOW(),84),(85,NOW(),85),(86,NOW(),86),
(87,NOW(),87),(88,NOW(),88),(89,NOW(),89),(90,NOW(),90),(91,NOW(),91),(92,NOW(),92),(93,NOW(),93),(94,NOW(),94)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── S11: Extrafield definitions ───────────────────────────────────────────

	private function seedExtrafieldDefs(int $e, array &$errors): void
	{
		$this->addColIfAbsent('llx_societe_extrafields',   'industry_type',       'varchar(64)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_societe_extrafields',   'referral_source',     'varchar(128) DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_societe_extrafields',   'preferred_contact',   'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_societe_extrafields',   'annual_budget',       'double       DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_societe_extrafields',   'nda_signed',          'tinyint      DEFAULT 0',    $errors);
		$this->addColIfAbsent('llx_societe_extrafields',   'nda_date',            'date         DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_projet_extrafields',    'project_type',        'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_projet_extrafields',    'billing_type',        'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_projet_extrafields',    'estimated_hours',     'int          DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_projet_extrafields',    'kickoff_confirmed',   'tinyint      DEFAULT 0',    $errors);
		$this->addColIfAbsent('llx_projet_task_extrafields','task_phase',         'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_projet_task_extrafields','billable_hours',     'double       DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_projet_task_extrafields','client_review',      'tinyint      DEFAULT 0',    $errors);
		$this->addColIfAbsent('llx_propal_extrafields',    'proposal_type',       'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_propal_extrafields',    'discount_reason',     'varchar(255) DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_propal_extrafields',    'follow_up_date',      'date         DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_propal_extrafields',    'signed_date',         'date         DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_facture_extrafields',   'payment_method',      'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_facture_extrafields',   'is_deposit',          'tinyint      DEFAULT 0',    $errors);
		$this->addColIfAbsent('llx_facture_extrafields',   'balance_due',         'date         DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_contrat_extrafields',   'billing_frequency',   'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_contrat_extrafields',   'auto_renew',          'tinyint      DEFAULT 0',    $errors);
		$this->addColIfAbsent('llx_contrat_extrafields',   'annual_value',        'double       DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_contrat_extrafields',   'renewal_notice_days', 'int          DEFAULT 30',   $errors);
		$this->addColIfAbsent('llx_product_extrafields',   'service_category',    'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_product_extrafields',   'tos_required',        'tinyint      DEFAULT 1',    $errors);
		$this->addColIfAbsent('llx_product_extrafields',   'delivery_notes',      'varchar(255) DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_socpeople_extrafields', 'job_role',            'varchar(128) DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_socpeople_extrafields', 'preferred_contact',   'varchar(32)  DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_socpeople_extrafields', 'decision_maker',      'tinyint      DEFAULT 0',    $errors);
		$this->addColIfAbsent('llx_user_extrafields',      'hourly_rate',         'double       DEFAULT NULL', $errors);
		$this->addColIfAbsent('llx_user_extrafields',      'specialty',           'varchar(128) DEFAULT NULL', $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_extrafields`
  (`rowid`,`elementtype`,`name`,`entity`,`tms`,`label`,`type`,`size`,`pos`,
   `alwayseditable`,`emptyonclone`,`fieldrequired`,`list`,`enabled`,`fk_user_author`)
VALUES
(200,'societe','industry_type',1,NOW(),'Industry','select','',10,1,0,0,'1','1',1),
(201,'societe','referral_source',1,NOW(),'Referral Source','varchar','128',20,1,0,0,'1','1',1),
(202,'societe','preferred_contact',1,NOW(),'Preferred Contact','select','',30,1,0,0,'1','1',1),
(203,'societe','annual_budget',1,NOW(),'Est. Annual Budget','double','',40,1,0,0,'1','1',1),
(204,'societe','nda_signed',1,NOW(),'NDA Signed','boolean','',50,1,0,0,'1','1',1),
(205,'societe','nda_date',1,NOW(),'NDA Date','date','',60,1,0,0,'1','1',1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── S12: Extrafield data ──────────────────────────────────────────────────

	private function seedExtrafieldData(int $e, array &$errors): void
	{
		$this->exec("UPDATE `llx_societe_extrafields` SET `industry_type`='ecommerce',`referral_source`='Direct inquiry',`preferred_contact`='email',`annual_budget`=5000,`nda_signed`=1,`nda_date`='2026-01-10' WHERE `fk_object`=50", $errors);
		$this->exec("UPDATE `llx_societe_extrafields` SET `industry_type`='nonprofit',`referral_source`='Community referral',`preferred_contact`='email',`annual_budget`=3500,`nda_signed`=0 WHERE `fk_object`=51", $errors);
		$this->exec("UPDATE `llx_societe_extrafields` SET `industry_type`='nonprofit',`referral_source`='Returning client',`preferred_contact`='email',`annual_budget`=6000,`nda_signed`=1,`nda_date`='2026-01-05' WHERE `fk_object`=52", $errors);
		$this->exec("UPDATE `llx_societe_extrafields` SET `industry_type`='agency',`referral_source`='Partner referral',`preferred_contact`='slack',`annual_budget`=4000,`nda_signed`=1,`nda_date`='2025-12-20' WHERE `fk_object`=53", $errors);
		$this->exec("UPDATE `llx_societe_extrafields` SET `industry_type`='education',`referral_source`='LinkedIn',`preferred_contact`='email',`annual_budget`=3000,`nda_signed`=0 WHERE `fk_object`=54", $errors);
		$this->exec("UPDATE `llx_societe_extrafields` SET `industry_type`='media',`referral_source`='Referral',`preferred_contact`='email',`annual_budget`=2400,`nda_signed`=0 WHERE `fk_object`=55", $errors);
		$this->exec("UPDATE `llx_societe_extrafields` SET `industry_type`='other',`referral_source`='Cold outreach',`preferred_contact`='phone',`annual_budget`=NULL,`nda_signed`=0 WHERE `fk_object`=60", $errors);
		$this->exec("UPDATE `llx_societe_extrafields` SET `industry_type`='other',`referral_source`='Trade show',`preferred_contact`='email',`annual_budget`=NULL,`nda_signed`=0 WHERE `fk_object`=61", $errors);
		$this->exec("UPDATE `llx_user_extrafields` SET `hourly_rate`=75.00,`specialty`='Joomla, Digital Strategy, Grant Consulting' WHERE `fk_object`=1", $errors);
		$this->exec("UPDATE `llx_user_extrafields` SET `hourly_rate`=50.00,`specialty`='Project Management, Client Communication' WHERE `fk_object`=50", $errors);
		$this->exec("UPDATE `llx_user_extrafields` SET `hourly_rate`=65.00,`specialty`='Joomla, PHP, VirtueMart, CSS' WHERE `fk_object`=51", $errors);
		$this->exec("UPDATE `llx_user_extrafields` SET `hourly_rate`=45.00,`specialty`='Bookkeeping, Invoicing, Dolibarr' WHERE `fk_object`=52", $errors);
		$this->exec("UPDATE `llx_user_extrafields` SET `hourly_rate`=55.00,`specialty`='Graphic Design, Branding, Figma' WHERE `fk_object`=53", $errors);
		$this->exec("UPDATE `llx_user_extrafields` SET `hourly_rate`=40.00,`specialty`='Joomla, HTML/CSS, Content Entry' WHERE `fk_object`=54", $errors);
		$this->exec("UPDATE `llx_product_extrafields` SET `service_category`='consulting',`tos_required`=1 WHERE `fk_object` IN (50,51,52,53,54,55,56,57)", $errors);
		$this->exec("UPDATE `llx_product_extrafields` SET `service_category`='web',`tos_required`=1 WHERE `fk_object` IN (58,59,60,61,62,63,64,65,66)", $errors);
		$this->exec("UPDATE `llx_product_extrafields` SET `service_category`='design',`tos_required`=1 WHERE `fk_object` IN (67,68,69)", $errors);
		$this->exec("UPDATE `llx_product_extrafields` SET `service_category`='vendor',`tos_required`=1 WHERE `fk_object` IN (70,71,72)", $errors);
		$this->exec("UPDATE `llx_product_extrafields` SET `service_category`='fee',`tos_required`=0 WHERE `fk_object` IN (73,74,75)", $errors);
		$this->exec("UPDATE `llx_product_extrafields` SET `service_category`='print',`tos_required`=0 WHERE `fk_object` IN (80,81,82,83)", $errors);
		$this->exec("UPDATE `llx_product_extrafields` SET `service_category`='swag',`tos_required`=0 WHERE `fk_object` IN (84,85,86)", $errors);
	}

	// ── S13: Base projects ────────────────────────────────────────────────────

	private function seedBaseProjects(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_projet`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`title`,`description`,`fk_soc`,
   `dateo`,`datee`,`fk_statut`,`fk_user_creat`,`usage_task`,`usage_bill_time`,`note_private`)
VALUES
(50,NOW(),NOW(),'PROJ-0001',1,'Pinnacle Goods Co. — E-Commerce Build','Full e-commerce site on Joomla/VirtueMart with payment gateway and product catalog.',50,'2026-01-15','2026-06-30',1,1,1,1,'DEMO project'),
(51,NOW(),NOW(),'PROJ-0002',1,'Riverstone Community Group — Community Site','Joomla community portal: member directory, events calendar, forum, and merch store.',51,'2026-02-01','2026-08-31',1,1,1,1,'DEMO project'),
(52,NOW(),NOW(),'PROJ-0003',1,'Brightpath Nonprofit Solutions — Grant Consulting','Annual retainer: monthly strategy sessions, grant research, and application writing.',52,'2026-01-01','2026-12-31',1,1,1,1,'DEMO project'),
(53,NOW(),NOW(),'PROJ-0004',1,'Apex Digital Agency — Corporate Site + Brand Identity','Corporate site redesign with logo and full brand identity package.',53,'2026-01-10','2026-05-31',1,1,1,1,'DEMO project')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_projet_extrafields` (`rowid`,`tms`,`fk_object`,`project_type`,`billing_type`,`estimated_hours`,`kickoff_confirmed`) VALUES
(50,NOW(),50,'ecomm','fixed',80,1),(51,NOW(),51,'web-build','fixed',120,1),
(52,NOW(),52,'grant','retainer',40,1),(53,NOW(),53,'branding','fixed',60,1)
ON DUPLICATE KEY UPDATE `project_type`=VALUES(`project_type`),`billing_type`=VALUES(`billing_type`),`estimated_hours`=VALUES(`estimated_hours`),`kickoff_confirmed`=VALUES(`kickoff_confirmed`)
SQL, $errors);
	}

	// ── S14: Project tasks ────────────────────────────────────────────────────

	private function seedProjectTasks(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_projet_task`
  (`rowid`,`datec`,`tms`,`fk_projet`,`fk_task_parent`,`label`,`planned_workload`,`dateo`,`datee`,`fk_statut`,`progress`,`rang`,`entity`,`fk_user_creat`)
VALUES
(100,NOW(),NOW(),50,0,'Phase 1 — Discovery & Scoping',0,'2026-01-15','2026-01-28',1,100,1,1,1),
(101,NOW(),NOW(),50,0,'Phase 2 — Design',0,'2026-01-29','2026-02-25',1,50,2,1,1),
(102,NOW(),NOW(),50,0,'Phase 3 — Build',0,'2026-02-26','2026-05-15',1,20,3,1,1),
(103,NOW(),NOW(),50,0,'Phase 4 — Launch',0,'2026-05-16','2026-06-10',1,0,4,1,1),
(110,NOW(),NOW(),50,100,'Kickoff Call',3600,'2026-01-15','2026-01-15',1,100,1,1,1),
(111,NOW(),NOW(),50,100,'Sitemap & Content Plan',7200,'2026-01-16','2026-01-20',1,100,2,1,1),
(112,NOW(),NOW(),50,100,'Technical Requirements Doc',5400,'2026-01-21','2026-01-24',1,100,3,1,1),
(113,NOW(),NOW(),50,100,'Domain & Hosting Setup',3600,'2026-01-25','2026-01-28',1,100,4,1,1),
(114,NOW(),NOW(),50,101,'Brand Review Session',3600,'2026-01-29','2026-01-30',1,100,1,1,1),
(115,NOW(),NOW(),50,101,'Homepage Mockup',14400,'2026-01-31','2026-02-07',1,80,2,1,1),
(116,NOW(),NOW(),50,101,'Product Page Mockups',10800,'2026-02-08','2026-02-14',1,60,3,1,1),
(117,NOW(),NOW(),50,101,'Design Approval Round 1',3600,'2026-02-15','2026-02-18',1,50,4,1,1),
(118,NOW(),NOW(),50,101,'Design Revisions + Final Sign-Off',7200,'2026-02-19','2026-02-25',1,0,5,1,1),
(119,NOW(),NOW(),50,102,'Joomla Install & Cassiopeia Config',7200,'2026-02-26','2026-03-07',1,40,1,1,1),
(120,NOW(),NOW(),50,102,'VirtueMart E-Commerce Setup',14400,'2026-03-08','2026-03-25',1,20,2,1,1),
(121,NOW(),NOW(),50,102,'Payment Gateway Integration',7200,'2026-03-26','2026-04-04',1,0,3,1,1),
(122,NOW(),NOW(),50,102,'Product Catalog Import (50 SKUs)',10800,'2026-04-05','2026-04-22',1,0,4,1,1),
(123,NOW(),NOW(),50,102,'Supporting Pages Build',7200,'2026-04-23','2026-05-08',1,0,5,1,1),
(124,NOW(),NOW(),50,102,'Build QA Pass',5400,'2026-05-09','2026-05-15',1,0,6,1,1),
(125,NOW(),NOW(),50,103,'Client UAT & Feedback',7200,'2026-05-16','2026-05-23',1,0,1,1,1),
(126,NOW(),NOW(),50,103,'DNS Cutover & Go-Live',3600,'2026-05-28','2026-05-28',1,0,2,1,1),
(127,NOW(),NOW(),50,103,'Post-Launch Review & Handoff',3600,'2026-06-05','2026-06-10',1,0,3,1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_projet_task`
  (`rowid`,`datec`,`tms`,`fk_projet`,`fk_task_parent`,`label`,`planned_workload`,`dateo`,`datee`,`fk_statut`,`progress`,`rang`,`entity`,`fk_user_creat`)
VALUES
(130,NOW(),NOW(),51,0,'Phase 1 — Discovery',0,'2026-02-01','2026-02-14',1,100,1,1,1),
(131,NOW(),NOW(),51,0,'Phase 2 — Design',0,'2026-02-15','2026-03-14',1,60,2,1,1),
(132,NOW(),NOW(),51,0,'Phase 3 — Build',0,'2026-03-15','2026-06-30',1,0,3,1,1),
(133,NOW(),NOW(),51,0,'Phase 4 — Launch',0,'2026-07-01','2026-08-15',1,0,4,1,1),
(140,NOW(),NOW(),51,130,'Kickoff & Requirements Session',5400,'2026-02-01','2026-02-03',1,100,1,1,1),
(141,NOW(),NOW(),51,130,'Member Directory Planning',7200,'2026-02-04','2026-02-08',1,100,2,1,1),
(142,NOW(),NOW(),51,130,'Events Calendar Config Plan',5400,'2026-02-09','2026-02-12',1,100,3,1,1),
(143,NOW(),NOW(),51,130,'Sitemap & Architecture Sign-Off',3600,'2026-02-13','2026-02-14',1,100,4,1,1),
(144,NOW(),NOW(),51,131,'Homepage Mockup',10800,'2026-02-15','2026-02-24',1,80,1,1,1),
(145,NOW(),NOW(),51,131,'Member Directory Page Mockup',7200,'2026-02-25','2026-03-04',1,60,2,1,1),
(146,NOW(),NOW(),51,131,'Events & Forum Page Mockups',7200,'2026-03-05','2026-03-11',1,0,3,1,1),
(147,NOW(),NOW(),51,131,'Design Approval + Revisions',5400,'2026-03-12','2026-03-14',1,0,4,1,1),
(148,NOW(),NOW(),51,132,'Joomla Install + CB/JomSocial',10800,'2026-03-15','2026-03-28',1,0,1,1,1),
(149,NOW(),NOW(),51,132,'Member Directory Setup',7200,'2026-03-29','2026-04-11',1,0,2,1,1),
(150,NOW(),NOW(),51,132,'Events Calendar Integration',7200,'2026-04-12','2026-04-25',1,0,3,1,1),
(151,NOW(),NOW(),51,132,'Forum & Discussion Setup',7200,'2026-04-26','2026-05-09',1,0,4,1,1),
(152,NOW(),NOW(),51,132,'Content Entry & Population',7200,'2026-05-10','2026-05-30',1,0,5,1,1),
(153,NOW(),NOW(),51,132,'Build QA',5400,'2026-06-15','2026-06-30',1,0,6,1,1),
(154,NOW(),NOW(),51,133,'Client UAT',7200,'2026-07-01','2026-07-11',1,0,1,1,1),
(155,NOW(),NOW(),51,133,'DNS Cutover',1800,'2026-07-18','2026-07-18',1,0,2,1,1),
(156,NOW(),NOW(),51,133,'Admin Training Session',3600,'2026-07-25','2026-07-25',1,0,3,1,1),
(157,NOW(),NOW(),51,133,'Post-Launch Monitoring',3600,'2026-08-01','2026-08-15',1,0,4,1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_projet_task`
  (`rowid`,`datec`,`tms`,`fk_projet`,`fk_task_parent`,`label`,`planned_workload`,`dateo`,`datee`,`fk_statut`,`progress`,`rang`,`entity`,`fk_user_creat`)
VALUES
(160,NOW(),NOW(),52,0,'Phase 1 — Onboarding & Research',0,'2026-01-01','2026-02-28',1,100,1,1,1),
(161,NOW(),NOW(),52,0,'Phase 2 — Active Delivery',0,'2026-03-01','2026-12-31',1,40,2,1,1),
(162,NOW(),NOW(),52,160,'Onboarding Strategy Session',7200,'2026-01-05','2026-01-05',1,100,1,1,1),
(163,NOW(),NOW(),52,160,'Grant Landscape Research — Q1',14400,'2026-01-06','2026-01-31',1,100,2,1,1),
(164,NOW(),NOW(),52,160,'Funding Priorities Document',7200,'2026-02-01','2026-02-14',1,100,3,1,1),
(165,NOW(),NOW(),52,160,'Grant Calendar Setup',3600,'2026-02-15','2026-02-28',1,100,4,1,1),
(166,NOW(),NOW(),52,161,'Application — Community Development Grant',28800,'2026-03-01','2026-04-15',1,100,1,1,1),
(167,NOW(),NOW(),52,161,'Application — Technology Access Grant',28800,'2026-04-16','2026-05-31',1,60,2,1,1),
(168,NOW(),NOW(),52,161,'Monthly Check-In — March',3600,'2026-03-31','2026-03-31',1,100,3,1,1),
(169,NOW(),NOW(),52,161,'Monthly Check-In — April',3600,'2026-04-30','2026-04-30',1,100,4,1,1),
(170,NOW(),NOW(),52,161,'Monthly Check-In — May',3600,'2026-05-31','2026-05-31',1,0,5,1,1),
(171,NOW(),NOW(),52,161,'Quarterly Impact Report — Q2',7200,'2026-06-15','2026-06-30',1,0,6,1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_projet_task`
  (`rowid`,`datec`,`tms`,`fk_projet`,`fk_task_parent`,`label`,`planned_workload`,`dateo`,`datee`,`fk_statut`,`progress`,`rang`,`entity`,`fk_user_creat`)
VALUES
(180,NOW(),NOW(),53,0,'Phase 1 — Discovery',0,'2026-01-10','2026-01-31',1,100,1,1,1),
(181,NOW(),NOW(),53,0,'Phase 2 — Design',0,'2026-02-01','2026-03-07',1,70,2,1,1),
(182,NOW(),NOW(),53,0,'Phase 3 — Build',0,'2026-03-08','2026-05-02',1,0,3,1,1),
(183,NOW(),NOW(),53,0,'Phase 4 — Launch',0,'2026-05-03','2026-05-31',1,0,4,1,1),
(184,NOW(),NOW(),53,180,'Kickoff & Brand Brief',5400,'2026-01-10','2026-01-13',1,100,1,1,1),
(185,NOW(),NOW(),53,180,'Competitive Analysis',7200,'2026-01-14','2026-01-22',1,100,2,1,1),
(186,NOW(),NOW(),53,180,'Discovery Sign-Off',1800,'2026-01-30','2026-01-31',1,100,3,1,1),
(187,NOW(),NOW(),53,181,'Logo Concepts — Round 1',14400,'2026-02-01','2026-02-12',1,100,1,1,1),
(188,NOW(),NOW(),53,181,'Logo Refinement + Approval',10800,'2026-02-13','2026-02-24',1,80,2,1,1),
(189,NOW(),NOW(),53,181,'Homepage Mockup',10800,'2026-02-25','2026-03-05',1,50,3,1,1),
(190,NOW(),NOW(),53,181,'Interior Page Mockups',7200,'2026-03-06','2026-03-07',1,0,4,1,1),
(191,NOW(),NOW(),53,182,'Joomla Install & Cassiopeia Config',7200,'2026-03-08','2026-03-18',1,0,1,1,1),
(192,NOW(),NOW(),53,182,'Content Entry (12 pages)',10800,'2026-03-19','2026-04-08',1,0,2,1,1),
(193,NOW(),NOW(),53,182,'SEO Setup & Meta Tags',5400,'2026-04-09','2026-04-22',1,0,3,1,1),
(194,NOW(),NOW(),53,182,'Build QA',5400,'2026-04-23','2026-05-02',1,0,4,1,1),
(195,NOW(),NOW(),53,183,'Client UAT',7200,'2026-05-03','2026-05-12',1,0,1,1,1),
(196,NOW(),NOW(),53,183,'DNS Cutover',1800,'2026-05-20','2026-05-20',1,0,2,1,1),
(197,NOW(),NOW(),53,183,'Post-Launch Handoff',3600,'2026-05-28','2026-05-31',1,0,3,1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── S15: Base proposals ───────────────────────────────────────────────────

	private function seedBaseProposals(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_propal`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`fk_soc`,`fk_projet`,`datep`,`fin_validite`,`fk_statut`,`fk_user_author`,`fk_user_valid`,`total_ht`,`total_tva`,`total_ttc`,`note_public`,`note_private`)
VALUES
(50,NOW(),NOW(),'PROP-2026-0001',1,50,50,'2026-01-10','2026-01-25',2,1,1,4012.99,0.00,4012.99,'Thank you for choosing us for your e-commerce build. This proposal covers discovery through launch.','DEMO — Pinnacle Goods e-commerce build. Signed 2026-01-12.'),
(51,NOW(),NOW(),'PROP-2026-0002',1,52,52,'2026-01-04','2026-01-18',3,1,1,5136.00,0.00,5136.00,'Annual grant consulting retainer covering monthly strategy sessions, grant research, and two full applications.','DEMO — Brightpath annual retainer. Billed via contract CONT-2026-0001.'),
(52,NOW(),NOW(),'PROP-2026-0003',1,51,51,'2026-01-25','2026-02-08',2,1,1,3200.00,0.00,3200.00,'This proposal covers the full community site build for Riverstone Community Group.','DEMO — Riverstone community site. Signed 2026-01-30.'),
(53,NOW(),NOW(),'PROP-2026-0004',1,53,53,'2026-01-08','2026-01-22',2,1,1,2598.00,0.00,2598.00,'Corporate site redesign and brand identity package.','DEMO — Apex Digital corporate site + brand. Signed 2026-01-10.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_propaldet`
  (`rowid`,`fk_propal`,`label`,`description`,`fk_product`,`product_type`,`qty`,`tva_tx`,`remise_percent`,`subprice`,`total_ht`,`total_tva`,`total_ttc`,`rang`)
VALUES
(50,50,NULL,NULL,70,1,1,0,0,144.00,144.00,0,144.00,1),(51,50,NULL,NULL,71,1,1,0,0,21.99,21.99,0,21.99,2),
(52,50,NULL,NULL,58,1,1,0,0,350.00,350.00,0,350.00,3),(53,50,NULL,NULL,59,1,1,0,0,600.00,600.00,0,600.00,4),
(54,50,NULL,NULL,60,1,1,0,0,900.00,900.00,0,900.00,5),(55,50,NULL,NULL,65,1,1,0,0,1200.00,1200.00,0,1200.00,6),
(56,50,NULL,NULL,61,1,1,0,0,350.00,350.00,0,350.00,7),(57,50,NULL,NULL,64,1,3,0,0,99.00,297.00,0,297.00,8),
(58,50,NULL,NULL,73,1,2,0,0,75.00,150.00,0,150.00,9),
(60,51,NULL,NULL,55,1,12,0,0,399.00,4788.00,0,4788.00,1),(61,51,NULL,NULL,54,1,2,0,22.40,750.00,1170.00,0,1170.00,2),
(70,52,NULL,NULL,58,1,1,0,0,350.00,350.00,0,350.00,1),(71,52,NULL,NULL,59,1,1,0,0,600.00,600.00,0,600.00,2),
(72,52,NULL,NULL,66,1,1,0,0,1400.00,1400.00,0,1400.00,3),(73,52,NULL,NULL,61,1,1,0,0,350.00,350.00,0,350.00,4),
(74,52,NULL,NULL,70,1,1,0,0,144.00,144.00,0,144.00,5),(75,52,NULL,NULL,73,1,2,0,0,75.00,150.00,0,150.00,6),
(76,52,NULL,NULL,71,1,1,0,0,21.99,21.99,0,21.99,7),
(80,53,NULL,NULL,58,1,1,0,0,350.00,350.00,0,350.00,1),(81,53,NULL,NULL,68,1,1,0,0,999.00,999.00,0,999.00,2),
(82,53,NULL,NULL,59,1,1,0,0,600.00,600.00,0,600.00,3),(83,53,NULL,NULL,60,1,1,0,0,900.00,900.00,0,900.00,4),
(84,53,NULL,NULL,61,1,1,0,0,350.00,350.00,0,350.00,5),(85,53,NULL,NULL,71,1,1,0,0,21.99,21.99,0,21.99,6),
(86,53,NULL,NULL,73,1,2,0,0,75.00,150.00,0,150.00,7)
ON DUPLICATE KEY UPDATE `rang`=VALUES(`rang`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_propal_extrafields` (`rowid`,`tms`,`fk_object`,`proposal_type`,`discount_reason`,`signed_date`) VALUES
(50,NOW(),50,'ecomm',NULL,'2026-01-12'),(51,NOW(),51,'grant','Returning client rate','2026-01-05'),
(52,NOW(),52,'web-build',NULL,'2026-01-30'),(53,NOW(),53,'branding',NULL,'2026-01-10')
ON DUPLICATE KEY UPDATE `proposal_type`=VALUES(`proposal_type`),`signed_date`=VALUES(`signed_date`)
SQL, $errors);
	}

	// ── S16: Base invoices ────────────────────────────────────────────────────

	private function seedBaseInvoices(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_facture`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`fk_soc`,`fk_projet`,`datef`,`date_lim_reglement`,`fk_statut`,`paye`,`fk_user_author`,`fk_user_valid`,`fk_cond_reglement`,`total_ht`,`total_tva`,`total_ttc`,`note_public`,`note_private`)
VALUES
(50,NOW(),NOW(),'INV-2026-0001',1,50,50,'2026-01-12','2026-01-27',1,0,1,1,1,2006.50,0.00,2006.50,'50% deposit per signed proposal PROP-2026-0001. Balance due at launch.','DEMO — Pinnacle Goods deposit.'),
(51,NOW(),NOW(),'INV-2026-0002',1,52,52,'2026-01-05','2026-01-20',2,1,1,1,1,399.00,0.00,399.00,'Monthly consulting retainer — January 2026.','DEMO — Brightpath January retainer. Paid via ACH 2026-01-18.'),
(52,NOW(),NOW(),'INV-2026-0003',1,51,51,'2026-01-30','2026-02-14',1,0,1,1,1,1600.00,0.00,1600.00,'50% deposit per signed proposal PROP-2026-0003. Balance due at launch.','DEMO — Riverstone deposit.'),
(53,NOW(),NOW(),'INV-2026-0004',1,53,53,'2026-01-10','2026-01-25',1,0,1,1,1,1299.00,0.00,1299.00,'50% deposit per signed proposal PROP-2026-0004. Balance due at launch.','DEMO — Apex Digital deposit.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_facturedet`
  (`rowid`,`fk_facture`,`label`,`description`,`fk_product`,`product_type`,`qty`,`tva_tx`,`remise_percent`,`subprice`,`total_ht`,`total_tva`,`total_ttc`,`rang`)
VALUES
(50,50,'50% Deposit — E-Commerce Build','Deposit per PROP-2026-0001. Balance due at launch.',NULL,1,1,0,0,2006.50,2006.50,0,2006.50,1),
(51,51,NULL,NULL,55,1,1,0,0,399.00,399.00,0,399.00,1),
(52,52,'50% Deposit — Community Site Build','Deposit per PROP-2026-0003. Balance due at launch.',NULL,1,1,0,0,1600.00,1600.00,0,1600.00,1),
(53,53,'50% Deposit — Corporate Site + Brand','Deposit per PROP-2026-0004. Balance due at launch.',NULL,1,1,0,0,1299.00,1299.00,0,1299.00,1)
ON DUPLICATE KEY UPDATE `rang`=VALUES(`rang`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_facture_extrafields` (`rowid`,`tms`,`fk_object`,`payment_method`,`is_deposit`,`balance_due`) VALUES
(50,NOW(),50,'stripe',1,'2026-06-10'),(51,NOW(),51,'ach',0,NULL),
(52,NOW(),52,'stripe',1,'2026-08-15'),(53,NOW(),53,'stripe',1,'2026-05-31')
ON DUPLICATE KEY UPDATE `payment_method`=VALUES(`payment_method`),`is_deposit`=VALUES(`is_deposit`),`balance_due`=VALUES(`balance_due`)
SQL, $errors);
	}

	// ── S17: Base contracts ───────────────────────────────────────────────────

	private function seedBaseContracts(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_contrat`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`fk_soc`,`fk_projet`,`date_contrat`,`statut`,`fk_user_author`,`note_public`,`note_private`)
VALUES
(50,NOW(),NOW(),'CONT-2026-0001',1,52,52,'2026-01-05',1,1,'Annual grant consulting retainer. Includes monthly strategy sessions and up to two full grant applications per year.','DEMO — Brightpath annual retainer. Auto-invoice monthly.'),
(51,NOW(),NOW(),'CONT-2026-0002',1,55,NULL,'2026-02-01',1,1,'Moko WaaS monthly service. Includes hosting, maintenance, and unlimited content updates.','DEMO — Clearwater Media WaaS. Billed monthly.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_contratdet`
  (`rowid`,`tms`,`fk_contrat`,`label`,`description`,`fk_product`,`product_type`,`qty`,`remise_percent`,`subprice`,`total_ht`,`tva_tx`,`total_tva`,`total_ttc`,`statut`,`date_ouverture_prevue`,`date_fin_validite`,`fk_user_author`,`rang`)
VALUES
(50,NOW(),50,'Consulting Retainer — Monthly','Monthly consulting hours, strategy, and grant support.',55,1,1,0,399.00,399.00,0,0,399.00,4,'2026-01-01','2026-12-31',1,1),
(51,NOW(),50,'Grant Writing — Full Application','Up to 2 full grant applications per year at retainer rate.',54,1,2,22.40,750.00,1170.00,0,0,1170.00,0,'2026-01-01','2026-12-31',1,2),
(52,NOW(),51,'Website — WaaS Monthly Service','Managed Joomla hosting, maintenance, and content edits.',63,1,1,0,125.00,125.00,0,0,125.00,4,'2026-02-01','2027-01-31',1,1),
(53,NOW(),51,'Hosting — Monthly Pass-Through','Server cost pass-through billed at cost.',70,1,1,0,12.00,12.00,0,0,12.00,4,'2026-02-01','2027-01-31',1,2)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_contrat_extrafields` (`rowid`,`tms`,`fk_object`,`billing_frequency`,`auto_renew`,`annual_value`,`renewal_notice_days`) VALUES
(50,NOW(),50,'monthly',0,5136.00,30),(51,NOW(),51,'monthly',1,1644.00,30)
ON DUPLICATE KEY UPDATE `billing_frequency`=VALUES(`billing_frequency`),`annual_value`=VALUES(`annual_value`)
SQL, $errors);
	}

	// ── S18–S19: Bank account and payments ────────────────────────────────────

	private function seedBankAndPayments(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_bank_account`
  (`rowid`,`datec`,`tms`,`ref`,`label`,`entity`,`bank`,`number`,`currency_code`,`rappro`,`min_allowed`,`clos`,`courant`,`fk_user_author`,`account_number`,`fk_pays`,`comment`)
VALUES
(50,NOW(),NOW(),'DEMO-CHK','Demo Operating — Checking',1,'Demo Bank NA','DEMO-ACCOUNT-0000','USD',0,0,0,1,1,'DEMO-ACCT-0000',840,'DEMO bank account. Replace with real credentials before production.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_paiement`
  (`rowid`,`datec`,`tms`,`datep`,`amount`,`fk_paiement`,`num_paiement`,`note`,`fk_bank`,`fk_user_creat`)
VALUES
(50,NOW(),NOW(),'2026-01-18',399.00,2,'DEMO-ACH-001','Brightpath Jan retainer — ACH received 2026-01-18. DEMO.',50,1),
(51,NOW(),NOW(),'2026-01-20',500.00,6,'DEMO-STRIPE-001','Pinnacle Goods partial deposit via Stripe. DEMO.',50,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_paiement_facture` (`rowid`,`fk_paiement`,`fk_facture`,`amount`) VALUES
(50,50,51,399.00),(51,51,50,500.00)
ON DUPLICATE KEY UPDATE `amount`=VALUES(`amount`)
SQL, $errors);

		$this->exec("UPDATE `llx_facture` SET `paye`=1,`fk_statut`=2 WHERE `rowid`=51", $errors);
	}

	// ── S20: Interventions ────────────────────────────────────────────────────

	private function seedInterventions(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_fichinter`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`fk_soc`,`fk_projet`,`datei`,`fk_statut`,`fk_user_author`,`note_public`,`note_private`)
VALUES
(50,NOW(),NOW(),'INT-2026-0001',1,50,50,'2026-02-20',3,1,'Additional revision round on homepage mockup per client request.','DEMO — invoiced. 1hr design revision, tkim. Billed at FEE-REVISION rate.'),
(51,NOW(),NOW(),'INT-2026-0002',1,51,51,'2026-03-10',3,1,'DNS configuration review and hold during pre-launch prep.','DEMO — invoiced. 1.5hr jmiller + mwebb DNS troubleshooting.'),
(52,NOW(),NOW(),'INT-2026-0003',1,52,52,'2026-03-15',3,1,'Extended grant research session — additional funders identified.','DEMO — non-invoiced. Covered under retainer. 2hr deep research.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_fichinterdet` (`rowid`,`fk_fichinter`,`date`,`duree`,`description`,`rang`) VALUES
(50,50,'2026-02-20',3600,'Homepage revision — additional round per client feedback.',1),
(51,51,'2026-03-10',5400,'DNS review: checked TTLs, held cutover pending registrar update.',1),
(52,52,'2026-03-15',7200,'Identified 6 additional grant opportunities; updated calendar.',1)
ON DUPLICATE KEY UPDATE `note`=VALUES(`note`)
SQL, $errors);
	}

	// ── S6: Base users ────────────────────────────────────────────────────────

	private function seedBaseUsers(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
UPDATE `llx_user` SET
  `login`='jmiller',`lastname`='Miller',`firstname`='Jonathan',
  `email`='jmiller@democonsulting.example',`job`='Principal Consultant',
  `admin`=1,`statut`=1,`entity`=1,`tms`=NOW()
WHERE `rowid`=1
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_user`
  (`rowid`,`datec`,`tms`,`login`,`entity`,`lastname`,`firstname`,`email`,`job`,`admin`,`statut`,`pass_crypted`,`employee`)
VALUES
(50,NOW(),NOW(),'snolan',1,'Nolan','Sarah','snolan@democonsulting.example','Project Manager',0,1,'$2y$10$DEMO.PLACEHOLDER.HASH.NOT.FOR.PROD.XXXXXXXXXXXXXXXXXX',1),
(51,NOW(),NOW(),'mwebb',1,'Webb','Marcus','mwebb@contractor.example','Web Developer',0,1,'$2y$10$DEMO.PLACEHOLDER.HASH.NOT.FOR.PROD.XXXXXXXXXXXXXXXXXX',1),
(52,NOW(),NOW(),'dross',1,'Ross','Dana','dross@democonsulting.example','Bookkeeper',0,1,'$2y$10$DEMO.PLACEHOLDER.HASH.NOT.FOR.PROD.XXXXXXXXXXXXXXXXXX',1),
(53,NOW(),NOW(),'tkim',1,'Kim','Tara','tkim@contractor.example','Graphic Designer',0,1,'$2y$10$DEMO.PLACEHOLDER.HASH.NOT.FOR.PROD.XXXXXXXXXXXXXXXXXX',1),
(54,NOW(),NOW(),'bstewart',1,'Stewart','Ben','bstewart@contractor.example','Junior Dev',0,1,'$2y$10$DEMO.PLACEHOLDER.HASH.NOT.FOR.PROD.XXXXXXXXXXXXXXXXXX',1),
(55,NOW(),NOW(),'apark',1,'Park','Alex','apark@demo.example','Demo Observer',0,1,'$2y$10$DEMO.PLACEHOLDER.HASH.NOT.FOR.PROD.XXXXXXXXXXXXXXXXXX',0)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_user_extrafields` (`rowid`,`tms`,`fk_object`) VALUES
(1,NOW(),1),(50,NOW(),50),(51,NOW(),51),(52,NOW(),52),(53,NOW(),53),(54,NOW(),54),(55,NOW(),55)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_usergroup` (`rowid`,`nom`,`entity`,`datec`,`tms`,`note`) VALUES
(50,'Admin',1,NOW(),NOW(),'Full access — Principal Consultant'),
(51,'Project Manager',1,NOW(),NOW(),'Create/edit projects and proposals; view invoices'),
(52,'Contractor',1,NOW(),NOW(),'Log time on assigned tasks; view own interventions'),
(53,'Finance',1,NOW(),NOW(),'View and create invoices and contracts; no project editing')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_usergroup_user` (`rowid`,`entity`,`fk_user`,`fk_usergroup`) VALUES
(50,1,1,50),(51,1,50,51),(52,1,51,52),(53,1,52,53),(54,1,53,52),(55,1,54,52),(56,1,55,50)
ON DUPLICATE KEY UPDATE `entity`=VALUES(`entity`)
SQL, $errors);
	}

	// ── S9B: Additional contacts ─────────────────────────────────────────────

	private function seedAdditionalContacts(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_socpeople`
  (`rowid`,`datec`,`tms`,`fk_soc`,`entity`,`ref_ext`,`name_alias`,`fk_parent`,
   `civility`,`lastname`,`firstname`,`address`,`zip`,`town`,`fk_departement`,`fk_pays`,
   `geolat`,`geolong`,`geopoint`,`georesultcode`,`birthday`,`poste`,
   `phone`,`phone_perso`,`phone_mobile`,`fax`,`url`,`email`,`socialnetworks`,
   `photo`,`no_email`,`priv`,`fk_prospectlevel`,`fk_stcommcontact`,
   `fk_user_creat`,`fk_user_modif`,`note_private`,`note_public`,
   `default_lang`,`canvas`,`import_key`,`statut`,`ip`)
VALUES
(100,NOW(),NOW(),50,1,NULL,NULL,NULL,NULL,'Chen','Patricia',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'E-Commerce Manager','555-0102',NULL,NULL,NULL,NULL,'pchen@pinnaclegoods.example',NULL,NULL,0,0,NULL,NULL,1,1,'Secondary contact — manages day-to-day product catalog and order ops.','Product catalog and e-commerce operations.',NULL,NULL,NULL,1,NULL),
(101,NOW(),NOW(),50,1,NULL,NULL,NULL,NULL,'Garrett','Mike',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'IT / Technical Lead','555-0103',NULL,NULL,NULL,NULL,'mgarrett@pinnaclegoods.example',NULL,NULL,0,0,NULL,NULL,1,1,'Technical contact — handles hosting, DNS, and dev access.','Technical liaison for build and launch phases.',NULL,NULL,NULL,1,NULL),
(102,NOW(),NOW(),51,1,NULL,NULL,NULL,NULL,'Kim','David',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Communications Coordinator','555-0201',NULL,NULL,NULL,NULL,'dkim@riverstonecg.example',NULL,NULL,0,0,NULL,NULL,1,1,'Handles content review and member announcements.','Content and comms point of contact.',NULL,NULL,NULL,1,NULL),
(103,NOW(),NOW(),51,1,NULL,NULL,NULL,NULL,'Torres','Ana',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Volunteer Coordinator','555-0202',NULL,NULL,NULL,NULL,'atorres@riverstonecg.example',NULL,NULL,0,0,NULL,NULL,1,1,'Manages member directory data and volunteer listings.','Member directory and event coordination.',NULL,NULL,NULL,1,NULL),
(104,NOW(),NOW(),52,1,NULL,NULL,NULL,NULL,'Washington','James',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Development Director','555-0301',NULL,NULL,NULL,NULL,'jwashington@brightpathnonprofit.example',NULL,NULL,0,0,NULL,NULL,1,1,'Leads grant strategy and manages foundation relationships.','Primary grant strategy partner.',NULL,NULL,NULL,1,NULL),
(105,NOW(),NOW(),52,1,NULL,NULL,NULL,NULL,'Reyes','Sofia',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Program Manager','555-0302',NULL,NULL,NULL,NULL,'sreyes@brightpathnonprofit.example',NULL,NULL,0,0,NULL,NULL,1,1,'Provides program data and outcomes for grant narratives.','Program outcomes and reporting data.',NULL,NULL,NULL,1,NULL),
(106,NOW(),NOW(),53,1,NULL,NULL,NULL,NULL,'Nakamura','Yuki',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Creative Director','555-0401',NULL,NULL,NULL,NULL,'ynakamura@apexdigital.example',NULL,NULL,0,0,NULL,NULL,1,1,'Reviews brand deliverables and approves design direction.','Design approvals and brand feedback.',NULL,NULL,NULL,1,NULL),
(107,NOW(),NOW(),53,1,NULL,NULL,NULL,NULL,'Murphy','Sean',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Marketing Manager','555-0402',NULL,NULL,NULL,NULL,'smurphy@apexdigital.example',NULL,NULL,0,0,NULL,NULL,1,1,'Manages content for the corporate site; handles copy review.','Content strategy and copy review.',NULL,NULL,NULL,1,NULL),
(108,NOW(),NOW(),54,1,NULL,NULL,NULL,NULL,'Osei','Bernard',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'IT Coordinator','555-0501',NULL,NULL,NULL,NULL,'bosei@ironwoodcharter.example',NULL,NULL,0,0,NULL,NULL,1,1,'Manages school tech infrastructure; technical approvals.','Technical liaison and hosting contact.',NULL,NULL,NULL,1,NULL),
(109,NOW(),NOW(),54,1,NULL,NULL,NULL,NULL,'Rivera','Diana',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Parent Liaison / Community Outreach','555-0502',NULL,NULL,NULL,NULL,'drivera@ironwoodcharter.example',NULL,NULL,0,0,NULL,NULL,1,1,'Community stakeholder; provides feedback on parent-facing features.','Community and parent communication feedback.',NULL,NULL,NULL,1,NULL),
(110,NOW(),NOW(),55,1,NULL,NULL,NULL,NULL,'Foster','Amanda',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Digital Producer','555-0601',NULL,NULL,NULL,NULL,'afoster@clearwatermedia.example',NULL,NULL,0,0,NULL,NULL,1,1,'Day-to-day site content updates and media uploads.','Content updates and WaaS task submissions.',NULL,NULL,NULL,1,NULL),
(111,NOW(),NOW(),55,1,NULL,NULL,NULL,NULL,'Singh','Priya',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Advertising Sales Manager','555-0602',NULL,NULL,NULL,NULL,'psingh@clearwatermedia.example',NULL,NULL,0,0,NULL,NULL,1,1,'Handles billing questions and ad integration requirements.','Billing and advertising requirements.',NULL,NULL,NULL,1,NULL),
(112,NOW(),NOW(),60,1,NULL,NULL,NULL,NULL,'Pham','Anthony',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Lead Physician','555-0701',NULL,NULL,NULL,NULL,'apham@harborviewwellness.example',NULL,NULL,0,0,NULL,NULL,1,1,'Decision maker on technology investments.','Clinical leadership — key decision maker.',NULL,NULL,NULL,1,NULL),
(113,NOW(),NOW(),61,1,NULL,NULL,NULL,NULL,'Olsen','Rachel',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Operations Manager','555-0801',NULL,NULL,NULL,NULL,'rolsen@summittrade.example',NULL,NULL,0,0,NULL,NULL,1,1,'Day-to-day operations; evaluating B2B portal needs.','Operations stakeholder for B2B project.',NULL,NULL,NULL,1,NULL),
(114,NOW(),NOW(),62,1,NULL,NULL,NULL,NULL,'Demo','Support',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Technical Support','555-0901',NULL,NULL,NULL,NULL,'support@demohost.example',NULL,NULL,0,0,NULL,NULL,1,1,'DEMO vendor contact — tech support escalation.','Technical support channel.',NULL,NULL,NULL,1,NULL),
(115,NOW(),NOW(),63,1,NULL,NULL,NULL,NULL,'Demo','Accounts',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Account Representative','555-1001',NULL,NULL,NULL,NULL,'accounts@printbridge.example',NULL,NULL,0,0,NULL,NULL,1,1,'DEMO vendor contact — order status and invoicing.','Order and account management.',NULL,NULL,NULL,1,NULL),
(116,NOW(),NOW(),64,1,NULL,NULL,NULL,NULL,'Alvarez','Carmen',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Project Coordinator','555-1101',NULL,NULL,NULL,NULL,'calvarez@skylinesoftware.example',NULL,NULL,0,0,NULL,NULL,1,1,'Coordinates subcontracted dev work and milestone sign-offs.','Project coordination and deliverable tracking.',NULL,NULL,NULL,1,NULL),
(117,NOW(),NOW(),65,1,NULL,NULL,NULL,NULL,'Grant','Thomas',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'Studio Manager','555-1201',NULL,NULL,NULL,NULL,'tgrant@meridiandesign.example',NULL,NULL,0,0,NULL,NULL,1,1,'Manages studio schedule, subcontract agreements, and invoicing.','Operational and billing contact.',NULL,NULL,NULL,1,NULL)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_socpeople_extrafields` (`rowid`,`tms`,`fk_object`) VALUES
(100,NOW(),100),(101,NOW(),101),(102,NOW(),102),(103,NOW(),103),(104,NOW(),104),
(105,NOW(),105),(106,NOW(),106),(107,NOW(),107),(108,NOW(),108),(109,NOW(),109),
(110,NOW(),110),(111,NOW(),111),(112,NOW(),112),(113,NOW(),113),(114,NOW(),114),
(115,NOW(),115),(116,NOW(),116),(117,NOW(),117)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_societe_contacts` (`rowid`,`fk_soc`,`fk_c_type_contact`,`element_id`,`element_type`,`source`) VALUES
(100,50,NULL,100,'socpeople','external'),(101,50,NULL,101,'socpeople','external'),
(102,51,NULL,102,'socpeople','external'),(103,51,NULL,103,'socpeople','external'),
(104,52,NULL,104,'socpeople','external'),(105,52,NULL,105,'socpeople','external'),
(106,53,NULL,106,'socpeople','external'),(107,53,NULL,107,'socpeople','external'),
(108,54,NULL,108,'socpeople','external'),(109,54,NULL,109,'socpeople','external'),
(110,55,NULL,110,'socpeople','external'),(111,55,NULL,111,'socpeople','external'),
(112,60,NULL,112,'socpeople','external'),(113,61,NULL,113,'socpeople','external'),
(114,62,NULL,114,'socpeople','external'),(115,63,NULL,115,'socpeople','external'),
(116,64,NULL,116,'socpeople','external'),(117,65,NULL,117,'socpeople','external')
ON DUPLICATE KEY UPDATE `fk_soc`=VALUES(`fk_soc`)
SQL, $errors);
	}

	// ── S8: Base third parties ────────────────────────────────────────────────

	private function seedBaseThirdParties(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_societe`
  (`rowid`,`statut`,`tms`,`datec`,`nom`,`entity`,`code_client`,`code_fournisseur`,
   `address`,`zip`,`town`,`fk_pays`,`email`,`url`,`phone`,
   `client`,`fournisseur`,`note_private`,`fk_stcomm`,`fk_user_creat`)
VALUES
(50,0,NOW(),NOW(),'Pinnacle Goods Co.',1,'CU-M001',NULL,'100 Demo Way','37040','Demo City',840,'contact@pinnaclegoods.example','https://pinnaclegoods.example','555-0101',2,0,'DEMO — fictional. E-commerce build. Deposit paid, build active.',2,1),
(51,0,NOW(),NOW(),'Riverstone Community Group',1,'CU-M002',NULL,'200 Demo Blvd','37040','Demo City',840,'info@riverstonecg.example','https://riverstonecg.example','555-0102',2,0,'DEMO — fictional. Joomla community site. Design phase.',2,1),
(52,0,NOW(),NOW(),'Brightpath Nonprofit Solutions',1,'CU-M003',NULL,'300 Sample St','37040','Demo City',840,'hello@brightpathnp.example','https://brightpathnp.example','555-0103',2,0,'DEMO — fictional. Annual grant consulting retainer. Contract active.',2,1),
(53,0,NOW(),NOW(),'Apex Digital Agency',1,'CU-M004',NULL,'400 Placeholder Ln','37040','Demo City',840,'hello@apexdigital.example','https://apexdigital.example','555-0104',2,0,'DEMO — fictional. Corporate site + brand identity. Discovery complete.',2,1),
(54,0,NOW(),NOW(),'Ironwood Charter School',1,'CU-M005',NULL,'500 Test Ave','37040','Demo City',840,'admin@ironwoodcharter.example','https://ironwoodcharter.example','555-0105',2,0,'DEMO — fictional. Education nonprofit. Website redesign + grant support.',2,1),
(55,0,NOW(),NOW(),'Clearwater Media Group',1,'CU-M006',NULL,'600 Example Dr','37040','Demo City',840,'press@clearwatermedia.example','https://clearwatermedia.example','555-0106',2,0,'DEMO — fictional. Media/publishing. WaaS monthly retainer.',2,1),
(60,0,NOW(),NOW(),'Harborview Wellness Clinic',1,'CU-M007',NULL,'700 Mock Rd','37040','Demo City',840,'info@harborviewwellness.example','','555-0107',1,0,'DEMO — prospect. Healthcare clinic. Initial consult scheduled.',2,1),
(61,0,NOW(),NOW(),'Summit Trade Co.',1,'CU-M008',NULL,'800 Fake Blvd','37040','Demo City',840,'sales@summittrade.example','','555-0108',1,0,'DEMO — prospect. B2B distributor. Proposal requested.',2,1),
(62,0,NOW(),NOW(),'DemoHost Pro',1,NULL,'VE-M001','900 Vendor Way','37040','Demo City',840,'billing@demohost.example','https://demohost.example','555-0109',0,1,'DEMO — vendor only. Fictional hosting provider for pass-through billing.',2,1),
(63,0,NOW(),NOW(),'PrintBridge Supply Co.',1,NULL,'VE-M002','1000 Supply St','37040','Demo City',840,'orders@printbridge.example','','555-0110',0,1,'DEMO — vendor only. Print and swag supplier.',2,1),
(64,0,NOW(),NOW(),'Skyline Software Partners',1,'CU-M009','VE-M003','1100 Both Ways Ave','37040','Demo City',840,'hello@skylinesoftware.example','https://skylinesoftware.example','555-0111',2,1,'DEMO — vendor + customer. Dev subcontractor and occasional client.',2,1),
(65,0,NOW(),NOW(),'Meridian Design Studio',1,'CU-M010','VE-M004','1200 Creative Blvd','37040','Demo City',840,'studio@meridiandesign.example','https://meridiandesign.example','555-0112',2,1,'DEMO — vendor + customer. Design partner; also a WaaS client.',2,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_societe_extrafields` (`rowid`,`tms`,`fk_object`) VALUES
(50,NOW(),50),(51,NOW(),51),(52,NOW(),52),(53,NOW(),53),(54,NOW(),54),(55,NOW(),55),
(60,NOW(),60),(61,NOW(),61),(62,NOW(),62),(63,NOW(),63),(64,NOW(),64),(65,NOW(),65)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── S9: Base contacts ─────────────────────────────────────────────────────

	private function seedBaseContacts(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_socpeople`
  (`rowid`,`datec`,`tms`,`fk_soc`,`entity`,`lastname`,`firstname`,
   `email`,`phone`,`poste`,`statut`,`fk_user_creat`,`note_private`)
VALUES
(50,NOW(),NOW(),50,1,'Garrett','Tom','tgarrett@pinnaclegoods.example','555-0201','Owner',1,1,'DEMO contact'),
(51,NOW(),NOW(),51,1,'Okafor','Blessing','bokafor@riverstonecg.example','555-0202','Executive Director',1,1,'DEMO contact'),
(52,NOW(),NOW(),52,1,'Hernandez','Carmen','chernandez@brightpathnp.example','555-0203','Executive Director',1,1,'DEMO contact'),
(53,NOW(),NOW(),53,1,'Fitzgerald','Ryan','rfitzgerald@apexdigital.example','555-0204','CEO',1,1,'DEMO contact'),
(54,NOW(),NOW(),54,1,'Yamamoto','Keiko','kyamamoto@ironwoodcharter.example','555-0205','Principal',1,1,'DEMO contact'),
(55,NOW(),NOW(),55,1,'Vance','Derek','dvance@clearwatermedia.example','555-0206','Editor in Chief',1,1,'DEMO contact'),
(60,NOW(),NOW(),60,1,'Nguyen','Lisa','lnguyen@harborviewwellness.example','555-0207','Office Manager',1,1,'DEMO prospect contact'),
(61,NOW(),NOW(),61,1,'Brooks','Kevin','kbrooks@summittrade.example','555-0208','Purchasing Director',1,1,'DEMO prospect contact'),
(62,NOW(),NOW(),62,1,'Demo','Billing','billing@demohost.example','555-0209','Accounts',1,1,'DEMO vendor contact'),
(63,NOW(),NOW(),63,1,'Demo','Sales','sales@printbridge.example','555-0210','Sales Rep',1,1,'DEMO vendor contact'),
(64,NOW(),NOW(),64,1,'Patel','Raj','rpatel@skylinesoftware.example','555-0211','CTO',1,1,'DEMO dual contact'),
(65,NOW(),NOW(),65,1,'Laurent','Marie','mlaurent@meridiandesign.example','555-0212','Creative Director',1,1,'DEMO dual contact')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── S22: Support tickets ──────────────────────────────────────────────────

	private function seedTickets(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_ticket`
  (`rowid`,`datec`,`tms`,`entity`,`ref`,`track_id`,
   `fk_soc`,`fk_project`,`fk_contract`,
   `fk_user_create`,`fk_user_assign`,
   `subject`,`message`,
   `type_code`,`category_code`,`severity_code`,
   `fk_statut`,`resolution`,`progress`,
   `date_read`,`date_close`,
   `notify_tiers_at_create`,
   `note_public`,`note_private`)
VALUES
(50,'2026-03-01 09:14:00',NOW(),1,'TK-2026-0001','tic-pinn-0001-chk',
 50,50,NULL,1,51,
 'Checkout page redirecting to 404 after payment gateway config',
 'After we configured the Stripe test keys per the build doc, clicking "Place Order" redirects to a 404 page instead of the order confirmation. Reproducible every time in incognito.',
 'ISSUE','OTHER','HIGH',3,1,100,
 '2026-03-01 10:02:00','2026-03-02 16:45:00',0,
 'Issue resolved. VirtueMart return URL was pointing to a non-existent route after the gateway module updated its callback path. Corrected in VirtueMart payment plugin config.',
 'DEMO — mwebb fixed. Root cause: VM payment plugin callback URL mismatch after Stripe module update.'),
(51,'2026-03-04 14:22:00',NOW(),1,'TK-2026-0002','tic-pinn-0002-img',
 50,50,NULL,1,54,
 'Product images look squished on mobile — aspect ratio off',
 'On iPhone 14 and Galaxy S23 the product thumbnails look squished vertically. Fine on desktop.',
 'ISSUE','OTHER','NORMAL',3,1,100,
 '2026-03-05 11:30:00','2026-03-05 15:00:00',0,
 'Fixed. Cassiopeia override CSS was applying a fixed height to .product-img without preserving aspect ratio. Changed to object-fit: contain with auto height.',
 'DEMO — bstewart fixed. Quick CSS one-liner. Billed 0 hrs (under scope).'),
(52,'2026-03-10 11:05:00',NOW(),1,'TK-2026-0003','tic-pinn-0003-shp',
 50,50,NULL,50,51,
 'Add separate shipping rate for Alaska and Hawaii',
 'We need a second shipping zone for AK and HI orders — flat rate $18.99 instead of the standard continental rate.',
 'REQUEST','OTHER','NORMAL',1,NULL,30,
 '2026-03-10 13:00:00',NULL,0,
 'In scope — VirtueMart shipping zones support this natively. Assigned to mwebb to configure before UAT.',
 'DEMO — scheduled for week of 2026-03-17. Straightforward VM shipping zone config.'),
(53,'2026-03-06 10:30:00',NOW(),1,'TK-2026-0004','tic-rvst-0001-cal',
 51,51,NULL,50,51,
 'Events calendar not showing on Safari — blank white box',
 'The events section shows a blank white box in Safari 17 on both iPhone and Mac. Chrome and Firefox work fine.',
 'ISSUE','OTHER','HIGH',3,1,100,
 '2026-03-06 11:15:00','2026-03-07 14:00:00',0,
 'Resolved. A Safari-incompatible CSS grid subgrid property was causing the calendar container to collapse. Replaced with a fallback flexbox layout.',
 'DEMO — mwebb fix, 1.5hr. CSS subgrid not supported in Safari 17. Covered under scope.'),
(54,'2026-03-11 08:50:00',NOW(),1,'TK-2026-0005','tic-rvst-0002-frm',
 51,51,NULL,102,NULL,
 'Can we add a private board for board members only?',
 'Is it possible to have a section of the forum that is only visible to board members? We would have maybe 8-10 people who need access.',
 'REQUEST','OTHER','LOW',0,NULL,0,
 NULL,NULL,0,
 'Logged. Possible via JomSocial group with restricted visibility. Needs scoping — likely a FEE-REVISION line or small add-on.',
 'DEMO — new, unassigned. Out of original scope. Evaluate for change order.'),
(55,'2026-02-03 15:45:00',NOW(),1,'TK-2026-0006','tic-bpth-0001-inv',
 52,52,50,52,52,
 'January retainer invoice sent to old email address',
 'We received the January invoice at the general inbox instead of accounting@brightpathnonprofit.example. Please update your records and resend.',
 'COM','OTHER','LOW',3,1,100,
 '2026-02-03 16:10:00','2026-02-03 17:00:00',0,
 'Updated billing contact email in Dolibarr. Invoice resent to correct address. Client confirmed receipt.',
 'DEMO — dross handled. Admin correction, no billable time.'),
(56,'2026-03-08 09:00:00',NOW(),1,'TK-2026-0007','tic-bpth-0002-grt',
 52,52,50,104,1,
 'Research IMLS Grants for Museums and Libraries program — are we eligible?',
 'We have been expanding our digital literacy programming. Can you research whether the IMLS Grants for Museums and Libraries program applies to nonprofit education orgs like us?',
 'HELP','OTHER','NORMAL',1,NULL,50,
 '2026-03-08 10:30:00',NULL,0,
 'Researching eligibility criteria and current program cycle. Preliminary review suggests possible fit via the Community Catalyst Grants track. Full memo due 2026-03-21.',
 'DEMO — jmiller assigned. Covered under retainer. Memo in progress.'),
(57,'2026-02-20 11:00:00',NOW(),1,'TK-2026-0008','tic-apex-0001-lgr',
 53,53,NULL,106,53,
 'Logo Round 2 feedback — adjustments requested',
 'Love the direction on concept B! Adjustments: (1) the wordmark feels too light at small sizes; (2) the icon mark needs more padding inside the circle; (3) can we see it reversed on the brand navy?',
 'REQUEST','OTHER','NORMAL',3,1,100,
 '2026-02-20 13:00:00','2026-02-24 17:00:00',0,
 'Revisions delivered 2026-02-24. Client approved final logo. Proceeding to brand identity package.',
 'DEMO — tkim handled. Covered as included revision round per PROP-2026-0004.'),
(58,'2026-03-09 14:15:00',NOW(),1,'TK-2026-0009','tic-clwr-0001-ctn',
 55,NULL,51,110,51,
 'Add new staff bio — Derek is leaving, introducing new EIC',
 'Derek Vance is moving on at end of March. Our new EIC is Priya Mehta. Can you update the About/Staff page with her bio and headshot?',
 'REQUEST','OTHER','NORMAL',1,NULL,20,
 '2026-03-09 15:30:00',NULL,0,
 'Waiting on content and headshot from Amanda Foster. Page update ready to go once assets arrive.',
 'DEMO — mwebb assigned. WaaS content update. Covered under CONT-2026-0002.'),
(59,'2026-03-12 08:30:00',NOW(),1,'TK-2026-0010','tic-clwr-0002-ssl',
 55,NULL,51,111,NULL,
 'Some users reporting SSL certificate warning on site',
 'Two readers emailed us saying they got a "your connection is not private" warning when visiting the site this morning.',
 'ISSUE','OTHER','HIGH',0,NULL,0,
 NULL,NULL,0,
 'Logged. Possible mixed-content or cert propagation issue. Needs immediate review — assigned on next check-in.',
 'DEMO — new, unassigned. Priority HIGH. Check cert expiry and mixed-content scan first.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_ticket_extrafields` (`rowid`,`tms`,`fk_object`) VALUES
(50,NOW(),50),(51,NOW(),51),(52,NOW(),52),(53,NOW(),53),(54,NOW(),54),
(55,NOW(),55),(56,NOW(),56),(57,NOW(),57),(58,NOW(),58),(59,NOW(),59)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── T1: Training users ────────────────────────────────────────────────────

	private function seedTrainingUsers(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_user`
  (`rowid`,`entity`,`admin`,`statut`,`employee`,`datec`,`tms`,
   `login`,`lastname`,`firstname`,`job`,`email`,
   `pass_crypted`,`fk_user_creat`)
VALUES
(60,1,0,1,0,NOW(),NOW(),'trainee01','One',  'Trainee','Training Participant','trainee01@democonsulting.example','$2y$10$Adrv.L2zvVfyfPXcWzU.h.ioQ.jiOEYLLpIIn9NtdgGe8W/vTj24y',1),
(61,1,0,1,0,NOW(),NOW(),'trainee02','Two',  'Trainee','Training Participant','trainee02@democonsulting.example','$2y$10$HnX7Yq2bp.YskkfWMKxdhO.bqEPkIEx.YBWj2wkJTzoFji69Ajt7O',1),
(62,1,0,1,0,NOW(),NOW(),'trainee03','Three','Trainee','Training Participant','trainee03@democonsulting.example','$2y$10$tAPiWooPXgw7d9DV2n5MhOSv.mqCf6vuZiLlSoVfKjDjPzOiFsyVC',1),
(63,1,0,1,0,NOW(),NOW(),'trainee04','Four', 'Trainee','Training Participant','trainee04@democonsulting.example','$2y$10$Vbr12L131wcVYAwMaIdQ6O8FSNoaJKzlonH/ShH94XHBTNoJndQ8m',1),
(64,1,0,1,0,NOW(),NOW(),'trainee05','Five', 'Trainee','Training Participant','trainee05@democonsulting.example','$2y$10$0reNZHKf8XCDmnjvo/H/3eyVr118A8KdheJCR5EjH.ltHgoL0qhka',1),
(65,1,0,1,0,NOW(),NOW(),'trainee06','Six',  'Trainee','Training Participant','trainee06@democonsulting.example','$2y$10$8twoT4W09pnov.QxHijGFuNpnXFLe/F2OVNKPLnnGVN/5cs7lTs.2',1),
(66,1,1,1,0,NOW(),NOW(),'trainer',  'Trainer','Demo',  'Training Facilitator','trainer@democonsulting.example', '$2y$10$MJ0rDIJSXBqFzmVnjhi8u.a64OEN7iGBWWqpquKCtFUVTN6DIzg.a',1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_user_extrafields` (`rowid`,`tms`,`fk_object`) VALUES
(60,NOW(),60),(61,NOW(),61),(62,NOW(),62),(63,NOW(),63),
(64,NOW(),64),(65,NOW(),65),(66,NOW(),66)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_usergroup_user` (`rowid`,`entity`,`fk_user`,`fk_usergroup`) VALUES
(60,1,60,50),(61,1,61,51),(62,1,62,52),(63,1,63,53),
(64,1,64,52),(65,1,65,51),(66,1,66,50)
ON DUPLICATE KEY UPDATE `entity`=VALUES(`entity`)
SQL, $errors);
	}

	// ── T2: Group rights + training role groups ───────────────────────────────

	private function seedTrainingGroups(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_usergroup_rights` (`rowid`,`entity`,`fk_usergroup`,`fk_id`) VALUES
(50,1,50,2401),(51,1,50,2402),(52,1,50,2403),
(53,1,50,2411),(54,1,50,2412),(55,1,50,2413),(56,1,50,2414),
(57,1,50,771), (58,1,50,772), (59,1,50,773),
(60,1,50,775), (61,1,50,776), (62,1,50,777), (63,1,50,779),
(64,1,50,1001),(65,1,50,1002),(66,1,50,1004),(67,1,50,1005),
(68,1,50,1181),(69,1,50,1231),(70,1,50,1232),
(71,1,51,2401),(72,1,51,2402),(73,1,51,2403),
(74,1,51,2411),(75,1,51,2412),(76,1,51,2413),
(77,1,51,771), (78,1,51,772),
(79,1,52,2401),(80,1,52,2402),
(81,1,52,771), (82,1,52,772),
(83,1,53,2401),(84,1,53,2411),
(85,1,53,771), (86,1,53,775),(87,1,53,776),(88,1,53,777),
(89,1,53,1181),(90,1,53,1231)
ON DUPLICATE KEY UPDATE `entity`=VALUES(`entity`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_usergroup` (`rowid`,`nom`,`entity`,`datec`,`tms`,`note`) VALUES
(70,'Basic',       1,NOW(),NOW(),'Training: Read-only orientation group'),
(71,'Sales',       1,NOW(),NOW(),'Training: Commercial focus — proposals, orders, CRM'),
(72,'Marketing',   1,NOW(),NOW(),'Training: Relationship focus — contacts, categories, agenda'),
(73,'Design & Dev',1,NOW(),NOW(),'Training: Technical focus — projects, tasks, tickets, time')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_usergroup_rights` (`rowid`,`entity`,`fk_usergroup`,`fk_id`) VALUES
(91,1,70,2401),
(92,1,71,2401),(93,1,71,2402),(94,1,71,2403),(95,1,71,771),(96,1,71,772),
(97,1,72,2401),(98,1,72,2402),(99,1,72,771),(100,1,72,772),
(101,1,73,2401),(102,1,73,2402),(103,1,73,2403),(104,1,73,771),(105,1,73,772)
ON DUPLICATE KEY UPDATE `entity`=VALUES(`entity`)
SQL, $errors);
	}

	// ── D1: Demo business users ───────────────────────────────────────────────
	//
	// Used in 'demo' mode instead of seedTrainingUsers().
	// IDs 70-76 are business personas (alice.martin…grace.kim).
	// Default password: Demo1234!  — hashed with bcrypt at seed time.
	// All users are employees, statut=1 (active), entity-aware.

	private function seedDemoUsers(int $e, array &$errors): void
	{
		// Compute bcrypt hashes at seed time — each call produces a unique salt
		$pw = [];
		for ($i = 0; $i < 7; $i++) {
			$pw[$i] = $this->db->escape(password_hash('Demo1234!', PASSWORD_BCRYPT, ['cost' => 10]));
		}

		$this->exec(
			"INSERT INTO `llx_user`"
			. " (`rowid`,`entity`,`admin`,`statut`,`employee`,`datec`,`tms`,"
			. "  `login`,`lastname`,`firstname`,`job`,`email`,`pass_crypted`,`fk_user_creat`)"
			. " VALUES"
			. " (70,{$e},0,1,1,NOW(),NOW(),'alice.martin', 'Martin', 'Alice', 'General Manager',    'alice.martin@democonsulting.example',  '{$pw[0]}',1),"
			. " (71,{$e},0,1,1,NOW(),NOW(),'bob.chen',     'Chen',   'Bob',   'Finance Manager',    'bob.chen@democonsulting.example',      '{$pw[1]}',1),"
			. " (72,{$e},0,1,1,NOW(),NOW(),'claire.dupont','Dupont', 'Claire','Sales Manager',      'claire.dupont@democonsulting.example', '{$pw[2]}',1),"
			. " (73,{$e},0,1,1,NOW(),NOW(),'david.miller', 'Miller', 'David', 'Account Executive',  'david.miller@democonsulting.example',  '{$pw[3]}',1),"
			. " (74,{$e},0,1,1,NOW(),NOW(),'emma.jones',   'Jones',  'Emma',  'Marketing Manager',  'emma.jones@democonsulting.example',    '{$pw[4]}',1),"
			. " (75,{$e},0,1,1,NOW(),NOW(),'frank.nguyen', 'Nguyen', 'Frank', 'Lead Developer',     'frank.nguyen@democonsulting.example',  '{$pw[5]}',1),"
			. " (76,{$e},0,1,1,NOW(),NOW(),'grace.kim',    'Kim',    'Grace', 'Senior Designer',    'grace.kim@democonsulting.example',     '{$pw[6]}',1)"
			. " ON DUPLICATE KEY UPDATE `tms`=NOW()",
			$errors
		);

		$this->exec(
			"INSERT INTO `llx_user_extrafields` (`rowid`,`tms`,`fk_object`) VALUES"
			. " (70,NOW(),70),(71,NOW(),71),(72,NOW(),72),(73,NOW(),73),"
			. " (74,NOW(),74),(75,NOW(),75),(76,NOW(),76)"
			. " ON DUPLICATE KEY UPDATE `tms`=NOW()",
			$errors
		);

		// Assign to demo groups: alice+bob→Management(74), claire+david→Sales(75),
		// emma→Marketing(76), frank+grace→Engineering(77)
		$this->exec(
			"INSERT INTO `llx_usergroup_user` (`rowid`,`entity`,`fk_user`,`fk_usergroup`) VALUES"
			. " (70,{$e},70,74),(71,{$e},71,74),"
			. " (72,{$e},72,75),(73,{$e},73,75),"
			. " (74,{$e},74,76),"
			. " (75,{$e},75,77),(76,{$e},76,77)"
			. " ON DUPLICATE KEY UPDATE `entity`=VALUES(`entity`)",
			$errors
		);

		$this->track('llx_user',             [70, 71, 72, 73, 74, 75, 76], $e);
		$this->track('llx_user_extrafields', [70, 71, 72, 73, 74, 75, 76], $e);
		$this->track('llx_usergroup_user',   [70, 71, 72, 73, 74, 75, 76], $e);
	}

	// ── D2: Demo department groups ────────────────────────────────────────────
	//
	// Used in 'demo' mode instead of seedTrainingGroups().
	// IDs 74-77: Management, Sales Dept, Marketing Dept, Engineering.
	// Maps to exercise groups: 74→70(Basic), 75→71(Sales), 76→72(Mktg), 77→73(Dev)

	private function seedDemoGroups(int $e, array &$errors): void
	{
		$this->exec(
			"INSERT INTO `llx_usergroup` (`rowid`,`nom`,`entity`,`datec`,`tms`,`note`) VALUES"
			. " (74,'Management',     {$e},NOW(),NOW(),'Demo: Senior leadership and finance'),"
			. " (75,'Sales Dept',     {$e},NOW(),NOW(),'Demo: Commercial team — proposals, orders, CRM'),"
			. " (76,'Marketing Dept', {$e},NOW(),NOW(),'Demo: Marketing and communications team'),"
			. " (77,'Engineering',    {$e},NOW(),NOW(),'Demo: Development and design team')"
			. " ON DUPLICATE KEY UPDATE `tms`=NOW()",
			$errors
		);

		// Assign read + basic module permissions matching their role
		$this->exec(
			"INSERT INTO `llx_usergroup_rights` (`rowid`,`entity`,`fk_usergroup`,`fk_id`) VALUES"
			. " (110,{$e},74,2401),(111,{$e},74,2402),(112,{$e},74,2403),"
			. " (113,{$e},75,2401),(114,{$e},75,2402),(115,{$e},75,2403),(116,{$e},75,771),(117,{$e},75,772),"
			. " (118,{$e},76,2401),(119,{$e},76,2402),(120,{$e},76,771),(121,{$e},76,772),"
			. " (122,{$e},77,2401),(123,{$e},77,2402),(124,{$e},77,2403),(125,{$e},77,771),(126,{$e},77,772)"
			. " ON DUPLICATE KEY UPDATE `entity`=VALUES(`entity`)",
			$errors
		);

		$this->track('llx_usergroup',        [74, 75, 76, 77], $e);
		$this->track('llx_usergroup_rights', [110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126], $e);
	}

	// ── T3: Training client third parties ────────────────────────────────────

	private function seedTrainingThirdParties(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_societe`
  (`rowid`,`statut`,`tms`,`datec`,`nom`,`entity`,`code_client`,
   `address`,`zip`,`town`,`fk_pays`,`email`,`url`,`phone`,
   `client`,`fournisseur`,`note_private`,`fk_stcomm`,`fk_user_creat`)
VALUES
(90,0,NOW(),NOW(),'Redwood Legal Group',       1,'CU-M009','900 Demo Pkwy','37042','Demo City',840,'contact@redwoodlegal.example',   'https://redwoodlegal.example',   '555-0190',1,0,'DEMO — Legal services firm. Prospect in intake. Draft proposal pending.',2,1),
(91,0,NOW(),NOW(),'Bellwether Bakehouse',       1,'CU-M010','910 Sample Ave','37040','Demo City',840,'hello@bellwetherbake.example',   'https://bellwetherbake.example', '555-0191',2,0,'DEMO — Artisan bakery + cafe. Proposal cancelled and revised. Signed revised scope.',2,1),
(92,0,NOW(),NOW(),'Maple Ridge Property Group', 1,'CU-M011','920 Test Blvd', '37041','Demo City',840,'info@mapleridgeprop.example',    'https://mapleridgeprop.example', '555-0192',2,0,'DEMO — Property management firm. Project fully closed. Both invoices paid.',2,1),
(93,0,NOW(),NOW(),'Thornton Hardware & Supply', 1,'CU-M012','930 Fake Rd',   '37040','Demo City',840,'accounts@thorntonhw.example',    'https://thorntonhw.example',    '555-0193',2,0,'DEMO — Local hardware retailer. Deposit invoice 45 days overdue. Late fee applied.',2,1),
(94,0,NOW(),NOW(),'Halcyon Health Partners',    1,'CU-M013','940 Demo Dr',   '37043','Demo City',840,'billing@halcyonhealth.example',  'https://halcyonhealth.example', '555-0194',2,0,'DEMO — Health and wellness practice. Monthly retainer active. 3 months billed.',2,1),
(95,0,NOW(),NOW(),'Vantage Point Logistics',    1,'CU-M014','950 Example St','37040','Demo City',840,'ops@vantagepointlog.example',    'https://vantagepointlog.example','555-0195',2,0,'DEMO — Regional logistics firm. Active web project with approved mid-project change order.',2,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_societe_extrafields` (`rowid`,`tms`,`fk_object`) VALUES
(90,NOW(),90),(91,NOW(),91),(92,NOW(),92),(93,NOW(),93),(94,NOW(),94),(95,NOW(),95)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_socpeople`
  (`rowid`,`datec`,`tms`,`fk_soc`,`entity`,`lastname`,`firstname`,
   `poste`,`phone`,`email`,`fk_pays`,`fk_user_creat`,`statut`)
VALUES
(90,NOW(),NOW(),90,1,'Whitmore', 'Patricia','Managing Partner',    '555-0190','p.whitmore@redwoodlegal.example',   840,1,1),
(91,NOW(),NOW(),91,1,'Bellamy',  'Cole',    'Owner',               '555-0191','cole@bellwetherbake.example',        840,1,1),
(92,NOW(),NOW(),92,1,'Cartwright','Sandra', 'Operations Director', '555-0192','scartwright@mapleridgeprop.example', 840,1,1),
(93,NOW(),NOW(),93,1,'Thornton', 'Dale',    'Owner/Accounts',      '555-0193','dale@thorntonhw.example',            840,1,1),
(94,NOW(),NOW(),94,1,'Nguyen',   'Linh',    'Practice Manager',    '555-0194','lnguyen@halcyonhealth.example',      840,1,1),
(95,NOW(),NOW(),95,1,'Marcello', 'Victor',  'IT Director',         '555-0195','vmarcello@vantagepointlog.example',  840,1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_socpeople_extrafields` (`rowid`,`tms`,`fk_object`) VALUES
(90,NOW(),90),(91,NOW(),91),(92,NOW(),92),(93,NOW(),93),(94,NOW(),94),(95,NOW(),95)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── T5+T6: Training projects + tasks ─────────────────────────────────────

	private function seedTrainingProjects(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_projet`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`title`,`description`,`fk_soc`,
   `dateo`,`datee`,`fk_statut`,`fk_user_creat`,`usage_task`,`usage_bill_time`,`note_private`)
VALUES
(60,NOW(),NOW(),'PROJ-0005',1,'Bellwether Bakehouse — Brochure Site + Logo',
 'Joomla brochure site with custom logo. Revised from original e-commerce scope.',
 91,'2026-02-10','2026-05-15',1,1,1,1,'DEMO — training scenario: revised scope'),
(61,NOW(),NOW(),'PROJ-0006',1,'Maple Ridge Property Group — Corporate Site',
 'Joomla corporate site, 8 pages, contact form, listings feed.',
 92,'2025-10-01','2026-01-31',2,1,1,1,'DEMO — training scenario: closed project.'),
(62,NOW(),NOW(),'PROJ-0007',1,'Thornton Hardware & Supply — Website Redesign',
 'Joomla site refresh, product catalog, contractor portal.',
 93,'2026-01-20','2026-06-30',1,1,1,1,'DEMO — training scenario: overdue invoice. Collections workflow.'),
(63,NOW(),NOW(),'PROJ-0008',1,'Vantage Point Logistics — Corporate + Portal',
 'Corporate site with customer login portal added mid-project via change order.',
 95,'2026-02-01','2026-07-31',1,1,1,1,'DEMO — training scenario: change order. Two proposals.'),
(64,NOW(),NOW(),'PROJ-0009',1,'Vantage Point Logistics — Customer Portal (CO)',
 'Change order project record for customer portal add-on. Linked to PROJ-0008.',
 95,'2026-04-01','2026-07-31',1,1,1,1,'DEMO — change order sub-project record.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_projet_extrafields` (`rowid`,`tms`,`fk_object`,`project_type`,`billing_type`,`estimated_hours`,`kickoff_confirmed`) VALUES
(60,NOW(),60,'web-build','fixed',50,1),
(61,NOW(),61,'web-build','fixed',40,1),
(62,NOW(),62,'web-build','fixed',60,1),
(63,NOW(),63,'web-build','fixed',80,1),
(64,NOW(),64,'web-build','fixed',30,1)
ON DUPLICATE KEY UPDATE `project_type`=VALUES(`project_type`),`estimated_hours`=VALUES(`estimated_hours`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_projet_task`
  (`rowid`,`datec`,`tms`,`fk_projet`,`fk_task_parent`,`label`,`planned_workload`,
   `dateo`,`datee`,`fk_statut`,`progress`,`rang`,`entity`,`fk_user_creat`)
VALUES
(300,NOW(),NOW(),60,0,'Discovery & Brand Brief',  7200,'2026-02-10','2026-02-14',1,100,1,1,1),
(301,NOW(),NOW(),60,0,'Logo Design',             18000,'2026-02-15','2026-03-01',1, 80,2,1,1),
(302,NOW(),NOW(),60,0,'Joomla Site Build',       14400,'2026-03-02','2026-04-15',1, 40,3,1,1),
(303,NOW(),NOW(),60,0,'Content Entry + QA',       7200,'2026-04-16','2026-05-05',1,  0,4,1,1),
(304,NOW(),NOW(),60,0,'Launch + Handoff',          3600,'2026-05-06','2026-05-15',1,  0,5,1,1),
(310,NOW(),NOW(),61,0,'Discovery & Sitemap',       7200,'2025-10-01','2025-10-10',3,100,1,1,1),
(311,NOW(),NOW(),61,0,'Design Phase',             14400,'2025-10-11','2025-10-31',3,100,2,1,1),
(312,NOW(),NOW(),61,0,'Build Phase',              18000,'2025-11-01','2025-12-15',3,100,3,1,1),
(313,NOW(),NOW(),61,0,'UAT + Launch',              7200,'2025-12-16','2026-01-15',3,100,4,1,1),
(314,NOW(),NOW(),61,0,'Post-Launch Review + Handoff',3600,'2026-01-16','2026-01-31',3,100,5,1,1),
(320,NOW(),NOW(),62,0,'Discovery + Requirements',  7200,'2026-01-20','2026-01-31',1,100,1,1,1),
(321,NOW(),NOW(),62,0,'Design Phase',             14400,'2026-02-01','2026-02-28',1, 60,2,1,1),
(322,NOW(),NOW(),62,0,'Build Phase',              18000,'2026-03-01','2026-05-15',1, 20,3,1,1),
(323,NOW(),NOW(),62,0,'Contractor Portal Module', 10800,'2026-03-15','2026-05-15',1, 10,4,1,1),
(324,NOW(),NOW(),62,0,'Launch',                    3600,'2026-05-16','2026-06-15',1,  0,5,1,1),
(330,NOW(),NOW(),63,0,'Discovery + Tech Spec',     7200,'2026-02-01','2026-02-14',1,100,1,1,1),
(331,NOW(),NOW(),63,0,'Design Phase',             14400,'2026-02-15','2026-03-15',1, 70,2,1,1),
(332,NOW(),NOW(),63,0,'Build — Corporate Site',   18000,'2026-03-16','2026-05-31',1, 30,3,1,1),
(333,NOW(),NOW(),63,0,'Launch Prep',               7200,'2026-06-01','2026-07-15',1,  0,4,1,1),
(340,NOW(),NOW(),64,0,'Portal Requirements + Auth Spec',7200,'2026-04-01','2026-04-10',1,50,1,1,1),
(341,NOW(),NOW(),64,0,'Customer Portal Build',    21600,'2026-04-11','2026-06-30',1, 10,2,1,1),
(342,NOW(),NOW(),64,0,'Portal QA + Launch',        7200,'2026-07-01','2026-07-31',1,  0,3,1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── T7: Training proposals ────────────────────────────────────────────────

	private function seedTrainingProposals(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_propal`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`fk_soc`,`fk_projet`,
   `datep`,`fin_validite`,`fk_statut`,`fk_user_author`,`fk_user_valid`,
   `total_ht`,`total_tva`,`total_ttc`,`note_public`,`note_private`)
VALUES
(60,NOW(),NOW(),'PROP-2026-0005',1,90,NULL,
 CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),0,1,NULL,
 0.00,0.00,0.00,
 'Thank you for considering Moko Consulting for your firm website project.',
 'DEMO — Redwood Legal draft proposal. Exercise 1.2: validate and send.'),
(61,NOW(),NOW(),'PROP-2026-0006',1,91,NULL,
 '2026-01-20','2026-02-04',4,1,NULL,
 5200.00,0.00,5200.00,
 'Full e-commerce site with loyalty program and online ordering.',
 'DEMO — Bellwether original proposal. CANCELLED — client budget ceiling $3,000.'),
(62,NOW(),NOW(),'PROP-2026-0007',1,91,60,
 '2026-02-05','2026-02-20',2,1,1,
 2847.99,0.00,2847.99,
 'Revised scope: Joomla brochure site and logo design. Excludes e-commerce.',
 'DEMO — Bellwether revised + signed 2026-02-08.'),
(63,NOW(),NOW(),'PROP-2026-0008',1,92,61,
 '2025-09-15','2025-09-30',3,1,1,
 4100.00,0.00,4100.00,
 'Joomla corporate website — 8 pages, contact form, property listings integration.',
 'DEMO — Maple Ridge. Fully paid and closed.'),
(64,NOW(),NOW(),'PROP-2026-0009',1,93,62,
 '2026-01-15','2026-01-30',2,1,1,
 3650.00,0.00,3650.00,
 'Website redesign with product catalog and contractor portal.',
 'DEMO — Thornton Hardware. Signed but deposit invoice overdue.'),
(65,NOW(),NOW(),'PROP-2026-0010',1,94,NULL,
 '2026-01-10','2026-01-25',3,1,1,
 4788.00,0.00,4788.00,
 'Annual digital marketing consulting retainer. Monthly sessions + deliverables.',
 'DEMO — Halcyon Health. Billed via contract.'),
(66,NOW(),NOW(),'PROP-2026-0011',1,95,63,
 '2026-01-25','2026-02-09',2,1,1,
 4250.00,0.00,4250.00,
 'Corporate website redesign. Discovery through launch.',
 'DEMO — Vantage Point original proposal. Signed 2026-01-30.'),
(67,NOW(),NOW(),'PROP-2026-0012',1,95,64,
 '2026-03-20','2026-04-04',2,1,1,
 2750.00,0.00,2750.00,
 'Change order: customer login portal addition. Rush delivery required.',
 'DEMO — Vantage Point change order. Signed 2026-03-25.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_propaldet`
  (`rowid`,`fk_propal`,`fk_product`,`product_type`,
   `qty`,`tva_tx`,`remise_percent`,`subprice`,`total_ht`,`total_tva`,`total_ttc`,`rang`)
VALUES
(100,61,58,1,1,0,0, 350.00, 350.00,0, 350.00,1),(101,61,59,1,1,0,0, 600.00, 600.00,0, 600.00,2),
(102,61,60,1,1,0,0, 900.00, 900.00,0, 900.00,3),(103,61,65,1,1,0,0,1200.00,1200.00,0,1200.00,4),
(104,61,61,1,1,0,0, 350.00, 350.00,0, 350.00,5),(105,61,67,1,1,0,0, 599.00, 599.00,0, 599.00,6),
(106,61,70,1,1,0,0, 144.00, 144.00,0, 144.00,7),(107,61,71,1,1,0,0,  21.99,  21.99,0,  21.99,8),
(108,61,73,1,2,0,0,  75.00, 150.00,0, 150.00,9),
(110,62,58,1,1,0,0, 350.00, 350.00,0, 350.00,1),(111,62,59,1,1,0,0, 600.00, 600.00,0, 600.00,2),
(112,62,60,1,1,0,0, 900.00, 900.00,0, 900.00,3),(113,62,61,1,1,0,0, 350.00, 350.00,0, 350.00,4),
(114,62,67,1,1,0,0, 599.00, 599.00,0, 599.00,5),(115,62,70,1,1,0,0, 144.00, 144.00,0, 144.00,6),
(116,62,71,1,1,0,0,  21.99,  21.99,0,  21.99,7),(117,62,73,1,1,0,0,  75.00,  75.00,0,  75.00,8),
(120,63,58,1,1,0,0, 350.00, 350.00,0, 350.00,1),(121,63,59,1,1,0,0, 600.00, 600.00,0, 600.00,2),
(122,63,60,1,1,0,0, 900.00, 900.00,0, 900.00,3),(123,63,61,1,1,0,0, 350.00, 350.00,0, 350.00,4),
(124,63,70,1,1,0,0, 144.00, 144.00,0, 144.00,5),(125,63,71,1,1,0,0,  21.99,  21.99,0,  21.99,6),
(126,63,64,1,6,0,0,  99.00, 594.00,0, 594.00,7),(127,63,73,1,2,0,0,  75.00, 150.00,0, 150.00,8),
(130,64,58,1,1,0,0, 350.00, 350.00,0, 350.00,1),(131,64,59,1,1,0,0, 600.00, 600.00,0, 600.00,2),
(132,64,60,1,1,0,0, 900.00, 900.00,0, 900.00,3),(133,64,66,1,1,0,0,1400.00,1400.00,0,1400.00,4),
(134,64,61,1,1,0,0, 350.00, 350.00,0, 350.00,5),(135,64,70,1,1,0,0, 144.00, 144.00,0, 144.00,6),
(136,64,71,1,1,0,0,  21.99,  21.99,0,  21.99,7),
(140,65,53,1,12,0,0,399.00,4788.00,0,4788.00,1),
(150,66,58,1,1,0,0, 350.00, 350.00,0, 350.00,1),(151,66,59,1,1,0,0, 600.00, 600.00,0, 600.00,2),
(152,66,60,1,1,0,0, 900.00, 900.00,0, 900.00,3),(153,66,61,1,1,0,0, 350.00, 350.00,0, 350.00,4),
(154,66,70,1,1,0,0, 144.00, 144.00,0, 144.00,5),(155,66,71,1,1,0,0,  21.99,  21.99,0,  21.99,6),
(156,66,73,1,2,0,0,  75.00, 150.00,0, 150.00,7),
(160,67,60,1,1,0,0,1800.00,1800.00,0,1800.00,1),(161,67,74,1,1,0,0, 150.00, 150.00,0, 150.00,2),
(162,67,73,1,1,0,0,  75.00,  75.00,0,  75.00,3),(163,67,70,1,1,0,0, 144.00, 144.00,0, 144.00,4),
(164,67,71,1,1,0,0,  21.99,  21.99,0,  21.99,5)
ON DUPLICATE KEY UPDATE `rang`=VALUES(`rang`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_propal_extrafields` (`rowid`,`tms`,`fk_object`,`proposal_type`,`signed_date`) VALUES
(60,NOW(),60,'web-build',NULL),
(61,NOW(),61,'ecomm',    NULL),
(62,NOW(),62,'web-build','2026-02-08'),
(63,NOW(),63,'web-build','2025-09-18'),
(64,NOW(),64,'web-build','2026-01-18'),
(65,NOW(),65,'consulting','2026-01-12'),
(66,NOW(),66,'web-build','2026-01-30'),
(67,NOW(),67,'web-build','2026-03-25')
ON DUPLICATE KEY UPDATE `signed_date`=VALUES(`signed_date`)
SQL, $errors);
	}

	// ── T8: Training invoices ─────────────────────────────────────────────────

	private function seedTrainingInvoices(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_facture`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`fk_soc`,`fk_projet`,
   `datef`,`date_lim_reglement`,`fk_statut`,`paye`,
   `fk_user_author`,`fk_user_valid`,`fk_cond_reglement`,
   `total_ht`,`total_tva`,`total_ttc`,`note_public`,`note_private`)
VALUES
(60,NOW(),NOW(),'INV-2026-0005',1,91,60,'2026-02-09','2026-02-24',1,0,1,1,1,
 1424.00,0.00,1424.00,'50% deposit per signed proposal PROP-2026-0007. Balance due at launch.','DEMO — Bellwether deposit.'),
(61,NOW(),NOW(),'INV-2025-0001',1,92,61,'2025-09-19','2025-10-04',2,1,1,1,1,
 2050.00,0.00,2050.00,'50% deposit per signed proposal PROP-2026-0008.','DEMO — Maple Ridge deposit. PAID.'),
(62,NOW(),NOW(),'INV-2026-0006',1,92,61,'2026-01-20','2026-02-04',2,1,1,1,1,
 2050.00,0.00,2050.00,'Balance due — Corporate Site project. All deliverables received.','DEMO — Maple Ridge balance. PAID. Project closed.'),
(63,NOW(),NOW(),'INV-2026-0007',1,93,62,'2026-01-19','2026-02-03',1,0,1,1,1,
 1825.00,0.00,1825.00,'50% deposit per signed proposal PROP-2026-0009. Payment due upon receipt.','DEMO — Thornton Hardware deposit. OVERDUE 45 days.'),
(64,NOW(),NOW(),'INV-2026-0008',1,93,NULL,'2026-03-05','2026-03-12',1,0,1,1,1,
 35.00,0.00,35.00,'Late payment fee per service agreement section 4.2. Original invoice INV-2026-0007 due 2026-02-03.','DEMO — Thornton late fee.'),
(65,NOW(),NOW(),'INV-2026-0009',1,94,NULL,'2026-01-12','2026-01-27',2,1,1,1,1,
 399.00,0.00,399.00,'Monthly consulting retainer — January 2026.','DEMO — Halcyon Jan retainer. PAID.'),
(66,NOW(),NOW(),'INV-2026-0010',1,94,NULL,'2026-02-12','2026-02-27',2,1,1,1,1,
 399.00,0.00,399.00,'Monthly consulting retainer — February 2026.','DEMO — Halcyon Feb retainer. PAID.'),
(67,NOW(),NOW(),'INV-2026-0011',1,94,NULL,'2026-03-12','2026-03-27',1,0,1,1,1,
 399.00,0.00,399.00,'Monthly consulting retainer — March 2026.','DEMO — Halcyon Mar retainer. OPEN. Exercise 6.2: process payment.'),
(68,NOW(),NOW(),'INV-2026-0012',1,95,63,'2026-01-31','2026-02-15',1,0,1,1,1,
 2125.00,0.00,2125.00,'50% deposit per signed proposal PROP-2026-0011.','DEMO — Vantage Point deposit.'),
(69,NOW(),NOW(),'INV-2026-0013',1,95,64,'2026-03-26','2026-04-10',1,0,1,1,1,
 1375.00,0.00,1375.00,'50% deposit — Customer Portal change order PROP-2026-0012. Includes rush surcharge.','DEMO — Vantage Point change order deposit.'),
(70,NOW(),NOW(),'INV-2026-0014',1,52,52,'2026-02-05','2026-02-20',2,1,1,1,1,
 399.00,0.00,399.00,'Monthly consulting retainer — February 2026.','DEMO — Brightpath Feb retainer. PAID.'),
(71,NOW(),NOW(),'INV-2026-0015',1,52,52,'2026-03-05','2026-03-20',1,0,1,1,1,
 399.00,0.00,399.00,'Monthly consulting retainer — March 2026.','DEMO — Brightpath Mar retainer. OPEN. Exercise 6.2: process payment.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_facturedet`
  (`rowid`,`fk_facture`,`fk_product`,`product_type`,
   `qty`,`tva_tx`,`remise_percent`,`subprice`,`total_ht`,`total_tva`,`total_ttc`,`rang`)
VALUES
(60,60,NULL,1,1,0,0,1424.00,1424.00,0,1424.00,1),
(61,61,NULL,1,1,0,0,2050.00,2050.00,0,2050.00,1),
(62,62,NULL,1,1,0,0,2050.00,2050.00,0,2050.00,1),
(63,63,NULL,1,1,0,0,1825.00,1825.00,0,1825.00,1),
(64,64,75, 1,1,0,0,  35.00,  35.00,0,  35.00,1),
(65,65,53, 1,1,0,0, 399.00, 399.00,0, 399.00,1),
(66,66,53, 1,1,0,0, 399.00, 399.00,0, 399.00,1),
(67,67,53, 1,1,0,0, 399.00, 399.00,0, 399.00,1),
(68,68,NULL,1,1,0,0,2125.00,2125.00,0,2125.00,1),
(69,69,NULL,1,1,0,0,1375.00,1375.00,0,1375.00,1),
(70,70,53, 1,1,0,0, 399.00, 399.00,0, 399.00,1),
(71,71,53, 1,1,0,0, 399.00, 399.00,0, 399.00,1)
ON DUPLICATE KEY UPDATE `rang`=VALUES(`rang`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_facture_extrafields` (`rowid`,`tms`,`fk_object`,`payment_method`,`is_deposit`,`balance_due`) VALUES
(60,NOW(),60,'stripe',1,'2026-05-15'),
(61,NOW(),61,'check', 1,NULL),
(62,NOW(),62,'check', 0,NULL),
(63,NOW(),63,'check', 1,NULL),
(64,NOW(),64,'check', 0,NULL),
(65,NOW(),65,'ach',   0,NULL),
(66,NOW(),66,'ach',   0,NULL),
(67,NOW(),67,'ach',   0,NULL),
(68,NOW(),68,'stripe',1,'2026-07-31'),
(69,NOW(),69,'stripe',1,'2026-07-31'),
(70,NOW(),70,'ach',   0,NULL),
(71,NOW(),71,'ach',   0,NULL)
ON DUPLICATE KEY UPDATE `payment_method`=VALUES(`payment_method`)
SQL, $errors);
	}

	// ── T9: Training contracts ────────────────────────────────────────────────

	private function seedTrainingContracts(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_contrat`
  (`rowid`,`datec`,`tms`,`ref`,`entity`,`fk_soc`,`fk_projet`,
   `date_contrat`,`statut`,`fk_user_author`,`note_public`,`note_private`)
VALUES
(60,NOW(),NOW(),'CONT-2026-0003',1,94,NULL,'2026-01-12',1,1,
 'Monthly digital marketing consulting retainer. Includes 4 strategy sessions per month and monthly deliverable report.',
 'DEMO — Halcyon Health retainer. $399/mo. Exercise 6.3/6.4.'),
(61,NOW(),NOW(),'CONT-2026-0004',1,95,63,'2026-01-31',1,1,
 'Corporate site build — fixed scope per PROP-2026-0011.',
 'DEMO — Vantage Point project contract.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_contratdet`
  (`rowid`,`tms`,`fk_contrat`,`label`,`fk_product`,`product_type`,
   `qty`,`remise_percent`,`subprice`,`total_ht`,`tva_tx`,`total_tva`,`total_ttc`,
   `statut`,`date_ouverture_prevue`,`date_fin_validite`,`fk_user_author`,`rang`)
VALUES
(60,NOW(),60,'Consulting Retainer — Monthly',53,1,1,0, 399.00, 399.00,0,0, 399.00,4,'2026-01-01','2026-12-31',1,1),
(61,NOW(),61,'Web Build — Fixed Scope',      60,1,1,0,4250.00,4250.00,0,0,4250.00,4,'2026-02-01','2026-07-31',1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_contrat_extrafields` (`rowid`,`tms`,`fk_object`,`billing_frequency`,`auto_renew`,`annual_value`,`renewal_notice_days`) VALUES
(60,NOW(),60,'monthly',1,4788.00,30),
(61,NOW(),61,'monthly',0,4250.00,30)
ON DUPLICATE KEY UPDATE `billing_frequency`=VALUES(`billing_frequency`)
SQL, $errors);
	}

	// ── T10: Training payments ────────────────────────────────────────────────

	private function seedTrainingPayments(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_paiement`
  (`rowid`,`datec`,`tms`,`datep`,`amount`,`fk_paiement`,
   `num_paiement`,`note`,`fk_bank`,`fk_user_creat`)
VALUES
(60,NOW(),NOW(),'2025-09-22',2050.00,1,'DEMO-CHK-MRP-001','Maple Ridge deposit — check. DEMO.',50,1),
(61,NOW(),NOW(),'2026-01-22',2050.00,1,'DEMO-CHK-MRP-002','Maple Ridge balance — check. DEMO.',50,1),
(62,NOW(),NOW(),'2026-01-15', 399.00,2,'DEMO-ACH-HAL-001','Halcyon Jan retainer — ACH. DEMO.',50,1),
(63,NOW(),NOW(),'2026-02-15', 399.00,2,'DEMO-ACH-HAL-002','Halcyon Feb retainer — ACH. DEMO.',50,1),
(64,NOW(),NOW(),'2026-02-18', 399.00,2,'DEMO-ACH-BRT-002','Brightpath Feb retainer — ACH. DEMO.',50,1),
(65,NOW(),NOW(),'2026-03-10', 400.00,1,'DEMO-CHK-THW-001','Thornton partial payment — check. DEMO. Exercise 5.3.',50,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_paiement_facture` (`rowid`,`fk_paiement`,`fk_facture`,`amount`) VALUES
(60,60,61,2050.00),(61,61,62,2050.00),
(62,62,65, 399.00),(63,63,66, 399.00),
(64,64,70, 399.00),(65,65,63, 400.00)
ON DUPLICATE KEY UPDATE `amount`=VALUES(`amount`)
SQL, $errors);

		$this->exec(<<<'SQL'
UPDATE `llx_facture` SET `paye`=1,`fk_statut`=2 WHERE `rowid` IN (61,62,65,66,70)
SQL, $errors);
	}

	// ── T11: Recurring invoice templates ─────────────────────────────────────

	private function seedTrainingRecurring(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_facture_rec`
  (`rowid`,`titre`,`entity`,`fk_soc`,`datec`,`tms`,
   `total_ht`,`total_tva`,`total_ttc`,
   `fk_user_author`,`fk_cond_reglement`,
   `frequency`,`unit_frequency`,`date_when`,`nb_gen_max`,`auto_validate`,`generate_pdf`,
   `note_private`)
VALUES
(50,'Brightpath — Monthly Retainer',1,52,NOW(),NOW(),
 399.00,0.00,399.00,1,1,
 1,'m',DATE_ADD(CURDATE(),INTERVAL 1 MONTH),0,0,1,
 'DEMO recurring template. Generates Brightpath monthly retainer invoice.'),
(51,'Clearwater — WaaS Monthly',1,55,NOW(),NOW(),
 137.00,0.00,137.00,1,1,
 1,'m',DATE_ADD(CURDATE(),INTERVAL 1 MONTH),0,0,1,
 'DEMO recurring template. WaaS $125 + $12 hosting pass-through.'),
(52,'Halcyon Health — Monthly Retainer',1,94,NOW(),NOW(),
 399.00,0.00,399.00,1,1,
 1,'m',DATE_ADD(CURDATE(),INTERVAL 1 MONTH),0,0,1,
 'DEMO recurring template. Generates Halcyon monthly retainer invoice. Exercise 6.3.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_facturedet_rec`
  (`rowid`,`fk_facture_rec`,`fk_product`,`label`,`product_type`,
   `qty`,`tva_tx`,`subprice`,`total_ht`,`total_tva`,`total_ttc`,`rang`)
VALUES
(50,50,53,'Consulting Retainer — Monthly',1,1,0,399.00,399.00,0,399.00,1),
(51,51,63,'WaaS Monthly Service',         1,1,0,125.00,125.00,0,125.00,1),
(52,51,70,'Hosting Pass-Through',         1,1,0, 12.00, 12.00,0, 12.00,2),
(53,52,53,'Consulting Retainer — Monthly',1,1,0,399.00,399.00,0,399.00,1)
ON DUPLICATE KEY UPDATE `rang`=VALUES(`rang`)
SQL, $errors);
	}

	// ── T12: Vendor cycle (warehouse, stock, PO, supplier invoices) ───────────

	private function seedVendorCycle(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_entrepot`
  (`rowid`,`ref`,`datec`,`tms`,`entity`,`description`,`address`,`zip`,`town`,`fk_pays`,`statut`,`fk_user_author`)
VALUES
(50,'DEMO-WH-01',NOW(),NOW(),1,'Demo fulfillment warehouse — physical goods storage.','100 Demo Way','37040','Demo City',840,1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_product_stock` (`rowid`,`tms`,`fk_product`,`fk_entrepot`,`reel`) VALUES
(50,NOW(),80,50,12),(51,NOW(),81,50,8), (52,NOW(),82,50,15),(53,NOW(),83,50,6),
(54,NOW(),84,50,5), (55,NOW(),85,50,24),(56,NOW(),86,50,18),(57,NOW(),87,50,20),
(58,NOW(),88,50,30),(59,NOW(),89,50,10),(60,NOW(),90,50,36),(61,NOW(),91,50,10),
(62,NOW(),92,50,15),(63,NOW(),93,50,20),(64,NOW(),94,50,25)
ON DUPLICATE KEY UPDATE `reel`=VALUES(`reel`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_stock_mouvement`
  (`rowid`,`tms`,`datem`,`fk_product`,`fk_entrepot`,`value`,`price`,`type_mouvement`,`fk_user_author`,`label`)
VALUES
(50,NOW(),'2026-02-15',80,50,12, 89.00,0,1,'Initial stock receipt from PrintBridge — business cards. DEMO.'),
(51,NOW(),'2026-02-15',84,50,5,  89.00,0,1,'Initial stock receipt from PrintBridge — USB drives. DEMO.'),
(52,NOW(),'2026-02-15',85,50,24, 12.00,0,1,'Initial stock receipt from PrintBridge — notebooks. DEMO.'),
(53,NOW(),'2026-02-15',86,50,18, 14.50,0,1,'Initial stock receipt from PrintBridge — tote bags. DEMO.'),
(54,NOW(),'2026-02-20',87,50,20, 55.00,0,1,'Initial stock receipt from PrintBridge — postcards. DEMO.'),
(55,NOW(),'2026-02-20',88,50,30, 38.00,0,1,'Initial stock receipt from PrintBridge — die-cut stickers. DEMO.'),
(56,NOW(),'2026-02-20',89,50,10, 72.00,0,1,'Initial stock receipt from PrintBridge — letterhead. DEMO.'),
(57,NOW(),'2026-02-20',90,50,36,  9.50,0,1,'Initial stock receipt from PrintBridge — ceramic mugs. DEMO.'),
(58,NOW(),'2026-02-20',91,50,10, 18.00,0,1,'Initial stock receipt from PrintBridge — pen 12-packs. DEMO.'),
(59,NOW(),'2026-02-20',92,50,15, 16.50,0,1,'Initial stock receipt from PrintBridge — water bottles. DEMO.'),
(60,NOW(),'2026-02-20',93,50,20, 11.00,0,1,'Initial stock receipt from PrintBridge — structured caps. DEMO.'),
(61,NOW(),'2026-02-20',94,50,25,  8.75,0,1,'Initial stock receipt from PrintBridge — unisex tees. DEMO.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_commande_fournisseur`
  (`rowid`,`ref`,`entity`,`fk_soc`,`fk_projet`,`date_creation`,`tms`,`date_commande`,`fk_user_author`,`fk_statut`,`note_private`)
VALUES
(50,'PO-2026-0001',1,63,50,NOW(),NOW(),'2026-02-10',1,3,
 'DEMO — PO to PrintBridge for Pinnacle Goods business card order. Exercise 9.3.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_commande_fournisseurdet`
  (`rowid`,`fk_commande`,`fk_product`,`label`,`qty`,`tva_tx`,`subprice`,`total_ht`,`total_tva`,`total_ttc`,`rang`)
VALUES
(50,50,80,'Business Cards — 500qty (Pinnacle Goods)',1,0,89.00,89.00,0,89.00,1)
ON DUPLICATE KEY UPDATE `rang`=VALUES(`rang`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_facture_fourn`
  (`rowid`,`ref`,`ref_supplier`,`entity`,`fk_soc`,`datec`,`datef`,`tms`,
   `libelle`,`paye`,`total_ht`,`total_tva`,`total_ttc`,`fk_statut`,`fk_user_author`,`note_private`)
VALUES
(50,'FINV-2026-0001','INV-DHOST-2026-03',1,62,NOW(),'2026-03-01',NOW(),
 'Annual hosting renewal — Dolibarr demo server hosting.',
 0,144.00,0.00,144.00,1,1,
 'DEMO — DemoHost Pro annual renewal. Exercise 9.1/9.2.'),
(51,'FINV-2026-0002','PB-INV-2026-0047',1,63,NOW(),'2026-02-15',NOW(),
 'Business cards print run — Pinnacle Goods order.',
 1,89.00,0.00,89.00,2,1,
 'DEMO — PrintBridge print run for Pinnacle. Ref PO-2026-0001. PAID.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_product_fournisseur_price`
  (`rowid`,`datec`,`tms`,`fk_product`,`fk_soc`,`ref_fourn`,`price`,`quantity`,`unitprice`,`tva_tx`,`entity`,`fk_user`,`fk_availability`)
VALUES
(50,NOW(),NOW(),80,63,'PB-BIZCARD-500',  55.00,1,55.00,0,1,1,1),
(51,NOW(),NOW(),81,63,'PB-BROCHURE-100', 89.00,1,89.00,0,1,1,1),
(52,NOW(),NOW(),82,63,'PB-RACKCARD-250', 72.00,1,72.00,0,1,1,1),
(53,NOW(),NOW(),83,63,'PB-BANNER-2X4',   45.00,1,45.00,0,1,1,1),
(54,NOW(),NOW(),84,63,'PB-USB-10PK',     52.00,1,52.00,0,1,1,1),
(55,NOW(),NOW(),85,63,'PB-NOTEBOOK-A5',   7.50,1, 7.50,0,1,1,1),
(56,NOW(),NOW(),86,63,'PB-TOTE-CANVAS',   8.00,1, 8.00,0,1,1,1),
(57,NOW(),NOW(),87,63,'PB-POSTCARD-4X6', 55.00,1,55.00,0,1,1,1),
(58,NOW(),NOW(),88,63,'PB-STICKER-DC',   38.00,1,38.00,0,1,1,1),
(59,NOW(),NOW(),89,63,'PB-LTRHD-8511',   72.00,1,72.00,0,1,1,1),
(60,NOW(),NOW(),90,63,'PB-MUG-11OZ',      9.50,1, 9.50,0,1,1,1),
(61,NOW(),NOW(),91,63,'PB-PEN-ALU-12',   18.00,1,18.00,0,1,1,1),
(62,NOW(),NOW(),92,63,'PB-BOTTLE-20OZ', 16.50,1,16.50,0,1,1,1),
(63,NOW(),NOW(),93,63,'PB-CAP-STRCT',   11.00,1,11.00,0,1,1,1),
(64,NOW(),NOW(),94,63,'PB-TEE-UNISEX',   8.75,1, 8.75,0,1,1,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── T13: Expense reports ──────────────────────────────────────────────────

	private function seedExpenseReports(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_expensereport`
  (`rowid`,`ref`,`entity`,`fk_user_author`,`fk_user_creat`,
   `date_debut`,`date_fin`,`date_create`,`tms`,
   `total_ht`,`total_tva`,`total_ttc`,`fk_statut`,`note_private`)
VALUES
(50,'EX-2026-0001',1,1,1,'2026-03-01','2026-03-31',NOW(),NOW(),
 87.49,0.00,87.49,5,
 'DEMO — jmiller March expenses. Approved. fk_statut=5=Approved+Closed. Exercise 10.2 reference.'),
(51,'EX-2026-0002',1,53,1,'2026-03-01','2026-03-31',NOW(),NOW(),
 70.49,0.00,70.49,2,
 'DEMO — tkim March expenses. fk_statut=2=Validated, pending approval. Exercise 10.1/10.2.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_expensereport_det`
  (`rowid`,`fk_expensereport`,`fk_c_type_fees`,`fk_projet`,
   `comments`,`qty`,`subprice`,`value_unit`,`tva_tx`,`total_ht`,`product_type`)
VALUES
(50,50,4,50, 'Round trip mileage — Pinnacle Goods client meeting, 22 miles at $0.67/mi.',22, 0.67,0.67,0,14.74,0),
(51,50,2,NULL,'Adobe Creative Cloud monthly subscription — design tools.',                1,54.99,54.99,0,54.99,1),
(52,50,3,52,  'Working lunch — Brightpath grant review session.',                         1,17.76,17.76,0,17.76,0),
(53,51,4,NULL,'Mileage — vendor supply pickup, 18 miles at $0.67/mi.',                  18, 0.67,0.67,0,12.06,0),
(54,51,2,NULL,'Figma Pro monthly subscription — design tooling.',                         1,15.00,15.00,0,15.00,1),
(55,51,2,NULL,'Adobe Creative Cloud — monthly.',                                          1,54.99,54.99,0,54.99,1),
(56,51,3,NULL,'Working lunch — client design review.',                                    1, 8.44, 8.44,0, 8.44,0)
ON DUPLICATE KEY UPDATE `comments`=VALUES(`comments`)
SQL, $errors);
	}

	// ── T14: CRM activity log ─────────────────────────────────────────────────

	private function seedCrmActivity(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_actioncomm`
  (`id`,`ref`,`entity`,`datep`,`datep2`,`fk_action`,`code`,`datec`,`tms`,
   `fk_user_author`,`fk_user_mod`,`fk_project`,`fk_soc`,`fk_contact`,
   `label`,`note`,`percent`,`status`)
VALUES
(50,'AC-0050',1,'2026-01-15','2026-01-15',6,'AC_EMAIL_IN',NOW(),NOW(),1,1,NULL,60,60,
 'Inbound inquiry — website interest',
 'Patricia reached out via contact form. Interested in new patient portal and SEO. Asked for a proposal.','100',1),
(51,'AC-0051',1,'2026-01-22','2026-01-22',4,'AC_EMAIL',NOW(),NOW(),1,1,NULL,60,60,
 'Sent intake questionnaire',
 'Emailed intake questionnaire and service overview PDF. Requested 30-min discovery call.','100',1),
(52,'AC-0052',1,'2026-02-05','2026-02-05',5,'AC_RDV',NOW(),NOW(),1,1,NULL,60,60,
 'Discovery call — 30 min',
 'Discussed needs: 5-page site, bio pages for 8 practitioners, contact/booking form, basic SEO setup. Decision maker confirmed. Requested proposal by Feb 20.','100',1),
(53,'AC-0053',1,'2026-01-28','2026-01-28',5,'AC_RDV',NOW(),NOW(),1,1,NULL,61,61,
 'Intro call — Summit Trade',
 'Referred by Apex Digital. Looking for B2B order portal and catalog. Budget unclear. Asked for scoping call.','100',1),
(54,'AC-0054',1,'2026-02-12','2026-02-12',4,'AC_EMAIL',NOW(),NOW(),1,1,NULL,61,61,
 'Scoping questionnaire sent',
 'Sent detailed questionnaire. Waiting on response re: integrations needed (ERP, payment processing).','100',1),
(55,'AC-0055',1,'2026-01-31','2026-01-31',5,'AC_RDV',NOW(),NOW(),1,1,52,52,52,
 'January grant check-in call',
 'Reviewed Q1 grant calendar. 3 deadlines identified for Feb/March. Confirmed Community Dev Grant application is priority.','100',1),
(56,'AC-0056',1,'2026-02-07','2026-02-07',4,'AC_EMAIL',NOW(),NOW(),1,1,52,52,52,
 'Draft grant outline — Community Dev',
 'Sent first draft of Community Development Grant narrative outline. Requested client review by Feb 14.','100',1),
(57,'AC-0057',1,'2026-02-28','2026-02-28',5,'AC_RDV',NOW(),NOW(),1,1,52,52,52,
 'February check-in — grant progress review',
 'Reviewed feedback on Community Dev draft. Minor revisions requested. Technology Access Grant — confirmed funder eligibility.','100',1),
(58,'AC-0058',1,'2026-03-31','2026-03-31',5,'AC_RDV',NOW(),NOW(),1,1,52,52,52,
 'March check-in — scheduled',
 'Scheduled: monthly retainer check-in. Agenda: Community Dev final review, Tech Access draft review, Q2 impact report kickoff.','0',-1),
(59,'AC-0059',1,'2026-02-10','2026-02-10',4,'AC_EMAIL',NOW(),NOW(),1,1,NULL,93,93,
 'Payment reminder — INV-2026-0007',
 'Sent friendly payment reminder. Invoice 7 days past due. No response received.','100',1),
(60,'AC-0060',1,'2026-02-24','2026-02-24',50,'AC_OTH',NOW(),NOW(),1,1,NULL,93,93,
 'Follow-up — second reminder',
 'Left voicemail for Dale Thornton re: overdue invoice. No callback received within 48 hours. Email follow-up sent same day.','100',1),
(61,'AC-0061',1,'2026-03-05','2026-03-05',50,'AC_OTH',NOW(),NOW(),1,1,NULL,93,93,
 'Late fee applied — formal notice sent',
 'Applied $35 late fee per service agreement. Sent formal collections notice with both invoices attached. Partial payment received 2026-03-10.','100',1),
(62,'AC-0062',1,'2026-03-01','2026-03-01',6,'AC_EMAIL_IN',NOW(),NOW(),1,1,NULL,90,90,
 'Initial inquiry — firm website',
 'Patricia Whitmore emailed asking about new website for law firm. Wants 5 pages, attorney bios, contact form.','100',1),
(63,'AC-0063',1,'2026-03-05','2026-03-05',4,'AC_EMAIL',NOW(),NOW(),1,1,NULL,90,90,
 'Sent intake questionnaire and service overview',
 'Emailed intro questionnaire and Moko service overview. Scheduled 30-min call for March 8.','100',1),
(64,'AC-0064',1,'2026-03-08','2026-03-08',5,'AC_RDV',NOW(),NOW(),1,1,NULL,90,90,
 'Discovery call — 30 min',
 'Confirmed scope: 5-page Joomla site, attorney bio pages x4, contact/consultation request form, Google Analytics. Proposal requested by March 15.','100',1),
(65,'AC-0065',1,'2026-03-18','2026-03-18',6,'AC_EMAIL_IN',NOW(),NOW(),1,1,63,95,95,
 'Scope change request — customer portal',
 'Victor Marcello emailed requesting addition of customer login portal. Not in original scope. Needs rush delivery for trade show April 15.','100',1),
(66,'AC-0066',1,'2026-03-20','2026-03-20',5,'AC_RDV',NOW(),NOW(),1,1,63,95,95,
 'Change order scoping call',
 'Confirmed portal requirements: OAuth login, shipment status page, document library. Rush delivery confirmed necessary. Change order proposal to follow.','100',1),
(67,'AC-0067',1,'2026-03-25','2026-03-25',4,'AC_EMAIL',NOW(),NOW(),1,1,63,95,95,
 'Change order proposal sent — PROP-2026-0012',
 'Sent change order proposal. Victor acknowledged rush fee. Signed same day.','100',1),
(68,'AC-0068',1,'2026-01-25','2026-01-25',5,'AC_RDV',NOW(),NOW(),1,1,NULL,91,91,
 'Initial discovery call — full e-commerce',
 'Cole Bellamy — artisan bakery. Wants online ordering, loyalty program, gift cards. Full e-commerce build discussed.','100',1),
(69,'AC-0069',1,'2026-02-02','2026-02-02',4,'AC_EMAIL',NOW(),NOW(),1,1,NULL,91,91,
 'Client declined original proposal — budget',
 'Cole responded: original proposal ($5,200) exceeds budget ceiling of $3,000. Asked if we can descope.','100',1),
(70,'AC-0070',1,'2026-02-04','2026-02-04',5,'AC_RDV',NOW(),NOW(),1,1,NULL,91,91,
 'Revision call — agreed on descoped scope',
 'Agreed on brochure site + logo only. No e-commerce. Cole approved revised budget range.','100',1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── T15: Time tracking ────────────────────────────────────────────────────

	private function seedTimeTracking(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_element_time`
  (`rowid`,`fk_element`,`elementtype`,`element_date`,`element_datehour`,
   `element_date_withhour`,`element_duration`,`fk_user`,`thm`,
   `datec`,`tms`,`note`,`status`)
VALUES
(50,119,'project_task','2026-02-20','2026-02-20 10:00:00',1, 7200, 1,75.00,NOW(),NOW(),'Joomla install and Cassiopeia template config.',0),
(51,120,'project_task','2026-03-05','2026-03-05 09:00:00',1,14400,51,65.00,NOW(),NOW(),'VirtueMart install, category structure, payment gateway integration test.',0),
(52,120,'project_task','2026-03-10','2026-03-10 13:00:00',1, 7200,54,40.00,NOW(),NOW(),'Product catalog import — first 25 SKUs loaded.',0),
(53,115,'project_task','2026-02-10','2026-02-10 10:00:00',1, 3600,53,55.00,NOW(),NOW(),'Homepage mockup review session with client.',1),
(54,116,'project_task','2026-02-14','2026-02-14 14:00:00',1, 5400,53,55.00,NOW(),NOW(),'Product page mockups — 3 variants designed.',0),
(55,144,'project_task','2026-02-15','2026-02-15 10:00:00',1, 7200,53,55.00,NOW(),NOW(),'Homepage mockup — initial concept and layout.',0),
(56,145,'project_task','2026-03-01','2026-03-01 09:00:00',1, 5400,53,55.00,NOW(),NOW(),'Member directory page mockup — CB component layout.',0),
(57,140,'project_task','2026-02-01','2026-02-01 14:00:00',1, 3600, 1,75.00,NOW(),NOW(),'Kickoff session — gathered requirements, confirmed sitemap.',1),
(58,166,'project_task','2026-03-15','2026-03-15 09:00:00',1,14400, 1,75.00,NOW(),NOW(),'Community Development Grant — second draft full narrative write.',0),
(59,167,'project_task','2026-04-02','2026-04-02 10:00:00',1, 7200, 1,75.00,NOW(),NOW(),'Technology Access Grant — research and eligibility review.',0),
(60,163,'project_task','2026-01-31','2026-01-31 13:00:00',1, 3600, 1,75.00,NOW(),NOW(),'January check-in call — grant calendar review and prioritization.',1),
(61,187,'project_task','2026-02-03','2026-02-03 10:00:00',1, 7200,53,55.00,NOW(),NOW(),'Logo concepts — round 1, three directions developed.',0),
(62,187,'project_task','2026-02-10','2026-02-10 14:00:00',1, 5400,53,55.00,NOW(),NOW(),'Logo refinement — client preferred direction 2, two revisions.',0),
(63,184,'project_task','2026-01-10','2026-01-10 09:00:00',1, 3600, 1,75.00,NOW(),NOW(),'Kickoff and brand brief session.',1),
(64,330,'project_task','2026-02-01','2026-02-01 09:00:00',1, 5400, 1,75.00,NOW(),NOW(),'Discovery session — gathered requirements and tech stack review.',1),
(65,331,'project_task','2026-02-20','2026-02-20 13:00:00',1, 7200,53,55.00,NOW(),NOW(),'Homepage mockup and interior page concepts.',0),
(66,340,'project_task','2026-04-01','2026-04-01 09:00:00',1, 5400,51,65.00,NOW(),NOW(),'Portal requirements session — auth spec and data model.',0)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── T16: Linked documents ─────────────────────────────────────────────────

	private function seedLinkedDocs(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_element_element`
  (`rowid`,`fk_source`,`sourcetype`,`fk_target`,`targettype`,`relationtype`)
VALUES
(50,50,'propal',50,'facture','generates'),
(51,52,'propal',52,'facture','generates'),
(52,51,'propal',51,'facture','generates'),
(53,53,'propal',53,'facture','generates'),
(54,50,'contrat',51,'facture','generates'),
(55,50,'contrat',70,'facture','generates'),
(56,50,'contrat',71,'facture','generates'),
(58,62,'propal',60,'facture','generates'),
(59,63,'propal',61,'facture','generates'),
(60,63,'propal',62,'facture','generates'),
(61,64,'propal',63,'facture','generates'),
(62,64,'propal',64,'facture','generates'),
(63,65,'propal',65,'facture','generates'),
(64,66,'propal',68,'facture','generates'),
(65,67,'propal',69,'facture','generates')
ON DUPLICATE KEY UPDATE `relationtype`=VALUES(`relationtype`)
SQL, $errors);
	}

	// ── T17: Categories / tags ────────────────────────────────────────────────

	private function seedCategories(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_categorie`
  (`rowid`,`entity`,`fk_parent`,`label`,`type`,`description`,`color`,`fk_user_creat`,`tms`,`date_creation`)
VALUES
(50,1,NULL,'Active Client',   2,'Currently engaged with an active project or contract','#2ecc71',1,NOW(),NOW()),
(51,1,NULL,'Prospect',        2,'Potential client — no signed proposal yet',            '#3498db',1,NOW(),NOW()),
(52,1,NULL,'Overdue Account', 2,'Has one or more overdue invoices',                     '#e74c3c',1,NOW(),NOW()),
(53,1,NULL,'Closed',          2,'Project complete, all invoices paid',                  '#95a5a6',1,NOW(),NOW()),
(54,1,NULL,'On Hold',         2,'Engagement paused at client or Moko request',          '#f39c12',1,NOW(),NOW()),
(55,1,NULL,'Consulting',      0,'Strategy, grant writing, and advisory services',       '#9b59b6',1,NOW(),NOW()),
(56,1,NULL,'Web Development', 0,'Joomla builds, maintenance, WaaS',                    '#2980b9',1,NOW(),NOW()),
(57,1,NULL,'Design',          0,'Logo, branding, social assets',                       '#e67e22',1,NOW(),NOW()),
(58,1,NULL,'Print & Swag',    0,'Physical goods — print runs and branded merchandise', '#27ae60',1,NOW(),NOW()),
(59,1,NULL,'Vendor Items',    0,'Pass-through hosting, domains, platform subs',        '#7f8c8d',1,NOW(),NOW()),
(60,1,NULL,'Fixed Scope',     6,'One-time project with defined deliverables',          '#1abc9c',1,NOW(),NOW()),
(61,1,NULL,'Retainer',        6,'Ongoing engagement billed monthly',                   '#8e44ad',1,NOW(),NOW()),
(62,1,NULL,'Change Order',    6,'Amendment to an existing project scope',              '#d35400',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_categorie_societe` (`fk_categorie`,`fk_soc`) VALUES
(50,50),(50,51),(50,52),(50,53),(50,54),(50,55),
(50,91),(50,92),(50,93),(50,94),(50,95),
(51,60),(51,61),(51,90),
(52,93),
(53,92)
ON DUPLICATE KEY UPDATE `fk_soc`=VALUES(`fk_soc`)
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_categorie_product` (`fk_categorie`,`fk_product`) VALUES
(55,50),(55,51),(55,52),(55,53),(55,54),(55,55),(55,56),(55,57),
(56,58),(56,59),(56,60),(56,61),(56,62),(56,63),(56,64),(56,65),(56,66),
(57,67),(57,68),(57,69),
(58,80),(58,81),(58,82),(58,83),(58,84),(58,85),(58,86),
(58,87),(58,88),(58,89),(58,90),(58,91),(58,92),(58,93),(58,94),
(59,70),(59,71),(59,72)
ON DUPLICATE KEY UPDATE `fk_product`=VALUES(`fk_product`)
SQL, $errors);
	}

	// ── T19: Membership types + members ──────────────────────────────────────

	private function seedMemberships(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_adherent_type`
  (`rowid`,`entity`,`label`,`note`,`subscription`,`vote`,`use_mailings`,
   `duration_value`,`duration_unit`,`datec`,`tms`)
VALUES
(50,1,'Partner Network',
 'DEMO — Annual partner membership for B2B clients. Includes newsletter, early access, and referral benefits.',
 250.00,0,1,1,'y',NOW(),NOW()),
(51,1,'Associate Member',
 'DEMO — Complimentary membership for nonprofits and associate organisations. No subscription fee.',
 0.00,0,1,1,'y',NOW(),NOW())
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);

		$this->exec(<<<'SQL'
INSERT INTO `llx_adherent`
  (`rowid`,`entity`,`fk_adherent_type`,`civility_id`,`firstname`,`lastname`,
   `email`,`phone`,`address`,`zip`,`town`,`country_code`,
   `fk_soc`,`datec`,`tms`,`datefin`,`amount`,`statut`,`photo`,`note_public`,`note_private`)
VALUES
(50,1,50,'MR','Jordan','Mitchell',
 'j.mitchell@pinnaclegoods.example','555-0140','100 Commerce Blvd','10001','New York','US',
 50,NOW(),NOW(),'2027-03-01',250.00,1,NULL,
 'Active Partner Network member — web build client.',
 'DEMO — rowid 50 Pinnacle Goods. Joined March 2026. Exercise: marketing_08.'),
(51,1,50,'MS','Amanda','Cruz',
 'a.cruz@halcyonhealth.example','555-0177','400 Wellness Ave','10002','New York','US',
 94,NOW(),NOW(),'2027-03-01',250.00,1,NULL,
 'Active Partner Network member — retainer client.',
 'DEMO — rowid 94 Halcyon Health Partners. Joined March 2026. Exercise: marketing_08.'),
(52,1,51,'MR','Daniel','Bright',
 'd.bright@brightpathnp.example','555-0155','22 Grant Plaza','10003','New York','US',
 52,NOW(),NOW(),'2027-03-01',0.00,1,NULL,
 'Associate member — nonprofit organisation.',
 'DEMO — rowid 52 Brightpath Nonprofit Solutions. Complimentary tier. Exercise: marketing_08.'),
(53,1,51,'MS','Sara','Vance',
 's.vance@mapleproperty.example','555-0166','500 Realty Row','10004','New York','US',
 61,NOW(),NOW(),'2025-12-31',0.00,-1,NULL,
 'Associate membership expired December 2025.',
 'DEMO — rowid 61 Maple Ridge Property Group. Expired — use for renewal workflow demo. Exercise: marketing_08.')
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── T20: Mass email campaigns ─────────────────────────────────────────────

	private function seedMailings(int $e, array &$errors): void
	{
		$this->exec(<<<'SQL'
INSERT INTO `llx_mailing`
  (`rowid`,`entity`,`datec`,`tms`,`titre`,`sujet`,`body`,
   `email_from`,`email_replyto`,`nbemail`,`status`,`fk_user_creat`)
VALUES
(50,1,NOW(),NOW(),
 'Q2 Logistics Client Update — DRAFT',
 'Important updates to your supply chain services — Q2 2026',
 '<p>Dear {FIRSTNAME},</p><p>We have important updates regarding your supply chain services for Q2 2026.</p><p>Please review the details and contact us with any questions.</p><p>Best regards,<br>The Demo Consulting Team</p>',
 'noreply@democonsulting.example','noreply@democonsulting.example',0,0,1),
(51,1,NOW(),NOW(),
 'March Newsletter — Partners',
 'Partner Network Update — March 2026',
 '<p>Hello {FIRSTNAME},</p><p>Here is your monthly partner network update. This quarter we are excited to announce new service offerings and expanded support hours.</p><p>Thank you for your continued partnership.<br>Demo Consulting Team</p>',
 'noreply@democonsulting.example','noreply@democonsulting.example',2,2,1)
ON DUPLICATE KEY UPDATE `tms`=NOW()
SQL, $errors);
	}

	// ── T18: Customer sales orders (dynamic IDs) ──────────────────────────────

	public function seedOrders(int $entity = 1): array
	{
		$errors  = [];
		$o_start = $this->nextId('commande', 50);
		$d_start = $this->nextId('commandedet', 50);

		// 5 training orders
		$o = [$o_start, $o_start+1, $o_start+2, $o_start+3, $o_start+4];
		// 31 order lines across 5 orders (7+7+6+6+5)
		$d = [];
		for ($i = 0; $i < 31; $i++) { $d[$i] = $d_start + $i; }

		$this->exec("INSERT INTO `llx_commande`
  (`rowid`,`ref`,`entity`,`fk_soc`,`datec`,`tms`,`date_commande`,
   `fk_statut`,`total_ht`,`total_tva`,`total_ttc`,`fk_user_author`,`note_private`)
VALUES
({$o[0]},'ORD-2026-0001',1,50,NOW(),NOW(),'2026-03-01',
 1,1340.00,0.00,1340.00,1,'DEMO — Pinnacle Goods print/swag for store opening. Exercise 9.3.'),
({$o[1]},'ORD-2026-0002',1,53,NOW(),NOW(),'2026-02-25',
 2,1140.00,0.00,1140.00,1,'DEMO — Apex Digital brand launch collateral pack. Delivered.'),
({$o[2]},'ORD-2026-0003',1,91,NOW(),NOW(),'2026-02-08',
 1,1057.00,0.00,1057.00,1,'DEMO — Bellwether opening day swag pack. Exercise 9.4.'),
({$o[3]},'ORD-2026-0004',1,93,NOW(),NOW(),'2026-01-25',
 0, 868.00,0.00, 868.00,1,'DEMO — Thornton branded staff items. On hold pending deposit payment.'),
({$o[4]},'ORD-2026-0005',1,95,NOW(),NOW(),'2026-03-27',
 1,1099.00,0.00,1099.00,1,'DEMO — Vantage Point print collateral for corporate site launch.')
ON DUPLICATE KEY UPDATE `tms`=NOW()", $errors);

		$this->exec("INSERT INTO `llx_commandedet`
  (`rowid`,`fk_commande`,`fk_product`,`product_type`,`label`,
   `qty`,`tva_tx`,`remise_percent`,`subprice`,`total_ht`,`total_tva`,`total_ttc`,`rang`)
VALUES
({$d[0]}, {$o[0]},80,0,NULL,2,0,0, 89.00, 178.00,0, 178.00,1),
({$d[1]}, {$o[0]},81,0,NULL,3,0,0,129.00, 387.00,0, 387.00,2),
({$d[2]}, {$o[0]},82,0,NULL,2,0,0, 99.00, 198.00,0, 198.00,3),
({$d[3]}, {$o[0]},84,0,NULL,3,0,0, 89.00, 267.00,0, 267.00,4),
({$d[4]}, {$o[0]},85,0,NULL,6,0,0, 12.00,  72.00,0,  72.00,5),
({$d[5]}, {$o[0]},86,0,NULL,4,0,0, 14.50,  58.00,0,  58.00,6),
({$d[6]}, {$o[0]},90,0,NULL,12,0,0,15.00, 180.00,0, 180.00,7),
({$d[7]}, {$o[1]},80,0,NULL,1,0,0, 89.00,  89.00,0,  89.00,1),
({$d[8]}, {$o[1]},81,0,NULL,2,0,0,129.00, 258.00,0, 258.00,2),
({$d[9]}, {$o[1]},88,0,NULL,5,0,0, 59.00, 295.00,0, 295.00,3),
({$d[10]},{$o[1]},91,0,NULL,2,0,0, 28.00,  56.00,0,  56.00,4),
({$d[11]},{$o[1]},92,0,NULL,6,0,0, 25.00, 150.00,0, 150.00,5),
({$d[12]},{$o[1]},93,0,NULL,10,0,0,18.00, 180.00,0, 180.00,6),
({$d[13]},{$o[1]},94,0,NULL,8,0,0, 14.00, 112.00,0, 112.00,7),
({$d[14]},{$o[2]},80,0,NULL,1,0,0, 89.00,  89.00,0,  89.00,1),
({$d[15]},{$o[2]},82,0,NULL,2,0,0, 99.00, 198.00,0, 198.00,2),
({$d[16]},{$o[2]},86,0,NULL,10,0,0,14.50, 145.00,0, 145.00,3),
({$d[17]},{$o[2]},87,0,NULL,3,0,0, 79.00, 237.00,0, 237.00,4),
({$d[18]},{$o[2]},90,0,NULL,24,0,0,15.00, 360.00,0, 360.00,5),
({$d[19]},{$o[2]},91,0,NULL,1,0,0, 28.00,  28.00,0,  28.00,6),
({$d[20]},{$o[3]},80,0,NULL,1,0,0, 89.00,  89.00,0,  89.00,1),
({$d[21]},{$o[3]},83,0,NULL,2,0,0, 69.00, 138.00,0, 138.00,2),
({$d[22]},{$o[3]},85,0,NULL,12,0,0,12.00, 144.00,0, 144.00,3),
({$d[23]},{$o[3]},86,0,NULL,6,0,0, 14.50,  87.00,0,  87.00,4),
({$d[24]},{$o[3]},93,0,NULL,15,0,0,18.00, 270.00,0, 270.00,5),
({$d[25]},{$o[3]},94,0,NULL,10,0,0,14.00, 140.00,0, 140.00,6),
({$d[26]},{$o[4]},80,0,NULL,2,0,0, 89.00, 178.00,0, 178.00,1),
({$d[27]},{$o[4]},81,0,NULL,2,0,0,129.00, 258.00,0, 258.00,2),
({$d[28]},{$o[4]},89,0,NULL,3,0,0, 99.00, 297.00,0, 297.00,3),
({$d[29]},{$o[4]},83,0,NULL,1,0,0, 69.00,  69.00,0,  69.00,4),
({$d[30]},{$o[4]},82,0,NULL,3,0,0, 99.00, 297.00,0, 297.00,5)
ON DUPLICATE KEY UPDATE `rang`=VALUES(`rang`)", $errors);

		$this->track('llx_commande',    $o, $entity);
		$this->track('llx_commandedet', $d, $entity);

		dolibarr_set_const($this->db, 'MOKODOLITRAINING_T18_ORDER_IDS', json_encode($o), 'chaine', 0, '', $entity);
		dolibarr_set_const($this->db, 'MOKODOLITRAINING_T18_DET_IDS',   json_encode($d), 'chaine', 0, '', $entity);

		return ['ok' => empty($errors) ? 1 : 0, 'errors' => $errors];
	}
}

