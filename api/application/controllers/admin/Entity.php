<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Entity extends API_Controller_Secure
{
	function __construct()
	{
		parent::__construct();
	}


	/*
	Description: 	Use to update order of category
	URL: 			/api_admin/entity/setOrderProduct
	*/
	public function setOrder_post()
	{
		$this->Entity_model->setOrder($this->Post['sectionsid']);
	}

	/*
	Description: 	get flagged inappropriate content list
	URL: 			/api_admin/entity/getFlagged	
	*/
	public function getFlagged_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer');
		$this->form_validation->set_rules('PageSize', 'PageSize', 'trim|integer');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		$FlaggedData=$this->Entity_model->getFlagged('
			E.EntityGUID,
			E.FlaggedCount,
			CONCAT_WS(" ",U.FirstName,U.LastName) FullName,
			IF(U.ProfilePic IS NULL,CONCAT("'.PROFILE_PICTURE_URL.'","default.jpg"),CONCAT("'.PROFILE_PICTURE_URL.'",U.ProfilePic)) AS ProfilePic,
			ET.EntityTypeName,
			EF.Text1 Reason,
			EF.Text2 Detail,
			EF.EntryDate,
			', array() , TRUE,  @$this->Post['PageNo'], @$this->Post['PageSize']);
		if($FlaggedData){
			$this->Return['Data'] = $FlaggedData['Data'];
		}
	}

	/*
	Description: 	get flagged inappropriate content
	URL: 			/api_admin/entity/getFlaggedContent	
	*/
	public function getFlaggedContent_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('EntityGUID', 'EntityGUID', 'trim|required|callback_validateEntityGUID');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		$FlaggedData=$this->Entity_model->getFlagged('
			E.EntityGUID,
			E.FlaggedCount,
			CONCAT_WS(" ",U.FirstName,U.LastName) FullName,
			IF(U.ProfilePic IS NULL,CONCAT("'.PROFILE_PICTURE_URL.'","default.jpg"),CONCAT("'.PROFILE_PICTURE_URL.'",U.ProfilePic)) AS ProfilePic,
			ET.EntityTypeName,
			EF.Text1 Reason,
			EF.Text2 Detail,
			EF.EntryDate,
			', array('EntityID' => $this->EntityID));
		if($FlaggedData){
			$this->Return['Data'] = $FlaggedData;
		}
	}


	/*
	Description: 	Use to delete entity.
	URL: 			/api_admin/entity/delete/	
	*/
	public function delete_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('EntityGUID', 'EntityGUID', 'trim|required|callback_validateEntityGUID');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		$this->Entity_model->deleteEntity($this->EntityID);
		$this->Return['Message']      	=	"Deleted successfully.";

	}

	/*
	Description: 	Use to delete entity (multiple).
	URL: 			/api_admin/entity/deleteSelected/	
	*/
	public function deleteSelected_post()
	{

		if(empty($this->Post['select-all-checkbox'])){
			$this->Return['ResponseCode'] 	=	500;
			$this->Return['Message']      	=	"Please select atleast one records'.";
		}else{
			foreach($this->Post['select-all-checkbox'] as $EntityGUID){
				$EntityData	= $this->Entity_model->getEntity('E.EntityID',array('EntityGUID'=>$EntityGUID));
				if ($EntityData){
					$this->Entity_model->deleteEntity($EntityData['EntityID']);		
				}
				$this->Return['Message']      	=	"Deleted successfully.";
			}
		}
	}


}
