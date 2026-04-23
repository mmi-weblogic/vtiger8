<?php
/*
 * Record locking action — acquire, release, keepalive.
 * Lock expires after 10 minutes with no keepalive.
 */

class Vtiger_LockRecord_Action extends Vtiger_Action_Controller {

    const LOCK_TTL = 600; // 10 minutes

    function loginRequired() { return true; }
    function checkPermission(Vtiger_Request $request) { return true; }

    private static function isExpired($lockedTime) {
        return !$lockedTime || (time() - strtotime($lockedTime)) >= self::LOCK_TTL;
    }

    function process(Vtiger_Request $request) {
        $mode        = $request->get('mode');
        $recordId    = (int) $request->get('record');
        $db          = PearDatabase::getInstance();
        $response    = new Vtiger_Response();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userId      = (int) $currentUser->getId();

        if ($mode === 'acquire') {
            $result = $db->pquery(
                'SELECT locked_by, locked_time FROM vtiger_crmentity WHERE crmid = ?',
                array($recordId)
            );
            $row      = $db->fetch_array($result);
            $lockedBy = (int) $row['locked_by'];
            $expired  = self::isExpired($row['locked_time']);

            if ($lockedBy && $lockedBy !== $userId && !$expired) {
                $userRes = $db->pquery('SELECT first_name, last_name FROM vtiger_users WHERE id = ?', array($lockedBy));
                $userRow = $db->fetch_array($userRes);
                $response->setResult(array(
                    'locked'    => true,
                    'locked_by' => trim($userRow['first_name'] . ' ' . $userRow['last_name']),
                ));
            } else {
                // Acquire (or take over an expired lock)
                $db->pquery(
                    'UPDATE vtiger_crmentity SET locked_by = ?, locked_time = NOW() WHERE crmid = ?',
                    array($userId, $recordId)
                );
                $response->setResult(array('locked' => false));
            }

        } elseif ($mode === 'release') {
            $db->pquery(
                'UPDATE vtiger_crmentity SET locked_by = NULL, locked_time = NULL WHERE crmid = ? AND locked_by = ?',
                array($recordId, $userId)
            );
            $response->setResult(array('released' => true));

        } elseif ($mode === 'keepalive') {
            $db->pquery(
                'UPDATE vtiger_crmentity SET locked_time = NOW() WHERE crmid = ? AND locked_by = ?',
                array($recordId, $userId)
            );
            $response->setResult(array('ok' => true));

        } elseif ($mode === 'check') {
            $result = $db->pquery(
                'SELECT e.locked_by, e.locked_time, u.first_name, u.last_name
                 FROM vtiger_crmentity e
                 LEFT JOIN vtiger_users u ON u.id = e.locked_by
                 WHERE e.crmid = ?',
                array($recordId)
            );
            $row      = $db->fetch_array($result);
            $lockedBy = (int) $row['locked_by'];
            $expired  = self::isExpired($row['locked_time']);

            if ($lockedBy && $lockedBy !== $userId && !$expired) {
                $response->setResult(array(
                    'locked'    => true,
                    'locked_by' => trim($row['first_name'] . ' ' . $row['last_name']),
                ));
            } else {
                // Auto-clear any stale lock
                if ($lockedBy && $expired) {
                    $db->pquery(
                        'UPDATE vtiger_crmentity SET locked_by = NULL, locked_time = NULL WHERE crmid = ?',
                        array($recordId)
                    );
                }
                $response->setResult(array('locked' => false));
            }
        }

        $response->emit();
    }
}
