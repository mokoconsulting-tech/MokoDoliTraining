<?php
/*
 * Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>
 * This file is part of a Moko Consulting project.
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * DEFGROUP: MokoDoliTraining.Class
 * INGROUP:  MokoDoliTraining
 * REPO:     https://github.com/mokoconsulting-tech/MokoDoliTraining
 * PATH:     /src/class/MokoDoliTrainingClass.class.php
 * VERSION:  development
 * BRIEF:    Training class (session) manager.
 *           Handles class CRUD, trainee enrollment, usergroup assignment,
 *           and mass suspend/reactivate when a session closes.
 *
 * Class status:      0=Draft  1=Active  2=Closed
 * Enrollment status: 0=Suspended  1=Active  2=Completed
 * Status transitions: Draft→Active, Active→Closed, Closed→Active
 */

class MokoDoliTrainingClass
{
	private $db;

	// ── Status constants ───────────────────────────────────────────────────
	const CLASS_DRAFT    = 0;
	const CLASS_ACTIVE   = 1;
	const CLASS_CLOSED   = 2;

	const ENROLL_SUSPENDED = 0;
	const ENROLL_ACTIVE    = 1;
	const ENROLL_COMPLETED = 2;

	/**
	 * Allowed status transitions: [from_status => [to_status, ...]]
	 * Uses literal integers (not self::) for PHP 7.4 compatibility.
	 */
	const TRANSITIONS = [
		0 => [1],   // Draft  → Active only
		1 => [2],   // Active → Closed only
		2 => [1],   // Closed → Active only
	];

	public function __construct($db)
	{
		$this->db = $db;
	}

	// ── Validation ─────────────────────────────────────────────────────────

	/**
	 * Server-side validation. Returns array of lang-key error strings (empty = valid).
	 *
	 * @param array    $data        Class field values
	 * @param int      $entity      Dolibarr entity
	 * @param int|null $exclude_id  Existing rowid to exclude from duplicate-ref check (for update)
	 * @return string[]
	 */
	public function validate(array $data, int $entity = 1, ?int $exclude_id = null): array
	{
		$errors = [];
		$ref    = trim($data['ref']   ?? '');
		$label  = trim($data['label'] ?? '');

		if ($ref   === '') $errors[] = 'ClassErrRefRequired';
		if ($label === '') $errors[] = 'ClassErrLabelRequired';
		if ($ref   !== '' && strlen($ref) > 30) $errors[] = 'ClassErrRefTooLong';

		$ds = trim($data['date_start'] ?? '');
		$de = trim($data['date_end']   ?? '');
		if ($ds !== '' && $de !== '') {
			$d_start = \DateTime::createFromFormat('Y-m-d', $ds);
			$d_end   = \DateTime::createFromFormat('Y-m-d', $de);
			if ($d_start && $d_end && $d_end < $d_start) {
				$errors[] = 'ClassErrEndBeforeStart';
			}
		}

		if ($ref !== '' && $this->isRefExists($ref, $entity, $exclude_id)) {
			$errors[] = 'ClassErrRefDuplicate';
		}

		return $errors;
	}

	/**
	 * Check if a ref already exists for the given entity, optionally excluding a rowid.
	 */
	public function isRefExists(string $ref, int $entity = 1, ?int $exclude_id = null): bool
	{
		$tbl = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$sql = "SELECT rowid FROM `{$tbl}`"
			. " WHERE `ref`='" . $this->db->escape($ref) . "' AND `entity`={$entity}";
		if ($exclude_id !== null) $sql .= " AND `rowid`<>{$exclude_id}";
		$sql .= " LIMIT 1";

		$res = $this->db->query($sql);
		return ($res && $this->db->fetch_object($res) !== false);
	}

	/**
	 * Auto-generate a unique ref in the form CLASS-YYYY-NNN.
	 * Sequential per entity and year; collisions handled by incrementing.
	 */
	public function generateRef(int $entity = 1): string
	{
		$year   = date('Y');
		$tbl    = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$prefix = $this->db->escape("CLASS-{$year}-");

		$res = $this->db->query(
			"SELECT COUNT(*) AS cnt FROM `{$tbl}`"
			. " WHERE `entity`={$entity} AND `ref` LIKE '{$prefix}%'"
		);
		$n = 1;
		if ($res) {
			$row = $this->db->fetch_object($res);
			$n   = (int) $row->cnt + 1;
		}

		// Ensure the generated ref doesn't collide (rare if two classes created simultaneously)
		$candidate = "CLASS-{$year}-" . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
		while ($this->isRefExists($candidate, $entity)) {
			$n++;
			$candidate = "CLASS-{$year}-" . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
		}
		return $candidate;
	}

	// ── Class CRUD ─────────────────────────────────────────────────────────

