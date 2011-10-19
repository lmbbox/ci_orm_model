<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Examples extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		$this->load->spark('ci_orm_model/0.0.1');
		$this->load->database();
		$this->load->model('collections_model');
		$this->load->model('photos_model');
		$this->load->model('photo_collection_model');
		$this->load->model('photo_meta_model');
		
		log_message('debug', 'Examples Controller Initialized');
	}
	
	public function index()
	{
		echo "These aren't the droids we're looking for. Move along... move along.";
	}
	
	public function collections($cid = NULL)
	{
		// Get Collections
		$collections = $this->collections_model->get();
		
		
		// Insert Collection
		$collection = array();
		$collection['title'] = $this->input->post('title');
		$this->collections_model->insert($collection);
		
		
		// Update Collection
		$collection = $this->collections_model->where('cid', $cid)->get_record();
		$collection['title'] = $this->input->post('title');
		$this->collections_model->update($collection);
		
		
		// Delete Collection
		$this->collections_model->delete(array('cid' => $cid));
	}
	
	public function photos($pid = NULL)
	{
		// Get Photos
		$photos = $this->photos_model->order_by('pid', 'DESC')->get();
		$photos = $this->photos_model->order_by('pid', 'DESC')->limit(25, 0)->get();
		
		
		// Get Photo
		$photo = $this->photos_model->where('pid', $pid)->get_record();
		
		
		// Update Photo
		$photo = array();
		$photo['pid'] = $pid;
		$photo['title'] = $this->input->post('title');
		$this->photos_model->update($photo);
		
		
		// Update Photo Collections
		$photo_collections = array();
		foreach ($this->input->post('photo_collections') as $collection)
		{
			$photo_collection = array();
			$photo_collection['pid'] = $pid;
			$photo_collection['cid'] = $collection;
			
			$photo_collections[] = array('data' => $photo_collection, 'foreign_data' => NULL);
		}
		
		$this->photo_collection_model->delete(array('pid' => $pid));
		$this->photo_collection_model->insert_all($photo_collections);
		
		
		// Update Photo Meta
		$meta_names = $this->input->post('photo_meta_names');
		$meta_values = $this->input->post('photo_meta_values');
		
		$photo_metas = array();
		foreach ($meta_names as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}
			
			$photo_meta = array();
			$photo_meta['pid'] = $pid;
			$photo_meta['name'] = $value;
			$photo_meta['value'] = $meta_values[$key];
			
			$photo_metas[] = array('data' => $photo_meta, 'foreign_data' => NULL);
		}
		
		$this->photo_meta_model->delete(array('pid' => $pid));
		$this->photo_meta_model->insert_all($photo_metas);
	}
}

/* End of file examples.php */
/* Location: ./sparks/ci_orm_model/0.0.1/controllers/examples.php */