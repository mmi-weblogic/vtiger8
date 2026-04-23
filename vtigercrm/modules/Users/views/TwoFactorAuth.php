<?php
class Users_TwoFactorAuth_View extends Vtiger_Action_Controller {

	function loginRequired() { return false; }
	function checkPermission(Vtiger_Request $request) { return true; }

	function process(Vtiger_Request $request) {
		if (empty($_SESSION['2fa_pending_userid'])) {
			header('Location: index.php?module=Users&parent=Settings&view=Login');
			exit();
		}
		$viewer = new Vtiger_Viewer();
		$viewer->assign('ERROR', $request->get('error'));
		$viewer->view('TwoFactorAuth.tpl', 'Users');
	}
}
