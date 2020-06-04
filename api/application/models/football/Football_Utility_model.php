<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Football_Utility_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('football/Football_Sports_model', 'Football_Sports_model');
        $this->load->model('football/Football_Contest_model', 'Football_Contest_model');
        mongoDBConnection();
    }

    /*
      Description: 	Use to get country list
     */

    function getCountries() {
        /* Define section  */
        $Return = array('Data' => array('Records' => array()));
        /* Define variables - ends */
        $Query = $this->db->query("SELECT CountryCode,CountryName,phonecode   FROM `set_location_country` ORDER BY CountryName ASC
		");
        //echo $this->db->last_query();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: Use to manage cron api logs
     */

    function insertCronAPILogs($CronID, $Response) {
        if (!CRON_SAVE_LOG) {
            return true;
        }
        $InsertData = array(
            'CronID' => $CronID,
            'Response' => @json_encode($Response, JSON_UNESCAPED_UNICODE)
        );
        $this->db->insert('log_cron_api', $InsertData);
    }

    /*
      Description: 	Use to get banner list
     */

    function bannerList($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {

        $MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaThumbURL, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaURL,	M.MediaCaption', array("SectionID" => 'Banner'), TRUE);

        if ($MediaData) {
            $Return = ($MediaData ? $MediaData : new StdClass());
            return $Return;
        }

        return false;
    }

    /*
      Description: 	Use to add ReferralCode
     */

    function generateReferralCode($UserID = '') {
        $ReferralCode = random_string('alnum', 6);
        $this->db->insert('tbl_referral_codes', array_filter(array('UserID' => $UserID, 'ReferralCode' => $ReferralCode)));
        return $ReferralCode;
    }

    /*
      Description: Use to manage cron logs
     */

    function insertCronLogs($CronType) {
        $InsertData = array(
            'CronType' => $CronType,
            'EntryDate' => date('Y-m-d H:i:s')
        );
        $this->db->insert('log_cron', $InsertData);
        return $this->db->insert_id();
    }

    /*
      Description: Use to manage cron logs
     */

    function updateCronLogs($CronID) {
        $UpdateData = array(
            'CompletionDate' => date('Y-m-d H:i:s'),
            'CronStatus' => 'Completed'
        );
        $this->db->where('CronID', $CronID);
        $this->db->limit(1);
        $this->db->update('log_cron', $UpdateData);
    }

    /*
      Description: Use to get site config.
     */

    function getConfigs($Where = array()) {
        $this->db->select('ConfigTypeGUID,ConfigTypeDescprition,ConfigTypeValue, (CASE WHEN StatusID = 2 THEN "Active" WHEN StatusID = 6 THEN "Inactive" ELSE "Unknown" END) AS Status');
        $this->db->from('set_site_config');
        if (!empty($Where['ConfigTypeGUID'])) {
            $this->db->where("ConfigTypeGUID", $Where['ConfigTypeGUID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("StatusID", $Where['StatusID']);
        }
        $this->db->order_by("Sort", 'ASC');
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();
        // $this->db->cache_on();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: Use to update config.
     */

    function updateConfig($ConfigTypeGUID, $Input = array()) {
        if (!empty($Input)) {

            /* Update Config */
            $UpdateData = array(
                'ConfigTypeValue' => $Input['ConfigTypeValue'],
                'StatusID' => $Input['StatusID']
            );
            $this->db->where('ConfigTypeGUID', $ConfigTypeGUID);
            $this->db->limit(1);
            $this->db->update('set_site_config', $UpdateData);
            // $this->db->cache_delete('admin', 'config'); //Delete Cache
        }
    }

    /*
      Description: use to delte Notification.
     */

    function deleteNotifications() {
        /* Update Config */
        $Query = "DELETE FROM tbl_notifications WHERE `NotificationText` LIKE '%Reminder%' AND EntryDate < " . date('Y-m-d');
        $this->db->query($Query);
    }

    /*
      Description : To add banner
     */

    function addBanner($UserID, $Input = array(), $StatusID) {
        $this->db->trans_start();
        $EntityGUID = get_guid();
        /* Add to entity table and get ID. */
        $BannerID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 14, "UserID" => $UserID, "StatusID" => $StatusID));

        $this->db->trans_complete($this->SessionUserID, array_merge($this->Post), $this->StatusID);
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return array('BannerID' => $BannerID, 'BannerGUID' => $EntityGUID);
    }

    /*
      Description: Use to send OTP on mobile
     */

    function sendMobileSMS($SMSArray) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://control.msg91.com/api/sendotp.php?authkey=" . MSG91_AUTH_KEY . "&sender=" . MSG91_SENDER_ID . "&mobile=" . $SMSArray['PhoneNumber'] . "&otp=" . $SMSArray['Text'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            return true;
        }
    }

    /*
      Description: Use to send SMS on mobile
     */

    function sendSMS($SMSArray) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api.msg91.com/api/sendhttp.php?route=4&sender=FSLELE&mobiles=" . $SMSArray['PhoneNumber'] . "&authkey=" . MSG91_AUTH_KEY . "&message=" . $SMSArray['Text'] . "&country=91",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));

        $response = curl_exec($curl);
        // print_r($response);exit();

        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /*
      Description: Use to send Bulk SMS on mobile
     */

    function sendBulkSMS($SMSArray) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.msg91.com/api/v2/sendsms",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{  
                                    "sender"    : "FSLELE",
                                    "route"     : "4",
                                    "country"   : "91",
                                    "sms"       : [ 
                                                        {
                                                            "message": "' . $SMSArray['Text'] . '",
                                                            "to": [' . $SMSArray['PhoneNumber'] . ']
                                                        }
                                                    ]
                                }',
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Host: api.msg91.com",
                "authkey: 273511AObV1jwyud5cc067fd",
                "cache-control: no-cache",
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /*
      Description: Use to send emails
     */

    function sendMails($MailArray) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://control.msg91.com/api/sendmail.php?body=" . $MailArray['emailMessage'] . "&subject=" . $MailArray['emailSubject'] . "&to=" . $MailArray['emailTo'] . "&from=" . MSG91_FROM_EMAIL . "&authkey=" . MSG91_AUTH_KEY,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /*
      Description: 	Use to get state list
     */

    function getStates($where = array()) {
        /* Define section  */
        $Return = array('Data' => array('Records' => array()));
        /* Define variables - ends */

        $this->db->select('StateName,CountryCode');
        $this->db->from('set_location_state');
        if (!empty($Where['CountryCode'])) {
            $this->db->where("CountryCode", $Where['CountryCode']);
        }

        $this->db->order_by("StateName", 'ASC');

        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();

        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;

        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: 	Use to get app version details
     */

    function getAppVersionDetails() {
        $Query = $this->db->query("SELECT ConfigTypeGUID,ConfigTypeDescprition,ConfigTypeValue FROM set_site_config WHERE ConfigTypeGUID IN ('AndridAppUrl','AndroidAppVersion','IsAndroidAppUpdateMandatory')");
        if ($Query->num_rows() > 0) {
            $VersionData = array();
            foreach ($Query->result_array() as $Value) {
                $VersionData[$Value['ConfigTypeGUID']] = $Value['ConfigTypeValue'];
            }
            return $VersionData;
        }
        return FALSE;
    }

    /*
      Description: To get access token
     */

    function getAccessToken() {
        $this->load->helper('file');
        if (file_exists(FOOTBALl_SPORTS_FILE_PATH)) {
            $AccessToken = read_file(FOOTBALl_SPORTS_FILE_PATH);
        } else {
            $AccessToken = $this->generateAccessToken();
        }
        return trim(preg_replace("/\r|\n/", "", $AccessToken));
    }

    /*
      Description: To generate access token
     */

    function generateAccessToken() {

        /* For Sports Cricket Api */
        if (FOOTBALL_SPORT_API_NAME == 'CRICKETAPI') {
            $Response = json_decode($this->ExecuteCurl(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/auth/', array('access_key' => FOOTBALL_SPORTS_API_ACCESS_KEY_CRICKETAPI,
                        'secret_key' => FOOTBALL_SPORTS_API_SECRET_KEY_CRICKETAPI,
                        'app_id' => FOOTBALL_SPORTS_API_APP_ID_CRICKETAPI, 'device_id' => FOOTBALL_SPORTS_API_DEVICE_ID_CRICKETAPI)), true);
            if ($Response['status']) $AccessToken = $Response['auth']['access_token'];
        }
        if (empty($AccessToken)) {
            /* Blank Access Token */
            $InsertData = array();
            $InsertData['CronType'] = 'blankResponse';
            $InsertData['EntryDate'] = date('Y-m-d H:i:s');
            $InsertData['CronStatus'] = 'Exit';
            $InsertData['CronResponse'] = json_encode(array('Response' => $Response,
                'AccessToken' => 'Blank Access Token'));
            $this->db->insert('log_cron', $InsertData);
            exit;
        }

        /* Update Access Token */
        $this->load->helper('file');
        write_file(FOOTBALl_SPORTS_FILE_PATH, $AccessToken, 'w');
        return trim(preg_replace("/\r|\n/", "", $AccessToken));
    }

    /*
      Description: To Excecute curl request
     */

    function ExecuteCurl($Url, $Params = '') {
        $Curl = curl_init($Url);
        if (!empty($Params)) {
            curl_setopt($Curl, CURLOPT_POSTFIELDS, $Params);
        }
        curl_setopt($Curl, CURLOPT_HEADER, 0);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        $Response = curl_exec($Curl);
        curl_close($Curl);
        $Result = json_decode($Response);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $Response;
        } else {
            return $Response;
        }
    }

    function ExecuteCurlLive($Url, $Params = '') {
        $Curl = curl_init($Url);
        if (!empty($Params)) {
            curl_setopt($Curl, CURLOPT_POSTFIELDS, $Params);
        }
        curl_setopt($Curl, CURLOPT_HEADER, 0);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        $Response = curl_exec($Curl);
        curl_close($Curl);
        $Result = json_decode($Response);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $Response;
        } else {
            return $Response;
        }
    }

    /*
      Description: To fetch sports api data
     */

    function callSportsAPI($ApiUrl) {
        $Response = json_decode($this->ExecuteCurl($ApiUrl . $this->getAccessToken()), true);

        if (@$Response['status'] == 'unauthorized' || @$Response['status_code'] == 403) {
            if (@$Response['status_msg'] == 'RequestLimitExceeds') { // API Calling Limit Exceeds
                /* Request Limit Exceeds Respone */
                log_message('ERROR', "Request Limit Exceeds");
                return TRUE;
            } else {
                /* Re-generate token */
                $Response = json_decode($this->ExecuteCurl($ApiUrl . $this->generateAccessToken()), true);
            }
        } else {

            $Response = json_decode($this->ExecuteCurl($ApiUrl . $this->generateAccessToken()), true);
        }
        return $Response;
    }

    public function getAccessTokenLive() {
        $param = array(
            'access_key' => FOOTBALL_SPORTS_API_ACCESS_KEY_CRICKETAPI,
            'secret_key' => FOOTBALL_SPORTS_API_SECRET_KEY_CRICKETAPI,
            'app_id' => FOOTBALL_SPORTS_API_APP_ID_CRICKETAPI,
            'device_id' => FOOTBALL_SPORTS_API_DEVICE_ID_CRICKETAPI
        );
       
        $ch = curl_init(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/auth/');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        return $result['auth']['access_token'];
    }

    /*
      Description: To set series data (Cricket API)
     */

    function getSeriesLiveCricketApi($CronID) {
        /* Update Existing Series Status */
        $this->db->query('UPDATE football_sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesEndDate < "' . date('Y-m-d') . '"');
        $SeriesData = $this->Football_Sports_model->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2), true, 0);
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {
            $SeriesStartDate = $this->db->query('SELECT CAST(MatchStartDateTime as DATE) as MatchStartDateTime FROM football_sports_matches WHERE SeriesID = ' . $SeriesValue['SeriesID'] . ' ORDER BY MatchStartDateTime ASC LIMIT 1')->row()->MatchStartDateTime;
            $SeriesEndDate = $this->db->query('SELECT CAST(MatchStartDateTime as DATE) as MatchStartDateTime FROM football_sports_matches WHERE SeriesID = ' . $SeriesValue['SeriesID'] . ' ORDER BY MatchStartDateTime DESC LIMIT 1')->row()->MatchStartDateTime;
            $this->db->where('SeriesID', $SeriesValue['SeriesID']);
            $this->db->limit(1);
            $this->db->update('sports_series', array('SeriesStartDate' => $SeriesStartDate, 'SeriesEndDate' => $SeriesEndDate));
        }
        $Response = $this->callSportsAPI(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/recent_tournaments/?access_token=');
        $SeriesData = array();
        foreach ($Response['data']['tournaments'] as $Value) {

            /* To get All Series Data */
            $SeriesIdsData = $this->db->query('SELECT GROUP_CONCAT(SeriesIDLive) AS SeriesIDsLive FROM football_sports_series')->row()->SeriesIDsLive;
            $SeriesIDsLive = array();
            if ($SeriesIdsData) {
                $SeriesIDsLive = explode(",", $SeriesIdsData);
            }

            if (in_array($Value['key'], $SeriesIDsLive)) continue;

            /* Add series to entity table and get EntityID. */
            $SeriesGUID = get_guid();
            $SeriesData[] = array_filter(array(
                'SeriesID' => $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2)),
                'SeriesGUID' => $SeriesGUID,
                'SeriesIDLive' => $Value['key'],
                'SeriesName' => $Value['name'],
                'SeriesShortName' => $Value['short_name'],
                'SeriesStartDate' => date('Y-m-d', strtotime($Value['start_date']['gmt'])),
                'SeriesEndDate' => date('Y-m-d', strtotime($Value['end_date']['gmt']))
            ));
        }
        if (!empty($SeriesData)) {
            $this->db->insert_batch('football_sports_series', $SeriesData);
        }
    }

    /*
      Description: To get rounds data (Cricket API)
     */

    function getRoundsLiveCricketApi($CronID) {
        /* Update Existing Series Status */
        $SeriesData = $this->Football_Sports_model->getSeries('SeriesName,SeriesIDLive,SeriesID,SeriesStartDateUTC,SeriesEndDateUTC', array('StatusID' => 2), true, 0);
        if (!$SeriesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {

            $RoundIdsData = $this->db->query('SELECT RoundIDLive FROM football_sports_series_rounds WHERE RoundIDLive=' . $SeriesValue['SeriesIDLive'] . '')->row()->RoundIDLive;
            if (empty($RoundIdsData)) {
                $RoundsData = array_filter(array(
                    'SeriesID' => $SeriesValue['SeriesID'],
                    'RoundIDLive' => $SeriesValue['SeriesIDLive'],
                    'RoundName' => $SeriesValue['SeriesName'],
                    'RoundFormat ' => "Football",
                    'SeriesType' => "Tournament",
                    'RoundStartDate' => $SeriesValue['SeriesStartDateUTC'],
                    'RoundEndDate' => $SeriesValue['SeriesEndDateUTC'],
                    'AuctionDraftIsPlayed ' => "Yes",
                    'AuctionDraftStatusID' => 1,
                    'StatusID' => 2
                ));
                $this->db->insert('football_sports_series_rounds', $RoundsData);
            }


            /* $Response = $this->callSportsAPI(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $SeriesValue['SeriesIDLive'] . '/?access_token=');
              foreach ($Response['data']['tournament']['rounds'] as $Value) {

              $RoundIdsData = $this->db->query('SELECT GROUP_CONCAT(RoundIDLive) AS RoundIDLive FROM football_sports_series_rounds')->row()->RoundIDsLive;
              $RoundIDsLive = array();
              if ($RoundIdsData) {
              $RoundIDsLive = explode(",", $RoundIdsData);
              }
              if (in_array($SeriesValue['SeriesIDLive'], $RoundIDsLive))
              continue;

              $RoundsData[] = array_filter(array(
              'SeriesID' => $SeriesValue['SeriesID'],
              'RoundIDLive' => $SeriesValue['SeriesIDLive'],
              'RoundName' => $SeriesValue['SeriesName'],
              'RoundFormat ' => "Football",
              'SeriesType' => "Tournament",
              'RoundStartDate' => $SeriesValue['SeriesStartDateUTC'],
              'RoundEndDate' => $SeriesValue['SeriesEndDateUTC'],
              'AuctionDraftIsPlayed ' => "Yes",
              'AuctionDraftStatusID' => 1,
              'StatusID' => 2
              ));
              } */
        }
        /* if (!empty($RoundsData)) {
          $this->db->insert_batch('football_sports_series_rounds', $RoundsData);
          } */
    }

    /*
      Description: To set matches data (Cricket API)
     */

    function getMatchesLiveCricketApi($CronID) {
        /* error_reporting(E_ALL);
          ini_set('display_errors', 1); */
        $AccessToken = $this->getAccessTokenLive();
        /* Get series data */
        $this->db->select('S.SeriesID,SR.RoundID,S.SeriesIDLive,SeriesStartDate');
        $this->db->from('football_sports_series S,football_sports_series_rounds SR,tbl_entity E');
        $this->db->where("E.EntityID", "S.SeriesID", FALSE);
        $this->db->where("SR.SeriesID", "S.SeriesID", FALSE);
        $this->db->where("E.StatusID", 2);
        $this->db->where("SR.StatusID", 2);
        //$this->db->where("SR.RoundID", 2);
        $this->db->order_by("S.SeriesStartDate", "ASC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $SeriesData = $Query->result_array();
            /* Get Live Matches Data */
            foreach ($SeriesData as $SeriesValue) {
                /** get rounds * */
                $ResponseRounds = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $SeriesValue['SeriesIDLive'] . '/?access_token=' . $AccessToken);
                $ResponseRounds = @json_decode($ResponseRounds, TRUE);

                if (isset($ResponseRounds['data']['tournament']['rounds']) && !empty($ResponseRounds['data']['tournament']['rounds'])) {
                    foreach ($ResponseRounds['data']['tournament']['rounds'] as $Value) {
                        /** get matches live * */
                        $ResponseMatches = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $SeriesValue['SeriesIDLive'] . '/round-detail/' . $Value['key'] . '/?access_token=' . $AccessToken);
                        $ResponseMatches = @json_decode($ResponseMatches, TRUE);
                        if (!empty($ResponseMatches)) {
                            // print_r($ResponseMatches['data']['round']['matches']);exit;
                            foreach ($ResponseMatches['data']['round']['matches'] as $MatchValue) {
                                /* Managae Teams */
                                $LocalTeamData = $VisitorTeamData = array();
                                $TeamNames = $MatchValue['match']['name'];
                                $TeamName = explode(" vs ", $TeamNames);
                                $TeamShortNames = $MatchValue['match']['short_name'];
                                $TeamShortName = explode(" vs ", $TeamShortNames);

                                if (isset($TeamName[0]) && !empty(trim($TeamName[0]))) {
                                    if ($TeamName[0] == "TBC") continue;
                                }

                                if (isset($TeamName[1]) && !empty(trim($TeamName[1]))) {
                                    if ($TeamName[1] == "TBC") continue;
                                }

                                /* To check if local team is already exist */
                                $Query = $this->db->query('SELECT TeamID FROM football_sports_teams WHERE TeamIDLive = ' . $MatchValue['match']['home'] . ' LIMIT 1');
                                $TeamIDLocal = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                                if (!$TeamIDLocal) {

                                    /* $TeamData = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $SeriesValue['SeriesIDLive'] . '/team/' . $MatchValue['match']['home'] . '/?access_token=' . $AccessToken);
                                      $TeamData = @json_decode($TeamData, TRUE);
                                      $TeamName = (string) $TeamData['data']['team']['name'];
                                      $TeamNameShort = (string) $TeamData['data']['team']['code']; */
                                    /* Add team to entity table and get EntityID. */

                                    $TeamGUID = get_guid();
                                    $TeamIDLocal = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                                    $LocalTeamData[] = array(
                                        'TeamID' => $TeamIDLocal,
                                        'TeamGUID' => $TeamGUID,
                                        'TeamIDLive' => $MatchValue['match']['home'],
                                        'TeamName' => (isset($TeamName[0])) ? trim($TeamName[0]) : "",
                                        'TeamNameShort' => (isset($TeamShortName[0])) ? trim($TeamShortName[0]) : ""
                                    );
                                }
                                /* To check if visitor team is already exist */
                                $Query = $this->db->query('SELECT TeamID FROM football_sports_teams WHERE TeamIDLive = ' . $MatchValue['match']['away'] . ' LIMIT 1');
                                $TeamIDVisitor = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                                if (!$TeamIDVisitor) {

                                    /* $TeamData = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $SeriesValue['SeriesIDLive'] . '/team/' . $MatchValue['match']['away'] . '/?access_token=' . $AccessToken);
                                      $TeamData = @json_decode($TeamData, TRUE);
                                      $VisitorTeamName = (string) $TeamData['data']['team']['name'];
                                      $VisitorTeamNameShort = (string) $TeamData['data']['team']['code']; */

                                    /* Add team to entity table and get EntityID. */
                                    $TeamGUID = get_guid();
                                    $TeamIDVisitor = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                                    $VisitorTeamData[] = array(
                                        'TeamID' => $TeamIDVisitor,
                                        'TeamGUID' => $TeamGUID,
                                        'TeamIDLive' => $MatchValue['match']['away'],
                                        'TeamName' => (isset($TeamName[1])) ? trim($TeamName[1]) : "",
                                        'TeamNameShort' => (isset($TeamShortName[1])) ? trim($TeamShortName[1]) : ""
                                    );
                                }
                                $TeamsData = array_merge($VisitorTeamData, $LocalTeamData);
                                if (!empty($TeamsData)) {
                                    $this->db->insert_batch('football_sports_teams', $TeamsData);
                                }
                                /* To check if match is already exist */
                                $Query = $this->db->query('SELECT M.MatchID,E.StatusID FROM football_sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = ' . $MatchValue['match']['key'] . ' LIMIT 1');
                                $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                                if (!$MatchID) {
                                    if (strtotime(date('Y-m-d H:i:s')) >= strtotime(date('Y-m-d H:i:s', strtotime($MatchValue['match']['start_date']['gmt'])))) {
                                        continue;
                                    }
                                    /* Add matches to entity table and get EntityID. */
                                    $MatchGUID = get_guid();
                                    $MatchesAPIData = array(
                                        'MatchID' => $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => 1)),
                                        'MatchGUID' => $MatchGUID,
                                        'MatchIDLive' => $MatchValue['match']['key'],
                                        'SeriesID' => $SeriesValue['SeriesID'],
                                        'MatchLocation' => $MatchValue['match']['stadium']['city'],
                                        'RoundID' => $SeriesValue['RoundID'],
                                        'MatchNo' => 1,
                                        'MatchTypeID' => 1,
                                        'TeamIDLocal' => $TeamIDLocal,
                                        'TeamIDVisitor' => $TeamIDVisitor,
                                        'MatchStartDateTime' => date('Y-m-d H:i', strtotime($MatchValue['match']['start_date']['gmt']))
                                    );
                                    //print_r($MatchesAPIData);exit;
                                    $this->db->insert('football_sports_matches', $MatchesAPIData);
                                } else {

                                    if ($Query->row()->StatusID != 1) continue; // Pending Match
                                        /* Update Match Data */
                                    $MatchesAPIData = array(
                                        'MatchLocation' => $MatchValue['match']['stadium']['city'],
                                        'TeamIDLocal' => $TeamIDLocal,
                                        'TeamIDVisitor' => $TeamIDVisitor,
                                        'RoundID' => $SeriesValue['RoundID'],
                                        'MatchStartDateTime' => date('Y-m-d H:i', strtotime($MatchValue['match']['start_date']['gmt'])),
                                        'LastUpdatedOn' => date('Y-m-d H:i:s')
                                    );
                                    $this->db->where('MatchID', $MatchID);
                                    $this->db->limit(1);
                                    $this->db->update('football_sports_matches', $MatchesAPIData);
                                }
                            }
                        }
                    }
                }
            }
        }else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
    }

    /*
      Description: To set players data (Cricket API)
     */

    function getPlayersLiveCricketApi($CronID) {

        //$AccessToken = $this->getAccessTokenLive();
        //$MatchesData = $this->Football_Sports_model->getMatches('MatchStartDateTime,MatchIDLive,MatchID,SeriesIDLive,SeriesID,RoundNo,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('StatusID' => array(1), 'CronFilter' => 'OneDayDiff'), true, 1, 50);
        $MatchesData = $this->Football_Sports_model->getMatches('MatchStartDateTime,MatchIDLive,MatchID,SeriesIDLive,SeriesID,RoundNo,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('StatusID' => array(1)), true, 1, 75);
        //$MatchesData = $this->Football_Sports_model->getMatches('MatchStartDateTime,MatchIDLive,MatchID,SeriesIDLive,SeriesID,RoundNo,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('MatchID' => 104052), true, 1, 50);
        if (!$MatchesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
        $AccessToken = $this->getAccessTokenLive();
        $PlayerRolesArr = array('goalkeeper' => 'Goalkeeper', 'defender' => 'Defender',
            'midfielder' => 'Midfielder', 'striker' => 'Striker');
        foreach ($MatchesData['Data']['Records'] as $Value) {
            $MatchID = $Value['MatchID'];
            $SeriesID = $Value['SeriesID'];

            $TeamsArr = array($Value['TeamIDLiveLocal'] => $Value['TeamIDLiveLocal'],
                $Value['TeamIDLiveVisitor'] => $Value['TeamIDLiveVisitor']);

            foreach ($TeamsArr as $TeamKey => $TeamValue) {
                //$Response = $this->callSportsAPI(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $Value['SeriesIDLive'] . '/team/' . $TeamValue . '/?access_token=');
                $Response = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $Value['SeriesIDLive'] . '/team/' . $TeamValue . '/?access_token=' . $AccessToken);
                $Response = @json_decode($Response, TRUE);
                //$this->Common_model->insertCronAPILogs($CronID, $Response);
                if (empty($Response['data']['team']['name'])) continue;
                $this->db->trans_start();

                $Query = $this->db->query('SELECT TeamID FROM football_sports_teams WHERE TeamIDLive = "' . $TeamKey . '" LIMIT 1');
                $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamID) {

                    $TeamGUID = get_guid();
                    $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $TeamData = array_filter(array(
                        'TeamID' => $TeamID,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $TeamKey,
                        'TeamName' => $Response['data']['team']['name'],
                        'TeamNameShort' => strtoupper($Response['data']['team']['code'])
                    ));

                    $this->db->insert('football_sports_teams', $TeamData);
                }

                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }
                $TeamPlayersData = array();
                foreach ($Response['data']['team']['players'] as $PlayerIDLive) {

                    $this->db->trans_start();

                    $Query = $this->db->query('SELECT PlayerID FROM football_sports_players WHERE PlayerIDLive = "' . $PlayerIDLive['key'] . '" LIMIT 1');
                    $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                    if (!$PlayerID) {

                        $PlayerGUID = get_guid();
                        $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                        $PlayersAPIData = array(
                            'PlayerID' => $PlayerID,
                            'PlayerGUID' => $PlayerGUID,
                            'PlayerIDLive' => $PlayerIDLive['key'],
                            'PlayerName' => $PlayerIDLive['name']
                        );
                        $this->db->insert('football_sports_players', $PlayersAPIData);
                    }
                    $Query = $this->db->query('SELECT MatchID FROM football_sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND SeriesID = ' . $SeriesID . ' AND TeamID = ' . $TeamID . ' AND MatchID =' . $Value['MatchID'] . ' LIMIT 1');
                    $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                    if (!$MatchID) {
                        $TeamPlayersData[] = array(
                            'SeriesID' => $SeriesID,
                            'MatchID' => $Value['MatchID'],
                            'TeamID' => $TeamID,
                            'PlayerID' => $PlayerID,
                            'IsPlaying' => "No",
                            'PlayerRole' => $PlayerRolesArr[strtolower($PlayerIDLive['role'])]
                        );
                    } else {
                        
                    }

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === false) {
                        return false;
                    }
                }
                if (!empty($TeamPlayersData)) {
                    $this->db->insert_batch('football_sports_team_players', $TeamPlayersData);
                }
            }

            $this->getPlayersSalaryLiveCricketApi(null, $Value['MatchID']);

            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('football_sports_matches', array('LastUpdatedOn' => date('Y-m-d H:i:s')));
        }
    }

    /*
      Description: To set players salary data (Cricket API)
     */

    function getPlayersSalaryLiveCricketApi($CronID, $MatchID = "") {

        if (!empty($MatchID)) {
            $MatchesData = $this->Football_Sports_model->getMatches('MatchStartDateTime,MatchIDLive,MatchID,SeriesIDLive,SeriesID,RoundNo,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('MatchID' => $MatchID), true, 1, 50);
        } else {
            $MatchesData = $this->Football_Sports_model->getMatches('MatchStartDateTime,MatchIDLive,MatchID,SeriesIDLive,SeriesID,RoundNo,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('StatusID' => array(1)), true, 1, 50);
        }
        //$MatchesData = $this->Football_Sports_model->getMatches('MatchStartDateTime,MatchIDLive,MatchID,SeriesIDLive,SeriesID,RoundNo,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('CronFilter' => 'OneDayDiff'), true, 1, 50);
        if (!$MatchesData) {
            return true;
        }
        $AccessToken = $this->getAccessTokenLive();
        $PlayerRolesArr = array('goalkeeper' => 'Goalkeeper', 'defender' => 'Defender',
            'midfielder' => 'Midfielder', 'striker' => 'Striker');
        foreach ($MatchesData['Data']['Records'] as $Value) {
            $MatchID = $Value['MatchID'];
            $SeriesID = $Value['SeriesID'];

            /** get players salary * */
            $Response = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/fantasy-match-credits/' . $Value['MatchIDLive'] . '/?access_token=' . $AccessToken . "&model=RZ-C-A100");
            $Response = @json_decode(gzdecode($Response), true);
            if (empty($Response['data']['fantasy'])) continue;

            /** get players matches * */
            $PlayerData = $this->Football_Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);
            if (empty($PlayerData['Data']['Records'])) continue;

            $PlayersAllKey = array_column($PlayerData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
            foreach ($Response['data']['fantasy']['credits'] as $Rows) {
                /** update player salary * */
                $this->db->where('MatchID', $Value['MatchID']);
                $this->db->where('PlayerID', $PlayersAllKey[$Rows['player_key']]);
                $this->db->limit(1);
                $this->db->update('football_sports_team_players', array('PlayerSalary' => $Rows['credits']));
            }
        }
    }

    function getPlayersLiveCricketApiOLD($CronID) {

        $AccessToken = $this->getAccessTokenLive();
        /* Get matches data */
        $MatchesData = $this->Football_Sports_model->getMatches('MatchStartDateTime,MatchIDLive,MatchID,SeriesIDLive,SeriesID,RoundNo,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('StatusID' => array(1), 'CronFilter' => 'OneDayDiff'), true, 1, 15);
        //$MatchesData = $this->Football_Sports_model->getMatches('MatchStartDateTime,MatchIDLive,MatchID,SeriesIDLive,SeriesID,RoundNo,RoundID,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff', array('MatchIDLive' => "1139126163915018251", 'CronFilter' => 'OneDayDiff'), true, 1, 50);
        if (!$MatchesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }

        $PlayerRolesArr = array('goalkeeper' => 'Goalkeeper', 'defender' => 'Defender',
            'midfielder' => 'Midfielder', 'striker' => 'Striker');
        /* Get Series Data */
        $SeriesData = $this->Football_Sports_model->getSeries('SeriesIDLive,SeriesID', array(), TRUE, 0);

        $SeriesIds = array_column($SeriesData['Data']['Records'], 'SeriesID', 'SeriesIDLive');

        /* Get Team Data */
        $TeamsData = $this->Football_Sports_model->getTeams('TeamIDLive,TeamID', array(), TRUE, 0);
        $TeamIds = array_column($TeamsData['Data']['Records'], 'TeamID', 'TeamIDLive');

        foreach ($MatchesData['Data']['Records'] as $Value) {
            $MatchID = $Value['MatchID'];
            $SeriesID = $Value['SeriesID'];

            /* Call Match API */
            //echo FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/match/' . $Value['MatchIDLive'] . '/?access_token=' . $AccessToken;
            $Response = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/match/' . $Value['MatchIDLive'] . '/?access_token=' . $AccessToken);
            $Response = @json_decode($Response, TRUE);

//            print_r($Response);
//            exit;
            /* Get Match Players */
            $MatchPlayers = $this->Football_Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID,SeriesID,TeamID,PlayerRole', array('MatchID' => $Value['MatchID'], 'IsRemoved' => 'No'), true, 0);
            $DBPlayers = $APIPlayers = array();
            if ($MatchPlayers) {
                $DBPlayers = array_column($MatchPlayers['Data']['Records'], 'PlayerIDLive', 'PlayerID');
            }
            $APIPlayers = array_keys($Response['data']['players']);
            $DifferentPlayers = array_diff($DBPlayers, $APIPlayers);
            if (!empty($DifferentPlayers)) {
                foreach ($DifferentPlayers as $PlayerID => $Player) {
                    $this->db->where(array('PlayerID' => $PlayerID, 'MatchID' => $Value['MatchID'],
                        'IsRemoved' => 'No'));
                    $this->db->limit(1);
                    $this->db->update('football_sports_team_players', array('IsRemoved' => 'Yes'));
                }
            }

            /* Team Wise Players */
            $HomeTeamPlayers = $Response['data']['teams'][$Response['data']['match']['home']]['players'];
            $AwayTeamPlayers = $Response['data']['teams'][$Response['data']['match']['away']]['players'];
            foreach ($Response['data']['players'] as $PlayerIDLive) {
                $this->db->trans_start();

                /* To check if player is already exist */
                $Query = $this->db->query('SELECT PlayerID FROM football_sports_players WHERE PlayerIDLive = "' . $PlayerIDLive['key'] . '" LIMIT 1');
                $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                if (!$PlayerID) {

                    /* Add players to entity table and get EntityID. */
                    $PlayerGUID = get_guid();
                    $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                    $PlayersAPIData = array(
                        'PlayerID' => $PlayerID,
                        'PlayerGUID' => $PlayerGUID,
                        'PlayerIDLive' => $PlayerIDLive['key'],
                        'PlayerName' => $PlayerIDLive['name']
                    );
                    $this->db->insert('football_sports_players', $PlayersAPIData);
                }

                $Query = $this->db->query('SELECT SeriesID,TeamID,PlayerRole FROM football_sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND MatchID =' . $Value['MatchID'] . ' AND IsRemoved = "No" LIMIT 1');
                $IsPlayer = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                $NewTeamID = (in_array($PlayerID, $HomeTeamPlayers)) ? $TeamIds[$Response['data']['match']['home']] : $TeamIds[$Response['data']['match']['away']];
                $NewSeriesID = $SeriesIds[$Response['data']['match']['tournament']['key']];
                if (!$IsPlayer) {
                    $TeamPlayersData = array(
                        'SeriesID' => $SeriesID,
                        'MatchID' => $Value['MatchID'],
                        'TeamID' => $NewTeamID,
                        'PlayerID' => $PlayerID,
                        'RoundID' => $Value['RoundID'],
                        'IsPlaying' => "No",
                        'IsRemoved' => "No",
                        'PlayerRole' => $PlayerRolesArr[strtolower($PlayerIDLive['role'])]
                    );
                    $this->db->insert('football_sports_team_players', $TeamPlayersData);
                } else if ($Query->row()->TeamID != $NewTeamID || $Query->row()->SeriesID != $NewSeriesID || $Query->row()->PlayerRole != $PlayerRolesArr[strtolower($PlayerIDLive['role'])]) {

                    /* Update IsRemoved Status */
                    $this->db->where(array('PlayerID' => $PlayerID, 'MatchID' => $Value['MatchID'],
                        'TeamID' => $Query->row()->TeamID, 'SeriesID' => $Query->row()->SeriesID,
                        'IsRemoved' => 'No'));
                    $this->db->limit(1);
                    $this->db->update('football_sports_team_players', array('IsRemoved' => 'Yes'));

                    $TeamPlayersData = array(
                        'SeriesID' => $NewSeriesID,
                        'MatchID' => $Value['MatchID'],
                        'TeamID' => $NewTeamID,
                        'PlayerID' => $PlayerID,
                        'RoundID' => $Value['RoundID'],
                        'IsPlaying' => "No",
                        'IsRemoved' => "No",
                        'PlayerRole' => $PlayerRolesArr[strtolower($PlayerIDLive['role'])]
                    );
                    $this->db->insert('football_sports_team_players', $TeamPlayersData);
                }

                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }
            }

            /* Update Last Updated Status */
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('football_sports_matches', array('LastUpdatedOn' => date('Y-m-d H:i:s')));
        }
    }

    /*
      Description: To set player stats (Cricket API)
     */

    function getPlayerStatsLiveCricketApi($CronID) {
        $AccessToken = $this->getAccessTokenLive();
        /* To get All Player Stats Data */
        $MatchData = $this->Football_Sports_model->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID,MatchStartDateTime', array('StatusID' => 5, 'PlayerStatsUpdate' => 'No', 'MatchCompleteDateTime' => date('Y-m-d H:i:s')), true, 0);
        //$MatchData = $this->Football_Sports_model->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID,MatchStartDateTime', array("MatchGUID" =>  'a2b9197b-1e63-d4ea-e0b3-17d2aece1da9'), true, 0);
        if (!$MatchData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($MatchData['Data']['Records'] as $Value) {
            $PlayerData = $this->Football_Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);

            if (empty($PlayerData)) continue;
            foreach ($PlayerData['Data']['Records'] as $PlayerValue) {
                /* Call Player Stats API */
                //$Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $Value['SeriesIDLive'] . '/player/' . $PlayerValue['PlayerIDLive'] . '/stats/?access_token=');
                $Response = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/tournament/' . $Value['SeriesIDLive'] . '/player/' . $PlayerValue['PlayerIDLive'] . '/stats/?access_token=' . $AccessToken);
                $Response = @json_decode($Response, TRUE);
                if ($Response['status_code'] == 200 && !empty($Response['data'])) {
                    $PlayerStats = new stdClass();
                    $PlayerStats->Defensive = (object) array(
                                'Fouls' => @$Response['data']['stats']['stats']['defensive']['fouls'],
                                'Offsides' => @$Response['data']['stats']['stats']['defensive']['offsides'],
                                'OwnGoals' => @$Response['data']['stats']['stats']['defensive']['own_goals'],
                    );
                    $PlayerStats->Offensive = (object) array(
                                'CleanSheets' => @$Response['data']['stats']['stats']['offensive']['clean_sheets'],
                                'FoulsDrawn' => @$Response['data']['stats']['stats']['offensive']['fouls_drawn'],
                                'Goals' => @$Response['data']['stats']['stats']['offensive']['goals'],
                                'PenaltyGoals' => @$Response['data']['stats']['stats']['offensive']['penalty_goals'],
                    );

                    $PlayerStats->Summary = (object) array(
                                'Assists' => @$Response['data']['stats']['stats']['summary']['assists'],
                                'Goals' => @$Response['data']['stats']['stats']['summary']['goals'],
                                'MatchesPlayed' => @$Response['data']['stats']['stats']['summary']['matches_played'],
                                'MinutesPlayed' => @$Response['data']['stats']['stats']['summary']['minutes_played'],
                                'PlayerOfTheMatch' => @$Response['data']['stats']['stats']['summary']['player_of_the_match'],
                                'RedCards' => @$Response['data']['stats']['stats']['summary']['red_cards'],
                                'YellowCards' => @$Response['data']['stats']['stats']['summary']['yellow_cards'],
                    );

                    /* Update Player Stats */
                    $PlayerStats = array(
                        'PlayerStats' => json_encode($PlayerStats),
                        'LastUpdatedOn' => date('Y-m-d H:i:s')
                    );
                    $this->db->where('PlayerID', $PlayerValue['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_players', $PlayerStats);
                }
            }
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('football_sports_matches', array('PlayerStatsUpdate' => 'Yes'));
        }
    }

    /*
      Description: To get match live score (Cricket API)
     */

    function getMatchScoreLiveCricketApi($CronID) {
        $AccessToken = $this->getAccessTokenLive();
        /* Get Live Matches Data */
        $LiveMatches = $this->Football_Sports_model->getMatches('MatchIDLive,MatchID,MatchStartDateTime,Status,SeriesID,RoundNo,RoundID', array('Filter' => 'Yesterday', 'StatusID' => array(1, 2, 10), 'OrderBy' => 'M.MatchStartDateTime',
            'Sequence' => 'ASC'), true, 1, 20);
        if (!$LiveMatches) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        
        $PlayerRolesArr = array('goalkeeper' => 'Goalkeeper', 'defender' => 'Defender',
            'midfielder' => 'Midfielder', 'striker' => 'Striker');
        $MatchStatus = array('completed' => 5, "started" => 2, "not_started" => 9);
        $GameStatus = array('completed' => 5, "started" => 2, "not_started" => 9,
            "Abandoned" => 5, "Cancelled" => 3, "No Result" => 5);
        $InningsStatus = array(1 => 'Scheduled', 2 => 'Completed', 3 => 'Live', 4 => 'Abandoned');
        foreach ($LiveMatches['Data']['Records'] as $Value) {
            if ($Value['Status'] == 'Pending' && (strtotime(date('Y-m-d H:i:s')) + 19800 >= strtotime($Value['MatchStartDateTime']))) { // +05:30
                /* Update Match Status */
                $this->db->where('EntityID', $Value['MatchID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 2));
                /* Update Game Status */
                $this->db->query("UPDATE football_sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND C.MatchID=" . $Value['MatchID'] . "");
            }
            /* $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/v1/match/' . $Value['MatchIDLive'] . '/?access_token='); */
            $Response = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/match/' . $Value['MatchIDLive'] . '/?access_token=' . $AccessToken);
            $Response = @json_decode($Response, TRUE);
            $MatchStatusLive = @$Response['data']['match']['status'];
            $MatchStatusOverView = @$Response['data']['match']['status_overview'];

            /* Get Match Review Check Point */
            $MatchReviewCheckPoint = @$Response['data']['match']['data_review_checkpoint'];

            if ($Value['Status'] == 'Running' && $MatchStatusLive != 'not_started') {
                /* Update Match Status */
                /* $this->db->where('EntityID', $Value['MatchID']);
                  $this->db->limit(1);
                  $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => ($MatchStatusLive != 'completed') ? $MatchStatus[$MatchStatusLive] : (($MatchReviewCheckPoint == 'post-match-validated') ? 5 : 10))); */
            }

            /* Get Match Players Live */
            if (empty($Response['data']['players'])) continue;
            foreach ($Response['data']['players'] as $PlayerIdLive => $Player) {
                $LivePlayersData[$PlayerIdLive] = $Player['name'];
            }
            /* Get Match Players */
            $PlayersIdsData = array();
            $PlayersData = $this->Football_Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID,IsPlaying', array('MatchID' => $Value['MatchID']), true, 0);
            if ($PlayersData) {
                $PlayersIdsData = array_column($PlayersData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
            }
            /* Update Playing XI Status */
            if ($MatchStatusLive == 'not_started') {
                foreach ($Response['data']['players'] as $PlayerIdLive => $PlayerValue) {
                    if ($PlayerValue['in_playing_squad'] || $PlayerValue['in_bench_squad']) {
                        /* Update Playing XI Status */
                        $this->db->where('MatchID', $Value['MatchID']);
                        $this->db->where('PlayerID', $PlayersIdsData[$PlayerIdLive]);
                        $this->db->limit(1);
                        $this->db->update('football_sports_team_players', array('IsPlaying' => "Yes"));
                    }
                }
            }
            if (!in_array($MatchStatusLive, array('started', 'completed'))) {
                continue;
            }
            $MatchScoreDetails = $PlayersData = array();
            $MatchScoreDetails['StatusLive'] = ($MatchStatusLive == 'started') ? 'Live' : (($MatchStatusLive == 'not_started') ? 'Not Started' : 'Completed');
            foreach ($Response['data']['teams'] as $TeamKey => $TeamValue) {
                if ($TeamValue['position'] == 'home') {
                    $MatchScoreDetails['TeamScoreLocal'] = array('Name' => $TeamValue['name'],
                        'ShortName' => $TeamValue['code'], 'Scores' => $Response['data']['score']['home']);
                } else {
                    $MatchScoreDetails['TeamScoreVisitor'] = array('Name' => $TeamValue['name'],
                        'ShortName' => $TeamValue['code'], 'Scores' => $Response['data']['score']['away']);
                }
            }
            $MatchScoreDetails['MatchVenue'] = @$Response['data']['match']['stadium']['city'];
            $MatchScoreDetails['ManOfTheMatch'] = @$Response['data']['match_result']['man_of_the_match']; // Player Live ID
            foreach ($Response['data']['players'] as $Key => $PlayerValue) {
                $PlayersData[$Key]['PlayerIDLive'] = $PlayerValue['key'];
                $PlayersData[$Key]['Name'] = $PlayerValue['name'];
                $PlayersData[$Key]['Role'] = $PlayerRolesArr[$PlayerValue['role']];
                $PlayersData[$Key]['IsPlaying'] = $PlayerValue['in_playing_squad'];
                $PlayersData[$Key]['IsBench'] = $PlayerValue['in_bench_squad'];
                $PlayersData[$Key]['Assist'] = $PlayerValue['stats']['goal']['assist'];
                $PlayersData[$Key]['OwnGoal'] = $PlayerValue['stats']['goal']['own_goal_conceded'];
                $PlayersData[$Key]['Goals'] = $PlayerValue['stats']['goal']['scored'];
                $PlayersData[$Key]['PenaltyMiss'] = $PlayerValue['stats']['penalty']['missed'];
                $PlayersData[$Key]['PenaltySaved'] = $PlayerValue['stats']['penalty']['saved'];
                $PlayersData[$Key]['RedCard'] = $PlayerValue['stats']['card']['RC'];
                $PlayersData[$Key]['YellowCard'] = $PlayerValue['stats']['card']['YC'];
                $PlayersData[$Key]['CleanSheet'] = (!empty($PlayerValue['stats']['clean_sheet'])) ? $PlayerValue['stats']['clean_sheet'] : '';
            }
            $MatchScoreDetails['Players'] = $PlayersData;

            /* Update Match Data */
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('football_sports_matches', array('MatchScoreDetails' => json_encode($MatchScoreDetails)));

            if ($MatchStatusLive == 'completed') {

                if (strtolower($MatchStatusOverView) == "abandoned" || strtolower($MatchStatusOverView) == "canceled" || strtolower($MatchStatusOverView) == "play_suspended_unknown") {

                    $CronID = $this->insertCronLogs('autoCancelContest');
                    $this->autoCancelContest($CronID, 'Abonded', $Value['MatchID']);
                    $this->updateCronLogs($CronID);
                    //$this->autoCancelContestMatchAbonded($Value['MatchID']);
                    /* Update Match Status */
                    $this->db->where('EntityID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 8));
                } else if (strtolower($MatchStatusOverView) == "result") {

                    /* Update Final points before complete match */
                    $CronID = $this->insertCronLogs('getPlayerPoints');
                    $this->getPlayerPoints($CronID);
                    $this->updateCronLogs($CronID);

                    /* Update Final player points before complete match */
                    $CronID = $this->insertCronLogs('getJoinedContestTeamPoints');
                    $this->getJoinedContestTeamPoints($CronID, $Value['MatchID']);
                    $this->updateCronLogs($CronID);

                    /* Update Match Status */
                    $this->db->where('EntityID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => ($MatchReviewCheckPoint == 'post_match_validated') ? 5 : 5));
                    /* Update Contest Status */
                    $this->db->query('UPDATE football_sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE  E.StatusID = 2 AND  C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID']);
                }
            }
        }
        $this->autoCancelContest();
    }

    /*
      Description: To get match live score (Cricket API)
     */

    function getLivePlaying11MatchPlayer() {
        ini_set('max_execution_time', 300);
        $DateTime = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')) + 2400);
        $AccessToken = $this->getAccessTokenLive();
        /* Get Live Matches Data */
        $LiveMatches = $this->Football_Sports_model->getMatches('MatchIDLive,MatchGUID,MatchID,MatchStartDateTime,Status,IsPlayingXINotificationSent,TeamNameShortLocal,TeamNameShortVisitor', array('MatchStartDateTime' => $DateTime, 'StatusID' => array(1, 2),
            "IsPlayingXINotificationSent" => "No"), true, 1, 25);
        foreach ($LiveMatches['Data']['Records'] as $Value) {
            $Response = @file_get_contents(FOOTBALL_SPORTS_API_URL_CRICKETAPI . '/v1/match/' . $Value['MatchIDLive'] . '/?access_token=' . $AccessToken);
            $Response = @json_decode($Response, TRUE);
            $MatchStatusLive = @$Response['data']['match']['status'];
            $MatchStatusOverView = @$Response['data']['match']['status_overview'];
            if ($MatchStatusLive == 'not_started') {
                /* Get Match Players */
                $PlayersIdsData = array();
                $PlayersData = $this->Football_Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID,IsPlaying', array('MatchID' => $Value['MatchID']), true, 0);
                if ($PlayersData) {
                    $PlayersIdsData = array_column($PlayersData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
                }
                $Falg = false;
                foreach ($Response['data']['players'] as $PlayerIdLive => $PlayerValue) {
                    if ($PlayerValue['in_playing_squad'] || $PlayerValue['in_bench_squad']) {
                        $Falg = true;
                        /* Update Playing XI Status */
                        $this->db->where('MatchID', $Value['MatchID']);
                        $this->db->where('PlayerID', $PlayersIdsData[$PlayerIdLive]);
                        $this->db->limit(1);
                        $this->db->update('football_sports_team_players', array('IsPlaying' => "Yes"));
                    }
                }
                if ($Value['IsPlayingXINotificationSent'] == "No" && $Falg) {
                    /* Update Playing XI Notification Status */
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_matches', array('IsPlayingXINotificationSent' => "Yes"));
                    /* Send Playing XI Notification - To all users */
                    pushNotificationAndroidBroadcast('Playing XI - Announced', 'Playing XI for ' . $Value['TeamNameShortLocal'] . ' Vs ' . $Value['TeamNameShortVisitor'] . ' announced.', $Value['MatchGUID']);
                    pushNotificationIphoneBroadcast('Playing XI - Announced', 'Playing XI for ' . $Value['TeamNameShortLocal'] . ' Vs ' . $Value['TeamNameShortVisitor'] . ' announced.', $Value['MatchGUID']);
                }
            }
        }
    }

    /*
      Description: To Auto Cancel Contest
     */

    function autoCancelContestMatchAbonded($MatchID) {

        ini_set('max_execution_time', 300);
        /* Get Contest Data */
        $ContestsUsers = $this->Football_Contest_model->getContests('ContestID,Privacy,EntryFee,TotalJoined,ContestFormat,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor', array('StatusID' => array(1, 2, 5), "MatchID" => $MatchID), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] > 0) {
            foreach ($ContestsUsers['Data']['Records'] as $Value) {
                /* Update Contest Status */
                $this->db->where('EntityID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
            }
        }
    }

    /*
      Description: To Auto Cancel Contest
     */

    function autoCancelContest($CronID = 0, $CancelType = "Cancelled", $MatchID = "") {
        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        if (!empty($MatchID)) {
            $ContestsUsers = $this->db->query('SELECT C.ContestID,C.UnfilledWinningPercent,C.Privacy,C.EntryFee,C.ContestFormat,C.ContestSize,C.IsConfirm,C.CustomizeWinning,M.MatchStartDateTime,(SELECT COUNT(TotalPoints) FROM football_sports_contest_join WHERE ContestID =  C.ContestID ) TotalJoined FROM tbl_entity E, football_sports_contest C, football_sports_matches M WHERE E.EntityID = C.ContestID AND C.MatchID = M.MatchID AND C.MatchID = ' . $MatchID . ' AND E.StatusID IN(1,2) AND LeagueType = "Dfs" AND DATE(M.MatchStartDateTime) <= "' . date('Y-m-d') . '" ORDER BY M.MatchStartDateTime ASC');
        } else {
            $ContestsUsers = $this->db->query('SELECT C.ContestID,C.UnfilledWinningPercent,C.Privacy,C.EntryFee,C.ContestFormat,C.ContestSize,C.IsConfirm,C.CustomizeWinning,M.MatchStartDateTime,(SELECT COUNT(TotalPoints) FROM football_sports_contest_join WHERE ContestID =  C.ContestID ) TotalJoined FROM tbl_entity E, football_sports_contest C, football_sports_matches M WHERE E.EntityID = C.ContestID AND C.MatchID = M.MatchID AND E.StatusID IN(1,2) AND LeagueType = "Dfs" AND DATE(M.MatchStartDateTime) <= "' . date('Y-m-d') . '" ORDER BY M.MatchStartDateTime ASC');
        }

        if ($ContestsUsers->num_rows() == 0) {
            return FALSE;
        }

        foreach ($ContestsUsers->result_array() as $Value) {
            if ($CancelType == "Cancelled") {

                if (((strtotime($Value['MatchStartDateTime'])) - strtotime(date('Y-m-d H:i:s'))) > 0) {
                    continue;
                }
                /* To Check Unfilled Contest */
                if (($Value['UnfilledWinningPercent'] == 'GuranteedPool' || $Value['UnfilledWinningPercent'] == 'Yes') && $Value['ContestSize'] != $Value['TotalJoined']) {
                    if ($Value['TotalJoined'] == 0) {

                        /* Update Contest Status */
                        $this->db->where('EntityID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
                    } else {
                        $TotalCollection = $Value['EntryFee'] * $Value['TotalJoined'];
                        foreach (json_decode($Value['CustomizeWinning'], TRUE) as $WinningValue) {
                            $NewCustomizeWinning[] = array(
                                'From' => $WinningValue['From'],
                                'To' => $WinningValue['To'],
                                'Percent' => $WinningValue['Percent'],
                                'WinningAmount' => round(($TotalCollection * $WinningValue['Percent']) / 100, 2)
                            );
                        }

                        /* Update Contest New Customize Winning */
                        if (!empty($NewCustomizeWinning)) {
                            /* $this->db->where('ContestID', $Value['ContestID']);
                              $this->db->limit(1);
                              $this->db->update('football_sports_contest', array('CustomizeWinning' => json_encode($NewCustomizeWinning))); */
                        }
                    }
                    continue;
                }

                /* To check contest cancel condition */
                $IsCancelled = 0;
                if ($Value['Privacy'] == 'Yes') { // Should be 100% filled
                    $IsCancelled = ($Value['ContestSize'] != $Value['TotalJoined']) ? 1 : 0;
                } else {
                    if ($Value['ContestFormat'] == 'Head to Head') {
                        $IsCancelled = ($Value['TotalJoined'] == 2) ? 0 : 1;
                    } else {
                        if ($Value['IsConfirm'] == 'Yes') {
                            $IsCancelled = 0;
                        } else {
                            $JoinedPercent = ($Value['TotalJoined'] * 100) / $Value['ContestSize'];
                            $IsCancelled = ($JoinedPercent >= 100) ? 0 : 1;
                        }
                    }
                }
                if ($IsCancelled == 0) {
                    continue;
                }
            }
            /* Update Contest Status */
            $this->db->where('EntityID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));

            $this->db->where('ContestID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('football_sports_contest', array('CancelledBy' => 'Cron'));
        }
    }

    function autoCancelContestDebug($CronID = "") {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $ContestsUsers = $this->Football_Contest_model->getContests('ContestID,UnfilledWinningPercent,EntryFee,TotalJoined,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor', array('StatusID' => array(1, 2), 'Filter' => 'MatchLive', 'IsConfirm' => "No",
            "IsPaid" => "Yes", "LeagueType" => "Dfs"), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] == 0) {
            if ($CronID) {
                $this->db->where('CronID', $CronID);
                $this->db->limit(1);
                $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
                exit;
            }
        }
        foreach ($ContestsUsers['Data']['Records'] as $Value) {

            $MatchStartDateTime = strtotime($Value['MatchStartDateTime']) - 19800; // -05:30 Hours
            $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
            if (($MatchStartDateTime - $CurrentDateTime) > 300) continue;

            if ($Value['UnfilledWinningPercent'] == 'GuranteedPool' && $Value['ContestSize'] != $Value['TotalJoined']) { // Variable Contests
                if ($Value['TotalJoined'] == 0) {
                    /* Update Contest Status */
                    $this->db->where('EntityID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
                }
                continue;
            }

            $IsCancelled = (($Value['IsConfirm'] == 'No' && $Value['TotalJoined'] != $Value['ContestSize']) ? 1 : 0);
            if ($IsCancelled == 0) continue;

            /* Update Contest Status */
            $this->db->where('EntityID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
        }
    }

    /*
      Description: Use to get sports points.
     */

    function getPoints($Where = array()) {
        $this->db->select('PointsTypeGUID,PointsTypeDescprition,PointsTypeShortDescription,PointsType,PointsInningType,PointsScoringField,StatusID,PointsValue');
        $this->db->from('football_sports_setting_points');

        if (!empty($Where['StatusID'])) {
            $this->db->where("StatusID", $Where['StatusID']);
        }

        $this->db->order_by("PointsType", 'ASC');
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();
        // $this->db->cache_on();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: Find sub arrays from multidimensional array
     */

    function findSubArray($DataArray, $keyName, $Value) {
        $Data = array();
        foreach ($DataArray as $Row) {
            if ($Row[$keyName] == $Value) $Data[] = $Row;
        }
        return $Data;
    }

    /*
      Description: To calculate points according to keys
     */

    function calculatePoints($Points = array(), $ScoreValue, $Position, $PlayerIDLive) {
        /* Match Types */
        $PlayerPoints = array('PointsTypeGUID' => $Points['PointsTypeGUID'], 'PointsTypeShortDescription' => $Points['PointsTypeShortDescription'],
            'DefinedPoints' => strval($Points['PointsValue']), 'ScoreValue' => (!empty($ScoreValue)) ? strval($ScoreValue) : "0");
        switch ($Points['PointsTypeGUID']) {
            case 'GoalByStriker':
                $PlayerPoints['CalculatedPoints'] = ($Position == 'Striker') ? $ScoreValue * $Points['PointsValue'] : "0";
                return $PlayerPoints;
                break;
            case 'GoalByMidfielder':
                $PlayerPoints['CalculatedPoints'] = ($Position == 'Midfielder') ? $ScoreValue * $Points['PointsValue'] : "0";
                return $PlayerPoints;
                break;
            case 'GoalByDefender':
                $PlayerPoints['CalculatedPoints'] = ($Position == 'Defender') ? $ScoreValue * $Points['PointsValue'] : "0";
                return $PlayerPoints;
                break;
            case 'GoalByGoalkeeper':
                $PlayerPoints['CalculatedPoints'] = ($Position == 'Goalkeeper') ? $ScoreValue * $Points['PointsValue'] : "0";
                return $PlayerPoints;
                break;
            case 'CleanSheetByDefender':
                $PlayerPoints['CalculatedPoints'] = ($Position == 'Defender') ? $Points['PointsValue'] : "0";
                return $PlayerPoints;
                break;
            case 'CleanSheetByGoalkeeper':
                $PlayerPoints['CalculatedPoints'] = ($Position == 'Goalkeeper') ? $Points['PointsValue'] : "0";
                return $PlayerPoints;
                break;
            case 'OwnGoal':
            case 'Assists':
            case 'PenaltyMiss':
            case 'PenaltySaved':
            case 'RedCard':
            case 'YellowCard':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $ScoreValue * $Points['PointsValue'] : "0";
                return $PlayerPoints;
                break;
            case 'ManOfMatch':
                $PlayerPoints['CalculatedPoints'] = ($PlayerIDLive == $ScoreValue) ? $Points['PointsValue'] : "0";
                return $PlayerPoints;
                break;
            default:
                return false;
                break;
        }
    }

    /*
      Description: To get player points
     */

    function getPlayerPoints($CronID, $MatchID = "") {


        if (!empty($MatchID)) {
            $LiveMatches = $this->Football_Sports_model->getMatches('MatchID,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,SeriesID,RoundNo', array('MatchID' => $MatchID), true, 1, 1);
        } else {
            $LiveMatches = $this->Football_Sports_model->getMatches('MatchID,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,SeriesID,RoundNo', array('Filter' => 'Yesterday', 'StatusID' => array(2, 5, 10),
                'IsPlayerPointsUpdated' => 'No', 'OrderBy' => 'M.MatchStartDateTime',
                'Sequence' => 'DESC'), true, 1, 10);
        }
        /* Get Live Matches Data */
        if (!empty($LiveMatches)) {


            /* Get Points Data */
            $PointsDataArr = $this->cache->memcached->get('FootballPoints');
            if (empty($PointsDataArr)) {
                $PointsDataArr = $this->getPoints();

                $this->cache->memcached->save('FootballPoints', $PointsDataArr, 3600 * 12); // Expire in every 12 hours
            }


            $StatringXIArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'StatringXI');
            $CaptainPointMPArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'CaptainPointMP');
            $ViceCaptainPointMPArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'ViceCaptainPointMP');

            /* Sorting Keys */
            $PointsSortingKeys = array('SB', 'ST', 'MF', 'DF', 'GK', 'OG', 'AS',
                'PM', 'PS', 'CSD', 'CSG', 'RC', 'YC', 'MOM');
            foreach ($LiveMatches['Data']['Records'] as $Value) {
                if (empty((array) $Value['MatchScoreDetails'])) continue;

                $MatchPlayersCache = array();
                /* Delete Cache Key */
                //$this->cache->memcached->delete('getJoinedGamePoints_' . $Value['SeriesID'] . $Value['RoundNo']);
                $StatringXIPoints = (isset($StatringXIArr[0]['PointsValue'])) ? strval($StatringXIArr[0]['PointsValue']) : "2";
                $CaptainPointMPPoints = (isset($CaptainPointMPArr[0]['PointsValue'])) ? strval($CaptainPointMPArr[0]['PointsValue']) : "2";
                $ViceCaptainPointMPPoints = (isset($ViceCaptainPointMPArr[0]['PointsValue'])) ? strval($ViceCaptainPointMPArr[0]['PointsValue']) : "1.5";

                /* Get Match Players */
                $MatchPlayers['Data']['Records'] = $this->cache->memcached->get('FootballMatchPlayerPlaying11_' . $Value['MatchID']);
                if (empty($MatchPlayers['Data']['Records'])) {
                    $MatchPlayers = $this->Football_Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID,PlayerRole', array('MatchID' => $Value['MatchID'], 'IsRemoved' => 'No'), true, 0);
                    $this->cache->memcached->save('FootballMatchPlayerPlaying11_' . $Value['MatchID'], $MatchPlayers['Data']['Records'], 1800);
                }

                if (!$MatchPlayers) {
                    continue;
                }

                /* Get Match Live Score Data */
                $AllPalyers = array();
                foreach ($Value['MatchScoreDetails']['Players'] as $PlayerKey => $Player) {
                    $AllPalyers[$PlayerKey]['Name'] = $Player['Name'];
                    $AllPalyers[$PlayerKey]['PlayerIDLive'] = $Player['PlayerIDLive'];
                    $AllPalyers[$PlayerKey]['Role'] = $Player['Role'];
                    $AllPalyers[$PlayerKey]['IsPlaying'] = $Player['IsPlaying'];
                    $AllPalyers[$PlayerKey]['IsBench'] = $Player['IsBench'];
                    $AllPalyers[$PlayerKey]['Assist'] = $Player['Assist'];
                    $AllPalyers[$PlayerKey]['OwnGoal'] = $Player['OwnGoal'];
                    $AllPalyers[$PlayerKey]['Goals'] = $Player['Goals'];
                    $AllPalyers[$PlayerKey]['PenaltyMiss'] = $Player['PenaltyMiss'];
                    $AllPalyers[$PlayerKey]['PenaltySaved'] = $Player['PenaltySaved'];
                    $AllPalyers[$PlayerKey]['RedCard'] = $Player['RedCard'];
                    $AllPalyers[$PlayerKey]['YellowCard'] = $Player['YellowCard'];
                    $AllPalyers[$PlayerKey]['CleanSheet'] = $Player['CleanSheet'];
                }
                if (empty($AllPalyers)) {
                    continue;
                }
                $AllPlayersLiveIds = array_keys($AllPalyers);
                foreach ($MatchPlayers['Data']['Records'] as $PlayerValue) {

                    $PlayerData = $AllPalyers[$PlayerValue['PlayerIDLive']];
                    if (empty($PlayerData['IsPlaying']) && empty($PlayerData['IsBench'])) {
                        continue;
                    }

                    $PointsData['SB'] = array('PointsTypeGUID' => 'StatringXI', 'PointsTypeShortDescription' => 'SB',
                        'DefinedPoints' => $StatringXIPoints, 'ScoreValue' => "1",
                        'CalculatedPoints' => $StatringXIPoints);
                    $ScoreData = $AllPalyers[$PlayerValue['PlayerIDLive']];

                    /* To Check Player Is Played Or Not */
                    if (in_array($PlayerValue['PlayerIDLive'], $AllPlayersLiveIds) && !empty($ScoreData)) {
                        foreach ($PointsDataArr['Data']['Records'] as $PointValue) {
                            if (IS_VICECAPTAIN) {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                                            'StatringXI', 'ViceCaptainPointMP'))) continue;
                            } else {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                                            'StatringXI'))) continue;
                            }
                            $allKeys = array_keys($ScoreData);
                            if (($DeleteKey = array_search('Name', $allKeys)) !== false) {
                                unset($allKeys[$DeleteKey]);
                            }
                            if (($DeleteKey = array_search('PlayerIDLive', $allKeys)) !== false) {
                                unset($allKeys[$DeleteKey]);
                            }

                            /** calculate points * */
                            foreach ($allKeys as $ScoreValue) {
                                $calculatePoints = $this->calculatePoints($PointValue, @$ScoreData[$PointValue['PointsScoringField']], @$ScoreData['Role'], $PlayerValue['PlayerIDLive']);
                                if (is_array($calculatePoints) && !empty($calculatePoints)) {
                                    $PointsData[$calculatePoints['PointsTypeShortDescription']] = array('PointsTypeGUID' => $calculatePoints['PointsTypeGUID'],
                                        'PointsTypeShortDescription' => $calculatePoints['PointsTypeShortDescription'],
                                        'DefinedPoints' => strval($calculatePoints['DefinedPoints']),
                                        'ScoreValue' => strval($calculatePoints['ScoreValue']),
                                        'CalculatedPoints' => strval(round($calculatePoints['CalculatedPoints'], 2)));
                                }
                            }
                        }
                    } else {
                        $PointsData['SB'] = array('PointsTypeGUID' => 'StatringXI',
                            'PointsTypeShortDescription' => 'SB', 'DefinedPoints' => $StatringXIPoints,
                            'ScoreValue' => "1", 'CalculatedPoints' => $StatringXIPoints);
                        foreach ($PointsDataArr['Data']['Records'] as $PointValue) {
                            if (IS_VICECAPTAIN) {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                                            'StatringXI', 'ViceCaptainPointMP'))) continue;
                            } else {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                                            'StatringXI'))) continue;
                            }
                            if (in_array($PointValue['PointsTypeGUID'], array('GoalByStriker,GoalByMidfielder,GoalByDefender,GoalByGoalkeeper,OwnGoal,Assists,PenaltyMiss,PenaltySaved,CleanSheetByDefender,CleanSheetByGoalkeeper,RedCard,YellowCard,ManOfMatch'))) continue;
                            $PointsData[$PointValue['PointsTypeShortDescription']] = array('PointsTypeGUID' => $PointValue['PointsTypeGUID'],
                                'PointsTypeShortDescription' => $PointValue['PointsTypeShortDescription'],
                                'DefinedPoints' => "0", 'ScoreValue' => "0",
                                'CalculatedPoints' => "0");
                        }
                    }

                    /* Sort Points Keys Data */
                    $OrderedArray = array();
                    foreach ($PointsSortingKeys as $SortValue) {
                        unset($PointsData[$SortValue]['PointsTypeShortDescription']);
                        $OrderedArray[] = $PointsData[$SortValue];
                    }
                    $PointsData = $OrderedArray;

                    /* Calculate Total Points */
                    if (!empty($PointsData)) {
                        $PlayerTotalPoints = 0;
                        foreach ($PointsData as $PointValue) {
                            if ($PointValue['CalculatedPoints'] > 0) {
                                $PlayerTotalPoints += $PointValue['CalculatedPoints'];
                            } else {
                                $PlayerTotalPoints = $PlayerTotalPoints - abs($PointValue['CalculatedPoints']);
                            }
                        }
                    }


                    /* Update Player Points Data */
                    $this->db->where(array('SeriesID' => $Value['SeriesID'], 'MatchID' => $Value['MatchID'],
                        'PlayerID' => $PlayerValue['PlayerID']));
                    $this->db->limit(1);
                    $this->db->update('football_sports_team_players', array('TotalPoints' => $PlayerTotalPoints, 'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : null));

                    $MatchPlayersCache[] = array(
                        'PlayerGUID' => $PlayerValue['PlayerGUID'],
                        'PlayerID' => $PlayerValue['PlayerID'],
                        'TotalPoints' => $PlayerTotalPoints,
                        'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : "",
                    );
                }

                $MatchPlayers = $this->db->query('SELECT P.PlayerGUID,TP.PlayerID,TP.TotalPoints,TP.PointsData FROM football_sports_players P,football_sports_team_players TP WHERE P.PlayerID = TP.PlayerID AND TP.MatchID = ' . $Value['MatchID'])->result_array();
                $this->cache->memcached->save('FootballMatchPlayerPoint_' . $Value['MatchID'], $MatchPlayers, 3600 * 4);

                /* Update Match Player Points Status */
                if ($Value['StatusID'] == 5) {
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_matches', array('IsPlayerPointsUpdated' => 'Yes'));

                    /* Update Final player points before complete match */
                    /* $CronID = $this->insertCronLogs('getJoinedGamePoints');
                      $this->getJoinedGamePoints($CronID, array(2, 5));
                      $this->updateCronLogs($CronID); */
                }
            }
        }
    }

    /*
      Description: To get joined game points
     */

    function getJoinedContestTeamPoints($CronID, $MatchID = "", $StatusArr = array(2), $ContestStatus = 2) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        ini_set('max_execution_time', 300);
        /* Get Matches Live */
        if (empty($MatchID)) {
            $MatchQuery = $this->db->query('SELECT MatchID FROM `football_sports_matches` M, tbl_entity E WHERE E.EntityID = M.MatchID AND E.StatusID IN (' . implode(',', $StatusArr) . ') ORDER BY M.PointsLastUpdatedOn ASC');
            if ($MatchQuery->num_rows() == 0) {
                return FALSE;
            }
            $MatchID = $MatchQuery->row()->MatchID;
        }
        /* Get contest Live */
        if (!empty($MatchID)) {
            $LiveMatcheContest = $this->Football_Contest_model->getContests('SmartPool,MatchID,ContestID,MatchIDLive,CustomizeWinning,NoOfWinners,ContestSize', array('StatusID' => array(2, 5), 'MatchID' => $MatchID, "LeagueType" => "Dfs"), true, 0);
        } else {
            $LiveMatcheContest = $this->Football_Contest_model->getContests('SmartPool,MatchID,ContestID,MatchIDLive,MatchStartDateTimeUTC,StatusID,CustomizeWinning,NoOfWinners,ContestSize', array('StatusID' => $StatusArr, 'Filter' => 'MatchLive', "LeagueType" => "Dfs"), true, 0);
        }
        if ($LiveMatcheContest['Data']['TotalRecords'] == 0) {
            return true;
        }

        /* To Get Match Players */
        $MatchPlayers = $this->cache->memcached->get('FootballMatchPlayerPoint_' . $MatchID);
        if (empty($MatchPlayers)) {
            $MatchPlayers = $this->db->query('SELECT P.PlayerGUID,TP.PlayerID,TP.TotalPoints,TP.PointsData FROM football_sports_players P,football_sports_team_players TP WHERE P.PlayerID = TP.PlayerID AND TP.MatchID = ' . $MatchID)->result_array();
            $this->cache->memcached->save('FootballMatchPlayerPoint_' . $MatchID, $MatchPlayers, 1800);
        }

        /* Update Match PointsLastUpdatedOn */
        $this->db->where('MatchID', $MatchID);
        $this->db->limit(1);
        $this->db->update('football_sports_matches', array('PointsLastUpdatedOn' => date('Y-m-d H:i:s')));
        log_message('ERROR', "Points MatchID - " . $MatchID);

        ini_set('memory_limit', '512M');

        /* Get Vice Captain Points */
        //$ViceCaptainPointsData = $this->db->query('SELECT PointsODI,PointsT20,PointsTEST FROM football_sports_setting_points WHERE PointsTypeGUID = "ViceCaptainPointMP" LIMIT 1')->row_array();
        /* Get Captain Points */
        //$CaptainPointsData = $this->db->query('SELECT PointsODI,PointsT20,PointsTEST FROM football_sports_setting_points WHERE PointsTypeGUID = "CaptainPointMP" LIMIT 1')->row_array();

        foreach ($LiveMatcheContest['Data']['Records'] as $RowContest) {
            /* Get Live Contests */
            $Query = "SELECT M.MatchTypeID, M.MatchID, JC.ContestID, JC.UserID, JC.UserTeamID,U.UserGUID"
                    . ",UT.UserTeamName,UT.UserTeamGUID,U.Username,CONCAT_WS(' ',U.FirstName,U.LastName) FullName,"
                    . "IF(U.ProfilePic IS NULL,CONCAT('" . BASE_URL . "','uploads/profile/picture/','default.jpg'),"
                    . "CONCAT('" . BASE_URL . "','uploads/profile/picture/',U.ProfilePic)) ProfilePic, "
                    . "( SELECT CONCAT( '[', GROUP_CONCAT( JSON_OBJECT( 'PlayerGUID', P.PlayerGUID, "
                    . "'PlayerName', P.PlayerName,'PlayerRole',TP.PlayerRole,'PlayerSalary',TP.PlayerSalary,'SeriesGUID', S.SeriesGUID,'TeamGUID', "
                    . "T.TeamGUID,'PlayerPic',IF(P.PlayerPic IS NULL,CONCAT('" . BASE_URL . "','uploads/PlayerPic/','player.png'),"
                    . "CONCAT('" . BASE_URL . "','uploads/PlayerPic/',P.PlayerPic)),'PlayerPosition', UTP.PlayerPosition ) ), ']' ) "
                    . "FROM football_sports_players P,football_sports_team_players TP,football_sports_teams T, football_sports_users_team_players UTP,football_sports_series S "
                    . "WHERE P.PlayerID = UTP.PlayerID AND UTP.MatchID = M.MatchID AND UTP.UserTeamID = JC.UserTeamID AND "
                    . "P.PlayerID = TP.PlayerID AND TP.MatchID = M.MatchID AND TP.TeamID = T.TeamID AND TP.SeriesID = S.SeriesID ) "
                    . "AS UserPlayersJSON FROM `football_sports_contest_join` JC, football_sports_matches M, tbl_entity E,tbl_users U,"
                    . "football_sports_users_teams UT WHERE JC.MatchID = M.MatchID AND E.EntityID = JC.ContestID AND "
                    . "UT.UserTeamID = JC.UserTeamID AND U.UserID = JC.UserID AND E.StatusID = $ContestStatus AND JC.ContestID = '" . $RowContest['ContestID'] . "' ORDER BY JC.ContestID";
            $Data = $this->db->query($Query);
            if ($Data->num_rows() > 0) {
                /* Contest Rank Array */
                $ContestIdArr = array();
                /* Joined Users Teams Data */
                foreach ($Data->result_array() as $Key => $Value) {
                    $PositionPointsMultiplier = array('ViceCaptain' => 1.5, 'Captain' => 2, 'Player' => 1);
                    $ContestIdArr[] = $RowContest['ContestID'];
                    $PlayersPointsArr = array_column($MatchPlayers, 'TotalPoints', 'PlayerGUID');
                    $PlayersIdsArr = array_column($MatchPlayers, 'PlayerID', 'PlayerGUID');
                    $PlayersPointsData = array_column($MatchPlayers, 'PointsData', 'PlayerGUID');
                    /* Player Points Multiplier */
                    /* $PositionPointsMultiplier = (IS_VICECAPTAIN) ? array('ViceCaptain' => $ViceCaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],'Captain' => $CaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],'Player' => 1) : array('Captain' => $CaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],'Player' => 1); */
                    $UserTotalPoints = 0;
                    $UserPlayersArr = array();
                    /* To Get User Team Players */
                    foreach (json_decode($Value['UserPlayersJSON'], TRUE) as $UserTeamValue) {
                        if (!isset($PlayersPointsArr[$UserTeamValue['PlayerGUID']])) continue;

                        $Points = ($PlayersPointsArr[$UserTeamValue['PlayerGUID']] != 0) ? $PlayersPointsArr[$UserTeamValue['PlayerGUID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                        $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);
                        $UserPlayersArr[] = array('PlayerGUID' => $UserTeamValue['PlayerGUID'],
                            'PlayerName' => $UserTeamValue['PlayerName'], 'PlayerPic' => $UserTeamValue['PlayerPic'],
                            'PlayerPosition' => $UserTeamValue['PlayerPosition'],
                            'PlayerSalary' => (String) $UserTeamValue['PlayerSalary'],
                            'PlayerRole' => $UserTeamValue['PlayerRole'], 'TeamGUID' => $UserTeamValue['TeamGUID'],
                            'SeriesGUID' => $UserTeamValue['SeriesGUID'], 'Points' => (String) $Points,
                            'PointCredits' => (String) $Points, 'PointsData' => !empty($PlayersPointsData[$UserTeamValue['PlayerGUID']]) ? json_decode($PlayersPointsData[$UserTeamValue['PlayerGUID']], true) : array(), 'UserWinningAmount' => '0.0');
                    }
                    /* Add/Edit Joined Contest Data (MongoDB) */
                    $ContestCollection = $this->fantasydb->{'Contest_' . $Value['ContestID']};
                    $ContestCollection->updateOne(
                            ['_id' => (int) $Value['ContestID'] . $Value['UserID'] . $Value['UserTeamID']], ['$set' => ['ContestID' => $Value['ContestID'], 'UserID' => $Value['UserID'],
                            'UserTeamID' => $Value['UserTeamID'], 'UserTeamGUID' => $Value['UserTeamGUID'],
                            'UserGUID' => $Value['UserGUID'], 'UserTeamName' => $Value['UserTeamName'],
                            'Username' => $Value['Username'], 'FullName' => $Value['FullName'],
                            'ProfilePic' => $Value['ProfilePic'], 'TotalPoints' => (String) $UserTotalPoints,
                            'TotalTeamPoints' => (float) $UserTotalPoints,
                            'UserTeamPlayers' => $UserPlayersArr, 'UserWinningAmount' => '0.0',
                            'IsWinningAssigned' => 'No', 'SmartPoolWinning' => '', "SmartPool" => $RowContest['SmartPool']]], ['upsert' => true]
                    );
                }
                /* Update User Rank (MongoDB) */
                foreach (array_unique($ContestIdArr) as $ContestID) {
                    $ContestCollection = $this->fantasydb->{'Contest_' . $ContestID};
                    $ContestData = $ContestCollection->find([], ['projection' => ['TotalTeamPoints' => 1], 'sort' => ['TotalTeamPoints' => -1]]);
                    $PrevPoint = $PrevRank = 0;
                    $SkippedCount = 1;
                    foreach ($ContestData as $ContestValue) {
                        if ($PrevPoint != $ContestValue['TotalTeamPoints']) {
                            $PrevRank = $PrevRank + $SkippedCount;
                            $PrevPoint = $ContestValue['TotalTeamPoints'];
                            $SkippedCount = 1;
                        } else {
                            $SkippedCount++;
                        }
                        $RankArr[$ContestValue['_id']] = $PrevRank;
                        $ContestCollection->updateOne(
                                ['_id' => $ContestValue['_id']], ['$set' => ['UserRank' => (String) $PrevRank]], ['upsert' => false]
                        );
                    }
                }
            }
        }
    }

    /*
      Description: To set contest winners with mongodb
     */

    function setContestWinners($CronID) {

        ini_set('max_execution_time', 300);
        $Contests = $this->db->query('SELECT C.WinningAmount,C.ContestID,C.CustomizeWinning FROM tbl_entity E,football_sports_contest C WHERE E.EntityID = C.ContestID AND E.StatusID = 5 AND C.IsWinningDistributed = "No" AND C.LeagueType = "Dfs" AND C.SmartPool = "No"');
        //$Contests = $this->db->query('SELECT C.WinningAmount,C.ContestID,C.CustomizeWinning FROM tbl_entity E,sports_contest C WHERE E.EntityID = C.ContestID AND E.StatusID = 5 AND C.IsWinningDistributed = "No" AND C.LeagueType = "Dfs" AND C.ContestID = 103505');
        if ($Contests->num_rows() > 0) {
            foreach ($Contests->result_array() as $Value) {

                /* Get Joined Contests */
                $ContestCollection = $this->fantasydb->{'Contest_' . $Value['ContestID']};
                $JoinedContestsUsers = iterator_to_array($ContestCollection->find([
                            "ContestID" => $Value['ContestID'], "IsWinningAssigned" => "No",
                            "TotalTeamPoints" => ['$gt' => 1]], ['projection' => ['UserRank' => 1, 'UserTeamID' => 1,
                                'TotalTeamPoints' => 1, 'UserID' => 1], 'sort' => ['UserRank' => -1]]));
                $AllUsersRank = array_column($JoinedContestsUsers, 'UserRank');
                $AllRankWinners = array_count_values($AllUsersRank);

                if (count($AllRankWinners) == 0) {
                    /* Update Contest Winning Assigned Status */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_contest', array('IsWinningDistributed' => "Yes"));
                    continue;
                }

                $userWinnersData = $OptionWinner = array();
                $CustomizeWinning = (!empty($Value['CustomizeWinning'])) ? json_decode($Value['CustomizeWinning'], true) : array();
                foreach ($AllRankWinners as $Rank => $WinnerValue) {
                    $Flag = $TotalAmount = $AmountPerUser = 0;
                    for ($J = 0; $J < count($CustomizeWinning); $J++) {
                        if ($Rank >= $CustomizeWinning[$J]['From'] && $Rank <= $CustomizeWinning[$J]['To']) {
                            $TotalAmount = $CustomizeWinning[$J]['WinningAmount'];
                            if ($WinnerValue > 1) {
                                $L = 0;
                                for ($k = 1; $k < $WinnerValue; $k++) {
                                    if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                        if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                            $Flag = 1;
                                        } else {
                                            $L = $L + 1;
                                            if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                                if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                    $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                    $Flag = 1;
                                                }
                                            }
                                        }
                                    }
                                    if ($Flag == 0) {
                                        if ($Rank + $k >= $CustomizeWinning[$J]['From'] && $Rank + $k <= $CustomizeWinning[$J]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J]['WinningAmount'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $AmountPerUser = $TotalAmount / $WinnerValue;
                    $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers, $Rank, $AmountPerUser);
                }
                foreach ($userWinnersData as $WinnerArray) {
                    foreach ($WinnerArray as $WinnerRow) {
                        $OptionWinner[] = $WinnerRow;
                    }
                }
                if (!empty($OptionWinner)) {
                    foreach ($OptionWinner as $WinnerValue) {
                        $TaxAmount = 0;
                        /* Update User Winning Amount (MongoDB) */
                        $ContestCollection->updateOne(
                                ['_id' => $Value['ContestID'] . $WinnerValue['UserID'] . $WinnerValue['UserTeamID']], ['$set' => ['UserWinningAmount' => (String) round($WinnerValue['UserWinningAmount'], 2), 'TaxAmount' => $TaxAmount, 'IsWinningAssigned' => 'Yes']], ['upsert' => false]
                        );
                    }
                }
            }
        }

        $this->setSmartPoolWinnersMongo($CronID);
    }

    /*
      Description: To set Smart Pool winners
     */

    function setSmartPoolWinnersMongo($CronID) {

        ini_set('max_execution_time', 300);

        $Contests = $this->db->query('SELECT C.WinningAmount,C.ContestID,C.CustomizeWinning,C.ContestSize,C.NoOfWinners FROM tbl_entity E,football_sports_contest C WHERE E.EntityID = C.ContestID AND E.StatusID = 5 AND C.IsWinningDistributed = "No" AND C.LeagueType = "Dfs" AND C.SmartPool = "Yes"');
        if ($Contests->num_rows() > 0) {
            foreach ($Contests->result_array() as $Value) {
                /* Get Joined Contests */
                $ContestCollection = $this->fantasydb->{'Contest_' . $Value['ContestID']};
                $JoinedContestsUsers = iterator_to_array($ContestCollection->find([
                            "ContestID" => $Value['ContestID'], "IsWinningAssigned" => "No",
                            "TotalTeamPoints" => ['$gt' => 0]], ['projection' => ['UserRank' => 1, 'UserTeamID' => 1,
                                'TotalTeamPoints' => 1, 'UserID' => 1], 'sort' => ['UserRank' => -1]]));
                $AllUsersRank = array_column($JoinedContestsUsers, 'UserRank');
                $AllRankWinners = array_count_values($AllUsersRank);
                if (count($AllRankWinners) == 0) {
                    /* Update Contest Winning Assigned Status */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_contest', array('IsWinningDistributed' => "Yes"));
                    continue;
                }
                $CustomizeWinning = json_decode($Value['CustomizeWinning'], true);
                if (empty($CustomizeWinning)) {
                    $CustomizeWinning[] = array(
                        'From' => 1,
                        'To' => $Value['NoOfWinners'],
                        'Percent' => 100,
                        'WinningAmount' => $Value['WinningAmount']
                    );
                }
                foreach ($AllRankWinners as $Rank => $WinnerValue) {
                    $Flag = 0;
                    for ($J = 0; $J < count($CustomizeWinning); $J++) {
                        $FromWinner = $CustomizeWinning[$J]['From'];
                        $ToWinner = $CustomizeWinning[$J]['To'];
                        if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                            $ProductName = $CustomizeWinning[$J]['ProductName'];
                            $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers, $Rank, $ProductName);
                        }
                    }
                }
                foreach ($userWinnersData as $WinnerArray) {
                    foreach ($WinnerArray as $WinnerRow) {
                        $OptionWinner[] = $WinnerRow;
                    }
                }
                if (!empty($OptionWinner)) {
                    foreach ($OptionWinner as $WinnerValue) {
                        $ContestCollection->updateOne(
                                ['_id' => $Value['ContestID'] . $WinnerValue['UserID'] . $WinnerValue['UserTeamID']], ['$set' => ['SmartPoolWinning' => $WinnerValue['UserWinningAmount'], 'IsWinningAssigned' => 'Yes']], ['upsert' => false]
                        );
                    }
                }
            }
        }
    }

    /*
      Description: To set Smart Pool winners
     */

    function setSmartPoolWinners($CronID) {

        ini_set('max_execution_time', 300);

        $Contests = $this->Football_Contest_model->getContests('WinningAmount,NoOfWinners,ContestID,ContestSize,CustomizeWinning', array('StatusID' => 5, 'SmartPool' => "Yes", 'IsWinningDistributed' => 'No',
            "LeagueType" => "Dfs"), true, 0);

        if (isset($Contests['Data']['Records'])) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(),
                    'Contests' => $Contests), JSON_UNESCAPED_UNICODE)));

            foreach ($Contests['Data']['Records'] as $Value) {
                $JoinedContestsUsers['Data']['Records'] = $this->db->query("SELECT JC.UserID,JC.UserTeamID,JC.TotalPoints,JC.UserRank FROM football_sports_contest_join JC WHERE JC.ContestID =" . $Value['ContestID'] . " AND JC.TotalPoints > 0 ORDER BY JC.UserRank DESC")->result_array();

                if (!empty($JoinedContestsUsers['Data']['Records'])) {

                    $AllUsersRank = array_column($JoinedContestsUsers['Data']['Records'], 'UserRank');
                    $AllRankWinners = array_count_values($AllUsersRank);
                    $userWinnersData = $OptionWinner = array();
                    $CustomizeWinning = $Value['CustomizeWinning'];
                    if (empty($CustomizeWinning)) {
                        $CustomizeWinning[] = array(
                            'From' => 1,
                            'To' => $Value['NoOfWinners'],
                            'Percent' => 100,
                            'WinningAmount' => $Value['WinningAmount']
                        );
                    }

                    foreach ($AllRankWinners as $Rank => $WinnerValue) {
                        $Flag = 0;
                        for ($J = 0; $J < count($CustomizeWinning); $J++) {
                            $FromWinner = $CustomizeWinning[$J]['From'];
                            $ToWinner = $CustomizeWinning[$J]['To'];
                            if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                                $ProductName = $CustomizeWinning[$J]['ProductName'];
                                $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers['Data']['Records'], $Rank, $ProductName);
                            }
                        }
                    }
                    foreach ($userWinnersData as $WinnerArray) {
                        foreach ($WinnerArray as $WinnerRow) {
                            $OptionWinner[] = $WinnerRow;
                        }
                    }
                    if (!empty($OptionWinner)) {
                        foreach ($OptionWinner as $WinnerValue) {

                            $this->db->trans_start();

                            $this->db->where('UserID', $WinnerValue['UserID']);
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('football_sports_contest_join', array('SmartPoolWinning' => $WinnerValue['UserWinningAmount'],
                                'ModifiedDate' => date('Y-m-d H:i:s')));

                            $this->db->trans_complete();
                            if ($this->db->trans_status() === false) {
                                return false;
                            }
                        }
                    }

                    /* update contest winner amount distribute flag set YES */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_contest', array('IsWinningDistributed' => 'Yes'));
                } else {
                    /* $this->db->where('EntityID', $Value['ContestID']);
                      $this->db->limit(1);
                      $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3)); */

                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_contest', array('IsRefund' => 'Yes', 'isMailSent' => "Yes"));
                }
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            return true;
        }
    }

    /*
      Description: To common funtion find key value
     */

    function findKeyValueArray($JoinedContestsUsers, $Rank, $AmountPerUser) {
        $WinnerUsers = array();
        foreach ($JoinedContestsUsers as $Rows) {
            if ($Rows['UserRank'] == $Rank) {
                $Temp['UserID'] = $Rows['UserID'];
                $Temp['FirstName'] = $Rows['FirstName'];
                $Temp['Email'] = $Rows['Email'];
                $Temp['UserWinningAmount'] = $AmountPerUser;
                $Temp['UserRank'] = $Rows['UserRank'];
                $Temp['TotalPoints'] = $Rows['TotalPoints'];
                $Temp['UserTeamID'] = $Rows['UserTeamID'];
                $WinnerUsers[] = $Temp;
            }
        }
        return $WinnerUsers;
    }

    function getJoinedContestTeamPointsOLD($CronID, $MatchID = "", $StatusArr = array(2)) {

        ini_set('max_execution_time', 300);
        /* Get Matches Live */
        if (!empty($MatchID)) {
            $LiveMatcheContest = $this->Football_Contest_model->getContests('MatchID,ContestID,MatchIDLive', array('StatusID' => array(2, 5), 'MatchID' => $MatchID, "LeagueType" => "Dfs"), true, 0);
        } else {
            $LiveMatcheContest = $this->Football_Contest_model->getContests('MatchID,ContestID,MatchIDLive', array('StatusID' => $StatusArr, 'Filter' => 'YesterdayToday',
                "LeagueType" => "Dfs"), true, 0);
        }
        if (!$LiveMatcheContest) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($LiveMatcheContest['Data']['Records'] as $Value) {
            $MatchIDLive = $Value['MatchIDLive'];
            $MatchID = $Value['MatchID'];
            $ContestID = $Value['ContestID'];

            /* To Get Match Players */
            $MatchPlayers = $this->Football_Sports_model->getPlayers('PlayerID,TotalPoints', array('MatchID' => $Value['MatchID']), true, 0);

            $Contests = $this->Football_Contest_model->getJoinedContests('MatchTypeID,ContestID,UserID,MatchID,UserTeamID', array('ContestID' => $ContestID), true, 0);

            if (!empty($Contests['Data']['Records'])) {

                /* Match Types */
                //$MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20', '4' => 'PointsT20', '5' => 'PointsTEST', '7' => 'PointsT20', '9' => 'PointsODI', '8' => 'PointsODI');
                foreach ($Contests['Data']['Records'] as $Value) {
                    /* Player Points Multiplier */
                    $PositionPointsMultiplier = array('ViceCaptain' => 1.5, 'Captain' => 2,
                        'Player' => 1);
                    $UserTotalPoints = 0;
                    $PlayersPointsArr = array_column($MatchPlayers['Data']['Records'], 'TotalPoints', 'PlayerGUID');
                    $PlayersIdsArr = array_column($MatchPlayers['Data']['Records'], 'PlayerID', 'PlayerGUID');
                    /* To Get User Team Players */
                    $UserTeamPlayers = $this->Football_Contest_model->getUserTeams('PlayerID,PlayerPosition,UserTeamPlayers', array('UserTeamID' => $Value['UserTeamID']), 0);

                    foreach ($UserTeamPlayers['UserTeamPlayers'] as $UserTeamValue) {
                        if (!isset($PlayersPointsArr[$UserTeamValue['PlayerGUID']])) continue;

                        $Points = ($PlayersPointsArr[$UserTeamValue['PlayerGUID']] != 0) ? $PlayersPointsArr[$UserTeamValue['PlayerGUID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                        $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);

                        /* Update Player Points */
                        $this->db->where('UserTeamID', $Value['UserTeamID']);
                        $this->db->where('PlayerID', $PlayersIdsArr[$UserTeamValue['PlayerGUID']]);
                        $this->db->limit(1);
                        $this->db->update('football_sports_users_team_players', array('Points' => $Points));
                        //echo $this->db->last_query();exit;
                    }
                    /* Update Player Total Points */
                    $this->db->where('UserTeamID', $Value['UserTeamID']);
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_contest_join', array('TotalPoints' => $UserTotalPoints, 'ModifiedDate' => date('Y-m-d H:i:s')));
                }
            }
            $this->updateRankByContest($ContestID);
        }
    }

    /*
      Description: To update rank
     */

    function updateRankByContest($ContestID) {
        if (!empty($ContestID)) {
            $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                         ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                         FROM football_sports_contest_join WHERE football_sports_contest_join.ContestID = '" . $ContestID . "')) AS UserRank,ContestID,UserTeamID
                         FROM football_sports_contest_join,tbl_users 
                         WHERE football_sports_contest_join.ContestID = '" . $ContestID . "' AND tbl_users.UserID = football_sports_contest_join.UserID
                     ");
            $results = $query->result_array();
            if (!empty($results)) {
                $this->db->trans_start();
                foreach ($results as $rows) {
                    $this->db->where('ContestID', $rows['ContestID']);
                    $this->db->where('UserTeamID', $rows['UserTeamID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_contest_join', array('UserRank' => $rows['UserRank']));
                }
                $this->db->trans_complete();
            }
        }
    }

    /*
      Description: To get joined game points
     */

    function getJoinedGamePoints($CronID, $StatusArr = array(2, 5)) {
        ini_set('memory_limit', '512M');

        /* Get Joined Games */

        //$Query = $this->db->query("SELECT GR.GameID,GR.ReferenceID,GR.UserTeamID,M.IsPlayerPointsUpdated,M.SeriesID,M.RoundNo,M.MatchID,( SELECT CONCAT( '[', GROUP_CONCAT( JSON_OBJECT( 'PlayerID', PlayerID, 'PlayerPosition', PlayerPosition ) ), ']' ) FROM sports_users_team_players WHERE UserTeamID = GR.UserTeamID AND RoundNo = M.RoundNo AND SeriesID = M.SeriesID) UserPlayersJSON FROM `sports_matches` M, sports_game_rounds_join GR, tbl_entity E WHERE GR.GameID = E.EntityID AND M.`MatchScoreDetails` IS NOT NULL AND M.`IsGamePointsUpdated` = 'No' AND FIND_IN_SET(M.MatchID,GR.MatchIds) AND E.StatusID IN (".implode(',', $StatusArr).") AND GR.RoundNo = M.RoundNo ORDER BY M.MatchStartDateTime ASC");

        $Query = $this->db->query("SELECT GR.GameID,GR.UserID,GR.ReferenceID,GR.UserTeamID,M.IsPlayerPointsUpdated,M.SeriesID,M.RoundNo,M.MatchID,( SELECT CONCAT( '[', GROUP_CONCAT( JSON_OBJECT( 'TeamID', TeamID, 'PlayerID', PlayerID, 'PlayerPosition', PlayerPosition ) ), ']' ) FROM football_sports_users_team_players WHERE UserTeamID = GR.UserTeamID AND RoundNo = M.RoundNo AND SeriesID = M.SeriesID) UserPlayersJSON FROM `football_sports_matches` M, tbl_entity E WHERE M.`MatchScoreDetails` IS NOT NULL AND M.`IsGamePointsUpdated` = 'No' AND FIND_IN_SET(M.MatchID,GR.MatchIds) AND E.StatusID IN (" . implode(',', $StatusArr) . ")"); // Remove Limit 2


        if ($Query->num_rows() > 0) {

            /* Game Rounds Rank Array */
            $GameIDArr = $SereisIDArr = $RoundNoArr = array();

            /* Get Captain Points */
            $CaptainPointsData = $this->db->query('SELECT PointsValue FROM sports_setting_points WHERE PointsTypeGUID = "CaptainPointMP" LIMIT 1')->row_array();

            /* Get Vice Captain Points */
            $ViceCaptainPointsData = $this->db->query('SELECT PointsValue FROM sports_setting_points WHERE PointsTypeGUID = "ViceCaptainPointMP" LIMIT 1')->row_array();

            /* Player Points Multiplier */
            $PositionPointsMultiplier = (IS_VICECAPTAIN) ? array('ViceCaptain' => $ViceCaptainPointsData['PointsValue'],
                'Captain' => $CaptainPointsData['PointsValue'], 'Player' => 1) : array(
                'Captain' => $CaptainPointsData['PointsValue'], 'Player' => 1);


            /* Joined Users Teams Data */
            foreach ($Query->result_array() as $Key => $Value) {

                $GameIDArr[] = $Value['GameID'];
                $SereisIDArr[] = $Value['SeriesID'];
                $RoundNoArr[] = $Value['RoundNo'];

                /* To Get Match Wise Players */
                $MatchPlayers = $this->cache->memcached->get('getJoinedGamePoints_' . $Value['SeriesID'] . $Value['RoundNo']);
                if (empty($MatchPlayers)) {
                    $MatchPlayers = $this->db->query('SELECT P.PlayerGUID,TP.PlayerID,TP.TeamID,SUM(TP.TotalPoints) TotalPoints FROM sports_players P,sports_team_players TP WHERE P.PlayerID = TP.PlayerID AND TP.IsRemoved = "No" AND TP.SeriesID = ' . $Value['SeriesID'] . ' AND TP.RoundNo = ' . $Value['RoundNo'] . '  GROUP BY P.PlayerID')->result_array();
                    $this->cache->memcached->save('getJoinedGamePoints_' . $Value['SeriesID'] . $Value['RoundNo'], $MatchPlayers, 3600);
                }
                $PlayersPointsArr = array_column($MatchPlayers, 'TotalPoints', 'PlayerGUID');
                $PlayersIdsArr = array_column($MatchPlayers, 'PlayerGUID', 'PlayerID');
                $PlayerTeamIdsArr = array_column($MatchPlayers, 'TeamID', 'PlayerID');
                $UserTotalPoints = 0;
                $UserPlayersArr = array();

                /* Update Game Rounds Player Points */
                foreach (json_decode($Value['UserPlayersJSON'], TRUE) as $UserTeamValue) {

                    /* To Check Player Is Exist with same team or not (Will not included in calculation) */
                    if ($UserTeamValue['TeamID'] != $PlayerTeamIdsArr[$UserTeamValue['PlayerID']]) {
                        // continue;
                    }
                    $Points = $PlayersPointsArr[$PlayersIdsArr[$UserTeamValue['PlayerID']]] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']];
                    $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);
                    $this->fantasydb->sports_user_teams_players->updateOne(
                            ['_id' => $Value['UserTeamID'] . $Value['SeriesID'] . $Value['RoundNo'],
                        'UserTeamPlayers.PlayerGUID' => $PlayersIdsArr[$UserTeamValue['PlayerID']]], ['$set' => ['UserTeamPlayers.$.Points' => (float) $Points]]
                    );
                }

                /* Update Game Round Total Points */
                $this->fantasydb->sports_game_rounds_join->updateOne(
                        ['ReferenceID' => $Value['ReferenceID'], 'GameID' => (int) $Value['GameID'],
                    'RoundNo' => (int) $Value['RoundNo']], ['$set' => ['TotalPoints' => (float) $UserTotalPoints]]
                );

                /* Update Game Overall Total Points */
                $GamePointsData = $this->fantasydb->sports_game_rounds_join->aggregate([
                    ['$match' => ['GameID' => (int) $Value['GameID'], 'UserID' => (int) $Value['UserID']]],
                    ['$group' => ['_id' => '', 'TotalPoints' => ['$sum' => '$TotalPoints']]],
                    ['$project' => ['_id' => 0, 'GamePoints' => '$TotalPoints']]]);
                foreach ($GamePointsData as $GameValue) {
                    if (!empty($GameValue['GamePoints'])) {
                        $this->fantasydb->sports_game_join->updateOne(
                                ['_id' => $Value['ReferenceID']], ['$set' => ['TotalPoints' => (float) $GameValue['GamePoints']]]
                        );
                    }
                }

                /* Update Game Points Update Status */
                if ($Value['IsPlayerPointsUpdated'] == 'Yes') {
                    $this->db->where(array('MatchID' => $Value['MatchID'], 'IsGamePointsUpdated' => 'No'));
                    $this->db->limit(1);
                    $this->db->update('sports_matches', array('IsGamePointsUpdated' => 'Yes'));
                }
            }
            $GameIds = array_unique($GameIDArr);

            /* Update Game Round Wise User Rank (MongoDB) */
            foreach ($GameIds as $GameID) {
                foreach (array_unique($RoundNoArr) as $RoundNo) {
                    $GameData = $this->fantasydb->sports_game_rounds_join->find([
                        'GameID' => (int) $GameID, 'RoundNo' => (int) $RoundNo], ['projection' => ['TotalPoints' => 1, 'ReferenceID' => 1],
                        'sort' => ['TotalPoints' => -1]]);
                    $PrevPoint = $PrevRank = 0;
                    $SkippedCount = 1;
                    foreach ($GameData as $GameValue) {
                        if ($PrevPoint != $GameValue['TotalPoints']) {
                            $PrevRank = $PrevRank + $SkippedCount;
                            $PrevPoint = $GameValue['TotalPoints'];
                            $SkippedCount = 1;
                        } else {
                            $SkippedCount++;
                        }
                        $this->fantasydb->sports_game_rounds_join->updateOne(
                                ['ReferenceID' => $GameValue['ReferenceID'], 'GameID' => (int) $GameID,
                            'RoundNo' => (int) $RoundNo], ['$set' => ['UserRank' => (int) $PrevRank]]
                        );
                    }
                }
            }

            /* Update Overall User Rank (MongoDB) */
            foreach ($GameIds as $GameID) {
                $GameData = $this->fantasydb->sports_game_join->find(['GameID' => (int) $GameID], ['projection' => ['TotalPoints' => 1], 'sort' => ['TotalPoints' => -1]]);
                $PrevPoint = $PrevRank = 0;
                $SkippedCount = 1;
                foreach ($GameData as $GameValue) {
                    if ($PrevPoint != $GameValue['TotalPoints']) {
                        $PrevRank = $PrevRank + $SkippedCount;
                        $PrevPoint = $GameValue['TotalPoints'];
                        $SkippedCount = 1;
                    } else {
                        $SkippedCount++;
                    }
                    $this->fantasydb->sports_game_join->updateOne(
                            ['_id' => $GameValue['_id']], ['$set' => ['UserRank' => (int) $PrevRank]]
                    );
                }
            }
        }
    }

    /*
      Description: To transfer joined contest data (MongoDB To MySQL).
     */

    function tranferJoinedContestData($CronID) {
        /* Get Contests Data */
        $Contests = $this->db->query('SELECT C.ContestID FROM football_sports_contest C WHERE C.IsWinningDistributed = "Yes" AND C.LeagueType = "Dfs" AND C.ContestTransferred="No" LIMIT 50');
        if ($Contests->num_rows() > 0) {
            foreach ($Contests->result_array() as $Value) {

                /* Get Joined Contests */
                $ContestCollection = $this->fantasydb->{'Contest_' . $Value['ContestID']};
                $JoinedContestsUsers = $ContestCollection->find(["ContestID" => $Value['ContestID'],
                    "IsWinningAssigned" => "Yes"], ['projection' => ['ContestID' => 1, 'UserID' => 1, 'UserTeamID' => 1,
                        'UserTeamPlayers' => 1, 'TotalPoints' => 1, 'UserRank' => 1,
                        'UserWinningAmount' => 1, 'TaxAmount' => 1, 'SmartPoolWinning' => 1]]);

                if ($ContestCollection->count(["ContestID" => $Value['ContestID'],
                            "IsWinningAssigned" => "Yes"]) == 0) {

                    /* Update Contest Winning Assigned Status */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('football_sports_contest', array('ContestTransferred' => "Yes"));
                    continue;
                }

                foreach ($JoinedContestsUsers as $JC) {

                    /* Update User Team Player Points */
                    $this->db->where(array('UserID' => $JC['UserID'], 'ContestID' => $JC['ContestID'],
                        'UserTeamID' => $JC['UserTeamID']));
                    $this->db->limit(1);
                    $this->db->update('football_sports_contest_join', array('TotalPoints' => $JC['TotalPoints'], 'UserRank' => $JC['UserRank'],
                        'UserWinningAmount' => $JC['UserWinningAmount'],
                        'SmartPoolWinning' => $JC['SmartPoolWinning'], 'ModifiedDate' => date('Y-m-d H:i:s')));

                    /* Update MongoDB Row */
                    $ContestCollection->updateOne(
                            ['_id' => $JC['_id']], ['$set' => ['IsWinningAssigned' => 'Moved']], ['upsert' => false]
                    );
                }
            }
        }
    }

}
