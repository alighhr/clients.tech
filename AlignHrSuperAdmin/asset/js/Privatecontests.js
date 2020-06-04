app.controller('PageController', function ($scope, $http,$timeout){
    $scope.data.pageSize = 15;
    /*----------------*/
     /*list*/

     $scope.getFilterData = function ()
    {
        var data = 'SessionKey='+SessionKey+'&Params=SeriesName,SeriesGUID&StatusID=2&'+$('#filterPanel form').serialize();

        $http.post(API_URL+'admin/matches/getFilterData', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200 && response.Data){ 
            /* success case */
             $scope.filterData =  response.Data;
             $timeout(function(){
                $("select.chosen-select").chosen({ width: '100%',"disable_search_threshold": 8}).trigger("chosen:updated");
            }, 300);          
         }
     });
    }

    $scope.applyFilter = function() {
        $scope.data = angular.copy($scope.orig); /*copy and reset from original scope*/
        $scope.getList();
    }

    $scope.getUserInfo = function(){
        $scope.userData = {};
        var UserGUID = getQueryStringValue('UserGUID');
        $http.post(API_URL + 'users/getProfile', 'SessionKey=' + SessionKey + '&UserGUID=' + UserGUID + '&Params=Status,ProfilePic,MediaPAN,MediaBANK', contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.userData = response.Data;
            }
        });
    }
    /*list append*/
    $scope.getList = function ()
    {
        $scope.getUserInfo();
        if ($scope.data.listLoading || $scope.data.noRecords) return;
        $scope.data.listLoading = true;
        var data = 'SessionKey='+SessionKey+'&Privacy=Yes&UserGUID='+getQueryStringValue('UserGUID')+'&Params=GameType,AdminPercent,TotalJoined,MatchStartDateTime,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,Status,TeamNameLocal,TeamNameVisitor,CustomizeWinning&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize + '&OrderBy=EntryDate&Sequence=DESC&' + $('#filterForm1').serialize() +'&' + $('#filterForm').serialize();
        $http.post(API_URL+'admin/contest/getPrivateContest', data, contentType).then(function(response) {
        var response = response.data;
        console.log(response);
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
    });
    }

    $scope.loadContestJoinedUser = function (Position, ContestGUID)
    {

        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE + 'contests/joinedContest_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'contest/getJoinedContestsUsers', 'SessionKey=' + SessionKey + '&ContestGUID=' + ContestGUID + '&Params=UserTeamName,TotalPoints,UserWinningAmount,Email,PhoneNumber,FirstName,Username,UserGUID,UserTeamPlayers,UserTeamID,UserRank', contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data;
                console.log($scope.contestData)
                $('#contestJoinedUsers_model').modal({show: true});

                $timeout(function () {

                    $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
                }, 200);
            }
        });

        $http.post(API_URL + 'contest/getContest', 'SessionKey=' + SessionKey + '&ContestGUID=' + ContestGUID + '&Params=Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,SeriesGUID,TeamNameLocal,TeamNameVisitor,SeriesName,CustomizeWinning,ContestType,CashBonusContribution,UserJoinLimit,ContestFormat,IsConfirm,ShowJoinedContest,TotalJoined', contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.pageLoading = false;
                $scope.contestData = response.Data;
                console.log($scope.contestData)
                $('#contestJoinedUsers_model').modal({show: true});

                $timeout(function () {

                    $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
                }, 200);
            }
        });
        $('.table').removeProperty('min-height');
    }

    $scope.loadFormStatus = function (Position, ContestGUID)
    {

        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE + 'contests/updateStatus_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'contest/getContest', 'SessionKey=' + SessionKey + '&ContestGUID=' + ContestGUID + '&Params=ContestName,ContestType,Status,StatusID', contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data

                $('#status_model').modal({show: true});
                $timeout(function () {
                    $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
                }, 200);
            }
        });
    }

    /*To get matches according to Series*/
    $scope.getMatches = function(SeriesGUID,Status){
        $scope.MatchData = {};
        //&StatusID=1
        var data  = 'SeriesGUID='+SeriesGUID+'&Params=MatchNo,MatchStartDateTime,TeamNameLocal,TeamNameVisitor&OrderBy=MatchStartDateTime&Sequence=ASC&Status='+Status;
        $http.post(API_URL+'sports/getMatches', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode == 200 && response.Data){ /* success case */
             $scope.MatchData =  response.Data.Records;
             $timeout(function(){
                $('.matchSelect2').select2();
                $("select.chosen-select").chosen({ width: '100%',"disable_search_threshold": 8}).trigger("chosen:updated");
            }, 400);
         }
        });
    }

    /*edit status*/
    $scope.editStatus = function (Status, contestGUID)
    {

        if (Status == 'Cancelled') {
            var req = 'SessionKey=' + SessionKey + '&ContestGUID=' + contestGUID;
            $http.post(API_URL + 'admin/contest/cancel', req, contentType).then(function (response) {
                var response = response.data;
                if (response.ResponseCode == 200) { /* success case */
                    $scope.editDataLoading = true;
                    var data = 'SessionKey=' + SessionKey + '&' + $("form[name='update_form']").serialize();
                    $http.post(API_URL + 'admin/contest/changeStatus', data, contentType).then(function (response) {
                        var response = response.data;
                        if (response.ResponseCode == 200) { /* success case */
                            alertify.success(response.Message);
                            $scope.data.dataList[$scope.data.Position] = response.Data;
                            $('.modal-header .close').click();
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);

                        } else {
                            alertify.error(response.Message);
                        }
                        $scope.editDataLoading = false;
                    });
                }
            });
        } else {
            $scope.editDataLoading = true;
            var data = 'SessionKey=' + SessionKey + '&ContestGUID=' + contestGUID + '&Status=' + Status;
            $http.post(API_URL + 'admin/contest/changeStatus', data, contentType).then(function (response) {
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
    }



}); 
