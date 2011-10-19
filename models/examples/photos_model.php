<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

load_ci_orm_model();

class Photos_Model extends CI_ORM_Model {
	
	public function __construct($check_table = FALSE, $check_fields = FALSE)
	{
		parent::__construct();
		
		$this->_set_write_requires_pks(TRUE);
		$this->_set_use_transactions(FALSE);
		$this->_set_table('photos');
		
		$this->_add_field('pid', array('write_format' => 'intval', 'read_format' => 'intval'));
		$this->_add_field('title');
		$this->_add_field('filename');
		$this->_add_field('original_file');
		
		$this->_add_required_field('pid', 'insert');
		$this->_add_required_field('title', 'insert');
		
		$this->_add_validate_field('pid', array('is_integer' => TRUE, 'empty' => FALSE));
		$this->_add_validate_field('title', 'is_string');
		$this->_add_validate_field('filename', 'is_string');
		$this->_add_validate_field('original_file', 'is_string');
		
		$this->_add_protected_field('original_file');
		$this->_add_primary_key('pid');
		
		$this->_add_foreign_table('photo_meta', 'photo_meta_model', array('pid' => 'pid'));
		$this->_add_foreign_table('photo_collection', 'photo_collection_model', array('pid' => 'pid'));
		
		$this->_run_check($check_table, $check_fields);
	}
	
	// Just the example table's schema
	public function create_table()
	{
		$sql = "CREATE TABLE `photos` (
					`pid` int(11) NOT NULL AUTO_INCREMENT,
					`title` varchar(255) NOT NULL DEFAULT '',
					`filename` varchar(255) NOT NULL DEFAULT '',
					`original_file` varchar(255) NOT NULL DEFAULT '',
					PRIMARY KEY (`pid`)
				) ENGINE=MyISAM AUTO_INCREMENT=10116352 DEFAULT CHARSET=utf8;";
		return $this->query($sql);
	}
}

/* End of file photos_model.php */
/* Location: ./sparks/ci_orm_model/0.0.1/models/photos_model.php */