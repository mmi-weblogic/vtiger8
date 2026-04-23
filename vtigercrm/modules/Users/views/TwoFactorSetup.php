<?php
require_once 'include/utils/TOTP.php';

class Users_TwoFactorSetup_View extends Vtiger_Index_View {

	function process(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId      = (int)$currentUser->getId();
		$db          = PearDatabase::getInstance();
		$result      = $db->pquery('SELECT totp_enabled FROM vtiger_users WHERE id = ?', array($userId));
		$row         = $db->fetch_array($result);

		$viewer = $this->getViewer($request);
		$viewer->assign('TOTP_ENABLED', !empty($row['totp_enabled']));
		$viewer->assign('USERNAME', $currentUser->get('user_name'));
		$viewer->assign('RECORD_ID', $userId);
		$viewer->view('TwoFactorSetup.tpl', 'Users');
	}
}
