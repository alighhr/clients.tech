<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Users_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Utility_model');
    }

    /*
      Description: 	Use to update user profile info.
     */

    function updateUserInfo($UserID, $Input = array()) {

        $UpdateArray = array_filter(array(
            "UserTypeID" => @$Input['UserTypeID'],
            "FirstName" => @ucfirst(strtolower($Input['FirstName'])),
            "MiddleName" => @ucfirst(strtolower($Input['MiddleName'])),
            "LastName" => @ucfirst(strtolower($Input['LastName'])),
            "About" => @$Input['About'],
            "About1" => @$Input['About1'],
            "About2" => @$Input['About2'],
            "ProfilePic" => @$Input['ProfilePic'],
            "ProfileCoverPic" => @$Input['ProfileCoverPic'],
            "Email" => @strtolower($Input['Email']),
            "Username" => @strtoupper($Input['Username']),
            "Gender" => @$Input['Gender'],
            "BirthDate" => @$Input['BirthDate'],
            "Age" => @$Input['Age'],
            "Height" => @$Input['Height'],
            "Weight" => @$Input['Weight'],
            "Address" => @$Input['Address'],
            "Address1" => @$Input['Address1'],
            "Postal" => @$Input['Postal'],
            "CountryCode" => @$Input['CountryCode'],
            "TimeZoneID" => @$Input['TimeZoneID'],
            "CityName" => @$Input['CityName'],
            "StateName" => @$Input['StateName'],
            "Company" => @$Input['Company'],
            "Latitude" => @$Input['Latitude'],
            "Longitude" => @$Input['Longitude'],
            "LanguageKnown" => @$Input['LanguageKnown'],
            "PhoneNumber" => @$Input['PhoneNumber'],
            "IsPrivacyNameDisplay" => @$Input['IsPrivacyNameDisplay'],
            "isWithdrawal" => @$Input['isWithdrawal'],
            "Website" => @strtolower($Input['Website']),
            "FacebookURL" => @strtolower($Input['FacebookURL']),
            "TwitterURL" => @strtolower($Input['TwitterURL']),
            "GoogleURL" => @strtolower($Input['GoogleURL']),
            "InstagramURL" => @strtolower($Input['InstagramURL']),
            "LinkedInURL" => @strtolower($Input['LinkedInURL']),
            "WhatsApp" => @strtolower($Input['WhatsApp']),
            "BusinessName" => @$Input['BusinessName'],
            "Domain" => @$Input['Domain'],
            "PaymentMode" => @$Input['PaymentMode'],
            "SubscriptionType" => @$Input['SubscriptionType'],
            "StartDate" => @$Input['StartDate'],
            "EndDate" => @$Input['EndDate'],
        ));

        if (isset($Input['LastName']) && $Input['LastName'] == '') {
            $UpdateArray['LastName'] = null;
        }
        if (isset($Input['Username']) && $Input['Username'] == '') {
            $UpdateArray['Username'] = null;
        }
        if (isset($Input['Gender']) && $Input['Gender'] == '') {
            $UpdateArray['Gender'] = null;
        }
        if (isset($Input['BirthDate']) && $Input['BirthDate'] == '') {
            $UpdateArray['BirthDate'] = null;
        }
        if (isset($Input['Address']) && $Input['Address'] == '') {
            $UpdateArray['Address'] = null;
        }
        if (isset($Input['PhoneNumber']) && $Input['PhoneNumber'] == '') {
            $UpdateArray['PhoneNumber'] = null;
        }
        if (isset($Input['Website']) && $Input['Website'] == '') {
            $UpdateArray['Website'] = null;
        }
        if (isset($Input['FacebookURL']) && $Input['FacebookURL'] == '') {
            $UpdateArray['FacebookURL'] = null;
        }
        if (isset($Input['TwitterURL']) && $Input['TwitterURL'] == '') {
            $UpdateArray['TwitterURL'] = null;
        }
        if (isset($Input['PhoneNumber']) && $Input['PhoneNumber'] == '') {
            $UpdateArray['PhoneNumber'] = null;
        }


        /* for change email address */
        if (!empty($UpdateArray['Email']) || !empty($UpdateArray['PhoneNumber'])) {
            $UserData = $this->Users_model->getUsers('Email,FirstName,PhoneNumber', array('UserID' => $UserID));
        }

        /* for update email address */
        if (!empty($UpdateArray['Email'])) {
            if ($UserData['Email'] != $UpdateArray['Email']) {
                $UpdateArray['EmailForChange'] = $UpdateArray['Email'];
                /* Genrate a Token for Email verification and save to tokens table. */
                $this->load->model('Recovery_model');
                $Token = $this->Recovery_model->generateToken($UserID, 2);
                /* Send welcome Email to User with Token. */
                // sendMail(array(
                //     'emailTo' => $UpdateArray['EmailForChange'],
                //     'emailSubject' => SITE_NAME . ", OTP for change of email address.",
                //     'emailMessage' => emailTemplate($this->load->view('emailer/change_email', array("Name" => $UserData['FirstName'], 'Token' => $Token), TRUE))
                // ));
                send_mail(array(
                    'emailTo' => $UpdateArray['EmailForChange'],
                    'template_id' => CHANGE_EMAIL,
                    'Subject' => SITE_NAME . ", OTP for change of email address",
                    "Name" => $UserData['FirstName'],
                    'Token' => $Token
                ));
                unset($UpdateArray['Email']);
            }
        }


        /* for update phone number */
        if (!empty($UpdateArray['PhoneNumber']) && PHONE_NO_VERIFICATION && !isset($Input['SkipPhoneNoVerification'])) {
            if ($UserData['PhoneNumber'] != $UpdateArray['PhoneNumber']) {

                $UpdateArray['PhoneNumberForChange'] = $UpdateArray['PhoneNumber'];
                /* Genrate a Token for PhoneNumber verification and save to tokens table. */
                $this->load->model('Recovery_model');
                $Token = $this->Recovery_model->generateToken($UserID, 3);

                /* Send change phonenumber SMS to User with Token. */

                $this->Utility_model->sendMobileSMS(array(
                    'PhoneNumber' => $UpdateArray['PhoneNumberForChange'],
                    'Text' => $Token
                        // 'Text' => SITE_NAME . ", OTP to verify Mobile no. is: $Token",
                ));
                unset($UpdateArray['PhoneNumber']);
            }
        }
        if (!empty($Input['PanStatus'])) {
            $UpdateArray['PanStatus'] = $Input['PanStatus'];
            $Type = 'Pancard';
            if (!empty($Input['Comments'])) {
                $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID, M.MediaCaption', array("SectionID" => 'PAN', "EntityID" => $UserID), FALSE);
            }
        }
        if (!empty($Input['BankStatus'])) {
            $Type = 'Bank Detail';
            $UpdateArray['BankStatus'] = $Input['BankStatus'];
            if (!empty($Input['Comments'])) {
                $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID, M.MediaCaption', array("SectionID" => 'BankDetail', "EntityID" => $UserID), FALSE);
            }
        }

        if (!empty($Input['Comments'])) {
            $MediaGUID = $MediaData['MediaGUID'];
            $Caption = json_decode($MediaData['MediaCaption']);
            $Caption->RejectReason = $Input['Comments'];

            $UpdateCaption['MediaCaption'] = json_encode($Caption);

            $this->db->where('MediaGUID', $MediaGUID);
            $this->db->limit(1);
            $this->db->update('tbl_media', $UpdateCaption);

            $this->Notification_model->addNotification('verify', 'Verification Rejected', $UserID, $UserID, '', 'Your ' . $Type . ' verification has been Rejected due to ' . $Input['Comments']);
        }

        if (!empty($Input['UpdateBankInfo']) && $Input['UpdateBankInfo'] == 'Yes') {
            $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID, M.MediaCaption', array("SectionID" => 'BankDetail', "EntityID" => $UserID), FALSE);

            $Caption = json_decode($MediaData['MediaCaption']);

            $Caption->FullName = $Input['Name'];
            $Caption->Bank = $Input['Bank'];
            $Caption->AccountNumber = $Input['AccountNumber'];
            $Caption->IFSCCode = $Input['IFSCCode'];

            $UpdateCaption['MediaCaption'] = json_encode($Caption);

            $this->db->where('MediaGUID', $MediaData['MediaGUID']);
            $this->db->limit(1);
            $this->db->update('tbl_media', $UpdateCaption);
        }

        /* Update User details to users table. */
        if (!empty($UpdateArray)) {
            $this->db->where('UserID', $UserID);
            $this->db->limit(1);
            $this->db->update('tbl_users', $UpdateArray);

            if ($UpdateArray['BankStatus'] == 2 || $UpdateArray['PanStatus'] == 2) {
                $this->Notification_model->addNotification('verify', 'Verified', $UserID, $UserID, '', 'Your ' . $Type . ' verification has been Approved.');
            }
        }

        if (!empty($Input['InterestGUIDs'])) {
            /* Revoke categories - starts */
            $this->db->where(array("EntityID" => $UserID));
            $this->db->delete('tbl_entity_categories');
            /* Revoke categories - ends */

            /* Assign categories - starts */
            $this->load->model('Category_model');
            foreach ($Input['InterestGUIDs'] as $CategoryGUID) {
                $CategoryData = $this->Category_model->getCategories('CategoryID', array('CategoryGUID' => $CategoryGUID));
                if ($CategoryData) {
                    $InsertCategory[] = array('EntityID' => $UserID, 'CategoryID' => $CategoryData['CategoryID']);
                }
            }
            if (!empty($InsertCategory)) {
                $this->db->insert_batch('tbl_entity_categories', $InsertCategory);
            }
            /* Assign categories - ends */
        }


        if (!empty($Input['SpecialtyGUIDs'])) {
            /* Revoke categories - starts */
            $this->db->where(array("EntityID" => $UserID));
            $this->db->delete('tbl_entity_categories');
            /* Revoke categories - ends */

            /* Assign categories - starts */
            $this->load->model('Category_model');
            foreach ($Input['SpecialtyGUIDs'] as $CategoryGUID) {
                $CategoryData = $this->Category_model->getCategories('CategoryID', array('CategoryGUID' => $CategoryGUID));
                if ($CategoryData) {
                    $InsertCategory[] = array('EntityID' => $UserID, 'CategoryID' => $CategoryData['CategoryID']);
                }
            }
            if (!empty($InsertCategory)) {
                $this->db->insert_batch('tbl_entity_categories', $InsertCategory);
            }
            /* Assign categories - ends */
        }



        $this->Entity_model->updateEntityInfo($UserID, array('StatusID' => @$Input['StatusID']));
        return TRUE;
    }

    /*
      Description: 	Use to set user new password.
     */

    function updateUserLoginInfo($UserID, $Input = array(), $SourceID) {
        $UpdateArray = array_filter(array(
            "Password" => (!empty($Input['Password']) ? md5($Input['Password']) : ''),
            "ModifiedDate	" => (!empty($Input['Password']) ? date("Y-m-d H:i:s") : ''),
            "LastLoginDate" => @$Input['LastLoginDate']
        ));

        /* Update User Login details */
        $this->db->where('UserID', $UserID);
        $this->db->where('SourceID', $SourceID);
        $this->db->limit(1);
        $this->db->update('tbl_users_login', $UpdateArray);

        if (!empty($Input['Password'])) {
            /* Send Password Assistance Email to User with Token (If user is not Pending or Email-Confirmed then email send without Token). */
            $UserData = $this->Users_model->getUsers('FirstName,Email', array('UserID' => $UserID));
            // $SendMail = sendMail(array(
            //     'emailTo' => $UserData['Email'],
            //     'emailSubject' => SITE_NAME . " Password Assistance",
            //     'emailMessage' => emailTemplate($this->load->view('emailer/change_password', array("Name" => $UserData['FirstName']), TRUE))
            // ));

            $SendMail = send_mail(array(
                'emailTo' => $UserData['Email'],
                'template_id' => CHANGE_PASSWORD,
                'Subject' => SITE_NAME . " Password Assistance",
                "Name" => $UserData['FirstName']
            ));
        }
        return TRUE;
    }

    /*
      Description: 	ADD user to system.
      Procedures:
      1. Add user to user table and get UserID.
      2. Save login info to users_login table.
      3. Save User details to users_profile table.
      4. Genrate a Token for Email verification and save to tokens table.
      5. Send welcome Email to User with Token.
     */

    function addUser($Input = array(), $UserTypeID, $SourceID, $StatusID = 2) {
        $this->db->trans_start();
        $EntityGUID = get_guid();

        /* Add user to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 1, "StatusID" => $StatusID));
        /* Add user to user table . */
        // if (!empty($Input['PhoneNumber']) && PHONE_NO_VERIFICATION) {
        //     $Input['PhoneNumberForChange'] = $Input['PhoneNumber'];
        //     unset($Input['PhoneNumber']);
        // }
        $InsertData = array_filter(array(
            "UserID" => $EntityID,
            "UserGUID" => $EntityGUID,
            "UserTypeID" => $UserTypeID,
            "StoreID" => @$Input['StoreID'],
            "FirstName" => @ucfirst(strtolower($Input['FirstName'])),
            "MiddleName" => @ucfirst(strtolower($Input['MiddleName'])),
            "LastName" => @ucfirst(strtolower($Input['LastName'])),
            "About" => @$Input['About'],
            "ProfilePic" => @$Input['ProfilePic'],
            "ProfileCoverPic" => @$Input['ProfileCoverPic'],
            "Email" => @strtolower($Input['Email']),
            //"EmailForChange" => ($SourceID == 1) ? @strtolower($Input['Email']) : '',
            "Username" => @strtolower($Input['Username']),
            "Gender" => @$Input['Gender'],
            "BirthDate" => @$Input['BirthDate'],
            "Address" => @$Input['Address'],
            "Address1" => @$Input['Address1'],
            "StateName" => @$Input['State'],
            "CityName" => @$Input['City'],
            "Company" => @$Input['Company'],
            "Postal" => @$Input['Postal'],
            "CountryCode" => @$Input['CountryCode'],
            "TimeZoneID" => @$Input['TimeZoneID'],
            "Latitude" => @$Input['Latitude'],
            "PanStatus" => @$Input['PanStatus'],
            "BankStatus" => @$Input['BankStatus'],
            "Longitude" => @$Input['Longitude'],
            "PhoneNumber" => @$Input['PhoneNumber'],
            "PhoneNumberForChange" => @$Input['PhoneNumberForChange'],
            "Website" => @strtolower($Input['Website']),
            "FacebookURL" => @strtolower($Input['FacebookURL']),
            "TwitterURL" => @strtolower($Input['TwitterURL']),
            "ReferredByUserID" => @$Input['Referral']->UserID,
            "BusinessName" => @$Input['BusinessName'],
            "Domain" => @$Input['Domain'],
            "PaymentMode" => @$Input['PaymentMode'],
            "SubscriptionType" => @$Input['SubscriptionType'],
            "StartDate" => @$Input['StartDate'],
            "EndDate" => @$Input['EndDate'],
            "ClientCode" => @strtoupper($Input['ClientCode'])
        ));
        $this->db->insert('tbl_users', $InsertData);

        /* Manage Singup Bonus */
        $BonusData = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "SignupBonus" LIMIT 1');
        if ($BonusData->row()->StatusID == 2) {
            $WalletData = array(
                "Amount" => $BonusData->row()->ConfigTypeValue,
                "CashBonus" => $BonusData->row()->ConfigTypeValue,
                "TransactionType" => 'Cr',
                "Narration" => 'Signup Bonus',
                "EntryDate" => date("Y-m-d H:i:s")
            );
            $this->addToWallet($WalletData, $EntityID, 5);
            $this->Notification_model->addNotification('bonus', 'Signup Bonus', $EntityID, $EntityID, '', '' . DEFAULT_CURRENCY . $BonusData->row()->ConfigTypeValue . 'has been credited in your bonus Wallet');
        }

        /* Manage Singup Deposit Amount */
        $BonusData = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "SignupWalletDepositBonus" LIMIT 1');
        if ($BonusData->row()->StatusID == 2) {
            $WalletData = array(
                "Amount" => $BonusData->row()->ConfigTypeValue,
                "WalletAmount" => $BonusData->row()->ConfigTypeValue,
                "TransactionType" => 'Cr',
                "Narration" => 'Signup Direct Deposit',
                "EntryDate" => date("Y-m-d H:i:s")
            );
            $this->addToWallet($WalletData, $EntityID, 5);
            $this->Notification_model->addNotification('bonus', 'Signup Deposit Bonus', $EntityID, $EntityID, '', '' . DEFAULT_CURRENCY . $BonusData->row()->ConfigTypeValue . ' has been credited in your deposit Wallet');
        }

        /* Save login info to users_login table. */
        $InsertData = array_filter(array(
            "UserID" => $EntityID,
            "Password" => md5(($SourceID == '1' ? $Input['Password'] : $Input['SourceGUID'])),
            "SourceID" => $SourceID,
            "EntryDate" => date("Y-m-d H:i:s")));
        $this->db->insert('tbl_users_login', $InsertData);

        /* save user settings */
        $this->db->insert('tbl_users_settings', array("UserID" => $EntityID));

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return $EntityID;
    }

    /*
      Description: 	Use to get single user info or list of users.
      Note:			$Field should be comma seprated and as per selected tables alias.
     */

    function getUsers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        /* Additional fields to select */
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'RegisteredOn' => 'DATE_FORMAT(E.EntryDate, "' . DATE_FORMAT . '") RegisteredOn',
                'LastLoginDate' => 'DATE_FORMAT(UL.LastLoginDate, "' . DATE_FORMAT . '") LastLoginDate',
                'Rating' => 'E.Rating',
                'UserTypeName' => 'UT.UserTypeName',
                'IsAdmin' => 'UT.IsAdmin',
                'UserID' => 'U.UserID',
                'UserTypeID' => 'U.UserTypeID',
                'FirstName' => 'U.FirstName',
                'MiddleName' => 'U.MiddleName',
                'LastName' => 'U.LastName',
                'ProfilePic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","user-img.svg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic',
                'ProfileCoverPic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/cover/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfileCoverPic)) AS ProfileCoverPic',
                'About' => 'U.About',
                'About1' => 'U.About1',
                'About2' => 'U.About2',
                'Email' => 'U.Email',
                'EmailForChange' => 'U.EmailForChange',
                'Username' => 'U.Username',
                'Gender' => 'U.Gender',
                'BirthDate' => 'U.BirthDate',
                'Address' => 'U.Address',
                'Address1' => 'U.Address1',
                'Postal' => 'U.Postal',
                'CountryCode' => 'U.CountryCode',
                'CountryName' => 'CO.CountryName',
                'CityName' => 'U.CityName',
                'StateName' => 'U.StateName',
                'Company'   => 'U.Company',
                'PhoneNumber' => 'U.PhoneNumber',
                'Email' => 'U.Email',
                'PhoneNumberForChange' => 'U.PhoneNumberForChange',
                'Website' => 'U.Website',
                'FacebookURL' => 'U.FacebookURL',
                'TwitterURL' => 'U.TwitterURL',
                'GoogleURL' => 'U.GoogleURL',
                'InstagramURL' => 'U.InstagramURL',
                'LinkedInURL' => 'U.LinkedInURL',
                'WhatsApp' => 'U.WhatsApp',
                'WalletAmount' => 'U.WalletAmount',
                'WinningAmount' => 'U.WinningAmount',
                'isWithdrawal' => 'U.isWithdrawal',
                'CashBonus' => 'U.CashBonus',
                'TotalCash' => '(U.WalletAmount + U.WinningAmount + U.CashBonus) AS TotalCash',
                'ReferralCode' => '(SELECT ReferralCode FROM tbl_referral_codes WHERE tbl_referral_codes.UserID=U.UserID LIMIT 1) AS ReferralCode',
                'ReferredByUserID' => 'U.ReferredByUserID',
                'ModifiedDate' => 'E.ModifiedDate',
                'Status' => 'CASE E.StatusID
												when "1" then "Pending"
												when "2" then "Verified"
												when "3" then "Deleted"
												when "4" then "Blocked"
												when "8" then "Hidden"		
											END as Status',
                'PanStatus' => 'CASE U.PanStatus
												when "1" then "Pending"
												when "2" then "Verified"
                                                when "3" then "Rejected"    
												when "9" then "Not Submitted"   
											END as PanStatus',
                'BankStatus' => 'CASE U.BankStatus
												when "1" then "Pending"
												when "2" then "Verified"
												when "3" then "Rejected"	
                                                when "9" then "Not Submitted"   
											END as BankStatus',
                'ReferredCount' => '(SELECT COUNT(*) FROM `tbl_users` WHERE `ReferredByUserID` = U.UserID) AS ReferredCount',
                'StatusID' => 'E.StatusID',
                'PanStatusID' => 'U.PanStatus',
                'BankStatusID' => 'U.BankStatus',
                'IsPrivacyNameDisplay' => 'U.IsPrivacyNameDisplay',
                'PushNotification' => 'US.PushNotification',
                'PhoneStatus' => 'if(U.PhoneNumber is null, "Pending", "Verified") as PhoneStatus',
                'EmailStatus' => 'if(U.Email is null, "Pending", "Verified") as EmailStatus',
                'BusinessName' => 'U.BusinessName',
                'Domain' => 'U.Domain',
                'PaymentMode' => 'U.PaymentMode',
                'SubscriptionType' => 'U.SubscriptionType',
                'StartDate' => 'U.StartDate',
                'EndDate' => 'U.EndDate',
                'ClientCode' => 'U.ClientCode',
            );
            foreach ($Params as $Param) {
                $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
            }
        }
        $this->db->select('U.UserGUID, U.UserID,  CONCAT_WS(" ",U.FirstName,U.LastName) FullName');
        if (!empty($Field)) $this->db->select($Field, FALSE);


        /* distance calculation - starts */
        /* this is called Haversine formula and the constant 6371 is used to get distance in KM, while 3959 is used to get distance in miles. */
        if (!empty($Where['Latitude']) && !empty($Where['Longitude'])) {
            $this->db->select("(3959*acos(cos(radians(" . $Where['Latitude'] . "))*cos(radians(E.Latitude))*cos(radians(E.Longitude)-radians(" . $Where['Longitude'] . "))+sin(radians(" . $Where['Latitude'] . "))*sin(radians(E.Latitude)))) AS Distance", false);
            $this->db->order_by('Distance', 'ASC');

            if (!empty($Where['Radius'])) {
                $this->db->having("Distance <= " . $Where['Radius'], null, false);
            }
        }
        /* distance calculation - ends */

        $this->db->from('tbl_entity E');
        $this->db->from('tbl_users U');
        $this->db->where("U.UserID", "E.EntityID", FALSE);

        if (array_keys_exist($Params, array('UserTypeName', 'IsAdmin')) || !empty($Where['IsAdmin'])) {
            $this->db->from('tbl_users_type UT');
            $this->db->where("UT.UserTypeID", "U.UserTypeID", FALSE);
        }
        $this->db->join('tbl_users_login UL', 'U.UserID = UL.UserID', 'left');
        $this->db->join('tbl_users_settings US', 'U.UserID = US.UserID', 'left');

        if (array_keys_exist($Params, array('CountryName'))) {
            $this->db->join('set_location_country CO', 'U.CountryCode = CO.CountryCode', 'left');
        }

        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            // if (validateEmail($Where['Keyword'])) {
            //     $Where['Email'] = $Where['Keyword'];
            // } elseif (is_numeric($Where['Keyword'])) {
            //     $Where['PhoneNumber'] = $Where['Keyword'];
            // } else {
            $this->db->group_start();
            $this->db->like("U.FirstName", $Where['Keyword']);
            $this->db->or_like("U.LastName", $Where['Keyword']);
            $this->db->or_like("U.Username", $Where['Keyword']);
            $this->db->or_like("U.Email", $Where['Keyword']);
            $this->db->or_like("U.EmailForChange", $Where['Keyword']);
            $this->db->or_like("U.PhoneNumber", $Where['Keyword']);
            $this->db->or_like("U.PhoneNumberForChange", $Where['Keyword']);
            $this->db->or_like("CONCAT_WS('',U.FirstName,U.Middlename,U.LastName)", preg_replace('/\s+/', '', $Where['Keyword']), FALSE);
            $this->db->group_end();
            // }
        }

        if (!empty($Where['SourceID'])) {
            $this->db->where("UL.SourceID", $Where['SourceID']);
        }

        if (!empty($Where['UserTypeID'])) {
            $this->db->where_in("U.UserTypeID", $Where['UserTypeID']);
        }

        if (!empty($Where['UserTypeIDNot']) && $Where['UserTypeIDNot'] == 'Yes') {
            $this->db->where("U.UserTypeID!=", $Where['UserTypeIDNot']);
        }

        if (!empty($Where['UserID'])) {
            $this->db->where("U.UserID", $Where['UserID']);
        }
        if (!empty($Where['StateName'])) {
            $this->db->where("U.StateName", $Where['StateName']);
        }
        if (!empty($Where['UserArray'])) {
            $this->db->where_in("U.UserGUID", $Where['UserArray']);
        }
        if (!empty($Where['UserIDNot'])) {
            $this->db->where("U.UserID!=", $Where['UserIDNot']);
        }
        if (!empty($Where['UserGUID'])) {
            $this->db->where("U.UserGUID", $Where['UserGUID']);
        }
        if (!empty($Where['ReferredByUserID'])) {
            $this->db->where("U.ReferredByUserID", $Where['ReferredByUserID']);
        }

        if (!empty($Where['Username'])) {
            $this->db->where("U.Username", $Where['Username']);
        }
        if (!empty($Where['Email'])) {
            $this->db->where("U.Email", $Where['Email']);
        }
        if (!empty($Where['ClientCode'])) {
            $this->db->where("U.ClientCode", $Where['ClientCode']);
        }
        if (!empty($Where['PhoneNumber'])) {
            $this->db->where("U.PhoneNumber", $Where['PhoneNumber']);
        }
        if(!empty($Where['SubscriptionType'])){
            $this->db->where("U.SubscriptionType", $Where['SubscriptionType']);
        }

        if (!empty($Where['LoginKeyword'])) {
            $this->db->group_start();
            $this->db->where("U.Email", $Where['LoginKeyword']);
            $this->db->or_where("U.EmailForChange", $Where['LoginKeyword']);
            $this->db->or_where("U.Username", $Where['LoginKeyword']);
            $this->db->or_where("U.PhoneNumber", $Where['LoginKeyword']);
            $this->db->group_end();
        }
        if (!empty($Where['Password'])) {
            $this->db->where("UL.Password", md5($Where['Password']));
        }

        if (!empty($Where['IsAdmin'])) {
            $this->db->where("UT.IsAdmin", $Where['IsAdmin']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['PanStatus'])) {
            $this->db->where("U.PanStatus", $Where['PanStatus']);
        }
        if (!empty($Where['BankStatus'])) {
            $this->db->where("U.BankStatus", $Where['BankStatus']);
        }

        if (!empty($Where['EntryFrom'])) {
            $this->db->where("DATE(E.EntryDate) >=", $Where['EntryFrom']);
        }
        if (!empty($Where['EntryTo'])) {
            $this->db->where("DATE(E.EntryDate) <=", $Where['EntryTo']);
        }
        if (!empty($Where['ListType'])) {
            $this->db->where("DATE(E.EntryDate) =", date("Y-m-d"));
        }
        if (!empty($Where['ForVerify']) && $Where['ForVerify'] == 'Yes') {
            $this->db->where("U.PanStatus !=", 9);
        }
        if (!empty($Where['isWithdrawal'])) {
            $this->db->where("U.isWithdrawal", $Where['isWithdrawal']);
        }
        if (!empty($Where['IsPrivacyNameDisplay'])) {
            $this->db->where("U.IsPrivacyNameDisplay", $Where['IsPrivacyNameDisplay']);
        }
        if (!empty($Where['WalletType']) && !empty($Where['Operator']) && !empty($Where['Amount'])) {
            $this->db->where($Where['WalletType'] . $Where['Operator'], $Where['Amount']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence']) && in_array($Where['Sequence'], array('ASC', 'DESC'))) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by('U.UserID', 'DESC');
        }


        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
        } else {
            $this->db->limit(1);
        }

        $Query = $this->db->get();
        // echo $this->db->last_query();exit;
        if ($Query->num_rows() > 0) {
            foreach ($Query->result_array() as $Record) {

                /* get attached media */
                if (in_array('MediaPAN', $Params)) {
                    $Media['PAN'] = array(
                        'EntryDate' => '',
                        'MediaGUID' => '',
                        'MediaURL' => '',
                        'MediaThumbURL' => '',
                        'MediaCaption' => ''
                    );
                    $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID,DATE_FORMAT(CONVERT_TZ(E.EntryDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") EntryDate, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,"110_",M.MediaName) AS MediaThumbURL, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaURL,	M.MediaCaption', array("SectionID" => 'PAN', "EntityID" => $Record['UserID']), FALSE);
                    $Record['MediaPAN'] = ($MediaData ? $MediaData : $Media['PAN']);
                }

                if (in_array('MediaBANK', $Params)) {
                    $Media['BANK'] = array(
                        'EntryDate' => '',
                        'MediaGUID' => '',
                        'MediaURL' => '',
                        'MediaThumbURL' => '',
                        'MediaCaption' => ''
                    );
                    $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID,DATE_FORMAT(CONVERT_TZ(E.EntryDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") EntryDate, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,"110_",M.MediaName) AS MediaThumbURL, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaURL,	M.MediaCaption', array("SectionID" => 'BankDetail', "EntityID" => $Record['UserID']), FALSE);
                    if ($MediaData && $MediaData['MediaCaption']) {
                        $MediaCaption = json_decode($MediaData['MediaCaption']);
                        if ($MediaCaption->FullName == null || $MediaCaption->Bank == null || $MediaCaption->AccountNumber == null || $MediaCaption->IFSCCode == null) {
                            $MediaData = '';
                        }
                    }
                    $Record['MediaBANK'] = ($MediaData ? $MediaData : $Media['BANK']);
                }

                /* Get Wallet Data */
                if (in_array('Wallet', $Params)) {
                    $WalletData = $this->getWallet('Amount,Currency,PaymentGateway,TransactionType,TransactionID,EntryDate,Narration,Status,OpeningBalance,ClosingBalance', array('UserID' => $Where['UserID'], 'TransactionMode' => 'WalletAmount'), TRUE);
                    $Record['Wallet'] = ($WalletData) ? $WalletData['Data']['Records'] : array();
                }

                /* Get Playing History Data */
                if (in_array('PlayingHistory', $Params)) {

                    $PlayingHistory = $this->db->query('SELECT (
                                                            select COUNT(DISTINCT MatchID) as TotalJoinedMatches from sports_contest_join where UserID = 341505
                                                        )as TotalJoinedMatches,
                                                        (select COUNT(DISTINCT S.SeriesID) as TotalJoinedSeries from sports_contest_join CJ, sports_matches M,sports_series S where S.SeriesID = M.SeriesID AND CJ.MatchID = M.MatchID AND CJ.UserID = 341505 ) TotalJoinedSeries,
                                                        (select COUNT(ContestID) as TotalJoinedContest from sports_contest_join where UserID = 341505 ) TotalJoinedContest,
                                                        (select COUNT(ContestID) as TotalJoinedContestWinning from sports_contest_join where UserID = 341505 AND UserWinningAmount > 0 ) TotalJoinedContestWinning')->row();
                    $Record['PlayingHistory'] = ($PlayingHistory) ? $PlayingHistory : array();
                }

                if (!$multiRecords) {
                    return $Record;
                }
                $Records[] = $Record;
            }

            $Return['Data']['Records'] = $Records;
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: 	Use to create session.
     */

    function createSession($UserID, $Input = array()) {
        /* Multisession handling */
        if (!MULTISESSION) {
            $this->db->delete('tbl_users_session', array('UserID' => $UserID));
        } else {
            /* 			if(empty(@$Input['DeviceGUID'])){
              $this->db->delete('tbl_users_session', array('DeviceGUID' => $Input['DeviceGUID']));
              } */
        }

        /* Multisession handling - ends */
        $InsertData = array_filter(array(
            'UserID' => $UserID,
            'SessionKey' => get_guid(),
            'IPAddress' => @$Input['IPAddress'],
            'SourceID' => (!empty($Input['SourceID']) ? $Input['SourceID'] : DEFAULT_SOURCE_ID),
            'DeviceTypeID' => (!empty($Input['DeviceTypeID']) ? $Input['DeviceTypeID'] : DEFAULT_DEVICE_TYPE_ID),
            'DeviceGUID' => @$Input['DeviceGUID'],
            'DeviceToken' => @$Input['DeviceToken'],
            'EntryDate' => date("Y-m-d H:i:s"),
        ));

        $this->db->insert('tbl_users_session', $InsertData);
        /* update current date of login */
        $this->updateUserLoginInfo($UserID, array("LastLoginDate" => date("Y-m-d H:i:s")), $InsertData['SourceID']);
        /* Update Latitude, Longitude */
        if (!empty($Input['Latitude']) && !empty($Input['Longitude'])) {
            $this->updateUserInfo($UserID, array("Latitude" => $Input['Latitude'], "Longitude" => $Input['Longitude']));
        }
        return $InsertData['SessionKey'];
    }

    /*
      Description:    Use to get User login Sources.
     */

    function checkSources($UserID) {
        $this->db->select('S.SourceName');
        $this->db->from('set_source S');
        $this->db->where('EXISTS(SELECT 1 FROM `tbl_users_login` WHERE SourceID=S.SourceID AND UserID=' . $UserID . ')');
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            return $Query->result_array();
        }
        return FALSE;
    }

    /*
      Description: 	Use to get UserID by SessionKey and validate SessionKey.
     */

    function checkSession($SessionKey) {
        $this->db->select('UserID');
        $this->db->from('tbl_users_session');
        $this->db->where("SessionKey", $SessionKey);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            return $Query->row()->UserID;
        }
        return FALSE;
    }

    /*
      Description: 	Use to delete Session.
     */

    function deleteSession($SessionKey) {
        $this->db->limit(1);
        $this->db->delete('tbl_users_session', array('SessionKey' => $SessionKey));
        return TRUE;
    }

    /*
      Description: 	Use to set new email address of user.
     */

    function updateEmail($UserID, $Email) {
        /* check new email address is not in use */
        $UserData = $this->Users_model->getUsers('', array('Email' => $Email,));
        if (!$UserData) {

            $this->db->trans_start();
            /* update profile table */
            $this->db->where('UserID', $UserID);
            $this->db->limit(1);
            $this->db->update('tbl_users', array("Email" => $Email, "EmailForChange" => null));

            /* Delete session */
            $this->db->limit(1);
            $this->db->delete('tbl_users_session', array('UserID' => $UserID));
            /* Delete session - ends */
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            }

            $this->Entity_model->updateEntityInfo($UserID, array("StatusID" => 2));
        }
        return TRUE;
    }

    /*
      Description: 	Use to set new email address of user.
     */

    function updatePhoneNumber($UserID, $PhoneNumber) {
        /* check new PhoneNumber is not in use */
        $UserData = $this->Users_model->getUsers('StatusID,PanStatus,BankStatus,PhoneNumber', array('PhoneNumber' => $PhoneNumber));
        if (!$UserData) {
            $this->db->trans_start();
            /* update profile table */
            $this->db->where('UserID', $UserID);
            $this->db->limit(1);
            $this->db->update('tbl_users', array("PhoneNumber" => $PhoneNumber, "PhoneNumberForChange" => null));

            /* change entity status to activate */
            if ($UserData['StatusID'] == 1) {
                $this->Entity_model->updateEntityInfo($UserID, array("StatusID" => 2));
            }

            /* Manage Verification Bonus */
            if ($UserData['PanStatus'] == 'Verified' && $UserData['BankStatus'] == 'Verified' && empty($UserData['PhoneNumber'])) {
                $BonusData = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "VerificationBonus" LIMIT 1');
                if ($BonusData->row()->StatusID == 2) {
                    $WalletData = array(
                        "Amount" => $BonusData->row()->ConfigTypeValue,
                        "CashBonus" => $BonusData->row()->ConfigTypeValue,
                        "TransactionType" => 'Cr',
                        "Narration" => 'Verification Bonus',
                        "EntryDate" => date("Y-m-d H:i:s")
                    );
                    $this->addToWallet($WalletData, $UserID, 5);
                }
            }

            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /*
      Description: To get user wallet data
     */

    function add($Input = array(), $UserID, $CouponID = NULL) {
        /* Get Coupon Details */
        if (!empty($CouponID)) {
            $this->load->model('Store_model');
            $CouponDetailsArr = $this->Store_model->getCoupons('CouponTitle,CouponDescription,OfferType,CouponCode,CouponType,CouponValue', array('CouponID' => $CouponID));
            $CouponDetailsArr['DiscountedAmount'] = ($CouponDetailsArr['CouponType'] == 'Flat' ? $CouponDetailsArr['CouponValue'] : ($Input['Amount'] / 100) * $CouponDetailsArr['CouponValue']);
        }
        /* Add Wallet Pre Request */
        $TransactionID = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $InsertData = array(
            "Amount" => @$Input['Amount'],
            "WalletAmount" => @$Input['Amount'],
            "PaymentGateway" => $Input['PaymentGateway'],
            "CouponDetails" => (!empty($CouponID)) ? json_encode($CouponDetailsArr) : NULL,
            "CouponCode" => (!empty($CouponID)) ? $CouponDetailsArr['CouponCode'] : NULL,
            "TransactionType" => 'Cr',
            "TransactionID" => $TransactionID,
            "Narration" => 'Deposit Money',
            "EntryDate" => date("Y-m-d H:i:s")
        );
        $WalletID = $this->addToWallet($InsertData, $UserID);

        if ($WalletID) {
            $PaymentResponse = array();
            if ($Input['PaymentGateway'] == 'PayUmoney') {

                /* Generate Payment Hash */
                $Amount = (strpos(@$Input['Amount'], '.') !== FALSE) ? @$Input['Amount'] : @$Input['Amount'] . '.0';
                $HashString = PAYUMONEY_MERCHANT_KEY . '|' . $TransactionID . "|" . $Amount . "|" . $WalletID . "|" . @$Input['FirstName'] . "|" . @$Input['Email'] . "|||||||||||" . PAYUMONEY_SALT;

                /* Generate Payment Value */
                $PaymentResponse['Action'] = PAYUMONEY_ACTION_KEY;
                $PaymentResponse['MerchantKey'] = PAYUMONEY_MERCHANT_KEY;
                $PaymentResponse['Salt'] = PAYUMONEY_SALT;
                $PaymentResponse['MerchantID'] = PAYUMONEY_MERCHANT_ID;
                $PaymentResponse['Hash'] = strtolower(hash('sha512', $HashString));
                $PaymentResponse['TransactionID'] = $TransactionID;
                $PaymentResponse['Amount'] = $Amount;
                $PaymentResponse['Email'] = @$Input['Email'];
                $PaymentResponse['PhoneNumber'] = @$Input['PhoneNumber'];
                $PaymentResponse['FirstName'] = @$Input['FirstName'];
                $PaymentResponse['ProductInfo'] = $WalletID;
                $PaymentResponse['SuccessURL'] = SITE_HOST . ROOT_FOLDER . 'myAccount?status=success';
                $PaymentResponse['FailedURL'] = SITE_HOST . ROOT_FOLDER . 'myAccount?status=failed';
            } elseif ($Input['PaymentGateway'] == 'Paytm') {

                /* Generate Checksum */
                $ParamList = array();
                $PaymentResponse['MerchantID'] = $ParamList['MID'] = ($Input['RequestSource'] == 'Web') ? PAYTM_MERCHANT_ID : APP_PAYTM_MERCHANT_ID;
                $PaymentResponse['OrderID'] = $ParamList['ORDER_ID'] = $WalletID;
                $PaymentResponse['CustomerID'] = $ParamList['CUST_ID'] = "CUST" . $UserID;
                $PaymentResponse['IndustryTypeID'] = $ParamList['INDUSTRY_TYPE_ID'] = ($Input['RequestSource'] == 'Web') ? PAYTM_INDUSTRY_TYPE_ID : APP_PAYTM_INDUSTRY_TYPE_ID;
                $PaymentResponse['ChannelID'] = $ParamList['CHANNEL_ID'] = ($Input['RequestSource'] == 'Web') ? 'WEB' : 'WAP';
                $PaymentResponse['Amount'] = $ParamList['TXN_AMOUNT'] = $Input['Amount'];
                $PaymentResponse['Website'] = $ParamList['WEBSITE'] = ($Input['RequestSource'] == 'Web') ? PAYTM_WEBSITE_WEB : APP_PAYTM_WEBSITE_APP;
                $PaymentResponse['CallbackURL'] = $ParamList['CALLBACK_URL'] = ($Input['RequestSource'] == 'Web') ? SITE_HOST . ROOT_FOLDER . 'api/main/paytmResponse' : 'https://' . APP_PAYTM_DOMAIN . '/theia/paytmCallback?ORDER_ID=' . $WalletID;
                $PaymentResponse['TransactionURL'] = ($Input['RequestSource'] == 'Web') ? PAYTM_TXN_URL : APP_PAYTM_TXN_URL;
                $MerchantKey = ($Input['RequestSource'] == 'Web') ? PAYTM_MERCHANT_KEY : APP_PAYTM_MERCHANT_KEY;
                $PaymentResponse['CheckSumHash'] = $this->generatePaytmCheckSum($ParamList, $MerchantKey);
            } elseif ($Input['PaymentGateway'] == 'Razorpay') {

                $PaymentResponse['MerchantKey'] = RAZORPAY_KEY_ID;
                $PaymentResponse['MerchantName'] = SITE_NAME;
                $PaymentResponse['Amount'] = @$Input['Amount'] * 100;
                $PaymentResponse['OrderID'] = $WalletID;
            }
            return $PaymentResponse;
        }
        return FALSE;
    }

    /*
      Description: To Get Paytm Transaction Details
     */

    public function getPaytmTxnDetails($OrderID) {
        $PaytmParams["MID"] = PAYTM_MERCHANT_mId;
        $PaytmParams["ORDERID"] = $OrderID;
        $PaytmParams['CHECKSUMHASH'] = urlencode($this->generatePaytmCheckSum($PaytmParams, PAYTM_MERCHANT_KEY_WITHDRAWAL));
        $Connection = curl_init();
        curl_setopt($Connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($Connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($Connection, CURLOPT_URL, "https://" . PAYTM_DOMAIN . "/merchant-status/getTxnStatus");
        curl_setopt($Connection, CURLOPT_POST, true);
        curl_setopt($Connection, CURLOPT_POSTFIELDS, "JsonData=" . json_encode($PaytmParams, JSON_UNESCAPED_SLASHES));
        curl_setopt($Connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($Connection, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        return json_decode(curl_exec($Connection), true);
    }

    function generatePaytmCheckSum($arrayList, $key, $sort = 1) {
        if ($sort != 0) {
            ksort($arrayList);
        }
        $str = $this->getArray2Str($arrayList);
        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = $this->encrypt_e($hashString, $key);
        return $checksum;
    }

    function getArray2Str($arrayList) {
        $findme = 'REFUND';
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            $pos = strpos($value, $findme);
            $pospipe = strpos($value, $findmepipe);
            if ($pos !== false || $pospipe !== false) {
                continue;
            }

            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    function checkString_e($value) {
        if ($value == 'null') $value = '';
        return $value;
    }

    function generateSalt_e($length) {
        $random = "";
        srand((double) microtime() * 1000000);

        $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
        $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
        $data .= "0FGH45OP89";

        for ($i = 0; $i < $length; $i++) {
            $random .= substr($data, (rand() % (strlen($data))), 1);
        }

        return $random;
    }

    function getChecksumFromString($str, $key) {

        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = $this->encrypt_e($hashString, $key);
        return $checksum;
    }

    /* function encrypt_e($input, $ky) {
      $key = $ky;
      $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
      $input = $this->pkcs5_pad_e($input, $size);
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
      $iv = "@@@@&&&&####$$$$";
      mcrypt_generic_init($td, $key, $iv);
      $data = mcrypt_generic($td, $input);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
      $data = base64_encode($data);
      return $data;
      } */

    function encrypt_e($input, $ky) {
        $ky = html_entity_decode($ky);
        $iv = "@@@@&&&&####$$$$";
        $data = openssl_encrypt($input, "AES-128-CBC", $ky, 0, $iv);
        return $data;
    }

    function decrypt_e($crypt, $ky) {
        $ky = html_entity_decode($ky);
        $iv = "@@@@&&&&####$$$$";
        $data = openssl_decrypt($crypt, "AES-128-CBC", $ky, 0, $iv);
        return $data;
    }

    /* function decrypt_e($crypt, $ky) {
      $crypt = base64_decode($crypt);
      $key = $ky;
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
      $iv = "@@@@&&&&####$$$$";
      mcrypt_generic_init($td, $key, $iv);
      $decrypted_data = mdecrypt_generic($td, $crypt);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
      $decrypted_data = $this->pkcs5_unpad_e($decrypted_data);
      $decrypted_data = rtrim($decrypted_data);
      return $decrypted_data;
      } */

    function pkcs5_pad_e($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    function pkcs5_unpad_e($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) return false;
        return substr($text, 0, -1 * $pad);
    }

    function verifychecksum_e($arrayList, $key, $checksumvalue) {
        $arrayList = $this->removeCheckSumParam($arrayList);
        ksort($arrayList);
        $str = $this->getArray2Str($arrayList);
        $paytm_hash = $this->decrypt_e($checksumvalue, $key);
        $salt = substr($paytm_hash, -4);
        $finalString = $str . "|" . $salt;
        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;
        $validFlag = "FALSE";
        if ($website_hash == $paytm_hash) {
            $validFlag = "TRUE";
        } else {
            $validFlag = "FALSE";
        }
        return $validFlag;
    }

    function removeCheckSumParam($arrayList) {
        if (isset($arrayList["CHECKSUMHASH"])) {
            unset($arrayList["CHECKSUMHASH"]);
        }
        return $arrayList;
    }

    /*
      Description: To confirm payment gateway response
     */

    function confirm($Input = array(), $UserID) {
        $this->db->trans_start();

        /* Verify payment from payment gateway */
        if ($Input['PaymentGatewayStatus'] == 'Success') {


            $PaymentGatewayResponse = (!empty($Input['PaymentGatewayResponse'])) ? @json_decode($Input['PaymentGatewayResponse'], TRUE) : NULL;
            /* Razorpay */
            if ($Input['PaymentGateway'] == 'Razorpay') {

                $this->db->select('WalletID');
                $this->db->where("WalletID", $Input['WalletID']);
                $this->db->where("UserID", $UserID);
                $this->db->where("PaymentGateway", "Razorpay");
                $this->db->where('StatusID', 5);
                $this->db->from('tbl_users_wallet');
                $this->db->limit(1);
                $WalletDetails = $this->db->get()->row();
                if (!empty($WalletDetails)) {
                    return $this->getWalletDetails($UserID);
                }


                $CURL = curl_init();
                curl_setopt_array($CURL, array(
                    CURLOPT_URL => "https://" . RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET . "@api.razorpay.com/v1/payments/" . $Input['Razor_payment_id'] . "/capture",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"amount\"\r\n\r\n" . @$Input['Amount'] . "00\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
                    CURLOPT_HTTPHEADER => array(
                        "cache-control: no-cache",
                        "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
                    ),
                ));

                $Response = curl_exec($CURL);
                $Err = curl_error($CURL);
                curl_close($CURL);
                if ($Err) {
                    return FALSE;
                } else {
                    $ResponseArr = json_decode($Response, true);
                    if (isset($ResponseArr['error']) === false) {
                        $Input['PaymentGatewayStatus'] = 'Success';
                        $Input['PaymentGatewayResponse'] = $Response;
                    } else {
                        $ResponseArr['razorpay_payment_id'] = @$PaymentGatewayResponse['razorpay_payment_id'];
                        $Input['PaymentGatewayStatus'] = 'Failed';
                        $Input['PaymentGatewayResponse'] = json_encode($ResponseArr);
                    }
                }
            }
        }

        /* update profile table */
        $UpdataData = array_filter(
                array(
                    'PaymentGatewayResponse' => @$Input['PaymentGatewayResponse'],
                    'ModifiedDate' => date("Y-m-d H:i:s"),
                    'StatusID' => ($Input['PaymentGatewayStatus'] == 'Failed' || $Input['PaymentGatewayStatus'] == 'Cancelled') ? 3 : 5,
                //'ClosingWalletAmount' => 'ClosingWalletAmount+' . $Amount
        ));
        $this->db->where('WalletID', $Input['WalletID']);
        $this->db->where('UserID', $UserID);
        $this->db->where('StatusID', 1);
        $this->db->limit(1);
        $this->db->update('tbl_users_wallet', $UpdataData);
        if ($this->db->affected_rows() <= 0) return $this->getWalletDetails($UserID);


        $this->db->select("Amount");
        $this->db->from('tbl_users_wallet');
        $this->db->where('WalletID', $Input['WalletID']);
        $this->db->where('UserID', $UserID);
        $this->db->where('StatusID', 5);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Amount = $Query->row()->Amount;
            /* Update user main wallet amount */
            if ($Input['PaymentGatewayStatus'] == 'Success') {

                $this->db->set('ClosingWalletAmount', 'ClosingWalletAmount+' . $Amount, FALSE);
                $this->db->where('WalletID', $Input['WalletID']);
                $this->db->where('UserID', $UserID);
                $this->db->where('StatusID', 5);
                $this->db->limit(1);
                $this->db->update('tbl_users_wallet');


                $this->db->set('WalletAmount', 'WalletAmount+' . $Amount, FALSE);
                $this->db->where('UserID', $UserID);
                $this->db->limit(1);
                $this->db->update('tbl_users');

                $CouponDetails = $this->getWallet('CouponDetails', array("WalletID" => $Input['WalletID']));

                /* Check Coupon Details */
                if (!empty($CouponDetails['CouponDetails'])) {
                    $WalletData = array(
                        "Amount" => $CouponDetails['CouponDetails']['DiscountedAmount'],
                        "CashBonus" => ($CouponDetails['CouponDetails']['OfferType'] == "CashBonus" ? $CouponDetails['CouponDetails']['DiscountedAmount'] : 0),
                        "WalletAmount" => ($CouponDetails['CouponDetails']['OfferType'] == "RealMoney" ? $CouponDetails['CouponDetails']['DiscountedAmount'] : 0),
                        "TransactionType" => 'Cr',
                        "EntityID" => $Input['WalletID'],
                        "Narration" => ($CouponDetails['CouponDetails']['OfferType'] == "RealMoney" ? 'Coupon Discount' : 'Coupon Discount Bonus'),
                        "EntryDate" => date("Y-m-d H:i:s")
                    );
                    $this->addToWallet($WalletData, $UserID, 5);
                }
                $this->Notification_model->addNotification('AddCash', 'Cash Added', $UserID, $UserID, '', 'Deposit of ' . DEFAULT_CURRENCY . @$Amount . ' is Successful.');
                /* Manage First Deposit & Referral Bonus */
                $TotalDeposits = $this->db->query('SELECT COUNT(*) TotalDeposits FROM `tbl_users_wallet` WHERE `UserID` = ' . $UserID . ' AND Narration = "Deposit Money" AND StatusID = 5')->row()->TotalDeposits;
                if ($TotalDeposits == 1) { // On First Successful Transaction

                    /* Get Deposit Bonus Data */
                    $DepositBonusData = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "FirstDepositBonus" LIMIT 1');
                    if ($DepositBonusData->row()->StatusID == 2) {

                        $MinimumFirstTimeDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MinimumFirstTimeDepositLimit" LIMIT 1');
                        $MaximumFirstTimeDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MaximumFirstTimeDepositLimit" LIMIT 1');

                        if ($MinimumFirstTimeDepositLimit->row()->ConfigTypeValue <= $Amount && $MaximumFirstTimeDepositLimit->row()->ConfigTypeValue >= $Amount) {
                            /* Update Wallet */
                            $FirstTimeAmount = ($Amount * $DepositBonusData->row()->ConfigTypeValue) / 100;
                            $WalletData = array(
                                "Amount" => $FirstTimeAmount,
                                "CashBonus" => $FirstTimeAmount,
                                "TransactionType" => 'Cr',
                                "Narration" => 'First Deposit Bonus',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->addToWallet($WalletData, $UserID, 5);
                        }
                    }

                    /* Get User Data */
                    $UserData = $this->getUsers('ReferredByUserID', array("UserID" => $UserID));
                    if (!empty($UserData['ReferredByUserID'])) {

                        /* Get Referral To Bonus Data */
                        $ReferralToBonus = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "ReferToDepositBonus" LIMIT 1');
                        if ($ReferralToBonus->row()->StatusID == 2) {

                            /* Update Wallet */
                            $WalletData = array(
                                "Amount" => $ReferralToBonus->row()->ConfigTypeValue,
                                "CashBonus" => $ReferralToBonus->row()->ConfigTypeValue,
                                "TransactionType" => 'Cr',
                                "Narration" => 'Referral Bonus',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->addToWallet($WalletData, $UserID, 5);
                            $this->Notification_model->addNotification('ReferralBonus', 'Referred Bonus Added', $UserID, $UserID, '', 'You have received ' . DEFAULT_CURRENCY . @$ReferralToBonus->row()->ConfigTypeValue . ' Cash Bonus for Referred.');
                        }

                        /* Get Referral By Bonus Data */
                        $ReferralByBonus = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "ReferByDepositBonus" LIMIT 1');
                        if ($ReferralByBonus->row()->StatusID == 2) {

                            /* Update Wallet */
                            $WalletData = array(
                                "Amount" => $ReferralByBonus->row()->ConfigTypeValue,
                                "CashBonus" => $ReferralByBonus->row()->ConfigTypeValue,
                                "TransactionType" => 'Cr',
                                "Narration" => 'Referral Bonus',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->addToWallet($WalletData, $UserData['ReferredByUserID'], 5);
                            $this->Notification_model->addNotification('ReferralBonus', 'Referral Bonus Added', $UserData['ReferredByUserID'], $UserData['ReferredByUserID'], '', 'You have received ' . DEFAULT_CURRENCY . @$ReferralByBonus->row()->ConfigTypeValue . ' Cash Bonus for Successful Referral.');
                        }
                    }
                }

                /* MLM Referrals Wallet */
                $MLMISActive = FALSE;
                $FirstLevel = 0;
                $SecondLevel = 0;
                $ThirdLevel = 0;
                $MLMConfigType = $this->db->query('SELECT ConfigTypeGUID,ConfigTypeValue,StatusID FROM set_site_config '
                                . 'WHERE (ConfigTypeGUID = "MlmIsActive" OR ConfigTypeGUID = "MlmFirstLevel" OR ConfigTypeGUID = '
                                . '"MlmSecondLevel" OR ConfigTypeGUID = "MlmThirdLevel")')->result_array();

                if (!empty($MLMConfigType)) {
                    foreach ($MLMConfigType as $ConfigValue) {
                        if ($ConfigValue['ConfigTypeGUID'] == "MlmIsActive") {
                            if ($ConfigValue['ConfigTypeValue'] == "Yes" && $ConfigValue['StatusID'] == 2) {
                                $MLMISActive = TRUE;
                            }
                        }
                        if ($ConfigValue['ConfigTypeGUID'] == "MlmFirstLevel" && $ConfigValue['StatusID'] == 2) {
                            $FirstLevel = $ConfigValue['ConfigTypeValue'];
                        }
                        if ($ConfigValue['ConfigTypeGUID'] == "MlmSecondLevel" && $ConfigValue['StatusID'] == 2) {
                            $SecondLevel = $ConfigValue['ConfigTypeValue'];
                        }
                        if ($ConfigValue['ConfigTypeGUID'] == "MlmThirdLevel" && $ConfigValue['StatusID'] == 2) {
                            $ThirdLevel = $ConfigValue['ConfigTypeValue'];
                        }
                    }
                }
                if ($MLMISActive) {
                    $WalletAmount = $Amount;

                    if ($WalletAmount > 0) {
                        /** get first level * */
                        $LevelFirst = $this->Users_model->getUserReferralBy($UserID);

                        if (!empty($LevelFirst['Records'])) {

                            /** get 2.5% on first level * */
                            $LevelFirstRffferID = $LevelFirst['Records']['ReferredByUserID'];
                            if (!empty($LevelFirstRffferID) && $FirstLevel != 0) {
                                $FirstLevelDeposit = ($WalletAmount * $FirstLevel) / 100;
                                /** add to wallet amount * */
                                $WalletData = array(
                                    "Amount" => $FirstLevelDeposit,
                                    "WinningAmount" => $FirstLevelDeposit,
                                    "TransactionType" => 'Cr',
                                    "Narration" => 'Referral Deposit',
                                    "AmountType" => "Referral",
                                    "ReferralGetAmountUserID" => $UserID,
                                    "EntryDate" => date("Y-m-d H:i:s")
                                );
                                $this->Users_model->addToWallet($WalletData, $LevelFirstRffferID, 5);


                                /** get second level * */
                                $LevelSecond = $this->Users_model->getUserReferralBy($LevelFirstRffferID);

                                if (!empty($LevelSecond['Records'])) {
                                    $LevelSecondRffferID = $LevelSecond['Records']['ReferredByUserID'];

                                    if (!empty($LevelSecondRffferID) && $SecondLevel != 0) {
                                        /** get 1.5% on first level * */
                                        $SecondLevelDeposit = ($WalletAmount * $SecondLevel) / 100;
                                        /** add to wallet amount * */
                                        $WalletData = array(
                                            "Amount" => $SecondLevelDeposit,
                                            "WinningAmount" => $SecondLevelDeposit,
                                            "TransactionType" => 'Cr',
                                            "Narration" => 'Referral Deposit',
                                            "AmountType" => "Referral",
                                            "ReferralGetAmountUserID" => $UserID,
                                            "EntryDate" => date("Y-m-d H:i:s")
                                        );
                                        $this->Users_model->addToWallet($WalletData, $LevelSecondRffferID, 5);

                                        /** get third level * */
                                        $LevelThird = $this->Users_model->getUserReferralBy($LevelSecondRffferID);
                                        if (!empty($LevelThird['Records'])) {
                                            $LevelThirdRffferID = $LevelThird['Records']['ReferredByUserID'];
                                            if (!empty($LevelThirdRffferID) && $ThirdLevel != 0) {
                                                /** get 1% on first level * */
                                                $ThirdLevelDeposit = ($WalletAmount * $ThirdLevel) / 100;
                                                /** add to wallet amount * */
                                                $WalletData = array(
                                                    "Amount" => $ThirdLevelDeposit,
                                                    "WinningAmount" => $ThirdLevelDeposit,
                                                    "TransactionType" => 'Cr',
                                                    "Narration" => 'Referral Deposit',
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
                }
            }
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return $this->getWalletDetails($UserID);
    }

    function confirmApp($Input = array(), $UserID) {
        //$this->db->trans_start();

        /* Verify payment from payment gateway */
        if ($Input['PaymentGatewayStatus'] == 'Success') {

            $PaymentGatewayResponse = (!empty($Input['PaymentGatewayResponse'])) ? @json_decode($Input['PaymentGatewayResponse'], TRUE) : NULL;

            /* Razorpay */
            if ($Input['PaymentGateway'] == 'Razorpay') {

                $this->db->select('WalletID');
                $this->db->where("WalletID", $Input['WalletID']);
                $this->db->where("UserID", $UserID);
                $this->db->where("PaymentGateway", "Razorpay");
                $this->db->where('StatusID', 5);
                $this->db->from('tbl_users_wallet');
                $this->db->limit(1);
                $WalletDetails = $this->db->get()->row();
                if (!empty($WalletDetails)) {
                    return $this->getWalletDetails($UserID);
                }

                $CURL = curl_init();
                curl_setopt_array($CURL, array(
                    CURLOPT_URL => "https://" . RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET . "@api.razorpay.com/v1/payments/" . @$Input['Razor_payment_id'] . "/capture",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"amount\"\r\n\r\n" . @$Input['Amount'] . "00\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
                    CURLOPT_HTTPHEADER => array(
                        "cache-control: no-cache",
                        "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
                    ),
                ));

                $Response = curl_exec($CURL);
                $Err = curl_error($CURL);
                curl_close($CURL);
                if ($Err) {
                    return FALSE;
                } else {
                    $ResponseArr = json_decode($Response, true);
                    if (isset($ResponseArr['error']) === false) {
                        $Input['PaymentGatewayStatus'] = 'Success';
                        $Input['PaymentGatewayResponse'] = $Response;
                    } else {
                        $ResponseArr['razorpay_payment_id'] = @$PaymentGatewayResponse['razorpay_payment_id'];
                        $Input['PaymentGatewayStatus'] = 'Failed';
                        $Input['PaymentGatewayResponse'] = json_encode($ResponseArr);
                    }
                }
            } else if ($Input['PaymentGateway'] == 'Paytm') {
                /* Generate Checksum */
                $ParamList = array();
                $ParamList['MID'] = APP_PAYTM_MERCHANT_ID;
                $ParamList['ORDER_ID'] = $Input['WalletID'];
                $ParamList['CUST_ID'] = "CUST" . $UserID;
                $ParamList['INDUSTRY_TYPE_ID'] = APP_PAYTM_INDUSTRY_TYPE_ID;
                $ParamList['CHANNEL_ID'] = 'WAP';
                $ParamList['TXN_AMOUNT'] = $Input['Amount'];
                $ParamList['WEBSITE'] = APP_PAYTM_WEBSITE_APP;
                $ParamList['CALLBACK_URL'] = 'https://' . APP_PAYTM_DOMAIN . '/theia/paytmCallback?ORDER_ID=' . $Input['WalletID'];
                $MerchantKey = APP_PAYTM_MERCHANT_KEY;
                $Checksumvalue = $PaymentGatewayResponse['CHECKSUMHASH'];
                $IsValidChecksum = $this->verifychecksum_e($ParamList, $MerchantKey, $Checksumvalue);
                if ($PaymentGatewayResponse["STATUS"] != "TXN_SUCCESS") {
                    return FALSE;
                }
            }
        }

        /* update profile table */
        $UpdataData = array_filter(
                array(
                    'PaymentGatewayResponse' => @$Input['PaymentGatewayResponse'],
                    'ModifiedDate' => date("Y-m-d H:i:s"),
                    'StatusID' => ($Input['PaymentGatewayStatus'] == 'Failed' || $Input['PaymentGatewayStatus'] == 'Cancelled') ? 3 : 5
        ));
        $this->db->where('WalletID', $Input['WalletID']);
        $this->db->where('UserID', $UserID);
        $this->db->where('StatusID', 1);
        $this->db->limit(1);
        $this->db->update('tbl_users_wallet', $UpdataData);
        if ($this->db->affected_rows() <= 0) return $this->getWalletDetails($UserID);


        $this->db->select("Amount");
        $this->db->from('tbl_users_wallet');
        $this->db->where('WalletID', $Input['WalletID']);
        $this->db->where('UserID', $UserID);
        $this->db->where('StatusID', 5);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Amount = $Query->row()->Amount;
            /* Update user main wallet amount */
            if ($Input['PaymentGatewayStatus'] == 'Success') {

                $this->db->set('ClosingWalletAmount', 'ClosingWalletAmount+' . @$Input['Amount'], FALSE);
                $this->db->where('WalletID', $Input['WalletID']);
                $this->db->where('UserID', $UserID);
                $this->db->where('StatusID', 5);
                $this->db->limit(1);
                $this->db->update('tbl_users_wallet');


                $this->db->set('WalletAmount', 'WalletAmount+' . @$Input['Amount'], FALSE);
                $this->db->where('UserID', $UserID);
                $this->db->limit(1);
                $this->db->update('tbl_users');

                $this->Notification_model->addNotification('AddCash', 'Cash Added', $UserID, $UserID, '', 'Deposit of ' . DEFAULT_CURRENCY . @$Input['Amount'] . ' is Successful.');

                $CouponDetails = $this->getWallet('CouponDetails', array("WalletID" => $Input['WalletID']));

                /* Check Coupon Details */
                if (!empty($CouponDetails['CouponDetails'])) {
                    $WalletData = array(
                        "Amount" => $CouponDetails['CouponDetails']['DiscountedAmount'],
                        "CashBonus" => ($CouponDetails['CouponDetails']['OfferType'] == "CashBonus" ? $CouponDetails['CouponDetails']['DiscountedAmount'] : 0),
                        "WalletAmount" => ($CouponDetails['CouponDetails']['OfferType'] == "RealMoney" ? $CouponDetails['CouponDetails']['DiscountedAmount'] : 0),
                        "TransactionType" => 'Cr',
                        "EntityID" => $Input['WalletID'],
                        "Narration" => ($CouponDetails['CouponDetails']['OfferType'] == "RealMoney" ? 'Coupon Discount' : 'Coupon Discount Bonus'),
                        "EntryDate" => date("Y-m-d H:i:s")
                    );
                    $this->addToWallet($WalletData, $UserID, 5);
                }

                /* Manage First Deposit & Referral Bonus */
                $TotalDeposits = $this->db->query('SELECT COUNT(*) TotalDeposits FROM `tbl_users_wallet` WHERE `UserID` = ' . $UserID . ' AND Narration = "Deposit Money" AND StatusID = 5')->row()->TotalDeposits;
                if ($TotalDeposits == 1) { // On First Successful Transaction

                    /* Get Deposit Bonus Data */
                    $DepositBonusData = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "FirstDepositBonus" LIMIT 1');
                    if ($DepositBonusData->row()->StatusID == 2) {

                        $MinimumFirstTimeDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MinimumFirstTimeDepositLimit" LIMIT 1');
                        $MaximumFirstTimeDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MaximumFirstTimeDepositLimit" LIMIT 1');

                        if ($MinimumFirstTimeDepositLimit->row()->ConfigTypeValue <= $Input['Amount'] && $MaximumFirstTimeDepositLimit->row()->ConfigTypeValue >= $Input['Amount']) {
                            /* Update Wallet */
                            $FirstTimeAmount = ($Input['Amount'] * $DepositBonusData->row()->ConfigTypeValue) / 100;
                            $WalletData = array(
                                "Amount" => $FirstTimeAmount,
                                "CashBonus" => $FirstTimeAmount,
                                "TransactionType" => 'Cr',
                                "Narration" => 'First Deposit Bonus',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->addToWallet($WalletData, $UserID, 5);
                        }
                    }

                    /* Get User Data */
                    $UserData = $this->getUsers('ReferredByUserID', array("UserID" => $UserID));
                    if (!empty($UserData['ReferredByUserID'])) {

                        /* Get Referral To Bonus Data */
                        $ReferralToBonus = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "ReferToDepositBonus" LIMIT 1');
                        if ($ReferralToBonus->row()->StatusID == 2) {

                            /* Update Wallet */
                            $WalletData = array(
                                "Amount" => $ReferralToBonus->row()->ConfigTypeValue,
                                "CashBonus" => $ReferralToBonus->row()->ConfigTypeValue,
                                "TransactionType" => 'Cr',
                                "Narration" => 'Referral Bonus',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->addToWallet($WalletData, $UserID, 5);
                            $this->Notification_model->addNotification('ReferralBonus', 'Referred Bonus Added', $UserID, $UserID, '', 'You have received ' . DEFAULT_CURRENCY . @$ReferralToBonus->row()->ConfigTypeValue . ' Cash Bonus for Referred.');
                        }

                        /* Get Referral By Bonus Data */
                        $ReferralByBonus = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "ReferByDepositBonus" LIMIT 1');
                        if ($ReferralByBonus->row()->StatusID == 2) {

                            /* Update Wallet */
                            $WalletData = array(
                                "Amount" => $ReferralByBonus->row()->ConfigTypeValue,
                                "CashBonus" => $ReferralByBonus->row()->ConfigTypeValue,
                                "TransactionType" => 'Cr',
                                "Narration" => 'Referral Bonus',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $this->addToWallet($WalletData, $UserData['ReferredByUserID'], 5);
                            $this->Notification_model->addNotification('ReferralBonus', 'Referral Bonus Added', $UserData['ReferredByUserID'], $UserData['ReferredByUserID'], '', 'You have received ' . DEFAULT_CURRENCY . @$ReferralByBonus->row()->ConfigTypeValue . ' Cash Bonus for Successful Referral.');
                        }
                    }
                }

                /* MLM Referrals Wallet */
                $MLMISActive = FALSE;
                $FirstLevel = 0;
                $SecondLevel = 0;
                $ThirdLevel = 0;
                $MLMConfigType = $this->db->query('SELECT ConfigTypeGUID,ConfigTypeValue,StatusID FROM set_site_config '
                                . 'WHERE (ConfigTypeGUID = "MlmIsActive" OR ConfigTypeGUID = "MlmFirstLevel" OR ConfigTypeGUID = '
                                . '"MlmSecondLevel" OR ConfigTypeGUID = "MlmThirdLevel")')->result_array();

                if (!empty($MLMConfigType)) {
                    foreach ($MLMConfigType as $ConfigValue) {
                        if ($ConfigValue['ConfigTypeGUID'] == "MlmIsActive") {
                            if ($ConfigValue['ConfigTypeValue'] == "Yes" && $ConfigValue['StatusID'] == 2) {
                                $MLMISActive = TRUE;
                            }
                        }
                        if ($ConfigValue['ConfigTypeGUID'] == "MlmFirstLevel" && $ConfigValue['StatusID'] == 2) {
                            $FirstLevel = $ConfigValue['ConfigTypeValue'];
                        }
                        if ($ConfigValue['ConfigTypeGUID'] == "MlmSecondLevel" && $ConfigValue['StatusID'] == 2) {
                            $SecondLevel = $ConfigValue['ConfigTypeValue'];
                        }
                        if ($ConfigValue['ConfigTypeGUID'] == "MlmThirdLevel" && $ConfigValue['StatusID'] == 2) {
                            $ThirdLevel = $ConfigValue['ConfigTypeValue'];
                        }
                    }
                }
                if ($MLMISActive) {
                    $WalletAmount = $Amount;

                    if ($WalletAmount > 0) {
                        /** get first level * */
                        $LevelFirst = $this->Users_model->getUserReferralBy($UserID);

                        if (!empty($LevelFirst['Records'])) {

                            /** get 2.5% on first level * */
                            $LevelFirstRffferID = $LevelFirst['Records']['ReferredByUserID'];
                            if (!empty($LevelFirstRffferID) && $FirstLevel != 0) {
                                $FirstLevelDeposit = ($WalletAmount * $FirstLevel) / 100;
                                /** add to wallet amount * */
                                $WalletData = array(
                                    "Amount" => $FirstLevelDeposit,
                                    "WinningAmount" => $FirstLevelDeposit,
                                    "TransactionType" => 'Cr',
                                    "Narration" => 'Referral Deposit',
                                    "AmountType" => "Referral",
                                    "ReferralGetAmountUserID" => $UserID,
                                    "EntryDate" => date("Y-m-d H:i:s")
                                );
                                $this->Users_model->addToWallet($WalletData, $LevelFirstRffferID, 5);


                                /** get second level * */
                                $LevelSecond = $this->Users_model->getUserReferralBy($LevelFirstRffferID);

                                if (!empty($LevelSecond['Records'])) {
                                    $LevelSecondRffferID = $LevelSecond['Records']['ReferredByUserID'];

                                    if (!empty($LevelSecondRffferID) && $SecondLevel != 0) {
                                        /** get 1.5% on first level * */
                                        $SecondLevelDeposit = ($WalletAmount * $SecondLevel) / 100;
                                        /** add to wallet amount * */
                                        $WalletData = array(
                                            "Amount" => $SecondLevelDeposit,
                                            "WinningAmount" => $SecondLevelDeposit,
                                            "TransactionType" => 'Cr',
                                            "Narration" => 'Referral Deposit',
                                            "AmountType" => "Referral",
                                            "ReferralGetAmountUserID" => $UserID,
                                            "EntryDate" => date("Y-m-d H:i:s")
                                        );
                                        $this->Users_model->addToWallet($WalletData, $LevelSecondRffferID, 5);

                                        /** get third level * */
                                        $LevelThird = $this->Users_model->getUserReferralBy($LevelSecondRffferID);
                                        if (!empty($LevelThird['Records'])) {
                                            $LevelThirdRffferID = $LevelThird['Records']['ReferredByUserID'];
                                            if (!empty($LevelThirdRffferID) && $ThirdLevel != 0) {
                                                /** get 1% on first level * */
                                                $ThirdLevelDeposit = ($WalletAmount * $ThirdLevel) / 100;
                                                /** add to wallet amount * */
                                                $WalletData = array(
                                                    "Amount" => $ThirdLevelDeposit,
                                                    "WinningAmount" => $ThirdLevelDeposit,
                                                    "TransactionType" => 'Cr',
                                                    "Narration" => 'Referral Deposit',
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
                }
            }
        }
//        $this->db->trans_complete();
//        if ($this->db->trans_status() === FALSE) {
//            return FALSE;
//        }
        return $this->getWalletDetails($UserID);
    }

    /*
      Description: To add data into user wallet
     */

    function addToWallet($Input = array(), $UserID, $StatusID = 1) {
        $this->db->trans_start();

        $InsertData = array_filter(array(
            "UserID" => $UserID,
            "Amount" => @$Input['Amount'],
            "WalletAmount" => @$Input['WalletAmount'],
            "WinningAmount" => @$Input['WinningAmount'],
            "CashBonus" => @$Input['CashBonus'],
            "SportsType" => @$Input['SportsType'],
            "Currency" => @$Input['Currency'],
            "CouponCode" => @$Input['CouponCode'],
            "PaymentGateway" => @$Input['PaymentGateway'],
            "TransactionType" => @$Input['TransactionType'],
            "TransactionID" => (!empty($Input['TransactionID'])) ? $Input['TransactionID'] : substr(hash('sha256', mt_rand() . microtime()), 0, 20),
            "Narration" => @$Input['Narration'],
            "EntityID" => @$Input['EntityID'],
            "UserTeamID" => @$Input['UserTeamID'],
            "CouponDetails" => @$Input['CouponDetails'],
            "ReferralGetAmountUserID" => @$Input['ReferralGetAmountUserID'],
            "AmountType" => @$Input['AmountType'],
            "PaymentGatewayResponse" => @$Input['PaymentGatewayResponse'],
            "EntryDate" => date("Y-m-d H:i:s"),
            "StatusID" => $StatusID
        ));
        $this->db->insert('tbl_users_wallet', $InsertData);
        $WalletID = $this->db->insert_id();

        /* Update User Balance */
        // if ($StatusID == 5 || $Input['Narration'] == 'Withdrawal Request') {
        //     switch (@$Input['Narration']) {
        //         case 'Deposit Money':
        //         case 'Admin Deposit Money':
        //             $this->db->set('WalletAmount', 'WalletAmount+' . @$Input['Amount'], FALSE);
        //             break;
        //         case 'Join Contest Winning':
        //             $this->db->set('WinningAmount', 'WinningAmount+' . @$Input['WinningAmount'], FALSE);
        //             break;
        //         case 'Join Contest':
        //             $this->db->set('WalletAmount', 'WalletAmount-' . @$Input['WalletAmount'], FALSE);
        //             $this->db->set('WinningAmount', 'WinningAmount-' . @$Input['WinningAmount'], FALSE);
        //             $this->db->set('CashBonus', 'CashBonus-' . @$Input['CashBonus'], FALSE);
        //             break;
        //         case 'Cancel Contest':
        //             $this->db->set('WalletAmount', 'WalletAmount+' . @$Input['WalletAmount'], FALSE);
        //             $this->db->set('WinningAmount', 'WinningAmount+' . @$Input['WinningAmount'], FALSE);
        //             $this->db->set('CashBonus', 'CashBonus+' . @$Input['CashBonus'], FALSE);
        //             break;
        //         case 'Wrong Winning Distribution':
        //             $this->db->set('WinningAmount', 'WinningAmount-' . @$Input['WinningAmount'], FALSE);
        //             break;
        //         case 'Referral Deposit':
        //             if ($Input['WinningAmount'] > 0) {
        //                 $this->db->set('WinningAmount', 'WinningAmount+' . @$Input['WinningAmount'], FALSE);
        //             }
        //             break;
        //         case 'Lucky Wheel':
        //         case 'Signup Bonus':
        //         case 'Verification Bonus':
        //         case 'First Deposit Bonus':
        //         case 'Referral Bonus':
        //         case 'Admin Cash Bonus':
        //         case 'Coupon Discount Bonus':
        //             if ($Input['CashBonus'] > 0) {
        //                 $this->db->set('CashBonus', 'CashBonus+' . @$Input['Amount'], FALSE);
        //             }
        //             break;
        //         case 'Coupon Discount':
        //         case 'Signup Direct Deposit':
        //             if ($Input['WalletAmount'] > 0) {
        //                 $this->db->set('WalletAmount', 'WalletAmount+' . @$Input['Amount'], FALSE);
        //             }
        //             break;
        //         case 'Withdrawal Request':
        //             $this->db->set('WinningAmount', 'WinningAmount-' . @$Input['WinningAmount'], FALSE);
        //             if (@$Input['WithdrawalStatus'] == 1) {
        //                 $this->db->set('WithdrawalHoldAmount', 'WithdrawalHoldAmount+' . @$Input['WinningAmount'], FALSE);
        //             }
        //             break;
        //         case 'Withdrawal Reject':
        //             $this->db->set('WinningAmount', 'WinningAmount+' . @$Input['WinningAmount'], FALSE);
        //             $this->db->set('WithdrawalHoldAmount', 'WithdrawalHoldAmount-' . @$Input['WinningAmount'], FALSE);
        //             break;
        //         case 'Cash Bonus Expire':
        //             $this->db->set('CashBonus', 'CashBonus-' . @$Input['Amount'], FALSE);
        //             break;
        //         default:
        //             break;
        //     }
        //     $this->db->where('UserID', $UserID);
        //     $this->db->limit(1);
        //     $this->db->update('tbl_users');
        // }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return $WalletID;
    }

    /*
      Description: To get user wallet opening balance
     */

    function getUserWalletOpeningBalance($UserID, $Field) {
        $Query = $this->db->query('SELECT IF(' . $Field . ' IS NULL,0,' . $Field . ') Amount FROM `tbl_users_wallet` WHERE StatusID = 5 AND `UserID` = ' . $UserID . ' ORDER BY `WalletID` DESC LIMIT 1');
        if ($Query->num_rows() > 0) {
            return $Query->row()->Amount;
        } else {
            return $this->db->query('SELECT ' . str_replace("Closing", "", $Field) . ' Amount FROM `tbl_users` WHERE `UserID` = ' . $UserID . ' LIMIT 1')->row()->Amount;
        }
    }

    /*
      Description: To get user wallet details
     */

    function getWalletDetails($UserID) {
        return $this->db->query('SELECT `WalletAmount`,`WinningAmount`,`CashBonus`,(WalletAmount + WinningAmount + CashBonus) AS TotalCash FROM `tbl_users` WHERE `UserID` =' . $UserID . ' LIMIT 1')->row();
    }

    function getDeposits($Where = array(), $PageNo = 1, $PageSize = 15) {

        $this->db->select('W.UserID,W.Amount,W.PaymentGateway,W.TransactionID,DATE_FORMAT(CONVERT_TZ(W.EntryDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") EntryDate,U.Email,U.PhoneNumber,U.FirstName,U.LastName');
        $this->db->from('tbl_users_wallet W');
        $this->db->from('tbl_users U');
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("U.FirstName", $Where['Keyword']);
            $this->db->or_like("U.LastName", $Where['Keyword']);
            $this->db->or_like("U.Email", $Where['Keyword']);
            $this->db->or_like("CONCAT_WS('',U.FirstName,U.Middlename,U.LastName)", preg_replace('/\s+/', '', $Where['Keyword']), FALSE);
            $this->db->group_end();
        }
        $this->db->where('W.UserID', "U.UserID", false);
        $this->db->where('W.Narration', "Deposit Money");
        $this->db->where('W.StatusID', 5);

        if (!empty($Where['Type']) && $Where['Type'] == 'Today') {
            $this->db->where("W.EntryDate >=", date('Y:m:d'));
        }
        if (!empty($Where['FromDate'])) {
            $this->db->where("W.EntryDate >=", $Where['FromDate']);
        }
        if (!empty($Where['ToDate'])) {
            $this->db->where("W.EntryDate <=", $Where['ToDate']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        if ($PageNo != 0) {
            $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
        }

        $DepositsData = $this->db->get();
        // echo $this->db->last_query(); die();

        $Return['Data']['Records'] = $DepositsData->result_array();

        return $Return;
    }

    /*
      Description: To get user wallet data
     */

    function getWallet($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        $Return = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'UserID' => 'W.UserID',
                'Amount' => 'W.Amount',
                'OpeningWalletAmount' => 'W.OpeningWalletAmount',
                'OpeningWinningAmount' => 'W.OpeningWinningAmount',
                'OpeningCashBonus' => 'W.OpeningCashBonus',
                'WalletAmount' => 'W.WalletAmount',
                'WinningAmount' => 'W.WinningAmount',
                'CashBonus' => 'W.CashBonus',
                'EntityID' => 'W.EntityID',
                'UserTeamID' => 'W.UserTeamID',
                'ClosingWalletAmount' => 'W.ClosingWalletAmount',
                'ClosingWinningAmount' => 'W.ClosingWinningAmount',
                'ClosingCashBonus' => 'W.ClosingCashBonus',
                'WithdrawalHoldAmount' => 'W.WithdrawalHoldAmount',
                'Currency' => 'W.Currency',
                'PaymentGateway' => 'W.PaymentGateway',
                'CouponDetails' => 'W.CouponDetails',
                'TransactionType' => 'W.TransactionType',
                'TransactionID' => 'W.TransactionID',
                'OpeningBalance' => '(W.OpeningWalletAmount + W.OpeningWinningAmount + W.OpeningCashBonus) OpeningBalance',
                'ClosingBalance' => '(W.ClosingWalletAmount + W.ClosingWinningAmount + W.ClosingCashBonus) ClosingBalance',
                'Narration' => 'W.Narration',
                'EntryDate' => 'DATE_FORMAT(CONVERT_TZ(W.EntryDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") EntryDate',
                'Status' => 'CASE W.StatusID
                                        when "1" then "Pending"
                                        when "2" then "Processing"
                                        when "3" then "Failed"
                                        when "5" then "Completed"
                                    END as Status',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }

        if (in_array('MediaBANK', $Params)) {
            $Media['BANK'] = array(
                'EntryDate' => '',
                'MediaGUID' => '',
                'MediaURL' => '',
                'MediaThumbURL' => '',
                'MediaCaption' => (object)[]
            );
            $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID,DATE_FORMAT(CONVERT_TZ(E.EntryDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") EntryDate, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,"110_",M.MediaName) AS MediaThumbURL, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaURL,	M.MediaCaption', array("SectionID" => 'BankDetail', "EntityID" => $Where['UserID']), FALSE);
            if ($MediaData) $MediaData['MediaCaption'] = json_decode($MediaData['MediaCaption']);
            $Return['Data']['MediaBANK'] = ($MediaData ? $MediaData : $Media['BANK']);

            $MaximumWithdrawalLimitBank =  $this->db->query("SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = 'MaximumWithdrawalLimitBank'")->row()->ConfigTypeValue;
            $MinimumWithdrawalLimitBank =  $this->db->query("SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = 'MinimumWithdrawalLimitBank'")->row()->ConfigTypeValue;
            
            $MaximumWithdrawalLimitPaytm =  $this->db->query("SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = 'MaximumWithdrawalLimitPaytm'")->row()->ConfigTypeValue;
            $MinimumWithdrawalLimitPaytm =  $this->db->query("SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = 'MinimumWithdrawalLimitPaytm'")->row()->ConfigTypeValue;
           
            $Return['Data']['MediaBANK']['Message'] = "1.In Bank User can withdraw (minimum ".$MinimumWithdrawalLimitBank." and maximum of INR ".$MaximumWithdrawalLimitBank." per day)\n\n2.In Paytm Wallet User Can Withdraw (minimum ".$MinimumWithdrawalLimitPaytm." and maximum of INR ".$MaximumWithdrawalLimitPaytm." per day)\n\n3.Normal Bank Withdrawal a minimum of INR ".$MinimumWithdrawalLimitBank." and maximum  amount INR ". $MaximumWithdrawalLimitBank." in a day. Gets Processed In your bank account within 2-3 Working Days.\n\n4.Withdrawal timings are 11.00AM to 8.00PM (In case of instant withdrawal)";
        }

        if (in_array('WalletDetails', $Params)) {
            $WalletData = $this->db->query('select WalletAmount,WinningAmount,CashBonus,(WalletAmount + WinningAmount + CashBonus) AS TotalCash from tbl_users where UserID = ' . $Where['UserID'])->row();
            $Return['Data']['WalletAmount'] = $WalletData->WalletAmount;
            $Return['Data']['CashBonus'] = $WalletData->CashBonus;
            $Return['Data']['WinningAmount'] = $WalletData->WinningAmount;
            $Return['Data']['TotalCash'] = $WalletData->TotalCash;
        }

        if (in_array('VerificationDetails', $Params)) {
            $UserVerificationData = $this->Users_model->getUsers('Status,PanStatus,BankStatus,PhoneStatus', array('UserID' => $Where['UserID']));
            $Return['Data']['Status'] = $UserVerificationData['Status'];
            $Return['Data']['PanStatus'] = $UserVerificationData['PanStatus'];
            $Return['Data']['BankStatus'] = $UserVerificationData['BankStatus'];
            $Return['Data']['PhoneStatus'] = $UserVerificationData['PhoneStatus'];
        }
        if (in_array('CashbonusExpiry', $Params)) {
            $Return['Data']['CashbonusMessage'] = $this->getUserCashbonusExpiry($Where['UserID']);
        }

        $this->db->select('W.WalletID');
        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_users_wallet W');
        if (!empty($Where['WalletID'])) {
            $this->db->where("W.WalletID", $Where['WalletID']);
        }
        if (!empty($Where['CouponCode'])) {
            $this->db->where("W.CouponCode", $Where['CouponCode']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("W.UserID", $Where['UserID']);
        }
        if (!empty($Where['PaymentGateway'])) {
            $this->db->where("W.PaymentGateway", $Where['PaymentGateway']);
        }
        if (!empty($Where['TransactionType'])) {
            $this->db->where("W.TransactionType", $Where['TransactionType']);
        }
        if (!empty($Where['Narration'])) {
            $this->db->where("W.Narration", $Where['Narration']);
        }
        if (!empty($Where['EntityID'])) {
            $this->db->where("W.EntityID", $Where['EntityID']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("W.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['TransactionMode']) && $Where['TransactionMode'] != 'All') {
            $this->db->where("W." . $Where['TransactionMode'] . ' >', 0);
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'FailedCompleted') {
            $this->db->where_in("W.StatusID", array(3, 5));
        }
        if (!empty($Where['FromDate'])) {
            $this->db->where("W.EntryDate >=", $Where['FromDate']);
        }
        if (!empty($Where['ToDate'])) {
            $this->db->where("W.EntryDate <=", $Where['ToDate']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("W.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        $this->db->order_by('W.WalletID', 'DESC');

        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        $Query = $this->db->get();

        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $Records[$key]['CouponDetails'] = (!empty($Record['CouponDetails'])) ? json_decode($Record['CouponDetails'], TRUE) : array();
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {

                $Record = $Query->row_array();
                $Record['CouponDetails'] = (!empty($Record['CouponDetails'])) ? json_decode($Record['CouponDetails'], TRUE) : array();
                return $Record;
            }
        } else {
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: To send OTP for withdrawal
     */

    function getUserCashbonusExpiry($UserID) {
        $Return = "";
        $ConfigData = $this->db->query("SELECT ConfigTypeGUID, ConfigTypeValue, StatusID FROM `set_site_config` WHERE ConfigTypeGUID = 'CashBonusExpireTimeInDays'");
        $ConfigDatas = $ConfigData->result_array();
        if (!empty($ConfigData)) {
            if ($ConfigDatas[0]['StatusID']) {
                if ($ConfigDatas[0]['ConfigTypeValue'] > 0 && is_numeric($ConfigDatas[0]['ConfigTypeValue'])) {
                    $prevDate = date('Y-m-d', strtotime('-' . $ConfigDatas[0]['ConfigTypeValue'] - 10 . ' day', strtotime(date('Y-m-d'))));
                    $NextDates = date('Y-m-d', strtotime('+' . 10 . ' day', strtotime(date('Y-m-d'))));

                    $wallets = $this->db->query("SELECT UserID,WalletAmount,CashBonus,WinningAmount,Email,FirstName FROM `tbl_users` WHERE tbl_users.CashBonus > 0 AND UserID=$UserID");
                    $cashBonusUser = $wallets->result_array();

                    foreach ($cashBonusUser as $value) {
                        $options = $this->db->query("SELECT SUM(CashBonus) as trans_cash_bonus FROM `tbl_users_wallet` "
                                . "WHERE TransactionType = 'Cr' AND `Narration` IN "
                                . "('Signup Bonus','Admin Cash Bonus','First Deposit Bonus','Verification Bonus','Referral Bonus','Coupon Discount Bonus','Referral Winning') "
                                . "AND `EntryDate` < '" . $prevDate . "' AND UserID = '" . $value['UserID'] . "'");
                        $cashExpireUser = $options->result_array();

                        if (!empty($cashExpireUser[0]['trans_cash_bonus'])) {
                            foreach ($cashExpireUser as $cashExpire) {
                                $options = $this->db->query("SELECT SUM(CashBonus) as trans_total_cash_bonus FROM `tbl_users_wallet`"
                                        . " WHERE TransactionType = 'Cr' AND `Narration` IN "
                                        . "('Signup Bonus','Admin Cash Bonus','First Deposit Bonus','Verification Bonus','Referral Bonus','Coupon Discount Bonus','Referral Winning') AND UserID = '" . $value['UserID'] . "'");
                                $totalBonusExpireUser = $options->result_array();

                                if (!empty($totalBonusExpireUser[0]['trans_total_cash_bonus'])) {
                                    foreach ($totalBonusExpireUser as $key => $totalBonusExpire) {

                                        $expireBonus = $totalBonusExpire['trans_total_cash_bonus'] - $cashExpire['trans_cash_bonus'];

                                        if ($expireBonus >= 0) {

                                            $actaulCashBonusExpireAmount = $value['CashBonus'] - $expireBonus;

                                            if ($actaulCashBonusExpireAmount > 1 && ($value['CashBonus'] - $actaulCashBonusExpireAmount) >= 0) {

                                                if (($value['CashBonus'] - $actaulCashBonusExpireAmount) >= 0 && ($value['WalletAmount'] + $value['WinningAmount'] + $value['CashBonus'] - $actaulCashBonusExpireAmount) > 0) {
//                                                    $Return = "We noticed that you have a Cash Bonus balance in your FSL11 account which was credited " . $ConfigDatas[0]['ConfigTypeValue'] . " days ago. "
//                                                            . "This unused Cash Bonus in your account will be forfeited on $NextDates.";
                                                    $Return = "₹$actaulCashBonusExpireAmount cash bonus which was credited in your account " . $ConfigDatas[0]['ConfigTypeValue'] . " days ago, remaining Cash Bonus in your account will be forfeited on $NextDates.";
                                                    return $Return;
                                                    /* sendPushMessage($value['UserID'], $NotificationTitle, $NotificationMessage); */
                                                    /* $this->Notification_model->addNotification('bonus', $NotificationTitle, $value['UserID'], $value['UserID'], '', $NotificationMessage); */
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $Return;
    }

    /*
      Description: To send OTP for withdrawal
     */

    public function withdrawalOTP($Input = array(), $UserID) {
        /* Insert Withdrawal Add Request */
        $OTP = random_string('numeric', 6);
        $InsertData = array_filter(array(
            "UserID" => $UserID,
            "Amount" => @$Input['Amount'],
            "PaytmPhoneNumber" => @$Input['PaytmPhoneNumber'],
            "OTP" => $OTP,
            "IsOTPVerified" => "No",
            "PaymentGateway" => $Input['PaymentGateway'],
            "EntryDate" => date("Y-m-d H:i:s"),
            "StatusID" => 1,
        ));
        $this->db->insert('tbl_users_withdrawal', $InsertData);
        $WithdrawalID = $this->db->insert_id();
        if (empty($WithdrawalID)) {
            return false;
        }

        /* Verify OTP Send SMS */
        $this->Utility_model->sendSMS(array(
            'PhoneNumber' => $Input['PaytmPhoneNumber'],
            'Text' => SITE_NAME . ", OTP for withdraw request is: " . $OTP,
        ));
        return array('WithdrawalID' => $WithdrawalID, 'WalletDetails' => $this->getWalletDetails($UserID));
    }

    /*
      Description: To withdrawal amount
     */

    function withdrawal($Input = array(), $UserID) {
        if (AUTO_WITHDRAWAL && $Input['PaymentGateway'] == 'Paytm') {
            /* Withdraw to Paytm Account */
            $StatusID = 3;
            $OrderID = "Order" . substr(hash('sha256', mt_rand() . microtime()), 0, 10);
            $data = array(
                "request" => array("requestType" => $OrderID,
                    "merchantGuid" => PAYTM_MERCHANT_GUID,
                    "merchantOrderId" => $OrderID,
                    "salesWalletName" => null,
                    "salesWalletGuid" => PAYTM_SALES_WALLET_GUID,
                    "payeeEmailId" => null,
                    "payeePhoneNumber" => @$Input['PaytmPhoneNumber'],
                    "payeeSsoId" => "",
                    "appliedToNewUsers" => "N",
                    "amount" => @$Input['Amount'],
                    "currencyCode" => "INR"
                ),
                "metadata" => "Withdraw Money",
                // "ipAddress"     => $this->input->ip_address(),
                "ipAddress" => "127.0.0.1",
                "platformName" => "PayTM",
                "operationType" => "SALES_TO_USER_CREDIT"
            );
            $requestData = json_encode($data);
            $Checksumhash = $this->getChecksumFromString($requestData, PAYTM_MERCHANT_KEY_WITHDRAWAL);
            $headerValue = array('Content-Type:application/json', 'mid:' . PAYTM_MERCHANT_GUID, 'checksumhash:' . $Checksumhash);

            $ch = curl_init(PAYTM_GRATIFICATION_URL);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the output in string format
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerValue);
            $info = curl_getinfo($ch);
            $PaymentGatewayResponse = curl_exec($ch);
            $PaymentGatewayResponse = json_decode($PaymentGatewayResponse);
            $StatusArr = array("FAILURE" => 3, "SUCCESS" => 5, "PENDING" => 3); // NEED TO MANAGE WEBHOOKS IN PENDING CASE
            $StatusID = (!empty($PaymentGatewayResponse)) ? $StatusArr[$PaymentGatewayResponse->status] : 3;
        } else {
            $StatusID = 1;
        }

        $this->db->trans_start();

        if (!empty($this->Post['PaymentGateway']) && $this->Post['PaymentGateway'] == 'Paytm') {
            /* Update Withdrawal Request */
            $InsertData = array_filter(array(
                "UserID" => $UserID,
                "Amount" => @$Input['Amount'],
                "PaytmPhoneNumber" => @$Input['PaytmPhoneNumber'],
                "PaymentGatewayResponse" => (!empty($PaymentGatewayResponse)) ? json_encode($PaymentGatewayResponse) : null,
                "IsOTPVerified" => "Yes",
                "PaymentGateway" => $Input['PaymentGateway'],
                "EntryDate" => date("Y-m-d H:i:s"),
                "ModifiedDate" => date("Y-m-d H:i:s"),
                "StatusID" => $StatusID,
            ));
            $this->db->insert('tbl_users_withdrawal', $InsertData);
            /*$UpdateData = array(
                "IsOTPVerified" => "Yes",
                "PaymentGatewayResponse" => (!empty($PaymentGatewayResponse)) ? json_encode($PaymentGatewayResponse) : null,
                "ModifiedDate" => date("Y-m-d H:i:s"),
                "StatusID" => $StatusID,
            );
            $this->db->where('WithdrawalID', $Input['WithdrawalID']);
            $this->db->limit(1);
            $this->db->update('tbl_users_withdrawal', $UpdateData);*/
        }

        /* Update user winning amount */
        if ($StatusID == 1 || $StatusID == 5) {
            $WalletData = array(
                "Amount" => @$Input['Amount'],
                "WinningAmount" => @$Input['Amount'],
                "TransactionType" => 'Dr',
                "Narration" => 'Withdrawal Request',
                "EntryDate" => date("Y-m-d H:i:s"),
                "WithdrawalStatus" => $StatusID
            );
            $WalletID = $this->addToWallet($WalletData, $UserID, $StatusID);
        }
        if (!empty($this->Post['PaymentGateway']) && $this->Post['PaymentGateway'] == 'Bank') {
            /* Insert Withdrawal Logs */
            $InsertData = array(
                "UserID" => $UserID,
                "WalletID" => $WalletID,
                "Amount" => @$Input['Amount'],
                "PaytmPhoneNumber" => @$Input['PaytmPhoneNumber'],
                "PaymentGatewayResponse" => (!empty($PaymentGatewayResponse)) ? json_encode($PaymentGatewayResponse) : NULL,
                "PaymentGateway" => $Input['PaymentGateway'],
                "EntryDate" => date("Y-m-d H:i:s"),
                "StatusID" => $StatusID
            );
            $this->db->insert('tbl_users_withdrawal', $InsertData);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        $Response = $this->getWalletDetails($UserID);
        $Response->paytmResponse = (!empty($PaymentGatewayResponse) ? $PaymentGatewayResponse : "");

        return $Response;
    }

    /*
      Description: To get user withdrawals data
     */

    function getWithdrawals($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'UserID' => 'W.UserID',
                'Amount' => 'W.Amount',
                'Email' => 'U.Email',
                'PhoneNumber' => 'U.PhoneNumber',
                'FirstName' => 'U.FirstName',
                'Middlename' => 'U.Middlename',
                'LastName' => 'U.LastName',
                'Comments' => 'W.Comments',
                'ProfilePic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic',
                'PaymentGateway' => 'W.PaymentGateway',
                'PaytmPhoneNumber' => 'W.PaytmPhoneNumber',
                'EntryDate' => 'DATE_FORMAT(CONVERT_TZ(W.EntryDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") EntryDate',
                'Status' => 'CASE W.StatusID
                                                            when "1" then "Pending"
                                                            when "2" then "Processing"
                                                            when "3" then "Rejected"
                                                            when "5" then "Completed"
                                                        END as Status',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('W.WithdrawalID,W.UserID');
        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_users_withdrawal W,tbl_users U');
        $this->db->where("W.UserID", "U.UserID", FALSE);

        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            // if (validateEmail($Where['Keyword'])) {
            //     $Where['Email'] = $Where['Keyword'];
            // } elseif (is_numeric($Where['Keyword'])) {
            //     $Where['PhoneNumber'] = $Where['Keyword'];
            // } else {
            $this->db->group_start();
            $this->db->like("U.FirstName", $Where['Keyword']);
            $this->db->or_like("U.LastName", $Where['Keyword']);
            $this->db->or_like("U.Email", $Where['Keyword']);
            $this->db->or_like("CONCAT_WS('',U.FirstName,U.Middlename,U.LastName)", preg_replace('/\s+/', '', $Where['Keyword']), FALSE);
            $this->db->group_end();
            // }
        }
        if (!empty($Where['WithdrawalID'])) {
            $this->db->where("W.WithdrawalID", $Where['WithdrawalID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("W.UserID", $Where['UserID']);
        }
        if (!empty($Where['PaymentGateway'])) {
            $this->db->where("W.PaymentGateway", $Where['PaymentGateway']);
        }
        if (!empty($Where['FromDate'])) {
            $this->db->where("W.EntryDate >=", $Where['FromDate']);
        }
        if (!empty($Where['ToDate'])) {
            $this->db->where("W.EntryDate <=", $Where['ToDate']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("W.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['ListType']) && $Where['ListType'] == 'Pending') {
            $this->db->where("W.StatusID =", 1);
        }
//        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
//            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
//        }
        $this->db->order_by('W.WithdrawalID', 'DESC');

        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                foreach ($Query->result_array() as $Record) {
                    /* get attached media */
                    if (in_array('MediaBANK', $Params)) {
                        $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,"110_",M.MediaName) AS MediaThumbURL, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaURL,    M.MediaCaption', array("SectionID" => 'BankDetail', "EntityID" => $Record['UserID']), FALSE);
                        $MediaData['MediaCaption'] = json_decode($MediaData['MediaCaption']);
                        $Record['MediaBANK'] = ($MediaData ? $MediaData : new stdClass());
                    }

                    $Records[] = $Record;
                }
                $Return['Data']['Records'] = $Records;

                return $Return;
            } else {
                $Record = $Query->row_array();

                /* get attached media */
                if (in_array('MediaBANK', $Params)) {
                    $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,"110_",M.MediaName) AS MediaThumbURL, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaURL,M.MediaCaption', array("SectionID" => 5, "EntityID" => $Record['UserID']), FALSE);
                    if ($MediaData) $MediaData['MediaCaption'] = json_decode($MediaData['MediaCaption']);
                    $Record['MediaBANK'] = ($MediaData ? $MediaData : new stdClass());
                }
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: To user refer & earn
     */

    function referEarn($Input = array(), $SessionUserID) {

        $UserData = $this->Users_model->getUsers('FirstName,ReferralCode', array('UserID' => $SessionUserID));
        $InviteURL = SITE_HOST . ROOT_FOLDER . 'profile?referral=' . $UserData['ReferralCode'];

        if ($Input['ReferType'] == 'Email' && !empty($Input['Email'])) {

            /* Send referral Email to User with referral url */
            // sendMail(array(
            //     'emailTo' => $Input['Email'],
            //     'emailSubject' => "Refer & Earn - " . SITE_NAME,
            //     'emailMessage' => emailTemplate($this->load->view('emailer/refer_earn', array("Name" => $UserData['FirstName'], "ReferralCode" => $UserData['ReferralCode'], 'ReferralURL' => $InviteURL), TRUE))
            // ));

            send_mail(array(
                'emailTo' => $Input['Email'],
                'template_id' => REFER_AND_EARN,
                'Subject' => 'Refer & Earn - ' . SITE_NAME,
                "Name" => $UserData['FirstName'],
                "ReferralCode" => $UserData['ReferralCode'],
                'ReferralURL' => $InviteURL
            ));
        } else if ($Input['ReferType'] == 'Phone' && !empty($Input['PhoneNumber'])) {

            /* Send referral SMS to User with referral url */
            $this->Utility_model->sendSMS(array(
                'PhoneNumber' => $Input['PhoneNumber'],
                'Text' => "Your Friend " . $UserData['FirstName'] . " just got registered with us and has referred you. Use his/her referral code: " . $UserData['ReferralCode'] . " Use the link provided to get " . DEFAULT_CURRENCY . REFERRAL_SIGNUP_BONUS . " signup bonus. " . $InviteURL
            ));
        }
    }

    function InviteContest($Input = array(), $SessionUserID) {

        $UserData = $this->Users_model->getUsers('FirstName', array('UserID' => $SessionUserID));

        $this->db->select('C.MatchID,TL.TeamName AS TeamNameLocal,TV.TeamName AS TeamNameVisitor,TL.TeamNameShort AS TeamNameShortLocal,TV.TeamNameShort AS TeamNameShortVisitor');
        $this->db->from('sports_contest C, sports_matches M, sports_teams TL, sports_teams TV');
        $this->db->where("C.UserInvitationCode", $Input['InviteCode']);
        $this->db->where("M.MatchID", "C.MatchID", false);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", false);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", false);
        $Query = $this->db->get();
        $MatchName = $Query->result_array();

        if ($Input['ReferType'] == 'Email' && !empty($Input['Email'])) {
            /* Send referral Email to User with referral url */
            send_mail(array(
                'emailTo' => $Input['Email'],
                'template_id' => CONTEST_INVITATION,
                'Subject' => 'Contest Invitation - ' . SITE_NAME,
                "Name" => $UserData['FirstName'],
                "InviteCode" => $Input['InviteCode'],
                "TeamNameLocal" => $MatchName[0]['TeamNameShortLocal'],
                "TeamNameVisitor" => $MatchName[0]['TeamNameShortVisitor']
            ));
        } else if ($Input['ReferType'] == 'Phone' && !empty($Input['PhoneNumber'])) {
            /* Send referral SMS to User with referral url */
            $this->Utility_model->sendSMS(array(
                'PhoneNumber' => $Input['PhoneNumber'],
                'Text' => "Put your cricket knowledge to test and play with me on ".SITE_NAME.". Click ".SITE_HOST."download-app to download the ".SITE_NAME." app or login on portal and Use contest code: " . $Input['InviteCode'] . " to join my contest for " . $MatchName[0]['TeamNameShortLocal'] . " V/S " . $MatchName[0]['TeamNameShortVisitor'] . " Match."
            ));
        }
    }

    /*
      Description: 	Use to update withdrawl status.
     */

    function updateWithdrawal($WithdrawalID, $Input = array()) {
        if ($Input['StatusID'] == 3) {
            $Comments = $Input['Comments'];
        } else {
            $Comments = '';
        }
        $UpdateArray = array_filter(array(
            "StatusID" => @$Input['StatusID'],
            "ModifiedDate" => date("Y-m-d H:i:s"),
            "Comments" => $Comments
        ));

        if (!empty($UpdateArray)) {
            /* Update entity Data. */
            $this->db->select('U.UserID,U.FirstName,U.Email,U.WinningAmount,U.WithdrawalHoldAmount');
            $this->db->select('W.Amount,W.WalletID');
            $this->db->where('W.WithdrawalID', $WithdrawalID);
            $this->db->from('tbl_users_withdrawal W');
            $this->db->join('tbl_users U', 'W.UserID = U.UserID');

            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $UserData = $Query->row();
            }
            if (!empty($UserData->WalletID)) {
                $this->db->where('WalletID', $UserData->WalletID);
                $this->db->limit(1);
                $this->db->update('tbl_users_wallet', array('StatusID' => $Input['StatusID']));
            }
            if (@$Input['StatusID'] == 2) {

                /* Updating Hold Amount */
                $VerifiedData = array(
                    'WithdrawalHoldAmount' => ($UserData->WithdrawalHoldAmount - $UserData->Amount)
                );
                $this->db->where('UserID', $UserData->UserID);
                $this->db->limit(1);
                $this->db->update('tbl_users', $VerifiedData);

                $this->Notification_model->addNotification('Withdrawal', 'Withdrawal Request Approved', $UserData->UserID, $UserData->UserID, '', 'Your withdrawal request for ' . DEFAULT_CURRENCY . $UserData->Amount . ' has been approved by admin and will be transferred to your given account details within 3-4 working days.');
                /* Send welcome Email to User with login details */
                send_mail(array(
                    'emailTo' => $UserData->Email,
                    'template_id' => WITHDRAWALS,
                    'Subject' => 'Withdrawal Request Confirmed - ' . SITE_NAME,
                    "Name" => $UserData->FirstName,
                    "Amount" => $UserData->Amount
                ));
            } else if (@$Input['StatusID'] == 5) {
                $this->Notification_model->addNotification('Withdrawal', 'Withdrawal Request Processed', $UserData->UserID, $UserData->UserID, '', 'Your withdrawal request for ' . DEFAULT_CURRENCY . $UserData->Amount . ' has been processed successfully.');
            } else if (@$Input['StatusID'] == 3) {
                //add withdrawable amount again to account
                /* $RefundData = array(
                  'WinningAmount'         => ($UserData->WinningAmount + $UserData->Amount),
                  'WithdrawalHoldAmount'  => ($UserData->WithdrawalHoldAmount - $UserData->Amount)
                  );
                  $this->db->where('UserID', $UserData->UserID);
                  $this->db->limit(1);
                  $this->db->update('tbl_users', $RefundData); */

                $this->Notification_model->addNotification('Withdrawal', 'Withdrawal Request Declined', $UserData->UserID, $UserData->UserID, '', 'Your withdrawal request for ' . DEFAULT_CURRENCY . $UserData->Amount . ' has been declined by admin for ' . $Comments);
                $WalletData = array(
                    "Amount" => $UserData->Amount,
                    "WinningAmount" => $UserData->Amount,
                    "TransactionType" => 'Cr',
                    "Narration" => 'Withdrawal Reject',
                    "EntryDate" => date("Y-m-d H:i:s"),
                    "WithdrawalStatus" => 3
                );
                $this->addToWallet($WalletData, $UserData->UserID, 5);
            }
            $this->db->where('WithdrawalID', $WithdrawalID);
            $this->db->limit(1);
            $this->db->update('tbl_users_withdrawal', $UpdateArray);
        }

        /* add event attributes */
        //$this->addEntityAttributes($EntityID,@$Input['Attributes']);
        return TRUE;
    }

    /*
      Description: 	Use to get dummy user names
     */

    function getDummyNames($Limit = 10, $offset = 0, $Rand = "") {
        if (!empty($Rand)) {
            $Query = $this->db->query("SELECT Names FROM set_virtual_names ORDER BY RAND() DESC LIMIT $Limit");
        } else {
            $Query = $this->db->query("SELECT Names FROM set_virtual_names ORDER BY ID DESC LIMIT $Limit OFFSET $offset");
        }

        if ($Query->num_rows() > 0) {
            return $Query->result_array();
        }
        return FALSE;
    }

    /*
      Description: 	ADD user to system.
      Procedures:
      1. Add user to user table and get UserID.
      2. Save login info to users_login table.
      3. Save User details to users_profile table.
      4. Genrate a Token for Email verification and save to tokens table.
      5. Send welcome Email to User with Token.
     */

    function addVirtualUser($Input = array(), $UserTypeID, $SourceID, $StatusID = 1) {

        $Query = $this->db->query("SELECT UserID FROM tbl_users WHERE Email = '" . strtolower($Input['Email']) . "' LIMIT 1");
        if ($Query->num_rows() == 0) {
            $this->db->trans_start();
            $EntityGUID = get_guid();

            /* Add user to entity table and get EntityID. */
            $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 1, "StatusID" => $StatusID));
            /* Add user to user table . */
            if (!empty($Input['PhoneNumber']) && PHONE_NO_VERIFICATION) {
                $Input['PhoneNumber'] = $Input['PhoneNumber'];
            }
            $InsertData = array_filter(array(
                "UserID" => $EntityID,
                "UserGUID" => $EntityGUID,
                "UserTypeID" => $UserTypeID,
                "StoreID" => @$Input['StoreID'],
                "FirstName" => @ucfirst(strtolower($Input['FirstName'])),
                "MiddleName" => @ucfirst(strtolower($Input['MiddleName'])),
                "LastName" => @ucfirst(strtolower($Input['LastName'])),
                "About" => @$Input['About'],
                "ProfilePic" => @$Input['ProfilePic'],
                "ProfileCoverPic" => @$Input['ProfileCoverPic'],
                "Email" => @strtolower($Input['Email']),
                "EmailForChange" => '',
                "Username" => @strtolower($Input['Username']),
                "Gender" => @$Input['Gender'],
                "BirthDate" => @$Input['BirthDate'],
                "Address" => @$Input['Address'],
                "Address1" => @$Input['Address1'],
                "Postal" => @$Input['Postal'],
                "CountryCode" => @$Input['CountryCode'],
                "TimeZoneID" => @$Input['TimeZoneID'],
                "Latitude" => @$Input['Latitude'],
                "PanStatus" => @$Input['PanStatus'],
                "BankStatus" => @$Input['BankStatus'],
                "Longitude" => @$Input['Longitude'],
                "PhoneNumber" => @$Input['PhoneNumber'],
                "PhoneNumberForChange" => @$Input['PhoneNumberForChange'],
                "Website" => @strtolower($Input['Website']),
                "FacebookURL" => @strtolower($Input['FacebookURL']),
                "TwitterURL" => @strtolower($Input['TwitterURL']),
                "ReferredByUserID" => @$Input['Referral']->UserID,
                "WalletAmount" => @$Input['WalletAmount'],
                "CashBonus" => @$Input['CashBonus'],
            ));
            $this->db->insert('tbl_users', $InsertData);

            /* Manage Singup Bonus */
            /* $BonusData = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "SignupBonus" LIMIT 1');
              if ($BonusData->row()->StatusID == 2) {
              $WalletData = array(
              "Amount" => $BonusData->row()->ConfigTypeValue,
              "CashBonus" => $BonusData->row()->ConfigTypeValue,
              "TransactionType" => 'Cr',
              "Narration" => 'Signup Bonus',
              "EntryDate" => date("Y-m-d H:i:s")
              );
              $this->addToWallet($WalletData, $EntityID, 5);
              $this->Notification_model->addNotification('bonus', 'Signup Bonus', $EntityID, $EntityID, '', '' . DEFAULT_CURRENCY . $BonusData->row()->ConfigTypeValue . 'has been credited in your Wallet');
              } */

            /* Save login info to users_login table. */
            $InsertData = array_filter(array(
                "UserID" => $EntityID,
                "Password" => md5(($SourceID == '1' ? $Input['Password'] : $Input['SourceGUID'])),
                "SourceID" => $SourceID,
                "EntryDate" => date("Y-m-d H:i:s")));
            $this->db->insert('tbl_users_login', $InsertData);

            /* save user settings */
            $this->db->insert('tbl_users_settings', array("UserID" => $EntityID));

            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            }
            return $EntityID;
        } else {
            return false;
        }
    }

    /*
      Description:  Use get referrals users
     */

    function getUserReferrals($UserID) {
        $Return = array();
        $this->db->select('UserID');
        $this->db->select('ReferredByUserID');
        $this->db->from('tbl_users');
        $this->db->where('ReferredByUserID', $UserID);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['TotalRecords'] = $Query->num_rows();
            $Return['Records'] = $Query->result_array();
        }
        return $Return;
    }

    /*
      Description:  Use get referrals users next level
     */

    function getUserReferralBy($UserID) {
        $Return = array();
        $this->db->select('UserID');
        $this->db->select('ReferredByUserID');
        $this->db->from('tbl_users');
        $this->db->where('UserID', $UserID);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Records'] = $Query->row_array();
        }
        return $Return;
    }

    /*
      Description:  Use get referrals users total winning.
     */

    function getUserReferralByTotalWinning($UserIDs, $UserID = "", $N = 1) {
        $Return = array();
        $this->db->select('SUM(Amount) as WinningAmount');
        $this->db->from('tbl_users_wallet');
        $this->db->where('ReferralGetAmountUserID', $UserIDs);
        $this->db->where('AmountType', "Referral");
        if (!empty($UserID)) {
            $this->db->where('UserID', $UserID);
        }
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Records'] = $Query->row_array();
        }
        return $Return;
    }

}
