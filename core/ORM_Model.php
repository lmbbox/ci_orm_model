<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter ORM Model
 *
 * An ORM Model for CodeIgniter
 *
 * @package		CodeIgniter ORM Model
 * @author		LMB^Box (Thomas Montague)
 * @copyright	Copyright (c) 2009 - 2011, LMB^Box
 * @license		GNU Lesser General Public License (http://www.gnu.org/copyleft/lgpl.html)
 * @link		http://lmbbox.com/projects/ci-orm-model/
 * @since		Version 0.0.1
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter ORM Model Class
 *
 * @package		CodeIgniter ORM Model
 * @subpackage	Libraries
 * @category	Libraries
 * @author		LMB^Box (Thomas Montague)
 * @link		http://codeigniter.lmbbox.com/user_guide/libraries/orm_model.html
 */
class CI_ORM_Model extends CI_Model {
	
	private $_write_requires_pks		= FALSE;
	private $_use_transactions			= FALSE;
	private $_table						= NULL;
	private $_fields					= array(); // array('field name' => array('default' => 'value', 'auto_update' => TRUE)
	private $_write_format_fields		= array(); // array('field name' => 'callback')
	private $_read_format_fields		= array(); // array('field name' => 'callback')
	private $_validate_fields			= array(); // array('field name' => NULL or 'is_string' or array('is_array' => TRUE, 'empty' => FALSE)
	private $_required_fields			= array(); // array('insert' => array('field name'), 'update' => array('field name'), 'delete' => array('field name'))
	private $_protected_fields			= array(); // array('field name')
	private $_primary_keys				= array(); // array('field name')
	private $_foreign_tables			= array(); // array('table' => array('model' => 'table_model', 'cascade' => TRUE, 'foreign_keys' => array('foreign_key' => 'primary_key')))
	
	protected $_query					= NULL;
	protected $_insert_pks				= NULL;
	protected $_affected_rows			= NULL;
	
	public function __construct()
	{
		parent::__construct();
		log_message('debug', get_class($this) . ' Class (Extending CI_ORM_Model Class) Initialized');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Toggle Write Requires Primary Keys
	 * 
	 * @access	protected
	 * @param	bool	$required
	 * @return	void
	 */
	final protected function _set_write_requires_pks($required)
	{
		$this->_write_requires_pks = (bool) $required;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Toggle Use SQL Transactions
	 * 
	 * @access	protected
	 * @param	bool	$required
	 * @return	void
	 */
	final protected function _set_use_transactions($required)
	{
		$this->_use_transactions = (bool) $required;
	}
	
	final protected function _set_table($table)
	{
		if (!is_string($table) || '' == ($table = trim($table)))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$this->_table = $table;
		return TRUE;
	}
	
	final protected function _set_fields()
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (FALSE === $this->db->table_exists($this->_table))
		{
			log_message('error', __METHOD__ . ' - Table "' . $this->_table . '" does not exist.');
			return FALSE;
		}
		
		$fields = $this->db->list_fields($this->_table);
		
		if (!is_array($fields) || empty($fields))
		{
			log_message('error', __METHOD__ . ' - Failed to get Fields from the table "' . $this->_table . '".');
			return FALSE;
		}
		
		$results = array();
		
		foreach ($fields as $field)
		{
			$results[] = $this->_add_field($field);
		}
		
		return !in_array(FALSE, $results);
	}
	
	final protected function _add_field($field, $settings = array())
	{
		if (!is_string($field) || '' == ($field = trim($field)) || !is_array($settings))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$this->_fields[$field] = array('default' => '', 'on_update' => FALSE, 'auto_update' => FALSE, 'force_default' => FALSE);
		
		foreach ($settings as $name => $value)
		{
			$this->_set_field_setting($field, $name, $value);
		}
		
		return TRUE;
	}
	
	final protected function _set_field_setting($field, $setting, $value)
	{
		if (!is_array($this->_fields) || empty($this->_fields))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (!is_string($field) || '' == ($field = trim($field)) || !array_key_exists($field, $this->_fields) || !is_string($setting) || '' == ($setting = strtolower(trim($setting))))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		switch ($setting)
		{
			case 'default':
				$this->_fields[$field]['default'] = $value;
				break;
			case 'on_update':
				$this->_fields[$field]['on_update'] = (bool) $value;
				break;
			case 'auto_update':
				$this->_fields[$field]['auto_update'] = (bool) $value;
				break;
			case 'force_default':
				$this->_fields[$field]['force_default'] = (bool) $value;
				break;
			case 'write_format':
				if (is_string($value) && '' != ($value = trim($value)))
				{
					$this->_write_format_fields[$field] = $value;
				}
				break;
			case 'read_format':
				if (is_string($value) && '' != ($value = trim($value)))
				{
					$this->_read_format_fields[$field] = $value;
				}
				break;
			default:
				log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
				return FALSE;
				break;
		}
		
		return TRUE;
	}
	
	final protected function _add_validate_field($field, $validation)
	{
		if (!is_array($this->_fields) || empty($this->_fields))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (is_string($validation) && '' != ($validation = trim($validation)))
		{
			$validation = array($validation => TRUE);
		}
		
		if (!is_string($field) || '' == ($field = trim($field)) || !array_key_exists($field, $this->_fields) || !is_array($validation) || empty($validation))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$this->_validate_fields[$field] = $validation;
		return TRUE;
	}
	
	final protected function _add_required_field($field, $operation = 'all')
	{
		if (!is_array($this->_fields) || empty($this->_fields))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (!is_string($field) || '' == ($field = trim($field)) || !array_key_exists($field, $this->_fields))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		switch (strtolower(trim($operation)))
		{
			case 'all':
				$this->_required_fields['insert'][] = $field;
				$this->_required_fields['update'][] = $field;
				$this->_required_fields['delete'][] = $field;
				break;
			case 'insert':
				$this->_required_fields['insert'][] = $field;
				break;
			case 'update':
				$this->_required_fields['update'][] = $field;
				break;
			case 'delete':
				$this->_required_fields['delete'][] = $field;
				break;
			default:
				log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
				return FALSE;
				break;
		}
		
		return TRUE;
	}
	
	final protected function _add_protected_field($field)
	{
		if (!is_array($this->_fields) || empty($this->_fields))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (!is_string($field) || '' == ($field = trim($field)) || !array_key_exists($field, $this->_fields))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$this->_protected_fields[] = $field;
		return TRUE;
	}
	
	final protected function _add_primary_key($field)
	{
		if (!is_array($this->_fields) || empty($this->_fields))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (!is_string($field) || '' == ($field = trim($field)) || !array_key_exists($field, $this->_fields))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		if (in_array($field, $this->_primary_keys))
		{
			log_message('error', __METHOD__ . ' - Field "' . $field . '" is already set as a Primary Key.');
			return FALSE;
		}
		
		$this->_primary_keys[] = $field;
		return TRUE;
	}
	
	final protected function _add_foreign_table($table, $model, $foreign_keys, $insert = TRUE, $update = TRUE, $delete = TRUE, $cascade = FALSE)
	{
		if (!is_array($this->_primary_keys) || empty($this->_primary_keys))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (!is_string($table) || '' == ($table = trim($table)) || !is_string($model) || '' == ($model = trim($model)) || !is_array($foreign_keys) || empty($foreign_keys))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$this->_foreign_tables[$table]['model'] = $model;
		$this->_foreign_tables[$table]['foreign_keys'] = $foreign_keys;
		$this->_foreign_tables[$table]['insert'] = (bool) $insert;
		$this->_foreign_tables[$table]['update'] = (bool) $update;
		$this->_foreign_tables[$table]['delete'] = (bool) $delete;
		$this->_foreign_tables[$table]['cascade'] = (bool) $cascade;
		return TRUE;
	}
	
	final protected function _run_check($check_table = FALSE, $check_fields = FALSE)
	{
		if (is_null($this->_table) || '' == $this->_table || !is_array($this->_fields) || empty($this->_fields) || !is_array($this->_primary_keys) || empty($this->_primary_keys))
		{
			show_error('Required config(s) missing for model. Check logs for details.');
		}
		
		if (TRUE === $check_table)
		{
			if (FALSE === $this->_check_table())
			{
				show_error('Invalid setup for model. Check logs for details.');
			}
		}
		
		if (TRUE === $check_fields)
		{
			if (FALSE === $this->_check_fields())
			{
				show_error('Invalid setup for model. Check logs for details.');
			}
		}
		
		return TRUE;
	}
	
	final protected function _check_table()
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (FALSE === $this->db->table_exists($this->_table))
		{
			log_message('error', __METHOD__ . ' - Table "' . $this->_table . '" does not exist.');
			return FALSE;
		}
		
		return TRUE;
	}
	
	final protected function _check_fields()
	{
		if (is_null($this->_table) || '' == $this->_table || !is_array($this->_fields) || empty($this->_fields))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		$fields = $this->db->list_fields($this->_table);
		$failed_check = FALSE;
		
		foreach ($this->_fields as $field => $default)
		{
			if (!in_array($field, $fields))
			{
				log_message('error', __METHOD__ . ' - Field "' . $field . '" does not exist in table.');
				$failed_check = TRUE;
			}
		}
		
		return !$failed_check;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get Number of Rows from last Query
	 * 
	 * @access	public
	 * @return	int
	 */
	final public function num_rows()
	{
		return $this->_query->num_rows();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get Number of Affected Rows from last Query
	 * 
	 * @access	public
	 * @return	int
	 */
	final public function affected_rows()
	{
		return $this->_affected_rows;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Select
	 *
	 * Generates the SELECT portion of the query
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	function select($select = '*', $escape = NULL)
	{
		$this->db->select($select, $escape);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Select Max
	 *
	 * Generates a SELECT MAX(field) portion of a query
	 *
	 * @access	public
	 * @param	string	the field
	 * @param	string	an alias
	 * @return	object
	 */
	function select_max($select = '', $alias = '')
	{
		$this->db->select_max($select, $alias);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Select Min
	 *
	 * Generates a SELECT MIN(field) portion of a query
	 *
	 * @access	public
	 * @param	string	the field
	 * @param	string	an alias
	 * @return	object
	 */
	function select_min($select = '', $alias = '')
	{
		$this->db->select_min($select, $alias);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Select Average
	 *
	 * Generates a SELECT AVG(field) portion of a query
	 *
	 * @access	public
	 * @param	string	the field
	 * @param	string	an alias
	 * @return	object
	 */
	function select_avg($select = '', $alias = '')
	{
		$this->db->select_avg($select, $alias);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Select Sum
	 *
	 * Generates a SELECT SUM(field) portion of a query
	 *
	 * @access	public
	 * @param	string	the field
	 * @param	string	an alias
	 * @return	object
	 */
	function select_sum($select = '', $alias = '')
	{
		$this->db->select_sum($select, $alias);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * DISTINCT
	 *
	 * Sets a flag which tells the query string compiler to add DISTINCT
	 *
	 * @access	public
	 * @param	bool
	 * @return	object
	 */
	function distinct($val = TRUE)
	{
		$this->db->distinct($val);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Join
	 *
	 * Generates the JOIN portion of the query
	 *
	 * @access	public
	 * @param	string
	 * @param	string	the join condition
	 * @param	string	the type of join
	 * @return	object
	 */
	function join($table, $cond, $type = '')
	{
		$this->db->join($table, $cond, $type);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Where
	 *
	 * Generates the WHERE portion of the query. Separates
	 * multiple calls with AND
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	object
	 */
	function where($key, $value = NULL, $escape = TRUE)
	{
		$this->db->where($key, $value, $escape);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * OR Where
	 *
	 * Generates the WHERE portion of the query. Separates
	 * multiple calls with OR
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	object
	 */
	function or_where($key, $value = NULL, $escape = TRUE)
	{
		$this->db->or_where($key, $value, $escape);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Where_in
	 *
	 * Generates a WHERE field IN ('item', 'item') SQL query joined with
	 * AND if appropriate
	 *
	 * @access	public
	 * @param	string	The field to search
	 * @param	array	The values searched on
	 * @return	object
	 */
	function where_in($key = NULL, $values = NULL)
	{
		$this->db->where_in($key, $value);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Where_in_or
	 *
	 * Generates a WHERE field IN ('item', 'item') SQL query joined with
	 * OR if appropriate
	 *
	 * @access	public
	 * @param	string	The field to search
	 * @param	array	The values searched on
	 * @return	object
	 */
	function or_where_in($key = NULL, $values = NULL)
	{
		$this->db->or_where_in($key, $value);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Where_not_in
	 *
	 * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
	 * with AND if appropriate
	 *
	 * @access	public
	 * @param	string	The field to search
	 * @param	array	The values searched on
	 * @return	object
	 */
	function where_not_in($key = NULL, $values = NULL)
	{
		$this->db->where_not_in($key, $value);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Where_not_in_or
	 *
	 * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
	 * with OR if appropriate
	 *
	 * @access	public
	 * @param	string	The field to search
	 * @param	array	The values searched on
	 * @return	object
	 */
	function or_where_not_in($key = NULL, $values = NULL)
	{
		$this->db->or_where_not_in($key, $value);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Like
	 *
	 * Generates a %LIKE% portion of the query. Separates
	 * multiple calls with AND
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	object
	 */
	function like($field, $match = '', $side = 'both')
	{
		$this->db->like($field, $match, $side);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Not Like
	 *
	 * Generates a NOT LIKE portion of the query. Separates
	 * multiple calls with AND
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	object
	 */
	function not_like($field, $match = '', $side = 'both')
	{
		$this->db->not_like($field, $match, $side);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * OR Like
	 *
	 * Generates a %LIKE% portion of the query. Separates
	 * multiple calls with OR
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	object
	 */
	function or_like($field, $match = '', $side = 'both')
	{
		$this->db->or_like($field, $match, $side);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * OR Not Like
	 *
	 * Generates a NOT LIKE portion of the query. Separates
	 * multiple calls with OR
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	object
	 */
	function or_not_like($field, $match = '', $side = 'both')
	{
		$this->db->or_not_like($field, $match, $side);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * GROUP BY
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	function group_by($by)
	{
		$this->db->group_by($by);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Sets the HAVING value
	 *
	 * Separates multiple calls with AND
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	object
	 */
	function having($key, $value = '', $escape = TRUE)
	{
		$this->db->having($key, $value, $escape);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Sets the OR HAVING value
	 *
	 * Separates multiple calls with OR
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	object
	 */
	function or_having($key, $value = '', $escape = TRUE)
	{
		$this->db->or_having($key, $value, $escape);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Sets the ORDER BY value
	 *
	 * @access	public
	 * @param	string
	 * @param	string	direction: asc or desc
	 * @return	object
	 */
	function order_by($orderby, $direction = '')
	{
		$this->db->order_by($orderby, $direction);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Sets the LIMIT value
	 *
	 * @access	public
	 * @param	integer	the limit value
	 * @param	integer	the offset value
	 * @return	object
	 */
	function limit($value, $offset = '')
	{
		$this->db->limit($value, $offset);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Sets the OFFSET value
	 *
	 * @access	public
	 * @param	integer	the offset value
	 * @return	object
	 */
	function offset($offset)
	{
		$this->db->offset($offset);
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get Records
	 * 
	 * @access	public
	 * @param	array|string	$where
	 * @param	array|string	$select
	 * @param	array			$order_by
	 * @param	array|string	$group_by
	 * @param	int				$limit
	 * @param	int				$offset
	 * @param	array			$join
	 * @param	bool			$escape
	 * @param	bool			$auto_format
	 * @return	array|bool
	 */
	final public function get($where = NULL, $select = NULL, $order_by = NULL, $group_by = NULL, $limit = NULL, $offset = NULL, $join = NULL, $escape = TRUE, $auto_format = TRUE)
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if ((is_array($where) && !empty($where)) || (is_string($where) && '' != ($where = trim($where))))
		{
			$this->db->where($where, NULL, (bool) $escape);
		}
		
		if ((is_array($select) && !empty($select)) || (is_string($select) && '' != ($select = trim($select))))
		{
			$this->db->select($select, (bool) $escape);
		}
		
		if (is_array($order_by) && !empty($order_by))
		{
			foreach ($order_by as $field => $direction)
			{
				$this->db->order_by($field, $direction);
			}
		}
		
		if ((is_array($group_by) && !empty($group_by)) || (is_string($group_by) && '' != ($group_by = trim($group_by))))
		{
			$this->db->group_by($group_by);
		}
		
		if (is_numeric($limit))
		{
			$this->db->limit($limit);
		}
		
		if (is_numeric($offset))
		{
			$this->db->offset($offset);
		}
		
		if (is_array($join) && !empty($join))
		{
			foreach ($join as $parameters)
			{
				if (!is_array($parameters) || empty($parameters) || '' == $parameters['table'] || '' == $parameters['condition'])
				{
					log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
					return FALSE;
				}
				
				$this->db->join($parameters['table'], $parameters['condition'], !isset($parameters['type']) ? '' : $parameters['type']);
			}
		}
		
		$this->_query = $this->db->get($this->_table);
		log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
		
		if (0 == $this->_query->num_rows())
		{
			return FALSE;
		}
		
		return TRUE === $auto_format ? $this->_read_format_fields($this->_query->result_array()) : $this->_query->result_array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get Record
	 * 
	 * @access	public
	 * @param	array|string	$where
	 * @param	array|string	$select
	 * @param	array			$join
	 * @param	bool			$escape
	 * @param	bool			$auto_format
	 * @return	array|bool
	 */
	final public function get_record($where = NULL, $select = NULL, $join = NULL, $escape = TRUE, $auto_format = TRUE)
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		$record = $this->get($where, $select, NULL, NULL, 1, NULL, $join, $escape, $auto_format);
		
		if (FALSE === $record || 1 != $this->num_rows())
		{
			return FALSE;
		}
		
		return $record[0];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Count Records
	 * 
	 * @access	public
	 * @param	array|string	$where
	 * @param	bool			$escape
	 * @return	int|bool
	 */
	final public function count($where = NULL, $join = NULL, $escape = TRUE)
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if ((is_array($where) && !empty($where)) || (is_string($where) && '' != ($where = trim($where))))
		{
			$this->db->where($where, NULL, (bool) $escape);
		}
		
		if (is_array($join) && !empty($join))
		{
			foreach ($join as $parameters)
			{
				if (!is_array($parameters) || empty($parameters) || '' == $parameters['table'] || '' == $parameters['condition'])
				{
					log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
					return FALSE;
				}
				
				$this->db->join($parameters['table'], $parameters['condition'], !isset($parameters['type']) ? '' : $parameters['type']);
			}
		}
		
		$results = $this->db->count_all_results($this->_table);
		log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
		
		return $results;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Record Exists
	 * 
	 * @access	public
	 * @param	array|string	$where
	 * @param	bool			$escape
	 * @return	bool
	 */
	final public function record_exists($where = NULL, $join = NULL, $escape = TRUE)
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		return 0 == $this->count($where, $join, $escape) ? FALSE : TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Insert Record
	 * 
	 * @access	public
	 * @param	array	$data
	 * @param	array	$foreign_data
	 * @param	bool	$defaults_override
	 * @param	bool	$do_foreign
	 * @param	bool	$override_transactions
	 * @return	array|bool
	 */
	final public function insert($data, $foreign_data = NULL, $defaults_override = FALSE, $do_foreign = TRUE, $override_transactions = NULL)
	{
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		return $this->_insert($data, $foreign_data, $defaults_override, $do_foreign, $override_transactions);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Insert Records
	 * 
	 * @access	public
	 * @param	array	$records
	 * @param	bool	$defaults_override
	 * @param	bool	$do_foreign
	 * @param	bool	$override_transactions
	 * @return	array|bool
	 */
	final public function insert_all($records, $defaults_override = FALSE, $do_foreign = TRUE, $override_transactions = NULL)
	{
		if (!is_array($records) || empty($records))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$_use_transactions = $this->_use_transactions;
			$this->_use_transactions = $override_transactions;
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_start();
		}
		
		$results = array();
		
		foreach ($records as $record)
		{
			$results[] = $this->_insert($record['data'], $record['foreign_data'], $defaults_override, $do_foreign, $this->_use_transactions);
		}
		
		if (in_array(FALSE, $results))
		{
			if (TRUE === $this->_use_transactions)
			{
				$this->db->trans_rollback();
			}
			else
			{
				foreach ($results as $result)
				{
					$this->delete($result);
				}
			}
			
			log_message('error', __METHOD__ . ' - Error inserting records into table "' . $this->_table . '"!');
			return FALSE;
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_complete();
			if (FALSE === $this->db->trans_status())
			{
				log_message('error', __METHOD__ . ' - Error inserting records into table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$this->_use_transactions = $_use_transactions;
		}
		
		return $results;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Insert Record
	 * 
	 * @access	protected
	 * @param	array	$data
	 * @param	array	$foreign_data
	 * @param	bool	$defaults_override
	 * @param	bool	$do_foreign
	 * @param	bool	$override_transactions
	 * @return	string|array|bool
	 */
	final protected function _insert($data, $foreign_data = NULL, $defaults_override = FALSE, $do_foreign = TRUE, $override_transactions = NULL)
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (FALSE === $this->_clean_data_fields($data) || FALSE === $this->_pre_populate_fields($data, FALSE, (bool) $defaults_override) || FALSE === $this->_write_format_fields($data) || !is_array($data) || empty($data) || FALSE === $this->_check_primary_keys($data) || FALSE === $this->_check_required_fields('insert', $data) || FALSE === $this->_validate_fields($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$_use_transactions = $this->_use_transactions;
			$this->_use_transactions = $override_transactions;
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_start();
		}
		
		if (FALSE === $this->db->insert($this->_table, $data) && FALSE === $this->_use_transactions)
		{
			log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
			log_message('error', __METHOD__ . ' - Error inserting record into table "' . $this->_table . '"!');
			return FALSE;
		}
		
		log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
		$this->_insert_pks = $this->_get_primary_keys($data);
		
		if (is_array($foreign_data) && !empty($foreign_data) && TRUE === (bool) $do_foreign && FALSE === $this->_foreign_table_insert($this->_insert_pks, $foreign_data, (bool) $defaults_override) && FALSE === $this->_use_transactions)
		{
			if (!is_array($this->_insert_pks) && !empty($this->_insert_pks))
			{
				$this->_insert_pks = array($this->_primary_keys[0] => $this->_insert_pks);
			}
			
			$this->delete($this->_insert_pks);
			log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
			log_message('error', __METHOD__ . ' - Error inserting record into table "' . $this->_table . '"!');
			return FALSE;
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_complete();
			if (FALSE === $this->db->trans_status())
			{
				log_message('error', __METHOD__ . ' - Error inserting record into table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$this->_use_transactions = $_use_transactions;
		}
		
		return $this->_insert_pks;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Insert Foreign Record(s)
	 * 
	 * @access	protected
	 * @param	string|array	$insert_pks
	 * @param	array			$foreign_data
	 * @param	bool			$defaults_override
	 * @return	bool
	 */
	final protected function _foreign_table_insert($insert_pks, $foreign_data, $defaults_override = FALSE)
	{
		if (!is_array($this->_foreign_tables) || empty($this->_foreign_tables))
		{
			return TRUE;
		}
		
		if (!is_array($this->_primary_keys) || empty($this->_primary_keys))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;		
		}
		
		if (empty($insert_pks) || !is_array($foreign_data) || empty($foreign_data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$CI =& get_instance();
		
		foreach ($this->_foreign_tables as $table => $settings)
		{
			if ('' == $table || !is_array($settings) || empty($settings) || '' == $settings['model'] || !is_array($settings['foreign_keys']) || empty($settings['foreign_keys']) || !is_bool($settings['insert']))
			{
				log_message('error', __METHOD__ . ' - Required config(s) missing.');
				return FALSE;		
			}
			
			if (!is_array($foreign_data[$table]) || !empty($foreign_data[$table]) || FALSE === $settings['insert'])
			{
				continue;
			}
			
			$data = $foreign_data[$table];
			
			if (is_array($insert_pks) && !empty($insert_pks))
			{
				foreach ($settings['foreign_keys'] as $fkey => $pkey)
				{
					if (isset($insert_pks[$pkey]) && !empty($insert_pks[$pkey]))
					{
						$data[$fkey] = $insert_pks[$pkey];
					}
				}
			}
			elseif (is_string($insert_pks) && '' != $insert_pks)
			{
				$data[$this->_primary_keys[0]] = $insert_pks;
			}
			else
			{
				log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
				return FALSE;
			}
			
			$CI->load->model($settings['model']);
			
			if (FALSE === $CI->$settings['model']->insert($data, $foreign_data, $defaults_override, $settings['cascade'], $this->_use_transactions) && FALSE === $this->_use_transactions)
			{
				log_message('error', __METHOD__ . ' - Error inserting foreign record for table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	final public function update($data, $foreign_data = NULL, $defaults_override = FALSE, $do_foreign = TRUE, $override_transactions = NULL)
	{
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		return $this->_update($data, $this->_get_primary_keys($data), $foreign_data, $defaults_override, $do_foreign, $override_transactions);
	}
	
	final public function update_where($data, $where = NULL, $foreign_data = NULL, $defaults_override = FALSE, $do_foreign = TRUE, $override_transactions = NULL, $escape = TRUE)
	{
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		return $this->_update($data, $where, $foreign_data, $defaults_override, $do_foreign, $override_transactions, $escape);
	}
	
	final public function update_all($records, $defaults_override = FALSE, $do_foreign = TRUE, $override_transactions = NULL)
	{
		if (!is_array($records) || empty($records))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$_use_transactions = $this->_use_transactions;
			$this->_use_transactions = $override_transactions;
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_start();
		}
		
		$results = array();
		
		foreach ($records as $record)
		{
			$results[] = $this->_update($record['data'], $this->_get_primary_keys($record['data']), $record['foreign_data'], $defaults_override, $do_foreign, $this->_use_transactions);
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_complete();
			if (FALSE === $this->db->trans_status())
			{
				log_message('error', __METHOD__ . ' - Error updating records on table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$this->_use_transactions = $_use_transactions;
		}
		
		return !in_array(FALSE, $results);
	}
	
	final protected function _update($data, $where = NULL, $foreign_data = NULL, $defaults_override = FALSE, $do_foreign = TRUE, $override_transactions = NULL, $escape = TRUE)
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		// FALSE === $this->_clean_primary_keys($data)
		if (FALSE === $this->_clean_data_fields($data) || FALSE === $this->_pre_populate_fields($data, TRUE, (bool) $defaults_override) || FALSE === $this->_preserve_protected_fields($data) || FALSE === $this->_write_format_fields($data) || !is_array($data) || empty($data) || FALSE === $this->_check_primary_keys($data) || FALSE === $this->_check_required_fields('update', $data) || FALSE === $this->_validate_fields($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$_use_transactions = $this->_use_transactions;
			$this->_use_transactions = $override_transactions;
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_start();
		}
		
		if ((is_array($where) && !empty($where)) || (is_string($where) && '' != ($where = trim($where))))
		{
			$this->db->where($where, NULL, (bool) $escape);
		}
		
		if (!is_array($data) || empty($data) || FALSE === $this->db->update($this->_table, $data) && FALSE === $this->_use_transactions)
		{
			log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
			log_message('error', __METHOD__ . ' - Error updating record(s) in table "' . $this->_table . '"!');
			return FALSE;
		}
		
		log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
		$this->_affected_rows = $this->db->affected_rows();
		
		if (is_array($foreign_data) && !empty($foreign_data) && TRUE === (bool) $do_foreign && FALSE === $this->_foreign_table_update($foreign_data, $where, (bool) $defaults_override) && FALSE === $this->_use_transactions)
		{
			log_message('error', __METHOD__ . ' - Error updating record(s) in table "' . $this->_table . '"!');
			return FALSE;
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_complete();
			if (FALSE === $this->db->trans_status())
			{
				log_message('error', __METHOD__ . ' - Error updating record(s) in table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$this->_use_transactions = $_use_transactions;
		}
		
		return TRUE;
	}
	
	final protected function _foreign_table_update($foreign_data, $where = NULL, $defaults_override = FALSE)
	{
		if (!is_array($this->_foreign_tables) || empty($this->_foreign_tables))
		{
			return TRUE;
		}
		
		if (!is_array($this->_primary_keys) || empty($this->_primary_keys))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;		
		}
		
		if (!is_array($foreign_data) || empty($foreign_data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		// Try to get Primary Key(s) Where
		if (is_array($where) && !empty($where))
		{
			$pk_where = array();
			foreach ($this->_primary_keys as $key)
			{
				$pk_where[$key] = isset($where[$key]) ? $where[$key] : NULL;
			}
		}
		elseif (is_string($where) && !empty($where))
		{
			// Don't know if it would be fesable to search for Primary Key(s) and get the value set
		}
		
		// If we couldn't find at least the first Primary Key in the $where, get them from the records
		if (!is_array($pk_where) || is_null($pk_where[$this->_primary_keys[0]]))
		{
			// Get all the records that are going to be deleted
			$records = $this->get($where, $this->_primary_keys);
			
			if (0 === $this->num_rows())
			{
				return TRUE;
			}
			
			$foreign_pk_where = array();
			foreach ($records as $record)
			{
				$pk_where = array();
				foreach ($this->_primary_keys as $key)
				{
					$pk_where[$key] = $record[$key];
				}
				
				$foreign_pk_where[] = $pk_where;
			}
			
			if (!is_array($foreign_pk_where) || empty($foreign_pk_where))
			{
				log_message('error', __METHOD__ . ' - Error updating foreign record(s) for table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		$CI =& get_instance();
		
		foreach ($this->_foreign_tables as $table => $settings)
		{
			if ('' == $table || !is_array($settings) || empty($settings) || '' == $settings['model'] || !is_array($settings['foreign_keys']) || empty($settings['foreign_keys']) || !is_bool($settings['update']))
			{
				log_message('error', __METHOD__ . ' - Required config(s) missing.');
				return FALSE;		
			}
			
			if (!is_array($foreign_data[$table]) || !empty($foreign_data[$table]) || FALSE === $settings['update'])
			{
				continue;
			}
			
			$CI->load->model($settings['model']);
			
			if (is_array($pk_where) && !is_null($pk_where[$this->_primary_keys[0]]))
			{
				$data = $foreign_data[$table];
				$fk_where = array();
				foreach ($settings['foreign_keys'] as $fkey => $pkey)
				{
					if (isset($pk_where[$pkey]))
					{
						$fk_where[$fkey] = $pk_where[$pkey];
						$data[$fkey] = $pk_where[$pkey];
					}
				}
				
				if (FALSE === $CI->$settings['model']->update_where($data, $fk_where, $foreign_data, $defaults_override, $settings['cascade'], $this->_use_transactions) && FALSE === $this->_use_transactions)
				{
					log_message('error', __METHOD__ . ' - Error updating foreign record(s) for table "' . $this->_table . '"!');
					return FALSE;
				}
			}
			elseif (is_array($foreign_pk_where) && !empty($foreign_pk_where))
			{
				foreach ($foreign_pk_where as $pk_where)
				{
					$data = $foreign_data[$table];
					$fk_where = array();
					foreach ($settings['foreign_keys'] as $fkey => $pkey)
					{
						if (isset($pk_where[$pkey]))
						{
							$fk_where[$fkey] = $pk_where[$pkey];
							$data[$fkey] = $pk_where[$pkey];
						}
					}
					
					if (FALSE === $CI->$settings['model']->update_where($data, $fk_where, $foreign_data, $defaults_override, $settings['cascade'], $this->_use_transactions) && FALSE === $this->_use_transactions)
					{
						log_message('error', __METHOD__ . ' - Error updating foreign record(s) for table "' . $this->_table . '"!');
						return FALSE;
					}
				}
			}
			else
			{
				log_message('error', __METHOD__ . ' - Error updating foreign record(s) for table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	final public function delete($where, $do_foreign = TRUE, $override_transactions = NULL, $escape = TRUE)
	{
		if ((!is_array($where) && !is_string($where)) || (is_array($where) && empty($where)) || (is_string($where) && '' == ($where = trim($where))))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		return $this->_delete($where, $do_foreign, $override_transactions, $escape);
	}
	
	final public function delete_all($do_foreign = TRUE, $override_transactions = NULL)
	{
		return $this->_delete(NULL, $do_foreign, $override_transactions);
	}
	
	final protected function _delete($where = NULL, $do_foreign = TRUE, $override_transactions = NULL, $escape = TRUE)
	{
		if (is_null($this->_table) || '' == $this->_table)
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$_use_transactions = $this->_use_transactions;
			$this->_use_transactions = $override_transactions;
		}
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_start();
		}
		
		if (TRUE === (bool) $do_foreign && FALSE === $this->_foreign_table_delete($where) && FALSE === $this->_use_transactions)
		{
			log_message('error', __METHOD__ . ' - Error deleting record(s) in table "' . $this->_table . '"!');
			return FALSE;
		}
		
		if ((is_array($where) && !empty($where)) || (is_string($where) && '' != ($where = trim($where))))
		{
			$this->db->where($where, NULL, (bool) $escape);
			
			if (FALSE === $this->db->delete($this->_table) && FALSE === $this->_use_transactions)
			{
				log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
				log_message('error', __METHOD__ . ' - Error deleting record(s) in table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		else
		{
			if (FALSE === $this->db->empty_table($this->_table) && FALSE === $this->_use_transactions)
			{
				log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
				log_message('error', __METHOD__ . ' - Error deleting record(s) in table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		log_message('debug', __METHOD__ . ' - Last Query: "'. $this->db->last_query() . '"');
		$this->_affected_rows = $this->db->affected_rows();
		
		if (TRUE === $this->_use_transactions)
		{
			$this->db->trans_complete();
			if (FALSE === $this->db->trans_status())
			{
				log_message('error', __METHOD__ . ' - Error deleting record(s) in table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		if (TRUE === $override_transactions || FALSE === $override_transactions)
		{
			$this->_use_transactions = $_use_transactions;
		}
		
		return TRUE;
	}
	
	final protected function _foreign_table_delete($where = NULL)
	{
		if (!is_array($this->_foreign_tables) || empty($this->_foreign_tables))
		{
			return TRUE;
		}
		
		if (!is_array($this->_primary_keys) || empty($this->_primary_keys))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;		
		}
		
		// Try to get Primary Key(s) Where
		if (is_array($where) && !empty($where))
		{
			$pk_where = array();
			foreach ($this->_primary_keys as $key)
			{
				$pk_where[$key] = isset($where[$key]) ? $where[$key] : NULL;
			}
		}
		elseif (is_string($where) && !empty($where))
		{
			// Don't know if it would be fesable to search for Primary Key(s) and get the value set
		}
		
		// If we couldn't find at least the first Primary Key in the $where, get them from the records
		if (!is_array($pk_where) || is_null($pk_where[$this->_primary_keys[0]]))
		{
			// Get all the records that are going to be deleted
			$records = $this->get($where, $this->_primary_keys);
			
			if (0 === $this->num_rows())
			{
				return TRUE;
			}
			
			$pk_where = array();
			foreach ($records as $record)
			{
				foreach ($this->_primary_keys as $key)
				{
					$pk_where[$key][] = $record[$key];
				}
			}
			
			if (!is_array($pk_where) || empty($pk_where))
			{
				log_message('error', __METHOD__ . ' - Error deleting foreign record(s) for table "' . $this->_table . '"!');
				return FALSE;
			}
			
			$foreign_pk_where = array();
			foreach ($pk_where as $field => $values)
			{
				$prefix = count($foreign_pk_where) == 0 ? '' : 'AND ';
				$foreign_pk_where[] = $prefix . $field . ' IN (' . implode(', ', $values) . ')';
			}
			
			$pk_where = implode(' ', $foreign_pk_where);
		}
		
		$CI =& get_instance();
		
		foreach ($this->_foreign_tables as $table => $settings)
		{
			if ('' == $table || !is_array($settings) || empty($settings) || '' == $settings['model'] || !is_array($settings['foreign_keys']) || empty($settings['foreign_keys']) || !is_bool($settings['delete']))
			{
				log_message('error', __METHOD__ . ' - Required config(s) missing.');
				return FALSE;		
			}
			
			if (FALSE === $settings['delete'])
			{
				continue;
			}
			
			$CI->load->model($settings['model']);
			
			if (is_array($pk_where) && !is_null($pk_where[$this->_primary_keys[0]]))
			{
				$fk_where = array();
				foreach ($settings['foreign_keys'] as $fkey => $pkey)
				{
					if (isset($pk_where[$pkey]))
					{
						$fk_where[$fkey] = $pk_where[$pkey];
					}
				}
				
				if (FALSE === $CI->$settings['model']->delete($fk_where, $settings['cascade'], $this->_use_transactions) && FALSE === $this->_use_transactions)
				{
					log_message('error', __METHOD__ . ' - Error deleting foreign record(s) for table "' . $this->_table . '"!');
					return FALSE;
				}
			}
			elseif (is_string($pk_where) && '' != $pk_where)
			{
				if (FALSE === $CI->$settings['model']->delete($pk_where, $settings['cascade'], $this->_use_transactions) && FALSE === $this->_use_transactions)
				{
					log_message('error', __METHOD__ . ' - Error deleting foreign record(s) for table "' . $this->_table . '"!');
					return FALSE;
				}
			}
			else
			{
				log_message('error', __METHOD__ . ' - Error deleting foreign record(s) for table "' . $this->_table . '"!');
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Read Format Fields
	 * 
	 * @access	protected
	 * @param	array	$data
	 * @param	bool	$single
	 * @return	array|bool
	 */
	final protected function _read_format_fields($data, $single = FALSE)
	{
		if (!is_array($this->_read_format_fields) || empty($this->_read_format_fields))
		{
			return $data;
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		if (TRUE === (bool) $single)
		{
			foreach ($this->_read_format_fields as $field => $format)
			{
				if ('' == $field || '' == $format || !isset($data[$field]))
				{
					continue;
				}
				
				if (is_callable(array($this, $format)))
				{
					$data[$field] = call_user_func(array($this, $format), $data[$field]);
				}
				elseif (is_callable($format))
				{
					$data[$field] = call_user_func($format, $data[$field]);
				}
			}
		}
		else
		{
			foreach ($data as $key => $value)
			{
				$data[$key] = $this->_read_format_fields($value, TRUE);
			}
		}
		
		return $data;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Clean Data Fields
	 * 
	 * @access	protected
	 * @param	array	&$data
	 * @return	bool
	 */
	final protected function _clean_data_fields(&$data)
	{
		if (!is_array($this->_fields) || empty($this->_fields))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;		
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		foreach ($data as $field => $value)
		{
			if (!array_key_exists($field, $this->_fields))
			{
				unset($data[$field]);
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Pre-populate Fields
	 * 
	 * @access	protected
	 * @param	array	&$data
	 * @param	bool	$override
	 * @return	bool
	 */
	final protected function _pre_populate_fields(&$data, $update = FALSE, $override = FALSE)
	{
		if (!is_array($this->_fields) || empty($this->_fields))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		foreach ($this->_fields as $field => $settings)
		{
			if ('' == $field || !is_array($settings) || empty($settings))
			{
				continue;
			}
			
			if (TRUE === $update && !isset($data[$field]) && FALSE === $settings['on_update'])
			{
				continue;
			}
			
			if (!isset($data[$field]) || TRUE === (bool) $override || TRUE === (bool) $settings['auto_update'] || TRUE === (bool) $settings['force_default'])
			{
				if ('' != $settings['default'] && is_callable(array($this, $settings['default'])))
				{
					$data[$field] = call_user_func(array($this, $settings['default']));
				}
				elseif ('' != $settings['default'] && is_callable($settings['default']))
				{
					$data[$field] = call_user_func($settings['default']);
				}
				else
				{
					$data[$field] = $settings['default'];
				}
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Preserve Protected Fields
	 * 
	 * @access	protected
	 * @param	array	&$data
	 * @return	bool
	 */
	final protected function _preserve_protected_fields(&$data)
	{
		if (!is_array($this->_protected_fields) || empty($this->_protected_fields))
		{
			return TRUE;
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		foreach ($this->_protected_fields as $field)
		{
			if (isset($data[$field]))
			{
				unset($data[$field]);
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Write Format Fields
	 * 
	 * @access	protected
	 * @param	array	&$data
	 * @return	bool
	 */
	final protected function _write_format_fields(&$data)
	{
		if (!is_array($this->_write_format_fields) || empty($this->_write_format_fields))
		{
			return TRUE;
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		foreach ($this->_write_format_fields as $field => $format)
		{
			if ('' == $field || '' == $format || !isset($data[$field]))
			{
				continue;
			}
			
			if (is_callable(array($this, $format)))
			{
				$data[$field] = call_user_func(array($this, $format), $data[$field]);
			}
			elseif (is_callable($format))
			{
				$data[$field] = call_user_func($format, $data[$field]);
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Validate Fields
	 * 
	 * @access	protected
	 * @param	array	$data
	 * @return	bool
	 */
	final protected function _validate_fields($data)
	{
		if (!is_array($this->_validate_fields) || empty($this->_validate_fields))
		{
			return TRUE;
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$failed_validation = FALSE;
		
		foreach ($this->_validate_fields as $field => $validation)
		{
			if ('' == $field || !isset($data[$field]) || !is_array($validation) || empty($validation))
			{
				continue;
			}
			
			foreach ($validation as $call => $return)
			{
				if ('' == $call)
				{
					continue;
				}
				
				if (is_callable(array($this, $call)) && $return !== call_user_func(array($this, $call), $data[$field]))
				{
					log_message('error', __METHOD__ . ' - Field "' . $field . '" validation failed.');
					$failed_validation = TRUE;
				}
				elseif (is_callable($call) && $return !== call_user_func($call, $data[$field]))
				{
					log_message('error', __METHOD__ . ' - Field "' . $field . '" validation failed.');
					$failed_validation = TRUE;
				}
			}
		}
		
		return !$failed_validation;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Check for Required Field(s)
	 * 
	 * @access	protected
	 * @param	string	$operation
	 * @param	array	$data
	 * @return	bool
	 */
	final protected function _check_required_fields($operation, $data)
	{
		$operation = strtolower(trim($operation));
		
		if (!in_array($operation, array('insert', 'update', 'delete')))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		if (!isset($this->_required_fields[$operation]) || !is_array($this->_required_fields[$operation]) || empty($this->_required_fields[$operation]))
		{
			return TRUE;
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$failed_check = FALSE;
		
		foreach ($this->_required_fields[$operation] as $field)
		{
			if (!isset($data[$field]))
			{
				log_message('error', __METHOD__ . ' - Field "' . $field . '" is required.');
				$failed_check = TRUE;
			}
		}
		
		return !$failed_check;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Check for Primary Key(s)
	 * 
	 * @access	protected
	 * @param	array	$data
	 * @return	bool
	 */
	final protected function _check_primary_keys($data)
	{
		if (FALSE === $this->_write_requires_pks)
		{
			return TRUE;
		}
		
		if (!is_array($this->_primary_keys) || empty($this->_primary_keys))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;		
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		$failed_check = FALSE;
		
		foreach ($this->_primary_keys as $field)
		{
			if (!isset($data[$field]))
			{
				log_message('error', __METHOD__ . ' - Field "' . $field . '" is required.');
				$failed_check = TRUE;
			}
		}
		
		return !$failed_check;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Clean Primary Key(s)
	 * 
	 * @access	protected
	 * @param	array	&$data
	 * @return	bool
	 */
	final protected function _clean_primary_keys(&$data)
	{
		if (TRUE === $this->_write_requires_pks)
		{
			return TRUE;
		}
		
		if (!is_array($this->_primary_keys) || empty($this->_primary_keys))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;		
		}
		
		if (!is_array($data) || empty($data))
		{
			log_message('error', __METHOD__ . ' - All parameters were not passed or correct.');
			return FALSE;
		}
		
		foreach ($data as $field => $value)
		{
			if (in_array($field, $this->_primary_keys))
			{
				unset($data[$field]);
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get Primary Key(s)
	 * 
	 * @access	protected
	 * @param	array	$data
	 * @return	string|array|bool
	 */
	final protected function _get_primary_keys($data = NULL)
	{
		if (!is_array($data) || empty($data))
		{
			return $this->db->insert_id();
		}
		
		if (!is_array($this->_primary_keys) || empty($this->_primary_keys))
		{
			log_message('error', __METHOD__ . ' - Required config(s) missing.');
			return FALSE;		
		}
		
		$primary_keys = array();
		
		foreach ($this->_primary_keys as $field)
		{
			if (isset($data[$field]) && !empty($data[$field]))
			{
				$primary_keys[$field] = $data[$field];
			}
		}
		
		return is_array($primary_keys) && !empty($primary_keys) ? $primary_keys : $this->db->insert_id();
	}
}

/* End of file ORM_Model.php */
/* Location: ./application/core/ORM_Model.php */