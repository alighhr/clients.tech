<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Utilities extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Utility_model');
        $this->load->model('Post_model');
        $this->load->model('Sports_model');
        $this->load->model('Contest_model');
        $this->load->model('Users_model');
        $this->load->model('AuctionDrafts_model');
        $this->load->model('PreContest_model');
        $this->load->model('SnakeDrafts_model');
    }

    /*
      Description: 	get site setting.
      URL: 			/api/utilities/setting/
     */

    function setting_get() {
        $ConfigData = $this->Utility_model->getConfigs(@$this->Post);
        if (!empty($ConfigData)) {
            $this->Return['Data'] = $ConfigData['Data'];
        }
    }

    /*
      Description: 	Use to send email to webadmin.
      URL: 			/api/utilities/contact/
     */

    public function contact_post() {
        /* Validation section */
        $this->form_validation->set_rules('Name', 'Name', 'trim');
        $this->form_validation->set_rules('Email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim');
        $this->form_validation->set_rules('Title', 'Title', 'trim');
        $this->form_validation->set_rules('Message', 'Message', 'trim|required');
        $this->form_validation->validation($this); /* Run validation */
        /* Validation - ends */
        $send = send_mail(array(
            'emailTo' => SITE_CONTACT_EMAIL,
            'template_id' => CONTACT,
            'Subject' => $this->Post['Name'] . ' filled out the contact form on ' . SITE_NAME,
            "Name" => $this->Post['Name'],
            'Email' => $this->Post['Email'],
            'PhoneNumber' => $this->Post['PhoneNumber'],
            'Title' => $this->Post['Title'],
            'Message' => $this->Post['Message']
        ));
        if (!$send) {
          return false;
        }
    }

    /*
      Description:  Use execute cron jobs.
      URL:      /api/utilities/getCountries
     */

    public function getCountries_post() {
        $CountryData = $this->Utility_model->getCountries();
        if (!empty($CountryData)) {
            $this->Return['Data'] = $CountryData['Data'];
        }
    }

    public function getStates_post() {
        /* Validation section */
        $this->form_validation->set_rules('CountryCode', 'Country Code', 'trim|required');
        $this->form_validation->validation($this); /* Run validation */
        /* Validation - ends */

        $StateData = $this->Utility_model->getStates(array('CountryCode' => $this->Post['CountryCode'], 'Status' => 2));

        if (!empty($StateData)) {
            $this->Return['Data'] = $StateData['Data'];
        }
    }

    /*
      Description:    Use to get list of random posts.
      URL:            /api/utilities/getPosts
     */

    public function getPosts_post() {
        /* Validation section */
        $this->form_validation->set_rules('PageNo', 'PageNo', 'trim|integer');
        $this->form_validation->set_rules('PageSize', 'PageSize', 'trim|integer');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $Posts = $this->Post_model->getPosts('
            P.PostGUID,
            P.PostContent,
            P.PostCaption,
            ', array(), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if ($Posts) {
            $this->Return['Data'] = $Posts['Data'];
        }
    }

    public function sendAppLink_post() {
        $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        $this->Utility_model->sendSMS(array(
            'PhoneNumber' => $this->Post['PhoneNumber'],
            'Text' => "Here is the new " . SITE_NAME . " Android Application! Click on the link to download the App and Start Winning. ".$this->db->query("SELECT ConfigTypeValue FROM `set_site_config` WHERE `ConfigTypeGUID` = 'AndridAppUrl' LIMIT 1")->row()->ConfigTypeValue
        ));
        $this->Return['Message'] = "Link Sent successfully.";
    }

    /*
      Description:  Use to create pre draft contest
      URL:      /api/utilities/createPreContest
     */

    public function createPreContest_get() {
        $this->PreContest_model->createPreContest();
    }

    /*
      Description:  Use to Delete Reminder Notification
      URL:      /api/utilities/deleteNotifications
     */

    public function deleteNotifications_get() {
        $this->Utility_model->deleteNotifications();
    }

    /*
      Description: 	Cron jobs to get series data.
      URL: 			/api/utilities/getSeriesLive
     */

    public function getSeriesLive_get() {
        $CronID = $this->Utility_model->insertCronLogs('getSeriesLive');
        if (SPORTS_API_NAME == 'ENTITY') {
            $SeriesData = $this->Sports_model->getSeriesLiveEntity($CronID);
            $SeriesData = $this->Sports_model->getSeriesLiveEntityRounds($CronID);
            $SeriesData = $this->Sports_model->updateSeriesANDRoundsStatusByMatchEntity();
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $SeriesData = $this->Sports_model->getSeriesRoundsLiveCricketAPI($CronID);
            $SeriesData = $this->Sports_model->updateSeriesANDRoundsStatusByMatch($CronID);
            $SeriesData = $this->Sports_model->getSeriesLiveCricketAPI($CronID);
        }
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get matches data.
      URL: 			/api/utilities/getMatchesLive
     */

    public function getMatchesLive_get() {
        $CronID = $this->Utility_model->insertCronLogs('getMatchesLive');
        if (SPORTS_API_NAME == 'ENTITY') {
            $MatchesData = $this->Sports_model->getMatchesLiveEntity($CronID);
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $MatchesData = $this->Sports_model->getMatchesLiveCricketApi($CronID);
        }
        if (!empty($MatchesData)) {
            $this->Return['Data'] = $MatchesData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get players data.
      URL: 			/api/utilities/getPlayersLive
     */

    public function getPlayersLive_get() {
        $CronID = $this->Utility_model->insertCronLogs('getPlayersLive');
        if (SPORTS_API_NAME == 'ENTITY') {
            $PlayersData = $this->Sports_model->getMatchWisePlayersLiveEntity($CronID);
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $PlayersData = $this->Sports_model->getPlayersLiveCricketApi($CronID);
        }
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get player stats data.
      URL: 			/api/utilities/getPlayerStatsLive
     */

    public function getPlayerStatsLive_get() {
        $CronID = $this->Utility_model->insertCronLogs('getPlayerStatsLive');
        if (SPORTS_API_NAME == 'ENTITY') {
            $PlayersStatsData = $this->Sports_model->getPlayerStatsLiveEntity($CronID);
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $PlayersStatsData = $this->Sports_model->getPlayerStatsLiveCricketApi($CronID);
        }
        if (!empty($PlayersStatsData)) {
            $this->Return['Data'] = $PlayersStatsData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to get player stats data.
      URL:      /api/utilities/getPlayerStatsAllLive
     */

    public function getPlayerStatsAllLive_get() {
        $CronID = $this->Utility_model->insertCronLogs('getPlayerStatsLive');
        if (SPORTS_API_NAME == 'ENTITY') {
            $PlayersStatsData = $this->Sports_model->playerStatsUpdateAllEntity($CronID);
        }
        if (!empty($PlayersStatsData)) {
            $this->Return['Data'] = $PlayersStatsData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get match live score
      URL: 			/api/utilities/getMatchScoreLive
     */

    public function getMatchScoreLive_get() {

        $CronID = $this->Utility_model->insertCronLogs('getMatchScoreLive');
        if (SPORTS_API_NAME == 'ENTITY') {
            $MatchScoreLiveData = $this->Sports_model->getMatchScoreLiveEntity($CronID);
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $MatchScoreLiveData = $this->Sports_model->getMatchScoreLiveCricketApi($CronID);
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $this->Sports_model->getPlayerPointsCricketAPI($CronID);
        }
        $this->Sports_model->getJoinedContestTeamPoints($CronID);
        if (!empty($MatchScoreLiveData)) {
            $this->Return['Data'] = $MatchScoreLiveData;
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get update match playing11
      URL: 			/api/utilities/getMatchScoreLive
     */

    public function getLivePlaying11MatchPlayer_get() {
        if (SPORTS_API_NAME == 'ENTITY') {
            $this->Sports_model->getLivePlaying11MatchPlayerEntityAPI();
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $this->Sports_model->getLivePlaying11MatchPlayerCricketAPI();
        }
    }

    public function send_post() {
        sendPushMessage($this->Post['UserID'], $this->Post['Title'], $this->Post['Message'], $this->Post['Data']);
    }

    /*
      Description: 	Cron jobs to auto cancel contest.
      URL: 			/api/utilities/autoCancelContest
     */

    public function autoCancelContest_get() {
        $CronID = $this->Utility_model->insertCronLogs('autoCancelContest');
        $this->Sports_model->autoCancelContest($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to auto cancel contest refund amount.
      URL:          /api/utilities/refundAmountCancelContest
     */

    public function refundAmountCancelContest_get() {
        $CronID = $this->Utility_model->insertCronLogs('refundAmountCancelContest');
        $this->Sports_model->refundAmountCancelContest($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Set auction draft winner and distribute amount in wallet.
      URL:          /api/utilities/setAuctionDraftWinner
     */

    public function setAuctionDraftWinner_get() {
        $CronID = $this->Utility_model->insertCronLogs('setAuctionDraftWinner');
        $this->Sports_model->setAuctionDraftWinner($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get player points.
      URL: 			/api/utilities/getPlayerPoints
     */

    public function getPlayerPoints_get() {
        $CronID = $this->Utility_model->insertCronLogs('getPlayerPoints');
        if (SPORTS_API_NAME == 'ENTITY') {
            $this->Sports_model->getPlayerPointsEntity($CronID);
        }
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $this->Sports_model->getPlayerPointsCricketAPI($CronID);
        }
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get joined player points.
      URL: 			/api/utilities/getJoinedContestPlayerPoints
     */

    public function getJoinedContestPlayerPoints_get() {
        $CronID = $this->Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
        $this->Sports_model->getJoinedContestTeamPoints($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to transfer joined contest data (MongoDB To MySQL).
      URL:          /api/utilities/tranferJoinedContestData
     */

    public function tranferJoinedContestData_get() {
        $CronID = $this->Utility_model->insertCronLogs('tranferJoinedContestData');
        $this->Sports_model->tranferJoinedContestData($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get auction joined player points.
      URL: 			/api/utilities/getAuctionJoinedUserTeamsPlayerPoints
     */

    public function getAuctionJoinedUserTeamsPlayerPoints_get() {
        $CronID = $this->Utility_model->insertCronLogs('getAuctionJoinedUserTeamsPlayerPoints');
        $this->Sports_model->getAuctionJoinedUserTeamsPlayerPoints($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto set winner.
      URL: 			/api/utilities/setContestWinners
     */

    public function setContestWinners_get() {
        $CronID = $this->Utility_model->insertCronLogs('setContestWinners');
        $this->Sports_model->setContestWinners($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto set winner distribue amount.
      URL: 			/api/utilities/amountDistributeContestWinner
     */

    public function amountDistributeContestWinner_get() {
        $CronID = $this->Utility_model->insertCronLogs('amountDistributeContestWinner');
        $this->Sports_model->amountDistributeContestWinner($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to auto send mail to winner.
      URL:          /api/utilities/setContestWinners
     */

    public function sendMailContestWinners_get() {
        $CronID = $this->Utility_model->insertCronLogs('ContestWinnersMailSend');
        $this->Sports_model->ContestWinnersMailSend($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs for Notification for upcoming Matches.
      URL:          /api/utilities/notifyUpcomingMatches
     */

    public function notifyUpcomingMatches_get() {
        $this->Sports_model->notifyUpcomingMatches();
    }

    /*
      Description: 	Cron jobs to auto add minute in every hours.
      URL: 			/api/utilities/liveAuctionAddMinuteInEveryHours
     */

    public function auctionLiveAddMinuteInEveryHours_get() {
        $CronID = $this->Utility_model->insertCronLogs('liveAuctionAddMinuteInEveryHours');
        $this->AuctionDrafts_model->auctionLiveAddMinuteInEveryHours($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to get auction joined player points.
      URL: 			/api/utilities/getAuctionJoinedUserTeamsPlayerPoints
     */

    public function getAuctionDraftJoinedUserTeamsPlayerPoints_get() {
        $CronID = $this->Utility_model->insertCronLogs('getAuctionJoinedUserTeamsPlayerPoints');
        $this->Sports_model->getAuctionDraftJoinedUserTeamsPlayerPoints($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto draft team submit if user not submit in 15 minutes.
      URL: 			/api/utilities/auctionTeamAutoSubmit
     */

    public function auctionTeamAutoSubmit_get() {
        $CronID = $this->Utility_model->insertCronLogs('draftTeamAutoSubmit');
        $this->AuctionDrafts_model->auctionTeamAutoSubmit($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description: 	Cron jobs to auto draft team submit if user not submit in 15 minutes.
      URL: 			/api/utilities/draftTeamAutoSubmit
     */

    public function draftTeamAutoSubmit_get() {
        $CronID = $this->Utility_model->insertCronLogs('draftTeamAutoSubmit');
        $this->SnakeDrafts_model->draftTeamAutoSubmit($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    /*
      Description:  Cron jobs to auto cancel contest refund amount debug function.
      URL:          /api/utilities/refundAmountCancelContest
     */

    public function refundAmountCancelContestDebug_get() {
        $this->Sports_model->refundAmountCancelContestDebug();
    }

    public function amountDistributeContestWinnerDebug_get() {
        $this->Sports_model->amountDistributeContestWinnerDebug();
    }

    /*
      Description: To get statics
     */

    public function dashboardStatics_post()
    {
        $CurrentDateIST = date('Y-m-d',(strtotime(date('Y-m-d H:i:s'))+TIMEZONE_DIFF_IN_SECONDS));
        $SiteStatics = new stdClass();
        $SiteStatics = $this->db->query(
            'SELECT
                                            TotalUnverifiedUsers,
                                            TotalUsers,
                                            TotalContest,
                                            TodayContests,
                                            TotalDeposits,
                                            CurrentTotalBalance,
                                            CurrentTotalWinnings,
                                            TotalWithdraw,
                                            TodayDeposit,
                                            NewUsers,
                                            TotalDeposits - TotalWithdraw AS TotalEarning,
                                            PendingWithdraw,
                                            TotalBooking,
                                            TotalBookingPending
                                        FROM
                                            (SELECT
                                              (
                                                    SELECT
                                                        COUNT(U.UserID) AS `TotalUsers`
                                                    FROM
                                                        `tbl_users` U,tbl_entity E
                                                    WHERE E.EntityID=U.UserID AND
                                                        U.`UserTypeID` = 2 AND E.StatusID = 1
                                                ) AS TotalUnverifiedUsers,
                                                (
                                                    SELECT
                                                        COUNT(U.UserID) AS `TotalUsers`
                                                    FROM
                                                        `tbl_users` U,tbl_entity E
                                                    WHERE E.EntityID=U.UserID AND
                                                        U.`UserTypeID` = 2 AND E.StatusID = 2
                                                ) AS TotalUsers,
                                                (
                                                    SELECT
                                                        COUNT(UserID) AS `NewUsers`
                                                    FROM
                                                        `tbl_users` U, `tbl_entity` E
                                                    WHERE
                                                        U.`UserTypeID` = 2 AND U.UserID = E.EntityID AND DATE(E.EntryDate) = "' . $CurrentDateIST . '"
                                                ) AS NewUsers,
                                                (
                                                    SELECT
                                                        COUNT(ContestID) AS `TotalContest`
                                                    FROM
                                                        `sports_contest`
                                                ) AS TotalContest,
                                                (
                                                    SELECT COUNT(DISTINCT(C.ContestID)) FROM `sports_contest` C, `sports_matches` M WHERE C.MatchID = M.MatchID AND DATE(M.MatchStartDateTime) = "' . $CurrentDateIST . '"
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
                                                       IFNULL(SUM(`WalletAmount`),0) AS CurrentTotalBalance
                                                    FROM
                                                        `tbl_users`
                                                ) AS CurrentTotalBalance,
                                                (
                                                    SELECT
                                                       IFNULL(SUM(`WinningAmount`),0) AS CurrentTotalWinnings
                                                    FROM
                                                        `tbl_users`
                                                ) AS CurrentTotalWinnings,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`WalletAmount`),0) AS TodayDeposit
                                                    FROM
                                                        `tbl_users_wallet`
                                                    WHERE
                                                        `Narration`= "Deposit Money" AND
                                                        `StatusID` = 5 AND DATE(EntryDate) = "' . $CurrentDateIST . '"
                                                ) AS TodayDeposit,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`Amount`),0) AS TotalWithdraw
                                                    FROM
                                                        `tbl_users_wallet`
                                                    WHERE
                                                        `StatusID` = 5 AND Narration = "Withdrawal Request"
                                                ) AS TotalWithdraw,
                                                (
                                                    SELECT
                                                        IFNULL(SUM(`Amount`),0) AS TotalWithdraw
                                                    FROM
                                                        `tbl_users_withdrawal`
                                                    WHERE
                                                        `StatusID` = 1
                                                ) AS PendingWithdraw,
                                                (
                                                    SELECT
                                                        COUNT(BookingID) AS `TotalBooking`
                                                    FROM
                                                        `tbl_booking`
                                                ) AS TotalBooking,
                                                (
                                                    SELECT
                                                        COUNT(BookingID) AS `TotalBookingPending`
                                                    FROM
                                                        `tbl_booking`
                                                    WHERE
                                                        `PaymentStatus` = "Pending"
                                                ) AS TotalBookingPending
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
        $WalletDetails = $this->Utility_model->getTotalDeposit(@$this->Post['Params'], $this->Post, TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
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

        $VersionData = $this->Utility_model->getAppVersionDetails();
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

    function clean($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

        return trim(preg_replace('/-+/', ' ', $string)); // Replaces multiple hyphens with single one.
    }

    /*
      Name:     createVirtualUsers
      Description:  create virtual user users
      URL:      /utilities/createVirtualUsers/
     */

    public function createVirtualUsers_get() {

        $tlds = array("com");
        $char = "0123456789abcdefghijklmnopqrstuvwxyz";
        $Limit = 2;
        $Names = $this->Users_model->getDummyNames($Limit, 5000);
        $domainArray = ['gmail.com', 'yahoo.in', 'rediffmail.com', 'hotmail.com'];
        if (!empty($Names)) {
            for ($k = 0; $k < count($domainArray); $k++) {
                for ($j = 0; $j < $Limit; $j++) {
                    $Name = $this->clean($Names[$j]['Names']);
                    if (strtolower($Name) == "null") {
                        continue;
                    }
                    $UserName = ucwords($Name);
                    $UserUnique = str_replace(" ", "", $UserName);
                    $ulen = mt_rand(5, 10);
                    $dlen = mt_rand(7, 17);
                    $email = "";
                    for ($i = 1; $i <= $ulen; $i++) {
                        $email .= substr($char, mt_rand(0, strlen($char)), 1);
                    }
                    $email .= "@";
                    $email .= $domainArray[$k];
                    $email .= ".";
                    $email .= $tlds[mt_rand(0, (sizeof($tlds) - 1))];
                    if ($Limit % 2 == 0) {
                        $username = strtolower(trim($UserUnique)) . substr(md5(microtime()), rand(0, 26), 4);
                    } else {
                        $username = strtolower(trim($UserUnique)) . $this->generateRandomLetters(4);
                    }
                    $username = $this->clean($username);
                    $Input = array();
                    $Input['Email'] = $username . "@" . $domainArray[$k];
                    $Input['Username'] = $username;
                    $Input['FirstName'] = $UserName;
                    $Input['Password'] = 'A@123456';
                    $Input['Source'] = "Direct";
                    $Input['PanStatus'] = 2;
                    $Input['BankStatus'] = 2;
                    $Input['DocumentStatus'] = 2;
                    $Input['WalletAmount'] = 1000000;
                    $Input['CashBonus'] = 100;
                    $UserID = $this->Users_model->addVirtualUser($Input, 3, 1, 2);
                    /* if ($UserID) {
                      $this->Utility_model->generateReferralCode($UserID);
                      $WalletData = array(
                      "Amount" => 1000000,
                      "CashBonus" => 0,
                      "TransactionType" => 'Cr',
                      "Narration" => 'Deposit Money',
                      "EntryDate" => date("Y-m-d H:i:s")
                      );
                      $this->Users_model->addToWallet($WalletData, $UserID, 5);
                      } */
                }
            }
        }
    }

    function generateRandomLetters($length) {
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= chr(rand(ord('a'), ord('z')));
        }
        return $random;
    }

    /*
      Name:     createVirtualUserTeams
      Description:  create virtual user team
      URL:      /utilities/createVirtualUserTeams/
     */

    public function createVirtualUserTeams_get() {

        /* $Query = $this->db->query('SELECT UT.UserTeamID,UT.IsVirtual,U.UserID,U.UserTypeID FROM sports_users_teams UT,tbl_users U WHERE U.UserID=UT.UserID AND U.UserTypeID=3 AND UT.MatchID =011 AND UT.IsVirtual="Yes"');
          foreach ($Query->result_array() as $Value) {
          $Query = $this->db->query('SELECT ContestID FROM sports_contest_join WHERE UserTeamID = "' . $Value['UserTeamID'] . '"');
          if ($Query->num_rows() > 0) continue;

          $this->db->where('UserTeamID', $Value['UserTeamID']);
          $this->db->limit(11);
          $this->db->delete('sports_users_team_players');

          $this->db->where('EntityID', $Value['UserTeamID']);
          $this->db->where('EntityTypeID', 12);
          $this->db->limit(1);
          $this->db->delete('tbl_entity');
          } */
        ini_set('max_execution_time', 120);
        /* get upcoming matches */
        $DateTime = date('Y-m-d H:i', strtotime('+1 days', strtotime(date('Y-m-d H:i'))));

        $this->db->select('M.MatchID,M.MatchStartDateTime,M.TeamIDLocal,M.TeamIDVisitor,SeriesID,RoundID');
        $this->db->from('tbl_entity E, sports_matches M');
        $this->db->where("M.MatchID", "E.EntityID", FALSE);
        $this->db->where('M.MatchStartDateTime <=', $DateTime);
        $this->db->where("E.StatusID", 1);
        $this->db->order_by('M.MatchStartDateTime', "ASC");
        $this->db->limit(15);
        $Query = $this->db->get();

        if ($Query->num_rows() > 0) {
            $AllMatches = $Query->result_array();
            foreach ($AllMatches as $Match) {

                /* get upcoming contest */
                $this->db->select('C.MatchID,C.ContestID,C.ContestSize,C.IsVirtualTeamCreated');
                $this->db->from('tbl_entity E, sports_contest C');
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
                    $this->db->select('P.PlayerGUID,TP.PlayerID,TP.TeamID,TP.PlayerRole,FORMAT(TP.PlayerSalary, 1) as PlayerSalary, (SELECT SUM(`TotalPoints`) FROM `sports_team_players` as STP WHERE ' . $where . ' AND TP.PlayerID = STP.PlayerID) as TotalPointsMatch');
                    $this->db->from('sports_team_players TP,sports_players P');
                    $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
                    $this->db->where("TP.MatchID", $Match['MatchID']);
                    $this->db->where("TP.PlayerSalary >", 0);
                    $this->db->where("TP.IsActive", "Yes");
                    $this->db->order_by('TP.PlayerSalary', 'DESC');
                    $Query = $this->db->get();
                    $AllPlayers = $Query->result_array();
                    $AllPlayers = phparraysort($AllPlayers, array('TotalPointsMatch', 'PlayerSalary'));

                    foreach ($Contest as $ContestRows) {
                        if ($ContestRows['IsVirtualTeamCreated'] == "No") {
                            /* check total team created */
                            $this->db->select('COUNT(UserTeamID) as TotalTeam');
                            $this->db->from('sports_users_teams');
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
                                $this->db->where('NOT EXISTS(Select 1 from sports_users_teams T where T.UserID = U.UserID AND T.MatchID = ' . $Match['MatchID'] . ')');
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
                                /* update contest virtual */
                                $this->db->where('ContestID', $ContestRows['ContestID']);
                                $this->db->limit(1);
                                $this->db->update('sports_contest', array('IsVirtualTeamCreated' => 'Yes'));
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
        $playerCount = 0;
        $secondPlayerCount = 1;
        $batsman = 0;
        $wicketkeeper = 0;
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
                $playerRole = strtoupper($player['PlayerRole']);
                $creditPoints += 9;
                if ($teamId == $localteam_id) {
                    if ($wicketkeeper < 1) {
                        if ($playerRole == 'WICKETKEEPER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr1[] = $temp;
                            $wicketkeeper++;
                            $playerCount++;
                        }
                    }
                    if ($batsman < 3) {
                        if ($playerRole == 'BATSMAN') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr2[] = $temp;
                            $batsman++;
                            $playerCount++;
                        }
                    }
                    if ($bowler < 3) {
                        if ($playerRole == 'BOWLER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr3[] = $temp;
                            $bowler++;
                            $playerCount++;
                        }
                    }
                    if ($allrounder < 1) {
                        if ($playerRole == 'ALLROUNDER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr4[] = $temp;
                            $allrounder++;
                            $playerCount++;
                        }
                    }
                }
            }

            $res1 = array_merge($Arr1, $Arr2, $Arr3, $Arr4);
        }

        foreach ($matchPlayer as $player) {

            if (count($res2) <= (10 - count($res1))) {
                $playerId = $player['PlayerID'];
                $teamId = $player['TeamID'];
                $playerRole = strtoupper($player['PlayerRole']);
                if ($teamId == $visitorteam_id) {
                    if ($wicketkeeper < 4 && ($batsman >= 3 && $bowler >= 3 && $allrounder >= 1) || $wicketkeeper == 0) {
                        if ($playerRole == 'WICKETKEEPER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr5[] = $temp1;
                            $wicketkeeper++;
                            $secondPlayerCount++;
                        }
                    }
                    if ($batsman < 5 && ($wicketkeeper >= 1 && $bowler >= 3 && $allrounder >= 1) || $batsman < 3) {
                        if ($playerRole == 'BATSMAN') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr6[] = $temp1;
                            $batsman++;
                            $secondPlayerCount++;
                        }
                    }
                    if ($bowler < 5 && ($wicketkeeper >= 1 && $batsman >= 3 && $allrounder >= 1) || $bowler < 3) {
                        if ($playerRole == 'BOWLER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr7[] = $temp1;
                            $bowler++;
                            $secondPlayerCount++;
                        }
                    }
                    if ($allrounder < 4 && ($wicketkeeper >= 1 && $batsman >= 3 && $bowler >= 3) || $allrounder < 1) {
                        if ($playerRole == 'ALLROUNDER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr8[] = $temp1;
                            $allrounder++;
                            $secondPlayerCount++;
                        }
                    }
                }
            }

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
                    if ($PlayerRoles['WICKETKEEPER'] > 0 && $PlayerRoles['WICKETKEEPER'] < 5) {
                        if ($PlayerRoles['BATSMAN'] > 2 && $PlayerRoles['BATSMAN'] < 7) {
                            if ($PlayerRoles['BOWLER'] > 2 && $PlayerRoles['BOWLER'] < 7) {
                                if ($PlayerRoles['ALLROUNDER'] > 0 && $PlayerRoles['ALLROUNDER'] < 5) {

                                    $this->Contest_model->addUserTeam(array('UserTeamPlayers' => $playing11, 'UserTeamType' => 'Normal', 'Status' => 1, 'IsVirtual' => 'Yes'), $user_id, $match_id);
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

        $Contests = $this->Contest_model->getContests('UserJoinLimit,IsPaid,EntryFee,CashBonusContribution,WinningAmount,MatchID,IsDummyJoined,ContestID,ContestSize,TotalJoined,MatchStartDateTimeUTC,VirtualUserJoinedPercentage', array('StatusID' => array(1), 'IsVirtualUserJoined' => "Yes", "ContestFull" => "No"), TRUE, 1, 30);
        if (!empty($Contests['Data']['Records'])) {
            foreach ($Contests['Data']['Records'] as $Rows) {

                $Seconds = strtotime($Rows['MatchStartDateTimeUTC']) - strtotime($UtcDateTime);
                $hours = $Hours = $Seconds / 60 / 60;

                if ($Hours > 10) {
                    continue;
                }

                $dummyJoinedContest = 0;
                $dummyJoinedContests = $this->db->query("SELECT count(JC.ContestID) as DummyJoinedContest FROM sports_contest_join as JC JOIN tbl_users ON tbl_users.UserID = JC.UserID WHERE JC.ContestID = " . $Rows['ContestID'] . " AND tbl_users.UserTypeID = 3")->row()->DummyJoinedContest;


                if ($dummyJoinedContests) {
                    $dummyJoinedContest = $dummyJoinedContests;
                }

                $totalJoined = $Rows['TotalJoined'];
                $contestSize = $Rows['ContestSize'];
                $joinDummyUser = $Rows['VirtualUserJoinedPercentage'];
                $dummyUserPercentage = round(($contestSize * $joinDummyUser) / 100);
                $dummyUserPercentageReal = $dummyUserPercentage;
                if ($contestSize <= $dummyUserPercentageReal) {
                    $dummyUserPercentageReal = $dummyUserPercentage = $contestSize;
                }
                if ($dummyJoinedContest >= $dummyUserPercentage) {

                    $this->Contest_model->UpdateVirtualJoinContest($Rows['ContestID']);
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
                $VitruelTeamPlayer = $this->Contest_model->GetVirtualTeamPlayerMatchWise($Rows['MatchID'], $dummyUserPercentage, $Rows['ContestID']);

                if (!empty($VitruelTeamPlayer)) {
                    //print_r($VitruelTeamPlayer);die;
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
                                if (!($c == 1 && $vc == 1)) {
                                    continue;
                                }

                                if ($this->db->query('SELECT COUNT(EntryDate) `TotalJoined` FROM `sports_contest_join` WHERE `ContestID` =' . $Rows['ContestID'] . ' AND UserID = ' . $usersTeam['UserID'])->row()->TotalJoined >= $Rows['UserJoinLimit']) {
                                    continue;
                                }

                                $JoinedContest = $this->db->query('SELECT ContestID FROM sports_contest_join WHERE UserTeamID =' . $usersTeam['UserTeamID'] . ' AND ContestID=' . $Rows['ContestID'] . ' AND UserID=' . $usersTeam['UserID'] . ' LIMIT 1');
                                if ($JoinedContest->num_rows() > 0) {
                                    continue;
                                }

                                $PostInput = array();

                                $Contests = $this->Contest_model->getContests('ContestSize,TotalJoined', array('StatusID' => array(1), 'IsVirtualUserJoined' => "Yes", "ContestID" => $Rows['ContestID']), FALSE);

                                if (!empty($Contests)) {
                                    if ($Contests['TotalJoined'] >= $Contests['ContestSize']) {
                                        break;
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
                                $ContestData = $this->db->query("SELECT COUNT(*) as TotalJoined FROM sports_contest_join WHERE ContestID = '" . $Rows['ContestID'] . "'");
                                $ContestData = $ContestData->row();
                                if (!empty($ContestData)) {
                                    if ($ContestData->TotalJoined >= $Contests['ContestSize']) {
                                        break;
                                    }
                                }
                                $PostInput['IsPaid'] = $this->Contest_model->joinContestVirtual($Rows, $usersTeam['UserID'], $Rows['ContestID'], $Rows['MatchID'], $usersTeam['UserTeamID']);
                            }
                        }
                    }
                    $this->Contest_model->ContestUpdateVirtualTeam($Rows['ContestID'], $Rows['IsDummyJoined']);
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
        $DateTime = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')) + 600);
        //$DateTime = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')) + 19 * 3600);
        $Query = $this->db->query("SELECT MatchStartDateTime,MatchID, MatchGUID, MatchIDLive FROM `sports_matches` "
                        . "JOIN tbl_entity ON tbl_entity.EntityID = sports_matches.MatchID "
                        . "WHERE MatchStartDateTime <= '" . $DateTime . "' "
                        . "AND StatusID = 1")->result_array();
        foreach ($Query as $matches) {
            $Contests = $this->Contest_model->getContests('MatchID, ContestID', array('StatusID' => array(1), 'IsVirtualUserJoined' => "Yes", 'MatchID' => $matches['MatchID']), TRUE, 1, 100);
            if ($Contests['Data']['TotalRecords'] > 0) {
                $contestsData = $Contests['Data']['Records'];
                if (count($contestsData) > 0) {
                    foreach ($contestsData as $Contest) {
                        $realJoinContestUser = $this->db->query("SELECT sports_contest_join.UserID, sports_contest_join.MatchID, sports_contest_join.UserTeamID, CONCAT('[',GROUP_CONCAT(JSON_OBJECT( 'MatchID', sports_contest_join.MatchID, 'PlayerID', PlayerID, 'PlayerPosition' ,PlayerPosition )), ']') AS userTeamPlayers FROM `sports_contest_join` JOIN tbl_users ON tbl_users.UserID = sports_contest_join.UserID JOIN sports_users_team_players ON sports_users_team_players.UserTeamID = sports_contest_join.UserTeamID WHERE sports_contest_join.MatchID = '" . $matches['MatchID'] . "' AND ContestID = '" . $Contest['ContestID'] . "' AND UserTypeID = 2 AND CopiedTeam = 0 GROUP BY sports_contest_join.UserID")->result_array();
                        if (!empty($realJoinContestUser)) {
                            $virtualJoinContestUser = $this->db->query("SELECT sports_contest_join.UserID, sports_contest_join.MatchID,"
                                            . " sports_contest_join.UserTeamID FROM `sports_contest_join` JOIN tbl_users ON "
                                            . "tbl_users.UserID = sports_contest_join.UserID WHERE sports_contest_join.MatchID = '" . $matches['MatchID'] . "'"
                                            . " AND ContestID = '" . $Contest['ContestID'] . "' AND UserTypeID = 3 AND CopiedTeam = 0 "
                                            . "LIMIT " . count($realJoinContestUser) . "")->result_array();
                            foreach ($virtualJoinContestUser as $key => $virtualJoinContest) {

                                $this->db->trans_start();

                                $this->db->where('UserTeamID', $virtualJoinContest['UserTeamID']);
                                $this->db->limit(11);
                                $this->db->delete('sports_users_team_players');
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
                                    $this->db->insert_batch('sports_users_team_players', $UserTeamPlayers);
                                    $this->db->where(array('UserTeamID' => $virtualJoinContest['UserTeamID'], 'ContestID' => $Contest['ContestID']));
                                    $this->db->limit(1);
                                    $this->db->update('sports_contest_join', array('CopiedTeam' => $realJoinContestUser[$key]['UserTeamID']));

                                    $this->db->where(array('UserTeamID' => $realJoinContestUser[$key]['UserTeamID'], 'ContestID' => $Contest['ContestID']));
                                    $this->db->limit(1);
                                    $this->db->update('sports_contest_join', array('CopiedTeam' => 1));
                                }

                                $this->db->trans_complete();
                                if ($this->db->trans_status() === false) {
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function checkVirtualUserTeamPlayer_get() {

        $this->db->select('M.MatchID,M.MatchStartDateTime,M.TeamIDLocal,M.TeamIDVisitor,SeriesID,RoundID');
        $this->db->from('tbl_entity E, sports_matches M');
        $this->db->where("M.MatchID", "E.EntityID", FALSE);
        $this->db->where("E.StatusID", 1);
        $this->db->order_by('M.MatchStartDateTime', "ASC");
        $this->db->limit(15);
        $Query = $this->db->get();

        if ($Query->num_rows() > 0) {
            $AllMatches = $Query->result_array();
            $a = array();
            foreach ($AllMatches as $Match) {
                $this->db->select("SUT.UserTeamID, SUT.UserID,(Select CONCAT('[',GROUP_CONCAT(distinct CONCAT('{\"PlayerID\":\"',UTP.PlayerID,'\",\"PlayerPosition\":\"',UTP.PlayerPosition,'\",\"TeamID\":\"',ST.TeamID,'\"}')),']') FROM sports_users_team_players UTP,sports_team_players ST WHERE ST.PlayerID=UTP.PlayerID AND UTP.UserTeamID = SUT.UserTeamID AND ST.MatchID= '" . $Match['MatchID'] . "') as Players");
                $this->db->from("sports_users_teams SUT");
                $this->db->where('SUT.MatchID', $Match['MatchID']);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $TeamPlayers = $Query->row_array();
                    $Players = json_decode($TeamPlayers['Players'], true);
                    $PlayerPoisitions = array_count_values(array_column($Players, 'PlayerPosition'));
                    $PlayerTeams = array_count_values(array_column($Players, 'TeamID'));
                    $Flag = FALSE;
                    if ($PlayerPoisitions['Captain'] != 1 && $PlayerPoisitions['ViceCaptain'] !== 1) {
                        $Flag = TRUE;
                    }
                    if (!$Flag) {
                        foreach ($PlayerTeams as $t) {
                            if ($t >= 8) {
                                $Flag = TRUE;
                            }
                        }
                    }
                    if ($Flag) {
                        $a[]['UserTeamID'] = $TeamPlayers['UserTeamID'];
                    }
                }
            }
        }
        print_r($a);
        exit;

        $Contests = $this->Contest_model->getContests('ContestID,MatchID,SeriesID,RoundID', array('ContestGUID' => "99d1bbec-c280-8b1c-b745-09b6a358bbe8"), TRUE, 1, 30);
        if (!empty($Contests['Data']['Records'])) {

            foreach ($Contests['Data']['Records'] as $Rows) {

                /* get upcoming matches */
                $this->db->select('M.MatchID,M.MatchStartDateTime,M.TeamIDLocal,M.TeamIDVisitor,SeriesID,RoundID');
                $this->db->from('tbl_entity E, sports_matches M');
                $this->db->where("M.MatchID", "E.EntityID", FALSE);
                $this->db->where("M.MatchID", $Rows['MatchID']);
                $this->db->order_by('M.MatchStartDateTime', "ASC");
                $this->db->limit(1);
                $Query = $this->db->get();
                $MatchUsers = $Query->row_array();

                $this->db->select('U.UserID,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.UserRank');
                $this->db->from('sports_contest_join JC, tbl_users U');
                $this->db->where("JC.UserID", "U.UserID", FALSE);
                $this->db->where("JC.ContestID", $Rows['ContestID']);
                $this->db->where("U.UserTypeID", 3);
                //$this->db->where("JC.UserTeamID", 111641);
                $Query = $this->db->get();
//                print_r($Rows);
//                dump($Query->result_array());
                if ($Query->num_rows() > 0) {
                    $JoinedContestsUsers = $Query->result_array();
                    $unique = 0;
                    $TeamCount = array(4, 5, 6);
                    foreach ($JoinedContestsUsers as $Value) {
                        //echo $Value['UserTeamID'];exit;
                        $this->db->select("SUT.UserTeamID, SUT.UserID,(Select CONCAT('[',GROUP_CONCAT(distinct CONCAT('{\"PlayerID\":\"',UTP.PlayerID,'\",\"PlayerPosition\":\"',UTP.PlayerPosition,'\",\"TeamID\":\"',ST.TeamID,'\"}')),']') FROM sports_users_team_players UTP,sports_team_players ST WHERE ST.PlayerID=UTP.PlayerID AND UTP.UserTeamID = SUT.UserTeamID AND ST.MatchID= '" . $Rows['MatchID'] . "') as Players");
                        $this->db->from("sports_users_teams SUT");
                        $this->db->where('SUT.UserTeamID', $Value['UserTeamID']);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $TeamPlayers = $Query->row_array();
                            $Players = json_decode($TeamPlayers['Players'], true);
                            //dump($Players);
                            $PlayerPoisitions = array_count_values(array_column($Players, 'PlayerPosition'));
                            $PlayerTeams = array_count_values(array_column($Players, 'TeamID'));
                            $Flag = FALSE;
                            if ($PlayerPoisitions['Captain'] != 1 && $PlayerPoisitions['ViceCaptain'] !== 1) {
                                $Flag = TRUE;
                            }
                            if (!$Flag) {
                                foreach ($PlayerTeams as $t) {
                                    if ($t >= 8) {
                                        $Flag = TRUE;
                                    }
                                }
                            }
                            if ($Flag) {
                                $where = '';
                                if ($Rows['SeriesID']) {
                                    $where = $where . " STP. SeriesID = " . $Rows['SeriesID'];
                                }

                                /* get match players */
                                $this->db->select('P.PlayerGUID,TP.PlayerID,TP.TeamID,TP.PlayerRole,FORMAT(TP.PlayerSalary, 1) as PlayerSalary, (SELECT SUM(`TotalPoints`) FROM `sports_team_players` as STP WHERE ' . $where . ' AND TP.PlayerID = STP.PlayerID) as TotalPointsMatch');
                                $this->db->from('sports_team_players TP,sports_players P');
                                $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
                                $this->db->where("TP.MatchID", $Rows['MatchID']);
                                $this->db->where("TP.PlayerSalary >", 0);
                                $this->db->where("TP.IsActive", "Yes");
                                $this->db->order_by('TP.PlayerSalary', 'DESC');
                                $Query = $this->db->get();
                                $AllPlayers = $Query->result_array();
                                $AllPlayers = phparraysort($AllPlayers, array('TotalPointsMatch', 'PlayerSalary'));

                                $ABC = rand(0, 2);
                                if ($unique % 2 == 0) {
                                    $localteamIDS = $MatchUsers['TeamIDLocal'];
                                    $visitorteamIDS = $MatchUsers['TeamIDVisitor'];
                                } else {
                                    $visitorteamIDS = $MatchUsers['TeamIDLocal'];
                                    $localteamIDS = $MatchUsers['TeamIDVisitor'];
                                }
                                $this->createTeamProcessByMatchTest($AllPlayers, $localteamIDS, $visitorteamIDS, $MatchUsers['SeriesID'], $Value['UserID'], $MatchUsers['MatchID'], $TeamCount[$ABC], $Value['UserTeamID']);
                                $unique++;
                            }
                        }
                    }
                }
            }
        }
    }

    public function createTeamProcessByMatchTest($matchPlayer, $localteam_id, $visitorteam_id, $series_id, $user_id, $match_id, $TeamCount, $UserTeamID) {
        $returnArray = array();
        $playerCount = 0;
        $secondPlayerCount = 1;
        $batsman = 0;
        $wicketkeeper = 0;
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
                $playerRole = strtoupper($player['PlayerRole']);
                $creditPoints += 9;
                if ($teamId == $localteam_id) {
                    if ($wicketkeeper < 1) {
                        if ($playerRole == 'WICKETKEEPER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr1[] = $temp;
                            $wicketkeeper++;
                            $playerCount++;
                        }
                    }
                    if ($batsman < 3) {
                        if ($playerRole == 'BATSMAN') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr2[] = $temp;
                            $batsman++;
                            $playerCount++;
                        }
                    }
                    if ($bowler < 3) {
                        if ($playerRole == 'BOWLER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr3[] = $temp;
                            $bowler++;
                            $playerCount++;
                        }
                    }
                    if ($allrounder < 1) {
                        if ($playerRole == 'ALLROUNDER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PointCredits'];
                            $Arr4[] = $temp;
                            $allrounder++;
                            $playerCount++;
                        }
                    }
                }
            }

            $res1 = array_merge($Arr1, $Arr2, $Arr3, $Arr4);
        }

        foreach ($matchPlayer as $player) {

            if (count($res2) <= (10 - count($res1))) {
                $playerId = $player['PlayerID'];
                $teamId = $player['TeamID'];
                $playerRole = strtoupper($player['PlayerRole']);
                if ($teamId == $visitorteam_id) {
                    if ($wicketkeeper < 4 && ($batsman >= 3 && $bowler >= 3 && $allrounder >= 1) || $wicketkeeper == 0) {
                        if ($playerRole == 'WICKETKEEPER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr5[] = $temp1;
                            $wicketkeeper++;
                            $secondPlayerCount++;
                        }
                    }
                    if ($batsman < 5 && ($wicketkeeper >= 1 && $bowler >= 3 && $allrounder >= 1) || $batsman < 3) {
                        if ($playerRole == 'BATSMAN') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr6[] = $temp1;
                            $batsman++;
                            $secondPlayerCount++;
                        }
                    }
                    if ($bowler < 5 && ($wicketkeeper >= 1 && $batsman >= 3 && $allrounder >= 1) || $bowler < 3) {
                        if ($playerRole == 'BOWLER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr7[] = $temp1;
                            $bowler++;
                            $secondPlayerCount++;
                        }
                    }
                    if ($allrounder < 4 && ($wicketkeeper >= 1 && $batsman >= 3 && $bowler >= 3) || $allrounder < 1) {
                        if ($playerRole == 'ALLROUNDER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PointCredits'];
                            $Arr8[] = $temp1;
                            $allrounder++;
                            $secondPlayerCount++;
                        }
                    }
                }
            }

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
                    if ($PlayerRoles['WICKETKEEPER'] > 0 && $PlayerRoles['WICKETKEEPER'] < 5) {
                        if ($PlayerRoles['BATSMAN'] > 2 && $PlayerRoles['BATSMAN'] < 7) {
                            if ($PlayerRoles['BOWLER'] > 2 && $PlayerRoles['BOWLER'] < 7) {
                                if ($PlayerRoles['ALLROUNDER'] > 0 && $PlayerRoles['ALLROUNDER'] < 5) {
                                    $this->Contest_model->editUserTeam(array('UserTeamPlayers' => $playing11, 'UserTeamType' => 'Normal', 'Status' => 1, 'IsVirtual' => 'Yes'), $UserTeamID);
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
      Description: 	cashbonus expiry api.
      URL: 			/api/utilities/allCashBonusExpire/
     */

    function allCashBonusExpire_get() {
        $ConfigData = $this->db->query("SELECT ConfigTypeGUID, ConfigTypeValue, StatusID FROM `set_site_config` WHERE ConfigTypeGUID = 'CashBonusExpireTimeInDays'");
        $ConfigDatas = $ConfigData->result_array();
        if (!empty($ConfigData)) {
            if ($ConfigDatas[0]['StatusID']) {
                if ($ConfigDatas[0]['ConfigTypeValue'] > 0 && is_numeric($ConfigDatas[0]['ConfigTypeValue'])) {
                    $prevDate = date('Y-m-d', strtotime('-' . $ConfigDatas[0]['ConfigTypeValue'] . ' day', strtotime(date('Y-m-d'))));
                    $wallets = $this->db->query("SELECT UserID,WalletAmount,CashBonus,WinningAmount,Email,FirstName FROM `tbl_users` WHERE tbl_users.CashBonus > 0 And UserTypeID != 3");
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

                                                    $WalletData = array(
                                                        "Amount" => $actaulCashBonusExpireAmount,
                                                        "CashBonus" => $actaulCashBonusExpireAmount,
                                                        "TransactionType" => 'Dr',
                                                        "Narration" => 'Cash Bonus Expire',
                                                        "EntryDate" => date("Y-m-d H:i:s")
                                                    );

                                                    $this->Users_model->addToWallet($WalletData, $value['UserID'], 5);

                                                    $NotificationTitle = "Cash Bonus Expire";
                                                    $NotificationMessage = "Your cash bonus $actaulCashBonusExpireAmount has been expired";

                                                    $this->Notification_model->addNotification('bonus', $NotificationTitle, $value['UserID'], $value['UserID'], '', $NotificationMessage);
                                                    /* $SendMail = send_mail(array(
                                                      'emailTo' => $value['Email'],
                                                      'template_id' => 'd-05b5dc93b6344115a15e808b7d648a3b',
                                                      'Subject' => SITE_NAME . $NotificationTitle,
                                                      "Name" => $value['FirstName'],
                                                      'Message' => "We noticed that you have a Cash Bonus balance in your FSL11 account which was credited 30 days ago. This unused Cash Bonus in your account will be forfeited on " . date('Y-m-d') . '.'
                                                      ));
                                                     */
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
    }

    /*
      Description: 	cashbonus expiry api.
      URL: 			/api/utilities/allCashBonusExpireSendNotification/
     */

    function allCashBonusExpireSendNotification_get() {
        $ConfigData = $this->db->query("SELECT ConfigTypeGUID, ConfigTypeValue, StatusID FROM `set_site_config` WHERE ConfigTypeGUID = 'CashBonusExpireTimeInDays'");
        $ConfigDatas = $ConfigData->result_array();
        if (!empty($ConfigData)) {
            if ($ConfigDatas[0]['StatusID']) {
                if ($ConfigDatas[0]['ConfigTypeValue'] > 0 && is_numeric($ConfigDatas[0]['ConfigTypeValue'])) {
                    $prevDate = date('Y-m-d', strtotime('-' . $ConfigDatas[0]['ConfigTypeValue'] - 10 . ' day', strtotime(date('Y-m-d'))));
                    $NextDates = date('Y-m-d', strtotime('+' . 10 . ' day', strtotime(date('Y-m-d'))));

                    $wallets = $this->db->query("SELECT UserID,WalletAmount,CashBonus,WinningAmount,Email,FirstName FROM `tbl_users` WHERE tbl_users.CashBonus > 0 And UserTypeID != 3");
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
                                                    $NotificationTitle = "Your cash bonus is about to expire!";
                                                    //$NotificationMessage = "We noticed that you have a Cash Bonus balance in your FSL11 account which was credited " . $ConfigDatas[0]['ConfigTypeValue'] . " days ago. "
                                                    // . "This unused Cash Bonus in your account will be forfeited on $NextDates.";
                                                    $NotificationMessage = "₹$actaulCashBonusExpireAmount cash bonus which was credited in your account " . $ConfigDatas[0]['ConfigTypeValue'] . " days ago, remaining Cash Bonus in your account will be forfeited on $NextDates.";
                                                    sendPushMessage($value['UserID'], $NotificationTitle, $NotificationMessage);
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

    public function wrongScoreMatchWinningRefund_get() {

        $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,E.StatusID,C.IsWinningDistributed,C.IsWinningDistributeAmount,C.ContestTransferred');
        $this->db->from('sports_contest C,tbl_entity E');
        $this->db->where("E.EntityID", "C.ContestID", FALSE);
        $this->db->where("C.LeagueType", "Dfs");
        $this->db->where("C.ContestTransferred", "Yes");
        $this->db->where("E.StatusID", 5);
        $this->db->where("C.MatchID", 1111);
        $this->db->where("C.ContestID", 1111);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Contests = $Query->result_array();
            exit;
            //dump($Contests);
            foreach ($Contests as $Value) {
                $Query = $this->db->query("SELECT * FROM `tbl_users_wallet` WHERE "
                        . "`Narration` = 'Join Contest Winning' AND `EntityID` = '" . $Value['ContestID'] . "' "
                        . "ORDER BY `WalletID` DESC");
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
        }
    }

    function wrongScoreMatchWinningAmountDistribute_get() {
        ini_set('max_execution_time', 300);
        /* Get Joined Contest Users */
        $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,E.StatusID');
        $this->db->from('sports_contest C,tbl_entity E');
        $this->db->where("E.EntityID", "C.ContestID", FALSE);
        $this->db->where("C.IsWinningDistributed", "Yes");
        //$this->db->where("C.IsWinningDistributeAmount", "No");
        $this->db->where("C.LeagueType", "Dfs");
        $this->db->where("C.ContestTransferred", "Yes");
        $this->db->where("E.StatusID", 5);
        $this->db->where("C.MatchID", 1111);
        $this->db->where("C.ContestID", 1);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Contests = $Query->result_array();
            exit;
            //dump($Contests);
            foreach ($Contests as $Value) {
                /* Get Joined Contest Users */
                $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.UserRank,JC.UserWinningAmount,JC.IsWinningDistributeAmount,JC.SmartPool,JC.SmartPoolWinning');
                $this->db->from('sports_contest_join JC, tbl_users U');
                $this->db->where("JC.UserID", "U.UserID", FALSE);
                //$this->db->where("JC.IsWinningDistributeAmount", "No");
                $this->db->where("JC.ContestID", $Value['ContestID']);
                $this->db->where("U.UserTypeID !=", 3);
                $this->db->where("JC.UserWinningAmount >", 0);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $JoinedContestsUsers = $Query->result_array();
                    //dump($JoinedContestsUsers);
                    if (!empty($JoinedContestsUsers)) {
                        foreach ($JoinedContestsUsers as $WinnerValue) {

                            $this->db->trans_start();

                            if ($WinnerValue['UserWinningAmount'] > 0) {
                                /** update user wallet * */
                                $WalletData = array(
                                    "Amount" => $WinnerValue['UserWinningAmount'],
                                    "WinningAmount" => $WinnerValue['UserWinningAmount'],
                                    "EntityID" => $WinnerValue['ContestID'],
                                    "UserTeamID" => $WinnerValue['UserTeamID'],
                                    "TransactionType" => 'Cr',
                                    "Narration" => 'Join Contest Winning',
                                    "EntryDate" => date("Y-m-d H:i:s")
                                );
                                $this->Users_model->addToWallet($WalletData, $WinnerValue['UserID'], 5);
                                $this->Notification_model->addNotification('winnings', 'Contest Winner', $WinnerValue['UserID'], $WinnerValue['UserID'], $WinnerValue['ContestID'], 'Congratulations you have won ' . DEFAULT_CURRENCY . $WinnerValue['UserWinningAmount'] . '');
                            }

                            /** user join contest winning status update * */
                            $this->db->where('UserID', $WinnerValue['UserID']);
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('IsWinningDistributeAmount' => "Yes", 'ModifiedDate' => date('Y-m-d H:i:s')));

                            $this->db->trans_complete();
                            if ($this->db->trans_status() === false) {
                                return false;
                            }
                        }
                    } else {
                        /* Update Contest Winning Status Yes */
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                    }
                } else {
                    /* Update Contest Winning Status Yes */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                }
            }
        }
    }

    public function CancelUpcomingContest_get() {
        $Query = $this->db->query("SELECT ContestID FROM `sports_contest` `C` JOIN `tbl_entity` `E` ON `C`.`ContestID` = `E`.`EntityID` WHERE `E`.`StatusID` = 1 AND `C`.`PreContestID` is NOT NULL ORDER BY `C`.`PreContestID` DESC")->result_array();

        if (!empty($Query)) {
            foreach ($Query as $Record) {
                $Query1 = $this->db->query("SELECT ContestID FROM `sports_contest_join` `JC` WHERE `JC`.`ContestID` =" . $Record['ContestID']);
                if ($Query1->num_rows() > 0) {
                    
                } else {
                    $this->Contest_model->cancelContest('', '', $Record['ContestID']);
                }
            }
        }
    }

    function dropCollection_get() {
        $Query = $this->db->query("SELECT ContestID FROM `sports_contest` `C` JOIN `tbl_entity` `E` ON `C`.`ContestID` = `E`.`EntityID` WHERE `E`.`StatusID` = 5 ORDER BY `C`.`ContestID` DESC")->result_array();
        if (!empty($Query)) {
            foreach ($Query as $Record) {
                $ContestCollection = $this->fantasydb->{'Contest_' . $Record['ContestID']};
                $result = $ContestCollection->drop();
            }
        }

        $Query = $this->db->query("SELECT ContestID FROM `football_sports_contest` `C` JOIN `tbl_entity` `E` ON `C`.`ContestID` = `E`.`EntityID` WHERE `E`.`StatusID` = 5 ORDER BY `C`.`ContestID` DESC")->result_array();
        if (!empty($Query)) {
            foreach ($Query as $Record) {
                $ContestCollection = $this->fantasydb->{'Contest_' . $Record['ContestID']};
                $result = $ContestCollection->drop();
            }
        }
    }

    public function check_post() {
        $checkSum = "";
        $paramList = array();
        $paramList['request'] = array('requestType' => 'merchanttxnid',
            'txnType' => 'SALES_TO_USER_CREDIT',
            'txnId' => 'Order2e1d072efa',
            'mId' => PAYTM_MERCHANT_mId);
        $paramList['operationType'] = 'CHECK_TXN_STATUS';
        $paramList['platformName'] = 'PayTM';

        $data_string = json_encode($paramList);
        $checkSum = $this->Users_model->getChecksumFromString($data_string, PAYTM_MERCHANT_KEY_WITHDRAWAL);

        $ch = curl_init(); // initiate curl
        // $url = "https://trust.paytm.in/wallet-web/checkStatus";
        $url = "https://trust.paytm.in/wallet-web/txnStatusList";
        // $url = "https://trust-uat.paytm.in/wallet-web/txnStatusList";

        $headers = array('Content-Type:application/json', 'mId:' . PAYTM_MERCHANT_GUID, 'checksumhash:' . $checkSum);
        $ch = curl_init();  // initiate curl
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_POST, 1);  // tell curl you want to post something
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string); // define what you want to post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the output in string format
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch); // execute
        $info = curl_getinfo($ch);

        print_r($output);
        print_r($info);
        //echo $output;
    }

    function getCSV_get() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $file = fopen('users-list-filter-1-02-12-2019.csv', 'r');
        $print_array = array();
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            $Email = "";
            $Phone = "";
            if (strlen($column[2]) == 10) {
                $Query1 = $this->db->query("SELECT UserID FROM `tbl_users`  WHERE PhoneNumber = '" . $column[2] . "'");
                if ($Query1->num_rows() == 0) {
                    $Phone = $column[2];
                }
            }
            $Query1 = $this->db->query("SELECT UserID FROM `tbl_users` WHERE Email ='" . $column[0] . "'");
            if ($Query1->num_rows() == 0) {
                $Email = $column[0];
            }
            if (!empty($Email) || !empty($Phone)) {
                $print_array[] = array(
                    'Email' => $Email,
                    'Name' => $column[1],
                    'Phone' => $Phone
                );
            }
        }
        //dump($print_array);
        $fp = fopen('users_groups-18-Oct-19new.csv', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=users_groups-18-Oct-19new.csv');
        fputcsv($fp, array('Email', 'Name', 'Phone'));

        foreach ($print_array as $row) {
            fputcsv($fp, $row);
        }
        //echo BASE_URL . 'users_groups-18-Oct-19new.csv';
    }

    /*
      Description: 	Cron jobs to get joined player points.
      URL: 			/api/utilities/getJoinedContestPlayerPoints
     */

    public function getJoinedContestTeamPointsOLD_get() {
        $CronID = $this->Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
        $this->Sports_model->getJoinedContestTeamPointsOLD($CronID);
        $this->Utility_model->updateCronLogs($CronID);
    }

    
}
