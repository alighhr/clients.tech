<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Recovery extends API_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('Recovery_model');
	}

	/*
	Name: 			Recovery Password
	Description: 	Use to set OTP and send to user for password recovery.
	URL: 			/api/recovery
	*/
	public function index_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('Keyword', 'Keyword', 'trim|required'. ($this->input->post('type' == 'Phone' ? '|numeric':'')));
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */
		$Recover = $this->Recovery_model->recovery($this->Post['Keyword']);

		if($Recover != false){
			$this->Return['ResponseCode'] 	=	200;
			$this->Return['Message']      	=	$Recover; 
		}else{
			$this->Return['ResponseCode'] 	=	500;
			$this->Return['Message']      	=	"Please enter your registered email / Phone number.";
		}
	}

	/*
	Name: 			Set Password From OTP
	Description: 	Use to set user password from OTP.
	URL: 			/api/recovery/setPassword
	*/
	public function setPassword_post()
	{
		/* Validation section */
		$this->form_validation->set_rules('OTP', 'OTP', 'trim|required|callback_validateToken[1]');
		$this->form_validation->set_rules('Password', 'Password', 'trim|required');
		//$this->form_validation->set_rules('Retype', 'Confirm Password', 'trim|matches[Password]');
		$this->form_validation->validation($this);  /* Run validation */		
		/* Validation - ends */

		$UserID 						= 	$this->Recovery_model->verifyToken($this->Post['OTP'],1);
		if($this->Users_model->updateUserLoginInfo($UserID, array("Password"=>$this->Post['Password']), DEFAULT_SOURCE_ID)){
			$this->Recovery_model->deleteToken($this->Post['OTP'],1); /*delete token*/
			$this->Return['Message']      	=	"New password has been set, please login to get access your account."; 	
		}
	}


}
