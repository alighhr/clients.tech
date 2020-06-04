<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Contest extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('football/Football_Contest_model', 'Football_Contest_model');
        $this->load->model('football/Football_Sports_model', 'Football_Sports_model');
    }

    /*
      Name: 			add
      Description: 	Use to add contest to system.
      URL: 			/api_admin/contest/add/
     */

    public function add_post() {
        //print_r($this->Post['CustomizeWinning']);exit;
        /* Validation section */
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Hot,Champion,Practice,More,Mega,Smart Pool,Infinity Pool,Winner Takes All,Only For Beginners,Head to Head]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('UnfilledWinningPercent', 'UnfilledWinningPercent', 'trim|in_list[GuranteedPool,Fixed]');
        $this->form_validation->set_rules('WinningRatio', 'Winning Percent', 'trim'.($this->Post['UnfilledWinningPercent'] == 'GuranteedPool' ? '|required':''));
        $this->form_validation->set_rules('WinUpTo', 'Win Up To', 'trim'.($this->Post['UnfilledWinningPercent'] == 'GuranteedPool' ? '|required':''));
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim' . (!empty($this->Post['UnfilledWinningPercent']) && $this->Post['UnfilledWinningPercent'] == 'Fixed' && $this->Post['SmartPool'] == 'No' ? '|required|integer' : ''));
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' && $this->Post['UnfilledWinningPercent'] == 'Fixed' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . ((!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes') && $this->Post['UnfilledWinningPercent'] == 'Fixed' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]' : ''));
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('MatchGUID[]', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('IsVirtualUserJoined', 'Is Virtual User Joined', 'trim');
        $this->form_validation->set_rules('VirtualUserJoinedPercentage', 'Virtual User Joined Percentage', 'trim' . (!empty($this->Post['IsVirtualUserJoined']) && $this->Post['IsVirtualUserJoined'] == 'Yes' ? '|required|integer' : ''));

        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning']) && $this->Post['SmartPool'] == 'No' && $this->Post['UnfilledWinningPercent'] == 'Fixed') {
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

        $all_matches = count($this->input->post('MatchGUID'));
        for ($i = 0; $i < $all_matches; $i++) {
            $this->Post('MatchGUID')[$i];
            $MatchIDs = $this->Entity_model->getEntity('E.EntityID', array('EntityGUID' => $this->Post('MatchGUID')[$i], 'EntityTypeName' => "Matches"));
            $MatchID = $MatchIDs['EntityID'];
            $insert = $this->Football_Contest_model->addContest($this->Post, $this->SessionUserID, $MatchID, $this->SeriesID);
        }

        if (!$insert) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Contest created successfully.";
        }
    }

    /*
      Name: 			edit
      Description: 	Use to update contest to system.
      URL: 			/api_admin/contest/edit/
     */

    public function edit_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateAnyUserJoinedContest[update]');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Hot,Champion,Practice,More,Mega,Smart Pool,Infinity Pool,Winner Takes All,Only For Beginners,Head to Head]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('UnfilledWinningPercent', 'UnfilledWinningPercent', 'trim|in_list[GuranteedPool,Fixed]');
        $this->form_validation->set_rules('WinningRatio', 'Winning Percent', 'trim'.($this->Post['UnfilledWinningPercent'] == 'GuranteedPool' ? '|required':''));
        $this->form_validation->set_rules('WinUpTo', 'Win Up To', 'trim'.($this->Post['UnfilledWinningPercent'] == 'GuranteedPool' ? '|required':''));
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim' . (!empty($this->Post['UnfilledWinningPercent']) && $this->Post['UnfilledWinningPercent'] == 'Fixed' && $this->Post['SmartPool'] == 'No' ? '|required|integer' : ''));
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' && $this->Post['UnfilledWinningPercent'] == 'Fixed' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . ((!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes') && $this->Post['UnfilledWinningPercent'] == 'Fixed' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]' : ''));
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('IsVirtualUserJoined', 'Is Virtual User Joined', 'trim');
        $this->form_validation->set_rules('VirtualUserJoinedPercentage', 'Virtual User Joined Percentage', 'trim' . (!empty($this->Post['IsVirtualUserJoined']) && $this->Post['IsVirtualUserJoined'] == 'Yes' ? '|required|integer' : ''));
        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning']) && $this->Post['SmartPool'] == 'No') {
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

        $this->Football_Contest_model->updateContest($this->Post, $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest updated successfully.";
    }

    /*
      Description: To get joined contests data
     */

    public function getUserJoinedContests_post() {
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal,Head to Head]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contests Data */
        $JoinedContestData = $this->Football_Contest_model->getJoinedContests(@$this->Post['Params'], array_merge($this->Post, array('SessionUserID' => @$this->UserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Description: To get private contest detail
     */

    public function getPrivateContest_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->set_rules('StatusID', 'StatusID', 'trim');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->Football_Contest_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => @$this->MatchID, 'ContestType' => @$this->Post['ContestType'], 'SeriesID' => @$this->SeriesID, 'UserID' => @$this->UserID, 'StatusID' => @$this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData['Data'];
        }
    }

    /*
      Description: To Cancel Contest
     */

    public function cancel_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Cancel Contests */
        $this->Football_Contest_model->cancelContest(@$this->Post, $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest cancelled successfully.";
    }

    /**
     * Function Name: validateAnyUserJoinedContest
     * Description:   To validate if any user joined contest
     */
    public function validateAnyUserJoinedContest($ContestGUID, $Type) {
        $TotalJoinedContest = $this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `football_sports_contest_join` WHERE `ContestID` =' . $this->ContestID)->row()->TotalRecords;
        // if ($TotalJoinedContest > 0){
        // 	$this->form_validation->set_message('validateAnyUserJoinedContest', 'You can not '.$Type.' this contest');
        // 	return FALSE;
        // }
        // else{
        return TRUE;
        // }
    }

    /**
     * Function Name: validateContestStatus
     * Description:   To validate contest status
     */
    public function validateContestStatus($ContestGUID) {
        $ContestData = $this->Football_Contest_model->getContests('Status,IsPaid,SeriesName,ContestName,MatchNo,TeamNameLocal,TeamNameVisitor,EntryFee', array('ContestID' => $this->ContestID));
        if ($ContestData['Status'] == 'Pending') {
            $this->Post['IsPaid'] = $ContestData['IsPaid'];
            $this->Post['EntryFee'] = $ContestData['EntryFee'];
            $this->Post['SeriesName'] = $ContestData['SeriesName'];
            $this->Post['ContestName'] = $ContestData['ContestName'];
            $this->Post['MatchNo'] = $ContestData['MatchNo'];
            $this->Post['TeamNameLocal'] = $ContestData['TeamNameLocal'];
            $this->Post['TeamNameVisitor'] = $ContestData['TeamNameVisitor'];
            return TRUE;
        }
        /* else {
          $this->form_validation->set_message('validateContestStatus', 'You can not cancel this contest.');
          return FALSE;
          } */
    }

    /*
      Description: To get contest winning users
     */

    public function getContestWinningUsers_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->Football_Contest_model->getContestWinningUsers(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => $this->ContestID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData['Data'];
        }
    }

    /*
      Description: 	Use to update contest status.
      URL: 			/api_admin/entity/changeStatus/
     */

    public function changeStatus_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->Entity_model->updateEntityInfo($this->ContestID, array("StatusID" => $this->StatusID));

        $this->Football_Contest_model->updateContest($this->Post, $this->SessionUserID, $this->ContestID);

        $this->Return['Data'] = $this->Football_Contest_model->getContests('Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,SeriesGUID,TeamNameLocal,TeamNameVisitor,SeriesName,CustomizeWinning,ContestType', array_merge($this->Post, array('ContestID' => $this->ContestID, 'SessionUserID' => $this->SessionUserID)));
        $this->Return['Message'] = "Status has been changed.";
    }

}

?>