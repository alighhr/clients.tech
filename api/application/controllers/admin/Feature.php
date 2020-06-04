<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feature extends API_Controller_Secure
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Feature_model');
	}



	/*
	Description: 	Use to add new category
	URL: 			/api_admin/category/add	
	*/
	public function add_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
		$this->form_validation->set_rules('Features', 'Features', 'trim|required');
		$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		$FeatureData = $this->Feature_model->addFeature($this->SessionUserID, $this->Post['Features'], $this->StatusID);
		if($FeatureData){
			$this->Return['Data']['FeatureGUID'] = $FeatureData['FeatureGUID'];
			$this->Return['Message']      	=	"New feature added successfully."; 
		}
	}






	/*
	Name: 			updateUserInfo
	Description: 	Use to update user profile info.
	URL: 			/user/updateProfile/	
	*/
	public function editFeature_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
		$this->form_validation->set_rules('FeatureGUID', 'FeatureGUID', 'trim|required|callback_validateEntityGUID[Feature,FeatureID]');
		$this->form_validation->set_rules('Features', 'Features', 'trim|required');
		$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');

		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		
		$this->Feature_model->editFeature($this->FeatureID, array('Features'=>$this->Post['Features'], 'StatusID'=>$this->StatusID));

		$FeatureData = $this->Feature_model->getFeatures('',
			array("FeatureID"=>$this->FeatureID));
		$this->Return['Data'] = $FeatureData;
		$this->Return['Message']      	=	"Feature updated successfully."; 
	}


	/*
	Description: 	use to get list of filters
	URL: 			/api_admin/entity/getFilterData	
	*/
	public function getFilterData_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('ParentCategoryGUID', 'Parent Category', 'trim|callback_validateEntityGUID[Category,ParentCategoryID]');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */


		$CategoryTypes = $this->Category_model->getCategoryTypes('',array("ParentCategoryID"=>@$this->ParentCategoryID),true,1,250);
		if($CategoryTypes){
			$Return['CategoryTypes'] = $CategoryTypes['Data']['Records'];			
		}
		$this->Return['Data'] = $Return;
	}
}

