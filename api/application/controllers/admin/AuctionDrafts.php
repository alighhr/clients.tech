<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class AuctionDrafts extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('AuctionDrafts_model');
        $this->load->model('Sports_model');
    }

    /*
      Name: 			add
      Description: 	Use to add contest to system.
      URL: 			/api_admin/contest/add/
     */

    public function add_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('LeagueType', 'LeagueType', 'trim|required|in_list[Draft,Auction]');
        $this->form_validation->set_rules('MinimumUserJoined', 'MinimumUserJoined', 'trim|required|numeric');
        $this->form_validation->set_rules('LeagueJoinDateTime', 'LeagueJoinDateTime', 'trim|required');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal,Reverse,InPlay,Hot,Champion,Practice,More]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required|integer');
        $this->form_validation->set_rules('DraftTeamPlayerLimit', 'DraftTeamPlayerLimit', 'trim'.($this->Post['LeagueType'] == 'Draft' ? '|required|integer' : ''));
        //$this->form_validation->set_rules('DraftPlayerSelectionCriteria', 'DraftPlayerSelectionCriteria', 'trim|required');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('AdminPercent', 'AdminPercent', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]' : ''));
        //$this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('MatchStartDate', 'MatchStartDate', 'trim|required');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');

        

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

        /* validation up to 2 hours before the match start */
        $ExtendDate = date('Y-m-d H:i:s', strtotime('+5 hours', strtotime($this->Post['LeagueJoinDateTime'])));
        
        if($ExtendDate > $this->Post['MatchStartDate']){
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You can create contest up to 5 hours before the series first match start. After that you can not create contest.";
            exit;
        }
        $TotalPlayer = 0;
        
        if (!empty($this->Post['DraftPlayerSelectionCriteria'])) {
            $DraftPlayerSelectionCriteria = array(
                "Wk" => $this->Post['DraftPlayerSelectionCriteria'][0],
                "Bat" => $this->Post['DraftPlayerSelectionCriteria'][1],
                "Ar" => $this->Post['DraftPlayerSelectionCriteria'][2],
                "Bowl" => $this->Post['DraftPlayerSelectionCriteria'][3],
            );
            $TotalPlayer = $this->Post['DraftPlayerSelectionCriteria'][0] + $this->Post['DraftPlayerSelectionCriteria'][1] + $this->Post['DraftPlayerSelectionCriteria'][2] + $this->Post['DraftPlayerSelectionCriteria'][3];
        }
        if ($TotalPlayer > $this->Post['DraftTeamPlayerLimit']) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Draft Team Player Limit Can Not Be Less Then Total No. of Player Draft Player Selection Criteria.";
            exit;
        }
        if (!$this->AuctionDrafts_model->addContest(array_merge($this->Post, array("DraftTeamPlayerLimit" => $this->Post['DraftTeamPlayerLimit'], "DraftPlayerSelectionCriteria" => json_encode($DraftPlayerSelectionCriteria))), $this->SessionUserID, $this->MatchID, $this->Post['RoundID'])) {
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
        $this->form_validation->set_rules('MinimumUserJoined', 'MinimumUserJoined', 'trim|required|numeric');
        $this->form_validation->set_rules('LeagueJoinDateTime', 'LeagueJoinDateTime', 'trim|required');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal,Reverse,InPlay,Hot,Champion,Practice,More]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required|integer');
        $this->form_validation->set_rules('DraftTeamPlayerLimit', 'DraftTeamPlayerLimit', 'trim'.($this->Post['LeagueType'] == 'Draft' ? '|required|integer' : ''));
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]' : ''));
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
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
        $TotalPlayer = 0;
        if (!empty($this->Post['DraftPlayerSelectionCriteria'])) {
            $AllPlayers = json_decode($this->Post['DraftPlayerSelectionCriteria'] , TRUE);
            $TotalPlayer = $AllPlayers['Wk'] + $AllPlayers['Bat'] + $AllPlayers['Bowl'] + $AllPlayers['Ar'];
        }
        if ($TotalPlayer > $this->Post['DraftTeamPlayerLimit']) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Draft Team Player Limit Can Not Be Less Then Total No. of Player Draft Player Selection Criteria.";
            exit;
        }
        $this->AuctionDrafts_model->updateContest(array_merge($this->Post, array("DraftTeamPlayerLimit" => $this->Post['DraftTeamPlayerLimit'], "DraftPlayerSelectionCriteria" => $this->Post['DraftPlayerSelectionCriteria'])), $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest updated successfully.";
    }

    /*
      Description: To get joined contests data
     */

    public function getUserJoinedContests_post() {
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contests Data */
        $JoinedContestData = $this->AuctionDrafts_model->getJoinedContests(@$this->Post['Params'], array_merge($this->Post, array('SessionUserID' => @$this->UserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Description: To get private contest detail
     */

    public function getPrivateContest_post() {
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->AuctionDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->UserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData['Data'];
        }
    }

    /*
      Description: To Cancel Contest
     */

    public function cancel_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateContestStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Cancel Contests */
        $this->AuctionDrafts_model->cancelContest(@$this->Post, $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest cancelled successfully.";
    }

    /**
     * Function Name: validateAnyUserJoinedContest
     * Description:   To validate if any user joined contest
     */
    public function validateAnyUserJoinedContest($ContestGUID, $Type) {
        $TotalJoinedContest = $this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `sports_contest_join` WHERE `ContestID` =' . $this->ContestID)->row()->TotalRecords;
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
        $ContestData = $this->AuctionDrafts_model->getContests('Status,IsPaid,SeriesName,ContestName,MatchNo,TeamNameLocal,TeamNameVisitor,EntryFee', array('ContestID' => $this->ContestID));
        if ($ContestData['Status'] == 'Pending') {
            $this->Post['IsPaid'] = $ContestData['IsPaid'];
            $this->Post['EntryFee'] = $ContestData['EntryFee'];
            $this->Post['SeriesName'] = $ContestData['SeriesName'];
            $this->Post['ContestName'] = $ContestData['ContestName'];
            $this->Post['MatchNo'] = $ContestData['MatchNo'];
            $this->Post['TeamNameLocal'] = $ContestData['TeamNameLocal'];
            $this->Post['TeamNameVisitor'] = $ContestData['TeamNameVisitor'];
            return TRUE;
        } else {
            $this->form_validation->set_message('validateContestStatus', 'You can not cancel this contest.');
            return FALSE;
        }
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
        $WinningUsersData = $this->AuctionDrafts_model->getContestWinningUsers(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => $this->ContestID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
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
        //$this->Entity_model->updateEntityInfo($this->ContestID, array("StatusID" => $this->StatusID));
        $this->AuctionDrafts_model->ChangeStatus($this->ContestID, array("StatusID" => $this->StatusID));
        $this->Return['Data'] = $this->AuctionDrafts_model->getContests('SeriesName,LeagueJoinDateTime,LeagueType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,SeriesGUID,TeamNameLocal,TeamNameVisitor,SeriesName,CustomizeWinning,ContestType', array_merge($this->Post, array('ContestID' => $this->ContestID, 'SessionUserID' => $this->SessionUserID)));
        $this->Return['Message'] = "Status has been changed.";
    }

}

?>