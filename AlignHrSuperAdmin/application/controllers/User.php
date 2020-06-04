<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class User extends Admin_Controller_Secure {
	
	/*------------------------------*/
	/*------------------------------*/	
	public function index()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css',
			'asset/plugins/datepicker/css/bootstrap-datetimepicker.css',
		);
		$load['js']=array(
			'asset/js/ngStorage.min.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/datepicker/js/bootstrap-datetimepicker.js',
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('user/user_list');
		$this->load->view('includes/footer');
	}



}