	/**
	 * Create a new training class.
	 *
	 * @param array    $data          Field values; ref and label are required
	 * @param int      $fk_user_creat Creating user rowid
	 * @param int      $entity        Dolibarr entity
	 * @param string[] $errors        Populated with validation/DB error strings on failure
	 * @return int  New rowid on success, -1 on failure
	 */
	public function create(array $data, int $fk_user_creat, int $entity = 1, array &$errors = []): int
	{
		$errors = $this->validate($data, $entity);
		if ($errors) return -1;

		$tbl   = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$ref   = $this->db->escape(trim($data['ref']));
		$lbl   = $this->db->escape(trim($data['label']));
		$ds    = !empty($data['date_start']) ? "'" . $this->db->escape($data['date_start']) . "'" : 'NULL';
		$de    = !empty($data['date_end'])   ? "'" . $this->db->escape($data['date_end'])   . "'" : 'NULL';
		$fk_ug = !empty($data['fk_usergroup'])   ? (int) $data['fk_usergroup']   : 'NULL';
		$fk_tr = !empty($data['fk_user_trainer']) ? (int) $data['fk_user_trainer'] : 0;
		$nb    = max(0, (int) ($data['nb_max'] ?? 0));
		$np    = $this->db->escape($data['note_public']  ?? '');
		$npr   = $this->db->escape($data['note_private'] ?? '');

		$sql = "INSERT INTO `{$tbl}`"
			. " (`entity`,`ref`,`label`,`date_start`,`date_end`,"
			. "  `fk_usergroup`,`fk_user_trainer`,`status`,`nb_max`,"
			. "  `note_public`,`note_private`,`datec`,`fk_user_creat`)"
			. " VALUES"
			. " ({$entity},'{$ref}','{$lbl}',{$ds},{$de},"
			. "  {$fk_ug},{$fk_tr}," . self::CLASS_DRAFT . ",{$nb},"
			. "  '{$np}','{$npr}',NOW(),{$fk_user_creat})";

		if (!$this->db->query($sql)) {
			$errors[] = $this->db->lasterror();
			return -1;
		}
		return (int) $this->db->last_insert_id($tbl, 'rowid');
	}

	/**
	 * Update an existing class.
	 *
	 * @param int      $id      Class rowid
	 * @param array    $data    Updated field values
	 * @param int      $entity  Dolibarr entity
	 * @param string[] $errors  Populated on failure
	 * @return int  1 on success, -1 on failure
	 */
	public function update(int $id, array $data, int $entity = 1, array &$errors = []): int
	{
		$errors = $this->validate($data, $entity, $id);
		if ($errors) return -1;

		$tbl   = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$ref   = $this->db->escape(trim($data['ref']));
		$lbl   = $this->db->escape(trim($data['label']));
		$ds    = !empty($data['date_start']) ? "'" . $this->db->escape($data['date_start']) . "'" : 'NULL';
		$de    = !empty($data['date_end'])   ? "'" . $this->db->escape($data['date_end'])   . "'" : 'NULL';
		$fk_ug = !empty($data['fk_usergroup'])   ? (int) $data['fk_usergroup']   : 'NULL';
		$fk_tr = !empty($data['fk_user_trainer']) ? (int) $data['fk_user_trainer'] : 0;
		$nb    = max(0, (int) ($data['nb_max'] ?? 0));
		$np    = $this->db->escape($data['note_public']  ?? '');
		$npr   = $this->db->escape($data['note_private'] ?? '');

		$sql = "UPDATE `{$tbl}` SET"
			. " `ref`='{$ref}', `label`='{$lbl}',"
			. " `date_start`={$ds}, `date_end`={$de},"
			. " `fk_usergroup`={$fk_ug}, `fk_user_trainer`={$fk_tr},"
			. " `nb_max`={$nb}, `note_public`='{$np}', `note_private`='{$npr}'"
			. " WHERE `rowid`={$id} AND `entity`={$entity}";

		if (!$this->db->query($sql)) {
			$errors[] = $this->db->lasterror();
			return -1;
		}
		return 1;
	}

	/**
	 * Delete a class and all its enrollment records.
	 * Does NOT touch llx_user or llx_usergroup_user — call closeClass() first.
	 */
	public function delete(int $id, int $entity = 1): int
	{
		$tbl_c  = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';

		$this->db->begin();
		$ok = $this->db->query("DELETE FROM `{$tbl_cu}` WHERE `fk_class`={$id}")
		   && $this->db->query("DELETE FROM `{$tbl_c}` WHERE `rowid`={$id} AND `entity`={$entity}");

		if ($ok) { $this->db->commit();   return 1; }
		           $this->db->rollback(); return -1;
	}

