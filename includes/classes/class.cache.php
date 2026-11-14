<?php
/**
 * MuServerMaker CMS
 * https://muservermaker.com/
 * * @version 1.0.0
 * @author MuServerMaker
 * @copyright (c) 2026 MuServerMaker, All Rights Reserved
 */

class CacheManager {

	protected $_protectedCacheFiles = array(
		'plugins.cache',
		'blocked_ip.cache',
		'downloads.cache',
		'news.cache',
		'cron.lock',
		'cron.tick',
		'.htaccess',
		'.gitignore',
	);
	protected $_jsonArrayFiles = array(
		'castle_siege.cache',
		'character_country.cache',
		'online_characters.cache',
	);
	protected $_file;

	public function setFile($file) {
		$this->_file = basename((string)$file);
	}

	public function getCacheFileListAndData($type='') {
		switch($type) {
			case 'guild':
				$cacheFiles = $this->_getGuildProfileCacheFileList();
				$basePath = __PATH_GUILD_PROFILES_CACHE__;
				break;
			case 'player':
				$cacheFiles = $this->_getPlayerProfileCacheFileList();
				$basePath = __PATH_PLAYER_PROFILES_CACHE__;
				break;
			default:
				$cacheFiles = $this->_getCacheFileList();
				$basePath = __PATH_CACHE__;
		}
		if(!is_array($cacheFiles)) return;
		$result = array();
		foreach($cacheFiles as $row) {
			$filePath = $basePath . $row;
			$mtime = @filemtime($filePath);
			$result[] = array(
				'file'  => $row,
				'size'  => (int)@filesize($filePath),
				'edit'  => ($mtime ? (int)$mtime : 'Unknown'), // marca de tiempo; el modulo le da formato
				'write' => is_writable($filePath)
			);
		}
		return $result;
	}

	public function clearCacheData() {
		if(!check_value($this->_file)) return;
		if(!in_array($this->_file, $this->_getCacheFileList(), true)) throw new Exception('The requested cache file is not valid.');
		$filePath = __PATH_CACHE__ . $this->_file;
		$fileData = $this->_isJsonArrayFile($this->_file) ? '[]' : '';
		if(@file_put_contents($filePath, $fileData, LOCK_EX) === false) throw new Exception('The cache file could not be open.');
	}

	public function deleteGuildCache() {
		foreach($this->_getGuildProfileCacheFileList() as $row) {
			@unlink(__PATH_GUILD_PROFILES_CACHE__ . $row);
		}
	}

	public function deletePlayerCache() {
		foreach($this->_getPlayerProfileCacheFileList() as $row) {
			@unlink(__PATH_PLAYER_PROFILES_CACHE__ . $row);
		}
	}

	protected function _getCacheFileList() {
		return $this->_listFiles(__PATH_CACHE__);
	}

	protected function _getGuildProfileCacheFileList() {
		return $this->_listFiles(__PATH_GUILD_PROFILES_CACHE__);
	}

	protected function _getPlayerProfileCacheFileList() {
		return $this->_listFiles(__PATH_PLAYER_PROFILES_CACHE__);
	}

	// Lista los archivos de una carpeta. Si la carpeta no existe la crea; si no puede, devuelve lista vacia.
	protected function _listFiles($path) {
		$result = array();
		if(!is_dir($path) && !@mkdir($path, 0755, true)) return $result;
		$dir = @opendir($path);
		if(!$dir) return $result;
		while(($file = readdir($dir)) !== false) {
			if($file === '.' || $file === '..') continue;
			if(is_file($path . $file) && !$this->_isProtected($file)) {
				$result[] = $file;
			}
		}
		closedir($dir);
		sort($result);
		return $result;
	}

	protected function _isProtected($file) {
		return in_array($file, $this->_protectedCacheFiles, true);
	}

	protected function _isJsonArrayFile($file) {
		return in_array($file, $this->_jsonArrayFiles, true);
	}
}