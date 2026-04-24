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
			$scores = EngagementScore::getScores($ids);
			$response->setResult(['scores' => $scores]);

		} elseif ($mode === 'recalculate_single') {
			$crmid  = (int)$request->get('crmid');
			$module = $request->get('eng_module');
			if ($crmid && in_array($module, EngagementScore::MODULES)) {
				$score = EngagementScore::recalculateSingle($crmid, $module);
				$response->setResult($score ?: ['stars' => 0, 'raw' => 0]);
			} else {
				$response->setError('Invalid params');
			}
		}

		$response->emit();
	}
}
