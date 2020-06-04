<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('Contest_model');
        $this->load->model('Sports_model');
    }

    /*
      Description: To get contest winning users
     */

    public function getMatchWiseReports_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'required|trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'required|trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->Contest_model->getMatchWiseReports(@$this->SeriesID, @$this->MatchID);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData;
        }
    }

    /*
      Description: To get contest winning users
     */

    public function getAccountReport_post() {
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->Contest_model->getAccountReport($this->Post, @$this->SeriesID);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData;
        }
    }

    /*
      Description: To get contest winning users
     */

    public function getUserAnalysisReport_post() {
        $this->form_validation->set_rules('UserType', 'User Type', 'trim|required');
        $this->form_validation->set_rules('DataFilter', 'Date Filter', 'trim|required');
        $this->form_validation->set_rules('FromDate', 'FromDate', 'trim');
        $this->form_validation->set_rules('ToDate', 'ToDate', 'trim');
        $this->form_validation->validation($this);  /* Run validation */
        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->Contest_model->getUserAnalysisReport($this->Post);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData;
        }
    }

    /*
      Description: To get contest winning users
     */

    public function getContestName_post() {
        $this->Return['Data'] = $this->Contest_model->getContestName();
    }

    /*
      Description: To get contest winning users
     */

    public function getContestAnalysisReport_post() {
        $this->form_validation->set_rules('ContestType', 'ContestType', 'trim|required');
        $this->form_validation->set_rules('ContestName', 'ContestName', 'trim|required');
        $this->form_validation->set_rules('SeriesGUID', 'SeriesGUID', 'trim|callback_validateEntityGUID[Series,SeriesID]');
        $this->form_validation->set_rules('MatchGUID', 'MatchGUID', 'trim|callback_validateEntityGUID[Matches,MatchID]');
        $this->form_validation->set_rules('FromDate', 'FromDate', 'trim');
        $this->form_validation->set_rules('ToDate', 'ToDate', 'trim');

        $this->form_validation->validation($this);  /* Run validation */
        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->Contest_model->getContestAnalysisReport($this->Post, @$this->SeriesID, @$this->MatchID);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData;
        }
    }

    /*
      Description: To get contest winning users
     */

    public function getUserRegisterReport_post() {
        $this->form_validation->set_rules('DataFilter', 'Date Filter', 'trim|required');
        $this->form_validation->set_rules('FromDate', 'FromDate', 'trim');
        $this->form_validation->set_rules('ToDate', 'ToDate', 'trim');

        $this->form_validation->validation($this);  /* Run validation */
        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->Contest_model->getUserRegisterReport($this->Post, @$this->SeriesID, @$this->MatchID);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData;
        }
    }

    /*
      Description: To get contest winning users
     */

    public function getUserJoinedFeeReport_post() {
        $this->form_validation->set_rules('DataFilter', 'Date Filter', 'trim|required');
        $this->form_validation->set_rules('EntryFeeRange', 'Entry Fee Range', 'trim|required');
        $this->form_validation->set_rules('FromDate', 'FromDate', 'trim');
        $this->form_validation->set_rules('ToDate', 'ToDate', 'trim');

        $this->form_validation->validation($this);  /* Run validation */
        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->Contest_model->getUserJoinedFeeReport($this->Post, @$this->SeriesID, @$this->MatchID);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData;
        }
    }

    /*
      Description: To get contest winning users
     */

    public function getUserPlanningLifetimeReport_post() {
        $this->form_validation->set_rules('DataFilter', 'Date Filter', 'trim|required');
        $this->form_validation->set_rules('FromDate', 'FromDate', 'trim');
        $this->form_validation->set_rules('ToDate', 'ToDate', 'trim');

        $this->form_validation->validation($this);  /* Run validation */
        /* Get Contests Winning Users Data */
        $WinningUsersData = $this->Contest_model->getUserPlanningLifetimeReport($this->Post, @$this->SeriesID, @$this->MatchID);
        if (!empty($WinningUsersData)) {
            $this->Return['Data'] = $WinningUsersData;
        }
    }

}

?>