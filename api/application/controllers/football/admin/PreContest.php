<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class PreContest extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('football/Football_PreContest_model', 'Football_PreContest_model');
        $this->load->model('football/Football_Contest_model', 'Football_Contest_model');
        $this->load->model('football/Football_Sports_model', 'Football_Sports_model');
    }

    /*
      Name: 			add
      Description: 	Use to add contest to system.
      URL: 			/api_admin/contest/add/
     */

    public function add_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|in_list[Hot,Champion,Practice,More,Mega,Smart Pool,Infinity Pool,Winner Takes All,Only For Beginners]' : ''));
        $this->form_validation->set_rules('UnfilledWinningPercent', 'UnfilledWinningPercent', 'trim|in_list[GuranteedPool,Fixed]');
        $this->form_validation->set_rules('WinningRatio', 'Winning Percent', 'trim' . ($this->Post['UnfilledWinningPercent'] == 'GuranteedPool' ? '|required' : ''));
        $this->form_validation->set_rules('WinUpTo', 'Win Up To', 'trim' . ($this->Post['UnfilledWinningPercent'] == 'GuranteedPool' ? '|required' : ''));
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsAutoCreate', 'Is Auto Create', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim' . ($this->Post['UnfilledWinningPercent'] == 'Fixed' && $this->Post['SmartPool'] == 'No' ? '|required|integer' : ''));
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' && $this->Post['UnfilledWinningPercent'] == 'Fixed' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' && $this->Post['UnfilledWinningPercent'] == 'Fixed' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]' : ''));
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('IsVirtualUserJoined', 'Is Virtual User Joined', 'trim');
        $this->form_validation->set_rules('VirtualUserJoinedPercentage', 'Virtual User Joined Percentage', 'trim' . (!empty($this->Post['IsVirtualUserJoined']) && $this->Post['IsVirtualUserJoined'] == 'Yes' ? '|required|integer' : ''));

        if (!empty($this->Post['CustomizeWinning']) && is_array($this->Post['CustomizeWinning']) && $this->Post['SmartPool'] == 'No' && $this->Post['UnfilledWinningPercent'] == 'Fixed') {
            if ($this->Post['IsPaid'] == 'Yes') {
                $TotalWinners = $TotalPercent = $TotalWinningAmount = 0;
                foreach ($this->Post['CustomizeWinning'] as $Key => $Value) {
                    $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][From]', 'From', 'trim|required|integer');
                    $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][To]', 'To', 'trim|required|integer');
                    $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][Percent]', 'Percent', 'trim|required|numeric');
                    $this->form_validation->set_rules('CustomizeWinning[' . $Key . '][WinningAmount]', 'WinningAmount', 'trim|required|numeric');
                    $TotalWinners += ($Value['To'] - $Value['From']) + 1;
                    $TotalPercent += $Value['Percent'];
                    $TotalWinningAmount += (($Value['To'] - $Value['From']) + 1) * $Value['WinningAmount'];
                }

                /* Check Total No Of Winners */
                if ($TotalWinners != $this->Post['NoOfWinners']) {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = "Customize Winners should be equals to No Of Winners.";
                    exit;
                }
            }
        }

        $this->form_validation->set_message('regex_match', '{field} value should be between 0 to 100.');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $Insert = $this->Football_PreContest_model->addContest($this->Post, $this->SessionUserID);

        if (!$Insert) {
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
        $this->form_validation->set_rules('PreContestID', 'PreContestID', 'trim|required');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[Head to Head,League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|in_list[Hot,Champion,Practice,More,Mega,Smart Pool,Infinity Pool,Winner Takes All,Only For Beginners]' : ''));
        $this->form_validation->set_rules('UnfilledWinningPercent', 'UnfilledWinningPercent', 'trim|in_list[GuranteedPool,Fixed]');
        $this->form_validation->set_rules('WinningRatio', 'Winning Percent', 'trim' . ($this->Post['UnfilledWinningPercent'] == 'GuranteedPool' ? '|required' : ''));
        $this->form_validation->set_rules('WinUpTo', 'Win Up To', 'trim' . ($this->Post['UnfilledWinningPercent'] == 'GuranteedPool' ? '|required' : ''));
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsAutoCreate', 'Is Auto Create', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim' . ($this->Post['UnfilledWinningPercent'] == 'Fixed' && $this->Post['SmartPool'] == 'No' ? '|required|integer' : ''));
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' && $this->Post['UnfilledWinningPercent'] == 'Fixed' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' && $this->Post['UnfilledWinningPercent'] == 'Fixed' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single,Multiple]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]' : ''));
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
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
                $TotalWinningAmount += (($Value['To'] - $Value['From']) + 1) * $Value['WinningAmount'];
            }
            /* Check Total No Of Winners */
            if ($TotalWinners != $this->Post['NoOfWinners']) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Customize Winners should be equals to No Of Winners.";
                exit;
            }
        }

        $this->form_validation->set_message('regex_match', '{field} value should be between 0 to 100.');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->Football_PreContest_model->updateContest($this->Post, $this->SessionUserID, $this->PreContestID);
        $this->Return['Message'] = "Contest updated successfully.";
    }

    /*
      Description: To get private contest detail
     */

    public function getPrivateContest_post() {
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->Football_Contest_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->UserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
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
        $this->Football_Contest_model->cancelContest(@$this->Post, $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest cancelled successfully.";
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
        } else {
            $this->form_validation->set_message('validateContestStatus', 'You can not cancel this contest.');
            return FALSE;
        }
    }

    /*
      Description: To get contests data
     */

    public function getPreContestByType_post() {

        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No,All]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        //$this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */

        $ContestData = array();

        $ContestTypes[] = array('Key' => 'Hot Contest', 'TagLine' => 'Filling Fast. Join Now!', 'Where' => array('ContestType' => 'Hot'));
        $ContestTypes[] = array('Key' => 'Contests for Champions', 'TagLine' => 'High Entry Fees, Intense Competition', 'Where' => array('ContestType' => 'Champion'));
        $ContestTypes[] = array('Key' => 'Head To Head Contest', 'TagLine' => 'The Ultimate Face Off', 'Where' => array('ContestType' => 'Head to Head'));
        $ContestTypes[] = array('Key' => 'Practice Contest', 'TagLine' => 'Hone Your Skills', 'Where' => array('ContestType' => 'Practice'));
        $ContestTypes[] = array('Key' => 'More Contest', 'TagLine' => 'Keep Winning!', 'Where' => array('ContestType' => 'More'));
        $ContestTypes[] = array('Key' => 'Mega Contest', 'TagLine' => 'Get ready for mega winnings!', 'Where' => array('ContestType' => 'Mega'));
        $ContestTypes[] = array('Key' => 'Winner Takes All', 'TagLine' => 'Everything To Play For', 'Where' => array('ContestType' => 'Winner Takes All'));
        $ContestTypes[] = array('Key' => 'Only For Beginners', 'TagLine' => 'Play Your First Contest Now', 'Where' => array('ContestType' => 'Only For Beginners'));

        foreach ($ContestTypes as $key => $Contests) {

            array_push($ContestData, $this->Football_PreContest_model->getPreContest(@$this->Post['Params'], array_merge($this->Post, array('UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID), $Contests['Where']), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize'])['Data']);
            $ContestData[$key]['Key'] = $Contests['Key'];
            $ContestData[$key]['TagLine'] = $Contests['TagLine'];
        }

        $Statics = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `football_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from football_sports_contest_join where football_sports_contest_join.ContestID = C.ContestID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `football_sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from football_sports_contest_join where football_sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(*) AS `JoinedContest` FROM `football_sports_contest_join` J, `football_sports_contest` C, `tbl_entity` E WHERE C.ContestID = J.ContestID AND C.ContestID = E.EntityID AND E.StatusID != 3 AND J.UserID = "' . $this->SessionUserID . '" AND C.MatchID = "' . $this->MatchID . '" 
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

    public function getPreContest_post() {
        $this->form_validation->set_rules('PreContestID', 'PreContestID', 'trim');
        //$this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->Football_PreContest_model->getPreContest(@$this->Post['Params'], array_merge($this->Post, array('PreContestID' => @$this->Post['PreContestID'], 'SessionUserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData['Data'];
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
        $this->Return['Data'] = $this->Football_Contest_model->getContests('Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,SeriesGUID,TeamNameLocal,TeamNameVisitor,SeriesName,CustomizeWinning,ContestType', array_merge($this->Post, array('ContestID' => $this->ContestID, 'SessionUserID' => $this->SessionUserID)));
        $this->Return['Message'] = "Status has been changed.";
    }

    /*
      Name: 			delete
      Description: 	Use to delete contest to system.
      URL: 			/contest/delete/
     */

    public function delete_post() {
        $this->form_validation->set_rules('PreContestID', 'PreContestID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* Delete Contests Data */

        $this->Football_PreContest_model->deleteContest($this->SessionUserID, $this->Post['PreContestID']);
        $this->Return['Message'] = "Contest deleted successfully.";
    }

    /*
      Name: 			deleteUpcomingPreContest
      Description: 	Use to delete contest to system.
      URL: 			/contest/deleteUpcomingPreContest/
     */

    public function deleteUpcomingPreContest_post() {
        $this->form_validation->set_rules('PreContestID', 'PreContestID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* Delete Contests Data */

        $this->Football_PreContest_model->deleteUpcomingPreContest($this->SessionUserID, $this->Post['PreContestID']);
        $this->Return['Message'] = "Contest deleted successfully.";
    }

}

?>