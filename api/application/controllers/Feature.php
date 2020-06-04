<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Feature extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Feature_model');
    }

    /*
      Description: 	Use to get Get Attributes.
      URL: 			/api/category/getAttributes
      Input (Sample JSON):
     */

    public function getAttributes_post() {
        /* Validation section */
        $this->form_validation->set_rules('CategoryGUID', 'CategoryGUID', 'trim|required|callback_validateEntityGUID[Category,CategoryID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $AttributesData = $this->Category_model->getAttributes('
			E.EntityGUID AttributeGUID,
			A.AttributeName,
			A.AttributeValues
			', array("EntityID" => $this->CategoryID), TRUE, 1, 25);
        if (!empty($AttributesData)) {
            $this->Return['Data'] = $AttributesData['Data'];
        }
    }

    /*
      Description: 	Use to get Get single category.
      URL: 			/api/category/getCategories
      Input (Sample JSON):
     */

    public function getFeatures_post() {
        /* Validation section */
        $this->form_validation->set_rules('FeatureGUID', 'FeatureGUID', 'trim|callback_validateEntityGUID[Feature,FeatureID]');
        $this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer');
        $this->form_validation->set_rules('PageSize', 'PageSize', 'trim|integer');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $FeatureData = $this->Feature_model->getFeatures('Features', array("FeatureID" => @$this->FeatureID), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($FeatureData)) {
            $this->Return['Data'] = $FeatureData['Data'];
        }
    }

    /*
      Description: 	Use to get Get single category.
      URL: 			/api/category/getCategory
      Input (Sample JSON):
     */

    public function getFeature_post() {
        /* Validation section */
        $this->form_validation->set_rules('FeatureGUID', 'FeatureGUID', 'trim|required|callback_validateEntityGUID[Feature,FeatureID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $FeatureData = $this->Feature_model->getFeatures('Features', array("FeatureID" => @$this->FeatureID));
        if (!empty($FeatureData)) {
            $this->Return['Data'] = $FeatureData;
        }
    }

}

