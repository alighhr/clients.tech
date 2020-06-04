app.controller('PageController', function ($scope, $http,$timeout){
    
    $scope.PointsCategory = 'Normal';  
    
    /*list append*/
    $scope.getList = function ()
    {
        if ($scope.data.listLoading || $scope.data.noRecords) return;
        $scope.data.listLoading = true;
        var data = 'pageNo&StatusID=1&PointsCategory='+$scope.PointsCategory;
        $http.post(API_URL+'sports/getPoints', data, contentType).then(function(response) {
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


    var timoutNow = 2000; // Timeout in 15 mins.
    var timeoutTimer;
    


     $scope.StartTimers = function(PointsTypeGUID,PointsMatchType,PointsValue) {
        timeoutTimer = setTimeout(function(){$scope.IdleTimeout(PointsTypeGUID,PointsMatchType,PointsValue)}, timoutNow);
    }
    // Reset timers.
    $scope.ResetTimers = function(PointsTypeGUID,PointsMatchType,PointsValue) {
        clearTimeout(timeoutTimer);
        $scope.StartTimers(PointsTypeGUID,PointsMatchType,PointsValue);
        $("#timeout").dialog('close');
    }
    $scope.IdleTimeout = function(PointsTypeGUID,PointsMatchType,PointsValue) {
         $scope.editData(PointsTypeGUID,PointsMatchType,PointsValue);
    }
    /*edit data*/
    $scope.editData = function (PointsTypeGUID,PointsMatchType,PointsValue)
    {
        $scope.editDataLoading = true;
        var data = 'SessionKey='+SessionKey+'&PointsTypeGUID='+PointsTypeGUID+'&PointsMatchType='+PointsMatchType+'&PointsValue='+PointsValue+'&PointsCategory='+$scope.PointsCategory;
        $http.post(API_URL+'admin/points/update', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */               
               // alertify.success(response.Message);
            }else{
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;          
        });
    }

    $scope.updateGeneralPoints = function(){
        console.log($('#generalPoint_form').serialize());
        $scope.editDataLoading = true;
        var data = 'PointsCategory='+$scope.PointsCategory+'&SessionKey='+SessionKey+'&'+$('#generalPoint_form').serialize();
        $http.post(API_URL+'admin/points/update', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */               
               alertify.success(response.Message);
            }else{
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;          
        });
    }
    $scope.updateBonusPoint = function(){
        // console.log($('#bonusPoint_form').serialize());return false;
        var data = 'PointsCategory='+$scope.PointsCategory+'&SessionKey='+SessionKey+'&'+$('#bonusPoint_form').serialize();
        $http.post(API_URL+'admin/points/update', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */               
               alertify.success(response.Message);
            }else{
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;          
        });
    }
    $scope.updateEconomyRate = function(){
        // console.log($('#economyRate_form').serialize());return false;
        var data = 'PointsCategory='+$scope.PointsCategory+'&SessionKey='+SessionKey+'&'+$('#economyRate_form').serialize();
        $http.post(API_URL+'admin/points/update', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */               
               alertify.success(response.Message);
            }else{
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;          
        });
    }
    $scope.updateStrikeRate = function(){
        // console.log($('#strikeRate_form').serialize());return false;
        var data = 'PointsCategory='+$scope.PointsCategory+'&SessionKey='+SessionKey+'&'+$('#strikeRate_form').serialize();
        $http.post(API_URL+'admin/points/update', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */               
               alertify.success(response.Message);
            }else{
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;          
        });
    }


}); 
