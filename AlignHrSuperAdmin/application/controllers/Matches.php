<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Matches extends Admin_Controller_Secure {
	
	/*------------------------------*/
	/*------------------------------*/	
	public function index()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			'asset/js/'.$this->ModuleData['ModuleName'].'.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/'.$this->ModuleData['ModuleName'].'.js',
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('matches/matches_list');
		$this->load->view('includes/footer');
	}

	public function upcomingMatches()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css'
		);
		$load['js']=array(
			// 'asset/js/'.$this->ModuleData['ModuleName'].'.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/upcomingMatches.js',
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('matches/upcoming_matche_list.php');
		$this->load->view('includes/footer');
	}



}
