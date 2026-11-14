<?php
/**
 * MuServerMaker CMS - WebShop
 * Compra de items del WebShop.
 *
 * ASCII puro a proposito: los acentos van como \u{XXXX} para que json_encode() no falle.
 * Reutiliza los helpers de BlackMarket para la transaccion y para el baul.
 */

class WebShop {

    private static function ident($value, $default = '') {
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$value);
        return ($clean !== '') ? $clean : $default;
    }

    private static function num($value, $max = 255) {
        return max(0, min($max, (int)$value));
    }

    /**
     * Arma los 16 bytes del item (32 caracteres hex) con el mismo formato que usaba el modulo.
     */
    private static function buildItemHex($item) {
        $id    = self::num(isset($item['id']) ? $item['id'] : 0);
        $group = self::num(isset($item['group']) ? $item['group'] : 0, 15);
        $level = self::num(isset($item['level']) ? $item['level'] : 0, 15);
        $opt   = self::num(isset($item['option']) ? $item['option'] : 0, 7);
        $skill = !empty($item['skill']) && (int)$item['skill'] !== 0;
        $luck  = !empty($item['luck']) && (int)$item['luck'] !== 0;

        $exc = 0;
        if(isset($item['exc_opts']) && is_array($item['exc_opts'])) {
            foreach($item['exc_opts'] as $ex) {
                $bit = isset($ex['id']) ? (int)$ex['id'] : -1;
                if($bit >= 0 && $bit <= 5) $exc |= (1 << $bit);
            }
        }

        $byte1 = ($opt & 3) | ($level << 3);
        if($skill) $byte1 |= 128;
        if($luck)  $byte1 |= 4;
        if($opt > 3) $exc |= 64;

        $byte9 = ($group << 4) & 0xF0;
        if(isset($item['op380']) && (int)$item['op380'] === 1) $byte9 |= 8;

        $harmony = self::num(isset($item['harmony']['val']) ? $item['harmony']['val'] : 0);

        $hex = sprintf('%02X', $id) . sprintf('%02X', $byte1 & 255) . 'FF' . '00000000'
             . sprintf('%02X', $exc & 255) . '00' . sprintf('%02X', $byte9) . sprintf('%02X', $harmony);
        for($k = 0; $k < 5; $k++) {
            $hex .= sprintf('%02X', self::num(isset($item['sockets'][$k]['val']) ? $item['sockets'][$k]['val'] : 255));
        }
        return $hex;
    }

    /**
     * Compra un item del WebShop. Devuelve array con status (success / no_funds / error).
     */
    public static function buyItem($username, $uid) {
        if(!check_value($username)) return array('status' => 'error', 'message' => "Tu sesi\u{00F3}n ha expirado.");
        if(!is_string($uid) || $uid === '' || strlen($uid) > 64) return array('status' => 'error', 'message' => 'Item no encontrado.');
        if(!class_exists('BlackMarket')) return array('status' => 'error', 'message' => "Configuraci\u{00F3}n inv\u{00E1}lida.");

        // 1. Item (se lee del servidor; del navegador solo llega el uid)
        $listFile = __PATH_INCLUDES__ . 'config/webshop_list.json';
        $shopItems = file_exists($listFile) ? json_decode((string)@file_get_contents($listFile), true) : null;
        $target = null;
        if(is_array($shopItems)) {
            foreach($shopItems as $it) {
                if(is_array($it) && isset($it['uid']) && (string)$it['uid'] === $uid) { $target = $it; break; }
            }
        }
        if($target === null) return array('status' => 'error', 'message' => 'Item no encontrado.');

        $price = isset($target['price']) ? (int)$target['price'] : -1;
        if($price < 1 || $price > 2000000000) return array('status' => 'error', 'message' => "Este item no tiene un precio v\u{00E1}lido.");

        // 2. Moneda: tabla y columnas salen de la configuracion del AdminCP
        $setFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
        $settings = file_exists($setFile) ? json_decode((string)@file_get_contents($setFile), true) : array();
        if(!is_array($settings)) $settings = array();

        $coinTable = self::ident(isset($settings['table_coins']) ? $settings['table_coins'] : '', 'CashShopData');
        $userCol   = self::ident(isset($settings['col_user']) ? $settings['col_user'] : '', 'AccountId');
        if(strtolower($coinTable) == 'memb_info' && strtolower($userCol) == 'accountid') $userCol = 'memb___id';

        $currency = self::ident(isset($target['currency']) ? $target['currency'] : '');
        if($currency === '') return array('status' => 'error', 'message' => 'Moneda no configurada.');

        // Si el AdminCP tiene monedas definidas, la del item debe ser una de ellas
        $allowed = array();
        for($i = 1; $i <= 3; $i++) {
            $col = self::ident(isset($settings['col_coin_' . $i]) ? $settings['col_coin_' . $i] : '');
            if($col !== '') $allowed[] = strtolower($col);
        }
        if(!empty($allowed) && !in_array(strtolower($currency), $allowed, true)) {
            return array('status' => 'error', 'message' => 'La moneda de este item no est' . "\u{00E1}" . ' habilitada.');
        }

        // 3. Conexion y controles previos
        $db = Connection::Database('Me_MuOnline');
        if(!is_object($db)) return array('status' => 'error', 'message' => "Sin conexi\u{00F3}n con la base de datos.");

        if(BlackMarket::isOnline($db, $username)) {
            return array('status' => 'error', 'message' => "<b>\u{00A1}CUENTA CONECTADA!</b><br>Debes desconectarte del juego para realizar compras.");
        }

        $dbName = BlackMarket::dbName();
        if($dbName === null) return array('status' => 'error', 'message' => "Configuraci\u{00F3}n inv\u{00E1}lida.");

        $pdo = BlackMarket::pdoFrom($db);
        if($pdo === null) return array('status' => 'error', 'message' => "Sin conexi\u{00F3}n con la base de datos.");

        $run = function($sql, $params = array(), $fetch = false) use ($pdo) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $fetch ? $stmt->fetchAll(PDO::FETCH_ASSOC) : true;
        };

        $itemHex = self::buildItemHex($target);
        $balance = 0;

        // 4. Transaccion: se cobra y se entrega, o no se hace nada
        try {
            $pdo->beginTransaction();

            // Saldo, con la fila bloqueada hasta el final
            $bal = $run("SELECT [$currency] AS saldo FROM [$coinTable] WITH (UPDLOCK, ROWLOCK) WHERE [$userCol] = ?", array($username), true);
            $balance = (count($bal) === 1) ? (int)$bal[0]['saldo'] : 0;
            if($balance < $price) {
                $pdo->rollBack();
                return array('status' => 'no_funds', 'balance' => $balance, 'price' => $price, 'currency' => $currency);
            }

            // Baul vacio (solo la zona visible), bloqueado hasta el final
            $wh = $run(BlackMarket::vaultSql($dbName), array($username), true);
            if(count($wh) !== 1) throw new Exception("Ba\u{00FA}l no inicializado. Entr\u{00E1} al juego y abr\u{00ED} el ba\u{00FA}l una vez.");
            if((int)$wh[0]['tiene_items'] === 1) throw new Exception("<b>\u{00A1}BA\u{00DA}L OCUPADO!</b><br>Vac\u{00ED}a tu ba\u{00FA}l para comprar.");

            // El item va al primer casillero; el resto del baul no se toca
            $put = BlackMarket::vaultPut($dbName, $itemHex, (int)$wh[0]['bytes']);

            if($price > 0) {
                $run("UPDATE [$coinTable] SET [$currency] = [$currency] - ? WHERE [$userCol] = ?", array($price, $username));
            }
            $run($put['sql'], array($put['hex'], $username));

            $pdo->commit();
        } catch (PDOException $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[WebShop::buyItem] ' . $e->getMessage());
            return array('status' => 'error', 'message' => "No se pudo completar la compra. No se realiz\u{00F3} ning\u{00FA}n cobro.");
        } catch (Exception $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            return array('status' => 'error', 'message' => $e->getMessage());
        } catch (Throwable $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[WebShop::buyItem] ' . $e->getMessage());
            return array('status' => 'error', 'message' => 'Error interno. No se realiz' . "\u{00F3}" . ' ning' . "\u{00FA}" . 'n cobro.');
        }

        // 5. Registro de la compra (fuera de la transaccion)
        $db->query(
            "INSERT INTO MUSERVERMAKER_WEBSHOP_LOG (account_id, item_name, item_uid, price, currency, balance_before, balance_after, date_log) VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE())",
            array($username, isset($target['name']) ? (string)$target['name'] : '', $uid, $price, $currency, $balance, $balance - $price)
        );

        return array('status' => 'success');
    }
}