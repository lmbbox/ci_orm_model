<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

load_ci_orm_model();

class Photo_Meta_Model extends CI_ORM_Model {
	
	public function __construct($check_table = FALSE, $check_fields = FALSE)
	{
		parent::__construct();
		
		$this->_set_use_transactions(FALSE);
		$this->_set_table('photo_meta');
		
		$this->_add_field('id', array('write_format' => 'intval', 'read_format' => 'intval'));
		$this->_add_field('pid', array('write_format' => 'intval', 'read_format' => 'intval'));
		$this->_add_field('name');
		$this->_add_field('value');
		
		$this->_add_required_field('pid', 'insert');
		$this->_add_required_field('name', 'insert');
		
		$this->_add_validate_field('id', array('is_integer' => TRUE, 'empty' => FALSE));
		$this->_add_validate_field('pid', array('is_integer' => TRUE, 'empty' => FALSE));
		$this->_add_validate_field('name', 'is_string');
		$this->_add_validate_field('value', 'is_string');
		
		$this->_add_primary_key('id');
		
		$this->_run_check($check_table, $check_fields);
	}
	
	// Just the example table's schema
	public function create_table()
	{
		$sql = "CREATE TABLE `photo_meta` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`pid` int(11) NOT NULL,
					`name` varchar(255) NOT NULL DEFAULT '',
					`value` varchar(255) NOT NULL DEFAULT '',
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM AUTO_INCREMENT=40568 DEFAULT CHARSET=utf8;";
		return $this->query($sql);
	}
}

/* End of file photo_meta_model.php */
/* Location: ./application/models/photo_meta_model.php */