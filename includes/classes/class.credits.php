<?php
/**
 * MuServerMaker CMS
 * https://muservermaker.com/
 * * @version 1.0.0
 * @author MuServerMaker - Security Hardened
 * @copyright (c) 2026 MuServerMaker, All Rights Reserved
 * * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

class CreditSystem {
	
	private $_configId;
	private $_identifier;
	
	private $_configTitle;
	private $_configDatabase;
	private $_configTable;
	private $_configCreditsCol;
	private $_configUserCol;
	private $_configUserColId;
	private $_configCheckOnline = true;
	private $_configDisplay = false;
	
	private $_allowedUserColId = array(
		'userid',
		'username',
		'email',
		'character'
	);
	
	protected $muonline;
	protected $memuonline;
	protected $common;
	protected $character;
	
	function __construct() {
		
		// load databases
		$this->muonline = Connection::Database('MuOnline');
		$this->memuonline = Connection::Database('Me_MuOnline');
		
		// instances
		$this->common = new common();
		$this->character = new Character();
	}
	
	public function setIdentifier($input) {
		if(!$this->_configId) throw new Exception(lang('error_66'));
		$config = $this->showConfigs(true);
		
		switch($config['config_user_col_id']) {
			case 'userid':
				$this->_setUserid($input);
				break;
			case 'username':
				$this->_setUsername($input);
				break;
			case 'email':
				$this->_setEmail($input);
				break;
			case 'character':
				$this->_setCharacter($input);
				break;
			default:
				throw new Exception("invalid identifier.");
		}
	}
	
	private function _setUserid($input) {
		if(!Validator::UnsignedNumber($input)) throw new Exception(lang('error_67'));
		$this->_identifier = $input;
	}
	
	private function _setUsername($input) {
		if(!Validator::AlphaNumeric($input)) throw new Exception(lang('error_68'));
		if(!Validator::UsernameLength($input)) throw new Exception(lang('error_69'));
		$this->_identifier = $input;
	}
	
	private function _setEmail($input) {
		if(!Validator::Email($input)) throw new Exception(lang('error_70'));
		$this->_identifier = $input;
	}
	
	private function _setCharacter($input) {
		if(!Validator::AlphaNumeric($input)) throw new Exception(lang('error_71'));
		$this->_identifier = $input;
	}
	
	public function addCredits($input) {
		if(!Validator::UnsignedNumber($input)) throw new Exception(lang('error_72'));
		if(!$this->_configId) throw new Exception(lang('error_66'));
		if(!$this->_identifier) throw new Exception(lang('error_73'));
		
		// get configs
		$config = $this->showConfigs(true);
		
        // [SEGURIDAD] Sanitización
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_table']);
        $colCredits = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_credits_col']);
        $colUser = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_user_col']);

		// check online
		if($config['config_checkonline']) {
			if($this->_isOnline($config['config_user_col_id'])) throw new Exception(lang('error_14'));
		}
		
		// check current credits
		$currentCredits = $this->getCredits();
		
		// new credits
		$newCredits = $input + $currentCredits;
		
		// choose database
		$database = ($config['config_database'] == "MuOnline" ? $this->muonline : $this->memuonline);
		
		// build query safely
		$sql = "UPDATE $table SET $colCredits = ? WHERE $colUser = ?";
		
		// add credits
		$addCredits = $database->query($sql, array($newCredits, $this->_identifier));
		if(!$addCredits) throw new Exception(lang('error_74'));
		
		$this->_addLog($config['config_title'], $input, "add");
	}
	
	public function subtractCredits($input) {
		if(!Validator::UnsignedNumber($input)) throw new Exception(lang('error_75'));
		if(!$this->_configId) throw new Exception(lang('error_66'));
		if(!$this->_identifier) throw new Exception(lang('error_73'));
		
		// get configs
		$config = $this->showConfigs(true);
		
        // [SEGURIDAD] Sanitización
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_table']);
        $colCredits = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_credits_col']);
        $colUser = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_user_col']);

		// check online
		if($config['config_checkonline']) {
			if($this->_isOnline($config['config_user_col_id'])) throw new Exception(lang('error_14'));
		}
		
		// check current credits
		if($this->getCredits() < $input) throw new Exception(lang('error_40',true));
		
		// choose database
		$database = ($config['config_database'] == "MuOnline" ? $this->muonline : $this->memuonline);
		
		// build query safely
		$sql = "UPDATE $table SET $colCredits = $colCredits - ? WHERE $colUser = ?";
		
		// subtract credits
		$subCredits = $database->query($sql, array($input, $this->_identifier));
		if(!$subCredits) throw new Exception(lang('error_76'));
		
		$this->_addLog($config['config_title'], $input, "subtract");
	}
	
	public function setConfigId($input) {
		if(!Validator::UnsignedNumber($input)) throw new Exception(lang('error_77'));
		if(!$this->_configurationExists($input)) throw new Exception(lang('error_77'));
		$this->_configId = $input;
	}
	
	public function setConfigTitle($input) {
		if(!Validator::Chars($input, array('a-z', 'A-Z', '0-9', ' '))) throw new Exception(lang('error_78'));
		$this->_configTitle = $input;
	}
	
	public function setConfigDatabase($input) {
		if(!Validator::Chars($input, array('a-z', 'A-Z', '0-9', '_'))) throw new Exception(lang('error_79'));
		$this->_configDatabase = $input;
	}
	
	public function setConfigTable($input) {
		if(!Validator::Chars($input, array('a-z', 'A-Z', '0-9', '_'))) throw new Exception(lang('error_80'));
		$this->_configTable = $input;
	}
	
	public function setConfigCreditsColumn($input) {
		if(!Validator::Chars($input, array('a-z', 'A-Z', '0-9', '_'))) throw new Exception(lang('error_81'));
		$this->_configCreditsCol = $input;
	}
	
	public function setConfigUserColumn($input) {
		if(!Validator::Chars($input, array('a-z', 'A-Z', '0-9', '_'))) throw new Exception(lang('error_82'));
		$this->_configUserCol = $input;
	}
	
	public function setConfigUserColumnId($input) {
		if(!Validator::AlphaNumeric($input)) throw new Exception(lang('error_83'));
		if(!in_array($input, $this->_allowedUserColId)) throw new Exception(lang('error_83'));
		$this->_configUserColId = $input;
	}
	
	public function setConfigCheckOnline($input) {
		$this->_configCheckOnline = ($input ? 1 : 0);
	}
	
	public function setConfigDisplay($input) {
		$this->_configDisplay = ($input ? 1 : 0);
	}
	
	private function _configurationExists($input) {
		$check = $this->memuonline->query_fetch_single("SELECT * FROM MUSERVERMAKER_CREDITS_CONFIG WHERE config_id = ?", array($input));
		if($check) return true;
		return false;
	}
	
	public function saveConfig() {
		if(!$this->_configTitle) throw new Exception(lang('error_84'));
		if(!$this->_configDatabase) throw new Exception(lang('error_84'));
		if(!$this->_configTable) throw new Exception(lang('error_84'));
		if(!$this->_configCreditsCol) throw new Exception(lang('error_84'));
		if(!$this->_configUserCol) throw new Exception(lang('error_84'));
		if(!$this->_configUserColId) throw new Exception(lang('error_84'));
		
		$data = array(
			'title' => $this->_configTitle,
			'database' => $this->_configDatabase,
			'table' => $this->_configTable,
			'creditscol' => $this->_configCreditsCol,
			'usercol' => $this->_configUserCol,
			'usercolid' => $this->_configUserColId,
			'checkonline' => $this->_configCheckOnline,
			'display' => $this->_configDisplay
		);
		
		$query = "INSERT INTO MUSERVERMAKER_CREDITS_CONFIG "
			. "(config_title, config_database, config_table, config_credits_col, config_user_col, config_user_col_id, config_checkonline, config_display) "
			. "VALUES "
			. "(:title, :database, :table, :creditscol, :usercol, :usercolid, :checkonline, :display)";
		
		$saveConfig = $this->memuonline->query($query, $data);
		if(!$saveConfig) throw new Exception(lang('error_85'));
	}
	
	public function editConfig() {
		if(!$this->_configId) throw new Exception(lang('error_84'));
		if(!$this->_configTitle) throw new Exception(lang('error_84'));
		if(!$this->_configDatabase) throw new Exception(lang('error_84'));
		if(!$this->_configTable) throw new Exception(lang('error_84'));
		if(!$this->_configCreditsCol) throw new Exception(lang('error_84'));
		if(!$this->_configUserCol) throw new Exception(lang('error_84'));
		if(!$this->_configUserColId) throw new Exception(lang('error_84'));
		
		$data = array(
			'id' => $this->_configId,
			'title' => $this->_configTitle,
			'database' => $this->_configDatabase,
			'table' => $this->_configTable,
			'creditscol' => $this->_configCreditsCol,
			'usercol' => $this->_configUserCol,
			'usercolid' => $this->_configUserColId,
			'checkonline' => $this->_configCheckOnline,
			'display' => $this->_configDisplay
		);
		
		$query = "UPDATE MUSERVERMAKER_CREDITS_CONFIG SET "
			. "config_title = :title, "
			. "config_database = :database, "
			. "config_table = :table, "
			. "config_credits_col = :creditscol, "
			. "config_user_col= :usercol, "
			. "config_user_col_id = :usercolid,"
			. "config_checkonline = :checkonline, "
			. "config_display = :display "
			. "WHERE config_id = :id";
		
		$editConfig = $this->memuonline->query($query, $data);
		if(!$editConfig) throw new Exception(lang('error_86'));
	}
	
	public function deleteConfig() {
		if(!$this->_configId) throw new Exception(lang('error_66'));
		if(!$this->memuonline->query("DELETE FROM MUSERVERMAKER_CREDITS_CONFIG WHERE config_id = ?", array($this->_configId))) {
			throw new Exception(lang('error_87'));
		}
	}
	
	public function showConfigs($singleConfig = false) {
		if($singleConfig) {
			if(!$this->_configId) throw new Exception(lang('error_66'));
			return $this->memuonline->query_fetch_single("SELECT * FROM MUSERVERMAKER_CREDITS_CONFIG WHERE config_id = ?", array($this->_configId));
		} else {
			$result = $this->memuonline->query_fetch("SELECT * FROM MUSERVERMAKER_CREDITS_CONFIG ORDER BY config_id ASC");
			if($result) return $result;
			return false;
		}
	}
	
	public function buildSelectInput($name="creditsconfig", $default=1, $class="") {
		$selectName = (Validator::Chars($name, array('a-z', 'A-Z', '0-9', '_')) ? $name : "creditsconfig");
		$selectedOption = (Validator::UnsignedNumber($default) ? $default : 1);
		$configs = $this->showConfigs();
		$return = ($class ? '<select name="'.$selectName.'" class="'.$class.'">' : '<select name="'.$selectName.'">');
		if(is_array($configs)) {
			if($default == 0) {
				$return .= '<option value="0" selected>none</option>';
			} else {
				$return .= '<option value="0">none</option>';
			}
			foreach($configs as $config) {
				if($selectedOption == $config['config_id']) {
					$return .= '<option value="'.$config['config_id'].'" selected>'.$config['config_title'].'</option>';
				} else {
					$return .= '<option value="'.$config['config_id'].'">'.$config['config_title'].'</option>';
				}
			}
		} else {
			$return .= '<option value="0" selected>none</option>';
		}
		$return .= '</select>';
		return $return;
	}
	
	private function _isOnline($input) {
		if(!$this->_identifier) throw new Exception(lang('error_88'));
		switch($input) {
			case 'userid':
				$accountInfo = $this->common->accountInformation($this->_identifier);
				if(!$accountInfo) throw new Exception(lang('error_12'));
				return $this->common->accountOnline($accountInfo[_CLMN_USERNM_]);
				break;
			case 'username':
				return $this->common->accountOnline($this->_identifier);
				break;
			case 'email':
				$userId = $this->common->retrieveUserIDbyEmail($this->_identifier);
				if(!$userId) throw new Exception(lang('error_12'));
				$accountInfo = $this->common->accountInformation($userId);
				if(!$accountInfo) throw new Exception(lang('error_12'));
				return $this->common->accountOnline($accountInfo[_CLMN_USERNM_]);
				break;
			case 'character':
				$characterData = $this->character->CharacterData($this->_identifier);
				if(!$characterData) throw new Exception(lang('error_12'));
				return $this->common->accountOnline($characterData[_CLMN_CHR_ACCID_]);
				break;
			default:
				throw new Exception(lang('error_88'));
		}
	}
	
	private function _addLog($configTitle="unknown", $credits=0, $transaction="unknown") {
		$inadmincp = access == 'admincp' ? 1 : 0;
		if($inadmincp == 1) {
			$module = $_GET['module'];
		} else {
			$module = $_GET['page'] . '/' . $_GET['subpage'];
		}
		$ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0');
		
		$data = array(
			'config' => $configTitle,
			'identifier' => $this->_identifier,
			'credits' => $credits,
			'transaction' => $transaction,
			'timestamp' => time(),
			'inadmincp' => $inadmincp,
			'module' => $module,
			'ip' => $ip
		);
		
		$query = "INSERT INTO MUSERVERMAKER_CREDITS_LOGS "
			. "(log_config, log_identifier, log_credits, log_transaction, log_date, log_inadmincp, log_module, log_ip) "
			. "VALUES "
			. "(:config, :identifier, :credits, :transaction, :timestamp, :inadmincp, :module, :ip)";
		
		$saveLog = $this->memuonline->query($query, $data);
	}
	
	public function getLogs($limit=50) {
		$query = str_replace(array('{LIMIT}'), array($limit), "SELECT TOP {LIMIT} * FROM MUSERVERMAKER_CREDITS_LOGS ORDER BY log_id DESC");
		$result = $this->memuonline->query_fetch($query);
		if(is_array($result)) return $result;
	}
	
	public function getCredits() {
		if(!$this->_configId) throw new Exception(lang('error_66'));
		if(!$this->_identifier) throw new Exception(lang('error_66'));
		
		// get configs
		$config = $this->showConfigs(true);
		
		// choose database
		$database = ($config['config_database'] == "MuOnline" ? $this->muonline : $this->memuonline);
		
        // [SEGURIDAD] Sanitización
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_table']);
        $colCredits = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_credits_col']);
        $colUser = preg_replace('/[^a-zA-Z0-9_]/', '', $config['config_user_col']);

		$sql = "SELECT $colCredits FROM $table WHERE $colUser = ?";
		
		$getCredits = $database->query_fetch_single($sql, array($this->_identifier));
		if(!is_array($getCredits)) return 0;
		
		return $getCredits[$config['config_credits_col']];
	}
}