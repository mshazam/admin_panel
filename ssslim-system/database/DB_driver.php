<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * Database Driver Class
 *
 * This is the platform-independent base DB implementation class.
 * This class will not be called directly. Rather, the adapter
 * class for the specific database will extend and instantiate it.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		Rick Ellis
 * @link		http://www.codeigniter.com/user_guide/database/
 */
class CI_DB_driver {

	var $username;
	var $password;
	var $hostname;
	var $database;
	var $dbdriver		= 'mysql';
	var $dbprefix		= '';
	var $port			= '';
	var $pconnect		= FALSE;
	var $conn_id		= FALSE;
	var $result_id		= FALSE;
	var $db_debug		= FALSE;
	var $benchmark		= 0;
	var $query_count	= 0;
	var $bind_marker	= '?';
	var $queries		= array();

	// These are use with Oracle
	var $stmt_id;
	var $curs_id;
	var $limit_used;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.  Accepts one parameter containing the database
     * connection settings.
     *
     * Database settings can be passed as discreet
     * parameters or as a data source name in the first
     * parameter. DSNs must have this prototype:
     * $dsn = 'driver://username:password@hostname/database';
     *
     * @param $params
     * @param \Ssslim\Core\Libraries\Logger $logger
     * @internal param $mixed . Can be an array or a DSN string
     */
	function __construct($params, Ssslim\Core\Libraries\Logger $logger)
	{
		$this->parseConfig($params);
        $this->logger = $logger;
    }

	private function parseConfig($params)
	{
		if (is_array($params))
		{
			$defaults = array(
				'hostname'	=> '',
				'username'	=> '',
				'password'	=> '',
				'database'	=> '',
				'conn_id'	=> FALSE,
				'dbdriver'	=> 'mysqli',
				'dbprefix'	=> '',
				'port'		=> '',
				'pconnect'	=> FALSE,
				'db_debug'	=> FALSE,
				'cachedir'	=> '',
				'cache_on'	=> FALSE
			);

			foreach ($defaults as $key => $val)
			{
				$this->$key = ( ! isset($params[$key])) ? $val : $params[$key];
			}
		}
		elseif (strpos($params, '://'))
		{
			if (FALSE === ($dsn = @parse_url($params)))
			{
                $this->logger->log('Invalid DB Connection String', \Ssslim\Core\Libraries\Logger::ERR, "ssslim-system.log");

				if ($this->db_debug)
				{
					return $this->display_error('db_invalid_connection_str');
				}
				return FALSE;
			}

			$this->hostname = ( ! isset($dsn['host'])) ? '' : rawurldecode($dsn['host']);
			$this->username = ( ! isset($dsn['user'])) ? '' : rawurldecode($dsn['user']);
			$this->password = ( ! isset($dsn['pass'])) ? '' : rawurldecode($dsn['pass']);
			$this->database = ( ! isset($dsn['path'])) ? '' : rawurldecode(substr($dsn['path'], 1));
		}
		return true;
	}

