app.controller('PageController', function ($scope, $http, $timeout) {
    $scope.data.pageSize = 15;
    /*----------------*/
    $scope.getFilterData = function ()
    {
        var SeriesGUID = getQueryStringValue('SeriesGUID');
        var data = 'SessionKey=' + SessionKey + '&SeriesGUID=' + SeriesGUID + '&Params=TeamName,TeamGUID&' + $('#filterPanel form').serialize();
        $http.post(API_URL + 'admin/matches/getTeamData', data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200 && response.Data) {
                /* success case */
                $scope.filterData = response.Data;
                $timeout(function () {
                    $("select.chosen-select").chosen({width: '100%', "disable_search_threshold": 8}).trigger("chosen:updated");
                }, 300);
            }
        });
    }

    /*list*/
    $scope.applyFilter = function ()
    {
        $scope.data = angular.copy($scope.orig); /*copy and reset from original scope*/
        $scope.getList();
    }

    $scope.getMatchDetail = function () {
        $scope.matchDetail = {};
        if (getQueryStringValue('MatchGUID')) {
            var MatchGUID = getQueryStringValue('MatchGUID');
            $scope.AllMatches = false;
        } else {
            var MatchGUID = '';
            $scope.AllMatches = true;
        }
        $http.post(API_URL + 'admin/matches/getMatch', 'MatchGUID=' + MatchGUID + '&Params=SeriesName,MatchType,MatchNo,MatchStartDateTime,TeamNameLocal,TeamNameVisitor,TeamNameShortLocal,TeamNameShortVisitor,TeamFlagLocal,TeamFlagVisitor,MatchLocation&SessionKey=' + SessionKey, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.matchDetail = response.Data
            }
        });
    }
    $scope.SGUID = getQueryStringValue('SeriesGUID');
    /*list append*/
    $scope.getList = function ()
    {
        if ($scope.data.listLoading || $scope.data.noRecords)
            return;
        $scope.data.listLoading = true;
        if (getQueryStringValue('MatchGUID')) {
            var SeriesGUID = '';
            var RoundID = '';
            var ApiName = 'sports/getPlayers';
            $scope.RoundIDAva = true;
            var MatchGUID = getQueryStringValue('MatchGUID');
            var PlayerRole = 'TeamName,IsActive,PlayerRole,PlayerSalary,PlayerSalaryCredit';
            $scope.getMatchDetail();
        } else if (getQueryStringValue('RoundID')) {
            var MatchGUID = '';
            var SeriesGUID = '';
             var ApiName = 'admin/series/getAuctionDraftPlayers';
            var RoundID = getQueryStringValue('RoundID');
            $scope.RoundIDAva = false;
            var PlayerRole = 'TeamName,PlayerSalary,PlayerRole,PlayerSalaryCredit';
        } else {
            var MatchGUID = '';
            $scope.RoundIDAva = true;
             var ApiName = 'sports/getPlayers';
            var SeriesGUID = getQueryStringValue('SeriesGUID');
            var PlayerRole = 'TeamName,IsActive,PlayerSalary,PlayerRole,PlayerSalaryCredit';
        }
        var data = 'SessionKey=' + SessionKey + '&RoundID=' + RoundID + '&SeriesGUID=' + SeriesGUID + '&MatchGUID=' + MatchGUID + '&Params=' + PlayerRole + ',PlayerPic,GroupBy&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize + '&' + $('#filterForm').serialize() + '&' + $('#filterForm1').serialize();
        $http.post(API_URL + ApiName, data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200 && response.Data.Records) { /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                    $scope.data.dataList.push(response.Data.Records[i]);
                }
                $scope.data.pageNo++;
            } else {
                $scope.data.noRecords = true;
            }
            $scope.data.listLoading = false;
            // setTimeout(function(){ tblsort(); }, 1000);
        });
    }

    /*load add form*/
    $scope.loadFormAdd = function (Position, CategoryGUID)
    {
        $scope.templateURLAdd = PATH_TEMPLATE + module + '/add_form.htm?' + Math.random();
        $('#add_model').modal({show: true});
        $timeout(function () {
            $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
        }, 200);
    }


    /*load edit form*/
    $scope.loadFormEdit = function (Position, PlayerGUID)
    {   
        var MatchGUID = getQueryStringValue('MatchGUID');
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE + module + '/edit_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'sports/getPlayer', 'PlayerGUID=' + PlayerGUID + '&MatchGUID='+ MatchGUID +'&Params=PlayerRole,IsActive,PlayerPic,PlayerCountry,PlayerBornPlace,PlayerBattingStyle,PlayerBowlingStyle,MatchType,MatchNo,MatchDateTime,SeriesName,TeamGUID&SessionKey=' + SessionKey, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) {
                /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#edit_model').modal({show: true});
                $timeout(function () {
                    $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
                }, 200);
            }
        });
    }

    /*load delete form*/
    $scope.loadFormDelete = function (Position, CategoryGUID)
    {
        $scope.data.Position = Position;
        $scope.templateURLDelete = PATH_TEMPLATE + module + '/delete_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'category/getCategory', 'SessionKey=' + SessionKey + '&CategoryGUID=' + CategoryGUID, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#delete_model').modal({show: true});
                $timeout(function () {
                    $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
                }, 200);
            }
        });
    }

    /*edit data*/
    $scope.editData = function ()
    {
        if (getQueryStringValue('MatchGUID')) {
            var SeriesGUID = '';
            var RoundID = '';
            var UrlName = 'admin/matches/updatePlayerInfo';
            var MatchGUID = getQueryStringValue('MatchGUID');
        }else if (getQueryStringValue('RoundID')) {
            var SeriesGUID = '';
            var MatchGUID = '';
            var UrlName = 'admin/matches/updatePlayerAuctionDraft';
            var RoundID = getQueryStringValue('RoundID');
        } else {
            var MatchGUID = '';
            var RoundID = '';
            var UrlName = 'admin/matches/updatePlayerInfo';
            var SeriesGUID = getQueryStringValue('SeriesGUID');
        }
        $scope.editDataLoading = true;
        var data = 'SessionKey=' + SessionKey + '&SeriesGUID=' + getQueryStringValue('SeriesGUID') + '&RoundID=' + RoundID + '&MatchGUID=' + getQueryStringValue('MatchGUID') + '&' + $("form[name='edit_form']").serialize();
        $http.post(API_URL + UrlName, data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position] = response.Data;
                $('.modal-header .close').click();
                location.reload();
            } else {
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;
        });
    }

    /*edit data*/
    $scope.updatePlayerSalary = function (PlayerGUID, MatchGUID, key) {

        $scope.editDataLoading = true;
        var concatString = '';
        if (key.hasOwnProperty('T20Credits')) {
            concatString += '&T20Credits=' + key.T20Credits;
        }
        if (key.hasOwnProperty('T20iCredits')) {
            concatString += '&T20iCredits=' + key.T20iCredits;
        }
        if (key.hasOwnProperty('ODICredits')) {
            concatString += '&ODICredits=' + key.ODICredits;
        }
        if (key.hasOwnProperty('TestCredits')) {
            concatString += '&TestCredits=' + key.TestCredits;
        }
        if (key.hasOwnProperty('PlayerSalaryCredit')) {
            concatString += '&PlayerSalaryCredit=' + key.PlayerSalaryCredit;
        }

        var data = 'SessionKey=' + SessionKey + '&PlayerGUID=' + PlayerGUID + '&MatchGUID=' + MatchGUID + concatString;
        $http.post(API_URL + 'admin/matches/updatePlayerSalary', data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position] = response.Data;
                $('.modal-header .close').click();
            } else {
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;
        });
    }
});