	/**
	 * Fetch a single class by rowid, including trainer and group names.
	 */
	public function fetch(int $id, int $entity = 1): ?array
	{
		$tbl = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$res = $this->db->query(
			"SELECT c.*, u.login AS trainer_login, u.firstname AS trainer_firstname,"
			. " u.lastname AS trainer_lastname, g.nom AS group_nom"
			. " FROM `{$tbl}` c"
			. " LEFT JOIN " . MAIN_DB_PREFIX . "user u ON u.rowid = c.fk_user_trainer"
			. " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup g ON g.rowid = c.fk_usergroup"
			. " WHERE c.rowid={$id} AND c.entity={$entity}"
		);
		if (!$res) return null;
		$row = $this->db->fetch_assoc($res);
		return $row ?: null;
	}

	/**
	 * Return classes for an entity with optional filtering and pagination.
	 *
	 * @param int   $entity   Dolibarr entity
	 * @param array $filters  Optional: 'status' (int|''), 'search' (string)
	 * @param int   $limit    Rows per page; 0 = no limit
	 * @param int   $offset   Row offset for pagination
	 * @return array[]
	 */
	public function fetchAll(int $entity = 1, array $filters = [], int $limit = 0, int $offset = 0): array
	{
		$tbl  = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$tbl2 = MAIN_DB_PREFIX . 'mokodolitraining_class_user';

		$where = $this->_buildWhereFilters($entity, $filters);

		$sql = "SELECT c.*, u.login AS trainer_login,"
			. " u.firstname AS trainer_firstname, u.lastname AS trainer_lastname,"
			. " g.nom AS group_nom,"
			. " COUNT(cu.rowid) AS nb_enrolled"
			. " FROM `{$tbl}` c"
			. " LEFT JOIN " . MAIN_DB_PREFIX . "user u ON u.rowid = c.fk_user_trainer"
			. " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup g ON g.rowid = c.fk_usergroup"
			. " LEFT JOIN `{$tbl2}` cu ON cu.fk_class = c.rowid"
			. " WHERE {$where}"
			. " GROUP BY c.rowid"
			. " ORDER BY c.datec DESC";

		if ($limit > 0) {
			$sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
		}

		$res = $this->db->query($sql);
		if (!$res) return [];
		$rows = [];
		while ($row = $this->db->fetch_assoc($res)) $rows[] = $row;
		return $rows;
	}

	/**
	 * Count classes matching optional filters (for pagination UI).
	 *
	 * @param int   $entity
	 * @param array $filters  Same keys as fetchAll()
	 * @return int
	 */
	public function countAll(int $entity = 1, array $filters = []): int
	{
		$tbl   = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$where = $this->_buildWhereFilters($entity, $filters);
		$res   = $this->db->query("SELECT COUNT(*) AS cnt FROM `{$tbl}` c WHERE {$where}");
		if (!$res) return 0;
		$row = $this->db->fetch_object($res);
		return (int) $row->cnt;
	}

	/**
	 * Return enrollment statistics for a class (counts by status).
	 *
	 * @return array{total: int, active: int, suspended: int, completed: int}
	 */
	public function getStats(int $fk_class): array
	{
		$tbl = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$res = $this->db->query(
			"SELECT"
			. " COUNT(*) AS total,"
			. " SUM(status=" . self::ENROLL_ACTIVE    . ") AS active,"
			. " SUM(status=" . self::ENROLL_SUSPENDED . ") AS suspended,"
			. " SUM(status=" . self::ENROLL_COMPLETED . ") AS completed"
			. " FROM `{$tbl}` WHERE fk_class={$fk_class}"
		);
		if (!$res) return ['total' => 0, 'active' => 0, 'suspended' => 0, 'completed' => 0];
		$row = $this->db->fetch_assoc($res);
		return [
			'total'     => (int) ($row['total']     ?? 0),
			'active'    => (int) ($row['active']    ?? 0),
			'suspended' => (int) ($row['suspended'] ?? 0),
			'completed' => (int) ($row['completed'] ?? 0),
		];
	}

	// ── Enrollment ─────────────────────────────────────────────────────────

