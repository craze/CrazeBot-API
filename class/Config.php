<?php
/**
 * The Confic class parse the web configuration file and provides
 * easy access to its values.
 * 
 * @author Geir André Halle
 *
 */
class Config {
	private $configFile = "config.php";
	public function __construct() {
		if (!is_file($this->configFile)) { echo "Can't find configuration file <em>'".$this->configFile."</em>'!<br>"; 
		} elseif (!is_readable($this->configFile)) { echo "Can't open configuration file <em>".$this->configFile."</em>!<br>Check permissions?<br>"; }
		$ini = parse_ini_file ( $this->configFile, false, INI_SCANNER_RAW );
		
		foreach ( $ini as $k => $v ) {
			$this->$k = $v;
		}
	}
}
?>