<?php
require_once 'include/utils/EngagementScore.php';

class Vtiger_EngagementScore_Action extends Vtiger_Action_Controller {

	function loginRequired() { return true; }
	function checkPermission(Vtiger_Request $request) { return true; }

	function process(Vtiger_Request $request) {
		$mode     = $request->get('mode');
		$response = new Vtiger_Response();

		if ($mode === 'calculate') {
			$currentUser = Users_Record_Model::getCurrentUserModel();
			if (!$currentUser->isAdminUser()) {
				$response->setError('Admin required');
				$response->emit();
				return;
			}
			$module = $request->get('eng_module');
			if (!in_array($module, EngagementScore::MODULES)) {
				$response->setError('Invalid module');
				$response->emit();
				return;
			}
			$count = EngagementScore::calculateAll($module);
			$response->setResult(['updated' => $count, 'module' => $module]);

		} elseif ($mode === 'get_scores') {
			$ids = $request->get('crmids');
			if (is_string($ids)) {
				$ids = array_filter(array_map('intval', explode(',', $ids)));
			} elseif (is_array($ids)) {
				$ids = array_filter(array_map('intval', $ids));
			} else {
				$ids = [];
			}
			// Filter to only records the current user has permission to view
			$ids = $this->filterAccessibleCrmids($ids);
			$scores = EngagementScore::getScores($ids);
			$response->setResult(['scores' => $scores]);

		} elseif ($mode === 'recalculate_single') {
			$crmid  = (int)$request->get('crmid');
			$module = $request->get('eng_module');
			if ($crmid && in_array($module, EngagementScore::MODULES)) {
				// Verify the current user can access this record
				$moduleModel = Vtiger_Module_Model::getInstance($module);
				$recordModel = Vtiger_Record_Model::getInstanceById($crmid, $module);
				if (!$recordModel || !Users_Privileges_Model::isPermitted($module, 'DetailView', $crmid)) {
					$response->setError('Permission denied');
					$response->emit();
					return;
				}
				$score = EngagementScore::recalculateSingle($crmid, $module);
				$response->setResult($score ?: ['stars' => 0, 'raw' => 0]);
			} else {
				$response->setError('Invalid params');
			}
		}

		$response->emit();
	}

	/**
	 * Filter a list of crmids to only those the current user has DetailView permission on.
	 */
	private function filterAccessibleCrmids(array $crmids) {
		if (empty($crmids)) return [];
		$db         = PearDatabase::getInstance();
		$accessible = [];
		// Look up the module for each crmid via vtiger_crmentity, then check permission
		$ph     = implode(',', array_fill(0, count($crmids), '?'));
		$result = $db->pquery(
			"SELECT crmid, setype FROM vtiger_crmentity WHERE crmid IN ($ph) AND deleted = 0",
			$crmids
		);
		while ($row = $db->fetch_array($result)) {
			$crmid  = (int)$row['crmid'];
			$module = $row['setype'];
			if (in_array($module, EngagementScore::MODULES) &&
				Users_Privileges_Model::isPermitted($module, 'DetailView', $crmid)) {
				$accessible[] = $crmid;
			}
		}
		return $accessible;
	}
}
