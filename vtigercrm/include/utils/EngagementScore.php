<?php
class EngagementScore {

	const MODULES = ['Leads', 'Contacts', 'Accounts'];

	// Raw score → star (1-5) thresholds
	private static function rawToStars($raw) {
		if ($raw <= 0)  return 0;
		if ($raw <= 2)  return 1;
		if ($raw <= 5)  return 2;
		if ($raw <= 10) return 3;
		if ($raw <= 20) return 4;
		return 5;
	}

	public static function ensureTable() {
		$db = PearDatabase::getInstance();
		$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_engagement_score (
			crmid        INT          NOT NULL,
			module       VARCHAR(50)  NOT NULL,
			star_score   TINYINT(1)   NOT NULL DEFAULT 0,
			raw_score    INT          NOT NULL DEFAULT 0,
			act_count    INT          NOT NULL DEFAULT 0,
			cmt_count    INT          NOT NULL DEFAULT 0,
			note_count   INT          NOT NULL DEFAULT 0,
			att_count    INT          NOT NULL DEFAULT 0,
			camp_count   INT          NOT NULL DEFAULT 0,
			calculated_at DATETIME,
			PRIMARY KEY (crmid)
		) ENGINE=InnoDB", []);
	}

	public static function calculateAll($module) {
		self::ensureTable();
		$db = PearDatabase::getInstance();

		switch ($module) {
			case 'Leads':
				$sql = "INSERT INTO vtiger_engagement_score
					(crmid, module, raw_score, star_score, act_count, cmt_count, note_count, att_count, camp_count, calculated_at)
					SELECT
						ld.leadid,
						'Leads',
						COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0),
						CASE
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) = 0 THEN 0
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 2 THEN 1
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 5 THEN 2
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 10 THEN 3
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 20 THEN 4
							ELSE 5
						END,
						COALESCE(act.cnt,0), COALESCE(cmt.cnt,0), COALESCE(nt.cnt,0), COALESCE(att.cnt,0), COALESCE(cp.cnt,0),
						NOW()
					FROM vtiger_leaddetails ld
					JOIN vtiger_crmentity ce ON ce.crmid = ld.leadid AND ce.deleted = 0
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seactivityrel GROUP BY crmid) act ON act.crmid = ld.leadid
					LEFT JOIN (SELECT related_to, COUNT(*) cnt FROM vtiger_modcomments GROUP BY related_to) cmt ON cmt.related_to = ld.leadid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_senotesrel GROUP BY crmid) nt ON nt.crmid = ld.leadid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seattachmentsrel GROUP BY crmid) att ON att.crmid = ld.leadid
					LEFT JOIN (SELECT leadid, COUNT(*) cnt FROM vtiger_campaignleadrel GROUP BY leadid) cp ON cp.leadid = ld.leadid
					ON DUPLICATE KEY UPDATE
						module=VALUES(module), raw_score=VALUES(raw_score), star_score=VALUES(star_score),
						act_count=VALUES(act_count), cmt_count=VALUES(cmt_count), note_count=VALUES(note_count),
						att_count=VALUES(att_count), camp_count=VALUES(camp_count), calculated_at=NOW()";
				break;

			case 'Contacts':
				$sql = "INSERT INTO vtiger_engagement_score
					(crmid, module, raw_score, star_score, act_count, cmt_count, note_count, att_count, camp_count, calculated_at)
					SELECT
						ct.contactid,
						'Contacts',
						COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0),
						CASE
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) = 0 THEN 0
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 2 THEN 1
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 5 THEN 2
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 10 THEN 3
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 20 THEN 4
							ELSE 5
						END,
						COALESCE(act.cnt,0), COALESCE(cmt.cnt,0), COALESCE(nt.cnt,0), COALESCE(att.cnt,0), COALESCE(cp.cnt,0),
						NOW()
					FROM vtiger_contactdetails ct
					JOIN vtiger_crmentity ce ON ce.crmid = ct.contactid AND ce.deleted = 0
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seactivityrel GROUP BY crmid) act ON act.crmid = ct.contactid
					LEFT JOIN (SELECT related_to, COUNT(*) cnt FROM vtiger_modcomments GROUP BY related_to) cmt ON cmt.related_to = ct.contactid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_senotesrel GROUP BY crmid) nt ON nt.crmid = ct.contactid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seattachmentsrel GROUP BY crmid) att ON att.crmid = ct.contactid
					LEFT JOIN (SELECT contactid, COUNT(*) cnt FROM vtiger_campaigncontrel GROUP BY contactid) cp ON cp.contactid = ct.contactid
					ON DUPLICATE KEY UPDATE
						module=VALUES(module), raw_score=VALUES(raw_score), star_score=VALUES(star_score),
						act_count=VALUES(act_count), cmt_count=VALUES(cmt_count), note_count=VALUES(note_count),
						att_count=VALUES(att_count), camp_count=VALUES(camp_count), calculated_at=NOW()";
				break;

			case 'Accounts':
				$sql = "INSERT INTO vtiger_engagement_score
					(crmid, module, raw_score, star_score, act_count, cmt_count, note_count, att_count, camp_count, calculated_at)
					SELECT
						ac.accountid,
						'Accounts',
						COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0),
						CASE
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) = 0 THEN 0
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 2 THEN 1
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 5 THEN 2
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 10 THEN 3
							WHEN COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) <= 20 THEN 4
							ELSE 5
						END,
						COALESCE(act.cnt,0), COALESCE(cmt.cnt,0), COALESCE(nt.cnt,0), COALESCE(att.cnt,0), COALESCE(cp.cnt,0),
						NOW()
					FROM vtiger_account ac
					JOIN vtiger_crmentity ce ON ce.crmid = ac.accountid AND ce.deleted = 0
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seactivityrel GROUP BY crmid) act ON act.crmid = ac.accountid
					LEFT JOIN (SELECT related_to, COUNT(*) cnt FROM vtiger_modcomments GROUP BY related_to) cmt ON cmt.related_to = ac.accountid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_senotesrel GROUP BY crmid) nt ON nt.crmid = ac.accountid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seattachmentsrel GROUP BY crmid) att ON att.crmid = ac.accountid
					LEFT JOIN (SELECT accountid, COUNT(*) cnt FROM vtiger_campaignaccountrel GROUP BY accountid) cp ON cp.accountid = ac.accountid
					ON DUPLICATE KEY UPDATE
						module=VALUES(module), raw_score=VALUES(raw_score), star_score=VALUES(star_score),
						act_count=VALUES(act_count), cmt_count=VALUES(cmt_count), note_count=VALUES(note_count),
						att_count=VALUES(att_count), camp_count=VALUES(camp_count), calculated_at=NOW()";
				break;

			default:
				return 0;
		}

		$db->pquery($sql, []);
		return $db->getRowCount();
	}

	public static function getScores(array $crmids) {
		if (empty($crmids)) return [];
		$db = PearDatabase::getInstance();
		$ph = implode(',', array_fill(0, count($crmids), '?'));
		$result = $db->pquery(
			"SELECT crmid, star_score, raw_score, act_count, cmt_count, note_count, att_count, camp_count
			 FROM vtiger_engagement_score WHERE crmid IN ($ph)",
			$crmids
		);
		$out = [];
		while ($row = $db->fetch_array($result)) {
			$out[(int)$row['crmid']] = [
				'stars'      => (int)$row['star_score'],
				'raw'        => (int)$row['raw_score'],
				'activities' => (int)$row['act_count'],
				'comments'   => (int)$row['cmt_count'],
				'notes'      => (int)$row['note_count'],
				'attachments'=> (int)$row['att_count'],
				'campaigns'  => (int)$row['camp_count'],
			];
		}
		return $out;
	}

	public static function recalculateSingle($crmid, $module) {
		self::ensureTable();
		$db = PearDatabase::getInstance();

		switch ($module) {
			case 'Leads':
				$result = $db->pquery(
					"SELECT
						COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) AS raw,
						COALESCE(act.cnt,0) AS a, COALESCE(cmt.cnt,0) AS c, COALESCE(nt.cnt,0) AS n,
						COALESCE(att.cnt,0) AS t, COALESCE(cp.cnt,0) AS p
					FROM vtiger_leaddetails ld
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seactivityrel GROUP BY crmid) act ON act.crmid = ld.leadid
					LEFT JOIN (SELECT related_to, COUNT(*) cnt FROM vtiger_modcomments GROUP BY related_to) cmt ON cmt.related_to = ld.leadid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_senotesrel GROUP BY crmid) nt ON nt.crmid = ld.leadid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seattachmentsrel GROUP BY crmid) att ON att.crmid = ld.leadid
					LEFT JOIN (SELECT leadid, COUNT(*) cnt FROM vtiger_campaignleadrel GROUP BY leadid) cp ON cp.leadid = ld.leadid
					WHERE ld.leadid = ?",
					[$crmid]
				);
				$campField = 'cp.leadid';
				break;
			case 'Contacts':
				$result = $db->pquery(
					"SELECT
						COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) AS raw,
						COALESCE(act.cnt,0) AS a, COALESCE(cmt.cnt,0) AS c, COALESCE(nt.cnt,0) AS n,
						COALESCE(att.cnt,0) AS t, COALESCE(cp.cnt,0) AS p
					FROM vtiger_contactdetails ct
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seactivityrel GROUP BY crmid) act ON act.crmid = ct.contactid
					LEFT JOIN (SELECT related_to, COUNT(*) cnt FROM vtiger_modcomments GROUP BY related_to) cmt ON cmt.related_to = ct.contactid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_senotesrel GROUP BY crmid) nt ON nt.crmid = ct.contactid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seattachmentsrel GROUP BY crmid) att ON att.crmid = ct.contactid
					LEFT JOIN (SELECT contactid, COUNT(*) cnt FROM vtiger_campaigncontrel GROUP BY contactid) cp ON cp.contactid = ct.contactid
					WHERE ct.contactid = ?",
					[$crmid]
				);
				break;
			case 'Accounts':
				$result = $db->pquery(
					"SELECT
						COALESCE(act.cnt,0)*3 + COALESCE(cmt.cnt,0)*2 + COALESCE(nt.cnt,0) + COALESCE(att.cnt,0) + COALESCE(cp.cnt,0) AS raw,
						COALESCE(act.cnt,0) AS a, COALESCE(cmt.cnt,0) AS c, COALESCE(nt.cnt,0) AS n,
						COALESCE(att.cnt,0) AS t, COALESCE(cp.cnt,0) AS p
					FROM vtiger_account ac
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seactivityrel GROUP BY crmid) act ON act.crmid = ac.accountid
					LEFT JOIN (SELECT related_to, COUNT(*) cnt FROM vtiger_modcomments GROUP BY related_to) cmt ON cmt.related_to = ac.accountid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_senotesrel GROUP BY crmid) nt ON nt.crmid = ac.accountid
					LEFT JOIN (SELECT crmid, COUNT(*) cnt FROM vtiger_seattachmentsrel GROUP BY crmid) att ON att.crmid = ac.accountid
					LEFT JOIN (SELECT accountid, COUNT(*) cnt FROM vtiger_campaignaccountrel GROUP BY accountid) cp ON cp.accountid = ac.accountid
					WHERE ac.accountid = ?",
					[$crmid]
				);
				break;
			default:
				return null;
		}

		$row = $db->fetch_array($result);
		if (!$row) return null;

		$raw   = (int)$row['raw'];
		$stars = self::rawToStars($raw);

		$db->pquery(
			"INSERT INTO vtiger_engagement_score (crmid, module, raw_score, star_score, act_count, cmt_count, note_count, att_count, camp_count, calculated_at)
			 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
			 ON DUPLICATE KEY UPDATE
			   module=VALUES(module), raw_score=VALUES(raw_score), star_score=VALUES(star_score),
			   act_count=VALUES(act_count), cmt_count=VALUES(cmt_count), note_count=VALUES(note_count),
			   att_count=VALUES(att_count), camp_count=VALUES(camp_count), calculated_at=NOW()",
			[$crmid, $module, $raw, $stars, $row['a'], $row['c'], $row['n'], $row['t'], $row['p']]
		);

		return ['stars' => $stars, 'raw' => $raw];
	}
}
