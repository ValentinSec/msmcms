<?php
/**
 * MuServerMaker CMS - Casino Goblin
 * Giro (cobro + premio pendiente) y retiro del premio al baul.
 *
 * ASCII puro a proposito: los acentos van como \u{XXXX} para que json_encode() no falle.
 * Reutiliza los helpers de BlackMarket para la transaccion y para el baul.
 */

class Casino {

    const TABLE_PENDING = 'MUSERVERMAKER_CASINO_PENDING';

    private static function ident($value, $default = '') {
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$value);
        return ($clean !== '') ? $clean : $default;
    }

    private static function num($value, $max = 255) {
        return max(0, min($max, (int)$value));
    }

    private static function cut($text, $len) {
        $text = trim((string)$text);
        return function_exists('mb_substr') ? mb_substr($text, 0, $len, 'UTF-8') : substr($text, 0, $len);
    }

    private static function fail($message) {
        return array('status' => 'error', 'message' => $message);
    }

    /** Cajas configuradas en el AdminCP, con la lista de premios normalizada. */
    public static function boxes() {
        $file = __PATH_INCLUDES__ . 'config/casino_boxes.json';
        $data = file_exists($file) ? json_decode((string)@file_get_contents($file), true) : null;
        if(!is_array($data)) return array();
        $out = array();
        foreach($data as $b) {
            if(!is_array($b) || !isset($b['id'])) continue;
            $b['rewards'] = (isset($b['rewards']) && is_array($b['rewards'])) ? array_values($b['rewards']) : array();
            $out[] = $b;
        }
        return $out;
    }

    /** Tabla de monedas y columna de usuario, segun la configuracion del AdminCP. */
    private static function coinSource() {
        $file = __PATH_INCLUDES__ . 'config/custom_settings.json';
        $settings = file_exists($file) ? json_decode((string)@file_get_contents($file), true) : array();
        if(!is_array($settings)) $settings = array();

        $table = self::ident(isset($settings['table_coins']) ? $settings['table_coins'] : '', 'CashShopData');
        $isMemb = in_array(strtolower($table), array('memb_info', 'memb_stat'), true);
        $userCol = self::ident(isset($settings['col_user']) ? $settings['col_user'] : '', $isMemb ? 'memb___id' : 'AccountID');
        if($isMemb && strtolower($userCol) == 'accountid') $userCol = 'memb___id';
        return array($table, $userCol);
    }

    /** Arma los 16 bytes del item (32 caracteres hex), mismo formato que usaba el modulo. */
    private static function buildItemHex($r) {
        $id    = (int)(isset($r['item_id']) ? $r['item_id'] : 0) & 255;
        $group = self::num(isset($r['group']) ? $r['group'] : 0, 15);
        $level = self::num(isset($r['level']) ? $r['level'] : 0, 15);
        $opt   = self::num(isset($r['option']) ? $r['option'] : 0, 7);

        $exc = 0;
        if(isset($r['exc_opts']) && is_array($r['exc_opts'])) {
            foreach($r['exc_opts'] as $v) $exc |= ((int)$v & 63);
        }

        $byte1 = ($opt & 3) | ($level << 3);
        if(!empty($r['skill'])) $byte1 |= 128;
        if(!empty($r['luck']))  $byte1 |= 4;
        if($opt > 3) $exc |= 64;

        $byte9 = ($group << 4) & 0xF0;
        if(!empty($r['opt_380'])) $byte9 |= 8;

        $harmony = 0;
        if(isset($r['harmony_type']) && (int)$r['harmony_type'] > 0) {
            $harmony = ((self::num($r['harmony_type'], 15) << 4) | self::num(isset($r['harmony_level']) ? $r['harmony_level'] : 0, 15)) & 255;
        }

        $hex = sprintf('%02X', $id) . sprintf('%02X', $byte1 & 255) . 'FF' . '00000000'
             . sprintf('%02X', $exc & 255) . '00' . sprintf('%02X', $byte9) . sprintf('%02X', $harmony);
        for($k = 0; $k < 5; $k++) {
            $s = isset($r['sockets'][$k]) ? $r['sockets'][$k] : 255;
            if(is_array($s)) $s = isset($s['val']) ? $s['val'] : 255;
            $hex .= sprintf('%02X', self::num($s));
        }
        return $hex;
    }

    /** Tooltip con las opciones exactas cargadas en el AdminCP. */
    private static function tooltip($r) {
        $h = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
        $group = self::num(isset($r['group']) ? $r['group'] : 0, 15);
        $id    = (int)(isset($r['item_id']) ? $r['item_id'] : 0) & 255;
        $level = self::num(isset($r['level']) ? $r['level'] : 0, 15);
        $opt   = self::num(isset($r['option']) ? $r['option'] : 0, 7);

        $exc = array();
        if(isset($r['exc_opts']) && is_array($r['exc_opts'])) {
            foreach($r['exc_opts'] as $v) $exc[(int)$v] = true;
        }

        $out = '<div class="cg-tt-name' . (!empty($exc) ? ' is-exc' : '') . '">'
             . $h(isset($r['name']) ? $r['name'] : 'Item') . ($level > 0 ? ' +' . $level : '') . '</div>';

        // 380
        if(!empty($r['opt_380'])) {
            $names = array();
            if(!class_exists('ItemsManager')) @include_once(__PATH_INCLUDES__ . 'config/items_manager.php');
            if(class_exists('ItemsManager') && method_exists('ItemsManager', 'get380Options')) {
                $n = ItemsManager::get380Options($group, $id);
                if(is_array($n)) foreach($n as $s) { if(is_string($s) && $s !== '') $names[] = $s; }
            }
            if(empty($names)) $names[] = '380 Option';
            foreach($names as $s) $out .= '<div class="cg-tt-380">' . $h($s) . '</div>';
        }

        // Harmony
        $hType = isset($r['harmony_type']) ? (int)$r['harmony_type'] : 0;
        if($hType > 0) {
            $hCat = ($group <= 4) ? 1 : (($group == 5) ? 5 : (($group >= 6 && $group <= 11) ? 6 : 0));
            $hNames = array(
                1 => array(1 => 'Min. attack increase', 2 => 'Max. attack increase', 3 => 'Required strength decrease', 4 => 'Required agility decrease', 5 => 'Attack increase (Min,Max)', 6 => 'Critical damage increase', 7 => 'Skill attack increase', 8 => 'Attack success rate increase (PVP)', 9 => 'SD decrement increase', 10 => 'SD bypass rate increase'),
                5 => array(1 => 'Wizardry increase', 2 => 'Required strength decrease', 3 => 'Required agility decrease', 4 => 'Skill attack increase', 5 => 'Critical damage increase', 6 => 'SD decrement increase', 7 => 'Attack success rate increase (PVP)', 8 => 'SD bypass rate increase'),
                6 => array(1 => 'Defense increase', 2 => 'Max. AG increase', 3 => 'Max. HP increase', 4 => 'Life auto increment increase', 5 => 'Mana auto increment increase', 6 => 'Defense success rate increase (PVP)', 7 => 'Damage decrement increase', 8 => 'SD ratio increase')
            );
            if(isset($hNames[$hCat][$hType])) {
                $hLvl = isset($r['harmony_level']) ? (int)$r['harmony_level'] : 0;
                $out .= '<div class="cg-tt-harm">' . $hNames[$hCat][$hType] . ' (Lvl ' . $hLvl . ')</div>';
            }
        }

        if(!empty($r['skill'])) $out .= '<div class="cg-tt-opt">Skill Attack Power</div>';
        if(!empty($r['luck']))  $out .= '<div class="cg-tt-opt">Luck (success rate +25%)</div><div class="cg-tt-opt">Luck (critical damage rate +5%)</div>';
        if($opt > 0)            $out .= '<div class="cg-tt-opt">Additional Option +' . ($opt * 4) . '</div>';

        // Excellent
        $type = ($group < 6) ? 'ATTACK' : (($group == 12) ? 'WINGS' : 'DEFENSE');
        $excNames = array(
            'ATTACK'  => array(32 => 'Excellent Damage Rate +10%', 16 => 'Increase Damage +level/20', 8 => 'Increase Damage +2%', 4 => 'Attack Speed +7', 2 => 'Increase Life after monster +life/8', 1 => 'Increase Mana after monster +mana/8'),
            'DEFENSE' => array(32 => 'Increase Max HP +4%', 16 => 'Increase Max Mana +4%', 8 => 'Damage Decrease +4%', 4 => 'Reflect Damage +5%', 2 => 'Defense Success Rate +10%', 1 => 'Zen after hunting monsters +40%'),
            'WINGS'   => array(8 => 'Complete recovery of Mana in 5% rate', 4 => 'Complete recovery of HP in 5% rate', 2 => 'Return the enemy attack power by 5%', 1 => 'Ignore opponent defensive power by 5%')
        );
        foreach(array(32, 16, 8, 4, 2, 1) as $bit) {
            if(isset($exc[$bit]) && isset($excNames[$type][$bit])) $out .= '<div class="cg-tt-exc">' . $excNames[$type][$bit] . '</div>';
        }

        // Sockets
        $seeds = array(0 => 'Fire (Level based Dmg)', 1 => 'Fire (Attack Speed)', 2 => 'Fire (Max Atk/Wiz)', 3 => 'Fire (Min Atk/Wiz)', 4 => 'Fire (Atk/Wiz)', 5 => 'Fire (AG Cost Dec)',
            10 => 'Water (Block Rate)', 11 => 'Water (Defense)', 12 => 'Water (Shield Prot)', 13 => 'Water (Dmg Reduct)', 14 => 'Water (Reflect)',
            16 => 'Ice (Mob Kill HP)', 17 => 'Ice (Mob Kill MP)', 18 => 'Ice (Skill Atk)', 19 => 'Ice (Attack Rate)', 20 => 'Ice (Durability)',
            21 => 'Wind (Auto Life Rec)', 22 => 'Wind (Max Life)', 23 => 'Wind (Max Mana)', 24 => 'Wind (Auto Mana Rec)', 25 => 'Wind (Max AG)', 26 => 'Wind (AG Value)',
            29 => 'Light (Exc Dmg)', 30 => 'Light (Exc Rate)', 31 => 'Light (Crit Dmg)', 32 => 'Light (Crit Rate)', 36 => 'Earth (Health)');
        $sock = '';
        for($k = 0; $k < 5; $k++) {
            $s = isset($r['sockets'][$k]) ? $r['sockets'][$k] : 255;
            if(is_array($s)) $s = isset($s['val']) ? $s['val'] : 255;
            $s = self::num($s);
            if($s == 255) continue;
            if($s == 254) { $sock .= '<div class="cg-tt-sock">Socket ' . ($k + 1) . ': Empty</div>'; continue; }
            $sType = $s % 50;
            $sLvl  = (int)floor($s / 50) + 1;
            $label = isset($seeds[$sType]) ? $seeds[$sType] : 'Seed';
            $sock .= '<div class="cg-tt-sock">Socket ' . ($k + 1) . ': ' . $label . ' Lvl ' . $sLvl . '</div>';
        }
        if($sock !== '') $out .= '<div class="cg-tt-sep"></div>' . $sock;

        return $out;
    }

    /** Datos de un premio para mostrar en pantalla (sin probabilidades). */
    private static function display($r) {
        return array(
            'id'      => isset($r['id']) ? (string)$r['id'] : '',
            'name'    => isset($r['name']) ? (string)$r['name'] : 'Item',
            'group'   => self::num(isset($r['group']) ? $r['group'] : 0, 15),
            'item_id' => (int)(isset($r['item_id']) ? $r['item_id'] : 0) & 255,
            'level'   => self::num(isset($r['level']) ? $r['level'] : 0, 15),
            'exc'     => !empty($r['exc_opts']),
            'tip'     => self::tooltip($r)
        );
    }

    /**
     * Abre una caja: cobra y deja el premio guardado como pendiente, todo o nada.
     */
    public static function spin($username, $boxId) {
        if(!check_value($username)) return self::fail("Debes iniciar sesi\u{00F3}n para jugar.");
        if(!is_string($boxId) || $boxId === '' || strlen($boxId) > 64) return self::fail('Esta caja ya no existe.');
        if(!class_exists('BlackMarket')) return self::fail("Configuraci\u{00F3}n inv\u{00E1}lida.");

        // 1. Caja (se lee del servidor; del navegador solo llega el id)
        $box = null;
        foreach(self::boxes() as $b) {
            if((string)$b['id'] === $boxId) { $box = $b; break; }
        }
        if($box === null) return self::fail('Esta caja ya no existe o fue eliminada.');

        $cost = isset($box['cost']) ? (int)$box['cost'] : 0;
        if($cost < 1 || $cost > 2000000000) return self::fail("Esta caja no tiene un costo v\u{00E1}lido.");

        $currency = self::ident(isset($box['currency']) ? $box['currency'] : '');
        if($currency === '') return self::fail('Moneda no configurada.');

        // 2. Sorteo proporcional a la probabilidad cargada en cada premio
        $rewards = array();
        $total = 0;
        foreach($box['rewards'] as $r) {
            if(!is_array($r) || !isset($r['group']) || !isset($r['item_id'])) continue;
            $w = (int)round((isset($r['chance']) ? (float)$r['chance'] : 0) * 100);
            if($w < 1) continue;
            $r['_w'] = $w;
            $total += $w;
            $rewards[] = $r;
        }
        if(empty($rewards) || $total < 1) return self::fail("Esta caja est\u{00E1} vac\u{00ED}a temporalmente.");

        try { $roll = random_int(1, $total); } catch (Throwable $e) { $roll = mt_rand(1, $total); }
        $winner = $rewards[count($rewards) - 1];
        $acc = 0;
        foreach($rewards as $r) {
            $acc += $r['_w'];
            if($roll <= $acc) { $winner = $r; break; }
        }

        // 3. Conexion y controles previos
        $db = Connection::Database('Me_MuOnline');
        if(!is_object($db)) return self::fail("Sin conexi\u{00F3}n con la base de datos.");

        if(BlackMarket::isOnline($db, $username)) {
            return self::fail("<b>\u{00A1}CUENTA CONECTADA!</b><br>Debes desconectarte del juego para abrir cofres.");
        }

        $pdo = BlackMarket::pdoFrom($db);
        if($pdo === null) return self::fail("Sin conexi\u{00F3}n con la base de datos.");

        list($coinTable, $userCol) = self::coinSource();

        $run = function($sql, $params = array(), $fetch = false) use ($pdo) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $fetch ? $stmt->fetchAll(PDO::FETCH_ASSOC) : true;
        };

        $show     = self::display($winner);
        $itemHex  = self::buildItemHex($winner);
        $boxName  = self::cut(isset($box['name']) ? $box['name'] : 'Caja', 60);
        $prize    = self::cut($show['name'], 60);
        $pendingId = 0;

        // 4. Transaccion: se cobra y se guarda el premio, o no se hace nada
        try {
            $pdo->beginTransaction();

            $bal = $run("SELECT [$currency] AS saldo FROM [$coinTable] WITH (UPDLOCK, ROWLOCK) WHERE [$userCol] = ?", array($username), true);
            if(count($bal) !== 1) throw new Exception("No se encontr\u{00F3} tu cuenta en la tabla de monedas.");
            if((int)$bal[0]['saldo'] < $cost) throw new Exception("No tienes suficientes <b>$currency</b> para abrir esta caja.");

            $run("UPDATE [$coinTable] SET [$currency] = [$currency] - ? WHERE [$userCol] = ?", array($cost, $username));

            $ins = $run(
                "INSERT INTO " . self::TABLE_PENDING . " (account_id, box_name, prize_name, item_hex, item_group, item_index, item_level) OUTPUT INSERTED.id VALUES (?, ?, ?, ?, ?, ?, ?)",
                array($username, $boxName, $prize, $itemHex, $show['group'], $show['item_id'], $show['level']),
                true
            );
            if(count($ins) !== 1) throw new Exception('No se pudo registrar el premio.');
            $pendingId = (int)$ins[0]['id'];

            $pdo->commit();
        } catch (PDOException $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[Casino::spin] ' . $e->getMessage());
            return self::fail("No se pudo abrir la caja. No se realiz\u{00F3} ning\u{00FA}n cobro.");
        } catch (Exception $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            return self::fail($e->getMessage());
        } catch (Throwable $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[Casino::spin] ' . $e->getMessage());
            return self::fail('Error interno. No se realiz' . "\u{00F3}" . ' ning' . "\u{00FA}" . 'n cobro.');
        }

        // 5. Registro (fuera de la transaccion)
        $db->query(
            "INSERT INTO MUSERVERMAKER_CASINO_LOG (account_id, box_name, cost_amount, currency_type, prize_name) VALUES (?, ?, ?, ?, ?)",
            array($username, $boxName, $cost, $currency, $prize)
        );

        $pool = array();
        foreach($rewards as $r) $pool[] = self::display($r);

        return array('status' => 'success', 'pending_id' => $pendingId, 'winner' => $show, 'pool' => $pool);
    }

    /** Premios ganados y todavia no retirados. */
    public static function pending($username) {
        if(!check_value($username)) return array();
        $db = Connection::Database('Me_MuOnline');
        if(!is_object($db)) return array();

        $rows = $db->query_fetch(
            "SELECT TOP 50 id, box_name, prize_name, item_group, item_index, item_level FROM " . self::TABLE_PENDING . " WHERE account_id = ? ORDER BY id DESC",
            array($username)
        );
        if(!is_array($rows)) return array();

        $out = array();
        foreach($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $out[] = array(
                'id'    => (int)$row['id'],
                'box'   => (string)$row['box_name'],
                'name'  => (string)$row['prize_name'],
                'group' => (int)$row['item_group'],
                'index' => (int)$row['item_index'],
                'level' => (int)$row['item_level']
            );
        }
        return $out;
    }

    /**
     * Envia un premio pendiente al baul. Si algo falla, el premio sigue pendiente.
     */
    public static function claim($username, $pendingId) {
        if(!check_value($username)) return self::fail("Tu sesi\u{00F3}n ha expirado.");
        $id = (int)$pendingId;
        if($id <= 0) return self::fail('Premio no encontrado.');
        if(!class_exists('BlackMarket')) return self::fail("Configuraci\u{00F3}n inv\u{00E1}lida.");

        $db = Connection::Database('Me_MuOnline');
        if(!is_object($db)) return self::fail("Sin conexi\u{00F3}n con la base de datos.");

        if(BlackMarket::isOnline($db, $username)) {
            return self::fail("<b>\u{00A1}CUENTA CONECTADA!</b><br>Debes desconectarte del juego para retirar el premio.");
        }

        $dbName = BlackMarket::dbName();
        if($dbName === null) return self::fail("Configuraci\u{00F3}n inv\u{00E1}lida.");

        $pdo = BlackMarket::pdoFrom($db);
        if($pdo === null) return self::fail("Sin conexi\u{00F3}n con la base de datos.");

        $run = function($sql, $params = array(), $fetch = false) use ($pdo) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $fetch ? $stmt->fetchAll(PDO::FETCH_ASSOC) : true;
        };

        try {
            $pdo->beginTransaction();

            // 1. Reclamar el premio: debe ser del usuario y solo un pedido puede obtenerlo
            $claimed = $run("DELETE FROM " . self::TABLE_PENDING . " OUTPUT DELETED.id, DELETED.item_hex WHERE id = ? AND account_id = ?", array($id, $username), true);
            if(count($claimed) !== 1) throw new Exception('Premio no encontrado o ya retirado.');
            $itemHex = strtoupper(trim((string)$claimed[0]['item_hex']));

            // 2. Baul vacio (solo la zona visible), bloqueado hasta el final
            $wh = $run(BlackMarket::vaultSql($dbName), array($username), true);
            if(count($wh) !== 1) throw new Exception("Ba\u{00FA}l no inicializado. Entr\u{00E1} al juego y abr\u{00ED} el ba\u{00FA}l una vez.");
            if((int)$wh[0]['tiene_items'] === 1) throw new Exception("<b>\u{00A1}BA\u{00DA}L OCUPADO!</b><br>Vac\u{00ED}a tu ba\u{00FA}l para recibir el premio. Sigue guardado en tu lista.");

            // 3. El item va al primer casillero; el resto del baul no se toca
            $put = BlackMarket::vaultPut($dbName, $itemHex, (int)$wh[0]['bytes']);
            $run($put['sql'], array($put['hex'], $username));

            $pdo->commit();
        } catch (PDOException $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[Casino::claim] ' . $e->getMessage());
            return self::fail("No se pudo entregar el premio. Sigue guardado en tu lista.");
        } catch (Exception $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            return self::fail($e->getMessage());
        } catch (Throwable $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[Casino::claim] ' . $e->getMessage());
            return self::fail('Error interno. El premio sigue guardado en tu lista.');
        }

        return array('status' => 'success');
    }
}