<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
    }

    /*
      Name: 			updateUserInfo
      Description: 	Use to update user profile info.
      URL: 			/user/accountDeactivate/
     */

    public function accountDeactivate_post() {
        $this->Entity_model->updateEntityInfo($this->SessionUserID, array("StatusID" => 6));
        $this->Users_model->deleteSessions($this->SessionUserID);
        $this->Return['Message'] = "Your account has been deactivated.";
    }

    /*
      Name: 			toggleAccountDisplay
      Description: 	Use to hide account to others.
      URL: 			/user/toggleAccountDisplay/
     */

    public function toggleAccountDisplay_post() {
        $UserData = $this->Users_model->getUsers('StatusID', array('UserID' => $this->SessionUserID));
        if ($UserData['StatusID'] == 2) {
            $this->Entity_model->updateEntityInfo($this->SessionUserID, array("StatusID" => 8));
        } elseif ($UserData['StatusID'] == 8) {
            $this->Entity_model->updateEntityInfo($this->SessionUserID, array("StatusID" => 2));
        }
    }

    /*
      Name: 			search
      Description: 	Use to search users
      URL: 			/api/users/search
     */

    public function search_post() {
        /* Validation section */
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Friend,Follow,Followers,Blocked]');
        $this->form_validation->set_rules('UserTypeID', 'UserTypeID', 'trim|in_list[2,3]');
        $this->form_validation->set_rules('Latitude', 'Latitude', 'trim');
        $this->form_validation->set_rules('Longitude', 'Longitude', 'trim');
        $this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer');
        $this->form_validation->set_rules('PageSize', 'PageSize', 'trim|integer');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $UserData = $this->Users_model->getUsers('Rating,ProfilePic', array(
            'SessionUserID' => $this->SessionUserID,
            'Keyword' => @$this->Post['Keyword'],
            'Filter' => @$this->Post['Filter'],
            'SpecialtyGUIDs' => @$this->Post['SpecialtyGUIDs'],
            'UserTypeID' => @$this->Post['UserTypeID'],
            'StatusID' => 2
                ), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if ($UserData) {
            $this->Return['Data'] = $UserData['Data'];
        }
    }

    /*
      Name: 			getProfile
      Description: 	Use to get user profile info.
      URL: 			/api/user/getProfile
     */

    public function getProfile_post() {
        /* Validation section */
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        /* check for self profile or other user profile by GUID */
        $this->UserID = (!empty($this->UserID) ? $this->UserID : $this->SessionUserID);
        /* Basic fields to select */
        $UserData = $this->Users_model->getUsers((!empty($this->Post['Params']) ? $this->Post['Params'] : ''), array('UserID' => $this->UserID));
        if ($UserData) {
            if ($this->Post['WithdrawText'] == "Yes") {
                $MaximumWithdrawalLimitBank = $this->db->query("SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = 'MaximumWithdrawalLimitBank'")->row()->ConfigTypeValue;
                $MinimumWithdrawalLimitBank = $this->db->query("SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = 'MinimumWithdrawalLimitBank'")->row()->ConfigTypeValue;
                $MaximumWithdrawalLimitPaytm = $this->db->query("SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = 'MaximumWithdrawalLimitPaytm'")->row()->ConfigTypeValue;
                $MinimumWithdrawalLimitPaytm = $this->db->query("SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = 'MinimumWithdrawalLimitPaytm'")->row()->ConfigTypeValue;
                //$UserData['Message'] = "1.User can withdraw (minimum " . $MinimumWithdrawalLimitBank . " and maximum of INR " . $MaximumWithdrawalLimitBank . " per day)\n2.User can withdraw Paytm (minimum " . $MinimumWithdrawalLimitPaytm . " and maximum of INR " . $MaximumWithdrawalLimitPaytm . " per day)\n3.User can withdraw a minimum of INR " . $MinimumWithdrawalLimitBank . " and maximum  amount INR " . $MaximumWithdrawalLimitBank . " in a day as apart of instant withdraw .instant withdraw amount credited your account within 2-3 working days.\n4.Withdrawal timings are 11:00 AM to 8(in case of instant withdraw)";
                $UserData['Message'] = "1.In Bank User can withdraw (minimum ".$MinimumWithdrawalLimitBank." and maximum of INR ".$MaximumWithdrawalLimitBank." per day)\n2.In Paytm Wallet User Can Withdraw (minimum ".$MinimumWithdrawalLimitPaytm." and maximum of INR ".$MaximumWithdrawalLimitPaytm." per day)\n3.Normal Bank Withdrawal a minimum of INR ".$MinimumWithdrawalLimitBank." and maximum  amount INR ". $MaximumWithdrawalLimitBank." in a day. Gets Processed In your bank account within 2-3 Working Days.\n4.Withdrawal timings are 11.00AM to 8.00PM (In case of instant withdrawal)";
            }
            $this->Return['Data'] = $UserData;
        }
    }

    /*
      Name: 			updateUserInfo
      Description: 	Use to update user profile info.
      URL: 			/user/updateProfile/
     */

    public function updateUserInfo_post() {
        /* Validation section */
        $this->form_validation->set_rules('Email', 'Email', 'trim|valid_email|callback_validateEmail[' . $this->Post['SessionKey'] . ']');
        $this->form_validation->set_rules('Username', 'Username', 'trim|alpha_dash|callback_validateUsername[' . $this->Post['SessionKey'] . ']');

        $this->form_validation->set_rules('Gender', 'Gender', 'trim|in_list[Male,Female,Other]');
        $this->form_validation->set_rules('BirthDate', 'BirthDate', 'trim|callback_validateDate');

        if (@$this->Post['PhoneNumber']) {
            $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim|is_unique[tbl_users.PhoneNumber]|callback_validatePhoneNumber[' . $this->Post['SessionKey'] . ']');
        }
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $this->Users_model->updateUserInfo($this->SessionUserID, $this->Post);
        $this->Return['Data'] = $this->Users_model->getUsers('FirstName,LastName,Email,ProfilePic,UserTypeID,UserTypeName', array("UserID" => $this->SessionUserID));
        $this->Return['Message'] = "Profile successfully updated.";
    }

    /**
     * Function Name: validatePhoneVerification
     * Description:   To validate same phone already verified
     */
    public function validatePhoneVerification($PhoneNumber) {

        $User = $this->Users_model->getUsers('PhoneNumber', array('PhoneNumber' => $PhoneNumber), FALSE, 1, 1);
        if (!empty($User)) {
            $this->form_validation->set_message('validatePhoneVerification', 'Current phone number already verified.');
            return FALSE;
        }
        return TRUE;
    }

    /*
      Name: 			changePassword
      Description: 	Use to change account login password by user.
      URL: 			/api/users/changePassword
     */

    public function changePassword_post() {
        /* Validation section */
        if (!$this->input->post('UserGUID')) {
            $this->form_validation->set_rules('CurrentPassword', 'Current Password', 'trim|callback_validatePassword');
        }
        $this->form_validation->set_rules('Password', 'Password', 'trim|required');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        if (!empty($this->input->post('UserGUID'))) {
            $Request = $this->Users_model->updateUserLoginInfo($this->UserID, array("Password" => $this->Post['Password']), DEFAULT_SOURCE_ID);
        } else {
            $Request = $this->Users_model->updateUserLoginInfo($this->SessionUserID, array("Password" => $this->Post['Password']), DEFAULT_SOURCE_ID);
        }
        if ($Request) {
            $this->Return['Message'] = "New password has been set.";
        }
    }

    /*
      Name: 			referEarn
      Description: 	Use to refer & earn user
      URL: 			/api/users/referEarn
     */

    public function referEarn_post() {
        /* Validation section */
        $this->form_validation->set_rules('ReferType', 'Refer Type', 'trim|required|in_list[Phone,Email]');
        $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim' . (!empty($this->Post['ReferType']) && $this->Post['ReferType'] == 'Phone' ? '|required|callback_validateAlreadyRegistered[Phone]' : ''));
        $this->form_validation->set_rules('Email', 'Email', 'trim' . (!empty($this->Post['ReferType']) && $this->Post['ReferType'] == 'Email' ? '|required|valid_email|callback_validateAlreadyRegistered[Email]' : ''));

        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $this->Users_model->referEarn($this->Post, $this->SessionUserID);
        $this->Return['Message'] = "User successfully invited.";
    }

    public function InviteContest_post() {
        /* Validation section */
        $this->form_validation->set_rules('ReferType', 'Refer Type', 'trim|required|in_list[Phone,Email]');
        $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim' . (!empty($this->Post['ReferType']) && $this->Post['ReferType'] == 'Phone' ? '|required' : ''));
        $this->form_validation->set_rules('Email', 'Email', 'trim' . (!empty($this->Post['ReferType']) && $this->Post['ReferType'] == 'Email' ? '|required|valid_email' : ''));
        $this->form_validation->set_rules('InviteCode', 'InviteCode', 'trim|required');

        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $this->Users_model->InviteContest($this->Post, $this->SessionUserID);
        $this->Return['Message'] = "Successfully invited.";
    }

    /* -----Validation Functions----- */
    /* ------------------------------ */

    function validatePassword($Password) {
        if (empty($Password)) {
            $this->form_validation->set_message('validatePassword', '{field} is required.');
            return FALSE;
        }
        $UserData = $this->Users_model->getUsers('', array('UserID' => $this->SessionUserID, 'Password' => $Password));
        if (!$UserData) {
            $this->form_validation->set_message('validatePassword', 'Invalid {field}.');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Function Name: validateAlreadyRegistered
     * Description:   To validate already registered number or email
     */
    function validateAlreadyRegistered($Value, $FieldValue) {
        $Query = ($FieldValue == 'Email') ? 'SELECT * FROM `tbl_users` WHERE `Email` = "' . $Value . '" OR `EmailForChange` = "' . $Value . '" LIMIT 1' : 'SELECT * FROM `tbl_users` WHERE `PhoneNumber` = "' . $Value . '" OR `PhoneNumberForChange` = "' . $Value . '" LIMIT 1';
        if ($this->db->query($Query)->num_rows() > 0) {
            $this->form_validation->set_message('validateAlreadyRegistered', ($FieldValue == 'Email') ? 'Email is already registered' : 'Phone Number is already registered');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /* To get Avatars */

    public function getAvtars_post() {

        $Avatars = array();
        $avatarObj1 = new StdClass();
        $avatarObj1->AvatarId = '1';
        $avatarObj1->AvatarImg = '1.png';
        $avatarObj1->AvatarURL = base_url() . 'uploads/profile/picture/1.png';
        array_push($Avatars, $avatarObj1);
        $avatarObj2 = new StdClass();
        $avatarObj2->AvatarId = '2';
        $avatarObj2->AvatarImg = '2.png';
        $avatarObj2->AvatarURL = base_url() . 'uploads/profile/picture/2.png';
        array_push($Avatars, $avatarObj2);
        $avatarObj3 = new StdClass();
        $avatarObj3->AvatarId = '3';
        $avatarObj3->AvatarImg = '3.png';
        $avatarObj3->AvatarURL = base_url() . 'uploads/profile/picture/3.png';
        array_push($Avatars, $avatarObj3);
        $avatarObj4 = new StdClass();
        $avatarObj4->AvatarId = '4';
        $avatarObj4->AvatarImg = '4.png';
        $avatarObj4->AvatarURL = base_url() . 'uploads/profile/picture/4.png';
        array_push($Avatars, $avatarObj4);
        $avatarObj5 = new StdClass();
        $avatarObj5->AvatarId = '5';
        $avatarObj5->AvatarImg = '5.png';
        $avatarObj5->AvatarURL = base_url() . 'uploads/profile/picture/5.png';
        array_push($Avatars, $avatarObj5);
        $avatarObj6 = new StdClass();
        $avatarObj6->AvatarId = '6';
        $avatarObj6->AvatarImg = '6.png';
        $avatarObj6->AvatarURL = base_url() . 'uploads/profile/picture/6.png';
        array_push($Avatars, $avatarObj6);
        $avatarObj7 = new StdClass();
        $avatarObj7->AvatarId = '7';
        $avatarObj7->AvatarImg = '7.png';
        $avatarObj7->AvatarURL = base_url() . 'uploads/profile/picture/7.png';
        array_push($Avatars, $avatarObj7);

        $this->Return['Data']['Records'] = $Avatars;
    }

    /*
      Name:         get refferal chain
      URL:          /api/users/getRefferalUsers
     */

    public function getRefferalUsers_post() {

        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->Return['Data']['Response']['FirstPercentage'] = "0";
        $this->Return['Data']['Response']['SecondPercentage'] = "0";
        $this->Return['Data']['Response']['ThirdPercentage'] = "0";
        $MLMConfigType = $this->db->query('SELECT ConfigTypeGUID,ConfigTypeValue,StatusID FROM set_site_config '
                        . 'WHERE (ConfigTypeGUID = "MlmIsActive" OR ConfigTypeGUID = "MlmFirstLevel" OR ConfigTypeGUID = '
                        . '"MlmSecondLevel" OR ConfigTypeGUID = "MlmThirdLevel")')->result_array();
        if (!empty($MLMConfigType)) {
            foreach ($MLMConfigType as $ConfigValue) {
                if ($ConfigValue['ConfigTypeGUID'] == "MlmFirstLevel" && $ConfigValue['StatusID'] == 2) {
                    $this->Return['Data']['Response']['FirstPercentage'] = $ConfigValue['ConfigTypeValue'];
                }
                if ($ConfigValue['ConfigTypeGUID'] == "MlmSecondLevel" && $ConfigValue['StatusID'] == 2) {
                    $this->Return['Data']['Response']['SecondPercentage'] = $ConfigValue['ConfigTypeValue'];
                }
                if ($ConfigValue['ConfigTypeGUID'] == "MlmThirdLevel" && $ConfigValue['StatusID'] == 2) {
                    $this->Return['Data']['Response']['ThirdPercentage'] = $ConfigValue['ConfigTypeValue'];
                }
            }
        }
        $this->Return['Data']['Response']['TotalReferralsDeposit'] = "0";
        $this->Return['Data']['Response']['ReferralsDeposit'] = "0";
        $this->Return['Data']['Response']['FirstLevel'] = "0";
        $this->Return['Data']['Response']['SecondLevel'] = "0";
        $this->Return['Data']['Response']['ThirdLevel'] = "0";
        $this->Return['Data']['Response']['FristLevelTotalWinningAmount'] = "0";
        $this->Return['Data']['Response']['SecondLevelTotalWinningAmount'] = "0";
        $this->Return['Data']['Response']['ThirdLevelTotalWinningAmount'] = "0";
        $this->Return['Data']['Response']['TotalAmount'] = "0";
        $this->Return['Data']['Response']['DepositAmount'] = "0";
        $this->Return['Data']['Response']['WinningAmount'] = "0";
        $this->Return['Data']['Response']['CashBonusAmount'] = "0";

        $UsersRecords = $this->Users_model->getUsers('WalletAmount,WinningAmount,CashBonus', array('UserID' => $this->UserID));
        $this->Return['Data']['Response']['TotalAmount'] = $UsersRecords["WalletAmount"] + $UsersRecords["WinningAmount"] + $UsersRecords["CashBonus"];
        $this->Return['Data']['Response']['DepositAmount'] = $UsersRecords["WalletAmount"];
        $this->Return['Data']['Response']['WinningAmount'] = $UsersRecords["WinningAmount"];
        $this->Return['Data']['Response']['CashBonusAmount'] = $UsersRecords["CashBonus"];
        $RefferalWinnings = $this->Users_model->getWallet('RefferalWinningAmount', array('UserID' => $this->UserID));
        $this->Return['Data']['Response']['ReferralsDeposit'] = (!empty($RefferalWinnings['RefferalWinningAmount'])) ? $RefferalWinnings['RefferalWinningAmount'] : 0;
        $levelFirst = $this->Users_model->getUserReferrals($this->UserID);
        $this->Return['Data']['Response']['FirstLevel'] = $levelFirst['TotalRecords'];

        if ($levelFirst['TotalRecords'] > 0) {
            $SecondLevelUsers = array();
            $SecondTotal = 0;
            $ThirdTotal = 0;
            $FristLevelTotalWinningAmount = 0;
            $SecondLevelTotalWinningAmount = 0;
            $ThirdLevelTotalWinningAmount = 0;
            foreach ($levelFirst['Records'] as $User) {
                $FirstLevelAmount = $this->Users_model->getUserReferralByTotalWinning($User['UserID'], $this->UserID);
                $FristLevelTotalWinningAmount = $FristLevelTotalWinningAmount + $FirstLevelAmount['Records']['WinningAmount'];

                $levelSecond = $this->Users_model->getUserReferrals($User['UserID']);
                $SecondTotal = $SecondTotal + $levelSecond['TotalRecords'];

                foreach ($levelSecond['Records'] as $Rows2) {
                    $SecondLevelAmount = $this->Users_model->getUserReferralByTotalWinning($Rows2['UserID'], $this->UserID);
                    $SecondLevelTotalWinningAmount = $SecondLevelTotalWinningAmount + $SecondLevelAmount['Records']['WinningAmount'];

                    $levelThird = $this->Users_model->getUserReferrals($Rows2['UserID']);
                    $ThirdTotal = $ThirdTotal + $levelThird['TotalRecords'];
                    foreach ($levelThird['Records'] as $Rows3) {
                        $ThirdLevelAmount = $this->Users_model->getUserReferralByTotalWinning($Rows3['UserID'], $this->UserID);
                        $ThirdLevelTotalWinningAmount = $ThirdLevelTotalWinningAmount + $ThirdLevelAmount['Records']['WinningAmount'];
                    }
                }
            }
            $this->Return['Data']['Response']['SecondLevel'] = $SecondTotal;
            $this->Return['Data']['Response']['ThirdLevel'] = $ThirdTotal;

            $this->Return['Data']['Response']['FristLevelTotalWinningAmount'] = $FristLevelTotalWinningAmount;
            $this->Return['Data']['Response']['SecondLevelTotalWinningAmount'] = $SecondLevelTotalWinningAmount;
            $this->Return['Data']['Response']['ThirdLevelTotalWinningAmount'] = $ThirdLevelTotalWinningAmount;
            $this->Return['Data']['Response']['TotalReferralsDeposit'] = $FristLevelTotalWinningAmount + $SecondLevelTotalWinningAmount + $ThirdLevelTotalWinningAmount;
        }
    }

    /*
      Name:         test amount credit refferal chain
      URL:          /api/users/getRefferalUsers
     */

    function getRefferalUserByTest_post() {
        $WalletAmount = 700;
        $UserID = 353919;
        /** user deposit amount on referrals * */
        /** user deposit amount on referrals * */
        /** user deposit amount on referrals * */
        if (REFERRALS_DEPOSIT_AMOUNT) {
            if ($WalletAmount > 0) {
                /** get first level * */
                $LevelFirst = $this->Users_model->getUserReferralBy($UserID);
                if (!empty($LevelFirst['Records'])) {

                    /** get 2.5% on first level * */
                    $LevelFirstRffferID = $LevelFirst['Records']['ReferredByUserID'];
                    $FirstLevelDeposit = ($WalletAmount * FIRST_LEVEL_PERCENTAGE) / 100;
                    /** add to wallet amount * */
                    $WalletData = array(
                        "Amount" => $FirstLevelDeposit,
                        "WinningAmount" => $FirstLevelDeposit,
                        "TransactionType" => 'Cr',
                        "Narration" => 'Referral Winning',
                        "AmountType" => "Referral",
                        "ReferralGetAmountUserID" => $UserID,
                        "EntryDate" => date("Y-m-d H:i:s")
                    );
                    $this->Users_model->addToWallet($WalletData, $LevelFirstRffferID, 5);


                    /** get second level * */
                    $LevelSecond = $this->Users_model->getUserReferralBy($LevelFirstRffferID);
                    if (!empty($LevelSecond['Records'])) {
                        $LevelSecondRffferID = $LevelSecond['Records']['ReferredByUserID'];

                        /** get 1.5% on first level * */
                        $SecondLevelDeposit = ($WalletAmount * SECOND_LEVEL_PERCENTAGE) / 100;
                        /** add to wallet amount * */
                        $WalletData = array(
                            "Amount" => $SecondLevelDeposit,
                            "WinningAmount" => $SecondLevelDeposit,
                            "TransactionType" => 'Cr',
                            "Narration" => 'Referral Winning',
                            "AmountType" => "Referral",
                            "ReferralGetAmountUserID" => $UserID,
                            "EntryDate" => date("Y-m-d H:i:s")
                        );
                        $this->Users_model->addToWallet($WalletData, $LevelSecondRffferID, 5);

                        /** get third level * */
                        $LevelThird = $this->Users_model->getUserReferralBy($LevelSecondRffferID);
                        if (!empty($LevelThird['Records'])) {
                            $LevelThirdRffferID = $LevelThird['Records']['ReferredByUserID'];

                            /** get 1% on first level * */
                            $ThirdLevelDeposit = ($WalletAmount * THIRD_LEVEL_PERCENTAGE) / 100;
                            /** add to wallet amount * */
                            $WalletData = array(
                                "Amount" => $ThirdLevelDeposit,
                                "WinningAmount" => $ThirdLevelDeposit,
                                "TransactionType" => 'Cr',
                                "Narration" => 'Referral Winning',
                                "AmountType" => "Referral",
                                "ReferralGetAmountUserID" => $UserID,
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->Users_model->addToWallet($WalletData, $LevelThirdRffferID, 5);
                        }
                    }
                }
            }
        }
    }

}