	/**
	 * Enroll an existing Dolibarr user into a class.
	 *
	 * Wrapped in a transaction with FOR UPDATE locking to prevent:
	 *   - Orphaned usergroup memberships if enrollment INSERT fails
	 *   - Exceeding nb_max under concurrent load
	 *
	 * Does NOT create Dolibarr accounts — the user must already exist.
	 *
	 * @return array{ok: bool, error: string}
	 */
	public function enroll(int $fk_class, int $fk_user, int $fk_enroller, int $entity = 1): array
	{
		$this->db->begin();

		$tbl_c  = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';

		// Lock the class row to make capacity check + insert atomic
		$res_c = $this->db->query(
			"SELECT rowid, status, nb_max, fk_usergroup FROM `{$tbl_c}`"
			. " WHERE rowid={$fk_class} FOR UPDATE"
		);
		if (!$res_c) {
			$this->db->rollback();
			return ['ok' => false, 'error' => 'Class not found.'];
		}
		$class = $this->db->fetch_assoc($res_c);
		if (!$class) {
			$this->db->rollback();
			return ['ok' => false, 'error' => 'Class not found.'];
		}

		if ((int) $class['status'] === self::CLASS_CLOSED) {
			$this->db->rollback();
			return ['ok' => false, 'error' => 'Cannot enroll into a closed class.'];
		}

		// Capacity check is now inside the locked transaction — race-condition free.
		// Only count ENROLL_ACTIVE seats; suspended/completed rows do not hold a spot.
		if ((int) $class['nb_max'] > 0) {
			$cnt_res = $this->db->query(
				"SELECT COUNT(*) AS cnt FROM `{$tbl_cu}`"
				. " WHERE fk_class={$fk_class} AND status=" . self::ENROLL_ACTIVE
			);
			$enrolled = 0;
			if ($cnt_res) {
				$cnt_row  = $this->db->fetch_object($cnt_res);
				$enrolled = (int) $cnt_row->cnt;
			}
			if ($enrolled >= (int) $class['nb_max']) {
				$this->db->rollback();
				return ['ok' => false, 'error' => 'Class is at maximum capacity.'];
			}
		}

		// Guard against double-enroll
		$chk = $this->db->query(
			"SELECT rowid FROM `{$tbl_cu}` WHERE fk_class={$fk_class} AND fk_user={$fk_user}"
		);
		if ($chk && $this->db->fetch_object($chk)) {
			$this->db->rollback();
			return ['ok' => false, 'error' => 'User is already enrolled in this class.'];
		}

		// Assign usergroup if the class specifies one
		$fk_usergroup_user = 0;
		if (!empty($class['fk_usergroup'])) {
			$fk_usergroup_user = $this->_assignUsergroup(
				$fk_user,
				(int) $class['fk_usergroup'],
				$entity
			);
		}

		// Insert enrollment — if this fails, the transaction rolls back the usergroup insert too
		$fk_uu_val = $fk_usergroup_user ?: 'NULL';
		$sql = "INSERT INTO `{$tbl_cu}`"
			. " (`entity`,`fk_class`,`fk_user`,`fk_usergroup_user`,`status`,`datec`,`fk_user_enroller`)"
			. " VALUES ({$entity},{$fk_class},{$fk_user},{$fk_uu_val}," . self::ENROLL_ACTIVE . ",NOW(),{$fk_enroller})";

		if (!$this->db->query($sql)) {
			$this->db->rollback();
			return ['ok' => false, 'error' => 'Failed to save enrollment: ' . $this->db->lasterror()];
		}

		$this->db->commit();
		return ['ok' => true, 'error' => ''];
	}

	/**
	 * Unenroll a user from a class.
	 *
	 * Removes the specific usergroup membership that was created on enroll.
	 * Does NOT touch other group memberships.
	 *
	 * @return array{ok: bool, error: string}
	 */
	public function unenroll(int $fk_class, int $fk_user, int $entity = 1): array
	{
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';

		$this->db->begin();

		// SELECT inside the transaction with FOR UPDATE to prevent TOCTOU
		// (concurrent unenroll or closeClass could delete the same row between
		// a pre-transaction SELECT and the DELETE we issue below)
		$res = $this->db->query(
			"SELECT rowid, fk_usergroup_user FROM `{$tbl_cu}`"
			. " WHERE fk_class={$fk_class} AND fk_user={$fk_user} FOR UPDATE"
		);
		if (!$res) {
			$this->db->rollback();
			return ['ok' => false, 'error' => 'Enrollment not found.'];
		}
		$row = $this->db->fetch_assoc($res);
		if (!$row) {
			$this->db->rollback();
			return ['ok' => false, 'error' => 'Enrollment not found.'];
		}

		// Remove the usergroup membership we created (if any)
		if (!empty($row['fk_usergroup_user'])) {
			$tbl_uu = MAIN_DB_PREFIX . 'usergroup_user';
			$this->db->query(
				"DELETE FROM `{$tbl_uu}` WHERE rowid=" . (int) $row['fk_usergroup_user']
			);
		}

		$ok = $this->db->query(
			"DELETE FROM `{$tbl_cu}` WHERE fk_class={$fk_class} AND fk_user={$fk_user}"
		);

		if ($ok) { $this->db->commit();   return ['ok' => true,  'error' => '']; }
		           $this->db->rollback(); return ['ok' => false, 'error' => $this->db->lasterror()];
	}

	// ── Class lifecycle ────────────────────────────────────────────────────

