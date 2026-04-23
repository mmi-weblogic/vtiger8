<?php
class Users_TwoFactorAuth_View extends Vtiger_View_Controller {

	function loginRequired() { return false; }
	function checkPermission(Vtiger_Request $request) { return true; }

	function preProcess(Vtiger_Request $request, $display = true) {
		global $current_user;
		$viewer = $this->getViewer($request);
		$viewer->assign('PAGETITLE', 'Two-Factor Authentication');
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		$viewer->assign('STYLES', $this->getHeaderCss($request));
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('LANGUAGE_STRINGS', array());
		$viewer->assign('INVENTORY_MODULES', array());
		$viewer->assign('SELECTED_MENU_CATEGORY', '');
		$viewer->assign('QUALIFIED_MODULE', '');
		$viewer->assign('PARENT_MODULE', '');
		$viewer->assign('NOTIFIER_URL', '');
		$viewer->assign('EXTENSION_MODULE', '');
		$viewer->assign('CURRENT_USER_MODEL', $current_user);
		$viewer->assign('LANGUAGE', '');
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('Footer.tpl', $request->getModule());
	}

	function process(Vtiger_Request $request) {
		if (empty($_SESSION['2fa_pending_userid'])) {
			header('Location: index.php?module=Users&parent=Settings&view=Login');
			exit();
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('ERROR', $request->get('error'));
		$viewer->view('TwoFactorAuth.tpl', 'Users');
	}
}
