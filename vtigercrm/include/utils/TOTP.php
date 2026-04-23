<?php
/**
 * Pure-PHP TOTP (RFC 6238) implementation — Google Authenticator compatible.
 */
class TOTP {

    const DIGITS = 6;
    const PERIOD = 30;
    const ISSUER = 'Weblogical CRM';

    public static function generateSecret($length = 16) {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / self::PERIOD);
        }
        $key    = self::base32Decode($secret);
        $time   = pack('N*', 0) . pack('N*', $timeSlice);
        $hash   = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code   = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            ( ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);
        return str_pad($code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    // Allow ±1 window (30 s clock drift)
    public static function verify($secret, $code, $discrepancy = 1) {
        $code = preg_replace('/\s+/', '', $code);
        $ts   = floor(time() / self::PERIOD);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            if (hash_equals(self::getCode($secret, $ts + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function getOtpauthUrl($username, $secret) {
        $label = rawurlencode(self::ISSUER . ':' . $username);
        return 'otpauth://totp/' . $label
            . '?secret='    . $secret
            . '&issuer='    . rawurlencode(self::ISSUER)
            . '&algorithm=SHA1&digits=' . self::DIGITS
            . '&period='    . self::PERIOD;
    }

    private static function base32Decode($input) {
        $map    = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $input  = strtoupper(rtrim($input, '='));
        $output = '';
        $buf    = 0;
        $bits   = 0;
        for ($i = 0; $i < strlen($input); $i++) {
            if (!isset($map[$input[$i]])) continue;
            $buf   = ($buf << 5) | $map[$input[$i]];
            $bits += 5;
            if ($bits >= 8) {
                $bits  -= 8;
                $output .= chr(($buf >> $bits) & 0xFF);
            }
        }
        return $output;
    }
}