	/**
	 * Validate and apply a status transition.
	 * Enforces the TRANSITIONS map without touching user accounts.
	 * For full side-effect transitions (user suspend/reactivate), use activateClass/closeClass.
	 *
	 * @return array{ok: bool, error: string}
	 */
	public function setStatus(int $id, int $to_status, int $entity = 1): array
	{
		$class = $this->fetch($id, $entity);
		if (!$class) return ['ok' => false, 'error' => 'Class not found.'];

		$from_status = (int) $class['status'];
		$allowed     = self::TRANSITIONS[$from_status] ?? [];
		if (!in_array($to_status, $allowed, true)) {
			return ['ok' => false, 'error' => "Transition {$from_status}→{$to_status} not allowed."];
		}

		$tbl = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$ok  = $this->db->query(
			"UPDATE `{$tbl}` SET `status`={$to_status} WHERE `rowid`={$id} AND `entity`={$entity}"
		);
		return $ok
			? ['ok' => true,  'error' => '']
			: ['ok' => false, 'error' => $this->db->lasterror()];
	}

	/**
	 * Activate a class: set status=Active, re-enable all enrolled user accounts.
	 *
	 * Phase 1 (atomic): class status + enrollment statuses — hard fail, rolled back.
	 * Phase 2 (best-effort): user account re-enable — partial success is accepted.
	 *
	 * @return array{ok: int, errors: string[]}
	 */
	public function activateClass(int $id, int $entity = 1): array
	{
		// TRANSITIONS guard — prevents illegal state changes (e.g. Draft → Active skipped)
		$class = $this->fetch($id, $entity);
		if (!$class) return ['ok' => 0, 'errors' => ['Class not found.']];
		if (!in_array(self::CLASS_ACTIVE, self::TRANSITIONS[(int) $class['status']] ?? [], true)) {
			return ['ok' => 0, 'errors' => ['Status transition not allowed from current state.']];
		}

		$ok = 0; $errors = [];

		$tbl_c  = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$tbl_u  = MAIN_DB_PREFIX . 'user';

		// Phase 1: Atomic — class + enrollment status changes must both succeed or neither commits
		$this->db->begin();

		if (!$this->db->query(
			"UPDATE `{$tbl_c}` SET `status`=" . self::CLASS_ACTIVE
			. " WHERE `rowid`={$id} AND `entity`={$entity}"
		)) {
			$this->db->rollback();
			return ['ok' => 0, 'errors' => ['Class status update: ' . $this->db->lasterror()]];
		}

		if (!$this->db->query(
			"UPDATE `{$tbl_cu}` SET `status`=" . self::ENROLL_ACTIVE
			. " WHERE `fk_class`={$id}"
		)) {
			$this->db->rollback();
			return ['ok' => 0, 'errors' => ['Enrollment status update: ' . $this->db->lasterror()]];
		}

		$this->db->commit();

		// Phase 2: Best-effort — re-enable user accounts outside the transaction;
		// partial failure here does not revert the class or enrollment status changes
		$users = $this->_getEnrolledUserIds($id);
		foreach ($users as $uid) {
			if ($this->db->query("UPDATE `{$tbl_u}` SET `statut`=1 WHERE `rowid`={$uid}")) {
				$ok++;
			} else {
				$errors[] = "Enable user {$uid}: " . $this->db->lasterror();
			}
		}

		return ['ok' => $ok, 'errors' => $errors];
	}

	/**
	 * Close a class: set status=Closed, mark enrollments completed,
	 * suspend trainees NOT enrolled in any other active class.
	 *
	 * Phase 1 (atomic): class status + enrollment completions — hard fail, rolled back.
	 * Phase 2 (best-effort): user account suspensions — partial success is accepted.
	 *
	 * @return array{ok: int, skipped: int, errors: string[]}
	 */
	public function closeClass(int $id, int $entity = 1): array
	{
		// TRANSITIONS guard
		$class = $this->fetch($id, $entity);
		if (!$class) return ['ok' => 0, 'skipped' => 0, 'errors' => ['Class not found.']];
		if (!in_array(self::CLASS_CLOSED, self::TRANSITIONS[(int) $class['status']] ?? [], true)) {
			return ['ok' => 0, 'skipped' => 0, 'errors' => ['Status transition not allowed from current state.']];
		}

		$ok = 0; $skipped = 0; $errors = [];

		$tbl_c  = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$tbl_u  = MAIN_DB_PREFIX . 'user';

		// Phase 1: Atomic — class + enrollment status changes must both succeed or neither commits
		$this->db->begin();

		if (!$this->db->query(
			"UPDATE `{$tbl_c}` SET `status`=" . self::CLASS_CLOSED
			. " WHERE `rowid`={$id} AND `entity`={$entity}"
		)) {
			$this->db->rollback();
			return ['ok' => 0, 'skipped' => 0, 'errors' => ['Class status update: ' . $this->db->lasterror()]];
		}

		if (!$this->db->query(
			"UPDATE `{$tbl_cu}` SET `status`=" . self::ENROLL_COMPLETED
			. " WHERE `fk_class`={$id} AND `status`=" . self::ENROLL_ACTIVE
		)) {
			$this->db->rollback();
			return ['ok' => 0, 'skipped' => 0, 'errors' => ['Enrollment completion: ' . $this->db->lasterror()]];
		}

		$this->db->commit();

		// Phase 2: Best-effort — suspend user accounts outside the transaction;
		// partial failure here does not revert the class or enrollment status changes
		$users = $this->_getEnrolledUserIds($id);
		foreach ($users as $uid) {
			if ($this->_hasOtherActiveEnrollment($uid, $id)) {
				$skipped++;
				continue;
			}
			if ($this->db->query("UPDATE `{$tbl_u}` SET `statut`=0 WHERE `rowid`={$uid}")) {
				$ok++;
			} else {
				$errors[] = "Suspend user {$uid}: " . $this->db->lasterror();
			}
		}

		return ['ok' => $ok, 'skipped' => $skipped, 'errors' => $errors];
	}

