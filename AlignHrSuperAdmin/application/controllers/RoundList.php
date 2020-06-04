<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class RoundList extends Admin_Controller_Secure {
	
	/*------------------------------*/
	/*------------------------------*/	
	public function index()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css',
			'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/css/bootstrap-datepicker3.min.css'
		);
		$load['js']=array(
			'asset/js/series.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/js/bootstrap-datepicker.min.js', 
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('series/round_list');
		$this->load->view('includes/footer');
	}



}
