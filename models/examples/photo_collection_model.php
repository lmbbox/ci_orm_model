<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

load_ci_orm_model();

class Photo_Collection_Model extends CI_ORM_Model {
	
	public function __construct($check_table = FALSE, $check_fields = FALSE)
	{
		parent::__construct();
		
		$this->_set_write_requires_pks(TRUE);
		$this->_set_use_transactions(FALSE);
		$this->_set_table('photo_collection');
		
		$this->_add_field('cid', array('write_format' => 'intval', 'read_format' => 'intval'));
		$this->_add_field('pid', array('write_format' => 'intval', 'read_format' => 'intval'));
		
		$this->_add_required_field('cid', 'insert');
		$this->_add_required_field('pid', 'insert');
		
		$this->_add_validate_field('cid', array('is_integer' => TRUE, 'empty' => FALSE));
		$this->_add_validate_field('pid', array('is_integer' => TRUE, 'empty' => FALSE));
		
		$this->_add_primary_key('cid');
		$this->_add_primary_key('pid');
		
		$this->_run_check($check_table, $check_fields);
	}
	
	// Just the example table's schema
	public function create_table()
	{
		$sql = "CREATE TABLE `photo_collection` (
					`pid` int(11) NOT NULL,
					`cid` int(11) NOT NULL,
					PRIMARY KEY (`pid`,`cid`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		return $this->query($sql);
	}
}

/* End of file photo_collection_model.php */
/* Location: ./sparks/ci_orm_model/0.0.1/models/photo_collection_model.php */