	// ── Export ─────────────────────────────────────────────────────────────

	/**
	 * Return the class roster as a CSV string (UTF-8, RFC 4180).
	 *
	 * @param int $fk_class
	 * @return string  CSV content (empty string if class not found)
	 */
	public function exportRosterCsv(int $fk_class): string
	{
		$class = $this->fetch($fk_class);
		if (!$class) return '';
		$rows  = $this->getEnrollments($fk_class);

		$status_map = [
			self::ENROLL_SUSPENDED => 'Suspended',
			self::ENROLL_ACTIVE    => 'Active',
			self::ENROLL_COMPLETED => 'Completed',
		];

		$csv  = "\"Class Ref\",\"Class Label\",\"Login\",\"First Name\",\"Last Name\","
			. "\"Email\",\"Enrolled On\",\"Enroll Status\",\"Account Status\"\n";

		$ref   = $class['ref']   ?? '';
		$label = $class['label'] ?? '';

		foreach ($rows as $r) {
			$enroll_date = !empty($r['enroll_date'])
				? date('Y-m-d H:i', strtotime($r['enroll_date']))
				: '';

			$csv .= implode(',', [
				'"' . str_replace('"', '""', $ref)                    . '"',
				'"' . str_replace('"', '""', $label)                  . '"',
				'"' . str_replace('"', '""', $r['login']     ?? '')   . '"',
				'"' . str_replace('"', '""', $r['firstname'] ?? '')   . '"',
				'"' . str_replace('"', '""', $r['lastname']  ?? '')   . '"',
				'"' . str_replace('"', '""', $r['email']     ?? '')   . '"',
				'"' . $enroll_date                                     . '"',
				'"' . ($status_map[(int) $r['enroll_status']] ?? 'Unknown') . '"',
				'"' . ((int) $r['user_statut'] === 1 ? 'Active' : 'Suspended') . '"',
			]) . "\n";
		}

		return $csv;
	}

	// ── Queries ────────────────────────────────────────────────────────────

	/**
	 * Return all enrollments for a class with user and enroller details.
	 */
	public function getEnrollments(int $fk_class): array
	{
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$tbl_u  = MAIN_DB_PREFIX . 'user';
		$res = $this->db->query(
			"SELECT cu.rowid, cu.fk_user, cu.fk_usergroup_user, cu.status AS enroll_status,"
			. " cu.datec AS enroll_date, cu.fk_user_enroller,"
			. " u.login, u.firstname, u.lastname, u.email, u.statut AS user_statut,"
			. " eu.login AS enroller_login"
			. " FROM `{$tbl_cu}` cu"
			. " JOIN `{$tbl_u}` u ON u.rowid = cu.fk_user"
			. " LEFT JOIN `{$tbl_u}` eu ON eu.rowid = cu.fk_user_enroller"
			. " WHERE cu.fk_class={$fk_class}"
			. " ORDER BY u.lastname, u.firstname"
		);
		if (!$res) return [];
		$rows = [];
		while ($row = $this->db->fetch_assoc($res)) $rows[] = $row;
		return $rows;
	}

	/**
	 * Return existing Dolibarr users NOT already enrolled in the given class.
	 * Excludes system users (rowid <= 1) and already-enrolled users.
	 */
	public function getAvailableUsers(int $fk_class, int $entity = 1): array
	{
		$tbl_u  = MAIN_DB_PREFIX . 'user';
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$res = $this->db->query(
			"SELECT u.rowid, u.login, u.firstname, u.lastname, u.statut"
			. " FROM `{$tbl_u}` u"
			. " WHERE u.rowid > 1"
			. "   AND u.entity IN (0, {$entity})"
			. "   AND u.rowid NOT IN ("
			. "     SELECT fk_user FROM `{$tbl_cu}` WHERE fk_class={$fk_class}"
			. "   )"
			. " ORDER BY u.lastname, u.firstname, u.login"
		);
		if (!$res) return [];
		$rows = [];
		while ($row = $this->db->fetch_assoc($res)) $rows[] = $row;
		return $rows;
	}

