<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Page extends API_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Page_model');
	}



	/*
	Name: 			getPage
	Description: 	Use to get page data.
	*/
	public function getPage_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('PageGUID', 'PageGUID', 'trim|required');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		$PageData = $this->Page_model->getPage('*',
			array("PageGUID" => $this->Post['PageGUID'])
		);
		if($PageData){
			$PageData['Content'] = htmlentities($PageData['Content']);
			$this->Return['Data'] = $PageData;
		}	
	}



	/*
	Name: 			savePage
	Description: 	Use to update page data.	
	*/
	public function savePage_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('PageGUID', 'PageGUID', 'trim|required');
		$this->form_validation->set_rules('Title', 'Page Title', 'trim');
		$this->form_validation->set_rules('Content', 'Content', 'trim');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		
		$this->Page_model->editPage($this->Post['PageGUID'], array('Title'=>$this->Post['Title'], 'Content'=>$this->Post['Content']));

		$this->Return['Message']      	=	"Updated successfully."; 
	}



}
