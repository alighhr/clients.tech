<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class AuctionDrafts extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('AuctionDrafts_model');
        $this->load->model('Sports_model');
        $this->load->model('Users_model');
        $RoundName = '';
    }

    public function getMatchBestPlayers_post() {
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');

        $this->form_validation->validation($this);  /* Run validation */

        $BestTeamData = $this->AuctionDrafts_model->getAuctionBestPlayers(array('SeriesID' => $this->Post['SeriesID'], 'RoundID' => $this->Post['RoundID'], 'UserID' => $this->SessionUserID), FALSE);
        if (!empty($BestTeamData)) {
            $this->Return['Data'] = $BestTeamData['Data'];
        }
    }

    public function downloadSnakeAuctionTeams_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required|callback_validateContestSeriesStatus[RoundName]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Teams Data */
        $ContestTeamsData = $this->AuctionDrafts_model->downloadSnakeAuctionTeams(array_merge($this->Post, array('ContestID' => $this->ContestID, 'RoundName' => $this->RoundName)));
        // print_r($ContestTeamsData); exit();
        if (!empty($ContestTeamsData)) {
            $this->Return['Data'] = $ContestTeamsData;
        }
    }

    /* Description : To validate contest match status */

    public function validateContestSeriesStatus() {

        /* Get Match Status */
        $SeriesData = $this->db->query('SELECT AuctionDraftStatusID, RoundName FROM `sports_series_rounds` WHERE `SeriesID` =' . $this->Post['SeriesID'] . ' AND RoundID = ' . $this->Post['RoundID'])->row();

        if (!in_array($SeriesData->AuctionDraftStatusID, array('2', '5'))) {
            $this->form_validation->set_message('validateContestMatchStatus', 'You can download teams only for running & completed matches.');
            return FALSE;
        }

        /* Get Total Joined Teams Count */
        $TotalJoined = $this->db->query('SELECT COUNT(ContestID) AS `TotalJoined` FROM `sports_contest_join` WHERE `ContestID` =' . @$this->ContestID)->row()->TotalJoined;

        if ($TotalJoined == 0) {
            $this->form_validation->set_message('validateContestMatchStatus', 'No one has joined this contest.');
            return FALSE;
        }
        $this->RoundName = $SeriesData->RoundName;
        return TRUE;
    }

    public function InviteContest_post() {
        /* Validation section */
        $this->form_validation->set_rules('ReferType', 'Refer Type', 'trim|required|in_list[Phone,Email]');
        $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim' . (!empty($this->Post['ReferType']) && $this->Post['ReferType'] == 'Phone' ? '|required' : ''));
        $this->form_validation->set_rules('Email', 'Email', 'trim' . (!empty($this->Post['ReferType']) && $this->Post['ReferType'] == 'Email' ? '|required|valid_email' : ''));
        $this->form_validation->set_rules('InviteCode', 'InviteCode', 'trim|required');

        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $this->AuctionDrafts_model->InviteContest($this->Post, $this->SessionUserID);
        $this->Return['Message'] = "Successfully invited.";
    }

    /*
      Description: To get series data
     */

    public function getSeries_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        $SeriesData = $this->AuctionDrafts_model->getSeries(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID, 'AuctionDraftStatusID' => $this->StatusID, 'SessionUserID' => $this->SessionUserID) : array('AuctionDraftStatusID' => $this->StatusID, 'SessionUserID' => $this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData['Data'];
        }
    }

    /*
      Description: To get series data
     */

    public function getSeriesRounds_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        $SeriesData = $this->AuctionDrafts_model->getSeriesRounds(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID, 'AuctionDraftStatusID' => $this->StatusID, 'SessionUserID' => $this->SessionUserID) : array('AuctionDraftStatusID' => $this->StatusID, 'SessionUserID' => $this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData['Data'];
        }
    }

    /*
      Name:             add
      Description:  Use to add contest to system.
      URL:          /contest/add/
     */

    public function add_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal]');
        $this->form_validation->set_rules('LeagueType', 'League Type', 'trim|required|in_list[Auction]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required|integer');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->set_rules('LeagueJoinDateTime', 'League Join Date Time', 'trim|required');
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

        /* validation up to 2 hours before the match start */
        $ExtendDate = date('Y-m-d H:i:s', strtotime('+5 hours', strtotime($this->Post['LeagueJoinDateTime'])));

        $MatchDateTime = $this->Sports_model->getRounds("SeriesMatchStartDate", array('RoundID' => $this->Post['RoundID']));
        $MatchTimeIST = date("Y-m-d H:i:s", strtotime($MatchDateTime['SeriesMatchStartDate'] . "+330 minutes"));

        if ($ExtendDate > $MatchTimeIST) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You can create contest up to 5 hours before the series first match start. After that you can not create contest.";
            exit;
        }
        /* Validation - ends */
        $ContestID = $this->AuctionDrafts_model->addContest($this->Post, $this->SessionUserID, @$this->MatchID, $this->Post['RoundID']);
        if (!$ContestID) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Contest created successfully.";
            $this->Return['Data'] = $this->AuctionDrafts_model->getContests('CustomizeWinning,MatchScoreDetails,UserID,ContestFormat,ContestType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,UserInvitationCode,RoundID', array('ContestID' => $ContestID));
        }
    }

    /*
      Name:             edit
      Description:  Use to update contest to system.
      URL:          /contest/edit/
     */

    public function edit_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateAnyUserJoinedContest[update]');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal]');
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

        $this->AuctionDrafts_model->updateContest($this->Post, $this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest updated successfully.";
    }

    /*
      Name:             delete
      Description:  Use to delete contest to system.
      URL:          /contest/delete/
     */

    public function delete_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateAnyUserJoinedContest[delete]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Delete Contests Data */
        $this->AuctionDrafts_model->deleteContest($this->SessionUserID, $this->ContestID);
        $this->Return['Message'] = "Contest deleted successfully.";
    }

    /*
      Description: To get contests data
     */

    public function getContests_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No,All]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim');
        //$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->set_rules('AuctionStatus', 'AuctionStatus', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->AuctionDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID, 'AuctionStatusID' => @$this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData['Data'];
        }
    }

    /*
      Description: To get contests data
     */

    public function getContestsByType_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No,All]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('AuctionStatus', 'AuctionStatus', 'trim|callback_validateStatus');
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

            array_push($ContestData, $this->AuctionDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('RoundID' => @$this->Post['RoundID'], 'UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID, 'AuctionStatusID' => 1,'ContestFull'=>"No"), $Contests['Where']), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize'])['Data']);

            $ContestData[$key]['Key'] = $Contests['Key'];
            $ContestData[$key]['TagLine'] = $Contests['TagLine'];
        }

        $Statics = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.RoundID = "' . @$this->Post['RoundID'] . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID AND sports_contest_join.RoundID = C.RoundID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.RoundID = "' . @$this->Post['RoundID'] . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(*) AS `JoinedContest` FROM `sports_contest_join` J, `sports_contest` C WHERE C.ContestID = J.ContestID AND J.UserID = "' . $this->SessionUserID . '" AND C.RoundID = "' . @$this->Post['RoundID'] . '" 
                    )as JoinedContest,( 
                    SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . $this->SessionUserID . '" AND RoundID = "' . @$this->Post['RoundID'] . '"
                ) as TotalTeams,(SELECT COUNT(*) AS `H2HContest` FROM `sports_contest` C, `tbl_entity` E, `sports_contest_join` CJ WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.RoundID = "' . @$this->Post['RoundID'] . '" AND C.ContestFormat="Head to Head" AND E.StatusID = 1 AND C.ContestID = CJ.ContestID AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID )) as H2HContests')->row();

        if (!empty($ContestData)) {
            $this->Return['Data']['Results'] = $ContestData;
            $this->Return['Data']['Statics'] = $Statics;
        }
    }

    /*
      Description: To get contest detail
     */

    public function getContest_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->AuctionDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => $this->ContestID, 'MatchID' => $this->MatchID, 'SessionUserID' => $this->SessionUserID)));
        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData;
        }
    }

    /*
      Name:             join
      Description:  Use to join contest to system.
      URL:          /contest/join/
     */

    public function join_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateUserJoinContest');
        // $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        //$this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Join Contests */

        /** Check match on going to live* */
        $ContestDetails = $this->AuctionDrafts_model->getContests("EntryFee,GameTimeLive,ContestID,LeagueJoinDateTime", array("ContestID" => $this->ContestID));

        $GameTimeLive = $ContestDetails['GameTimeLive'];
        $LeagueJoinDateTime = $ContestDetails['LeagueJoinDateTime'];

        $currentDateTime = date('Y-m-d', strtotime("+$GameTimeLive minutes"));

        if ($LeagueJoinDateTime < $currentDateTime) {
            $this->AuctionDrafts_model->changeContestStatus($this->ContestID);
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You can not join the contest because time is over.";
        } else {
            $UserData = $this->Users_model->getUsers('FirstName,Email,PhoneNumber', array('UserID' => $this->SessionUserID));
            $JoinContest = $this->AuctionDrafts_model->joinContest($this->Post, $this->SessionUserID, $this->ContestID, $this->Post['SeriesID'], $this->Post['RoundID'], $this->UserTeamID);
            if (!$JoinContest) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred, please try again later.";
            } else {
                $SeriesData = $this->AuctionDrafts_model->getSeriesRounds('SeriesID,SeriesName', array('RoundID' => $this->Post['RoundID']));
                if (!empty($UserData['Email'])) {
                    send_mail(array(
                        'emailTo' => $UserData['Email'],
                        'template_id' => 'd-7ac605967f8346079f099dc608dcb1e3',
                        'Subject' => 'Auction Contest Joined Successfully- ' . SITE_NAME,
                        'Name' => $UserData['FirstName'],
                        'Message' => "Your entry into the Auction contest with entry fee " . DEFAULT_CURRENCY . $ContestDetails['EntryFee'] . " for " . $SeriesData['SeriesName'] . " Series was successful.",
                        'Link' => 'auctionLeague?SeriesGUID=' . $this->Post['RoundID'] . '&League=' . $this->Post['ContestGUID']
                    ));
                    $this->Return['Data'] = $JoinContest;
                    $this->Return['Message'] = "Contest joined successfully.";
                }
            }
        }
    }

    /*
      Description: To get players data
     */

    public function getPlayers_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('MySquadPlayer', 'MySquadPlayer', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */

        $PlayersData = $this->AuctionDrafts_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'ContestID' => @$this->ContestID, 'UserGUID' => @$this->UserID, 'SessionUserID' => @$this->SessionUserID, 'PlayerID' => @$this->PlayerID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData['Data'];
        }
    }

    /*
      Description: To get players data
     */

    public function getPlayersAuction_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('MySquadPlayer', 'MySquadPlayer', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $PlayersData = $this->AuctionDrafts_model->getPlayersAuction(@$this->Post['Params'], array_merge($this->Post, array('RoundID' => $this->post['RoundID'], 'ContestID' => @$this->ContestID, 'SessionUserID' => @$this->SessionUserID, 'PlayerID' => @$this->PlayerID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData['Data'];
        }
    }

    /*
      Description: To get player details
     */

    public function getPlayer_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Player Data */
        $PlayerDetails = $this->Sports_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('PlayerID' => $this->PlayerID)));
        if (!empty($PlayerDetails)) {
            $this->Return['Data'] = $PlayerDetails;
        }
    }

    /*
      Name:             addUserTeam
      Description:  Use to create team to system.
      URL:          /api_admin/contest/addUserTeam/
     */

    public function addUserTeam_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Auction]');
        $this->form_validation->set_rules('MatchInning', 'MatchInning', 'trim' . (!empty($this->Post['UserTeamType']) && $this->Post['UserTeamType'] == 'InPlay' ? '|required|callback_validateMatchStatusInnings' : ''));
        $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim');
        $this->form_validation->set_rules('IsPreTeam', 'IsPreTeam', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');

        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
            /* $AllPlayersLimit = 20;
              if (count($this->Post['UserTeamPlayers']) > $AllPlayersLimit) {
              $this->Return['ResponseCode'] = 500;
              $this->Return['Message'] = "Team Players length can't greater than  " . $AllPlayersLimit . ".";
              exit;
              } */
            foreach ($this->Post['UserTeamPlayers'] as $Key => $Value) {
                $this->form_validation->set_rules('UserTeamPlayers[' . $Key . '][PlayerGUID]', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
                $this->form_validation->set_rules('UserTeamPlayers[' . $Key . '][PlayerPosition]', 'PlayerPosition', 'trim|required|in_list[Player]');
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "User Team Players Required.";
            exit;
        }
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $UserTeam = $this->AuctionDrafts_model->addUserTeam($this->Post, $this->SessionUserID, $this->Post['SeriesID'], $this->Post['RoundID'], $this->ContestID);
        if (!$UserTeam) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Data']['UserTeamGUID'] = $UserTeam;
            $this->Return['Message'] = "Players added on assistant successfully.";
        }

        /** Check match on going to live* */
        /* $MatchDetails = $this->Sports_model->getMatches('MatchID,MatchDateTime,MatchClosedInMinutes', array("MatchID" => $this->MatchID));
          $MatchClosedInMinutes = $MatchDetails['MatchClosedInMinutes'];
          $MatchStartDateTime = $MatchDetails['MatchDateTime'];
          $currentDateTime = date('Y-m-d H:i:s', strtotime("+$MatchClosedInMinutes minutes"));
          if (strtotime($MatchStartDateTime) <= strtotime($currentDateTime) && !empty($MatchClosedInMinutes)) {
          $this->Return['ResponseCode'] = 500;
          $this->Return['Message'] = "You can not create team because time is over.";
          } else {

          } */
    }

    /*
      Name:             assistantTeamOnOff
      Description:  Use to on off assistant.
      URL:          /api_admin/auctionDrafts/assistantTeamOnOff/
     */

    public function assistantTeamOnOff_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $UserTeam = $this->AuctionDrafts_model->assistantTeamOnOff($this->Post, $this->SessionUserID, $this->Post['RoundID'], $this->ContestID, $this->UserTeamID);
        $this->Return['Message'] = "Assistant updated successfully.";
    }

    /*
      Name:             editUserTeam
      Description:  Use to update team to system.
      URL:          /api_admin/auctionDrafts/editUserTeam/
     */

    public function editUserTeam_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Auction]');
        $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
            /* if (count($this->Post['UserTeamPlayers']) > 20) {
              $this->Return['ResponseCode'] = 500;
              $this->Return['Message'] = "Team Players length can't greater than 20.";
              exit;
              } */
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "User Team Players Required.";
            exit;
        }
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        if (!$this->AuctionDrafts_model->editUserTeam($this->Post, $this->UserTeamID)) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Players updated on assistant successfully.";
        }
    }

    /*
      Description: To get user teams data
     */

    public function getUserTeams_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Auction]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get User Teams Data */
        $UserTeams = $this->AuctionDrafts_model->getUserTeams(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->SessionUserID, 'SeriesID' => @$this->SeriesID, 'UserTeamID' => @$this->UserTeamID)), (!empty($this->Post['UserTeamGUID'])) ? FALSE : TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($UserTeams)) {
            $this->Return['Data'] = (!empty($this->Post['UserTeamGUID'])) ? $UserTeams : $UserTeams['Data'];
        }
    }

    /*
      Name:             addAuctionPlayerBid
      Description:  Use to create team to system.
      URL:          /api_admin/auctionDrafts/addAuctionPlayerBid/
     */

    public function addAuctionPlayerBid_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $UserID = @$this->SessionUserID;
        if (empty($this->Post['SessionKey'])) {
            $UserID = @$this->UserID;
        }
        $Post['Params'] = "ContestGUID,SeriesGUID,SeriesID,ContestID,TimeDifference,BidDateTime,PlayerStatus,PlayerGUID,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBornPlace,PlayerSalary,PlayerSalaryCredit";

        $AuctionList = $this->AuctionDrafts_model->auctionBidTimeManagement($Post, @$this->ContestID, @$this->Post['RoundID']);
        if (!empty($AuctionList)) {
            if ($AuctionList[0]['TimeDifference'] < 29) {

                $AuctionBid = $this->AuctionDrafts_model->addAuctionPlayerBid($this->Post, $UserID, @$this->Post['RoundID'], $this->ContestID, $this->PlayerID);
                if ($AuctionBid['Status'] == 1) {
                    $this->Return['Data'] = $AuctionBid['Data'];
                    $this->Return['Message'] = "Player Bid successfully added.";
                } else {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = $AuctionBid['Message'];
                }
            } else {
                $this->Return['ResponseCode'] = 500;
                $this->Return['TimeDifference'] = $AuctionList[0]['TimeDifference'];
                $this->Return['Message'] = 'Player Bid limit time is greater than 29 seconds';
            }
        }
    }

    /*
      Name:             getAuctionGameInLive
      Description:  get live auction.
      URL:          /api_admin/auctionDrafts/getAuctionGameInLive/
     */

    public function getAuctionGameInLive_post() {
        $AuctionGames = $this->AuctionDrafts_model->getContests('RoundID,SeriesID,ContestID,ContestGUID,AuctionStatusID,AuctionStatus', array('Filter' => 'LiveAuction', 'AuctionStatusID' => 1, "LeagueType" => "Auction"), TRUE, 1);
        if (!empty($AuctionGames)) {
            $this->Return['Data'] = $AuctionGames;
        }
    }

    /*
      Name:             getAuctionGameStatusUpdate
      Description:  get live auction.
      URL:          /api_admin/auctionDrafts/getAuctionGameStatusUpdate/
     */

    public function getAuctionGameStatusUpdate_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        $AuctionStatus = $this->AuctionDrafts_model->getAuctionGameStatusUpdate($this->Post, $this->ContestID, $this->StatusID);
        if ($AuctionStatus) {
            $this->Return['Message'] = "Auction status successfully updated.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Auction status already updated.";
        }
    }

    /*
      Name:             auctionPlayerStausUpdate
      Description:  get live auction.
      URL:          /api_admin/auctionDrafts/auctionPlayerStausUpdate/
     */

    public function auctionPlayerStausUpdate_post() {
        $this->form_validation->set_rules('PlayerStatus', 'PlayerStatus', 'trim|required|in_list[Upcoming,Live,Sold,Unsold]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        $AuctionPlayerStatus = $this->AuctionDrafts_model->auctionPlayerStausUpdate($this->Post, $this->Post['SeriesID'], $this->Post['RoundID'], $this->ContestID, $this->PlayerID);

        //$AuctionPlayerStatus["CurrentBidUser"] = $this->AuctionDrafts_model->getCurrentBidUser($this->Post['RoundID'], $this->ContestID);
        $AuctionPlayerStatus["CurrentBidPlayerTime"] = $this->AuctionDrafts_model->getCurrentBidPlayerTime($this->Post['RoundID'], $this->ContestID, $this->PlayerID);

        if ($AuctionPlayerStatus['Status'] == 1 && $AuctionPlayerStatus['AuctionStatus'] != "Completed") {
            $this->Return['Data'] = $AuctionPlayerStatus;
            $this->Return['Message'] = "Auction player status successfully updated.";
        } else if ($AuctionPlayerStatus['Status'] == 1 && $AuctionPlayerStatus['AuctionStatus'] == "Completed") {
            $this->Return['Data'] = $AuctionPlayerStatus;
            $this->Return['ResponseCode'] = 200;
            $this->Return['Message'] = "Auction has been completed";
        } else {
            $this->Return['ResponseCode'] = 200;
            if ($AuctionPlayerStatus['Status'] == 3) {
                $this->Return['ResponseCode'] = 500;
            }
            $this->Return['Data'] = $AuctionPlayerStatus;
            $this->Return['Message'] = $AuctionPlayerStatus['Message'];
        }
        /* if ($AuctionPlayerStatus['Status'] == 1) {
          $this->Return['Data'] = $AuctionPlayerStatus;
          $this->Return['Message'] = "Auction player status successfully updated.";
          } else {
          $this->Return['ResponseCode'] = 500;
          $this->Return['Message'] = "Player already in live.";
          } */
    }

    /*
      Name:             getPlayerBid
      Description:  get player on going to bid
      URL:          /api_admin/auctionDrafts/getPlayerBid/
     */

    public function getPlayerBid_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* check game live */
        $playersData = array();
        $AuctionGames = $this->AuctionDrafts_model->getContests('ContestID,AuctionStatus', array('AuctionStatusID' => 2, 'ContestID' => $this->ContestID, "LeagueType" => "Auction"));
        if (!isset($AuctionGames['Data']['Records'])) {
            $playersData = $this->AuctionDrafts_model->getPlayers($this->Post['Params'], array_merge($this->Post, array('RoundID' => $this->Post['RoundID'], 'ContestID' => $this->ContestID, 'PlayerBidStatus' => 'Yes', 'PlayerStatus' => 'Live', 'PlayerBidStatus' => "Yes")));
            if (empty($playersData)) {
                $playersData = $this->AuctionDrafts_model->getPlayers($this->Post['Params'], array_merge($this->Post, array('RoundID' => $this->Post['RoundID'], 'ContestID' => $this->ContestID, 'PlayerBidStatus' => 'Yes', 'PlayerStatus' => 'Upcoming', 'PlayerBidStatus' => "Yes")));
            }
        } else {
            $playersData = $this->AuctionDrafts_model->getPlayers($this->Post['Params'], array_merge($this->Post, array('RoundID' => $this->Post['RoundID'], 'ContestID' => $this->ContestID, 'PlayerBidStatus' => "Yes")));
        }
        if (!empty($playersData)) {
            $playersData["CurrentBidUser"] = $this->AuctionDrafts_model->getCurrentBidUser($this->Post['RoundID'], $this->ContestID);
            $playersData["CurrentBidPlayerTime"] = $this->AuctionDrafts_model->getCurrentBidPlayerTime($this->Post['RoundID'], $this->ContestID, $playersData['PlayerID']);

            $Post['Params'] = "ContestGUID,SeriesGUID,SeriesID,ContestID,TimeDifference,BidDateTime,PlayerStatus,PlayerGUID,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBornPlace,PlayerSalary,PlayerSalaryCredit";

            $AuctionList = $this->AuctionDrafts_model->auctionBidTimeManagement($Post, $this->ContestID, $this->Post['RoundID']);
            $playersData["TimeDifference"] = (isset($AuctionList[0]['TimeDifference'])) ? 30 - $AuctionList[0]['TimeDifference'] : 30;

            $this->Return['Data'] = $playersData;
            $this->Return['Message'] = "Player successfully in auction.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Auction player not found.";
        }
    }

    /*
      Name:             auctionBidTimeManagement
      Description:  get live auction.
      URL:          /api_admin/auctionDrafts/auctionBidTimeManagement/
     */

    public function auctionBidTimeManagement_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim');
        $this->form_validation->validation($this);  /* Run validation */
        /* get live auction */
        $Post['Params'] = "ContestGUID,SeriesGUID,SeriesID,ContestID,TimeDifference,BidDateTime,PlayerStatus,PlayerGUID,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBornPlace,PlayerSalary,PlayerSalaryCredit";
        $AuctionList = $this->AuctionDrafts_model->auctionBidTimeManagement($Post, @$this->ContestID, @$this->Post['RoundID']);
        if ($AuctionList) {
            $this->Return['Data'] = $AuctionList;
            $this->Return['Message'] = "Auction in live.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Auction not availabe in live.";
        }
    }

    /**
     * Function Name: validateCheckAuctionInLive
     * Description:   To validate if check auction in live
     */
    public function validateCheckAuctionInLive($ContestGUID) {
        $AuctionGames = $this->AuctionDrafts_model->getContests('ContestID,AuctionStatusID', array('ContestGUID' => $ContestGUID), TRUE, 1);
        if ($AuctionGames['Data']['TotalRecords'] > 0) {
            if ($AuctionGames['Data']['Records'][0]['AuctionStatusID'] == 1) {
                $this->form_validation->set_message('validateCheckAuctionInLive', 'Auction not started');
                return FALSE;
            } else if ($AuctionGames['Data']['Records'][0]['AuctionStatusID'] == 5) {
                $this->form_validation->set_message('validateCheckAuctionInLive', 'Auction completed');
                return FALSE;
            } else if ($AuctionGames['Data']['Records'][0]['AuctionStatusID'] == 3) {
                $this->form_validation->set_message('validateCheckAuctionInLive', 'Auction cancelled');
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            return TRUE;
        }
    }

    /**
     * Function Name: validateAnyUserJoinedContest
     * Description:   To validate if any user joined contest
     */
    public function validateAnyUserJoinedContest($ContestGUID, $Type) {
        $TotalJoinedContest = $this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `sports_contest_join` WHERE `ContestID` =' . $this->ContestID)->row()->TotalRecords;
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
        $MatchStatus = $this->db->query("SELECT E.StatusID FROM sports_users_teams UT, tbl_entity E WHERE UT.MatchID = E.EntityID AND UT.UserTeamGUID = '" . $UserTeamGUID . "' ")->row()->StatusID;
        if ($MatchStatus != 1) {
            $this->form_validation->set_message('validateMatchStatus', 'Sorry, you can not edit team.');
            return FALSE;
        } else {
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
        $MatchData = $this->Sports_model->getMatches('MatchType,Status,MatchScoreDetails', array('MatchID' => $this->MatchID));
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
            $this->form_validation->set_message('validateMatchStatusInnings', 'You can create team between 0.1 to 22.5 overs.');
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
        $ContestData = $this->AuctionDrafts_model->getContests('MatchID,ContestSize,Privacy,IsPaid,EntryType,EntryFee,UserInvitationCode,ContestID,ContestType,UserJoinLimit,CashBonusContribution', array('ContestID' => $this->ContestID));
        if (!empty($ContestData)) {

            /* Get Match Status */
            $MatchData = $this->Sports_model->getMatches('MatchType,Status,MatchScoreDetails', array('MatchID' => $ContestData['MatchID']));
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
                if ($this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `sports_contest_join` WHERE  `JoinInning` = "' . $this->Post['JoinInning'] . '" AND `ContestID` =' . $ContestData['ContestID'])->row()->TotalRecords >= $ContestData['ContestSize']) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Join Contest limit is exceeded.');
                    return FALSE;
                }

                /* To Check If Contest Is Already Joined */
                $JoinContestWhere = array('SessionUserID' => $this->SessionUserID, 'ContestID' => $ContestData['ContestID'], 'JoinInning' => $this->Post['JoinInning']);
                if ($ContestData['EntryType'] == 'Multiple') {
                    $JoinContestWhere['UserTeamID'] = $this->UserTeamID;
                }
                $Response = $this->AuctionDrafts_model->getJoinedContests('', $JoinContestWhere, TRUE, 1, 1);
                if (!empty($Response['Data']['TotalRecords'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Contest is already joined.');
                    return FALSE;
                }

                /* To Check User Team Match Details */
                if (!$this->AuctionDrafts_model->getUserTeams('', array('UserTeamID' => $this->UserTeamID, 'MatchID' => $ContestData['MatchID'], 'MatchInning' => $this->Post['JoinInning']))) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Invalid UserTeamGUID.');
                    return FALSE;
                }
            } else {

                if ($MatchData['Status'] != 'Pending') {
                    $this->form_validation->set_message('validateUserJoinContest', 'You can join only upcoming matches contest.');
                    return FALSE;
                }

                /* Check Join Contest Size Limit */

                if ($this->db->query('SELECT COUNT(*) AS `TotalRecords` FROM `sports_contest_join` WHERE `ContestID` =' . $ContestData['ContestID'])->row()->TotalRecords >= $ContestData['ContestSize']) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Join Contest limit is exceeded.');
                    return FALSE;
                }

                /* To Check If Contest Is Already Joined */
                $JoinContestWhere = array('SessionUserID' => $this->SessionUserID, 'ContestID' => $ContestData['ContestID']);
                if ($ContestData['EntryType'] == 'Multiple') {

                    /* Get User Join Limit */
                    if ($this->db->query('SELECT COUNT(*) AS `TotalJoined` FROM `sports_contest_join` WHERE `ContestID` =' . $ContestData['ContestID'] . ' AND UserID = ' . $this->SessionUserID)->row()->TotalJoined >= $ContestData['UserJoinLimit']) {
                        $this->form_validation->set_message('validateUserJoinContest', 'You can join this contest only ' . $ContestData['UserJoinLimit'] . ' times.');
                        return FALSE;
                    }


                    $JoinContestWhere['UserTeamID'] = $this->UserTeamID;
                }
                $Response = $this->AuctionDrafts_model->getJoinedContests('', $JoinContestWhere, TRUE, 1, 1);
                if (!empty($Response['Data']['TotalRecords'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Contest is already joined.');
                    return FALSE;
                }

                /* To Check User Team Match Details */
                if (!$this->AuctionDrafts_model->getUserTeams('', array('UserTeamID' => $this->UserTeamID, 'MatchID' => $ContestData['MatchID']))) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Invalid UserTeamGUID.');
                    return FALSE;
                }

                /* To Check Contest Privacy */
                if ($ContestData['Privacy'] == 'Yes') {
                    if (empty($this->Post['UserInvitationCode'])) {
                        $this->form_validation->set_message('validateUserJoinContest', 'The User Invitation Code field is required.');
                        return FALSE;
                    }
                    if ($ContestData['UserInvitationCode'] != $this->Post['UserInvitationCode']) {
                        $this->form_validation->set_message('validateUserJoinContest', 'Invalid User Invitation Code.');
                        return FALSE;
                    }
                }
            }

            /* To Check Wallet Amount, If Contest Is Paid */
            if ($ContestData['IsPaid'] == 'Yes') {
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
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('UserInvitationCode', 'UserInvitationCode', 'trim|required');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        $ContestData = $this->AuctionDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('UserInvitationCode' => @$this->Post['UserInvitationCode'], "RoundID" => $this->Post['RoundID'])), FALSE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($ContestData)) {
            $this->Return['Data'] = $ContestData;
        }
    }

    /*
      Description: To get joined contest users data
     */

    public function getJoinedContestsUsers_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim');
        $this->form_validation->set_rules('playerSelectedCount', 'playerSelectedCount', 'trim');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contest Users Data */
        $JoinedContestData = $this->AuctionDrafts_model->getJoinedContestsUsers(@$this->Post['Params'], array('ContestID' => @$this->ContestID, 'SeriesID' => @$this->SeriesID, 'RoundID' => @$this->Post['RoundID'], 'SessionUserID' => @$this->SessionUserID, 'playerSelectedCount' => @$this->Post['playerSelectedCount']), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Description: To get auction player bid history
     */

    public function getContestBidHistory_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Joined Contest Users Data */
        $JoinedContestData = $this->AuctionDrafts_model->getContestBidHistory(@$this->Post['Params'], array('ContestID' => @$this->ContestID, 'RoundID' => $this->Post['RoundID'], 'PlayerID' => @$this->PlayerID), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Description: 	Use to update user status.
     */

    public function changeUserStatus_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('AuctionUserStatus', 'AuctionUserStatus', 'trim|required|in_list[Online,Offline]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->AuctionDrafts_model->changeUserStatus($this->Post, $this->UserID, $this->ContestID);
        $this->Return['Message'] = "Status has been changed.";
    }

    /*
      Description: 	Use to update user contest hold on off.
     */

    public function changeUserContestStatusHoldOnOff_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('IsHold', 'IsHold', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $AuctionTimeBank = $this->AuctionDrafts_model->changeUserContestStatusHoldOnOff($this->Post, $this->UserID, $this->ContestID);
        if ($AuctionTimeBank['Status'] != 1) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $AuctionTimeBank['Message'];
        } else {
            $this->Return['Message'] = $AuctionTimeBank['Message'];
        }
    }

    /* Description: 	Cron jobs to auto hold time manage.
     */

    public function auctionHoldTimeUpdate_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('HoldTime', 'HoldTime', 'trim|required|numeric');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->AuctionDrafts_model->auctionHoldTimeUpdate($this->Post, $this->UserID, $this->ContestID);
        $this->Return['Message'] = "Hold time successfully updated.";
    }

    /* Description:  aution on break
     */

    public function auctionOnBreak_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('AuctionIsBreakTimeStatus', 'AuctionIsBreakTimeStatus', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('AuctionTimeBreakAvailable', 'AuctionTimeBreakAvailable', 'trim|required|in_list[Yes,No]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $ContestData = $this->AuctionDrafts_model->auctionOnBreak($this->Post, $this->ContestID);
        $this->Return['Data']['BreakTime'] = 5 * 60;
        $this->Return['Message'] = "Successfully update.";
    }

    /*
      Name:             auctionTeamPlayersSubmit
      Description:  user submit auction team after complete auction top 16 players c / vc.
      URL:          /api/auctionDrafts/auctionTeamPlayersSubmit/
     */

    public function auctionTeamPlayersSubmit_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
            if (count($this->Post['UserTeamPlayers']) > 11) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Team Players length can't greater than 11.";
                exit;
            }
            $PlayerPoisitions = array_count_values(array_column($this->Post['UserTeamPlayers'], 'PlayerPosition'));
            if ($PlayerPoisitions['Captain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select 1 Captain.";
                exit;
            } else if (!empty($PlayerPoisitions['Captain']) && $PlayerPoisitions['Captain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 1 Captain.";
                exit;
            }
            /* else if (!empty($PlayerPoisitions['ViceCaptain']) && $PlayerPoisitions['ViceCaptain'] != 1) {
              $this->Return['ResponseCode'] = 500;
              $this->Return['Message'] = "You can select only 1 Vice Captain.";
              exit;
              } */
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

        if (!$this->AuctionDrafts_model->auctionTeamPlayersUpdate($this->Post, $this->UserTeamID, $this->Post['SeriesID'], $this->Post['RoundID'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Team Players updated successfully.";
        }
    }

}

?>