	/**
	 * Return all classes a user is enrolled in.
	 */
	public function getUserClasses(int $fk_user, int $entity = 1): array
	{
		$tbl_c  = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$res = $this->db->query(
			"SELECT c.*, cu.status AS enroll_status, cu.datec AS enroll_date"
			. " FROM `{$tbl_c}` c"
			. " JOIN `{$tbl_cu}` cu ON cu.fk_class = c.rowid"
			. " WHERE cu.fk_user={$fk_user} AND c.entity={$entity}"
			. " ORDER BY c.date_start DESC"
		);
		if (!$res) return [];
		$rows = [];
		while ($row = $this->db->fetch_assoc($res)) $rows[] = $row;
		return $rows;
	}

	/**
	 * Return all Dolibarr usergroups for the group selector in the class form.
	 */
	public function getGroups(int $entity = 1): array
	{
		$tbl = MAIN_DB_PREFIX . 'usergroup';
		$res = $this->db->query(
			"SELECT rowid, nom FROM `{$tbl}`"
			. " WHERE entity IN (0, {$entity}) ORDER BY nom"
		);
		if (!$res) return [];
		$rows = [];
		while ($row = $this->db->fetch_assoc($res)) $rows[] = $row;
		return $rows;
	}

	/**
	 * Return all active users for the trainer selector.
	 */
	public function getTrainers(int $entity = 1): array
	{
		$tbl_u = MAIN_DB_PREFIX . 'user';
		$res = $this->db->query(
			"SELECT rowid, login, firstname, lastname"
			. " FROM `{$tbl_u}`"
			. " WHERE statut=1 AND entity IN (0, {$entity}) AND rowid > 1"
			. " ORDER BY lastname, firstname"
		);
		if (!$res) return [];
		$rows = [];
		while ($row = $this->db->fetch_assoc($res)) $rows[] = $row;
		return $rows;
	}

	// ── Private helpers ────────────────────────────────────────────────────

	/**
	 * Add user to a usergroup, returning the new llx_usergroup_user.rowid.
	 * Returns 0 if already a member.
	 * Uses AUTO_INCREMENT — no manual rowid calculation, no race condition.
	 */
	private function _assignUsergroup(int $fk_user, int $fk_usergroup, int $entity): int
	{
		$tbl = MAIN_DB_PREFIX . 'usergroup_user';

		// Already a member?
		$chk = $this->db->query(
			"SELECT rowid FROM `{$tbl}`"
			. " WHERE fk_user={$fk_user} AND fk_usergroup={$fk_usergroup} AND entity={$entity}"
		);
		if ($chk && $this->db->fetch_object($chk)) return 0;

		$sql = "INSERT INTO `{$tbl}` (`entity`,`fk_user`,`fk_usergroup`)"
			. " VALUES ({$entity},{$fk_user},{$fk_usergroup})";

		if (!$this->db->query($sql)) return 0;
		return (int) $this->db->last_insert_id($tbl, 'rowid');
	}

	/** Count current enrollments for a class (all statuses). */
	private function _countEnrolled(int $fk_class): int
	{
		$tbl = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$res = $this->db->query(
			"SELECT COUNT(*) AS cnt FROM `{$tbl}` WHERE fk_class={$fk_class}"
		);
		if (!$res) return 0;
		$row = $this->db->fetch_object($res);
		return (int) $row->cnt;
	}

	/** Return array of fk_user values for all enrollments in a class. */
	private function _getEnrolledUserIds(int $fk_class): array
	{
		$tbl = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$res = $this->db->query(
			"SELECT fk_user FROM `{$tbl}` WHERE fk_class={$fk_class}"
		);
		if (!$res) return [];
		$ids = [];
		while ($row = $this->db->fetch_object($res)) $ids[] = (int) $row->fk_user;
		return $ids;
	}

	/**
	 * True if the user has an active enrollment in any OTHER active class.
	 * Used to skip suspension when closing a class the user has concurrent sessions in.
	 */
	private function _hasOtherActiveEnrollment(int $fk_user, int $except_class_id): bool
	{
		$tbl_cu = MAIN_DB_PREFIX . 'mokodolitraining_class_user';
		$tbl_c  = MAIN_DB_PREFIX . 'mokodolitraining_class';
		$res = $this->db->query(
			"SELECT cu.rowid FROM `{$tbl_cu}` cu"
			. " JOIN `{$tbl_c}` c ON c.rowid = cu.fk_class"
			. " WHERE cu.fk_user={$fk_user}"
			. "   AND c.status=" . self::CLASS_ACTIVE
			. "   AND c.rowid <> {$except_class_id}"
			. "   AND cu.status=" . self::ENROLL_ACTIVE
			. " LIMIT 1"
		);
		if (!$res) return false;
		return (bool) $this->db->fetch_object($res);
	}

