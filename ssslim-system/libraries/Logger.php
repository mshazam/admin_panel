<?php

namespace Ssslim\Core\Libraries;

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Logger {


	private $config;

	const SHELL_RED = "[0;31m";
	const SHELL_LIGHT_RED = "[1;31m";
	const SHELL_LIGHT_CYAN = "[1;36m";
	const SHELL_RESET_COLOR = "[0m";


	var $currentLogFile = "";
	var $currentLogFlags = 0;

	private $log_buffer = '';

	const INFO 	   	   = 1;
	const WARN 	   	   = 2;
	const ERR 		   = 4;
	const SUCCESS	   = 8;

	const DATE 	   	   = 1;
	const SHORT_DATE   = 2;
	const TIME 	   	   = 4;
	const USER 	   	   = 8;
	const URL		   = 16;
	const REF		   = 32;
	const IP		   = 64;
	const UAGENT	   = 128;
	const UID		   = 256;
	const HOST		   = 512;

	const SPLITBYDATE   = 16384;
	const NOCOLOR		= 32768;
	const BUFFER		= 65536;

	const DBLOG_PRIORITY_WARNING = 30;
	const DBLOG_PRIORITY_ALERT 	 = 50;

	const EVT_TYPE_REGISTRATION = 1;
	const EVT_TYPE_PAGEVIEW		= 2;

	const EVT_STATUS_SUCCESS	= 2;

	const AETYPE_MOVIE_PROFILE_VIEW	= 1;
	const AETYPE_PAGE_VIEW			= 20;

	const AETYPE_SITE_REGISTRATION	= 24;

	const AETYPE_EMAIL_RETARGETING	= 30;

	private $_bench_a = array();

	// constants higher than 10,000 are meant to be used for temporary or test data


	const DEF 	  = 14; // SHORT_DATE|USER|TIME
	const HTTP    = 224; // UAGENT|IP|REF
	const BRIEF	  = 5; // DATE|TIME




	function __construct(Config $config) {
		$this->config = $config;
	}

	public function pickBench($reset=FALSE, $name_space = 'default'){
		if ($reset) $this->_bench_a[$name_space] = array();
		$this->_bench_a[$name_space][] = microtime(true);
	}

	public function getBench($name_space = 'default') {
		$this->_bench_a[$name_space][] = microtime(true);
		return round($this->_bench_a[$name_space][count($this->_bench_a[$name_space]) - 1] - $this->_bench_a[$name_space][0], 4);
	}

	function logNDie($exitMsg, $logMsg, $type = Logger::ERR, $logFile="", $flag=Logger::DEF) {
		$this->log($logMsg, $logFile, $type, $flag);
		die($exitMsg);
	}

	/**
	 *logfile is automatically written in logs/ folder and the .log suffix is NOT appended
	 *
	 * @param string|array $msg
	 * @param int $type
	 * @param string $logFile
	 * @param int $flag (example: BRIEF to have only date and time)
	 * @param int $rec_step
	 *
	 */
	function log($msg, $type = Logger::INFO, $logFile="", $flag=Logger::DEF, $rec_step=0){
		if(!is_array($msg)){
			$this->logLine(str_repeat('-->', $rec_step).$msg, $type, $logFile, $flag);
		}else{
			foreach($msg as $k=>$p){
				if(is_array($p)){
					$this->logLine(str_repeat('-->', $rec_step)."\"$k\"{", $type, $logFile, $flag);
					$this->log($p, $type, $logFile, $flag, $rec_step+1);
					$this->logLine(str_repeat('-->', $rec_step)."}", $type, $logFile, $flag);
				}else{
					$this->logLine(str_repeat('-->', $rec_step)."\"$k\": ".$p, $type, $logFile, $flag);
				}
			}
		}
	}

	function logLine($msg, $type = Logger::INFO, $logFile="", $flag=Logger::DEF) {
		if ($logFile == "") $logFile = $this->currentLogFile;
		if ($this->currentLogFlags != 0) $flag = $this->currentLogFlags;
		$head = $flag & Logger::NOCOLOR ? '' : chr(27);
		$line_end = "\n";

		if (substr($msg, 0, 1) == "^") {
			$line_end = "";
			$msg = substr($msg, 1);
		}

		if ($type & Logger::INFO) $head .= $flag & Logger::NOCOLOR ? "I" : "[0;36mI";
		else if ($type & Logger::WARN) $head .= $flag & Logger::NOCOLOR ? "W" : "[1;33mW";
		else if ($type & Logger::ERR) $head .= $flag & Logger::NOCOLOR ? "E" : "[0;31mE";
		else if ($type & Logger::SUCCESS) $head .= $flag & Logger::NOCOLOR ? "S" : "[1;32mS";

		if ($flag & Logger::SHORT_DATE) $head .= gmdate(' m-d');
		else if ($flag & Logger::DATE) $head .= gmdate(' Y-m-d');
		if ($flag & Logger::TIME) $head .= gmdate(' H:i:s');

		$head .= $flag & Logger::NOCOLOR ? " " : chr(27)."[0m ";

		if ($flag & Logger::USER) $head .= "'".(defined('USER_NICK') ? USER_NICK : '')."', ";
		if ($flag & Logger::UID) $head .= "'".USER_ID."', ";

		if ($flag & Logger::IP) $head .= "'".$_SERVER['REMOTE_ADDR']."', ";
		if ($flag & Logger::UAGENT) $head .= "'".(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : "")."', ";
		// FIX: REQUEST URI SHOULD CONTAIN QUERY STRING ON ALL SERVERS
		if ($flag & Logger::URL) $head .= "U: '".(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'URL n/a')."', ";
		//if ($flag & Logger::URL) $head .= "U: '".(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'URL n/a').(isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : "")."', ";
		if ($flag & Logger::HOST) $head .= "H: '".(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'HOST n/a')."', ";
		if ($flag & Logger::REF) $head .= "R: '".(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : "")."', ";

		$msg = $head.$msg.$line_end;

		if ($flag & Logger::SPLITBYDATE) $logFile = dirname($logFile).'/'.gmdate('Y-m-d-').basename($logFile);

		if ($log_handle = fopen("logs/".$logFile, 'a+')) {
			fwrite ($log_handle, $msg);
			fclose ($log_handle);
		}

		if ($type && self::BUFFER) $this->log_buffer .= $msg;
	}

	function setFlags($flags) {
		$this->currentLogFlags = $flags;
	}

	function setLogFile($logFile) {
		$this->currentLogFile = $logFile;
	}

	public function get_buffer($reset = TRUE)
	{
		$lb = $this->log_buffer;
		$this->log_buffer = '';
		return $lb;
	}

	public function colorize($txt, $color)
	{
		return chr(27).$color.$txt.chr(27).self::SHELL_RESET_COLOR;
	}

	static function debug($val)
	{
		//echo '<pre>';var_dump($val);echo '</pre>';
		list($callee) = debug_backtrace();
		$arguments = func_get_args();
		$total_arguments = count($arguments);

		echo '<fieldset style="background: #13773d !important; border:2px #f2f0a5 solid; padding:5px;margin:20px">';
		echo '<legend style="background:#13773d;padding:5px;color:#f2f0a5">'.$callee['file'].' @ line: '.$callee['line'].'</legend><pre style="color:#f2f0a5;font-size:14px;margin:0 0 10px 20px">';
		$i = 0;
		foreach ($arguments as $argument)
		{
			echo '<br/><strong>Debug #'.(++$i).' of '.$total_arguments.'</strong>: ';
			var_dump($argument);
		}

		echo "</pre>";
		echo "</fieldset>";
	}
}
?>
