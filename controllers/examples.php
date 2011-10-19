<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Examples extends CI_Controller {
	
	public function index()
	{
		echo 'Yep, you should try one of these <br /><ul><li>';
		echo implode('</li><li>', get_class_methods('Dev'));
		echo '</li></ul>';
	}
	
	public function orm()
	{
		$this->load->spark('ci_orm_model/0.0.1');
		$this->load->database();
		$this->load->model('photos_model');
		
		$photos = $this->photos_model->order_by('pid', 'DESC')->get();
		
		echo '<pre>';
		var_dump($photos);
		echo '</prev>';
	}
}

/* End of file examples.php */
/* Location: ./sparks/ci_orm_model/0.0.1/controllers/examples.php */