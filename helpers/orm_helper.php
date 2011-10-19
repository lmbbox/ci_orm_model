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
 * @version		Version 0.0.1
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter ORM Model Helpers
 *
 * @package		CodeIgniter ORM Model
 * @subpackage	Helpers
 * @category	Helpers
 * @author		LMB^Box (Thomas Montague)
 * @link		http://codeigniter.lmbbox.com/user_guide/helpers/orm_model.html
 */

// ------------------------------------------------------------------------

/**
 * Load the CI_ORM_Model class
 * 
 * @access	public
 * @return	void
 */
if ( ! function_exists('load_ci_orm_model'))
{
	function load_ci_orm_model()
	{
		load_class('ORM_Model', str_replace(realpath(BASEPATH . '../' . SPARKPATH) . '/', '../' . SPARKPATH, realpath(dirname(__FILE__) . '/../')) . '/core');
	}
}

/* End of file orm_helper.php */
/* Location: ./sparks/ci_orm_model/0.0.1/helpers/orm_helper.php */