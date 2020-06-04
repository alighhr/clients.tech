<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class SnakeDrafts extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('SnakeDrafts_model');
        $this->load->model('Sports_model');
        $this->load->model('Users_model');
    }

    /*
      Description: To get series data
     */

    public function getSeries_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        $SeriesData = $this->SnakeDrafts_model->getSeries(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID, 'AuctionDraftStatusID' => $this->StatusID, 'SessionUserID' => $this->SessionUserID) : array('AuctionDraftStatusID' => $this->StatusID, 'SessionUserID' => $this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
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
        $SeriesData = $this->SnakeDrafts_model->getSeriesRounds(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID, 'AuctionDraftStatusID' => $this->StatusID, 'SessionUserID' => $this->SessionUserID) : array('AuctionDraftStatusID' => $this->StatusID, 'SessionUserID' => $this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData['Data'];
        }
    }

    /*
      Name:             add
      Description:  Use to add contest to system.
      URL:          /contest/add/
     */

    public function getTeams_post() {
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $Teams = $this->SnakeDrafts_model->getTeams($this->Post['RoundID']);
        $this->Return['Data'] = $Teams;
        $this->Return['Message'] = "Team successfully found.";
    }

    public function add_post() {

        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim');
        $this->form_validation->set_rules('ContestFormat', 'Contest Format', 'trim|required|in_list[League]');
        $this->form_validation->set_rules('ContestType', 'Contest Type', 'trim|required|in_list[Normal]');
        $this->form_validation->set_rules('LeagueType', 'League Type', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('Privacy', 'Privacy', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('IsPaid', 'IsPaid', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('ShowJoinedContest', 'ShowJoinedContest', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('WinningAmount', 'WinningAmount', 'trim|required');
        $this->form_validation->set_rules('ContestSize', 'ContestSize', 'trim' . (!empty($this->Post['ContestFormat']) && $this->Post['ContestFormat'] == 'League' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryFee', 'EntryFee', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|numeric' : ''));
        $this->form_validation->set_rules('NoOfWinners', 'NoOfWinners', 'trim' . (!empty($this->Post['IsPaid']) && $this->Post['IsPaid'] == 'Yes' ? '|required|integer' : ''));
        $this->form_validation->set_rules('EntryType', 'EntryType', 'trim|required|in_list[Single]');
        $this->form_validation->set_rules('UserJoinLimit', 'UserJoinLimit', 'trim' . (!empty($this->Post['EntryType']) && $this->Post['EntryType'] == 'Multiple' ? '|required|integer' : ''));
        $this->form_validation->set_rules('CashBonusContribution', 'CashBonusContribution', 'trim|required|numeric|regex_match[/^[0-9][0-9]?$|^100$/]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->set_rules('CustomizeWinning', 'Customize Winning', 'trim');
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
        /* Validation - ends */
        $DraftPlayerSelectionCriteria = json_decode($this->Post['DraftPlayerSelectionCriteria']);
        $countPlayer = 0;
        $WicketKeeper = 0;
        $Batsman = 0;
        $Bowler = 0;
        $AllRounder = 0;
        foreach ($DraftPlayerSelectionCriteria as $key => $value) {
            if (key($value) == 'WicketKeeper') {
                $countPlayer += $value->WicketKeeper;
                $WicketKeeper = $value->WicketKeeper;
            };
            if (key($value) == 'Batsman') {
                $countPlayer += $value->Batsman;
                $Batsman = $value->Batsman;
            };
            if (key($value) == 'Bowler') {
                $countPlayer += $value->Bowler;
                $Bowler = $value->Bowler;
            };
            if (key($value) == 'AllRounder') {
                $countPlayer += $value->AllRounder;
                $AllRounder = $value->AllRounder;
            };
        }
        if ($this->Post['DraftTeamPlayerLimit'] >= $countPlayer) {
            $SeriesData = $this->Sports_model->getRoundPlayers($this->Post['RoundID']);
            if (($SeriesData['Batsman'] + $SeriesData['WicketKeeper'] + $SeriesData['Bowler'] + $SeriesData['AllRounder']) >= $this->Post['ContestSize'] * $this->Post['DraftTeamPlayerLimit']) {
                if (!($SeriesData['Batsman'] >= $Batsman * $this->Post['ContestSize'])) {
                    $this->Return['ResponseCode'] = 500;
                    $this->Return['Message'] = "Please change your Batsman criteria.";
                } else {
                    if (!($SeriesData['WicketKeeper'] >= $WicketKeeper * $this->Post['ContestSize'])) {
                        $this->Return['ResponseCode'] = 500;
                        $this->Return['Message'] = "Please change your WicketKeeper criteria.";
                    } else {

                        if (!($SeriesData['Bowler'] >= $Bowler * $this->Post['ContestSize'])) {
                            $this->Return['ResponseCode'] = 500;
                            $this->Return['Message'] = "Please change your Bowler criteria.";
                        } else {
                            if (!($SeriesData['AllRounder'] >= $AllRounder * $this->Post['ContestSize'])) {
                                $this->Return['ResponseCode'] = 500;
                                $this->Return['Message'] = "Please change your AllRounder criteria.";
                            } else {
                                $ContestID = $this->SnakeDrafts_model->addContest($this->Post, $this->SessionUserID, @$this->MatchID, $this->Post['SeriesID']);
                                if (!$ContestID) {
                                    $this->Return['ResponseCode'] = 500;
                                    $this->Return['Message'] = "An error occurred, please try again later.";
                                } else {
                                    $this->Return['Message'] = "Contest created successfully.";
                                    $this->Return['Data'] = $this->SnakeDrafts_model->getContests('CustomizeWinning,MatchScoreDetails,UserID,ContestFormat,ContestType,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,UserInvitationCode,DraftPlayerSelectionCriteria,DraftTeamPlayerLimit,RoundID', array('ContestID' => $ContestID));
                                }
                            }
                        }
                    }
                }
            } else {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Draft Team Player Limit is wrong.";
            }
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Draft Team Player Limit is wrong.";
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

        $this->SnakeDrafts_model->updateContest($this->Post, $this->SessionUserID, $this->ContestID);
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
        $this->SnakeDrafts_model->deleteContest($this->SessionUserID, $this->ContestID);
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
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        //$this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->set_rules('AuctionStatus', 'AuctionStatus', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->SnakeDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID, 'AuctionStatusID' => @$this->StatusID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

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
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
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
            array_push($ContestData, $this->SnakeDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => @$this->MatchID, 'UserID' => @$this->UserID, 'SessionUserID' => $this->SessionUserID, 'AuctionStatusID' => 1), $Contests['Where']), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize'])['Data']);
            $ContestData[$key]['Key'] = $Contests['Key'];
            $ContestData[$key]['TagLine'] = $Contests['TagLine'];
        }

        $Statics = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(*) AS `JoinedContest` FROM `sports_contest_join` J, `sports_contest` C WHERE C.ContestID = J.ContestID AND J.UserID = "' . $this->SessionUserID . '" AND C.MatchID = "' . $this->MatchID . '"
                    )as JoinedContest,(
                    SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . $this->SessionUserID . '" AND MatchID = "' . $this->MatchID . '"
                ) as TotalTeams,(SELECT COUNT(*) AS `H2HContest` FROM `sports_contest` C, `tbl_entity` E, `sports_contest_join` CJ WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $this->MatchID . '" AND C.ContestFormat="Head to Head" AND E.StatusID = 1 AND C.ContestID = CJ.ContestID AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID )) as H2HContests')->row();

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
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $ContestData = $this->SnakeDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => $this->ContestID, 'MatchID' => $this->MatchID, 'SessionUserID' => $this->SessionUserID)));
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
        //$this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateUserJoinContest');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Join Contests */

        /** Check match on going to live* */
        $ContestDetails = $this->SnakeDrafts_model->getContests("EntryFee,GameTimeLive,ContestID,LeagueJoinDateTime", array("ContestID" => $this->ContestID));

        $GameTimeLive = $ContestDetails['GameTimeLive'];
        $LeagueJoinDateTime = $ContestDetails['LeagueJoinDateTime'];

        $currentDateTime = date('Y-m-d', strtotime("+$GameTimeLive minutes"));

        if ($LeagueJoinDateTime < $currentDateTime) {
            $this->SnakeDrafts_model->changeContestStatus($this->ContestID);
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "You can not join the contest because time is over.";
        } else {
            $UserData = $this->Users_model->getUsers('FirstName,Email,PhoneNumber', array('UserID' => $this->SessionUserID));
            $JoinContest = $this->SnakeDrafts_model->joinContest($this->Post, $this->SessionUserID, $this->ContestID, $this->Post['SeriesID'], $this->Post['RoundID'], $this->UserTeamID);
            if (!$JoinContest) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "An error occurred, please try again later.";
            } else {
                $SeriesData = $this->SnakeDrafts_model->getSeriesRounds('SeriesID,SeriesName', array('RoundID' => $this->Post['RoundID']));
                if (!empty($UserData['Email'])) {
                    send_mail(array(
                        'emailTo' => $UserData['Email'],
                        'template_id' => 'd-7ac605967f8346079f099dc608dcb1e3',
                        'Subject' => 'Snake Draft Contest Joined Successfully- ' . SITE_NAME,
                        'Name' => $UserData['FirstName'],
                        'Message' => "Your entry into the Auction contest with entry fee " . DEFAULT_CURRENCY . $ContestDetails['EntryFee'] . " for " . $SeriesData['SeriesName'] . " Series was successful.",
                        'Link' => 'snakeLeague?SeriesGUID=' . $this->Post['RoundID'] . '&League=' . $this->Post['ContestGUID']
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
        // $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
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
        $PlayersData = $this->SnakeDrafts_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'ContestID' => @$this->ContestID, 'SessionUserID' => @$this->SessionUserID, 'PlayerID' => @$this->PlayerID, 'UserID' => @$this->UserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($PlayersData)) {
            $this->Return['Data'] = $PlayersData['Data'];
        }
    }

    /*
      Description: To get players data
     */

    public function getPlayersAuction_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('MySquadPlayer', 'MySquadPlayer', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $PlayersData = $this->SnakeDrafts_model->getPlayersAuction(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'ContestID' => @$this->ContestID, 'SessionUserID' => @$this->SessionUserID, 'PlayerID' => @$this->PlayerID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
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
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('MatchInning', 'MatchInning', 'trim' . (!empty($this->Post['UserTeamType']) && $this->Post['UserTeamType'] == 'InPlay' ? '|required|callback_validateMatchStatusInnings' : ''));
        $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim');
        $this->form_validation->set_rules('IsPreTeam', 'IsPreTeam', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
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

        /** get series dynamic validation * */
        $DraftTeamPlayerLimit = 100;
        if (count($this->Post['UserTeamPlayers']) > $DraftTeamPlayerLimit) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Team Players length can't greater than  " . $DraftTeamPlayerLimit . ".";
            exit;
        }
        /* Validation - ends */
        $UserTeam = $this->SnakeDrafts_model->addUserTeam($this->Post, $this->SessionUserID, $this->Post['SeriesID'], $this->Post['RoundID'], $this->ContestID);
        if (!$UserTeam) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Data']['UserTeamGUID'] = $UserTeam;
            $this->Return['Message'] = "Players added on assistant successfully.";
        }
    }

    /*
      Name:             assistantTeamOnOff
      Description:  Use to on off assistant.
      URL:          /api_admin/auctionDrafts/assistantTeamOnOff/
     */

    public function assistantTeamOnOff_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $UserTeam = $this->SnakeDrafts_model->assistantTeamOnOff($this->Post, $this->SessionUserID, $this->Post['RoundID'], $this->ContestID, $this->UserTeamID);
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
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');
        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
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

        /** get series dynamic validation * */
        $DraftTeamPlayerLimit = 100;
        if (count($this->Post['UserTeamPlayers']) > $DraftTeamPlayerLimit) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Team Players length can't greater than  " . $DraftTeamPlayerLimit . ".";
            exit;
        }
        /* Validation - ends */
        if (!$this->SnakeDrafts_model->editUserTeam($this->Post, $this->UserTeamID)) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Players updated on assistant successfully.";
        }
    }

    public function editUserTeamOLD_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('UserTeamName', 'UserTeamName', 'trim');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');
        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
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

        /** get series dynamic validation * */
        if (count($this->Post['UserTeamPlayers']) > 25) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Team Players length can't greater than  25.";
            exit;
        }
        /* Validation - ends */

        if (!$this->SnakeDrafts_model->editUserTeam($this->Post, $this->UserTeamID)) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Team updated successfully.";
        }
    }

    /*
      Description: To get user teams data
     */

    public function getUserTeams_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamType', 'UserTeamType', 'trim|required|in_list[Draft]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Normal]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get User Teams Data */
        $UserTeams = $this->SnakeDrafts_model->getUserTeams(@$this->Post['Params'], array_merge($this->Post, array('UserID' => $this->SessionUserID, 'SeriesID' => $this->SeriesID, 'UserTeamID' => @$this->UserTeamID)), (!empty($this->Post['UserTeamGUID'])) ? FALSE : TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($UserTeams)) {
            $this->Return['Data'] = (!empty($this->Post['UserTeamGUID'])) ? $UserTeams : $UserTeams['Data'];
        }
    }

    /*
      Name:             getRounds
      Description:  get live drafts round.
      URL:          /api_admin/auctionDrafts/getRounds/
     */

    public function getRounds_post() {
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        $ContestRound = $this->SnakeDrafts_model->getContests('ContestID,DraftLiveRound,DraftTeamPlayerLimit', array("LeagueType" => "Draft", "ContestID" => $this->ContestID), FALSE, 1);
        $Rounds = $this->SnakeDrafts_model->getRounds($this->Post['RoundID'], $this->ContestID, $ContestRound);
        if (!empty($Rounds)) {
            $this->Return['Data'] = $Rounds;
            if (!empty($ContestRound)) {
                $this->Return['DraftLiveRound'] = $ContestRound['DraftLiveRound'];
            } else {
                $this->Return['DraftLiveRound'] = "0";
            }
            $this->Return['Message'] = "Auction player status successfully updated.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Rounds not found";
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
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $UserID = @$this->SessionUserID;
        if (empty($this->Post['SessionKey'])) {
            $UserID = @$this->UserID;
        }
        $AuctionBid = $this->SnakeDrafts_model->addAuctionPlayerBid($this->Post, $UserID, $this->SeriesID, $this->ContestID, $this->PlayerID);
        if ($AuctionBid['Status'] == 1) {
            $this->Return['Data'] = $AuctionBid['Data'];
            $this->Return['Message'] = "Player Bid successfully added.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $AuctionBid['Message'];
        }
    }

    /*
      Name:             getDraftGameInLive
      Description:  get live draft.
      URL:          /api_admin/auctionDrafts/getAuctionGameInLive/
     */

    public function getDraftGameInLive_post() {
        $AuctionGames = $this->SnakeDrafts_model->getContests('ContestID,ContestGUID,AuctionStatusID,AuctionStatus,SeriesGUID,RoundID,SeriesID', array('Filter' => 'LiveAuction', 'AuctionStatusID' => 1, "LeagueType" => "Draft"), TRUE, 1);
        if (!empty($AuctionGames)) {
            $this->Return['Data'] = $AuctionGames;
        }
    }

    /*
      Name:             getAuctionGameStatusUpdate
      Description:  get live auction.
      URL:          /api_admin/auctionDrafts/getAuctionGameStatusUpdate/
     */

    public function getDraftGameStatusUpdate_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        $AuctionStatus = $this->SnakeDrafts_model->getDraftGameStatusUpdate($this->Post, $this->ContestID, $this->StatusID);
        if ($AuctionStatus) {
            $this->Return['Message'] = "Auction status successfully updated.";
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Auction status already updated.";
        }
    }

    /*
      Name:             userLiveStatusUpdate
      Description:  set user onging to live.
      URL:          /api_admin/auctionDrafts/userLiveStatusUpdate/
     */

    public function userLiveStatusUpdate_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('UserStatus', 'UserStatus', 'trim|required|in_list[Yes,No]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */
        $AuctionStatus = $this->SnakeDrafts_model->userLiveStatusUpdate($this->Post, $this->ContestID, $this->UserID, $this->Post['SeriesID'], $this->Post['RoundID']);
        if ($AuctionStatus) {
            $this->Return['Message'] = "User status successfully updated.";
            $this->Return['Data']['DraftUserLiveTime'] = date('Y-m-d H:i:s');
            $this->Return['ServerDateTime'] = date('Y-m-d H:i:s');
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "User status already updated.";
        }
    }

    /*
      Name:             roundUpdate
      Description:  draft round update.
      URL:          /api_admin/auctionDrafts/roundUpdate/
     */

    public function roundUpdate_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('DraftLiveRound', 'DraftLiveRound', 'trim|required|numeric');
        $this->form_validation->validation($this);  /* Run validation */
        $AuctionStatus = $this->SnakeDrafts_model->roundUpdate($this->Post, $this->ContestID, $this->SeriesID);
        $this->Return['Message'] = "User status successfully updated.";
    }

    /*
      Name:             getUserInLive
      Description:  get user on going to live
      URL:          /api_admin/auctionDrafts/getUserInLive/
     */

    public function getUserInLive_post() {
        /* check game live */
        $UserList = $this->SnakeDrafts_model->getUserInLive();
        if ($UserList['Status'] == 1) {
            $this->Return['Data'] = $UserList['Data'];
            $this->Return['Message'] = $UserList['Message'];
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $UserList['Message'];
        }
    }

    /*
      Name:             checkUserDraftInlive
      Description:  get user on going to live
      URL:          /api_admin/auctionDrafts/checkUserInliveDraft/
     */

    public function checkUserDraftInlive_post() {
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->validation($this);  /* Run validation */
        /* check game live */
        $UserList = $this->SnakeDrafts_model->checkUserDraftInlive($this->Post, $this->Post['RoundID'], $this->ContestID);
        if ($UserList['Status'] == 1) {
            $this->Return['Data'] = $UserList['Data'][0];
            $this->Return['ServerDateTime'] = date('Y-m-d H:i:s');
            $this->Return['Message'] = $UserList['Message'];
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $UserList['Message'];
        }
    }

    /*
      Name:             draftPlayerSold
      Description:  draft player sold.
      URL:          /api_admin/auctionDrafts/draftPlayerSold/
     */

    public function draftPlayerSold_post() {
        $this->form_validation->set_rules('PlayerStatus', 'PlayerStatus', 'trim|required|in_list[Sold,Unsold]');
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        $Draft = $this->SnakeDrafts_model->draftPlayerSold($this->Post, $this->Post['SeriesID'], $this->ContestID, $this->UserID, @$this->PlayerID, $this->Post['RoundID']);
        $Draft['Data']['SeriesGUID'] = $this->Post['SeriesGUID'];
        $Draft['Data']['ContestGUID'] = $this->Post['ContestGUID'];
        $Draft['Data']['RoundID'] = $this->Post['RoundID'];
        $Draft['Data']['SeriesID'] = $this->Post['SeriesID'];
        $this->Return['ResponseCode'] = 200;
        $this->Return['Data'] = $Draft['Data'];
        $this->Return['Message'] = $Draft['Message'];
    }

    /*
      Name:             getRoundNextUserInLive
      Description:  get round next user in live
      URL:          /api_admin/auctionDrafts/getRoundNextUserInLive/
     */

    public function getRoundNextUserInLive_post() {
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]|callback_validateCheckAuctionInLive');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* check game live */
        $UserList = $this->SnakeDrafts_model->getRoundNextUserInLive($this->Post, $this->Post['SeriesID'], $this->Post['RoundID'], $this->ContestID);
        if ($UserList['Status'] == 1) {
            $this->Return['Data'] = $UserList['Data'];
            $this->Return['Message'] = $UserList['Message'];
        } else {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = $UserList['Message'];
        }
    }

    /*
      Description: 	Use to update user status.
     */

    public function changeUserStatus_post() {
        /* Validation section */
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]');
        $this->form_validation->set_rules('DraftUserStatus', 'DraftUserStatus', 'trim|required|in_list[Online,Offline]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $this->SnakeDrafts_model->changeUserStatus($this->Post, $this->UserID, $this->ContestID);
        $this->Return['Message'] = "Status has been changed.";
    }

    /**
     * Function Name: validateCheckAuctionInLive
     * Description:   To validate if check auction in live
     */
    public function validateCheckAuctionInLive($ContestGUID) {
        $AuctionGames = $this->SnakeDrafts_model->getContests('ContestID,AuctionStatusID', array('ContestGUID' => $ContestGUID), TRUE, 1);
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
        $ContestData = $this->SnakeDrafts_model->getContests('MatchID,ContestSize,Privacy,IsPaid,EntryType,EntryFee,UserInvitationCode,ContestID,ContestType,UserJoinLimit,CashBonusContribution', array('ContestID' => $this->ContestID));
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
                $Response = $this->SnakeDrafts_model->getJoinedContests('', $JoinContestWhere, TRUE, 1, 1);
                if (!empty($Response['Data']['TotalRecords'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Contest is already joined.');
                    return FALSE;
                }

                /* To Check User Team Match Details */
                /* if (!$this->SnakeDrafts_model->getUserTeams('', array('UserTeamID' => $this->UserTeamID, 'MatchID' => $ContestData['MatchID'], 'MatchInning' => $this->Post['JoinInning']))) {
                  $this->form_validation->set_message('validateUserJoinContest', 'Invalid UserTeamGUID.');
                  return FALSE;
                  } */
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
                $Response = $this->SnakeDrafts_model->getJoinedContests('', $JoinContestWhere, TRUE, 1, 1);
                if (!empty($Response['Data']['TotalRecords'])) {
                    $this->form_validation->set_message('validateUserJoinContest', 'Contest is already joined.');
                    return FALSE;
                }

                /* To Check User Team Match Details */
                /* if (!$this->SnakeDrafts_model->getUserTeams('', array('UserTeamID' => $this->UserTeamID, 'MatchID' => $ContestData['MatchID']))) {
                  $this->form_validation->set_message('validateUserJoinContest', 'Invalid UserTeamGUID.');
                  return FALSE;
                  } */

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
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        $ContestData = $this->SnakeDrafts_model->getContests(@$this->Post['Params'], array_merge($this->Post, array('UserInvitationCode' => @$this->Post['UserInvitationCode'], "RoundID" => $this->Post['RoundID'])), FALSE);
        if ($ContestData) {
            if (isset($ContestData['Data']['Records'])) {
                $this->Return['Data'] = array();
            } else {
                $this->Return['Data'] = $ContestData;
            }
        }
    }

    /*
      Description: To get joined contest users data
     */

    public function getJoinedContestsUsers_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        //$this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Joined Contest Users Data */
        $JoinedContestData = $this->SnakeDrafts_model->getJoinedContestsUsers(@$this->Post['Params'], array('ContestID' => @$this->ContestID, 'RoundID' => @$this->Post['RoundID'], 'SessionUserID' => @$this->SessionUserID), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($JoinedContestData)) {
            $this->Return['Data'] = $JoinedContestData['Data'];
        }
    }

    /*
      Name:             draftTeamPlayersSubmit
      Description:  user submit auction team after complete auction top 16 players c / vc.
      URL:          /api/auctionDrafts/draftTeamPlayersSubmit/
     */

    public function draftTeamPlayersSubmit_post() {
        /* Validation section */
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('UserTeamGUID', 'UserTeamGUID', 'trim|required|callback_validateEntityGUID[User Teams,UserTeamID]');
        $this->form_validation->set_rules('UserTeamPlayers', 'UserTeamPlayers', 'trim');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        if (!empty($this->Post['UserTeamPlayers']) && is_array($this->Post['UserTeamPlayers'])) {
            if (count($this->Post['UserTeamPlayers']) > 15) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Team Players length can't greater than 15.";
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
            } else if (!empty($PlayerPoisitions['ViceCaptain']) && $PlayerPoisitions['ViceCaptain'] != 1) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can select only 1 Vice Captain.";
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

        $Sql = "SELECT UserTeamID FROM sports_users_teams WHERE UserTeamID = '" . $this->UserTeamID . "' AND UserTeamType='Draft' AND IsPreTeam='No' AND AuctionTopPlayerSubmitted='Yes' LIMIT 1";
        $IsTeamSubmitted = $this->Sports_model->customQuery($Sql, TRUE);
        /* if (empty($IsTeamSubmitted)) { */
        if (!$this->SnakeDrafts_model->draftTeamPlayersSubmit($this->Post, $this->UserTeamID, $this->Post['RoundID'])) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Message'] = "Team Submitted successfully.";
        }
        /* } else {
          $this->Return['ResponseCode'] = 500;
          $this->Return['Message'] = "Your team already submitted by system.";
          } */
    }

    /*
      Description: To get snake draft player history
     */

    public function getContestDraftPlayerHistory_post() {
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        // $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get draft players list */
        $DraftedPlatyerList = $this->SnakeDrafts_model->getContestDraftPlayerHistory(@$this->Post['Params'], array('ContestID' => @$this->ContestID, 'RoundID' => $this->Post['RoundID']), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($DraftedPlatyerList)) {
            $this->Return['Data'] = $DraftedPlatyerList['Data'];
        }
    }

    /*
      Description: To get snake draft player history
     */

    public function getServerLiveDateTime_post() {
        $this->Return['Data']['AppTime'] = $this->Post['AppTime'];
        $this->Return['Data']['ServerTime'] = strtotime(date('Y-m-d H:i:s'));
    }

    /*
      Description: To get snake draft round wise player count
     */

    public function getRoundPlayers_post() {
        /* Validation section */
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $SeriesData = $this->Sports_model->getRoundPlayers($this->Post['RoundID']);
        $this->Return['Data'] = $SeriesData;
    }

    public function draftPlayersPoint_post() {
        $this->form_validation->set_rules('ContestGUID', 'ContestGUID', 'trim|required|callback_validateEntityGUID[Contest,ContestID]');
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $playersData = $this->SnakeDrafts_model->draftPlayersPoint(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => @$this->ContestID, 'PlayerID' => @$this->PlayerID, 'UserID' => @$this->SessionUserID, 'StatusID' => 5)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($playersData)) {
            $this->Return['Data'] = $playersData['Data'];
            $this->Return['status'] = 1;
        } else {
            $playersData = $this->SnakeDrafts_model->draftPlayersPoint(@$this->Post['Params'], array_merge($this->Post, array('ContestID' => @$this->ContestID, 'PlayerID' => @$this->PlayerID, 'UserID' => @$this->SessionUserID, 'StatusID' => 1)), TRUE, 1, 1);
            if (!empty($playersData)) {
                $this->Return['Data'] = $playersData['Data'];
                $this->Return['status'] = 0;
            }
        }
    }

}

?>