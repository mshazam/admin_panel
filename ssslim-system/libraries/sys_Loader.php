<?php  
// namespace Ssslim\Libraries;
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Code Igniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		Rick Ellis
 * @copyright	Copyright (c) 2006, pMachine, Inc.
 * @license		http://www.codeignitor.com/user_guide/license.html
 * @link		http://www.codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Loader Class
 *
 * Loads views and files
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @author		Rick Ellis
 * @category	Loader
 * @link		http://www.codeigniter.com/user_guide/libraries/loader.html
 */

class CI_Loader {

	// All these are set automatically. Don't mess with them.
	var $_ci_ob_level;
	var $_ci_view_path		= '';
	var $_ci_cached_vars	= array();
	var $_ci_classes		= array();
	var $_ci_helpers		= array();
	var $_ci_varmap			= array('unit_test' => 'unit', 'user_agent' => 'agent');
	var $CI;
	
	protected $_class_cache = array();


	/**
	 * Constructor
	 *
	 * Sets the path to the view files and gets the initial output buffering level
	 *
	 * @access	public
	 */
	function __construct()
	{
//		$this->CI = &get_instance();
		$this->_ci_view_path = APPPATH.'views/';
		$this->_ci_ob_level  = ob_get_level();
				
	}

	public function init(){
		//virtual method to be overridden by app child class
	}

    public function ssslim_autoloader($class)
    {
        $map = ['Ssslim/Core/Libraries/' => BASEPATH . 'libraries/', 'Ssslim/Libraries/' => APPPATH . 'libraries/', 'Ssslim/Controllers/' => APPPATH . 'controllers/' ];

        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . EXT;

        foreach ($map as $pfx => $basePath) {

            if (0 === strpos($logicalPathPsr4, $pfx)) {
                $classFile = $basePath . substr($logicalPathPsr4, strlen($pfx) - 1);
                include($classFile);
                return true;
            }
        }
        return false;
    }

	// --------------------------------------------------------------------
	
	/**
	 * Loads a config file
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function config($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
	{			
		$CI =& get_instance();
		$CI->config->load($file, $use_sections, $fail_gracefully);
	}

	// --------------------------------------------------------------------


    /**
     * @param $view
     * @param array $vars
     * @param bool $return
     * @return String
     */

	public function view($view, $vars = array(), $return = FALSE)
	{
		// Set the path to the requested file
        $ext = pathinfo($view, PATHINFO_EXTENSION);
        $file = ($ext == '') ? $view.EXT : $view;
        $path = $this->_ci_view_path.$file;

		if ( ! file_exists($path))	show_error('Unable to load the requested file: '.$file);

		if (is_array($vars))extract($vars);

		ob_start();
				
		// If the PHP installation does not support short tags we'll
		// do a little string replacement, changing the short tags
		// to standard PHP echo statements.
	
		include($path);

        $buffer = ob_get_contents();
        @ob_end_clean();

		// Return the file data if requested
		if ($return === TRUE) return $buffer;
        else echo $buffer;
	}

	// --------------------------------------------------------------------
	
	public function getDB($config = '') {
		$key = "db_$config"; // for the singleton cache (only 1 connection per DB group)
		if (!empty($this->_class_cache[$key])) return $this->_class_cache[$key];

		include(APPPATH.'config/database'.EXT);
		$group = ($config == '') ? $active_group : $config;

		if ( ! isset($db[$group]))	show_error('You have specified an invalid database connection group: '.$group);
		$params = $db[$group];

		require_once(BASEPATH.'database/DB_driver'.EXT);
		require_once(BASEPATH.'database/drivers/mysqli/mysqli_driver'.EXT);

		return $this->_class_cache[$key] = new DB($params, $this->getLogger());
	}

	public function getRoutes() {
		if (!empty($this->_class_cache['Routes'])) return $this->_class_cache['Routes'];
		return $this->_class_cache['Routes'] = new \Ssslim\Libraries\Routes($this);
	}

	public function getConfig() {
		if (!empty($this->_class_cache['Config'])) return $this->_class_cache['Config'];
		// return $this->_class_cache['Config'] = new \Ssslim\Core\Libraries\Config();
		return $this->_class_cache['Config'] = new \Ssslim\Libraries\Config($this);
	}

    public function getLogger() {
        if (!empty($this->_class_cache['Logger'])) return $this->_class_cache['Logger'];
        return $this->_class_cache['Logger'] = new \Ssslim\Core\Libraries\Logger($this->getConfig());
    }

    public function getExceptions() {
        if (!empty($this->_class_cache['Exceptions'])) return $this->_class_cache['Exceptions'];
        return $this->_class_cache['Exceptions'] = new \Ssslim\Core\Libraries\Exceptions($this->getLogger());
    }

	function getNetwork() {
		if (!empty($this->_class_cache['Network'])) return $this->_class_cache['Network'];
		return $this->_class_cache['Network'] = new \Ssslim\Libraries\Network();
	}

}
