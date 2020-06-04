<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Service extends API_Controller_Secure
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Service_model');
	}



	/*
	Description: 	Use to add new category
	URL: 			/api_admin/category/add	
	*/
	public function add_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
		$this->form_validation->set_rules('ServiceType', 'Service Type', 'trim|required|in_list[Fixed,Variable]');
		$this->form_validation->set_rules('CategoryGUID', 'Category', 'trim|required|callback_validateEntityGUID[Category,CategoryID]');
		$this->form_validation->set_rules('Price', 'Price', 'trim|required');
		$this->form_validation->set_rules('TimeDuration', 'TimeDuration', 'trim|required');
		$this->form_validation->set_rules('FeatureGUID[]', 'FeatureGUID', 'trim|required|callback_validateEntityGUID[Feature,FeatureID]');
		
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		

		$insert = $this->Service_model->addService($this->Post, $this->SessionUserID, $this->CategoryID);
		$all_features = count($this->input->post('FeatureGUID'));
        for ($i = 0; $i < $all_features; $i++) {
			$FeatureIDs = $this->Entity_model->getEntity('E.EntityID', array('EntityGUID' => $this->Post('FeatureGUID')[$i], 'EntityTypeName' => "Feature"));
            $FeatureID = $FeatureIDs['EntityID'];
			$InsertFeature = $this->Service_model->addServiceFeature($FeatureID, $insert);
            
		}
		
		if(!empty($this->Post['MediaGUIDs'])){
			$MediaGUIDsArray = explode(",", $this->Post['MediaGUIDs']);
			foreach($MediaGUIDsArray as $MediaGUID){
				$EntityData = $this->Entity_model->getEntity('E.EntityID MediaID',array('EntityGUID'=>$MediaGUID, 'EntityTypeID'=>16));
				if ($EntityData){
					$this->Media_model->addMediaToEntity($EntityData['MediaID'], $this->SessionUserID,$insert);
				}
			}
		}

		if (!$insert) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Service created successfully.";
        }
	}






	/*
	Name: 			updateUserInfo
	Description: 	Use to update user profile info.
	URL: 			/user/updateProfile/	
	*/
	public function editService_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
		$this->form_validation->set_rules('ServiceGUID', 'ServiceGUID', 'trim|required|callback_validateEntityGUID[Service,ServiceID]');
		//$this->form_validation->set_rules('ServiceType', 'Service Type', 'trim|required|in_list[Fixed,Variable]');
		$this->form_validation->set_rules('CategoryGUID', 'Category', 'trim|required|callback_validateEntityGUID[Category,CategoryID]');
		$this->form_validation->set_rules('Price', 'Price', 'trim|required');
		$this->form_validation->set_rules('TimeDuration', 'TimeDuration', 'trim|required');
		$this->form_validation->set_rules('FeatureGUID[]', 'FeatureGUID', 'trim|required|callback_validateEntityGUID[Feature,FeatureID]');

		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		if (!empty($this->Post['MediaGUID'])) {
            $EntityData = $this->Entity_model->getEntity('E.EntityID MediaID', array('EntityGUID' => $this->Post['MediaGUID'], 'EntityTypeID' => 16));
            if ($EntityData) {
                $this->Media_model->addMediaToEntity($EntityData['MediaID'], $this->SessionUserID, $this->ServiceID);
            }
        }

		$Update = $this->Service_model->editService($this->ServiceID,$this->CategoryID,$this->Post);
		$all_features = count($this->input->post('FeatureGUID'));
		
		$DeleteFeature = $this->Service_model->deleteServiceFeature($this->ServiceID);
        for ($i = 0; $i < $all_features; $i++) {
			$FeatureIDs = $this->Entity_model->getEntity('E.EntityID', array('EntityGUID' => $this->Post('FeatureGUID')[$i], 'EntityTypeName' => "Feature"));
            $FeatureID = $FeatureIDs['EntityID'];
			$UpdateFeature = $this->Service_model->addServiceFeature($FeatureID, $this->ServiceID);
            
        }
		
		$ServiceData = $this->Service_model->getServices('',
			array("ServiceID"=>$this->ServiceID));
		$this->Return['Data'] = $ServiceData;
		$this->Return['Message']      	=	"Service updated successfully."; 
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


