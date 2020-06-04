<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Contest extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('Settings_model');

        $this->load->model('football/Football_Contest_model', 'Football_Contest_model');
        $this->load->model('football/Football_Sports_model', 'Football_Sports_model');
    }

    /*
      Name: 			add
      Description: 	Use to add contest to system.
      URL: 			/contest/add/
     */

    public function add_post() {

        /* Validation section */
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Hot,Champion,Practice,More,Mega,Smart Pool,Infinity Pool,Winner Takes All,Only For Beginners,Normal,Head to Head]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        // $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim'.(!empty($this->Post['EntryType']) && $this->Post['EntryType']=='Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');

        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['CustomizeWinning']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));

        if ($this->Post['app'] != 'Yes') {
            $this->form_validation->set_rules('percent', 'Customize Winning Percent', 'trim' . (!empty($this->Post['CustomizeWinning']) && $this->Post['ContestFormat'] == 'League' ? '|required' : ''));
        }

        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning']) && $this->Post['UnfilledWinningPercent'] == 'Fixed') {

            $TotalWinners = $TotalPercent = $TotalWinningAmount = 0;
            foreach ($this->Post['CustomizeWinning'] as $Key => $Value) {
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][From]', 'From', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][To]', 'To', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][Percent]', 'Percent', 'trim|required|numeric');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][WinningAmount]', 'WinningAmount', 'trim|required|numeric');
                $TotalWinners += ($Value['To'] - $Value['From']) + 1;
                $TotalPercent += $Value['Percent'];
                $TotalWinningAmount += $TotalWinners * $Value['WinningAmount'];
            }
            /* Check Total No Of Winners */
            if ($TotalWinners != $this->Post['NoOfWinners']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners should be equals to No Of Winners.";
                exit;
            }

            /* Check Total Percent */
            if ($TotalPercent < 100 || $TotalPercent > 100) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners Percent should be 100%.";
                exit;
            }

            /* Check Total Winning Amount */
            if ($TotalWinningAmount != $this->Post['WinningAmount']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winning Amount should be equals to Winning Amount";
                exit;
            }
        }
        $this->form_validation->set_message('regex_match', '{field} value should be between 0 to 100.');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $MatchData = $this->Football_Sports_model->getMatches('MatchStartDateTimeUTC', array('MatchID' => $this->MatchID));
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
        $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
        $MatchStartDateTime = strtotime($MatchData['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
        if ($MatchStartDateTime > $CurrentDateTime) {

            $response = $this->Football_Contest_model->addContest($this->Post, $this->SessionUserID, $this->MatchID, $this->SeriesID);
            if (!$response) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred, please try again later.";
            } else {
                $this->Return['Message'] = "Contest created successfully.";
                $this->Return['TotalTeams'] = $this->db->query('SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . $this->SessionUserID . '" AND MatchID = "' . $this->MatchID . '"')->row()->TotalTeams;
                $this->Return['Data']['ContestGUID'] = $this->Football_Contest_model->getContests('CustomizeWinning,MatchScoreDetails,UserID,ContestFormat,ContestType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,UserInvitationCode', array('ContestID' => $response));
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry! This contest has already started.";
        }
    }

    /*
      Name: 			edit
      Description: 	Use to update contest to system.
      URL: 			/contest/edit/
     */

    public function edit_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateAnyUserJoinedContest[update]');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Hot,Champion,Practice,More,Mega,Smart Pool,Infinity Pool,Winner Takes All,Only For Beginners,Normal,Head to Head]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required|integer');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning'])) {
            $TotalWinners = $TotalPercent = $TotalWinningAmount = 0;
            foreach ($this->Post['CustomizeWinning'] as $Key => $Value) {
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][From]', 'From', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][To]', 'To', 'trim|required|integer');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][Percent]', 'Percent', 'trim|required|numeric');
                $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][WinningAmount]', 'WinningAmount', 'trim|required|numeric');
                $TotalWinners += ($Value['To'] - $Value['From']) + 1;
                $TotalPercent += $Value['Percent'];
                $TotalWinningAmount += $TotalWinners * $Value['WinningAmount'];
            }

            /* Check Total No Of Winners */
            if ($TotalWinners != $this->Post['NoOfWinners']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners should be equals to No Of Winners.";
                exit;
            }

            /* Check Total Percent */
            if ($TotalPercent < 100 || $TotalPercent > 100) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners Percent should be 100%.";
                exit;
            }

            /* Check Total Winning Amount */
            if ($TotalWinningAmount != $this->Post['WinningAmount']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winning Amount should be equals to Winning Amount";
                exit;
            }
        }
        $this->form_validation->set_message('regex_match', '{field} value should be between 0 to 100.');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $MatchData = $this->Football_Sports_model->getMatches('MatchStartDateTimeUTC', array('MatchID' => $this->MatchID));
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
        $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
        $MatchStartDateTime = strtotime($MatchData['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
        if ($MatchStartDateTime > $CurrentDateTime) {
            $this->Football_Contest_model->updateContest($this->Post, $this->SessionUserID, $this->ContestID);
            $this->Return['Message'] = "Contest updated successfully.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry! This match has already started.";
        }
    }

    /*
      Name: 			delete
      Description: 	Use to delete contest to system.
      URL: 			/contest/delete/
     */

    public function delete_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateAnyUserJoinedContest[delete]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Delete Contests Data */
        $this->Football_Contest_model->deleteContest($this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest deleted successfully.";
    }

    /*
      Description: To get contests data
     */

    public function getContests_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|in_list[Yes,No,All]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('GUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->set_rules('StatusID', 'StatusID', 'trim');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('ContestType', 'ContestType', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->Football_Contest_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => @$this->MatchID, 'ContestType' => @$this->Post['ContestType'], 'SeriesID' => @$this->SeriesID, 'UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID, 'StatusID' => @$this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData['Data'];
        }
    }

    /*
      Description: To get contests data
     */

    public function getContestsByType_post() {

        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No,All]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal,Head to Head]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */

        $ContestData = array();

        $ContestTypes[] = array('Key' => 'Mega Contest', 'TagLine' => 'Get ready for mega winnings!', 'Where' => array('ContestType' => 'Mega'));
        $ContestTypes[] = array('Key' => 'Hot Contest', 'TagLine' => 'Filling Fast. Join Now!', 'Where' => array('ContestType' => 'Hot'));
        $ContestTypes[] = array('Key' => 'Infinity Pool', 'TagLine' => 'Unlimited Possiblities', 'Where' => array('ContestType' => 'Infinity Pool'));
        $ContestTypes[] = array('Key' => 'Winner Takes All', 'TagLine' => 'Everything To Play For', 'Where' => array('ContestType' => 'Winner Takes All'));
        $ContestTypes[] = array('Key' => 'Head To Head Contest', 'TagLine' => 'The Ultimate Face Off', 'Where' => array('ContestType' => 'Head to Head'));
        $ContestTypes[] = array('Key' => 'Contests for Champions', 'TagLine' => 'High Entry Fees, Intense Competition', 'Where' => array('ContestType' => 'Champion'));
        $ContestTypes[] = array('Key' => 'Only For Beginners', 'TagLine' => 'Play Your First Contest Now', 'Where' => array('ContestType' => 'Only For Beginners'));
        $ContestTypes[] = array('Key' => 'Practice Contest', 'TagLine' => 'Hone Your Skills', 'Where' => array('ContestType' => 'Practice'));
        $ContestTypes[] = array('Key' => 'More Contest', 'TagLine' => 'Keep Winning!', 'Where' => array('ContestType' => 'More'));
        $ContestTypes[] = array('Key' => 'Smart Pool', 'TagLine' => 'Win Mega Prizes', 'Where' => array('ContestType' => 'Smart Pool'));
        
        if ($this->Post['Privacy'] == 'All') {
            $ContestTypes[] = array('Key' => 'Private Contest', 'TagLine' => 'Play with friends and Family', 'Where' => array('ContestType' => 'Normal'));
        }

        foreach ($ContestTypes as $key => $Contests) {

            array_push($ContestData, $this->Football_Contest_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => @$this->MatchID, 'StatusID' => @$this->StatusID, 'UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID), $Contests['Where']), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize'])['Data']);
            $ContestData[$key]['Key'] = $Contests['Key'];
            $ContestData[$key]['TagLine'] = $Contests['TagLine'];
        }

        $Statics = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `football_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from football_sports_contest_join where football_sports_contest_join.ContestID = C.ContestID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `football_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from football_sports_contest_join where football_sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(DISTINCT(ContestID)) AS `JoinedContest` FROM `football_sports_contest_join` J WHERE J.UserID = "' . $this->SessionUserID . '" AND J.MatchID = "' . $this->MatchID . '" 
                    )as JoinedContest,( 
                    SELECT COUNT(*) AS `TotalTeams` FROM `football_sports_users_teams`WHERE UserID = "' . $this->SessionUserID . '" AND MatchID = "' . $this->MatchID . '"
                ) as TotalTeams,(SELECT COUNT(*) AS `H2HContest` FROM `football_sports_contest` C, `tbl_entity` E, `football_sports_contest_join` CJ WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestFormat="Head to Head" AND E.StatusID = 1 AND C.ContestID = CJ.ContestID AND C.ContestSize != (SELECT COUNT(*) from football_sports_contest_join where football_sports_contest_join.ContestID = C.ContestID )) as H2HContests')->row();

        if (!empty($ContestData)) {
            $this->Return['Data']['Results'] = $ContestData;
            $this->Return['Data']['Statics'] = $Statics;
        }
    }

    /*
      Description: To get contest detail
     */

    public function getContest_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->Football_Contest_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => @$this->ContestID, 'MatchID' => @$this->MatchID, 'SessionUserID' => @$this->SessionUserID)));
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData;
        }
    }

    /*
      Name: 			join
      Description: 	Use to join contest to system.
      URL: 			/contest/join/
     */

    public function join_post() {
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateUserJoinContest');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Join Contests */
        $Contest = $this->Football_Contest_model->getContests('MatchStartDateTimeUTC,UnfilledWinningPercent,WinUpTo,WinningRatio,EntryFee,SmartPool,GameTimeLive', array('StatusID' => array(1, 2), 'ContestID' => $this->ContestID));
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
        $Contest['GameTimeLive'] = 0;
        if ($Contest['GameTimeLive'] > 0) {
            $MatchStartDateTime = strtotime($Contest['MatchStartDateTimeUTC']) - $Contest['GameTimeLive'] * 60;
        } else {
            $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
            $MatchStartDateTime = strtotime($Contest['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
        }
        if ($MatchStartDateTime > $CurrentDateTime) {
            $JoinContest = $this->Football_Contest_model->joinContest(array_merge($this->Post, $Contest), $this->SessionUserID, $this->ContestID, $this->MatchID, $this->UserTeamID);
            if (!$JoinContest) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred, please try again later.";
            } else {
                $this->Return['Data'] = $JoinContest;
                $this->Return['Message'] = "Contest joined successfully.";
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry! This contest has already started.";
        }
    }

    /*
      Name: 			switchTeam
      Description: 	Use to  switch team with joined contest.
      URL: 			/contest/switchTeam/
     */

    public function switchTeam_post() {
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateUserJoinContest');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Join Contests */
        $Contest = $this->Football_Contest_model->getContests('MatchStartDateTimeUTC,GameTimeLive', array('StatusID' => array(1, 2), 'ContestID' => $this->ContestID));
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
        $Contest['GameTimeLive'] = 0;
        if ($Contest['GameTimeLive'] > 0) {
            $MatchStartDateTime = strtotime($Contest['MatchStartDateTimeUTC']) - $Contest['GameTimeLive'] * 60;
        } else {
            $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
            $MatchStartDateTime = strtotime($Contest['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
        }
        if ($MatchStartDateTime > $CurrentDateTime) {
            $JoinContest = $this->Football_Contest_model->joinContest($this->Post, $this->SessionUserID, $this->ContestID, $this->MatchID, $this->UserTeamID);
            if (!$JoinContest) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred, please try again later.";
            } else {
                $this->Return['Message'] = "Contest Switched successfully.";
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry! This contest has already started.";
        }
    }

    /*
      Description: To get joined contests data
     */

    public function getJoinedContests_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal,Head to Head]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Joined Contests Data */
        $JoinedContestData = $this->Football_Contest_model->getJoinedContests(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => @$this->MatchID, 'SessionUserID' => $this->SessionUserID, 'StatusID' => $this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Name: 			addUserTeam
      Description: 	Use to create team to system.
      URL: 			/api_admin/contest/addUserTeam/
     */

    public function addUserTeam_post() {
        /* Validation section */
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Normal,InPlay]');
        $this->form_validation->set_rules('MatchInning', 'MatchInning', 'trim' . (!empty($this->Post['UserTeamType']) && $this->Post['UserTeamType'] == 'InPlay' ? '|required|callback_validateMatchStatusInnings' : ''));
        // $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim|required');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');
        // print_r($this->Post['UserTeamPlayers']);
        // exit;
        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
            $AllPlayersLimit = ($this->Post['UserTeamType'] == 'InPlay') ? 6 : 11;
            $PlayersLimit = ($this->Post['UserTeamType'] == 'InPlay') ? 4 : 9;
            if (count($this->Post['UserTeamPlayers']) != $AllPlayersLimit) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Team Players length should be " . $AllPlayersLimit . ".";
                exit;
            }
            $PlayerPoisitions = array_count_values(array_column($this->Post['UserTeamPlayers'], 'PlayerPosition'));
            if ($PlayerPoisitions['Captain'] != 1 || $PlayerPoisitions['ViceCaptain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select 1 Captain & 1 Vice Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['Captain']) && $PlayerPoisitions['Captain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 1 Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['ViceCaptain']) && $PlayerPoisitions['ViceCaptain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 1 Vice Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['Player']) && $PlayerPoisitions['Player'] != $PlayersLimit) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only " . $PlayersLimit . " Players.";
                exit;
            }
            foreach ($this->Post['UserTeamPlayers'] as $Key => $Value) {
                $this->form_validation->set_rules('UserTeamPlayers[' . $Key . '][PlayerGUID]', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
                $this->form_validation->set_rules('UserTeamPlayers[' . $Key . '][PlayerPosition]', 'PlayerPosition', 'trim|required|in_list[Captain,ViceCaptain,Player]');
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "User Team Players Required.";
            exit;
        }
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */


        foreach ($this->Post['UserTeamPlayers'] as $Key => $Value) {
            $Where = array(
                'MatchID' => $this->MatchID,
                'PlayerID' => $Value['PlayerID']
            );
            $Role = $this->Football_Sports_model->getPlayers('PlayerID,PlayerRole', $Where, FALSE, 0);
            $this->Post['UserTeamPlayers'][$Key]["PlayerRole"] = $Role['PlayerRole'];
        }
        $PlayerRoles = array_count_values(array_column($this->Post['UserTeamPlayers'], 'PlayerRole'));

        /* To validate Goalkeeper */
        if (!isset($PlayerRoles['Goalkeeper'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You have to pick atleast 1 Goalkeeper.";
            exit;
        } else if ($PlayerRoles['Goalkeeper'] > 1) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You can only pick Max 1 Goalkeeper.";
            exit;
        }

        /* To validate Defender */
        if (!isset($PlayerRoles['Defender'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 3 & Max 5 Defender';
            exit;
        } else if ($PlayerRoles['Defender'] < 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 3 Defender';
            exit;
        } else if ($PlayerRoles['Defender'] > 5) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 5 Defender allowed';
            exit;
        }

        /* To validate Forward */
        if (!isset($PlayerRoles['Striker'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 1 & Max 3 Striker';
            exit;
        } else if ($PlayerRoles['Striker'] < 1) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 1 Striker';
            exit;
        } else if ($PlayerRoles['Striker'] > 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 3 Striker allowed';
            exit;
        }

        /* To validate Midfielder */
        if (!isset($PlayerRoles['Midfielder'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 3 & Max 5 Midfielder';
            exit;
        } else if ($PlayerRoles['Midfielder'] < 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 3 Midfielder';
            exit;
        } else if ($PlayerRoles['Midfielder'] > 5) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 5 Midfielder allowed';
            exit;
        }

        $MatchData = $this->Football_Sports_model->getMatches('MatchStartDateTimeUTC', array('MatchID' => $this->MatchID));
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
        $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
        $MatchStartDateTime = strtotime($MatchData['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
        if ($MatchStartDateTime > $CurrentDateTime) {
            $UserTeams = $this->Football_Contest_model->getUserTeams("UserTeamID", array('UserID' => $this->SessionUserID, 'MatchID' => $this->MatchID), TRUE);
            if (!empty($UserTeams)) {
                if ($UserTeams['Data']['TotalRecords'] >= 6) {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = "You can not create more then 6 team on single match.";
                    exit;
                }
                $Flag = false;
                $Uct = 0;
                $AllPlayerList = $this->Post['UserTeamPlayers'];
                foreach ($AllPlayerList as $Key => $Rows) {
                    $PlayerIDs = $this->Football_Sports_model->getPlayers('PlayerID', array('PlayerGUID' => $Rows['PlayerGUID']));
                    $AllPlayerList[$Key]['PlayerID'] = $PlayerIDs['PlayerID'];
                }
                foreach ($UserTeams['Data']['Records'] as $Rows) {
                    if ($Uct != 0) {
                        if ($Flag == false) {
                            break;
                        } else {
                            $Flag = false;
                        }
                    }
                    $Uct++;
                    foreach ($AllPlayerList as $Ply) {
                        $Where = array(
                            'UserTeamID' => $Rows['UserTeamID'],
                            'PlayerID' => $Ply['PlayerID'],
                            'PlayerPosition' => $Ply['PlayerPosition'],
                            'MatchID' => $this->MatchID
                        );
                        $UserTeamPlayer = $this->Football_Contest_model->getUserTeamPlayers("PlayerID", $Where, FALSE);
                        if (empty($UserTeamPlayer)) {
                            $Flag = true;
                        }
                    }
                }
            } else {
                $Flag = true;
            }
            if ($Flag) {
                $UserTeam = $this->Football_Contest_model->addUserTeam($this->Post, $this->SessionUserID, $this->MatchID);
                if (!$UserTeam) {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = "An error occurred, please try again later.";
                } else {
                    $this->Return['Data']['UserTeamGUID'] = $UserTeam;
                    $this->Return['Message'] = "Team created successfully.";
                }
            } else {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You've already created this team. Change your Playing (XI) and/or Captain & Vice-Captain";
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry! This match has already started.";
        }
    }

    public function addUserTeamApp_post() {
        /* Validation section */
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Normal,InPlay]');
        $this->form_validation->set_rules('MatchInning', 'MatchInning', 'trim' . (!empty($this->Post['UserTeamType']) && $this->Post['UserTeamType'] == 'InPlay' ? '|required|callback_validateMatchStatusInnings' : ''));
        // $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim|required');
        // $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');


        $this->Post['UserTeamPlayers'] = json_decode($this->Post['UserTeamPlayers'], true);

        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
            $AllPlayersLimit = ($this->Post['UserTeamType'] == 'InPlay') ? 6 : 11;
            $PlayersLimit = ($this->Post['UserTeamType'] == 'InPlay') ? 4 : 9;
            if (count($this->Post['UserTeamPlayers']) != $AllPlayersLimit) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Team Players length should be " . $AllPlayersLimit . ".";
                exit;
            }
            $PlayerPoisitions = array_count_values(array_column($this->Post['UserTeamPlayers'], 'PlayerPosition'));
            if ($PlayerPoisitions['Captain'] != 1 || $PlayerPoisitions['ViceCaptain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select 1 Captain & 1 Vice Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['Captain']) && $PlayerPoisitions['Captain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 1 Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['ViceCaptain']) && $PlayerPoisitions['ViceCaptain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 1 Vice Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['Player']) && $PlayerPoisitions['Player'] != $PlayersLimit) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only " . $PlayersLimit . " Players.";
                exit;
            }
            foreach ($this->Post['UserTeamPlayers'] as $Key => $Value) {
                $this->form_validation->set_rules('UserTeamPlayers[' . $Key . '][PlayerGUID]', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
                $this->form_validation->set_rules('UserTeamPlayers[' . $Key . '][PlayerPosition]', 'PlayerPosition', 'trim|required|in_list[Captain,ViceCaptain,Player]');
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "User Team Players Required.";
            exit;
        }
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        foreach ($this->Post['UserTeamPlayers'] as $Key => $Value) {
            $Where = array(
                'MatchID' => $this->MatchID,
                'PlayerID' => $Value['PlayerID']
            );
            $Role = $this->Football_Sports_model->getPlayers('PlayerID,PlayerRole', $Where, FALSE, 0);
            $this->Post['UserTeamPlayers'][$Key]["PlayerRole"] = $Role['PlayerRole'];
        }
        $PlayerRoles = array_count_values(array_column($this->Post['UserTeamPlayers'], 'PlayerRole'));

        /* To validate Goalkeeper */
        if (!isset($PlayerRoles['Goalkeeper'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You have to pick atleast 1 Goalkeeper.";
            exit;
        } else if ($PlayerRoles['Goalkeeper'] > 1) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You can only pick Max 1 Goalkeeper.";
            exit;
        }

        /* To validate Defender */
        if (!isset($PlayerRoles['Defender'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 3 & Max 5 Defender';
            exit;
        } else if ($PlayerRoles['Defender'] < 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 3 Defender';
            exit;
        } else if ($PlayerRoles['Defender'] > 5) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 5 Defender allowed';
            exit;
        }

        /* To validate Forward */
        if (!isset($PlayerRoles['Striker'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 1 & Max 3 Striker';
            exit;
        } else if ($PlayerRoles['Striker'] < 1) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 1 Striker';
            exit;
        } else if ($PlayerRoles['Striker'] > 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 3 Striker allowed';
            exit;
        }

        /* To validate Midfielder */
        if (!isset($PlayerRoles['Midfielder'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 3 & Max 5 Midfielder';
            exit;
        } else if ($PlayerRoles['Midfielder'] < 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 3 Midfielder';
            exit;
        } else if ($PlayerRoles['Midfielder'] > 5) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 5 Midfielder allowed';
            exit;
        }

        $MatchData = $this->Football_Sports_model->getMatches('MatchStartDateTimeUTC', array('MatchID' => $this->MatchID));
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
        $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
        $MatchStartDateTime = strtotime($MatchData['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
        if ($MatchStartDateTime > $CurrentDateTime) {
            $UserTeams = $this->Football_Contest_model->getUserTeams("UserTeamID", array('UserID' => $this->SessionUserID, 'MatchID' => $this->MatchID), TRUE);
            if (!empty($UserTeams)) {
                if ($UserTeams['Data']['TotalRecords'] >= 6) {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = "You can not create more then 6 team on single match.";
                    exit;
                }
                $Flag = false;
                $Uct = 0;
                $AllPlayerList = $this->Post['UserTeamPlayers'];
                foreach ($AllPlayerList as $Key => $Rows) {
                    $PlayerIDs = $this->Football_Sports_model->getPlayers('PlayerID', array('PlayerGUID' => $Rows['PlayerGUID']));
                    $AllPlayerList[$Key]['PlayerID'] = $PlayerIDs['PlayerID'];
                }
                foreach ($UserTeams['Data']['Records'] as $Rows) {
                    if ($Uct != 0) {
                        if ($Flag == false) {
                            break;
                        } else {
                            $Flag = false;
                        }
                    }
                    $Uct++;
                    foreach ($AllPlayerList as $Ply) {
                        $Where = array(
                            'UserTeamID' => $Rows['UserTeamID'],
                            'PlayerID' => $Ply['PlayerID'],
                            'PlayerPosition' => $Ply['PlayerPosition'],
                            'MatchID' => $this->MatchID
                        );
                        $UserTeamPlayer = $this->Football_Contest_model->getUserTeamPlayers("PlayerID", $Where, FALSE);
                        if (empty($UserTeamPlayer)) {
                            $Flag = true;
                        }
                    }
                }
            } else {
                $Flag = true;
            }
            if ($Flag) {
                $UserTeam = $this->Football_Contest_model->addUserTeam($this->Post, $this->SessionUserID, $this->MatchID);
                if (!$UserTeam) {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = "An error occurred, please try again later.";
                } else {
                    $this->Return['Data']['UserTeamGUID'] = $UserTeam;
                    $this->Return['Message'] = "Team created successfully.";
                }
            } else {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You've already created this team. Change your Playing (XI) and/or Captain & Vice-Captain";
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry! This match has already started.";
        }
    }

    /*
      Name: 			editUserTeam
      Description: 	Use to update team to system.
      URL: 			/api_admin/contest/editUserTeam/
     */

    public function editUserTeam_post() {
        /* Validation section */
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Normal,InPlay]');
        $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim|required');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');

        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
            if (count($this->Post['UserTeamPlayers']) != 11) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Team Players length should be 11.";
                exit;
            }
            $PlayerPoisitions = array_count_values(array_column($this->Post['UserTeamPlayers'], 'PlayerPosition'));
            if ($PlayerPoisitions['Captain'] != 1 || $PlayerPoisitions['ViceCaptain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select 1 Captain & 1 Vice Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['Captain']) && $PlayerPoisitions['Captain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 1 Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['ViceCaptain']) && $PlayerPoisitions['ViceCaptain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 1 Vice Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['Player']) && $PlayerPoisitions['Player'] != 9) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 9 Players.";
                exit;
            }
            foreach ($this->Post['UserTeamPlayers'] as $Key => $Value) {
                $this->form_validation->set_rules('UserTeamPlayers[' . $Key . '][PlayerGUID]', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
                $this->form_validation->set_rules('UserTeamPlayers[' . $Key . '][PlayerPosition]', 'PlayerPosition', 'trim|required|in_list[Captain,ViceCaptain,Player]');
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "User Team Players Required.";
            exit;
        }
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        foreach ($this->Post['UserTeamPlayers'] as $Key => $Value) {
            $Where = array(
                'MatchID' => $this->MatchID,
                'PlayerID' => $Value['PlayerID']
            );
            $Role = $this->Football_Sports_model->getPlayers('PlayerID,PlayerRole', $Where, FALSE, 0);
            $this->Post['UserTeamPlayers'][$Key]["PlayerRole"] = $Role['PlayerRole'];
        }
        $PlayerRoles = array_count_values(array_column($this->Post['UserTeamPlayers'], 'PlayerRole'));

        /* To validate Goalkeeper */
        if (!isset($PlayerRoles['Goalkeeper'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You have to pick atleast 1 Goalkeeper.";
            exit;
        } else if ($PlayerRoles['Goalkeeper'] > 1) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You can only pick Max 1 Goalkeeper.";
            exit;
        }

        /* To validate Defender */
        if (!isset($PlayerRoles['Defender'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 3 & Max 5 Defender';
            exit;
        } else if ($PlayerRoles['Defender'] < 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 3 Defender';
            exit;
        } else if ($PlayerRoles['Defender'] > 5) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 5 Defender allowed';
            exit;
        }


        /* To validate Forward */
        if (!isset($PlayerRoles['Striker'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 1 & Max 3 Striker';
            exit;
        } else if ($PlayerRoles['Striker'] < 1) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 1 Striker';
            exit;
        } else if ($PlayerRoles['Striker'] > 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 3 Striker allowed';
            exit;
        }

        /* To validate Midfielder */
        if (!isset($PlayerRoles['Midfielder'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'You have to pick Min 3 & Max 5 Midfielder';
            exit;
        } else if ($PlayerRoles['Midfielder'] < 3) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Every team needs atleast 3 Midfielder';
            exit;
        } else if ($PlayerRoles['Midfielder'] > 5) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = 'Max 5 Midfielder allowed';
            exit;
        }

        /** validate Advance or safe Play * */
        /* $AdvanceSalePlay = $this->Football_Contest_model->ValidateAdvanceSafePlay($this->MatchID, $this->SessionUserID, $this->UserTeamID);
          if (!$AdvanceSalePlay) {
          $this->Return['ResponseCode'] = 500;
          $this->Return['Message'] = "Sorry! This team already joined with advance contest please sitch or create new team.Advance contest has already started";
          exit;
          } */

        $MatchData = $this->Football_Sports_model->getMatches('MatchStartDateTimeUTC', array('MatchID' => $this->MatchID));
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
        $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
        $MatchStartDateTime = strtotime($MatchData['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
        if ($MatchStartDateTime > $CurrentDateTime) {
            $UserTeams = $this->Football_Contest_model->getUserTeams("UserTeamID", array('UserID' => $this->SessionUserID, 'MatchID' => $this->MatchID), TRUE);
            if (!empty($UserTeams)) {
                if ($UserTeams['Data']['TotalRecords'] > 6) {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = "You can not create more then 6 team on single match.";
                    exit;
                }
                $Flag = false;
                $Uct = 0;
                $AllPlayerList = $this->Post['UserTeamPlayers'];
                foreach ($AllPlayerList as $Key => $Rows) {
                    $PlayerIDs = $this->Football_Sports_model->getPlayers('PlayerID', array('PlayerGUID' => $Rows['PlayerGUID']));
                    $AllPlayerList[$Key]['PlayerID'] = $PlayerIDs['PlayerID'];
                }
                foreach ($UserTeams['Data']['Records'] as $Rows) {
                    if ($Uct != 0) {
                        if ($Flag == false) {
                            break;
                        } else {
                            $Flag = false;
                        }
                    }
                    $Uct++;
                    foreach ($AllPlayerList as $Ply) {
                        $Where = array(
                            'UserTeamID' => $Rows['UserTeamID'],
                            'PlayerID' => $Ply['PlayerID'],
                            'PlayerPosition' => $Ply['PlayerPosition'],
                            'MatchID' => $this->MatchID
                        );
                        $UserTeamPlayer = $this->Football_Contest_model->getUserTeamPlayers("PlayerID", $Where, FALSE);
                        if (empty($UserTeamPlayer)) {
                            $Flag = true;
                        }
                    }
                }
            } else {
                $Flag = true;
            }
            if ($Flag) {
                if (!$this->Football_Contest_model->editUserTeam($this->Post, $this->UserTeamID)) {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = "An error occurred, please try again later.";
                } else {
                    $this->Football_Contest_model->virtualTeamUpdate($this->Post, $this->UserTeamID);
                    $this->Return['Message'] = "Team updated successfully.";
                }
            } else {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You've already created this team. Change your Playing (XI) and/or Captain & Vice-Captain";
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry! This match has already started.";
        }
    }

    /*
      Description: To get user teams data
     */

    public function getUserTeams_post() {

        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Normal,InPlay,All]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get User Teams Data */
        /* Get User Teams Data */
        if ($this->SessionUserID != $this->UserID) {
            $MatchData = $this->Football_Sports_model->getMatches('MatchStartDateTimeUTC', array('MatchID' => $this->MatchID));
            $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
            $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
            $MatchStartDateTime = strtotime($MatchData['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
            if ($MatchStartDateTime > $CurrentDateTime) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Please wait, Match has not started yet!";
                exit;
            }
        }

        $UserTeamsMongo = array();
        $Contest = $this->Football_Contest_model->getContests('Status', array('ContestID' => @$this->ContestID));
        if (!empty($Contest) && !empty(@$this->ContestID)) {
            if ($Contest['Status'] == 'Pending' || $Contest['Status'] == 'Cancelled') {
                $UserTeams = $this->Football_Contest_model->getUserTeams(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->SessionUserID, 'MatchID' => $this->MatchID, 'UserTeamID' => @$this->UserTeamID, 'TeamsContestID' => @$this->ContestID)), (!empty($this->Post['UserTeamGUID'])) ? FALSE : TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
            } else {
                $UserTeamsMongo = $this->Football_Contest_model->getJoinedContestsUsersTeamMongoDB(array_merge($this->Post, array('UserTeamID' => @$this->UserTeamID, 'UserID' => $this->UserID, 'ContestID' => $this->ContestID)), @$this->Post['PageNo'], @$this->Post['PageSize']);
                if (!$UserTeamsMongo) {
                    $UserTeams = $this->Football_Contest_model->getUserTeams(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->SessionUserID, 'MatchID' => $this->MatchID, 'UserTeamID' => @$this->UserTeamID, 'TeamsContestID' => @$this->ContestID)), (!empty($this->Post['UserTeamGUID'])) ? FALSE : TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
                }
            }
        } else {
            $UserTeams = $this->Football_Contest_model->getUserTeams(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->SessionUserID, 'MatchID' => $this->MatchID, 'UserTeamID' => @$this->UserTeamID, 'TeamsContestID' => @$this->ContestID)), (!empty($this->Post['UserTeamGUID'])) ? FALSE : TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        }
        if (!empty($UserTeamsMongo)) {
            $this->Return['Data'] = $UserTeamsMongo['Data'];
        } else if (!empty($UserTeams)) {
            $this->Return['Data'] = (!empty($this->Post['UserTeamGUID'])) ? $UserTeams : $UserTeams['Data'];
        }
    }

    /*
      Name: 			switchUserTeam
      Description: 	Use to  switch user team with joined contest.
      URL: 			/contest/switchUserTeam/
     */

    public function switchUserTeam_post() {
        $this->form_validation->set_rules('UserTeamGUID[]', 'UserTeamGUID', 'trim|required');
        $this->form_validation->set_rules('OldUserTeamGUID[]', 'OldUserTeamGUID', 'trim|required');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Join Contests */
        $Contest = $this->Football_Contest_model->getContests('MatchStartDateTimeUTC,GameTimeLive', array('StatusID' => array(1, 2), 'ContestID' => $this->ContestID));
        $CurrentDateTime = strtotime(date('Y-m-d H:i:s')); // UTC 
        $Contest['GameTimeLive'] = 0;
        if ($Contest['GameTimeLive'] > 0) {
            $MatchStartDateTime = strtotime($Contest['MatchStartDateTimeUTC']) - $Contest['GameTimeLive'] * 60;
        } else {
            $ClosedInMinutes = $this->Settings_model->getSiteSettings("MatchLiveTime");
            $MatchStartDateTime = strtotime($Contest['MatchStartDateTimeUTC']) - $ClosedInMinutes * 60;
        }
        if ($MatchStartDateTime > $CurrentDateTime) {
            $UserTeamGUID = json_decode($this->Post['UserTeamGUID']);
            $OldUserTeamGUID = json_decode($this->Post['OldUserTeamGUID']);
            foreach ($UserTeamGUID as $key => $Rows) {
                $UserTeamIDNew = $this->Entity_model->getEntity("EntityID", array("EntityGUID" => $Rows, 'EntityTypeName' => 'User Teams'));
                $UserTeamIDOld = $this->Entity_model->getEntity("EntityID", array("EntityGUID" => $OldUserTeamGUID[$key], 'EntityTypeName' => 'User Teams'));
                $this->Football_Contest_model->switchUserTeam($this->SessionUserID, $this->ContestID, $UserTeamIDNew['EntityID'], $UserTeamIDOld['EntityID']);
            }
            $this->Return['Message'] = "Team switched successfully.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Sorry! This match has already started.";
        }
    }

    /**
     * Function Name: validateAnyUserJoinedContest
     * Description:   To validate if any user joined contest
     */
    public function validateAnyUserJoinedContest($ContestGUID, $Type) {
        $TotalJoinedContest = $this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `football_sports_contest_join` WHERE `ContestID` =' . $this->ContestID)->row()->TotalRecords;
        if ($TotalJoinedContest > 0) {
            $this->form_validation->set_message('validateAnyUserJoinedContest', 'You can not ' . $Type . ' this contest');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Function Name: validateMatchStatus
     * Description:   To validate match status
     */
    public function validateMatchStatus($UserTeamGUID) {
        $MatchStatus = $this->db->query("SELECT E.StatusID FROM football_sports_users_teams UT, tbl_entity E WHERE UT.MatchID = E.EntityID AND UT.UserTeamGUID = '" . $UserTeamGUID . "' ")->row()->StatusID;
        if ($MatchStatus != 1) {
            $this->form_validation->set_message('validateMatchStatus', 'Sorry, you can not edit team.');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Function Name: validateContestStatus
     * Description:   To validate contest status
     */
    public function validateContestStatus($ContestGUID) {
        $ContestStatus = $this->db->query("SELECT E.StatusID FROM football_sports_contest C, tbl_entity E WHERE C.ContestID = E.EntityID AND C.ContestGUID = '" . $ContestGUID . "' ")->row()->StatusID;
        if ($ContestStatus != 1) {
            $this->form_validation->set_message('validateContestStatus', 'Sorry, you can not switch team.');
            return FALSE;
        } else {

            /* Validate Old User Team GUID */
            /* $Query = $this->db->query('SELECT UserTeamID FROM sports_users_teams WHERE UserTeamGUID = "' . $this->Post['OldUserTeamGUID'] . '" LIMIT 1');
              if ($Query->num_rows() > 0) {
              $this->OldUserTeamID = $Query->row()->UserTeamID;
              } else {
              $this->form_validation->set_message('validateContestStatus', 'Invalid OldUserTeamGUID.');
              return FALSE;
              } */

            /* To Check If Contest Is Joined With Old Team */
            /* $Where = array('SessionUserID' => $this->SessionUserID, 'ContestID' => $this->ContestID, 'UserTeamID' => $this->OldUserTeamID);
              $Response = $this->Football_Contest_model->getJoinedContests('', $Where, TRUE, 1, 1);
              if (empty($Response['Data']['TotalRecords'])) {
              $this->form_validation->set_message('validateContestStatus', 'You can switch team only with joined contest.');
              return FALSE;
              } */

            /* To Check If Contest Is Already Joined With New Team */
            /* $Response = $this->Football_Contest_model->getJoinedContests('', array('SessionUserID' => $this->SessionUserID, 'ContestID' => $this->ContestID, 'UserTeamID' => $this->UserTeamID), true, 1, 1);
              if (!empty($Response['Data']['TotalRecords'])) {
              $this->form_validation->set_message('validateContestStatus', 'Contest already joined with this team.');
              return FALSE;
              } */

            return TRUE;
        }
    }

    /**
     * Function Name: validateMatchStatusInnings
     * Description:   To validate match status & innings 
     */
    public function validateMatchStatusInnings($MatchInning) {
        if (empty($MatchInning)) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'The MatchInning field is required.');
            return FALSE;
        }
        $MatchData = $this->Football_Sports_model->getMatches('MatchType,Status,MatchScoreDetails', array('MatchID' => $this->MatchID));
        if ($MatchData['Status'] != 'Running' || empty($MatchData['MatchScoreDetails'])) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'You can not create team.');
            return FALSE;
        }
        if ($MatchData['MatchType'] == 'Test' && !in_array($MatchInning, array('First', 'Second', 'Third', 'Fourth'))) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'Match Inning field must be one of: First,Second,Third,Fourth.');
            return FALSE;
        }
        if ($MatchData['MatchType'] != 'Test' && !in_array($MatchInning, array('First', 'Second'))) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'Match Inning field must be one of: First,Second.');
            return FALSE;
        }
        $MatchOvers = ($MatchInning == 'First') ? $MatchData['MatchScoreDetails']['TeamScoreLocal']['Overs'] : $MatchData['MatchScoreDetails']['TeamScoreVisitor']['Overs'];
        $MatchOverBalls = (!empty($MatchOvers)) ? $this->getOverBalls($MatchOvers) : 0; // Over should be between 0.1 To 22.5 (1-137 Balls)
        if ($MatchOverBalls < 1 || $MatchOverBalls > 137) {
            $this->form_validation->set_message('validateMatchStatusInnings', 'You can 0create team between 0.1 to 22.5 overs.');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Function Name: getOverBalls
     * Description:   To get over balls
     */
    public function getOverBalls($TotalOvers) {
        $TotalBalls = 0;
        if (is_float($TotalOvers)) {
            list($Overs, $Balls) = explode('.', $TotalOvers);
            $TotalBalls = ($Overs * 6) + $Balls;
        } else {
            $TotalBalls = $TotalOvers * 6;
        }
        return $TotalBalls;
    }

    /**
     * Function Name: validateUserJoinContest
     * Description:   To validate user join contest
     */
    public function validateUserJoinContest($ContestGUID) {

        $ContestData = $this->Football_Contest_model->getContests('UnfilledWinningPercent,MatchID,ContestSize,Privacy,IsPaid,EntryType,EntryFee,UserInvitationCode,ContestID,ContestType,UserJoinLimit,CashBonusContribution', array('ContestID' => $this->ContestID));

        if (!empty($ContestData)) {
            /* Get Match Status */
            $MatchData = $this->Football_Sports_model->getMatches('MatchType,Status,MatchScoreDetails,MatchGUID', array('MatchID' => $ContestData['MatchID']));
            if ($ContestData['ContestType'] == 'InPlay') {

                /* To check Join Inning Field */
                if (empty($this->Post['JoinInning'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'The JoinInning field is required.');
                    return FALSE;
                }
                if ($MatchData['MatchType'] == 'Test' && !in_array($this->Post['JoinInning'], array('First', 'Second', 'Third', 'Fourth'))) {
                    $this->form_validation->set_message('validateUserJoinContest', 'JoinInning field must be one of: First,Second,Third,Fourth.');
                    return FALSE;
                }
                if ($MatchData['MatchType'] != 'Test' && !in_array($this->Post['JoinInning'], array('First', 'Second'))) {
                    $this->form_validation->set_message('validateUserJoinContest', 'JoinInning field must be one of: First,Second.');
                    return FALSE;
                }
                if ($MatchData['Status'] != 'Running') {
                    $this->form_validation->set_message('validateUserJoinContest', 'You can join only running matches contest.');
                    return FALSE;
                }

                /* To check Over Condition between (0.1 - 22.5) */
                $MatchOvers = ($this->Post['JoinInning'] == 'First') ? $MatchData['MatchScoreDetails']['TeamScoreLocal']['Overs'] : $MatchData['MatchScoreDetails']['TeamScoreVisitor']['Overs'];
                $MatchOverBalls = (!empty($MatchOvers)) ? $this->getOverBalls($MatchOvers) : 0; // Over should be between 0.1 To 22.5 (1-137 Balls)
                if ($MatchOverBalls < 1 || $MatchOverBalls > 137) {
                    $this->form_validation->set_message('validateUserJoinContest', 'You can join contest between 0.1 to 22.5 overs.');
                    return FALSE;
                }

                /* Check Join Contest Size Limit */
                if ($ContestData['UnfilledWinningPercent'] == "Fixed") {
                    if ($this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `football_sports_contest_join` WHERE  `JoinInning` = "' . $this->Post['JoinInning'] . '" AND `ContestID` =' . $ContestData['ContestID'])->row()->TotalRecords >= $ContestData['ContestSize']) {
                        $this->form_validation->set_message('validateUserJoinContest', 'This Contest got full. Please try others to join');
                        return FALSE;
                    }
                }

                /* To Check If Contest Is Already Joined */
                $JoinContestWhere = array('SessionUserID' => $this->SessionUserID, 'ContestID' => $ContestData['ContestID'], 'JoinInning' => $this->Post['JoinInning']);
                if ($ContestData['EntryType'] == 'Multiple') {
                    $JoinContestWhere['UserTeamID'] = $this->UserTeamID;
                }
                $Response = $this->Football_Contest_model->getJoinedContests('', $JoinContestWhere, TRUE, 1, 1);
                if (!empty($Response['Data']['TotalRecords'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Contest is already joined.');
                    return FALSE;
                }

                /* To Check User Team Match Details */
                if (!$this->Football_Contest_model->getUserTeams('', array('UserTeamID' => $this->UserTeamID, 'MatchID' => $ContestData['MatchID'], 'MatchInning' => $this->Post['JoinInning']))) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Invalid UserTeamGUID.');
                    return FALSE;
                }
            } else {

                if ($MatchData['Status'] != 'Pending') {
                    $this->form_validation->set_message('validateUserJoinContest', 'You can join only upcoming matches contest.');
                    return FALSE;
                }

                /* Check Join Contest Size Limit */
                if ($ContestData['UnfilledWinningPercent'] == "Fixed") {
                    if ($this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `football_sports_contest_join` WHERE `ContestID` =' . $ContestData['ContestID'])->row()->TotalRecords >= $ContestData['ContestSize']) {
                        $this->form_validation->set_message('validateUserJoinContest', 'This Contest got full. Please try others to join');
                        return FALSE;
                    }
                }

                /* To Check If Contest Is Already Joined */
                $JoinContestWhere = array('SessionUserID' => $this->SessionUserID, 'ContestID' => $ContestData['ContestID']);
                if ($ContestData['EntryType'] == 'Multiple') {

                    /* Get User Join Limit */
                    if ($this->db->query('SELECT COUNT(*) AS `TotalJoined` FROM `football_sports_contest_join` WHERE `ContestID` =' . $ContestData['ContestID'] . ' AND UserID = ' . $this->SessionUserID)->row()->TotalJoined >= $ContestData['UserJoinLimit']) {
                        $this->form_validation->set_message('validateUserJoinContest', 'You can join this contest only ' . $ContestData['UserJoinLimit'] . ' times.');
                        return FALSE;
                    }


                    $JoinContestWhere['UserTeamID'] = $this->UserTeamID;
                }
                $Response = $this->Football_Contest_model->getJoinedContests('', $JoinContestWhere, TRUE, 1, 1);
                if (!empty($Response['Data']['TotalRecords'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Contest is already joined.');
                    return FALSE;
                }

                /* To Check User Team Match Details */
                if (!$this->Football_Contest_model->getUserTeams('', array('UserTeamID' => $this->UserTeamID, 'MatchID' => $ContestData['MatchID']))) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Invalid UserTeamGUID.');
                    return FALSE;
                }

                /* To Check Contest Privacy */
                /* if ($ContestData['Privacy'] == 'Yes') {
                  if (empty($this->Post['UserInvitationCode'])) {
                  $this->form_validation->set_message('validateUserJoinContest', 'The User Invitation Code field is required.');
                  return FALSE;
                  }
                  if ($ContestData['UserInvitationCode'] != $this->Post['UserInvitationCode']) {
                  $this->form_validation->set_message('validateUserJoinContest', 'Invalid User Invitation Code.');
                  return FALSE;
                  }
                  } */
            }

            /* To Check Wallet Amount, If Contest Is Paid */
            if ($ContestData['IsPaid'] == 'Yes') {
                $this->load->model('Users_model');
                $UserData = $this->Users_model->getUsers('TotalCash,WalletAmount,WinningAmount,CashBonus', array('UserID' => $this->SessionUserID));
                $this->Post['WalletAmount'] = $UserData['WalletAmount'];
                $this->Post['WinningAmount'] = $UserData['WinningAmount'];
                $this->Post['CashBonus'] = $UserData['CashBonus'];

                $ContestEntryRemainingFees = @$ContestData['EntryFee'];
                $CashBonusContribution = @$ContestData['CashBonusContribution'];
                $WalletAmountDeduction = 0;
                $WinningAmountDeduction = 0;
                $CashBonusDeduction = 0;
                if (!empty($CashBonusContribution) && @$UserData['CashBonus'] > 0) {
                    $CashBonusContributionAmount = $ContestEntryRemainingFees * ($CashBonusContribution / 100);
                    if (@$UserData['CashBonus'] >= $CashBonusContributionAmount) {
                        $CashBonusDeduction = $CashBonusContributionAmount;
                    } else {
                        $CashBonusDeduction = @$UserData['CashBonus'];
                    }
                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $CashBonusDeduction;
                }
                if ($ContestEntryRemainingFees > 0 && @$UserData['WinningAmount'] > 0) {
                    if (@$UserData['WinningAmount'] >= $ContestEntryRemainingFees) {
                        $WinningAmountDeduction = $ContestEntryRemainingFees;
                    } else {
                        $WinningAmountDeduction = @$UserData['WinningAmount'];
                    }
                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WinningAmountDeduction;
                }
                if ($ContestEntryRemainingFees > 0 && @$UserData['WalletAmount'] > 0) {
                    if (@$UserData['WalletAmount'] >= $ContestEntryRemainingFees) {
                        $WalletAmountDeduction = $ContestEntryRemainingFees;
                    } else {
                        $WalletAmountDeduction = @$UserData['WalletAmount'];
                    }
                    $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WalletAmountDeduction;
                }
                if ($ContestEntryRemainingFees > 0) {
                    $this->Return['Data']['RemainingFee'] = $ContestEntryRemainingFees;
                    $this->form_validation->set_message('validateUserJoinContest', 'Insufficient wallet amount.');
                    return FALSE;
                }
            }
            $this->Post['IsPaid'] = $ContestData['IsPaid'];
            $this->Post['EntryFee'] = $ContestData['EntryFee'];
            $this->Post['CashBonusContribution'] = $ContestData['CashBonusContribution'];
            return TRUE;
        } else {
            $this->form_validation->set_message('validateUserJoinContest', 'Invalid ContestGUID.');
            return FALSE;
        }
    }

    /*
      Function Name : getPrivateContest
      Description : To get private contest by contest code
     */

    public function getPrivateContest_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('UserInvitationCode', 'UserInvitationCode', 'trim|required|callback_validateContestInviteCode');
        $this->form_validation->validation($this);  /* Run validation */

        $ContestData = $this->Football_Contest_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('UserInvitationCode' => @$this->Post['UserInvitationCode'], "MatchID" => $this->MatchID)), FALSE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData;
        }
    }

    /*
      Description: To get joined contest users data
     */

    public function getJoinedContestsUsers_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');

        $this->form_validation->validation($this);  /* Run validation */

        /* Get Joined Contest Users Data */
        $Contest = $this->Football_Contest_model->getContests('Status', array('ContestID' => $this->ContestID));

        if ($Contest['Status'] == 'Pending' || $Contest['Status'] == 'Cancelled') {

            $JoinedContestData = $this->Football_Contest_model->getJoinedContestsUsersCache(@$this->Post['Params'], array('UserID' => $this->SessionUserID, 'MatchID' => $this->MatchID, 'ContestID' => $this->ContestID), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        } else {
            /* Get Joined Contest Users Data (MongoDB) */
            $JoinedContestData = $this->Football_Contest_model->getJoinedContestsUsersMongoDB(array_merge($this->Post, array('UserID' => $this->SessionUserID, 'MatchID' => @$this->MatchID, 'ContestID' => $this->ContestID)), @$this->Post['PageNo'], @$this->Post['PageSize']);

            if (!$JoinedContestData) {
                $JoinedContestData = $this->Football_Contest_model->getJoinedContestsUsersCache(@$this->Post['Params'], array('UserID' => $this->SessionUserID, 'MatchID' => $this->MatchID, 'ContestID' => $this->ContestID), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
            }
        }


        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Description : To create winners breakout
     */

    public function WinningBreakups_post() {
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'required|trim|callback_validateContestWinningAmount');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'required|trim|numeric|callback_validateContestSize');
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'required|trim');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'required|trim|in_list[Yes,No]');
        $this->form_validation->validation($this);  /* Run validation */

        $Result = $this->Football_Contest_model->getWinningBreakup('', array_merge($this->Post, array('MatchID' => $this->MatchID, 'UserID' => $this->UserID)), TRUE, 0);
        if ($Result) {
            $this->Return['Data'] = $Result['Data'];
            $this->Return['Message'] = "Winning Breakup successfully.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        }
    }

    /*
      Description: To download contest teams
     */

    public function downloadTeams_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]|callback_validateContestMatchStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Teams Data */
        $ContestTeamsData = $this->Football_Contest_model->downloadTeams(array_merge($this->Post, array('ContestID' => $this->ContestID, 'MatchID' => $this->MatchID)));

        // print_r($ContestTeamsData); exit();
        if (!empty($ContestTeamsData)) {
            $this->Return['Data'] = $ContestTeamsData;
        }
    }

    /* Description : To validate contest match status */

    public function validateContestMatchStatus() {

        /* Get Match Status */
        $MatchData = $this->Football_Sports_model->getMatches('Status', array('MatchID' => @$this->MatchID));
        if (!in_array($MatchData['Status'], array('Running', 'Completed'))) {
            $this->form_validation->set_message('validateContestMatchStatus', 'You can download teams only for running & completed matches.');
            return FALSE;
        }

        /* Get Total Joined Teams Count */
        $TotalJoined = $this->db->query('SELECT COUNT(ContestID) AS `TotalJoined` FROM `football_sports_contest_join` WHERE `ContestID` =' . @$this->ContestID)->row()->TotalJoined;
        if ($TotalJoined == 0) {
            $this->form_validation->set_message('validateContestMatchStatus', 'No one has joined this contest.');
            return FALSE;
        }
        return TRUE;
    }

    public function validateContestInviteCode() {

        /** check invite code * */
        $ContestID = $this->db->query("SELECT ContestID FROM `football_sports_contest` WHERE `UserInvitationCode` ='" . $this->Post['UserInvitationCode'] . "'")->row()->ContestID;
        if (!$ContestID) {
            $this->form_validation->set_message('validateContestInviteCode', 'Invalid contest code.');
            return FALSE;
        }
        $ContestID = $this->db->query("SELECT ContestID FROM `football_sports_contest` WHERE `UserInvitationCode` ='" . $this->Post['UserInvitationCode'] . "' AND `MatchID` ='" . @$this->MatchID . "'")->row()->ContestID;
        if (!$ContestID) {
            $this->form_validation->set_message('validateContestInviteCode', 'This contest code not valid for this match.');
            return FALSE;
        }
        return TRUE;
    }

    /* Description : To validate contest size */

    public function validateContestSize() {
        if ($this->Post['ContestSize'] < 2) {
            $this->form_validation->set_message('validateContestSize', 'Why play alone? Need atleast 2 members!');
            return FALSE;
        }
        // else if ($this->Post['ContestSize'] > 100) {
        //     $this->form_validation->set_message('validateContestSize', 'Contest size cannot exceed 100 members!');
        //     return FALSE;
        // }
        return TRUE;
    }

    /* Description : To validate contest winning amount */

    public function validateContestWinningAmount() {
        // if ($this->Post['WinningAmount'] > 10000) {
        //     $this->form_validation->set_message('validateContestSize', 'Winning amount cannot exceed 10000');
        //     return FALSE;
        // }
        return TRUE;
    }

}

?>