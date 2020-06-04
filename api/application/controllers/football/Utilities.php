<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Utilities extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Post_model');
        $this->load->model('Users_model');
        $this->load->model('football/Football_Contest_model', 'Football_Contest_model');
        $this->load->model('football/Football_Sports_model', 'Football_Sports_model');
        $this->load->model('football/Football_PreContest_model', 'Football_PreContest_model');
        $this->load->model('football/Football_Utility_model', 'Football_Utility_model');
        //$this->load->model('AuctionDrafts_model');
    }

    function setting_get() {
        $ConfigData = $this->Football_Utility_model->getConfigs(@$this->Post);
        if (!empty($ConfigData)) {
            $this->Return['Data'] = $ConfigData['Data'];
        }
    }

    /*
      Description:  Use to create pre draft contest
      URL:      /api/utilities/createPreContest
     */

    public function createPreContest_get() {
        $this->Football_PreContest_model->createPreContest();
    }

    /*
      Description:  Use to Delete Reminder Notification
      URL:      /api/utilities/deleteNotifications
     */

    public function deleteNotifications_get() {
        $this->Football_Utility_model->deleteNotifications();
    }

    /*
      Description: 	Cron jobs to get series data.
      URL: 			/api/utilities/getSeriesLive
     */

    public function getSeriesLive_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getSeriesLive');
        $SeriesData = $this->Football_Utility_model->getSeriesLiveCricketApi($CronID);
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData;
        }
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to get rounds data.
      URL:          /api/utilities/getRoundsLive
     */

    public function getRoundsLive_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getRoundsLive');
        if (FOOTBALL_SPORT_API_NAME == 'CRICKETAPI') {
            $RoundsData = $this->Football_Utility_model->getRoundsLiveCricketApi($CronID);
        }
        if (!empty($RoundsData)) {
            $this->Return['Data'] = $RoundsData;
        }
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get matches data.
      URL: 			/api/utilities/getMatchesLive
     */

    public function getMatchesLive_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getMatchesLive');
        if (FOOTBALL_SPORT_API_NAME == 'CRICKETAPI') {
            $MatchesData = $this->Football_Utility_model->getMatchesLiveCricketApi($CronID);
        }
        if (!empty($MatchesData)) {
            $this->Return['Data'] = $MatchesData;
        }
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get players data.
      URL: 			/api/utilities/getPlayersLive
     */

    public function getPlayersLive_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getPlayersLive');
        if (FOOTBALL_SPORT_API_NAME == 'CRICKETAPI') {
            $PlayersData = $this->Football_Utility_model->getPlayersLiveCricketApi($CronID);
        }
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData;
        }
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get players salary data.
      URL: 			/api/utilities/getPlayersSalaryLive
     */

    public function getPlayersSalaryLive_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getPlayersSalaryLive');
        if (FOOTBALL_SPORT_API_NAME == 'CRICKETAPI') {
            $PlayersData = $this->Football_Utility_model->getPlayersSalaryLiveCricketApi($CronID);
        }
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData;
        }
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get player stats data.
      URL: 			/api/utilities/getPlayerStatsLive
     */

    public function getPlayerStatsLive_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getPlayerStatsLive');
        if (FOOTBALL_SPORT_API_NAME == 'CRICKETAPI') {
            $PlayersStatsData = $this->Football_Utility_model->getPlayerStatsLiveCricketApi($CronID);
        }
        if (!empty($PlayersStatsData)) {
            $this->Return['Data'] = $PlayersStatsData;
        }
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get match live score
      URL: 			/api/utilities/getMatchScoreLive
     */

    public function getMatchScoreLive_get() {

        $CronID = $this->Football_Utility_model->insertCronLogs('getMatchScoreLive');
        if (FOOTBALL_SPORT_API_NAME == 'CRICKETAPI') {
            $MatchScoreLiveData = $this->Football_Utility_model->getMatchScoreLiveCricketApi($CronID);
        }
        if (!empty($MatchScoreLiveData)) {
            $this->Return['Data'] = $MatchScoreLiveData;
        }
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get player points.
      URL: 			/api/utilities/getPlayerPoints
     */

    public function getPlayerPoints_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getPlayerPoints');
        $this->Football_Utility_model->getPlayerPoints($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get joined player points.
      URL: 			/api/utilities/getJoinedContestPlayerPoints
     */

    public function getJoinedContestPlayerPoints_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
        $this->Football_Utility_model->getJoinedContestTeamPoints($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto set winner.
      URL: 			/api/utilities/setContestWinners
     */

    public function setContestWinners_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('setContestWinners');
        $this->Football_Utility_model->setContestWinners($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto set winner.
      URL: 			/api/utilities/setContestWinners
     */

    public function amountDistributeContestWinner_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('amountDistributeContestWinner');
        $this->Football_Sports_model->amountDistributeContestWinner($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get update match playing11
      URL: 			/api/utilities/getMatchScoreLive
     */

    public function getLivePlaying11MatchPlayer_get() {
        $this->Football_Utility_model->getLivePlaying11MatchPlayer();
    }

    public function send_post() {
        sendPushMessage($this->Post['UserID'], $this->Post['Title'], $this->Post['Message'], $this->Post['Data']);
    }

    /*
      Description: 	Cron jobs to auto cancel contest.
      URL: 			/api/utilities/autoCancelContest
     */

    public function autoCancelContest_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('autoCancelContest');
        $this->Football_Utility_model->autoCancelContest($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to auto cancel contest refund amount.
      URL:          /api/utilities/refundAmountCancelContest
     */

    public function refundAmountCancelContest_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('refundAmountCancelContest');
        $this->Football_Sports_model->refundAmountCancelContest($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to auto cancel contest refund amount debug function.
      URL:          /api/utilities/refundAmountCancelContest
     */

    public function refundAmountCancelContestDebug_get() {
        $this->Football_Sports_model->refundAmountCancelContestDebug();
    }

    public function amountDistributeContestWinnerDebug_get() {
        $this->Football_Sports_model->amountDistributeContestWinnerDebug();
    }

    public function setAuctionDraftWinner_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('setAuctionDraftWinner');
        $this->Football_Sports_model->setAuctionDraftWinner($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get auction joined player points.
      URL: 			/api/utilities/getAuctionJoinedUserTeamsPlayerPoints
     */

    public function getAuctionJoinedUserTeamsPlayerPoints_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getAuctionJoinedUserTeamsPlayerPoints');
        $this->Football_Sports_model->getAuctionJoinedUserTeamsPlayerPoints($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to auto send mail to winner.
      URL:          /api/utilities/setContestWinners
     */

    public function sendMailContestWinners_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('ContestWinnersMailSend');
        $this->Football_Sports_model->ContestWinnersMailSend($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs for Notification for upcoming Matches.
      URL:          /api/utilities/notifyUpcomingMatches
     */

    public function notifyUpcomingMatches_get() {
        $this->Football_Sports_model->notifyUpcomingMatches();
    }

    /*
      Description:  Cron jobs to transfer joined contest data (MongoDB To MySQL).
      URL:          /api/utilities/tranferJoinedContestData
     */

    public function tranferJoinedContestData_get() {
        $CronID = $this->Utility_model->insertCronLogs('tranferJoinedContestData');
        $this->Football_Utility_model->tranferJoinedContestData($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto add minute in every hours.
      URL: 			/api/utilities/liveAuctionAddMinuteInEveryHours
     */

    public function auctionLiveAddMinuteInEveryHours_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('liveAuctionAddMinuteInEveryHours');
        $this->AuctionDrafts_model->auctionLiveAddMinuteInEveryHours($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get auction joined player points.
      URL: 			/api/utilities/getAuctionJoinedUserTeamsPlayerPoints
     */

    public function getAuctionDraftJoinedUserTeamsPlayerPoints_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('getAuctionJoinedUserTeamsPlayerPoints');
        $this->Football_Sports_model->getAuctionDraftJoinedUserTeamsPlayerPoints($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto draft team submit if user not submit in 15 minutes.
      URL: 			/api/utilities/draftTeamAutoSubmit
     */

    public function draftTeamAutoSubmit_get() {
        $CronID = $this->Football_Utility_model->insertCronLogs('draftTeamAutoSubmit');
        $this->SnakeDrafts_model->draftTeamAutoSubmit($CronID);
        $this->Football_Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: To get statics
     */

    public function dashboardStatics_post() {
        $SiteStatics = new stdClass();
        $SiteStatics = $this->db->query('SELECT
                                            TotalUsers,
                                            TotalContest,
                                            TodayContests,
                                            TotalDeposits,
                                            TotalWithdraw,
                                            TodayDeposit,
                                            NewUsers,
                                            TotalDeposits - TotalWithdraw AS TotalEarning,
                                            PendingWithdraw
                                        FROM
                                            (SELECT
                                                (
                                                    SELECT
                                                        COUNT(UserID) AS `TotalUsers`
                                                    FROM
                                                        `tbl_users`
                                                    WHERE
                                                        `UserTypeID` = 2
                                                ) AS TotalUsers,
                                                (
                                                    SELECT
                                                        COUNT(UserID) AS `NewUsers`
                                                    FROM
                                                        `tbl_users` U, `tbl_entity` E
                                                    WHERE
                                                        U.`UserTypeID` = 2 AND U.UserID = E.EntityID AND DATE(E.EntryDate) = "' . date('Y-m-d') . '"
                                                ) AS NewUsers,
                                                (
                                                    SELECT
                                                        COUNT(ContestID) AS `TotalContest`
                                                    FROM
                                                        `football_sports_contest`
                                                ) AS TotalContest,
                                                (
                                                    SELECT COUNT(DISTINCT(C.ContestID)) FROM `football_sports_contest` C, `football_sports_matches` M WHERE C.MatchID = M.MatchID AND DATE(M.MatchStartDateTime) = "' . date('Y-m-d') . '"
                                                ) AS TodayContests,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`WalletAmount`),0) AS TotalDeposits
                                                    FROM
                                                        `tbl_users_wallet`
                                                    WHERE
                                                        `Narration`= "Deposit Money" AND
                                                        `StatusID` = 5
                                                ) AS TotalDeposits,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`WalletAmount`),0) AS TodayDeposit
                                                    FROM
                                                        `tbl_users_wallet`
                                                    WHERE
                                                        `Narration`= "Deposit Money" AND
                                                        `StatusID` = 5 AND DATE(EntryDate) = "' . date('Y-m-d') . '"
                                                ) AS TodayDeposit,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`Amount`),0) AS TotalWithdraw
                                                    FROM
                                                        `tbl_users_withdrawal`
                                                    WHERE
                                                        `StatusID` = 5
                                                ) AS TotalWithdraw,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`Amount`),0) AS TotalWithdraw
                                                    FROM
                                                        `tbl_users_withdrawal`
                                                    WHERE
                                                        `StatusID` = 1
                                                ) AS PendingWithdraw
                                            ) Total'
                )->row();
        $this->Return['Data'] = $SiteStatics;
    }

    /*
      Name:           getTotalDeposits
      Description:    To get Total Deposits data
      URL:            /Utilites/getTotalDeposits/
     */

    public function getTotalDeposits_post() {
        /* Get Total Deposit Data */
        $WalletDetails = $this->Football_Utility_model->getTotalDeposit(@$this->Post['Params'], $this->Post, TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($WalletDetails)) {
            $this->Return['Data'] = $WalletDetails['Data'];
        }
    }

    /*
      Description:  Use to get app version details
      URL:      /api/utilities/getAppVersionDetails
     */

    public function getAppVersionDetails_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('UserAppVersion', 'UserAppVersion', 'trim|required');
        $this->form_validation->set_rules('DeviceType', 'Device type', 'trim|required|callback_validateDeviceType');
        $this->form_validation->validation($this); /* Run validation */
        /* Validation - ends */

        $VersionData = $this->Football_Utility_model->getAppVersionDetails();
        if (!empty($VersionData)) {
            $this->Return['Data'] = $VersionData;
        }
    }

    /*
      Description:  Use to get referel amount details.
      URL:      /api/utilities/getReferralDetails
     */

    public function getReferralDetails_post() {
        $ReferByQuery = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "ReferByDepositBonus" AND StatusID = 2 LIMIT 1');
        $ReferToQuery = $this->db->query('SELECT ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID = "ReferToDepositBonus" AND StatusID = 2 LIMIT 1');
        $this->Return['Data']['ReferByBonus'] = ($ReferByQuery->num_rows() > 0) ? $ReferByQuery->row()->ConfigTypeValue : 0;
        $this->Return['Data']['ReferToBonus'] = ($ReferToQuery->num_rows() > 0) ? $ReferToQuery->row()->ConfigTypeValue : 0;
    }

    /*
      Description:  Use to get referel amount details.
      URL:      /api/utilities/getReferralDetails
     */

    public function razorpayWebResponse_post() {

        $Input = file_get_contents("php://input");
        $PayResponse = json_decode($Input, 1);


        $InsertData = array_filter(array(
            "PageGUID" => "RazorPay",
            "Title" => "Test",
            "Content" => json_encode($Input)
        ));
        $this->db->insert('set_pages', $InsertData);



        $payResponse = $PayResponse['payload']['payment']['entity'];
        if ($payResponse['status'] === "authorized") {

            $this->db->trans_start();

            $payment_id = $payResponse['id'];
            /* update profile table */
            $UpdataData = array_filter(
                    array(
                        'PaymentGatewayResponse' => @$Input,
                        'ModifiedDate' => date("Y-m-d H:i:s"),
                        'StatusID' => 5
            ));
            $this->db->where('WalletID', $payResponse['notes']['OrderID']);
            $this->db->where('UserID', $payResponse['notes']['UserID']);
            $this->db->where('StatusID', 1);
            $this->db->limit(1);
            $this->db->update('tbl_users_wallet', $UpdataData);
            if ($this->db->affected_rows() <= 0) return FALSE;

            $Amount = $payResponse['amount'] / 100;
            $this->db->set('WalletAmount', 'WalletAmount+' . $Amount, FALSE);
            $this->db->where('UserID', $payResponse['notes']['UserID']);
            $this->db->limit(1);
            $this->db->update('tbl_users');

            $UserID = $payResponse['notes']['UserID'];
            $this->Notification_model->addNotification('AddCash', 'Cash Added', $UserID, $UserID, '', 'Deposit of ' . DEFAULT_CURRENCY . @$Amount . ' is Successful.');

            $CouponDetails = $this->Users_model->getWallet('CouponDetails', array("WalletID" => $payResponse['notes']['OrderID']));

            /* Check Coupon Details */
            if (!empty($CouponDetails['CouponDetails'])) {
                $WalletData = array(
                    "Amount" => $CouponDetails['CouponDetails']['DiscountedAmount'],
                    "CashBonus" => $CouponDetails['CouponDetails']['DiscountedAmount'],
                    "TransactionType" => 'Cr',
                    "Narration" => 'Coupon Discount',
                    "EntryDate" => date("Y-m-d H:i:s")
                );
                $this->Users_model->addToWallet($WalletData, $UserID, 5);
            }

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
                $UserData = $this->Users_model->getUsers('ReferredByUserID', array("UserID" => $UserID));
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
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
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

    /*
      Name:     createVirtualUsers
      Description:  create virtual user users
      URL:      /utilities/createVirtualUsers/
     */

    public function createVirtualUsers_get() {

        $tlds = array("com");
        $char = "0123456789abcdefghijklmnopqrstuvwxyz";
        $Limit = 3000;
        $Names = $this->Users_model->getDummyNames($Limit);
        if (!empty($Names)) {
            for ($j = 0; $j < $Limit; $j++) {
                $UserName = ucwords($Names[$j]['Names']);
                $UserUnique = str_replace(" ", "", $UserName);
                $ulen = mt_rand(5, 10);
                $dlen = mt_rand(7, 17);
                $email = "";
                for ($i = 1; $i <= $ulen; $i++) {
                    $email .= substr($char, mt_rand(0, strlen($char)), 1);
                }
                $email .= "@";
                $email .= "gmail";
                $email .= ".";
                $email .= $tlds[mt_rand(0, (sizeof($tlds) - 1))];
                $username = strtolower($UserUnique) . substr(md5(microtime()), rand(0, 26), 4);
                $Input = array();
                $Input['Email'] = $username . "@gmail.com";
                $Input['Username'] = $username;
                $Input['FirstName'] = $UserName;
                $Input['Password'] = 'A@123456';
                $Input['Source'] = "Direct";
                $Input['PanStatus'] = 2;
                $Input['BankStatus'] = 2;
                $Input['DocumentStatus'] = 2;
                $UserID = $this->Users_model->addVirtualUser($Input, 3, 1, 2);
                if ($UserID) {
                    $this->Football_Utility_model->generateReferralCode($UserID);
                    $WalletData = array(
                        "Amount" => 1000000,
                        "CashBonus" => 0,
                        "TransactionType" => 'Cr',
                        "Narration" => 'Deposit Money',
                        "EntryDate" => date("Y-m-d H:i:s")
                    );
                    $this->Users_model->addToWallet($WalletData, $UserID, 5);
                }
            }
        }
    }

    /*
      Name:     createVirtualUserTeams
      Description:  create virtual user team
      URL:      /utilities/createVirtualUserTeams/
     */

    public function createVirtualUserTeams_get() {

        ini_set('max_execution_time', 120);
        /* get upcoming matches */
        $this->db->select('M.MatchID,M.MatchStartDateTime,M.TeamIDLocal,M.TeamIDVisitor,SeriesID,RoundID');
        $this->db->from('tbl_entity E, football_sports_matches M');
        $this->db->where("M.MatchID", "E.EntityID", FALSE);
        $this->db->where("E.StatusID", 1);
        $this->db->order_by('M.MatchStartDateTime', "ASC");
        $this->db->limit(30);
        $Query = $this->db->get();

        if ($Query->num_rows() > 0) {
            $AllMatches = $Query->result_array();
            foreach ($AllMatches as $Match) {

                /* get upcoming contest */
                $this->db->select('C.MatchID,C.ContestID,C.ContestSize,C.IsVirtualTeamCreated');
                $this->db->from('tbl_entity E, football_sports_contest C');
                $this->db->where("C.ContestID", "E.EntityID", FALSE);
                $this->db->where("C.IsVirtualUserJoined", "Yes");
                $this->db->where("C.MatchID", $Match['MatchID']);
                $this->db->where("E.StatusID", 1);
                $this->db->order_by('C.ContestSize', "DESC");
                $this->db->limit(1);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $Contest = $Query->result_array();

                    $where = '';
                    if ($Match['SeriesID']) {
                        $where = $where . " STP. SeriesID = " . $Match['SeriesID'];
                        // $data = " = 66975 ";
                    }
                    if ($Match['RoundID']) {
                        $where = $where . " AND STP. RoundID = " . $Match['RoundID'];
                    }
                    /* get match players */
                    $this->db->select('P.PlayerGUID,TP.PlayerID,TP.TeamID,TP.PlayerRole,FORMAT(TP.PlayerSalary, 1) as PlayerSalary, (SELECT SUM(`TotalPoints`) FROM `football_sports_team_players` as STP WHERE ' . $where . ' AND TP.PlayerID = STP.PlayerID) as TotalPointsMatch');
                    $this->db->from('football_sports_team_players TP,football_sports_players P');
                    $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
                    $this->db->where("TP.MatchID", $Match['MatchID']);
                    $this->db->where("TP.PlayerSalary >", 0);
                    $this->db->where("TP.IsActive", "Yes");
                    $this->db->order_by('TP.PlayerSalary', 'DESC');
                    $Query = $this->db->get();
                    $AllPlayers = $Query->result_array();
                    $AllPlayers = phparraysort($AllPlayers, array('TotalPointsMatch', 'PlayerSalary'));
                    if (!empty($AllPlayers)) {

                        foreach ($Contest as $ContestRows) {

                            if ($ContestRows['IsVirtualTeamCreated'] == "No") {
                                /* check total team created */
                                $this->db->select('COUNT(UserTeamID) as TotalTeam');
                                $this->db->from('football_sports_users_teams');
                                $this->db->where("IsVirtual", "Yes");
                                $this->db->where("MatchID", $Match['MatchID']);
                                $this->db->limit(1);
                                $Query = $this->db->get();
                                $Result = $Query->row_array();

                                $TotalTeams = $ContestRows['ContestSize'] - $Result['TotalTeam'];

                                if ($TotalTeams > 0) {
                                    /* get users */
                                    $this->db->select('U.UserGUID,U.UserID,U.FirstName');
                                    $this->db->from('tbl_users U');
                                    /* $this->db->join('sports_users_teams T', 'T.UserID != U.UserID');
                                      $this->db->where("T.MatchID", $Match['MatchID']); */
                                    $this->db->where('NOT EXISTS(Select 1 from football_sports_users_teams T where T.UserID = U.UserID AND T.MatchID = ' . $Match['MatchID'] . ')');
                                    $this->db->where("U.UserTypeID", 3);
                                    $this->db->limit($TotalTeams);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $AllUsers = $Query->result_array();

                                        $unique = 0;
                                        $TeamCount = array(4, 5, 6);
                                        foreach ($AllUsers as $User) {
                                            $ABC = rand(0, 2);
                                            if ($unique % 2 == 0) {
                                                $localteamIDS = $Match['TeamIDLocal'];
                                                $visitorteamIDS = $Match['TeamIDVisitor'];
                                            } else {
                                                $visitorteamIDS = $Match['TeamIDLocal'];
                                                $localteamIDS = $Match['TeamIDVisitor'];
                                            }
                                            $this->createTeamProcessByMatch($AllPlayers, $localteamIDS, $visitorteamIDS, $Match['SeriesID'], $User['UserID'], $Match['MatchID'], $TeamCount[$ABC]);
                                            $unique++;
                                        }
                                    }
                                } else {
                                    if ($Result['TotalTeam'] > 0) {
                                        /* update contest virtual */
                                        $this->db->where('ContestID', $ContestRows['ContestID']);
                                        $this->db->limit(1);
                                        $this->db->update('football_sports_contest', array('IsVirtualTeamCreated' => 'Yes'));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /*
      Name:     createTeamProcessByMatch
      Description:  virtual usercommon create team
      URL:      /testApp/createTeamProcessByMatch/
     */

    public function createTeamProcessByMatch($matchPlayer, $localteam_id, $visitorteam_id, $series_id, $user_id, $match_id, $TeamCount) {
        $returnArray = array();
        $playerCount = 1;
        $secondPlayerCount = 1;
        $batsman = 0;
        $goalkeeper = 0;
        $bowler = 0;
        $allrounder = 0;
        $teamCount = 0;
        $teamB = array();
        $Arr1 = array();
        $Arr2 = array();
        $Arr3 = array();
        $Arr4 = array();
        $Arr5 = array();
        $Arr6 = array();
        $Arr7 = array();
        $Arr8 = array();
        $creditPoints = 0;
        $points = 0;
        $selectedViceCaptainPlayer = [];
        $selectedCaptainPlayer = [];
        shuffle($matchPlayer);
        shuffle($matchPlayer);

        foreach ($matchPlayer as $player) {

            if (count($res1) <= $TeamCount) {
                $playerId = $player['PlayerID'];
                $teamId = $player['TeamID'];
                $playerRole = ($player['PlayerRole']);
                $creditPoints += 9;

                if ($teamId == $localteam_id) {

                    if ($goalkeeper < 1) {
                        if ($playerRole == 'Goalkeeper') {
                            $temp['play_role'] = ($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr1[] = $temp;
                            $goalkeeper++;
                        }
                    }
                    if ($batsman < 3) {
                        if ($playerRole == 'Defender') {
                            $temp['play_role'] = ($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr2[] = $temp;
                            $batsman++;
                        }
                    }
                    if ($bowler < 3) {
                        if ($playerRole == 'Midfielder') {
                            $temp['play_role'] = ($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr3[] = $temp;
                            $bowler++;
                        }
                    }
                    if ($allrounder < 1) {
                        if ($playerRole == 'Striker') {
                            $temp['play_role'] = ($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr4[] = $temp;
                            $allrounder++;
                        }
                    }
                }
            }
            $playerCount++;
            $res1 = array_merge($Arr1, $Arr2, $Arr3, $Arr4);
        }

        foreach ($matchPlayer as $player) {
            if (count($res2) <= 10 - count($res1)) {
                $playerId = $player['PlayerID'];
                $teamId = $player['TeamID'];
                $playerRole = ($player['PlayerRole']);
                if ($teamId == $visitorteam_id) {
                    if ($goalkeeper < 1) {
                        if ($playerRole == 'Goalkeeper') {
                            $temp1['play_role'] = ($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr5[] = $temp1;
                            $goalkeeper++;
                        }
                    }
                    if ($batsman < 5) {
                        if ($playerRole == 'Defender') {
                            $temp1['play_role'] = ($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr6[] = $temp1;
                            $batsman++;
                        }
                    }
                    if ($bowler < 5) {
                        if ($playerRole == 'Midfielder') {
                            $temp1['play_role'] = ($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr7[] = $temp1;
                            $bowler++;
                        }
                    }
                    if ($allrounder < 3) {
                        if ($playerRole == 'Striker') {
                            $temp1['play_role'] = ($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr8[] = $temp1;
                            $allrounder++;
                        }
                    }
                }
            }
            $secondPlayerCount++;
            $res2 = array_merge($Arr5, $Arr6, $Arr7, $Arr8);
        }

        $Index = rand(0, (count($res2) - 1));
        $res2[$Index]['PlayerPosition'] = "Captain";
        $Index2 = rand(0, (count($res1) - 1));
        $res1[$Index2]['PlayerPosition'] = "ViceCaptain";
        $playing11 = array_merge($res2, $res1);

        if (count($playing11) == 11) {
            $PlayerTeams = array_count_values(array_column($playing11, 'team_id'));
            $teamStatus = 1;
            foreach ($PlayerTeams as $t) {
                if ($t >= 8) {
                    $teamStatus = 2;
                }
            }
            if ($teamStatus == 1) {
                $PlayerPoisitions = array_count_values(array_column($playing11, 'PlayerPosition'));
                if ($PlayerPoisitions['Captain'] == 1 && $PlayerPoisitions['ViceCaptain'] == 1) {
                    $PlayerRoles = array_count_values(array_column($playing11, 'play_role'));
                    if ($PlayerRoles['Goalkeeper'] == 1) {
                        if ($PlayerRoles['Defender'] > 2 && $PlayerRoles['Defender'] < 6) {
                            if ($PlayerRoles['Midfielder'] > 2 && $PlayerRoles['Midfielder'] < 6) {
                                if ($PlayerRoles['Striker'] > 0 && $PlayerRoles['Striker'] < 4) {
                                    $this->Football_Contest_model->addUserTeam(array('UserTeamPlayers' => $playing11, 'UserTeamType' => 'Normal', 'Status' => 1, 'IsVirtual' => 'Yes'), $user_id, $match_id);
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /*
      Name:     autoJoinContestVirtualUser
      Description:  join virtual user contest
      URL:      /testApp/autoJoinContestVirtualUser/
     */

    public function autoJoinContestVirtualUser_get() {

        $UtcDateTime = date('Y-m-d H:i');
        $UtcDateTime = date('Y-m-d H:i', strtotime($UtcDateTime));
        $NextDateTime = strtotime($UtcDateTime) + 3600 * 20;
        $MatchDateTime = date('Y-m-d H:i', $NextDateTime);
        $totaHours = 10;

        $Contests = $this->Football_Contest_model->getContests('UserJoinLimit,IsPaid,EntryFee,CashBonusContribution,WinningAmount,MatchID,IsDummyJoined,ContestID,ContestSize,TotalJoined,MatchStartDateTimeUTC,VirtualUserJoinedPercentage', array('StatusID' => array(1), 'IsVirtualUserJoined' => "Yes", "ContestFull" => "No"), TRUE, 1, 30);
        if (!empty($Contests['Data']['Records'])) {
            foreach ($Contests['Data']['Records'] as $Rows) {
                $Seconds = strtotime($Rows['MatchStartDateTimeUTC']) - strtotime($UtcDateTime);
                $hours = $Hours = $Seconds / 60 / 60;

                if ($Hours > 10) {
                    continue;
                }

                $dummyJoinedContest = 0;
                $dummyJoinedContests = $this->db->query("SELECT count(JC.ContestID) as DummyJoinedContest FROM football_sports_contest_join as JC JOIN tbl_users ON tbl_users.UserID = JC.UserID WHERE JC.ContestID = " . $Rows['ContestID'] . " AND tbl_users.UserTypeID = 3")->row()->DummyJoinedContest;


                if ($dummyJoinedContests) {
                    $dummyJoinedContest = $dummyJoinedContests;
                }

                $totalJoined = $Rows['TotalJoined'];
                $contestSize = $Rows['ContestSize'];
                $joinDummyUser = $Rows['VirtualUserJoinedPercentage'];
                $dummyUserPercentage = round(($contestSize * $joinDummyUser) / 100);
                $dummyUserPercentageReal = $dummyUserPercentage;
                if ($dummyJoinedContest >= $dummyUserPercentage) {

                    $this->Football_Contest_model->UpdateVirtualJoinContest($Rows['ContestID']);
                    continue;
                }
                $checkHours = floor($hours);
                $dummyUserPercentage = round(($dummyUserPercentage * ($totaHours - $checkHours) / $totaHours)) - $dummyJoinedContest;

                if ($hours < 0.3) {
                    $dummyUserPercentage = round(($dummyUserPercentageReal * 100 / 100)) - $dummyJoinedContest;
                }

                $isEliglibleJoin = $totalJoined + $dummyUserPercentage;

                if (!($isEliglibleJoin <= $contestSize)) {
                    $dummyUserPercentage = $contestSize - $totalJoined - 10;
                }

                if (!($dummyUserPercentage > 0)) {
                    continue;
                }
                // echo $dummyUserPercentage;
                // print_r($Rows);die;
                //echo"<pre>";print_r($Rows['ContestID']);die;

                $VitruelTeamPlayer = $this->Football_Contest_model->GetVirtualTeamPlayerMatchWise($Rows['MatchID'], $dummyUserPercentage, $Rows['ContestID']);
                if (!empty($VitruelTeamPlayer)) {
                    foreach ($VitruelTeamPlayer as $usersTeam) {
                        $userTeamPlayers = json_decode($usersTeam['Players']);
                        $myPlayers = '';
                        $c = 0;
                        $vc = 0;
                        foreach ($userTeamPlayers as $player) {
                            $myPlayers .= $player->PlayerID . ",";
                            if ($player->PlayerPosition == "Captain") {
                                $captain_player = $player->PlayerID;
                                $c++;
                            }
                            if ($player->PlayerPosition == "ViceCaptain") {
                                $vice_captain_player = $player->PlayerID;
                                $vc++;
                            }
                        }
                        if (isset($myPlayers) && isset($captain_player) && isset($vice_captain_player)) {
                            $myPlayers = rtrim($myPlayers, ",");
                            if (!empty($usersTeam['UserTeamID'])) {
                                if ($c > 1 || $vc > 1) {
                                    continue;
                                }

                                if ($this->db->query('SELECT COUNT(EntryDate) `TotalJoined` FROM `football_sports_contest_join` WHERE `ContestID` =' . $Rows['ContestID'] . ' AND UserID = ' . $usersTeam['UserID'])->row()->TotalJoined >= $Rows['UserJoinLimit']) {
                                    continue;
                                }

                                $JoinedContest = $this->db->query('SELECT ContestID FROM football_sports_contest_join WHERE UserTeamID =' . $usersTeam['UserTeamID'] . ' AND ContestID=' . $Rows['ContestID'] . ' AND UserID=' . $usersTeam['UserID'] . ' LIMIT 1');
                                if ($JoinedContest->num_rows() > 0) {
                                    continue;
                                }

                                $PostInput = array();

                                $Contests = $this->Football_Contest_model->getContests('IsPaid,EntryFee,CashBonusContribution,WinningAmount,MatchID,IsDummyJoined,ContestID,ContestSize,TotalJoined,MatchStartDateTimeUTC,VirtualUserJoinedPercentage', array('StatusID' => array(1), 'IsVirtualUserJoined' => "Yes", "ContestFull" => "No", "ContestID" => $Rows['ContestID']), FALSE);

                                if (!empty($Contests)) {
                                    if ($Contests['TotalJoined'] >= $Contests['ContestSize']) {
                                        continue;
                                    }
                                }

                                /* Get User Wallet Details */
                                $UserData = $this->Users_model->getUsers('TotalCash,WalletAmount,WinningAmount,CashBonus', array('UserID' => $usersTeam['UserID']));
                                $Rows['WalletAmount'] = $UserData['WalletAmount'];
                                $Rows['WinningAmount'] = $UserData['WinningAmount'];
                                $Rows['CashBonus'] = $UserData['CashBonus'];
                                /* Calculate Wallet Amount */

                                $ContestEntryRemainingFees = @$Rows['EntryFee'];
                                $CashBonusContribution = @$Rows['CashBonusContribution'];
                                $WalletAmountDeduction = $WinningAmountDeduction = $CashBonusDeduction = 0;
                                if (!empty($CashBonusContribution) && @$UserData['CashBonus'] > 0) {
                                    $CashBonusContributionAmount = $ContestEntryRemainingFees * ($CashBonusContribution / 100);
                                    $CashBonusDeduction = (@$UserData['CashBonus'] >= $CashBonusContributionAmount) ? $CashBonusContributionAmount : @$UserData['CashBonus'];
                                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $CashBonusDeduction;
                                }
                                if ($ContestEntryRemainingFees > 0 && @$UserData['WinningAmount'] > 0) {
                                    $WinningAmountDeduction = (@$UserData['WinningAmount'] >= $ContestEntryRemainingFees) ? $ContestEntryRemainingFees : @$UserData['WinningAmount'];
                                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WinningAmountDeduction;
                                }
                                if ($ContestEntryRemainingFees > 0 && @$UserData['WalletAmount'] > 0) {
                                    $WalletAmountDeduction = (@$UserData['WalletAmount'] >= $ContestEntryRemainingFees) ? $ContestEntryRemainingFees : @$UserData['WalletAmount'];
                                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WalletAmountDeduction;
                                }

                                $Rows['CashBonusDeduction'] = $CashBonusDeduction;
                                $Rows['WinningAmountDeduction'] = $WinningAmountDeduction;
                                $Rows['WalletAmountDeduction'] = $WalletAmountDeduction;
                                $PostInput['IsPaid'] = $this->Football_Contest_model->joinContestVirtual($Rows, $usersTeam['UserID'], $Rows['ContestID'], $Rows['MatchID'], $usersTeam['UserTeamID']);
                            }
                        }
                    }
                    $this->Football_Contest_model->ContestUpdateVirtualTeam($Rows['ContestID'], $Rows['IsDummyJoined']);
                }
            }
        }
    }

    /*
      Description: 	virtual user copy team to real user.
      URL: 			/api/utilities/copyRealUserTeam/
     */

    function copyRealUserTeam_get() {
        $UtcDateTime = date('Y-m-d H:i');
        $UtcDateTime = date('Y-m-d H:i', strtotime($UtcDateTime));
        //$NextDateTime = strtotime($UtcDateTime) + 1200;
        //$NextDateTime = strtotime($UtcDateTime) + 3600*17+2000;
        //$MatchDateTime = date('Y-m-d H:i', $NextDateTime);
        $DateTime = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')) + 600);
        //$DateTime = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')) + 17 * 3600);
        $Query = $this->db->query("SELECT MatchStartDateTime,MatchID, MatchGUID, MatchIDLive FROM `football_sports_matches` "
                        . "JOIN tbl_entity ON tbl_entity.EntityID = football_sports_matches.MatchID "
                        . "WHERE MatchStartDateTime <= '" . $DateTime . "' "
                        . "AND StatusID = 1")->result_array();
        foreach ($Query as $matches) {
            $Contests = $this->Football_Contest_model->getContests('MatchID, ContestID', array('StatusID' => array(1), 'IsVirtualUserJoined' => "Yes", 'MatchID' => $matches['MatchID']), TRUE, 1, 100);
            if ($Contests['Data']['TotalRecords'] > 0) {
                $contestsData = $Contests['Data']['Records'];
                if (count($contestsData) > 0) {
                    foreach ($contestsData as $Contest) {
                        $realJoinContestUser = $this->db->query("SELECT football_sports_contest_join.UserID, "
                                        . "football_sports_contest_join.MatchID, football_sports_contest_join.UserTeamID, "
                                        . "CONCAT('[',GROUP_CONCAT(JSON_OBJECT( 'MatchID', football_sports_contest_join.MatchID, "
                                        . "'PlayerID', PlayerID, 'PlayerPosition' ,PlayerPosition )), ']') AS userTeamPlayers "
                                        . "FROM `football_sports_contest_join` JOIN tbl_users ON "
                                        . "tbl_users.UserID = football_sports_contest_join.UserID JOIN football_sports_users_team_players"
                                        . " ON football_sports_users_team_players.UserTeamID = football_sports_contest_join.UserTeamID"
                                        . " WHERE football_sports_contest_join.MatchID = '" . $matches['MatchID'] . "' AND "
                                        . "ContestID = '" . $Contest['ContestID'] . "' AND UserTypeID = 2 AND CopiedTeam = 0 "
                                        . "GROUP BY football_sports_contest_join.UserID")->result_array();
                        if (!empty($realJoinContestUser)) {
                            $virtualJoinContestUser = $this->db->query("SELECT football_sports_contest_join.UserID, football_sports_contest_join.MatchID,"
                                            . " football_sports_contest_join.UserTeamID FROM `football_sports_contest_join` JOIN tbl_users ON "
                                            . "tbl_users.UserID = football_sports_contest_join.UserID WHERE football_sports_contest_join.MatchID = '" . $matches['MatchID'] . "'"
                                            . " AND ContestID = '" . $Contest['ContestID'] . "' AND UserTypeID = 3 AND CopiedTeam = 0 "
                                            . "LIMIT " . count($realJoinContestUser) . "")->result_array();
                            foreach ($virtualJoinContestUser as $key => $virtualJoinContest) {
                                $this->db->where('UserTeamID', $virtualJoinContest['UserTeamID']);
                                $this->db->limit(11);
                                $this->db->delete('football_sports_users_team_players');
                                $UserTeamPlayers = array();
                                foreach (json_decode($realJoinContestUser[$key]['userTeamPlayers'], TRUE) as $RealPlayer) {

                                    $UserTeamPlayers[] = array(
                                        'UserTeamID' => $virtualJoinContest['UserTeamID'],
                                        'MatchID' => $RealPlayer['MatchID'],
                                        'PlayerID' => $RealPlayer['PlayerID'],
                                        'PlayerPosition' => $RealPlayer['PlayerPosition']
                                    );
                                }
                                if (!empty($UserTeamPlayers)) {
                                    $this->db->insert_batch('football_sports_users_team_players', $UserTeamPlayers);
                                    $this->db->where(array('UserTeamID' => $virtualJoinContest['UserTeamID'], 'ContestID' => $Contest['ContestID']));
                                    $this->db->limit(1);
                                    $this->db->update('football_sports_contest_join', array('CopiedTeam' => $realJoinContestUser[$key]['UserTeamID']));

                                    $this->db->where(array('UserTeamID' => $realJoinContestUser[$key]['UserTeamID'], 'ContestID' => $Contest['ContestID']));
                                    $this->db->limit(1);
                                    $this->db->update('football_sports_contest_join', array('CopiedTeam' => 1));
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /*
      Description:  Use to update wallet opening balance
      URL:      /api/utilities/updateOpeningBalance
     */

    public function updateOpeningBalance_get() {

        /* Reset Entries */
        $this->db->query('UPDATE `tbl_users_wallet` SET `OpeningWalletAmount` = 0,`OpeningWinningAmount`=0,`OpeningCashBonus`=0,`ClosingWalletAmount`=0,`ClosingWinningAmount`=0,`ClosingCashBonus` =0');
        $Query = $this->db->query('SELECT `UserID` FROM `tbl_users_wallet` GROUP BY UserID');
        if ($Query->num_rows() > 0) {
            foreach ($Query->result_array() as $key => $Record) {
                $Query1 = $this->db->query('SELECT * FROM `tbl_users_wallet` WHERE `UserID` = ' . $Record['UserID'] . ' ORDER BY `WalletID` ASC');
                foreach ($Query1->result_array() as $key1 => $Record1) {
                    $Query2 = $this->db->query('SELECT * FROM `tbl_users_wallet` WHERE `UserID` = ' . $Record['UserID'] . ' AND WalletID < ' . $Record1['WalletID'] . ' ORDER BY `WalletID` DESC LIMIT 1');
                    if ($Query2->num_rows() > 0) {
                        $OpeningWalletAmount = $Query2->row()->ClosingWalletAmount;
                        $OpeningWinningAmount = $Query2->row()->ClosingWinningAmount;
                        $OpeningCashBonus = $Query2->row()->ClosingCashBonus;
                        $ClosingWalletAmount = ($Record1['StatusID'] == 5) ? (($OpeningWalletAmount != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningWalletAmount + $Record1['WalletAmount'] : $OpeningWalletAmount - $Record1['WalletAmount'] ) : $Record1['WalletAmount']) : $OpeningWalletAmount;
                        $ClosingWinningAmount = ($Record1['StatusID'] == 5) ? (($OpeningWinningAmount != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningWinningAmount + $Record1['WinningAmount'] : $OpeningWinningAmount - $Record1['WinningAmount'] ) : $Record1['WinningAmount']) : $OpeningWinningAmount;
                        $ClosingCashBonus = ($Record1['StatusID'] == 5) ? (($OpeningCashBonus != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningCashBonus + $Record1['CashBonus'] : $OpeningCashBonus - $Record1['CashBonus'] ) : $Record1['CashBonus']) : $OpeningCashBonus;
                    } else {
                        $OpeningWalletAmount = $OpeningWinningAmount = $OpeningCashBonus = 0;
                        $ClosingWalletAmount = ($Record1['StatusID'] == 5) ? (($OpeningWalletAmount != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningWalletAmount + $Record1['WalletAmount'] : $OpeningWalletAmount - $Record1['WalletAmount'] ) : $Record1['WalletAmount']) : 0;
                        $ClosingWinningAmount = ($Record1['StatusID'] == 5) ? (($OpeningWinningAmount != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningWinningAmount + $Record1['WinningAmount'] : $OpeningWinningAmount - $Record1['WinningAmount'] ) : $Record1['WinningAmount']) : 0;
                        $ClosingCashBonus = ($Record1['StatusID'] == 5) ? (($OpeningCashBonus != 0) ? (($Record1['TransactionType'] == 'Cr') ? $OpeningCashBonus + $Record1['CashBonus'] : $OpeningCashBonus - $Record1['CashBonus'] ) : $Record1['CashBonus']) : 0;
                    }
                    $UpdateArr = array(
                        'OpeningWalletAmount' => $OpeningWalletAmount,
                        'OpeningWinningAmount' => $OpeningWinningAmount,
                        'OpeningCashBonus' => $OpeningCashBonus,
                        'ClosingWalletAmount' => $ClosingWalletAmount,
                        'ClosingWinningAmount' => $ClosingWinningAmount,
                        'ClosingCashBonus' => $ClosingCashBonus
                    );
                    $this->db->where('WalletID', $Record1['WalletID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_users_wallet', $UpdateArr);
                }
            }
        }
    }

    public function wrongWinningDistribution_get() {
        exit;
        /* Reset Entries */
        $Query = $this->db->query("SELECT * FROM `tbl_users_wallet` WHERE `Narration` = 'Join Contest Winning' AND `EntryDate` LIKE '%2019-06-10%' ORDER BY `WalletID` DESC");
        if ($Query->num_rows() > 0) {
            foreach ($Query->result_array() as $key => $Record) {
                $Query1 = $this->db->query('SELECT WinningAmount,UserID FROM `tbl_users` WHERE `UserID` = ' . $Record['UserID'] . '');
                $UserWallet = $Query1->row_array();
                if (!empty($UserWallet)) {
                    $ContestWinningAmount = $Record['WinningAmount'];
                    $UserWinningAmount = $UserWallet['WinningAmount'];
                    if ($UserWinningAmount >= $ContestWinningAmount) {
                        $WalletData = array(
                            "Amount" => $ContestWinningAmount,
                            "WinningAmount" => $ContestWinningAmount,
                            "TransactionType" => 'Dr',
                            "Narration" => 'Wrong Winning Distribution',
                            "EntityID" => $Record['EntityID'],
                            "UserTeamID" => $Record['UserTeamID'],
                            "EntryDate" => date("Y-m-d H:i:s")
                        );
                        $this->Users_model->addToWallet($WalletData, $Record['UserID'], 5);
                    }
                }
            }
        }
    }

    public function CancelUpcomingContest_get() {
        $Query = $this->db->query("SELECT ContestID FROM `football_sports_contest` `C` JOIN `tbl_entity` `E` ON `C`.`ContestID` = `E`.`EntityID` WHERE `E`.`StatusID` = 1 AND `C`.`PreContestID` is NOT NULL ORDER BY `C`.`PreContestID` DESC")->result_array();

        if (!empty($Query)) {
            foreach ($Query as $Record) {
                $Query1 = $this->db->query("SELECT ContestID FROM `football_sports_contest_join` `JC` WHERE `JC`.`ContestID` =" . $Record['ContestID']);
                if ($Query1->num_rows() > 0) {
                    
                } else {
                    $this->Football_Contest_model->cancelContest('', '', $Record['ContestID']);
                }
            }
        }
    }

}
