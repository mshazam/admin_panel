<?php
namespace Ssslim\Core\Libraries;


class Exceptions {
	var $action;
	var $severity;
	var $message;
	var $filename;
	var $line;
	var $ob_level;

	var $levels = array(
						E_ERROR				=>	'Error',
						E_RECOVERABLE_ERROR	=>	'Error',
						E_WARNING			=>	'Warning',
						E_PARSE				=>	'Parsing Error',
						E_NOTICE			=>	'Notice',
						E_CORE_ERROR		=>	'Core Error',
						E_CORE_WARNING		=>	'Core Warning',
						E_COMPILE_ERROR		=>	'Compile Error',
						E_COMPILE_WARNING	=>	'Compile Warning',
						E_USER_ERROR		=>	'User Error',
						E_USER_WARNING		=>	'User Warning',
						E_USER_NOTICE		=>	'User Notice',
						E_STRICT			=>	'Strict',
						E_DEPRECATED		=>	'Deprecated'
					);


	/**
	 * Constructor
	 *
	 */	
	function __construct(Logger $logger)
	{
		$this->ob_level = ob_get_level();
		$this->logger = $logger;
			
		// Note:  Do not log messages from this constructor.
	}

	public function ssslim_exception_handler($severity, $message, $filepath, $line)
	{

		if (error_reporting() == 0) return; // prevent warnings from being logged when the suppress errors operator '@' is used


		// Should we display the error?
		// We'll get the current error_reporting level and add its bits
		// with the severity bits to find out.

		if ( (($severity & error_reporting()) == $severity) && ($severity != E_STRICT && $severity != 8192))
		{
			$this->show_php_error($severity, $message, $filepath, $line);
		}

		$this->log_exception($severity, $message, $filepath, $line);
	}


	// --------------------------------------------------------------------

	/**
	 * Exception Logger
	 *
	 * This function logs PHP generated error messages
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	/*ORIGINAL
	function log_exception($severity, $message, $filepath, $line)
	{	
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		
		log_message('error', 'Severity: '.$severity.'  --> '.$message. ' '.$filepath.' '.$line, TRUE);
	}*/
	
	function log_exception($severity, $message, $filepath, $line)
	{	
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		
		$error = 'PHP '.$severity.': '.$message. ' '.$filepath.' '.$line;
		$error .= ' on: '.(!empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'n/a');
		$error .= ' ref: '. (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'n/a');
		
		error_log($error);
	}

	// --------------------------------------------------------------------

	/**
	 * 404 Page Not Found Handler
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	/*ORIGINAL
	function show_404($page = '')
	{	
		$heading = "404 Page Not Found";
		$message = "The page you requested was not found.";

		log_message('error', '404 Page Not Found --> '.$page);
		echo $this->show_error($heading, $message, 'error_404');
		exit;
	}*/
	function show_404($page = '')
	{
	    $error = '404 Page Not Found --> ';

	    $error .= ($page == '') ? $_SERVER['REQUEST_URI'] : $page;

	    if (!empty($_SERVER['HTTP_REFERER']))
	        $error .= ' --> referer = '. $_SERVER['HTTP_REFERER'];

        $this->logger->log($error, Logger::ERR, "ssslim-system.log");

	    exit($this->show_error('404 Page Not Found', 'The page you requested was not found.', 'error_404'));
	}
  	
	// --------------------------------------------------------------------

	/**
	 * General Error Page
	 *
	 * This function takes an error message as input
	 * (either as a string or an array) and displays
	 * it using the specified template.
	 *
	 * @access	private
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template name
	 * @return	string
	 */
	function show_error($heading, $message, $template = 'error_general')
	{
		$message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';

		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();	
		}
		ob_start();
		include(APPPATH.'errors/'.$template.EXT);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line)
	{	
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
	
		$filepath = str_replace("\\", "/", $filepath);
		
		// For safety reasons we do not show the full file path
		if (FALSE !== strpos($filepath, '/'))
		{
			$x = explode('/', $filepath);
			$filepath = $x[count($x)-2].'/'.end($x);
		}
		
		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();	
		}
		ob_start();
		include(APPPATH.'errors/error_php'.EXT);
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}


}
// END Exceptions Class
?>