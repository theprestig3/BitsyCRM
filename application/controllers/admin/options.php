<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Options extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->_check_permissions();
		
		$this->data['folder_name'] = 'admin/settings/';
	}
	
	public function index()
	{
		$settings = $this->data['settings'] = $this->settings->get_settings();
		$options = $this->data['options'] = $this->core->get_settings_as_objs();
		// pagination
		$this->data['base_pagination'] = base_url('admin/options/page/');
		$this->data['total_rows'] = count($options);
		$this->data['per_page'] = 10; 
		$this->data['row_start'] = intval($this->uri->segment(4));
		$this->data['links'] = pagination_links($this->data);
		// end pagination
		$this->data['meta_title'] = 'Global Options';
	}
	
	public function page()
	{
		$settings = $this->data['settings'] = $this->settings->get_settings();
		$options = $this->data['options'] = $this->core->get_settings_as_objs();
		// pagination
		$this->data['base_pagination'] = base_url('admin/options/page/');
		$this->data['total_rows'] = count($options);
		$this->data['per_page'] = 10; 
		$this->data['row_start'] = intval($this->uri->segment(4));
		$this->data['links'] = pagination_links($this->data);
		// end pagination
		$this->data['meta_title'] = 'Global Options';
	}
	
	public function create()
	{
		$this->form_validation->set_rules('option_name', 'Option Name', 'required|trim|xss_clean');
		$this->form_validation->set_rules('option_value', 'Option Value', 'required|trim|xss_clean');
		
		if ($this->form_validation->run() === TRUE)
		{
			$add_setting = $this->settings->add_setting(url_title($this->input->post('option_name'), 'underscore', TRUE), $this->input->post('option_value'));
			
			if ($add_setting === TRUE)
			{
				flashmsg('Option created successfully.', 'success');
				redirect('/admin/options');
			}
			else
			{
				flashmsg('There was an error while trying to create the option.', 'error');
				$this->data['meta_title'] = 'Create Option';
			}
		}
		else
		{
			$this->data['meta_title'] = 'Create Option';
		}
	}
	
	public function edit($option_name = NULL)
	{
		if (empty($option_name))
		{
			flashmsg('You must specify an option to edit.', 'error');
			redirect('/admin/options');
		}
		
		$option_name = $this->data['option_name'] = $option_name;
		$option_value = $this->data['option_value'] = $this->settings->get_setting($option_name);
		
		$this->form_validation->set_rules('option_value', 'Option Value', 'required|trim|xss_clean');
		
		if ($this->form_validation->run() === TRUE)
		{
			// The settings library returns false if nothing is actually being updated
			// so avoid it by checking to see if the value is different or not
			if ($option_value == $this->input->post('option_value'))
			{
				flashmsg('Option edited successfully.', 'success');
				redirect('/admin/options');
			}
			
			$edit_setting = $this->settings->edit_setting($option_name, $this->input->post('option_value'));
			
			if ($edit_setting === TRUE)
			{
				flashmsg('Option edited successfully.', 'success');
				redirect('/admin/options');
			}
			else
			{
				flashmsg('There was an error while trying to update the option.', 'error');
				$this->data['meta_title'] = 'Edit Option';
			}
		}
		else
		{
			$this->data['meta_title'] = 'Edit Option';
		}
	}
	
}