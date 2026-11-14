<?php
/**
 * MuServerMaker CMS - Black Market
 * Motor central del mercado entre jugadores: listar, comprar, publicar y retirar.
 *
 * IMPORTANTE: este archivo es ASCII puro a proposito. Los acentos de los mensajes
 * se escriben como \u{XXXX} para que json_encode() nunca falle por la codificacion
 * con la que se guarde el archivo.
 */

class BlackMarket {

    const ITEM_BYTES = 16;      // bytes que ocupa un item dentro del baul
    const VISIBLE_SLOTS = 120;  // casilleros que muestra el UserCP (8 x 15)

    /* ====================================================================
     * HELPERS INTERNOS
     * ==================================================================== */

    private static function config() {
        static $cfg = null;
        if($cfg === null) {
            $file = __PATH_INCLUDES__ . 'config/black_market_config.json';
            $data = file_exists($file) ? json_decode((string)@file_get_contents($file), true) : null;
            $cfg = is_array($data) ? $data : array();
        }
        return $cfg;
    }

    private static function taxPercent() {
        $cfg = self::config();
        $tax = isset($cfg['tax_percent']) ? (int)$cfg['tax_percent'] : 10;
        if($tax < 0) $tax = 0;
        if($tax > 100) $tax = 100;
        return $tax;
    }

    public static function dbName() {
        $serverData = muservermakerConfigs();
        $dbName = isset($serverData['SQL_DB_NAME']) ? $serverData['SQL_DB_NAME'] : 'MuOnline';
        // Lista blanca: solo caracteres validos de identificador
        return preg_match('/^[A-Za-z0-9_]+$/', (string)$dbName) ? $dbName : null;
    }

    public static function pdoFrom($db) {
        try {
            $ref = new ReflectionClass($db);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $pdo = $prop->getValue($db);
        } catch (Throwable $e) { $pdo = null; }
        return ($pdo instanceof PDO) ? $pdo : null;
    }

    public static function isOnline($db, $username) {
        $online = $db->query_fetch_single("SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = ? AND ConnectStat = 1", array($username));
        return is_array($online);
    }

    private static function itemName($group, $id) {
        if(!class_exists('ItemsManager')) {
            $file = __PATH_INCLUDES__ . 'config/items_manager.php';
            if(file_exists($file)) include_once($file);
        }
        if(class_exists('ItemsManager') && method_exists('ItemsManager', 'getItemName')) {
            return ItemsManager::getItemName($group, $id);
        }
        return "Item ($group-$id)";
    }

    public static function vaultSql($dbName) {
        // Solo se evalua la zona visible del baul (120 casilleros). Lo que el
        // servidor del juego guarde mas alla no se mira ni se modifica.
        $vis = self::VISIBLE_SLOTS * self::ITEM_BYTES;
        return "SELECT DATALENGTH(Items) AS bytes,
                       CASE
                           WHEN Items IS NULL OR DATALENGTH(Items) = 0 THEN 0
                           WHEN SUBSTRING(Items, 1, $vis) = REPLICATE(CONVERT(varbinary(max), 0xFF), CASE WHEN DATALENGTH(Items) < $vis THEN DATALENGTH(Items) ELSE $vis END) THEN 0
                           WHEN SUBSTRING(Items, 1, $vis) = REPLICATE(CONVERT(varbinary(max), 0x00), CASE WHEN DATALENGTH(Items) < $vis THEN DATALENGTH(Items) ELSE $vis END) THEN 0
                           ELSE 1
                       END AS tiene_items
                FROM [$dbName].[dbo].[warehouse] WITH (UPDLOCK, ROWLOCK) WHERE AccountId = ?";
    }

