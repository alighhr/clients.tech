<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Teams extends API_Controller_Secure
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Category_model');
		$this->load->model('Sports_model');
		$this->load->model('Media_model');
	}



	/*
	Name: 			updateUserInfo
	Description: 	Use to update user profile info.
	URL: 			/user/updateProfile/	
	*/
	public function editTeam_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
		$this->form_validation->set_rules('TeamGUID', 'teamGUID', 'trim|required|callback_validateEntityGUID[Teams,TeamID]');
		
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		
		/* check for media present - associate media with this Post */

		if(!empty($this->Post['MediaGUIDs'])){
			$MediaGUIDsArray = explode(",", $this->Post['MediaGUIDs']);
			foreach($MediaGUIDsArray as $MediaGUID){
				$EntityData=$this->Entity_model->getEntity('E.EntityID MediaID',array('EntityGUID'=>$MediaGUID, 'EntityTypeID'=>9));
				
				if ($EntityData){
					$this->Media_model->addMediaToEntity($EntityData['MediaID'], $this->SessionUserID, $this->TeamID);
				}
				
				$MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID,M.MediaName',
					array("SectionID" => "TeamFlag","MediaID" => $EntityData['MediaID']), FALSE);
			}
		}
				$this->Sports_model->updateTeamFlag($this->TeamID,array('TeamName'=>$this->Post['TeamName'],'TeamFlag'=> @$MediaData['MediaName']));
		/* check for media present - associate media with this Post - ends */


		$TeamData = $this->Sports_model->getTeams('TeamName,TeamNameShort,TeamFlag',
			array("TeamID"=>$this->TeamID));
		$this->Return['Data'] 			= $TeamData;
		$this->Return['Message']      	=	"Team updated successfully."; 
	}


}
