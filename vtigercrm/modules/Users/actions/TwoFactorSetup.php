<?php
require_once 'include/utils/TOTP.php';

class Users_TwoFactorSetup_Action extends Vtiger_Action_Controller {

	function loginRequired() { return true; }
	function checkPermission(Vtiger_Request $request) { return true; }

	function process(Vtiger_Request $request) {
		$mode        = $request->get('mode');
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId      = (int)$currentUser->getId();
		$db          = PearDatabase::getInstance();
		$response    = new Vtiger_Response();

		if ($mode === 'generate') {
			$secret = TOTP::generateSecret();
			$_SESSION['2fa_setup_secret'] = $secret;
			$otpauthUrl = TOTP::getOtpauthUrl($currentUser->get('user_name'), $secret);
			$response->setResult(array('secret' => $secret, 'otpauth_url' => $otpauthUrl));

		} elseif ($mode === 'enable') {
			$code   = preg_replace('/\s+/', '', $request->get('code'));
			$secret = isset($_SESSION['2fa_setup_secret']) ? $_SESSION['2fa_setup_secret'] : '';
			if ($secret && TOTP::verify($secret, $code)) {
				$db->pquery('UPDATE vtiger_users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?', array($secret, $userId));
				unset($_SESSION['2fa_setup_secret']);
				$response->setResult(array('success' => true));
			} else {
				$response->setError('Invalid code. Please try again.');
			}

		} elseif ($mode === 'disable') {
			$db->pquery('UPDATE vtiger_users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?', array($userId));
			$response->setResult(array('success' => true));

		} elseif ($mode === 'status') {
			$result  = $db->pquery('SELECT totp_enabled FROM vtiger_users WHERE id = ?', array($userId));
			$row     = $db->fetch_array($result);
			$response->setResult(array('enabled' => !empty($row['totp_enabled'])));
		}

		$response->emit();
	}
}