    /**
     * Arma la escritura del item en el PRIMER casillero del baul.
     * El resto del baul queda como estaba.
     */
    public static function vaultPut($dbName, $targetHex, $whBytes) {
        $itemHexLen = self::ITEM_BYTES * 2;
        if(!ctype_xdigit($targetHex) || strlen($targetHex) !== $itemHexLen) throw new Exception("Item inv\u{00E1}lido.");

        if($whBytes < self::ITEM_BYTES) {
            // Baul sin datos (NULL o largo 0): se crea completo
            return array(
                'sql' => "UPDATE [$dbName].[dbo].[warehouse] SET Items = CONVERT(varbinary(max), ?, 2) WHERE AccountId = ?",
                'hex' => $targetHex . str_repeat('F', (3840 * 2) - $itemHexLen)
            );
        }

        $next = self::ITEM_BYTES + 1; // primer byte posterior al primer casillero
        return array(
            'sql' => "UPDATE [$dbName].[dbo].[warehouse] SET Items = CONVERT(varbinary(16), ?, 2) + SUBSTRING(Items, $next, DATALENGTH(Items)) WHERE AccountId = ?",
            'hex' => $targetHex
        );
    }

    /* ====================================================================
     * PRESENTACION
     * ==================================================================== */

    /**
     * Convierte el hex de un item en el tooltip HTML.
     */
    public static function parseItemIndex($hex) {
        $id = hexdec(substr($hex, 0, 2));
        $byte1 = hexdec(substr($hex, 2, 2));
        $byte7 = hexdec(substr($hex, 14, 2));
        $byte9 = hexdec(substr($hex, 18, 2));
        $byte10 = hexdec(substr($hex, 20, 2));
        $group = ($byte9 >> 4) & 0xF;

        // Deteccion S4 / Socket
        $isSocketItem = false;
        if (($group >= 7 && $group <= 11) && ($id >= 45 && $id <= 65)) $isSocketItem = true;
        if ($group == 0 && $id >= 26) $isSocketItem = true;
        if ($group == 2 && $id >= 16) $isSocketItem = true;
        if ($group == 4 && $id >= 23) $isSocketItem = true;
        if ($group == 5 && $id >= 30) $isSocketItem = true;

        $level = ($byte1 >> 3) & 0xF;
        $skill = ($byte1 >> 7) & 1;
        $luck = ($byte1 >> 2) & 1;

        // Fix Option Calc (PHP)
        $optVal = ($byte1 & 3);
        if (($byte7 & 64) > 0) $optVal += 4;
        $option = $optVal * 4;

        // Excellent Options Real Names
        $exeType = 'DEFENSE';
        if ($group < 6) $exeType = 'ATTACK';
        if ($group == 12) $exeType = 'WINGS';
        if ($group == 13) $exeType = 'DEFENSE';

        $excOpts = [];
        $bits = [32, 16, 8, 4, 2, 1];
        foreach ($bits as $bit) {
            if ($byte7 & $bit) {
                if ($exeType == 'ATTACK') {
                    if($bit==32) $excOpts[] = "Excellent Damage Rate +10%";
                    if($bit==16) $excOpts[] = "Increase Damage +level/20";
                    if($bit==8)  $excOpts[] = "Increase Damage +2%";
                    if($bit==4)  $excOpts[] = "Attack Speed +7";
                    if($bit==2)  $excOpts[] = "Increase Life after monster +life/8";
                    if($bit==1)  $excOpts[] = "Increase Mana after monster +mana/8";
                } elseif ($exeType == 'DEFENSE') {
                    if($bit==32) $excOpts[] = "Increase Max HP +4%";
                    if($bit==16) $excOpts[] = "Increase Max Mana +4%";
                    if($bit==8)  $excOpts[] = "Damage Decrease +4%";
                    if($bit==4)  $excOpts[] = "Reflect Damage +5%";
                    if($bit==2)  $excOpts[] = "Defense Success Rate +10%";
                    if($bit==1)  $excOpts[] = "Increases acquisition rate of Zen after hunting monsters +30%";
                } elseif ($exeType == 'WINGS') {
                    if($bit==1)  $excOpts[] = "Ignore opponent's defensive power by 5%";
                    if($bit==2)  $excOpts[] = "Return's the enemy's attack power by 5%";
                    if($bit==4)  $excOpts[] = "Complete recovery of HP in 5% rate";
                    if($bit==8)  $excOpts[] = "Complete recovery of Mana in 5% rate";
                }
            }
        }

        // 380 Option (pendiente: validar contra la lista blanca de ItemsManager)
        $is380 = false;
        $op380Names = [];
        if (!$isSocketItem && $group != 13) {
            $is380 = (($byte9 & 16) > 0) || (($byte9 & 8) > 0);
            if ($is380 && class_exists('ItemsManager') && method_exists('ItemsManager', 'get380Options')) {
                $op380Names = ItemsManager::get380Options($group, $id);
            }
        }

        $name = ItemsManager::getItemName($group, $id);
        $isExc = count($excOpts) > 0;

        $html = "<div class='tt-box'>";

        // Nombre (+nivel oculto si es 0)
        $lvlStr = ($level > 0) ? " +".$level : "";
        $nameColor = $isExc ? "tt-exc" : "tt-name";
        $html .= "<span class='$nameColor' style='display:block; font-weight:bold; font-size:13px; margin-bottom:5px; border-bottom:1px solid #444; padding-bottom:5px; color:".($isExc?"#2ecc71":"#fff")."'>$name$lvlStr</span>";

        // 380
        if($is380) {
            if(!empty($op380Names)) {
                foreach($op380Names as $opName) {
                    $html .= "<div class='tt-380' style='color:#d000d0; font-weight:bold;'>$opName</div>";
                }
            } else {
                $html .= "<div class='tt-380' style='color:#d000d0; font-weight:bold;'>380 Option Active</div>";
            }
        }

        // Harmony
        if (!$isSocketItem && $byte10 > 0) {
            $hType = 1;
            if ($group == 5) $hType = 5;
            if ($group >= 6 && $group <= 11) $hType = 6;

            $optId = ($byte10 >> 4) & 0xF;
            $optLvl = ($byte10 & 0xF);

            $hNames = [
                1 => [1 => "Min. attack increase", 2 => "Max. attack increase", 3 => "Required strength decrease", 4 => "Required agility decrease", 5 => "Attack increase (Min,Max)", 6 => "Critical damage increase", 7 => "Skill attack increase", 8 => "Attack success rate increase (PVP)", 9 => "SD decrement increase", 10 => "SD bypass rate increase"],
                5 => [1 => "Wizardry increase", 2 => "Required strength decrease", 3 => "Required agility decrease", 4 => "Skill attack increase", 5 => "Critical damage increase", 6 => "SD decrement increase", 7 => "Attack success rate increase (PVP)", 8 => "SD bypass rate increase"],
                6 => [1 => "Defense increase", 2 => "Max. AG increase", 3 => "Max. HP increase", 4 => "Life auto increment increase", 5 => "Mana auto increment increase", 6 => "Defense success rate increase (PVP)", 7 => "Damage decrement increase", 8 => "SD ratio increase"]
            ];

            if (isset($hNames[$hType][$optId])) {
                $hName = $hNames[$hType][$optId];
                $html .= "<div style='color:#ffff00'>$hName (Lvl $optLvl)</div>";
            }
        }

        // Skill
        if($skill) $html .= "<div class='tt-opt' style='color:#9aadcd'>Skill Attack Power</div>";

        // Luck
        if($luck) $html .= "<div class='tt-luck' style='color:#9aadcd'>Luck (success rate +25%)</div><div class='tt-luck' style='color:#9aadcd'>Luck (critical damage rate +5%)</div>";

        // Add Option
        if($option>0) $html .= "<div class='tt-opt' style='color:#9aadcd'>Additional Option +$option</div>";

        // Excellent
        foreach($excOpts as $ex) $html .= "<div class='tt-exc' style='color:#2ecc71'>$ex</div>";

        // Sockets
        $socketsHTML = "";
        $hasSockets = false;
        $elements = [0=>'Fire', 1=>'Water', 2=>'Ice', 3=>'Wind', 4=>'Earth', 5=>'Lightning'];
        $socketNames = [0=>"Attack/Wizardry increase", 1=>"Attack Speed increase", 2=>"Max Attack/Wizardry increase", 3=>"Min Attack/Wizardry increase", 4=>"Attack/Wizardry increase", 5=>"AG consumption decrease", 10=>"Block Rating increase", 11=>"Defense increase", 12=>"Shield Protection increase", 13=>"Damage Reduction", 14=>"Damage Reflection", 16=>"Monster Die Life recovery", 17=>"Monster Die Mana recovery", 18=>"Skill attack power increase", 19=>"Attack Accuracy increase", 20=>"Item durability increase", 21=>"Auto Life recovery increase", 22=>"Max Life increase", 23=>"Max Mana increase", 24=>"Auto Mana recovery increase", 25=>"Max AG increase", 26=>"AG value increase", 29=>"Excellent Damage increase", 30=>"Excellent Damage Rate increase", 31=>"Critical Damage increase", 32=>"Critical Damage Rate increase", 34=>"Strength increase", 35=>"Dexterity increase", 36=>"Health increase", 37=>"Energy increase"];

        for($k=0; $k<5; $k++) {
            $idx = 22 + ($k*2);
            if(strlen($hex) >= $idx+2) {
                $sockVal = hexdec(substr($hex, $idx, 2));
                if($sockVal != 255 && $sockVal != 0) {
                    $hasSockets = true;
                    if($sockVal == 254) {
                        $socketsHTML .= "<div class='tt-opt' style='color:#666'>Socket ".($k+1).": Vac\u{00ED}o</div>";
                    } else {
                        $seedType = floor($sockVal / 50);
                        $seedId = $sockVal % 50;
                        $sLvl = floor($sockVal / 50) + 1;

                        $elName = isset($elements[$seedType]) ? $elements[$seedType] : 'Seed';
                        $optName = isset($socketNames[$seedId]) ? $socketNames[$seedId] : 'Unknown';
                        $socketsHTML .= "<div class='tt-opt' style='color:#cca4e3'>Socket ".($k+1).": $elName ($optName) +$sLvl</div>";
                    }
                }
            }
        }
        if($hasSockets) {
            $html .= "<div style='margin-top:5px; border-top:1px solid #444; padding-top:2px;'>$socketsHTML</div>";
        }

        $html .= "</div>";
        return $html;
    }

