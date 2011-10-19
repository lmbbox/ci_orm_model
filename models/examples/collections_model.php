<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

load_ci_orm_model();

class Collections_Model extends CI_ORM_Model {
	
	public function __construct($check_table = FALSE, $check_fields = FALSE)
	{
		parent::__construct();
		
		$this->_set_use_transactions(FALSE);
		$this->_set_table('collections');
		
		$this->_add_field('cid', array('write_format' => 'intval', 'read_format' => 'intval'));
		$this->_add_field('title');
		
		$this->_add_required_field('cid', 'insert');
		$this->_add_required_field('title', 'insert');
		
		$this->_add_validate_field('cid', array('is_integer' => TRUE, 'empty' => FALSE));
		$this->_add_validate_field('title', 'is_string');
		
		$this->_add_primary_key('cid');
		
		$this->_add_foreign_table('photo_collection', 'photo_collection_model', array('cid' => 'cid'));
		
		$this->_run_check($check_table, $check_fields);
	}
	
	// Just the example table's schema
	public function create_table()
	{
		$sql = "CREATE TABLE `collections` (
					`cid` int(11) NOT NULL AUTO_INCREMENT,
					`title` varchar(255) NOT NULL DEFAULT '',
					PRIMARY KEY (`cid`)
				) ENGINE=MyISAM AUTO_INCREMENT=182 DEFAULT CHARSET=utf8;";
		return $this->query($sql);
	}
}

/* End of file collections_model.php */
/* Location: ./sparks/ci_orm_model/0.0.1/models/collections_model.php */