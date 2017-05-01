<?php
/**
 * The Bot class parse the robot configuration file and provides
 * easy access to its values. Also used for channel files.
 *
 * @author Geir André Halle
 *
 */
class Bot {
	/**
	 *
	 * @param string $botConfig Complete path to configuration file
	 */
	function __construct($botConfig) {
		if (is_readable($botConfig)) {
			$f = str_replace(';','\x3B',file_get_contents($botConfig));
			$i = parse_ini_string($f, false, INI_SCANNER_RAW);

			foreach ($i as $k => $v) {
				$this->$k = str_replace('\x3B',';',$v);
			}
		}
	}
}
?>