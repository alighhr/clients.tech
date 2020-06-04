app.controller('PageController', function ($scope, $http,$timeout){
    $scope.data.pageSize = 15;
    // $scope.data.ParentCategoryGUID = ParentCategoryGUID;
    /*----------------*/
    // $scope.getFilterData = function ()
    // {
    //     var data = 'SessionKey='+SessionKey+'&SeriesGUID='+SeriesGUID+'&'+$('#filterPanel form').serialize();
    //     $http.post(API_URL+'admin/series/getFilterData', data, contentType).then(function(response) {
    //         var response = response.data;
    //         if(response.ResponseCode==200 && response.Data){ /* success case */
    //          $scope.filterData =  response.Data;
    //          $timeout(function(){
    //             $("select.chosen-select").chosen({ width: '100%',"disable_search_threshold": 8}).trigger("chosen:updated");
    //         }, 300);          
    //      }
    //  });
    // }


    /*list*/
    $scope.applyFilter = function ()
    {
        $scope.data = angular.copy($scope.orig); /*copy and reset from original scope*/
        $scope.getList();
    }


    /*list append*/
    $scope.getList = function ()
    {
        if ($scope.data.listLoading || $scope.data.noRecords) return;
        $scope.data.listLoading = true;
        var data = 'SessionKey='+SessionKey+'&OrderByToday=Yes&Params=SeriesName,SeriesGUID,StatusID,Status,SeriesStartDate,SeriesEndDate,AuctionDraftIsPlayed,TotalMatches&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize+'&'+$('#filterForm').serialize()+'&'+$('#filterForm1').serialize();
        $http.post(API_URL_FOOTBALL+'sports/getSeries', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200 && response.Data.Records){ /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                 $scope.data.dataList.push(response.Data.Records[i]);
                }
             $scope.data.pageNo++;               
            }else{
                $scope.data.noRecords = true;
            }
            $scope.data.listLoading = false;
        });
    }
    
        $scope.loadRoundList = function ()
    {
        if (getQueryStringValue('SeriesGUID')) {
            var SeriesGUID = getQueryStringValue('SeriesGUID');
        } else {
            var SeriesGUID = '';
        }
        $scope.data.pageLoading = true;
        $http.post(API_URL_FOOTBALL + 'admin/series/getRounds', 'SessionKey=' + SessionKey + '&SeriesGUID=' + SeriesGUID + '&Params=SeriesID,RoundID,DraftUserLimit,DraftTeamPlayerLimit,DraftPlayerSelectionCriteria,StatusID,SeriesType,RoundFormat,SeriesIDLive,AuctionDraftIsPlayed,SeriesStartDate,SeriesEndDate,SeriesStartDateUTC,SeriesEndDateUTC,TotalMatches,SeriesMatchStartDate,Status,AuctionDraftStatus', contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.pageLoading = false;
                $scope.RoundformData = response.Data;
                $timeout(function () {
                    $('.datepicker').datepicker({
                        format: 'yyyy-mm-dd',
                        autoclose: true,
                        todayHighlight: true
                    });
                    $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
                }, 200);
            }
        });
        $http.post(API_URL_FOOTBALL+'admin/series/getSeriesDetails','SeriesGUID='+SeriesGUID+'&Params=DraftUserLimit,SeriesName,SeriesStartDate,SeriesEndDate,SeriesGUID,AuctionDraftIsPlayed,Status&SessionKey='+SessionKey, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.SeriesData = response.Data
                $timeout(function(){            
                   $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
               }, 200);
            }
        });
        $('.table').removeProp('min-height');
    }


    /*load add form*/
    $scope.loadFormAdd = function (Position, CategoryGUID)
    {
        $scope.templateURLAdd = PATH_TEMPLATE+module+'/add_form.htm?'+Math.random();
        $('#add_model').modal({show:true});
        $timeout(function(){            
           $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
       }, 200);
    }



    /*load edit form*/
    $scope.loadFormEdit = function (Position, SeriesGUID)
    {
      
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE+module+'/edit_form.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL_FOOTBALL+'admin/series/getSeriesDetails','SeriesGUID='+SeriesGUID+'&Params=DraftUserLimit,DraftTeamPlayerLimit,SeriesName,SeriesGUID,AuctionDraftIsPlayed,Status&SessionKey='+SessionKey, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#edit_model').modal({show:true});
                $timeout(function(){            
                   $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
               }, 200);
            }
        });
    }

    /*load delete form*/
    $scope.loadFormDelete = function (Position, CategoryGUID)
    {
        $scope.data.Position = Position;
        $scope.templateURLDelete = PATH_TEMPLATE+module+'/delete_form.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL_FOOTBALL+'category/getCategory', 'SessionKey='+SessionKey+'&CategoryGUID='+CategoryGUID, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                $('#delete_model').modal({show:true});
                $timeout(function(){            
                   $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
               }, 200);
            }
        });
    }

     /*edit data*/
    $scope.editData = function() {
        $scope.editDataLoading = true;
        var data = 'SessionKey=' + SessionKey + '&' + $('#edit_form').serialize();
        $http.post(API_URL_FOOTBALL + 'admin/series/changeStatus', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position].Status = response.Data.Status;
            } else {
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;
        });
    }

    /*edit data*/
    $scope.changeStatus = function(RoundID,SeriesStartDateUTC,SeriesEndDateUTC,AuctionDraftIsPlayed,SeriesType,SeriesID) {        
        var data = 'SessionKey=' + SessionKey +'&RoundID='+RoundID+'&SeriesID='+SeriesID+'&SeriesType='+SeriesType+'&RoundStartDate='+SeriesStartDateUTC+'&RoundEndDate='+SeriesEndDateUTC+ '&AuctionDraftIsPlayed=' +AuctionDraftIsPlayed;
        $http.post(API_URL_FOOTBALL + 'admin/series/updateRounds', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
            } else {
                alertify.error(response.Message);
            }
        });
    }

}); 


