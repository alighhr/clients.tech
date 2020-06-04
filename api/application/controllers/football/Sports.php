<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Sports extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('football/Football_Sports_model', 'Football_Sports_model');
    }

    /*
      Description: To get series data
     */

    public function getSeries_post() {
        $SeriesData = $this->Football_Sports_model->getSeries(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID) : array()), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData['Data'];
        }
    }

    /*
      Description: To get rounds data
     */

    public function getRounds_post() {
        $SeriesData = $this->Football_Sports_model->getRounds(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID) : array()), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData['Data'];
        }
    }

    /*
      Description: To get matches data
     */

    public function getMatches_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('Filter', 'Filter', 'trim|in_list[Today,Series,MyJoinedMatch]');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Matches Data */
        $MatchesData = $this->Football_Sports_model->getMatches(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => @$this->SeriesID, 'StatusID' => $this->StatusID, 'UserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($MatchesData)) {
            $this->Return['Data'] = $MatchesData['Data'];
            $this->Return['Data']['UpcomingMatchesTime'] = MATCH_TIME_IN_HOUR;
        }
    }

    /*
      Description: To get match details
     */

    public function getMatch_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|callback_validateStatus');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Match Data */

        $MatchDetails = $this->Football_Sports_model->getMatches(@$this->Post['Params'], array_merge($this->Post, array('MatchID' => $this->MatchID, 'SessionUserID' => @$this->SessionUserID, 'StatusID' => @$this->StatusID)));
        if (!empty($MatchDetails)) {
            $this->Return['Data'] = $MatchDetails;
        }
    }

    /*
      Description: To get players data
     */

    public function getPlayers_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('TeamGUID', 'TeamGUID', 'trim|callback_validateEntityGUID[Teams,TeamID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $playersData = $this->Football_Sports_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('TeamID' => @$this->TeamID, 'MatchID' => @$this->MatchID, 'SeriesID' => @$this->SeriesID, 'UserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($playersData)) {
            $this->Return['Data'] = $playersData['Data'];
        }
    }

    /*
      Description: To get player details
     */

    public function getPlayer_post() {
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');        
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Player Data */
        $PlayerDetails = $this->Football_Sports_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('PlayerID' => $this->PlayerID)));
        if (!empty($PlayerDetails)) {
            $this->Return['Data'] = $PlayerDetails;
        }
    }

    /*
      Description: To get teams
     */

    public function getTeams_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('TeamGUID', 'TeamGUID', 'trim|callback_validateEntityGUID[Teams,TeamID]');

        $this->form_validation->validation($this);  /* Run validation */

        $TeamsData = $this->Football_Sports_model->getTeams(@$this->Post['Params'], array_merge($this->Post, array('TeamID' => @$this->TeamID, 'SeriesID' => @$this->SeriesID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($TeamsData)) {
            $this->Return['Data'] = $TeamsData['Data'];
        }
    }

    /*
      Description: To get team
     */

    public function getTeam_post() {
        $this->form_validation->set_rules('TeamGUID', 'TeamGUID', 'trim|required|callback_validateEntityGUID[Teams,TeamID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Match Data */
        $TeamDetails = $this->Football_Sports_model->getTeams(@$this->Post['Params'], array_merge($this->Post, array('TeamID' => $this->TeamID)));
        if (!empty($TeamDetails)) {
            $this->Return['Data'] = $TeamDetails;
        }
    }

    /*
      Description: To get sports points for app
     */

    public function getPointsApp_post() {
        $this->form_validation->set_rules('PointsCategory', 'PointsCategory', 'trim|in_list[Normal,InPlay,Reverse]');
        $this->form_validation->validation($this);  /* Run validation */

        $PointsData = $this->Football_Sports_model->getPointsApp($this->Post);
        if (!empty($PointsData)) {
            $this->Return['Data'] = $PointsData['Data'];
        }
    }

    /*
      Description: To get sports points
     */

    public function getPoints_post() {
        $this->form_validation->set_rules('PointsCategory', 'PointsCategory', 'trim|in_list[Normal,InPlay,Reverse]');
        $this->form_validation->validation($this);  /* Run validation */

        $PointsData = $this->Football_Sports_model->getPoints($this->Post);
        if (!empty($PointsData)) {
            $this->Return['Data'] = $PointsData['Data'];
        }
    }

    /*
      Description: To get sports best played players of the match
     */

    public function match_players_best_played_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */

        $BestTeamData = $this->Football_Sports_model->match_players_best_played(array('MatchID' => $this->MatchID), false);
        if (!empty($BestTeamData)) {
            $this->Return['Data'] = $BestTeamData['Data'];
        }
    }

    /*
      Description: To get sports best played players of the match
     */

    public function getMatchBestPlayers_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|required|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');

        $this->form_validation->validation($this);  /* Run validation */

        $BestTeamData = $this->Football_Sports_model->getMatchBestPlayers(array('MatchID' => $this->MatchID, 'UserID' => $this->SessionUserID), FALSE);
        if (!empty($BestTeamData)) {
            $this->Return['Data'] = $BestTeamData['Data'];
        }
    }

    /*
      Description: To get sports player fantasy stats series wise
     */

    public function getPlayerFantasyStats_post() {
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');

        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->validation($this);  /* Run validation */

        $PendingMatchStatsArr = $CompletedMatchesStatsArr = array();
        $TotalRecords = 0;

        /* Get Pending Match Stats */
        $PendingMatchStats = $this->Football_Sports_model->getPlayerFantasyStats(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'MatchID' => $this->MatchID, 'PlayerID' => $this->PlayerID, 'StatusID' => 1, 'OrderBy' => 'MatchStartDateTime', 'Sequence' => 'ASC')), TRUE, 1, 1);
        if (!empty($PendingMatchStats)) {
            $TotalRecords = $PendingMatchStats['Data']['TotalRecords'];
            $PendingMatchStatsArr = $PendingMatchStats['Data']['Records'];
        }

        /* Get Completed Matches Stats */
        $CompletedMatchesStats = $this->Football_Sports_model->getPlayerFantasyStats(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID, 'MatchID' => $this->MatchID, 'PlayerID' => $this->PlayerID, 'StatusID' => 5)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($CompletedMatchesStats)) {
            $TotalRecords += $CompletedMatchesStats['Data']['TotalRecords'];
            $CompletedMatchesStatsArr = $CompletedMatchesStats['Data']['Records'];
        }
        $this->Return['Data']['TotalRecords'] = $TotalRecords;
        $this->Return['Data']['Records'] = array_merge_recursive($PendingMatchStatsArr, $CompletedMatchesStatsArr);
        $this->Return['Data']['PlayerRole'] = $PendingMatchStatsArr[0]['PlayerRole'];
        $this->Return['Data']['PlayerRoleCompleted'] = $CompletedMatchesStats['Data']['Records'][0]['PlayerRole'];
        $this->Return['Data']['PlayerBattingStats'] = (!empty($CompletedMatchesStats['Data']['PlayerBattingStats']) ? $CompletedMatchesStats['Data']['PlayerBattingStats'] : new stdClass());
        $this->Return['Data']['PlayerBowlingStats'] = (!empty($CompletedMatchesStats['Data']['PlayerBowlingStats']) ? $CompletedMatchesStats['Data']['PlayerBowlingStats'] : new stdClass());
        $this->Return['Data']['PlayerFieldingStats'] = (!empty($CompletedMatchesStats['Data']['PlayerFieldingStats']) ? $CompletedMatchesStats['Data']['PlayerFieldingStats'] : new stdClass());
    }

    public function draftPlayersPoint_post() {
        //$this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        /* $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]'); */
        $this->form_validation->set_rules('PlayerGUID', 'PlayerGUID', 'trim|required|callback_validateEntityGUID[Players,PlayerID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|callback_validateSession');
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $playersData = $this->Football_Sports_model->draftPlayersPoint(@$this->Post['Params'], array_merge($this->Post, array('PlayerID' => @$this->PlayerID, 'UserID' => @$this->SessionUserID, 'StatusID' => 5)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($playersData)) {
            $this->Return['Data'] = $playersData['Data'];
            $this->Return['status'] = 1;
        } else {
            $playersData = $this->Football_Sports_model->draftPlayersPoint(@$this->Post['Params'], array_merge($this->Post, array('PlayerID' => @$this->PlayerID, 'UserID' => @$this->SessionUserID, 'StatusID' => 1)), TRUE, 1, 1);
            if (!empty($playersData)) {
                $this->Return['Data'] = $playersData['Data'];
                $this->Return['status'] = 0;
            }
        }
    }

}

?>
