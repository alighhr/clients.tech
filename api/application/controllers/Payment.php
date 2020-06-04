<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Payment extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Utility_model');
        $this->load->model('Post_model');
        $this->load->model('Sports_model');
        $this->load->model('Contest_model');
        $this->load->model('Users_model');
        //$this->load->model('AuctionDrafts_model');
    }

    /*
      Description:  Use to get referel amount details.
      URL:      /api/utilities/getReferralDetails
     */

    public function razorpayWebResponse() {

        $Input = file_get_contents("php://input");
        $PayResponse = json_decode($Input, 1);


        $InsertData = array_filter(array(
            "Content" => json_encode($Input),
            "CreateDate" => date('Y-m-d H:i:s')
        ));
        $this->db->insert('tbl_test_razorPay', $InsertData);

        $payResponse = $PayResponse['payload']['payment']['entity'];
        if ($payResponse['status'] === "authorized") {

            $this->db->trans_start();

            $payment_id = $payResponse['id'];
            $Amount = $payResponse['amount'] / 100;
            /* update profile table */
            $UpdataData = array_filter(
                    array(
                        'PaymentGatewayResponse' => @$Input,
                        'ModifiedDate' => date("Y-m-d H:i:s"),
                        'StatusID' => 5,
                    //'ClosingWalletAmount' => 'ClosingWalletAmount+' . $Amount
            ));
            $this->db->set('ClosingWalletAmount', 'ClosingWalletAmount+' . $Amount, FALSE);
            $this->db->where('WalletID', $payResponse['notes']['OrderID']);
            $this->db->where('UserID', $payResponse['notes']['UserID']);
            $this->db->where('StatusID', 1);
            $this->db->limit(1);
            $this->db->update('tbl_users_wallet', $UpdataData);
            if ($this->db->affected_rows() <= 0) return FALSE;


            $this->db->set('WalletAmount', 'WalletAmount+' . $Amount, FALSE);
            $this->db->where('UserID', $payResponse['notes']['UserID']);
            $this->db->limit(1);
            $this->db->update('tbl_users');

            $UserID = $payResponse['notes']['UserID'];
            $this->Notification_model->addNotification('AddCash', 'Cash Added', $UserID, $UserID, '', 'Deposit of ' . DEFAULT_CURRENCY . @$Amount . ' is Successful.');

            $TotalDeposits = $this->db->query('SELECT COUNT(*) TotalDeposits FROM `tbl_users_wallet` WHERE `UserID` = ' . $UserID . ' AND Narration = "Deposit Money" AND StatusID = 5')->row()->TotalDeposits;

            if ($TotalDeposits == 1) { // On First Successful Transaction

                /* Get Deposit Bonus Data */
                $DepositBonusData = $this->db->query('SELECT ConfigTypeValue,StatusID FROM set_site_config WHERE ConfigTypeGUID = "FirstDepositBonus" LIMIT 1');
                if ($DepositBonusData->row()->StatusID == 2) {

                    $MinimumFirstTimeDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MinimumFirstTimeDepositLimit" LIMIT 1');
                    $MaximumFirstTimeDepositLimit = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "MaximumFirstTimeDepositLimit" LIMIT 1');

                    if ($MinimumFirstTimeDepositLimit->row()->ConfigTypeValue <= @$Amount && $MaximumFirstTimeDepositLimit->row()->ConfigTypeValue >= @$Amount) {
                        /* Update Wallet */
                        $FirstTimeAmount = (@$Amount * $DepositBonusData->row()->ConfigTypeValue) / 100;
                        $WalletData = array(
                            "Amount" => $FirstTimeAmount,
                            "CashBonus" => $FirstTimeAmount,
                            "TransactionType" => 'Cr',
                            "Narration" => 'First Deposit Bonus',
                            "EntryDate" => date("Y-m-d H:i:s")
                        );
                        $this->Users_model->addToWallet($WalletData, $UserID, 5);
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
                        $this->Users_model->addToWallet($WalletData, $UserID, 5);
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
                        $this->Users_model->addToWallet($WalletData, $UserData['ReferredByUserID'], 5);
                        $this->Notification_model->addNotification('ReferralBonus', 'Referral Bonus Added', $UserData['ReferredByUserID'], $UserData['ReferredByUserID'], '', 'You have received ' . DEFAULT_CURRENCY . @$ReferralByBonus->row()->ConfigTypeValue . ' Cash Bonus for Successful Referral.');
                    }
                }
            }
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            }

            $CURL = curl_init();
            curl_setopt_array($CURL, array(
                CURLOPT_URL => "https://" . RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET . "@api.razorpay.com/v1/payments/" . $payment_id . "/capture",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"amount\"\r\n\r\n" . $payResponse['amount'] . "\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
                ),
            ));

            $Response = curl_exec($CURL);
            $Err = curl_error($CURL);
            curl_close($CURL);

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
                                "WalletAmount" => $FirstLevelDeposit,
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
                                        "WalletAmount" => $SecondLevelDeposit,
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
                                                "WalletAmount" => $ThirdLevelDeposit,
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
        } else {
            /* if ($payResponse['status'] === "failed") {
              $UpdataData = array_filter(
              array(
              'PaymentGatewayResponse' => @$Input,
              'ModifiedDate' => date("Y-m-d H:i:s"),
              'StatusID' => 3
              ));
              $this->db->where('WalletID', $payResponse['notes']['OrderID']);
              $this->db->where('UserID', $payResponse['notes']['UserID']);
              $this->db->where('StatusID', 1);
              $this->db->limit(1);
              $this->db->update('tbl_users_wallet', $UpdataData);
              if ($this->db->affected_rows() <= 0)
              return FALSE;
              } */
        }
    }

}
