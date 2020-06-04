<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class SnakeDrafts_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Sports_model');
        $this->load->model('Settings_model');
    }

    /*
      Description: To get all series
     */

    function getSeries($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'SeriesID' => 'S.SeriesID',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
                'StatusID' => 'E.StatusID',
                'SeriesIDLive' => 'S.SeriesIDLive',
                'AuctionDraftIsPlayed' => 'S.AuctionDraftIsPlayed',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
                'SeriesStartDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesStartDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesStartDate',
                'SeriesEndDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesEndDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesEndDate',
                'SeriesStartDateUTC' => 'S.SeriesStartDate as SeriesStartDateUTC',
                'SeriesEndDateUTC' => 'S.SeriesEndDate as SeriesEndDateUTC',
                'TotalMatches' => '(SELECT COUNT(*) AS TotalMatches
                FROM sports_matches
                WHERE sports_matches.SeriesID =  S.SeriesID ) AS TotalMatches',
                'SeriesMatchStartDate' => '(SELECT MatchStartDateTime AS SeriesMatchStartDate
                FROM sports_matches
                WHERE sports_matches.SeriesID =  S.SeriesID order by MatchStartDateTime asc limit 1) AS SeriesMatchStartDate',
                'Status' => 'CASE E.StatusID
                when "2" then "Active"
                when "6" then "Inactive"
                END as Status',
                'AuctionDraftStatus' => 'CASE S.AuctionDraftStatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "5" then "Completed"
                END as AuctionDraftStatus',
                //'IsJoinedContest' => '(select count(SeriesID) from sports_contest_join where SeriesID = S.SeriesID AND E.StatusID=' . (!is_array(@$Where['StatusID'])) ? @$Where['StatusID'] : 2 . ') AND UserID=' . @$Where['SessionUserID'] . ') as IsJoinedContest',
                'TotalUserWinning' => '(select SUM(JC.UserWinningAmount) from sports_contest_join JC where JC.SeriesID = S.SeriesID AND E.StatusID=6 AND JC.UserID=' . @$Where['SessionUserID'] . ') as TotalUserWinning'
            );

            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('S.SeriesGUID,S.SeriesName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_series S');
        $this->db->where("S.SeriesID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $this->db->like("S.SeriesName", $Where['Keyword']);
        }
        if (!empty($Where['DraftAuctionPlay']) && $Where['DraftAuctionPlay'] == "Yes") {
            $this->db->where("S.AuctionDraftIsPlayed", "Yes");
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("S.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['AuctionDraftIsPlayed'])) {
            $this->db->where("S.AuctionDraftIsPlayed", $Where['AuctionDraftIsPlayed']);
        }
        if (!empty($Where['SeriesStartDate'])) {
            $this->db->where("S.SeriesStartDate >=", $Where['SeriesStartDate']);
        }
        if (!empty($Where['SeriesEndDate'])) {
            $this->db->where("S.SeriesEndDate >=", $Where['SeriesEndDate']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['AuctionDraftIsPlayed'])) {
            $this->db->where("S.AuctionDraftIsPlayed", $Where['AuctionDraftIsPlayed']);
        }
        if (!empty($Where['AuctionDraftStatusID'])) {
            $this->db->where("S.AuctionDraftStatusID", $Where['AuctionDraftStatusID']);
        }

        if (isset($Where['MyJoinedSeries']) && $Where['MyJoinedSeries'] = "Yes") {
            $this->db->where('EXISTS (select ContestID from sports_contest_join JE where JE.SeriesID = S.SeriesID AND JE.UserID=' . @$Where['SessionUserID'] . ')');
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('E.StatusID', 'ASC');
        $this->db->order_by('S.SeriesStartDate', 'DESC');
        $this->db->order_by('S.SeriesName', 'ASC');

        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }

        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                if (!empty($Where['MyJoinedSeriesCount']) && $Where['MyJoinedSeriesCount'] == "Yes") {
                    $Return['Data']['Statics'] = $this->db->query('SELECT (
                            SELECT COUNT(DISTINCT S.SeriesID) AS `UpcomingJoinedContest` FROM `sports_series` S
                            JOIN `sports_contest_join` J ON S.SeriesID = J.SeriesID JOIN `tbl_entity` E ON E.EntityID = J.SeriesID WHERE E.StatusID = 1 AND J.UserID ="' . @$Where['SessionUserID'] . '" 
                        )as UpcomingJoinedContest,
                        ( SELECT COUNT(DISTINCT S.SeriesID) AS `LiveJoinedContest` FROM `sports_series` S JOIN `sports_contest_join` J ON S.SeriesID = J.SeriesID JOIN `tbl_entity` E ON E.EntityID = J.SeriesID WHERE  E.StatusID = 2 AND J.UserID = "' . @$Where['SessionUserID'] . '" 
                        )as LiveJoinedContest,
                        ( SELECT COUNT(DISTINCT S.SeriesID) AS `CompletedJoinedContest` FROM `sports_series` S JOIN `sports_contest_join` J ON S.SeriesID = J.SeriesID JOIN `tbl_entity` E ON E.EntityID = J.SeriesID WHERE  E.StatusID = 5 AND J.UserID = "' . @$Where['SessionUserID'] . '" 
                    )as CompletedJoinedContest'
                            )->row();
                }
                return $Return;
            } else {
                return $Query->row_array();
            }
        }
        return FALSE;
    }

    /*
      Description: To get all series rounds
     */

    function getSeriesRounds($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'SeriesID' => 'S.SeriesID',
                'RoundID' => 'S.RoundID',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
                'StatusID' => 'S.StatusID',
                'SeriesIDLive' => 'S.RoundIDLive SeriesIDLive',
                'AuctionDraftIsPlayed' => 'S.AuctionDraftIsPlayed',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
                'SeriesStartDate' => 'DATE_FORMAT(CONVERT_TZ(S.RoundStartDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesStartDate',
                'SeriesEndDate' => 'DATE_FORMAT(CONVERT_TZ(S.RoundEndDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesEndDate',
                'SeriesStartDateUTC' => 'S.RoundStartDate as SeriesStartDateUTC',
                'SeriesEndDateUTC' => 'S.RoundEndDate as SeriesEndDateUTC',
                'TotalMatches' => '(SELECT COUNT(*) AS TotalMatches
                FROM sports_matches
                WHERE sports_matches.RoundID =  S.RoundID ) AS TotalMatches',
                'SeriesMatchStartDate' => '(SELECT MatchStartDateTime AS SeriesMatchStartDate
                FROM sports_matches
                WHERE sports_matches.RoundID =  S.RoundID order by MatchStartDateTime asc limit 1) AS SeriesMatchStartDate',
                'Status' => 'CASE S.StatusID
                when "2" then "Active"
                when "6" then "Inactive"
                END as Status',
                'AuctionDraftStatus' => 'CASE S.AuctionDraftStatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "5" then "Completed"
                END as AuctionDraftStatus',
                //'IsJoinedContest' => '(select count(SeriesID) from sports_contest_join where SeriesID = S.SeriesID AND E.StatusID=' . (!is_array(@$Where['StatusID'])) ? @$Where['StatusID'] : 2 . ') AND UserID=' . @$Where['SessionUserID'] . ') as IsJoinedContest',
                'TotalUserWinning' => '(select SUM(JC.UserWinningAmount) from sports_contest_join JC where JC.RoundID = S.RoundID AND S.StatusID=6 AND JC.UserID=' . @$Where['SessionUserID'] . ') as TotalUserWinning'
            );

            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('S.RoundID,S.RoundName SeriesName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_series_rounds S');
        if (!empty($Where['Keyword'])) {
            $this->db->like("S.RoundName", $Where['Keyword']);
        }
        if (!empty($Where['DraftAuctionPlay']) && $Where['DraftAuctionPlay'] == "Yes") {
            $this->db->where("S.AuctionDraftIsPlayed", "Yes");
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("S.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("S.RoundID", $Where['RoundID']);
        }
        if (!empty($Where['AuctionDraftIsPlayed'])) {
            $this->db->where("S.AuctionDraftIsPlayed", $Where['AuctionDraftIsPlayed']);
        }
        if (!empty($Where['SeriesStartDate'])) {
            $this->db->where("S.SeriesStartDate >=", $Where['SeriesStartDate']);
        }
        if (!empty($Where['SeriesEndDate'])) {
            $this->db->where("S.SeriesEndDate >=", $Where['SeriesEndDate']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("S.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['AuctionDraftIsPlayed'])) {
            $this->db->where("S.AuctionDraftIsPlayed", $Where['AuctionDraftIsPlayed']);
        }
        if (!empty($Where['AuctionDraftStatusID'])) {
            $this->db->where("S.AuctionDraftStatusID", $Where['AuctionDraftStatusID']);
        }

        if (isset($Where['MyJoinedSeries']) && $Where['MyJoinedSeries'] = "Yes") {
            $this->db->where('EXISTS (select JE.ContestID from sports_contest_join JE,sports_contest C where C.ContestID=JE.ContestID AND C.LeagueType="Draft" AND JE.RoundID = S.RoundID AND JE.UserID=' . @$Where['SessionUserID'] . ')');
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('S.StatusID', 'ASC');
        $this->db->order_by('S.RoundStartDate', 'DESC');
        $this->db->order_by('S.RoundName', 'ASC');

        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }

        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                if (!empty($Where['MyJoinedSeriesCount']) && $Where['MyJoinedSeriesCount'] == "Yes") {
                    $Return['Data']['Statics'] = $this->db->query('SELECT (
                            SELECT COUNT(DISTINCT S.RoundID) AS `UpcomingJoinedContest` FROM `sports_series_rounds` S
                            JOIN `sports_contest_join` J ON S.RoundID = J.RoundID WHERE S.AuctionDraftStatusID = 1 AND J.UserID ="' . @$Where['SessionUserID'] . '" 
                        )as UpcomingJoinedContest,
                        ( SELECT COUNT(DISTINCT S.RoundID) AS `LiveJoinedContest` FROM `sports_series_rounds` S JOIN `sports_contest_join` J ON S.RoundID = J.RoundID WHERE  S.AuctionDraftStatusID = 2 AND J.UserID = "' . @$Where['SessionUserID'] . '" 
                        )as LiveJoinedContest,
                        ( SELECT COUNT(DISTINCT S.RoundID) AS `CompletedJoinedContest` FROM `sports_series_rounds` S JOIN `sports_contest_join` J ON S.RoundID = J.RoundID WHERE  S.AuctionDraftStatusID = 5 AND J.UserID = "' . @$Where['SessionUserID'] . '" 
                    )as CompletedJoinedContest'
                            )->row();
                }
                return $Return;
            } else {
                return $Query->row_array();
            }
        }
        return FALSE;
    }

    /*
      Description:    ADD contest to system.
     */

    function addContest($Input = array(), $SessionUserID, $MatchID, $SeriesID, $StatusID = 1) {
        $defaultCustomizeWinningObj = new stdClass();
        $defaultCustomizeWinningObj->From = 1;
        $defaultCustomizeWinningObj->To = 1;
        $defaultCustomizeWinningObj->Percent = 100;
        $defaultCustomizeWinningObj->WinningAmount = @$Input['WinningAmount'];

        $this->db->trans_start();
        $EntityGUID = get_guid();

        // $Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
        $DraftTeamPlayerLimit = @$Input['DraftTeamPlayerLimit'];

        /* Add contest to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 11, "UserID" => $SessionUserID, "StatusID" => $StatusID));
        $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime('-330 minutes', 0);
        /* Add contest to contest table . */
        $InsertData = array_filter(array(
            "ContestID" => $EntityID,
            "ContestGUID" => $EntityGUID,
            "UserID" => $SessionUserID,
            //"ContestName" => (!empty(@$Input['ContestName'])) ? @$Input['ContestName'] : (@$Input['IsPaid'] == "Yes") ? "Win ".@$Input['WinningAmount'] : "Win Skill",
            "ContestName" => @$Input['ContestName'],
            "LeagueType" => @$Input['LeagueType'],
            "LeagueJoinDateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime) : null,
            "AuctionUpdateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime + 3600) : null,
            "ContestFormat" => @$Input['ContestFormat'],
            "ContestType" => @$Input['ContestType'],
            "Privacy" => @$Input['Privacy'],
            "IsPaid" => @$Input['IsPaid'],
            "IsConfirm" => @$Input['IsConfirm'],
            "LeagueType" => "Draft",
            "ShowJoinedContest" => @$Input['ShowJoinedContest'],
            "WinningAmount" => @$Input['WinningAmount'],
            "GameType" => @$Input['GameType'],
            "GameTimeLive" => @$Input['GameTimeLive'],
            "AdminPercent" => @$Input['AdminPercent'],
            "ContestSize" => (@$Input['ContestFormat'] == 'Head to Head') ? 2 : @$Input['ContestSize'],
            "EntryFee" => (@$Input['IsPaid'] == 'Yes') ? @$Input['EntryFee'] : 0,
            "NoOfWinners" => (@$Input['IsPaid'] == 'Yes') ? @$Input['NoOfWinners'] : 1,
            "EntryType" => @$Input['EntryType'],
            "UserJoinLimit" => (@$Input['EntryType'] == 'Multiple') ? @$Input['UserJoinLimit'] : 1,
            "CashBonusContribution" => @$Input['CashBonusContribution'],
            "CustomizeWinning" => (@$Input['IsPaid'] == 'Yes') ? @$Input['CustomizeWinning'] : json_encode(array($defaultCustomizeWinningObj)),
            "SeriesID" => @$SeriesID,
            "MatchID" => @$MatchID,
            "UserInvitationCode" => random_string('alnum', 6),
            "MinimumUserJoined" => @$Input['MinimumUserJoined'],
            "DraftTotalRounds" => $DraftTeamPlayerLimit,
            "DraftTeamPlayerLimit" => $DraftTeamPlayerLimit,
            "DraftLiveRound" => 1,
            //"IsCalculateCaptainVC" => @$Input['IsCalculateCaptainVC'],
            //"PointSystem" => @$Input['PointSystem'],
            "DraftPlayerSelectionCriteria" => @$Input['DraftPlayerSelectionCriteria'],
            "RoundID" => @$Input['RoundID']
        ));
        $this->db->insert('sports_contest', $InsertData);
        $this->addAuctionPlayerDraft($SeriesID, $EntityID, $Input['RoundID']);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }
        return $EntityID;
    }

    /*
      Description:    ADD auction players
     */

    function addAuctionPlayerDraft($SeriesID, $ContestID, $RoundID) {
        $this->db->select('PlayerID,PlayerRole,TeamID');
        $this->db->from('sports_team_players');
        $this->db->where("RoundID", $RoundID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->group_by("PlayerID");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $RoundPlayers = $Query->result_array();
            $Players = $RoundPlayers;
            shuffle($Players);
            if (!empty($Players)) {
                $InsertBatch = array();
                $InsertBatchPlayer = array();
                $InsertBatchPlayerPrivateContest = array();
                foreach ($Players as $Player) {
                    $Temp['SeriesID'] = $SeriesID;
                    $Temp['RoundID'] = $RoundID;
                    $Temp['ContestID'] = $ContestID;
                    $Temp['PlayerID'] = $Player['PlayerID'];
                    $Temp['PlayerRole'] = $Player['PlayerRole'];
                    $Temp['BidCredit'] = 0;
                    $Temp['PlayerStatus'] = "Upcoming";
                    $InsertBatch[] = $Temp;

                    $Temp1['SeriesID'] = $SeriesID;
                    $Temp1['RoundID'] = $RoundID;
                    $Temp1['ContestID'] = $ContestID;
                    $Temp1['PlayerID'] = $Player['PlayerID'];
                    $Temp1['PlayerRole'] = $Player['PlayerRole'];
                    $InsertBatchPlayer[] = $Temp1;

                    $Temp2['SeriesID'] = $SeriesID;
                    $Temp2['TeamID'] = $Player['TeamID'];
                    $Temp2['RoundID'] = $RoundID;
                    $Temp2['ContestID'] = $ContestID;
                    $Temp2['PlayerID'] = $Player['PlayerID'];
                    $Temp2['PlayerRole'] = $Player['PlayerRole'];
                    $Temp2['IsPlaying'] = "Yes";
                    $InsertBatchPlayerPrivateContest[] = $Temp2;
                }
                if (!empty($InsertBatch)) {
                    $this->db->insert_batch('tbl_auction_player_bid_status', $InsertBatch);

                    $Query = $this->db->query('SELECT SeriesID FROM sports_auction_draft_player_point WHERE RoundID = "' . $RoundID . '" LIMIT 1');
                    $SeriesID = ($Query->num_rows() > 0) ? $Query->row()->SeriesID : false;
                    if (!$SeriesID) {
                        $this->db->insert_batch('sports_auction_draft_player_point', $InsertBatchPlayer);
                    }
                    if ($InsertBatchPlayerPrivateContest) {
                        //$this->db->insert_batch('sports_private_contest_team_players', $InsertBatchPlayerPrivateContest);
                        //$this->db->insert_batch('sports_auction_draft_player_point_private', $InsertBatchPlayer);
                    }
                }
            }
        }
    }

    /*
      Description:    ADD auction players
     */

    function addAuctionPlayer($SeriesID, $ContestID) {
        $playersData = $this->getPlayersDraft("PlayerID,PlayerName,PlayerRole", array('SeriesID' => $SeriesID, 'OrderBy' => "PlayerID", "Sequence" => "ASC"), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if ($playersData['Data']['TotalRecords'] > 0) {
            $Players = $playersData['Data']['Records'];
            shuffle($Players);
            if (!empty($Players)) {
                $InsertBatch = array();
                $InsertBatchPlayer = array();
                foreach ($Players as $Player) {
                    $Temp['SeriesID'] = $SeriesID;
                    $Temp['ContestID'] = $ContestID;
                    $Temp['PlayerID'] = $Player['PlayerID'];
                    $Temp['PlayerRole'] = $Player['PlayerRole'];
                    $Temp['BidCredit'] = 0;
                    $Temp['PlayerStatus'] = "Upcoming";
                    $InsertBatch[] = $Temp;

                    $Temp1['SeriesID'] = $SeriesID;
                    $Temp1['ContestID'] = $ContestID;
                    $Temp1['PlayerID'] = $Player['PlayerID'];
                    $Temp1['PlayerRole'] = $Player['PlayerRole'];
                    $InsertBatchPlayer[] = $Temp1;
                }
                if (!empty($InsertBatch)) {
                    $this->db->insert_batch('tbl_auction_player_bid_status', $InsertBatch);

                    $Query = $this->db->query('SELECT SeriesID FROM sports_auction_draft_player_point WHERE SeriesID = "' . $SeriesID . '" LIMIT 1');
                    $SeriesID = ($Query->num_rows() > 0) ? $Query->row()->SeriesID : false;
                    if (!$SeriesID) {
                        $this->db->insert_batch('sports_auction_draft_player_point', $InsertBatchPlayer);
                    }
                }
            }
        }
    }

    function getPlayersDraft($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'BidCredit' => 'UTP.BidCredit',
                'ContestID' => 'APBS.ContestID as ContestID',
                'SeriesID' => 'APBS.SeriesID as SeriesID',
                'BidSoldCredit' => '(SELECT BidCredit FROM tbl_auction_player_bid_status WHERE SeriesID=' . $Where['SeriesID'] . ' AND ContestID=' . $Where['ContestID'] . ' AND PlayerID=P.PlayerID) BidSoldCredit',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'BidDateTime' => 'APBS.DateTime as BidDateTime',
                'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                'PlayerStatus' => '(SELECT PlayerStatus FROM tbl_auction_player_bid_status WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ' AND ContestID=' . @$Where['ContestID'] . ') as PlayerStatus',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'PlayerPosition' => 'UTP.PlayerPosition',
                'AuctionTopPlayerSubmitted' => 'UT.AuctionTopPlayerSubmitted',
                'IsAssistant' => 'UT.IsAssistant',
                'UserTeamName' => 'UT.UserTeamName',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P');

        if (!empty($Where['PlayerBidStatus']) && $Where['PlayerBidStatus'] == "Yes") {
            $this->db->from('tbl_auction_player_bid_status APBS,sports_series S,sports_contest C');
            $this->db->where("APBS.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("S.SeriesID", "APBS.SeriesID", FALSE);
            $this->db->where("C.ContestID", "APBS.ContestID", FALSE);
            if (!empty($Where['PlayerStatus'])) {
                $this->db->where("APBS.PlayerStatus", $Where['PlayerStatus']);
            }
            if (!empty($Where['ContestID'])) {
                $this->db->where("APBS.ContestID", $Where['ContestID']);
            }
        }

        if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
            $this->db->from('sports_users_teams UT, sports_users_team_players UTP');
            $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            if (!empty($Where['SessionUserID'])) {
                $this->db->where("UT.UserID", @$Where['SessionUserID']);
            }
            if (!empty($Where['IsAssistant'])) {
                $this->db->where("UT.IsAssistant", @$Where['IsAssistant']);
            }
            if (!empty($Where['IsPreTeam'])) {
                $this->db->where("UT.IsPreTeam", @$Where['IsPreTeam']);
            }
            if (!empty($Where['BidCredit'])) {
                $this->db->where("UTP.BidCredit >", @$Where['BidCredit']);
            }
            $this->db->where("UT.ContestID", @$Where['ContestID']);
        }
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("P.PlayerName", $Where['Keyword']);
            $this->db->or_like("P.PlayerRole", $Where['Keyword']);
            $this->db->or_like("P.PlayerCountry", $Where['Keyword']);
            $this->db->or_like("P.PlayerBattingStyle", $Where['Keyword']);
            $this->db->or_like("P.PlayerBowlingStyle", $Where['Keyword']);
            $this->db->group_end();
        }
        $this->db->where('EXISTS (select PlayerID FROM sports_team_players WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ')');
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['IsAdminSalaryUpdated'])) {
            $this->db->where("P.IsAdminSalaryUpdated", $Where['IsAdminSalaryUpdated']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {
            //$this->db->order_by('P.PlayerSalary', 'DESC');
            //$this->db->order_by('P.PlayerID', 'DESC');
        }
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }

        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $IsAssistant = "";
                    $AuctionTopPlayerSubmitted = "No";
                    $UserTeamGUID = "";
                    $UserTeamName = "";
                    // $Records[$key]['PlayerSalary'] = $Record['PlayerSalary']*10000000;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerRole'] = "";
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
                    $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                    $this->db->where('PlayerID', $Record['PlayerID']);
                    $this->db->from('sports_team_players');
                    $this->db->order_by("PlayerSalary", 'DESC');
                    $this->db->limit(1);
                    $PlayerDetails = $this->db->get()->result_array();
                    if (!empty($PlayerDetails)) {
                        $Records[$key]['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                    }
                }
                if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
                    $Return['Data']['IsAssistant'] = $IsAssistant;
                    $Return['Data']['UserTeamGUID'] = $UserTeamGUID;
                    $Return['Data']['UserTeamName'] = $UserTeamName;
                    $Return['Data']['AuctionTopPlayerSubmitted'] = $AuctionTopPlayerSubmitted;
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerRole'] = "";
                $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                $this->db->where('PlayerID', $Record['PlayerID']);
                $this->db->from('sports_team_players');
                $this->db->order_by("PlayerSalary", 'DESC');
                $this->db->limit(1);
                $PlayerDetails = $this->db->get()->result_array();
                if (!empty($PlayerDetails)) {
                    $Record['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                }
                return $Record;
            }
        }
        return FALSE;
    }

    function addAuctionPlayerRandom($SeriesID, $ContestID) {
        $playersData = $this->getPlayers("PlayerID,PlayerSalary", array('SeriesID' => $SeriesID), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
        if ($playersData['Data']['TotalRecords'] > 0) {
            $Players = $playersData['Data']['Records'];
            if (!empty($Players)) {
                $PlayerCatOne = array();
                $PlayerCatTwo = array();
                $Temp = array();
                foreach ($Players as $Rows) {
                    $Temp["PlayerID"] = $Rows["PlayerID"];
                    $Temp["PlayerSalary"] = $Rows["PlayerSalary"];
                    if ($Rows["PlayerSalary"] >= 9) {
                        $PlayerCatOne[] = $Temp;
                    } else {
                        $PlayerCatTwo[] = $Temp;
                    }
                }
                shuffle($PlayerCatOne);
                shuffle($PlayerCatTwo);
                $Players = array_merge($PlayerCatOne, $PlayerCatTwo);
                shuffle($Players);
                $InsertBatch = array();
                $TempPlayer = array();
                foreach ($Players as $Player) {
                    $TempPlayer['SeriesID'] = $SeriesID;
                    $TempPlayer['ContestID'] = $ContestID;
                    $TempPlayer['PlayerID'] = $Player['PlayerID'];
                    $TempPlayer['BidCredit'] = 0;
                    $TempPlayer['PlayerStatus'] = "Upcoming";
                    $TempPlayer['CreateDateTime'] = date("Y-m-d H:i:s");
                    $InsertBatch[] = $TempPlayer;
                }
                if (!empty($InsertBatch)) {
                    $this->db->insert_batch('tbl_auction_player_bid_status', $InsertBatch);
                }
            }
        }
    }

    /*
      Description: Update contest to system.
     */

    function updateContest($Input = array(), $SessionUserID, $ContestID, $StatusID = 1) {
        $defaultCustomizeWinningObj = new stdClass();
        $defaultCustomizeWinningObj->From = 1;
        $defaultCustomizeWinningObj->To = 1;
        $defaultCustomizeWinningObj->Percent = 100;
        $defaultCustomizeWinningObj->WinningAmount = @$Input['WinningAmount'];
        $LeagueJoinDateTime = strtotime(@$Input['LeagueJoinDateTime']) + strtotime('-330 minutes', 0);
        /* Add contest to contest table . */
        $UpdateData = array_filter(array(
            "ContestName" => @$Input['ContestName'],
            "ContestFormat" => @$Input['ContestFormat'],
            "ContestType" => @$Input['ContestType'],
            "LeagueJoinDateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime) : null,
            "AuctionUpdateTime" => (@$Input['LeagueJoinDateTime']) ? date('Y-m-d H:i', $LeagueJoinDateTime + 3600) : null,
            "Privacy" => @$Input['Privacy'],
            "IsPaid" => @$Input['IsPaid'],
            "IsConfirm" => @$Input['IsConfirm'],
            "GameType" => @$Input['GameType'],
            "GameTimeLive" => @$Input['GameTimeLive'],
            "AdminPercent" => @$Input['AdminPercent'],
            "MinimumUserJoined" => @$Input['MinimumUserJoined'],
            "ShowJoinedContest" => @$Input['ShowJoinedContest'],
            "WinningAmount" => @$Input['WinningAmount'],
            "ContestSize" => (@$Input['ContestFormat'] == 'Head to Head') ? 2 : @$Input['ContestSize'],
            "EntryFee" => (@$Input['IsPaid'] == 'Yes') ? @$Input['EntryFee'] : 0,
            "NoOfWinners" => (@$Input['IsPaid'] == 'Yes') ? @$Input['NoOfWinners'] : 1,
            "EntryType" => @$Input['EntryType'],
            "UserJoinLimit" => (@$Input['EntryType'] == 'Multiple') ? @$Input['UserJoinLimit'] : 1,
            "CashBonusContribution" => @$Input['CashBonusContribution'],
            // "CustomizeWinning" => (@$Input['IsPaid'] == 'Yes') ? @$Input['CustomizeWinning'] : NULL,
            "CustomizeWinning" => (@$Input['IsPaid'] == 'Yes') ? @$Input['CustomizeWinning'] : array($defaultCustomizeWinningObj),
        ));
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('sports_contest', $UpdateData);
    }

    function getTeams($RoundID) {
        $Return['Records'] = array();
        $Return['TotalRecords'] = 0;
        $this->db->select(" DISTINCT T.TeamID", FALSE);
        $this->db->select("T.TeamName,T.TeamNameShort");
        $this->db->from('sports_teams T');
        $this->db->where('EXISTS (select MatchID from sports_matches M where (M.TeamIDLocal = T.TeamID OR M.TeamIDVisitor = T.TeamID) AND M.RoundID=' . $RoundID . ')');
        $this->db->order_by('T.TeamID', "DESC");
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['TotalRecords'] = $TempQ->num_rows();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Records'] = $Query->result_array();
        }
        return $Return;
    }

    /*
      Description: Update auction game.
     */

    function getDraftGameStatusUpdate($Input = array(), $ContestID, $AuctionStatusID) {


        /* check contest cancel or not * */
        $Contest = $this->autoCancelAuction($ContestID);
        if ($Contest) {
            return false;
        }
        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionStatusID" => $AuctionStatusID,
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('sports_contest', $UpdateData);
        return $Rows = $this->db->affected_rows();
    }

    /*
      Description: To Auto suffle round update
     */

    function autoShuffleRoundUpdate($ContestID) {
        $this->db->select("J.ContestID,J.UserID,J.DraftUserPosition");
        $this->db->from('sports_contest_join J');
        $this->db->where("J.ContestID", $ContestID);
        $this->db->order_by("J.DraftUserPosition", "ASC");
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $Users = $Query->result_array();
            shuffle($Users);
            $i = 1;
            foreach ($Users as $User) {
                /* Update auction Status */
                $this->db->where('ContestID', $User['ContestID']);
                $this->db->where('UserID', $User['UserID']);
                $this->db->limit(1);
                $this->db->update('sports_contest_join', array('DraftUserPosition' => $i));
                $i++;
            }
        }
        return true;
    }

    /*
      Description: To Auto Cancel Auction
     */

    function autoCancelAuction($ContestID) {

        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        $ContestsUsers = $this->getContests('ContestID,EntryFee,TotalJoined,ContestSize,IsConfirm', array('AuctionStatusID' => 1, 'ContestID' => $ContestID, 'IsConfirm' => "No", "IsPaid" => "Yes"), true, 0);

        if ($ContestsUsers['Data']['TotalRecords'] == 0) {
            return false;
        }
        foreach ($ContestsUsers['Data']['Records'] as $Value) {

            $IsCancelled = (($Value['IsConfirm'] == 'No' && $Value['TotalJoined'] != $Value['ContestSize']) ? 1 : 0);
            if ($IsCancelled == 0)
                return false;

            /* Update Contest Status */
            $this->db->where('EntityID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));

            /* Update auction Status */
            $this->db->where('ContestID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('sports_contest', array('AuctionStatusID' => 3, 'IsRefund' => "Yes"));

            /* Get Joined Contest */
            $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $Value['ContestID']), true, 0);
            if (!$JoinedContestsUsers)
                return false;

            foreach ($JoinedContestsUsers['Data']['Records'] as $JoinValue) {

                /* Refund Wallet Money */
                if (!empty($Value['EntryFee'])) {

                    /* Get Wallet Details */
                    $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                        'UserID' => $JoinValue['UserID'],
                        'EntityID' => $Value['ContestID'],
                        'Narration' => 'Join Contest'
                    ));
                    $InsertData = array(
                        "Amount" => $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['CashBonus'],
                        "WalletAmount" => $WalletDetails['WalletAmount'],
                        "WinningAmount" => $WalletDetails['WinningAmount'],
                        "CashBonus" => $WalletDetails['CashBonus'],
                        "TransactionType" => 'Cr',
                        "EntityID" => $Value['ContestID'],
                        "UserTeamID" => $JoinValue['UserTeamID'],
                        "Narration" => 'Cancel Contest',
                        "EntryDate" => date("Y-m-d H:i:s")
                    );
                    $this->Users_model->addToWallet($InsertData, $JoinValue['UserID'], 5);
                }

                /** update user refund status Yes */
                $this->db->where('ContestID', $Value['ContestID']);
                $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                $this->db->where('UserID', $JoinValue['UserID']);
                $this->db->limit(1);
                $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));

                /* Send Mail To Users */
                /* $EmailArr = array(
                  "Name" => $JoinValue['FirstName'],
                  "SeriesName" => $Value['SeriesName'],
                  "ContestName" => $Value['ContestName'],
                  "MatchNo" => $Value['MatchNo'],
                  "TeamNameLocal" => $Value['TeamNameLocal'],
                  "TeamNameVisitor" => $Value['TeamNameVisitor']
                  );
                  sendMail(array(
                  'emailTo' => $JoinValue['Email'],
                  'emailSubject' => "Cancel Contest- " . SITE_NAME,
                  'emailMessage' => emailTemplate($this->load->view('emailer/cancel_contest', $EmailArr, true))
                  )); */
            }

            return true;
        }
    }

    /*
      Description: Update user live status.
     */

    function userLiveStatusUpdate($Input = array(), $ContestID, $UserID, $SeriesID, $RoundID) {

        /** to update other user offline * */
        $UpdateDatas = array(
            "DraftUserLive" => "No"
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('RoundID', $RoundID);
        $this->db->update('sports_contest_join', $UpdateDatas);

        /* user status update . */
        $UpdateData = array(
            "DraftUserLive" => $Input['UserStatus'],
            "DraftUserLiveTime" => date('Y-m-d H:i:s'),
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('RoundID', $RoundID);
        $this->db->limit(1);
        $this->db->update('sports_contest_join', $UpdateData);
        return $Rows = $this->db->affected_rows();
    }

    /*
      Description: Update draft round.
     */

    function roundUpdate($Input = array(), $ContestID, $SeriesID) {

        /** to update other user offline * */
        $UpdateDatas = array(
            "DraftLiveRound" => $Input['DraftLiveRound']
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('SeriesID', $SeriesID);
        $this->db->update('sports_contest', $UpdateDatas);
        return $Rows = $this->db->affected_rows();
    }

    /*
      Description: get user in live
     */

    function checkUserDraftInlive($Input, $RoundID, $ContestID) {
        $Return = array();
        $Return["Status"] = 0;
        /** check draft in live * */
        $DraftGames = $this->getContests('ContestID,AuctionStatus,SeriesID,SeriesGUID,DraftLiveRound,RoundID', array('AuctionStatusID' => 2, 'LeagueType' => "Draft", "ContestID" => $ContestID, "RoundID" => $RoundID), TRUE, 1);
        if ($DraftGames['Data']['TotalRecords'] > 0) {
            $Users = array();
            foreach ($DraftGames['Data']['Records'] as $Key => $Draft) {

                /** to get user live and time difference * */
                $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID");
                $this->db->from('sports_contest_join J, tbl_users U');
                $this->db->where("J.DraftUserLive", "Yes");
                $this->db->where("U.UserID", "J.UserID", FALSE);
                $this->db->where("J.ContestID", $Draft['ContestID']);
                $this->db->where("J.RoundID", $RoundID);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $LiveUser = $Query->row_array();
                    $CurrentDateTime = date('Y-m-d H:i:s');
                    $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                    $CurrentDateTime = new DateTime($CurrentDateTime);
                    $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                    $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                    $Users[$Key]["UserLiveInTimeSeconds"] = $diffSeconds;
                    $Users[$Key]["ContestID"] = $Draft['ContestID'];
                    $Users[$Key]["SeriesID"] = $Draft['SeriesID'];
                    $Users[$Key]["RoundID"] = $Draft['RoundID'];
                    $Users[$Key]["ContestGUID"] = $Draft['ContestGUID'];
                    $Users[$Key]["SeriesGUID"] = $Draft['SeriesGUID'];
                    $Users[$Key]["DraftLiveRound"] = $Draft['DraftLiveRound'];
                    $Users[$Key]["UserID"] = $LiveUser['UserID'];
                    $Users[$Key]["UserGUID"] = $LiveUser['UserGUID'];
                    $Users[$Key]["DraftUserLiveTime"] = $LiveUser['DraftUserLiveTime'];
                    $Users[$Key]["UserStatus"] = "Live";
                } else {
                    /** to get user live and time difference * */
                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID");
                    $this->db->from('sports_contest_join J, tbl_users U');
                    $this->db->where("U.UserID", "J.UserID", FALSE);
                    $this->db->where("J.ContestID", $Draft['ContestID']);
                    $this->db->where("J.RoundID", $Draft['RoundID']);
                    $this->db->where("J.DraftUserPosition", 1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $LiveUser = $Query->row_array();
                        $CurrentDateTime = date('Y-m-d H:i:s');
                        $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                        $Users[$Key]["UserLiveInTimeSeconds"] = 0;
                        $Users[$Key]["ContestID"] = $Draft['ContestID'];
                        $Users[$Key]["SeriesID"] = $Draft['SeriesID'];
                        $Users[$Key]["RoundID"] = $Draft['RoundID'];
                        $Users[$Key]["ContestGUID"] = $Draft['ContestGUID'];
                        $Users[$Key]["SeriesGUID"] = $Draft['SeriesGUID'];
                        $Users[$Key]["DraftLiveRound"] = $Draft['DraftLiveRound'];
                        $Users[$Key]["UserID"] = $LiveUser['UserID'];
                        $Users[$Key]["UserGUID"] = $LiveUser['UserGUID'];
                        $Users[$Key]["DraftUserLiveTime"] = $LiveUser['DraftUserLiveTime'];
                        $Users[$Key]["UserStatus"] = "Upcoming";
                    }
                }
            }
            $U = array();
            foreach ($Users as $Rows) {
                $U[] = $Rows;
            }
            $Return["Data"] = $U;
            $Return["Message"] = "Users in live";
            $Return["Status"] = 1;
        } else {
            $Return["Message"] = "Draft not live";
        }
        return $Return;
    }

    /*
      Description: get user in live
     */

    function getUserInLive() {
        $Return = array();
        $Return["Status"] = 0;
        /** check draft in live * */
        $DraftGames = $this->getContests('ContestID,AuctionStatus,SeriesID,SeriesGUID,RoundID', array('AuctionStatusID' => 2, 'LeagueType' => "Draft"), TRUE, 1);
        if ($DraftGames['Data']['TotalRecords'] > 0) {
            $Users = array();
            foreach ($DraftGames['Data']['Records'] as $Key => $Draft) {

                /** to get user live and time difference * */
                $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID");
                $this->db->from('sports_contest_join J, tbl_users U');
                $this->db->where("J.DraftUserLive", "Yes");
                $this->db->where("U.UserID", "J.UserID", FALSE);
                $this->db->where("J.ContestID", $Draft['ContestID']);
                $this->db->where("J.SeriesID", $Draft['SeriesID']);
                $this->db->where("J.RoundID", $Draft['RoundID']);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $LiveUser = $Query->row_array();
                    $CurrentDateTime = date('Y-m-d H:i:s');
                    $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                    $CurrentDateTime = new DateTime($CurrentDateTime);
                    $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                    $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                    $Users[$Key]["UserLiveInTimeSeconds"] = $diffSeconds;
                    $Users[$Key]["ContestID"] = $Draft['ContestID'];
                    $Users[$Key]["SeriesID"] = $Draft['SeriesID'];
                    $Users[$Key]["RoundID"] = $Draft['RoundID'];
                    $Users[$Key]["ContestGUID"] = $Draft['ContestGUID'];
                    $Users[$Key]["SeriesGUID"] = $Draft['SeriesGUID'];
                    $Users[$Key]["UserID"] = $LiveUser['UserID'];
                    $Users[$Key]["UserGUID"] = $LiveUser['UserGUID'];
                    $Users[$Key]["UserStatus"] = "Live";
                } else {
                    /** to get user live and time difference * */
                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID");
                    $this->db->from('sports_contest_join J, tbl_users U');
                    $this->db->where("U.UserID", "J.UserID", FALSE);
                    $this->db->where("J.ContestID", $Draft['ContestID']);
                    $this->db->where("J.SeriesID", $Draft['SeriesID']);
                    $this->db->where("J.RoundID", $Draft['RoundID']);
                    $this->db->where("J.DraftUserPosition", 1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $LiveUser = $Query->row_array();
                        $CurrentDateTime = date('Y-m-d H:i:s');
                        $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                        $Users[$Key]["UserLiveInTimeSeconds"] = 0;
                        $Users[$Key]["ContestID"] = $Draft['ContestID'];
                        $Users[$Key]["SeriesID"] = $Draft['SeriesID'];
                        $Users[$Key]["RoundID"] = $Draft['RoundID'];
                        $Users[$Key]["ContestGUID"] = $Draft['ContestGUID'];
                        $Users[$Key]["SeriesGUID"] = $Draft['SeriesGUID'];
                        $Users[$Key]["UserID"] = $LiveUser['UserID'];
                        $Users[$Key]["UserGUID"] = $LiveUser['UserGUID'];
                        $Users[$Key]["UserStatus"] = "Upcoming";
                    }
                }
            }
            $U = array();
            foreach ($Users as $Rows) {
                $U[] = $Rows;
            }
            $Return["Data"] = $U;
            $Return["Message"] = "Users in live";
            $Return["Status"] = 1;
        } else {
            $Return["Message"] = "Draft not live";
        }
        return $Return;
    }

    /*
      Description: get round next user in live
     */

    function draftRoundUpdate($ContestID, $SeriesID, $Round) {
        /* Add contest to contest table . */
        $UpdateData = array_filter(array(
            "DraftLiveRound" => $Round
        ));
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('sports_contest', $UpdateData);
        return true;
    }

    function getRoundNextUserInLive($Input, $SeriesID, $RoundID, $ContestID) {
        $Return = array();
        $Return["Status"] = 0;
        $Return['Message'] = "Record Not found";
        /** check draft in live * */
        $DraftGames = $this->getContests('ContestID,SeriesID,SeriesGUID,DraftTotalRounds,TotalJoined,DraftLiveRound,RoundID', array('AuctionStatusID' => 2, 'LeagueType' => "Draft", "ContestID" => $ContestID, "RoundID" => $RoundID), TRUE, 1);

        if ($DraftGames['Data']['TotalRecords'] > 0) {
            $Users = array();
            foreach ($DraftGames['Data']['Records'] as $Key => $Draft) {

                if ($Draft['DraftLiveRound'] <= $Draft['DraftTotalRounds']) {
                    $Flag = FALSE;
                    /** skipped first user not getting player * */
                    if ($Draft['DraftLiveRound'] == 1) {

                        $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,U.FirstName");
                        $this->db->from('sports_contest_join J, tbl_users U');
                        $this->db->where("J.DraftUserLive", "Yes");
                        $this->db->where("U.UserID", "J.UserID", FALSE);
                        $this->db->where("J.ContestID", $ContestID);
                        $this->db->where("J.SeriesID", $SeriesID);
                        $this->db->where("J.RoundID", $RoundID);
                        $this->db->where("J.DraftUserPosition", 1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $LiveUser = $Query->row_array();
                            $UserTeamID = $this->db->query('SELECT T.UserTeamID from `sports_users_teams` T join tbl_users U on U.UserID = T.UserID WHERE T.RoundID = "' . $RoundID . '" AND T.UserID = "' . $LiveUser['UserID'] . '" AND T.ContestID = "' . $ContestID . '" AND IsPreTeam = "No" AND IsAssistant="No" ')->row()->UserTeamID;
                            if (!empty($UserTeamID)) {
                                $UserTeamID = $this->db->query('SELECT PlayerID from `sports_users_team_players` WHERE UserTeamID = "' . $UserTeamID . '" ')->row()->PlayerID;
                                if (empty($UserTeamID)) {
                                    $Flag = TRUE;
                                }
                            } else {
                                $Flag = TRUE;
                            }
                            if ($Flag) {
                                $CurrentDateTime = date('Y-m-d H:i:s');
                                $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                                $CurrentDateTime = new DateTime($CurrentDateTime);
                                $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                $Users["ContestID"] = $Draft['ContestID'];
                                $Users["SeriesID"] = $Draft['SeriesID'];
                                $Users["RoundID"] = $Draft['RoundID'];
                                $Users["ContestGUID"] = $Draft['ContestGUID'];
                                $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                $Users["DraftNextRound"] = $Draft['DraftLiveRound'] + 1;
                                $Users["UserID"] = $LiveUser['UserID'];
                                $Users["UserGUID"] = $LiveUser['UserGUID'];
                                $Users["FirstName"] = $LiveUser['FirstName'];
                                $Return["Status"] = 1;
                                $Return["Data"] = $Users;
                                $Return['Message'] = "User in live";
                            }
                        }
                    }

                    if (!$Flag) {
                        /** check last player in live * */
                        $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID");
                        $this->db->from('sports_contest_join J, tbl_users U');
                        $this->db->where("J.DraftUserLive", "Yes");
                        $this->db->where("U.UserID", "J.UserID", FALSE);
                        $this->db->where("J.ContestID", $ContestID);
                        $this->db->where("J.SeriesID", $SeriesID);
                        $this->db->where("J.RoundID", $RoundID);
                        $Query = $this->db->get();
                        if ($Query->num_rows() == 0) {
                            /** check last player in live * */
                            $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,U.FirstName");
                            $this->db->from('sports_contest_join J, tbl_users U');
                            $this->db->where("U.UserID", "J.UserID", FALSE);
                            $this->db->where("J.ContestID", $ContestID);
                            $this->db->where("J.SeriesID", $SeriesID);
                            $this->db->where("J.RoundID", $RoundID);
                            $this->db->where("J.DraftUserPosition", 1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $LiveUser = $Query->row_array();
                                $CurrentDateTime = date('Y-m-d H:i:s');
                                $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                                $Users["UserLiveInTimeSeconds"] = 0;
                                $Users["ContestID"] = $Draft['ContestID'];
                                $Users["SeriesID"] = $Draft['SeriesID'];
                                $Users["RoundID"] = $Draft['RoundID'];
                                $Users["ContestGUID"] = $Draft['ContestGUID'];
                                $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                $Users["DraftNextRound"] = $Draft['DraftLiveRound'];
                                $Users["UserID"] = $LiveUser['UserID'];
                                $Users["UserGUID"] = $LiveUser['UserGUID'];
                                $Users["FirstName"] = $LiveUser['FirstName'];
                                $Return["Status"] = 1;
                                $Return["Data"] = $Users;
                                $Return['Message'] = "User in live";
                            }
                        } else {
                            /** check round even or odd * */
                            if (($Draft['DraftLiveRound'] % 2) != 0) {
                                /** value odd number * */
                                /** check last player in live * */
                                $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,U.FirstName");
                                $this->db->from('sports_contest_join J, tbl_users U');
                                $this->db->where("J.DraftUserLive", "Yes");
                                $this->db->where("U.UserID", "J.UserID", FALSE);
                                $this->db->where("J.ContestID", $ContestID);
                                $this->db->where("J.SeriesID", $SeriesID);
                                $this->db->where("J.RoundID", $RoundID);
                                $this->db->where("J.DraftUserPosition", $Draft['TotalJoined']);
                                $Query = $this->db->get();

                                if ($Query->num_rows() > 0) {
                                    $LiveUser = $Query->row_array();
                                    $CurrentDateTime = date('Y-m-d H:i:s');
                                    $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                                    $CurrentDateTime = new DateTime($CurrentDateTime);
                                    $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                    $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                    $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                    $Users["ContestID"] = $Draft['ContestID'];
                                    $Users["SeriesID"] = $Draft['SeriesID'];
                                    $Users["RoundID"] = $Draft['RoundID'];
                                    $Users["ContestGUID"] = $Draft['ContestGUID'];
                                    $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                    $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                    $Users["DraftNextRound"] = $Draft['DraftLiveRound'] + 1;
                                    $Users["UserID"] = $LiveUser['UserID'];
                                    $Users["UserGUID"] = $LiveUser['UserGUID'];
                                    $Users["FirstName"] = $LiveUser['FirstName'];
                                    $Return["Status"] = 1;
                                    $Return["Data"] = $Users;
                                    $Return['Message'] = "User in live";
                                    $this->draftRoundUpdate($Draft['ContestID'], $Draft['SeriesID'], $Draft['DraftLiveRound'] + 1);
                                } else {
                                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.DraftUserPosition");
                                    $this->db->from('sports_contest_join J, tbl_users U');
                                    $this->db->where("J.DraftUserLive", "Yes");
                                    $this->db->where("U.UserID", "J.UserID", FALSE);
                                    $this->db->where("J.ContestID", $ContestID);
                                    $this->db->where("J.SeriesID", $SeriesID);
                                    $this->db->where("J.RoundID", $RoundID);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $CurrentUser = $Query->row_array();
                                        $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.DraftUserPosition,U.FirstName");
                                        $this->db->from('sports_contest_join J, tbl_users U');
                                        $this->db->where("J.DraftUserLive", "No");
                                        $this->db->where("J.DraftUserPosition", $CurrentUser['DraftUserPosition'] + 1);
                                        $this->db->where("U.UserID", "J.UserID", FALSE);
                                        $this->db->where("J.ContestID", $ContestID);
                                        $this->db->where("J.SeriesID", $SeriesID);
                                        $this->db->where("J.RoundID", $RoundID);
                                        $Query = $this->db->get();

                                        if ($Query->num_rows() > 0) {
                                            $NextUser = $Query->row_array();
                                            $CurrentDateTime = date('Y-m-d H:i:s');
                                            $DraftUserLiveTime = $NextUser['DraftUserLiveTime'];
                                            $CurrentDateTime = new DateTime($CurrentDateTime);
                                            $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                            $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                            $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                            $Users["ContestID"] = $Draft['ContestID'];
                                            $Users["SeriesID"] = $Draft['SeriesID'];
                                            $Users["RoundID"] = $Draft['RoundID'];
                                            $Users["ContestGUID"] = $Draft['ContestGUID'];
                                            $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                            $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                            $Users["DraftNextRound"] = $Draft['DraftLiveRound'];
                                            $Users["UserID"] = $NextUser['UserID'];
                                            $Users["UserGUID"] = $NextUser['UserGUID'];
                                            $Users["FirstName"] = $NextUser['FirstName'];
                                            $Return["Status"] = 1;
                                            $Return["Data"] = $Users;
                                            $Return['Message'] = "User in live";
                                        }
                                    }
                                }
                            } else {
                                /* value odd number * */
                                /** check last player in live * */
                                $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,U.FirstName");
                                $this->db->from('sports_contest_join J, tbl_users U');
                                $this->db->where("J.DraftUserLive", "Yes");
                                $this->db->where("U.UserID", "J.UserID", FALSE);
                                $this->db->where("J.ContestID", $ContestID);
                                $this->db->where("J.SeriesID", $SeriesID);
                                $this->db->where("J.RoundID", $RoundID);
                                $this->db->where("J.DraftUserPosition", 1);
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $LiveUser = $Query->row_array();
                                    $CurrentDateTime = date('Y-m-d H:i:s');
                                    $DraftUserLiveTime = $LiveUser['DraftUserLiveTime'];
                                    $CurrentDateTime = new DateTime($CurrentDateTime);
                                    $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                    $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                    $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                    $Users["ContestID"] = $Draft['ContestID'];
                                    $Users["SeriesID"] = $Draft['SeriesID'];
                                    $Users["RoundID"] = $Draft['RoundID'];
                                    $Users["ContestGUID"] = $Draft['ContestGUID'];
                                    $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                    $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                    $Users["DraftNextRound"] = $Draft['DraftLiveRound'] + 1;
                                    $Users["UserID"] = $LiveUser['UserID'];
                                    $Users["UserGUID"] = $LiveUser['UserGUID'];
                                    $Users["FirstName"] = $LiveUser['FirstName'];
                                    $Return["Status"] = 1;
                                    $Return["Data"] = $Users;
                                    $Return['Message'] = "User in live";
                                    $this->draftRoundUpdate($Draft['ContestID'], $Draft['SeriesID'], $Draft['DraftLiveRound'] + 1);
                                } else {
                                    $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.DraftUserPosition");
                                    $this->db->from('sports_contest_join J, tbl_users U');
                                    $this->db->where("J.DraftUserLive", "Yes");
                                    $this->db->where("U.UserID", "J.UserID", FALSE);
                                    $this->db->where("J.ContestID", $ContestID);
                                    $this->db->where("J.SeriesID", $SeriesID);
                                    $this->db->where("J.RoundID", $RoundID);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $CurrentUser = $Query->row_array();
                                        $this->db->select("J.ContestID,J.UserID,J.DraftUserLiveTime,J.DraftUserLive,U.UserGUID,J.DraftUserPosition,U.FirstName");
                                        $this->db->from('sports_contest_join J, tbl_users U');
                                        $this->db->where("J.DraftUserLive", "No");
                                        $this->db->where("J.DraftUserPosition", $CurrentUser['DraftUserPosition'] - 1);
                                        $this->db->where("U.UserID", "J.UserID", FALSE);
                                        $this->db->where("J.ContestID", $ContestID);
                                        $this->db->where("J.SeriesID", $SeriesID);
                                        $this->db->where("J.RoundID", $RoundID);
                                        $Query = $this->db->get();
                                        if ($Query->num_rows() > 0) {
                                            $NextUser = $Query->row_array();
                                            $CurrentDateTime = date('Y-m-d H:i:s');
                                            $DraftUserLiveTime = $NextUser['DraftUserLiveTime'];
                                            $CurrentDateTime = new DateTime($CurrentDateTime);
                                            $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                                            $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                                            $Users["UserLiveInTimeSeconds"] = $diffSeconds;
                                            $Users["ContestID"] = $Draft['ContestID'];
                                            $Users["SeriesID"] = $Draft['SeriesID'];
                                            $Users["RoundID"] = $Draft['RoundID'];
                                            $Users["ContestGUID"] = $Draft['ContestGUID'];
                                            $Users["SeriesGUID"] = $Draft['SeriesGUID'];
                                            $Users["DraftLiveRound"] = $Draft['DraftLiveRound'];
                                            $Users["DraftNextRound"] = $Draft['DraftLiveRound'];
                                            $Users["UserID"] = $NextUser['UserID'];
                                            $Users["UserGUID"] = $NextUser['UserGUID'];
                                            $Users["FirstName"] = $NextUser['FirstName'];
                                            $Return["Status"] = 1;
                                            $Return["Data"] = $Users;
                                            $Return['Message'] = "User in live";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $Return["Message"] = "Draft not live";
        }
        return $Return;
    }

    function addDraftUserTeam($UserID, $ContestID, $SeriesID, $RoundID) {
        /** check is assistant and unsold player * */
        $UserTeamID = $this->db->query('SELECT T.UserTeamID from `sports_users_teams` T join tbl_users U on U.UserID = T.UserID WHERE T.SeriesID = "' . $SeriesID . '" AND T.RoundID = "' . $RoundID . '" AND T.UserID = "' . $UserID . '" AND T.ContestID = "' . $ContestID . '" AND IsPreTeam = "No" AND IsAssistant="No" ')->row()->UserTeamID;
        if (empty($UserTeamID)) {
            $EntityGUID = get_guid();
            $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $UserID, "StatusID" => 2));
            /* Add user team to user team table . */
            $teamName = "PostSnakeTeam 1";
            $UserTeamID = $EntityID;
            $InsertData = array(
                "UserTeamID" => $EntityID,
                "UserTeamGUID" => $EntityGUID,
                "UserID" => $UserID,
                "UserTeamName" => $teamName,
                "UserTeamType" => "Draft",
                "IsPreTeam" => "No",
                "SeriesID" => $SeriesID,
                "RoundID" => $RoundID,
                "ContestID" => $ContestID,
                "IsAssistant" => "No",
            );
            $this->db->insert('sports_users_teams', $InsertData);
        }
        return $UserTeamID;
    }

    function addDraftUserTeamSquad($UserTeamID, $UserID, $ContestID, $SeriesID, $Player, $RoundID = 0) {
        /** dynamic player role * */
        $ContestCriteria = $this->SnakeDrafts_model->getContests('ContestID,DraftTeamPlayerLimit,DraftPlayerSelectionCriteria', array("RoundID" => $RoundID, "ContestID" => $ContestID), FALSE, 1);
        //$Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
        $DraftPlayerSelectionCriteria = (!empty($ContestCriteria['DraftPlayerSelectionCriteria'])) ? $ContestCriteria['DraftPlayerSelectionCriteria'] : array("Wk" => 0, "Bat" => 0, "Ar" => 0, "Bowl" => 0);
        $DraftTeamPlayerLimit = (!empty($ContestCriteria['DraftTeamPlayerLimit'])) ? $ContestCriteria['DraftTeamPlayerLimit'] : 0;
        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID,UTP.PlayerPosition");
        $this->db->from('sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $DraftSquad = $Query->result_array();
            foreach ($DraftSquad as $Key => $PlayerSquad) {
                $DraftSquad[$Key]['PlayerRole'] = $this->db->query('SELECT S.PlayerRole from `tbl_auction_player_bid_status` S  WHERE S.SeriesID = "' . $SeriesID . '" AND S.ContestID = "' . $ContestID . '" AND S.PlayerID = "' . $PlayerSquad['PlayerID'] . '"')->row()->PlayerRole;
            }
            /** check player role condition * */
            $PlayerRoles = array_count_values(array_column($DraftSquad, 'PlayerRole'));
            /** check bowler role * */
            if (@$PlayerRoles['Bowler'] < $DraftPlayerSelectionCriteria['Bowl'] && $Player['PlayerRole'] == "Bowler") {
                /** insert user team player squad * */
                $InsertData = array(
                    "UserTeamID" => $UserTeamID,
                    "PlayerPosition" => "Player",
                    "PlayerID" => $Player['PlayerID'],
                    "SeriesID" => $SeriesID,
                    "DateTime" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('sports_users_team_players', $InsertData);
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Sold",
                    "DateTime" => date('Y-m-d H:i:s')
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                return true;
            } else if (@$PlayerRoles['Batsman'] < $DraftPlayerSelectionCriteria['Bat'] && $Player['PlayerRole'] == "Batsman") {
                /** insert user team player squad * */
                $InsertData = array(
                    "UserTeamID" => $UserTeamID,
                    "PlayerPosition" => "Player",
                    "PlayerID" => $Player['PlayerID'],
                    "SeriesID" => $SeriesID,
                    "DateTime" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('sports_users_team_players', $InsertData);
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Sold",
                    "DateTime" => date('Y-m-d H:i:s')
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                return true;
            } else if (@$PlayerRoles['AllRounder'] < $DraftPlayerSelectionCriteria['Ar'] && $Player['PlayerRole'] == "AllRounder") {
                /** insert user team player squad * */
                $InsertData = array(
                    "UserTeamID" => $UserTeamID,
                    "PlayerPosition" => "Player",
                    "PlayerID" => $Player['PlayerID'],
                    "SeriesID" => $SeriesID,
                    "DateTime" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('sports_users_team_players', $InsertData);
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Sold",
                    "DateTime" => date('Y-m-d H:i:s')
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                return true;
            } else if (@$PlayerRoles['WicketKeeper'] < $DraftPlayerSelectionCriteria['Wk'] && $Player['PlayerRole'] == "WicketKeeper") {
                /** insert user team player squad * */
                $InsertData = array(
                    "UserTeamID" => $UserTeamID,
                    "PlayerPosition" => "Player",
                    "PlayerID" => $Player['PlayerID'],
                    "SeriesID" => $SeriesID,
                    "DateTime" => date('Y-m-d H:i:s'),
                );
                $this->db->insert('sports_users_team_players', $InsertData);
                /* Add contest to contest table . */
                $UpdateData = array_filter(array(
                    "PlayerStatus" => "Sold",
                    "DateTime" => date('Y-m-d H:i:s')
                ));
                $this->db->where('SeriesID', $SeriesID);
                $this->db->where('ContestID', $ContestID);
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                return true;
            } else {
                //echo 1;exit;
                /** check total player in squad * */
                if ($Rows < $DraftTeamPlayerLimit) {
                    /** insert user team player squad * */
                    $InsertData = array(
                        "UserTeamID" => $UserTeamID,
                        "PlayerPosition" => "Player",
                        "PlayerID" => $Player['PlayerID'],
                        "SeriesID" => $SeriesID,
                        "DateTime" => date('Y-m-d H:i:s'),
                    );
                    $this->db->insert('sports_users_team_players', $InsertData);
                    /* Add contest to contest table . */
                    $UpdateData = array_filter(array(
                        "PlayerStatus" => "Sold",
                        "DateTime" => date('Y-m-d H:i:s')
                    ));
                    $this->db->where('SeriesID', $SeriesID);
                    $this->db->where('ContestID', $ContestID);
                    $this->db->where('PlayerID', $Player['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerPosition" => "Player",
                "PlayerID" => $Player['PlayerID'],
                "SeriesID" => $SeriesID,
                "DateTime" => date('Y-m-d H:i:s'),
            );
            $this->db->insert('sports_users_team_players', $InsertData);

            /** update player status * */
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold",
                "DateTime" => date('Y-m-d H:i:s')
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $Player['PlayerID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
            return true;
        }
    }

    function addDraftUserTeamSquadAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $Player, $RoundID) {
        $Return = array();
        $Return["Status"] = 0;
        $Return["Player"] = array();
        /** dynamic player role * */
        $ContestCriteria = $this->SnakeDrafts_model->getContests('ContestID,DraftTeamPlayerLimit,DraftPlayerSelectionCriteria', array("RoundID" => $RoundID, "ContestID" => $ContestID), FALSE, 1);
        //$Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
        $DraftPlayerSelectionCriteria = (!empty($ContestCriteria['DraftPlayerSelectionCriteria'])) ? $ContestCriteria['DraftPlayerSelectionCriteria'] : array("Wk" => 0, "Bat" => 0, "Ar" => 0, "Bowl" => 0);
        //$DraftPlayerSelectionCriteria = (!empty($DraftPlayerSelectionCriteria)) ? $DraftPlayerSelectionCriteria : array("Wk" => 0, "Bat" => 0, "Ar" => 0, "Bowl" => 0);
        $DraftTeamPlayerLimit = (!empty($ContestCriteria['DraftTeamPlayerLimit'])) ? $ContestCriteria['DraftTeamPlayerLimit'] : 0;

        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID,UTP.PlayerPosition");
        $this->db->from('sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();

        if ($Rows > 0) {
            $DraftSquad = $Query->result_array();
            foreach ($DraftSquad as $Key => $PlayerSquad) {
                $DraftSquad[$Key]['PlayerRole'] = $this->db->query('SELECT S.PlayerRole from `tbl_auction_player_bid_status` S  WHERE S.SeriesID = "' . $SeriesID . '" AND S.RoundID = "' . $RoundID . '" AND S.ContestID = "' . $ContestID . '" AND S.PlayerID = "' . $PlayerSquad['PlayerID'] . '"')->row()->PlayerRole;
            }
            /** check player role condition * */
            $PlayerRoles = array_count_values(array_column($DraftSquad, 'PlayerRole'));

            /** check is assistant and unsold player * */
            $this->db->select("BS.PlayerRole,UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID,UTP.AuctionDraftAssistantPriority,BS.PlayerStatus,SP.PlayerName");
            $this->db->from('sports_users_teams UT, sports_users_team_players UTP,tbl_auction_player_bid_status BS,sports_players SP');
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            $this->db->where("UT.ContestID", "BS.ContestID", FALSE);
            $this->db->where("UT.SeriesID", "BS.SeriesID", FALSE);
            $this->db->where("UTP.PlayerID", "BS.PlayerID", FALSE);
            $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
            $this->db->where("UT.IsAssistant", "Yes");
            $this->db->where("UT.IsPreTeam", "Yes");
            $this->db->where("UT.UserTeamType", "Draft");
            //$this->db->where("BS.PlayerStatus", "Upcoming");
            $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
            $this->db->where("UT.ContestID", $ContestID);
            $this->db->where("UT.SeriesID", $SeriesID);
            $this->db->where("UT.RoundID", $RoundID);
            $this->db->where("UT.UserID", $UserID);
            $this->db->order_by("UTP.AuctionDraftAssistantPriority", "ASC");
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $AssistantPlayers = $Query->result_array();
                foreach ($AssistantPlayers as $Assistant) {

                    /** check wicketKeeper minium criteria* */
                    if (@$PlayerRoles['WicketKeeper'] < $DraftPlayerSelectionCriteria['Wk'] && $Assistant['PlayerRole'] == "WicketKeeper") {
                        /** insert user team player squad * */
                        $InsertData = array(
                            "UserTeamID" => $UserTeamID,
                            "PlayerPosition" => "Player",
                            "PlayerID" => $Assistant['PlayerID'],
                            "SeriesID" => $SeriesID,
                            "RoundID" => $RoundID,
                            "DateTime" => date('Y-m-d H:i:s'),
                        );
                        $this->db->insert('sports_users_team_players', $InsertData);
                        /* Add contest to contest table . */
                        $UpdateData = array_filter(array(
                            "PlayerStatus" => "Sold",
                            "DateTime" => date('Y-m-d H:i:s')
                        ));
                        $this->db->where('SeriesID', $SeriesID);
                        $this->db->where('RoundID', $RoundID);
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('PlayerID', $Assistant['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                        $Return["Status"] = 1;
                        $Return["Player"] = $Assistant;
                        return $Return;
                    }

                    /** check bowler role * */
                    if (@$PlayerRoles['Bowler'] < $DraftPlayerSelectionCriteria['Bowl'] && $Assistant['PlayerRole'] == "Bowler") {
                        /** insert user team player squad * */
                        $InsertData = array(
                            "UserTeamID" => $UserTeamID,
                            "PlayerPosition" => "Player",
                            "PlayerID" => $Assistant['PlayerID'],
                            "SeriesID" => $SeriesID,
                            "RoundID" => $RoundID,
                            "DateTime" => date('Y-m-d H:i:s'),
                        );
                        $this->db->insert('sports_users_team_players', $InsertData);
                        /* Add contest to contest table . */
                        $UpdateData = array_filter(array(
                            "PlayerStatus" => "Sold",
                            "DateTime" => date('Y-m-d H:i:s')
                        ));
                        $this->db->where('SeriesID', $SeriesID);
                        $this->db->where('RoundID', $RoundID);
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('PlayerID', $Assistant['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                        $Return["Status"] = 1;
                        $Return["Player"] = $Assistant;
                        return $Return;
                    }

                    if (@$PlayerRoles['Batsman'] < $DraftPlayerSelectionCriteria['Bat'] && $Assistant['PlayerRole'] == "Batsman") {
                        /** insert user team player squad * */
                        $InsertData = array(
                            "UserTeamID" => $UserTeamID,
                            "PlayerPosition" => "Player",
                            "PlayerID" => $Assistant['PlayerID'],
                            "SeriesID" => $SeriesID,
                            "RoundID" => $RoundID,
                            "DateTime" => date('Y-m-d H:i:s'),
                        );
                        $this->db->insert('sports_users_team_players', $InsertData);
                        /* Add contest to contest table . */
                        $UpdateData = array_filter(array(
                            "PlayerStatus" => "Sold",
                            "DateTime" => date('Y-m-d H:i:s')
                        ));
                        $this->db->where('SeriesID', $SeriesID);
                        $this->db->where('RoundID', $RoundID);
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('PlayerID', $Assistant['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                        $Return["Status"] = 1;
                        $Return["Player"] = $Assistant;
                        return $Return;
                    }

                    if (@$PlayerRoles['AllRounder'] < $DraftPlayerSelectionCriteria['Ar'] && $Assistant['PlayerRole'] == "AllRounder") {
                        /** insert user team player squad * */
                        $InsertData = array(
                            "UserTeamID" => $UserTeamID,
                            "PlayerPosition" => "Player",
                            "PlayerID" => $Assistant['PlayerID'],
                            "SeriesID" => $SeriesID,
                            "RoundID" => $RoundID,
                            "DateTime" => date('Y-m-d H:i:s'),
                        );
                        $this->db->insert('sports_users_team_players', $InsertData);
                        /* Add contest to contest table . */
                        $UpdateData = array_filter(array(
                            "PlayerStatus" => "Sold",
                            "DateTime" => date('Y-m-d H:i:s')
                        ));
                        $this->db->where('SeriesID', $SeriesID);
                        $this->db->where('RoundID', $RoundID);
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('PlayerID', $Assistant['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                        $Return["Status"] = 1;
                        $Return["Player"] = $Assistant;

                        return $Return;
                    }

                    if (@$PlayerRoles['WicketKeeper'] >= $DraftPlayerSelectionCriteria['Wk'] &&
                            @$PlayerRoles['Bowler'] >= $DraftPlayerSelectionCriteria['Bowl'] &&
                            @$PlayerRoles['Batsman'] >= $DraftPlayerSelectionCriteria['Bat'] &&
                            @$PlayerRoles['AllRounder'] >= $DraftPlayerSelectionCriteria['Ar']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Assistant['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Assistant['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Assistant;

                            return $Return;
                        } else {

                            $Return["Status"] = 0;
                            $Return["Player"] = $Assistant;
                            return $Return;
                        }
                    }
                }
            }
        } else {
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerPosition" => "Player",
                "PlayerID" => $Player['PlayerID'],
                "SeriesID" => $SeriesID,
                "RoundID" => $RoundID,
                "DateTime" => date('Y-m-d H:i:s'),
            );
            $this->db->insert('sports_users_team_players', $InsertData);

            /** update player status * */
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold",
                "DateTime" => date('Y-m-d H:i:s')
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('RoundID', $RoundID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $Player['PlayerID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
            $Return["Status"] = 1;
            $Return["Player"] = $Player;
            return $Return;
        }
    }

    function addDraftUserTeamSquadNotAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $Player, $DraftContestDetails, $RoundID) {
        $Return = array();
        $Return["Status"] = 0;
        $Return["Player"] = array();
        /** dynamic player role * */
        $ContestCriteria = $this->SnakeDrafts_model->getContests('ContestID,DraftTeamPlayerLimit,DraftPlayerSelectionCriteria', array("RoundID" => $RoundID, "ContestID" => $ContestID), FALSE, 1);
        //$Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
        $DraftPlayerSelectionCriteria = (!empty($ContestCriteria['DraftPlayerSelectionCriteria'])) ? $ContestCriteria['DraftPlayerSelectionCriteria'] : array("Wk" => 0, "Bat" => 0, "Ar" => 0, "Bowl" => 0);
        $DraftTeamPlayerLimit = (!empty($ContestCriteria['DraftTeamPlayerLimit'])) ? $ContestCriteria['DraftTeamPlayerLimit'] : 0;
        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID,UTP.PlayerPosition");
        $this->db->from('sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $DraftSquad = $Query->result_array();
            foreach ($DraftSquad as $Key => $PlayerSquad) {
                $DraftSquad[$Key]['PlayerRole'] = $this->db->query('SELECT S.PlayerRole from `tbl_auction_player_bid_status` S  WHERE S.SeriesID = "' . $SeriesID . '" AND S.RoundID = "' . $RoundID . '" AND S.ContestID = "' . $ContestID . '" AND S.PlayerID = "' . $PlayerSquad['PlayerID'] . '"')->row()->PlayerRole;
            }

            $DraftLiveRound = $DraftContestDetails['Data']['Records'][0]['DraftLiveRound'];
            $CriteriaRounds = $DraftPlayerSelectionCriteria['Wk'] + $DraftPlayerSelectionCriteria['Bat'] + $DraftPlayerSelectionCriteria['Bowl'] + $DraftPlayerSelectionCriteria['Ar'];
            /** check player role condition * */
            $PlayerRoles = array_count_values(array_column($DraftSquad, 'PlayerRole'));

            if ($DraftLiveRound <= $CriteriaRounds) {

                if (@$PlayerRoles['WicketKeeper'] < $DraftPlayerSelectionCriteria['Wk']) {
                    /** check total player in squad * */
                    if ($Rows < $DraftTeamPlayerLimit) {

                        /** check is assistant and unsold player * */
                        $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                        $this->db->from('tbl_auction_player_bid_status BS,sports_players SP');
                        $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                        //$this->db->where("BS.PlayerStatus", "Upcoming");
                        $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                        $this->db->where("BS.ContestID", $ContestID);
                        $this->db->where("BS.SeriesID", $SeriesID);
                        $this->db->where("BS.RoundID", $RoundID);
                        $this->db->where("BS.PlayerRole", "WicketKeeper");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $AllPlayer = $Query->row_array();
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $AllPlayer['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $AllPlayer;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        return $Return;
                    }
                }

                if (@$PlayerRoles['Batsman'] < $DraftPlayerSelectionCriteria['Bat']) {
                    /** check total player in squad * */
                    if ($Rows < $DraftTeamPlayerLimit) {

                        /** check is assistant and unsold player * */
                        $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                        $this->db->from('tbl_auction_player_bid_status BS,sports_players SP');
                        $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                        //$this->db->where("BS.PlayerStatus", "Upcoming");
                        $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                        $this->db->where("BS.ContestID", $ContestID);
                        $this->db->where("BS.SeriesID", $SeriesID);
                        $this->db->where("BS.RoundID", $RoundID);
                        $this->db->where("BS.PlayerRole", "Batsman");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $AllPlayer = $Query->row_array();
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $AllPlayer['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $AllPlayer;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        return $Return;
                    }
                }

                if (@$PlayerRoles['Bowler'] < $DraftPlayerSelectionCriteria['Bowl']) {
                    /** check total player in squad * */
                    if ($Rows < $DraftTeamPlayerLimit) {

                        /** check is assistant and unsold player * */
                        $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                        $this->db->from('tbl_auction_player_bid_status BS,sports_players SP');
                        $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                        // $this->db->where("BS.PlayerStatus", "Upcoming");
                        $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                        $this->db->where("BS.ContestID", $ContestID);
                        $this->db->where("BS.SeriesID", $SeriesID);
                        $this->db->where("BS.RoundID", $RoundID);
                        $this->db->where("BS.PlayerRole", "Bowler");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $AllPlayer = $Query->row_array();
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $AllPlayer['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $AllPlayer;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        return $Return;
                    }
                }

                if (@$PlayerRoles['AllRounder'] < $DraftPlayerSelectionCriteria['Ar']) {
                    /** check total player in squad * */
                    if ($Rows < $DraftTeamPlayerLimit) {

                        /** check is assistant and unsold player * */
                        $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                        $this->db->from('tbl_auction_player_bid_status BS,sports_players SP');
                        $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                        //$this->db->where("BS.PlayerStatus", "Upcoming");
                        $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                        $this->db->where("BS.ContestID", $ContestID);
                        $this->db->where("BS.SeriesID", $SeriesID);
                        $this->db->where("BS.RoundID", $RoundID);
                        $this->db->where("BS.PlayerRole", "AllRounder");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $AllPlayer = $Query->row_array();
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $AllPlayer['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $AllPlayer;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        return $Return;
                    }
                }
            } else {
                /** check total player in squad * */
                if ($Rows < $DraftTeamPlayerLimit) {

                    /** check is assistant and unsold player * */
                    $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                    $this->db->from('tbl_auction_player_bid_status BS,sports_players SP');
                    $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                    //$this->db->where("BS.PlayerStatus", "Upcoming");
                    $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                    $this->db->where("BS.ContestID", $ContestID);
                    $this->db->where("BS.SeriesID", $SeriesID);
                    $this->db->where("BS.RoundID", $RoundID);
                    $this->db->limit(1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $AllPlayer = $Query->row_array();
                        /** insert user team player squad * */
                        $InsertData = array(
                            "UserTeamID" => $UserTeamID,
                            "PlayerPosition" => "Player",
                            "PlayerID" => $AllPlayer['PlayerID'],
                            "SeriesID" => $SeriesID,
                            "RoundID" => $RoundID,
                            "DateTime" => date('Y-m-d H:i:s'),
                        );
                        $this->db->insert('sports_users_team_players', $InsertData);
                        /* Add contest to contest table . */
                        $UpdateData = array_filter(array(
                            "PlayerStatus" => "Sold",
                            "DateTime" => date('Y-m-d H:i:s')
                        ));
                        $this->db->where('SeriesID', $SeriesID);
                        $this->db->where('RoundID', $RoundID);
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('PlayerID', $AllPlayer['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                        $Return["Status"] = 1;
                        $Return["Player"] = $AllPlayer;
                        return $Return;
                    } else {
                        $Return["Status"] = 0;
                        return $Return;
                    }
                } else {
                    $Return["Status"] = 0;
                    return $Return;
                }
            }
        } else {
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerPosition" => "Player",
                "PlayerID" => $Player['PlayerID'],
                "SeriesID" => $SeriesID,
                "RoundID" => $RoundID,
                "DateTime" => date('Y-m-d H:i:s'),
            );
            $this->db->insert('sports_users_team_players', $InsertData);

            /** update player status * */
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold",
                "DateTime" => date('Y-m-d H:i:s')
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('RoundID', $RoundID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $Player['PlayerID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
            $Return["Status"] = 1;
            $Return["Player"] = $Player;
            return $Return;
        }
    }

    function addDraftUserTeamSquadIsPlayer($UserTeamID, $UserID, $ContestID, $SeriesID, $Player, $DraftContestDetails, $RoundID) {
        $Return = array();
        $Return["Status"] = 0;
        $Return["Player"] = array();
        /** dynamic player role * */
        $ContestCriteria = $this->SnakeDrafts_model->getContests('ContestID,DraftTeamPlayerLimit,DraftPlayerSelectionCriteria', array("RoundID" => $RoundID, "ContestID" => $ContestID), FALSE, 1);
        //$Series = $this->Sports_model->getSeries("DraftTeamPlayerLimit,DraftPlayerSelectionCriteria", array("SeriesID" => $SeriesID));
        $DraftPlayerSelectionCriteria = (!empty($ContestCriteria['DraftPlayerSelectionCriteria'])) ? $ContestCriteria['DraftPlayerSelectionCriteria'] : array("Wk" => 0, "Bat" => 0, "Ar" => 0, "Bowl" => 0);
        //$DraftPlayerSelectionCriteria = (!empty($DraftPlayerSelectionCriteria)) ? $DraftPlayerSelectionCriteria : array("Wk" => 0, "Bat" => 0, "Ar" => 0, "Bowl" => 0);
        $DraftTeamPlayerLimit = (!empty($ContestCriteria['DraftTeamPlayerLimit'])) ? $ContestCriteria['DraftTeamPlayerLimit'] : 0;
        /** check is assistant and unsold player * */
        $this->db->select("UTP.PlayerID,UTP.PlayerPosition");
        $this->db->from('sports_users_team_players UTP');
        $this->db->where("UTP.UserTeamID", $UserTeamID);
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $DraftSquad = $Query->result_array();
            foreach ($DraftSquad as $Key => $PlayerSquad) {
                $DraftSquad[$Key]['PlayerRole'] = $this->db->query('SELECT S.PlayerRole from `tbl_auction_player_bid_status` S  WHERE S.SeriesID = "' . $SeriesID . '" AND S.RoundID = "' . $RoundID . '" AND S.ContestID = "' . $ContestID . '" AND S.PlayerID = "' . $PlayerSquad['PlayerID'] . '"')->row()->PlayerRole;
            }

            $DraftLiveRound = $DraftContestDetails['Data']['Records'][0]['DraftLiveRound'];
            $CriteriaRounds = $DraftPlayerSelectionCriteria['Wk'] + $DraftPlayerSelectionCriteria['Bat'] + $DraftPlayerSelectionCriteria['Bowl'] + $DraftPlayerSelectionCriteria['Ar'];
            /** check player role condition * */
            $PlayerRoles = array_count_values(array_column($DraftSquad, 'PlayerRole'));

            if ($DraftLiveRound <= $CriteriaRounds) {

                if ($Player['PlayerRole'] == "WicketKeeper") {

                    if (@$PlayerRoles['WicketKeeper'] < $DraftPlayerSelectionCriteria['Wk']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Player['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Player['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Player;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for WicketKeeper is fulfilled. Please select player for another position will you complete the minimum criteria of 11 Players";
                        return $Return;
                    }
                }

                if ($Player['PlayerRole'] == "Batsman") {
                    if (@$PlayerRoles['Batsman'] < $DraftPlayerSelectionCriteria['Bat']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Player['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Player['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Player;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for Batsman is fulfilled. Please select player for another position will you complete the minimum criteria of 11 Players";
                        return $Return;
                    }
                }

                if ($Player['PlayerRole'] == "Bowler") {
                    if (@$PlayerRoles['Bowler'] < $DraftPlayerSelectionCriteria['Bowl']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Player['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Player['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Player;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for Bowler is fulfilled. Please select player for another position will you complete the minimum criteria of 11 Players";
                        return $Return;
                    }
                }

                if ($Player['PlayerRole'] == "AllRounder") {
                    if (@$PlayerRoles['AllRounder'] < $DraftPlayerSelectionCriteria['Ar']) {
                        /** check total player in squad * */
                        if ($Rows < $DraftTeamPlayerLimit) {
                            /** insert user team player squad * */
                            $InsertData = array(
                                "UserTeamID" => $UserTeamID,
                                "PlayerPosition" => "Player",
                                "PlayerID" => $Player['PlayerID'],
                                "SeriesID" => $SeriesID,
                                "RoundID" => $RoundID,
                                "DateTime" => date('Y-m-d H:i:s'),
                            );
                            $this->db->insert('sports_users_team_players', $InsertData);
                            /* Add contest to contest table . */
                            $UpdateData = array_filter(array(
                                "PlayerStatus" => "Sold",
                                "DateTime" => date('Y-m-d H:i:s')
                            ));
                            $this->db->where('SeriesID', $SeriesID);
                            $this->db->where('RoundID', $RoundID);
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('PlayerID', $Player['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                            $Return["Status"] = 1;
                            $Return["Player"] = $Player;
                            return $Return;
                        } else {
                            $Return["Status"] = 0;
                            $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                            return $Return;
                        }
                    } else {
                        $Return["Status"] = 0;
                        $Return['Message'] = "Minimum Criteria for AllRounder is fulfilled. Please select player for another position will you complete the minimum criteria of 11 Players";
                        return $Return;
                    }
                }
            } else {
                if ($Rows < $DraftTeamPlayerLimit) {
                    /** insert user team player squad * */
                    $InsertData = array(
                        "UserTeamID" => $UserTeamID,
                        "PlayerPosition" => "Player",
                        "PlayerID" => $Player['PlayerID'],
                        "SeriesID" => $SeriesID,
                        "RoundID" => $RoundID,
                        "DateTime" => date('Y-m-d H:i:s'),
                    );
                    $this->db->insert('sports_users_team_players', $InsertData);
                    /* Add contest to contest table . */
                    $UpdateData = array_filter(array(
                        "PlayerStatus" => "Sold",
                        "DateTime" => date('Y-m-d H:i:s')
                    ));
                    $this->db->where('SeriesID', $SeriesID);
                    $this->db->where('RoundID', $RoundID);
                    $this->db->where('ContestID', $ContestID);
                    $this->db->where('PlayerID', $Player['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_auction_player_bid_status', $UpdateData);
                    $Return["Status"] = 1;
                    $Return["Player"] = $Player;
                    return $Return;
                } else {
                    $Return["Status"] = 0;
                    $Return['Message'] = "Team Players length can't exceed $DraftTeamPlayerLimit";
                    return $Return;
                }
            }
        } else {
            /** insert user team player squad * */
            $InsertData = array(
                "UserTeamID" => $UserTeamID,
                "PlayerPosition" => "Player",
                "PlayerID" => $Player['PlayerID'],
                "SeriesID" => $SeriesID,
                "RoundID" => $RoundID,
                "DateTime" => date('Y-m-d H:i:s'),
            );
            $this->db->insert('sports_users_team_players', $InsertData);

            /** update player status * */
            /* Add contest to contest table . */
            $UpdateData = array_filter(array(
                "PlayerStatus" => "Sold",
                "DateTime" => date('Y-m-d H:i:s')
            ));
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('RoundID', $RoundID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('PlayerID', $Player['PlayerID']);
            $this->db->limit(1);
            $this->db->update('tbl_auction_player_bid_status', $UpdateData);
            $Return["Status"] = 1;
            $Return["Player"] = $Player;
            return $Return;
        }
    }

    /*
      Description: draft player sold.
     */

    function draftPlayerSold($Input = array(), $SeriesID, $ContestID, $UserID, $PlayerID = "", $RoundID) {
        $Return = array();
        $Return["Data"]["Status"] = 0;
        $Return['Message'] = "Draft player error";
        $Return["Data"]['Player'] = array();
        $Return["Data"]['User'] = array();
        $Return["Data"]['DraftStatus'] = "Running";

        /** check auction completed * */
        $DraftGames = $this->getContests('ContestID,SeriesID,SeriesGUID,DraftTotalRounds,TotalJoined,DraftLiveRound,RoundID', array('AuctionStatusID' => 2, 'LeagueType' => "Draft", "ContestID" => $ContestID, "RoundID" => $RoundID), TRUE, 1);

        /** check player in live * */
        $this->db->select("ContestID,DraftUserLiveTime");
        $this->db->from('sports_contest_join');
        $this->db->where("DraftUserLive", "Yes");
        $this->db->where("UserID", $UserID);
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->where("RoundID", $RoundID);
        $this->db->limit(1);
        $Query = $this->db->get();
        //print_r($Query);die;
        if ($Query->num_rows() > 0) {
            $DraftUserDetails = $Query->row_array();
            /** check is assistant and unsold player * */
            $this->db->select("Username as FirstName,UserGUID,UserID");
            $this->db->from('tbl_users');
            $this->db->where("UserID", $UserID);
            $this->db->limit(1);
            $Query = $this->db->get();
            $UserDetails = $Query->row_array();
            /** check player id empty * */
            if (empty($PlayerID)) {

                $DraftUserLiveTime = $DraftUserDetails['DraftUserLiveTime'];
                $CurrentDateTime = new DateTime($CurrentDateTime);
                $AuctionBreakDateTime = new DateTime($DraftUserLiveTime);
                $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                if ($diffSeconds >= 119) {
                    /** check is assistant and unsold player * */
                    $this->db->select("BS.PlayerRole,UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID,UTP.AuctionDraftAssistantPriority,BS.PlayerStatus,SP.PlayerName");
                    $this->db->from('sports_users_teams UT, sports_users_team_players UTP,tbl_auction_player_bid_status BS,sports_players SP');
                    $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
                    $this->db->where("UT.ContestID", "BS.ContestID", FALSE);
                    $this->db->where("UT.SeriesID", "BS.SeriesID", FALSE);
                    $this->db->where("UTP.PlayerID", "BS.PlayerID", FALSE);
                    $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                    $this->db->where("UT.IsAssistant", "Yes");
                    $this->db->where("UT.IsPreTeam", "Yes");
                    $this->db->where("UT.UserTeamType", "Draft");
                    //$this->db->where("BS.PlayerStatus", "Upcoming");
                    $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                    $this->db->where("UT.ContestID", $ContestID);
                    $this->db->where("UT.SeriesID", $SeriesID);
                    $this->db->where("UT.RoundID", $RoundID);
                    $this->db->where("UT.UserID", $UserID);
                    $this->db->order_by("UTP.AuctionDraftAssistantPriority", "ASC");
                    $this->db->limit(1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $AssistantPlayers = $Query->result_array();
                        foreach ($AssistantPlayers as $Player) {
                            /** user team and squad create * */
                            $UserTeamID = $this->addDraftUserTeam($UserID, $ContestID, $SeriesID, $RoundID);
                            if ($UserTeamID) {
                                $Status = $this->addDraftUserTeamSquadAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $Player, $RoundID);
                                if (empty($Status)) {
                                    /** check is assistant and unsold player * */
                                    $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                                    $this->db->from('tbl_auction_player_bid_status BS,sports_players SP');
                                    $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                                    // $this->db->where("BS.PlayerStatus", "Upcoming");
                                    $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                                    $this->db->where("BS.ContestID", $ContestID);
                                    $this->db->where("BS.SeriesID", $SeriesID);
                                    $this->db->where("BS.RoundID", $RoundID);
                                    $this->db->limit(1);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        $AllPlayer = $Query->result_array();
                                        foreach ($AllPlayer as $Player) {
                                            /** user team and squad create * */
                                            $UserTeamID = $this->addDraftUserTeam($UserID, $ContestID, $SeriesID, $RoundID);
                                            if ($UserTeamID) {
                                                $Status = $this->addDraftUserTeamSquadNotAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $Player, $DraftGames, $RoundID);
                                                if ($Status['Status'] == 1) {
                                                    $Return["Data"]["Status"] = 1;
                                                    $Return['Message'] = "Successfully player added";
                                                } else {
                                                    $Return['Message'] = "Team Players length can't greater than 15";
                                                }
                                                $Return["Data"]['Player'] = $Status['Player'];
                                                $Return["Data"]['User'] = $UserDetails;
                                            }
                                        }
                                    }
                                } else {
                                    if ($Status['Status'] == 1) {
                                        $Return["Data"]["Status"] = 1;
                                        $Return['Message'] = "Successfully player added";
                                    } else {
                                        $Return['Message'] = "Team Players length can't greater than 15";
                                    }
                                    $Return["Data"]['Player'] = $Status['Player'];
                                    $Return["Data"]['User'] = $UserDetails;
                                }
                            }
                        }
                    } else {
                        /** check is assistant and unsold player * */
                        $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                        $this->db->from('tbl_auction_player_bid_status BS,sports_players SP');
                        $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                        // $this->db->where("BS.PlayerStatus", "Upcoming");
                        $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                        $this->db->where("BS.ContestID", $ContestID);
                        $this->db->where("BS.SeriesID", $SeriesID);
                        $this->db->where("BS.RoundID", $RoundID);
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $AllPlayer = $Query->result_array();
                            //print_r($AllPlayer);exit;
                            foreach ($AllPlayer as $Player) {
                                /** user team and squad create * */
                                $UserTeamID = $this->addDraftUserTeam($UserID, $ContestID, $SeriesID, $RoundID);
                                if ($UserTeamID) {
                                    $Status = $this->addDraftUserTeamSquadNotAssistant($UserTeamID, $UserID, $ContestID, $SeriesID, $Player, $DraftGames, $RoundID);

                                    if ($Status['Status'] == 1) {
                                        $Return["Data"]["Status"] = 1;
                                        $Return['Message'] = "Successfully player added";
                                    } else {
                                        $Return['Message'] = "Team Players length can't greater than 15";
                                    }
                                    $Return["Data"]['Player'] = $Status['Player'];
                                    $Return["Data"]['User'] = $UserDetails;
                                }
                            }
                        }
                    }
                }
            } else {
                /** check is assistant and unsold player * */
                $this->db->select("BS.PlayerRole,BS.PlayerStatus,BS.PlayerID,SP.PlayerName");
                $this->db->from('tbl_auction_player_bid_status BS,sports_players SP');
                $this->db->where("BS.PlayerID", "SP.PlayerID", FALSE);
                $this->db->where_in("BS.PlayerStatus", array("Live", "Upcoming"));
                $this->db->where("BS.ContestID", $ContestID);
                $this->db->where("BS.SeriesID", $SeriesID);
                $this->db->where("BS.RoundID", $RoundID);
                $this->db->where("BS.PlayerID", $PlayerID);
                $this->db->limit(1);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $AllPlayer = $Query->result_array();
                    foreach ($AllPlayer as $Player) {
                        /** user team and squad create * */
                        $UserTeamID = $this->addDraftUserTeam($UserID, $ContestID, $SeriesID, $RoundID);
                        if ($UserTeamID) {
                            $IsPlayer = $this->db->query('SELECT PlayerID from `sports_users_team_players` WHERE UserTeamID = "' . $UserTeamID . '" AND PlayerID="' . $PlayerID . '" ')->row()->PlayerID;
                            if (empty($IsPlayer)) {
                                $Status = $this->addDraftUserTeamSquadIsPlayer($UserTeamID, $UserID, $ContestID, $SeriesID, $Player, $DraftGames, $RoundID);
                                if ($Status['Status'] == 1) {
                                    $Return["Data"]["Status"] = 1;
                                    $Return['Message'] = "Successfully player added";
                                } else {
                                    $Return['Message'] = "Team Players length can't greater than 15";
                                    $Return['Message'] = $Status['Message'];
                                }
                            } else {
                                $Return['Message'] = "Player already sold in same user team";
                            }
                            $Return["Data"]['Player'] = $Status['Player'];
                            $Return["Data"]['User'] = $UserDetails;
                        }
                    }
                } else {
                    $Return['Message'] = "Draft player already sold";
                }
            }

            if ($DraftGames['Data']['TotalRecords'] > 0) {
                $Users = array();
                foreach ($DraftGames['Data']['Records'] as $Key => $Draft) {
                    if ($Draft['DraftLiveRound'] >= $Draft['DraftTotalRounds']) {
                        if (($Draft['DraftLiveRound'] % 2) == 0) {
                            /** check last player in live * */
                            $this->db->select("J.ContestID,J.UserID");
                            $this->db->from('sports_contest_join J, tbl_users U');
                            $this->db->where("J.DraftUserLive", "Yes");
                            $this->db->where("U.UserID", "J.UserID", FALSE);
                            $this->db->where("J.ContestID", $ContestID);
                            $this->db->where("J.SeriesID", $SeriesID);
                            $this->db->where("J.RoundID", $RoundID);
                            $this->db->where("J.UserID", $UserID);
                            $this->db->where("J.DraftUserPosition", 1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $Return["Data"]['DraftStatus'] = "Completed";
                                /* draft complete . */
                                $UpdateData = array_filter(array(
                                    "AuctionStatusID" => 5,
                                    "AuctionUpdateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('RoundID', $RoundID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->limit(1);
                                $this->db->update('sports_contest', $UpdateData);
                            }
                        } else {
                            /** check last player in live * */
                            $this->db->select("J.ContestID,J.UserID");
                            $this->db->from('sports_contest_join J, tbl_users U');
                            $this->db->where("J.DraftUserLive", "Yes");
                            $this->db->where("U.UserID", "J.UserID", FALSE);
                            $this->db->where("J.ContestID", $ContestID);
                            $this->db->where("J.SeriesID", $SeriesID);
                            $this->db->where("J.RoundID", $RoundID);
                            $this->db->where("J.UserID", $UserID);
                            $this->db->where("J.DraftUserPosition", $Draft['TotalJoined']);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $Return["Data"]['DraftStatus'] = "Completed";
                                /* draft complete . */
                                $UpdateData = array_filter(array(
                                    "AuctionStatusID" => 5,
                                    "AuctionUpdateTime" => date('Y-m-d H:i:s')
                                ));
                                $this->db->where('SeriesID', $SeriesID);
                                $this->db->where('RoundID', $RoundID);
                                $this->db->where('ContestID', $ContestID);
                                $this->db->limit(1);
                                $this->db->update('sports_contest', $UpdateData);
                            }
                        }
                    }
                }
            }
        } else {
            $Return['Message'] = "User not in live";
        }

        return $Return;
    }

    /*
      Description: get draft rounds.
     */

    function getRounds($RoundID, $ContestID, $ContestDetails) {

        $Return = array();
        $Rounds = array();
        /** to check total player * */
        $DraftTeamPlayerLimit = (!empty($ContestDetails['DraftTeamPlayerLimit'])) ? $ContestDetails['DraftTeamPlayerLimit'] : 0;

        /** get total joined draft users * */
        $JoinedUsers = $this->getJoinedContestsUsers("Username,UserID,DraftUserPosition,ProfilePic,AuctionUserStatus,DraftUserLive", array('ContestID' => $ContestID, 'RoundID' => $RoundID, "OrderBy" => "DraftUserPosition", "Sequence" => "ASC"), TRUE);
        if (!empty($JoinedUsers)) {
            $TotalRecords = $JoinedUsers['Data']['TotalRecords'];
            if ($JoinedUsers['Data']['TotalRecords'] > 0) {
                for ($i = 1; $i <= $DraftTeamPlayerLimit; $i++) {
                    $Users = array();
                    foreach ($JoinedUsers['Data']['Records'] as $Rows) {
                        $Temp['DraftUserPosition'] = $Rows["DraftUserPosition"];
                        $Temp['UserGUID'] = $Rows["UserGUID"];
                        $Temp['FirstName'] = ucwords($Rows["Username"]);
                        $Temp['UserID'] = $Rows["UserID"];
                        $Temp['UserGUID'] = $Rows["UserGUID"];
                        $Temp['ProfilePic'] = $Rows["ProfilePic"];
                        $Temp['AuctionUserStatus'] = $Rows["AuctionUserStatus"];
                        $Temp['DraftUserLive'] = $Rows["DraftUserLive"];
                        $Users[] = $Temp;
                    }
                    if ($i % 2 == 0) {
                        $Users = array_reverse($Users);
                    }
                    $Rounds[$i - 1]['Users'] = $Users;
                    $Rounds[$i - 1]['Round'] = $i;
                }
            }
        }
        return $Rounds;
    }

    function checkAuctionPlayerOnBidAndAuctionCompleted($SeriesID, $ContestID) {
        /** check upcoming player * */
        $this->db->select("PlayerID,BidCredit,PlayerStatus");
        $this->db->from("tbl_auction_player_bid_status");
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('ContestID', $ContestID);
        $this->db->where_in('PlayerStatus', array("Upcoming", "Live"));
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() <= 0) {
            /** auction completed * */
            $UpdateData = array(
                "AuctionStatusID" => 5
            );
            $this->db->where('ContestID', $ContestID);
            $this->db->limit(1);
            $this->db->update('sports_contest', $UpdateData);
        }

        return;
    }

    function addUserTeamPlayerAfterSold($UserID, $SeriesID, $ContestID, $PlayerID, $BidCredit) {

        /** update player bid credit * */
        $UpdateData = array(
            "BidCredit" => $BidCredit,
        );
        $this->db->where('SeriesID', $SeriesID);
        $this->db->where('ContestID', $ContestID);
        $this->db->where('PlayerID', $PlayerID);
        $this->db->limit(1);
        $this->db->update('tbl_auction_player_bid_status', $UpdateData);


        $EntityGUID = get_guid();
        /* Add user team to entity table and get EntityID. */

        $UserBudget = $this->getJoinedContestsUsers("ContestID,UserID,AuctionBudget", array('ContestID' => $ContestID, 'SeriesID' => $SeriesID, 'UserID' => $UserID), FALSE);
        if (!empty($UserBudget)) {
            $this->db->trans_start();

            $UserContestBudget = $UserBudget['AuctionBudget'];
            $UserContestBudget = $UserContestBudget - $BidCredit;
            /* update contest user budget. */
            $UpdateData = array(
                "AuctionBudget" => $UserContestBudget,
            );
            $this->db->where('SeriesID', $SeriesID);
            $this->db->where('ContestID', $ContestID);
            $this->db->where('UserID', $UserID);
            $this->db->limit(1);
            $this->db->update('sports_contest_join', $UpdateData);

            $UserTeamID = $this->db->query('SELECT T.UserTeamID from `sports_users_teams` T join tbl_users U on U.UserID = T.UserID WHERE T.SeriesID = "' . $SeriesID . '" AND T.UserID = "' . $UserID . '" AND T.ContestID = "' . $ContestID . '" AND IsPreTeam = "No" AND IsAssistant="No" ')->row()->UserTeamID;
            if (empty($UserTeamID)) {
                $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $UserID, "StatusID" => 2));
                /* Add user team to user team table . */
                $teamName = "PostAuctionTeam 1";
                $InsertData = array(
                    "UserTeamID" => $EntityID,
                    "UserTeamGUID" => $EntityGUID,
                    "UserID" => $UserID,
                    "UserTeamName" => $teamName,
                    "UserTeamType" => "Auction",
                    "IsPreTeam" => "No",
                    "SeriesID" => $SeriesID,
                    "ContestID" => $ContestID,
                    "IsAssistant" => "No",
                );
                $this->db->insert('sports_users_teams', $InsertData);
                /* Add User Team Players */
                if (!empty($PlayerID)) {

                    /* Manage User Team Players */
                    $UserTeamPlayers = array(
                        'UserTeamID' => $EntityID,
                        'SeriesID' => $SeriesID,
                        'PlayerID' => $PlayerID,
                        'PlayerPosition' => "Player",
                        'BidCredit' => $BidCredit
                    );
                    $this->db->insert('sports_users_team_players', $UserTeamPlayers);
                }
            } else {
                /* Add User Team Players */
                if (!empty($PlayerID)) {
                    /* Manage User Team Players */
                    $UserTeamPlayers = array(
                        'UserTeamID' => $UserTeamID,
                        'SeriesID' => $SeriesID,
                        'PlayerID' => $PlayerID,
                        'PlayerPosition' => "Player",
                        'BidCredit' => $BidCredit
                    );
                    $this->db->insert('sports_users_team_players', $UserTeamPlayers);
                }
            }
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            }
        } else {
            return false;
        }
        return $EntityGUID;
    }

    /*
      Description: Delete contest to system.
     */

    function deleteContest($SessionUserID, $ContestID) {
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->delete('sports_contest');
    }

    /*
      Description: To get contest
     */

    function getContests($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'StatusID' => 'E.StatusID',
                'ContestID' => 'C.ContestID',
                'ContestGUID' => 'C.ContestGUID',
                'Privacy' => 'C.Privacy',
                'IsPaid' => 'C.IsPaid',
                'GameType' => 'C.GameType',
                'RoundID' => 'C.RoundID',
                'AuctionUpdateTime' => 'C.AuctionUpdateTime',
                'AuctionBreakDateTime' => 'C.AuctionBreakDateTime',
                'AuctionTimeBreakAvailable' => 'C.AuctionTimeBreakAvailable',
                'AuctionIsBreakTimeStatus' => 'C.AuctionIsBreakTimeStatus',
                'LeagueType' => 'IF(C.LeagueType = "Draft", "Snake Draft", "Auction Draft") as LeagueType',
                'LeagueJoinDateTime' => 'CONVERT_TZ(C.LeagueJoinDateTime,"+00:00","' . DEFAULT_TIMEZONE . '") AS LeagueJoinDateTime',
                'LeagueJoinDateTimeUTC' => 'C.LeagueJoinDateTime as LeagueJoinDateTimeUTC',
                'GameTimeLive' => 'C.GameTimeLive',
                'AdminPercent' => 'C.AdminPercent',
                'IsConfirm' => 'C.IsConfirm',
                'ShowJoinedContest' => 'C.ShowJoinedContest',
                'WinningAmount' => 'C.WinningAmount',
                'ContestSize' => 'C.ContestSize',
                'ContestFormat' => 'C.ContestFormat',
                'ContestType' => 'C.ContestType',
                'CustomizeWinning' => 'C.CustomizeWinning',
                'EntryFee' => 'C.EntryFee',
                'NoOfWinners' => 'C.NoOfWinners',
                'EntryType' => 'C.EntryType',
                'UserJoinLimit' => 'C.UserJoinLimit',
                'DraftTotalRounds' => 'C.DraftTotalRounds',
                'MinimumUserJoined' => 'C.MinimumUserJoined',
                'CashBonusContribution' => 'C.CashBonusContribution',
                'EntryType' => 'C.EntryType',
                'IsWinningDistributed' => 'C.IsWinningDistributed',
                'UserInvitationCode' => 'C.UserInvitationCode',
                'DraftLiveRound' => 'C.DraftLiveRound',
                'SeriesID' => 'C.SeriesID',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'C.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'C.DraftPlayerSelectionCriteria',
                'SeriesGUID' => 'S.SeriesGUID',
                'SeriesName' => 'S.SeriesName',
                'IsJoined' => '(SELECT IF( EXISTS(
                                SELECT EntryDate FROM sports_contest_join
                                WHERE sports_contest_join.ContestID =  C.ContestID AND UserID = ' . @$Where['SessionUserID'] . ' LIMIT 1), "Yes", "No")) AS IsJoined',
                'TotalJoined' => '(SELECT COUNT(0) FROM sports_contest_join
                                WHERE sports_contest_join.ContestID =  C.ContestID) AS TotalJoined',
                'StatusID' => 'E.StatusID',
                'AuctionStatusID' => 'C.AuctionStatusID',
                'AuctionStatus' => 'CASE C.AuctionStatusID
                             when "1" then "Pending"
                             when "2" then "Running"
                             when "5" then "Completed"
                             when "3" then "Cancelled"
                             END as AuctionStatus',
                'Status' => 'CASE E.StatusID
                             when "1" then "Pending"
                             when "2" then "Running"
                             when "3" then "Cancelled"
                             when "5" then "Completed"
                             END as Status'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        if (!empty($Where['TotalJoinedByRound']) && $Where['TotalJoinedByRound'] == 'Yes' && $Where['RoundID']) {
            $TotalJoinedByRound = $this->db->query("SELECT COUNT(0) as TotalJoinedByRound FROM sports_contest_join, sports_contest C WHERE sports_contest_join.ContestID =  C.ContestID AND sports_contest_join.RoundID =  C.RoundID AND LeagueType = 'Draft' AND sports_contest_join.UserID = '" . @$Where['SessionUserID'] . "' AND sports_contest_join.RoundID = '" . @$Where['RoundID'] . "'");
            $TotalJoinedByRound = $TotalJoinedByRound->row()->TotalJoinedByRound;
            $Return['Data']['TotalJoinedByRound'] = $TotalJoinedByRound;
        }
        $this->db->select('C.ContestGUID,C.ContestName,S.SeriesID,C.RoundID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_contest C,sports_series S');
        $this->db->where("C.ContestID", "E.EntityID", FALSE);
        $this->db->where("S.SeriesID", "C.SeriesID", FALSE);
        $this->db->where("C.LeagueType !=", 'Dfs');
        $this->db->where("C.LeagueType", 'Draft');
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = $Where['Keyword'];
            $this->db->group_start();
            $this->db->like("C.ContestName", $Where['Keyword']);
            $this->db->or_like("S.SeriesName", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("C.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("C.RoundID", $Where['RoundID']);
        }
        if (!empty($Where['ContestType'])) {
            $this->db->where("C.ContestType", $Where['ContestType']);
        }
        if (!empty($Where['ContestGUID'])) {
            $this->db->where("C.ContestGUID", $Where['ContestGUID']);
        }
        if (!empty($Where['LeagueType'])) {
            $this->db->where("C.LeagueType", $Where['LeagueType']);
        }
        if (!empty($Where['AuctionStatusID'])) {
            $this->db->where("C.AuctionStatusID", $Where['AuctionStatusID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("C.UserID", $Where['UserID']);
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Today') {
            $this->db->where("DATE(M.MatchStartDateTime)", date('Y-m-d'));
        }

        if (!empty($Where['Filter']) && $Where['Filter'] == 'LiveAuction') {
            $CurrentDatetime = strtotime(date('Y-m-d H:i:s')) + 3600;
            $NextTime = date("Y-m-d H:i:s");
            $CurrentDatetime = strtotime(date('Y-m-d H:i:s')) - 3600;
            $PreTime = date("Y-m-d H:i:s", $CurrentDatetime);
            $this->db->where("C.LeagueJoinDateTime <=", $NextTime);
            //$this->db->where("C.LeagueJoinDateTime >=", $PreTime);
        }
        if (!empty($Where['Privacy']) && $Where['Privacy'] != 'All') {
            $this->db->where("C.Privacy", $Where['Privacy']);
        }
        if (!empty($Where['ContestType'])) {
            $this->db->where("C.ContestType", $Where['ContestType']);
        }
        if (!empty($Where['ContestFormat'])) {
            $this->db->where("C.ContestFormat", $Where['ContestFormat']);
        }
        if (!empty($Where['IsPaid'])) {
            $this->db->where("C.IsPaid", $Where['IsPaid']);
        }
        if (!empty($Where['IsConfirm'])) {
            $this->db->where("C.IsConfirm", $Where['IsConfirm']);
        }
        if (!empty($Where['WinningAmount'])) {
            $this->db->where("C.WinningAmount >=", $Where['WinningAmount']);
        }
        if (!empty($Where['ContestSize'])) {
            $this->db->where("C.ContestSize", $Where['ContestSize']);
        }
        if (!empty($Where['AutionInLive']) && $Where['AutionInLive'] == "Yes") {
            $this->db->where("C.LeagueJoinDateTime <=", date('Y-m-d H:i:s'));
            $this->db->where("C.AuctionUpdateTime <=", date('Y-m-d H:i:s'));
        }
        if (!empty($Where['EntryFee'])) {
            $this->db->where("C.EntryFee", $Where['EntryFee']);
        }
        if (!empty($Where['NoOfWinners'])) {
            $this->db->where("C.NoOfWinners", $Where['NoOfWinners']);
        }
        if (!empty($Where['EntryType'])) {
            $this->db->where("C.EntryType", $Where['EntryType']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("C.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where_in("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['AuctionStatusID'])) {
            $this->db->where_in("C.AuctionStatusID", $Where['AuctionStatusID']);
        }
        $this->db->where("E.StatusID !=", 3);
        if (isset($Where['MyJoinedContest']) && $Where['MyJoinedContest'] = "Yes") {
            $this->db->where('EXISTS (select ContestID from sports_contest_join JE where JE.ContestID = C.ContestID AND JE.UserID=' . @$Where['SessionUserID'] . ')');
        }
        if (!empty($Where['UserInvitationCode'])) {
            $this->db->where("C.UserInvitationCode", $Where['UserInvitationCode']);
        }
        if (!empty($Where['IsWinningDistributed'])) {
            $this->db->where("C.IsWinningDistributed", $Where['IsWinningDistributed']);
        }
        if (!empty($Where['ContestFull']) && $Where['ContestFull'] == 'No') {
            $this->db->having("TotalJoined !=", 'C.ContestSize', FALSE);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('C.LeagueJoinDateTime', 'DESC');

        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        //$this->db->group_by('C.ContestID'); // Will manage later
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                $defaultCustomizeWinningObj = new stdClass();
                $defaultCustomizeWinningObj->From = 1;
                $defaultCustomizeWinningObj->To = 1;
                $defaultCustomizeWinningObj->Percent = 100;
                $defaultCustomizeWinningObj->WinningAmount = $Record['WinningAmount'];
                foreach ($Query->result_array() as $key => $Record) {

                    $Records[] = $Record;
                    $Records[$key]['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : array($defaultCustomizeWinningObj);
                    //$Records[$key]['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                    $TotalAmountReceived = $this->getTotalContestCollections($Record['ContestGUID']);
                    $Records[$key]['TotalAmountReceived'] = ($TotalAmountReceived) ? $TotalAmountReceived : 0;
                    $TotalWinningAmount = $this->getTotalWinningAmount($Record['ContestGUID']);
                    $Records[$key]['TotalWinningAmount'] = ($TotalWinningAmount) ? $TotalWinningAmount : 0;
                    $Records[$key]['NoOfWinners'] = ($Record['NoOfWinners'] == 0 ) ? 1 : $Record['NoOfWinners'];
                    $Records[$key]['IsSeriesMatchStarted'] = "No";
                    if (in_array('DraftPlayerSelectionCriteria', $Params)) {
                        $Records[$key]['DraftPlayerSelectionCriteria'] = (!empty($Record['DraftPlayerSelectionCriteria'])) ? json_decode($Record['DraftPlayerSelectionCriteria'], TRUE) : array();
                    }
                    if (isset($Where['MyJoinedContest']) && $Where['MyJoinedContest'] == "Yes") {
                        $Records[$key]['IsAuctionFinalTeamSubmitted'] = "No";
                        /** to check auction user final team submitted * */
                        $this->db->select("UserTeamID");
                        $this->db->from('sports_users_teams');
                        $this->db->where("ContestID", $Record['ContestID']);
                        $this->db->where("UserID", @$Where['SessionUserID']);
                        $this->db->where("IsPreTeam", "No");
                        $this->db->where("IsAssistant", "No");
                        $this->db->where("AuctionTopPlayerSubmitted", "Yes");
                        $this->db->where("UserTeamType", "Draft");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $Records[$key]['IsAuctionFinalTeamSubmitted'] = "Yes";
                        }

                        if (isset($Where['MyStats']) && $Where['MyStats'] = "Yes") {
                            $this->db->select("TotalPoints,UserRank,UserWinningAmount,AuctionBudget");
                            $this->db->from('sports_contest_join');
                            $this->db->where("ContestID", $Record['ContestID']);
                            $this->db->where("UserID", @$Where['SessionUserID']);
                            $this->db->limit(1);
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $JoinContest = $Query->row_array();
                                $Records[$key]['TotalPoints'] = $JoinContest['TotalPoints'];
                                $Records[$key]['UserRank'] = $JoinContest['UserRank'];
                                $Records[$key]['UserWinningAmount'] = $JoinContest['UserWinningAmount'];
                                $Records[$key]['AuctionBudget'] = 1000000000 - $JoinContest['AuctionBudget'];
                            }
                        }
                    }

                    /** to check series stared or not * */
                    if (isset($Where['IsSeriesStarted']) && $Where['IsSeriesStarted'] == "Yes") {
                        $this->db->select("MatchID,MatchStartDateTime");
                        $this->db->from('sports_matches');
                        $this->db->where("SeriesID", $Record['SeriesID']);
                        $this->db->where("RoundID", $Where['RoundID']);
                        $this->db->order_by("MatchStartDateTime", "ASC");
                        $this->db->limit(1);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $MatchDetails = $Query->row_array();
                            $CurrentDateTime = strtotime(date('Y-m-d H:i:s'));
                            $MatchDateTime = strtotime($MatchDetails["MatchStartDateTime"]);
                            if ($CurrentDateTime >= $MatchDateTime) {
                                $Records[$key]['IsSeriesMatchStarted'] = "Yes";
                            }
                        }
                    }
                }

                $Return['Data']['Records'] = $Records;
            } else {
                $Record = $Query->row_array();
                $Record['CustomizeWinning'] = (!empty($Record['CustomizeWinning'])) ? json_decode($Record['CustomizeWinning'], TRUE) : new stdClass();
                $TotalAmountReceived = $this->getTotalContestCollections($Record['ContestGUID']);
                $Record['TotalAmountReceived'] = ($TotalAmountReceived) ? $TotalAmountReceived : 0;
                $TotalWinningAmount = $this->getTotalWinningAmount($Record['ContestGUID']);
                $Record['TotalWinningAmount'] = ($TotalWinningAmount) ? $TotalWinningAmount : 0;
                if (in_array('DraftPlayerSelectionCriteria', $Params)) {
                    $Record['DraftPlayerSelectionCriteria'] = (!empty($Record['DraftPlayerSelectionCriteria'])) ? json_decode($Record['DraftPlayerSelectionCriteria'], TRUE) : array();
                }
                /** to check series type * */
                if (isset($Where['DraftSeriesType']) && $Where['DraftSeriesType'] == "Yes") {
                    $this->db->select("RoundFormat");
                    $this->db->from('sports_series_rounds');
                    $this->db->where("RoundID", $Where['RoundID']);
                    $this->db->limit(1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $MatchDetails = $Query->row_array();
                        $Record['DraftSeriesType'] = strtolower($MatchDetails['RoundFormat']);
                    }
                }
                $Record['IsSeriesMatchStarted'] = "No";
                /** to check series stared or not * */
                if (isset($Where['IsSeriesStarted']) && $Where['IsSeriesStarted'] == "Yes") {
                    $this->db->select("MatchID,MatchStartDateTime");
                    $this->db->from('sports_matches');
                    $this->db->where("SeriesID", $Record['SeriesID']);
                    $this->db->where("RoundID", $Where['RoundID']);
                    $this->db->order_by("MatchStartDateTime", "ASC");
                    $this->db->limit(1);
                    $Query = $this->db->get();
                    if ($Query->num_rows() > 0) {
                        $MatchDetails = $Query->row_array();
                        $CurrentDateTime = strtotime(date('Y-m-d H:i:s'));
                        $MatchDateTime = strtotime($MatchDetails["MatchStartDateTime"]);
                        if ($CurrentDateTime >= $MatchDateTime) {
                            $Record['IsSeriesMatchStarted'] = "Yes";
                        }
                    }
                }
                return $Record;
            }
        }
        if (!empty($Where['MatchID'])) {
            $Return['Data']['Statics'] = $this->db->query('SELECT (SELECT COUNT(*) AS `NormalContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Normal" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                                    )as NormalContest,
                    ( SELECT COUNT(*) AS `ReverseContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Reverse" AND C.ContestFormat="League" AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID)
                    )as ReverseContest,(
                    SELECT COUNT(*) AS `JoinedContest` FROM `sports_contest_join` J, `sports_contest` C,tbl_entity E WHERE C.ContestID = J.ContestID AND J.UserID = "' . @$Where['SessionUserID'] . '" AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestID = E.EntityID AND E.StatusID != 3 
                    )as JoinedContest,( 
                    SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . @$Where['SessionUserID'] . '" AND MatchID = "' . $Where['MatchID'] . '"
                ) as TotalTeams,(SELECT COUNT(*) AS `H2HContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestFormat="Head to Head" AND E.StatusID = 1 AND C.ContestSize != (SELECT COUNT(*) from sports_contest_join where sports_contest_join.ContestID = C.ContestID )) as H2HContests')->row();
        }
        $Return['Data']['Records'] = empty($Records) ? array() : $Records;
        return $Return;
    }

    function getTotalContestCollections($ContestGUID) {
        return $this->db->query('SELECT SUM(C.EntryFee) as TotalAmountReceived FROM sports_contest C join sports_contest_join J on C.ContestID = J.ContestID WHERE C.ContestGUID = "' . $ContestGUID . '"')->row()->TotalAmountReceived;
    }

    function getTotalWinningAmount($ContestGUID) {
        return $this->db->query('SELECT SUM(J.UserWinningAmount) as TotalWinningAmount FROM sports_contest C join sports_contest_join J on C.ContestID = J.ContestID WHERE C.ContestGUID = "' . $ContestGUID . '"')->row()->TotalWinningAmount;
    }

    /*
      Description: Join contest
     */

    function joinContest($Input = array(), $SessionUserID, $ContestID, $SeriesID, $RoundID, $UserTeamID) {

        $this->db->trans_start();
        /* Add entry to join contest table . */
        $DraftUserPosition = 0;
        $this->db->select("COUNT(UserID) as Joined");
        $this->db->from("sports_contest_join");
        $this->db->where("ContestID", $ContestID);
        $Query = $this->db->get();
        $Result = $Query->row_array();
        if (isset($Result['Joined'])) {
            $DraftUserPosition = $Result['Joined'] + 1;
        }
        $InsertData = array(
            "UserID" => $SessionUserID,
            "ContestID" => $ContestID,
            "SeriesID" => $SeriesID,
            "RoundID" => $RoundID,
            "UserTeamID" => $UserTeamID,
            "DraftUserPosition" => $DraftUserPosition,
            "EntryDate" => date('Y-m-d H:i:s')
        );
        $this->db->insert('sports_contest_join', $InsertData);
        /* Manage User Wallet */
        if (@$Input['IsPaid'] == 'Yes') {
            $ContestEntryRemainingFees = @$Input['EntryFee'];
            $CashBonusContribution = @$Input['CashBonusContribution'];
            $WalletAmountDeduction = 0;
            $WinningAmountDeduction = 0;
            $CashBonusDeduction = 0;
            if (!empty($CashBonusContribution) && @$Input['CashBonus'] > 0) {
                $CashBonusContributionAmount = $ContestEntryRemainingFees * ($CashBonusContribution / 100);
                if (@$Input['CashBonus'] >= $CashBonusContributionAmount) {
                    $CashBonusDeduction = $CashBonusContributionAmount;
                } else {
                    $CashBonusDeduction = @$Input['CashBonus'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $CashBonusDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['WalletAmount'] > 0) {
                if (@$Input['WalletAmount'] >= $ContestEntryRemainingFees) {
                    $WalletAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WalletAmountDeduction = @$Input['WalletAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WalletAmountDeduction;
            }
            if ($ContestEntryRemainingFees > 0 && @$Input['WinningAmount'] > 0) {
                if (@$Input['WinningAmount'] >= $ContestEntryRemainingFees) {
                    $WinningAmountDeduction = $ContestEntryRemainingFees;
                } else {
                    $WinningAmountDeduction = @$Input['WinningAmount'];
                }
                $ContestEntryRemainingFees = $ContestEntryRemainingFees - $WinningAmountDeduction;
            }
            $InsertData = array(
                "Amount" => @$Input['EntryFee'],
                "WalletAmount" => $WalletAmountDeduction,
                "WinningAmount" => $WinningAmountDeduction,
                "CashBonus" => $CashBonusDeduction,
                "TransactionType" => 'Dr',
                "EntityID" => $ContestID,
                "UserTeamID" => $UserTeamID,
                "Narration" => 'Join Contest',
                "EntryDate" => date("Y-m-d H:i:s")
            );
            $WalletID = $this->Users_model->addToWallet($InsertData, $SessionUserID, 5);
            if (!$WalletID)
                return FALSE;
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }


        /* update contest round * */
        $this->autoShuffleRoundUpdate($ContestID);

        return $this->Users_model->getWalletDetails($SessionUserID);
    }

    /*
      Description: To get joined contest
     */

    function getJoinedContests($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {

        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'MatchID' => 'M.MatchID',
                'MatchGUID' => 'M.MatchGUID',
                'StatusID' => 'E.StatusID',
                'ContestID' => 'C.ContestID',
                'Privacy' => 'C.Privacy',
                'IsPaid' => 'C.IsPaid',
                'IsConfirm' => 'C.IsConfirm',
                'ShowJoinedContest' => 'C.ShowJoinedContest',
                'CashBonusContribution' => 'C.CashBonusContribution',
                'UserInvitationCode' => 'C.UserInvitationCode',
                'WinningAmount' => 'C.WinningAmount',
                'ContestSize' => 'C.ContestSize',
                'UserTeamID' => 'JC.UserTeamID',
                'ContestFormat' => 'C.ContestFormat',
                'ContestType' => 'C.ContestType',
                'EntryFee' => 'C.EntryFee',
                'NoOfWinners' => 'C.NoOfWinners',
                'EntryType' => 'C.EntryType',
                'CustomizeWinning' => 'C.CustomizeWinning',
                'UserID' => 'JC.UserID',
                'JoinInning' => 'JC.JoinInning',
                'EntryDate' => 'JC.EntryDate',
                'TotalPoints' => 'JC.TotalPoints',
                'UserWinningAmount' => 'JC.UserWinningAmount',
                'SeriesID' => 'S.SeriesID',
                'SeriesName' => 'S.SeriesName AS SeriesName',
                'TotalJoined' => '(SELECT COUNT(*) AS TotalJoined
                                                FROM sports_contest_join
                                                WHERE sports_contest_join.ContestID =  C.ContestID ) AS TotalJoined',
                'UserTotalJoinedInMatch' => '(SELECT COUNT(*)
                                                FROM sports_contest_join
                                                WHERE sports_contest_join.MatchID =  M.MatchID AND UserID= ' . $Where['SessionUserID'] . ') AS UserTotalJoinedInMatch',
                'UserRank' => 'JC.UserRank',
                'StatusID' => 'E.StatusID',
                'Status' => 'CASE E.StatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "3" then "Cancelled"
                when "5" then "Completed"
                END as Status',
                'CurrentDateTime' => 'DATE_FORMAT(CONVERT_TZ(Now(),"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . ' ") CurrentDateTime',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }

        $this->db->select('C.ContestGUID,C.ContestName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_contest C,sports_contest_join JC');
        $this->db->where("C.ContestID", "JC.ContestID", FALSE);
        $this->db->where("C.ContestID", "E.EntityID", FALSE);

        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = $Where['Keyword'];
            $this->db->group_start();
            $this->db->like("C.ContestName", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("C.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("JC.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['SessionUserID'])) {
            $this->db->where("JC.UserID", $Where['SessionUserID']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("JC.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['Privacy'])) {
            $this->db->where("C.Privacy", $Where['Privacy']);
        }
        if (!empty($Where['IsPaid'])) {
            $this->db->where("C.IsPaid", $Where['IsPaid']);
        }
        if (!empty($Where['WinningAmount'])) {
            $this->db->where("C.WinningAmount >=", $Where['WinningAmount']);
        }
        if (!empty($Where['ContestSize'])) {
            $this->db->where("C.ContestSize", $Where['ContestSize']);
        }
        if (!empty($Where['EntryFee'])) {
            $this->db->where("C.EntryFee", $Where['EntryFee']);
        }
        if (!empty($Where['NoOfWinners'])) {
            $this->db->where("C.NoOfWinners", $Where['NoOfWinners']);
        }
        if (!empty($Where['EntryType'])) {
            $this->db->where("C.EntryType", $Where['EntryType']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        // $this->db->group_by("UT.UserTeamID");
        $Query = $this->db->get();
        //echo $this->db->last_query();
        //exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                $Return['Data']['Records'] = $Query->result_array();
            } else {
                $Record = $Query->row_array();
                return $Record;
            }
        } else {
            $Return['Data']['Records'] = array();
        }

        return $Return;
    }

    /*
      Description: To get all players
     */

    function getPlayers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'BidCredit' => 'UTP.BidCredit',
                'ContestID' => 'APBS.ContestID as ContestID',
                'SeriesID' => 'APBS.SeriesID as SeriesID',
                'BidSoldCredit' => '(SELECT BidCredit FROM tbl_auction_player_bid_status WHERE RoundID=' . $Where['RoundID'] . ' AND ContestID=' . $Where['ContestID'] . ' AND PlayerID=P.PlayerID) BidSoldCredit',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'BidDateTime' => 'APBS.DateTime as BidDateTime',
                'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                //'PlayerStatus' => '(SELECT PlayerStatus FROM tbl_auction_player_bid_status WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ' AND ContestID=' . @$Where['ContestID'] . ') as PlayerStatus',
                'PlayerStatus' => 'APBS.PlayerStatus as PlayerStatus',
                'PlayerRole' => 'APBS.PlayerRole as PlayerRole',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'PlayerPosition' => 'UTP.PlayerPosition',
                'AuctionDraftAssistantPriority' => 'UTP.AuctionDraftAssistantPriority',
                'AuctionTopPlayerSubmitted' => 'UT.AuctionTopPlayerSubmitted',
                'IsAssistant' => 'UT.IsAssistant',
                'UserTeamName' => 'UT.UserTeamName',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P');

        if (!empty($Where['PlayerBidStatus']) && $Where['PlayerBidStatus'] == "Yes") {
            $this->db->from('tbl_auction_player_bid_status APBS,sports_series S,sports_contest C');
            $this->db->where("APBS.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("S.SeriesID", "APBS.SeriesID", FALSE);
            $this->db->where("C.ContestID", "APBS.ContestID", FALSE);
            if (!empty($Where['PlayerStatus'])) {
                $this->db->where("APBS.PlayerStatus", $Where['PlayerStatus']);
            }
            if (!empty($Where['ContestID'])) {
                $this->db->where("APBS.ContestID", $Where['ContestID']);
            }
            if (!empty($Where['RoundID'])) {
                $this->db->where("APBS.RoundID", $Where['RoundID']);
            }
        }

        if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
            $this->db->select('SADP.TotalPoints');
            $this->db->from('sports_users_teams UT, sports_users_team_players UTP , sports_auction_draft_player_point SADP');
            $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            $this->db->where("SADP.RoundID", "UTP.RoundID", FALSE);
            $this->db->where("SADP.PlayerID", "UTP.PlayerID", FALSE);
            if (!empty($Where['SessionUserID'])) {
                $this->db->where("UT.UserID", @$Where['SessionUserID']);
            }
            if (!empty($Where['IsAssistant'])) {
                $this->db->where("UT.IsAssistant", @$Where['IsAssistant']);
            }
            if (!empty($Where['IsPreTeam'])) {
                $this->db->where("UT.IsPreTeam", @$Where['IsPreTeam']);
            }
            if (!empty($Where['UserID'])) {
                $this->db->where("UT.UserID", @$Where['UserID']);
            }
            if (!empty($Where['BidCredit'])) {
                $this->db->where("UTP.BidCredit >", @$Where['BidCredit']);
            }
            $this->db->where("UT.ContestID", @$Where['ContestID']);
        }
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("P.PlayerName", $Where['Keyword']);
            $this->db->or_like("P.PlayerRole", $Where['Keyword']);
            $this->db->or_like("P.PlayerCountry", $Where['Keyword']);
            $this->db->or_like("P.PlayerBattingStyle", $Where['Keyword']);
            $this->db->or_like("P.PlayerBowlingStyle", $Where['Keyword']);
            $this->db->group_end();
        }
        $this->db->where('EXISTS (select PlayerID FROM sports_team_players WHERE PlayerID=P.PlayerID AND RoundID=' . @$Where['RoundID'] . ')');
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['IsAdminSalaryUpdated'])) {
            $this->db->where("P.IsAdminSalaryUpdated", $Where['IsAdminSalaryUpdated']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {
            //$this->db->order_by('P.PlayerSalary', 'DESC');
            //$this->db->order_by('P.PlayerID', 'DESC');
        }
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }

        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $IsAssistant = "";
                    $AuctionTopPlayerSubmitted = "No";
                    $UserTeamGUID = "";
                    $UserTeamName = "";
                    // $Records[$key]['PlayerSalary'] = $Record['PlayerSalary']*10000000;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    //$Records[$key]['PlayerRole'] = "";
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
                    $this->db->select('TP.PlayerID,TP.PlayerRole,TP.PlayerSalary,T.TeamNameShort,T.TeamName');
                    $this->db->from('sports_team_players TP,sports_teams T');
                    $this->db->where('TP.TeamID', "T.TeamID", FALSE);
                    $this->db->where('TP.PlayerID', $Record['PlayerID']);
                    $this->db->where('TP.RoundID', @$Where['RoundID']);
                    $this->db->order_by("TP.PlayerSalary", 'DESC');
                    $this->db->limit(1);
                    $PlayerDetails = $this->db->get()->result_array();
                    if (!empty($PlayerDetails)) {
                        //$Records[$key]['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                        $Records[$key]['TeamNameShort'] = $PlayerDetails['0']['TeamNameShort'];
                        $Records[$key]['TeamName'] = $PlayerDetails['0']['TeamName'];
                    }
                }
                if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
                    $Return['Data']['IsAssistant'] = $IsAssistant;
                    $Return['Data']['UserTeamGUID'] = $UserTeamGUID;
                    $Return['Data']['UserTeamName'] = $UserTeamName;
                    $Return['Data']['AuctionTopPlayerSubmitted'] = $AuctionTopPlayerSubmitted;
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                //$Record['PlayerRole'] = "";
                $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                $this->db->where('PlayerID', $Record['PlayerID']);
                $this->db->from('sports_team_players');
                $this->db->order_by("PlayerSalary", 'DESC');
                $this->db->limit(1);
                $PlayerDetails = $this->db->get()->result_array();
                if (!empty($PlayerDetails)) {
                    $Record['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                }
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: To get all players auction
     */

    function getPlayersAuction($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'BidCredit' => 'UTP.BidCredit',
                'ContestID' => 'APBS.ContestID as ContestID',
                'SeriesID' => 'APBS.SeriesID as SeriesID',
                'BidSoldCredit' => '(SELECT BidCredit FROM tbl_auction_player_bid_status WHERE SeriesID=' . $Where['SeriesID'] . ' AND ContestID=' . $Where['ContestID'] . ' AND PlayerID=P.PlayerID) BidSoldCredit',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'BidDateTime' => 'APBS.DateTime as BidDateTime',
                'TimeDifference' => " IF(APBS.DateTime IS NULL,20,TIMEDIFF(UTC_TIMESTAMP,APBS.DateTime)) as TimeDifference",
                'PlayerStatus' => '(SELECT PlayerStatus FROM tbl_auction_player_bid_status WHERE PlayerID=P.PlayerID AND SeriesID=' . @$Where['SeriesID'] . ' AND ContestID=' . @$Where['ContestID'] . ') as PlayerStatus',
                'UserTeamGUID' => 'UT.UserTeamGUID',
                'UserID' => 'UT.UserID',
                'PlayerPosition' => 'UTP.PlayerPosition',
                'AuctionTopPlayerSubmitted' => 'UT.AuctionTopPlayerSubmitted',
                'IsAssistant' => 'UT.IsAssistant',
                'UserTeamName' => 'UT.UserTeamName',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P,tbl_auction_player_bid_status ABS');

        $this->db->where("ABS.PlayerID", "P.PlayerID", FALSE);

        if (!empty($Where['PlayerBidStatus']) && $Where['PlayerBidStatus'] == "Yes") {
            $this->db->from('tbl_auction_player_bid_status APBS,sports_series S,sports_contest C');
            $this->db->where("APBS.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("S.SeriesID", "APBS.SeriesID", FALSE);
            $this->db->where("C.ContestID", "APBS.ContestID", FALSE);
            if (!empty($Where['PlayerStatus'])) {
                $this->db->where("APBS.PlayerStatus", $Where['PlayerStatus']);
            }
            if (!empty($Where['ContestID'])) {
                $this->db->where("APBS.ContestID", $Where['ContestID']);
            }
        }

        if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
            $this->db->from('sports_users_teams UT, sports_users_team_players UTP');
            $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
            if (!empty($Where['SessionUserID'])) {
                $this->db->where("UT.UserID", @$Where['SessionUserID']);
            }
            if (!empty($Where['IsAssistant'])) {
                $this->db->where("UT.IsAssistant", @$Where['IsAssistant']);
            }
            if (!empty($Where['IsPreTeam'])) {
                $this->db->where("UT.IsPreTeam", @$Where['IsPreTeam']);
            }
            if (!empty($Where['BidCredit'])) {
                $this->db->where("UTP.BidCredit >", @$Where['BidCredit']);
            }
            $this->db->where("UT.ContestID", @$Where['ContestID']);
        }

        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("P.PlayerName", $Where['Keyword']);
            $this->db->or_like("P.PlayerRole", $Where['Keyword']);
            $this->db->or_like("P.PlayerCountry", $Where['Keyword']);
            $this->db->or_like("P.PlayerBattingStyle", $Where['Keyword']);
            $this->db->or_like("P.PlayerBowlingStyle", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("ABS.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("ABS.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['IsAdminSalaryUpdated'])) {
            $this->db->where("P.IsAdminSalaryUpdated", $Where['IsAdminSalaryUpdated']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {
            //$this->db->order_by('P.PlayerSalary', 'DESC');
            $this->db->order_by('CreateDateTime', 'ASC');
        }
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }

        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        //echo $this->db->last_query();
        //exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $IsAssistant = "";
                    $AuctionTopPlayerSubmitted = "No";
                    $UserTeamGUID = "";
                    $UserTeamName = "";
                    // $Records[$key]['PlayerSalary'] = $Record['PlayerSalary']*10000000;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerRole'] = "";
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
                    $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                    $this->db->where('PlayerID', $Record['PlayerID']);
                    $this->db->from('sports_team_players');
                    $this->db->order_by("PlayerSalary", 'DESC');
                    $this->db->limit(1);
                    $PlayerDetails = $this->db->get()->result_array();
                    if (!empty($PlayerDetails)) {
                        $Records[$key]['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                    }
                }
                if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
                    $Return['Data']['IsAssistant'] = $IsAssistant;
                    $Return['Data']['UserTeamGUID'] = $UserTeamGUID;
                    $Return['Data']['UserTeamName'] = $UserTeamName;
                    $Return['Data']['AuctionTopPlayerSubmitted'] = $AuctionTopPlayerSubmitted;
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerRole'] = "";
                $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                $this->db->where('PlayerID', $Record['PlayerID']);
                $this->db->from('sports_team_players');
                $this->db->order_by("PlayerSalary", 'DESC');
                $this->db->limit(1);
                $PlayerDetails = $this->db->get()->result_array();
                if (!empty($PlayerDetails)) {
                    $Record['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                }
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: ADD user team
     */

    function addUserTeam($Input = array(), $SessionUserID, $SeriesID, $RoundID, $ContestID, $StatusID = 2) {

        $this->db->trans_start();
        $EntityGUID = get_guid();
        /* Add user team to entity table and get EntityID. */
        $EntityID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID" => 12, "UserID" => $SessionUserID, "StatusID" => $StatusID));
        $UserTeamCount = $this->db->query('SELECT count(T.UserTeamID) as UserTeamsCount,U.Username from `sports_users_teams` T join tbl_users U on U.UserID = T.UserID WHERE T.SeriesID = "' . $SeriesID . '" AND T.UserID = "' . $SessionUserID . '" ')->row();
        /* Add user team to user team table . */
        $teamName = "PreSnakeTeam 1";
        $InsertData = array(
            "UserTeamID" => $EntityID,
            "UserTeamGUID" => $EntityGUID,
            "UserID" => $SessionUserID,
            "UserTeamName" => $teamName,
            "UserTeamType" => @$Input['UserTeamType'],
            "IsPreTeam" => @$Input['IsPreTeam'],
            "SeriesID" => @$SeriesID,
            "RoundID" => @$RoundID,
            "ContestID" => @$ContestID,
            "IsAssistant" => "No",
        );
        $this->db->insert('sports_users_teams', $InsertData);

        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

            /* Get Players */
            $PlayersIdsData = array();
            $PlayersData = $this->getSeriesPlayers('PlayerID,MatchID,SeriesID', array('SeriesID' => $SeriesID, 'RoundID' => $RoundID), TRUE, 0);
            if ($PlayersData) {
                foreach ($PlayersData['Data']['Records'] as $PlayerValue) {
                    $PlayersIdsData[$PlayerValue['PlayerGUID']] = $PlayerValue['PlayerID'];
                }
            }

            /* Manage User Team Players */
            $Input['UserTeamPlayers'] = (!is_array($Input['UserTeamPlayers'])) ? json_decode($Input['UserTeamPlayers'], TRUE) : $Input['UserTeamPlayers'];
            $UserTeamPlayers = array();
            foreach ($Input['UserTeamPlayers'] as $Value) {
                if (isset($PlayersIdsData[$Value['PlayerGUID']])) {
                    $UserTeamPlayers[] = array(
                        'UserTeamID' => $EntityID,
                        'SeriesID' => @$SeriesID,
                        'RoundID' => @$RoundID,
                        'PlayerID' => $PlayersIdsData[$Value['PlayerGUID']],
                        'PlayerPosition' => $Value['PlayerPosition'],
                        'AuctionDraftAssistantPriority' => $Value['AuctionDraftAssistantPriority'],
                        'DateTime' => date('Y-m-d H:i:s')
                    );
                }
            }
            if ($UserTeamPlayers)
                $this->db->insert_batch('sports_users_team_players', $UserTeamPlayers);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return $EntityGUID;
    }

    /*
      Description: Assistant on off
     */

    function assistantTeamOnOff($Input = array(), $SessionUserID, $RoundID, $ContestID, $UserTeamID) {

        $this->db->trans_start();

        /* Update Contest Status */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->where('UserID', $SessionUserID);
        $this->db->where('RoundID', $RoundID);
        $this->db->where('ContestID', $ContestID);
        $this->db->where('IsPreTeam', "Yes");
        $this->db->limit(1);
        $this->db->update('sports_users_teams', array('IsAssistant' => @$Input['IsAssistant']));

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /*
      Description: add auction player bid
     */

    function get_max($Array, $Index) {
        $All = array();
        foreach ($Array as $key => $value) {
            /* creating array where the key is transaction_no and
              the value is the array containing this transaction_no */
            $All[$value['BidCredit']] = $value;
        }
        /* now sort the array by the key (transaction_no) */
        krsort($All);
        /* get the second array and return it (see the link below) */
        return array_slice($All, $Index, 1)[0];
    }

    function addAuctionPlayerBid($Input = array(), $SessionUserID, $SeriesID, $ContestID, $PlayerID) {
        $Return = array();
        /** to check user already in bid * */
        $this->db->select("PlayerID,UserID,DateTime");
        $this->db->from('tbl_auction_player_bid');
        $this->db->where("PlayerID", $PlayerID);
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->limit(1);
        $this->db->order_by("DateTime", "DESC");
        $this->db->order_by("BidCredit", "DESC");
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $PlayerBid = $Query->result_array();
            if (!empty($PlayerBid)) {
                if ($SessionUserID == $PlayerBid[0]['UserID']) {
                    $Return["Message"] = "You are currently in bid please wait next bid";
                    $Return["Status"] = 0;
                    return $Return;
                }
            }
        }

        /** to check auction in live * */
        /* $AuctionGames = $this->getContests('ContestID,AuctionBreakDateTime,AuctionStatus,SeriesID,AuctionTimeBreakAvailable,AuctionIsBreakTimeStatus', array('AuctionStatusID' => 2, 'ContestID' => $ContestID), FALSE);
          if (empty($AuctionGames)) {
          $Return["Message"] = "Auction not stared.";
          $Return["Status"] = 0;
          return $Return;
          } */

        /** to check user available budget * */
        $this->db->select("AuctionBudget");
        $this->db->from('sports_contest_join');
        $this->db->where("AuctionBudget >=", $Input['BidCredit']);
        $this->db->where("ContestID", $ContestID);
        $this->db->where("SeriesID", $SeriesID);
        $this->db->where("UserID", $SessionUserID);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            /** To check player in assistant * */
//            $BidUserID = "";
//            $BidUserCredit = "";
//            $this->db->select("UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID");
//            $this->db->from('sports_users_teams UT, sports_users_team_players UTP');
//            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
//            $this->db->where("UT.IsAssistant", "Yes");
//            $this->db->where("UT.IsPreTeam", "Yes");
//            $this->db->where("UTP.BidCredit >", $Input['BidCredit']);
//            $this->db->where("UT.ContestID", $ContestID);
//            $this->db->where("UT.SeriesID", $SeriesID);
//            $this->db->where("UTP.PlayerID", $PlayerID);
//            $Query = $this->db->get();
//            $PlayersAssistant = $Query->result_array();
//            $Rows = $Query->num_rows();
//            if ($Rows > 0) {
//                /** To check assistant player single * */
//                if ($Rows == 1) {
//
//                    $CurrentBidCredit = $Input['BidCredit'];
//                    $AssistantBidCredit = $PlayersAssistant[0]['BidCredit'];
//                    if ($AssistantBidCredit > $CurrentBidCredit) {
//                        if (100000 >= $CurrentBidCredit || $CurrentBidCredit < 1000000) {
//                            $CurrentBidCredit = $CurrentBidCredit + 100000;
//                        } else if (1000000 >= $CurrentBidCredit || $CurrentBidCredit < 10000000) {
//                            $CurrentBidCredit = $CurrentBidCredit + 1000000;
//                        } else if (10000000 >= $CurrentBidCredit || $CurrentBidCredit < 100000000) {
//                            $CurrentBidCredit = $CurrentBidCredit + 10000000;
//                        } else if (10000000 >= $CurrentBidCredit || $CurrentBidCredit < 1000000000) {
//                            $CurrentBidCredit = $CurrentBidCredit + 100000000;
//                        }
//                    }
//                    $BidUserID = $PlayersAssistant[0]['UserID'];
//                    $BidUserCredit = $CurrentBidCredit;
//
//                    /** to check user available budget * */
//                    $this->db->select("AuctionBudget");
//                    $this->db->from('sports_contest_join');
//                    $this->db->where("AuctionBudget >=", $CurrentBidCredit);
//                    $this->db->where("ContestID", $ContestID);
//                    $this->db->where("SeriesID", $SeriesID);
//                    $this->db->where("UserID", $PlayersAssistant[0]['UserID']);
//                    $Query = $this->db->get();
//                    if ($Query->num_rows() > 0) {
//                        /* add player bid */
//                        $InsertData = array(
//                            "SeriesID" => $SeriesID,
//                            "ContestID" => $ContestID,
//                            "UserID" => $PlayersAssistant[0]['UserID'],
//                            "PlayerID" => $PlayerID,
//                            "BidCredit" => $CurrentBidCredit,
//                            "DateTime" => date('Y-m-d H:i:s')
//                        );
//                        $this->db->insert('tbl_auction_player_bid', $InsertData);
//                    } else {
//                        $Return["Message"] = "You have not insufficient budget";
//                        $Return["Status"] = 0;
//                        return $Return;
//                    }
//                } else if ($Rows > 1) {
//                    /** get second highest user* */
//                    $SecondUser = $this->get_max($PlayersAssistant, 1);
//                    if (empty($SecondUser)) {
//                        $SecondUser = $PlayersAssistant[0];
//                    }
//                    $CurrentBidCredit = $AssistantBidCredit = $SecondUser['BidCredit'];
//                    if (100000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000) {
//                        $CurrentBidCredit = $AssistantBidCredit + 100000;
//                    } else if (1000000 >= $AssistantBidCredit || $AssistantBidCredit < 10000000) {
//                        $CurrentBidCredit = $AssistantBidCredit + 1000000;
//                    } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 100000000) {
//                        $CurrentBidCredit = $AssistantBidCredit + 10000000;
//                    } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000000) {
//                        $CurrentBidCredit = $AssistantBidCredit + 100000000;
//                    }
//                    /** get top user* */
//                    $TopUser = $this->get_max($PlayersAssistant, 0);
//                    $TopUserBidCredit = $TopUser['BidCredit'];
//                    if ($CurrentBidCredit > $TopUserBidCredit) {
//                        $CurrentBidCredit = $TopUserBidCredit;
//                    }
//                    $BidUserID = $TopUser['UserID'];
//                    $BidUserCredit = $CurrentBidCredit;
//
//                    /** to check user available budget * */
//                    $this->db->select("AuctionBudget");
//                    $this->db->from('sports_contest_join');
//                    $this->db->where("AuctionBudget >=", $CurrentBidCredit);
//                    $this->db->where("ContestID", $ContestID);
//                    $this->db->where("SeriesID", $SeriesID);
//                    $this->db->where("UserID", $TopUser['UserID']);
//                    $Query = $this->db->get();
//                    if ($Query->num_rows() > 0) {
//                        /* add player bid */
//                        $InsertData = array(
//                            "SeriesID" => $SeriesID,
//                            "ContestID" => $ContestID,
//                            "UserID" => $TopUser['UserID'],
//                            "PlayerID" => $PlayerID,
//                            "BidCredit" => $CurrentBidCredit,
//                            "DateTime" => date('Y-m-d H:i:s')
//                        );
//                        $this->db->insert('tbl_auction_player_bid', $InsertData);
//                    } else {
//                        $Return["Message"] = "You have not insufficient budget";
//                        $Return["Status"] = 0;
//                        return $Return;
//                    }
//                }
//            } else {
//                $BidUserID = $SessionUserID;
//                $BidUserCredit = $Input['BidCredit'];
//                /* add player bid */
//                $InsertData = array(
//                    "SeriesID" => $SeriesID,
//                    "ContestID" => $ContestID,
//                    "UserID" => $SessionUserID,
//                    "PlayerID" => $PlayerID,
//                    "BidCredit" => @$Input['BidCredit'],
//                    "DateTime" => date('Y-m-d H:i:s')
//                );
//                $this->db->insert('tbl_auction_player_bid', $InsertData);
//            }

            $BidUserID = $SessionUserID;
            $BidUserCredit = $Input['BidCredit'];
            /* add player bid */
            $InsertData = array(
                "SeriesID" => $SeriesID,
                "ContestID" => $ContestID,
                "UserID" => $SessionUserID,
                "PlayerID" => $PlayerID,
                "BidCredit" => @$Input['BidCredit'],
                "DateTime" => date('Y-m-d H:i:s')
            );
            $this->db->insert('tbl_auction_player_bid', $InsertData);

            if (!empty($BidUserID) && !empty($BidUserCredit)) {
                $UserData = $this->Users_model->getUsers("Email", array('UserID' => $BidUserID));
                $UserData['BidCredit'] = $BidUserCredit;
                $Return["Message"] = "You have not insufficient budget";
                $Return["Status"] = 1;
                $Return["Data"] = $UserData;
            }
        } else {
            $Return["Message"] = "You have not insufficient budget";
            $Return["Status"] = 0;
        }

        return $Return;
    }

    /*
      Description: get auction bid player time
     */

    function auctionBidTimeManagement($Input, $ContestID = "", $SeriesID = "") {
        $Players = array();
        $TempPlayer = array();
        /** get live auction * */
        $AuctionGames = $this->getContests('ContestID,AuctionBreakDateTime,AuctionStatus,SeriesID,AuctionTimeBreakAvailable,AuctionIsBreakTimeStatus', array('AuctionStatusID' => 2, 'ContestID' => $ContestID, 'SeriesID' => $SeriesID), TRUE, 1);
        if ($AuctionGames['Data']['TotalRecords'] > 0) {
            foreach ($AuctionGames['Data']['Records'] as $Auction) {
                $Players = array();
                /** get contest hold user time management * */
                $AuctionHoldDateTime = "";
                $this->db->select("ContestID,UserID,AuctionTimeBank,AuctionHoldDateTime");
                $this->db->from('sports_contest_join');
                $this->db->where("ContestID", $Auction['ContestID']);
                $this->db->where("SeriesID", $Auction['SeriesID']);
                $this->db->where("IsHold", "Yes");
                $Query = $this->db->get();
                $Rows = $Query->num_rows();
                $HoldUser = $Query->row_array();
                if (!empty($HoldUser)) {
                    $AuctionHoldDateTime = $HoldUser['AuctionHoldDateTime'];
                }
                /** get live player * */
                $PlayerInLive = $playersData = $this->getPlayers($Input['Params'], array_merge($Input, array('SeriesID' => $Auction['SeriesID'], 'ContestID' => $Auction['ContestID'], 'PlayerBidStatus' => 'Yes', 'PlayerStatus' => 'Live', 'OrderBy' => "PlayerID", "Sequence" => "ASC")));
                if (!empty($playersData)) {
                    $Players[] = $playersData;
                } else {
                    /** get upcoming player * */
                    $playersData = $this->getPlayers($Input['Params'], array_merge($Input, array('SeriesID' => $Auction['SeriesID'], 'ContestID' => $Auction['ContestID'], 'PlayerBidStatus' => 'Yes', 'PlayerStatus' => 'Upcoming', 'OrderBy' => "PlayerID", "Sequence" => "ASC")));
                    if (!empty($playersData)) {
                        $Players[] = $playersData;
                    }
                }
                if (!empty($Players)) {
                    foreach ($Players as $key => $Player) {
                        $Players[$key]['PreAssistant'] = "No";
                        if (empty($PlayerInLive)) {
                            $Players[$key]['AuctionTimeBreakAvailable'] = $Auction['AuctionTimeBreakAvailable'];
                        } else {
                            $Players[$key]['AuctionTimeBreakAvailable'] = "No";
                        }

                        $Players[$key]['AuctionIsBreakTimeStatus'] = $Auction['AuctionIsBreakTimeStatus'];
                        /** auction break date time to current date time difference * */
                        $Players[$key]['BreakTimeInSec'] = 0;
                        if ($Auction['AuctionIsBreakTimeStatus'] == "Yes" && $Auction['AuctionTimeBreakAvailable'] == "No") {
                            $AuctionBreakDateTime = $Auction['AuctionBreakDateTime'];
                            $CurrentDateTime = date('Y-m-d H:i:s');
                            $CurrentDateTime = new DateTime($CurrentDateTime);
                            $AuctionBreakDateTime = new DateTime($AuctionBreakDateTime);
                            $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionBreakDateTime->getTimestamp();
                            $Players[$key]['BreakTimeInSec'] = $diffSeconds;
                        }

                        /** to check player in already bid * */
                        $this->db->select("PlayerID,SeriesID,ContestID,BidCredit,DateTime,UserID");
                        $this->db->from('tbl_auction_player_bid');
                        $this->db->where("ContestID", $Player['ContestID']);
                        $this->db->where("SeriesID", $Player['SeriesID']);
                        $this->db->where("PlayerID", $Player['PlayerID']);
                        $this->db->order_by("DateTime", "DESC");
                        $this->db->limit(1);
                        $PlayerDetails = $this->db->get()->result_array();
                        $CurrentDateTime = date('Y-m-d H:i:s');
                        if (!empty($PlayerDetails)) {
                            $Players[$key]['IsSold'] = "UpcomingSold";
                            $DateTime = $PlayerDetails[0]['DateTime'];
                            /** get bid time difference in seconds * */
                            $Players[$key]['TimeDifference'] = strtotime($CurrentDateTime) - strtotime($DateTime);
                            if (!empty($AuctionHoldDateTime)) {
                                $Players[$key]['TimeDifference'] = strtotime($AuctionHoldDateTime) - strtotime($DateTime);
                            }

                            /** check current player in assistant * */
                            $this->db->select("UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID,U.UserGUID,UTP.DateTime");
                            $this->db->from('sports_users_teams UT, sports_users_team_players UTP,tbl_users U');
                            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
                            $this->db->where("U.UserID", "UT.UserID", FALSE);
                            $this->db->where("UT.IsAssistant", "Yes");
                            $this->db->where("UT.IsPreTeam", "Yes");
                            $this->db->where("UT.ContestID", $Player['ContestID']);
                            $this->db->where("UT.SeriesID", $Player['SeriesID']);
                            $this->db->where("UTP.PlayerID", $Player['PlayerID']);
                            $this->db->where("UTP.BidCredit >", $PlayerDetails[0]['BidCredit']);
                            $this->db->order_by("UTP.BidCredit", "DESC");
                            $this->db->limit(2);
                            $Query = $this->db->get();
                            $Rows = $Query->num_rows();
                            if ($Rows > 0) {
                                if ($Rows > 1) {
                                    /** get second highest user* */
                                    $PlayersAssistant = $Query->result_array();
                                    //print_r($PlayersAssistant);exit;
                                    $UserID = 0;
                                    $UserGUID = 0;
                                    $BidCredit = array_column($PlayersAssistant, 'BidCredit', "UserGUID");
                                    $AssistantDateTime = array_column($PlayersAssistant, 'DateTime', "UserGUID");
                                    $UserIDGUID = array_column($PlayersAssistant, 'UserID', "UserGUID");
                                    $MoreThenSamePlayer = array_count_values($BidCredit);
                                    array_filter($MoreThenSamePlayer, function($n) {
                                        return $n > 1;
                                    });
                                    if (!empty($MoreThenSamePlayer)) {
                                        $UserGUID = array_search(min($AssistantDateTime), $AssistantDateTime);
                                        $UserID = $UserIDGUID[array_search(min($AssistantDateTime), $AssistantDateTime)];

                                        $CurrentBidCreditNew = $AssistantBidCredit = $PlayersAssistant[0]['BidCredit'];
                                        if (100000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 100000;
                                        } else if (1000000 >= $AssistantBidCredit || $AssistantBidCredit < 10000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 1000000;
                                        } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 100000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 10000000;
                                        } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 100000000;
                                        }
                                        if ($CurrentBidCreditNew > $PlayersAssistant[0]['BidCredit']) {
                                            $CurrentBidCreditNew = $PlayersAssistant[0]['BidCredit'];
                                        }
                                    } else {
                                        $SecondUser = $this->get_max($PlayersAssistant, 1);
                                        if (empty($SecondUser)) {
                                            $SecondUser = $PlayersAssistant[0];
                                        }
                                        $CurrentBidCreditNew = $AssistantBidCredit = $SecondUser['BidCredit'];
                                        if (100000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 100000;
                                        } else if (1000000 >= $AssistantBidCredit || $AssistantBidCredit < 10000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 1000000;
                                        } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 100000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 10000000;
                                        } else if (10000000 >= $AssistantBidCredit || $AssistantBidCredit < 1000000000) {
                                            $CurrentBidCreditNew = $AssistantBidCredit + 100000000;
                                        }
                                        /** get top user* */
                                        $TopUser = $this->get_max($PlayersAssistant, 0);
                                        $TopUserBidCredit = $TopUser['BidCredit'];
                                        if ($CurrentBidCreditNew > $TopUserBidCredit) {
                                            $CurrentBidCreditNew = $TopUserBidCredit;
                                        }
                                        $UserID = $TopUser['UserID'];
                                        $UserGUID = $TopUser['UserGUID'];
                                    }
                                    /** to check user available budget * */
                                    $this->db->select("AuctionBudget");
                                    $this->db->from('sports_contest_join');
                                    $this->db->where("AuctionBudget >=", $CurrentBidCreditNew);
                                    $this->db->where("ContestID", $Player['ContestID']);
                                    $this->db->where("SeriesID", $Player['SeriesID']);
                                    $this->db->where("UserID", $UserID);
                                    $Query = $this->db->get();
                                    if ($Query->num_rows() > 0) {
                                        /* add player bid */
                                        $Players[$key]['UserGUID'] = $UserGUID;
                                        $Players[$key]['BidCredit'] = $CurrentBidCreditNew;
                                        $Players[$key]['PreAssistant'] = "Yes";
                                    } else {
                                        $Players[$key]['PreAssistant'] = "No";
                                    }
                                } else {
                                    $PlayersAssistantOnBId = $Query->row_array();
                                    $Players[$key]['UserGUID'] = $PlayersAssistantOnBId["UserGUID"];
                                    if ($PlayersAssistantOnBId["UserID"] != $PlayerDetails[0]['UserID']) {
                                        $CurrentBidCredit = $PlayerDetails[0]['BidCredit'];
                                        $AssistantBidCredit = $PlayersAssistantOnBId['BidCredit'];
                                        if ($AssistantBidCredit > $CurrentBidCredit) {
                                            if (100000 >= $CurrentBidCredit || $CurrentBidCredit < 1000000) {
                                                $CurrentBidCredit = $CurrentBidCredit + 100000;
                                            } else if (1000000 >= $CurrentBidCredit || $CurrentBidCredit < 10000000) {
                                                $CurrentBidCredit = $CurrentBidCredit + 1000000;
                                            } else if (10000000 >= $CurrentBidCredit || $CurrentBidCredit < 100000000) {
                                                $CurrentBidCredit = $CurrentBidCredit + 10000000;
                                            } else if (10000000 >= $CurrentBidCredit || $CurrentBidCredit < 1000000000) {
                                                $CurrentBidCredit = $CurrentBidCredit + 100000000;
                                            }
                                        }
                                        if ($AssistantBidCredit >= $CurrentBidCredit) {
                                            $Players[$key]['BidCredit'] = $CurrentBidCredit;

                                            /** to check user available budget * */
                                            $this->db->select("AuctionBudget");
                                            $this->db->from('sports_contest_join');
                                            $this->db->where("AuctionBudget >=", $CurrentBidCredit);
                                            $this->db->where("ContestID", $Player['ContestID']);
                                            $this->db->where("SeriesID", $Player['SeriesID']);
                                            $this->db->where("UserID", $PlayersAssistantOnBId['UserID']);
                                            $Query = $this->db->get();
                                            if ($Query->num_rows() > 0) {
                                                $Players[$key]['PreAssistant'] = "Yes";
                                            } else {
                                                $Players[$key]['PreAssistant'] = "No";
                                            }
                                        } else {
                                            $Players[$key]['PreAssistant'] = "No";
                                        }
                                    } else {
                                        $Players[$key]['PreAssistant'] = "No";
                                    }
                                }
                            }
                        } else {

                            /** check current player in assistant * */
                            $this->db->select("UTP.PlayerID,UTP.BidCredit,UT.UserTeamID,UT.UserID,U.UserGUID");
                            $this->db->from('sports_users_teams UT, sports_users_team_players UTP,tbl_users U');
                            $this->db->where("UT.UserTeamID", "UTP.UserTeamID", FALSE);
                            $this->db->where("U.UserID", "UT.UserID", FALSE);
                            $this->db->where("UT.IsAssistant", "Yes");
                            $this->db->where("UT.IsPreTeam", "Yes");
                            $this->db->where("UT.ContestID", $Player['ContestID']);
                            $this->db->where("UT.SeriesID", $Player['SeriesID']);
                            $this->db->where("UTP.PlayerID", $Player['PlayerID']);
                            $this->db->order_by("UTP.DateTime", "DESC");
                            $Query = $this->db->get();
                            if ($Query->num_rows() > 0) {
                                $PlayersAssistantOnBId = $Query->row_array();
                                $Players[$key]['UserGUID'] = $PlayersAssistantOnBId["UserGUID"];
                                $Players[$key]['BidCredit'] = 100000;
                                /** to check user available budget * */
                                $this->db->select("AuctionBudget");
                                $this->db->from('sports_contest_join');
                                $this->db->where("AuctionBudget >=", 100000);
                                $this->db->where("ContestID", $Player['ContestID']);
                                $this->db->where("SeriesID", $Player['SeriesID']);
                                $this->db->where("UserID", $PlayersAssistantOnBId['UserID']);
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $Players[$key]['PreAssistant'] = "Yes";
                                } else {
                                    $Players[$key]['PreAssistant'] = "No";
                                }
                            } else {
                                $Players[$key]['PreAssistant'] = "No";
                            }

                            /** get bid time difference in seconds * */
                            if (!empty($Player['BidDateTime'])) {
                                $Players[$key]['TimeDifference'] = strtotime($CurrentDateTime) - strtotime($Player['BidDateTime']);

                                if (!empty($AuctionHoldDateTime)) {
                                    $Players[$key]['TimeDifference'] = strtotime($AuctionHoldDateTime) - strtotime($Player['BidDateTime']);
                                }
                            } else {
                                /** check first player and second player * */
                                $this->db->select("ContestID");
                                $this->db->from('tbl_auction_player_bid_status');
                                $this->db->where("ContestID", $Auction['ContestID']);
                                $this->db->where("SeriesID", $Auction['SeriesID']);
                                $this->db->where("DateTime is NOT NULL", NULL, FALSE);
                                $Query = $this->db->get();
                                if ($Query->num_rows() > 0) {
                                    $Players[$key]['TimeDifference'] = 15;
                                } else {
                                    $Players[$key]['TimeDifference'] = 20;
                                }
                            }

                            $Players[$key]['IsSold'] = "UpcomingUnSold";
                        }
                    }
                    $TempPlayer[] = $Players[0];
                }
            }
        }

        return $TempPlayer;
    }

    /*
      Description: EDIT user team
     */

    function editUserTeam($Input = array(), $UserTeamID) {



        $this->db->trans_start();

        /* Delete Team Players */
        $this->db->delete('sports_users_team_players', array('UserTeamID' => $UserTeamID));

        /* Edit user team to user team table . */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->limit(1);
        $this->db->update('sports_users_teams', array('UserTeamName' => $Input['UserTeamName'], 'UserTeamType' => $Input['UserTeamType']));

        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

            /* Get Players */
            $PlayersIdsData = array();
            $PlayersData = $this->getSeriesPlayers('PlayerID,MatchID,SeriesID', array('SeriesID' => $Input['SeriesID'], 'RoundID' => $Input['RoundID']), TRUE, 0);
            if ($PlayersData) {
                foreach ($PlayersData['Data']['Records'] as $PlayerValue) {
                    $PlayersIdsData[$PlayerValue['PlayerGUID']] = $PlayerValue['PlayerID'];
                }
            }

            /* Manage User Team Players */
            $Input['UserTeamPlayers'] = (!is_array($Input['UserTeamPlayers'])) ? json_decode($Input['UserTeamPlayers'], TRUE) : $Input['UserTeamPlayers'];
            $UserTeamPlayers = array();
            foreach ($Input['UserTeamPlayers'] as $Value) {
                if (isset($PlayersIdsData[$Value['PlayerGUID']])) {
                    $UserTeamPlayers[] = array(
                        'UserTeamID' => $UserTeamID,
                        'SeriesID' => $Input['SeriesID'],
                        'RoundID' => $Input['RoundID'],
                        'PlayerID' => $PlayersIdsData[$Value['PlayerGUID']],
                        'PlayerPosition' => $Value['PlayerPosition'],
                        'DateTime' => date('Y-m-d H:i:s'),
                        'AuctionDraftAssistantPriority' => $Value['AuctionDraftAssistantPriority'],
                    );
                }
            }
            if ($UserTeamPlayers)
                $this->db->insert_batch('sports_users_team_players', $UserTeamPlayers);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /*
      Description: get player series wise
     */

    function getSeriesPlayers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'SeriesGUID' => 'S.SeriesGUID',
                'TeamGUID' => 'T.TeamGUID',
                'TeamName' => 'T.TeamName',
                'TeamNameShort' => 'T.TeamNameShort',
                'TeamFlag' => 'T.TeamFlag',
                'PlayerID' => 'P.PlayerID',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerRole' => 'TP.PlayerRole',
                'IsPlaying' => 'TP.IsPlaying',
                'TotalPoints' => 'TP.TotalPoints',
                'PointsData' => 'TP.PointsData',
                'SeriesID' => 'TP.SeriesID',
                'TeamID' => 'TP.TeamID',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'PlayerSalary' => 'FORMAT(TP.PlayerSalary,1) as PlayerSalary',
                'PlayerSalaryCredit' => 'FORMAT(TP.PlayerSalary,1) PlayerSalaryCredit',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
                'MatchTypeID' => 'SSM.MatchTypeID',
                'MatchType' => 'SSM.MatchTypeName as MatchType',
                'TotalPointCredits' => '(SELECT SUM(`TotalPoints`) FROM `sports_team_players` WHERE `PlayerID` = TP.PlayerID AND `SeriesID` = TP.SeriesID) TotalPointCredits'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('DISTINCT(P.PlayerGUID),P.PlayerName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P');
        if (array_keys_exist($Params, array('TeamGUID', 'TeamName', 'TeamNameShort', 'TeamFlag', 'PlayerRole', 'IsPlaying', 'TotalPoints', 'PointsData', 'SeriesID', 'MatchID'))) {
            $this->db->from('sports_teams T, sports_team_players TP');
            $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
            $this->db->where("TP.TeamID", "T.TeamID", FALSE);
        }
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("P.PlayerName", $Where['Keyword']);
            $this->db->or_like("TP.PlayerRole", $Where['Keyword']);
            $this->db->or_like("P.PlayerCountry", $Where['Keyword']);
            $this->db->or_like("P.PlayerBattingStyle", $Where['Keyword']);
            $this->db->or_like("P.PlayerBowlingStyle", $Where['Keyword']);
            $this->db->group_end();
        }
        if (array_keys_exist($Params, array('SeriesGUID'))) {
            $this->db->from('sports_series S');
            $this->db->where("S.SeriesID", "TP.SeriesID", FALSE);
        }
        if (!empty($Where['MatchID'])) {
            $this->db->where("TP.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("TP.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("TP.RoundID", $Where['RoundID']);
        }
        if (!empty($Where['PlayerGUID'])) {
            $this->db->where("P.PlayerGUID", $Where['PlayerGUID']);
        }
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['PlayerRole'])) {
            $this->db->where("TP.PlayerRole", $Where['PlayerRole']);
        }
        if (!empty($Where['IsAdminSalaryUpdated'])) {
            $this->db->where("P.IsAdminSalaryUpdated", $Where['IsAdminSalaryUpdated']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }
        if (!empty($Where['PlayerSalary']) && $Where['PlayerSalary'] == 'Yes') {
            $this->db->where("TP.PlayerSalary >", 0);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {
            $this->db->order_by('P.PlayerName', 'ASC');
        }


        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;
        $MatchStatus = 0;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerSalary'] = $Record['PlayerSalary'];
                    $TotalPointsRound = ($MatchStatus == 2 || $MatchStatus == 5) ? @$Record['TotalPoints'] : @$Record['TotalPointCredits'];
                    $Records[$key]['PointCredits'] = $TotalPointsRound;
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerSalary'] = $Record['PlayerSalary'];
                $TotalPointsRound = ($MatchStatus == 2 || $MatchStatus == 5) ? @$Record['TotalPoints'] : @$Record['TotalPointCredits'];
                $Record['PointCredits'] = $TotalPointsRound;
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: To get user teams
     */

    function getUserTeams($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'UserTeamID' => 'UT.UserTeamID',
                'MatchID' => 'UT.MatchID',
                'MatchInning' => 'UT.MatchInning'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('UT.UserTeamGUID,UT.UserTeamName,UT.UserTeamType');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_users_teams UT');
        $this->db->where("UT.UserTeamID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $this->db->like("UT.UserTeamName", $Where['Keyword']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("UT.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['MatchID'])) {
            $this->db->where("UT.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['UserTeamType']) && $Where['UserTeamType'] != 'All') {
            $this->db->where("UT.UserTeamType", $Where['UserTeamType']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("UT.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['MatchInning'])) {
            $this->db->where("UT.MatchInning", $Where['MatchInning']);
        }
        if (!empty($Where['UserID']) && empty($Where['UserTeamID'])) { // UserTeamID used to manage other user team details (On live score page)
            $this->db->where("UT.UserID", $Where['UserID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('UT.UserTeamID', 'DESC');
        if (!empty($Where['MatchID'])) {
            $Return['Data']['Statics'] = $this->db->query('SELECT (
                SELECT COUNT(*) AS `NormalContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN (1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Normal"
                )as NormalContest,
                (
                SELECT COUNT(*) AS `ReverseContest` FROM `sports_contest` C, `tbl_entity` E WHERE C.ContestID = E.EntityID AND E.StatusID IN(1,2,5) AND C.MatchID = "' . $Where['MatchID'] . '" AND C.ContestType="Reverse"
                )as ReverseContest,
                (
                SELECT COUNT(*) AS `JoinedContest` FROM `sports_contest_join` J, `sports_contest` C WHERE C.ContestID = J.ContestID AND J.UserID = "' . @$Where['SessionUserID'] . '" AND C.MatchID = "' . $Where['MatchID'] . '"
                )as JoinedContest,
                ( 
                SELECT COUNT(*) AS `TotalTeams` FROM `sports_users_teams`WHERE UserID = "' . @$Where['SessionUserID'] . '" AND MatchID = "' . $Where['MatchID'] . '" 
            ) as TotalTeams'
                    )->row();
        }
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }

        $Query = $this->db->get();


        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                if (in_array('UserTeamPlayers', $Params)) {
                    foreach ($Return['Data']['Records'] as $key => $value) {
                        $Return['Data']['Records'][$key]['UserTeamPlayers'] = $this->getUserTeamPlayers('PlayerPosition,PlayerName,PlayerPic,PlayerCountry,PlayerRole,Points', array('UserTeamID' => $value['UserTeamID']));
                    }
                }
                return $Return;
            } else {
                $Record = $Query->row_array();
                if (in_array('UserTeamPlayers', $Params)) {
                    $UserTeamPlayers = $this->getUserTeamPlayers('PlayerPosition,PlayerName,PlayerPic,PlayerCountry,PlayerRole,Points,BidCredit,ContestGUID', array('UserTeamID' => $Where['UserTeamID']));
                    $Record['UserTeamPlayers'] = ($UserTeamPlayers) ? $UserTeamPlayers : array();
                }
                return $Record;
            }
        }

        return FALSE;
    }

    /*
      Description: To get user team players
     */

    function getUserTeamPlayers($Field = '', $Where = array()) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerPosition' => 'UTP.PlayerPosition',
                'Points' => 'UTP.Points',
                'PlayerName' => 'P.PlayerName',
                'PlayerID' => 'P.PlayerID',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerSalary' => 'P.PlayerSalary',
                'PlayerRole' => 'TP.PlayerRole',
                'TeamGUID' => 'T.TeamGUID',
                'MatchType' => 'SM.MatchTypeName as MatchType',
                'BidCredit' => 'UTP.BidCredit'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('DISTINCT P.PlayerGUID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_users_team_players UTP, sports_players P, sports_team_players TP,sports_teams T');
        $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
        $this->db->where("UTP.PlayerID", "TP.PlayerID", FALSE);
        $this->db->where("T.TeamID", "TP.TeamID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = $Where['Keyword'];
            $this->db->like("P.PlayerName", $Where['Keyword']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("UTP.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['PlayerRole'])) {
            $this->db->where("TP.PlayerRole", $Where['PlayerRole']);
        }
        if (!empty($Where['PlayerPosition'])) {
            $this->db->where("UTP.PlayerPosition", $Where['PlayerPosition']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        //$this->db->group_by('P.PlayerID');
        $this->db->order_by('P.PlayerName', 'ASC');
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Records = array();
            foreach ($Query->result_array() as $key => $Record) {
                $Records[] = $Record;
                if (array_keys_exist($Params, array('PlayerSalary'))) {
                    $Records[$key]['PlayerSalary'] = (!empty($Record['PlayerSalary'])) ? json_decode($Record['PlayerSalary']) : new stdClass();
                }

                if (array_keys_exist($Params, array('PointCredits'))) {
                    if ($Record['MatchType'] == 'T20') {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['T20Credits']) ? json_decode($Record['PlayerSalary'], TRUE)['T20Credits'] : 0;
                    } else if ($Record['MatchType'] == 'Test') {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['T20iCredits']) ? json_decode($Record['PlayerSalary'], TRUE)['T20iCredits'] : 0;
                    } else if ($Record['MatchType'] == 'T20I') {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['ODICredits']) ? json_decode($Record['PlayerSalary'], TRUE)['ODICredits'] : 0;
                    } else if ($Record['MatchType'] == 'ODI') {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['TestCredits']) ? json_decode($Record['PlayerSalary'], TRUE)['TestCredits'] : 0;
                    } else {
                        $Records[$key]['PointCredits'] = (json_decode($Record['PlayerSalary'], TRUE)['T20Credits']) ? json_decode($Record['PlayerSalary'], TRUE)['T20Credits'] : 0;
                    }
                }
            }
            return $Records;
        }
        return FALSE;
    }

    /*
      Description: To get contest winning users
     */

    function getContestWinningUsers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'UserWinningAmount' => 'JC.UserWinningAmount',
                'TotalPoints' => 'JC.TotalPoints',
                'EntryFee' => 'C.EntryFee',
                'ContestSize' => 'C.ContestSize',
                'NoOfWinners' => 'C.NoOfWinners',
                'UserTeamName' => 'UT.UserTeamName',
                'FullName' => 'CONCAT_WS(" ",U.FirstName,U.LastName) FullName',
                'UserRank' => 'JC.UserRank'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('C.ContestName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_contest_join JC, sports_contest C, sports_users_teams UT, tbl_users U');
        $this->db->where("C.ContestID", "JC.ContestID", FALSE);
        $this->db->where("JC.UserTeamID", "UT.UserTeamID", FALSE);
        $this->db->where("JC.UserID", "U.UserID", FALSE);
        $this->db->where("JC.UserWinningAmount >", 0);
        if (!empty($Where['Keyword'])) {
            $this->db->like("C.ContestName", $Where['ContestName']);
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("JC.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('UserRank', 'ASC');

        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                return $Return;
            } else {
                return $Query->row_array();
            }
        }
        return FALSE;
    }

    function getUserTeamPlayersAuction($Field = '', $Where = array()) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerPosition' => 'UTP.PlayerPosition',
                'Points' => 'UTP.Points',
                'PlayerName' => 'P.PlayerName',
                'PlayerID' => 'P.PlayerID',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerSalary' => 'P.PlayerSalary',
                'BidCredit' => 'UTP.BidCredit',
                'TotalPoints' => 'SUM(UTP.Points) TotalPoints'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_users_team_players UTP, sports_players P');
        $this->db->where("UTP.PlayerID", "P.PlayerID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = $Where['Keyword'];
            $this->db->like("P.PlayerName", $Where['Keyword']);
        }
        if (!empty($Where['UserTeamID'])) {
            $this->db->where("UTP.UserTeamID", $Where['UserTeamID']);
        }
        if (!empty($Where['PlayerPosition'])) {
            $this->db->where("UTP.PlayerPosition", $Where['PlayerPosition']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('UTP.Points', 'DESC');
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Players = $Query->result_array();
            foreach ($Players as $Key => $Player) {
                $Players[$Key]['PlayerRole'] = "";
                $this->db->select("BS.PlayerRole,BS.PlayerID");
                $this->db->from('tbl_auction_player_bid_status BS');
                $this->db->where("BS.ContestID", $Where["ContestID"]);
                $this->db->where("BS.PlayerID", $Player["PlayerID"]);
                $this->db->limit(1);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $Role = $Query->row_array();
                    $Players[$Key]['PlayerRole'] = $Role['PlayerRole'];
                }
            }
            return $Players;
        }
        return FALSE;
    }

    /*
      Description: To get joined contest users
     */

    function getJoinedContestsUsers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'TotalPoints' => 'JC.TotalPoints',
                'UserWinningAmount' => 'JC.UserWinningAmount',
                'FirstName' => 'U.FirstName',
                'MiddleName' => 'U.MiddleName',
                'LastName' => 'U.LastName',
                'Username' => 'U.Username',
                'Email' => 'U.Email',
                'UserID' => 'U.UserID',
                'UserRank' => 'JC.UserRank',
                'AuctionTimeBank' => 'JC.AuctionTimeBank',
                'AuctionBudget' => 'JC.AuctionBudget',
                'AuctionUserStatus' => 'JC.AuctionUserStatus',
                'ProfilePic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic',
                'UserRank' => 'JC.UserRank',
                'DraftUserPosition' => 'JC.DraftUserPosition',
                'DraftUserLive' => 'JC.DraftUserLive'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('U.UserGUID,JC.UserTeamID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('sports_contest_join JC, tbl_users U');
        $this->db->where("JC.UserID", "U.UserID", FALSE);
        if (!empty($Where['ContestID'])) {
            $this->db->where("JC.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("JC.UserID", $Where['UserID']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("JC.RoundID", $Where['RoundID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("JC.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {

            if (!empty($Where['SessionUserID'])) {
                $this->db->order_by('JC.UserID=' . $Where['SessionUserID'] . ' DESC', null, FALSE);
            }

            $this->db->order_by('JC.UserRank', 'ASC');
        }

        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;

        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                foreach ($Return['Data']['Records'] as $key => $record) {
                    if (!empty($record['UserTeamID'])) {
                        $UserTeamPlayers = $this->getUserTeamPlayersAuction('PLayerID,BidCredit,Points,PlayerPosition,PlayerName,PlayerRole,PlayerPic,TeamGUID,PlayerSalary,MatchType,PointCredits', array('UserTeamID' => $record['UserTeamID'], "ContestID" => $Where['ContestID']));
                        $Return['Data']['Records'][$key]['UserTeamPlayers'] = ($UserTeamPlayers) ? $UserTeamPlayers : array();
                    } else {
                        $Return['Data']['Records'][$key]['UserTeamPlayers'] = array();
                    }
                }
                return $Return;
            } else {
                $result = $Query->row_array();
                return $result;
            }
        }
        return FALSE;
    }

    /*
      Description: To Cancel Contest
     */

    function cancelContest($Input = array(), $SessionUserID, $ContestID) {

        /* Update Contest Status */
        $this->db->where('EntityID', $ContestID);
        $this->db->limit(1);
        $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));

        /* Get Joined Contest */
        $JoinedContestsUsers = $this->getJoinedContestsUsers('UserID,FirstName,Email,UserTeamID', array('ContestID' => $ContestID), TRUE, 0);
        if (!$JoinedContestsUsers)
            exit;

        foreach ($JoinedContestsUsers['Data']['Records'] as $Value) {

            /* Refund Wallet Money */
            if (!empty($Input['EntryFee'])) {

                /* Get Wallet Details */
                $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                    'UserID' => $Value['UserID'],
                    'EntityID' => $ContestID,
                    'UserTeamID' => $Value['UserTeamID'],
                    'Narration' => 'Join Contest'
                ));

                $InsertData = array(
                    "Amount" => $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['WinningAmount'],
                    "WalletAmount" => $WalletDetails['WalletAmount'],
                    "WinningAmount" => $WalletDetails['WinningAmount'],
                    "CashBonus" => $WalletDetails['CashBonus'],
                    "TransactionType" => 'Cr',
                    "EntityID" => $ContestID,
                    "UserTeamID" => $Value['UserTeamID'],
                    "Narration" => 'Cancel Contest',
                    "EntryDate" => date("Y-m-d H:i:s")
                );
                $this->Users_model->addToWallet($InsertData, $Value['UserID'], 5);
            }

            /* Send Mail To Users */
            $EmailArr = array(
                "Name" => $Value['FirstName'],
                "SeriesName" => @$Input['SeriesName'],
                "ContestName" => @$Input['ContestName'],
                "MatchNo" => @$Input['MatchNo'],
                "TeamNameLocal" => @$Input['TeamNameLocal'],
                "TeamNameVisitor" => @$Input['TeamNameVisitor']
            );
            sendMail(array(
                'emailTo' => $Value['Email'],
                'emailSubject' => "Cancel Contest- " . SITE_NAME,
                'emailMessage' => emailTemplate($this->load->view('emailer/cancel_contest', $EmailArr, TRUE))
            ));
        }
    }

    /*
      Description: To get joined contest users
     */

    function getContestBidHistory($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'FirstName' => 'U.FirstName',
                'MiddleName' => 'U.MiddleName',
                'LastName' => 'U.LastName',
                'Username' => 'U.Username',
                'Email' => 'U.Email',
                'UserID' => 'U.UserID',
                'BidCredit' => 'JC.BidCredit',
                //'DateTime' => 'JC.DateTime',
                'DateTime' => 'DATE_FORMAT(CONVERT_TZ(DateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") DateTime',
                'ProfilePic' => 'IF(U.ProfilePic IS NULL,CONCAT("' . BASE_URL . '","uploads/profile/picture/","default.jpg"),CONCAT("' . BASE_URL . '","uploads/profile/picture/",U.ProfilePic)) AS ProfilePic'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('U.UserGUID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_auction_player_bid JC, tbl_users U');

        $this->db->where("JC.UserID", "U.UserID", FALSE);
        if (!empty($Where['ContestID'])) {
            $this->db->where("JC.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['UserID'])) {
            $this->db->where("JC.UserID", $Where['UserID']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("JC.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("JC.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by('JC.DateTime', 'DESC');
        }

        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;

        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                foreach ($Return['Data']['Records'] as $key => $record) {
                    //$UserTeamPlayers = $this->getUserTeamPlayers('PlayerPosition,PlayerName,PlayerRole,PlayerPic,TeamGUID,PlayerSalary,MatchType,PointCredits', array('UserTeamID' => $record['UserTeamID']));
                    // $Return['Data']['Records'][$key]['UserTeamPlayers'] = ($UserTeamPlayers) ? $UserTeamPlayers : array();
                }
                return $Return;
            } else {
                $result = $Query->row_array();
                return $result;
            }
        }
        return FALSE;
    }

    /*
      Description: To auto add minute in every hours
     */

    function auctionLiveAddMinuteInEveryHours($CronID) {

        /* Get Contests Data */
        $Contests = $this->getContests("ContestID,SeriesID,AuctionUpdateTime,LeagueJoinDateTimeUTC,AuctionTimeBreakAvailable", array('LeagueType' => 'Auction', "AuctionStatusID" => 2), TRUE, 1, 50);
        if (isset($Contests['Data']['Records']) && !empty($Contests['Data']['Records'])) {
            foreach ($Contests['Data']['Records'] as $Value) {
                $CurrentDatetime = strtotime(date('Y-m-d H:i:s'));
                $AuctionUpdateTime = strtotime($Value['AuctionUpdateTime']);
                if ($CurrentDatetime >= $AuctionUpdateTime) {
                    /** contest auction joined user get * */
                    $this->db->select("ContestID,UserID,AuctionTimeBank");
                    $this->db->from('sports_contest_join');
                    $this->db->where("ContestID", $Value['ContestID']);
                    $this->db->where("SeriesID", $Value['SeriesID']);
                    $Query = $this->db->get();
                    $Rows = $Query->num_rows();
                    if ($Rows > 0) {
                        $JoinedUsers = $Query->result_array();
                        foreach ($JoinedUsers as $User) {
                            /** contest auction user time bank update every hours * */
                            $UpdateData = array(
                                "AuctionTimeBank" => $User['AuctionTimeBank'] + 60
                            );
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->where('UserID', $User['UserID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', $UpdateData);
                        }
                    }

                    /** contest auction break time update * */
                    $UpdateData = array(
                        "AuctionTimeBreakAvailable" => "Yes",
                        "AuctionUpdateTime" => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + 3600)
                    );
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', $UpdateData);
                }
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query()), JSON_UNESCAPED_UNICODE)));
        }
        return true;
    }

    /*
      Description: Update user status.
     */

    function changeUserStatus($Input = array(), $UserID, $ContestID) {

        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionUserStatus" => $Input['DraftUserStatus']
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->limit(1);
        $this->db->update('sports_contest_join', $UpdateData);
        return true;
    }

    /*
      Description: Update contest status.
     */

    function changeContestStatus($ContestID) {

        /* Add contest to contest table . */
        /* Update Match Status */
        $this->db->where('EntityID', $ContestID);
        $this->db->limit(1);
        $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 2));
        return true;
    }

    /*
      Description: Update user hold time.
     */

    function auctionHoldTimeUpdate($Input = array(), $UserID, $ContestID) {

        $AuctionTimeBank = $this->db->query('SELECT AuctionTimeBank FROM sports_contest_join WHERE ContestID = ' . $ContestID . ' AND UserID= ' . $UserID . ' LIMIT 1')->row()->AuctionTimeBank;
        $RemainingTime = $AuctionTimeBank - $Input['HoldTime'];
        if ($RemainingTime < 0) {
            $RemainingTime = 0;
        }
        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionTimeBank" => $RemainingTime
        );
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->limit(1);
        $this->db->update('sports_contest_join', $UpdateData);
        return true;
    }

    /*
      Description: Update user status.
     */

    function changeUserContestStatusHoldOnOff($Input = array(), $UserID, $ContestID) {
        $Return = array();
        /* Add contest to contest table . */
        $UpdateData = array();
        $UpdateData['IsHold'] = $Input['IsHold'];
        if ($Input['IsHold'] == "Yes") {
            /** to check already user in hold * */
            $this->db->select("UserID");
            $this->db->from('sports_contest_join');
            $this->db->where("ContestID", $ContestID);
            $this->db->where("UserID", $UserID);
            $this->db->where("IsHold", "Yes");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $Return["Message"] = "Auction already hold";
                $Return["Status"] = 0;
                return $Return;
            }

            $UpdateData['AuctionHoldDateTime'] = date("Y-m-d H:i:s");

            /** check user time left * */
            $AuctionTimeBank = $this->db->query('SELECT AuctionTimeBank FROM sports_contest_join WHERE ContestID = ' . $ContestID . ' AND UserID= ' . $UserID . ' AND AuctionTimeBank <= 0 LIMIT 1')->row()->AuctionTimeBank;
            if (!empty($AuctionTimeBank)) {
                $Return["Message"] = "User hold time exceeded";
                $Return["Status"] = 0;
                return $Return;
            }
        }
        if ($Input['IsHold'] == "No") {

            /** to check already user in unhold * */
            $this->db->select("UserID");
            $this->db->from('sports_contest_join');
            $this->db->where("ContestID", $ContestID);
            $this->db->where("UserID", $UserID);
            $this->db->where("IsHold", "No");
            $this->db->limit(1);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $Return["Message"] = "User alrady unhold";
                $Return["Status"] = 1;
                return $Return;
            }

            /** check user on hold * */
            $IsHold = $this->db->query('SELECT IsHold FROM sports_contest_join WHERE ContestID = ' . $ContestID . ' AND UserID= ' . $UserID . ' AND IsHold= "Yes" LIMIT 1')->row()->IsHold;
            if (!empty($IsHold)) {
                /* update user time break . */
                $Query = $this->db->query('SELECT AuctionHoldDateTime,AuctionTimeBank FROM sports_contest_join WHERE ContestID = "' . $ContestID . '" AND UserID = "' . $UserID . '" LIMIT 1');
                $Contest = $Query->row_array();
                if (!empty($Contest)) {
                    $CurrentDateTime = date('Y-m-d H:i:s');
                    $CurrentDateTime = new DateTime($CurrentDateTime);
                    $AuctionHoldDateTime = new DateTime($Contest['AuctionHoldDateTime']);
                    $diffSeconds = $CurrentDateTime->getTimestamp() - $AuctionHoldDateTime->getTimestamp();
                    $AuctionTimeBank = $Contest['AuctionTimeBank'] - $diffSeconds;
                    if ($AuctionTimeBank < 0) {
                        $AuctionTimeBank = 0;
                    }
                    $UpdateData['AuctionTimeBank'] = $AuctionTimeBank;
                }

                /* get last player last bid . */
                $Input['Params'] = "ContestGUID,SeriesGUID,SeriesID,ContestID,TimeDifference,BidDateTime,PlayerStatus,PlayerGUID,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBornPlace,PlayerSalary,PlayerSalaryCredit";
                $AuctionList = $this->auctionBidTimeManagement($Input, $ContestID);
                if (!empty($AuctionList)) {
                    $TimeDifference = abs($AuctionList[0]['TimeDifference']);
                    $PlayerStatus = abs($AuctionList[0]['PlayerStatus']);
                    /** update player table date time upcoming * */
                    if ($PlayerStatus == "Upcoming") {
                        $CurrentDate = strtotime(date("Y-m-d H:i:s")) - $TimeDifference;
                        $CurrentDate = date("Y-m-d H:i:s", $CurrentDate);
                        /** update player table date time * */
                        $this->db->where('ContestID', $ContestID);
                        $this->db->where('SeriesID', $AuctionList[0]['SeriesID']);
                        $this->db->where('PlayerID', $AuctionList[0]['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_auction_player_bid_status', array("DateTime" => $CurrentDate));
                    }
                    /** update player table date time live * */
                    if ($PlayerStatus == "Live") {
                        /* get last player bid auction contest . */
                        $this->db->select("PlayerID,SeriesID,ContestID,UserID,BidCredit,DateTime");
                        $this->db->from('tbl_auction_player_bid');
                        $this->db->where("ContestID", $ContestID);
                        $this->db->where("PlayerID", $AuctionList[0]['PlayerID']);
                        $this->db->order_by("DateTime", "DESC");
                        $this->db->limit(1);
                        $LastBid = $this->db->get()->row_array();
                        if (!empty($LastBid)) {
                            $CurrentDate = strtotime(date("Y-m-d H:i:s")) - $TimeDifference;
                            $CurrentDate = date("Y-m-d H:i:s", $CurrentDate);
                            /** update player table date time * */
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('SeriesID', $LastBid['SeriesID']);
                            $this->db->where('PlayerID', $LastBid['PlayerID']);
                            $this->db->where('UserID', $LastBid['UserID']);
                            $this->db->where('BidCredit', $LastBid['BidCredit']);
                            $this->db->where('DateTime', $LastBid['DateTime']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid', array("DateTime" => $CurrentDate));
                        } else {
                            /** update player table date time * */
                            $CurrentDate = strtotime(date("Y-m-d H:i:s")) - $TimeDifference;
                            $CurrentDate = date("Y-m-d H:i:s", $CurrentDate);
                            /** update player table date time * */
                            $this->db->where('ContestID', $ContestID);
                            $this->db->where('SeriesID', $AuctionList[0]['SeriesID']);
                            $this->db->where('PlayerID', $AuctionList[0]['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_auction_player_bid_status', array("DateTime" => $CurrentDate));
                        }
                    }
                }
            } else {
                $Return["Message"] = "Auction already unhold";
                $Return["Status"] = 0;
                return $Return;
            }
        }
        $this->db->where('ContestID', $ContestID);
        $this->db->where('UserID', $UserID);
        $this->db->limit(1);
        $this->db->update('sports_contest_join', $UpdateData);
        $Return["Message"] = "User hold status successfully updated";
        $Return["Status"] = 1;
        return $Return;
    }

    /*
      Description: aution on break
     */

    function auctionOnBreak($Input = array(), $ContestID) {
        $UpdateData = array();

        /* Add contest to contest table . */
        $UpdateData = array(
            "AuctionIsBreakTimeStatus" => $Input['AuctionIsBreakTimeStatus'],
            "AuctionTimeBreakAvailable" => $Input['AuctionTimeBreakAvailable']
        );
        if ($Input['AuctionIsBreakTimeStatus'] == "Yes") {
            $UpdateData['AuctionBreakDateTime'] = date('Y-m-d H:i:s');
        }
        $this->db->where('ContestID', $ContestID);
        $this->db->limit(1);
        $this->db->update('sports_contest', $UpdateData);
        return true;
    }

    /*
      Description: EDIT auction user team players
     */

    function auctionTeamPlayersSubmit($Input = array(), $UserTeamID, $SeriesID) {


        $this->db->trans_start();

        /* Delete Team Players */
        $this->db->delete('sports_users_team_players', array('UserTeamID' => $UserTeamID));

        /* Edit user team to user team table . */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->limit(1);
        $this->db->update('sports_users_teams', array('AuctionTopPlayerSubmitted' => "Yes"));


        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

            /* Get Players */
            $PlayersIdsData = array();
            $PlayersData = $this->getSeriesPlayers('PlayerID,MatchID,SeriesID', array('SeriesID' => $SeriesID), TRUE, 0);
            if ($PlayersData) {
                foreach ($PlayersData['Data']['Records'] as $PlayerValue) {
                    $PlayersIdsData[$PlayerValue['PlayerGUID']] = $PlayerValue['PlayerID'];
                }
            }

            /* Manage User Team Players */
            $Input['UserTeamPlayers'] = (!is_array($Input['UserTeamPlayers'])) ? json_decode($Input['UserTeamPlayers'], TRUE) : $Input['UserTeamPlayers'];
            $UserTeamPlayers = array();
            foreach ($Input['UserTeamPlayers'] as $Value) {
                if (isset($PlayersIdsData[$Value['PlayerGUID']])) {
                    $UserTeamPlayers[] = array(
                        'UserTeamID' => $UserTeamID,
                        'SeriesID' => $SeriesID,
                        'PlayerID' => $PlayersIdsData[$Value['PlayerGUID']],
                        'PlayerPosition' => $Value['PlayerPosition'],
                        'BidCredit' => $Value['BidCredit']
                    );
                }
            }
            if ($UserTeamPlayers)
                $this->db->insert_batch('sports_users_team_players', $UserTeamPlayers);
        }

        $this->db->select("UserID,ContestID");
        $this->db->from('sports_users_teams');
        $this->db->where("UserTeamID", $UserTeamID);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Records = $Query->row_array();
            /* update join contest team . */
            $this->db->where('ContestID', $Records['ContestID']);
            $this->db->where('UserID', $Records['UserID']);
            $this->db->limit(1);
            $this->db->update('sports_contest_join', array('UserTeamID' => $UserTeamID));
        }


        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    function getAuctionPlayersPoints($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerID' => 'P.PlayerID',
                'PlayerSalary' => 'P.PlayerSalary',
                'SeriesGUID' => 'S.SeriesGUID as SeriesGUID',
                'ContestGUID' => 'C.ContestGUID as ContestGUID',
                'TotalPoints' => 'SUM(TotalPoints) TotalPoints',
                'SeriesID' => 'TP.SeriesID',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('DISTINCT P.PlayerGUID,P.PlayerName');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P,sports_team_players TP');
        $this->db->where("P.PlayerID", "E.EntityID", FALSE);
        $this->db->where("TP.PlayerID", "P.PlayerID", FALSE);

        if (!empty($Where['SeriesID'])) {
            $this->db->where("TP.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        }
        //$this->db->group_by("TP.PlayerID");
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }

        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // exit;
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $IsAssistant = "";
                    $AuctionTopPlayerSubmitted = "No";
                    $UserTeamGUID = "";
                    $UserTeamName = "";
                    // $Records[$key]['PlayerSalary'] = $Record['PlayerSalary']*10000000;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerRole'] = "";
                    $IsAssistant = $Record['IsAssistant'];
                    $UserTeamGUID = $Record['UserTeamGUID'];
                    $UserTeamName = $Record['UserTeamName'];
                    $AuctionTopPlayerSubmitted = $Record['AuctionTopPlayerSubmitted'];
                    $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                    $this->db->where('PlayerID', $Record['PlayerID']);
                    $this->db->from('sports_team_players');
                    $this->db->order_by("PlayerSalary", 'DESC');
                    $this->db->limit(1);
                    $PlayerDetails = $this->db->get()->result_array();
                    if (!empty($PlayerDetails)) {
                        $Records[$key]['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                    }
                }
                if (!empty($Where['MySquadPlayer']) && $Where['MySquadPlayer'] == "Yes") {
                    $Return['Data']['IsAssistant'] = $IsAssistant;
                    $Return['Data']['UserTeamGUID'] = $UserTeamGUID;
                    $Return['Data']['UserTeamName'] = $UserTeamName;
                    $Return['Data']['AuctionTopPlayerSubmitted'] = $AuctionTopPlayerSubmitted;
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerRole'] = "";
                $this->db->select('PlayerID,PlayerRole,PlayerSalary');
                $this->db->where('PlayerID', $Record['PlayerID']);
                $this->db->from('sports_team_players');
                $this->db->order_by("PlayerSalary", 'DESC');
                $this->db->limit(1);
                $PlayerDetails = $this->db->get()->result_array();
                if (!empty($PlayerDetails)) {
                    $Record['PlayerRole'] = $PlayerDetails['0']['PlayerRole'];
                }
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: EDIT auction user team players
     */

    function draftTeamPlayersSubmit($Input = array(), $UserTeamID, $RoundID) {


        $this->db->trans_start();

        /* Delete Team Players */
        $this->db->delete('sports_users_team_players', array('UserTeamID' => $UserTeamID));

        /* Edit user team to user team table . */
        $this->db->where('UserTeamID', $UserTeamID);
        $this->db->limit(1);
        $this->db->update('sports_users_teams', array('AuctionTopPlayerSubmitted' => "Yes"));


        /* Add User Team Players */
        if (!empty($Input['UserTeamPlayers'])) {

            /* Get Players */
            $PlayersIdsData = array();
            $PlayersData = $this->getSeriesPlayers('PlayerID,MatchID,SeriesID', array('RoundID' => $RoundID), TRUE, 0);
            if ($PlayersData) {
                foreach ($PlayersData['Data']['Records'] as $PlayerValue) {
                    $PlayersIdsData[$PlayerValue['PlayerGUID']] = $PlayerValue['PlayerID'];
                }
            }

            /* Manage User Team Players */
            $Input['UserTeamPlayers'] = (!is_array($Input['UserTeamPlayers'])) ? json_decode($Input['UserTeamPlayers'], TRUE) : $Input['UserTeamPlayers'];
            $UserTeamPlayers = array();
            foreach ($Input['UserTeamPlayers'] as $Value) {
                if (isset($PlayersIdsData[$Value['PlayerGUID']])) {
                    $UserTeamPlayers[] = array(
                        'UserTeamID' => $UserTeamID,
                        'SeriesID' => $SeriesID,
                        'RoundID' => $RoundID,
                        'PlayerID' => $PlayersIdsData[$Value['PlayerGUID']],
                        'PlayerPosition' => $Value['PlayerPosition'],
                    );
                }
            }
            if ($UserTeamPlayers)
                $this->db->insert_batch('sports_users_team_players', $UserTeamPlayers);
        }

        $this->db->select("UserID,ContestID");
        $this->db->from('sports_users_teams');
        $this->db->where("UserTeamID", $UserTeamID);
        $this->db->limit(1);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Records = $Query->row_array();
            /* update join contest team . */
            $this->db->where('ContestID', $Records['ContestID']);
            $this->db->where('UserID', $Records['UserID']);
            $this->db->limit(1);
            $this->db->update('sports_contest_join', array('UserTeamID' => $UserTeamID));
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    function draftTeamAutoSubmit($CronID) {

        /** get draft contest all joined user team not submitted after 15 min * */
        $this->db->select("C.ContestID,C.AuctionUpdateTime,TIMESTAMPDIFF(MINUTE,C.AuctionUpdateTime,UTC_TIMESTAMP()) as M");
        $this->db->from('sports_contest C');
        $this->db->where("C.AuctionStatusID", 5);
        $this->db->where("C.LeagueType", "Draft");
        $this->db->where("C.DraftUserTeamSubmitted", "No");
        $this->db->where("TIMESTAMPDIFF(MINUTE,C.AuctionUpdateTime,UTC_TIMESTAMP()) >", 15);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Contests = $Query->result_array();
            foreach ($Contests as $Contest) {
                $this->db->select("C.ContestID,C.UserID,T.UserTeamID,T.UserID");
                $this->db->from('sports_contest_join C,sports_users_teams T');
                $this->db->where("T.ContestID", "C.ContestID", FALSE);
                $this->db->where("T.UserID", "C.UserID", FALSE);
                $this->db->where("C.ContestID", $Contest['ContestID']);
                $this->db->where("T.UserTeamType", "Draft");
                $this->db->where("T.IsPreTeam", "No");
                $this->db->where("T.AuctionTopPlayerSubmitted", "No");
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $JoinedUser = $Query->result_array();
                    foreach ($JoinedUser as $Join) {
                        /** get first and second player* */
                        $Sql = "SELECT UserTeamID,PlayerID FROM sports_users_team_players WHERE UserTeamID = '" . $Join['UserTeamID'] . "'  ORDER BY DateTime ASC LIMIT 2";
                        $Players = $this->Sports_model->customQuery($Sql);
                        if (!empty($Players)) {
                            $PlayerPosition = array("Captain", "ViceCaptain");
                            foreach ($Players as $Key => $Player) {
                                /** first and second player position update* */
                                $Sql = "UPDATE sports_users_team_players SET PlayerPosition='" . $PlayerPosition[$Key] . "' WHERE UserTeamID = '" . $Join['UserTeamID'] . "' AND PlayerID='" . $Player['PlayerID'] . "'  LIMIT 1";
                                $Return = $this->Sports_model->customQuery($Sql, FALSE, TRUE);
                            }
                            /* Edit user team to user team table . */
                            $this->db->where('UserTeamID', $Join['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('sports_users_teams', array('AuctionTopPlayerSubmitted' => "Yes"));

                            /* update join contest team . */
                            $this->db->where('ContestID', $Join['ContestID']);
                            $this->db->where('UserID', $Join['UserID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('UserTeamID' => $Join['UserTeamID']));
                        }
                    }
                }
                /* Edit user team to user team table . */
                $this->db->where('ContestID', $Contest['ContestID']);
                $this->db->limit(1);
                $this->db->update('sports_contest', array('DraftUserTeamSubmitted' => "Yes"));
            }
        }
    }

    /**
     * Get snake draft player history
     */
    function getContestDraftPlayerHistory($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'PlayerName' => 'SP.PlayerName',
                'PlayerRole' => 'JC.PlayerRole',
                'DateTime' => 'DATE_FORMAT(CONVERT_TZ(DateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") DateTime',
                'PlayerPic' => 'IF(SP.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",SP.PlayerPic)) AS PlayerPic',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('SP.PlayerID');
        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_auction_player_bid_status JC');
        $this->db->join('sports_players SP', 'JC.PlayerID = SP.PlayerID', 'inner');
        $this->db->where('JC.PlayerStatus', 'Sold');
        if (!empty($Where['ContestID'])) {
            $this->db->where("JC.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("JC.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("JC.RoundID", $Where['RoundID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by('JC.DateTime', 'DESC');
        }
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Return['Data']['Records'] = $Query->result_array();
                foreach ($Return['Data']['Records'] as $key => $record) {
                    $this->db->select('U.FirstName');
                    $this->db->from('sports_users_teams UT');
                    $this->db->join('tbl_users U', 'U.UserID = UT.UserID', 'inner');
                    $this->db->join('sports_users_team_players UTP', 'UTP.UserTeamID = UT.UserTeamID', 'inner');
                    $this->db->where('UTP.PlayerID', $record['PlayerID']);
                    $this->db->where('UT.ContestID', $Where['ContestID']);
                    $this->db->where('UT.IsPreTeam', "No");
                    $this->db->where('UT.IsAssistant', "No");
                    $this->db->limit(1);
                    $data = $this->db->get();
                    $result = $data->row_array();
                    $Return['Data']['Records'][$key]['FirstName'] = $result['FirstName'];
                }
                return $Return;
            } else {
                $result = $Query->row_array();
                return $result;
            }
        }
        return FALSE;
    }

    function draftPlayersPoint($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'TeamGUID' => 'T.TeamGUID',
                'TeamName' => 'T.TeamName',
                'TeamNameShort' => 'T.TeamNameShort',
                'TeamFlag' => 'T.TeamFlag',
                'PlayerID' => 'P.PlayerID',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerRole' => 'TP.PlayerRole',
                'IsPlaying' => 'TP.IsPlaying',
                'TotalPoints' => 'TP.TotalPoints',
                'PointsData' => 'TP.PointsData',
                'SeriesID' => 'TP.SeriesID',
                'MatchID' => 'TP.MatchID',
                'TeamID' => 'TP.TeamID',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'PlayerSalary' => 'TP.PlayerSalary',
                'PlayerSalaryCredit' => 'TP.PlayerSalary PlayerSalaryCredit',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
                'MatchTypeID' => 'SSM.MatchTypeID',
                'MatchType' => 'SSM.MatchTypeName as MatchType',
                'TotalPointCredits' => '(SELECT SUM(`TotalPoints`) FROM `sports_team_players` WHERE `PlayerID` = TP.PlayerID AND `SeriesID` = TP.SeriesID) TotalPointCredits'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName,TL.TeamName AS TeamNameLocal,TV.TeamName AS TeamNameVisitor,TL.TeamNameShort AS TeamNameShortLocal,TV.TeamNameShort AS TeamNameShortVisitor, CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TL.TeamFlag) AS TeamFlagLocal, CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TV.TeamFlag) AS TeamFlagVisitor');

        if (!empty($Field))
            $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P, sports_teams TL, sports_teams TV');
        if (array_keys_exist($Params, array('TeamGUID', 'TeamName', 'TeamNameShort', 'TeamFlag', 'PlayerRole', 'IsPlaying', 'TotalPoints', 'PointsData', 'SeriesID', 'MatchID'))) {
            $this->db->from('sports_teams T,sports_matches M, sports_private_contest_team_players TP,sports_set_match_types SSM');
            $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
            $this->db->where("TP.TeamID", "T.TeamID", FALSE);
            $this->db->where("TP.MatchID", "M.MatchID", FALSE);
            $this->db->where("M.MatchTypeID", "SSM.MatchTypeID", FALSE);
        }
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
        $this->db->where("TP.MatchID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("P.PlayerName", $Where['Keyword']);
            $this->db->or_like("TP.PlayerRole", $Where['Keyword']);
            $this->db->or_like("P.PlayerCountry", $Where['Keyword']);
            $this->db->or_like("P.PlayerBattingStyle", $Where['Keyword']);
            $this->db->or_like("P.PlayerBowlingStyle", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("TP.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['ContestID'])) {
            $this->db->where("TP.ContestID", $Where['ContestID']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("TP.RoundID", $Where['RoundID']);
        }
        if (!empty($Where['PlayerGUID'])) {
            $this->db->where("P.PlayerGUID", $Where['PlayerGUID']);
        }
        if (!empty($Where['TeamID'])) {
            $this->db->where("TP.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['IsPlaying'])) {
            $this->db->where("TP.IsPlaying", $Where['IsPlaying']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("P.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['IsAdminSalaryUpdated'])) {
            $this->db->where("P.IsAdminSalaryUpdated", $Where['IsAdminSalaryUpdated']);
        }
        if (!empty($Where['StatusID'])) {
            if ($Where['StatusID'] == 5) {
                $Where['StatusID'] = array(2, 5, 3, 8);
                $this->db->where_in("E.StatusID", $Where['StatusID']);
            } else {
                $Where['StatusID'] = array(1, 2, 3, 4, 5, 8, 10);
                $this->db->where_in("E.StatusID", $Where['StatusID']);
            }
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }

        if (!empty($Where['RandData'])) {
            $this->db->order_by($Where['RandData']);
        } else {
            $this->db->order_by('P.PlayerName', 'ASC');
        }


        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        //$this->db->where("E.StatusID", $StatusID);
        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        //echo $this->db->last_query();exit;
        $MatchStatus = 0;
        if (!empty($Where['SeriesID'])) {
            /* Get Match Status */
            $MatchQuery = $this->db->query('SELECT E.StatusID FROM `sports_matches` `M`,`tbl_entity` `E` WHERE M.`SeriesID` = "' . $Where['SeriesID'] . '" AND M.SeriesID = E.EntityID LIMIT 1');
            $MatchStatus = ($MatchQuery->num_rows() > 0) ? $MatchQuery->row()->StatusID : 0;
        }
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {

                    $Records[] = $Record;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerSalary'] = $Record['PlayerSalary'];
                    $Records[$key]['PointCredits'] = ($MatchStatus == 2 || $MatchStatus == 5) ? @$Record['TotalPoints'] : @$Record['TotalPointCredits'];

                    if (in_array('MyTeamPlayer', $Params)) {
                        $this->db->select('SUTP.PlayerID,SUTP.SeriesID');
                        $this->db->where('SUTP.SeriesID', $Where['SeriesID']);
                        $this->db->where('SUT.UserID', $Where['UserID']);
                        $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                        $MyPlayers = $this->db->get()->result_array();
                        if (!empty($MyPlayers)) {
                            foreach ($MyPlayers as $k => $value) {
                                if ($value['PlayerID'] == $Record['PlayerID']) {
                                    $Records[$key]['MyPlayer'] = 'Yes';
                                } else {
                                    $Records[$key]['MyPlayer'] = 'No';
                                }
                            }
                        } else {
                            $Records[$key]['MyPlayer'] = 'No';
                        }
                    }

                    if (in_array('PlayerSelectedPercent', $Params)) {
                        $TotalTeams = $this->db->query('Select count(*) as TotalTeams from sports_users_teams WHERE SeriesID="' . $Where['SeriesID'] . '"')->row()->TotalTeams;

                        $this->db->select('count(SUTP.PlayerID) as TotalPlayer');
                        $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                        $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                        $this->db->where("SUTP.SeriesID", $Where['SeriesID']);
                        $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                        $Players = $this->db->get()->row();
                        $Records[$key]['PlayerSelectedPercent'] = ($TotalTeams > 0 ) ? round((($Players->TotalPlayer * 100 ) / $TotalTeams), 2) > 100 ? 100 : round((($Players->TotalPlayer * 100 ) / $TotalTeams), 2) : 0;
                    }

                    if (in_array('TopPlayer', $Params)) {
                        $Wicketkipper = $this->findKeyValuePlayers($Records, "WicketKeeper");
                        $Batsman = $this->findKeyValuePlayers($Records, "Batsman");
                        $Bowler = $this->findKeyValuePlayers($Records, "Bowler");
                        $Allrounder = $this->findKeyValuePlayers($Records, "AllRounder");
                        usort($Batsman, function ($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });
                        usort($Bowler, function ($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });
                        usort($Wicketkipper, function ($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });
                        usort($Allrounder, function ($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });

                        $TopBatsman = array_slice($Batsman, 0, 4);
                        $TopBowler = array_slice($Bowler, 0, 3);
                        $TopWicketkipper = array_slice($Wicketkipper, 0, 1);
                        $TopAllrounder = array_slice($Allrounder, 0, 3);

                        $AllPlayers = array();
                        $AllPlayers = array_merge($TopBatsman, $TopBowler);
                        $AllPlayers = array_merge($AllPlayers, $TopAllrounder);
                        $AllPlayers = array_merge($AllPlayers, $TopWicketkipper);

                        rsort($AllPlayers, function($a, $b) {
                            return $b['TotalPoints'] - $a['TotalPoints'];
                        });
                    }

                    if (in_array($Record['PlayerID'], array_column($AllPlayers, 'PlayerID'))) {
                        $Records[$key]['TopPlayer'] = 'Yes';
                    } else {
                        $Records[$key]['TopPlayer'] = 'No';
                    }
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerSalary'] = $Record['PlayerSalary'];
                $Record['PointCredits'] = ($MatchStatus == 2 || $MatchStatus == 5) ? @$Record['TotalPoints'] : @$Record['TotalPointCredits'];
                if (in_array('MyTeamPlayer', $Params)) {
                    $this->db->select('SUTP.PlayerID,SUTP.SeriesID');
                    $this->db->where('SUTP.SeriesID', $Where['SeriesID']);
                    $this->db->where('SUT.UserID', $Where['UserID']);
                    $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                    $MyPlayers = $this->db->get()->result_array();
                    foreach ($MyPlayers as $key => $value) {
                        if ($value['PlayerID'] == $Record['PlayerID']) {
                            $Records['MyPlayer'] = 'Yes';
                        } else {
                            $Records['MyPlayer'] = 'No';
                        }
                    }
                }

                if (in_array('TopPlayer', $Params)) {
                    $Wicketkipper = $this->findKeyValuePlayers($Records, "WicketKeeper");
                    $Batsman = $this->findKeyValuePlayers($Records, "Batsman");
                    $Bowler = $this->findKeyValuePlayers($Records, "Bowler");
                    $Allrounder = $this->findKeyValuePlayers($Records, "AllRounder");
                    usort($Batsman, function ($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                    usort($Bowler, function ($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                    usort($Wicketkipper, function ($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                    usort($Allrounder, function ($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                    $TopBatsman = array_slice($Batsman, 0, 4);
                    $TopBowler = array_slice($Bowler, 0, 3);
                    $TopWicketkipper = array_slice($Wicketkipper, 0, 1);
                    $TopAllrounder = array_slice($Allrounder, 0, 3);
                    $AllPlayers = array();
                    $AllPlayers = array_merge($TopBatsman, $TopBowler);
                    $AllPlayers = array_merge($AllPlayers, $TopAllrounder);
                    $AllPlayers = array_merge($AllPlayers, $TopWicketkipper);

                    rsort($AllPlayers, function($a, $b) {
                        return $b['TotalPoints'] - $a['TotalPoints'];
                    });
                }
                if (in_array($Record['PlayerID'], array_column($AllPlayers, 'PlayerID'))) {
                    $Records['TopPlayer'] = 'Yes';
                } else {
                    $Records['TopPlayer'] = 'No';
                }

                return $Record;
            }
        }
        return FALSE;
    }

}

?>