	// ── Trainee account creation ───────────────────────────────────────────────

	/**
	 * Create a new Dolibarr user account for a trainee and enroll them in a class.
	 *
	 * Sets a session flag before calling User::create() so the trigger can mark
	 * this record as source='module' in llx_mokodolitraining_user_track, distinguishing
	 * it from user accounts created outside the training module.
	 *
	 * @param int    $fk_class   Class to enroll the new user in immediately after creation
	 * @param string $login      Username (login) — must be unique in Dolibarr
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $email
	 * @param string $pass       Plaintext password (Dolibarr hashes it)
	 * @param int    $fk_creator Rowid of the instructor creating this account
	 * @param int    $entity
	 * @return array{ok: bool, fk_user: int, error: string}
	 */
	public function createTrainee(
		int    $fk_class,
		string $login,
		string $firstname,
		string $lastname,
		string $email,
		string $pass,
		int    $fk_creator,
		int    $entity = 1
	): array {
		require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

		// Validate required fields
		if (trim($login) === '') {
			return ['ok' => false, 'fk_user' => 0, 'error' => 'TraineeErrLoginRequired'];
		}
		if (trim($pass) === '') {
			return ['ok' => false, 'fk_user' => 0, 'error' => 'TraineeErrPasswordRequired'];
		}

		// Check login uniqueness
		$tbl = MAIN_DB_PREFIX . 'user';
		$esc = $this->db->escape(trim($login));
		$res = $this->db->query("SELECT rowid FROM `{$tbl}` WHERE login='{$esc}' AND entity IN (0,{$entity})");
		if ($res && $this->db->fetch_object($res)) {
			return ['ok' => false, 'fk_user' => 0, 'error' => 'TraineeErrLoginDuplicate'];
		}

		// Mark creation as coming from the module so the trigger sets source='module'
		$_SESSION['mdt_creating_trainee'] = $fk_class;

		$newuser            = new User($this->db);
		$newuser->login     = trim($login);
		$newuser->firstname = trim($firstname);
		$newuser->lastname  = trim($lastname);
		$newuser->email     = trim($email);
		$newuser->pass      = $pass;
		$newuser->entity    = $entity;
		$newuser->statut    = 1; // active

		global $user;
		$new_id = $newuser->create($user);

		unset($_SESSION['mdt_creating_trainee']);

		if ($new_id <= 0) {
			return ['ok' => false, 'fk_user' => 0, 'error' => $newuser->error ?: 'TraineeCreateFailed'];
		}

		// Enroll the new user in the class
		$enroll = $this->enroll($fk_class, $new_id, $fk_creator, $entity);
		if (!$enroll['ok']) {
			return ['ok' => false, 'fk_user' => $new_id, 'error' => $enroll['error']];
		}

		return ['ok' => true, 'fk_user' => $new_id, 'error' => ''];
	}

	/**
	 * Return all username-tracking records for a given class (or all classes in entity).
	 * Ordered newest-first.
	 *
	 * @return array[]  Each row: fk_user, login, set_by, datec, fk_class, source, setter_login
	 */
	public function getTrackedUsers(int $entity, ?int $fk_class = null): array
	{
		$tbl  = MAIN_DB_PREFIX . 'mokodolitraining_user_track';
		$utbl = MAIN_DB_PREFIX . 'user';
		$sql  = "SELECT t.rowid, t.fk_user, t.login, t.set_by, t.datec, t.fk_class, t.source,"
			  . " u.login AS setter_login"
			  . " FROM `{$tbl}` t"
			  . " LEFT JOIN `{$utbl}` u ON u.rowid = t.set_by"
			  . " WHERE t.entity = " . (int) $entity;
		if ($fk_class !== null) {
			$sql .= " AND t.fk_class = " . (int) $fk_class;
		}
		$sql .= " ORDER BY t.datec DESC";
		$res = $this->db->query($sql);
		if (!$res) return [];
		$out = [];
		while ($row = $this->db->fetch_assoc($res)) {
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * Build the WHERE clause for fetchAll/countAll from an entity + filters array.
	 * Returns a safe SQL fragment (no leading WHERE keyword).
	 */
	private function _buildWhereFilters(int $entity, array $filters): string
	{
		$where = "c.entity={$entity}";

		if (isset($filters['status']) && $filters['status'] !== '') {
			$where .= " AND c.status=" . (int) $filters['status'];
		}

		if (!empty($filters['search'])) {
			$s      = $this->db->escape($filters['search']);
			$where .= " AND (c.ref LIKE '%{$s}%' OR c.label LIKE '%{$s}%')";
		}

		if (!empty($filters['trainer_id'])) {
			$where .= " AND c.fk_user_trainer=" . (int) $filters['trainer_id'];
		}

		return $where;
	}
}
