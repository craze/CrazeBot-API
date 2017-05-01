<?php
require_once "class/Bot.php";	// Handles CrazeBot data
require_once "class/Config.php";// Handles API configuration

/**
 * The API class generates JSON data based on the requested URI.
 *  1. $api = new API();
 *  2. $output = $api->json(); 
 * 
 * @author Geir André Halle
 *
 */
class API {
	private $bot;				// CrazeBot instance
	private $cfg;				// API configuration
	private $ch = null;			// Channel object
	private $chStr = "";		// Channel name as a string
	private $arg = array ();	// Virtual folders as requested by GET

	function __construct() {
		$this->cfg = new Config ();
		$this->bot = new Bot ( $this->cfg->botDir . '/global.properties' );
		$this->arg = explode ( "/", ltrim ( str_replace ( $_SERVER['SCRIPT_NAME'], "", $_SERVER['PHP_SELF']), '/' ) );
		
		if (count ( $this->arg ) >= 1)  {
			$this->chStr = $this->arg [0];
		}
		if (in_array ( '#' . strtolower ( $this->chStr ), explode ( ',', $this->bot->channelList ) )) {
			$this->ch = new Bot ( $this->cfg->botDir . '/#' . strtolower ( $this->chStr ) . '.properties' );
		}
	}
	/**
	 * 
	 * @return string Dynamically generated JSON encoded data
	 */
	public function json() {
		$jsonData = array();
		if ($this->ch && $this->arg [1]) {
			// Virtual folders that requires a parent channel
			switch ($this->arg [1]) {
				case 'commands':
					$jsonData = array(
						'settings' => array(
							'prefix' => $this->ch->commandPrefix,
							'mode' => $this->ch->mode
						),
						'commands' => $this->getCommands()
					);
					break;
				case 'complete_config':
					// Dumps entire channel configuration, disabled by default
					if ($this->cfg->useComplete) {
						$jsonData = $this->ch;
					}
					break;
				case 'filters':
					$jsonData = array(
						'config' => array('useFilters' => $this->ch->useFilters, 'globalFilter' => $this->ch->globalFilter),
						'filter' => $this->getFilters()
					);
					break;
				case 'settings':
					$jsonData = $this->getSettings();
					break;
				case 'users':
					$jsonData = array('users' => $this->getUsers());
					break;
				default:
					break;
			}
		} else {
			// Virtual folders that does not require additional folders
			switch ($this->arg [0]) {
				case 'botinfo':
					$jsonData = array('botname' => $this->bot->nick,
					'admins' => $this->getAdmins(),
					'bothelpMessage' => $this->bot->bothelpMessage, 
					'public' => $this->bot->publicJoin,
					'channelList' => $this->bot->channelList
					);
					if ($this->bot->tweetServer) {
						$jsonData = $jsonData + array('tweetserver' => $this->bot->tweetServer);
					}
					break;
				default:
					break;
			}
		}
		return json_encode($jsonData,JSON_UNESCAPED_UNICODE);
		
	}
	/**
	 * 
	 * @return array List of robot admins
	 */
	private function getAdmins() {
		$admins = array();
		$admins = explode ( ",", $this->bot->adminList);
		return $admins;
	}
	/**
	 * 
	 * @return array|array[]
	 */
	private function getUsers() {
		$users = array();
		if($this->ch) {
			$uR = explode ( ",", $this->ch->regulars, - 1);
			$uM = explode ( ",", $this->ch->moderators, - 1);
			$uO = explode ( ",", $this->ch->owners, - 1);
			
			$users = array(
					'owner' => $uO,
					'moderator' => $uM,
					'regular' => $uR
			);
		}
		return $users;
	}
	/**
	 * 
	 * @return array|array[] One array for each command inside parent array
	 */
	private function getCommands() {
		$cmdList = array ();
		if ($this->ch) {
			$cK = explode ( ",", $this->ch->commandsKey, - 1 );
			$cV = explode ( ",,", $this->ch->commandsValue, - 1 );
			
			$c = 0;
			while ( $c < count ( $cK ) ) {
				$cmd= $cK[$c];
				$cmdList[$cmd] = array('text' => htmlspecialchars($cV[$c]));
				
				if ($this->cmdRestricted($cmd)) {
					$cmdList[$cmd] = $cmdList[$cmd] + array(
							'restricted' => $this->cmdRestricted($cmd)
					);
				}
				if ($this->cmdHasSchedule($cmd)) {
					$cmdList[$cmd] = $cmdList[$cmd] + array(
							'schedule' => $this->cmdSchedule($cmd)
					);
				}
				if ($this->cmdHasRepeat($cmd)) {
					$cmdList[$cmd] = $cmdList[$cmd] + array(
							'repeat' => $this->cmdRepeat($cmd)
					);
				}
				$c ++;
			}
		}
		return $cmdList;
	}
	/**
	 * 
	 * @return array|array[] Array containing each filter and its value
	 */
	private function getFilters() {
		$filterList = array();
		if ($this->ch) {
			$filterList = array(
					'Offensive' => $this->ch->filterOffensive,
					'MaxLength' => $this->ch->filterMaxLength,
					'Colors' => $this->ch->filterColors,
					'Caps' => $this->ch->filterCaps,
					'EmotesSingle' => $this->ch->filterEmotesSingle,
					'Symbols' => $this->ch->filterSymbols,
					'EmotesMax' => $this->ch->filterEmotesMax,
					'CapsMinCapitals' => $this->ch->filterCapsMinCapitals,
					'Me' => $this->ch->filterMe,
					'SymbolsMin' => $this->ch->filterSymbolsMin,
					'Links' => $this->ch->filterLinks,
					'SymbolsPercent' => $this->ch->filterSymbolsPercent,
					'CapsPercent' => $this->ch->filterCapsPercent,
					'Emotes' => $this->ch->filterEmotes,
					'CapsMinCharacters' => $this->ch->filterCapsMinCharacters
			);
		}
		return $filterList;
	}
	/**
	 *
	 * @return array|array[] Array containing each setting and its value
	 */
	private function getSettings() {
		$settings = array(
				'mode' => $this->ch->mode,
				'commandPrefix' => $this->ch->commandPrefix,
				'timeoutDuration' => $this->ch->timeoutDuration,
				'banPhraseSeverity' => $this->ch->banPhraseSeverity,
				'emoteSet' => $this->ch->emoteSet,
				'enableThrow' => $this->ch->enableThrow,
				'permittedDomains' => $this->ch->permittedDomains,
				'subsciberAlert' => $this->ch->subScriberAlert,
				'announceJoinParts' => $this->ch->announceJoinParts,
				'clickToTweetFormat' => $this->ch->ClickToTweetFormat,
				'subscriberRegulars' => $this->ch->subscriberRegulars,
				'useTopic' => $this->ch->useTopic,
				'topic' => $this->ch->topic,
				'topicTime' => $this->ch->topicTime,
				'subMessage' => $this->ch->submessage,
				'commercialLength' => $this->ch->commercialLength,
				'enableWarnings' => $this->ch->enableWarnings,
				'signKicks' => $this->ch->signKicks
		);
		return $settings;
	}
	/**
	 * 
	 * @param string $cmd
	 * @return boolean
	 */
	private function cmdHasRepeat($cmd) {
		$repeat = false;
		if ($this->ch) {
			$cRK = explode ( ",", $this->ch->commandsRepeatKey, - 1 );
			
			if (in_array($cmd, $cRK)) {
				$repeat = true;
			}
		}
		return $repeat;
	}
	/**
	 * 
	 * @param string $cmd
	 * @return array|mixed[]
	 */
	private function cmdRepeat($cmd) {
		$schedule = array();
		if ($this->ch) {
			$cRA = explode ( ",", $this->ch->commandsRepeatActive, - 1 );
			$cRDe = explode ( ",", $this->ch->commandsRepeatDelay, - 1 );
			$cRDi = explode ( ",", $this->ch->commandsRepeatDiff, - 1 );
			$cRK = explode ( ",", $this->ch->commandsRepeatKey, - 1 );
			
			$k = array_search($cmd,$cRK);
			
			$schedule = array('active' => $cRA[$k],'delay' => $cRDe[$k],'diff' => $cRDi[$k]);
		}
		return $schedule;
	}
	/**
	 * 
	 * @param string $cmd
	 * @return boolean True if $cmd has a schedule, false if not
	 */
	private function cmdHasSchedule($cmd) {
		$schedule = false;
		if ($this->ch) {
			$cSK = explode ( ",,", $this->ch->commandsScheduleKey, - 1 );
			
			if (in_array($cmd, $cSK)) {
				$schedule = true;
			}
		}
		return $schedule;
	}
	/**
	 * 
	 * @param string $cmd
	 * @return array Schedule for the specified command
	 */
	private function cmdSchedule($cmd) {
		$schedule = array();
		if ($this->ch) {
			$cSA = explode ( ",,", $this->ch->commandsScheduleActive, - 1 );
			$cSD = explode ( ",,", $this->ch->commandsScheduleDiff, - 1 );
			$cSK = explode ( ",,", $this->ch->commandsScheduleKey, - 1 );
			$cSP = explode ( ",,", $this->ch->commandsSchedulePattern, - 1 );
			
			$k = array_search($cmd,$cSK);
			
			$schedule = array('active' => $cSA[$k],'diff' => $cSD[$k],'pattern' => $cSP[$k]);
		}
		return $schedule;
	}
	/**
	 * 
	 * @param string $cmd 		The command to check
	 * @return boolean|integer Required level for a command, or false if n/a.
	 */
	private function cmdRestricted($cmd) {
		$restriction = false;
		if ($this->ch) {
			$cR = explode ( ",", $this->ch->commandRestrictions, - 1 );
			
			$lvlArr = array ();
			$c = 0;
			foreach ($cR as $lvl) {
				$lvlEntry = explode ( "|", $lvl );
				$lvlArr [$lvlEntry [0]] = $lvlEntry [1];
				$c ++;
			}
			if (array_key_exists($cmd,$lvlArr)) { $restriction = $lvlArr[$cmd]; }
		}
		return $restriction;
	}
}
?>