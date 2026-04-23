<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once 'include/utils/TOTP.php';

class Users_Login_Action extends Vtiger_Action_Controller {

	function loginRequired() {
		return false;
	}

	function checkPermission(Vtiger_Request $request) {
		return true;
	}

	function process(Vtiger_Request $request) {
		if ($_SERVER["REQUEST_METHOD"] != "POST") {
			echo "Invalid request";
			exit();
		}

		$username = $request->get('username');
		$password = $request->getRaw('password');

		$user = CRMEntity::getInstance('Users');
		$user->column_fields['user_name'] = $username;

		if ($user->doLogin($password)) {
			$userid = $user->retrieve_user_id($username);

			// Check if 2FA is enabled for this user
			$db     = PearDatabase::getInstance();
			$result = $db->pquery('SELECT totp_enabled, totp_secret FROM vtiger_users WHERE id = ?', array($userid));
			$row    = $db->fetch_array($result);

			if (!empty($row['totp_enabled']) && !empty($row['totp_secret'])) {
				// Hold credentials in session until 2FA is verified
				session_regenerate_id(true);
				$_SESSION['2fa_pending_userid']   = $userid;
				$_SESSION['2fa_pending_username'] = $username;
				$_SESSION['2fa_pending_skin']     = $request->get('skin');
				header('Location: index.php?module=Users&view=TwoFactorAuth');
				exit();
			}

			// No 2FA — complete login immediately
			$this->completeLogin($userid, $username, $request->get('skin'));
		} else {
			header('Location: index.php?module=Users&parent=Settings&view=Login&error=login');
			exit;
		}
	}

	public static function completeLogin($userid, $username, $skin = '') {
		session_regenerate_id(true);

		Vtiger_Session::set('AUTHUSERID', $userid);

		$db = PearDatabase::getInstance();
		$db->pquery('UPDATE vtiger_users SET current_session_id = ? WHERE id = ?', array(session_id(), $userid));

		$_SESSION['authenticated_user_id']       = $userid;
		$_SESSION['app_unique_key']              = vglobal('application_unique_key');
		$_SESSION['authenticated_user_language'] = vglobal('default_language');
		$_SESSION['authenticated_user_skin']     = $skin;

		$_SESSION['KCFINDER']              = array();
		$_SESSION['KCFINDER']['disabled']  = false;
		$_SESSION['KCFINDER']['uploadURL'] = 'test/upload';
		$_SESSION['KCFINDER']['uploadDir'] = '../test/upload';
		$_SESSION['KCFINDER']['deniedExts'] = implode(' ', vglobal('upload_badext'));

		$moduleModel = Users_Module_Model::getInstance('Users');
		$moduleModel->saveLoginHistory($username);

		header('Location: index.php?module=Users&parent=Settings&view=SystemSetup');
		exit();
	}
}
