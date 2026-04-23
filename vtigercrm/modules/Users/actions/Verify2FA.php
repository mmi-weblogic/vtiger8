<?php
require_once 'include/utils/TOTP.php';
require_once 'modules/Users/actions/Login.php';

class Users_Verify2FA_Action extends Vtiger_Action_Controller {

	function loginRequired() { return false; }
	function checkPermission(Vtiger_Request $request) { return true; }

	function process(Vtiger_Request $request) {
		$pendingId = isset($_SESSION['2fa_pending_userid']) ? (int)$_SESSION['2fa_pending_userid'] : 0;

		if (!$pendingId) {
			header('Location: index.php?module=Users&parent=Settings&view=Login');
			exit();
		}

		$code = preg_replace('/\s+/', '', $request->get('totp_code'));

		$db     = PearDatabase::getInstance();
		$result = $db->pquery('SELECT totp_secret, user_name FROM vtiger_users WHERE id = ?', array($pendingId));
		$row    = $db->fetch_array($result);

		if ($row && TOTP::verify($row['totp_secret'], $code)) {
			$skin     = isset($_SESSION['2fa_pending_skin']) ? $_SESSION['2fa_pending_skin'] : '';
			$username = $row['user_name'];

			unset($_SESSION['2fa_pending_userid'], $_SESSION['2fa_pending_username'], $_SESSION['2fa_pending_skin']);

			Users_Login_Action::completeLogin($pendingId, $username, $skin);
		} else {
			header('Location: index.php?module=Users&view=TwoFactorAuth&error=invalid');
			exit();
		}
	}
}
