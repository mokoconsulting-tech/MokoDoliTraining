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
 * VERSION:  01.00.01
 * BRIEF:    Dolibarr trigger class for MokoDoliTraining audit and safety hooks.
 * NOTE:     Priority 99 ensures this fires last, after all core triggers.
 */

class interface_99_modMokoDoliTraining_MokoDoliTrainingTrigger extends CommonTrigger
{
	public function __construct($db)
	{
		$this->db          = $db;
		$this->name        = 'MOKODOLITRAINING_TRIGGER';
		$this->family      = 'demo';
		$this->description = 'MokoDoliTraining audit and safety trigger.';
		$this->version     = '1.0.0';
		$this->picto       = 'technic';
	}

	public function getName(): string       { return $this->name;        }
	public function getDesc(): string       { return $this->description; }
	public function getVersion(): string    { return $this->version;     }

	/**
	 * Fired by Dolibarr when tracked events occur.
	 * Currently: logs USER_SETPASSWORD during training sessions as a safety notice.
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
		if (!getDolGlobalString('MOKODOLITRAINING_SEEDED')) return 0;

		$watched = [
			'USER_SETPASSWORD',
			'USER_MODIFY',
			'USER_DELETE',
		];

		if (!in_array($action, $watched, true)) return 0;

		dol_include_once('/mokodolitraining/class/MokoDoliTrainingAudit.class.php');
		$audit = new MokoDoliTrainingAudit($this->db);

		$target_id = $object->id ?? ($object->rowid ?? 0);
		$note      = $action . ' on user rowid=' . (int) $target_id . ' while training data is seeded';

		$audit->log(
			fk_user:      (int) $user->id,
			action:       'integrity_check',
			status:       'ok',
			rows_affected: 0,
			note:         $note,
			entity:       (int) $conf->entity
		);

		return 0;
	}
}
