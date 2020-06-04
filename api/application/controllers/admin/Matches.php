<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Matches extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('Sports_model');
        $this->load->model('Contest_model');
    }

    /*
      Description: To get matches data
     */

    public function getMatches_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('LocalTeamGUID', 'TeamGUID', 'trim|callback_validateEntityGUID[Teams,LTeamID]');
        $this->form_validation->set_rules('VisitorTeamGUID', 'TeamGUID', 'trim|callback_validateEntityGUID[Teams,VTeamID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Today,Series,AddDays]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Matches Data */
        $MatchesData = $this->Sports_model->getMatches(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID, 'TeamIDLocal' => @$this->LTeamID, 'TeamIDVisitor' => @$this->VTeamID) : array()), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($MatchesData)) {
            $this->Return['Data'] = $MatchesData['Data'];
        }
    }

    /*
      Description: To get match details
     */

    public function getMatch_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Match Data */
        $MatchDetails = $this->Sports_model->getMatches(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => $this->MatchID)), FALSE, 0);
        if (!empty($MatchDetails)) {
            $this->Return['Data'] = $MatchDetails;
        }
    }

    /*
      Description: 	Use to update user profile info.
      URL: 			/api_admin/entity/changeStatus/
     */

    public function changeStatus_post() {
        /* Validation section */
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        if ($this->Post['Status'] == 'Cancelled') {
            $ContestData = $this->Contest_model->getContests('ContestID,EntryFee', array('MatchID' => $this->MatchID), TRUE, 1, 100);
            if (!empty($ContestData['Data']['Records'])) {
                foreach ($ContestData['Data']['Records'] as $Contest) {
                    $this->Contest_model->cancelContest(array_merge($this->Post, $Contest), $this->SessionUserID, $Contest['ContestID']);
                }
            }
        }

        $this->Entity_model->updateEntityInfo($this->MatchID, array("StatusID" => $this->StatusID));
        $Message = "Status has been changed";

        $this->Return['Data'] = $this->Sports_model->getMatches('SeriesName,MatchType,MatchNo,MatchStartDateTime,MatchStartDateTimeUTC,TeamNameLocal,TeamNameVisitor,TeamNameShortLocal,TeamNameShortVisitor,TeamFlagLocal,TeamFlagVisitor,MatchLocation,Status', array('MatchID' => $this->MatchID), FALSE, 0);


        if (!empty($this->Post['IncreaseMatchTime'])) {
            if ($this->Post['APIAutoTimeUpdate']) {
                $APIAutoTimeUpdate = 'Yes';
            }
            $this->Sports_model->updateMatchTime($this->MatchID, array("Time" => $this->Post['IncreaseMatchTime'], 'MatchStartDateTime' => $this->Return['Data']['MatchStartDateTimeUTC'], 'APIAutoTimeUpdate' => @$APIAutoTimeUpdate));
            $Message = "Match Time Updated";
        }
        $this->Return['Message'] = $Message;
    }

    /*
      Description: 	Use to update user profile info.
      URL: 			/admin/matches/getFilterData/
     */

    public function getFilterData_post() {
        /* Validation section */
        $this->form_validation->set_rules('SeriesGUID', 'Series', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $SeriesData = $this->Sports_model->getSeries(@$this->Post['Params'], @$this->Post, true, 0);

        if (!empty($SeriesData)) {
            $Return['SeiresData'] = $SeriesData['Data']['Records'];
        }
        $this->Return['Data'] = empty($Return) ? array() : $Return;
    }

    public function getFilterDataSeries_post() {
        $ReturnSeries = $this->db->query("SELECT DISTINCT(sports_matches.SeriesID) as SeriesID, sports_series.SeriesName,sports_series.SeriesGUID FROM `sports_matches` join tbl_entity on tbl_entity.EntityID = sports_matches.MatchID join sports_series on sports_series.SeriesID = sports_matches.SeriesID WHERE tbl_entity.StatusID = 5")->result_array();
        $Return['SeiresData'] =  $ReturnSeries;
        $this->Return['Data'] = empty($Return) ? array() : $Return;
    }

    /* Description:  Use to update user profile info.
      URL:          /admin/matches/getFilterData/
     */

    public function getTeamData_post() {
        /* Validation section */
        $this->form_validation->set_rules('SeriesGUID', 'Series', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('LocalTeamGUID', 'TeamGUID', 'trim|callback_validateEntityGUID[Teams,TeamID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $TeamData = $this->Sports_model->getTeams(@$this->Post['Params'], array_merge(@$this->Post, array('SeriesID' => @$this->SeriesID, 'LocalTeamGUID' => @$this->TeamID)), true, 0);

        if (!empty($TeamData)) {
            $Return['TeamData'] = $TeamData['Data']['Records'];
        }
        $this->Return['Data'] = empty($Return) ? array() : $Return;
    }

    /*
      Description: 	Use to update player role.
      URL: 			/admin/matches/updatePlayerInfo/
     */

    public function updatePlayerInfo_post() {
        /* Validation section */
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim' . (empty($this->Post['MatchGUID']) ? '|required' : '') . '|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim' . (empty($this->Post['SeriesGUID']) ? '|required' : '') . '|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('PlayerRole', 'PlayerRole', 'trim|required|in_list[Batsman,Bowler,WicketKeeper,AllRounder]');
        $this->form_validation->set_rules('MediaGUIDs', 'MediaGUIDs', 'trim'); /* Media GUIDs */
        $this->form_validation->set_rules('IsActive', 'IsActive', 'trim|in_list[Yes,No]');
        $this->form_validation->validation($this);  /* Run validation */



        /* check for media present - associate media with this Post */
        if (!empty($this->Post['MediaGUIDs'])) {
            $MediaGUIDsArray = explode(",", $this->Post['MediaGUIDs']);
            foreach ($MediaGUIDsArray as $MediaGUID) {
                $EntityData = $this->Entity_model->getEntity('E.EntityID MediaID', array('EntityGUID' => $MediaGUID, 'EntityTypeID' => 4));
                if ($EntityData) {
                    $this->Media_model->addMediaToEntity($EntityData['MediaID'], $this->SessionUserID, $this->PlayerID);

                    /* Update Player Pic Media Name */
                    $this->db->query('UPDATE sports_players AS P, tbl_media AS M SET P.PlayerPic = M.MediaName WHERE M.EntityID = P.PlayerID AND M.MediaID = ' . $EntityData['MediaID']);
                }
            }
            $this->Return['Message'] = "Player image has been changed.";
            exit;
        }

        if ($this->Post['IsActive'] == 'No') {
            $check_player = $this->db->query("SELECT UserTeamID FROM sports_users_team_players WHERE MatchID = '" . $this->MatchID . "' AND PlayerID = '" . $this->PlayerID . "' limit 1")->result_array();
            if (!empty($check_player)) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can't inactive this player because the player already in user team.";
                exit;
            }
        } else {
            $check_player = $this->db->query("SELECT UserTeamID FROM sports_users_team_players WHERE MatchID = '" . $this->MatchID . "' AND PlayerID = '" . $this->PlayerID . "' limit 1")->result_array();
            if (!empty($check_player)) {
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "You can't change player role because the player already in user team.";
                exit;
            }
        }
        $SeriesID = $this->db->query("SELECT SeriesID FROM sports_matches WHERE MatchID = '" . $this->MatchID . "'")->row()->SeriesID;
        /* $Matches = $this->db->query("SELECT
          M.MatchID
          FROM
          sports_matches M
          WHERE NOT EXISTS
          (
          SELECT
          1
          FROM
          sports_users_teams
          WHERE
          sports_users_teams.MatchID = M.MatchID
          ) AND M.SeriesID = '" . $SeriesID . "' AND DATE(M.MatchStartDateTime) >= '" . date('Y-m-d') . "'")->result_array(); */

        $Matches = $this->db->query("SELECT
                                        M.MatchID,MatchStartDateTime
                                    FROM
                                        sports_matches M
                                    WHERE  M.SeriesID = '" . $SeriesID . "' AND DATE(M.MatchStartDateTime) >= '" . date('Y-m-d') . "'")->result_array();


        /* Validation - ends */
        if (!empty($Matches)) {
            foreach ($Matches as $Rows) {
                $check_player = $this->db->query("SELECT UserTeamID FROM sports_users_team_players WHERE MatchID = '" . $Rows['MatchID'] . "' AND PlayerID = '" . $this->PlayerID . "' limit 1")->result_array();
                if (empty($check_player)) {
                    $this->Sports_model->updatePlayerRole($this->PlayerID, $Rows['MatchID'], array("PlayerRole" => $this->Post['PlayerRole'], "IsActive" => $this->Post['IsActive'], 'IsAdminUpdate' => 'Yes'));
                }
            }
        }

        $this->Return['Data'] = $this->Sports_model->getPlayers('PlayerSalaryCredit,TeamGUID,TeamName,TeamNameShort,TeamFlag,PlayerID,PlayerIDLive,PlayerRole,IsPlaying,PlayerSalary,SeriesID,MatchID,PlayerPic,PlayerCountry,PlayerBattingStyle,PlayerBowlingStyle,PlayerBattingStats,PlayerBowlingStats', array('PlayerID' => $this->PlayerID, 'MatchID' => $this->MatchID), FALSE, 0);
        $this->Return['Message'] = "Player role has been changed.";
    }

    /*
      Description: 	Use to update player role.
      URL: 			/admin/matches/updatePlayerAuctionDraft/
     */

    public function updatePlayerAuctionDraft_post() {
        /* Validation section */
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('PlayerRole', 'PlayerRole', 'trim|required|in_list[Batsman,Bowler,WicketKeeper,AllRounder]');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        /* Validation - ends */
        $this->Sports_model->updatePlayerRoleAuctionDraft($this->PlayerID, $this->Post['RoundID'], array("PlayerRole" => $this->Post['PlayerRole'], 'IsAdminUpdate' => 'Yes'));

        /* check for media present - associate media with this Post */
        if (!empty($this->Post['MediaGUIDs'])) {
            $MediaGUIDsArray = explode(",", $this->Post['MediaGUIDs']);
            foreach ($MediaGUIDsArray as $MediaGUID) {
                $EntityData = $this->Entity_model->getEntity('E.EntityID MediaID', array('EntityGUID' => $MediaGUID, 'EntityTypeID' => 4));
                if ($EntityData) {
                    $this->Media_model->addMediaToEntity($EntityData['MediaID'], $this->SessionUserID, $this->PlayerID);

                    /* Update Player Pic Media Name */
                    $this->db->query('UPDATE sports_players AS P, tbl_media AS M SET P.PlayerPic = M.MediaName WHERE M.EntityID = P.PlayerID AND M.MediaID = ' . $EntityData['MediaID']);
                }
            }
        }
        $this->Return['Data'] = $this->Sports_model->getAuctionDraftPlayers('TeamGUID,TeamName,TeamNameShort,TeamFlag,PlayerID,PlayerIDLive,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStyle,PlayerBowlingStyle,PlayerBattingStats,PlayerBowlingStats', array('PlayerID' => $this->PlayerID, 'RoundID' => $this->Post['RoundID']), FALSE, 0);
        $this->Return['Message'] = "Player role has been changed.";
    }

    /*
      Description: 	Use to update player salary.
      URL: 			/admin/matches/updatePlayerSalary/
     */

    public function updatePlayerSalary_post() {
        /* Validation section */
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('PlayerSalaryCredit', 'PlayerSalaryCredit', 'trim|required|numeric');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $SeriesID = $this->db->query("SELECT SeriesID FROM sports_matches WHERE MatchID = '" . $this->MatchID . "'")->row()->SeriesID;
        $Matches = $this->db->query("SELECT
                                        M.MatchID,MatchStartDateTime
                                    FROM
                                        sports_matches M
                                    WHERE  M.SeriesID = '" . $SeriesID . "' AND DATE(M.MatchStartDateTime) >= '" . date('Y-m-d') . "'")->result_array();
        $Flag = TRUE;
        if (!empty($Matches)) {
         
            foreach ($Matches as $Rows) {
                $check_player = $this->db->query("SELECT UserTeamID FROM sports_users_team_players WHERE MatchID = '" . $Rows['MatchID'] . "' AND PlayerID = '" . $this->PlayerID . "' limit 1")->result_array();
                if (empty($check_player)) {
                    $this->Sports_model->updatePlayerSalaryMatch($this->Post, $this->PlayerID, $Rows['MatchID']);
                } else {
                    if ($this->MatchID == $Rows['MatchID']) {
                        $Flag = FALSE;
                    }
                }
            }
        }

        if (!$Flag) {
            $this->Return['Message'] = "Player salary has been changed only upcoming matches not current select match.";
        } else {
            $this->Return['Message'] = "Player salary has been changed current select match and upcoming matches.";
        }

        /* $TeamAvailable = $this->db->query("SELECT UserTeamID FROM sports_users_team_players WHERE MatchID = '" . $this->MatchID . "' AND PlayerID = '" . $this->PlayerID . "' limit 1")->result_array();
          if (!empty($TeamAvailable)) {
          $this->Return['ResponseCode'] = 500;
          $this->Return['Message'] = "You can't change player salary because the player already in user team";
          exit;
          }
          $this->Sports_model->updatePlayerSalaryMatch($this->Post, $this->PlayerID, $this->MatchID);
          $this->Return['Message'] = "Player salary has been changed."; */
    }

    /*
      Description: 	Use to update user profile info.
      URL: 			/admin/matches/getFilterData/
     */

    public function getFilterDataRounds_post() {
        /* Validation section */
        $this->form_validation->set_rules('SeriesGUID', 'Series', 'trim|callback_validateEntityGUID[Series,SeriesGUID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $SeriesData = $this->Sports_model->getRounds(@$this->Post['Params'], @$this->Post, true, 0);

        if (!empty($SeriesData)) {
            $Return['SeiresData'] = $SeriesData['Data']['Records'];
        }
        $this->Return['Data'] = empty($Return) ? array() : $Return;
    }

    public function getRoundPlayers_post() {
        /* Validation section */
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $SeriesData = $this->Sports_model->getRoundPlayers($this->Post['RoundID']);
        $this->Return['Data'] = $SeriesData;
    }

}

;
?>
