<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Config extends API_Controller_Secure
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Utility_model');
	}

	/*
	Name: 			update
	Description: 	Use to get site config.
	URL: 			/config/getConfigs/	
	*/
	public function getConfigs_post()
	{
		$ConfigData = $this->Utility_model->getConfigs(@$this->Post);
		if(!empty($ConfigData)){
			$this->Return['Data'] = $ConfigData['Data'];
		}
	}

	/*
	Name: 			update
	Description: 	Use to update site config.
	URL: 			/config/update/	
	*/
	public function update_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('ConfigTypeGUID', 'ConfigTypeGUID', 'trim|required');
		$this->form_validation->set_rules('ConfigTypeValue', 'ConfigTypeValue', 'trim|required');
		$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
		/* Validation - ends */

		$this->form_validation->validation($this);  /* Run validation */
		
		$this->Utility_model->updateConfig($this->Post['ConfigTypeGUID'],array_merge(array("StatusID"=>$this->StatusID),$this->Post));
		$this->Return['Data'] = array();
	}


	/*
		Name : banner list
		Description : User to get banner list
		URL : /config/banner/
	*/
	public function bannerList_post()
	{
		$data = $this->Utility_model->bannerList('','',TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
		if($data){
			$this->Return['Data'] = $data['Data'];	
		}else{
			$this->Return['Data'] = new StdClass();
		}
		
	}
	
	/*
		Name : add banner
		Description : to add banner
		URL : /config/addBanner/
	*/
	public function addBanner_post()
	{
		$this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
		$this->form_validation->set_rules('MediaGUIDs', 'MediaGUIDs', 'trim|required');
		$this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
		$this->form_validation->validation($this);  /* Run validation */

		$BannerData = $this->Utility_model->addBanner($this->SessionUserID, array_merge($this->Post),$this->StatusID);

		if(!empty($this->Post['MediaGUIDs'])){
			$MediaGUIDsArray = explode(",", $this->Post['MediaGUIDs']);
			foreach($MediaGUIDsArray as $MediaGUID){
				$EntityData = $this->Entity_model->getEntity('E.EntityID MediaID',array('EntityGUID'=>$MediaGUID, 'EntityTypeID'=>14));
				if ($EntityData){
					$this->Media_model->addMediaToEntity($EntityData['MediaID'], $this->SessionUserID,$BannerData['BannerID']);
				}
			}
		}
		
		$this->Return['Data'] = array();
		$this->Return['Message']      	=	"Banner added successfully."; 
	}

	/*
	Name : add version
	Description : to add app version
	URL : /config/addVersion/
	*/
	public function addVersion_post()
	{
		$this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
		$this->form_validation->set_rules('MediaGUIDs', 'MediaGUIDs', 'trim|required');
		$this->form_validation->set_rules('Version', 'Version', 'trim|required|numeric');
		$this->form_validation->set_rules('IsConpulsary', 'IsConpulsary', 'trim|required|in_list[Yes,No]');
		$this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
		$this->form_validation->validation($this);  /* Run validation */


		$BannerData = $this->Utility_model->addAppversion($this->SessionUserID, array_merge($this->Post),$this->StatusID);

		/*if(!empty($this->Post['MediaGUIDs'])){
			$MediaGUIDsArray = explode(",", $this->Post['MediaGUIDs']);
			foreach($MediaGUIDsArray as $MediaGUID){
				$EntityData = $this->Entity_model->getEntity('E.EntityID MediaID',array('EntityGUID'=>$MediaGUID, 'EntityTypeID'=>14));
				if ($EntityData){
					$this->Media_model->addMediaToEntity($EntityData['MediaID'], $this->SessionUserID,$BannerData['BannerID']);
				}
			}
		}*/
		
		$this->Return['Data'] = array();
		$this->Return['Message']      	=	"App added successfully."; 
	}
	


}
