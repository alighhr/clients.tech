<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class AuctionDrafts extends Admin_Controller_Secure {
    /* ------------------------------ */
    /* ------------------------------ */

    public function index() { 
        $load['css'] = array(
            'asset/plugins/chosen/chosen.min.css',
            'asset/plugins/datepicker/css/bootstrap-datetimepicker.css'
        );
        $load['js'] = array(
            'asset/js/' . $this->ModuleData['ModuleName'] . '.js',
            'asset/plugins/chosen/chosen.jquery.min.js',
            'asset/plugins/jquery.form.js',
            'asset/js/' . $this->ModuleData['ModuleName'] . '.js',
            'asset/plugins/datepicker/js/bootstrap-datetimepicker.min.js'
        );

        $this->load->view('includes/header', $load);
        $this->load->view('includes/menu');
        $this->load->view('auctionDrafts/drafts_list');
        $this->load->view('includes/footer');
    }

    public function auctionDraftsAdd()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css',
			'asset/plugins/datepicker/css/bootstrap-datetimepicker.css'
		);
		$load['js']=array(
			'asset/js/auctionDrafts.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/auctionDrafts.js',
			'asset/plugins/datepicker/js/bootstrap-datetimepicker.min.js'
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('auctionDrafts/add_form');
		$this->load->view('includes/footer');
    }
    
    public function auctionDraftsEdit()
	{
		$load['css']=array(
			'asset/plugins/chosen/chosen.min.css',
			'asset/plugins/datepicker/css/bootstrap-datetimepicker.css'
		);
		$load['js']=array(
			'asset/js/auctionDrafts.js',
			'asset/plugins/chosen/chosen.jquery.min.js',
			'asset/plugins/jquery.form.js',
			'asset/js/auctionDrafts.js',
			'asset/plugins/datepicker/js/bootstrap-datetimepicker.min.js'
		);	

		$this->load->view('includes/header',$load);
		$this->load->view('includes/menu');
		$this->load->view('auctionDrafts/edit_form');
		$this->load->view('includes/footer');
	}

}
