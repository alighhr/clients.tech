<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Service extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Service_model');
    }

    /*
      Description: 	Use to get Get single category.
      URL: 			/api/category/getCategories
      Input (Sample JSON):
     */

    public function getServices_post() {
        /* Validation section */
        $this->form_validation->set_rules('ServiceGUID', 'ServiceGUID', 'trim|callback_validateEntityGUID[Service,ServiceID]');
        $this->form_validation->set_rules('CategoryGUID', 'CategoryGUID', 'trim|callback_validateEntityGUID[Category,CategoryID]');
        $this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer');
        $this->form_validation->set_rules('PageSize', 'PageSize', 'trim|integer');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $ServiceData = $this->Service_model->getServices(@$this->Post['Params'], array_merge($this->Post,array("ServiceID" => @$this->ServiceID,"CategoryID" => @$this->CategoryID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($ServiceData)) {
            $this->Return['Data'] = $ServiceData['Data'];
        }
    }

    /*
      Description: 	Use to get Get single category.
      URL: 			/api/category/getCategory
      Input (Sample JSON):
     */

    public function getService_post() {
        /* Validation section */
        
        $this->form_validation->set_rules('ServiceGUID', 'ServiceGUID', 'trim|required|callback_validateEntityGUID[Service,ServiceID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $ServiceData = $this->Service_model->getServices('CategoryGUID,ServiceType,CategoryID,Description,Price,TimeDuration,VariablePrice,VariableTimeDuration', array("ServiceID" => @$this->ServiceID));
        if (!empty($ServiceData)) {
            $this->Return['Data'] = $ServiceData;
        }
    }

}


