<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class TestApp extends API_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Contest_model');
        $this->load->model('Sports_model');
        $this->load->model('Recovery_model');
        $this->load->model('Utility_model');
    }

    /*
      Name: 			createUsers
      Description: 	create dummy users
      URL: 			/testApp/createUsers/
     */

    public function createUsers_get() {

        $tlds = array("com");
        $char = "0123456789abcdefghijklmnopqrstuvwxyz";
        for ($j = 0; $j < 50000; $j++) {

            $ulen = mt_rand(5, 10);
            $dlen = mt_rand(7, 17);
            $email = "";
            for ($i = 1; $i <= $ulen; $i++) {
                $email .= substr($char, mt_rand(0, strlen($char)), 1);
            }
            $email .= "@";
            $email .= "gmail";
            $email .= ".";
            $email .= $tlds[mt_rand(0, (sizeof($tlds) - 1))];
            $username = explode('@', $email);
            $Input = array();
            $Input['Email'] = $email;
            $Input['Username'] = $username[0];
            $Input['FirstName'] = strtoupper($username[0]);
            $Input['Password'] = 123456;
            $Input['Source'] = "Direct";
            $Input['PanStatus'] = 2;
            $Input['BankStatus'] = 2;
            $UserID = $this->Users_model->addUser($Input, 3, 1, 2);
            if ($UserID) {
                $this->Utility_model->generateReferralCode($UserID);
                $WalletData = array(
                    "Amount" => 1000,
                    "CashBonus" => 0,
                    "TransactionType" => 'Cr',
                    "Narration" => 'Deposit Money',
                    "EntryDate" => date("Y-m-d H:i:s")
                );
                $this->Users_model->addToWallet($WalletData, $UserID, 5);
            }
        }
    }

    /*
      Name: 			createTeam
      Description: 	create dummy team
      URL: 			/testApp/createTeam/
     */

    public function createTeam_get() {

        $AllUsers = $this->Users_model->getUsers('UserID', array('UserTypeID' => 3), true, 1, 500);
        if (!empty($AllUsers)) {
            $Match = $this->Sports_model->getMatches('SeriesID,MatchID,TeamIDLocal,TeamIDVisitor', array('StatusID' => array(1, 2),'MatchID' => 353112), False, 1, 1);
            if (!empty($Match)) {
                $MatchID = $Match['MatchID'];
                $SeriesID = $Match['SeriesID'];
                $playersData = $this->Sports_model->getPlayers('PlayerID,TeamID,PlayerRole,PlayerSalary', array('MatchID' => $MatchID, 'RandData' => "rand()"), TRUE, 1, 50);
                if (!empty($playersData)) {
                    $unique = 0;
                    foreach ($AllUsers['Data']['Records'] as $users) {
                        if ($unique % 2 == 0) {
                            $localteamIDS = $Match['TeamIDLocal'];
                            $visitorteamIDS = $Match['TeamIDVisitor'];
                        } else {
                            $visitorteamIDS = $Match['TeamIDLocal'];
                            $localteamIDS = $Match['TeamIDVisitor'];
                        }
                        $this->createTeamProcessByMatch($playersData, $localteamIDS, $visitorteamIDS, $SeriesID, $users['UserID'], $MatchID);
                        $unique++;
                    }
                }
            }
        }
    }

    /*
      Name: 			createTeamProcessByMatch
      Description: 	common create team
      URL: 			/testApp/createTeamProcessByMatch/
     */

    public function createTeamProcessByMatch($matchPlayer, $localteam_id, $visitorteam_id, $series_id, $user_id, $match_id) {
        $returnArray = array();
        $playerCount = 1;
        $secondPlayerCount = 1;
        $batsman = 0;
        $wicketkeeper = 0;
        $bowler = 0;
        $allrounder = 0;
        $teamCount = 0;
        $teamB = array();
        $Arr1 = array();
        $Arr2 = array();
        $Arr3 = array();
        $Arr4 = array();
        $Arr5 = array();
        $Arr6 = array();
        $Arr7 = array();
        $Arr8 = array();
        $creditPoints = 0;
        $points = 0;
        $selectedViceCaptainPlayer = [];
        $selectedCaptainPlayer = [];
        foreach ($matchPlayer['Data']['Records'] as $player) {
            if (count($playerCount) <= 7) {
                $playerId = $player['PlayerID'];
                $teamId = $player['TeamID'];
                $playerRole = strtoupper($player['PlayerRole']);
                $creditPoints += 9;
                if ($teamId == $localteam_id) {
                    if ($wicketkeeper < 1) {
                        if ($playerRole == 'WICKETKEEPER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'ViceCaptain';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PlayerSalary']->T20Credits;
                            $Arr1[] = $temp;
                            $wicketkeeper++;
                        }
                    } if ($batsman < 3) {
                        if ($playerRole == 'BATSMAN') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PlayerSalary']->T20Credits;
                            $Arr2[] = $temp;
                            $batsman++;
                        }
                    }if ($bowler < 2) {
                        if ($playerRole == 'BOWLER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Player';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PlayerSalary']->T20Credits;
                            $Arr3[] = $temp;
                            $bowler++;
                        }
                    }if ($allrounder < 1) {
                        if ($playerRole == 'ALLROUNDER') {
                            $temp['play_role'] = strtoupper($player['PlayerRole']);
                            $temp['play_id'] = $player['PlayerID'];
                            $temp['team_id'] = $teamId;
                            $temp['PlayerPosition'] = 'Captain';
                            $temp['PlayerGUID'] = $player['PlayerGUID'];
                            $temp['creditPoints'] = $player['PlayerSalary']->T20Credits;
                            $Arr4[] = $temp;
                            $allrounder++;
                        }
                    }
                }
            }
            $playerCount++;
            $res1 = array_merge($Arr1, $Arr2, $Arr3, $Arr4);
        }
        foreach ($matchPlayer['Data']['Records'] as $player) {
            if (count($secondPlayerCount) <= 4) {
                $playerId = $player['PlayerID'];
                $teamId = $player['TeamID'];
                $playerRole = strtoupper($player['PlayerRole']);
                if ($teamId == $visitorteam_id) {
                    if ($wicketkeeper < 1) {
                        if ($playerRole == 'WICKETKEEPER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'ViceCaptain';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PlayerSalary']->T20Credits;
                            $Arr5[] = $temp1;
                            $wicketkeeper++;
                        }
                    } if ($batsman < 4) {
                        if ($playerRole == 'BATSMAN') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PlayerSalary']->T20Credits;
                            $Arr6[] = $temp1;
                            $batsman++;
                        }
                    }if ($bowler < 4) {
                        if ($playerRole == 'BOWLER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PlayerSalary']->T20Credits;
                            $Arr7[] = $temp1;
                            $bowler++;
                        }
                    }if ($allrounder < 2) {
                        if ($playerRole == 'ALLROUNDER') {
                            $temp1['play_role'] = strtoupper($player['PlayerRole']);
                            $temp1['play_id'] = $player['PlayerID'];
                            $temp1['team_id'] = $teamId;
                            $temp1['PlayerPosition'] = 'Player';
                            $temp1['PlayerGUID'] = $player['PlayerGUID'];
                            $temp1['creditPoints'] = $player['PlayerSalary']->T20Credits;
                            $Arr8[] = $temp1;
                            $allrounder++;
                        }
                    }
                }
            }
            $secondPlayerCount++;
            $res2 = array_merge($Arr5, $Arr6, $Arr7, $Arr8);
        }
        $playing11 = array_merge($res2, $res1);
        if (count($playing11) == 11) {
            $this->Contest_model->addUserTeam(array('UserTeamPlayers' => json_encode($playing11), 'UserTeamType' => 'Normal'), $user_id, $match_id);
        }
        return true;
    }

    /*
      Name: 			autoJoinContest
      Description: 	join dummy team
      URL: 			/testApp/autoJoinContest/
     */

    public function autoJoinContest_get() {
        $Match = $this->Sports_model->getMatches('SeriesID,MatchID,TeamIDLocal,TeamIDVisitor,MatchStartDateTime', array('StatusID' => array(1, 2),'MatchID' => 353112), False, 1, 1);
        if (!empty($Match)) {
            $MatchID = $Match['MatchID'];
            $SeriesID = $Match['SeriesID'];
            $ContestData = $this->Contest_model->getContests("ContestID,ContestSize,TotalJoined", array('MatchID' => $MatchID,
                'ContestType' => "Normal",
                'IsWinningDistributed' => 'No',
                "ContestFull" => 'No',
                "StatusID" => 1), TRUE, 1, 50);
            if (!empty($ContestData)) {
                foreach ($ContestData['Data']['Records'] as $contest) {
                    $AllUsers = $this->Users_model->getUsers('UserID', array('UserTypeID' => 3), true, 1, $contest['ContestSize']);
                    if (!empty($AllUsers)) {
                        foreach ($AllUsers['Data']['Records'] as $users) {
                            $UserTeams = $this->Contest_model->getUserTeams('UserTeamID,MatchID', array('UserID' => $users['UserID'], 'MatchID' => $MatchID), FALSE, 0);
                            if (!empty($UserTeams)) {
                                $this->Contest_model->joinContest(array("MatchID" => $MatchID), $users['UserID'], $contest['ContestID'],$MatchID, $UserTeams['UserTeamID']);
                            }
                        }
                    }
                }
            }
        }
    }

}

?>