    /**
     * Lista los items del mercado, filtrados por categoria (group). -1 = todas.
     */
    public static function getMarketItems($categoria) {
        $db = Connection::Database('Me_MuOnline');
        $cat = (int)$categoria;

        $items = $db->query_fetch("SELECT TOP 200 id, seller_account, CONVERT(varchar(max), item_hex, 2) as item_hex, item_name, price, currency, date_posted FROM MUSERVERMAKER_MARKET_SELL ORDER BY date_posted DESC");
        if(!is_array($items)) return array();

        $filtered = array();
        foreach($items as $item) {
            $hex = $item['item_hex'];
            $id = hexdec(substr($hex, 0, 2));
            $byte9 = hexdec(substr($hex, 18, 2));
            $group = ($byte9 >> 4) & 0xF;

            if($cat == -1 || $group == $cat) {
                $item = array_change_key_case($item, CASE_LOWER);
                $item['img'] = __BASE_URL__ . "img/items/{$group}-{$id}.gif";
                $item['tooltip'] = self::parseItemIndex($hex);
                $item['clean_name'] = ItemsManager::getItemName($group, $id);
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    /* ====================================================================
     * COMPRA
     * ==================================================================== */

    /**
     * Procesa la compra de un item. Devuelve array con status/message.
     * Consultas parametrizadas + transaccion: se hace todo o no se hace nada.
     */
    public static function buyItem($username, $itemId) {
        if(!check_value($username)) return array('status' => 'error', 'message' => "Sesi\u{00F3}n inv\u{00E1}lida.");

        $db = Connection::Database('Me_MuOnline');

        // La cuenta debe estar DESCONECTADA (evita dup de items/creditos)
        if(self::isOnline($db, $username)) {
            return array('status' => 'error', 'message' => 'Debes DESCONECTAR tu cuenta del juego para operar en el mercado.');
        }

        $dbName = self::dbName();
        if($dbName === null) return array('status' => 'error', 'message' => "Configuraci\u{00F3}n inv\u{00E1}lida.");

        $id = (int)$itemId;

        // 1. Traer el item publicado (parametrizado)
        $itemData = $db->query_fetch_single("SELECT id, seller_account, CONVERT(varchar(max), item_hex, 2) as item_hex, item_name, price, currency FROM MUSERVERMAKER_MARKET_SELL WHERE id = ?", array($id));
        if(!is_array($itemData)) return array('status' => 'error', 'message' => 'Item no disponible.');
        if($itemData['seller_account'] == $username) return array('status' => 'error', 'message' => 'No puedes auto-comprarte.');

        $price = (int)$itemData['price'];
        $seller = $itemData['seller_account'];
        $currency = $itemData['currency'];

        // 2. Moneda: lista blanca de tabla/columna/columna-usuario (NO vienen del usuario)
        if($currency == 'Credits') {
            $tableCoin = 'MEMB_INFO'; $colCoin = 'Credits'; $colUser = 'memb___id';
        } else {
            $tableCoin = 'CashShopData'; $colCoin = 'WCoinC'; $colUser = 'AccountID';
        }

        // 3. Conexion PDO directa: necesaria para la transaccion y para detectar errores SQL
        $pdo = self::pdoFrom($db);
        if($pdo === null) return array('status' => 'error', 'message' => "Sin conexi\u{00F3}n con la base de datos.");

        $run = function($sql, $params = array(), $fetch = false) use ($pdo) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $fetch ? $stmt->fetchAll(PDO::FETCH_ASSOC) : true;
        };

        // Lo que recibe el vendedor: precio menos el impuesto configurado en el AdminCP
        $pay = (int)floor($price * (100 - self::taxPercent()) / 100);
        $targetHex = strtoupper((string)$itemData['item_hex']);

        try {
            $pdo->beginTransaction();

            // 4. Reclamar la publicacion: solo UNA compra puede borrarla (anti doble-compra)
            $claimed = $run("DELETE FROM MUSERVERMAKER_MARKET_SELL OUTPUT DELETED.id, DELETED.price WHERE id = ?", array($id), true);
            if(count($claimed) !== 1 || (int)$claimed[0]['price'] !== $price) {
                throw new Exception("Este item ya no est\u{00E1} disponible.");
            }

            // 5. Saldo del comprador, con la fila bloqueada hasta el final de la compra
            $buyer = $run("SELECT $colCoin AS saldo FROM [$dbName].[dbo].[$tableCoin] WITH (UPDLOCK, ROWLOCK) WHERE $colUser = ?", array($username), true);
            if(count($buyer) !== 1 || (int)$buyer[0]['saldo'] < $price) {
                throw new Exception('Saldo insuficiente.');
            }

            // 6. Baul vacio: comparacion binaria en SQL Server, sin traer el campo grande
            $wh = $run(self::vaultSql($dbName), array($username), true);
            if(count($wh) !== 1) throw new Exception("No se encontr\u{00F3} tu ba\u{00FA}l.");
            if((int)$wh[0]['tiene_items'] === 1) throw new Exception("Tu ba\u{00FA}l debe estar VAC\u{00CD}O.");

            // El item se escribe solo en el primer casillero; el resto del baul no se toca
            $put = self::vaultPut($dbName, $targetHex, (int)$wh[0]['bytes']);
            // 7. Movimientos: si cualquiera falla, se deshace todo
            $run("UPDATE [$dbName].[dbo].[$tableCoin] SET $colCoin = $colCoin - ? WHERE $colUser = ?", array($price, $username));
            $run("UPDATE [$dbName].[dbo].[$tableCoin] SET $colCoin = $colCoin + ? WHERE $colUser = ?", array($pay, $seller));
            $run($put['sql'], array($put['hex'], $username));

            $pdo->commit();
        } catch (PDOException $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[BlackMarket::buyItem] ' . $e->getMessage());
            return array('status' => 'error', 'message' => "No se pudo completar la compra. No se realiz\u{00F3} ning\u{00FA}n cobro.");
        } catch (Exception $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            return array('status' => 'error', 'message' => $e->getMessage());
        }

        // 8. Log de la compra (fuera de la transaccion)
        $idItem = hexdec(substr($targetHex, 0, 2));
        $group  = (hexdec(substr($targetHex, 18, 2)) >> 4) & 0xF;
        $realName = self::itemName($group, $idItem);
        $db->query("INSERT INTO [$dbName].[dbo].[MUSERVERMAKER_MARKET_BUY] (buyer_account, seller_account, item_hex, item_name, price_paid, currency, transaction_date) VALUES (?, ?, CONVERT(varbinary(max), ?, 2), ?, ?, ?, GETDATE())", array($username, $seller, $targetHex, $realName, $price, $currency));

        return array('status' => 'success');
    }

    /* ====================================================================
     * VENTA: PUBLICAR
     * ==================================================================== */

    /**
     * Publica el item de un casillero del baul. Saca el item del baul y crea
     * la publicacion en la misma transaccion (anti duplicacion).
     */
    public static function publishItem($username, $slot, $price) {
        if(!check_value($username)) return array('status' => 'error', 'message' => "Sesi\u{00F3}n inv\u{00E1}lida.");

        $slot = (int)$slot;
        $price = (int)$price;
        if($price <= 0 || $price > 2000000000) return array('status' => 'error', 'message' => "Precio inv\u{00E1}lido.");
        if($slot < 0 || $slot >= self::VISIBLE_SLOTS) return array('status' => 'error', 'message' => "Casillero inv\u{00E1}lido.");

        $db = Connection::Database('Me_MuOnline');
        if(self::isOnline($db, $username)) {
            return array('status' => 'error', 'message' => "Debes desconectarte del juego para gestionar el ba\u{00FA}l.");
        }

        $dbName = self::dbName();
        if($dbName === null) return array('status' => 'error', 'message' => "Configuraci\u{00F3}n inv\u{00E1}lida.");

        $pdo = self::pdoFrom($db);
        if($pdo === null) return array('status' => 'error', 'message' => "Sin conexi\u{00F3}n con la base de datos.");

        $run = function($sql, $params = array(), $fetch = false) use ($pdo) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $fetch ? $stmt->fetchAll(PDO::FETCH_ASSOC) : true;
        };

        // Moneda configurada en el AdminCP
        $cfg = self::config();
        $currency = isset($cfg['allowed_currency']) ? (string)$cfg['allowed_currency'] : 'Credits';
        if(!preg_match('/^[A-Za-z0-9_]{1,20}$/', $currency)) $currency = 'Credits';

        // Posiciones en bytes dentro del baul (enteros calculados aca, no vienen del usuario)
        $before = $slot * self::ITEM_BYTES;        // bytes anteriores al casillero
        $start  = $before + 1;                     // primer byte del casillero (SQL cuenta desde 1)
        $after  = $before + self::ITEM_BYTES + 1;  // primer byte posterior al casillero

        try {
            $pdo->beginTransaction();

            // 1. Leer SOLO el casillero pedido, con el baul bloqueado hasta el final
            $row = $run("SELECT DATALENGTH(Items) AS bytes, CONVERT(varchar(32), SUBSTRING(Items, $start, 16), 2) AS slot_hex FROM [$dbName].[dbo].[warehouse] WITH (UPDLOCK, ROWLOCK) WHERE AccountId = ?", array($username), true);
            if(count($row) !== 1) throw new Exception("No se encontr\u{00F3} tu ba\u{00FA}l.");

            $bytes = (int)$row[0]['bytes'];
            if($bytes < $before + self::ITEM_BYTES) throw new Exception("Casillero inv\u{00E1}lido.");

            $slotHex = strtoupper((string)$row[0]['slot_hex']);
            if(strlen($slotHex) !== 32 || !ctype_xdigit($slotHex)) throw new Exception("Item vac\u{00ED}o.");
            if(substr($slotHex, 0, 8) === 'FFFFFFFF' || preg_match('/^0+$/', $slotHex)) throw new Exception("Item vac\u{00ED}o.");

            // 2. Datos de la publicacion
            $itemId = hexdec(substr($slotHex, 0, 2));
            $group  = (hexdec(substr($slotHex, 18, 2)) >> 4) & 0xF;
            $name   = self::itemName($group, $itemId);
            $img    = "img/items/{$group}-{$itemId}.gif";

            // 3. Crear la publicacion
            $run("INSERT INTO MUSERVERMAKER_MARKET_SELL (seller_account, item_hex, item_name, item_img, price, currency, date_posted) VALUES (?, CONVERT(varbinary(max), ?, 2), ?, ?, ?, ?, GETDATE())", array($username, $slotHex, $name, $img, $price, $currency));

            // 4. Vaciar el casillero, sin tocar el resto del baul
            $run("UPDATE [$dbName].[dbo].[warehouse] SET Items = SUBSTRING(Items, 1, $before) + 0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF + SUBSTRING(Items, $after, DATALENGTH(Items)) WHERE AccountId = ?", array($username));

            $pdo->commit();
        } catch (PDOException $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[BlackMarket::publishItem] ' . $e->getMessage());
            return array('status' => 'error', 'message' => "No se pudo publicar el item. Tu ba\u{00FA}l no fue modificado.");
        } catch (Exception $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            return array('status' => 'error', 'message' => $e->getMessage());
        } catch (Throwable $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[BlackMarket::publishItem] ' . $e->getMessage());
            return array('status' => 'error', 'message' => 'Error interno.');
        }

        return array('status' => 'success');
    }

    /* ====================================================================
     * VENTA: RETIRAR
     * ==================================================================== */

    /**
     * Devuelve al baul una publicacion PROPIA. Exige baul vacio.
     */
    public static function retractItem($username, $itemId) {
        if(!check_value($username)) return array('status' => 'error', 'message' => "Sesi\u{00F3}n inv\u{00E1}lida.");

        $id = (int)$itemId;
        if($id <= 0) return array('status' => 'error', 'message' => 'Item no encontrado.');

        $db = Connection::Database('Me_MuOnline');
        if(self::isOnline($db, $username)) {
            return array('status' => 'error', 'message' => "Debes desconectarte del juego para gestionar el ba\u{00FA}l.");
        }

        $dbName = self::dbName();
        if($dbName === null) return array('status' => 'error', 'message' => "Configuraci\u{00F3}n inv\u{00E1}lida.");

        $pdo = self::pdoFrom($db);
        if($pdo === null) return array('status' => 'error', 'message' => "Sin conexi\u{00F3}n con la base de datos.");

        $run = function($sql, $params = array(), $fetch = false) use ($pdo) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $fetch ? $stmt->fetchAll(PDO::FETCH_ASSOC) : true;
        };

        try {
            $pdo->beginTransaction();

            // 1. Reclamar la publicacion: debe ser del usuario. Si alguien la compra
            //    al mismo tiempo, solo una de las dos operaciones la obtiene.
            $claimed = $run("DELETE FROM MUSERVERMAKER_MARKET_SELL OUTPUT DELETED.id, CONVERT(varchar(max), DELETED.item_hex, 2) AS item_hex WHERE id = ? AND seller_account = ?", array($id, $username), true);
            if(count($claimed) !== 1) throw new Exception('Item no encontrado.');

            $targetHex = strtoupper((string)$claimed[0]['item_hex']);

            // 2. Baul vacio, bloqueado hasta el final
            $wh = $run(self::vaultSql($dbName), array($username), true);
            if(count($wh) !== 1) throw new Exception("No se encontr\u{00F3} tu ba\u{00FA}l.");
            if((int)$wh[0]['tiene_items'] === 1) throw new Exception("El ba\u{00FA}l debe estar VAC\u{00CD}O.");

            // 3. Devolver el item al primer casillero; el resto del baul no se toca
            $put = self::vaultPut($dbName, $targetHex, (int)$wh[0]['bytes']);
            $run($put['sql'], array($put['hex'], $username));
            
            $pdo->commit();
        } catch (PDOException $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[BlackMarket::retractItem] ' . $e->getMessage());
            return array('status' => 'error', 'message' => "No se pudo retirar el item. La publicaci\u{00F3}n sigue activa.");
        } catch (Exception $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            return array('status' => 'error', 'message' => $e->getMessage());
        } catch (Throwable $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[BlackMarket::retractItem] ' . $e->getMessage());
            return array('status' => 'error', 'message' => 'Error interno.');
        }

        return array('status' => 'success');
    }
}