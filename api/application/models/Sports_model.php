<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Sports_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Settings_model');
        $this->load->model('AuctionDrafts_model');
        mongoDBConnection();
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
                'StatusID' => 'E.StatusID',
                'DraftUserLimit' => 'S.DraftUserLimit',
                'DraftTeamPlayerLimit' => 'S.DraftTeamPlayerLimit',
                'DraftPlayerSelectionCriteria' => 'S.DraftPlayerSelectionCriteria',
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
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('S.SeriesGUID,S.SeriesName');
        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_series S');
        $this->db->where("S.SeriesID", "E.EntityID", FALSE);

        if (!empty($Where['SeriesIDLive'])) {
            $this->db->like("S.SeriesIDLive", $Where['SeriesIDLive']);
        }
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
        if (!empty($Where['SeriesStartDate']) && empty($Where['SeriesEndDate'])) {
            $WhereOR = "(`S`.`SeriesStartDate` >= '" . $Where['SeriesStartDate'] . "' OR `S`.`SeriesEndDate` >= '" . $Where['SeriesStartDate'] . "')";
            $this->db->where($WhereOR);
        }
        if (!empty($Where['SeriesEndDate']) && empty($Where['SeriesStartDate'])) {
            $this->db->where("S.SeriesEndDate <=", $Where['SeriesEndDate']);
        }

        if (!empty($Where['SeriesEndDateRound'])) {
            $this->db->where("S.SeriesEndDate >=", $Where['SeriesEndDateRound']);
        }
        if (!empty($Where['SeriesStartDate']) && !empty($Where['SeriesEndDate'])) {
            $WhereOR = "(`S`.`SeriesStartDate` >= '" . $Where['SeriesStartDate'] . "' AND `S`.`SeriesEndDate` <= '" . $Where['SeriesEndDate'] . "')";
            $this->db->where($WhereOR);
        }

        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }

        /** open after code production mode * */
        if (!empty($Where['AuctionDraftStatusID'])) {
            $this->db->where("S.AuctionDraftStatusID", $Where['AuctionDraftStatusID']);
        }

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            if (!empty($Where['OrderByToday']) && $Where['OrderByToday'] == 'Yes') {
                $this->db->order_by('DATE(S.SeriesEndDate)="' . date('Y-m-d') . '" ASC', null, FALSE);
                $this->db->order_by('E.StatusID=2 DESC', null, FALSE);
            } else {
                $this->db->order_by('E.StatusID', 'ASC');
                $this->db->order_by('S.SeriesStartDate', 'DESC');
                $this->db->order_by('S.SeriesName', 'ASC');
            }
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
                $Return['Data']['Records'] = $Query->result_array();
                return $Return;
            } else {
                return $Query->row_array();
            }
        }
        return FALSE;
    }

    /*
      Description: Custom query set
     */

    public function customQuery($Sql, $Single = false, $UpdDelete = false, $NoReturn = false) {
        $Query = $this->db->query($Sql);
        if ($Single) {
            return $Query->row_array();
        } elseif ($UpdDelete) {
            return $this->db->affected_rows();
        } elseif (!$NoReturn) {
            return $Query->result_array();
        } else {
            return true;
        }
    }

    /*
      Description: Use to match type data.
     */

    function getMatchTypes($MatchTypeID = '') {
        $this->db->select('*');
        $this->db->from('sports_set_match_types');
        if ($MatchTypeID) {
            $this->db->where("MatchTypeID", $MatchTypeID);
        }
        // $this->db->cache_on(); //Turn caching on
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            return $Query->result_array();
        }
        return FALSE;
    }

    /*
      Description: To get all matches
     */

    function getMatches($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'SeriesID' => 'S.SeriesID',
                'SeriesGUID' => 'S.SeriesGUID',
                'StatusID' => 'E.StatusID',
                'SeriesIDLive' => 'S.SeriesIDLive',
                'CompetitionStateKey' => 'S.CompetitionStateKey',
                'SeriesName' => 'S.SeriesName',
                'SeriesStartDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesStartDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesStartDate',
                'SeriesEndDate' => 'DATE_FORMAT(CONVERT_TZ(S.SeriesEndDate,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") SeriesEndDate',
                'MatchID' => 'M.MatchID',
                'IsPlayingXINotificationSent' => 'M.IsPlayingXINotificationSent',
                'APIAutoTimeUpdate' => 'M.APIAutoTimeUpdate',
                'MatchIDLive' => 'M.MatchIDLive',
                'MatchTypeID' => 'M.MatchTypeID',
                'MatchNo' => 'M.MatchNo',
                'RoundID' => 'M.RoundID',
                'MatchLocation' => 'M.MatchLocation',
                'IsPreSquad' => 'M.IsPreSquad',
                'IsPlayerPointsUpdated' => 'M.IsPlayerPointsUpdated',
                'MatchScoreDetails' => 'M.MatchScoreDetails',
                'MatchStartDateTime' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . '") MatchStartDateTime',
                'CurrentDateTime' => 'DATE_FORMAT(CONVERT_TZ(Now(),"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . ' ") CurrentDateTime',
                'MatchDate' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "%Y-%m-%d") MatchDate',
                'MatchTime' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "%H:%i:%s") MatchTime',
                'MatchStartDateTimeUTC' => 'M.MatchStartDateTime as MatchStartDateTimeUTC',
                'ServerDateTimeUTC' => 'UTC_TIMESTAMP() as ServerDateTimeUTC',
                'TeamIDLocal' => 'TL.TeamID AS TeamIDLocal',
                'TeamIDVisitor' => 'TV.TeamID AS TeamIDVisitor',
                'TeamGUIDLocal' => 'TL.TeamGUID AS TeamGUIDLocal',
                'TeamGUIDVisitor' => 'TV.TeamGUID AS TeamGUIDVisitor',
                'TeamIDLiveLocal' => 'TL.TeamIDLive AS TeamIDLiveLocal',
                'TeamIDLiveVisitor' => 'TV.TeamIDLive AS TeamIDLiveVisitor',
                'TeamNameLocal' => 'TL.TeamName AS TeamNameLocal',
                'TeamNameVisitor' => 'TV.TeamName AS TeamNameVisitor',
                'TeamNameShortLocal' => 'TL.TeamNameShort AS TeamNameShortLocal',
                'TeamNameShortVisitor' => 'TV.TeamNameShort AS TeamNameShortVisitor',
                'TeamFlagLocal' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TL.TeamFlag) as TeamFlagLocal',
                'TeamFlagVisitor' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TV.TeamFlag) as TeamFlagVisitor',
                'TeamPlayersAvailable' => 'IF(
                                        (
                                        SELECT
                                            count(sports_team_players.PlayerID) as TPlayer
                                        FROM
                                            sports_team_players
                                        WHERE
                                            sports_team_players.MatchID = M.MatchID AND PlayerSalary > 0 HAVING TPlayer > 21
                                        LIMIT 1
                                    ) IS NOT NULL,
                                    "Yes",
                                    "No"
                                    ) AS TeamPlayersAvailable',
                'ContestAvailable' => 'IF(
                                        (
                                        SELECT
                                            count(sports_contest.ContestID) as ContestID
                                        FROM
                                            sports_contest
                                        WHERE
                                            sports_contest.MatchID = M.MatchID HAVING ContestID > 0
                                        LIMIT 1
                                    ) IS NOT NULL,
                                    "Yes",
                                    "No"
                                    ) AS ContestAvailable',
                'MyTotalJoinedContest' => '(SELECT COUNT(DISTINCT sports_contest_join.ContestID)
                                                FROM sports_contest_join
                                                WHERE sports_contest_join.MatchID =  M.MatchID AND UserID= ' . @$Where['UserID'] . ') AS MyTotalJoinedContest',
                'ContestsAvailable' => '(select count(C.ContestGUID) from sports_contest C where C.MatchID = M.MatchID AND E.StatusID=1) as ContestsAvailable',
                'Status' => 'CASE E.StatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "3" then "Cancelled"
                when "5" then "Completed"  
                when "8" then "Abandoned"  
                when "9" then "No Result" 
                when "10" then "Reviewing" 
                END as Status',
                'MatchType' => 'MT.MatchTypeName AS MatchType',
                'isReminderNotificationSent' => 'M.isReminderNotificationSent',
                'LastUpdateDiff' => 'IF(M.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", M.LastUpdatedOn))) LastUpdateDiff',
                'TotalUserWinning' => '(select SUM(UserWinningAmount) from sports_contest_join where MatchID = M.MatchID AND UserID=' . @$Where['UserID'] . ') as TotalUserWinning',
                'isJoinedContest' => '(select count(*) from sports_contest_join where MatchID = M.MatchID AND UserID = "' . @$Where['SessionUserID'] . '" AND E.StatusID=' . @$Where['StatusID'] . ') as JoinedContests'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('M.MatchGUID,TL.TeamName AS TeamNameLocal,TV.TeamName AS TeamNameVisitor,TL.TeamNameShort AS TeamNameShortLocal,TV.TeamNameShort AS TeamNameShortVisitor');
        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_series S, sports_matches M, sports_teams TL, sports_teams TV, sports_set_match_types MT');
        $this->db->where("M.SeriesID", "S.SeriesID", FALSE);
        $this->db->where("M.MatchID", "E.EntityID", FALSE);
        $this->db->where("M.MatchTypeID", "MT.MatchTypeID", FALSE);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
        if (!empty($Where['Keyword'])) {
            $this->db->group_start();
            $this->db->like("S.SeriesName", $Where['Keyword']);
            $this->db->or_like("M.MatchNo", $Where['Keyword']);
            $this->db->or_like("M.MatchLocation", $Where['Keyword']);
            $this->db->or_like("TL.TeamName", $Where['Keyword']);
            $this->db->or_like("TV.TeamName", $Where['Keyword']);
            $this->db->or_like("TL.TeamNameShort", $Where['Keyword']);
            $this->db->or_like("TV.TeamNameShort", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("S.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['SeriesEndDate'])) {
            $this->db->where("S.SeriesEndDate", $Where['SeriesEndDate']);
        }
        if (!empty($Where['MatchID'])) {
            $this->db->where("M.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['LivePlayerStatusUpdate'])) {
            $this->db->where("M.LivePlayerStatusUpdate", $Where['LivePlayerStatusUpdate']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("M.RoundID", $Where['RoundID']);
        }
        if (!empty($Where['MatchIDLive'])) {
            $this->db->where("M.MatchIDLive", $Where['MatchIDLive']);
        }
        if (!empty($Where['PlayerStatsUpdate'])) {
            $this->db->where("M.PlayerStatsUpdate", $Where['PlayerStatsUpdate']);
        }
        if (!empty($Where['MatchCompleteDateTime'])) {
            $this->db->where("M.MatchCompleteDateTime <", $Where['MatchCompleteDateTime']);
        }
        if (!empty($Where['MatchTypeID'])) {
            $this->db->where("M.MatchTypeID", $Where['MatchTypeID']);
        }
        if (!empty($Where['IsPlayingXINotificationSent'])) {
            $this->db->where("M.IsPlayingXINotificationSent", $Where['IsPlayingXINotificationSent']);
        }
        if (!empty($Where['TeamIDLocal'])) {
            $this->db->where("M.TeamIDLocal", $Where['TeamIDLocal']);
        }
        if (!empty($Where['IsPreSquad'])) {
            $this->db->where("M.IsPreSquad", $Where['IsPreSquad']);
        }
        if (!empty($Where['TeamIDVisitor'])) {
            $this->db->where("M.TeamIDVisitor", $Where['TeamIDVisitor']);
        }
        if (!empty($Where['IsPlayerPointsUpdated'])) {
            $this->db->where("M.IsPlayerPointsUpdated", $Where['IsPlayerPointsUpdated']);
        }
        if (!empty($Where['MatchStartDateTime'])) {
            $this->db->where("M.MatchStartDateTime <=", $Where['MatchStartDateTime']);
        }
        if (!empty($Where['MatchStartDateTimeComplete'])) {
            $this->db->where("M.MatchStartDateTime >=", $Where['MatchStartDateTimeComplete']);
        }
        if (!empty($Where['MatchStartDateTimeBetweenLive'])) {
            $MatchStartDateTimeBetween = $Where['MatchStartDateTimeBetweenLive'];
            $MatchStartDateTimeCurrent = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')));
            $this->db->where("M.MatchStartDateTime >=", $MatchStartDateTimeBetween);
            $this->db->where("M.MatchStartDateTime <=", $MatchStartDateTimeCurrent);
        }
        if (!empty($Where['isReminderNotificationSent'])) {
            $this->db->where("isReminderNotificationSent", $Where['isReminderNotificationSent']);
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Today') {
            // $this->db->where("DATE(M.MatchStartDateTime)", date('Y-m-d'));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'TodayMatch') {
            $this->db->where("DATE(M.MatchStartDateTime)", date('Y-m-d'));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'Yesterday') {
            $this->db->where("M.MatchStartDateTime <=", date('Y-m-d H:i:s'));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'AddDays') {
            $this->db->where_in("DATE(M.MatchStartDateTime)", array(date('Y-m-d', strtotime('+7 day', strtotime(date('Y-m-d')))),date('Y-m-d', strtotime('+6 day', strtotime(date('Y-m-d')))),date('Y-m-d', strtotime('+5 day', strtotime(date('Y-m-d')))),date('Y-m-d', strtotime('+4 day', strtotime(date('Y-m-d')))),date('Y-m-d', strtotime('+3 day', strtotime(date('Y-m-d')))), date('Y-m-d', strtotime('+2 day', strtotime(date('Y-m-d')))), date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d')))), date('Y-m-d')));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'YesterdayToday') {
            $this->db->where_in("DATE(M.MatchStartDateTime)", array(date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d')))), date('Y-m-d')));
        }
        if (!empty($Where['Filter']) && $Where['Filter'] == 'MyJoinedMatch') {
            $this->db->where('EXISTS (select 1 from sports_contest_join J where J.MatchID = M.MatchID AND J.UserID=' . $Where['UserID'] . ')');
        }
        if (!empty($Where['StatusID'])) {
            if ($Where['StatusID'] == 2) {
                $Where['StatusID'] = array(2, 10);
                $this->db->where_in("E.StatusID", $Where['StatusID']);
            } else if ($Where['StatusID'] == 5) {
                $Where['StatusID'] = array(5, 8, 3);
                $this->db->where_in("E.StatusID", $Where['StatusID']);
            } else {
                $this->db->where_in("E.StatusID", $Where['StatusID']);
            }
        }
        if (!empty($Where['CronFilter']) && $Where['CronFilter'] == 'OneDayDiff') {
            $this->db->having("LastUpdateDiff", 0);
            $this->db->or_having("LastUpdateDiff >=", 86400); // 1 Day
        }
        /* if (!empty($Where['existingContests'])) {
          $StatusID = $Where['StatusID'];
          print_r($StatusID);
          $this->db->where('EXISTS (select MatchID from sports_contest where MatchID = M.MatchID AND E.StatusID IN(2,10)');
          } */

        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            if (!empty($Where['OrderByToday']) && $Where['OrderByToday'] == 'Yes') {
                $this->db->order_by('E.StatusID=1 DESC', null, FALSE);
                $this->db->order_by('M.MatchStartDateTime', "ASC");
                /* $this->db->order_by('DATE(M.MatchStartDateTime)="' . date('Y-m-d') . '" ASC', null, FALSE);
                  $this->db->order_by('E.StatusID=1 DESC', null, FALSE); */
            } else {
                $this->db->order_by('E.StatusID', 'ASC');
                $this->db->order_by('M.MatchStartDateTime', 'ASC');
            }
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
        if ($multiRecords) {
            if ($Query->num_rows() > 0) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $Records[$key]['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                }
                $Return['Data']['Records'] = $Records;
            }
            if (!empty($Where['MyJoinedMatchesCount']) && $Where['MyJoinedMatchesCount'] == 1) {
                $Return['Data']['Statics'] = $this->db->query('SELECT (
                        SELECT COUNT(DISTINCT M.MatchID) AS `UpcomingJoinedContest` FROM `sports_matches` M
                            JOIN `sports_contest_join` J ON M.MatchID = J.MatchID JOIN `tbl_entity` E ON E.EntityID = M.MatchID WHERE E.StatusID = 1 AND J.UserID ="' . @$Where['UserID'] . '" 
                        )as UpcomingJoinedContest,
                        ( SELECT COUNT(DISTINCT M.MatchID) AS `LiveJoinedContest` FROM `sports_matches` M JOIN `sports_contest_join` J ON M.MatchID = J.MatchID JOIN `tbl_entity` E ON E.EntityID = M.MatchID WHERE  E.StatusID IN (2,10) AND J.UserID = "' . @$Where['UserID'] . '" 
                        )as LiveJoinedContest,
                        ( SELECT COUNT(DISTINCT J.MatchID) AS `CompletedJoinedContest` FROM `sports_contest_join` J, `tbl_entity` E, `sports_matches` M WHERE E.EntityID = M.MatchID AND J.MatchID=M.MatchID AND E.StatusID IN (5,10) AND J.UserID = "' . @$Where['UserID'] . '" 
                    )as CompletedJoinedContest'
                        )->row();
            }
            return $Return;
        } else {
            if ($Query->num_rows() > 0) {
                $Record = $Query->row_array();
                $Record['MatchScoreDetails'] = (!empty($Record['MatchScoreDetails'])) ? json_decode($Record['MatchScoreDetails'], TRUE) : new stdClass();
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description : To Update Match Time
     */

    function updateMatchTime($MatchID, $Input = array()) {
        $UpdateArray = array();

        if (!empty($Input['Time'])) {
            $CurrentTime = strtotime($Input['MatchStartDateTime']) + ($Input['Time'] * 60);
            $UpdateArray['MatchStartDateTime'] = date('Y-m-d H:i:s', $CurrentTime);
        }
        if (!empty($Input['APIAutoTimeUpdate'])) {
            $UpdateArray['APIAutoTimeUpdate'] = $Input['APIAutoTimeUpdate'];
        }

        if (!empty($UpdateArray)) {
            $this->db->where('MatchID', $MatchID);
            $this->db->limit(1);
            $this->db->update('sports_matches', $UpdateArray);
        }
        return TRUE;
    }

    /*
      Description: To get all teams
     */

    function getTeams($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));

            $Field = '';
            $FieldArray = array(
                'TeamID' => 'T.TeamID',
                'StatusID' => 'E.StatusID',
                'TeamIDLive' => 'T.TeamIDLive',
                'TeamName' => 'T.TeamName',
                'TeamNameShort' => 'T.TeamNameShort',
                'TeamFlag' => 'T.TeamFlag',
                'TeamFlag' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",T.TeamFlag) as TeamFlag',
                'Status' => 'CASE E.StatusID
                                when "2" then "Active"
                                when "6" then "Inactive"
                                END as Status',
            );

            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('DISTINCT(T.TeamGUID),T.TeamName');
        if (!empty($Field)) {
            $this->db->select($Field, FALSE);
        }
        $this->db->from('tbl_entity E, sports_teams T');
        if (!empty($Where['SeriesID'])) {
            $this->db->join('sports_matches M', "M.TeamIDLocal = T.TeamID", 'left');
            $this->db->join('sports_matches V', "V.TeamIDVisitor = T.TeamID", 'left');
            $this->db->where("M.SeriesID", $Where['SeriesID']);
        }
        $this->db->where("T.TeamID", "E.EntityID", FALSE);
        if (!empty($Where['Keyword'])) {
            $Where['Keyword'] = trim($Where['Keyword']);
            $this->db->group_start();
            $this->db->like("T.TeamName", $Where['Keyword']);
            $this->db->or_like("T.TeamNameShort", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['TeamID'])) {
            $this->db->where("T.TeamID", $Where['TeamID']);
        }
        if (!empty($Where['TeamIDLive'])) {
            $this->db->where("T.TeamIDLive", $Where['TeamIDLive']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        $this->db->order_by('T.TeamName', 'ASC');

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
        // echo $this->db->last_query(); die();
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                $key = 0;
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    if ($Where['SeriesName'] == 'Yes') {
                        $Query1 = $this->db->query('SELECT GROUP_CONCAT(DISTINCT S.SeriesName) as SeriesName
                            FROM `sports_matches` `M` INNER JOIN sports_series S ON S.SeriesID=M.SeriesID WHERE ' . (!empty($Where['SeriesID']) ? '`M`.`SeriesID` = ' . $Where['SeriesID'] : '(`M`.`TeamIDLocal` =' . $Records[$key]['TeamID'] . ' OR M.TeamIDVisitor = ' . $Records[$key]['TeamID'] . ')'))->result_array();
                        $Records[$key]['SeriesName'] = (isset($Query1[0]['SeriesName']) ? $Query1[0]['SeriesName'] : '');
                    }
                    $key++;
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                return $Query->row_array();
            }
        }
        return FALSE;
    }

    /*
      Description : To Update Team Flag
     */

    function updateTeamFlag($TeamID, $Input = array()) {
        $UpdateArray = array();
        if (!empty($Input['TeamFlag'])) {
            $UpdateArray['TeamFlag'] = $Input['TeamFlag'];
        }
        $UpdateArray['TeamName'] = $Input['TeamName'];
        if (!empty($UpdateArray)) {
            $this->db->where('TeamID', $TeamID);
            $this->db->limit(1);
            $this->db->update('sports_teams', $UpdateArray);
        }
        return TRUE;
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
                'SeriesGUID' => 'S.SeriesGUID',
                'TeamGUID' => 'T.TeamGUID',
                'TeamName' => 'T.TeamName',
                'TeamNameShort' => 'T.TeamNameShort',
                'TeamFlag' => 'T.TeamFlag',
                'PlayerID' => 'P.PlayerID',
                'PlayerIDLive' => 'P.PlayerIDLive',
                'PlayerRole' => 'TP.PlayerRole',
                'IsPlaying' => 'TP.IsPlaying',
                'IsActive' => 'TP.IsActive',
                'TotalPoints' => 'TP.TotalPoints',
                'PointsData' => 'TP.PointsData',
                'PointsDataSecondInng' => 'TP.PointsDataSecondInng',
                'SeriesID' => 'TP.SeriesID',
                'MatchID' => 'TP.MatchID',
                'TeamID' => 'TP.TeamID',
                'PlayerPic' => 'IF(P.PlayerPic IS NULL,CONCAT("' . BASE_URL . '","uploads/PlayerPic/","player.png"),CONCAT("' . BASE_URL . '","uploads/PlayerPic/",P.PlayerPic)) AS PlayerPic',
                'PlayerCountry' => 'P.PlayerCountry',
                'PlayerBattingStyle' => 'P.PlayerBattingStyle',
                'PlayerBowlingStyle' => 'P.PlayerBowlingStyle',
                'PlayerBattingStats' => 'P.PlayerBattingStats',
                'PlayerBowlingStats' => 'P.PlayerBowlingStats',
                'PlayerFieldingStats' => 'P.PlayerFieldingStats',
                'PlayerSalary' => 'FORMAT(TP.PlayerSalary,1) as PlayerSalary',
                'PlayerSalaryCredit' => 'FORMAT(TP.PlayerSalary,1) PlayerSalaryCredit',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
                'MatchTypeID' => 'SSM.MatchTypeID',
                'MatchType' => 'SSM.MatchTypeName as MatchType',
                'TotalPointCredits' => '(SELECT SUM(`TotalPoints`) FROM `sports_team_players` WHERE `PlayerID` = TP.PlayerID AND `RoundID` = TP.RoundID) TotalPointCredits',
                'RoundID' => 'TP.RoundID'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('DISTINCT(P.PlayerGUID),P.PlayerName');
        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P');
        if (array_keys_exist($Params, array('TeamGUID', 'TeamName', 'TeamNameShort', 'TeamFlag',
                    'PlayerRole', 'IsPlaying', 'TotalPoints', 'PointsData', 'SeriesID',
                    'MatchID'))) {
            $this->db->from('sports_teams T,sports_matches M, sports_team_players TP,sports_set_match_types SSM');
            $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
            $this->db->where("TP.TeamID", "T.TeamID", FALSE);
            $this->db->where("TP.MatchID", "M.MatchID", FALSE);
            $this->db->where("M.MatchTypeID", "SSM.MatchTypeID", FALSE);
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
        if (!empty($Where['IsActive'])) {
            $this->db->where("TP.IsActive", $Where['IsActive']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("TP.SeriesID", $Where['SeriesID']);
            //$this->db->group_by("P.PlayerGUID");
        }
        if (!empty($Where['PlayerGUID'])) {
            $this->db->where("P.PlayerGUID", $Where['PlayerGUID']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("TP.RoundID", $Where['RoundID']);
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
        } else if (!empty($Where['OrderFilter'])) {
            $this->db->order_by("IsPlaying", "ASC");
            $this->db->order_by("IsActive", "ASC");
        } else {
            $this->db->order_by('P.PlayerName', 'ASC');
        }
        if (!empty($Where['AdminView']) && $Where['AdminView'] == 'Yes') {
            $this->db->group_by('P.PlayerID');
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
        // echo $this->db->last_query();exit;
        $MatchStatus = 0;
        if (!empty($Where['MatchID'])) {
            /* Get Match Status */
            $MatchQuery = $this->db->query('SELECT E.StatusID FROM `sports_matches` `M`,`tbl_entity` `E` WHERE M.`MatchID` = "' . $Where['MatchID'] . '" AND M.MatchID = E.EntityID LIMIT 1');
            $MatchStatus = ($MatchQuery->num_rows() > 0) ? $MatchQuery->row()->StatusID : 0;
        }
        if ($Query->num_rows() > 0) {
            if (in_array('TopPlayer', $Params)) {
                $BestPlayers = $this->getMatchBestPlayers(array('MatchID' => $Where['MatchID']));
                if (!empty($BestPlayers)) {
                    $BestXIPlayers = array_column($BestPlayers['Data']['Records'], 'PlayerGUID');
                }
            }
            if ($multiRecords) {
                $Records = array();
                $PlayersRecords = $Query->result_array();
                $Return['Data']['Playing11Announce'] = "No";
                $IsPlaying = in_array("Yes", array_column($PlayersRecords, 'IsPlaying'));
                if ($IsPlaying) {
                    $Return['Data']['Playing11Announce'] = "Yes";
                }
                foreach ($PlayersRecords as $key => $Record) {

                    $Records[] = $Record;
                    $Records[$key]['TopPlayer'] = (in_array($Record['PlayerGUID'], $BestXIPlayers)) ? 'Yes' : 'No';
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PlayerFieldingStats'] = (!empty($Record['PlayerFieldingStats'])) ? json_decode($Record['PlayerFieldingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = $PointsData = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();

                    /* $Records[$key]['PointsDataSecondInng'] = $PointsDataSecondInng = (!empty($Record['PointsDataSecondInng'])) ? json_decode($Record['PointsDataSecondInng'], TRUE) : array();
                      if (!empty($PointsData) && !empty($PointsDataSecondInng)) {
                      foreach ($PointsData as $Key => $Value) {
                      if ($Value['PointsTypeGUID'] == "EveryRunScored") {
                      $Point = $this->searchForId($Value['PointsTypeGUID'], $PointsDataSecondInng);
                      if ($Point > 0) {
                      $PointsData[$Key]['CalculatedPoints'] += $Point;
                      }
                      }
                      }
                      } */

                    //$Records[$key]['PointsDataAll'] = $PointsData;
                    $TotalPointsRound = ($MatchStatus == 2 || $MatchStatus == 5) ? @$Record['TotalPoints'] : @$Record['TotalPointCredits'];
                    $Records[$key]['PointCredits'] = (!empty($TotalPointsRound)) ? $TotalPointsRound : '0.0';
                    if (in_array('MyTeamPlayer', $Params)) {
                        $this->db->select('DISTINCT(SUTP.PlayerID)');
                        $this->db->where("JC.UserTeamID", "SUTP.UserTeamID", FALSE);
                        $this->db->where("SUT.UserTeamID", "SUTP.UserTeamID", FALSE);
                        $this->db->where('SUT.MatchID', $Where['MatchID']);
                        $this->db->where('SUT.UserID', $Where['UserID']);
                        $this->db->from('sports_contest_join JC,sports_users_teams SUT,sports_users_team_players SUTP');
                        $MyPlayers = $this->db->get()->result_array();
                        $MyPlayersIds = (!empty($MyPlayers)) ? array_column($MyPlayers, 'PlayerID') : array();
                        $Records[$key]['MyPlayer'] = (in_array($Record['PlayerID'], $MyPlayersIds)) ? 'Yes' : 'No';
                    }

                    if (in_array('PlayerSelectedPercent', $Params)) {
                        $TotalTeams = $this->db->query('Select count(*) as TotalTeams from sports_users_teams WHERE MatchID="' . $Where['MatchID'] . '"')->row()->TotalTeams;

                        $this->db->select('count(SUTP.PlayerID) as TotalPlayer');
                        $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                        $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                        $this->db->where("SUTP.MatchID", $Where['MatchID']);
                        $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                        $Players = $this->db->get()->row();
                        $Records[$key]['PlayerSelectedPercent'] = ($TotalTeams > 0 ) ? round((($Players->TotalPlayer * 100 ) / $TotalTeams), 2) > 100 ? 100 : round((($Players->TotalPlayer * 100 ) / $TotalTeams), 2) : 0;
                    }
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Records[$key]['TopPlayer'] = (in_array($Record['PlayerGUID'], $BestXIPlayers)) ? 'Yes' : 'No';
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PlayerFieldingStats'] = (!empty($Record['PlayerFieldingStats'])) ? json_decode($Record['PlayerFieldingStats']) : new stdClass();
                $Record['PointsData'] = $PointsData = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();

                /* $Records[$key]['PointsDataSecondInng'] = $PointsDataSecondInng = (!empty($Record['PointsDataSecondInng'])) ? json_decode($Record['PointsDataSecondInng'], TRUE) : array();
                  if (!empty($PointsData) && !empty($PointsDataSecondInng)) {
                  foreach ($PointsData as $Key => $Value) {
                  if ($Value['PointsTypeGUID'] == "EveryRunScored") {
                  $Point = $this->searchForId($Value['PointsTypeGUID'], $PointsDataSecondInng);
                  if ($Point > 0) {
                  $PointsData[$Key]['CalculatedPoints'] += $Point;
                  }
                  }
                  }
                  } */

                $Record['PlayerSalary'] = ($Record['PlayerSalary'] == 0 ? "11.0" : $Record['PlayerSalary']);
                $TotalPointsRound = ($MatchStatus == 2 || $MatchStatus == 5) ? @$Record['TotalPoints'] : @$Record['TotalPointCredits'];
                $Record['PointCredits'] = $TotalPointsRound;
                if (in_array('MyTeamPlayer', $Params)) {
                    $this->db->select('DISTINCT(SUTP.PlayerID)');
                    $this->db->where("JC.UserTeamID", "SUTP.UserTeamID", FALSE);
                    $this->db->where("SUT.UserTeamID", "SUTP.UserTeamID", FALSE);
                    $this->db->where('SUT.MatchID', $Where['MatchID']);
                    $this->db->where('SUT.UserID', $Where['UserID']);
                    $this->db->from('sports_contest_join JC,sports_users_teams SUT,sports_users_team_players SUTP');
                    $MyPlayers = $this->db->get()->result_array();
                    $MyPlayersIds = (!empty($MyPlayers)) ? array_column($MyPlayers, 'PlayerID') : array();
                    $Record['MyPlayer'] = (in_array($Record['PlayerID'], $MyPlayersIds)) ? 'Yes' : 'No';
                }

                return $Record;
            }
        }
        return FALSE;
    }

    function searchForId($id, $array) {
        foreach ($array as $key => $val) {
            if ($val['PointsTypeGUID'] === $id) {
                return $val['CalculatedPoints'];
            }
        }
        return null;
    }

    /*
      Description: Use to get sports points for app.
     */

    function getPointsApp($Where = array()) {
        switch (@$Where['PointsCategory']) {
            case 'InPlay':
                $this->db->select('PointsT20InPlay PointsT20, PointsODIInPlay PointsODI, PointsTESTInPlay PointsTEST');
                break;
            case 'Reverse':
                $this->db->select('PointsT20Reverse PointsT20, PointsODIReverse PointsODI, PointsTESTReverse PointsTEST');
                break;
            default:
                /* $this->db->select('PointsT20,PointsODI,PointsTEST'); */
                $this->db->select($Where['Type']);
                break;
        }

        $this->db->select('PointsTypeGUID,PointsTypeDescprition,PointsTypeShortDescription,PointsType,PointsInningType,PointsScoringField,StatusID');
        $this->db->from('sports_setting_points');

        if (!empty($Where['StatusID'])) {
            $this->db->where("StatusID", $Where['StatusID']);
        }

        $this->db->order_by("Sort", 'ASC');
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();
        // $this->db->cache_on();
        $Query = $this->db->get();
        $battingArray = array();
        $bowlingArray = array();
        $fieldingArray = array();

        if ($Query->num_rows() > 0) {
            //$Return['Data']['Records'] = $Query->result_array();
            foreach ($Query->result_array() as $value) {
                if ($value['PointsInningType'] == 'Batting') {
                    $battingArray[] = $value;
                }
                if ($value['PointsInningType'] == 'Bowling') {
                    $bowlingArray[] = $value;
                }
                if ($value['PointsInningType'] == 'Fielding') {
                    $fieldingArray[] = $value;
                }
            }
            $Return['Data']['Batting'] = $battingArray;
            $Return['Data']['Bowling'] = $bowlingArray;
            $Return['Data']['Fielding'] = $fieldingArray;

            return $Return;
        }
        return FALSE;
    }

    /*
      Description: Use to get sports points.
     */

    function getPoints($Where = array()) {
        switch (@$Where['PointsCategory']) {
            case 'InPlay':
                $this->db->select('PointsT20InPlay PointsT20, PointsODIInPlay PointsODI, PointsTESTInPlay PointsTEST');
                break;
            case 'Reverse':
                $this->db->select('PointsT20Reverse PointsT20, PointsODIReverse PointsODI, PointsTESTReverse PointsTEST');
                break;
            default:
                $this->db->select('PointsT20,PointsODI,PointsTEST');
                break;
        }
        $this->db->select('PointsTypeGUID,PointsTypeDescprition,PointsTypeShortDescription,PointsType,PointsInningType,PointsScoringField,StatusID');
        $this->db->from('sports_setting_points');
        if (!empty($Where['StatusID'])) {
            $this->db->where("StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by("PointsType", 'ASC');
        }
        $TempOBJ = clone $this->db;
        $TempQ = $TempOBJ->get();
        $Return['Data']['TotalRecords'] = $TempQ->num_rows();
        // $this->db->cache_on();
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Return['Data']['Records'] = $Query->result_array();
            return $Return;
        }
        return FALSE;
    }

    /*
      Description: Use to update points.
     */

    function updatePoints($Input = array()) {
        if (!empty($Input)) {
            $PointsCategory = ($Input['PointsCategory'] != 'Normal') ? $Input['PointsCategory'] : '';
            for ($i = 0; $i < count($Input['PointsT20']); $i++) {
                $updateArray[] = array(
                    'PointsTypeGUID' => $Input['PointsTypeGUID'][$i],
                    'PointsT20' . $PointsCategory => $Input['PointsT20'][$i],
                    'PointsTEST' . $PointsCategory => $Input['PointsTEST'][$i],
                    'PointsODI' . $PointsCategory => $Input['PointsODI'][$i]
                );
            }
            /* Update points details to sports_setting_points table. */
            $this->db->update_batch('sports_setting_points', $updateArray, 'PointsTypeGUID');
            /* Clear Cache */
            $this->cache->memcached->delete('CricketPoints');
        }
    }

    /*
      Description: Use to update player role.
     */

    function updatePlayerRole($PlayerID, $MatchID, $Input = array()) {
        if (!empty($Input)) {
            $this->db->where('PlayerID', $PlayerID);
            $this->db->where('MatchID', $MatchID);
            $this->db->limit(1);
            $this->db->update('sports_team_players', $Input);
        }
    }

    function updatePlayerRoleAuctionDraft($PlayerID, $RoundID, $Input = array()) {
        if (!empty($Input)) {
            $this->db->where('PlayerID', $PlayerID);
            $this->db->where('RoundID', $RoundID);
            $this->db->limit(1);
            $this->db->update('sports_auction_draft_series_players', $Input);
        }
    }

    /*
      Description: Use to auction draft play.
     */

    function updateAuctionPlayStatus($SeriesID, $Input = array()) {
        if (!empty($Input)) {
            $this->db->where('SeriesID', $SeriesID);
            $this->db->limit(1);
            $this->db->update('sports_series', $Input);
        }
    }

    /*
      Description: Use to update player salary.
     */

    function updatePlayerSalary($Input = array(), $PlayerID) {
        if (!empty($Input)) {
            $UpdateData = array(
                'PlayerSalary' => json_encode(array(
                    'T20Credits' => @$Input['T20Credits'],
                    'T20iCredits' => @$Input['T20iCredits'],
                    'ODICredits' => @$Input['ODICredits'],
                    'TestCredits' => @$Input['TestCredits']
                )),
                'IsAdminSalaryUpdated' => 'Yes'
            );

            $this->db->where('PlayerID', $PlayerID);
            $this->db->limit(1);
            $this->db->update('sports_players', $UpdateData);
            // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
            // $this->db->cache_delete('admin', 'matches'); //Delete Cache
        }
    }

    function updatePlayerSalaryMatch($Input = array(), $PlayerID, $MatchID) {
        if (!empty($Input)) {
            $UpdateData = array(
                'PlayerSalary' => $Input['PlayerSalaryCredit'],
                'IsAdminUpdate' => 'Yes'
            );
            $this->db->where('PlayerID', $PlayerID);
            $this->db->where('MatchID', $MatchID);
            $this->db->limit(1);
            $this->db->update('sports_team_players', $UpdateData);
        }
    }

    /*
      Description: To Excecute curl request
     */

    function ExecuteCurl($Url, $Params = '') {
        $Curl = curl_init($Url);
        if (!empty($Params)) {
            curl_setopt($Curl, CURLOPT_POSTFIELDS, $Params);
        }
        curl_setopt($Curl, CURLOPT_HEADER, 0);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, TRUE);
        $Response = curl_exec($Curl);
        curl_close($Curl);
        $Result = json_decode($Response);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $Response;
        } else {
            return gzdecode($Response);
        }
    }

    function callSportsAPICredit($ApiUrl) {
        $AccessToken = $this->generateAccessToken();
        $Response = json_decode($this->ExecuteCurl($ApiUrl . $AccessToken), TRUE);
        return $Response;
    }

    /*
      Description: To get access token
     */

    function getAccessToken() {
        $this->load->helper('file');
        $AccessToken = "";
        if (file_exists(SPORTS_FILE_PATH)) {
            $AccessToken = read_file(SPORTS_FILE_PATH);
        } else {
            $AccessToken = $this->generateAccessToken();
        }
        return trim(preg_replace("/\r|\n/", "", $AccessToken));
    }

    /*
      Description: To generate access token
     */

    function generateAccessToken() {
        /* For Sports Entity Api */
        if (SPORTS_API_NAME == 'ENTITY') {
            $Response = json_decode($this->ExecuteCurl(SPORTS_API_URL_ENTITY . '/v2/auth/', array('access_key' => SPORTS_API_ACCESS_KEY_ENTITY, 'secret_key' => SPORTS_API_SECRET_KEY_ENTITY,
                        'extend' => 1)), TRUE);
            if ($Response['status'] == 'ok') $AccessToken = $Response['response']['token'];
        }

        /* For Sports Cricket Api */
        if (SPORTS_API_NAME == 'CRICKETAPI') {
            $Response = json_decode($this->ExecuteCurl(SPORTS_API_URL_CRICKETAPI . '/rest/v2/auth/', array('access_key' => SPORTS_API_ACCESS_KEY_CRICKETAPI,
                        'secret_key' => SPORTS_API_SECRET_KEY_CRICKETAPI, 'app_id' => SPORTS_API_APP_ID_CRICKETAPI,
                        'device_id' => SPORTS_API_DEVICE_ID_CRICKETAPI)), TRUE);
            if ($Response['status']) $AccessToken = $Response['auth']['access_token'];
        }
        if (empty($AccessToken)) exit;

        /* Update Access Token */
        $this->load->helper('file');
        write_file(SPORTS_FILE_PATH, $AccessToken, 'w');
        return trim(preg_replace("/\r|\n/", "", $AccessToken));
    }

    /*
      Description: To fetch sports api data
     */

    function callSportsAPI($ApiUrl) {
        $AccessToken = $this->generateAccessToken();
        $Response = json_decode($this->ExecuteCurl($ApiUrl.$AccessToken), TRUE);
        return $Response;
    }

    /*
     * 
     * 
      Description: ENTITY API
     * 
     * 
     */

    /*
      Description: To set series data (Entity API)
     */

    function getSeriesLiveEntity($CronID) {
        ini_set('max_execution_time', 120);
        $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/seasons/?token=');
        if (empty($Response['response']['items'])) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            return true;
        }
        $SeriesData = array();
        foreach ($Response['response']['items'] as $Value) {
            if (!in_array($Value['sid'], array(date('Y'), date('Y') . date('y') + 1, date('Y') - 1 . date('y')))) continue;

            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/' . $Value['competitions_url'] . '?per_page=500&token=');
            /* To get All Series Data */

            foreach ($Response['response']['items'] as $Value) {
                $SeriesIdsData = $this->db->query('SELECT SeriesIDLive FROM sports_series WHERE SeriesIDLive=' . $Value['cid'] . ' ')->row()->SeriesIDLive;
                if (!empty($SeriesIdsData)) {
                    continue;
                }
                if ($Value['dateend'] < date('Y-m-d')) {
                    continue;
                }
                /* Add series to entity table and get EntityID. */
                $SeriesGUID = get_guid();
                $SeriesData[] = array_filter(array(
                    'SeriesID' => $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2)),
                    'SeriesGUID' => $SeriesGUID,
                    'SeriesIDLive' => $Value['cid'],
                    'SeriesName' => $Value['title'],
                    'SeriesStartDate' => $Value['datestart'],
                    'SeriesEndDate' => $Value['dateend']
                ));
            }
        }
        if (!empty($SeriesData)) {
            $this->db->insert_batch('sports_series', $SeriesData);
        }
    }

    /*
      Description: To set rounds data (Entity API)
     */

    function getSeriesLiveEntityRounds($CronID) {
        ini_set('max_execution_time', 120);
        $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/seasons/?token=');
        if (empty($Response['response']['items'])) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            return true;
        }
        /** add update series rounds list * */
        foreach ($Response['response']['items'] as $Rows) {
            if (!in_array($Rows['sid'], array(date('Y'), date('Y') . date('y') + 1, date('Y') - 1 . date('y')))) continue;

            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/' . $Rows['competitions_url'] . '?per_page=500&token=');

            foreach ($Response['response']['items'] as $Value) {
                //if (strtotime(date('Y-m-d')) <= strtotime($Value['dateend'])) {
                $TotalRounds = $Value['total_rounds'];
                $SeriesRounds = $Value['rounds'];
                $SeriesType = strtolower($Value['type']);
                $SeriesFormat = $Value['match_format'];
                $SeriesStatus = strtolower($Value['status']);
                if ($SeriesStatus != "result") {
                    $SeriesID = $this->db->query('SELECT SeriesID FROM sports_series WHERE SeriesIDLive =' . $Value['cid'] . ' ')->row()->SeriesID;
                    if ($SeriesID) {
                        if ($TotalRounds > 0) {
                            foreach ($SeriesRounds as $Rounds) {
                                if ($SeriesType == "tour") {
                                    $RoundID = $this->db->query('SELECT RoundID FROM sports_series_rounds WHERE RoundIDLive =' . $Rounds['rid'] . ' ')->row()->RoundID;
                                    if (empty($RoundID)) {
                                        $Status = 1;
                                        if ((strtotime($Rounds['datestart']) > strtotime(date('Y-m-d')))) {
                                            $Status = 1;
                                        } else if ((strtotime($Rounds['datestart']) <= strtotime(date('Y-m-d'))) && (strtotime($Rounds['dateend']) > strtotime(date('Y-m-d')))) {
                                            //$Status = 2;
                                        } else {
                                            //$Status = 5;
                                        }
                                        $SeriesRoundsList = array(
                                            'SeriesID' => $SeriesID,
                                            'RoundIDLive' => $Rounds['rid'],
                                            'RoundName' => $Rounds['name'],
                                            'SeriesType' => ucfirst($SeriesType),
                                            'RoundStartDate' => $Rounds['datestart'],
                                            'RoundEndDate' => $Rounds['dateend'],
                                            'RoundFormat' => ucfirst($Rounds['match_format']),
                                            'StatusID' => ($SeriesStatus != "result") ? 2 : 5,
                                            'AuctionDraftStatusID' => $Status
                                        );
                                        $this->db->insert('sports_series_rounds', $SeriesRoundsList);
                                    }
                                } else if ($SeriesType == "tournament") {
                                    $RoundID = $this->db->query('SELECT RoundID FROM sports_series_rounds WHERE RoundIDLive =' . $Value['cid'] . ' ')->row()->RoundID;
                                    if (empty($RoundID)) {
                                        $SeriesRoundsList = array(
                                            'SeriesID' => $SeriesID,
                                            'RoundIDLive' => $Value['cid'],
                                            'RoundName' => $Value['title'],
                                            'SeriesType' => ucfirst($SeriesType),
                                            'RoundStartDate' => $Value['datestart'],
                                            'RoundEndDate' => $Value['dateend'],
                                            'RoundFormat' => ucfirst($Value['match_format']),
                                            'StatusID' => ($SeriesStatus != "result") ? 2 : 5,
                                            'AuctionDraftStatusID' => ($SeriesStatus != "result") ? ($SeriesStatus != "live") ? 1 : 1 : 5
                                        );
                                        $this->db->insert('sports_series_rounds', $SeriesRoundsList);
                                    }
                                }
                            }
                        } else {
                            $RoundID = $this->db->query('SELECT RoundID FROM sports_series_rounds WHERE RoundIDLive =' . $Value['cid'] . ' ')->row()->RoundID;
                            if (empty($RoundID)) {
                                $SeriesRoundsList = array(
                                    'SeriesID' => $SeriesID,
                                    'RoundIDLive' => $Value['cid'],
                                    'RoundName' => $Value['title'],
                                    'SeriesType' => ucfirst($SeriesType),
                                    'RoundStartDate' => $Value['datestart'],
                                    'RoundEndDate' => $Value['dateend'],
                                    'RoundFormat' => ucfirst($Value['match_format']),
                                    'StatusID' => ($SeriesStatus != "result") ? 2 : 5,
                                    'AuctionDraftStatusID' => ($SeriesStatus != "result") ? ($SeriesStatus != "live") ? 1 : 1 : 5
                                );
                                $this->db->insert('sports_series_rounds', $SeriesRoundsList);
                            }
                        }
                    }
                }
                // }
            }
        }
    }

    /*
      Description: To set series and rounds live or complete
     */

    function updateSeriesANDRoundsStatusByMatchEntity() {

        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID,SeriesType,AuctionDraftIsPlayed,SeriesStartDateUTC,SeriesEndDateUTC', array('StatusID' => 2), true, 0);
        //$SeriesData = $this->getSeries('SeriesIDLive,SeriesID,SeriesType,AuctionDraftIsPlayed,SeriesStartDateUTC,SeriesEndDateUTC', array('SeriesID' => 17893), true, 0);
        foreach ($SeriesData['Data']['Records'] as $Value) {
            $RoundsData = $this->getRounds('RoundID,SeriesIDLive,SeriesID,SeriesType,RoundFormat,AuctionDraftStatusID,SeriesStartDateUTC,SeriesEndDateUTC', array('SeriesID' => $Value['SeriesID'], 'NoInStatusID' => 5, 'OrderBy' => 'RoundStartDate', 'Sequence' => 'ASC'), true, 0);
            $TotalRecords = $RoundsData['Data']['TotalRecords'];
            foreach ($RoundsData['Data']['Records'] as $Rows) {
                if ($Rows['SeriesType'] == "Tournament") {

                    $MatchStartDateTime = $this->db->query('SELECT MatchStartDateTime as MatchStartDateTime FROM sports_matches
                                WHERE sports_matches.RoundID =  ' . $Rows['RoundID'] . ' order by MatchStartDateTime asc limit 1')->row()->MatchStartDateTime;
                    $CurrentDateTime = strtotime(date('Y-m-d H:i'));
                    if ($CurrentDateTime >= strtotime($MatchStartDateTime)) {
                        /** series * */
                        $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                        /** rounds * */
                        $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '" ');
                    }
                    $Query = $this->db->query("SELECT M.MatchID,M.MatchStartDateTime FROM sports_matches M,tbl_entity E
                                WHERE E.EntityID=M.MatchID AND E.StatusID=5 AND DATE(M.MatchStartDateTime) = '" . $Rows['SeriesEndDateUTC'] . "' AND  M.RoundID ='" . $Rows['RoundID'] . "' order by M.MatchStartDateTime desc limit 1");
                    if ($Query->num_rows() > 0) {
                        $MatchResult = $Query->row_array();
                        $MatchStartTime = strtotime($MatchResult['MatchStartDateTime']);
                        if (strtotime(date('Y-m-d H:i:s')) > $MatchStartTime) {
                            /** rounds * */
                            $this->db->query('UPDATE sports_series_rounds AS S SET S.StatusID = 5 WHERE S.StatusID != 5 AND  SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                            $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                        }
                    }
                    if (strtotime(date('Y-m-d')) > strtotime($Value['SeriesEndDateUTC'])) {
                        /** series * */
                        $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                        $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesID = "' . $Value['SeriesID'] . '"');
                    }
                } else if ($Rows['SeriesType'] == "Tour") {
                    $MatchStartDateTime = $this->db->query('SELECT MatchStartDateTime as MatchStartDateTime FROM sports_matches
                                WHERE sports_matches.RoundID =  ' . $Rows['RoundID'] . ' order by MatchStartDateTime asc limit 1')->row()->MatchStartDateTime;
                    $CurrentDateTime = strtotime(date('Y-m-d H:i:s'));
                    if ($CurrentDateTime >= strtotime($MatchStartDateTime)) {
                        /** series * */
                        $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                        /** rounds * */
                        $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '" ');
                    }
                    $Query = $this->db->query("SELECT M.MatchID,M.MatchStartDateTime FROM sports_matches M,tbl_entity E
                                WHERE E.EntityID=M.MatchID AND E.StatusID=5 AND DATE(M.MatchStartDateTime) = '" . $Rows['SeriesEndDateUTC'] . "' AND  M.RoundID ='" . $Rows['RoundID'] . "' order by M.MatchStartDateTime desc limit 1");
                    if ($Query->num_rows() > 0) {
                        $MatchResult = $Query->row_array();
                        $MatchStartTime = strtotime($MatchResult['MatchStartDateTime']);
                        if (strtotime(date('Y-m-d H:i:s')) > $MatchStartTime) {
                            /** rounds * */
                            $this->db->query('UPDATE sports_series_rounds AS S SET S.StatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                            $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                        }
                    }
                    /* if (strtotime(date('Y-m-d')) > strtotime($Rows['SeriesEndDateUTC'])) {

                      $this->db->query('UPDATE sports_series_rounds AS S SET S.StatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                      $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                      } */
                    if (strtotime(date('Y-m-d')) > strtotime($Rows['SeriesEndDateUTC']) && $TotalRecords == 1) {
                        /** series * */
                        $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                        $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesID = "' . $Value['SeriesID'] . '"');
                    }
                }
            }
        }
    }

    /*
      Description: To set matches data (Entity API)
     */

    function getMatchesLiveEntity($CronID) {
        ini_set('max_execution_time', 120);

        /* Get series data */
        /* $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2, 'SeriesEndDate' => date('Y-m-d')), true, 0); */
        $SeriesData = $this->getRounds('RoundID,SeriesIDLive,SeriesID,SeriesType,RoundFormat', array('NoInStatusID' => 5), true, 0);
        //$SeriesData = $this->getRounds('RoundID,SeriesIDLive,SeriesID,SeriesType,RoundFormat', array('RoundID' => 188), true, 0);
        if (!$SeriesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        /* To get All Match Types */
        $MatchTypesData = $this->getMatchTypes();
        $MatchTypeIdsData = array_column($MatchTypesData, 'MatchTypeID', 'MatchTypeName');
        $AllResponse = array();
        /* Get Live Matches Data */
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {
            if (strtolower($SeriesValue['SeriesType']) == "tournament") {
                $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/competitions/' . $SeriesValue['SeriesIDLive'] . '/matches/?per_page=150&token=');
                if (empty($Response['response']['items'])) continue;

                $AllResponse = $Response['response']['items'];
            } else {
                $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/rounds/' . $SeriesValue['SeriesIDLive'] . '/matches/?per_page=150&token=');
                if (empty($Response['response']['matches'])) continue;

                $AllResponse = $Response['response']['matches'];
            }

            foreach ($AllResponse as $key => $Value) {

                /* $this->db->trans_start(); */

                /* Managae Teams */
                $PreSquad = $Value['pre_squad'];
                $Verified = $Value['verified'];
                $LocalTeam = $Value['teama'];
                $VisitorTeam = $Value['teamb'];
                $LocalTeamData = $VisitorTeamData = array();

                if ($VisitorTeam['name'] == "TBA") continue;

                /* To check if local team is already exist */
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = ' . $LocalTeam['team_id'] . ' LIMIT 1');
                $TeamIDLocal = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamIDLocal) {

                    /* Add team to entity table and get EntityID. */
                    $TeamGUID = get_guid();
                    $TeamIDLocal = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $LocalTeamData[] = array(
                        'TeamID' => $TeamIDLocal,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $LocalTeam['team_id'],
                        'TeamName' => $LocalTeam['name'],
                        'TeamNameShort' => (!empty($LocalTeam['short_name'])) ? $LocalTeam['short_name'] : null,
                        'TeamFlag' => (!empty($LocalTeam['logo_url'])) ? $LocalTeam['logo_url'] : null
                    );
                }

                /* To check if visitor team is already exist */
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = ' . $VisitorTeam['team_id'] . ' LIMIT 1');
                $TeamIDVisitor = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamIDVisitor) {

                    /* Add team to entity table and get EntityID. */
                    $TeamGUID = get_guid();
                    $TeamIDVisitor = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $VisitorTeamData[] = array(
                        'TeamID' => $TeamIDVisitor,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $VisitorTeam['team_id'],
                        'TeamName' => $VisitorTeam['name'],
                        'TeamNameShort' => (!empty($VisitorTeam['short_name'])) ? $VisitorTeam['short_name'] : null,
                        'TeamFlag' => (!empty($VisitorTeam['logo_url'])) ? $VisitorTeam['logo_url'] : null
                    );
                }
                $TeamsData = array_merge($VisitorTeamData, $LocalTeamData);
                if (!empty($TeamsData)) {
                    $this->db->insert_batch('sports_teams', $TeamsData);
                }

                /* To check if match is already exist */
                $Query = $this->db->query('SELECT M.MatchID,E.StatusID FROM sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = ' . $Value['match_id'] . ' LIMIT 1');
                $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                if (!$MatchID) {
                    if (strtotime(date('Y-m-d H:i:s')) >= strtotime(date('Y-m-d H:i', strtotime($Value['date_start'])))) {
                        continue;
                    }
                    /* Add matches to entity table and get EntityID. */
                    $MatchGUID = get_guid();
                    $MatchesAPIData = array(
                        'MatchID' => $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => 1)),
                        'MatchGUID' => $MatchGUID,
                        'MatchIDLive' => $Value['match_id'],
                        'SeriesID' => $SeriesValue['SeriesID'],
                        'RoundID' => $SeriesValue['RoundID'],
                        'MatchTypeID' => $MatchTypeIdsData[$Value['format_str']],
                        'MatchNo' => $Value['subtitle'],
                        'MatchLocation' => $Value['venue']['location'],
                        'TeamIDLocal' => $TeamIDLocal,
                        'TeamIDVisitor' => $TeamIDVisitor,
                        'MatchStartDateTime' => date('Y-m-d H:i', strtotime($Value['date_start']))
                    );
                    if ($PreSquad == "true") {
                        $MatchesAPIData["IsPreSquad"] = "Yes";
                        $this->getMatchWisePlayersLiveEntity(null, $MatchID);
                    } else {
                        $MatchesAPIData["IsPreSquad"] = "No";
                    }
                    $this->db->insert('sports_matches', $MatchesAPIData);
                } else {

                    if ($Query->row()->StatusID != 1) continue; // Pending Match

                        /* Update Match Data */
                    $MatchesAPIData = array(
                        'RoundID' => $SeriesValue['RoundID'],
                        'MatchNo' => $Value['subtitle'],
                        'MatchLocation' => $Value['venue']['location'],
                        'TeamIDLocal' => $TeamIDLocal,
                        'TeamIDVisitor' => $TeamIDVisitor,
                        'MatchStartDateTime' => date('Y-m-d H:i', strtotime($Value['date_start'])),
                        'LastUpdatedOn' => date('Y-m-d H:i:s')
                    );
                    if ($PreSquad == "true") {
                        $MatchesAPIData["IsPreSquad"] = "Yes";
                    } else {
                        $MatchesAPIData["IsPreSquad"] = "No";
                    }
                    $this->db->where('MatchID', $MatchID);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', $MatchesAPIData);
                    if ($PreSquad == "true") {
                        $this->getMatchWisePlayersLiveEntity(null, $MatchID);
                    }
                }

                /* $this->db->trans_complete();
                  if ($this->db->trans_status() === false) {
                  return false;
                  } */
            }
        }
    }

    /*
      Description: To set players data match wise (Entity API)
     */

    function getMatchWisePlayersLiveEntity($CronID, $MatchID = "") {
        ini_set('max_execution_time', 300);

        /* Get series data */
        if (!empty($MatchID)) {
            $MatchData = $this->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID,RoundID,IsPreSquad', array('StatusID' => array(1), "MatchID" => $MatchID), true, 0);
        } else {
            $MatchData = $this->getMatches('RoundID,MatchStartDateTime,MatchIDLive,MatchID,MatchType,SeriesIDLive,SeriesID,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff,IsPreSquad', array('StatusID' => array(1), 'Filter' => 'Upcoming'), true, 1, 250);
        }
        if (!$MatchData) {
            if (!empty($CronID)) {
                $this->db->where('CronID', $CronID);
                $this->db->limit(1);
                $this->db->update('log_cron', array('CronStatus' => 'Exit'));
                return true;
            }
        }
        /* Player Roles */
        $PlayerRolesArr = array('bowl' => 'Bowler', 'bat' => 'Batsman', 'wkbat' => 'WicketKeeper', 'wk' => 'WicketKeeper', 'all' => 'AllRounder');
        foreach ($MatchData['Data']['Records'] as $Value) {
            $TeamPlayersDataAuction = array();
            $MatchID = $Value['MatchID'];
            $SeriesID = $Value['SeriesID'];
            $RoundID = $Value['RoundID'];
            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/competitions/' . $Value['SeriesIDLive'] . '/squads/' . $Value['MatchIDLive'] . '?token=');
            //print_r(json_encode($Response));exit;
            if (empty($Response['response']['squads'])) continue;

            /* To check if any team is created */
            $TotalJoinedTeams = $this->db->query("SELECT COUNT(*) TotalJoinedTeams FROM `sports_users_teams` WHERE `MatchID` = " . $MatchID)->row()->TotalJoinedTeams;

            //print_r($Response['response']['squads']);exit;
            foreach ($Response['response']['squads'] as $SquadsValue) {
                $TeamID = $SquadsValue['team_id'];
                $Players = $SquadsValue['players'];
                $TeamPlayersData = array();
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = "' . $TeamID . '" LIMIT 1');
                $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (empty($TeamID)) {
                    /* Add team to entity table and get EntityID. */
                    $TeamDetails = $SquadsValue['team'];
                    $TeamGUID = get_guid();
                    $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $TeamData = array_filter(array(
                        'TeamID' => $TeamID,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $SquadsValue['team_id'],
                        'TeamName' => $TeamDetails['title'],
                        'TeamNameShort' => strtoupper($TeamDetails['abbr'])
                    ));
                    $this->db->insert('sports_teams', $TeamData);
                }
                //echo $TeamID;exit;
                $this->db->trans_start();
                if (!empty($Players)) {
                    foreach ($Players as $Player) {
                        if (isset($Player['pid']) && !empty($Player['pid'])) {
                            /* To check if player is already exist */
                            $Query = $this->db->query('SELECT PlayerID FROM sports_players WHERE PlayerIDLive = ' . $Player['pid'] . ' LIMIT 1');
                            $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                            if (!$PlayerID) {
                                /* Add players to entity table and get EntityID. */
                                $PlayerGUID = get_guid();
                                $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                                $PlayersAPIData = array(
                                    'PlayerID' => $PlayerID,
                                    'PlayerGUID' => $PlayerGUID,
                                    'PlayerIDLive' => $Player['pid'],
                                    'PlayerName' => $Player['title'],
                                    'PlayerSalary' => $Player['fantasy_player_rating'],
                                    'PlayerCountry' => ($Player['country']) ? strtoupper($Player['country']) : null,
                                    'PlayerBattingStyle' => ($Player['batting_style']) ? $Player['batting_style'] : null,
                                    'PlayerBowlingStyle' => ($Player['bowling_style']) ? $Player['bowling_style'] : null
                                );
                                $this->db->insert('sports_players', $PlayersAPIData);
                            } else {
                                $PlayersAPIData = array(
                                    'PlayerName' => $Player['title'],
                                    'PlayerSalary' => $Player['fantasy_player_rating'],
                                    'PlayerCountry' => ($Player['country']) ? strtoupper($Player['country']) : null,
                                    'PlayerBattingStyle' => ($Player['batting_style']) ? $Player['batting_style'] : null,
                                    'PlayerBowlingStyle' => ($Player['bowling_style']) ? $Player['bowling_style'] : null
                                );
                                $this->db->where('PlayerID', $PlayerID);
                                $this->db->update('sports_players', $PlayersAPIData);
                            }
                            $Query = $this->db->query('SELECT MatchID FROM sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND SeriesID = ' . $SeriesID . ' AND TeamID = ' . $TeamID . ' AND MatchID =' . $MatchID . ' LIMIT 1');
                            $IsMatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                            if (!$IsMatchID) {
                                if (!empty($PlayerRolesArr[strtolower($Player['playing_role'])])) {
                                    $TeamPlayersData[] = array(
                                        'SeriesID' => $SeriesID,
                                        'RoundID' => $RoundID,
                                        'MatchID' => $MatchID,
                                        'TeamID' => $TeamID,
                                        'PlayerID' => $PlayerID,
                                        'PlayerSalary' => $Player['fantasy_player_rating'],
                                        'IsPlaying' => "No",
                                        'PlayerRole' => $PlayerRolesArr[strtolower($Player['playing_role'])],
                                        'IsDfsPlay' => ($Value['IsPreSquad'] == "Yes") ? "Yes" : "No"
                                    );
                                    if (!empty($Value['RoundID'])) {
                                        $TeamPlayersDataAuction[] = array(
                                            'PlayerID' => $PlayerID,
                                            'SeriesID' => $Value['SeriesID'],
                                            'TeamID' => $TeamID,
                                            'RoundID' => $Value['RoundID'],
                                            'PlayerRole' => $PlayerRolesArr[strtolower($Player['playing_role'])]
                                        );
                                    }
                                }
                            } else {
                                /* if ($TotalJoinedTeams > 0) {
                                  continue;
                                  } */
                                $UpdatePlayer = array(
                                    'RoundID' => $RoundID,
                                    'IsDfsPlay' => "Yes"
                                );
                                $Query = $this->db->query('SELECT IsAdminUpdate FROM sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND SeriesID = ' . $SeriesID . ' AND TeamID = ' . $TeamID . ' AND MatchID =' . $MatchID . ' LIMIT 1');
                                $IsAdminUpdate = $Query->row()->IsAdminUpdate;
                                if ($IsAdminUpdate == "No") {
                                    $UpdatePlayer['PlayerSalary'] = $Player['fantasy_player_rating'];
                                    $UpdatePlayer['PlayerRole'] = $PlayerRolesArr[strtolower($Player['playing_role'])];
                                }
                                if ($Value['IsPreSquad'] == "Yes") {
                                    /* Update Fantasy Points */
                                    $this->db->where('SeriesID', $SeriesID);
                                    $this->db->where('MatchID', $MatchID);
                                    $this->db->where('TeamID', $TeamID);
                                    $this->db->where('PlayerID', $PlayerID);
                                    $this->db->limit(1);
                                    $this->db->update('sports_team_players', $UpdatePlayer);
                                }
                                if (!empty($Value['RoundID'])) {
                                    $TeamPlayersDataAuction[] = array(
                                        'PlayerID' => $PlayerID,
                                        'SeriesID' => $Value['SeriesID'],
                                        'TeamID' => $TeamID,
                                        'RoundID' => $Value['RoundID'],
                                        'PlayerRole' => $PlayerRolesArr[strtolower($Player['playing_role'])]
                                    );
                                }
                            }
                        }
                    }
                }

                if (!empty($TeamPlayersData)) {
                    $this->db->insert_batch('sports_team_players', $TeamPlayersData);
                }
                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }
            }

            if (!empty($TeamPlayersDataAuction)) {
                $this->addAuctionDraftSeriesPlayer($TeamPlayersDataAuction);
            }
        }
    }

    /*
      Description: To set player stats (Entity API)
     */

    function getPlayerStatsLiveEntity($CronID) {
        ini_set('max_execution_time', 120);

        /* To get All Player Stats Data */
        $MatchData = $this->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID', array('StatusID' => 5, 'PlayerStatsUpdate' => 'No', 'MatchCompleteDateTime' => date('Y-m-d H:i:s')), true, 0);
        //$MatchData = $this->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID', array('SeriesID' => 201), true, 0,100);
        if (!$MatchData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($MatchData['Data']['Records'] as $Value) {
            $PlayerData = $this->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);
            if (empty($PlayerData)) continue;

            foreach ($PlayerData['Data']['Records'] as $Player) {
                $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/players/' . $Player['PlayerIDLive'] . '/stats/?token=');
                $BattingStats = (!empty($Response['response']['batting'])) ? str_replace(array('odi', 't20', 't20i', 'lista', 'firstclass', 'test', 'others', 'womant20', 'womanodi', 'run4', 'run6', 'runs', 'balls', 'run50', 'notout', 'run100', 'strike', 'average', 'catches', 'highest', 'innings', 'matches', 'stumpings', 'match_id', 'inning_id'), array('ODI', 'T20', 'T20I', 'ListA', 'FirstClass', 'Test', 'Others', 'WomanT20', 'WomanODI', 'Fours', 'Sixes', 'Runs', 'Balls', 'Fifties', 'NotOut', 'Hundreds', 'StrikeRate', 'Average', 'Catches', 'HighestScore', 'Innings', 'Matches', 'Stumpings', 'MatchID', 'InningID'), json_encode($Response['response']['batting'])) : null;
                $BowlingStats = (!empty($Response['response']['bowling'])) ? str_replace(array('odi', 't20', 't20i', 'lista', 'firstclass', 'test', 'others', 'womant20', 'womanodi', 'match_id', 'inning_id', 'innings', 'matches', 'balls', 'overs', 'runs', 'wickets', 'bestinning', 'bestmatch', 'econ', 'average', 'strike', 'wicket4i', 'wicket5i', 'wicket10m'), array('ODI', 'T20', 'T20I', 'ListA', 'FirstClass', 'Test', 'Others', 'WomanT20', 'WomanODI', 'MatchID', 'InningID', 'Innings', 'Matches', 'Balls', 'Overs', 'Runs', 'Wickets', 'BestInning', 'BestMatch', 'Economy', 'Average', 'StrikeRate', 'FourPlusWicketsInSingleInning', 'FivePlusWicketsInSingleInning', 'TenPlusWicketsInSingleInning'), json_encode($Response['response']['bowling'])) : null;
                /* Update Player Stats */
                $PlayerStats = array(
                    'PlayerBattingStats' => $BattingStats,
                    'PlayerBowlingStats' => $BowlingStats,
                    'LastUpdatedOn' => date('Y-m-d H:i:s')
                );
                $this->db->where('PlayerID', $Player['PlayerID']);
                $this->db->limit(1);
                $this->db->update('sports_players', $PlayerStats);
            }

            $MatchUpdate = array(
                'PlayerStatsUpdate' => "Yes",
            );
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('sports_matches', $MatchUpdate);
        }
    }

    /*
      Description: To set player stats (Entity API)
     */

    function playerStatsUpdateAllEntity($CronID) {
        /* To get All Player Stats Data */
        $PlayerData = $this->db->query('SELECT PlayerName,PlayerCountry,PlayerBattingStats,PlayerBowlingStats,PlayerIDLive,PlayerID from sports_players WHERE PlayerStatsUpdate = "No" Order By PlayerID DESC')->result_array();
        foreach ($PlayerData as $Player) {
            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/players/' . $Player['PlayerIDLive'] . '/stats/?token=');
            $BattingStats = (!empty($Response['response']['batting'])) ? str_replace(array('odi', 't20', 't20i', 'lista', 'firstclass', 'test', 'others', 'womant20', 'womanodi', 'run4', 'run6', 'runs', 'balls', 'run50', 'notout', 'run100', 'strike', 'average', 'catches', 'highest', 'innings', 'matches', 'stumpings', 'match_id', 'inning_id'), array('ODI', 'T20', 'T20I', 'ListA', 'FirstClass', 'Test', 'Others', 'WomanT20', 'WomanODI', 'Fours', 'Sixes', 'Runs', 'Balls', 'Fifties', 'NotOut', 'Hundreds', 'StrikeRate', 'Average', 'Catches', 'HighestScore', 'Innings', 'Matches', 'Stumpings', 'MatchID', 'InningID'), json_encode($Response['response']['batting'])) : null;
            $BowlingStats = (!empty($Response['response']['bowling'])) ? str_replace(array('odi', 't20', 't20i', 'lista', 'firstclass', 'test', 'others', 'womant20', 'womanodi', 'match_id', 'inning_id', 'innings', 'matches', 'balls', 'overs', 'runs', 'wickets', 'bestinning', 'bestmatch', 'econ', 'average', 'strike', 'wicket4i', 'wicket5i', 'wicket10m'), array('ODI', 'T20', 'T20I', 'ListA', 'FirstClass', 'Test', 'Others', 'WomanT20', 'WomanODI', 'MatchID', 'InningID', 'Innings', 'Matches', 'Balls', 'Overs', 'Runs', 'Wickets', 'BestInning', 'BestMatch', 'Economy', 'Average', 'StrikeRate', 'FourPlusWicketsInSingleInning', 'FivePlusWicketsInSingleInning', 'TenPlusWicketsInSingleInning'), json_encode($Response['response']['bowling'])) : null;

            if ($BattingStats == null || $BowlingStats == null) {
                $PlayerStatsUpdate = 'No';
            } else {
                $PlayerStatsUpdate = 'Yes';
            }
            /* Update Player Stats */
            $PlayerStats = array(
                'PlayerBattingStats' => $BattingStats,
                'PlayerBattingStats' => $BattingStats,
                'PlayerStatsUpdate' => $PlayerStatsUpdate,
                'LastUpdatedOn' => date('Y-m-d H:i:s')
            );
            $this->db->where('PlayerID', $Player['PlayerID']);
            $this->db->limit(1);
            $this->db->update('sports_players', $PlayerStats);
        }
    }

    /*
      Description: To get match live score (Entity API)
     */

    function getMatchScoreLiveEntity($CronID) {
        ini_set('max_execution_time', 120);
        $newtimestamps = strtotime(date('Y-m-d H:i:s') . ' + 20 minute');
        $NewMatchDateTime = date('Y-m-d H:i:s', $newtimestamps);

        $DateTime = date('Y-m-d H:i', strtotime('-6 days', strtotime(date('Y-m-d H:i'))));
        /* Get Live Matches Data */
        $LiveMatches = $this->getMatches('MatchIDLive,MatchID,StatusID,MatchStartDateTime,MatchStartDateTimeUTC,Status,IsPlayingXINotificationSent,TeamNameShortLocal,TeamNameShortVisitor', array('MatchStartDateTimeBetweenLive' => $DateTime, 'StatusID' => array(
                1, 2, 10)), true, 1, 50);
        /* Get Live Matches Data */
        //$LiveMatches = $this->getMatches('MatchIDLive,MatchID,StatusID,MatchStartDateTimeUTC', array('MatchStartDateTimeDelay' => $NewMatchDateTime, 'StatusID' => array(1, 2, 10)), true, 1, 20);
        //$LiveMatches = $this->getMatches('MatchIDLive,MatchID,StatusID', array('MatchID' => '1125489'), true, 1, 5);
        //$LiveMatches = $this->getMatches('MatchIDLive,MatchID,StatusID,MatchStartDateTimeUTC', array('Filter' => 'Yesterday', 'StatusID' => array(1, 2, 10)), true, 1, 20);
        if (!$LiveMatches) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        $MatchStatus = array("live" => 2, "abandoned" => 8, "cancelled" => 3, "no result" => 9);
        $ContestStatus = array("live" => 2, "abandoned" => 5, "cancelled" => 3, "no result" => 5);
        $InningsStatus = array(1 => 'scheduled', 2 => 'completed', 3 => 'live', 4 => 'abandoned');

        foreach ($LiveMatches['Data']['Records'] as $Value) {
            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/matches/' . $Value['MatchIDLive'] . '/scorecard/?token=');
            if (!empty($Response)) {
                if ($Response['status'] == "ok" && !empty($Response['response'])) {

                    $MatchStatusLive = strtolower($Response['response']['status_str']);
                    $MatchStatusLiveCheck = $Response['response']['status'];
                    $GameState = $Response['response']['game_state'];
                    $GameStateInnings = $Response['response']['innings'];
                    $Verified = $Response['response']['verified'];
                    $PreSquad = $Response['response']['pre_squad'];
                    $StatusNote = strtolower($Response['response']['status_note']);
                    if ($GameState != 7 || $GameState != 6) {
                        if ($MatchStatusLiveCheck == 2 || $MatchStatusLiveCheck == 3) {

                            /** set is playing player 22 * */
                            $ResponsePlayerSquad = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/matches/' . $Value['MatchIDLive'] . '/squads/?token=');
                            if (!empty($ResponsePlayerSquad)) {
                                if ($ResponsePlayerSquad['status'] == 'ok') {
                                    $squadTeamA = $ResponsePlayerSquad['response']['teama']['squads'];
                                    $squadTeamB = $ResponsePlayerSquad['response']['teamb']['squads'];
                                    $PlayingPlayerIDs = array();
                                    foreach ($squadTeamA as $aRows) {
                                        if ($aRows['playing11'] == 'true') {
                                            $PlayingPlayerIDs[] = $aRows['player_id'];
                                        }
                                    }
                                    foreach ($squadTeamB as $bRows) {
                                        if ($bRows['playing11'] == 'true') {
                                            $PlayingPlayerIDs[] = $bRows['player_id'];
                                        }
                                    }
                                    if (count($PlayingPlayerIDs) > 20) {
                                        $PlayersIdsData = array();
                                        $PlayersData = $this->Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);
                                        if ($PlayersData) {
                                            $PlayersIdsData = array_column($PlayersData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
                                        }
                                        foreach ($PlayingPlayerIDs as $IsPlayer) {
                                            $this->db->where('MatchID', $Value['MatchID']);
                                            $this->db->where('PlayerID', $PlayersIdsData[$IsPlayer]);
                                            $this->db->limit(1);
                                            $this->db->update('sports_team_players', array('IsPlaying' => "Yes"));
                                        }
                                    }
                                }
                            }

                            if ($MatchStatusLive == 'scheduled' || $MatchStatusLive == 'rescheduled') continue;

                            if (empty($Response['response']['players'])) continue;

                            $LivePlayersData = array_column($Response['response']['players'], 'title', 'pid');
                            $MatchScoreDetails = $InningsData = array();
                            $MatchScoreDetails['StatusLive'] = $Response['response']['status_str'];
                            $MatchScoreDetails['StatusNote'] = $Response['response']['status_note'];
                            $MatchScoreDetails['TeamScoreLocal'] = array('Name' => $Response['response']['teama']['name'], 'ShortName' => $Response['response']['teama']['short_name'], 'LogoURL' => $Response['response']['teama']['logo_url'], 'Scores' => @$Response['response']['teama']['scores'], 'Overs' => @$Response['response']['teama']['overs']);
                            $MatchScoreDetails['TeamScoreVisitor'] = array('Name' => $Response['response']['teamb']['name'], 'ShortName' => $Response['response']['teamb']['short_name'], 'LogoURL' => $Response['response']['teamb']['logo_url'], 'Scores' => @$Response['response']['teamb']['scores'], 'Overs' => @$Response['response']['teamb']['overs']);
                            $MatchScoreDetails['MatchVenue'] = @$Response['response']['venue']['name'] . ", " . $Response['response']['venue']['location'];
                            $MatchScoreDetails['Result'] = @$Response['response']['result'];
                            $MatchScoreDetails['Toss'] = @$Response['response']['toss']['text'];
                            $MatchScoreDetails['ManOfTheMatchPlayer'] = @$Response['response']['man_of_the_match']['name'];
                            foreach ($Response['response']['innings'] as $InningsValue) {
                                $BatsmanData = $BowlersData = $FielderData = $AllPlayingXI = array();

                                /* Manage Batsman Data */
                                foreach ($InningsValue['batsmen'] as $BatsmenValue) {
                                    $BatsmanData[] = array(
                                        'Name' => @$LivePlayersData[$BatsmenValue['batsman_id']],
                                        'PlayerIDLive' => $BatsmenValue['batsman_id'],
                                        'Role' => $BatsmenValue['role'],
                                        'Runs' => $BatsmenValue['runs'],
                                        'BallsFaced' => $BatsmenValue['balls_faced'],
                                        'Fours' => $BatsmenValue['fours'],
                                        'Sixes' => $BatsmenValue['sixes'],
                                        'HowOut' => $BatsmenValue['how_out'],
                                        'IsPlaying' => ($BatsmenValue['how_out'] == 'Not out') ? 'Yes' : 'No',
                                        'StrikeRate' => $BatsmenValue['strike_rate']
                                    );
                                    $AllPlayingXI[$BatsmenValue['batsman_id']]['batting'] = array(
                                        'Name' => @$LivePlayersData[$BatsmenValue['batsman_id']],
                                        'PlayerIDLive' => $BatsmenValue['batsman_id'],
                                        'Role' => $BatsmenValue['role'],
                                        'Runs' => $BatsmenValue['runs'],
                                        'BallsFaced' => $BatsmenValue['balls_faced'],
                                        'Fours' => $BatsmenValue['fours'],
                                        'Sixes' => $BatsmenValue['sixes'],
                                        'HowOut' => $BatsmenValue['how_out'],
                                        'IsPlaying' => ($BatsmenValue['how_out'] == 'Not out') ? 'Yes' : 'No',
                                        'StrikeRate' => $BatsmenValue['strike_rate']
                                    );
                                }

                                /* Manage Bowler Data */
                                foreach ($InningsValue['bowlers'] as $BowlersValue) {
                                    $BowlersData[] = array(
                                        'Name' => @$LivePlayersData[$BowlersValue['bowler_id']],
                                        'PlayerIDLive' => $BowlersValue['bowler_id'],
                                        'Overs' => $BowlersValue['overs'],
                                        'Maidens' => $BowlersValue['maidens'],
                                        'RunsConceded' => $BowlersValue['runs_conceded'],
                                        'Wickets' => $BowlersValue['wickets'],
                                        'NoBalls' => $BowlersValue['noballs'],
                                        'Wides' => $BowlersValue['wides'],
                                        'Economy' => $BowlersValue['econ']
                                    );
                                    $AllPlayingXI[$BowlersValue['bowler_id']]['bowling'] = array(
                                        'Name' => @$LivePlayersData[$BowlersValue['bowler_id']],
                                        'PlayerIDLive' => $BowlersValue['bowler_id'],
                                        'Overs' => $BowlersValue['overs'],
                                        'Maidens' => $BowlersValue['maidens'],
                                        'RunsConceded' => $BowlersValue['runs_conceded'],
                                        'Wickets' => $BowlersValue['wickets'],
                                        'NoBalls' => $BowlersValue['noballs'],
                                        'Wides' => $BowlersValue['wides'],
                                        'Economy' => $BowlersValue['econ']
                                    );
                                }

                                /* Manage Fielder Data */
                                foreach ($InningsValue['fielder'] as $FielderValue) {
                                    $FielderData[] = array(
                                        'Name' => $FielderValue['fielder_name'],
                                        'PlayerIDLive' => $FielderValue['fielder_id'],
                                        'Catches' => $FielderValue['catches'],
                                        'RunOutThrower' => 0,
                                        'RunOutCatcher' => 0,
                                        'RunOutDirectHit' => $FielderValue['runout_direct_hit'],
                                        'RunOutCatcherThrower' => $FielderValue['runout_thrower'] + $FielderValue['runout_catcher'],
                                        'Stumping' => $FielderValue['stumping']
                                    );
                                    $AllPlayingXI[$FielderValue['fielder_id']]['fielding'] = array(
                                        'Name' => $FielderValue['fielder_name'],
                                        'PlayerIDLive' => $FielderValue['fielder_id'],
                                        'Catches' => $FielderValue['catches'],
                                        'RunOutThrower' => 0,
                                        'RunOutCatcher' => 0,
                                        'RunOutDirectHit' => $FielderValue['runout_direct_hit'],
                                        'RunOutCatcherThrower' => $FielderValue['runout_thrower'] + $FielderValue['runout_catcher'],
                                        'Stumping' => $FielderValue['stumping']
                                    );
                                }

                                $InningsData[] = array(
                                    'Name' => $InningsValue['name'],
                                    'ShortName' => $InningsValue['short_name'],
                                    'Scores' => $InningsValue['scores'],
                                    'Status' => $InningsStatus[$InningsValue['status']],
                                    'ScoresFull' => $InningsValue['scores_full'],
                                    'BatsmanData' => $BatsmanData,
                                    'BowlersData' => $BowlersData,
                                    'FielderData' => $FielderData,
                                    'AllPlayingData' => $AllPlayingXI,
                                    'ExtraRuns' => array('Byes' => $InningsValue['extra_runs']['byes'], 'LegByes' => $InningsValue['extra_runs']['legbyes'], 'Wides' => $InningsValue['extra_runs']['wides'], 'NoBalls' => $InningsValue['extra_runs']['noballs'])
                                );
                            }
                            $MatchScoreDetails['Innings'] = $InningsData;

                            $MatchCompleteDateTime = date('Y-m-d H:i:s', strtotime("+2 hours"));
                            /* Update Match Data */

                            $this->db->where('MatchID', $Value['MatchID']);
                            $this->db->limit(1);
                            $this->db->update('sports_matches', array('MatchScoreDetails' => json_encode($MatchScoreDetails), 'MatchCompleteDateTime' => $MatchCompleteDateTime));

                            /* $this->getPlayerPointsEntity(0, $Value['MatchID']); */

                            $CronID = $this->Utility_model->insertCronLogs('getPlayerPoints');
                            $this->getPlayerPointsEntity($CronID, $Value['MatchID']);
                            $this->Utility_model->updateCronLogs($CronID);

                            if ($Value['StatusID'] != 2) {
                                /* Update Contest Status */
                                $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID'] . '  AND E.StatusID != 3');

                                /* Update Match Status */
                                $this->db->where('EntityID', $Value['MatchID']);
                                $this->db->limit(1);
                                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 2));
                            }
                            if (strtolower($MatchStatusLive) == "completed" && $StatusNote != "abandoned") {

                                /* $this->getJoinedContestTeamPoints($CronID, $Value['MatchID']); */
                                /* Update Final player points before complete match */
                                $CronID = $this->Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
                                $this->getJoinedContestTeamPoints($CronID, $Value['MatchID'], array(2, 5), 5);
                                $this->Utility_model->updateCronLogs($CronID);

                                /* Update Match Status */
                                $this->db->where('EntityID', $Value['MatchID']);
                                $this->db->limit(1);
                                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 10));
                                if ($Verified == "true") {

                                    /* $this->getPlayerPointsEntity(0, $Value['MatchID']); */
                                    $CronID = $this->Utility_model->insertCronLogs('getPlayerPoints');
                                    $this->getPlayerPointsEntity($CronID, $Value['MatchID']);
                                    $this->Utility_model->updateCronLogs($CronID);

                                    /* Update Contest Status */
                                    /* $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID'] . ' AND E.StatusID != 3'); */

                                    /* Update Match Status */

                                    $this->db->where('EntityID', $Value['MatchID']);
                                    $this->db->limit(1);
                                    $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 5));


                                    /* Update Match Status */
                                    $this->db->where('MatchID', $Value['MatchID']);
                                    $this->db->limit(1);
                                    $this->db->update('sports_matches', array("MatchCompleteDateTime" => date('Y-m-d H:i:s', strtotime("+2 hours"))));

                                    /* Update Contest Status */
                                    $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE  E.StatusID = 2 AND  C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID']);
                                }
                            }
                        } else {
                            if ($StatusNote == 'no result') {
                                $MatchStatusLive = 'no result';
                            }
                            if ($MatchStatusLiveCheck == 4) {
                                $MatchStatusLive = 'abandoned';
                            }
                            if (strpos($StatusNote, 'abandoned') !== false) {
                                $MatchStatusLive = 'abandoned';
                            }
                            if (strpos($StatusNote, 'scheduled') !== false) {
                                $MatchStatusLive = 'scheduled';
                            }
                            $this->db->trans_start();
                            if ($MatchStatusLiveCheck == 4) {
                                /* Update Contest Status */
                                /* $this->autoCancelContestMatchAbonded($Value['MatchID']); */
                                $CronID = $this->Utility_model->insertCronLogs('autoCancelContest');
                                $this->autoCancelContest($CronID, 'Abonded', $Value['MatchID']);
                                $this->Utility_model->updateCronLogs($CronID);

                                /* Update Match Status */
                                $this->db->where('EntityID', $Value['MatchID']);
                                $this->db->limit(1);
                                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 8));
                            }
                            $this->db->trans_complete();
                            if ($this->db->trans_status() === false) {
                                return false;
                            }
                        }
                    }
                }
            }
        }
    }

    /*
      Description: To get match live score (Cricket API)
     */

    function getLivePlaying11MatchPlayerEntityAPI() {
        ini_set('max_execution_time', 300);
        $DateTime = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')) + 2400);
        /* Get Live Matches Data */
        $LiveMatches = $this->getMatches('MatchIDLive,MatchGUID,MatchID,MatchStartDateTime,Status,IsPlayingXINotificationSent,TeamNameShortLocal,TeamNameShortVisitor', array('MatchStartDateTime' => $DateTime, 'StatusID' => array(1, 2),
            "IsPlayingXINotificationSent" => "No"), true, 1, 25);
        foreach ($LiveMatches['Data']['Records'] as $Value) {
            /** set is playing player 22 * */
            $ResponsePlayerSquad = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/matches/' . $Value['MatchIDLive'] . '/squads/?token=');
            if (!empty($ResponsePlayerSquad)) {
                if ($ResponsePlayerSquad['status'] == 'ok') {
                    $squadTeamA = $ResponsePlayerSquad['response']['teama']['squads'];
                    $squadTeamB = $ResponsePlayerSquad['response']['teamb']['squads'];
                    $PlayingPlayerIDs = array();
                    foreach ($squadTeamA as $aRows) {
                        if ($aRows['playing11'] == 'true') {
                            $PlayingPlayerIDs[] = $aRows['player_id'];
                        }
                    }
                    foreach ($squadTeamB as $bRows) {
                        if ($bRows['playing11'] == 'true') {
                            $PlayingPlayerIDs[] = $bRows['player_id'];
                        }
                    }
                    if (count($PlayingPlayerIDs) > 20) {
                        $PlayersIdsData = array();
                        $PlayersData = $this->Sports_model->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);
                        if ($PlayersData) {
                            $PlayersIdsData = array_column($PlayersData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
                        }
                        foreach ($PlayingPlayerIDs as $IsPlayer) {
                            $this->db->where('MatchID', $Value['MatchID']);
                            $this->db->where('PlayerID', $PlayersIdsData[$IsPlayer]);
                            $this->db->limit(1);
                            $this->db->update('sports_team_players', array('IsPlaying' => "Yes"));
                        }
                        if ($Value['IsPlayingXINotificationSent'] == "No") {
                            /* Update Playing XI Notification Status */
                            $this->db->where('MatchID', $Value['MatchID']);
                            $this->db->limit(1);
                            $this->db->update('sports_matches', array('IsPlayingXINotificationSent' => "Yes"));
                            /* Send Playing XI Notification - To all users */
                            pushNotificationAndroidBroadcast('Playing XI - Announced', 'Playing XI for ' . $Value['TeamNameShortLocal'] . ' Vs ' . $Value['TeamNameShortVisitor'] . ' announced.', $Value['MatchGUID']);
                            pushNotificationIphoneBroadcast('Playing XI - Announced', 'Playing XI for ' . $Value['TeamNameShortLocal'] . ' Vs ' . $Value['TeamNameShortVisitor'] . ' announced.', $Value['MatchGUID']);
                        }
                    }
                }
            }
        }
    }

    /*
      Description: To set series data (Cricket API)
     */

    function getSeriesLiveCricketAPI($CronID) {
        ini_set('max_execution_time', 120);
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2), true, 0);
        foreach ($SeriesData['Data']['Records'] as $SeriesValue) {
            $MatchDetails = $this->db->query('SELECT CAST(MatchStartDateTime as DATE) as MatchStartDateTime,MatchTypeID FROM sports_matches WHERE SeriesID = ' . $SeriesValue['SeriesID'] . ' ORDER BY MatchStartDateTime ASC LIMIT 1')->row_array();
            $SeriesStartDate = $MatchDetails['MatchStartDateTime'];
            $MatchDetailsNext = $this->db->query('SELECT CAST(MatchStartDateTime as DATE) as MatchStartDateTime,MatchTypeID,RoundID FROM sports_matches WHERE SeriesID = ' . $SeriesValue['SeriesID'] . ' ORDER BY MatchStartDateTime DESC LIMIT 1')->row_array();
            $SeriesEndDate = $MatchDetailsNext['MatchStartDateTime'];
            if ($MatchDetailsNext['MatchTypeID'] == 5) {
                $SeriesEndDate = date('Y-m-d', strtotime($MatchDetailsNext['MatchStartDateTime'] . ' + 4 days'));
            }
            $this->db->where('SeriesID', $SeriesValue['SeriesID']);
            $this->db->limit(1);
            $this->db->update('sports_series', array('SeriesStartDate' => $SeriesStartDate, 'SeriesEndDate' => $SeriesEndDate));

            $updateData = array(
                'RoundEndDate' => $SeriesEndDate
            );
            $this->db->where('RoundID', $MatchDetailsNext['RoundID']);
            $this->db->limit(1);
            $this->db->update('sports_series_rounds', $updateData);
        }
    }

    /*
      Description: To set series data (Entity API)
     */

    function getSeriesRoundsLiveCricketAPI($CronID) {
        ini_set('max_execution_time', 120);
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('SeriesEndDateRound' => date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))))), true, 0);
        if ($SeriesData['Data']['TotalRecords'] > 0) {
            foreach ($SeriesData['Data']['Records'] as $Rows) {
                $SeasonKey = trim($Rows['SeriesIDLive']);
                $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/season/' . $SeasonKey . '/?access_token=');
                if (!empty($Response['data']['season'])) {
                    $SeriesResponse = $Response['data']['season'];
                    $SeriesName = $SeriesResponse['name'];
                    $SeriesStartDate = date('Y-m-d', $SeriesResponse['start_date']['timestamp']);
                    $SeriesEndDate = date('Y-m-d', $SeriesResponse['end_date']['timestamp']);
                    $SeriesRounds = $SeriesResponse['rounds'];
                    $SeriesTeams = count($SeriesResponse['teams']);
                    if (!empty($SeriesRounds)) {
                        foreach ($SeriesRounds as $Value) {
                            $RoundKey = $Value['key'];

                            if ($SeriesTeams > 0) {
                                if ($SeriesTeams <= 2) {
                                    $matchesDetails = $Value['groups'][$RoundKey]['matches'];

                                    $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/match/' . $matchesDetails[0] . '/?access_token=');
                                    $SeriesStartDate = date('Y-m-d', $Response['data']['card']['start_date']['timestamp']);

                                    $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/match/' . $matchesDetails[count($matchesDetails) - 1] . '/?access_token=');
                                    $SeriesEndDate = date('Y-m-d', $Response['data']['card']['start_date']['timestamp']);

                                    $RoundID = $this->db->query("SELECT RoundID FROM sports_series_rounds WHERE RoundIDLive ='" . $RoundKey . "' ")->row()->RoundID;

                                    if (empty($RoundID)) {
                                        $RoundFormat = "T20";
                                        if (strpos(strtolower($Value['name']), 'test') !== false) {
                                            $SeriesEndDate = date('Y-m-d', strtotime($SeriesEndDate . ' + 4 days'));
                                        }

                                        $SeriesRoundsList = array(
                                            'SeriesID' => $Rows['SeriesID'],
                                            'RoundIDLive' => $RoundKey,
                                            'RoundName' => $SeriesName . ' ' . $Value['name'],
                                            'SeriesType' => "Tour",
                                            'RoundStartDate' => $SeriesStartDate,
                                            'RoundEndDate' => $SeriesEndDate,
                                            'RoundFormat' => ucfirst($Value['name']),
                                            'StatusID' => 2,
                                            'AuctionDraftStatusID' => 1
                                        );

                                        $this->db->insert('sports_series_rounds', $SeriesRoundsList);
                                        $RoundID = $this->db->insert_id();
                                        $this->updateRoundMatchesPlayer($RoundID, $matchesDetails);
                                    } else {
                                        /* $MatchRoundData = array(
                                          'RoundEndDate' => $SeriesEndDate
                                          );
                                          $this->db->where('RoundID', $RoundID);
                                          $this->db->where('RoundIDLive', $RoundKey);
                                          $this->db->update('sports_series_rounds', $MatchRoundData); */

                                        $this->updateRoundMatchesPlayer($RoundID, $matchesDetails);
                                    }
                                } elseif ($SeriesTeams > 2) {
                                    if (isset($Value['groups'][$RoundKey])) {
                                        $matchesDetails = $Value['groups'][$RoundKey]['matches'];
                                        $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/match/' . $matchesDetails[0] . '/?access_token=');
                                        $RoundID = $this->db->query("SELECT RoundID FROM sports_series_rounds WHERE SeriesID ='" . $Rows['SeriesID'] . "' ")->row()->RoundID;
                                        $RoundFormatApi = $Response['data']['card']['format'];
                                    } else {
                                        foreach ($Value['groups'] as $K => $V) {
                                            $matchesDetails = $V['matches'];
                                            break;
                                        }
                                        $RoundFormatApi = $this->db->query("SELECT CASE MatchTypeID
                                                                                    when '1' then 'odi'
                                                                                    when '2' then 'odi'
                                                                                    when '3' then 't20'
                                                                                    when '4' then 't20i'
                                                                                    when '5' then 'test'
                                                                                    when '7' then 't20'
                                                                                    when '7' then 'odi'
                                                                                    when '9' then 'odi'
                                                                                    END as Type FROM sports_matches WHERE SeriesID ='" . $Rows['SeriesID'] . "' limit 1")->row()->Type;

                                        $RoundID = $this->db->query("SELECT RoundID FROM sports_series_rounds WHERE SeriesID ='" . $Rows['SeriesID'] . "' ")->row()->RoundID;
                                    }
                                    if (empty($RoundID)) {
                                        $RoundFormat = "T20";
                                        if (strpos(strtolower($RoundFormatApi), 't20') !== false) {
                                            $RoundFormat = "T20";
                                        } else if (strpos(strtolower($RoundFormatApi), 't20i') !== false) {
                                            $RoundFormat = "T20i";
                                        } else if (strpos(strtolower($RoundFormatApi), 'odi') !== false) {
                                            $RoundFormat = "Odi";
                                        } else if (strpos(strtolower($RoundFormatApi), 'test') !== false) {
                                            $RoundFormat = "Test";
                                        }
                                        $Status = 1;
                                        if ((strtotime($SeriesStartDate) > strtotime(date('Y-m-d')))) {
                                            $Status = 1;
                                        } else if ((strtotime($SeriesStartDate) <= strtotime(date('Y-m-d'))) && (strtotime($SeriesEndDate) > strtotime(date('Y-m-d')))) {
                                            $Status = 2;
                                        } else {
                                            $Status = 5;
                                        }
                                        $SeriesRoundsList = array(
                                            'SeriesID' => $Rows['SeriesID'],
                                            'RoundIDLive' => $Rows['SeriesID'],
                                            'RoundName' => $SeriesName,
                                            'SeriesType' => "Tournament",
                                            'RoundStartDate' => $SeriesStartDate,
                                            'RoundEndDate' => $SeriesEndDate,
                                            'RoundFormat' => $RoundFormat,
                                            'StatusID' => $Status,
                                            'AuctionDraftStatusID' => $Status
                                        );
                                        $this->db->insert('sports_series_rounds', $SeriesRoundsList);
                                        $RoundID = $this->db->insert_id();
                                        $this->updateRoundMatchesPlayer($RoundID, $matchesDetails);
                                    } else {
                                        $this->updateRoundMatchesPlayer($RoundID, $matchesDetails);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function updateSeriesRounds($RoundID, $SeriesID, $Input = array()) {
        if (!empty($Input)) {
            $updateData = array(
                'RoundStartDate' => date('Y-m-d', strtotime($Input['RoundStartDate'])),
                'RoundEndDate' => date('Y-m-d', strtotime($Input['RoundEndDate'])),
                'AuctionDraftIsPlayed' => $Input['AuctionDraftIsPlayed']
            );
            $this->db->where('RoundID', $RoundID);
            $this->db->limit(1);
            $this->db->update('sports_series_rounds', $updateData);
            if (!empty($SeriesID)) {
                $this->db->where('SeriesID', $Input['SeriesID']);
                $this->db->limit(1);
                $this->db->update('sports_series', array('SeriesStartDate' => date('Y-m-d', strtotime($Input['RoundStartDate'])), 'SeriesEndDate' => date('Y-m-d', strtotime($Input['RoundEndDate']))));
            }
        }
    }

    function updateRoundMatchesPlayer($RoundID, $matchesDetails) {
        foreach ($matchesDetails as $value) {
            $MatchRoundData = array(
                'RoundID' => $RoundID
            );
            $this->db->where('MatchIDLive', $value);
            $this->db->update('sports_matches', $MatchRoundData);

            $MatchData = $this->getMatches('MatchID', array("MatchIDLive" => $value), true, 0);
            if ($MatchData['Data']['TotalRecords'] > 0) {
                $MatchPlayerRoundData = array(
                    'RoundID' => $RoundID
                );
                $this->db->where('MatchID', $MatchData['Data']['Records'][0]['MatchID']);
                $this->db->update('sports_team_players', $MatchPlayerRoundData);
            }
        }
    }

    /*
      Description: To get all rounds
     */

    function getRounds($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
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
                'StatusID' => 'CASE S.StatusID
                                when "1" then "Pending"
                                when "2" then "Running"
                                when "5" then "Completed"
                                END as StatusID',
                'SeriesType' => 'S.SeriesType',
                'RoundFormat' => 'S.RoundFormat',
                'AuctionDraftStatusID' => 'S.AuctionDraftStatusID',
                'SeriesIDLive' => 'S.RoundIDLive SeriesIDLive',
                'AuctionDraftIsPlayed' => 'S.AuctionDraftIsPlayed',
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
                when "1" then "Upcoming"
                when "2" then "Active"
                when "5" then "Completed"
                when "6" then "Inactive"
                END as Status',
                'AuctionDraftStatus' => 'CASE S.AuctionDraftStatusID
                when "1" then "Pending"
                when "2" then "Running"
                when "5" then "Completed"
                END as AuctionDraftStatus',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('S.RoundID,S.SeriesID,S.RoundName AS SeriesName');
        if (!empty($Field)) $this->db->select($Field, FALSE);
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
        if (!empty($Where['RoundFormat'])) {
            $this->db->where("S.RoundFormat", strtolower($Where['RoundFormat']));
        }
        if (!empty($Where['SeriesStartDate'])) {
            $this->db->where("S.RoundStartDate >=", $Where['SeriesStartDate']);
        }
        if (!empty($Where['SeriesEndDate'])) {
            $this->db->where("S.RoundEndDate >=", $Where['SeriesEndDate']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("S.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['NoInStatusID'])) {
            $this->db->where("S.StatusID !=", $Where['NoInStatusID']);
        }
        if (!empty($Where['AuctionDraftIsPlayed'])) {
            $this->db->where("S.AuctionDraftIsPlayed", $Where['AuctionDraftIsPlayed']);
        }
        if (!empty($Where['AuctionDraftStatusID'])) {
            $this->db->where("S.AuctionDraftStatusID", $Where['AuctionDraftStatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            if (!empty($Where['OrderByToday']) && $Where['OrderByToday'] == 'Yes') {
                $this->db->order_by('DATE(S.RoundStartDate)="' . date('Y-m-d') . '" ASC', null, FALSE);
                $this->db->order_by('S.StatusID=2 DESC', null, FALSE);
            } else {
                $this->db->order_by('S.StatusID', 'ASC');
                $this->db->order_by('S.RoundStartDate', 'DESC');
                $this->db->order_by('S.RoundName', 'ASC');
            }
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
                $DataReturn = array();
                foreach ($Query->result_array() as $Rows) {
                    if (!empty($Where['IsPlayRounds']) && $Where['IsPlayRounds'] == "Yes") {
                        if (!empty($Rows['SeriesMatchStartDate'])) {
                            $this->db->select('COUNT(PlayerID) as TotalPlayer');
                            $this->db->from('sports_auction_draft_series_players');
                            $this->db->where("RoundID", $Rows['RoundID']);
                            $this->db->where("IsActive", "Yes");
                            $Query = $this->db->get();
                            $TotalPlayers = $Query->row_array();
                            if ($TotalPlayers['TotalPlayer'] >= 20) {
                                $DataReturn[] = $Rows;
                            }
                        }
                    } else {
                        $DataReturn[] = $Rows;
                    }
                }
                $Return['Data']['Records'] = $DataReturn;
                return $Return;
            } else {
                return $Query->row_array();
            }
        }
        return FALSE;
    }

    function updateRounds($Input = array()) {
        if (!empty($Input)) {
            $updateData = array(
                'RoundStartDate' => date('Y-m-d', strtotime($Input['RoundStartDate'])),
                'RoundEndDate' => date('Y-m-d', strtotime($Input['RoundEndDate'])),
                'AuctionDraftIsPlayed' => $Input['AuctionDraftIsPlayed']
            );
            $this->db->where('RoundID', $Input['RoundID']);
            $this->db->limit(1);
            $this->db->update('sports_series_rounds', $updateData);
            if (!empty($Input['SeriesType']) && $Input['SeriesType'] == 'Tournament' && !empty($Input['SeriesID'])) {
                $this->db->where('SeriesID', $Input['SeriesID']);
                $this->db->limit(1);
                $this->db->update('sports_series', array('SeriesStartDate' => date('Y-m-d', strtotime($Input['RoundStartDate'])), 'SeriesEndDate' => date('Y-m-d', strtotime($Input['RoundEndDate']))));
            }
        }
    }

    function getRoundPlayers($RoundID) {
        $Return = array();
        $this->db->select('PlayerID,PlayerRole');
        $this->db->from('sports_auction_draft_series_players');
        $this->db->where("RoundID", $RoundID);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $RoundPlayers = $Query->result_array();
            $PlayerPoisitions = array_count_values(array_column($RoundPlayers, 'PlayerRole'));
            return $PlayerPoisitions;
        }
        return $Return;
    }

    /*
      Description: To set series and rounds live or complete
     */

    function updateSeriesANDRoundsStatusByMatch() {

        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID,SeriesType,AuctionDraftIsPlayed,SeriesStartDateUTC,SeriesEndDateUTC', array('StatusID' => 2), true, 0);
        foreach ($SeriesData['Data']['Records'] as $Value) {
            $RoundsData = $this->getRounds('RoundID,SeriesIDLive,SeriesID,SeriesType,RoundFormat,AuctionDraftStatusID,SeriesStartDateUTC,SeriesEndDateUTC', array('SeriesID' => $Value['SeriesID'], 'NoInStatusID' => 5, 'OrderBy' => 'RoundStartDate', 'Sequence' => 'ASC'), true, 0);
            $TotalRecords = $RoundsData['Data']['TotalRecords'];
            foreach ($RoundsData['Data']['Records'] as $Rows) {
                if ($Rows['SeriesType'] == "Tournament") {

                    $MatchStartDateTime = $this->db->query('SELECT MatchStartDateTime as MatchStartDateTime FROM sports_matches
                                WHERE sports_matches.RoundID =  ' . $Rows['RoundID'] . ' order by MatchStartDateTime asc limit 1')->row()->MatchStartDateTime;
                    $CurrentDateTime = strtotime(date('Y-m-d H:i'));
                    if (!empty($MatchStartDateTime)) {
                        if ($CurrentDateTime >= strtotime($MatchStartDateTime)) {
                            /** series * */
                            $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                            /** rounds * */
                            $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '" ');
                        }
                    }
                    $Query = $this->db->query("SELECT M.MatchID,M.MatchStartDateTime FROM sports_matches M,tbl_entity E
                                WHERE E.EntityID=M.MatchID AND E.StatusID=5 AND DATE(M.MatchStartDateTime) = '" . $Rows['SeriesEndDateUTC'] . "' AND  M.RoundID ='" . $Rows['RoundID'] . "' order by M.MatchStartDateTime desc limit 1");
                    if ($Query->num_rows() > 0) {
                        $MatchResult = $Query->row_array();
                        $MatchStartTime = strtotime($MatchResult['MatchStartDateTime']);
                        if (strtotime(date('Y-m-d H:i:s')) > $MatchStartTime) {
                            /** rounds * */
                            $this->db->query('UPDATE sports_series_rounds AS S SET S.StatusID = 5 WHERE S.StatusID != 5 AND  SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                            $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                        }
                    }
                    if (strtotime(date('Y-m-d')) > strtotime(date('Y-m-d', strtotime($Value['SeriesEndDateUTC'] . ' + 3 days')))) {
                        /** series * */
                        $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                        $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesID = "' . $Value['SeriesID'] . '"');
                    }
                } else if ($Rows['SeriesType'] == "Tour") {
                    $MatchStartDateTime = $this->db->query('SELECT MatchStartDateTime as MatchStartDateTime FROM sports_matches
                                WHERE sports_matches.RoundID =  ' . $Rows['RoundID'] . ' order by MatchStartDateTime asc limit 1')->row()->MatchStartDateTime;
                    if (!empty($MatchStartDateTime)) {
                        $CurrentDateTime = strtotime(date('Y-m-d H:i:s'));
                        if ($CurrentDateTime >= strtotime($MatchStartDateTime)) {
                            /** series * */
                            $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                            /** rounds * */
                            $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '" ');
                        }
                    }
                    $Query = $this->db->query("SELECT M.MatchID,M.MatchStartDateTime FROM sports_matches M,tbl_entity E
                                WHERE E.EntityID=M.MatchID AND E.StatusID=5 AND DATE(M.MatchStartDateTime) = '" . $Rows['SeriesEndDateUTC'] . "' AND  M.RoundID ='" . $Rows['RoundID'] . "' order by M.MatchStartDateTime desc limit 1");
                    if ($Query->num_rows() > 0) {
                        $MatchResult = $Query->row_array();
                        $MatchStartTime = strtotime($MatchResult['MatchStartDateTime']);
                        if (strtotime(date('Y-m-d H:i:s')) > $MatchStartTime) {
                            /** rounds * */
                            $this->db->query('UPDATE sports_series_rounds AS S SET S.StatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                            $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');

                            /** series * */
                            /* $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                              $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesID = "' . $Value['SeriesID'] . '"'); */
                        }
                    }
                    /* if (strtotime(date('Y-m-d')) > strtotime($Rows['SeriesEndDateUTC']) && $TotalRecords == 1) {

                      $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                      $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesID = "' . $Value['SeriesID'] . '"');
                      } */
                }
            }
        }
    }

    function updateSeriesANDRoundsStatusByMatchOLD() {

        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID,SeriesType,AuctionDraftIsPlayed,SeriesStartDateUTC,SeriesEndDateUTC', array('StatusID' => 2), true, 0);
        foreach ($SeriesData['Data']['Records'] as $Value) {
            $RoundsData = $this->getRounds('RoundID,SeriesIDLive,SeriesID,SeriesType,RoundFormat,AuctionDraftStatusID,SeriesStartDateUTC,SeriesEndDateUTC', array('SeriesID' => $Value['SeriesID'], 'NoInStatusID' => 5,
                'OrderBy' => 'RoundStartDate', 'Sequence' => 'ASC'), true, 0);

            $TotalRecords = $RoundsData['Data']['TotalRecords'];
            if (!empty($RoundsData['Data']['Records'])) {
                foreach ($RoundsData['Data']['Records'] as $Rows) {
                    if ($Rows['SeriesType'] == "Tournament") {

                        $MatchStartDateTime = $this->db->query('SELECT MatchStartDateTime as MatchStartDateTime FROM sports_matches
                                    WHERE sports_matches.RoundID =  ' . $Rows['RoundID'] . ' order by MatchStartDateTime asc limit 1')->row()->MatchStartDateTime;
                        $CurrentDateTime = strtotime(date('Y-m-d H:i'));
                        if ($CurrentDateTime >= strtotime($MatchStartDateTime)) {
                            /** series * */
                            $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                            /** rounds * */
                            $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '" ');
                        }
                        $Query = $this->db->query("SELECT M.MatchID,M.MatchStartDateTime FROM sports_matches M,tbl_entity E
                                    WHERE E.EntityID=M.MatchID AND E.StatusID=5 AND DATE(M.MatchStartDateTime) = '" . $Rows['SeriesEndDateUTC'] . "' AND  M.RoundID ='" . $Rows['RoundID'] . "' order by M.MatchStartDateTime desc limit 1");
                        if ($Query->num_rows() > 0) {
                            $MatchResult = $Query->row_array();
                            $MatchStartTime = strtotime($MatchResult['MatchStartDateTime']);
                            if (strtotime(date('Y-m-d H:i:s')) > $MatchStartTime) {
                                /** rounds * */
                                $this->db->query('UPDATE sports_series_rounds AS S SET S.StatusID = 5 WHERE S.StatusID != 5 AND  SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                                $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                            }
                        }
                        if (strtotime(date('Y-m-d')) > strtotime($Value['SeriesEndDateUTC'])) {
                            /** series * */
                            $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                            $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesID = "' . $Value['SeriesID'] . '"');
                        }
                    } else if ($Rows['SeriesType'] == "Tour") {
                        $MatchStartDateTime = $this->db->query('SELECT MatchStartDateTime as MatchStartDateTime FROM sports_matches
                                    WHERE sports_matches.RoundID =  ' . $Rows['RoundID'] . ' order by MatchStartDateTime asc limit 1')->row()->MatchStartDateTime;
                        $CurrentDateTime = strtotime(date('Y-m-d H:i:s'));
                        if ($CurrentDateTime >= strtotime($MatchStartDateTime)) {
                            /** series * */
                            $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                            /** rounds * */
                            $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 2 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '" ');
                        }
                        $Query = $this->db->query("SELECT M.MatchID,M.MatchStartDateTime FROM sports_matches M,tbl_entity E
                                    WHERE E.EntityID=M.MatchID AND E.StatusID=5 AND DATE(M.MatchStartDateTime) = '" . $Rows['SeriesEndDateUTC'] . "' AND  M.RoundID ='" . $Rows['RoundID'] . "' order by M.MatchStartDateTime desc limit 1");
                        if ($Query->num_rows() > 0) {
                            $MatchResult = $Query->row_array();
                            $MatchStartTime = strtotime($MatchResult['MatchStartDateTime']);
                            if (strtotime(date('Y-m-d H:i:s')) > $MatchStartTime) {
                                /** rounds * */
                                $this->db->query('UPDATE sports_series_rounds AS S SET S.StatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                                $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                            }
                        }
                        /* if (strtotime(date('Y-m-d')) > strtotime($Rows['SeriesEndDateUTC'])) {

                          $this->db->query('UPDATE sports_series_rounds AS S SET S.StatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                          $this->db->query('UPDATE sports_series_rounds SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '" AND RoundID = "' . $Rows['RoundID'] . '"');
                          } */
                        if (strtotime(date('Y-m-d')) > strtotime($Rows['SeriesEndDateUTC']) && $TotalRecords == 1) {
                            /** series * */
                            $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                            $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesID = "' . $Value['SeriesID'] . '"');
                        }
                    }
                }
            } else {
                $Query = $this->db->query("SELECT M.MatchID,M.MatchStartDateTime FROM sports_matches M,tbl_entity E
                                    WHERE E.EntityID=M.MatchID AND E.StatusID=5 AND DATE(M.MatchStartDateTime) = '" . $Value['SeriesEndDateUTC'] . "' AND  M.SeriesID ='" . $Value['SeriesID'] . "' order by M.MatchStartDateTime desc limit 1");
                if ($Query->num_rows() > 0) {
                    $MatchResult = $Query->row_array();
                    $MatchStartTime = strtotime($MatchResult['MatchStartDateTime']);
                    if (strtotime(date('Y-m-d H:i:s')) > $MatchStartTime) {
                        /** series * */
                        $this->db->query('UPDATE sports_series SET AuctionDraftStatusID = 5 WHERE SeriesID = "' . $Value['SeriesID'] . '"');
                        $this->db->query('UPDATE sports_series AS S, tbl_entity AS E SET E.StatusID = 6 WHERE E.EntityID = S.SeriesID AND E.StatusID != 6 AND SeriesID = "' . $Value['SeriesID'] . '"');
                    }
                }
            }
        }
    }

    function getPlayersByType($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
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
                'PlayerSalary' => 'P.PlayerSalary',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
                'MatchTypeID' => 'SSM.MatchTypeID',
                'MatchType' => 'SSM.MatchTypeName as MatchType',
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName');
        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P');
        if (array_keys_exist($Params, array('TeamGUID', 'TeamName', 'TeamNameShort', 'TeamFlag',
                    'PlayerRole', 'IsPlaying', 'TotalPoints', 'PointsData', 'SeriesID',
                    'MatchID'))) {
            $this->db->from('sports_teams T,sports_matches M, sports_team_players TP,sports_set_match_types SSM');
            $this->db->where("P.PlayerID", "TP.PlayerID", FALSE);
            $this->db->where("TP.TeamID", "T.TeamID", FALSE);
            $this->db->where("TP.MatchID", "M.MatchID", FALSE);
            $this->db->where("M.MatchTypeID", "SSM.MatchTypeID", FALSE);
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
        if (!empty($Where['MatchID'])) {
            $this->db->where("TP.MatchID", $Where['MatchID']);
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
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {

                    $Records[] = $Record;
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerSalary'] = (!empty($Record['PlayerSalary'])) ? json_decode($Record['PlayerSalary']) : new stdClass();
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

                    if (in_array('MyTeamPlayer', $Params)) {
                        $this->db->select('SUTP.PlayerID,SUTP.MatchID');
                        $this->db->where('SUTP.MatchID', $Where['MatchID']);
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

                        $TotalTeams = $this->db->query('Select count(*) as TotalTeams from sports_users_teams')->row();
                        $this->db->select('count(SUTP.PlayerID) as TotalPlayer');
                        $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                        $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                        $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                        $Players = $this->db->get()->row();
                        $Records[$key]['PlayerSelectedPercent'] = strval(round((($Players->TotalPlayer * 100 ) / $TotalTeams), 2));
                    }

                    if (in_array('TopPlayer', $Params)) {
                        $Wicketkipper = $this->findKeyValuePlayers($Records, "WicketKeeper");
                        $Batsman = $this->findKeyValuePlayers($Records, "Batsman");
                        $Bowler = $this->findKeyValuePlayers($Records, "Bowler");
                        $Allrounder = $this->findKeyValuePlayers($Records, "AllRounder");
                        usort($Batsman, function ($a, $b) {
                            return $b->TotalPoints - $a->TotalPoints;
                        });
                        usort($Bowler, function ($a, $b) {
                            return $b->TotalPoints - $a->TotalPoints;
                        });
                        usort($Wicketkipper, function ($a, $b) {
                            return $b->TotalPoints - $a->TotalPoints;
                        });
                        usort($Allrounder, function ($a, $b) {
                            return $b->TotalPoints - $a->TotalPoints;
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
                            return $b->TotalPoints - $a->TotalPoints;
                        });
                        $PlayersAll = array_column($AllPlayers, 'PlayerID');
                        if (!empty($Record['PlayerID']) && !empty($PlayersAll)) {
                            if (in_array($Record['PlayerID'], $PlayersAll)) {
                                $Records[$key]['TopPlayer'] = 'Yes';
                            } else {
                                $Records[$key]['TopPlayer'] = 'No';
                            }
                        } else {
                            $Records[$key]['TopPlayer'] = 'No';
                        }
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
                $Record['PlayerSalary'] = (!empty($Record['PlayerSalary'])) ? json_decode($Record['PlayerSalary']) : new stdClass();
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

                if (in_array('MyTeamPlayer', $Params)) {

                    $this->db->select('SUTP.PlayerID,SUTP.MatchID');
                    $this->db->where('SUTP.MatchID', $Where['MatchID']);
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
                        return $b->TotalPoints - $a->TotalPoints;
                    });
                    usort($Bowler, function ($a, $b) {
                        return $b->TotalPoints - $a->TotalPoints;
                    });
                    usort($Wicketkipper, function ($a, $b) {
                        return $b->TotalPoints - $a->TotalPoints;
                    });
                    usort($Allrounder, function ($a, $b) {
                        return $b->TotalPoints - $a->TotalPoints;
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
                        return $b->TotalPoints - $a->TotalPoints;
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

    /*
      Description: To get player points
     */

    function getPlayerPointsCricketAPI($CronID, $MatchID = "") {

        ini_set('max_execution_time', 300);
        if (!empty($MatchID)) {
            $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,RoundID,SeriesID', array('MatchID' => $MatchID), true, 1, 1);
        } else {
            $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,RoundID,SeriesID', array('Filter' => 'Yesterday', 'StatusID' => array(2, 5, 10),
                'IsPlayerPointsUpdated' => 'No', 'OrderBy' => 'M.MatchStartDateTime',
                'Sequence' => 'DESC'), true, 1, 10);
        }

        /* Get Live Matches Data */
        if (!empty($LiveMatches)) {

            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(),
                    'LiveMatches' => $LiveMatches), JSON_UNESCAPED_UNICODE)));

            /* Get Points Data */
            /* $PointsDataArr = $this->cache->memcached->get('CricketPoints');
              if (empty($PointsDataArr)) {
              $PointsDataArr = $this->getPoints(array("StatusID" => 1));
              $this->cache->memcached->save('CricketPoints', $PointsDataArr, 3600 * 12); // Expire in every 12 hours
              } */
            $PointsDataArr = $this->getPoints(array("StatusID" => 1));

            $StatringXIArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'StatringXI');
            $CaptainPointMPArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'CaptainPointMP');
            $ViceCaptainPointMPArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'ViceCaptainPointMP');
            $BattingMinimumRunsArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'BattingMinimumRuns');
            $MinimumRunScoreStrikeRate = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'MinimumBallsScoreStrikeRate');
            $MinimumOverEconomyRate = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'MinimumOverEconomyRate');

            $MatchTypes = array('ODI' => 'PointsODI', 'List A' => 'PointsODI', 'T20' => 'PointsT20',
                'T20I' => 'PointsT20', 'Test' => 'PointsTEST', 'Woman ODI' => 'PointsODI',
                'Woman T20' => 'PointsT20');

            /* Sorting Keys */
            $PointsSortingKeys = array('SB', 'RUNS', '4s', '6s', 'STB', 'BTB', 'DUCK',
                'WK', 'MD', 'EB', 'BWB', 'RO', 'ST', 'CT');
            foreach ($LiveMatches['Data']['Records'] as $Value) {
                if (empty((array) $Value['MatchScoreDetails'])) continue;
                $MatchPlayersCache = array();
                //$this->cache->memcached->delete('getJoinedContestPlayerPoints_' . $Value['MatchID']);
                $StatringXIPoints = (isset($StatringXIArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($StatringXIArr[0][$MatchTypes[$Value['MatchType']]]) : "2";
                $CaptainPointMPPoints = (isset($CaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($CaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]]) : "2";
                $ViceCaptainPointMPPoints = (isset($ViceCaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($ViceCaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]]) : "1.5";
                $BattingMinimumRunsPoints = (isset($BattingMinimumRunsArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($BattingMinimumRunsArr[0][$MatchTypes[$Value['MatchType']]]) : "15";
                $MinimumRunScoreStrikeRate = (isset($MinimumRunScoreStrikeRate[0][$MatchTypes[$Value['MatchType']]])) ? strval($MinimumRunScoreStrikeRate[0][$MatchTypes[$Value['MatchType']]]) : "15";
                $MinimumOverEconomyRate = (isset($MinimumOverEconomyRate[0][$MatchTypes[$Value['MatchType']]])) ? strval($MinimumOverEconomyRate[0][$MatchTypes[$Value['MatchType']]]) : "2";

                /* Get Match Players */
                $MatchPlayers['Data']['Records'] = $this->cache->memcached->get('MatchPlayerPlaying11_' . $Value['MatchID']);
                if (empty($MatchPlayers['Data']['Records'])) {
                    $MatchPlayers = $this->getPlayers('PlayerIDLive,PlayerID,MatchID,PlayerRole', array('MatchID' => $Value['MatchID'], 'IsPlaying' => 'Yes'), true, 0);
                    $this->cache->memcached->save('MatchPlayerPlaying11_' . $Value['MatchID'], $MatchPlayers['Data']['Records'], 1800);
                }
                if (!$MatchPlayers) {
                    continue;
                }

                /* Get Match Live Score Data */
                $BatsmanPlayers = $BowlingPlayers = $FielderPlayers = $AllPalyers = $AllPalyers2 = $AllPlayeRoleData = array();

                foreach ($Value['MatchScoreDetails']['Innings'] as $PlayerID) {
                    foreach ($PlayerID['AllPlayingData'] as $PlayerKey => $PlayerSubValue) {
                        if (isset($PlayerSubValue['batting'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['batting']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['batting']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Role'] = $PlayerSubValue['batting']['Role'];
                            $AllPalyers[$PlayerKey]['Runs'] = $PlayerSubValue['batting']['Runs'];
                            $AllPalyers[$PlayerKey]['BallsFaced'] = $PlayerSubValue['batting']['BallsFaced'];
                            $AllPalyers[$PlayerKey]['Fours'] = $PlayerSubValue['batting']['Fours'];
                            $AllPalyers[$PlayerKey]['Sixes'] = $PlayerSubValue['batting']['Sixes'];
                            $AllPalyers[$PlayerKey]['HowOut'] = $PlayerSubValue['batting']['HowOut'];
                            $AllPalyers[$PlayerKey]['IsPlaying'] = $PlayerSubValue['batting']['IsPlaying'];
                            $AllPalyers[$PlayerKey]['StrikeRate'] = $PlayerSubValue['batting']['StrikeRate'];
                        }
                        if (isset($PlayerSubValue['bowling'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['bowling']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['bowling']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Overs'] = $PlayerSubValue['bowling']['Overs'];
                            $AllPalyers[$PlayerKey]['Maidens'] = $PlayerSubValue['bowling']['Maidens'];
                            $AllPalyers[$PlayerKey]['RunsConceded'] = $PlayerSubValue['bowling']['RunsConceded'];
                            $AllPalyers[$PlayerKey]['Wickets'] = $PlayerSubValue['bowling']['Wickets'];
                            $AllPalyers[$PlayerKey]['NoBalls'] = $PlayerSubValue['bowling']['NoBalls'];
                            $AllPalyers[$PlayerKey]['Wides'] = $PlayerSubValue['bowling']['Wides'];
                            $AllPalyers[$PlayerKey]['Economy'] = $PlayerSubValue['bowling']['Economy'];
                        }
                        if (isset($PlayerSubValue['fielding'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['fielding']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['fielding']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Catches'] = $PlayerSubValue['fielding']['Catches'];
                            $AllPalyers[$PlayerKey]['RunOutThrower'] = $PlayerSubValue['fielding']['RunOutThrower'];
                            $AllPalyers[$PlayerKey]['RunOutCatcher'] = $PlayerSubValue['fielding']['RunOutCatcher'];
                            $AllPalyers[$PlayerKey]['RunOutDirectHit'] = $PlayerSubValue['fielding']['RunOutDirectHit'];
                            $AllPalyers[$PlayerKey]['RunOutCatcherThrower'] = $PlayerSubValue['fielding']['RunOutCatcherThrower'];
                            $AllPalyers[$PlayerKey]['Stumping'] = $PlayerSubValue['fielding']['Stumping'];
                        }
                    }
                }

                if (isset($Value['MatchScoreDetails']['Innings2'])) {
                    foreach ($Value['MatchScoreDetails']['Innings2'] as $PlayerID) {
                        foreach ($PlayerID['AllPlayingData'] as $PlayerKey => $PlayerSubValue) {
                            if (isset($PlayerSubValue['batting'])) {
                                $AllPalyers2[$PlayerKey]['Name'] = $PlayerSubValue['batting']['Name'];
                                $AllPalyers2[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['batting']['PlayerIDLive'];
                                $AllPalyers2[$PlayerKey]['Role'] = $PlayerSubValue['batting']['Role'];
                                $AllPalyers2[$PlayerKey]['Runs'] = $PlayerSubValue['batting']['Runs'];
                                $AllPalyers2[$PlayerKey]['BallsFaced'] = $PlayerSubValue['batting']['BallsFaced'];
                                $AllPalyers2[$PlayerKey]['Fours'] = $PlayerSubValue['batting']['Fours'];
                                $AllPalyers2[$PlayerKey]['Sixes'] = $PlayerSubValue['batting']['Sixes'];
                                $AllPalyers2[$PlayerKey]['HowOut'] = $PlayerSubValue['batting']['HowOut'];
                                $AllPalyers2[$PlayerKey]['IsPlaying'] = $PlayerSubValue['batting']['IsPlaying'];
                                $AllPalyers2[$PlayerKey]['StrikeRate'] = $PlayerSubValue['batting']['StrikeRate'];
                            }
                            if (isset($PlayerSubValue['bowling'])) {
                                $AllPalyers2[$PlayerKey]['Name'] = $PlayerSubValue['bowling']['Name'];
                                $AllPalyers2[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['bowling']['PlayerIDLive'];
                                $AllPalyers2[$PlayerKey]['Overs'] = $PlayerSubValue['bowling']['Overs'];
                                $AllPalyers2[$PlayerKey]['Maidens'] = $PlayerSubValue['bowling']['Maidens'];
                                $AllPalyers2[$PlayerKey]['RunsConceded'] = $PlayerSubValue['bowling']['RunsConceded'];
                                $AllPalyers2[$PlayerKey]['Wickets'] = $PlayerSubValue['bowling']['Wickets'];
                                $AllPalyers2[$PlayerKey]['NoBalls'] = $PlayerSubValue['bowling']['NoBalls'];
                                $AllPalyers2[$PlayerKey]['Wides'] = $PlayerSubValue['bowling']['Wides'];
                                $AllPalyers2[$PlayerKey]['Economy'] = $PlayerSubValue['bowling']['Economy'];
                            }
                            if (isset($PlayerSubValue['fielding'])) {
                                $AllPalyers2[$PlayerKey]['Name'] = $PlayerSubValue['fielding']['Name'];
                                $AllPalyers2[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['fielding']['PlayerIDLive'];
                                $AllPalyers2[$PlayerKey]['Catches'] = $PlayerSubValue['fielding']['Catches'];
                                $AllPalyers2[$PlayerKey]['RunOutThrower'] = $PlayerSubValue['fielding']['RunOutThrower'];
                                $AllPalyers2[$PlayerKey]['RunOutCatcher'] = $PlayerSubValue['fielding']['RunOutCatcher'];
                                $AllPalyers2[$PlayerKey]['RunOutDirectHit'] = $PlayerSubValue['fielding']['RunOutDirectHit'];
                                $AllPalyers2[$PlayerKey]['RunOutCatcherThrower'] = $PlayerSubValue['fielding']['RunOutCatcherThrower'];
                                $AllPalyers2[$PlayerKey]['Stumping'] = $PlayerSubValue['fielding']['Stumping'];
                            }
                        }
                    }
                }
                if (empty($AllPalyers)) {
                    continue;
                }

                $AllPlayersLiveIds = array_keys($AllPalyers);
                $AllPlayersLiveIds2 = array_keys($AllPalyers2);
                foreach ($MatchPlayers['Data']['Records'] as $PlayerValue) {

                    $this->IsStrikeRate = $this->IsEconomyRate = $this->IsBattingState = $this->IsBowlingState = $PlayerTotalPoints = "0";
                    $this->defaultStrikeRatePoints = $this->defaultEconomyRatePoints = $this->defaultBattingPoints = $this->defaultBowlingPoints = $PointsData = $PointsData2 = array();
                    $PointsData['SB'] = array('PointsTypeGUID' => 'StatringXI', 'PointsTypeShortDescription' => 'SB',
                        'DefinedPoints' => $StatringXIPoints, 'ScoreValue' => "1",
                        'CalculatedPoints' => $StatringXIPoints);
                    $PointsData2['SB'] = array('PointsTypeGUID' => 'StatringXI',
                        'PointsTypeShortDescription' => 'SB', 'DefinedPoints' => $StatringXIPoints,
                        'ScoreValue' => "1", 'CalculatedPoints' => 0);
                    $ScoreData = $AllPalyers[$PlayerValue['PlayerIDLive']];

                    /* To Check Player Is Played Or Not */
                    if (in_array($PlayerValue['PlayerIDLive'], $AllPlayersLiveIds) && !empty($ScoreData)) {
                        foreach ($PointsDataArr['Data']['Records'] as $PointValue) {

                            /* if (IS_VICECAPTAIN) {
                              if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                              'StatringXI', 'ViceCaptainPointMP'))) continue;
                              } else {
                              if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                              'StatringXI'))) continue;
                              } */
                            $allKeys = array_keys($ScoreData);
                            if (($DeleteKey = array_search('Name', $allKeys)) !== false) {
                                unset($allKeys[$DeleteKey]);
                            }
                            if (($DeleteKey = array_search('PlayerIDLive', $allKeys)) !== false) {
                                unset($allKeys[$DeleteKey]);
                            }


                            /** calculate points * */
                            foreach ($allKeys as $ScoreValue) {
                                $calculatePoints = $this->calculatePoints($PointValue, $Value['MatchType'], $MinimumRunScoreStrikeRate, @$ScoreData[$PointValue['PointsScoringField']], @$ScoreData['BallsFaced'], @$ScoreData['Overs'], @$ScoreData['Runs'], $MinimumOverEconomyRate, $PlayerValue['PlayerRole'], $ScoreData['HowOut']);
                                if (is_array($calculatePoints)) {
                                    $PointsData[$calculatePoints['PointsTypeShortDescription']] = array('PointsTypeGUID' => $calculatePoints['PointsTypeGUID'],
                                        'PointsTypeShortDescription' => $calculatePoints['PointsTypeShortDescription'],
                                        'DefinedPoints' => strval($calculatePoints['DefinedPoints']),
                                        'ScoreValue' => strval($calculatePoints['ScoreValue']),
                                        'CalculatedPoints' => strval($calculatePoints['CalculatedPoints']));
                                }
                            }
                        }


                        /* Manage Single Strike Rate & Economy Rate & Bowling & Batting State */
                        if ($this->IsStrikeRate == 0) {
                            $PointsData['STB'] = $this->defaultStrikeRatePoints;
                        }
                        if ($this->IsEconomyRate == 0) {
                            $PointsData['EB'] = $this->defaultEconomyRatePoints;
                        }
                        if ($this->IsBattingState == 0) {
                            $PointsData['BTB'] = $this->defaultBattingPoints;
                        }
                        if ($this->IsBowlingState == 0) {
                            $PointsData['BWB'] = $this->defaultBowlingPoints;
                        }
                    } else {
                        $PointsData['SB'] = array('PointsTypeGUID' => 'StatringXI',
                            'PointsTypeShortDescription' => 'SB', 'DefinedPoints' => $StatringXIPoints,
                            'ScoreValue' => "1", 'CalculatedPoints' => $StatringXIPoints);
                        foreach ($PointsDataArr['Data']['Records'] as $PointValue) {
                            if (IS_VICECAPTAIN) {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                                            'StatringXI', 'ViceCaptainPointMP'))) continue;
                            } else {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                                            'StatringXI'))) continue;
                            }
                            if (in_array($PointValue['PointsTypeGUID'], array('StrikeRate50N74.99', 'StrikeRate75N99.99',
                                        'StrikeRate100N149.99', 'StrikeRate150N199.99',
                                        'StrikeRate200NMore', 'EconomyRate5.01N7.00Balls',
                                        'EconomyRate5.01N8.00Balls', 'EconomyRate7.01N10.00Balls',
                                        'EconomyRate8.01N10.00Balls', 'EconomyRate10.01N12.00Balls',
                                        'EconomyRateAbove12.1Balls', 'FourWickets',
                                        'FiveWickets', 'SixWickets', 'SevenWicketsMore',
                                        'EightWicketsMore', 'For50runs', 'For100runs',
                                        'For150runs', 'For200runs', 'For300runs',
                                        'MinimumRunScoreStrikeRate', 'MinimumOverEconomyRate'))) continue;
                            $PointsData[$PointValue['PointsTypeShortDescription']] = array('PointsTypeGUID' => $PointValue['PointsTypeGUID'],
                                'PointsTypeShortDescription' => $PointValue['PointsTypeShortDescription'],
                                'DefinedPoints' => "0", 'ScoreValue' => "0",
                                'CalculatedPoints' => "0");
                        }
                    }

                    /* Sort Points Keys Data */
                    $OrderedArray = array();
                    foreach ($PointsSortingKeys as $SortValue) {
                        unset($PointsData[$SortValue]['PointsTypeShortDescription']);
                        $OrderedArray[] = $PointsData[$SortValue];
                    }
                    $PointsData = $OrderedArray;

                    /* Calculate Total Points */
                    if (!empty($PointsData)) {
                        foreach ($PointsData as $PointValue) {
                            if ($PointValue['CalculatedPoints'] > 0) {
                                $PlayerTotalPoints += $PointValue['CalculatedPoints'];
                            } else {
                                $PlayerTotalPoints = $PlayerTotalPoints - abs($PointValue['CalculatedPoints']);
                            }
                        }
                    }

                    /** second inning test point calculation * */
                    if (strtoupper($Value['MatchType']) == "TEST") {
                        $ScoreData2 = $AllPalyers2[$PlayerValue['PlayerIDLive']];

                        /* To Check Player Is Played Or Not */
                        if (in_array($PlayerValue['PlayerIDLive'], $AllPlayersLiveIds2) && !empty($ScoreData2)) {
                            foreach ($PointsDataArr['Data']['Records'] as $PointValue) {

                                $allKeys2 = array_keys($ScoreData2);
                                if (($DeleteKey = array_search('Name', $allKeys2)) !== false) {
                                    unset($allKeys2[$DeleteKey]);
                                }
                                if (($DeleteKey = array_search('PlayerIDLive', $allKeys2)) !== false) {
                                    unset($allKeys2[$DeleteKey]);
                                }

                                /** calculate points * */
                                foreach ($allKeys2 as $ScoreValue) {
                                    $calculatePoints = $this->calculatePoints($PointValue, $Value['MatchType'], $MinimumRunScoreStrikeRate, @$ScoreData2[$PointValue['PointsScoringField']], @$ScoreData2['BallsFaced'], @$ScoreData2['Overs'], @$ScoreData2['Runs'], $MinimumOverEconomyRate, $PlayerValue['PlayerRole'], $ScoreData2['HowOut']);
                                    if (is_array($calculatePoints)) {
                                        $PointsData2[$calculatePoints['PointsTypeShortDescription']] = array('PointsTypeGUID' => $calculatePoints['PointsTypeGUID'],
                                            'PointsTypeShortDescription' => $calculatePoints['PointsTypeShortDescription'],
                                            'DefinedPoints' => strval($calculatePoints['DefinedPoints']),
                                            'ScoreValue' => strval($calculatePoints['ScoreValue']),
                                            'CalculatedPoints' => strval($calculatePoints['CalculatedPoints']));
                                    }
                                }
                            }

                            /* Manage Single Strike Rate & Economy Rate & Bowling & Batting State */
                            if ($this->IsStrikeRate == 0) {
                                $PointsData2['STB'] = $this->defaultStrikeRatePoints;
                            }
                            if ($this->IsEconomyRate == 0) {
                                $PointsData2['EB'] = $this->defaultEconomyRatePoints;
                            }
                            if ($this->IsBattingState == 0) {
                                $PointsData2['BTB'] = $this->defaultBattingPoints;
                            }
                            if ($this->IsBowlingState == 0) {
                                $PointsData2['BWB'] = $this->defaultBowlingPoints;
                            }
                        }

                        /* Sort Points Keys Data */
                        $OrderedArray2 = array();
                        foreach ($PointsSortingKeys as $SortValue) {
                            unset($PointsData2[$SortValue]['PointsTypeShortDescription']);
                            $OrderedArray2[] = $PointsData2[$SortValue];
                        }
                        $PointsData2 = $OrderedArray2;

                        /* Calculate Total Points */
                        if (!empty($PointsData2)) {
                            foreach ($PointsData2 as $PointValue) {
                                if ($PointValue['CalculatedPoints'] > 0) {
                                    $PlayerTotalPoints += $PointValue['CalculatedPoints'];
                                } else {
                                    $PlayerTotalPoints = $PlayerTotalPoints - abs($PointValue['CalculatedPoints']);
                                }
                            }
                        }
                    }


                    /* Update Player Points Data */
                    $UpdateData = array(
                        'TotalPoints' => $PlayerTotalPoints,
                        'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : null,
                        'PointsDataSecondInng' => (!empty($PointsData2)) ? json_encode($PointsData2) : null
                    );
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->where('PlayerID', $PlayerValue['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('sports_team_players', $UpdateData);
                    $MatchPlayersCache[] = array(
                        'PlayerGUID' => $PlayerValue['PlayerGUID'],
                        'PlayerID' => $PlayerValue['PlayerID'],
                        'TotalPoints' => $PlayerTotalPoints,
                        'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : "",
                        'PointsDataSecondInng' => (!empty($PointsData2)) ? json_encode($PointsData2) : ""
                    );
                }

                $MatchPlayers = $this->db->query('SELECT P.PlayerGUID,TP.PlayerID,TP.TotalPoints,TP.PointsData,TP.PointsDataSecondInng FROM sports_players P,sports_team_players TP WHERE P.PlayerID = TP.PlayerID AND TP.MatchID = ' . $Value['MatchID'])->result_array();
                $this->cache->memcached->save('MatchPlayerPoint_' . $Value['MatchID'], $MatchPlayers, 3600 * 4);
                // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
                // $this->db->cache_delete('admin', 'matches'); //Delete Cache

                /* Update Match Player Points Status */
                if ($Value['StatusID'] == 5) {
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', array('IsPlayerPointsUpdated' => 'Yes'));

                    /* Update Final player points before complete match */
                    /* $CronID = $this->Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
                      $this->getJoinedContestTeamPoints($CronID, $Value['MatchID']);
                      $this->Utility_model->updateCronLogs($CronID); */
                }

                /** auction draft series wise player point update * */
                if (!empty($Value['RoundID'])) {
                    $this->updateAuctionDraftPlayerPoint($Value['SeriesID'], $Value['RoundID']);
                }
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
    }

    /*
      Description: To get player points
     */

    function getPlayerPointsEntity($CronID, $MatchID = "") {

        ini_set('max_execution_time', 300);
        if (!empty($MatchID)) {
            $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,RoundID,SeriesID', array('MatchID' => $MatchID), true, 1, 1);
        } else {
            $LiveMatches = $this->getMatches('MatchID,MatchType,MatchScoreDetails,StatusID,IsPlayerPointsUpdated,RoundID,SeriesID', array('Filter' => 'Yesterday', 'StatusID' => array(2, 5, 10),
                'IsPlayerPointsUpdated' => 'No', 'OrderBy' => 'M.MatchStartDateTime',
                'Sequence' => 'DESC'), true, 1, 10);
        }

        /* Get Live Matches Data */
        if (!empty($LiveMatches)) {

            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(),
                    'LiveMatches' => $LiveMatches), JSON_UNESCAPED_UNICODE)));

            /* Get Points Data */
            $PointsDataArr = $this->cache->memcached->get('CricketPoints');
            if (empty($PointsDataArr)) {
                $PointsDataArr = $this->getPoints(array("StatusID" => 1));
                $this->cache->memcached->save('CricketPoints', $PointsDataArr, 3600 * 12); // Expire in every 12 hours
            }

            $StatringXIArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'StatringXI');
            $CaptainPointMPArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'CaptainPointMP');
            $ViceCaptainPointMPArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'ViceCaptainPointMP');
            $BattingMinimumRunsArr = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'BattingMinimumRuns');
            $MinimumRunScoreStrikeRate = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'MinimumBallsScoreStrikeRate');
            $MinimumOverEconomyRate = $this->findSubArray($PointsDataArr['Data']['Records'], 'PointsTypeGUID', 'MinimumOverEconomyRate');

            $MatchTypes = array('ODI' => 'PointsODI', 'List A' => 'PointsODI', 'T20' => 'PointsT20',
                'T20I' => 'PointsT20', 'Test' => 'PointsTEST', 'Woman ODI' => 'PointsODI',
                'Woman T20' => 'PointsT20');

            /* Sorting Keys */
            $PointsSortingKeys = array('SB', 'RUNS', '4s', '6s', 'STB', 'BTB', 'DUCK',
                'WK', 'MD', 'EB', 'BWB', 'RO', 'ST', 'CT', 'ROCT');
            foreach ($LiveMatches['Data']['Records'] as $Value) {
                if (empty((array) $Value['MatchScoreDetails'])) continue;
                $MatchPlayersCache = array();
                //$this->cache->memcached->delete('getJoinedContestPlayerPoints_' . $Value['MatchID']);
                $StatringXIPoints = (isset($StatringXIArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($StatringXIArr[0][$MatchTypes[$Value['MatchType']]]) : "2";
                $CaptainPointMPPoints = (isset($CaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($CaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]]) : "2";
                $ViceCaptainPointMPPoints = (isset($ViceCaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($ViceCaptainPointMPArr[0][$MatchTypes[$Value['MatchType']]]) : "1.5";
                $BattingMinimumRunsPoints = (isset($BattingMinimumRunsArr[0][$MatchTypes[$Value['MatchType']]])) ? strval($BattingMinimumRunsArr[0][$MatchTypes[$Value['MatchType']]]) : "15";
                $MinimumRunScoreStrikeRate = (isset($MinimumRunScoreStrikeRate[0][$MatchTypes[$Value['MatchType']]])) ? strval($MinimumRunScoreStrikeRate[0][$MatchTypes[$Value['MatchType']]]) : "10";
                $MinimumOverEconomyRate = (isset($MinimumOverEconomyRate[0][$MatchTypes[$Value['MatchType']]])) ? strval($MinimumOverEconomyRate[0][$MatchTypes[$Value['MatchType']]]) : "1";

                /* Get Match Players */
                $MatchPlayers['Data']['Records'] = $this->cache->memcached->get('MatchPlayerPlaying11_' . $Value['MatchID']);
                if (empty($MatchPlayers['Data']['Records'])) {
                    $MatchPlayers = $this->getPlayers('PlayerIDLive,PlayerID,MatchID,PlayerRole', array('MatchID' => $Value['MatchID'], 'IsPlaying' => 'Yes'), true, 0);
                    $this->cache->memcached->save('MatchPlayerPlaying11_' . $Value['MatchID'], $MatchPlayers['Data']['Records'], 1800);
                }
                if (!$MatchPlayers) {
                    continue;
                }

                /* Get Match Live Score Data */
                $BatsmanPlayers = $BowlingPlayers = $FielderPlayers = $AllPalyers = $AllPalyers2 = $AllPlayeRoleData = array();

                foreach ($Value['MatchScoreDetails']['Innings'] as $PlayerID) {
                    foreach ($PlayerID['AllPlayingData'] as $PlayerKey => $PlayerSubValue) {
                        if (isset($PlayerSubValue['batting'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['batting']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['batting']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Role'] = $PlayerSubValue['batting']['Role'];
                            $AllPalyers[$PlayerKey]['Runs'] = $PlayerSubValue['batting']['Runs'];
                            $AllPalyers[$PlayerKey]['BallsFaced'] = $PlayerSubValue['batting']['BallsFaced'];
                            $AllPalyers[$PlayerKey]['Fours'] = $PlayerSubValue['batting']['Fours'];
                            $AllPalyers[$PlayerKey]['Sixes'] = $PlayerSubValue['batting']['Sixes'];
                            $AllPalyers[$PlayerKey]['HowOut'] = $PlayerSubValue['batting']['HowOut'];
                            $AllPalyers[$PlayerKey]['IsPlaying'] = $PlayerSubValue['batting']['IsPlaying'];
                            $AllPalyers[$PlayerKey]['StrikeRate'] = $PlayerSubValue['batting']['StrikeRate'];
                        }
                        if (isset($PlayerSubValue['bowling'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['bowling']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['bowling']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Overs'] = $PlayerSubValue['bowling']['Overs'];
                            $AllPalyers[$PlayerKey]['Maidens'] = $PlayerSubValue['bowling']['Maidens'];
                            $AllPalyers[$PlayerKey]['RunsConceded'] = $PlayerSubValue['bowling']['RunsConceded'];
                            $AllPalyers[$PlayerKey]['Wickets'] = $PlayerSubValue['bowling']['Wickets'];
                            $AllPalyers[$PlayerKey]['NoBalls'] = $PlayerSubValue['bowling']['NoBalls'];
                            $AllPalyers[$PlayerKey]['Wides'] = $PlayerSubValue['bowling']['Wides'];
                            $AllPalyers[$PlayerKey]['Economy'] = $PlayerSubValue['bowling']['Economy'];
                        }
                        if (isset($PlayerSubValue['fielding'])) {
                            $AllPalyers[$PlayerKey]['Name'] = $PlayerSubValue['fielding']['Name'];
                            $AllPalyers[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['fielding']['PlayerIDLive'];
                            $AllPalyers[$PlayerKey]['Catches'] = $PlayerSubValue['fielding']['Catches'];
                            $AllPalyers[$PlayerKey]['RunOutThrower'] = $PlayerSubValue['fielding']['RunOutThrower'];
                            $AllPalyers[$PlayerKey]['RunOutCatcher'] = $PlayerSubValue['fielding']['RunOutCatcher'];
                            $AllPalyers[$PlayerKey]['RunOutDirectHit'] = $PlayerSubValue['fielding']['RunOutDirectHit'];
                            $AllPalyers[$PlayerKey]['RunOutCatcherThrower'] = $PlayerSubValue['fielding']['RunOutCatcherThrower'];
                            $AllPalyers[$PlayerKey]['Stumping'] = $PlayerSubValue['fielding']['Stumping'];
                        }
                    }
                }

                if (isset($Value['MatchScoreDetails']['Innings2'])) {
                    foreach ($Value['MatchScoreDetails']['Innings2'] as $PlayerID) {
                        foreach ($PlayerID['AllPlayingData'] as $PlayerKey => $PlayerSubValue) {
                            if (isset($PlayerSubValue['batting'])) {
                                $AllPalyers2[$PlayerKey]['Name'] = $PlayerSubValue['batting']['Name'];
                                $AllPalyers2[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['batting']['PlayerIDLive'];
                                $AllPalyers2[$PlayerKey]['Role'] = $PlayerSubValue['batting']['Role'];
                                $AllPalyers2[$PlayerKey]['Runs'] = $PlayerSubValue['batting']['Runs'];
                                $AllPalyers2[$PlayerKey]['BallsFaced'] = $PlayerSubValue['batting']['BallsFaced'];
                                $AllPalyers2[$PlayerKey]['Fours'] = $PlayerSubValue['batting']['Fours'];
                                $AllPalyers2[$PlayerKey]['Sixes'] = $PlayerSubValue['batting']['Sixes'];
                                $AllPalyers2[$PlayerKey]['HowOut'] = $PlayerSubValue['batting']['HowOut'];
                                $AllPalyers2[$PlayerKey]['IsPlaying'] = $PlayerSubValue['batting']['IsPlaying'];
                                $AllPalyers2[$PlayerKey]['StrikeRate'] = $PlayerSubValue['batting']['StrikeRate'];
                            }
                            if (isset($PlayerSubValue['bowling'])) {
                                $AllPalyers2[$PlayerKey]['Name'] = $PlayerSubValue['bowling']['Name'];
                                $AllPalyers2[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['bowling']['PlayerIDLive'];
                                $AllPalyers2[$PlayerKey]['Overs'] = $PlayerSubValue['bowling']['Overs'];
                                $AllPalyers2[$PlayerKey]['Maidens'] = $PlayerSubValue['bowling']['Maidens'];
                                $AllPalyers2[$PlayerKey]['RunsConceded'] = $PlayerSubValue['bowling']['RunsConceded'];
                                $AllPalyers2[$PlayerKey]['Wickets'] = $PlayerSubValue['bowling']['Wickets'];
                                $AllPalyers2[$PlayerKey]['NoBalls'] = $PlayerSubValue['bowling']['NoBalls'];
                                $AllPalyers2[$PlayerKey]['Wides'] = $PlayerSubValue['bowling']['Wides'];
                                $AllPalyers2[$PlayerKey]['Economy'] = $PlayerSubValue['bowling']['Economy'];
                            }
                            if (isset($PlayerSubValue['fielding'])) {
                                $AllPalyers2[$PlayerKey]['Name'] = $PlayerSubValue['fielding']['Name'];
                                $AllPalyers2[$PlayerKey]['PlayerIDLive'] = $PlayerSubValue['fielding']['PlayerIDLive'];
                                $AllPalyers2[$PlayerKey]['Catches'] = $PlayerSubValue['fielding']['Catches'];
                                $AllPalyers2[$PlayerKey]['RunOutThrower'] = $PlayerSubValue['fielding']['RunOutThrower'];
                                $AllPalyers2[$PlayerKey]['RunOutCatcher'] = $PlayerSubValue['fielding']['RunOutCatcher'];
                                $AllPalyers2[$PlayerKey]['RunOutDirectHit'] = $PlayerSubValue['fielding']['RunOutDirectHit'];
                                $AllPalyers2[$PlayerKey]['RunOutCatcherThrower'] = $PlayerSubValue['fielding']['RunOutCatcherThrower'];
                                $AllPalyers2[$PlayerKey]['Stumping'] = $PlayerSubValue['fielding']['Stumping'];
                            }
                        }
                    }
                }
                if (empty($AllPalyers)) {
                    continue;
                }

                $AllPlayersLiveIds = array_keys($AllPalyers);
                $AllPlayersLiveIds2 = array_keys($AllPalyers2);
                foreach ($MatchPlayers['Data']['Records'] as $PlayerValue) {

                    $this->IsStrikeRate = $this->IsEconomyRate = $this->IsBattingState = $this->IsBowlingState = $PlayerTotalPoints = "0";
                    $this->defaultStrikeRatePoints = $this->defaultEconomyRatePoints = $this->defaultBattingPoints = $this->defaultBowlingPoints = $PointsData = $PointsData2 = array();
                    $PointsData['SB'] = array('PointsTypeGUID' => 'StatringXI', 'PointsTypeShortDescription' => 'SB',
                        'DefinedPoints' => $StatringXIPoints, 'ScoreValue' => "1",
                        'CalculatedPoints' => $StatringXIPoints);
                    $PointsData2['SB'] = array('PointsTypeGUID' => 'StatringXI',
                        'PointsTypeShortDescription' => 'SB', 'DefinedPoints' => $StatringXIPoints,
                        'ScoreValue' => "1", 'CalculatedPoints' => 0);
                    $ScoreData = $AllPalyers[$PlayerValue['PlayerIDLive']];

                    /* To Check Player Is Played Or Not */
                    if (in_array($PlayerValue['PlayerIDLive'], $AllPlayersLiveIds) && !empty($ScoreData)) {
                        foreach ($PointsDataArr['Data']['Records'] as $PointValue) {

                            /* if (IS_VICECAPTAIN) {
                              if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                              'StatringXI', 'ViceCaptainPointMP'))) continue;
                              } else {
                              if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                              'StatringXI'))) continue;
                              } */
                            $allKeys = array_keys($ScoreData);
                            if (($DeleteKey = array_search('Name', $allKeys)) !== false) {
                                unset($allKeys[$DeleteKey]);
                            }
                            if (($DeleteKey = array_search('PlayerIDLive', $allKeys)) !== false) {
                                unset($allKeys[$DeleteKey]);
                            }

                            /** calculate points * */
                            foreach ($allKeys as $ScoreValue) {
                                $calculatePoints = $this->calculatePoints($PointValue, $Value['MatchType'], $MinimumRunScoreStrikeRate, @$ScoreData[$PointValue['PointsScoringField']], @$ScoreData['BallsFaced'], @$ScoreData['Overs'], @$ScoreData['Runs'], $MinimumOverEconomyRate, $PlayerValue['PlayerRole'], $ScoreData['HowOut']);
                                if (is_array($calculatePoints)) {
                                    $PointsData[$calculatePoints['PointsTypeShortDescription']] = array('PointsTypeGUID' => $calculatePoints['PointsTypeGUID'],
                                        'PointsTypeShortDescription' => $calculatePoints['PointsTypeShortDescription'],
                                        'DefinedPoints' => strval($calculatePoints['DefinedPoints']),
                                        'ScoreValue' => strval($calculatePoints['ScoreValue']),
                                        'CalculatedPoints' => strval($calculatePoints['CalculatedPoints']));
                                }
                            }
                        }

                        /* Manage Single Strike Rate & Economy Rate & Bowling & Batting State */
                        if ($this->IsStrikeRate == 0) {
                            $PointsData['STB'] = $this->defaultStrikeRatePoints;
                        }
                        if ($this->IsEconomyRate == 0) {
                            $PointsData['EB'] = $this->defaultEconomyRatePoints;
                        }
                        if ($this->IsBattingState == 0) {
                            $PointsData['BTB'] = $this->defaultBattingPoints;
                        }
                        if ($this->IsBowlingState == 0) {
                            $PointsData['BWB'] = $this->defaultBowlingPoints;
                        }
                    } else {
                        $PointsData['SB'] = array('PointsTypeGUID' => 'StatringXI',
                            'PointsTypeShortDescription' => 'SB', 'DefinedPoints' => $StatringXIPoints,
                            'ScoreValue' => "1", 'CalculatedPoints' => $StatringXIPoints);
                        foreach ($PointsDataArr['Data']['Records'] as $PointValue) {
                            if (IS_VICECAPTAIN) {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                                            'StatringXI', 'ViceCaptainPointMP'))) continue;
                            } else {
                                if (in_array($PointValue['PointsTypeGUID'], array('BattingMinimumRuns', 'CaptainPointMP',
                                            'StatringXI'))) continue;
                            }
                            if (in_array($PointValue['PointsTypeGUID'], array('StrikeRate50N74.99', 'StrikeRate75N99.99',
                                        'StrikeRate100N149.99', 'StrikeRate150N199.99',
                                        'StrikeRate200NMore', 'EconomyRate5.01N7.00Balls',
                                        'EconomyRate5.01N8.00Balls', 'EconomyRate7.01N10.00Balls',
                                        'EconomyRate8.01N10.00Balls', 'EconomyRate10.01N12.00Balls',
                                        'EconomyRateAbove12.1Balls', 'FourWickets',
                                        'FiveWickets', 'SixWickets', 'SevenWicketsMore',
                                        'EightWicketsMore', 'For50runs', 'For100runs',
                                        'For150runs', 'For200runs', 'For300runs',
                                        'MinimumRunScoreStrikeRate', 'MinimumOverEconomyRate'))) continue;
                            $PointsData[$PointValue['PointsTypeShortDescription']] = array('PointsTypeGUID' => $PointValue['PointsTypeGUID'],
                                'PointsTypeShortDescription' => $PointValue['PointsTypeShortDescription'],
                                'DefinedPoints' => "0", 'ScoreValue' => "0",
                                'CalculatedPoints' => "0");
                        }
                    }
                    /* Sort Points Keys Data */
                    $OrderedArray = array();
                    foreach ($PointsSortingKeys as $SortValue) {
                        unset($PointsData[$SortValue]['PointsTypeShortDescription']);
                        $OrderedArray[] = $PointsData[$SortValue];
                    }
                    $PointsData = $OrderedArray;


                    /* Calculate Total Points */
                    if (!empty($PointsData)) {
                        foreach ($PointsData as $PointValue) {
                            if ($PointValue['CalculatedPoints'] > 0) {
                                $PlayerTotalPoints += $PointValue['CalculatedPoints'];
                            } else {
                                $PlayerTotalPoints = $PlayerTotalPoints - abs($PointValue['CalculatedPoints']);
                            }
                        }
                    }

                    /** second inning test point calculation * */
                    if (strtoupper($Value['MatchType']) == "TEST") {
                        $ScoreData2 = $AllPalyers2[$PlayerValue['PlayerIDLive']];

                        /* To Check Player Is Played Or Not */
                        if (in_array($PlayerValue['PlayerIDLive'], $AllPlayersLiveIds2) && !empty($ScoreData2)) {
                            foreach ($PointsDataArr['Data']['Records'] as $PointValue) {

                                $allKeys2 = array_keys($ScoreData2);
                                if (($DeleteKey = array_search('Name', $allKeys2)) !== false) {
                                    unset($allKeys2[$DeleteKey]);
                                }
                                if (($DeleteKey = array_search('PlayerIDLive', $allKeys2)) !== false) {
                                    unset($allKeys2[$DeleteKey]);
                                }

                                /** calculate points * */
                                foreach ($allKeys2 as $ScoreValue) {
                                    $calculatePoints = $this->calculatePoints($PointValue, $Value['MatchType'], $MinimumRunScoreStrikeRate, @$ScoreData2[$PointValue['PointsScoringField']], @$ScoreData2['BallsFaced'], @$ScoreData2['Overs'], @$ScoreData2['Runs'], $MinimumOverEconomyRate, $PlayerValue['PlayerRole'], $ScoreData2['HowOut']);
                                    if (is_array($calculatePoints)) {
                                        $PointsData2[$calculatePoints['PointsTypeShortDescription']] = array('PointsTypeGUID' => $calculatePoints['PointsTypeGUID'],
                                            'PointsTypeShortDescription' => $calculatePoints['PointsTypeShortDescription'],
                                            'DefinedPoints' => strval($calculatePoints['DefinedPoints']),
                                            'ScoreValue' => strval($calculatePoints['ScoreValue']),
                                            'CalculatedPoints' => strval($calculatePoints['CalculatedPoints']));
                                    }
                                }
                            }

                            /* Manage Single Strike Rate & Economy Rate & Bowling & Batting State */
                            if ($this->IsStrikeRate == 0) {
                                $PointsData2['STB'] = $this->defaultStrikeRatePoints;
                            }
                            if ($this->IsEconomyRate == 0) {
                                $PointsData2['EB'] = $this->defaultEconomyRatePoints;
                            }
                            if ($this->IsBattingState == 0) {
                                $PointsData2['BTB'] = $this->defaultBattingPoints;
                            }
                            if ($this->IsBowlingState == 0) {
                                $PointsData2['BWB'] = $this->defaultBowlingPoints;
                            }
                        }

                        /* Sort Points Keys Data */
                        $OrderedArray2 = array();
                        foreach ($PointsSortingKeys as $SortValue) {
                            unset($PointsData2[$SortValue]['PointsTypeShortDescription']);
                            $OrderedArray2[] = $PointsData2[$SortValue];
                        }
                        $PointsData2 = $OrderedArray2;

                        /* Calculate Total Points */
                        if (!empty($PointsData2)) {
                            foreach ($PointsData2 as $PointValue) {
                                if ($PointValue['CalculatedPoints'] > 0) {
                                    $PlayerTotalPoints += $PointValue['CalculatedPoints'];
                                } else {
                                    $PlayerTotalPoints = $PlayerTotalPoints - abs($PointValue['CalculatedPoints']);
                                }
                            }
                        }
                    }


                    /* Update Player Points Data */
                    $UpdateData = array(
                        'TotalPoints' => $PlayerTotalPoints,
                        'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : null,
                        'PointsDataSecondInng' => (!empty($PointsData2)) ? json_encode($PointsData2) : null
                    );

                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->where('PlayerID', $PlayerValue['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('sports_team_players', $UpdateData);
                    $MatchPlayersCache[] = array(
                        'PlayerGUID' => $PlayerValue['PlayerGUID'],
                        'PlayerID' => $PlayerValue['PlayerID'],
                        'TotalPoints' => $PlayerTotalPoints,
                        'PointsData' => (!empty($PointsData)) ? json_encode($PointsData) : "",
                        'PointsDataSecondInng' => (!empty($PointsData2)) ? json_encode($PointsData2) : ""
                    );
                }

                $MatchPlayers = $this->db->query('SELECT P.PlayerGUID,TP.PlayerID,TP.TotalPoints,TP.PointsData,TP.PointsDataSecondInng FROM sports_players P,sports_team_players TP WHERE P.PlayerID = TP.PlayerID AND TP.MatchID = ' . $Value['MatchID'])->result_array();
                $this->cache->memcached->save('MatchPlayerPoint_' . $Value['MatchID'], $MatchPlayers, 3600 * 4);
                // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
                // $this->db->cache_delete('admin', 'matches'); //Delete Cache

                /* Update Match Player Points Status */
                if ($Value['StatusID'] == 5) {
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', array('IsPlayerPointsUpdated' => 'Yes'));

                    /* Update Final player points before complete match */
                    /* $CronID = $this->Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
                      $this->getJoinedContestTeamPoints($CronID, $Value['MatchID']);
                      $this->Utility_model->updateCronLogs($CronID); */
                }

                /** auction draft series wise player point update * */
                if (!empty($Value['RoundID'])) {
                    $this->updateAuctionDraftPlayerPoint($Value['SeriesID'], $Value['RoundID']);
                }
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
    }

    /*
      Description: Update series wise auction draft player point
     */

    function updateAuctionDraftPlayerPoint($SeriesID, $RoundID) {

        $this->db->select("MatchID,SeriesID,PlayerID,SUM(TotalPoints) as TotalPoints,PlayerRole");
        $this->db->from('sports_team_players');
        $this->db->where("SeriesID", $SeriesID);
        $this->db->where("RoundID", $RoundID);
        $this->db->group_by("PlayerID");
        $Query = $this->db->get();
        $Rows = $Query->num_rows();
        if ($Rows > 0) {
            $Players = $Query->result_array();
            foreach ($Players as $Player) {

                $Query = $this->db->query('SELECT PlayerID FROM sports_auction_draft_player_point WHERE SeriesID = "' . $SeriesID . '" AND RoundID = "' . $RoundID . '" AND PlayerID="' . $Player['PlayerID'] . '" LIMIT 1');
                $PlayerIDExists = ($Query->num_rows() > 0) ? true : false;
                if ($PlayerIDExists) {
                    $UpdateData = array(
                        "TotalPoints" => $Player['TotalPoints'],
                        "UpdateDateTime" => date('Y-m-d H:i:s')
                    );
                    $this->db->where('SeriesID', $SeriesID);
                    $this->db->where('RoundID', $RoundID);
                    $this->db->where('PlayerID', $Player['PlayerID']);
                    $this->db->limit(1);
                    $this->db->update('sports_auction_draft_player_point', $UpdateData);
                } else {
                    $InsertData = array(
                        "TotalPoints" => $Player['TotalPoints'],
                        "UpdateDateTime" => date('Y-m-d H:i:s'),
                        "SeriesID" => $SeriesID,
                        "RoundID" => $RoundID,
                        "ContestID" => 0,
                        "PlayerID" => $Player['PlayerID'],
                        "PlayerRole" => $Player['PlayerRole']
                    );
                    $this->db->insert('sports_auction_draft_player_point', $InsertData);
                }
            }
        }
        return true;
    }

    /*
      Description: To get joined contest player points
     */

    function getJoinedContestPlayerPoints($CronID, $StatusArr = array(2)) {

        ini_set('max_execution_time', 300);

        /* To Get Joined Running Contest */
        $Contests = $this->Contest_model->getJoinedContests('MatchTypeID,ContestID,UserID,MatchID,UserTeamID', array('StatusID' => $StatusArr, 'Filter' => 'YesterdayToday', "LeagueType" => "Dfs"), true, 0);
        if (!empty($Contests['Data']['Records'])) {

            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(),
                    'Contests' => $Contests), JSON_UNESCAPED_UNICODE)));

            /* Get Vice Captain Points */
            $ViceCaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "ViceCaptainPointMP" LIMIT 1')->row_array();

            /* Get Captain Points */
            $CaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "CaptainPointMP" LIMIT 1')->row_array();

            /* Match Types */
            $MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20', '4' => 'PointsT20',
                '5' => 'PointsTEST', '7' => 'PointsT20', '9' => 'PointsODI');
            $ContestIDArray = array();
            foreach ($Contests['Data']['Records'] as $Value) {
                $ContestIDArray[] = $Value['ContestID'];
                /* Player Points Multiplier */
                if (IS_VICECAPTAIN) {
                    $PositionPointsMultiplier = array('ViceCaptain' => $ViceCaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],
                        'Captain' => $CaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],
                        'Player' => 1);
                } else {
                    $PositionPointsMultiplier = array('Captain' => $CaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],
                        'Player' => 1);
                }

                $UserTotalPoints = 0;

                /* To Get Match Players */
                $MatchPlayers = $this->getPlayers('PlayerID,TotalPoints', array('MatchID' => $Value['MatchID']), true, 0);

                $PlayersPointsArr = array_column($MatchPlayers['Data']['Records'], 'TotalPoints', 'PlayerGUID');
                $PlayersIdsArr = array_column($MatchPlayers['Data']['Records'], 'PlayerID', 'PlayerGUID');
                /* To Get User Team Players */
                $UserTeamPlayers = $this->Contest_model->getUserTeams('PlayerID,PlayerPosition,UserTeamPlayers', array('UserTeamID' => $Value['UserTeamID']), 0);
                foreach ($UserTeamPlayers['UserTeamPlayers'] as $UserTeamValue) {
                    if (!isset($PlayersPointsArr[$UserTeamValue['PlayerGUID']])) continue;

                    $Points = ($PlayersPointsArr[$UserTeamValue['PlayerGUID']] != 0) ? $PlayersPointsArr[$UserTeamValue['PlayerGUID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                    $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);

                    /* Update Player Points */
                    $this->db->where('UserTeamID', $Value['UserTeamID']);
                    $this->db->where('PlayerID', $PlayersIdsArr[$UserTeamValue['PlayerGUID']]);
                    $this->db->limit(1);
                    $this->db->update('sports_users_team_players', array('Points' => $Points));
                }
                /* Update Player Total Points */
                $this->db->where('UserTeamID', $Value['UserTeamID']);
                $this->db->where('ContestID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('sports_contest_join', array('TotalPoints' => $UserTotalPoints, 'ModifiedDate' => date('Y-m-d H:i:s')));
            }
            $ContestIDArray = array_unique($ContestIDArray);
            $this->updateRankByContest($ContestIDArray);
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
    }

    /*
      Description: user match team player points calculate and rank cache with mongodb
     */

    function getJoinedContestTeamPoints($CronID, $MatchID = "", $StatusArr = array(2, 10), $ContestStatus = 2) {
        ini_set('max_execution_time', 300);

        if (empty($MatchID)) {
            $MatchQuery = $this->db->query('SELECT MatchID FROM `sports_matches` M, tbl_entity E WHERE E.EntityID = M.MatchID AND E.StatusID IN (' . implode(',', $StatusArr) . ') ORDER BY M.PointsLastUpdatedOn ASC');
            if ($MatchQuery->num_rows() == 0) {
                return FALSE;
            }
            $MatchID = $MatchQuery->row()->MatchID;
        }
        if (!empty($MatchID)) {
            $LiveMatcheContest = $this->Contest_model->getContests('SmartPool,MatchID,ContestID,MatchIDLive,CustomizeWinning,NoOfWinners,ContestSize', array('StatusID' => array(2, 5), 'MatchID' => $MatchID, "LeagueType" => "Dfs"), true, 0);
        } else {
            $LiveMatcheContest = $this->Contest_model->getContests('SmartPool,MatchID,ContestID,MatchIDLive,MatchStartDateTimeUTC,StatusID,CustomizeWinning,NoOfWinners,ContestSize', array('StatusID' => $StatusArr, 'Filter' => 'MatchLive', "LeagueType" => "Dfs"), true, 0);
        }
        if ($LiveMatcheContest['Data']['TotalRecords'] == 0) {
            return true;
        }
        /* To Get Match Players */
        $MatchPlayers = $this->cache->memcached->get('MatchPlayerPoint_' . $MatchID);
        if (empty($MatchPlayers)) {
            $MatchPlayers = $this->db->query('SELECT P.PlayerGUID,TP.PlayerID,TP.TotalPoints,TP.PointsData,TP.PointsDataSecondInng FROM sports_players P,sports_team_players TP WHERE P.PlayerID = TP.PlayerID AND TP.MatchID = ' . $MatchID)->result_array();
            $this->cache->memcached->save('MatchPlayerPoint_' . $MatchID, $MatchPlayers, 1800);
        }
        /* Update Match PointsLastUpdatedOn */
        $this->db->where('MatchID', $MatchID);
        $this->db->limit(1);
        $this->db->update('sports_matches', array('PointsLastUpdatedOn' => date('Y-m-d H:i:s')));
        log_message('ERROR', "Points MatchID - " . $MatchID);

        ini_set('memory_limit', '512M');
        /* Get Vice Captain Points */
        $ViceCaptainPointsData = $this->db->query('SELECT PointsODI,PointsT20,PointsTEST FROM sports_setting_points WHERE PointsTypeGUID = "ViceCaptainPointMP" LIMIT 1')->row_array();
        /* Get Captain Points */
        $CaptainPointsData = $this->db->query('SELECT PointsODI,PointsT20,PointsTEST FROM sports_setting_points WHERE PointsTypeGUID = "CaptainPointMP" LIMIT 1')->row_array();
        /* Match Types */
        $MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20', '4' => 'PointsT20',
            '5' => 'PointsTEST', '7' => 'PointsT20', '9' => 'PointsODI', '8' => 'PointsODI');

        foreach ($LiveMatcheContest['Data']['Records'] as $RowContest) {
            /* Get Live Contests */
            $Query = "SELECT M.MatchTypeID, M.MatchID, JC.ContestID, JC.UserID, JC.UserTeamID,U.UserGUID"
                    . ",UT.UserTeamName,UT.UserTeamGUID,U.Username,CONCAT_WS(' ',U.FirstName,U.LastName) FullName,"
                    . "IF(U.ProfilePic IS NULL,CONCAT('" . BASE_URL . "','uploads/profile/picture/','default.jpg'),"
                    . "CONCAT('" . BASE_URL . "','uploads/profile/picture/',U.ProfilePic)) ProfilePic, "
                    . "( SELECT CONCAT( '[', GROUP_CONCAT( JSON_OBJECT( 'PlayerGUID', P.PlayerGUID, "
                    . "'PlayerName', P.PlayerName,'PlayerRole',TP.PlayerRole,'PlayerSalary',TP.PlayerSalary,'SeriesGUID', S.SeriesGUID,'TeamGUID', "
                    . "T.TeamGUID,'PlayerPic',IF(P.PlayerPic IS NULL,CONCAT('" . BASE_URL . "','uploads/PlayerPic/','player.png'),"
                    . "CONCAT('" . BASE_URL . "','uploads/PlayerPic/',P.PlayerPic)),'PlayerPosition', UTP.PlayerPosition ) ), ']' ) "
                    . "FROM sports_players P,sports_team_players TP,sports_teams T, sports_users_team_players UTP,sports_series S "
                    . "WHERE P.PlayerID = UTP.PlayerID AND UTP.MatchID = M.MatchID AND UTP.UserTeamID = JC.UserTeamID AND "
                    . "P.PlayerID = TP.PlayerID AND TP.MatchID = M.MatchID AND TP.TeamID = T.TeamID AND TP.SeriesID = S.SeriesID ) "
                    . "AS UserPlayersJSON FROM `sports_contest_join` JC, sports_matches M, tbl_entity E,tbl_users U,"
                    . "sports_users_teams UT WHERE JC.MatchID = M.MatchID AND E.EntityID = JC.ContestID AND "
                    . "UT.UserTeamID = JC.UserTeamID AND U.UserID = JC.UserID AND E.StatusID = $ContestStatus AND JC.ContestID = '" . $RowContest['ContestID'] . "' ORDER BY JC.ContestID";
            $Data = $this->db->query($Query);

            if ($Data->num_rows() > 0) {
                /* Contest Rank Array */
                $ContestIdArr = array();
                /* Joined Users Teams Data */
                foreach ($Data->result_array() as $Key => $Value) {

                    $ContestIdArr[] = $RowContest['ContestID'];
                    $PlayersPointsArr = array_column($MatchPlayers, 'TotalPoints', 'PlayerGUID');
                    $PlayersIdsArr = array_column($MatchPlayers, 'PlayerID', 'PlayerGUID');
                    $PlayersPointsData = array_column($MatchPlayers, 'PointsData', 'PlayerGUID');
                    /* Player Points Multiplier */
                    $PositionPointsMultiplier = (IS_VICECAPTAIN) ? array('ViceCaptain' => $ViceCaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],
                        'Captain' => $CaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],
                        'Player' => 1) : array('Captain' => $CaptainPointsData[$MatchTypesArr[$Value['MatchTypeID']]],
                        'Player' => 1);
                    $UserTotalPoints = 0;
                    $UserPlayersArr = array();
                    /* To Get User Team Players */
                    foreach (json_decode($Value['UserPlayersJSON'], TRUE) as $UserTeamValue) {
                        if (!isset($PlayersPointsArr[$UserTeamValue['PlayerGUID']])) continue;

                        $Points = ($PlayersPointsArr[$UserTeamValue['PlayerGUID']] != 0) ? $PlayersPointsArr[$UserTeamValue['PlayerGUID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                        $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);
                        $UserPlayersArr[] = array('PlayerGUID' => $UserTeamValue['PlayerGUID'],
                            'PlayerName' => $UserTeamValue['PlayerName'], 'PlayerPic' => $UserTeamValue['PlayerPic'],
                            'PlayerPosition' => $UserTeamValue['PlayerPosition'],
                            'PlayerSalary' => (String) $UserTeamValue['PlayerSalary'],
                            'PlayerRole' => $UserTeamValue['PlayerRole'], 'TeamGUID' => $UserTeamValue['TeamGUID'],
                            'SeriesGUID' => $UserTeamValue['SeriesGUID'], 'Points' => (String) $Points,
                            'PointCredits' => (String) $Points, 'PointsData' => !empty($PlayersPointsData[$UserTeamValue['PlayerGUID']]) ? json_decode($PlayersPointsData[$UserTeamValue['PlayerGUID']], true) : array(), 'UserWinningAmount' => '0.0');
                    }
                    /* Add/Edit Joined Contest Data (MongoDB) */
                    $ContestCollection = $this->fantasydb->{'Contest_' . $Value['ContestID']};
                    $ContestCollection->updateOne(
                            ['_id' => (int) $Value['ContestID'] . $Value['UserID'] . $Value['UserTeamID']], ['$set' => ['ContestID' => $Value['ContestID'], 'UserID' => $Value['UserID'],
                            'UserTeamID' => $Value['UserTeamID'], 'UserTeamGUID' => $Value['UserTeamGUID'],
                            'UserGUID' => $Value['UserGUID'], 'UserTeamName' => $Value['UserTeamName'],
                            'Username' => $Value['Username'], 'FullName' => $Value['FullName'],
                            'ProfilePic' => $Value['ProfilePic'], 'TotalPoints' => (String) $UserTotalPoints,
                            'TotalTeamPoints' => (float) $UserTotalPoints,
                            'UserTeamPlayers' => $UserPlayersArr, 'UserWinningAmount' => '0.0',
                            'IsWinningAssigned' => 'No', 'SmartPoolWinning' => '', "SmartPool" => $RowContest['SmartPool']]], ['upsert' => true]
                    );
                }
                /* Update User Rank (MongoDB) */
                foreach (array_unique($ContestIdArr) as $ContestID) {
                    $ContestCollection = $this->fantasydb->{'Contest_' . $ContestID};
                    $ContestData = $ContestCollection->find([], ['projection' => ['TotalTeamPoints' => 1], 'sort' => ['TotalTeamPoints' => -1]]);
                    $PrevPoint = $PrevRank = 0;
                    $SkippedCount = 1;
                    foreach ($ContestData as $ContestValue) {
                        if ($PrevPoint != $ContestValue['TotalTeamPoints']) {
                            $PrevRank = $PrevRank + $SkippedCount;
                            $PrevPoint = $ContestValue['TotalTeamPoints'];
                            $SkippedCount = 1;
                        } else {
                            $SkippedCount++;
                        }
                        $RankArr[$ContestValue['_id']] = $PrevRank;
                        $ContestCollection->updateOne(
                                ['_id' => $ContestValue['_id']], ['$set' => ['UserRank' => (String) $PrevRank]], ['upsert' => false]
                        );
                    }
                }
            }
        }
    }

    /*
      Description: user match team player points calculate and rank
     */

    function getJoinedContestTeamPointsOLD($CronID, $MatchID = "", $StatusArr = array(2)) {

        ini_set('max_execution_time', 300);
        /* Get Matches Live */
        if (!empty($MatchID)) {
            $LiveMatcheContest = $this->Contest_model->getContests('MatchID,ContestID,MatchIDLive', array('StatusID' => array(2, 5), 'MatchID' => $MatchID, "LeagueType" => "Dfs"), true, 0);
        } else {
            $LiveMatcheContest = $this->Contest_model->getContests('MatchID,ContestID,MatchIDLive', array('StatusID' => array(5), "LeagueType" => "Dfs", 'OrderBy' => "ContestID", 'Sequence' => "DESC"), true, 1,200);
            //$LiveMatcheContest = $this->Contest_model->getContests('MatchID,ContestID,MatchIDLive', array('ContestID' => 192625), true, 0);
        }
        if (!$LiveMatcheContest) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }
        foreach ($LiveMatcheContest['Data']['Records'] as $Value) {
            $MatchIDLive = $Value['MatchIDLive'];
            $MatchID = $Value['MatchID'];
            $ContestID = $Value['ContestID'];

            /* To Get Match Players */
            $MatchPlayers = $this->getPlayers('PlayerID,TotalPoints', array('MatchID' => $Value['MatchID']), true, 0);
            $Contests = $this->Contest_model->getJoinedContests('MatchTypeID,ContestID,UserID,MatchID,UserTeamID', array('ContestID' => $ContestID), true, 0);
            if (!empty($Contests['Data']['Records'])) {

                /* Match Types */
                $MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20', '4' => 'PointsT20',
                    '5' => 'PointsTEST', '7' => 'PointsT20', '9' => 'PointsODI',
                    '8' => 'PointsODI');
                foreach ($Contests['Data']['Records'] as $Value) {
                    /* Player Points Multiplier */
                    $PositionPointsMultiplier = array('ViceCaptain' => 1.5, 'Captain' => 2,
                        'Player' => 1);
                    $UserTotalPoints = 0;
                    $PlayersPointsArr = array_column($MatchPlayers['Data']['Records'], 'TotalPoints', 'PlayerGUID');
                    $PlayersIdsArr = array_column($MatchPlayers['Data']['Records'], 'PlayerID', 'PlayerGUID');
                    /* To Get User Team Players */
                    $UserTeamPlayers = $this->Contest_model->getUserTeams('PlayerID,PlayerPosition,UserTeamPlayers', array('UserTeamID' => $Value['UserTeamID']), 0);

                    foreach ($UserTeamPlayers['UserTeamPlayers'] as $UserTeamValue) {
                        if (!isset($PlayersPointsArr[$UserTeamValue['PlayerGUID']])) continue;

                        $Points = ($PlayersPointsArr[$UserTeamValue['PlayerGUID']] != 0) ? $PlayersPointsArr[$UserTeamValue['PlayerGUID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                        $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);

                        /* Update Player Points */
                        $this->db->where('UserTeamID', $Value['UserTeamID']);
                        $this->db->where('PlayerID', $PlayersIdsArr[$UserTeamValue['PlayerGUID']]);
                        $this->db->limit(1);
                        $this->db->update('sports_users_team_players', array('Points' => $Points));
                        //echo $this->db->last_query();exit;
                    }
                    /* Update Player Total Points */
                    /* $this->db->where('UserTeamID', $Value['UserTeamID']);
                      $this->db->where('ContestID', $Value['ContestID']);
                      $this->db->limit(1);
                      $this->db->update('sports_contest_join', array('TotalPoints' => $UserTotalPoints, 'ModifiedDate' => date('Y-m-d H:i:s'))); */
                }
            }
            /* $this->updateRankByContest($ContestID); */
        }
    }

    /*
      Description: To update rank
     */

    function updateRankByContest($ContestID) {
        if (!empty($ContestID)) {
            $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                         ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                         FROM sports_contest_join WHERE sports_contest_join.ContestID = '" . $ContestID . "')) AS UserRank,ContestID,UserTeamID
                         FROM sports_contest_join,tbl_users 
                         WHERE sports_contest_join.ContestID = '" . $ContestID . "' AND tbl_users.UserID = sports_contest_join.UserID
                     ");
            $results = $query->result_array();
            if (!empty($results)) {
                $this->db->trans_start();
                foreach ($results as $rows) {
                    $this->db->where('ContestID', $rows['ContestID']);
                    $this->db->where('UserTeamID', $rows['UserTeamID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('UserRank' => $rows['UserRank']));
                }
                $this->db->trans_complete();
            }
        }
    }

    /*
      Description:  Cron jobs to get auction joined player points.

     */

    function getAuctionJoinedUserTeamsPlayerPoints($CronID) {
        ini_set('max_execution_time', 600);
        /** get series play in auction * */
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2, 'SeriesEndDate' => date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d')))), "AuctionDraftIsPlayed" => "Yes"), true, 0);
        if ($SeriesData['Data']['TotalRecords'] == 0) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        foreach ($SeriesData['Data']['Records'] as $Rows) {
            /* Get Matches Live */
            $LiveMatcheContest = $this->AuctionDrafts_model->getContests('ContestID,SeriesID', array('StatusID' => 1, "AuctionStatusID" => 5, "LeagueType" => "Auction",
                "SeriesID" => $Rows['SeriesID']), true, 0);

            foreach ($LiveMatcheContest['Data']['Records'] as $Value) {
                $MatchIDLive = $Value['MatchIDLive'];
                $SeriesID = $Value['SeriesID'];
                $ContestID = $Value['ContestID'];
                $Contests = $this->AuctionDrafts_model->getJoinedContests('ContestID,UserID,UserTeamID', array('StatusID' => 1, 'ContestID' => $ContestID, "SeriesID" => $SeriesID), true, 0);

                if (!empty($Contests['Data']['Records'])) {
                    /* Get Vice Captain Points */
                    $ViceCaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "ViceCaptainPointMP" LIMIT 1')->row_array();

                    /* Get Captain Points */
                    $CaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "CaptainPointMP" LIMIT 1')->row_array();

                    /* Match Types */
                    $MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20',
                        '4' => 'PointsT20', '5' => 'PointsTEST', '7' => 'PointsT20',
                        '9' => 'PointsODI');
                    $ContestIDArray = array();
                    foreach ($Contests['Data']['Records'] as $Value) {
                        $ContestIDArray[] = $Value['ContestID'];
                        /* Player Points Multiplier */
                        $UserID = $Value["UserID"];
                        $PositionPointsMultiplier = array('ViceCaptain' => 1.5, 'Captain' => 2,
                            'Player' => 1);
                        $UserTotalPoints = 0;

                        /* To Get Match Players */
                        $MatchPlayers = $this->AuctionDrafts_model->getAuctionPlayersPoints('SeriesID,PlayerID,TotalPoints', array('SeriesID' => $SeriesID), true, 0);

                        $PlayersPointsArr = array_column($MatchPlayers['Data']['Records'], 'TotalPoints', 'PlayerGUID');
                        $PlayersIdsArr = array_column($MatchPlayers['Data']['Records'], 'PlayerID', 'PlayerGUID');

                        /* To Get User Team Players */
                        $UserTeamPlayers = $this->AuctionDrafts_model->getUserTeams('PlayerID,PlayerPosition,UserTeamPlayers', array('UserTeamID' => $Value['UserTeamID']), 0);
                        foreach ($UserTeamPlayers['UserTeamPlayers'] as $UserTeamValue) {
                            if (!isset($PlayersPointsArr[$UserTeamValue['PlayerGUID']])) continue;

                            $Points = ($PlayersPointsArr[$UserTeamValue['PlayerGUID']] != 0) ? $PlayersPointsArr[$UserTeamValue['PlayerGUID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                            $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);

                            /* Update Player Points */
                            $this->db->where('UserTeamID', $Value['UserTeamID']);
                            $this->db->where('PlayerID', $PlayersIdsArr[$UserTeamValue['PlayerGUID']]);
                            $this->db->limit(1);
                            $this->db->update('sports_users_team_players', array('Points' => $Points));
                        }

                        $UserTeamPlayersTotal = $this->AuctionDrafts_model->getUserTeamPlayersAuction('TotalPoints', array('UserTeamID' => $Value['UserTeamID']));

                        if (!empty($UserTeamPlayersTotal)) {
                            $UserTotalPoints = $UserTeamPlayersTotal[0]['TotalPoints'];
                        }
                        /* Update Player Total Points */
                        $this->db->where('UserID', $UserID);
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest_join', array('TotalPoints' => $UserTotalPoints, 'ModifiedDate' => date('Y-m-d H:i:s')));
                        $AB[] = $this->db->last_query();
                    }
                    $ContestIDArray = array_unique($ContestIDArray);
                }
                /* else {
                  $this->db->where('CronID', $CronID);
                  $this->db->limit(1);
                  $this->db->update('log_cron', array('CronStatus' => 'Exit'));
                  exit;
                  } */
                $this->updateRankByContest($ContestID);
            }
        }
    }

    /*
      Description: To set contest winners with mongodb
     */

    function setContestWinners($CronID) {

        ini_set('max_execution_time', 300);
        $Contests = $this->db->query('SELECT C.WinningAmount,C.ContestID,C.CustomizeWinning FROM tbl_entity E,sports_contest C WHERE E.EntityID = C.ContestID AND E.StatusID = 5 AND C.IsWinningDistributed = "No" AND C.LeagueType = "Dfs" AND C.SmartPool = "No"');
        //$Contests = $this->db->query('SELECT C.WinningAmount,C.ContestID,C.CustomizeWinning FROM tbl_entity E,sports_contest C WHERE E.EntityID = C.ContestID AND E.StatusID = 5 AND C.LeagueType = "Dfs" AND C.MatchID = 113644');
        if ($Contests->num_rows() > 0) {
            foreach ($Contests->result_array() as $Value) {

                /* Get Joined Contests */
                $ContestCollection = $this->fantasydb->{'Contest_' . $Value['ContestID']};
                $JoinedContestsUsers = iterator_to_array($ContestCollection->find([
                            "ContestID" => $Value['ContestID'], "IsWinningAssigned" => "No",
                            "TotalTeamPoints" => ['$gt' => 1]], ['projection' => ['UserRank' => 1, 'UserTeamID' => 1,
                                'TotalTeamPoints' => 1, 'UserID' => 1], 'sort' => ['UserRank' => -1]]));
                $AllUsersRank = array_column($JoinedContestsUsers, 'UserRank');
                $AllRankWinners = array_count_values($AllUsersRank);
                if (count($AllRankWinners) == 0) {
                    /* Update Contest Winning Assigned Status */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributed' => "Yes"));
                    continue;
                }

                $userWinnersData = $OptionWinner = array();
                $CustomizeWinning = (!empty($Value['CustomizeWinning'])) ? json_decode($Value['CustomizeWinning'], true) : array();
                foreach ($AllRankWinners as $Rank => $WinnerValue) {
                    $Flag = $TotalAmount = $AmountPerUser = 0;
                    for ($J = 0; $J < count($CustomizeWinning); $J++) {
                        if ($Rank >= $CustomizeWinning[$J]['From'] && $Rank <= $CustomizeWinning[$J]['To']) {
                            $TotalAmount = $CustomizeWinning[$J]['WinningAmount'];
                            if ($WinnerValue > 1) {
                                $L = 0;
                                for ($k = 1; $k < $WinnerValue; $k++) {
                                    if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                        if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                            $Flag = 1;
                                        } else {
                                            $L = $L + 1;
                                            if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                                if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                    $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                    $Flag = 1;
                                                }
                                            }
                                        }
                                    }
                                    if ($Flag == 0) {
                                        if ($Rank + $k >= $CustomizeWinning[$J]['From'] && $Rank + $k <= $CustomizeWinning[$J]['To']) {
                                            $TotalAmount += $CustomizeWinning[$J]['WinningAmount'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $AmountPerUser = $TotalAmount / $WinnerValue;
                    $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers, $Rank, $AmountPerUser);
                }
                foreach ($userWinnersData as $WinnerArray) {
                    foreach ($WinnerArray as $WinnerRow) {
                        $OptionWinner[] = $WinnerRow;
                    }
                }
                if (!empty($OptionWinner)) {
                    foreach ($OptionWinner as $WinnerValue) {
                        $TaxAmount = 0;
                        /* Update User Winning Amount (MongoDB) */
                        $ContestCollection->updateOne(
                                ['_id' => $Value['ContestID'] . $WinnerValue['UserID'] . $WinnerValue['UserTeamID']], ['$set' => ['UserWinningAmount' => (String) round($WinnerValue['UserWinningAmount'], 2), 'TaxAmount' => $TaxAmount, 'IsWinningAssigned' => 'Yes']], ['upsert' => false]
                        );
                    }
                }
            }
        }
        $this->setSmartPoolWinnersMongo($CronID);
    }

    /*
      Description: To set Smart Pool winners
     */

    function setSmartPoolWinnersMongo($CronID) {

        ini_set('max_execution_time', 300);

        $Contests = $this->db->query('SELECT C.WinningAmount,C.ContestID,C.CustomizeWinning,C.ContestSize,C.NoOfWinners FROM tbl_entity E,sports_contest C WHERE E.EntityID = C.ContestID AND E.StatusID = 5 AND C.IsWinningDistributed = "No" AND C.LeagueType = "Dfs" AND C.SmartPool = "Yes"');
        if ($Contests->num_rows() > 0) {
            foreach ($Contests->result_array() as $Value) {
                /* Get Joined Contests */
                $ContestCollection = $this->fantasydb->{'Contest_' . $Value['ContestID']};
                $JoinedContestsUsers = iterator_to_array($ContestCollection->find([
                            "ContestID" => $Value['ContestID'], "IsWinningAssigned" => "No",
                            "TotalTeamPoints" => ['$gt' => 0]], ['projection' => ['UserRank' => 1, 'UserTeamID' => 1,
                                'TotalTeamPoints' => 1, 'UserID' => 1], 'sort' => ['UserRank' => -1]]));
                $AllUsersRank = array_column($JoinedContestsUsers, 'UserRank');
                $AllRankWinners = array_count_values($AllUsersRank);
                if (count($AllRankWinners) == 0) {
                    /* Update Contest Winning Assigned Status */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributed' => "Yes"));
                    continue;
                }
                $CustomizeWinning = json_decode($Value['CustomizeWinning'], true);
                if (empty($CustomizeWinning)) {
                    $CustomizeWinning[] = array(
                        'From' => 1,
                        'To' => $Value['NoOfWinners'],
                        'Percent' => 100,
                        'WinningAmount' => $Value['WinningAmount']
                    );
                }
                foreach ($AllRankWinners as $Rank => $WinnerValue) {
                    $Flag = 0;
                    for ($J = 0; $J < count($CustomizeWinning); $J++) {
                        $FromWinner = $CustomizeWinning[$J]['From'];
                        $ToWinner = $CustomizeWinning[$J]['To'];
                        if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                            $ProductName = $CustomizeWinning[$J]['ProductName'];
                            $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers, $Rank, $ProductName);
                        }
                    }
                }
                foreach ($userWinnersData as $WinnerArray) {
                    foreach ($WinnerArray as $WinnerRow) {
                        $OptionWinner[] = $WinnerRow;
                    }
                }
                if (!empty($OptionWinner)) {
                    foreach ($OptionWinner as $WinnerValue) {
                        $ContestCollection->updateOne(
                                ['_id' => $Value['ContestID'] . $WinnerValue['UserID'] . $WinnerValue['UserTeamID']], ['$set' => ['SmartPoolWinning' => $WinnerValue['UserWinningAmount'], 'IsWinningAssigned' => 'Yes']], ['upsert' => false]
                        );
                    }
                }
            }
        }
    }

    /*
      Description: To set Smart Pool winners
     */

    function setSmartPoolWinners($CronID) {

        ini_set('max_execution_time', 300);

        $Contests = $this->Contest_model->getContests('WinningAmount,NoOfWinners,ContestID,ContestSize,CustomizeWinning', array('StatusID' => 5, 'SmartPool' => "Yes", 'IsWinningDistributed' => 'No',
            "LeagueType" => "Dfs"), true, 0);

        if (isset($Contests['Data']['Records'])) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(),
                    'Contests' => $Contests), JSON_UNESCAPED_UNICODE)));

            foreach ($Contests['Data']['Records'] as $Value) {
                $JoinedContestsUsers['Data']['Records'] = $this->db->query("SELECT JC.UserID,JC.UserTeamID,JC.TotalPoints,JC.UserRank FROM sports_contest_join JC WHERE JC.ContestID =" . $Value['ContestID'] . " AND JC.TotalPoints > 0 ORDER BY JC.UserRank DESC")->result_array();

                if (!empty($JoinedContestsUsers['Data']['Records'])) {

                    $AllUsersRank = array_column($JoinedContestsUsers['Data']['Records'], 'UserRank');
                    $AllRankWinners = array_count_values($AllUsersRank);
                    $userWinnersData = $OptionWinner = array();
                    $CustomizeWinning = $Value['CustomizeWinning'];
                    if (empty($CustomizeWinning)) {
                        $CustomizeWinning[] = array(
                            'From' => 1,
                            'To' => $Value['NoOfWinners'],
                            'Percent' => 100,
                            'WinningAmount' => $Value['WinningAmount']
                        );
                    }

                    foreach ($AllRankWinners as $Rank => $WinnerValue) {
                        $Flag = 0;
                        for ($J = 0; $J < count($CustomizeWinning); $J++) {
                            $FromWinner = $CustomizeWinning[$J]['From'];
                            $ToWinner = $CustomizeWinning[$J]['To'];
                            if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                                $ProductName = $CustomizeWinning[$J]['ProductName'];
                                $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers['Data']['Records'], $Rank, $ProductName);
                            }
                        }
                    }
                    foreach ($userWinnersData as $WinnerArray) {
                        foreach ($WinnerArray as $WinnerRow) {
                            $OptionWinner[] = $WinnerRow;
                        }
                    }
                    if (!empty($OptionWinner)) {
                        foreach ($OptionWinner as $WinnerValue) {

                            $this->db->trans_start();

                            $this->db->where('UserID', $WinnerValue['UserID']);
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('SmartPoolWinning' => $WinnerValue['UserWinningAmount'],
                                'ModifiedDate' => date('Y-m-d H:i:s')));

                            $this->db->trans_complete();
                            if ($this->db->trans_status() === false) {
                                return false;
                            }
                        }
                    }

                    /* update contest winner amount distribute flag set YES */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes'));
                } else {
                    /* $this->db->where('EntityID', $Value['ContestID']);
                      $this->db->limit(1);
                      $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3)); */

                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsRefund' => 'Yes', 'isMailSent' => "Yes"));
                }
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            return true;
        }
    }

    /*
      Description: To set contest winners
     */

    function setContestWinnersOLD($CronID) {

        ini_set('max_execution_time', 300);
        $Contests = $this->Contest_model->getContests('WinningAmount,NoOfWinners,ContestID,ContestSize,CustomizeWinning', array('StatusID' => 5, 'IsWinningAmount' => "Yes", 'IsWinningDistributed' => 'No',
            "LeagueType" => "Dfs"), true, 0);

        if (isset($Contests['Data']['Records'])) {

            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronResponse' => @json_encode(array('Query' => $this->db->last_query(),
                    'Contests' => $Contests), JSON_UNESCAPED_UNICODE)));

            foreach ($Contests['Data']['Records'] as $Value) {

                /* $JoinedContestsUsers = $this->Contest_model->getJoinedContestsUsers('UserRank,UserTeamID,TotalPoints,UserRank,UserID,FirstName,Email', array('ContestID' => $Value['ContestID'], 'OrderBy' => 'JC.UserRank', 'Sequence' => 'DESC', 'PointFilter' => 'TotalPoints'), true, 0); */
                $JoinedContestsUsers['Data']['Records'] = $this->db->query("SELECT JC.UserID,JC.UserTeamID,JC.TotalPoints,JC.UserRank FROM sports_contest_join JC WHERE JC.ContestID =" . $Value['ContestID'] . " AND JC.TotalPoints > 0 ORDER BY JC.UserRank DESC")->result_array();

                if (!empty($JoinedContestsUsers['Data']['Records'])) {

                    $AllUsersRank = array_column($JoinedContestsUsers['Data']['Records'], 'UserRank');
                    $AllRankWinners = array_count_values($AllUsersRank);
                    $userWinnersData = $OptionWinner = array();
                    $CustomizeWinning = $Value['CustomizeWinning'];
                    if (empty($CustomizeWinning)) {
                        $CustomizeWinning[] = array(
                            'From' => 1,
                            'To' => $Value['NoOfWinners'],
                            'Percent' => 100,
                            'WinningAmount' => $Value['WinningAmount']
                        );
                    }
                    /** calculate amount according to rank or contest winning configuration */
                    foreach ($AllRankWinners as $Rank => $WinnerValue) {
                        $Flag = $TotalAmount = $AmountPerUser = 0;
                        for ($J = 0; $J < count($CustomizeWinning); $J++) {
                            $FromWinner = $CustomizeWinning[$J]['From'];
                            $ToWinner = $CustomizeWinning[$J]['To'];
                            if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                                $TotalAmount = $CustomizeWinning[$J]['WinningAmount'];
                                if ($WinnerValue > 1) {
                                    $L = 0;
                                    for ($k = 1; $k < $WinnerValue; $k++) {
                                        if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                            if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                $Flag = 1;
                                            } else {
                                                $L = $L + 1;
                                                if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                                    if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                        $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                        $Flag = 1;
                                                    }
                                                }
                                            }
                                        }
                                        if ($Flag == 0) {
                                            if ($Rank + $k >= $CustomizeWinning[$J]['From'] && $Rank + $k <= $CustomizeWinning[$J]['To']) {
                                                $TotalAmount += $CustomizeWinning[$J]['WinningAmount'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $AmountPerUser = $TotalAmount / $WinnerValue;
                        $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers['Data']['Records'], $Rank, $AmountPerUser);
                    }
                    foreach ($userWinnersData as $WinnerArray) {
                        foreach ($WinnerArray as $WinnerRow) {
                            $OptionWinner[] = $WinnerRow;
                        }
                    }

                    if (!empty($OptionWinner)) {
                        foreach ($OptionWinner as $WinnerValue) {

                            $this->db->trans_start();

                            /** join contest user winning amount update * */
                            $this->db->where('UserID', $WinnerValue['UserID']);
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('UserWinningAmount' => $WinnerValue['UserWinningAmount'],
                                'ModifiedDate' => date('Y-m-d H:i:s')));

                            $this->db->trans_complete();
                            if ($this->db->trans_status() === false) {
                                return false;
                            }
                        }
                    }

                    /* update contest winner amount distribute flag set YES */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes'));
                } else {

                    $this->db->where('EntityID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));

                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsRefund' => 'Yes', 'isMailSent' => "Yes"));
                }
            }
            $this->setSmartPoolWinners($CronID);
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            return true;
        }
    }

    /*
      Description: To set contest winners amount distribute
     */

    function amountDistributeContestWinner($CronID) {
        ini_set('max_execution_time', 300);
        /* Get Joined Contest Users */
        $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,E.StatusID');
        $this->db->from('sports_contest C,tbl_entity E');
        $this->db->where("E.EntityID", "C.ContestID", FALSE);
        $this->db->where("C.IsWinningDistributed", "Yes");
        $this->db->where("C.IsWinningDistributeAmount", "No");
        $this->db->where("C.LeagueType", "Dfs");
        $this->db->where("C.ContestTransferred", "Yes");
        $this->db->where("E.StatusID", 5);
        $this->db->limit(15);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Contests = $Query->result_array();

            foreach ($Contests as $Value) {
                /* Get Joined Contest Users */
                $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.UserRank,JC.UserWinningAmount,JC.IsWinningDistributeAmount,JC.SmartPool,JC.SmartPoolWinning');
                $this->db->from('sports_contest_join JC, tbl_users U');
                $this->db->where("JC.UserID", "U.UserID", FALSE);
                $this->db->where("JC.IsWinningDistributeAmount", "No");
                $this->db->where("JC.ContestID", $Value['ContestID']);
                $this->db->where("U.UserTypeID !=", 3);
                $this->db->where("JC.UserWinningAmount >", 0);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $JoinedContestsUsers = $Query->result_array();
                    if (!empty($JoinedContestsUsers)) {
                        foreach ($JoinedContestsUsers as $WinnerValue) {

                            $this->db->trans_start();

                            if ($WinnerValue['UserWinningAmount'] > 0) {
                                /** update user wallet * */
                                $WalletData = array(
                                    "Amount" => $WinnerValue['UserWinningAmount'],
                                    "WinningAmount" => $WinnerValue['UserWinningAmount'],
                                    "EntityID" => $WinnerValue['ContestID'],
                                    "UserTeamID" => $WinnerValue['UserTeamID'],
                                    "TransactionType" => 'Cr',
                                    "Narration" => 'Join Contest Winning',
                                    "EntryDate" => date("Y-m-d H:i:s")
                                );
                                $this->Users_model->addToWallet($WalletData, $WinnerValue['UserID'], 5);
                                $this->Notification_model->addNotification('winnings', 'Contest Winner', $WinnerValue['UserID'], $WinnerValue['UserID'], $WinnerValue['ContestID'], 'Congratulations you have won ' . DEFAULT_CURRENCY . $WinnerValue['UserWinningAmount'] . '');
                            }

                            /** user join contest winning status update * */
                            $this->db->where('UserID', $WinnerValue['UserID']);
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('IsWinningDistributeAmount' => "Yes", 'ModifiedDate' => date('Y-m-d H:i:s')));

                            if ($WinnerValue['SmartPool'] == 'Yes' && !empty($WinnerValue['SmartPoolWinning'])) {
                                $this->Notification_model->addNotification('winnings', 'Smart Pool Contest Winner', $WinnerValue['UserID'], $WinnerValue['UserID'], $WinnerValue['ContestID'], 'Congratulations you have won ' . $WinnerValue['SmartPoolWinning'] . '.You will be notified soon for the same.');
                            }

                            $this->db->trans_complete();
                            if ($this->db->trans_status() === false) {
                                return false;
                            }
                        }
                    } else {
                        /* Update Contest Winning Status Yes */
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                    }
                } else {
                    /* Update Contest Winning Status Yes */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                }
            }
        } else {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
    }

    function amountDistributeContestWinnerOLD($CronID) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        ini_set('max_execution_time', 300);
        /* Get Joined Contest Users */
        $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,E.StatusID,C.IsWinningDistributed,C.IsWinningDistributeAmount');
        $this->db->from('sports_contest C,tbl_entity E');
        $this->db->where("E.EntityID", "C.ContestID", FALSE);
        //$this->db->where("C.IsWinningDistributed", "Yes");
        //$this->db->where("C.IsWinningDistributeAmount", "No");
        $this->db->where("C.LeagueType", "Dfs");
        $this->db->where("C.IsRefund", "No");
        $this->db->where("E.StatusID", 3);
        $this->db->where("C.MatchID", 60095);
        $this->db->limit(100);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Contests = $Query->result_array();
            //dump($Contests);
            $DoubleWinningData = array();
            $NoWinningData = array();
            foreach ($Contests as $Value) {
                /* Get Joined Contest Users */
                $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.UserRank,JC.UserWinningAmount,JC.IsWinningDistributeAmount,JC.SmartPool,JC.SmartPoolWinning');
                $this->db->from('sports_contest_join JC, tbl_users U');
                $this->db->where("JC.UserID", "U.UserID", FALSE);
                //$this->db->where("JC.IsWinningDistributeAmount", "No");
                $this->db->where("JC.ContestID", $Value['ContestID']);
                //$this->db->where("JC.UserWinningAmount >", 0);
                $this->db->where("U.UserTypeID !=", 3);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $JoinedContestsUsers = $Query->result_array();
                    dump($Contests);
                    foreach ($JoinedContestsUsers as $WinnerValue) {
                        // $this->db->trans_start();
                        $WalletDetails = $this->Users_model->getWallet('Amount,UserID,EntityID,UserTeamID,Narration', array('UserID' => $WinnerValue['UserID'], 'EntityID' => $WinnerValue['ContestID'], 'UserTeamID' => $WinnerValue['UserTeamID'], 'Narration' => 'Cancel Contest'));
//                         echo $this->db->last_query();
//                         dump($WalletDetails);
                        if (!empty($WalletDetails)) {
                            continue;
//                            if ($WalletDetails['Data']['TotalRecords'] >= 2) {
//                                $DoubleWinningData[] = array(
//                                    "UserID" => $WinnerValue['UserID'],
//                                    "Amount" => $WinnerValue['UserWinningAmount'],
//                                    "WinningAmount" => $WinnerValue['UserWinningAmount'],
//                                    "EntityID" => $WinnerValue['ContestID'],
//                                    "UserTeamID" => $WinnerValue['UserTeamID'],
//                                    "TransactionType" => 'Cr',
//                                    "Narration" => 'Join Contest Winning',
//                                    "EntryDate" => date("Y-m-d H:i:s")
//                                );
//                            }
                        } else {
                            if ($WinnerValue['UserWinningAmount'] > 0) {
                                /** update user wallet * */
                                $NoWinningData[] = array(
                                    "UserID" => $WinnerValue['UserID'],
                                    "Amount" => $WinnerValue['UserWinningAmount'],
                                    "WinningAmount" => $WinnerValue['UserWinningAmount'],
                                    "EntityID" => $WinnerValue['ContestID'],
                                    "UserTeamID" => $WinnerValue['UserTeamID'],
                                    "TransactionType" => 'Cr',
                                    "Narration" => 'Join Contest Winning',
                                    "EntryDate" => date("Y-m-d H:i:s")
                                );
                                //$this->Users_model->addToWallet($WalletData, $WinnerValue['UserID'], 5);
                                //$this->Notification_model->addNotification('winnings', 'Contest Winner', $WinnerValue['UserID'], $WinnerValue['UserID'], $WinnerValue['ContestID'], 'Congratulations you have won ' . DEFAULT_CURRENCY . $WinnerValue['UserWinningAmount'] . '');
                            }
                            // if ($WinnerValue['SmartPool'] == 'Yes' && !empty($WinnerValue['SmartPoolWinning'])) {
                            //exit;
                            //$this->Notification_model->addNotification('winnings', 'Smart Pool Contest Winner', $WinnerValue['UserID'], $WinnerValue['UserID'], $WinnerValue['ContestID'], 'Congratulations you have won ' . $WinnerValue['SmartPoolWinning'] . '.You will be notified soon for the same.');
                            // }
                        }

                        /** user join contest winning status update * */
//                        $this->db->where('UserID', $WinnerValue['UserID']);
//                        $this->db->where('ContestID', $Value['ContestID']);
//                        $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
//                        $this->db->limit(1);
//                        $this->db->update('sports_contest_join', array('IsWinningDistributeAmount' => "Yes", 'ModifiedDate' => date('Y-m-d H:i:s')));
//                        $this->db->trans_complete();
//                        if ($this->db->trans_status() === false) {
//                            return false;
//                        }
                    }

                    /* Update Contest Winning Status Yes */
//                    $this->db->where('ContestID', $Value['ContestID']);
//                    $this->db->limit(1);
//                    $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                } else {
                    /* Update Contest Winning Status Yes */
//                    $this->db->where('ContestID', $Value['ContestID']);
//                    $this->db->limit(1);
//                    $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                }
            }
            dump($NoWinningData);
        } else {
//            $this->db->where('CronID', $CronID);
//            $this->db->limit(1);
//            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
//            exit;
        }
    }

    /*
      Description: To send mail to contest winners
     */

    function ContestWinnersMailSend($CronID) {
        ini_set('max_execution_time', 300);
        $Contests = $this->Contest_model->getContests('ContestID, EntryFee, SeriesName, ContestName, MatchNo, TeamNameLocal, TeamNameVisitor', array('StatusID' => 5, 'IsWinningDistributed' => 'Yes', 'isMailSent' => 'No',
            "LeagueType" => "Dfs"), true, 0);

        if (!empty($Contests['Data']['Records'])) {
            foreach ($Contests['Data']['Records'] as $Value) {
                $Query = "SELECT JC.UserRank,JC.UserWinningAmount,JC.TotalPoints,U.UserID,U.FirstName,U.Email "
                        . "FROM sports_contest_join JC LEFT JOIN tbl_users U ON U.UserID = JC.UserID "
                        . "WHERE ContestID =" . $Value['ContestID'] . " AND JC.UserWinningAmount > 0 "
                        . "AND JC.isMailSent = 'No' AND U.UserTypeID !=3 ";
                $JoinedContestsUsers = $this->db->query($Query);

                if ($JoinedContestsUsers->num_rows() > 0) {
                    foreach ($JoinedContestsUsers->result_array() as $WinnerValue) {
                        if (!empty($WinnerValue['Email'])) {
                            send_mail(array(
                                'emailTo' => $WinnerValue['Email'],
                                'template_id' => CONTEST_WINNING,
                                'Subject' => 'You’re a winner- ' . SITE_NAME,
                                'Name' => $WinnerValue['FirstName'],
                                'Amount' => $WinnerValue['UserWinningAmount'],
                                'SeriesName' => $Value['SeriesName'],
                                'ContestName' => $Value['ContestName'],
                                'MatchNo' => $Value['MatchNo'],
                                'TeamNameLocal' => $Value['TeamNameLocal'],
                                'TeamNameVisitor' => $Value['TeamNameVisitor'],
                                'TotalPoints' => $WinnerValue['TotalPoints'],
                                'UserRank' => $WinnerValue['UserRank']
                            ));
                        }
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->where('UserID', $WinnerValue['UserID']);
                        $this->db->update('sports_contest_join', array('isMailSent' => 'Yes'));
                    }
                } else {
                    /* update contest winner mail send flag set YES */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('isMailSent' => 'Yes'));
                }
            }
        } else {
            exit;
        }
    }

    /*
      Description: To send Notification for upcoming Matches.
     */

    function notifyUpcomingMatches() {
        $DateTime = date('Y-m-d H:i', strtotime('+2 hours', strtotime(date('Y-m-d H:i'))));
        $UpcomingMatches = $this->getMatches('MatchGUID,MatchID,MatchStartDateTime, Status, TeamNameShortLocal, SeriesName, TeamNameShortVisitor', array('MatchStartDateTime' => $DateTime, 'isReminderNotificationSent' => 'No',
            'StatusID' => array(1)), true, 1, 2);
        if (!empty($UpcomingMatches['Data']['Records'])) {
            foreach ($UpcomingMatches['Data']['Records'] as $Value) {
                $Users = $this->Users_model->getUsers('UserID,FirstName', array('StatusID' => 2), true, 1, 10000000);
                if (!empty($Users['Data']['Records'])) {
                    $Title = "Reminder for " . $Value['TeamNameShortLocal'] . ' v/s ' . $Value['TeamNameShortVisitor'] . ' Match';
                    $Message = "Join the Contest now and win real cash";
                    foreach ($Users['Data']['Records'] as $UserValue) {
                        $InsertData[] = array_filter(array(
                            "NotificationPatternID" => 2,
                            "UserID" => '125',
                            "ToUserID" => $UserValue['UserID'],
                            "RefrenceID" => $Value['MatchGUID'],
                            "NotificationText" => $Title,
                            "NotificationMessage" => $Message,
                            "MediaID" => "",
                            "EntryDate" => date("Y-m-d H:i:s")
                        ));
                    }
                    if (!empty($InsertData)) {
                        $this->db->insert_batch('tbl_notifications', $InsertData);
                    }
                    pushNotificationAndroidBroadcast($Title, $Message, $Value['MatchGUID']);
                    pushNotificationIphoneBroadcast($Title, $Message, $Value['MatchGUID']);
                }
                $this->db->where('MatchID', $Value['MatchID']);
                $this->db->limit(1);
                $this->db->update('sports_matches', array('isReminderNotificationSent' => 'Yes'));
            }
        } else {
            exit;
        }
    }

    /*
      Description: To common funtion find key value
     */

    function findKeyValueArray($JoinedContestsUsers, $Rank, $AmountPerUser) {
        $WinnerUsers = array();
        foreach ($JoinedContestsUsers as $Rows) {
            if ($Rows['UserRank'] == $Rank) {
                $Temp['UserID'] = $Rows['UserID'];
                $Temp['FirstName'] = $Rows['FirstName'];
                $Temp['Email'] = $Rows['Email'];
                $Temp['UserWinningAmount'] = $AmountPerUser;
                $Temp['UserRank'] = $Rows['UserRank'];
                $Temp['TotalPoints'] = $Rows['TotalPoints'];
                $Temp['UserTeamID'] = $Rows['UserTeamID'];
                $WinnerUsers[] = $Temp;
            }
        }
        return $WinnerUsers;
    }

    /*
      Description: To Auto Cancel Contest
     */

    function autoCancelContest($CronID = 0, $CancelType = "Cancelled", $MatchID = "") {
        ini_set('max_execution_time', 300);

        /* Get Contest Data */
        if (!empty($MatchID)) {
            $ContestsUsers = $this->db->query('SELECT C.ContestID,C.UnfilledWinningPercent,C.Privacy,C.EntryFee,C.ContestFormat,C.ContestSize,C.IsConfirm,C.AdminPercent,C.WinningRatio,C.CustomizeWinning,M.MatchStartDateTime,(SELECT COUNT(TotalPoints) FROM sports_contest_join WHERE ContestID =  C.ContestID ) TotalJoined FROM tbl_entity E, sports_contest C, sports_matches M WHERE E.EntityID = C.ContestID AND C.MatchID = M.MatchID AND C.MatchID = ' . $MatchID . ' AND E.StatusID IN(1,2) AND C.LeagueType = "Dfs" AND C.IsGuranteedPoolWinningUpdated = "No" AND DATE(M.MatchStartDateTime) <= "' . date('Y-m-d') . '" ORDER BY M.MatchStartDateTime ASC');
        } else {
            $ContestsUsers = $this->db->query('SELECT C.ContestID,C.UnfilledWinningPercent,C.Privacy,C.EntryFee,C.ContestFormat,C.ContestSize,C.IsConfirm,C.AdminPercent,C.WinningRatio,C.CustomizeWinning,M.MatchStartDateTime,(SELECT COUNT(TotalPoints) FROM sports_contest_join WHERE ContestID =  C.ContestID ) TotalJoined FROM tbl_entity E, sports_contest C, sports_matches M WHERE E.EntityID = C.ContestID AND C.MatchID = M.MatchID AND E.StatusID IN(1,2) AND C.LeagueType = "Dfs" AND C.IsGuranteedPoolWinningUpdated = "No" AND DATE(M.MatchStartDateTime) <= "' . date('Y-m-d') . '" ORDER BY M.MatchStartDateTime ASC'); 
        }

        if ($ContestsUsers->num_rows() == 0) {
            return FALSE;
        }

        foreach ($ContestsUsers->result_array() as $Value) {
            if ($CancelType == "Cancelled") {

                if (((strtotime($Value['MatchStartDateTime'])) - strtotime(date('Y-m-d H:i:s'))) > 0) {
                    continue;
                }
                /* To Check Unfilled Contest */
                if (($Value['UnfilledWinningPercent'] == 'GuranteedPool' || $Value['UnfilledWinningPercent'] == 'Yes') && $Value['EntryFee'] > 0) {
                    if ($Value['TotalJoined'] == 0) {

                        /* Update Contest Status */
                        $this->db->where('EntityID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
                    } else {
                        $TotalCollection = $Value['EntryFee'] * $Value['TotalJoined'];
                        $TotalWinningAmount = $TotalCollection;
                        if($Value['AdminPercent'] > 0){
                            $TotalWinningAmount = $TotalCollection - (($TotalCollection * $Value['AdminPercent'])/100);
                        }
                        $NoOfWinningUsers = ceil(($Value['TotalJoined'] * $Value['WinningRatio'])/100); // Ex:- 4.1, 4.5, 4.9 will be 5 winning users

                        /* Update Contest New Customize Winning */
                        $NewCustomizeWinning = array(array(
                            'From' => 1,
                            'To' => $NoOfWinningUsers,
                            'Percent' => "100",
                            'WinningAmount' => round(($TotalWinningAmount / $NoOfWinningUsers),2)
                        ));
                        $this->db->where('ContestID', $Value['ContestID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest', array('IsGuranteedPoolWinningUpdated' => 'Yes','NoOfWinners' => $NoOfWinningUsers,'WinningAmount' => $TotalWinningAmount, 'CustomizeWinning' => json_encode($NewCustomizeWinning)));
                    }
                    continue;
                }

                /* To check contest cancel condition */
                $IsCancelled = 0;
                if ($Value['Privacy'] == 'Yes') { // Should be 100% filled
                    $IsCancelled = ($Value['ContestSize'] != $Value['TotalJoined']) ? 1 : 0;
                } else {
                    if ($Value['ContestFormat'] == 'Head to Head') {
                        $IsCancelled = ($Value['TotalJoined'] == 2) ? 0 : 1;
                    } else {
                        if ($Value['IsConfirm'] == 'Yes') {
                            $IsCancelled = 0;
                        } else {
                            $JoinedPercent = ($Value['TotalJoined'] * 100) / $Value['ContestSize'];
                            $IsCancelled = ($JoinedPercent >= 100) ? 0 : 1;
                        }
                    }
                }
                if ($IsCancelled == 0) {
                    continue;
                }
            }
            /* Update Contest Status */
            $this->db->where('EntityID', $Value['ContestID']);
            $this->db->limit(1);
            $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
        }
    }

    /*
      Description: To Auto Cancel Contest refund amount
     */

    function refundAmountCancelContest($CronID) {
        ini_set('max_execution_time', 300);
        /* Get Contest Data */
        $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,C.ContestSize,C.IsConfirm');
        $this->db->from('sports_contest C,tbl_entity E');
        $this->db->where("E.EntityID", "C.ContestID", FALSE);
        $this->db->where("C.IsRefund", "No");
        $this->db->where("C.LeagueType", "Dfs");
        $this->db->where("E.StatusID", 3);
        $this->db->limit(30);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $ContestsUsers = $Query->result_array();

            foreach ($ContestsUsers as $Value) {
                /* Get Joined Contest Users */
                $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.IsRefund,JC.JoinWalletAmount,JC.JoinWinningAmount,JC.JoinCashBonus');
                $this->db->from('sports_contest_join JC, tbl_users U');
                $this->db->where("JC.UserID", "U.UserID", FALSE);
                $this->db->where("JC.IsRefund", "No");
                $this->db->where("JC.ContestID", $Value['ContestID']);
                $this->db->where("U.UserTypeID !=", 3);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $JoinedContestsUsers = $Query->result_array();
                    foreach ($JoinedContestsUsers as $JoinValue) {

                        /* Refund Wallet Money */
                        if ($Value['EntryFee'] > 0) {
                            /* Get Wallet Details */
                            $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                                'UserID' => $JoinValue['UserID'],
                                'EntityID' => $Value['ContestID'],
                                'UserTeamID' => $JoinValue['UserTeamID'],
                                'Narration' => 'Cancel Contest'
                            ));

                            if (!empty($WalletDetails)) {
                                /** update user refund status Yes */
                                $this->db->where('ContestID', $JoinValue['ContestID']);
                                $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                                $this->db->where('UserID', $JoinValue['UserID']);
                                $this->db->limit(1);
                                $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                                continue;
                            }
                            /* Get Wallet Details */
                            /* $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                              'UserID' => $JoinValue['UserID'],
                              'EntityID' => $Value['ContestID'],
                              'UserTeamID' => $JoinValue['UserTeamID'],
                              'Narration' => 'Join Contest'
                              )); */
                            $InsertData = array(
                                "Amount" => $JoinValue['JoinWalletAmount'] + $JoinValue['JoinWinningAmount'] + $JoinValue['JoinCashBonus'],
                                "WalletAmount" => $JoinValue['JoinWalletAmount'],
                                "WinningAmount" => $JoinValue['JoinWinningAmount'],
                                "CashBonus" => $JoinValue['JoinCashBonus'],
                                "TransactionType" => 'Cr',
                                "EntityID" => $Value['ContestID'],
                                "UserTeamID" => $JoinValue['UserTeamID'],
                                "Narration" => 'Cancel Contest',
                                "EntryDate" => date("Y-m-d H:i:s")
                            );
                            $TotalRefundAmount = $JoinValue['JoinWalletAmount'] + $JoinValue['JoinWinningAmount'] + $JoinValue['JoinCashBonus'];
                            $this->Users_model->addToWallet($InsertData, $JoinValue['UserID'], 5);

                            /** update user refund status Yes */
                            $this->db->where('ContestID', $JoinValue['ContestID']);
                            $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                            $this->db->where('UserID', $JoinValue['UserID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));

                            /** add notification cancel contest */
                            $this->Notification_model->addNotification('refund', 'Contest Cancelled Refund', $JoinValue['UserID'], $JoinValue['UserID'], $Value['ContestID'], 'Your Rs.' . $TotalRefundAmount . ' has been refunded.');
                        } else {
                            /** update user refund status Yes */
                            $this->db->where('ContestID', $JoinValue['ContestID']);
                            $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                            $this->db->where('UserID', $JoinValue['UserID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                        }
                    }
                } else {
                    /* Update Contest Refund Status Yes */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsRefund' => "Yes"));
                }
            }
        }
    }

    function refundAmountCancelContestOLD($CronID) {
        ini_set('max_execution_time', 300);
        /* Get Contest Data */
        $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,C.ContestSize,C.IsConfirm');
        $this->db->from('sports_contest C,tbl_entity E');
        $this->db->where("E.EntityID", "C.ContestID", FALSE);
        //$this->db->where("C.IsRefund", "No");
        $this->db->where("C.LeagueType", "Dfs");
        $this->db->where("E.StatusID", 3);
        $this->db->where("C.MatchID", 60095);
        $this->db->limit(200);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $ContestsUsers = $Query->result_array();
            $CancelContestUser = array();
            foreach ($ContestsUsers as $Value) {
                /* Get Joined Contest Users */
                $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.IsRefund,JC.JoinWalletAmount,JC.JoinWinningAmount,JC.JoinCashBonus');
                $this->db->from('sports_contest_join JC, tbl_users U');
                $this->db->where("JC.UserID", "U.UserID", FALSE);
                //$this->db->where("JC.IsRefund", "No");
                $this->db->where("JC.ContestID", $Value['ContestID']);
                $this->db->where("U.UserTypeID !=", 3);
                $Query = $this->db->get();
                $JoinedContestsUsers = $Query->result_array();
                //dump($JoinedContestsUsers);
                if ($Query->num_rows() > 0) {
                    $JoinedContestsUsers = $Query->result_array();

                    foreach ($JoinedContestsUsers as $JoinValue) {
                        /* Refund Wallet Money */
                        if ($Value['EntryFee'] > 0) {
                            /* Get Wallet Details */
                            $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                                'UserID' => $JoinValue['UserID'],
                                'EntityID' => $Value['ContestID'],
                                'UserTeamID' => $JoinValue['UserTeamID'],
                                'Narration' => 'Cancel Contest'
                            ));
                            if (!empty($WalletDetails)) {
                                /** update user refund status Yes */
                                $this->db->where('ContestID', $JoinValue['ContestID']);
                                $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                                $this->db->where('UserID', $JoinValue['UserID']);
                                $this->db->limit(1);
                                $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                                //continue;
                            } else {
                                print_r($JoinedContestsUsers);
//                                print_r($WalletDetails1);
                                $TotalRefundAmount = $JoinValue['JoinWalletAmount'] + $JoinValue['JoinWinningAmount'] + $JoinValue['JoinCashBonus'];
                                $InsertData = array(
                                    "UserID" => $JoinValue['UserID'],
                                    "Amount" => $TotalRefundAmount,
                                    "WalletAmount" => $JoinValue['JoinWalletAmount'],
                                    "WinningAmount" => $JoinValue['JoinWinningAmount'],
                                    "CashBonus" => $JoinValue['JoinCashBonus'],
                                    "TransactionType" => 'Cr',
                                    "EntityID" => $Value['ContestID'],
                                    "UserTeamID" => $JoinValue['UserTeamID'],
                                    "Narration" => 'Cancel Contest',
                                    "EntryDate" => date("Y-m-d H:i:s")
                                );
                                $this->Users_model->addToWallet($InsertData, $JoinValue['UserID'], 5);
                                exit;
                            }

                            /* Get Wallet Details */
//                            $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
//                                'UserID' => $JoinValue['UserID'],
//                                'EntityID' => $Value['ContestID'],
//                                'UserTeamID' => $JoinValue['UserTeamID'],
//                                'Narration' => 'Join Contest'
//                            ));
//                            if (!empty($WalletDetails)) {
//                                $InsertData = array(
//                                    "Amount" => $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['CashBonus'],
//                                    "WalletAmount" => $WalletDetails['WalletAmount'],
//                                    "WinningAmount" => $WalletDetails['WinningAmount'],
//                                    "CashBonus" => $WalletDetails['CashBonus'],
//                                    "TransactionType" => 'Cr',
//                                    "EntityID" => $Value['ContestID'],
//                                    "UserTeamID" => $JoinValue['UserTeamID'],
//                                    "Narration" => 'Cancel Contest',
//                                    "EntryDate" => date("Y-m-d H:i:s")
//                                );
//                                $TotalRefundAmount = $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['CashBonus'];
//
//                                //$this->Users_model->addToWallet($InsertData, $JoinValue['UserID'], 5);
//                                // $this->Notification_model->addNotification('refund', 'Contest Cancelled Refund', $JoinValue['UserID'], $JoinValue['UserID'], $Value['ContestID'], 'Your Rs.' . $TotalRefundAmount . ' has been refunded.');
//                            }

                            /** update user refund status Yes */
//                            $this->db->where('ContestID', $JoinValue['ContestID']);
//                            $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
//                            $this->db->where('UserID', $JoinValue['UserID']);
//                            $this->db->limit(1);
//                            $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                        }
                    }
                } else {
                    /* Update Contest Refund Status Yes */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('IsRefund' => "Yes"));
                }
            }
            dump($CancelContestUser);
        }
    }

    /*
      Description: To set auction draft winners
     */

    function setAuctionDraftWinner($CronID) {

        ini_set('max_execution_time', 300);

        /* get auction draft series completed */
        $this->db->select('SeriesID,RoundID,AuctionDraftStatusID,AuctionDraftIsPlayed,RoundID');
        $this->db->from('sports_series_rounds');
        $this->db->where("AuctionDraftIsPlayed", "Yes");
        $this->db->where("AuctionDraftStatusID", 5);
        $this->db->where("StatusID", 5);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Series = $Query->result_array();

            foreach ($Series as $Rows) {

                $Contests = $this->AuctionDrafts_model->getContests('SeriesID,WinningAmount,NoOfWinners,ContestID,EntryFee,TotalJoined,ContestSize,CustomizeWinning', array('AuctionStatusID' => 5, 'IsWinningDistributed' => 'No',
                    "SeriesID" => $Rows['SeriesID'], "RoundID" => $Rows['RoundID']), true, 1, 500);

                if (isset($Contests['Data']['Records'])) {

                    foreach ($Contests['Data']['Records'] as $Value) {
                        //$JoinedContestsUsers = $this->AuctionDrafts_model->getJoinedContestsUsers('UserRank,UserTeamID,TotalPoints,UserRank,UserID,FirstName,Email', array('ContestID' => $Value['ContestID'], "IsWinningDistributeAmount" => "Yes", 'OrderBy' => 'JC.UserRank', 'Sequence' => 'DESC', 'PointFilter' => 'TotalPoints'), true, 0);
                        $JoinedContestsUsers = $this->AuctionDrafts_model->getJoinedContestsUsersWithOutTeam('UserRank,UserTeamID,TotalPoints,UserRank,UserID,FirstName,Email', array('ContestID' => $Value['ContestID'], "IsWinningDistributeAmount" => "No",
                            'OrderBy' => 'JC.UserRank', 'Sequence' => 'DESC', 'PointFilter' => 'TotalPoints'), true, 0);
                        if (!empty($JoinedContestsUsers)) {
                            $AllUsersRank = array_column($JoinedContestsUsers['Data']['Records'], 'UserRank');
                            $AllRankWinners = array_count_values($AllUsersRank);
                            $userWinnersData = $OptionWinner = array();
                            $CustomizeWinning = $Value['CustomizeWinning'];
                            if (empty($CustomizeWinning)) {
                                $CustomizeWinning[] = array(
                                    'From' => 1,
                                    'To' => $Value['NoOfWinners'],
                                    'Percent' => 100,
                                    'WinningAmount' => $Value['WinningAmount']
                                );
                            }
                            /** calculate amount according to rank or contest winning configuration */
                            foreach ($AllRankWinners as $Rank => $WinnerValue) {
                                $Flag = $TotalAmount = $AmountPerUser = 0;
                                for ($J = 0; $J < count($CustomizeWinning); $J++) {
                                    $FromWinner = $CustomizeWinning[$J]['From'];
                                    $ToWinner = $CustomizeWinning[$J]['To'];
                                    if ($Rank >= $FromWinner && $Rank <= $ToWinner) {
                                        $TotalAmount = $CustomizeWinning[$J]['WinningAmount'];
                                        if ($WinnerValue > 1) {
                                            $L = 0;
                                            for ($k = 1; $k < $WinnerValue; $k++) {
                                                if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                                    if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                        $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                        $Flag = 1;
                                                    } else {
                                                        $L = $L + 1;
                                                        if (!empty($CustomizeWinning[$J + $L]['From']) && !empty($CustomizeWinning[$J + $L]['To'])) {
                                                            if ($Rank + $k >= $CustomizeWinning[$J + $L]['From'] && $Rank + $k <= $CustomizeWinning[$J + $L]['To']) {
                                                                $TotalAmount += $CustomizeWinning[$J + $L]['WinningAmount'];
                                                                $Flag = 1;
                                                            }
                                                        }
                                                    }
                                                }
                                                if ($Flag == 0) {
                                                    if ($Rank + $k >= $CustomizeWinning[$J]['From'] && $Rank + $k <= $CustomizeWinning[$J]['To']) {
                                                        $TotalAmount += $CustomizeWinning[$J]['WinningAmount'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                $AmountPerUser = $TotalAmount / $WinnerValue;
                                $userWinnersData[] = $this->findKeyValueArray($JoinedContestsUsers['Data']['Records'], $Rank, $AmountPerUser);
                            }
                            foreach ($userWinnersData as $WinnerArray) {
                                foreach ($WinnerArray as $WinnerRow) {
                                    $OptionWinner[] = $WinnerRow;
                                }
                            }
                            //print_r($OptionWinner);exit;    
                            if (!empty($OptionWinner)) {
                                foreach ($OptionWinner as $WinnerValue) {

                                    $this->db->trans_start();

                                    /** join contest user winning amount update * */
                                    $this->db->where('UserID', $WinnerValue['UserID']);
                                    $this->db->where('ContestID', $Value['ContestID']);
                                    $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
                                    $this->db->limit(1);
                                    $this->db->update('sports_contest_join', array('UserWinningAmount' => $WinnerValue['UserWinningAmount'],
                                        "IsWinningDistributeAmount" => "Yes", 'ModifiedDate' => date('Y-m-d H:i:s')));


                                    if ($WinnerValue['UserWinningAmount'] > 0) {

                                        /** TDS * */
                                        if ($WinnerValue['UserWinningAmount'] > 10000) {
                                            $DeductAmount = ($WinnerValue['UserWinningAmount'] * TDS_PERCENTAGE) / 100;
                                            $DeductAmount = round($DeductAmount, 2);
                                            $WinnerValue['UserWinningAmount'] = $WinnerValue['UserWinningAmount'] - $DeductAmount;

                                            /** add transaction * */
                                            $InsertData = array_filter(array(
                                                "UserID" => $WinnerValue['UserID'],
                                                "Amount" => $DeductAmount,
                                                "TransactionType" => "Dr",
                                                "TransactionID" => substr(hash('sha256', mt_rand() . microtime()), 0, 20),
                                                "Narration" => "TDS Deducted",
                                                "EntityID" => $Value['ContestID'],
                                                "UserTeamID" => $WinnerValue['UserTeamID'],
                                                "EntryDate" => date("Y-m-d H:i:s"),
                                                "StatusID" => 5
                                            ));
                                            $this->db->insert('tbl_users_wallet', $InsertData);
                                        }

                                        $TransactionID = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                                        $WalletData = array(
                                            "Amount" => $WinnerValue['UserWinningAmount'],
                                            "WinningAmount" => $WinnerValue['UserWinningAmount'],
                                            "TransactionType" => 'Cr',
                                            "TransactionID" => $TransactionID,
                                            "EntityID" => $Value['ContestID'],
                                            "UserTeamID" => $WinnerValue['UserTeamID'],
                                            "Narration" => 'Join Contest Winning',
                                            "EntryDate" => date("Y-m-d H:i:s")
                                        );
                                        $this->Users_model->addToWallet($WalletData, $WinnerValue['UserID'], 5);

                                        /* $UserData = $this->Users_model->getUsers('FirstName,Email', array('UserID' => $WinnerValue['UserID']));
                                          if (!empty($UserData['Email'])) {
                                          send_mail(array(
                                          'emailTo'       => $UserData['Email'],
                                          'template_id'   => 'd-8bda083386954f34b8cdd625c6b9772a',
                                          'Subject'       => 'You’re a winner- '. SITE_NAME,
                                          'Name'          => $UserData['FirstName'],
                                          'Message'       => "Congratulations! You have won ".DEFAULT_CURRENCY. $WinnerValue['UserWinningAmount']." in Auctions  for the ".$Rows['SeriesName']." Series."
                                          ));
                                          } */
                                    }

                                    $this->db->trans_complete();
                                    if ($this->db->trans_status() === false) {
                                        return false;
                                    }
                                }
                            }

                            /* update contest winner amount distribute flag set YES */
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes', "IsWinningDistributeAmount" => "Yes"));

                            $this->db->where('EntityID', $Value['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_entity', array('ModifiedDate' => date("Y-m-d H:i:s"),
                                "StatusID" => 5));
                        } else {
                            /* update contest winner amount distribute flag set YES */
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest', array('IsWinningDistributed' => 'Yes', "IsWinningDistributeAmount" => "Yes"));

                            $this->db->where('EntityID', $Value['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('tbl_entity', array('ModifiedDate' => date("Y-m-d H:i:s"),
                                "StatusID" => 5));
                        }
                    }
                } else {
                    /* $this->db->where('CronID', $CronID);
                      $this->db->limit(1);
                      $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
                      exit; */
                }
            }
        }
    }

    /*
      Description: To set matches data (Cricket API)
     */

    function getMatchesLiveCricketApi($CronID) {
        ini_set('max_execution_time', 300);

        /* Get Live Matches Data */
        $DatesArr = array(date('Y-m'), date('Y-m', strtotime('+1 month')));
        foreach ($DatesArr as $DateValue) {
            $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/schedule/?date=' . $DateValue . '&access_token=');
            // $Response = $this->callSportsAPI(MW_SPORTS_API_URL_CRICKETAPI . 'getMatchesLiveCricket?YearMonth=' . $DateValue);
            if (!$Response['status']) {
                $this->db->where('CronID', $CronID);
                $this->db->limit(1);
                $this->db->update('log_cron', array('CronStatus' => 'Exit'));
                return true;
            }
            $LiveMatchesData = @$Response['data']['months'][0]['days'];
            if (empty($LiveMatchesData)) continue;

            /* To get All Series Data */
            $SeriesIdsData = array();
            $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array(), true, 0);
            if ($SeriesData) {
                $SeriesIdsData = array_column($SeriesData['Data']['Records'], 'SeriesID', 'SeriesIDLive');
            }

            /* To get All Match Types */
            $MatchTypesData = $this->getMatchTypes();
            $MatchTypeIdsData = array_column($MatchTypesData, 'MatchTypeID', 'MatchTypeNameCricketAPI');

            foreach ($LiveMatchesData as $key => $Value) {
                if (!empty($Value['matches'])) {
                    // continue;
                    /* $this->db->trans_start(); */

                    foreach ($Value['matches'] as $MatchValue) {

                        /* Manage Series Data */
                        if (!isset($SeriesIdsData[$MatchValue['season']['key']])) {

                            /* Add series to entity table and get EntityID. */
                            $SeriesGUID = get_guid();
                            $SeriesID = $this->Entity_model->addEntity($SeriesGUID, array("EntityTypeID" => 7, "StatusID" => 2));
                            $SeriesData = array_filter(array(
                                'SeriesID' => $SeriesID,
                                'SeriesGUID' => $SeriesGUID,
                                'SeriesIDLive' => $MatchValue['season']['key'],
                                'SeriesName' => $MatchValue['season']['name']
                            ));
                            $this->db->insert('sports_series', $SeriesData);
                            $SeriesIdsData[$MatchValue['season']['key']] = $SeriesID;
                        } else {
                            $SeriesID = $SeriesIdsData[$MatchValue['season']['key']];
                        }

                        /* Manage Teams */
                        $LocalTeam = $MatchValue['teams']['a'];
                        $VisitorTeam = $MatchValue['teams']['b'];
                        $LocalTeamData = $VisitorTeamData = $TeamLiveIds = array();
                        if ($LocalTeam['key'] == 'tbc' || $VisitorTeam['key'] == 'tbc') continue;

                        /* To check if local team is already exist */
                        $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = "' . $LocalTeam['key'] . '" LIMIT 1');
                        $TeamIDLocal = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                        if (!$TeamIDLocal) {

                            /* Add team to entity table and get EntityID. */
                            $TeamGUID = get_guid();
                            $TeamIDLocal = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                            $LocalTeamData[] = array(
                                'TeamID' => $TeamIDLocal,
                                'TeamGUID' => $TeamGUID,
                                'TeamIDLive' => $LocalTeam['key'],
                                'TeamName' => $LocalTeam['name'],
                                'TeamNameShort' => strtoupper($LocalTeam['key'])
                            );
                        }

                        /* To check if visitor team is already exist */
                        $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = "' . $VisitorTeam['key'] . '" LIMIT 1');
                        $TeamIDVisitor = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                        if (!$TeamIDVisitor) {

                            /* Add team to entity table and get EntityID. */
                            $TeamGUID = get_guid();
                            $TeamIDVisitor = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                            $VisitorTeamData[] = array(
                                'TeamID' => $TeamIDVisitor,
                                'TeamGUID' => $TeamGUID,
                                'TeamIDLive' => $VisitorTeam['key'],
                                'TeamName' => $VisitorTeam['name'],
                                'TeamNameShort' => strtoupper($VisitorTeam['key'])
                            );
                        }
                        $TeamsData = array_merge($VisitorTeamData, $LocalTeamData);
                        if (!empty($TeamsData)) {
                            $this->db->insert_batch('sports_teams', $TeamsData);
                        }

                        /* Manage Matches */
                        /* To check if match is already exist */
                        $Query = $this->db->query('SELECT M.APIAutoTimeUpdate,M.MatchID,E.StatusID FROM sports_matches M,tbl_entity E WHERE M.MatchID = E.EntityID AND M.MatchIDLive = "' . $MatchValue['key'] . '" LIMIT 1');
                        $MatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                        if (!$MatchID) {
                            /* if (trim(strtolower($MatchValue['format'])) == 't10') {
                              $MatchValue['format'] = 't20';
                              } */
                            /* Add matches to entity table and get EntityID. */
                            $MatchGUID = get_guid();
                            $MatchStatusArr = array('completed' => 5, 'notstarted' => 1,
                                'started' => 2);
                            $MatchesAPIData = array(
                                'MatchID' => $this->Entity_model->addEntity($MatchGUID, array("EntityTypeID" => 8, "StatusID" => $MatchStatusArr[$MatchValue['status']])),
                                'MatchGUID' => $MatchGUID,
                                'MatchIDLive' => $MatchValue['key'],
                                'SeriesID' => $SeriesID,
                                'MatchTypeID' => $MatchTypeIdsData[$MatchValue['format']],
                                'MatchNo' => $MatchValue['related_name'],
                                'MatchLocation' => $MatchValue['venue'],
                                'TeamIDLocal' => $TeamIDLocal,
                                'TeamIDVisitor' => $TeamIDVisitor,
                                'MatchStartDateTime' => date('Y-m-d H:i', strtotime($MatchValue['start_date']['iso']))
                            );

                            $this->db->insert('sports_matches', $MatchesAPIData);
                        } else {
                            $APIAutoTimeUpdate = ($Query->num_rows() > 0) ? $Query->row()->APIAutoTimeUpdate : "No";
                            if ($Query->row()->StatusID != 1) continue; // Pending Match

                                /* Update Match Data */
                            $MatchesAPIData = array(
                                'MatchNo' => $MatchValue['related_name'],
                                'MatchLocation' => $MatchValue['venue'],
                                'TeamIDLocal' => $TeamIDLocal,
                                'TeamIDVisitor' => $TeamIDVisitor,
                                'MatchStartDateTime' => date('Y-m-d H:i', strtotime($MatchValue['start_date']['iso'])),
                                'LastUpdatedOn' => date('Y-m-d H:i:s')
                            );
                            if ($APIAutoTimeUpdate == "No") {
                                $this->db->where('MatchID', $MatchID);
                                $this->db->limit(1);
                                $this->db->update('sports_matches', $MatchesAPIData);
                            }
                        }
                    }

                    /* $this->db->trans_complete();
                      if ($this->db->trans_status() === false) {
                      return false;
                      } */
                }
            }
        }
    }

    /*
      Description: To set players data (Entity API)
     */

    function getPlayersLiveEntity($CronID) {
        ini_set('max_execution_time', 300);

        /* Get series data */
        $SeriesData = $this->getSeries('SeriesIDLive,SeriesID', array('StatusID' => 2, 'SeriesEndDate' => date('Y-m-d')), true, 0);
        if (!$SeriesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit'));
            exit;
        }

        /* Player Roles */
        $PlayerRolesArr = array('bowl' => 'Bowler', 'bat' => 'Batsman', 'wkbat' => 'WicketKeeper',
            'wk' => 'WicketKeeper', 'all' => 'AllRounder');
        foreach ($SeriesData['Data']['Records'] as $Value) {
            $Response = $this->callSportsAPI(SPORTS_API_URL_ENTITY . '/v2/competitions/' . $Value['SeriesIDLive'] . '/squads/?token=');

            if (empty($Response['response']['squads'])) continue;
            foreach ($Response['response']['squads'] as $SquadsValue) {

                $this->db->trans_start();

                /* To check if visitor team is already exist */
                $IsNewTeam = false;
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = ' . $SquadsValue['team_id'] . ' LIMIT 1');
                $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamID) {

                    /* Add team to entity table and get EntityID. */
                    $TeamGUID = get_guid();
                    $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $TeamData = array_filter(array(
                        'TeamID' => $TeamID,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $SquadsValue['team']['tid'],
                        'TeamName' => $SquadsValue['team']['title'],
                        'TeamNameShort' => $SquadsValue['team']['abbr'],
                        'TeamFlag' => $SquadsValue['team']['thumb_url'],
                    ));
                    $IsNewTeam = true;
                    $this->db->insert('sports_teams', $TeamData);
                }
                if (!$IsNewTeam) {

                    /* To get all match ids */
                    $Query = $this->db->query('SELECT MatchID FROM `sports_matches` WHERE `SeriesID` = ' . $Value['SeriesID'] . ' AND (`TeamIDLocal` = ' . $TeamID . ' OR `TeamIDVisitor` = ' . $TeamID . ')');
                    $MatchIds = ($Query->num_rows() > 0) ? array_column($Query->result_array(), 'MatchID') : array();
                }

                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }

                foreach ($SquadsValue['players'] as $PlayerValue) {

                    $this->db->trans_start();

                    /* To check if player is already exist */
                    $Query = $this->db->query('SELECT PlayerID FROM sports_players WHERE PlayerIDLive = ' . $PlayerValue['pid'] . ' LIMIT 1');
                    $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                    if (!$PlayerID) {

                        /* Add players to entity table and get EntityID. */
                        $PlayerGUID = get_guid();
                        $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                        $PlayersAPIData = array(
                            'PlayerID' => $PlayerID,
                            'PlayerGUID' => $PlayerGUID,
                            'PlayerIDLive' => $PlayerValue['pid'],
                            'PlayerName' => $PlayerValue['title'],
                            'PlayerCountry' => ($PlayerValue['country']) ? strtoupper($PlayerValue['country']) : null,
                            'PlayerBattingStyle' => ($PlayerValue['batting_style']) ? $PlayerValue['batting_style'] : null,
                            'PlayerBowlingStyle' => ($PlayerValue['bowling_style']) ? $PlayerValue['bowling_style'] : null
                        );
                        $this->db->insert('sports_players', $PlayersAPIData);
                    }

                    /* To check If match player is already exist */
                    if (!$IsNewTeam && !empty($MatchIds)) {
                        $TeamPlayersData = array();
                        foreach ($MatchIds as $MatchID) {
                            $Query = $this->db->query('SELECT MatchID FROM sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND SeriesID = ' . $Value['SeriesID'] . ' AND TeamID = ' . $TeamID . ' AND MatchID =' . $MatchID . ' LIMIT 1');
                            $IsMatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                            if (!$IsMatchID) {
                                $TeamPlayersData[] = array(
                                    'SeriesID' => $Value['SeriesID'],
                                    'MatchID' => $MatchID,
                                    'TeamID' => $TeamID,
                                    'PlayerID' => $PlayerID,
                                    'PlayerRole' => $PlayerRolesArr[strtolower($PlayerValue['playing_role'])]
                                );
                            }
                        }
                        if (!empty($TeamPlayersData)) {
                            $this->db->insert_batch('sports_team_players', $TeamPlayersData);
                            // $this->db->cache_delete('sports', 'getPlayers'); //Delete Cache
                            // $this->db->cache_delete('admin', 'matches'); //Delete Cache
                        }
                    }

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === false) {
                        return false;
                    }
                }
            }
        }
    }

    /*
      Description: To set players data (Cricket API)
     */

    function getPlayersLiveCricketApi($CronID) {
        ini_set('max_execution_time', 300);
        /* Get matches data */
        $DateTime = date('Y-m-d H:i', strtotime('+7 days', strtotime(date('Y-m-d H:i'))));
        $MatchesData = $this->getMatches('MatchStartDateTime,MatchIDLive,MatchID,MatchType,SeriesIDLive,SeriesID,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff,RoundID', array('StatusID' => array(1, 2), "MatchStartDateTime" => $DateTime), true, 1, 100);
        //$MatchesData = $this->getMatches('MatchStartDateTime,MatchIDLive,MatchID,MatchType,SeriesIDLive,SeriesID,TeamIDLiveLocal,TeamIDLiveVisitor,LastUpdateDiff,RoundID', array('StatusID' => array(1, 2), "MatchStartDateTimeComplete" => $DateTime), true, 1, 100);
        if (!$MatchesData) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }

        foreach ($MatchesData['Data']['Records'] as $Value) {

            /* Get Both Teams */
            $TeamsArr = array($Value['TeamIDLiveLocal'] => $Value['SeriesIDLive'] . "_" . $Value['TeamIDLiveLocal'],
                $Value['TeamIDLiveVisitor'] => $Value['SeriesIDLive'] . "_" . $Value['TeamIDLiveVisitor']);
            foreach ($TeamsArr as $TeamKey => $TeamValue) {
                $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/season/' . $Value['SeriesIDLive'] . '/team/' . $TeamValue . '/?access_token=');
                // $Response = $this->callSportsAPI(MW_SPORTS_API_URL_CRICKETAPI . 'getPlayersLiveCricket?SeasonKey=' . $Value['SeriesIDLive'] . '&TeamKey=' .$TeamKey);
                // print_r($Response);die("vkj");
                if (empty($Response['data']['players_key'])) continue;

                $this->db->trans_start();

                /* To check if visitor team is already exist */
                $IsNewTeam = false;
                $Query = $this->db->query('SELECT TeamID FROM sports_teams WHERE TeamIDLive = "' . $TeamKey . '" LIMIT 1');
                $TeamID = ($Query->num_rows() > 0) ? $Query->row()->TeamID : false;
                if (!$TeamID) {

                    /* Add team to entity table and get EntityID. */
                    $TeamGUID = get_guid();
                    $TeamID = $this->Entity_model->addEntity($TeamGUID, array("EntityTypeID" => 9, "StatusID" => 2));
                    $TeamData = array_filter(array(
                        'TeamID' => $TeamID,
                        'TeamGUID' => $TeamGUID,
                        'TeamIDLive' => $TeamKey,
                        'TeamName' => $Response['data']['name'],
                        'TeamNameShort' => strtoupper($TeamKey)
                    ));
                    $IsNewTeam = true;
                    $this->db->insert('sports_teams', $TeamData);
                }
                if (!$IsNewTeam) {
                    /* To get all match ids */
                    $Query = $this->db->query('SELECT MatchID FROM `sports_matches` WHERE `SeriesID` = ' . $Value['SeriesID'] . ' AND (`TeamIDLocal` = ' . $TeamID . ' OR `TeamIDVisitor` = ' . $TeamID . ')');
                    $MatchIds = ($Query->num_rows() > 0) ? array_column($Query->result_array(), 'MatchID') : array();
                }

                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    return false;
                }

                /* Insert All Players */
                foreach ($Response['data']['players_key'] as $PlayerIDLive) {

                    $this->db->trans_start();

                    /* To check if player is already exist */
                    $Query = $this->db->query('SELECT PlayerID FROM sports_players WHERE PlayerIDLive = "' . $PlayerIDLive . '" LIMIT 1');
                    $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                    if (!$PlayerID) {

                        /* Add players to entity table and get EntityID. */
                        $PlayerGUID = get_guid();
                        $PlayerID = $this->Entity_model->addEntity($PlayerGUID, array("EntityTypeID" => 10, "StatusID" => 2));
                        $PlayersAPIData = array(
                            'PlayerID' => $PlayerID,
                            'PlayerGUID' => $PlayerGUID,
                            'PlayerIDLive' => $PlayerIDLive,
                            'PlayerName' => $Response['data']['players'][$PlayerIDLive]['name'],
                            'PlayerBattingStyle' => @$Response['data']['players'][$PlayerIDLive]['batting_style'][0],
                            'PlayerBowlingStyle' => @$Response['data']['players'][$PlayerIDLive]['bowling_style'][0],
                        );
                        $this->db->insert('sports_players', $PlayersAPIData);
                    }

                    /* To check If match player is already exist */
                    if (!$IsNewTeam && !empty($MatchIds)) {
                        $TeamPlayersData = array();
                        $TeamPlayersDataAuction = array();
                        foreach ($MatchIds as $MatchID) {
                            $Query = $this->db->query('SELECT MatchID FROM sports_team_players WHERE PlayerID = ' . $PlayerID . ' AND SeriesID = ' . $Value['SeriesID'] . ' AND TeamID = ' . $TeamID . ' AND MatchID =' . $MatchID . ' LIMIT 1');
                            $IsMatchID = ($Query->num_rows() > 0) ? $Query->row()->MatchID : false;
                            if (!$IsMatchID) {

                                /* Get Player Role */
                                $Keeper = $Response['data']['players'][$PlayerIDLive]['identified_roles']['keeper'];
                                $Batsman = $Response['data']['players'][$PlayerIDLive]['identified_roles']['batsman'];
                                $Bowler = $Response['data']['players'][$PlayerIDLive]['identified_roles']['bowler'];
                                $PlayerRole = ($Keeper == 1) ? 'WicketKeeper' : (($Batsman == 1 && $Bowler == 1) ? 'AllRounder' : ((empty($Batsman) && $Bowler == 1) ? 'Bowler' : ((empty($Bowler) && $Batsman == 1) ? 'Batsman' : '')));
                                if ($MatchID == $Value['MatchID']) {
                                    $TeamPlayersData[] = array(
                                        'SeriesID' => $Value['SeriesID'],
                                        'MatchID' => $MatchID,
                                        'TeamID' => $TeamID,
                                        'RoundID' => $Value['RoundID'],
                                        'PlayerID' => $PlayerID,
                                        'PlayerRole' => $PlayerRole
                                    );
                                }

                                if (!empty($Value['RoundID'])) {
                                    $TeamPlayersDataAuction[] = array(
                                        'SeriesID' => $Value['SeriesID'],
                                        'TeamID' => $TeamID,
                                        'RoundID' => $Value['RoundID'],
                                        'PlayerID' => $PlayerID,
                                        'PlayerRole' => $PlayerRole
                                    );
                                }
                            } else {
                                /* Get Player Role */
                                $Keeper = $Response['data']['players'][$PlayerIDLive]['identified_roles']['keeper'];
                                $Batsman = $Response['data']['players'][$PlayerIDLive]['identified_roles']['batsman'];
                                $Bowler = $Response['data']['players'][$PlayerIDLive]['identified_roles']['bowler'];
                                $PlayerRole = ($Keeper == 1) ? 'WicketKeeper' : (($Batsman == 1 && $Bowler == 1) ? 'AllRounder' : ((empty($Batsman) && $Bowler == 1) ? 'Bowler' : ((empty($Bowler) && $Batsman == 1) ? 'Batsman' : '')));
                                if (!empty($Value['RoundID'])) {
                                    $TeamPlayersDataAuction[] = array(
                                        'SeriesID' => $Value['SeriesID'],
                                        'TeamID' => $TeamID,
                                        'RoundID' => $Value['RoundID'],
                                        'PlayerID' => $PlayerID,
                                        'PlayerRole' => $PlayerRole
                                    );
                                }
                            }
                        }
                        if (!empty($TeamPlayersData)) {
                            $this->db->insert_batch('sports_team_players', $TeamPlayersData);
                        }
                        if (!empty($TeamPlayersDataAuction)) {
                            $this->addAuctionDraftSeriesPlayer($TeamPlayersDataAuction);
                        }
                    }

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === false) {
                        return false;
                    }
                }
            }

            $IsTeam = $this->db->query("SELECT UT.UserTeamID,UserTypeID FROM sports_users_teams UT,tbl_users U WHERE U.UserID=UT.UserID AND U.UserTypeID!=3 AND UT.MatchID = '" . $Value['MatchID'] . "' limit 1")->result_array();
            if (empty($IsTeam)) {
                /* Get Player Credit Points */
                $Response = $this->callSportsAPICredit(SPORTS_API_URL_CRICKETAPI . '/rest/v3/fantasy-match-credits/' . $Value['MatchIDLive'] . '/?access_token=');
                // $Response = $this->callSportsAPI(MW_SPORTS_API_URL_CRICKETAPI . 'getPlayerCreditPointsLiveCricket?MatchKey=' . $Value['MatchIDLive']);
                // print_r($Response);die("kjn");
                /* Manage CRON API Response */
                if (!empty($Response['data']['fantasy_points'])) {
                    foreach ($Response['data']['fantasy_points'] as $PlayerValue) {
                        $PlayerArr[] = array(
                            'PlayerSalary' => json_encode(array($Value['MatchType'] . 'Credits' => $PlayerValue['credit_value'])),
                            'PlayerIDLive' => $PlayerValue['player'],
                            'LastUpdatedOn' => date('Y-m-d H:i:s')
                        );
                        $Query = $this->db->query("SELECT PlayerID FROM sports_players WHERE PlayerIDLive = '" . $PlayerValue['player'] . "'");
                        $PlayerID = ($Query->num_rows() > 0) ? $Query->row()->PlayerID : false;
                        if (!empty($PlayerID)) {
                            $Query = $this->db->query("SELECT IsAdminUpdate FROM sports_team_players WHERE MatchID = '" . $Value['MatchID'] . "' AND PlayerID = '" . $PlayerID . "' AND IsAdminUpdate='Yes'");
                            if ($Query->num_rows() == 0) {
                                $MatchesAPIData = array(
                                    'PlayerSalary' => $PlayerValue['credit_value'],
                                );
                                $this->db->where('MatchID', $Value['MatchID']);
                                $this->db->where('PlayerID', $PlayerID);
                                $this->db->limit(1);
                                $this->db->update('sports_team_players', $MatchesAPIData);
                            }
                        }
                    }
                    $this->db->update_batch('sports_players', $PlayerArr, 'PlayerIDLive');
                }

                /* Update Last Updated Status */
                $this->db->where('MatchID', $Value['MatchID']);
                $this->db->limit(1);
                $this->db->update('sports_matches', array('LastUpdatedOn' => date('Y-m-d H:i:s')));

                $this->getPlayerStatsLiveCricketApi(NULL, $Value['MatchID']);
            }
        }

        $this->updateMatchwisePlayerStatus($MatchesData['Data']['Records']);
    }

    function updateMatchwisePlayerStatus($Matches) {

        if (!empty($Matches)) {
            foreach ($Matches as $Value) {

                $Query = $this->db->query('SELECT TP.PlayerID,P.PlayerIDLive FROM sports_team_players TP,sports_players P WHERE P.PlayerID=TP.PlayerID AND TP.MatchID = "' . $Value['MatchID'] . '"');
                $Players = ($Query->num_rows() > 0) ? $Query->result_array() : array();
                if (!empty($Players)) {
                    $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/match/' . $Value['MatchIDLive'] . '/?access_token=');

                    if (empty($Response['data']['card']['players'])) continue;

                    $AllPlayingPlayers = $Response['data']['card']['players'];
                    foreach ($Players as $Rows) {
                        if (isset($AllPlayingPlayers[trim($Rows['PlayerIDLive'])]) && !empty($AllPlayingPlayers[trim($Rows['PlayerIDLive'])])) {

                            /** check player in user team * */
                            $Query = $this->db->query('SELECT PlayerID FROM sports_users_team_players WHERE PlayerID = ' . $Rows['PlayerID'] . ' AND  MatchID =' . $Value['MatchID'] . ' LIMIT 1');
                            if ($Query->num_rows() > 0) continue;

                            /** check player update by admin * */
                            $Query = $this->db->query('SELECT IsAdminUpdate FROM sports_team_players WHERE PlayerID = ' . $Rows['PlayerID'] . ' AND  MatchID =' . $Value['MatchID'] . ' LIMIT 1');
                            if ($Query->row()->IsAdminUpdate == "Yes") continue;

                            /** Get Player Role * */
                            $LivePlayer = $AllPlayingPlayers[trim($Rows['PlayerIDLive'])];

                            $SeasonalRole = $LivePlayer['seasonal_role'];
                            $Keeper = $LivePlayer['identified_roles']['keeper'];
                            $Batsman = $LivePlayer['identified_roles']['batsman'];
                            $Bowler = $LivePlayer['identified_roles']['bowler'];
                            $PlayerRole = ($Keeper == 1) ? 'WicketKeeper' : (($Batsman == 1 && $Bowler == 1) ? 'AllRounder' : ((empty($Batsman) && $Bowler == 1) ? 'Bowler' : ((empty($Bowler) && $Batsman == 1) ? 'Batsman' : '')));
                            if ($SeasonalRole == "keeper" && $PlayerRole == "WicketKeeper") {
                                $PlayerRole = "WicketKeeper";
                            } else if ($SeasonalRole == "batsman" && $PlayerRole == "WicketKeeper") {
                                $PlayerRole = "Batsman";
                            }
                            /** update player role * */
                            $MatchesAPIData = array(
                                'PlayerRole' => $PlayerRole,
                                'IsActive' => "Yes"
                            );
                            $this->db->where('MatchID', $Value['MatchID']);
                            $this->db->where('PlayerID', $Rows['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('sports_team_players', $MatchesAPIData);
                        } else {
                            /** player not play * */
                            $LivePlayer = $AllPlayingPlayers[trim($Rows['PlayerIDLive'])];
                            /** check player in user team * */
                            $Query = $this->db->query('SELECT PlayerID FROM sports_users_team_players WHERE PlayerID = ' . $Rows['PlayerID'] . ' AND  MatchID =' . $Value['MatchID'] . ' LIMIT 1');
                            if ($Query->num_rows() > 0) continue;

                            /** update player status * */
                            $MatchesAPIData = array(
                                'IsActive' => "No"
                            );
                            $this->db->where('MatchID', $Value['MatchID']);
                            $this->db->where('PlayerID', $Rows['PlayerID']);
                            $this->db->limit(1);
                            $this->db->update('sports_team_players', $MatchesAPIData);
                        }
                    }
                }
            }
        }
    }

    function addAuctionDraftSeriesPlayer($Players) {
        $TeamPlayersData = array();
        $TeamPlayersDataPoints = array();
        if (!empty($Players)) {
            $Players = array_unique($Players);
            foreach ($Players as $Player) {
                $Query = $this->db->query('SELECT RoundID FROM sports_auction_draft_series_players WHERE PlayerID = ' . $Player['PlayerID'] . ' AND SeriesID = ' . $Player['SeriesID'] . ' AND RoundID = ' . $Player['RoundID'] . ' LIMIT 1');
                if ($Query->num_rows() == 0) {
                    $TeamPlayersData[] = array(
                        'SeriesID' => $Player['SeriesID'],
                        'PlayerID' => $Player['PlayerID'],
                        'RoundID' => $Player['RoundID'],
                        'TeamID' => $Player['TeamID'],
                        'PlayerRole' => $Player['PlayerRole']
                    );
                }
                $Querys = $this->db->query('SELECT RoundID FROM sports_auction_draft_player_point WHERE PlayerID = ' . $Player['PlayerID'] . ' AND SeriesID = ' . $Player['SeriesID'] . ' AND RoundID = ' . $Player['RoundID'] . ' LIMIT 1');
                if ($Querys->num_rows() == 0) {
                    $TeamPlayersDataPoints[] = array(
                        'SeriesID' => $Player['SeriesID'],
                        'PlayerID' => $Player['PlayerID'],
                        'RoundID' => $Player['RoundID'],
                        'PlayerRole' => $Player['PlayerRole'],
                        'ContestID' => 0
                    );
                }
            }
            if (!empty($TeamPlayersData)) {
                $this->db->insert_batch('sports_auction_draft_series_players', $TeamPlayersData);
            }
            if (!empty($TeamPlayersDataPoints)) {
                $this->db->insert_batch('sports_auction_draft_player_point', $TeamPlayersDataPoints);
            }
        }
        return true;
    }

    /*
      Description: To set player stats (Cricket API)
     */

    function getPlayerStatsLiveCricketApi($CronID = "", $MatchID = "") {

        ini_set('max_execution_time', 300);
        /* To get All Player Stats Data */
        if (!empty($MatchID)) {
            $MatchData = $this->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID,MatchStartDateTime,CompetitionStateKey', array('MatchID' => $MatchID, "LivePlayerStatusUpdate" => "No"), true, 0);
        } else {
            $MatchData = $this->getMatches('MatchID,MatchIDLive,SeriesIDLive,SeriesID,MatchStartDateTime,CompetitionStateKey', array('StatusID' => 5, 'PlayerStatsUpdate' => 'No', 'MatchCompleteDateTime' => date('Y-m-d H:i:s')), true, 0);
        }
        if ($MatchData) {
            if ($MatchData['Data']['TotalRecords'] > 0) {
                foreach ($MatchData['Data']['Records'] as $Value) {
                    $PlayerData = $this->getPlayers('PlayerIDLive,PlayerID,MatchID', array('MatchID' => $Value['MatchID']), true, 0);
                    if (empty($PlayerData)) continue;

                    foreach ($PlayerData['Data']['Records'] as $Rows) {
                        $CompetitionStateKey = strtolower($Value['CompetitionStateKey']);
                        if (empty($CompetitionStateKey)) {
                            $CompetitionStateKey = "icc";
                        }
                        /* Call Player Stats API */
                        $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/player/' . $Rows['PlayerIDLive'] . '/league/' . $CompetitionStateKey . '/stats/?access_token=');
                        /* Manage Batting Stats */
                        $BattingStats = new stdClass();
                        $BowlingStats = new stdClass();
                        $FieldingStats = new stdClass();
                        /* Test Batting Stats */
                        $TestReponse = $Response['data']['player']['stats']['test'];
                        $OneDayReponse = $Response['data']['player']['stats']['one-day'];
                        $T20Reponse = $Response['data']['player']['stats']['t20'];
                        $BattingStats->Test = (object) array(
                                    'MatchID' => 0,
                                    'InningID' => 0,
                                    'Matches' => (!empty($TestReponse['batting']['matches']) ? $TestReponse['batting']['matches'] : 0),
                                    'Innings' => (!empty($TestReponse['batting']['innings']) ? $TestReponse['batting']['innings'] : 0),
                                    'NotOut' => (!empty($TestReponse['batting']['not_outs']) ? $TestReponse['batting']['not_outs'] : 0),
                                    'Runs' => (!empty($TestReponse['batting']['runs']) ? $TestReponse['batting']['runs'] : 0),
                                    'Balls' => (!empty($TestReponse['batting']['balls']) ? $TestReponse['batting']['balls'] : 0),
                                    'HighestScore' => @$TestReponse['batting']['high_score'],
                                    'Hundreds' => (!empty($TestReponse['batting']['hundreds']) ? $TestReponse['batting']['hundreds'] : 0),
                                    'Fifties' => (!empty($TestReponse['batting']['fifties']) ? $TestReponse['batting']['fifties'] : 0),
                                    'Fours' => (!empty($TestReponse['batting']['fours']) ? $TestReponse['batting']['fours'] : 0),
                                    'Sixes' => (!empty($TestReponse['batting']['sixes']) ? $TestReponse['batting']['sixes'] : 0),
                                    'Average' => (!empty($TestReponse['batting']['average']) ? $TestReponse['batting']['average'] : 0),
                                    'StrikeRate' => (!empty($TestReponse['batting']['strike_rate']) ? $TestReponse['batting']['strike_rate'] : 0),
                                    'Catches' => (!empty($TestReponse['fielding']['catches']) ? $TestReponse['fielding']['catches'] : 0),
                                    'Stumpings' => (!empty($TestReponse['fielding']['stumpings']) ? $TestReponse['fielding']['stumpings'] : 0)
                        );
                        /* ODI Batting Stats */
                        $BattingStats->ODI = (object) array(
                                    'MatchID' => 0,
                                    'InningID' => 0,
                                    'Matches' => (!empty($OneDayReponse['batting']['matches']) ? $OneDayReponse['batting']['matches'] : 0),
                                    'Innings' => (!empty($OneDayReponse['batting']['innings']) ? $OneDayReponse['batting']['innings'] : 0),
                                    'NotOut' => (!empty($OneDayReponse['batting']['not_outs']) ? $OneDayReponse['batting']['not_outs'] : 0),
                                    'Runs' => (!empty($OneDayReponse['batting']['runs']) ? $OneDayReponse['batting']['runs'] : 0),
                                    'Balls' => (!empty($OneDayReponse['batting']['balls']) ? $OneDayReponse['batting']['balls'] : 0),
                                    'HighestScore' => @$OneDayReponse['batting']['high_score'],
                                    'Hundreds' => (!empty($OneDayReponse['batting']['hundreds']) ? $OneDayReponse['batting']['hundreds'] : 0),
                                    'Fifties' => (!empty($OneDayReponse['batting']['fifties']) ? $OneDayReponse['batting']['fifties'] : 0),
                                    'Fours' => (!empty($OneDayReponse['batting']['fours']) ? $OneDayReponse['batting']['fours'] : 0),
                                    'Sixes' => (!empty($OneDayReponse['batting']['sixes']) ? $OneDayReponse['batting']['sixes'] : 0),
                                    'Average' => (!empty($OneDayReponse['batting']['average']) ? $OneDayReponse['batting']['average'] : 0),
                                    'StrikeRate' => (!empty($OneDayReponse['batting']['strike_rate']) ? $OneDayReponse['batting']['strike_rate'] : 0),
                                    'Catches' => (!empty($OneDayReponse['fielding']['catches']) ? $OneDayReponse['fielding']['catches'] : 0),
                                    'Stumpings' => (!empty($OneDayReponse['fielding']['stumpings']) ? $OneDayReponse['fielding']['stumpings'] : 0)
                        );
                        /* T20 Batting Stats */
                        $BattingStats->T20 = (object) array(
                                    'MatchID' => 0,
                                    'InningID' => 0,
                                    'Matches' => (!empty($T20Reponse['batting']['matches']) ? $T20Reponse['batting']['matches'] : 0),
                                    'Innings' => (!empty($T20Reponse['batting']['innings']) ? $T20Reponse['batting']['innings'] : 0),
                                    'NotOut' => (!empty($T20Reponse['batting']['not_outs']) ? $T20Reponse['batting']['not_outs'] : 0),
                                    'Runs' => (!empty($T20Reponse['batting']['runs']) ? $T20Reponse['batting']['runs'] : 0),
                                    'Balls' => (!empty($T20Reponse['batting']['balls']) ? $T20Reponse['batting']['balls'] : 0),
                                    'HighestScore' => @$T20Reponse['batting']['high_score'],
                                    'Hundreds' => (!empty($T20Reponse['batting']['hundreds']) ? $T20Reponse['batting']['hundreds'] : 0),
                                    'Fifties' => (!empty($T20Reponse['batting']['fifties']) ? $T20Reponse['batting']['fifties'] : 0),
                                    'Fours' => (!empty($T20Reponse['batting']['fours']) ? $T20Reponse['batting']['fours'] : 0),
                                    'Sixes' => (!empty($T20Reponse['batting']['sixes']) ? $T20Reponse['batting']['sixes'] : 0),
                                    'Average' => (!empty($T20Reponse['batting']['average']) ? $T20Reponse['batting']['average'] : 0),
                                    'StrikeRate' => (!empty($T20Reponse['batting']['strike_rate']) ? $T20Reponse['batting']['strike_rate'] : 0),
                                    'Catches' => (!empty($T20Reponse['fielding']['catches']) ? $T20Reponse['fielding']['catches'] : 0),
                                    'Stumpings' => (!empty($T20Reponse['fielding']['stumpings']) ? $T20Reponse['fielding']['stumpings'] : 0)
                        );

                        /* Test Bowling Stats */
                        $BowlingStats->Test = (object) array(
                                    'MatchID' => 0,
                                    'InningID' => 0,
                                    'Matches' => (!empty($TestReponse['bowling']['matches']) ? $TestReponse['bowling']['matches'] : 0),
                                    'Innings' => (!empty($TestReponse['bowling']['innings']) ? $TestReponse['bowling']['innings'] : 0),
                                    'Balls' => (!empty($TestReponse['bowling']['balls']) ? $TestReponse['bowling']['balls'] : 0),
                                    'Overs' => 0,
                                    'Runs' => (!empty($TestReponse['bowling']['runs']) ? $TestReponse['bowling']['runs'] : 0),
                                    'Wickets' => (!empty($TestReponse['bowling']['wickets']) ? $TestReponse['bowling']['wickets'] : 0),
                                    'BestInning' => @$TestReponse['bowling']['best_innings']['wickets'] . '/' . @$TestReponse['bowling']['best_innings']['runs'],
                                    'BestMatch' => 0,
                                    'Economy' => (!empty($TestReponse['bowling']['economy']) ? $TestReponse['bowling']['economy'] : 0),
                                    'Average' => (!empty($TestReponse['bowling']['average']) ? $TestReponse['bowling']['average'] : 0),
                                    'StrikeRate' => (!empty($TestReponse['bowling']['strike_rate']) ? $TestReponse['bowling']['strike_rate'] : 0),
                                    'FourPlusWicketsInSingleInning' => (!empty($TestReponse['bowling']['four_wickets']) ? $TestReponse['bowling']['four_wickets'] : 0),
                                    'FivePlusWicketsInSingleInning' => (!empty($TestReponse['bowling']['five_wickets']) ? $TestReponse['bowling']['five_wickets'] : 0),
                                    'TenPlusWicketsInSingleInning' => (!empty($TestReponse['bowling']['ten_wickets']) ? $TestReponse['bowling']['ten_wickets'] : 0)
                        );

                        /* ODI Bowling Stats */
                        $BowlingStats->ODI = (object) array(
                                    'MatchID' => 0,
                                    'InningID' => 0,
                                    'Matches' => (!empty($OneDayReponse['bowling']['matches']) ? $OneDayReponse['bowling']['matches'] : 0),
                                    'Innings' => (!empty($OneDayReponse['bowling']['innings']) ? $OneDayReponse['bowling']['innings'] : 0),
                                    'Balls' => (!empty($OneDayReponse['bowling']['balls']) ? $OneDayReponse['bowling']['balls'] : 0),
                                    'Overs' => 0,
                                    'Runs' => (!empty($OneDayReponse['bowling']['runs']) ? $OneDayReponse['bowling']['runs'] : 0),
                                    'Wickets' => (!empty($OneDayReponse['bowling']['wickets']) ? $OneDayReponse['bowling']['wickets'] : 0),
                                    'BestInning' => @$OneDayReponse['bowling']['best_innings']['wickets'] . '/' . @$OneDayReponse['bowling']['best_innings']['runs'],
                                    'BestMatch' => 0,
                                    'Economy' => (!empty($OneDayReponse['bowling']['economy']) ? $OneDayReponse['bowling']['economy'] : 0),
                                    'Average' => (!empty($OneDayReponse['bowling']['average']) ? $OneDayReponse['bowling']['average'] : 0),
                                    'StrikeRate' => (!empty($OneDayReponse['bowling']['strike_rate']) ? $OneDayReponse['bowling']['strike_rate'] : 0),
                                    'FourPlusWicketsInSingleInning' => (!empty($OneDayReponse['bowling']['four_wickets']) ? $OneDayReponse['bowling']['four_wickets'] : 0),
                                    'FivePlusWicketsInSingleInning' => (!empty($OneDayReponse['bowling']['five_wickets']) ? $OneDayReponse['bowling']['five_wickets'] : 0),
                                    'TenPlusWicketsInSingleInning' => (!empty($OneDayReponse['bowling']['ten_wickets']) ? $OneDayReponse['bowling']['ten_wickets'] : 0)
                        );

                        /* T20 Bowling Stats */
                        $BowlingStats->T20 = (object) array(
                                    'MatchID' => 0,
                                    'InningID' => 0,
                                    'Matches' => (!empty($T20Reponse['bowling']['matches']) ? $T20Reponse['bowling']['matches'] : 0),
                                    'Innings' => (!empty($T20Reponse['bowling']['innings']) ? $T20Reponse['bowling']['innings'] : 0),
                                    'Balls' => (!empty($T20Reponse['bowling']['balls']) ? $T20Reponse['bowling']['balls'] : 0),
                                    'Overs' => 0,
                                    'Runs' => (!empty($T20Reponse['bowling']['runs']) ? $T20Reponse['bowling']['runs'] : 0),
                                    'Wickets' => (!empty($T20Reponse['bowling']['wickets']) ? $T20Reponse['bowling']['wickets'] : 0),
                                    'BestInning' => @$T20Reponse['bowling']['best_innings']['wickets'] . '/' . @$T20Reponse['bowling']['best_innings']['runs'],
                                    'BestMatch' => 0,
                                    'Economy' => (!empty($T20Reponse['bowling']['economy']) ? $T20Reponse['bowling']['economy'] : 0),
                                    'Average' => (!empty($T20Reponse['bowling']['average']) ? $T20Reponse['bowling']['average'] : 0),
                                    'StrikeRate' => (!empty($T20Reponse['bowling']['strike_rate']) ? $T20Reponse['bowling']['strike_rate'] : 0),
                                    'FourPlusWicketsInSingleInning' => (!empty($T20Reponse['bowling']['four_wickets']) ? $T20Reponse['bowling']['four_wickets'] : 0),
                                    'FivePlusWicketsInSingleInning' => (!empty($T20Reponse['bowling']['five_wickets']) ? $T20Reponse['bowling']['five_wickets'] : 0),
                                    'TenPlusWicketsInSingleInning' => (!empty($T20Reponse['bowling']['ten_wickets']) ? $T20Reponse['bowling']['ten_wickets'] : 0)
                        );

                        /* Test Fielding Stats */
                        $FieldingStats->Test = (object) array(
                                    'catches' => (!empty($TestReponse['fielding']['catches']) ? $TestReponse['fielding']['catches'] : 0),
                                    'stumpings' => (!empty($TestReponse['fielding']['stumpings']) ? $TestReponse['fielding']['stumpings'] : 0)
                        );

                        /* ODI Fielding Stats */
                        $FieldingStats->ODI = (object) array(
                                    'catches' => (!empty($OneDayReponse['fielding']['catches']) ? $OneDayReponse['fielding']['catches'] : 0),
                                    'stumpings' => (!empty($OneDayReponse['fielding']['stumpings']) ? $OneDayReponse['fielding']['stumpings'] : 0)
                        );

                        /* T20 Fielding Stats */
                        $FieldingStats->T20 = (object) array(
                                    'catches' => (!empty($T20Reponse['fielding']['catches']) ? $T20Reponse['fielding']['catches'] : 0),
                                    'stumpings' => (!empty($T20Reponse['fielding']['stumpings']) ? $T20Reponse['fielding']['stumpings'] : 0)
                        );

                        /* Update Player Stats */
                        $PlayerStats = array(
                            'PlayerBattingStats' => json_encode($BattingStats),
                            'PlayerBowlingStats' => json_encode($BowlingStats),
                            'PlayerFieldingStats' => json_encode($FieldingStats),
                            'LastUpdatedOn' => date('Y-m-d H:i:s')
                        );
                        $this->db->where('PlayerID', $Rows['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('sports_players', $PlayerStats);
                    }

                    if (!empty($MatchID)) {
                        $MatchUpdate = array(
                            'LivePlayerStatusUpdate' => "Yes",
                        );
                        $this->db->where('MatchID', $Value['MatchID']);
                        $this->db->limit(1);
                        $this->db->update('sports_matches', $MatchUpdate);
                    } else {
                        $MatchUpdate = array(
                            'PlayerStatsUpdate' => "Yes",
                        );
                        $this->db->where('MatchID', $Value['MatchID']);
                        $this->db->limit(1);
                        $this->db->update('sports_matches', $MatchUpdate);
                    }
                }
            }
        }
        return true;
    }

    /*
      Description: To get match live score (Cricket API)
     */

    function getLivePlaying11MatchPlayerCricketAPI() {
        ini_set('max_execution_time', 300);
        $DateTime = date('Y-m-d H:i', strtotime(date('Y-m-d H:i')) + 2400);
        /* Get Live Matches Data */
        $LiveMatches = $this->getMatches('MatchIDLive,MatchGUID,MatchID,MatchStartDateTime,Status,IsPlayingXINotificationSent,TeamNameShortLocal,TeamNameShortVisitor', array('MatchStartDateTime' => $DateTime, 'StatusID' => array(1, 2),
            "IsPlayingXINotificationSent" => "No"), true, 1, 25);
        foreach ($LiveMatches['Data']['Records'] as $Value) {

            $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/match/' . $Value['MatchIDLive'] . '/?access_token=');
            // $Response = $this->callSportsAPI(MW_SPORTS_API_URL_CRICKETAPI . 'getMatchScoreLiveCricket?MatchKey=' . $Value['MatchIDLive']);
            /** to check playing 11 team players * */
            if (!empty($Response['data']['card']['teams']['a']['match']['playing_xi']) && !empty($Response['data']['card']['teams']['b']['match']['playing_xi'])) {
                /* Get Playing XI */
                $PlayingXIArr = array_merge(empty($Response['data']['card']['teams']['a']['match']['playing_xi']) ? array() : $Response['data']['card']['teams']['a']['match']['playing_xi'], empty($Response['data']['card']['teams']['b']['match']['playing_xi']) ? array() : $Response['data']['card']['teams']['b']['match']['playing_xi']);
                /* Get Match Players */
                $IsPlayingPlayers = 0;
                $PlayersData = $this->Sports_model->getPlayersByType('PlayerIDLive,PlayerID,MatchID,IsPlaying', array('MatchID' => $Value['MatchID']), true, 0);
                if ($PlayersData) {
                    $IsPlayingData = array_count_values(array_values(array_column($PlayersData['Data']['Records'], 'IsPlaying', 'PlayerID')));
                    $IsPlayingPlayers = (isset($IsPlayingData['Yes'])) ? (int) $IsPlayingData['Yes'] : 0;
                }
                $MatchStatusLive = @$Response['data']['card']['status'];
                $MatchStatusOverView = @$Response['data']['card']['status_overview'];

                /* Get Match Players Live */
                if (empty($Response['data']['card']['players'])) continue;
                foreach ($Response['data']['card']['players'] as $PlayerIdLive => $Player) {
                    $LivePlayersData[$PlayerIdLive] = $Player['name'];
                }

                if (!empty($PlayingXIArr) && $IsPlayingPlayers < 25) {
                    /* Get Match Players */
                    $PlayersIdsData = array();
                    if ($PlayersData) {
                        $PlayersIdsData = array_column($PlayersData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
                    }
                    foreach ($PlayingXIArr as $PlayerIdLiveNew => $PlayerValue) {

                        /* Update Playing XI Status */
                        $this->db->where('MatchID', $Value['MatchID']);
                        $this->db->where('PlayerID', $PlayersIdsData[$PlayerValue]);
                        $this->db->limit(1);
                        $this->db->update('sports_team_players', array('IsPlaying' => "Yes"));
                    }
                    if ($Value['IsPlayingXINotificationSent'] == "No") {
                        /* Update Playing XI Notification Status */
                        $this->db->where('MatchID', $Value['MatchID']);
                        $this->db->limit(1);
                        $this->db->update('sports_matches', array('IsPlayingXINotificationSent' => "Yes", "PlayingNotifyTime" => date('Y-m-d H:i:s')));
                        /* Send Playing XI Notification - To all users */
                        pushNotificationAndroidBroadcast('Playing XI - Announced', 'Playing XI for ' . $Value['TeamNameShortLocal'] . ' Vs ' . $Value['TeamNameShortVisitor'] . ' announced.', $Value['MatchGUID']);
                        pushNotificationIphoneBroadcast('Playing XI - Announced', 'Playing XI for ' . $Value['TeamNameShortLocal'] . ' Vs ' . $Value['TeamNameShortVisitor'] . ' announced.', $Value['MatchGUID']);
                    }
                }
            }
        }
    }

    /*
      Description: To get match live score (Cricket API)
     */

    function getMatchScoreLiveCricketApi($CronID) {
        /* ini_set('display_errors', 1);
          ini_set('display_startup_errors', 1);
          error_reporting(E_ALL); */

        ini_set('max_execution_time', 300);
        $DateTime = date('Y-m-d H:i', strtotime('-6 days', strtotime(date('Y-m-d H:i'))));
        /* Get Live Matches Data */
        $LiveMatches = $this->getMatches('MatchIDLive,MatchID,MatchStartDateTime,Status,IsPlayingXINotificationSent,TeamNameShortLocal,TeamNameShortVisitor', array('MatchStartDateTimeBetweenLive' => $DateTime, 'StatusID' => array(
                1, 2, 10)), true, 1, 50);
        //$LiveMatches = $this->getMatches('MatchIDLive,MatchID,MatchStartDateTime,Status,IsPlayingXINotificationSent,TeamNameShortLocal,TeamNameShortVisitor', array('MatchID' => 152632), true, 1, 10);
        if (!$LiveMatches) {
            $this->db->where('CronID', $CronID);
            $this->db->limit(1);
            $this->db->update('log_cron', array('CronStatus' => 'Exit', 'CronResponse' => $this->db->last_query()));
            exit;
        }
        $MatchStatus = array('completed' => 5, "started" => 2, "notstarted" => 9);
        $ContestStatus = array('completed' => 5, "started" => 2, "notstarted" => 9,
            "Abandoned" => 3, "Cancelled" => 3, "No Result" => 5);
        $InningsStatus = array(1 => 'Scheduled', 2 => 'Completed', 3 => 'Live', 4 => 'Abandoned');
        foreach ($LiveMatches['Data']['Records'] as $Value) {

            if ($Value['Status'] == 'Pending' && (strtotime(date('Y-m-d H:i:s')) + 19800 >= strtotime($Value['MatchStartDateTime']))) { // +05:30

                /* Update Match Status */
                $this->db->where('EntityID', $Value['MatchID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 2));

                /* Update Contest Status */
                $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 2 WHERE E.StatusID = 1 AND C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID']);

                $CronID = $this->Utility_model->insertCronLogs('autoCancelContest');
                $this->autoCancelContest($CronID, 'Cancelled', $Value['MatchID']);
                $this->Utility_model->updateCronLogs($CronID);
            }

            $Response = $this->callSportsAPI(SPORTS_API_URL_CRICKETAPI . '/rest/v2/match/' . $Value['MatchIDLive'] . '/?access_token=');
            // $Response = $this->callSportsAPI(MW_SPORTS_API_URL_CRICKETAPI . 'getMatchScoreLiveCricket?MatchKey=' . $Value['MatchIDLive']);

           // print_r(json_encode($Response));
           // exit("kjbc");

            $MatchStatusLive = @$Response['data']['card']['status'];
            $MatchStatusOverView = @$Response['data']['card']['status_overview'];

            /* Get Match Review Check Point */
            $MatchReviewCheckPoint = @$Response['data']['card']['data_review_checkpoint'];

            /* Get Match Players Live */
            if (empty($Response['data']['card']['players'])) continue;

            foreach ($Response['data']['card']['players'] as $PlayerIdLive => $Player) {
                $LivePlayersData[$PlayerIdLive] = $Player['name'];
            }

            /* Get Playing XI */
            $PlayingXIArr = array_merge(empty($Response['data']['card']['teams']['a']['match']['playing_xi']) ? array() : $Response['data']['card']['teams']['a']['match']['playing_xi'], empty($Response['data']['card']['teams']['b']['match']['playing_xi']) ? array() : $Response['data']['card']['teams']['b']['match']['playing_xi']);

            /* Get Match Players */
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->update('sports_team_players', array('IsPlaying' => "No"));

            $IsPlayingPlayers = 0;
            $PlayersData = $this->getPlayersByType('PlayerIDLive,PlayerID,MatchID,IsPlaying,MatchType,PlayerSalary', array('MatchID' => $Value['MatchID']), true, 0);
            if ($PlayersData) {
                $IsPlayingData = array_count_values(array_values(array_column($PlayersData['Data']['Records'], 'IsPlaying', 'PlayerID')));
                $IsPlayingPlayers = (isset($IsPlayingData['Yes'])) ? (int) $IsPlayingData['Yes'] : 0;
            }
            if (!empty($PlayingXIArr) && $IsPlayingPlayers < 25) {

                /* Get Match Players */
                $PlayersIdsData = array();
                if ($PlayersData) {
                    $PlayersIdsData = array_column($PlayersData['Data']['Records'], 'PlayerID', 'PlayerIDLive');
                }

                foreach ($PlayingXIArr as $PlayerIdLiveNew => $PlayerValue) {
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->where('PlayerID', $PlayersIdsData[$PlayerValue]);
                    $this->db->limit(1);
                    $this->db->update('sports_team_players', array('IsPlaying' => "Yes"));
                }
                /* if ($Value['IsPlayingXINotificationSent'] == "No") {
                  $this->db->where('MatchID', $Value['MatchID']);
                  $this->db->limit(1);
                  $this->db->update('sports_matches', array('IsPlayingXINotificationSent' => "Yes"));
                  pushNotificationAndroidBroadcast('Playing XI - Announced', 'Playing XI for ' . $Value['TeamNameShortLocal'] . ' Vs ' . $Value['TeamNameShortVisitor'] . ' announced.');
                  } */
            }

            if (!in_array($MatchStatusLive, array('started', 'completed'))) {
                continue;
            }
            $MatchScoreDetails = $InningsData = $InningsData2 = array();
            $MatchScoreDetails['StatusLive'] = ($MatchStatusLive == 'started') ? 'Live' : (($MatchStatusLive == 'notstarted') ? 'Not Started' : 'Completed');
            $MatchScoreDetails['StatusNote'] = (!empty($Response['data']['card']['msgs']['result'])) ? $Response['data']['card']['msgs']['result'] : '';

            $MatchScoreDetails['TeamScoreLocal'] = array('Name' => $Response['data']['card']['teams']['a']['name'],
                'ShortName' => $Response['data']['card']['teams']['a']['short_name'],
                'LogoURL' => '', 'Scores' => @$Response['data']['card']['innings']['a_1']['runs'] . '/' . @$Response['data']['card']['innings']['a_1']['wickets'],
                'Overs' => @$Response['data']['card']['innings']['a_1']['overs']);

            $MatchScoreDetails['TeamScoreVisitor'] = array('Name' => $Response['data']['card']['teams']['b']['name'],
                'ShortName' => $Response['data']['card']['teams']['b']['short_name'],
                'LogoURL' => '', 'Scores' => @$Response['data']['card']['innings']['b_1']['runs'] . '/' . @$Response['data']['card']['innings']['b_1']['wickets'],
                'Overs' => @$Response['data']['card']['innings']['b_1']['overs']);

            $MatchScoreDetails['TeamScoreLocalSecondInn'] = array('Name' => $Response['data']['card']['teams']['a']['name'],
                'ShortName' => $Response['data']['card']['teams']['a']['short_name'],
                'LogoURL' => '', 'Scores' => @$Response['data']['card']['innings']['a_2']['runs'] . '/' . @$Response['data']['card']['innings']['a_2']['wickets'],
                'Overs' => @$Response['data']['card']['innings']['a_2']['overs']);

            $MatchScoreDetails['TeamScoreVisitorSecondInn'] = array('Name' => $Response['data']['card']['teams']['b']['name'],
                'ShortName' => $Response['data']['card']['teams']['b']['short_name'],
                'LogoURL' => '', 'Scores' => @$Response['data']['card']['innings']['b_2']['runs'] . '/' . @$Response['data']['card']['innings']['b_2']['wickets'],
                'Overs' => @$Response['data']['card']['innings']['b_2']['overs']);

            $MatchScoreDetails['MatchVenue'] = @$Response['data']['card']['venue'];
            $MatchScoreDetails['Result'] = (!empty($Response['data']['cards']['msgs']['result'])) ? $Response['data']['cards']['msgs']['result'] : '';
            $MatchScoreDetails['Toss'] = @$Response['data']['card']['toss']['str'];
            $MatchScoreDetails['ManOfTheMatchPlayer'] = (!empty($LivePlayersData[@$Response['data']['card']['man_of_match']])) ? $LivePlayersData[@$Response['data']['card']['man_of_match']] : '';

            foreach ($Response['data']['card']['teams'] as $TeamKey => $TeamValue) {
                $BatsmanData = $BowlersData = $FielderData = $AllPlayingXI = $AllPlayingSecondInning = array();
                $BatsmanData2 = $BowlersData2 = $FielderData2 = array();

                /* Manage Team Players Details */
                foreach ($Response['data']['card']['teams'][$TeamKey]['match']['playing_xi'] as $InningPlayer) {

                    /* Get Player Details */
                    $PlayerDetails = @$Response['data']['card']['players'][$InningPlayer];
                    /* Get Player Role */
                    $Keeper = $Response['data']['card']['players'][$InningPlayer]['identified_roles']['keeper'];
                    $Batsman = $Response['data']['card']['players'][$InningPlayer]['identified_roles']['batsman'];
                    $Bowler = $Response['data']['card']['players'][$InningPlayer]['identified_roles']['bowler'];
                    $PlayerRole = ($Keeper == 1) ? 'WicketKeeper' : (($Batsman == 1 && $Bowler == 1) ? 'AllRounder' : ((empty($Batsman) && $Bowler == 1) ? 'Bowler' : ((empty($Bowler) && $Batsman == 1) ? 'Batsman' : '')));

                    /** inning 1st * */
                    /* Batting */
                    if (isset($PlayerDetails['match']['innings'][1]['batting']['balls'])) {

                        $AllPlayingXI[$InningPlayer]['batting'] = $BatsmanData[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => @$InningPlayer,
                            'Role' => @$PlayerRole,
                            'Runs' => (!empty($PlayerDetails['match']['innings'][1]['batting']['runs'])) ? $PlayerDetails['match']['innings'][1]['batting']['runs'] : "",
                            'BallsFaced' => (!empty($PlayerDetails['match']['innings'][1]['batting']['balls'])) ? $PlayerDetails['match']['innings'][1]['batting']['balls'] : "",
                            'Fours' => (!empty($PlayerDetails['match']['innings'][1]['batting']['fours'])) ? $PlayerDetails['match']['innings'][1]['batting']['fours'] : "",
                            'Sixes' => (!empty($PlayerDetails['match']['innings'][1]['batting']['sixes'])) ? $PlayerDetails['match']['innings'][1]['batting']['sixes'] : "",
                            'HowOut' => (!empty($PlayerDetails['match']['innings'][1]['batting']['out_str'])) ? $PlayerDetails['match']['innings'][1]['batting']['out_str'] : "",
                            'IsPlaying' => (@$PlayerDetails['match']['innings'][1]['batting']['dismissed'] == 1) ? 'No' : ((isset($PlayerDetails['match']['innings'][1]['batting']['balls'])) ? 'Yes' : ''),
                            'StrikeRate' => (!empty($PlayerDetails['match']['innings'][1]['batting']['strike_rate'])) ? $PlayerDetails['match']['innings'][1]['batting']['strike_rate'] : ""
                        );
                    }

                    /* Bowling */
                    if (!empty(@$PlayerDetails['match']['innings'][1]['bowling'])) {

                        $AllPlayingXI[$InningPlayer]['bowling'] = $BowlersData[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => $InningPlayer,
                            'Overs' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['overs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['overs'] : '',
                            'Maidens' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['maiden_overs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['maiden_overs'] : '',
                            'RunsConceded' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['runs'])) ? $PlayerDetails['match']['innings'][1]['bowling']['runs'] : '',
                            'Wickets' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['wickets'])) ? $PlayerDetails['match']['innings'][1]['bowling']['wickets'] : '',
                            'NoBalls' => '',
                            'Wides' => '',
                            'Economy' => (!empty($PlayerDetails['match']['innings'][1]['bowling']['economy'])) ? $PlayerDetails['match']['innings'][1]['bowling']['economy'] : ''
                        );
                    }

                    /* Fielding */
                    if (!empty(@$PlayerDetails['match']['innings'][1]['fielding'])) {

                        $AllPlayingXI[$InningPlayer]['fielding'] = $FielderData[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => $InningPlayer,
                            'Catches' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['catches'])) ? $PlayerDetails['match']['innings'][1]['fielding']['catches'] : '',
                            'RunOutThrower' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'RunOutCatcher' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'RunOutDirectHit' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][1]['fielding']['runouts'] : '',
                            'Stumping' => (!empty($PlayerDetails['match']['innings'][1]['fielding']['stumbeds'])) ? $PlayerDetails['match']['innings'][1]['fielding']['stumbeds'] : ''
                        );
                    }

                    /** check run out case player * */
                    /* if (isset($PlayerDetails['match']['innings'][1]['batting']['dismissed'])
                      && $PlayerDetails['match']['innings'][1]['batting']['dismissed'] == true) {
                      if (isset($PlayerDetails['match']['innings'][1]['batting']['ball_of_dismissed']['wicket_type'])
                      && $PlayerDetails['match']['innings'][1]['batting']['ball_of_dismissed']['wicket_type'] == "runout") {

                      if ($PlayerDetails['match']['innings'][1]['batting']['ball_of_dismissed']['other_fielder'] != null) {
                      $FirstFilderRunOut = trim($PlayerDetails['match']['innings'][1]['batting']['ball_of_dismissed']['fielder']['key']);
                      $SecondFilderRunOut = trim($PlayerDetails['match']['innings'][1]['batting']['ball_of_dismissed']['other_fielder']);
                      } else {
                      $FirstFilderRunOut = trim($PlayerDetails['match']['innings'][1]['batting']['ball_of_dismissed']['fielder']['key']);
                      }

                      echo $FirstFilderRunOut . "=" . $SecondFilderRunOut;
                      exit;
                      }
                      } */

                    /** inning 2nd for test match * */
                    /* Batting */
                    if (isset($PlayerDetails['match']['innings'][2]['batting']['balls'])) {

                        $AllPlayingSecondInning[$InningPlayer]['batting'] = $BatsmanData2[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => @$InningPlayer,
                            'Role' => @$PlayerRole,
                            'Runs' => (!empty($PlayerDetails['match']['innings'][2]['batting']['runs'])) ? $PlayerDetails['match']['innings'][2]['batting']['runs'] : "",
                            'BallsFaced' => (!empty($PlayerDetails['match']['innings'][2]['batting']['balls'])) ? $PlayerDetails['match']['innings'][2]['batting']['balls'] : "",
                            'Fours' => (!empty($PlayerDetails['match']['innings'][2]['batting']['fours'])) ? $PlayerDetails['match']['innings'][2]['batting']['fours'] : "",
                            'Sixes' => (!empty($PlayerDetails['match']['innings'][2]['batting']['sixes'])) ? $PlayerDetails['match']['innings'][2]['batting']['sixes'] : "",
                            'HowOut' => (!empty($PlayerDetails['match']['innings'][2]['batting']['out_str'])) ? $PlayerDetails['match']['innings'][2]['batting']['out_str'] : "",
                            'IsPlaying' => (@$PlayerDetails['match']['innings'][2]['batting']['dismissed'] == 1) ? 'No' : ((isset($PlayerDetails['match']['innings'][2]['batting']['balls'])) ? 'Yes' : ''),
                            'StrikeRate' => (!empty($PlayerDetails['match']['innings'][2]['batting']['strike_rate'])) ? $PlayerDetails['match']['innings'][2]['batting']['strike_rate'] : ""
                        );
                    }

                    /* Bowling */
                    if (!empty(@$PlayerDetails['match']['innings'][2]['bowling'])) {

                        $AllPlayingSecondInning[$InningPlayer]['bowling'] = $BowlersData2[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => $InningPlayer,
                            'Overs' => (!empty($PlayerDetails['match']['innings'][2]['bowling']['overs'])) ? $PlayerDetails['match']['innings'][2]['bowling']['overs'] : '',
                            'Maidens' => (!empty($PlayerDetails['match']['innings'][2]['bowling']['maiden_overs'])) ? $PlayerDetails['match']['innings'][2]['bowling']['maiden_overs'] : '',
                            'RunsConceded' => (!empty($PlayerDetails['match']['innings'][2]['bowling']['runs'])) ? $PlayerDetails['match']['innings'][2]['bowling']['runs'] : '',
                            'Wickets' => (!empty($PlayerDetails['match']['innings'][2]['bowling']['wickets'])) ? $PlayerDetails['match']['innings'][2]['bowling']['wickets'] : '',
                            'NoBalls' => '',
                            'Wides' => '',
                            'Economy' => (!empty($PlayerDetails['match']['innings'][2]['bowling']['economy'])) ? $PlayerDetails['match']['innings'][2]['bowling']['economy'] : ''
                        );
                    }

                    /* Fielding */
                    if (!empty(@$PlayerDetails['match']['innings'][2]['fielding'])) {

                        $AllPlayingSecondInning[$InningPlayer]['fielding'] = $FielderData2[] = array(
                            'Name' => @$PlayerDetails['name'],
                            'PlayerIDLive' => $InningPlayer,
                            'Catches' => (!empty($PlayerDetails['match']['innings'][2]['fielding']['catches'])) ? $PlayerDetails['match']['innings'][2]['fielding']['catches'] : '',
                            'RunOutThrower' => (!empty($PlayerDetails['match']['innings'][2]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][2]['fielding']['runouts'] : '',
                            'RunOutCatcher' => (!empty($PlayerDetails['match']['innings'][2]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][2]['fielding']['runouts'] : '',
                            'RunOutDirectHit' => (!empty($PlayerDetails['match']['innings'][2]['fielding']['runouts'])) ? $PlayerDetails['match']['innings'][2]['fielding']['runouts'] : '',
                            'Stumping' => (!empty($PlayerDetails['match']['innings'][2]['fielding']['stumbeds'])) ? $PlayerDetails['match']['innings'][2]['fielding']['stumbeds'] : ''
                        );
                    }
                }

                /* Get Team Details */
                $TeamName = $Response['data']['card']['teams'][$TeamKey]['name'];
                $TeamShortName = $Response['data']['card']['teams'][$TeamKey]['short_name'];
                $InningsData[] = array(
                    'Name' => $TeamName . ' inning',
                    'ShortName' => $TeamShortName . ' inn.',
                    'Scores' => $Response['data']['card']['innings'][$TeamKey . '_1']['runs'] . "/" . $Response['data']['card']['innings'][$TeamKey . '_1']['wickets'],
                    'Status' => '',
                    'ScoresFull' => $Response['data']['card']['innings'][$TeamKey . '_1']['runs'] . "/" . $Response['data']['card']['innings'][$TeamKey . '_1']['wickets'] . " (" . $Response['data']['card']['innings'][$TeamKey . '_1']['overs'] . " ov)",
                    'BatsmanData' => $BatsmanData,
                    'BowlersData' => $BowlersData,
                    'FielderData' => $FielderData,
                    'AllPlayingData' => $AllPlayingXI,
                    'ExtraRuns' => array('Byes' => @$Response['data']['card']['innings'][$TeamKey . '_1']['extras'],
                        'LegByes' => @$Response['data']['card']['innings'][$TeamKey . '_1']['extras'],
                        'Wides' => @$Response['data']['card']['innings'][$TeamKey . '_1']['wide'],
                        'NoBalls' => @$Response['data']['card']['innings'][$TeamKey . '_1']['noball'])
                );
                if (!empty($AllPlayingSecondInning)) {
                    $InningsData2[] = array(
                        'Name' => $TeamName . ' inning',
                        'ShortName' => $TeamShortName . ' inn.',
                        'Scores' => $Response['data']['card']['innings'][$TeamKey . '_2']['runs'] . "/" . $Response['data']['card']['innings'][$TeamKey . '_2']['wickets'],
                        'Status' => '',
                        'ScoresFull' => $Response['data']['card']['innings'][$TeamKey . '_2']['runs'] . "/" . $Response['data']['card']['innings'][$TeamKey . '_2']['wickets'] . " (" . $Response['data']['card']['innings'][$TeamKey . '_2']['overs'] . " ov)",
                        'BatsmanData' => $BatsmanData2,
                        'BowlersData' => $BowlersData2,
                        'FielderData' => $FielderData2,
                        'AllPlayingData' => $AllPlayingSecondInning,
                        'ExtraRuns' => array('Byes' => @$Response['data']['card']['innings'][$TeamKey . '_2']['extras'],
                            'LegByes' => @$Response['data']['card']['innings'][$TeamKey . '_2']['extras'],
                            'Wides' => @$Response['data']['card']['innings'][$TeamKey . '_2']['wide'],
                            'NoBalls' => @$Response['data']['card']['innings'][$TeamKey . '_2']['noball'])
                    );
                }
            }
            $MatchScoreDetails['Innings'] = $InningsData;
            $MatchScoreDetails['Innings2'] = $InningsData2;
            /* Update Match Data */
            $this->db->where('MatchID', $Value['MatchID']);
            $this->db->limit(1);
            $this->db->update('sports_matches', array('MatchScoreDetails' => json_encode($MatchScoreDetails)));

            if ($MatchStatusLive == 'completed') {
                if (strtolower($MatchStatusOverView) == "abandoned" || strtolower($MatchStatusOverView) == "canceled" || strtolower($MatchStatusOverView) == "play_suspended_unknown") {
                    $CronID = $this->Utility_model->insertCronLogs('autoCancelContest');
                    $this->autoCancelContest($CronID, 'Abonded', $Value['MatchID']);
                    $this->Utility_model->updateCronLogs($CronID);

                    /* Update Match Status */
                    $this->db->where('EntityID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 8));
                    /* Update Contest Status */
                } else if (strtolower($MatchStatusOverView) == "result") {
                    /* Update Final points before complete match */
                    $CronID = $this->Utility_model->insertCronLogs('getPlayerPoints');
                    $this->getPlayerPointsCricketAPI($CronID, $Value['MatchID']);
                    $this->Utility_model->updateCronLogs($CronID);

                    $CompleteStatus = 10;
                    if ($MatchReviewCheckPoint == "post-match-validated") {
                        $CompleteStatus = 5;
                    }
                    /* Update Match Status */
                    $this->db->where('EntityID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => $CompleteStatus));

                    /* Update Match Status */
                    $this->db->where('MatchID', $Value['MatchID']);
                    $this->db->limit(1);
                    $this->db->update('sports_matches', array("MatchCompleteDateTime" => date('Y-m-d H:i:s', strtotime("+2 hours"))));

                    /* Update Contest Status */
                    if ($CompleteStatus == 5) {
                        $this->db->query('UPDATE sports_contest AS C, tbl_entity AS E SET E.StatusID = 5 WHERE  E.StatusID = 2 AND  C.ContestID = E.EntityID AND C.MatchID = ' . $Value['MatchID']);
                    }

                    /* Update Final player points before complete match */
                    $CronID = $this->Utility_model->insertCronLogs('getJoinedContestPlayerPoints');
                    $this->getJoinedContestTeamPoints($CronID, $Value['MatchID'], array(2, 5, 10), 5);
                    $this->Utility_model->updateCronLogs($CronID);
                }
            }
        }
        $this->autoCancelContest();
    }

    /*
      Description: To calculate points according to keys
     */

    function calculatePoints($Points = array(), $MatchType, $BattingMinimumRuns, $ScoreValue, $BallsFaced = 0, $Overs = 0, $Runs = 0, $MinimumOverEconomyRate = 0, $PlayerRole, $HowOut) {
        /* Match Types */
        $MatchTypes = array('ODI' => 'PointsODI', 'List A' => 'PointsODI', 'T20' => 'PointsT20',
            'T20I' => 'PointsT20', 'Test' => 'PointsTEST', 'Woman ODI' => 'PointsODI',
            'Woman T20' => 'PointsT20');
        $MatchTypeField = $MatchTypes[$MatchType];
        $PlayerPoints = array('PointsTypeGUID' => $Points['PointsTypeGUID'], 'PointsTypeShortDescription' => $Points['PointsTypeShortDescription'],
            'DefinedPoints' => strval($Points[$MatchTypeField]), 'ScoreValue' => (!empty($ScoreValue)) ? strval($ScoreValue) : "0");
        switch ($Points['PointsTypeGUID']) {
            case 'ThreeWickets':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue == 3) ? strval($Points[$MatchTypeField]) : "0";
                $this->defaultBowlingPoints = $PlayerPoints;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'FourWickets':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue == 4) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'FiveWickets':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue == 5) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'SixWickets':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue == 6) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'SevenWicketsMore':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 7) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'EightWicketsMore':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 8) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBowlingState == 0) {
                    $this->IsBowlingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'RunOUT':
            case 'RunOutCatcher':
            case 'RunOutThrower':
            case 'RunOutCatcherThrower':
            case 'Stumping':
            case 'Four':
            case 'Six':
            case 'EveryRunScored':
            case 'Catch':
            case 'Wicket':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points[$MatchTypeField] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'Maiden':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue > 0) ? $Points[$MatchTypeField] * $ScoreValue : 0;
                return $PlayerPoints;
                break;
            case 'Duck':
                if (!empty($HowOut) && $HowOut != "") {
                    if ($ScoreValue <= 0 && $PlayerRole != 'Bowler' && $HowOut != "Not out") {
                        $PlayerPoints['CalculatedPoints'] = ($BallsFaced >= 1) ? $Points[$MatchTypeField] : 0;
                    } else {
                        $PlayerPoints['CalculatedPoints'] = 0;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }

                return $PlayerPoints;
                break;
            case 'StrikeRate0N49.99':
                $PlayerPoints['CalculatedPoints'] = "0";
                $this->defaultStrikeRatePoints = $PlayerPoints;
                if ($BallsFaced >= $BattingMinimumRuns && $PlayerRole != 'Bowler') {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 0.1 && $ScoreValue <= 49.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate50N74.99':
                if ($BallsFaced >= $BattingMinimumRuns && $PlayerRole != 'Bowler') {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 50 && $ScoreValue <= 74.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate75N99.99':
                if ($BallsFaced >= $BattingMinimumRuns && $PlayerRole != 'Bowler') {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 75 && $ScoreValue <= 99.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate100N149.99':
                if ($BallsFaced >= $BattingMinimumRuns && $PlayerRole != 'Bowler') {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 100 && $ScoreValue <= 149.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate150N199.99':
                if ($BallsFaced >= $BattingMinimumRuns && $PlayerRole != 'Bowler') {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 150 && $ScoreValue <= 199.99) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'StrikeRate200NMore':
                if ($BallsFaced >= $BattingMinimumRuns && $PlayerRole != 'Bowler') {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 200) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsStrikeRate == 0) {
                        $this->IsStrikeRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate0N5Balls':
                $PlayerPoints['CalculatedPoints'] = "0";
                $this->defaultEconomyRatePoints = $PlayerPoints;
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 0.1 && $ScoreValue <= 5) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate5.01N7.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 5.01 && $ScoreValue <= 7) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate5.01N8.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 5.01 && $ScoreValue <= 8) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate7.01N10.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 7.01 && $ScoreValue <= 10) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate8.01N10.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 8.01 && $ScoreValue <= 10) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRate10.01N12.00Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 10.01 && $ScoreValue <= 12) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'EconomyRateAbove12.1Balls':
                if ($Overs >= $MinimumOverEconomyRate) {
                    $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 12.1) ? $Points[$MatchTypeField] : 0;
                    if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsEconomyRate == 0) {
                        $this->IsEconomyRate = 1;
                        return $PlayerPoints;
                    }
                } else {
                    $PlayerPoints['CalculatedPoints'] = 0;
                }
                break;
            case 'For30runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 30 && $ScoreValue < 50) ? strval($Points[$MatchTypeField]) : "0";
                $this->defaultBattingPoints = $PlayerPoints;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For50runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 50 && $ScoreValue < 100) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For100runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 100 && $ScoreValue < 150) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For150runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 150 && $ScoreValue < 200) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For200runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 200 && $ScoreValue < 300) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            case 'For300runs':
                $PlayerPoints['CalculatedPoints'] = ($ScoreValue >= 300) ? $Points[$MatchTypeField] : 0;
                if ($PlayerPoints['CalculatedPoints'] != 0 && $this->IsBattingState == 0) {
                    $this->IsBattingState = 1;
                    return $PlayerPoints;
                }
                break;
            default:
                return false;
                break;
        }
    }

    /*
      Description: Find sub arrays from multidimensional array
     */

    function findSubArray($DataArray, $keyName, $Value) {
        $Data = array();
        foreach ($DataArray as $Row) {
            if ($Row[$keyName] == $Value) $Data[] = $Row;
        }
        return $Data;
    }

    /*
      Description: Use to get player credits (salary)
     */

    function getPlayerCredits($BattingStats = array(), $BowlingStats = array(), $PlayerRole) {

        /* Player Roles */
        $PlayerRolesArr = array('bowl' => 'Bowler', 'bat' => 'Batsman', 'wkbat' => 'WicketKeeper',
            'wk' => 'WicketKeeper', 'all' => 'AllRounder');
        $PlayerCredits = array('T20Credits' => 0, 'T20iCredits' => 0, 'ODICredits' => 0,
            'TestCredits' => 0);
        $PlayerRole = (SPORTS_API_NAME == 'ENTITY') ? @$PlayerRolesArr[$PlayerRole] : $PlayerRole;
        if (empty($PlayerRole)) return $PlayerCredits;

        /* Get Player Credits */
        if ($PlayerRole == 'Batsman') {
            if (isset($BattingStats->T20)) {
                $PlayerCredits['T20Credits'] = $this->getT20BattingCredits($BattingStats->T20);
            }
            if (isset($BattingStats->T20i)) {
                $PlayerCredits['T20iCredits'] = $this->getT20iBattingCredits($BattingStats->T20i);
            }
            if (isset($BattingStats->ODI)) {
                $PlayerCredits['ODICredits'] = $this->getODIBattingCredits($BattingStats->ODI);
            }
            if (isset($BattingStats->Test)) {
                $PlayerCredits['TestCredits'] = $this->getTestBattingCredits($BattingStats->Test);
            }
        } else if ($PlayerRole == 'Bowler') {
            if (isset($BowlingStats->T20)) {
                $PlayerCredits['T20Credits'] = $this->getT20BowlingCredits($BowlingStats->T20);
            }
            if (isset($BowlingStats->T20i)) {
                $PlayerCredits['T20iCredits'] = $this->getT20iBowlingCredits($BowlingStats->T20i);
            }
            if (isset($BowlingStats->ODI)) {
                $PlayerCredits['ODICredits'] = $this->getODIBowlingCredits($BowlingStats->ODI);
            }
            if (isset($BowlingStats->Test)) {
                $PlayerCredits['TestCredits'] = $this->getTestBowlingCredits($BowlingStats->Test);
            }
        } else if ($PlayerRole == 'WicketKeeper') {
            if (isset($BattingStats->T20)) {
                $T20Credits = $this->getT20BattingCredits($BattingStats->T20);
                if ($T20Credits < 10.5) {
                    $T20Credits = number_format((float) ($T20Credits + 0.5), 2, '.', '');
                }
                $PlayerCredits['T20Credits'] = $T20Credits;
            }
            if (isset($BattingStats->T20i)) {
                $T20iCredits = $this->getT20iBattingCredits($BattingStats->T20i);
                if ($T20iCredits < 10.5) {
                    $T20iCredits = number_format((float) ($T20iCredits + 0.5), 2, '.', '');
                }
                $PlayerCredits['T20iCredits'] = $T20iCredits;
            }
            if (isset($BattingStats->ODI)) {
                $ODICredits = $this->getODIBattingCredits($BattingStats->ODI);
                if ($ODICredits < 10.5) {
                    $ODICredits = number_format((float) ($ODICredits + 0.5), 2, '.', '');
                }
                $PlayerCredits['ODICredits'] = $ODICredits;
            }
            if (isset($BattingStats->Test)) {
                $TestCredits = $this->getTestBattingCredits($BattingStats->Test);
                if ($TestCredits < 10.5) {
                    $TestCredits = number_format((float) ($TestCredits + 0.5), 2, '.', '');
                }
                $PlayerCredits['TestCredits'] = $TestCredits;
            }
        } else if ($PlayerRole == 'AllRounder') {
            if (isset($BattingStats->T20) && isset($BowlingStats->T20)) {
                $T20BattingCredits = $this->getT20BattingCredits($BattingStats->T20);
                $T20BowlingCredits = $this->getT20BowlingCredits($BowlingStats->T20);
                $T20CreditPoints = number_format((float) ($T20BattingCredits + $T20BowlingCredits) / 2, 2, '.', '');
                if ($T20CreditPoints >= 6 && $T20CreditPoints <= 6.49) {
                    $T20CreditPoints = 6.5;
                } else if ($T20CreditPoints >= 6.5 && $T20CreditPoints <= 6.99) {
                    $T20CreditPoints = 7.5;
                } else if ($T20CreditPoints >= 7 && $T20CreditPoints <= 7.49) {
                    $T20CreditPoints = 8.5;
                } else if ($T20CreditPoints >= 7.5 && $T20CreditPoints <= 7.99) {
                    $T20CreditPoints = 9.5;
                } else if ($T20CreditPoints >= 8 && $T20CreditPoints <= 8.49) {
                    $T20CreditPoints = 10;
                } else if ($T20CreditPoints >= 8.50) {
                    $T20CreditPoints = 10.5;
                }
                $PlayerCredits['T20Credits'] = $T20CreditPoints;
            }
            if (isset($BattingStats->T20i) && isset($BowlingStats->T20i)) {
                $T20iBattingCredits = $this->getT20iBattingCredits($BattingStats->T20i);
                $T20iBowlingCredits = $this->getT20iBowlingCredits($BowlingStats->T20i);
                $T20iCreditPoints = number_format((float) ($T20iBattingCredits + $T20iBowlingCredits) / 2, 2, '.', '');
                if ($T20iCreditPoints >= 6 && $T20iCreditPoints <= 6.49) {
                    $T20iCreditPoints = 6.5;
                } else if ($T20iCreditPoints >= 6.5 && $T20iCreditPoints <= 6.99) {
                    $T20iCreditPoints = 7.5;
                } else if ($T20iCreditPoints >= 7 && $T20iCreditPoints <= 7.49) {
                    $T20iCreditPoints = 8.5;
                } else if ($T20iCreditPoints >= 7.5 && $T20iCreditPoints <= 7.99) {
                    $T20iCreditPoints = 9.5;
                } else if ($T20iCreditPoints >= 8 && $T20iCreditPoints <= 8.49) {
                    $T20iCreditPoints = 10;
                } else if ($T20iCreditPoints >= 8.50) {
                    $T20iCreditPoints = 10.5;
                }
                $PlayerCredits['T20iCredits'] = $T20iCreditPoints;
            }
            if (isset($BattingStats->ODI) && isset($BowlingStats->ODI)) {
                $ODIBattingCredits = $this->getODIBattingCredits($BattingStats->ODI);
                $ODIBowlingCredits = $this->getODIBowlingCredits($BowlingStats->ODI);
                $ODICreditPoints = number_format((float) ($ODIBattingCredits + $ODIBowlingCredits) / 2, 2, '.', '');
                if ($ODICreditPoints >= 6 && $ODICreditPoints <= 6.49) {
                    $ODICreditPoints = 7;
                } else if ($ODICreditPoints >= 6.5 && $ODICreditPoints <= 6.99) {
                    $ODICreditPoints = 7.5;
                } else if ($ODICreditPoints >= 7 && $ODICreditPoints <= 7.24) {
                    $ODICreditPoints = 8;
                } else if ($ODICreditPoints >= 7.25 && $ODICreditPoints <= 7.49) {
                    $ODICreditPoints = 8.5;
                } else if ($ODICreditPoints >= 7.5 && $ODICreditPoints <= 7.74) {
                    $ODICreditPoints = 9;
                } else if ($ODICreditPoints >= 7.75 && $ODICreditPoints <= 8.25) {
                    $ODICreditPoints = 9.5;
                } else if ($ODICreditPoints >= 8.26 && $ODICreditPoints <= 8.75) {
                    $ODICreditPoints = 10;
                } else if ($ODICreditPoints > 8.75) {
                    $ODICreditPoints = 10.5;
                }
                $PlayerCredits['ODICredits'] = $ODICreditPoints;
            }
            if (isset($BattingStats->Test) && isset($BowlingStats->Test)) {
                $TestBattingCredits = $this->getTestBattingCredits($BattingStats->Test);
                $TestBowlingCredits = $this->getTestBowlingCredits($BowlingStats->Test);
                $TestCreditPoints = number_format((float) ($TestBattingCredits + $TestBowlingCredits) / 2, 2, '.', '');
                if ($TestCreditPoints >= 6 && $TestCreditPoints <= 6.49) {
                    $TestCreditPoints = 7;
                } else if ($TestCreditPoints >= 6.5 && $TestCreditPoints <= 6.99) {
                    $TestCreditPoints = 7.5;
                } else if ($TestCreditPoints >= 7 && $TestCreditPoints <= 7.24) {
                    $TestCreditPoints = 8;
                } else if ($TestCreditPoints >= 7.25 && $TestCreditPoints <= 7.49) {
                    $TestCreditPoints = 8.5;
                } else if ($TestCreditPoints >= 7.5 && $TestCreditPoints <= 7.74) {
                    $TestCreditPoints = 9;
                } else if ($TestCreditPoints >= 7.75 && $TestCreditPoints <= 8.25) {
                    $TestCreditPoints = 9.5;
                } else if ($TestCreditPoints >= 8.26 && $TestCreditPoints <= 8.75) {
                    $TestCreditPoints = 10;
                } else if ($TestCreditPoints > 8.75) {
                    $TestCreditPoints = 10.5;
                }
                $PlayerCredits['TestCredits'] = $TestCreditPoints;
            }
        }
        return $PlayerCredits;
    }

    /*
      Description: Use to get T20 batting credits
     */

    function getT20BattingCredits($T20Batting = array()) {
        $T20CreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($T20Batting)) {
            $T20BattingAverage = $T20Batting->Average;
            $StrikeRate = $T20Batting->StrikeRate;
            $InningPlayed = $T20Batting->Innings;
            if (!empty($T20BattingAverage)) {
                $T20CreditPoints = number_format((float) ($StrikeRate / 100) * $T20BattingAverage, 2, '.', '');
                if ($T20CreditPoints >= 20 && $T20CreditPoints <= 25) {
                    $T20CreditPoints = 7;
                } else if ($T20CreditPoints >= 25.01 && $T20CreditPoints <= 30) {
                    $T20CreditPoints = 7.5;
                } else if ($T20CreditPoints >= 30.01 && $T20CreditPoints <= 35) {
                    $T20CreditPoints = 8;
                } else if ($T20CreditPoints >= 35.01 && $T20CreditPoints <= 40) {
                    $T20CreditPoints = 8.5;
                } else if ($T20CreditPoints >= 40.01 && $T20CreditPoints <= 45) {
                    $T20CreditPoints = 9;
                } else if ($T20CreditPoints >= 45.01 && $T20CreditPoints <= 50) {
                    $T20CreditPoints = 9.5;
                } else if ($T20CreditPoints >= 50.01 && $T20CreditPoints <= 55) {
                    $T20CreditPoints = 10;
                } else if ($T20CreditPoints > 55) {
                    $T20CreditPoints = 10.5;
                }
                if ($InningPlayed < 20) {
                    if ($T20CreditPoints > 7 && $T20CreditPoints <= 7.99) {
                        $T20CreditPoints = 7;
                    } else if ($T20CreditPoints >= 8) {
                        $T20CreditPoints = $T20CreditPoints - 1;
                    }
                }
            }
        }
        return $T20CreditPoints;
    }

    /*
      Description: Use to get T20i batting credits
     */

    function getT20iBattingCredits($T20iBatting = array()) {
        $T20iCreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($T20iBatting)) {
            $T20iBattingAverage = $T20iBatting->Average;
            $StrikeRate = $T20iBatting->StrikeRate;
            $InningPlayed = $T20iBatting->Innings;
            if (!empty($T20iBattingAverage)) {
                $T20iCreditPoints = number_format((float) ($StrikeRate / 100) * $T20iBattingAverage, 2, '.', '');
                if ($T20iCreditPoints >= 20 && $T20iCreditPoints <= 25) {
                    $T20iCreditPoints = 7;
                } else if ($T20iCreditPoints >= 25.01 && $T20iCreditPoints <= 30) {
                    $T20iCreditPoints = 7.5;
                } else if ($T20iCreditPoints >= 30.01 && $T20iCreditPoints <= 35) {
                    $T20iCreditPoints = 8;
                } else if ($T20iCreditPoints >= 35.01 && $T20iCreditPoints <= 40) {
                    $T20iCreditPoints = 8.5;
                } else if ($T20iCreditPoints >= 40.01 && $T20iCreditPoints <= 45) {
                    $T20iCreditPoints = 9;
                } else if ($T20iCreditPoints >= 45.01 && $T20iCreditPoints <= 50) {
                    $T20iCreditPoints = 9.5;
                } else if ($T20iCreditPoints >= 50.01 && $T20iCreditPoints <= 55) {
                    $T20iCreditPoints = 10;
                } else if ($T20iCreditPoints > 55) {
                    $T20iCreditPoints = 10.5;
                }
                if ($InningPlayed < 20) {
                    if ($T20iCreditPoints > 7 && $T20iCreditPoints <= 7.99) {
                        $T20iCreditPoints = 7;
                    } else if ($T20iCreditPoints >= 8) {
                        $T20iCreditPoints = $T20iCreditPoints - 1;
                    }
                }
            }
        }
        return $T20iCreditPoints;
    }

    /*
      Description: Use to get ODI batting credits
     */

    function getODIBattingCredits($ODIBatting = array()) {
        $ODICreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($ODIBatting)) {
            $ODIBattingAverage = $ODIBatting->Average;
            $OdiStrikeRate = $ODIBatting->StrikeRate;
            $InningPlayed = $ODIBatting->Innings;
            if (!empty($ODIBattingAverage)) {
                $ODICreditPoints = number_format((float) (sqrt($OdiStrikeRate / 100)) * $ODIBattingAverage, 2, '.', '');
                if ($ODICreditPoints >= 20 && $ODICreditPoints <= 30) {
                    $ODICreditPoints = 6.5;
                } else if ($ODICreditPoints >= 30.01 && $ODICreditPoints <= 35) {
                    $ODICreditPoints = 7;
                } else if ($ODICreditPoints >= 35.01 && $ODICreditPoints <= 40) {
                    $ODICreditPoints = 7.5;
                } else if ($ODICreditPoints >= 40.01 && $ODICreditPoints <= 45) {
                    $ODICreditPoints = 8;
                } else if ($ODICreditPoints >= 45.01 && $ODICreditPoints <= 50) {
                    $ODICreditPoints = 8.5;
                } else if ($ODICreditPoints >= 50.01 && $ODICreditPoints <= 55) {
                    $ODICreditPoints = 9;
                } else if ($ODICreditPoints >= 55.01 && $ODICreditPoints <= 60) {
                    $ODICreditPoints = 9.5;
                } else if ($ODICreditPoints > 60) {
                    $ODICreditPoints = 10;
                }
                if ($InningPlayed < 20) {
                    if ($ODICreditPoints > 7 && $ODICreditPoints <= 7.99) {
                        $ODICreditPoints = 7;
                    } else if ($ODICreditPoints >= 8) {
                        $ODICreditPoints = $ODICreditPoints - 1;
                    }
                }
            }
        }
        return $ODICreditPoints;
    }

    /*
      Description: Use to get Test batting credits
     */

    function getTestBattingCredits($TestBatting = array()) {
        $TestCreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($TestBatting)) {
            $InningPlayed = $TestBatting->Innings;
            $TestBattingAverage = $TestBatting->Average;
            if (!empty($TestBattingAverage)) {
                $TestCreditPoints = number_format((float) $TestBattingAverage, 2, '.', '');
                if ($TestCreditPoints >= 20 && $TestCreditPoints <= 30) {
                    $TestCreditPoints = 6.5;
                } else if ($TestCreditPoints >= 30.01 && $TestCreditPoints <= 35) {
                    $TestCreditPoints = 7;
                } else if ($TestCreditPoints >= 35.01 && $TestCreditPoints <= 40) {
                    $TestCreditPoints = 7.5;
                } else if ($TestCreditPoints >= 40.01 && $TestCreditPoints <= 45) {
                    $TestCreditPoints = 8;
                } else if ($TestCreditPoints >= 45.01 && $TestCreditPoints <= 50) {
                    $TestCreditPoints = 8.5;
                } else if ($TestCreditPoints >= 50.01 && $TestCreditPoints <= 55) {
                    $TestCreditPoints = 9;
                } else if ($TestCreditPoints >= 55.01 && $TestCreditPoints <= 60) {
                    $TestCreditPoints = 9.5;
                } else if ($TestCreditPoints > 60) {
                    $TestCreditPoints = 10;
                }
                if ($InningPlayed < 20) {
                    if ($TestCreditPoints > 7 && $TestCreditPoints <= 7.99) {
                        $TestCreditPoints = 7;
                    } else if ($TestCreditPoints >= 8) {
                        $TestCreditPoints = $TestCreditPoints - 1;
                    }
                }
            }
        }
        return $TestCreditPoints;
    }

    /*
      Description: Use to get T20 bowling credits
     */

    function getT20BowlingCredits($T20Bowling = array()) {
        $T20CreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($T20Bowling)) {
            $T20BowlingAverage = $T20Bowling->Average;
            $T20BowlingWickets = $T20Bowling->Wickets;
            $T20BowlingInnings = $T20Bowling->Innings;
            $T20BowlingEconomyRate = $T20Bowling->Economy;
            $InningPlayed = $T20Bowling->Innings;
            if (!empty($T20BowlingAverage)) {
                $T20CreditPoints = number_format((float) (($T20BowlingEconomyRate * $T20BowlingEconomyRate) * $T20BowlingInnings) / (10 * $T20BowlingWickets), 2, '.', '');
                if ($T20CreditPoints > 8) {
                    $T20CreditPoints = 7;
                } else if ($T20CreditPoints >= 7.01 && $T20CreditPoints <= 8) {
                    $T20CreditPoints = 7.5;
                } else if ($T20CreditPoints >= 6.01 && $T20CreditPoints <= 7) {
                    $T20CreditPoints = 8;
                } else if ($T20CreditPoints >= 5.01 && $T20CreditPoints <= 6) {
                    $T20CreditPoints = 8.5;
                } else if ($T20CreditPoints >= 4.51 && $T20CreditPoints <= 5) {
                    $T20CreditPoints = 9;
                } else if ($T20CreditPoints >= 4.01 && $T20CreditPoints <= 4.5) {
                    $T20CreditPoints = 9.5;
                } else if ($T20CreditPoints >= 3.51 && $T20CreditPoints <= 4) {
                    $T20CreditPoints = 10;
                } else if ($T20CreditPoints <= 3.5) {
                    $T20CreditPoints = 10.5;
                }
                if ($InningPlayed < 20) {
                    if ($T20CreditPoints > 7 && $T20CreditPoints <= 7.99) {
                        $T20CreditPoints = 7;
                    } else if ($T20CreditPoints >= 8) {
                        $T20CreditPoints = $T20CreditPoints - 1;
                    }
                }
            }
        }
        return $T20CreditPoints;
    }

    /*
      Description: Use to get T20i bowling credits
     */

    function getT20iBowlingCredits($T20iBowling = array()) {
        $T20iCreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($T20iBowling)) {
            $T20iBowlingAverage = $T20iBowling->Average;
            $T20iBowlingWickets = $T20iBowling->Wickets;
            $T20iBowlingInnings = $T20iBowling->Innings;
            $T20iBowlingEconomyRate = $T20iBowling->Economy;
            $InningPlayed = $T20iBowling->Innings;
            if (!empty($T20iBowlingAverage)) {
                $T20iCreditPoints = number_format((float) (($T20iBowlingEconomyRate * $T20iBowlingEconomyRate) * $T20iBowlingInnings) / (10 * $T20iBowlingWickets), 2, '.', '');
                if ($T20iCreditPoints > 8) {
                    $T20iCreditPoints = 7;
                } else if ($T20iCreditPoints >= 7.01 && $T20iCreditPoints <= 8) {
                    $T20iCreditPoints = 7.5;
                } else if ($T20iCreditPoints >= 6.01 && $T20iCreditPoints <= 7) {
                    $T20iCreditPoints = 8;
                } else if ($T20iCreditPoints >= 5.01 && $T20iCreditPoints <= 6) {
                    $T20iCreditPoints = 8.5;
                } else if ($T20iCreditPoints >= 4.51 && $T20iCreditPoints <= 5) {
                    $T20iCreditPoints = 9;
                } else if ($T20iCreditPoints >= 4.01 && $T20iCreditPoints <= 4.5) {
                    $T20iCreditPoints = 9.5;
                } else if ($T20iCreditPoints >= 3.51 && $T20iCreditPoints <= 4) {
                    $T20iCreditPoints = 10;
                } else if ($T20iCreditPoints <= 3.5) {
                    $T20iCreditPoints = 10.5;
                }
                if ($InningPlayed < 20) {
                    if ($T20iCreditPoints > 7 && $T20iCreditPoints <= 7.99) {
                        $T20iCreditPoints = 7;
                    } else if ($T20iCreditPoints >= 8) {
                        $T20iCreditPoints = $T20iCreditPoints - 1;
                    }
                }
            }
        }
        return $T20iCreditPoints;
    }

    /*
      Description: Use to get ODI bowling credits
     */

    function getODIBowlingCredits($ODIBowling = array()) {
        $ODICreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($ODIBowling)) {
            $ODIBowlingAverage = $ODIBowling->Average;
            $ODIBowlingWickets = $ODIBowling->Wickets;
            $ODIBowlingInnings = $ODIBowling->Innings;
            $ODIBowlingEconomyRate = $ODIBowling->Economy;
            $InningPlayed = $ODIBowling->Innings;
            if (!empty($ODIBowlingAverage)) {
                $ODICreditPoints = number_format((float) ($ODIBowlingEconomyRate * $ODIBowlingInnings) / $ODIBowlingWickets, 2, '.', '');
                if ($ODICreditPoints > 7) {
                    $ODICreditPoints = 6.5;
                } else if ($ODICreditPoints >= 6.01 && $ODICreditPoints <= 7) {
                    $ODICreditPoints = 7;
                } else if ($ODICreditPoints >= 5.01 && $ODICreditPoints <= 6) {
                    $ODICreditPoints = 7.5;
                } else if ($ODICreditPoints >= 4.01 && $ODICreditPoints <= 5) {
                    $ODICreditPoints = 8;
                } else if ($ODICreditPoints >= 3.01 && $ODICreditPoints <= 4) {
                    $ODICreditPoints = 8.5;
                } else if ($ODICreditPoints >= 2.51 && $ODICreditPoints <= 3) {
                    $ODICreditPoints = 9;
                } else if ($ODICreditPoints >= 2.01 && $ODICreditPoints <= 2.5) {
                    $ODICreditPoints = 9.5;
                } else if ($ODICreditPoints <= 2) {
                    $ODICreditPoints = 10;
                }
                if ($InningPlayed < 20) {
                    if ($ODICreditPoints > 7 && $ODICreditPoints <= 7.99) {
                        $ODICreditPoints = 7;
                    } else if ($ODICreditPoints >= 8) {
                        $ODICreditPoints = $ODICreditPoints - 1;
                    }
                }
            }
        }
        return $ODICreditPoints;
    }

    /*
      Description: Use to get Test bowling credits
     */

    function getTestBowlingCredits($TestBowling = array()) {
        $TestCreditPoints = DEFAULT_PLAYER_CREDITS;
        if (!empty($TestBowling)) {
            $TestBowlingAverage = $TestBowling->Average;
            $TestBowlingWickets = $TestBowling->Wickets;
            $TestBowlingInnings = $TestBowling->Innings;
            $InningPlayed = $TestBowling->Innings;
            if (!empty($TestBowlingAverage)) {
                $TestCreditPoints = number_format((float) ($TestBowlingWickets / $TestBowlingInnings), 2, '.', '');
                if ($TestCreditPoints > 7) {
                    $TestCreditPoints = 6.5;
                } else if ($TestCreditPoints >= 6.01 && $TestCreditPoints <= 7) {
                    $TestCreditPoints = 7;
                } else if ($TestCreditPoints >= 5.01 && $TestCreditPoints <= 6) {
                    $TestCreditPoints = 7.5;
                } else if ($TestCreditPoints >= 4.01 && $TestCreditPoints <= 5) {
                    $TestCreditPoints = 8;
                } else if ($TestCreditPoints >= 3.01 && $TestCreditPoints <= 4) {
                    $TestCreditPoints = 8.5;
                } else if ($TestCreditPoints >= 2.51 && $TestCreditPoints <= 3) {
                    $TestCreditPoints = 9;
                } else if ($TestCreditPoints >= 2.01 && $TestCreditPoints <= 2.5) {
                    $TestCreditPoints = 9.5;
                } else if ($TestCreditPoints <= 2) {
                    $TestCreditPoints = 10;
                }
                if ($InningPlayed < 20) {
                    if ($TestCreditPoints > 7 && $TestCreditPoints <= 7.99) {
                        $TestCreditPoints = 7;
                    } else if ($TestCreditPoints >= 8) {
                        $TestCreditPoints = $TestCreditPoints - 1;
                    }
                }
            }
        }
        return $TestCreditPoints;
    }

    function match_players_best_played($Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $return['code'] = 200;

        $MatchData = $this->Sports_model->getMatches('MatchID,TeamIDLocal,TeamIDVisitor,SeriesID,MatchType,PlayerSalary', array('MatchID' => $Where['MatchID']), TRUE, 0);
        $dataArr = array();

        $playersData = $this->Sports_model->getPlayers('PlayerRole,PlayerPic,PlayerBattingStyle,PlayerBowlingStyle,MatchType,MatchNo,MatchDateTime,SeriesName,TeamGUID,PlayerBattingStats,PlayerBowlingStats,IsPlaying,PointsData,PlayerSalary,TeamNameShort,PlayerPosition,TotalPoints', array_merge($this->Post, array('MatchID' => @$this->MatchID)), TRUE, 0);
        $Wicketkipper = $this->findKeyValuePlayers($playersData['Data']['Records'], "WicketKeeper");
        $Batsman = $this->findKeyValuePlayers($playersData['Data']['Records'], "Batsman");
        $Bowler = $this->findKeyValuePlayers($playersData['Data']['Records'], "Bowler");
        $Allrounder = $this->findKeyValuePlayers($playersData['Data']['Records'], "AllRounder");

        usort($Batsman, function ($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
        });
        usort($Bowler, function ($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
        });
        usort($Wicketkipper, function ($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
        });
        usort($Allrounder, function ($a, $b) {
            return $b->TotalPoints - $a->TotalPoints;
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
            return $b->TotalPoints - $a->TotalPoints;
        });
        foreach ($AllPlayers as $key => $value) {
            $AllPlayers[$key]['PlayerPosition'] = 'Player';
            $AllPlayers[0]['PlayerPosition'] = 'Captain';
            $AllPlayers[1]['PlayerPosition'] = 'ViceCaptain';
            $TotalCalculatedPoints += $value['TotalPoints'];
        }
        // print_r($TotalCalculatedPoints); die;
        $Records['Data']['Records'] = $AllPlayers;
        $Records['Data']['TotalPoints'] = $TotalCalculatedPoints;
        $Records['Data']['TotalRecords'] = count($AllPlayers);
        if ($AllPlayers) {
            return $Records;
        } else {
            return false;
        }
    }

    function findKeyValuePlayers($array, $value) {
        if (is_array($array)) {
            $players = array();
            foreach ($array as $key => $rows) {
                if ($rows['PlayerRole'] == $value) {
                    $players[] = $array[$key];
                }
            }
            return $players;
        }
        return false;
    }

    function findKeyArrayDiff($array, $value) {
        if (is_array($array)) {
            $players = array();
            foreach ($array as $key => $rows) {
                if ($rows['PlayerID'] == $value) {
                    return false;
                }
            }
            return true;
        }
        return true;
    }

    /*
      Description: To get sports best played players of the match
     */

    function getMatchBestPlayers($Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {

        /* Get Match Players */
        /* $playersData = $this->Sports_model->getPlayers('PlayerRole,PlayerCountry,PlayerPic,PlayerBattingStyle,PlayerBowlingStyle,MatchType,MatchNo,MatchDateTime,SeriesName,TeamGUID,IsPlaying,PlayerSalary,TeamNameShort,PlayerPosition,TotalPoints,TotalPointCredits', array('MatchID' => $Where['MatchID'],'OrderBy' => 'TotalPoints','Sequence' => 'DESC','IsPlaying' => 'Yes'), TRUE,0); */
        $playersData = $this->Sports_model->getPlayers('PlayerID,PlayerRole,PointsData,PlayerPic,PlayerBattingStyle,PlayerBowlingStyle,MatchType,MatchNo,MatchDateTime,SeriesName,TeamGUID,IsPlaying,PlayerSalary,TeamNameShort,PlayerPosition,TotalPoints,TotalPointCredits,MyTeamPlayer,PlayerSelectedPercent', array('MatchID' => $Where['MatchID'], 'UserID' => $Where['UserID'],
            'OrderBy' => 'TotalPoints', 'Sequence' => 'DESC', 'IsPlaying' => 'Yes'), TRUE, 0);
        if (!$playersData) {
            return false;
        }
        $finalXIPlayers = array();
        foreach ($playersData['Data']['Records'] as $Key => $Value) {
            $Row = $Value;
            $Row['PlayerPosition'] = ($Key == 0) ? 'Captain' : (($Key == 1) ? 'ViceCaptain' : 'Player');
            $Row['TotalPoints'] = strval(($Key == 0) ? 2 * $Row['TotalPoints'] : (($Key == 1) ? 1.5 * $Row['TotalPoints'] : $Row['TotalPoints']));
            array_push($finalXIPlayers, $Row);
        }

        $Batsman = $this->findKeyValuePlayers($finalXIPlayers, "Batsman");
        $Bowler = $this->findKeyValuePlayers($finalXIPlayers, "Bowler");
        $Wicketkipper = $this->findKeyValuePlayers($finalXIPlayers, "WicketKeeper");
        $Allrounder = $this->findKeyValuePlayers($finalXIPlayers, "AllRounder");

        $TopBatsman = array_slice($Batsman, 0, 3);
        $TopBowler = array_slice($Bowler, 0, 3);
        $TopWicketkipper = array_slice($Wicketkipper, 0, 2);
        $TopAllrounder = array_slice($Allrounder, 0, 3);

        $BatsmanSort = $BowlerSort = $WicketKipperSort = $AllRounderSort = array();
        foreach ($TopBatsman as $BatsmanValue) {
            $BatsmanSort[] = $BatsmanValue['TotalPoints'];
        }
        array_multisort($BatsmanSort, SORT_DESC, $TopBatsman);

        foreach ($TopBowler as $BowlerValue) {
            $BowlerSort[] = $BowlerValue['TotalPoints'];
        }
        array_multisort($BowlerSort, SORT_DESC, $TopBowler);

        foreach ($TopWicketkipper as $WicketKipperValue) {
            $WicketKipperSort[] = $WicketKipperValue['TotalPoints'];
        }
        array_multisort($WicketKipperSort, SORT_DESC, $TopWicketkipper);

        foreach ($TopAllrounder as $AllrounderValue) {
            $AllRounderSort[] = $AllrounderValue['TotalPoints'];
        }
        array_multisort($AllRounderSort, SORT_DESC, $TopAllrounder);

        $AllPlayers = array();
        $AllPlayers = array_merge($TopBatsman, $TopBowler);
        $AllPlayers = array_merge($AllPlayers, $TopAllrounder);
        $AllPlayers = array_merge($AllPlayers, $TopWicketkipper);

        $TotalCalculatedPoints = 0;
        foreach ($AllPlayers as $Value) {
            $TotalCalculatedPoints += $Value['TotalPoints'];
        }

        $Records['Data']['Records'] = $AllPlayers;
        $Records['Data']['TotalPoints'] = strval($TotalCalculatedPoints);
        $Records['Data']['TotalRecords'] = count($AllPlayers);
        if ($AllPlayers) {
            return $Records;
        } else {
            return FALSE;
        }
    }

    /*
      Description: To Auto Cancel Contest
     */

    function autoCancelContestMatchAbonded($MatchID) {

        ini_set('max_execution_time', 300);
        /* Get Contest Data */
        $ContestsUsers = $this->Contest_model->getContests('ContestID,Privacy,EntryFee,TotalJoined,ContestFormat,ContestSize,IsConfirm,SeriesName,ContestName,MatchStartDateTime,MatchNo,TeamNameLocal,TeamNameVisitor', array('StatusID' => array(1, 2, 5), "MatchID" => $MatchID), true, 0);
        if ($ContestsUsers['Data']['TotalRecords'] > 0) {
            foreach ($ContestsUsers['Data']['Records'] as $Value) {
                /* Update Contest Status */
                $this->db->where('EntityID', $Value['ContestID']);
                $this->db->limit(1);
                $this->db->update('tbl_entity', array('ModifiedDate' => date('Y-m-d H:i:s'), 'StatusID' => 3));
            }
        }
    }

    /*
      Description: To get sports player fantasy stats series wise
     */

    function getPlayerFantasyStats($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'MatchNo' => 'M.MatchNo',
                'SeriesGUID' => 'M.SeriesGUID',
                'MatchLocation' => 'M.MatchLocation',
                'MatchStartDateTime' => 'DATE_FORMAT(CONVERT_TZ(M.MatchStartDateTime,"+00:00","' . DEFAULT_TIMEZONE . '"), "' . DATE_FORMAT . ' ") MatchStartDateTime',
                'TeamNameLocal' => 'TL.TeamName AS TeamNameLocal',
                'TeamNameVisitor' => 'TV.TeamName AS TeamNameVisitor',
                'TeamNameShortLocal' => 'TL.TeamNameShort AS TeamNameShortLocal',
                'TeamNameShortVisitor' => 'TV.TeamNameShort AS TeamNameShortVisitor',
                'TeamFlagLocal' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TL.TeamFlag) as TeamFlagLocal',
                'TeamFlagVisitor' => 'CONCAT("' . BASE_URL . '","uploads/TeamFlag/",TV.TeamFlag) as TeamFlagVisitor',
                'TotalPoints' => 'TP.TotalPoints',
                'PlayerRole' => 'TP.PlayerRole',
                'TotalTeams' => '(SELECT COUNT(UserTeamID) FROM `sports_users_teams` WHERE `MatchID` = M.MatchID) TotalTeams'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('M.MatchGUID,M.MatchID,TP.PlayerID');
        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_matches M, sports_teams TL, sports_teams TV, sports_team_players TP');
        $this->db->where("E.EntityID", "M.MatchID", FALSE);
        $this->db->where("M.MatchID", "TP.MatchID", FALSE);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
        if (!empty($Where['SeriesID'])) {
            $this->db->where("TP.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['MatchID'])) {
            $this->db->where("TP.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("TP.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['PlayerID'])) {
            $this->db->where("TP.PlayerID", $Where['PlayerID']);
        }
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
        }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        } else {
            $this->db->order_by('M.MatchStartDateTime', 'DESC');
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
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                if (in_array('PlayerBattingStats', $Params)) {
                    $PlayerData = $this->getPlayers('PlayerBattingStats,PlayerBowlingStats,PlayerFieldingStats', array('PlayerID' => $Where['PlayerID']), true, 0);
                    $Return['Data']['PlayerBattingStats'] = $PlayerData['Data']['Records'][0]['PlayerBattingStats'];
                    $Return['Data']['PlayerBowlingStats'] = $PlayerData['Data']['Records'][0]['PlayerBowlingStats'];
                    $Return['Data']['PlayerFieldingStats'] = $PlayerData['Data']['Records'][0]['PlayerFieldingStats'];
                }
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    if (in_array('PlayerSelectedPercent', $Params)) {
                        $this->db->select('COUNT(SUTP.PlayerID) TotalPlayer');
                        $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                        $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                        $this->db->where("SUTP.MatchID", $Record['MatchID']);
                        $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                        $Players = $this->db->get()->row();
                        $Records[$key]['PlayerSelectedPercent'] = ($Record['TotalTeams'] > 0) ? strval(round((($Players->TotalPlayer * 100 ) / $Record['TotalTeams']), 2) > 100 ? 100 : round((($Players->TotalPlayer * 100 ) / $Record['TotalTeams']), 2)) : "0";
                    }
                    unset($Records[$key]['PlayerID'], $Records[$key]['MatchID']);
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                if (in_array('PlayerSelectedPercent', $Params)) {
                    $this->db->select('COUNT(SUTP.PlayerID) TotalPlayer');
                    $this->db->where("SUTP.UserTeamID", "SUT.UserTeamID", FALSE);
                    $this->db->where("SUTP.PlayerID", $Record['PlayerID']);
                    $this->db->where("SUTP.MatchID", $Record['MatchID']);
                    $this->db->from('sports_users_teams SUT,sports_users_team_players SUTP');
                    $Players = $this->db->get()->row();
                    $Record['PlayerSelectedPercent'] = ($Record['TotalTeams'] > 0) ? strval(round((($Players->TotalPlayer * 100 ) / $Record['TotalTeams']), 2) > 100 ? 100 : round((($Players->TotalPlayer * 100 ) / $Record['TotalTeams']), 2)) : "0";
                }
                unset($Record['PlayerID'], $Record['MatchID']);
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description:  Cron jobs to get auction joined player points.

     */

    function getAuctionDraftJoinedUserTeamsPlayerPoints($CronID) {
        ini_set('max_execution_time', 300);
        /** get series play in auction * */
        $SeriesData = $this->getRounds('SeriesID,RoundID', array("AuctionDraftStatusID" => 2, "AuctionDraftIsPlayed" => "Yes"), true, 0);
        //$SeriesData = $this->getRounds('SeriesID,RoundID', array("AuctionDraftStatusID" => 2, "RoundID" => 46), true, 0);
        if ($SeriesData['Data']['TotalRecords'] == 0) {
            return true;
        }

        $ContestIDArray = array();
        /* Get Vice Captain Points */
        $ViceCaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "ViceCaptainPointMP" LIMIT 1')->row_array();
        /* Get Captain Points */
        $CaptainPointsData = $this->db->query('SELECT * FROM sports_setting_points WHERE PointsTypeGUID = "CaptainPointMP" LIMIT 1')->row_array();
        foreach ($SeriesData['Data']['Records'] as $Rows) {
            /** get series walse player point * */
            $this->db->select("SeriesID,PlayerID,TotalPoints");
            $this->db->from('sports_auction_draft_player_point');
            $this->db->where("SeriesID", $Rows['SeriesID']);
            $this->db->where("RoundID", $Rows['RoundID']);
            $this->db->where("TotalPoints >", 0);
            $Query = $this->db->get();
            if ($Query->num_rows() > 0) {
                $MatchPlayers = $Query->result_array();
                //dump($MatchPlayers);
                /* Get Matches Live */
                $Sql = "Select J.RoundID,J.ContestID,J.SeriesID,J.AuctionStatusID,CJ.UserID,CJ.UserTeamID "
                        . " FROM sports_contest J INNER JOIN sports_contest_join CJ ON CJ.ContestID=J.ContestID"
                        . " WHERE J.AuctionStatusID = 5 AND J.SeriesID='" . $Rows['SeriesID'] . "' AND J.RoundID='" . $Rows['RoundID'] . "' AND LeagueType != 'Dfs' AND CJ.UserTeamID IS NOT NULL";
                $ContestsJoined = $this->customQuery($Sql);
                foreach ($ContestsJoined as $Value) {
                    $SeriesID = $Value['SeriesID'];
                    $RoundID = $Value['RoundID'];
                    $ContestID = $Value['ContestID'];
                    /* Match Types */
                    $MatchTypesArr = array('1' => 'PointsODI', '3' => 'PointsT20', '4' => 'PointsT20', '5' => 'PointsTEST', '7' => 'PointsT20', '9' => 'PointsODI');
                    $ContestIDArray[] = $Value['ContestID'];
                    /* Player Points Multiplier */
                    $UserID = $Value["UserID"];
                    $PositionPointsMultiplier = array('ViceCaptain' => 1.5, 'Captain' => 2, 'Player' => 1);
                    $UserTotalPoints = 0;

                    // $PlayersPointsArr = array_column($MatchPlayers, 'TotalPoints', 'PlayerGUID');
                    $PlayersIdsArr = array_column($MatchPlayers, 'TotalPoints', 'PlayerID');

                    /* To Get User Team Players */
                    $UserTeamPlayers = $this->AuctionDrafts_model->getUserTeams('PlayerID,PlayerPosition,UserTeamPlayers,UserTeamType', array('UserTeamID' => $Value['UserTeamID'],'AuctionPlayingPlayer' => "Yes"), 0);
                    //print_r($UserTeamPlayers);exit;
                    $MyPlayers = array();
                    foreach ($UserTeamPlayers['UserTeamPlayers'] as $UserTeamValue) {
                        if (!isset($PlayersIdsArr[$UserTeamValue['PlayerID']])) continue;

                        $Points = ($PlayersIdsArr[$UserTeamValue['PlayerID']] != 0) ? $PlayersIdsArr[$UserTeamValue['PlayerID']] * $PositionPointsMultiplier[$UserTeamValue['PlayerPosition']] : 0;
                        $UserTotalPoints = ($Points > 0) ? $UserTotalPoints + $Points : $UserTotalPoints - abs($Points);
                        $Temp['Points'] = $Points;
                        $Temp['UserTeamID'] = $Value['UserTeamID'];
                        $Temp['PlayerID'] = $UserTeamValue['PlayerID'];
                        $MyPlayers[] = $Temp;
                        /* Update Player Points */
                        $this->db->where('UserTeamID', $Value['UserTeamID']);
                        $this->db->where('PlayerID', $UserTeamValue['PlayerID']);
                        $this->db->limit(1);
                        $this->db->update('sports_users_team_players', array('Points' => $Points));
                    }
                    if (!empty($MyPlayers)) {
                        arsort($MyPlayers);
                        $TopPlayer = array_chunk($MyPlayers, 11, TRUE);
                        $UserTotalPoints = array_sum(array_column($TopPlayer[0], "Points"));
                    }

                    /* Update Player Total Points */
                    $this->db->where('UserID', $UserID);
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->where('UserTeamID', $Value['UserTeamID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('TotalPoints' => $UserTotalPoints, 'ModifiedDate' => date('Y-m-d H:i:s')));
                    //echo $this->db->last_query();exit;
                    $ContestIDArray = array_unique($ContestIDArray);

                    /* else {
                      $this->db->where('CronID', $CronID);
                      $this->db->limit(1);
                      $this->db->update('log_cron', array('CronStatus' => 'Exit'));
                      exit;
                      } */
                }
            }
        }

        $this->updateRankByContestAuction($ContestIDArray);
    }

    /*
      Description: To update rank
     */

    function updateRankByContestAuction($ContestIDs) {
        if (!empty($ContestIDs)) {
            foreach ($ContestIDs as $ContestID) {
                $query = $this->db->query("SELECT FIND_IN_SET( TotalPoints, 
                         ( SELECT GROUP_CONCAT( TotalPoints ORDER BY TotalPoints DESC)
                         FROM sports_contest_join WHERE sports_contest_join.ContestID = '" . $ContestID . "')) AS UserRank,ContestID,UserTeamID
                         FROM sports_contest_join,tbl_users 
                         WHERE sports_contest_join.ContestID = '" . $ContestID . "' AND tbl_users.UserID = sports_contest_join.UserID
                     ");
                $results = $query->result_array();
                if (!empty($results)) {
                    $this->db->trans_start();
                    foreach ($results as $rows) {
                        $this->db->where('ContestID', $rows['ContestID']);
                        $this->db->where('UserTeamID', $rows['UserTeamID']);
                        $this->db->limit(1);
                        $this->db->update('sports_contest_join', array('UserRank' => $rows['UserRank']));
                    }
                    $this->db->trans_complete();
                }
            }
        }
    }

    /*
      Description: To get all players
     */

    function getAuctionDraftPlayers($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15) {
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
                'PlayerFieldingStats' => 'P.PlayerFieldingStats',
                'PlayerSalary' => 'FORMAT(TP.PlayerSalary,1) as PlayerSalary',
                'PlayerSalaryCredit' => 'FORMAT(TP.PlayerSalary,1) PlayerSalaryCredit',
                'LastUpdateDiff' => 'IF(P.LastUpdatedOn IS NULL, 0, TIME_TO_SEC(TIMEDIFF("' . date('Y-m-d H:i:s') . '", P.LastUpdatedOn))) LastUpdateDiff',
                'MatchTypeID' => 'SSM.MatchTypeID',
                'MatchType' => 'SSM.MatchTypeName as MatchType',
                'TotalPointCredits' => '(SELECT SUM(`TotalPoints`) FROM `sports_team_players` WHERE `PlayerID` = TP.PlayerID AND `SeriesID` = TP.SeriesID) TotalPointCredits',
                'RoundID' => 'TP.RoundID'
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('P.PlayerGUID,P.PlayerName');
        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P');
        if (array_keys_exist($Params, array('TeamGUID', 'TeamName', 'TeamNameShort', 'TeamFlag',
                    'PlayerRole', 'IsPlaying', 'TotalPoints', 'PointsData', 'SeriesID',
                    'MatchID'))) {
            $this->db->from('sports_teams T,sports_auction_draft_series_players TP');
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
        if (!empty($Where['MatchID'])) {
            $this->db->where("TP.MatchID", $Where['MatchID']);
        }
        if (!empty($Where['SeriesID'])) {
            $this->db->where("TP.SeriesID", $Where['SeriesID']);
        }
        if (!empty($Where['PlayerGUID'])) {
            $this->db->where("P.PlayerGUID", $Where['PlayerGUID']);
        }
        if (!empty($Where['RoundID'])) {
            $this->db->where("TP.RoundID", $Where['RoundID']);
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
        if (!empty($Where['StatusID'])) {
            $this->db->where("E.StatusID", $Where['StatusID']);
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
        // echo $this->db->last_query();exit;
        $MatchStatus = 0;
        if (!empty($Where['MatchID'])) {
            /* Get Match Status */
            $MatchQuery = $this->db->query('SELECT E.StatusID FROM `sports_matches` `M`,`tbl_entity` `E` WHERE M.`MatchID` = "' . $Where['MatchID'] . '" AND M.MatchID = E.EntityID LIMIT 1');
            $MatchStatus = ($MatchQuery->num_rows() > 0) ? $MatchQuery->row()->StatusID : 0;
        }
        if ($Query->num_rows() > 0) {
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    $Records[$key]['TopPlayer'] = (in_array($Record['PlayerGUID'], $BestXIPlayers)) ? 'Yes' : 'No';
                    $Records[$key]['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                    $Records[$key]['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                    $Records[$key]['PlayerFieldingStats'] = (!empty($Record['PlayerFieldingStats'])) ? json_decode($Record['PlayerFieldingStats']) : new stdClass();
                    $Records[$key]['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                    $Records[$key]['PlayerSalary'] = ($Record['PlayerSalary'] == 0 ? "8.0" : $Record['PlayerSalary']);
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } else {
                $Record = $Query->row_array();
                $Records[$key]['TopPlayer'] = (in_array($Record['PlayerGUID'], $BestXIPlayers)) ? 'Yes' : 'No';
                $Record['PlayerBattingStats'] = (!empty($Record['PlayerBattingStats'])) ? json_decode($Record['PlayerBattingStats']) : new stdClass();
                $Record['PlayerBowlingStats'] = (!empty($Record['PlayerBowlingStats'])) ? json_decode($Record['PlayerBowlingStats']) : new stdClass();
                $Record['PlayerFieldingStats'] = (!empty($Record['PlayerFieldingStats'])) ? json_decode($Record['PlayerFieldingStats']) : new stdClass();
                $Record['PointsData'] = (!empty($Record['PointsData'])) ? json_decode($Record['PointsData'], TRUE) : array();
                $Record['PlayerSalary'] = ($Record['PlayerSalary'] == 0 ? "8.0" : $Record['PlayerSalary']);
                return $Record;
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

        if (!empty($Field)) $this->db->select($Field, FALSE);
        $this->db->from('tbl_entity E, sports_players P, sports_teams TL, sports_teams TV');
        if (array_keys_exist($Params, array('TeamGUID', 'TeamName', 'TeamNameShort', 'TeamFlag',
                    'PlayerRole', 'IsPlaying', 'TotalPoints', 'PointsData', 'SeriesID',
                    'MatchID'))) {
            $this->db->from('sports_teams T,sports_matches M, sports_team_players TP,sports_set_match_types SSM');
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

    /*
      Description: To transfer joined contest data (MongoDB To MySQL).
     */

    function tranferJoinedContestData($CronID) {
        /* Get Contests Data */
        $Contests = $this->db->query('SELECT C.ContestID FROM sports_contest C WHERE C.IsWinningDistributed = "Yes" AND C.LeagueType = "Dfs" AND C.ContestTransferred="No" LIMIT 50');
        //$Contests = $this->db->query('SELECT C.ContestID FROM sports_contest C WHERE C.IsWinningDistributed = "Yes" AND C.LeagueType = "Dfs" AND C.MatchID=113644');
        if ($Contests->num_rows() > 0) {
            foreach ($Contests->result_array() as $Value) {

                /* Get Joined Contests */
                $ContestCollection = $this->fantasydb->{'Contest_' . $Value['ContestID']};
                $JoinedContestsUsers = $ContestCollection->find(["ContestID" => $Value['ContestID'],
                    "IsWinningAssigned" => "Yes"], ['projection' => ['ContestID' => 1, 'UserID' => 1, 'UserTeamID' => 1,
                        'UserTeamPlayers' => 1, 'TotalPoints' => 1, 'UserRank' => 1,
                        'UserWinningAmount' => 1, 'TaxAmount' => 1]]);

                if ($ContestCollection->count(["ContestID" => $Value['ContestID'],
                            "IsWinningAssigned" => "Yes"]) == 0) {

                    /* Update Contest Winning Assigned Status */
                    $this->db->where('ContestID', $Value['ContestID']);
                    $this->db->limit(1);
                    $this->db->update('sports_contest', array('ContestTransferred' => "Yes"));
                    continue;
                }

                foreach ($JoinedContestsUsers as $JC) {

                    /* Update User Team Player Points */
                    $this->db->where(array('UserID' => $JC['UserID'], 'ContestID' => $JC['ContestID'],
                        'UserTeamID' => $JC['UserTeamID']));
                    $this->db->limit(1);
                    $this->db->update('sports_contest_join', array('TotalPoints' => $JC['TotalPoints'], 'UserRank' => $JC['UserRank'],
                        'UserWinningAmount' => $JC['UserWinningAmount'], 'ModifiedDate' => date('Y-m-d H:i:s')));

                    /* Update MongoDB Row */
                    $ContestCollection->updateOne(
                            ['_id' => $JC['_id']], ['$set' => ['IsWinningAssigned' => 'Moved']], ['upsert' => false]
                    );
                }
            }
        }
    }

    function refundAmountCancelContestDebug() {
        ini_set('max_execution_time', 300);
        /* Get Contest Data */
        $this->db->select('M.MatchID,TL.TeamNameShort AS TeamNameLocal,TV.TeamNameShort AS TeamNameVisitor,M.MatchStartDateTime');
        $this->db->from('sports_matches M,tbl_entity E,sports_teams TL, sports_teams TV');
        $this->db->where("E.EntityID", "M.MatchID", FALSE);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
        $this->db->where('MONTH(M.MatchStartDateTime)', date('m'));
        $this->db->where("E.StatusID", 5);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Matches = $Query->result_array();
            //dump($Matches);
            $MatchRefund = array();
            foreach ($Matches as $Match) {
                $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,C.ContestSize,C.IsConfirm,C.ContestName');
                $this->db->from('sports_contest C,tbl_entity E');
                $this->db->where("E.EntityID", "C.ContestID", FALSE);
                $this->db->where("C.LeagueType", "Dfs");
                $this->db->where("E.StatusID", 3);
                $this->db->where("C.MatchID", $Match['MatchID']);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $CancellContest = $Query->result_array();
                    foreach ($CancellContest as $Value) {
                        /* Get Joined Contest Users */
                        $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.IsRefund');
                        $this->db->from('sports_contest_join JC, tbl_users U');
                        $this->db->where("JC.UserID", "U.UserID", FALSE);
                        //$this->db->where("JC.IsRefund", "No");
                        $this->db->where("JC.ContestID", $Value['ContestID']);
                        $this->db->where("U.UserTypeID !=", 3);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $JoinedContestsUsers = $Query->result_array();
                            foreach ($JoinedContestsUsers as $JoinValue) {
                                // $this->db->trans_start();
                                /* Refund Wallet Money */
                                if (!empty($Value['EntryFee'])) {
                                    /* Get Wallet Details */
                                    $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                                        'UserID' => $JoinValue['UserID'],
                                        'EntityID' => $Value['ContestID'],
                                        'UserTeamID' => $JoinValue['UserTeamID'],
                                        'Narration' => 'Cancel Contest'
                                    ));
                                    if (!empty($WalletDetails)) {
                                        /** update user refund status Yes */
                                        $this->db->where('ContestID', $JoinValue['ContestID']);
                                        $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                                        $this->db->where('UserID', $JoinValue['UserID']);
                                        $this->db->limit(1);
                                        $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                                        continue;
                                    }
                                    /* Get Wallet Details */
                                    $WalletDetails = $this->Users_model->getWallet('WalletAmount,WinningAmount,CashBonus', array(
                                        'UserID' => $JoinValue['UserID'],
                                        'EntityID' => $Value['ContestID'],
                                        'UserTeamID' => $JoinValue['UserTeamID'],
                                        'Narration' => 'Join Contest'
                                    ));
                                    if (!empty($WalletDetails)) {
                                        $MatchRefund[] = array(
                                            "Amount" => $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['CashBonus'],
                                            "WalletAmount" => $WalletDetails['WalletAmount'],
                                            "WinningAmount" => $WalletDetails['WinningAmount'],
                                            "CashBonus" => $WalletDetails['CashBonus'],
                                            "TransactionType" => 'Cr',
                                            "EntityID" => $Value['ContestID'],
                                            "UserTeamID" => $JoinValue['UserTeamID'],
                                            "Narration" => 'Cancel Contest',
                                            "EntryDate" => date("Y-m-d H:i:s"),
                                            "SportsType" => 'Cricket',
                                            "UserName" => $JoinValue['FirstName'],
                                            "UserEmail" => $JoinValue['Email'],
                                            "MatchName" => $Match['TeamNameLocal'] . " Vs " . $Match['TeamNameVisitor'],
                                            "MatchDate" => $Match['MatchStartDateTime']
                                        );
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
                                        $TotalRefundAmount = $WalletDetails['WalletAmount'] + $WalletDetails['WinningAmount'] + $WalletDetails['CashBonus'];
                                        $this->Users_model->addToWallet($InsertData, $JoinValue['UserID'], 5);
                                        $this->Notification_model->addNotification('refund', 'Contest Cancelled Refund', $JoinValue['UserID'], $JoinValue['UserID'], $Value['ContestID'], 'Your Rs.' . $TotalRefundAmount . ' has been refunded.');
                                    }

                                    /** update user refund status Yes */
                                    $this->db->where('ContestID', $JoinValue['ContestID']);
                                    $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                                    $this->db->where('UserID', $JoinValue['UserID']);
                                    $this->db->limit(1);
                                    $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                                } else {
                                    /** update user refund status Yes */
                                    $this->db->where('ContestID', $JoinValue['ContestID']);
                                    $this->db->where('UserTeamID', $JoinValue['UserTeamID']);
                                    $this->db->where('UserID', $JoinValue['UserID']);
                                    $this->db->limit(1);
                                    $this->db->update('sports_contest_join', array('IsRefund' => "Yes"));
                                }

//                                $this->db->trans_complete();
//                                if ($this->db->trans_status() === FALSE) {
//                                    return FALSE;
//                                }
                            }
                        } else {
                            /* Update Contest Refund Status Yes */
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest', array('IsRefund' => "Yes"));
                        }
                    }
                }
            }
            dump($MatchRefund);
        }
    }

    function amountDistributeContestWinnerDebug() {
        ini_set('max_execution_time', 300);

        $this->db->select('M.MatchID,TL.TeamNameShort AS TeamNameLocal,TV.TeamNameShort AS TeamNameVisitor,M.MatchStartDateTime');
        $this->db->from('sports_matches M,tbl_entity E,sports_teams TL, sports_teams TV');
        $this->db->where("E.EntityID", "M.MatchID", FALSE);
        $this->db->where("M.TeamIDLocal", "TL.TeamID", FALSE);
        $this->db->where("M.TeamIDVisitor", "TV.TeamID", FALSE);
        $this->db->where('MONTH(M.MatchStartDateTime)', date('m'));
        $this->db->where("E.StatusID", 5);
        $Query = $this->db->get();
        if ($Query->num_rows() > 0) {
            $Matches = $Query->result_array();
            //dump($Matches);
            $MatchWinning = array();
            foreach ($Matches as $Match) {
                /* Get Joined Contest Users */
                $this->db->select('C.ContestGUID,C.ContestID,C.EntryFee,E.StatusID,C.IsWinningDistributed,C.IsWinningDistributeAmount,C.ContestTransferred');
                $this->db->from('sports_contest C,tbl_entity E');
                $this->db->where("E.EntityID", "C.ContestID", FALSE);
                //$this->db->where("C.IsWinningDistributed", "Yes");
                //$this->db->where("C.IsWinningDistributeAmount", "No");
                $this->db->where("C.LeagueType", "Dfs");
                $this->db->where("C.ContestTransferred", "Yes");
                $this->db->where("E.StatusID", 5);
                $this->db->where("C.MatchID", $Match['MatchID']);
                $Query = $this->db->get();
                if ($Query->num_rows() > 0) {
                    $Contests = $Query->result_array();
                    //dump($Contests);
                    //$WalletData = array();
                    foreach ($Contests as $Value) {
                        /* Get Joined Contest Users */
                        $this->db->select('U.UserGUID,U.UserID,U.FirstName,U.Email,JC.ContestID,JC.MatchID,JC.UserTeamID,JC.UserRank,JC.UserWinningAmount,JC.IsWinningDistributeAmount,JC.SmartPool,JC.SmartPoolWinning');
                        $this->db->from('sports_contest_join JC, tbl_users U');
                        $this->db->where("JC.UserID", "U.UserID", FALSE);
                        // $this->db->where("JC.IsWinningDistributeAmount", "No");
                        $this->db->where("JC.ContestID", $Value['ContestID']);
                        $this->db->where("U.UserTypeID !=", 3);
                        $this->db->where("JC.UserWinningAmount >", 0);
                        $Query = $this->db->get();
                        if ($Query->num_rows() > 0) {
                            $JoinedContestsUsers = $Query->result_array();
                            if (!empty($JoinedContestsUsers)) {
                                foreach ($JoinedContestsUsers as $WinnerValue) {

                                    $this->db->trans_start();

                                    if ($WinnerValue['UserWinningAmount'] > 0) {
                                        $WalletDetails = $this->Users_model->getWallet('Amount,UserID,EntityID,UserTeamID,Narration', array('UserID' => $WinnerValue['UserID'], 'EntityID' => $WinnerValue['ContestID'], 'UserTeamID' => $WinnerValue['UserTeamID'], 'Narration' => 'Join Contest Winning'));
                                        /** update user wallet * */
                                        if (empty($WalletDetails)) {
                                            $MatchWinning[] = array(
                                                "Amount" => $WinnerValue['UserWinningAmount'],
                                                "WinningAmount" => $WinnerValue['UserWinningAmount'],
                                                "EntityID" => $WinnerValue['ContestID'],
                                                "UserTeamID" => $WinnerValue['UserTeamID'],
                                                "TransactionType" => 'Cr',
                                                "Narration" => 'Join Contest Winning',
                                                "EntryDate" => date("Y-m-d H:i:s"),
                                                'SportsType' => 'Cricket',
                                                "UserName" => $WinnerValue['FirstName'],
                                                "UserEmail" => $WinnerValue['Email'],
                                                "MatchName" => $Match['TeamNameLocal'] . " Vs " . $Match['TeamNameVisitor'],
                                                "MatchDate" => $Match['MatchStartDateTime']
                                            );
                                            $WalletData = array(
                                                "Amount" => $WinnerValue['UserWinningAmount'],
                                                "WinningAmount" => $WinnerValue['UserWinningAmount'],
                                                "EntityID" => $WinnerValue['ContestID'],
                                                "UserTeamID" => $WinnerValue['UserTeamID'],
                                                "TransactionType" => 'Cr',
                                                "Narration" => 'Join Contest Winning',
                                                "EntryDate" => date("Y-m-d H:i:s")
                                            );
                                            $this->Users_model->addToWallet($WalletData, $WinnerValue['UserID'], 5);
                                            $this->Notification_model->addNotification('winnings', 'Contest Winner', $WinnerValue['UserID'], $WinnerValue['UserID'], $WinnerValue['ContestID'], 'Congratulations you have won ' . DEFAULT_CURRENCY . $WinnerValue['UserWinningAmount'] . '');
                                        }
                                    }

                                    /** user join contest winning status update * */
                                    $this->db->where('UserID', $WinnerValue['UserID']);
                                    $this->db->where('ContestID', $Value['ContestID']);
                                    $this->db->where('UserTeamID', $WinnerValue['UserTeamID']);
                                    $this->db->limit(1);
                                    $this->db->update('sports_contest_join', array('IsWinningDistributeAmount' => "Yes", 'ModifiedDate' => date('Y-m-d H:i:s')));

                                    // if ($WinnerValue['SmartPool'] == 'Yes' && !empty($WinnerValue['SmartPoolWinning'])) {
                                    //  $this->Notification_model->addNotification('winnings', 'Smart Pool Contest Winner', $WinnerValue['UserID'], $WinnerValue['UserID'], $WinnerValue['ContestID'], 'Congratulations you have won ' . $WinnerValue['SmartPoolWinning'] . '.You will be notified soon for the same.');
                                    // }

                                    $this->db->trans_complete();
                                    if ($this->db->trans_status() === false) {
                                        return false;
                                    }
                                }
                            } else {
                                /* Update Contest Winning Status Yes */
                                $this->db->where('ContestID', $Value['ContestID']);
                                $this->db->limit(1);
                                $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                            }
                        } else {
                            /* Update Contest Winning Status Yes */
                            $this->db->where('ContestID', $Value['ContestID']);
                            $this->db->limit(1);
                            $this->db->update('sports_contest', array('IsWinningDistributeAmount' => "Yes"));
                        }
                    }
                    //dump($WalletData);
                }
            }

            dump($MatchWinning);
        }
    }

}

?>