	protected function db_connect()
	{
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Initialize Database Settings
	 *
	 * @access	private Called by the constructor
	 * @param	mixed
	 * @return	void
	 */	
	function initialize()
	{
		// If an existing DB connection resource is supplied
		// there is no need to connect and select the database
		if (is_resource($this->conn_id))
		{
			return TRUE;
		}


		// Connect to the database
		$this->conn_id = $this->db_connect();

		// No connection?  Throw an error
		if ( ! $this->conn_id)
		{
            $this->logger->log('Unable to connect to the database', \Ssslim\Core\Libraries\Logger::ERR, "ssslim-system.log");

			if ($this->db_debug)
			{
				$this->display_error('db_unable_to_connect');
			}
			return FALSE;
		}

		// Select the database
		if ($this->database != '')
		{
			if ( ! $this->db_select())
			{
                $this->logger->log('Unable to select database: '.$this->database, \Ssslim\Core\Libraries\Logger::ERR, "ssslim-system.log");

				if ($this->db_debug)
				{
					$this->display_error('db_unable_to_select', $this->database);
				}
				return FALSE;
			}
		}

		return TRUE;
	}
		


	// --------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * Execute the query
	 *
	 * Accepts an SQL string as input and returns a result object upon
	 * successful execution of a "read" type query.  Returns boolean TRUE
	 * upon successful execution of a "write" type query. Returns boolean
	 * FALSE upon failure, and if the $db_debug variable is set to TRUE
	 * will raise an error.
	 *
	 * @access	public
	 * @param	string	An SQL query string
	 * @param	array	An array of binding data
	 * @return	mixed|CI_DB_mysqli_result
	 */	
	function query($sql)
	{
		if ($sql == '')
		{
			if ($this->db_debug)
			{
                $this->logger->log('Invalid query: '.$sql, \Ssslim\Core\Libraries\Logger::ERR, "ssslim-system.log");

				return $this->display_error('db_invalid_query');
			}
			return FALSE;		
		}

		// Save the  query for debugging
		$this->queries[] = $sql;

		// Start the Query Timer
		$time_start = list($sm, $ss) = explode(' ', microtime());
	
		// Run the Query
		if (FALSE === ($this->result_id = $this->simple_query($sql)))
		{

			if ($this->db_debug)
			{
				/* patch by kRs to enhance debug output */
				
				$one_lined_sql = preg_replace("/\\s+/", " ", $sql);

				$error = 'PHP MySQL: '.$this->_error_message()." - QRY: '$one_lined_sql'";
				$error .= ' on: '.(!empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'n/a');
				$error .= ' ref: '. (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'n/a');

				// PATCH BY KRS TO ALLOW MYSQL ERRORS TO BE LOGGED IN CENTRALIZED LOG (SEND THEM TO PHP ERROR LOG)

				error_log($error);
//				log_message('error', 'Query error: '.$this->_error_message()." ".$one_lined_sql);

				return $this->display_error(
										array(
												'Error Number: '.$this->_error_number(),
												$this->_error_message(),
												$sql
											)
										);
			}
		
		  return FALSE;
		}
		
		// Stop and aggregate the query time results
		$time_end = list($em, $es) = explode(' ', microtime());
		$this->benchmark += ($em + $es) - ($sm + $ss);
		// patch by krs to have a breakdown of all queries with their execution time (triggered by the db_debug parameter in the query string)
		$this->queries[count($this->queries) - 1] .= ' - time: '.(($em + $es) - ($sm + $ss));
		$this->queries_time[] = ($em + $es) - ($sm + $ss);

		// Increment the query counter
		$this->query_count++;
		
		// Was the query a "write" type?
		// If so we'll simply return true
		if ($this->is_write_type($sql) === TRUE)
		{

			return TRUE;
		}

		// Load and instantiate the result driver	
		
		$driver 		= $this->load_rdriver();
		$RES 			= new $driver();
		$RES->conn_id	= $this->conn_id;
		$RES->result_id	= $this->result_id;

		return $RES;
	}

	// --------------------------------------------------------------------

	/**
	 * Load the result drivers
	 *
	 * @access	public
	 * @return	string 	the name of the result class		
	 */		
	function load_rdriver()
	{
		$driver = 'CI_DB_'.$this->dbdriver.'_result';

		if ( ! class_exists($driver))
		{
			include_once(BASEPATH.'database/DB_result'.EXT);
			include_once(BASEPATH.'database/drivers/'.$this->dbdriver.'/'.$this->dbdriver.'_result'.EXT);
		}
		
		return $driver;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Simple Query
	 * This is a simplified version of the query() function.  Internally
	 * we only use it when running transaction commands since they do
	 * not require all the features of the main query() function.
	 *
	 * @access	public
	 * @param	string	the sql query
	 * @return	mixed		
	 */	
	function simple_query($sql)
	{
		if ( ! $this->conn_id)
		{
			$this->initialize();
		}

		return $this->_execute($sql);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Determines if a query is a "write" type.
	 *
	 * @access	public
	 * @param	string	An SQL query string
	 * @return	boolean		
	 */	
	function is_write_type($sql)
	{
		if ( ! preg_match('/^\s*"?(INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql))
		{
			return FALSE;
		}
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Calculate the aggregate query elapsed time
	 *
	 * @access	public
	 * @param	integer	The number of decimal places
	 * @return	integer		
	 */	
	function elapsed_time($decimals = 6)
	{
		return number_format($this->benchmark, $decimals);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Returns the total number of queries
	 *
	 * @access	public
	 * @return	integer		
	 */	
	function total_queries()
	{
		return $this->query_count;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Returns the last query that was executed
	 *
	 * @access	public
	 * @return	void		
	 */	
	function last_query()
	{
		return end($this->queries);
	}
	
	// --------------------------------------------------------------------

	/**
	 * "Smart" Escape String
	 *
	 * Escapes data based on type
	 * Sets boolean and null types
	 *
	 * @access	public
	 * @param	string
	 * @return	integer		
	 */	
	function escape($str)
	{
		if ( ! $this->conn_id)
		{
			$this->initialize();
		}

		switch (gettype($str))
		{
			case 'string'	:	$str = "'".$this->escape_str($str)."'";
				break;
			case 'boolean'	:	$str = ($str === FALSE) ? 0 : 1;
				break;
			default			:	$str = ($str === NULL) ? 'NULL' : $str;
				break;
		}		

		return $str;
	}

	// --------------------------------------------------------------------


	
	/**
	 * Generate an insert string
	 *
	 * @access	public
	 * @param	string	the table upon which the query will be performed
	 * @param	array	an associative array data of key/values
	 * @return	string		
	 */	
	function insert_string($table, $data)
	{
		$fields = array();	
		$values = array();
		
		foreach($data as $key => $val)
		{
			$fields[] = $key;
			$values[] = $this->escape($val);
		}

		return $this->_insert($this->dbprefix.$table, $fields, $values);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Generate an update string
	 *
	 * @access	public
	 * @param	string	the table upon which the query will be performed
	 * @param	array	an associative array data of key/values
	 * @param	mixed	the "where" statement
	 * @return	string		
	 */	
	function update_string($table, $data, $where)
	{
		if ($where == '')
			return false;
					
		$fields = array();
		foreach($data as $key => $val)
		{
			$fields[$key] = $this->escape($val);
		}

		if ( ! is_array($where))
		{
			$dest = array($where);
		}
		else
		{
			$dest = array();
			foreach ($where as $key => $val)
			{
				$prefix = (count($dest) == 0) ? '' : ' AND ';
	
				if ($val != '')
				{
					if ( ! $this->_has_operator($key))
					{
						$key .= ' =';
					}
				
					$val = ' '.$this->escape($val);
				}
							
				$dest[] = $prefix.$key.$val;
			}
		}		

		return $this->_update($this->dbprefix.$table, $fields, $dest);
	}	

	// --------------------------------------------------------------------

	/**
	 * Enables a native PHP function to be run, using a platform agnostic wrapper.
	 *
	 * @access	public
	 * @param	string	the function name
	 * @param	mixed	any parameters needed by the function
	 * @return	mixed		
	 */	
	function call_function($function)
	{
		$driver = ($this->dbdriver == 'postgre') ? 'pg_' : $this->dbdriver.'_';
	
		if (FALSE === strpos($driver, $function))
		{
			$function = $driver.$function;
		}
		
		if ( ! function_exists($function))
		{
			if ($this->db_debug)
			{
				return $this->display_error('db_unsupported_function');
			}
			return FALSE;			
		}
		else
		{
			$args = (func_num_args() > 1) ? array_splice(func_get_args(), 1) : null;

			return call_user_func_array($function, $args);
		}
	}


	// --------------------------------------------------------------------

	/**
	 * Close DB Connection
	 *
	 * @access	public
	 * @return	void		
	 */	
	function close()
	{
		if (is_resource($this->conn_id))
		{
			$this->_close($this->conn_id);
		}
		$this->conn_id = FALSE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Display an error message
	 *
	 * @access	public
	 * @param	string	the error message
	 * @param	string	any "swap" values
	 * @param	boolean	whether to localize the message
	 * @return	string	sends the application/error_db.php template		
	 */	
	function display_error($error = '', $swap = '', $native = FALSE)
	{

		if ($native == TRUE)
		{
			$message = $error;
		}
		else
		{
			$message = ( ! is_array($error)) ? array(str_replace('%s', $swap, $error)) : $error;
		}

		// QUICK PATCH BY KRS TO STOP ONLINE SITE FROM DISPLAYING MYSQL ERRORS AND QUERIES
		if (!ONLINE) show_error($message);
		exit;
	}
	
}

?>