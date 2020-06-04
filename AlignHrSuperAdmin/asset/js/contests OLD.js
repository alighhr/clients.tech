app.controller('PageController', function ($scope, $http,$timeout, $rootScope){
    $scope.data.pageSize = 100;
    $scope.data.ParentCategoryGUID = ParentCategoryGUID;
    /*----------------*/
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

    /*list*/
    $scope.applyFilter = function ()
    {
        $scope.data = angular.copy($scope.orig); /*copy and reset from original scope*/
        $scope.getList();
    }

    /*list append*/
    $scope.getList = function ()
    {
        
        if(getQueryStringValue('MatchGUID')){
            var MatchGUID = getQueryStringValue('MatchGUID');
        }else{
            var MatchGUID = '';
        }

        if ($scope.data.listLoading || $scope.data.noRecords) return;
        $scope.data.listLoading = true;
        var data = 'SessionKey='+SessionKey+'&MatchGUID='+MatchGUID+'&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize+'&Params=GameType,GameTimeLive,AdminPercent,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,TeamNameLocal,TeamNameVisitor,Status,CustomizeWinning,ContestType,MatchStartDateTime,TotalJoined,TotalAmountReceived,TotalWinningAmount,CashBonusContribution,UserJoinLimit&Privacy=All&OrderBy=MatchStartDateTime&Sequence=DESC&'+$('#filterForm').serialize()+'&'+$('#filterForm1').serialize();
        $http.post(API_URL+'contest/getContests', data, contentType).then(function(response) {
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
    
    /*load add form*/
    $scope.loadFormAdd = function (Position, CategoryGUID)
    {
        $scope.templateURLAdd = PATH_TEMPLATE+module+'/add_form.htm?'+Math.random();
        $('#add_model').modal({show:true});
        $timeout(function(){            
           $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
       }, 200);


    }

    /*To get matches according to Series*/
    $scope.getMatches = function(SeriesGUID){
        $scope.MatchData = {};
        //&StatusID=1
        var data  = 'SeriesGUID='+SeriesGUID+'&Status=Pending&Params=MatchNo,MatchStartDateTime,TeamNameLocal,TeamNameVisitor&OrderBy=MatchStartDateTime&Sequence=DESC';
        $http.post(API_URL+'sports/getMatches', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200 && response.Data){ /* success case */
             $scope.MatchData =  response.Data.Records;
             $timeout(function(){
                $("select.chosen-select").chosen({ width: '100%',"disable_search_threshold": 8}).trigger("chosen:updated");
            }, 300);
         }
        });
    }

    /*load edit form*/

    $scope.loadFormEdit = function (Position, ContestGUID)
    {
                
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE+module+'/edit_form.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL+'contest/getContest', 'SessionKey='+SessionKey+'&ContestGUID='+ContestGUID+'&Params=GameType,GameTimeLive,AdminPercent,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,SeriesGUID,TeamNameLocal,TeamNameVisitor,SeriesName,CustomizeWinning,ContestType,CashBonusContribution,UserJoinLimit,ContestFormat,IsConfirm,ShowJoinedContest', contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data

                console.log(response.Data.CustomizeWinning);
                
                // $scope.custom.WinningAmount = parseFloat(response.Data.WinningAmount).toFixed(2);
                $scope.custom.WinningAmount = parseInt(response.Data.WinningAmount);
                $scope.custom.AdminPercent = parseInt(response.Data.AdminPercent);
                $scope.GameTimeLive = parseInt(response.Data.GameTimeLive);
                

                $scope.custom.choices = response.Data.CustomizeWinning;
                $scope.custom.NoOfWinners = response.Data.NoOfWinners;
                $scope.formData.CashBonusContribution = parseInt($scope.formData.CashBonusContribution);

                if($scope.custom.choices.length>0){
                    $scope.showField = true;
                }
                
                if (response.Data.CustomizeWinning) {
                    
                    if ($scope.numbers == '') {
                        for (var i = 1; i <= parseInt($scope.custom.NoOfWinners); i++) {
                            $scope.numbers.push(i);
                        }
                    } else {
                        for (var i = 1; i <= parseInt($scope.custom.NoOfWinners); i++) {
                            $scope.numbers.push(i)
                            $scope.numbers.splice(i);
                        }
                    }

                    angular.forEach($scope.custom.choices,function(value,key){
                        value.numbers = $scope.numbers;
                        value.percent = value.Percent;
                        value.amount  = value.WinningAmount;
                        value.From    = value.From;
                        value.To      = value.To;
                    });
                }
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
        $http.post(API_URL+'category/getCategory', 'SessionKey='+SessionKey+'&CategoryGUID='+CategoryGUID, contentType).then(function(response) {
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

    /*add data*/
    $scope.ContestFormat='Head to Head';
    $scope.IsPaid='Yes';
    $scope.addData = function ()
    {
        $scope.addDataLoading = true;
        
        // if(!$scope.contestPrizeParser($scope.custom.choices)){
            if($scope.contestPrizeParser($scope.custom.choices)[0].WinningAmount==0){
                var customWinings = JSON.stringify([{'From' : 1 ,'To':$scope.custom.NoOfWinners,'WinningAmount':$scope.custom.WinningAmount,'percent':100}]);
            }
            else{
                var customWinings   = JSON.stringify($scope.contestPrizeParser($scope.custom.choices));
            }

        /*}
        else{
            var customWinings   = '';
        }*/

        if($scope.ContestFormat=='Head to Head'){
            var ContestSize = 2;
        }
        var data = 'SessionKey='+SessionKey+'&Privacy=No&'+$("form[name='add_form']").serialize()+'&CustomizeWinning='+customWinings;
        
        $http.post(API_URL+'admin/contest/add', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */               
                alertify.success(response.Message);
                $scope.applyFilter();
                $('.modal-header .close').click();
            }else{
                alertify.error(response.Message);
            }
            $scope.addDataLoading = false;          
        });
    }


    /*edit data*/
    $scope.editData = function ()
    {
        $scope.editDataLoading = true;

        var inputData = {};

        inputData.ContestName           = $scope.formData.ContestName;
        inputData.IsPaid                = $scope.formData.IsPaid;
        inputData.WinningAmount         = $scope.custom.WinningAmount;
        inputData.CashBonusContribution = $scope.formData.CashBonusContribution;
        inputData.ContestFormat         = $scope.formData.ContestFormat;
        inputData.EntryFee              = $scope.formData.EntryFee;
        inputData.EntryType             = $scope.formData.EntryType;
        
        if(inputData.EntryType=='Multiple'){
        
            inputData.UserJoinLimit     = $scope.formData.UserJoinLimit;

        }
        
        inputData.ContestSize           = $scope.custom.ContestSize;
        inputData.ContestType           = $scope.formData.ContestType;
        inputData.IsConfirm             = $scope.formData.IsConfirm;
        inputData.ShowJoinedContest     = $scope.formData.ShowJoinedContest;
        inputData.ContestGUID           = $scope.formData.ContestGUID;
        inputData.NoOfWinners           = $scope.custom.NoOfWinners;
        inputData.CustomizeWinning      = JSON.stringify($scope.custom.choices);
        inputData.SessionKey            = SessionKey;
        inputData.Privacy               = $scope.formData.Privacy;
        inputData.GameType              = $scope.formData.GameType;
        inputData.GameTimeLive          = $scope.GameTimeLive;
        inputData.AdminPercent          = $scope.custom.AdminPercent;
                

        // console.log($(inputData).serialize()); return false;

        var data = 'SessionKey='+SessionKey+'&'+'&GameType='+inputData.GameType+'&GameTimeLive='+inputData.GameTimeLive+'&AdminPercent='+inputData.AdminPercent+'&ContestName='+inputData.ContestName+'&IsPaid='+inputData.IsPaid+'&WinningAmount='+inputData.WinningAmount+'&CashBonusContribution='+inputData.CashBonusContribution+'&ContestFormat='+inputData.ContestFormat+'&EntryFee='+inputData.EntryFee+'&EntryType='+inputData.EntryType+'&ContestSize='+inputData.ContestSize+'&ContestType='+inputData.ContestType+'&IsConfirm='+inputData.IsConfirm+'&ShowJoinedContest='+inputData.ShowJoinedContest+'&NoOfWinners='+inputData.NoOfWinners+'&ContestGUID='+inputData.ContestGUID+'&Privacy='+inputData.Privacy+'&CustomizeWinning='+inputData.CustomizeWinning;
        $http.post(API_URL+'admin/contest/edit', data, contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */               
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position] = response.Data;
                $('.modal-header .close').click();
            }else{
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;          
        });
    }


    /*--------------------------------------------------------------------------------------*/
    
    /*create contest calculations starts*/
        $scope.custom = {};
            $scope.clearForm = function() {
                $scope.showField = false;
                $scope.custom.choices = [];
                $scope.custom.choices.push({
                    row: 0,
                    From: 1,
                    To: 1,
                    amount: 0.00,
                    percent: 0
                });

                if ($scope.custom.NoOfWinners && $scope.contest_sizes) {
                    if ($scope.numbers == '') {
                        for (var i = 1; i <= parseInt($scope.custom.NoOfWinners); i++) {
                            $scope.numbers.push(i);
                        }
                    } else {
                        for (var i = 1; i <= parseInt($scope.custom.NoOfWinners); i++) {
                            $scope.numbers.push(i)
                            $scope.numbers.splice(i);
                        }
                    }
                }
            }
            $scope.totalPercentage = 0; // For Contest Creation Belives total Percentage is 0
            $scope.totalPersonCount = 0; // For Contest Creation Belives total Person count is 0
            $scope.currentSelectedMatch = 0; //To maintain current Selected Match Id
            $scope.AdminPercent = 10;
            /*------------calculate entryFee-------------------*/
            
            $scope.custom.ContestSize = 2;
            $scope.showSeries = true;
            $scope.contestError = false;
            $scope.contestErrorMsg = '';

            
            /*Function to Fetch Matches*/
            $scope.$watch('custom.ContestSize', function(newValue, oldValue) {
                
                // $scope.custom.NoOfWinners = '';
                if (newValue != oldValue) {
                    if (typeof newValue == 'undefined') {
                        $scope.EntryFee = 0.00;
                        return false;
                    }
                    
                    if (typeof $scope.custom.WinningAmount == 'undefined') {
                        $scope.winningamount_error = true;
                        return false;
                    }else{
                        $scope.winningamount_error = false;
                    }
                    /*if (newValue > 100) {
                        $scope.custom.ContestSize = 100;
                    }*/ 
                    if ($scope.custom.ContestSize.match(/^0[0-9].*$/)) {
                        $scope.custom.ContestSize = $scope.custom.ContestSize.replace(/^0+/, '');
                    }


                    if (parseInt($scope.custom.ContestSize) > 0) {
                        $scope.totalEntry = $scope.custom.WinningAmount / $scope.custom.ContestSize;
                        $scope.EntryFee = ($scope.totalEntry * $scope.AdminPercent / 100 + $scope.totalEntry).toFixed(2);
                    } else {
                        $scope.EntryFee = 0;
                    }
                    // if(isNaN($scope.EntryFee)){
                    //     $scope.EntryFee = 0;
                    // }
                }

            });

            $scope.$watch('custom.WinningAmount', function(newValue, oldValue) {
         
                if (newValue != oldValue) {
                    if (typeof newValue == 'undefined') {
                        $scope.EntryFee = 0.00;
                        return false;
                    }
                    /*if (newValue > 10000) {
                        $scope.custom.WinningAmount = 10000;
                    }*/
                    
                    if ($scope.custom.WinningAmount.match(/^0[0-9].*$/)) {
                        $scope.custom.WinningAmount = $scope.custom.WinningAmount.replace(/^0+/, '');
                    }
                    if (parseInt($scope.custom.ContestSize) > 0) {
                        $scope.totalEntry = $scope.custom.WinningAmount / $scope.custom.ContestSize;
                        $scope.EntryFee = ($scope.totalEntry * $scope.AdminPercent / 100 + $scope.totalEntry).toFixed(2);
                    } else {
                        $scope.EntryFee = 0;
                    }
                    $scope.clearForm();
                }
            },true);
            
            $scope.$watch('custom.AdminPercent', function(newValue, oldValue) {
                    $scope.AdminPercent = newValue;
                    if (newValue != oldValue) {
                    if (typeof newValue == 'undefined') {
                        $scope.EntryFee = 0.00;
                        return false;
                    }
                    /*if (newValue > 10000) {
                        $scope.custom.WinningAmount = 10000;
                    }*/
                    
                    if ($scope.custom.WinningAmount.match(/^0[0-9].*$/)) {
                        $scope.custom.WinningAmount = $scope.custom.WinningAmount.replace(/^0+/, '');
                    }
                    if (parseInt($scope.custom.ContestSize) > 0) {
                        $scope.totalEntry = $scope.custom.WinningAmount / $scope.custom.ContestSize;
                        $scope.EntryFee = ($scope.totalEntry * $scope.AdminPercent / 100 + $scope.totalEntry).toFixed(2);
                    } else {
                        $scope.EntryFee = 0;
                    }
                    $scope.clearForm();
                }
            },true); 

            /*------------calculate Percent and Amount-------------------*/
            $scope.custom.choices = [];
            $scope.amount = 0.00;

            $scope.changePercent = function(x) {
                /*Remove Error First*/
                $scope.calculation_error = false;
                $scope.calculation_error_msg = '';
                /*Remove Error First*/
                if (x != 0 && x > 0) {
                    let tempPersnCount1 = ($scope.custom.choices[x].To - $scope.custom.choices[x].From) + 1;
                    let tempPersnCount0 = ($scope.custom.choices[x - 1].To - $scope.custom.choices[x - 1].From) + 1;
                    if ((parseFloat(($scope.custom.WinningAmount * $scope.custom.choices[x].percent) / 100) / tempPersnCount1) > (parseFloat($scope.custom.WinningAmount * $scope.custom.choices[x - 1].percent / 100) / tempPersnCount0)) {
                        $scope.custom.choices[x].percent = '';
                        $scope.custom.choices[x].amount = parseFloat(0);
                        return false;
                    }
                }
                let total = 0;
                for (var i = 0; i < $scope.custom.choices.length; i++) {
                    total = total + parseFloat($scope.custom.choices[i].percent);
                }
                if (total > 100) {
                    $scope.custom.choices[x].percent = '';
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = 'Sum of percentage can not be more then 100%';
                    $scope.custom.choices[x].amount = parseFloat(0);
                    return false;
                }

                for (var i = 0; i < $scope.custom.choices.length; i++) {
                    if (i === x) {
                        let persenCount = 0;
                        if (parseInt($scope.custom.choices[i].To) == parseInt($scope.custom.choices[i].From)) {
                            persenCount = 1;
                        } else {
                            persenCount = ($scope.custom.choices[i].To - $scope.custom.choices[i].From) + 1;
                        }
                        $scope.winnersAmount = $scope.custom.WinningAmount * $scope.custom.choices[i].percent / 100;
                        let amount = ($scope.winnersAmount / persenCount).toFixed(2);
                        let fractionNumber = amount.split('.');
                        amount = fractionNumber[0] + '.' + fractionNumber[1].slice(0, 1);
                        $scope.custom.choices[i].amount = amount;
                        // $scope.choices[i].percent = $scope.choices[i].percent.toString();
                        $scope.custom.choices[i].percent = $scope.custom.choices[i].percent.toString();
                        
                        if ($scope.custom.choices[i].percent.match(/^0[0-9].*$/)) {
                            $scope.custom.WinningAmount = $scope.custom.WinningAmount.replace(/^0+/, '');
                        }
                        $scope.custom.choices[i].percent = $scope.custom.choices[i].percent.replace(/^0+/, '');
                    }
                }
            }
            $scope.customizeMultieams = function() {
                $scope.calculation_error = false;
                $scope.calculation_error_msg = '';
                if ($scope.custom.ContestSize == null || $scope.custom.ContestSize < 3) {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "Contest size must be greater then 2!";
                    $scope.EntryType = 'Single';
                    return false;
                }
            }
            $scope.customizeWin = function() {
                $scope.calculation_error = false;
                $scope.calculation_error_msg = '';
                if ($scope.winnings == "") {
                    $scope.showField = false;
                    $scope.custom.NoOfWinners = '';
                    return false;
                }
                if ($scope.custom.WinningAmount == null || $scope.custom.WinningAmount < 1) {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "Please enter total winning amount!";
                    $scope.winnings = false;
                    return false;
                }
                if ($scope.custom.ContestSize == null || $scope.custom.ContestSize < 2) {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "Contest size must be greater or equals to 2";
                    $scope.winnings = false;
                    return false;
                }
            }
            $scope.changeWinAmount = function() {
                $scope.calculation_error = false;
                $scope.calculation_error_msg = '';
                if ($scope.custom.WinningAmount == null || $scope.custom.WinningAmount < 1) {
                    $scope.winnings = false;
                }
            }
            $scope.changeWinners = function() {
                $scope.EntryType = 'Single';
                $scope.calculation_error = false;
                $scope.calculation_error_msg = '';
                if ($scope.custom.ContestSize == null || $scope.custom.ContestSize < 2) {
                    $scope.winnings = false;
                }
                $scope.showField = false;
                $scope.contestError = false;
                $scope.clearForm();
            }
            /*---------------add and remove Field-------------------*/
            $scope.From = 1;
            var x = 0;
            $scope.custom.choices.push({
                row: x,
                From: 1,
                To: 1,
                amount: 0.00,
                percent: 0
            });
            $scope.addField = function() {
                x = x + 1;
                $scope.numbers1 = [];

                var select2_value = "";
                $scope.percent_error = false;
                var lastIndex = $scope.custom.choices.length - 1;
                if ($scope.custom.choices[lastIndex].percent == 0) {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "Last percentage is blank!";
                    return false;
                }
                if ($scope.totalPercentage == 100) {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "Amount has been distributed already!";
                    return false;
                }
                for (var k = 0; $scope.custom.choices.length > k; k++) {

                    if (k == $scope.custom.choices.length - 1) {
                        if ($scope.custom.choices[k].percent) {
                            select2_value = ($scope.custom.choices[k].To + 1);
                            for (var j = ($scope.custom.choices[k].To + 1); j <= parseInt($scope.custom.NoOfWinners); j++) {
                                $scope.numbers1.push(j)
                            }
                        } else {
                            $scope.percent_error = true;
                            return false;
                        }
                    }
                }
                if (select2_value <= parseInt($scope.custom.NoOfWinners)) {
                    $scope.custom.choices.push({
                        row: x,
                        From: select2_value,
                        To: select2_value,
                        numbers: $scope.numbers1,
                        percent: 0,
                        amount: 0.00
                    });
                } else {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "All Winners has been selected already!";
                }

            }
            $scope.$watch('$scope.custom.choices', function(n, o, scope) {
                var totalPercentagetemp = 0;
                var isRemoval = false;
                var removalIndex = 0;
                /*Code to track Changes in top rows and if any remove below rows*/
                if ($scope.custom.choices.length > 1) {
                    for (var counter = 0; counter < $scope.custom.choices.length; counter++) {
                        if (counter < o.length - 1 && (o[counter].amount != n[counter].amount || o[counter].To != o[counter].To)) {
                            isRemoval = true;
                            removalIndex = counter + 1;
                        }
                    }
                }
                if (isRemoval == true) {
                    var numberOfRows = $scope.custom.choices.length;
                    if (removalIndex <= numberOfRows - 1) {
                        var removeElementCount = numberOfRows - removalIndex;
                        $scope.custom.choices.splice(removalIndex, removeElementCount);
                    }

                }
                /*Code to track Changes in top rows and if any remove below rows*/

                /*Total Percentage Count and Handler*/
                for (var counter = 0; counter < $scope.custom.choices.length; counter++) {
                    totalPercentagetemp += parseFloat($scope.custom.choices[counter].percent);
                }
                if (totalPercentagetemp > 100) {
                    $scope.custom.choices = 0;
                    return false;
                }
                $scope.totalPercentage = totalPercentagetemp;
                /*Total Percentage count and handler*/

                /*Total Person count and Handler*/
                let personCount = 0;
                for (var i = 0; i < $scope.custom.choices.length; i++) {
                    if ($scope.custom.choices[i].From == $scope.custom.choices[i].To) {
                        personCount++;
                    } else {
                        personCount += parseInt(($scope.custom.choices[i].To - $scope.custom.choices[i].From) + 1);
                    }
                }
                $scope.totalPersonCount = personCount;
                /*Total Person Count and Handler*/
            }, true);

            /*Handle Contest Size*/
            $scope.$watch('NoOfWinners', function(newValue, oldValue) {
                if (parseInt(newValue) > parseInt($scope.custom.ContestSize)) {
                    $scope.custom.NoOfWinners = oldValue;
                }
            });



            $scope.removeField = function(index) {
                if (index == 0) {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "You can not remove first row.";
                    return false;
                }
                if (index < $scope.custom.choices.length - 1) {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "While having row beneath you can not delete current row.";
                    return false;
                }
                if (index >= 0) {
                    $scope.custom.choices.splice(index, 1);
                    $scope.calculation_error = false;
                    $scope.calculation_error_msg = '';
                }
            }



            /*------------ show  and hide form-------------------*/
            $scope.showField = false;
            $scope.numbers = [];
            $scope.Showform = function() {
           
                if ($scope.custom.NoOfWinners == '' || $scope.custom.NoOfWinners == '0') {
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "Please enter proper winner count!";
                    return false;
                }

                if ($scope.custom.NoOfWinners && $scope.custom.ContestSize) {
                    if ($scope.numbers == '') {
                        for (var i = 1; i <= parseInt($scope.custom.NoOfWinners); i++) {
                            $scope.numbers.push(i);
                        }
                    } else {
                        for (var i = 1; i <= parseInt($scope.custom.NoOfWinners); i++) {
                            $scope.numbers.push(i)
                            $scope.numbers.splice(i);
                        }
                    }
                    $scope.custom.choices[0].numbers = $scope.numbers;
                    if (parseInt($scope.custom.ContestSize) >= parseInt($scope.custom.NoOfWinners)) {
                        $scope.error = false;
                        $scope.showField = true;
                    } else {
                        $scope.error = true;
                        $scope.showField = false;
                        return false;
                    }
                } else {
                    $scope.error = true;
                    $scope.showField = false;
                    $scope.calculation_error = true;
                    $scope.calculation_error_msg = "Please enter proper winner count!";
                    return false;
                }
            }
            $scope.contestPrizeParser = function($choices)
            { 
                let response = [];
                let valueArray = [];
                for(var $i=0;$i<$scope.custom.choices.length;$i++)
                {
                    valueArray.push({'From' : $scope.custom.choices[$i].From,'To' : $scope.custom.choices[$i].To,'Percent' : $scope.custom.choices[$i].percent,'WinningAmount':$scope.custom.choices[$i].amount});
                }
                response = valueArray;
                return response;
            }


            /*create contest calculations ends*/

    /*--------------------------------------------------------------------------------------*/

        /*load edit form*/

    $scope.loadFormStatus = function (Position, ContestGUID)
    {
                
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE+module+'/updateStatus_form.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL+'contest/getContest', 'SessionKey='+SessionKey+'&ContestGUID='+ContestGUID+'&Params=ContestName,ContestType,Status,StatusID', contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                
                $('#status_model').modal({show:true});
                
                $timeout(function(){            
                   
                   $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
               }, 200);
            }
        });
    }

    $scope.loadContestJoinedUser = function(Position,ContestGUID)
    {
                
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE+module+'/joinedContest_form.htm?'+Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL+'contest/getJoinedContestsUsers', 'SessionKey='+SessionKey+'&ContestGUID='+ContestGUID+'&Params=UserTeamName,TotalPoints,UserWinningAmount,FirstName,Username,UserGUID,UserTeamPlayers,UserTeamID,UserRank', contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data;
                console.log($scope.contestData)
                $('#contestJoinedUsers_model').modal({show:true});
                
                $timeout(function(){            
                   
                   $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
               }, 200);
            }
        });

        $http.post(API_URL+'contest/getContest', 'SessionKey='+SessionKey+'&ContestGUID='+ContestGUID+'&Params=Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,SeriesID,MatchID,SeriesGUID,TeamNameLocal,TeamNameVisitor,SeriesName,CustomizeWinning,ContestType,CashBonusContribution,UserJoinLimit,ContestFormat,IsConfirm,ShowJoinedContest,TotalJoined', contentType).then(function(response) {
            var response = response.data;
            if(response.ResponseCode==200){ /* success case */
                $scope.data.pageLoading = false;
                $scope.contestData = response.Data;
                console.log($scope.contestData)
                $('#contestJoinedUsers_model').modal({show:true});
                
                $timeout(function(){            
                   
                   $(".chosen-select").chosen({ width: '100%',"disable_search_threshold": 8 ,"placeholder_text_multiple": "Please Select",}).trigger("chosen:updated");
               }, 200);
            }
        });
        $('.table').removeProperty('min-height');
    }

    /*edit status*/
    $scope.editStatus = function (Status,contestGUID)
    {
        if(Status=='Cancelled'){
            var req = 'SessionKey='+SessionKey+'&ContestGUID='+contestGUID;
            $http.post(API_URL+'admin/contest/cancel', req, contentType).then(function(response) {
                var response = response.data;
                if(response.ResponseCode==200){ /* success case */               
                    $scope.editDataLoading = true;
                    var data = 'SessionKey='+SessionKey+'&'+$("form[name='update_form']").serialize();
                    $http.post(API_URL+'admin/contest/changeStatus', data, contentType).then(function(response) {
                        var response = response.data;
                        if(response.ResponseCode==200){ /* success case */               
                            alertify.success(response.Message);
                            $scope.data.dataList[$scope.data.Position] = response.Data;
                            $('.modal-header .close').click();

                            setTimeout(function() {
                                window.location.reload();
                            }, 300);

                        }else{
                            alertify.error(response.Message);
                        }
                        $scope.editDataLoading = false;          
                    });                    
                }
            });  
        }else{
            $scope.editDataLoading = true;
            var data = 'SessionKey='+SessionKey+'&'+$("form[name='update_form']").serialize();
            $http.post(API_URL+'admin/contest/changeStatus', data, contentType).then(function(response) {
                var response = response.data;
                if(response.ResponseCode==200){ /* success case */               
                    alertify.success(response.Message);
                    $scope.data.dataList[$scope.data.Position] = response.Data;
                    $('.modal-header .close').click();
                }else{
                    alertify.error(response.Message);
                }
                $scope.editDataLoading = false;          
            });
        }
    }
    
    
    /* set time*/
    $scope.getTime = function(selectID){
        //$scope.GameTimeLive = 0;
        if(selectID == "Safe"){
            $scope.GameTimeLive = 2;
        }else if(selectID == "Advance"){
            $scope.GameTimeLive = 40;
        }
    }


}); 

/* sortable - ends */