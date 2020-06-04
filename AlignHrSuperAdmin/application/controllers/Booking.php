<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Booking extends Admin_Controller_Secure
{

    /*------------------------------*/
    /*------------------------------*/
    public function index()
    {
        $load['css'] = array(
            'asset/plugins/chosen/chosen.min.css',
        );
        $load['js'] = array(
            'asset/js/ngStorage.min.js',
            'asset/js/' . $this->ModuleData['ModuleName'] . '.js',
            'asset/plugins/chosen/chosen.jquery.min.js',
            'asset/plugins/jquery.form.js',
        );

        $this->load->view('includes/header', $load);
        $this->load->view('includes/menu');
        $this->load->view('booking/booking_list');
        $this->load->view('includes/footer');
    }

}
