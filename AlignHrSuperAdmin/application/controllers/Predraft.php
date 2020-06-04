<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Predraft extends Admin_Controller_Secure {
	
	/*------------------------------*/
	/*------------------------------*/	
	public function index()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css',
            

		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'.js',
            

		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('predraft/draft_list');
		$this->load->view('includes/footer');
	}

	public function preAuctionList()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css',
			'asset/plugins/datepicker/css/bootstrap-datetimepicker.css'
		);
		$load['js']=array(
			'asset/js/preauction.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/preauction.js',
			'asset/plugins/datepicker/js/bootstrap-datetimepicker.min.js'
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('predraft/preAuction_list');
		$this->load->view('includes/footer');
	}
	public function preAuctionAdd()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css',
			'asset/plugins/datepicker/css/bootstrap-datetimepicker.css'
		);
		$load['js']=array(
			'asset/js/preauction.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/preauction.js',
			'asset/plugins/datepicker/js/bootstrap-datetimepicker.min.js'
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('predraft/add_form');
		$this->load->view('includes/footer');
	}
}
