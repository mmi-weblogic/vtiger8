<?php
class Settings_LoginHistory_DashboardData_Action extends Vtiger_Action_Controller {

	function loginRequired() { return true; }

	function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			throw new AppException('Permission denied');
		}
		return true;
	}

	function process(Vtiger_Request $request) {
		$period = $request->get('period');
		if (!in_array($period, ['day', 'week', 'month', 'year'])) {
			$period = 'week';
		}

		$db  = PearDatabase::getInstance();
		$now = new DateTime();
		$start = clone $now;
		switch ($period) {
			case 'day':   $start->setTime(0, 0, 0); break;
			case 'week':  $start->modify('-6 days')->setTime(0, 0, 0); break;
			case 'month': $start->modify('-29 days')->setTime(0, 0, 0); break;
			case 'year':  $start->modify('-364 days')->setTime(0, 0, 0); break;
		}
		$startStr = $start->format('Y-m-d H:i:s');
		$endStr   = $now->format('Y-m-d H:i:s');

		// Login stats + hours per user
		$loginResult = $db->pquery(
			"SELECT u.id AS user_id,
			        TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS display_name,
			        COUNT(lh.login_id) AS login_count,
			        ROUND(SUM(
			            CASE
			                WHEN lh.status = 'Signed off' AND lh.logout_time > lh.login_time
			                    THEN TIMESTAMPDIFF(SECOND, lh.login_time, lh.logout_time) / 3600.0
			                WHEN lh.status = 'Signed in'
			                    THEN LEAST(TIMESTAMPDIFF(SECOND, lh.login_time, NOW()) / 3600.0, 12.0)
			                ELSE 0
			            END
			        ), 2) AS total_hours
			 FROM vtiger_loginhistory lh
			 JOIN vtiger_users u ON u.user_name = lh.user_name AND u.deleted = 0
			 WHERE lh.login_time >= ? AND lh.login_time <= ?
			 GROUP BY u.id, u.first_name, u.last_name
			 ORDER BY login_count DESC",
			[$startStr, $endStr]
		);

		$userStats = [];
		while ($row = $db->fetch_array($loginResult)) {
			$uid  = (int)$row['user_id'];
			$name = trim($row['display_name']) ?: 'User #' . $uid;
			$userStats[$uid] = [
				'user_id' => $uid,
				'name'    => $name,
				'logins'  => (int)$row['login_count'],
				'hours'   => (float)$row['total_hours'],
				'records' => 0,
			];
		}

		// Records created/updated per user
		$recResult = $db->pquery(
			"SELECT mb.whodid,
			        TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS display_name,
			        COUNT(*) AS record_count
			 FROM vtiger_modtracker_basic mb
			 JOIN vtiger_users u ON u.id = mb.whodid AND u.deleted = 0
			 WHERE mb.changedon >= ? AND mb.changedon <= ?
			   AND mb.status IN (0, 2)
			   AND mb.whodid > 0
			 GROUP BY mb.whodid, u.first_name, u.last_name",
			[$startStr, $endStr]
		);

		while ($row = $db->fetch_array($recResult)) {
			$uid = (int)$row['whodid'];
			if (!isset($userStats[$uid])) {
				$name = trim($row['display_name']) ?: 'User #' . $uid;
				$userStats[$uid] = ['user_id' => $uid, 'name' => $name, 'logins' => 0, 'hours' => 0.0, 'records' => 0];
			}
			$userStats[$uid]['records'] = (int)$row['record_count'];
		}

		// Productivity scores (logins 25%, hours 35%, records 40%)
		$vals = array_values($userStats);
		$maxL = max(array_column($vals, 'logins') ?: [1]) ?: 1;
		$maxH = max(array_column($vals, 'hours')  ?: [1]) ?: 1;
		$maxR = max(array_column($vals, 'records') ?: [1]) ?: 1;

		foreach ($userStats as &$u) {
			$u['score'] = (int)round(
				($u['logins'] / $maxL) * 25 +
				($u['hours']  / $maxH) * 35 +
				($u['records']/ $maxR) * 40
			);
		}
		unset($u);

		uasort($userStats, function($a, $b) { return $b['score'] - $a['score']; });

		// Daily timeline for line chart
		$tlResult = $db->pquery(
			"SELECT DATE(lh.login_time) AS day,
			        TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS display_name,
			        COUNT(*) AS login_count
			 FROM vtiger_loginhistory lh
			 JOIN vtiger_users u ON u.user_name = lh.user_name AND u.deleted = 0
			 WHERE lh.login_time >= ? AND lh.login_time <= ?
			 GROUP BY DATE(lh.login_time), u.id, u.first_name, u.last_name
			 ORDER BY day ASC",
			[$startStr, $endStr]
		);

		$timeline  = [];
		$tlUsers   = [];
		while ($row = $db->fetch_array($tlResult)) {
			$name = trim($row['display_name']) ?: 'Unknown';
			if (!isset($timeline[$row['day']])) $timeline[$row['day']] = [];
			$timeline[$row['day']][$name] = (int)$row['login_count'];
			$tlUsers[$name] = true;
		}

		$userList = array_values($userStats);
		$response = new Vtiger_Response();
		$response->setResult([
			'users'    => $userList,
			'timeline' => $timeline,
			'tl_users' => array_keys($tlUsers),
			'totals'   => [
				'logins'       => array_sum(array_column($userList, 'logins')),
				'hours'        => round(array_sum(array_column($userList, 'hours')), 1),
				'records'      => array_sum(array_column($userList, 'records')),
				'active_users' => count($userList),
			],
			'period' => $period,
			'range'  => $start->format('M j, Y') . ' – ' . $now->format('M j, Y'),
		]);
		$response->emit();
	}
}
