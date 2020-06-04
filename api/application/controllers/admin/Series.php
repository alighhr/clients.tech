<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Series extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('Sports_model');
        $this->load->model('Contest_model');
    }

    /*
      Description: To get series data
     */

    public function getSeries_post() {
        $SeriesData = $this->Sports_model->getSeries(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID) : array()), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData['Data'];
        }
    }

    /*
      Description: To get rounds data
     */

    public function getRounds_post() {
        /* Validation section */
        $this->form_validation->set_rules('SeriesGUID', 'Series', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        $SeriesData = $this->Sports_model->getRounds(@$this->Post['Params'], array_merge($this->Post, (!empty($this->Post['SeriesGUID'])) ? array('SeriesID' => $this->SeriesID) : array()), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);

        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData['Data'];
        }
    }
    
        /*
      Description: To get players data
     */

    public function getAuctionDraftPlayers_post() {
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('TeamGUID', 'TeamGUID', 'trim|callback_validateEntityGUID[Teams,TeamID]');
        $this->form_validation->set_rules('Keyword', 'Search Keyword', 'trim');
        $this->form_validation->set_rules('OrderBy', 'OrderBy', 'trim');
        $this->form_validation->set_rules('Sequence', 'Sequence', 'trim|in_list[ASC,DESC]');
        $this->form_validation->set_rules('SessionKey', 'SessionKey', 'trim|required|callback_validateSession');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Players Data */
        $playersData = $this->Sports_model->getAuctionDraftPlayers(@$this->Post['Params'], array_merge($this->Post, array('TeamID' => @$this->TeamID, 'MatchID' => @$this->MatchID, 'SeriesID' => @$this->SeriesID, 'UserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($playersData)) {
            $this->Return['Data'] = $playersData['Data'];
        }
    }

    /*
      Description: 	use to get list of filters
      URL: 			/api_admin/entity/getFilterData
     */

    public function getFilterData_post() {
        /* Validation section */
        $this->form_validation->set_rules('SeriesGUID', 'Series', 'trim|callback_validateEntityGUID[Series,SeriesGUID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */


        $CategoryTypes = $this->Category_model->getCategoryTypes('', array("ParentCategoryID" => @$this->ParentCategoryID), true, 1, 250);
        if ($CategoryTypes) {
            $Return['CategoryTypes'] = $CategoryTypes['Data']['Records'];
        }
        $this->Return['Data'] = $Return;

        $SeriesData = $this->Sports_model->getSeries(@$this->Post['Params'], array());

        if (!empty($SeriesData)) {
            $Return['SeiresData'] = $SeriesData['Data']['Records'];
        }
        $this->Return['Data'] = $Return;
    }

    /*
      Description: 	Use to update series status.
      URL: 			/api_admin/entity/changeStatus/
     */

    public function changeStatus_post() {
        /* Validation section */
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('Status', 'Status', 'trim|required|callback_validateStatus');
        //$this->form_validation->set_rules('AuctionDraftIsPlayed', 'AuctionDraftIsPlayed', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */
        if (!empty($this->Post['DraftPlayerSelectionCriteria'])) {
            $DraftPlayerSelectionCriteria = array(
                "Wk" => $this->Post['DraftPlayerSelectionCriteria'][0],
                "Bat" => $this->Post['DraftPlayerSelectionCriteria'][1],
                "Ar" => $this->Post['DraftPlayerSelectionCriteria'][2],
                "Bowl" => $this->Post['DraftPlayerSelectionCriteria'][3],
            );
        }
        if ($this->Post['Status'] == 'Inactive') {
            $MatchesData = $this->Sports_model->getMatches('MatchID', array('SeriesID' => $this->SeriesID, 'StatusID' => 1), TRUE, 0);
            if (!empty($MatchesData['Data']['Records'])) {
                foreach ($MatchesData['Data']['Records'] as $value) {
                    $updateMatchStatus = $this->Entity_model->updateEntityInfo($value['MatchID'], array("StatusID" => 3));
                    if ($updateMatchStatus) {
                        $ContestQuery = 'SELECT ContestID,EntryFee FROM sports_contest WHERE MatchID =' . $value['MatchID'];
                        $ContestData = $this->db->query($ContestQuery)->result_array();
                        if (!empty($ContestData)) {
                            foreach ($ContestData as $Contest) {
                                $this->Contest_model->cancelContest($Contest, $this->SessionUserID, $Contest['ContestID']);
                            }
                        }
                    }
                }
            }
        }
        if ($this->Post['Status'] == 'Active') {
            $MatchesData = $this->Sports_model->getMatches('MatchID', array('SeriesID' => $this->SeriesID, 'StatusID' => 3, 'MatchStartDateTimeComplete' => date('Y-m-d H:i')), TRUE, 0);
            if (!empty($MatchesData['Data']['Records'])) {
                foreach ($MatchesData['Data']['Records'] as $value) {
                    $updateMatchStatus = $this->Entity_model->updateEntityInfo($value['MatchID'], array("StatusID" => 1));
                }
            }
        }
        $this->Entity_model->updateEntityInfo($this->SeriesID, array("StatusID" => $this->StatusID, "AuctionDraftIsPlayed" => $this->AuctionDraftIsPlayed));
        $this->Sports_model->updateAuctionPlayStatus($this->SeriesID, array("SeriesName" => $this->Post['SeriesName'], "AuctionDraftIsPlayed" => $this->Post['AuctionDraftIsPlayed'], "DraftUserLimit" => $this->Post['DraftUserLimit'], "DraftTeamPlayerLimit" => $this->Post['DraftTeamPlayerLimit'], "DraftPlayerSelectionCriteria" => json_encode($DraftPlayerSelectionCriteria)));
        $this->Return['Data'] = $this->Sports_model->getSeries('SeriesName,SeriesGUID,StatusID,Status,SeriesStartDate,SeriesEndDate', array('SeriesID' => $this->SeriesID), FALSE, 0);
        $this->Return['Message'] = "Status has been changed.";
    }

    /*
      Description : use to get series details
      URL 		: /api_admin/series/getSeriesDetails
     */

    public function getSeriesDetails_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|required|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */

        /* Get Contests Data */
        $SeriesData = $this->Sports_model->getSeries(@$this->Post['Params'], array_merge($this->Post, array('SeriesID' => $this->SeriesID)), FALSE, 0);
        if (!empty($SeriesData)) {
            $this->Return['Data'] = $SeriesData;
        }
    }

    public function updateRounds_post() {
        $this->form_validation->set_rules('RoundID', 'RoundID', 'trim|required');
        $this->form_validation->set_rules('AuctionDraftIsPlayed', 'AuctionDraftIsPlayed', 'trim|required');
        $this->form_validation->validation($this);  /* Run validation */

        $updateData = $this->Sports_model->updateRounds($this->Post);
        if ($updateData) {
            $this->Return['Message'] = 'Updated';
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
        $playersData = $this->Sports_model->getPlayers(@$this->Post['Params'], array_merge($this->Post, array('TeamID' => @$this->TeamID, 'MatchID' => @$this->MatchID, 'SeriesID' => @$this->SeriesID, 'UserID' => @$this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if (!empty($playersData)) {
            $this->Return['Data'] = $playersData['Data'];
        }
    }

}

?>
