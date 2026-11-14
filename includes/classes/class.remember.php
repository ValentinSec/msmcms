<?php
/**
 * MuServerMaker CMS - Recordarme
 * Llave persistente segura: selector + validador (solo se guarda el hash del validador).
 * La llave deja de valer si cambia la contrasena de la cuenta (huella pw_fp).
 * ASCII puro.
 */

class Remember {

    const COOKIE = 'msm_remember';
    const DAYS   = 30;
    const TABLE  = 'MUSERVERMAKER_REMEMBER';

    private static function https() {
        return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    }

    private static function setCookie($value, $expires) {
        setcookie(self::COOKIE, $value, array(
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => self::https(),
            'httponly' => true,
            'samesite' => 'Lax'
        ));
        if($expires > time()) $_COOKIE[self::COOKIE] = $value; else unset($_COOKIE[self::COOKIE]);
    }

    private static function clearCookie() {
        if(isset($_COOKIE[self::COOKIE])) self::setCookie('', time() - 3600);
        unset($_SESSION['msm_remembered']);
    }

    private static function readCookie() {
        $raw = isset($_COOKIE[self::COOKIE]) ? (string)$_COOKIE[self::COOKIE] : '';
        return preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $raw, $m) ? array($m[1], $m[2]) : null;
    }

    private static function ident($v, $def) {
        $v = preg_replace('/[^A-Za-z0-9_]/', '', (string)$v);
        return ($v !== '') ? $v : $def;
    }

    /** Huella de la contrasena actual: si la contrasena cambia, la llave deja de servir. */
    private static function passwordFingerprint($db, $username) {
        $tbl  = self::ident(defined('_TBL_MI_') ? _TBL_MI_ : '', 'MEMB_INFO');
        $user = self::ident(defined('_CLMN_USERNM_') ? _CLMN_USERNM_ : '', 'memb___id');
        $pwd  = self::ident(defined('_CLMN_PASSWD_') ? _CLMN_PASSWD_ : '', 'memb__pwd');
        $r = $db->query_fetch_single("SELECT [$pwd] AS p FROM [$tbl] WHERE [$user] = ?", array($username));
        if(!is_array($r)) return '';
        $r = array_change_key_case($r, CASE_LOWER);
        return isset($r['p']) ? hash('sha256', (string)$r['p']) : '';
    }

    /** Crea una llave nueva para la cuenta y la guarda en el navegador. */
    public static function issue($username) {
        $username = trim((string)$username);
        if($username === '') return false;
        $db = Connection::Database('Me_MuOnline');
        if(!is_object($db)) return false;

        $fp = self::passwordFingerprint($db, $username);
        if($fp === '') return false;

        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expires   = time() + self::DAYS * 86400;

        $ok = $db->query(
            "INSERT INTO " . self::TABLE . " (selector, validator_hash, account_id, pw_fp, expires_at) VALUES (?, ?, ?, ?, ?)",
            array($selector, hash('sha256', $validator), $username, $fp, $expires)
        );
        if(!$ok) return false;

        self::setCookie($selector . ':' . $validator, $expires);
        $_SESSION['msm_remembered'] = true;

        if(mt_rand(1, 50) === 1) $db->query("DELETE FROM " . self::TABLE . " WHERE expires_at < ?", array(time()));
        return true;
    }

    /** Si no hay sesion y hay una llave valida, restaura la sesion. */
    public static function restore() {
        if(!empty($_SESSION['valid']) && !empty($_SESSION['username'])) {
            // Sesion recordada: no se corta por inactividad
            if(!empty($_SESSION['msm_remembered'])) $_SESSION['timeout'] = time();
            return;
        }

        $parts = self::readCookie();
        if($parts === null) { if(isset($_COOKIE[self::COOKIE])) self::clearCookie(); return; }
        list($selector, $validator) = $parts;

        $db = Connection::Database('Me_MuOnline');
        if(!is_object($db)) return;

        $row = $db->query_fetch_single("SELECT id, account_id, validator_hash, pw_fp, expires_at FROM " . self::TABLE . " WHERE selector = ?", array($selector));
        if(!is_array($row)) { self::clearCookie(); return; }
        $row = array_change_key_case($row, CASE_LOWER);
        $username = trim((string)$row['account_id']);

        // Validador incorrecto: posible robo de la llave, se anulan todas las de la cuenta
        if(!hash_equals((string)$row['validator_hash'], hash('sha256', $validator))) {
            $db->query("DELETE FROM " . self::TABLE . " WHERE account_id = ?", array($username));
            self::clearCookie();
            return;
        }

        // Vencida o con contrasena cambiada
        if((int)$row['expires_at'] < time() || self::passwordFingerprint($db, $username) !== (string)$row['pw_fp']) {
            $db->query("DELETE FROM " . self::TABLE . " WHERE " . ((int)$row['expires_at'] < time() ? "id = ?" : "account_id = ?"), array((int)$row['expires_at'] < time() ? (int)$row['id'] : $username));
            self::clearCookie();
            return;
        }

        // La llave usada se descarta (rotacion)
        $db->query("DELETE FROM " . self::TABLE . " WHERE id = ?", array((int)$row['id']));

        $common = new common();
        $userId = $common->retrieveUserID($username);
        if(!check_value($userId)) { self::clearCookie(); return; }
        $accountData = $common->accountInformation($userId);
        if(!is_array($accountData)) { self::clearCookie(); return; }

        session_regenerate_id(true);
        $_SESSION['valid']    = true;
        $_SESSION['timeout']  = time();
        $_SESSION['userid']   = $userId;
        $_SESSION['username'] = $accountData[_CLMN_USERNM_];

        self::issue($_SESSION['username']);
    }

    /** Cierre de sesion: anula la llave de este navegador. */
    public static function forget() {
        $parts = self::readCookie();
        if($parts !== null) {
            $db = Connection::Database('Me_MuOnline');
            if(is_object($db)) $db->query("DELETE FROM " . self::TABLE . " WHERE selector = ?", array($parts[0]));
        }
        self::clearCookie();
    }
}