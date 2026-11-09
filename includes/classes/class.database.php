<?php
/**
 * MuServerMaker CMS
 * class.database.php - Version Estable (Linux Fix)
 */

class dB {
	
	public $error = '';
	public $dead = false;
	
	protected $db = null;
	
	function __construct($SQLHOST, $SQLPORT, $SQLDB, $SQLUSER, $SQLPWD, $SQLDRIVER) {
		try {
			$pdo_dsn = '';
			
            // --- DETECCIÓN DE SISTEMA (LINUX vs WINDOWS) ---
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                // Si es Linux (Tu Hosting), forzamos DBLIB con formato IP:PUERTO
                $pdo_dsn = "dblib:host={$SQLHOST}:{$SQLPORT};dbname={$SQLDB}";
            } else {
                // Si es Windows (Local/VPS), usamos la lógica original
                if ($SQLDRIVER == 2 || $SQLDRIVER == 'sqlsrv') {
                    $pdo_dsn = "sqlsrv:Server={$SQLHOST},{$SQLPORT};Database={$SQLDB}";
                } else {
                    $pdo_dsn = "odbc:Driver={SQL Server};Server={$SQLHOST},{$SQLPORT};Database={$SQLDB};";
                }
            }

            // Conexión
			$this->db = new PDO($pdo_dsn, $SQLUSER, $SQLPWD);
			
			// Configuraciones
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->db->setAttribute(PDO::ATTR_TIMEOUT, 5); // Timeout corto para no colgar la web
			
		} catch (PDOException $e) {
			$this->dead = true;
            $this->db = null;
			$this->error = "Error de conexión SQL.";
            // Solo logueamos en el error_log interno del servidor (seguro)
            error_log("DB Error: " . $e->getMessage()); 
		}
	}
	
	public function query($sql, $array=[]) {
        // Si no hay conexión, salimos sin romper nada
        if ($this->dead || $this->db === null) return false;

		if(!is_array($array)) {
			$array = empty($array) ? [] : [$array];
		}
		
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($array);
			// $stmt->closeCursor(); // A veces causa problemas en DBLIB, lo quitamos por seguridad
			return true;
		} catch (PDOException $e) {
            // Silencioso para evitar Error 500
            return false;
		}
	}
	
	public function query_fetch($sql, $array=[]) {
        // Si no hay conexión, devolvemos null
        if ($this->dead || $this->db === null) return null;

		if(!is_array($array)) {
			$array = empty($array) ? [] : [$array];
		}

		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($array);
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			// $stmt->closeCursor();
			
			return (count($result) > 0) ? $result : null;
		} catch (PDOException $e) {
			return null;
		}
	}
	
	public function query_fetch_single($sql, $array=[]) {
		$result = $this->query_fetch($sql, $array);
		return (isset($result[0])) ? $result[0] : null;
	}
}
?>