app.controller('PageController', function ($scope, $http, $timeout, $rootScope) {
    $scope.data.pageSize = 100;
    $scope.data.ParentCategoryGUID = ParentCategoryGUID;
    /*----------------*/
    $scope.getFilterData = function ()
    {
        var data = 'SessionKey=' + SessionKey + '&Params=SeriesName,SeriesGUID&StatusID=2&' + $('#filterPanel form').serialize();

        $http.post(API_URL + 'admin/matches/getFilterData', data, contentType).then(function (response) {
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
        $scope.getList('filters');
    }

    /*list append*/
    $scope.getList = function (Type = '')
    {

        /*var EntryFee = $('#EntryFee').val();
         var ContestSize = $('#ContestSize').val();
         
         var obj = {"EntryFee" :EntryFee, "ContestSize":ContestSize};
         var myJSON = (!jQuery.isEmptyObject(obj)) ? JSON.stringify(obj) : '';*/

        if ($scope.data.listLoading || $scope.data.noRecords)
            return;
        $scope.data.listLoading = true;
        var data = 'SessionKey=' + SessionKey + '&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize + '&Params=Privacy,AdminPercent,IsPaid,GameType,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,CustomizeWinning,ContestType,TotalJoined,IsConfirm,TotalAmountReceived,TotalWinningAmount,CashBonusContribution,UserJoinLimit&Privacy=All&OrderBy=PreContestID&Sequence=DESC&' + $('#filterForm').serialize();
        data += '&' + $('#filterForm1').serialize();
        $http.post(API_URL + 'admin/preContest/getPreContest', data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200 && response.Data.Records.length > 0) {
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

    /*load add form*/
    $scope.loadFormAdd = function (Position, CategoryGUID)
    {
        $scope.clearForm();
        $scope.EntryFee.fee = 0;
        $scope.custom.WinningAmount = 0;
        $scope.custom.CashBonusContribution = 0;
        $scope.IsAutoCreate = "";
        $scope.unfilledWinningPercent = "";
        $scope.templateURLAdd = PATH_TEMPLATE + module + '/add_form.htm?' + Math.random();
        $('#add_model').modal({show: true});
        $timeout(function () {
            $(".chosen-select").chosen({width: '100%', "disable_search_threshold": 8, "placeholder_text_multiple": "Please Select", }).trigger("chosen:updated");
        }, 200);


    }


    $("#filter_model").on('show.bs.modal', function () {
        $('form#filterForm1 span.select2-container').remove();
    });

    /*load edit form*/

    $scope.loadFormEdit = function (Position, PreContestID)
    {
        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE + module + '/edit_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'admin/preContest/getPreContest', 'SessionKey=' + SessionKey + '&PreContestID=' + PreContestID + '&Params=WinningRatio,WinUpTo,GameType,UnfilledWinningPercent,SmartPool,IsVirtualUserJoined,VirtualUserJoinedPercentage,AdminPercent,Privacy,IsPaid,WinningAmount,ContestSize,EntryFee,NoOfWinners,EntryType,CustomizeWinning,ContestType,CashBonusContribution,UserJoinLimit,ContestFormat,IsConfirm,ShowJoinedContest,IsAutoCreate', contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data.Records[0]
                $scope.SmartCustom.SmartPool = $scope.formData.SmartPool;


                $scope.custom.WinningAmount = parseInt(response.Data.Records[0].WinningAmount);
                $scope.remainingPercentage = 100;
                $scope.custom.AdminPercent = parseInt(response.Data.Records[0].AdminPercent);
                $scope.IsAutoCreate = response.Data.Records[0].IsAutoCreate;

                $scope.custom.NoOfWinners = response.Data.Records[0].NoOfWinners;
                $scope.custom.ContestSize = response.Data.Records[0].ContestSize;
                if ($scope.formData.CashBonusContribution) {
                    $scope.custom.CashBonusContribution = parseInt($scope.formData.CashBonusContribution);
                } else {
                    $scope.custom.CashBonusContribution = 0;
                }


                $scope.custom.choices = response.Data.Records[0].CustomizeWinning;
                if (response.Data.Records[0].CustomizeWinning.length > 0) {
                    $scope.showField = true;
                }

                if (response.Data.Records[0].CustomizeWinning) {

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
                    if ($scope.formData.SmartPool == 'No') {
                        angular.forEach($scope.custom.choices, function (value, key) {
                            value.numbers = $scope.numbers;
                            value.percent = value.Percent;
                            value.amount = value.WinningAmount;
                            value.From = parseInt(value.From);
                            value.To = parseInt(value.To);
                            $scope.remainingPercentage = $scope.remainingPercentage - value.percent;
                        });
                    } else {
                        angular.forEach($scope.custom.choices, function (value, key) {
                            value.numbers = $scope.numbers;
                            value.From = parseInt(value.From);
                            value.To = parseInt(value.To);
                            value.CategoryGUID = value.CategoryGUID;
                            value.ProductName = value.ProductName;
                            value.ProductUrl = value.ProductUrl;
                        });

                        $scope.getCategoriesList();
                    }
                }
                $('#edit_model').modal({show: true});
                $scope.editForm = true;

                $timeout(function () {
                    $scope.EntryFee.fee = $scope.formData.EntryFee;
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

    /*Delete contest*/
    $scope.deleteContest = function (Position, PreContestID)
    {

        if (confirm('Are you sure, want to delete this Contest ?')) {
            $scope.addDataLoading = true;
            $http.post(API_URL + 'admin/preContest/delete', 'SessionKey=' + SessionKey + '&PreContestID=' + PreContestID, contentType).then(function (response) {
                var response = response.data;
                if (response.ResponseCode == 200) { /* success case */
                    window.location.reload();
                    alertify.success(response.Message);
                } else {
                    alertify.error(response.Message);
                }
                $scope.addDataLoading = false;
            });
        }
    }

    /*Delete all contest*/
    $scope.deleteAllUpcomingContest = function (Position, PreContestID)
    {

        if (confirm('Are you sure, want to delete upcoming Pre contest ?')) {
            $scope.addDataLoading = true;
            $http.post(API_URL + 'admin/preContest/deleteUpcomingPreContest', 'SessionKey=' + SessionKey + '&PreContestID=' + PreContestID, contentType).then(function (response) {
                var response = response.data;
                if (response.ResponseCode == 200) { /* success case */
                    alertify.success(response.Message);
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);

                } else {
                    alertify.error(response.Message);
                }
                $scope.addDataLoading = false;
            });
        }
    }

    /*add data*/
    $scope.ContestFormat = 'Head to Head';
    $scope.UnfilledWinningPercent = 'Fixed';
    $scope.IsPaid = 'Yes';
    $scope.SmartCustom = {};
    $scope.SmartCustom.SmartPool = 'No';
    $scope.SmartPoolField = true;
    $scope.showContestSize = true;
    $scope.CashBonusContribution = {};
    $scope.CashBonusContribution.Contribution = 0;
    $scope.EntryFee = {};
    $scope.EntryFee.fee = 0;
    $scope.WinningRatio = 50;
    $scope.WinUpTo = 2;


    $scope.changeUnfilledWinningPercent = function (UnfilledWinningPercent) {
        $scope.showContestSize = true;
        if (UnfilledWinningPercent == "GuranteedPool")
        {
            $scope.showContestSize = false;
            $scope.SmartPoolField = false;
            $scope.UnfilledWinningPercent = UnfilledWinningPercent;
        } else {
            $scope.showContestSize = true;
            $scope.SmartPoolField = true;
            $scope.UnfilledWinningPercent = 'Fixed';
        }
    }

    /*list append*/
    $scope.getCategoriesList = function ()
    {
        $scope.data.categoryList = [];
        var data = 'SessionKey=' + SessionKey;
        $http.post(API_URL + 'category/getCategories', data, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                    $scope.data.categoryList.push(response.Data.Records[i]);
                }
            } else {
                $scope.data.noRecords = true;
            }
        });
    }

    $scope.getCategoryImage = function (CategoryGUID, index)
    {
        var CategoriesLength = $scope.data.categoryList.length;
        for (var i = 0; i < CategoriesLength; i++) {
            if ($scope.data.categoryList[i].CategoryGUID == CategoryGUID) {
                var MediaUrl = $scope.data.categoryList[i].Media.Records[0].MediaThumbURL;
                $('#ProductImage' + index).prop("src", MediaUrl);
            }
        }
    }
    $scope.Type = {};
    $scope.Type.EntryType = '';
    $scope.addDataPreContest = function ()
    {
        $scope.addDataLoading = true;
        if ($scope.contestPrizeParser($scope.custom.choices)[0].WinningAmount == 0 && $scope.SmartCustom.SmartPool == 'No') {
            var customWinings = JSON.stringify([{'From': 1, 'To': $scope.custom.NoOfWinners, 'WinningAmount': $scope.custom.WinningAmount, 'percent': 100}]);
        } else {
            var customWinings = JSON.stringify($scope.contestPrizeParser($scope.custom.choices));
        }
        if ($scope.ContestFormat == 'Head to Head') {
            var ContestSize = 2;
            $scope.custom.ContestSize = 2;
        }

        if ($scope.UnfilledWinningPercent == 'GuranteedPool') {
            $scope.custom.WinningAmount = 0;
            $scope.custom.ContestSize = 0;
        }

        if ($scope.SmartCustom.SmartPool == 'Yes') {
            $scope.custom.WinningAmount = 0;
            $scope.AdminPercent = 0;
        }

        if ($scope.UnfilledWinningPercent == 'Fixed' && $scope.custom.ContestSize == 0) {
            $scope.custom.ContestSize = 2;
        }

        var $data = {};
        $data.SessionKey = SessionKey;
        $data.ContestName = $('input[name="ContestName"]').val();
        $data.ContestFormat = $('select[name="ContestFormat"]').val();
        $data.UnfilledWinningPercent = $('select[name="UnfilledWinningPercent"]').val();
        $data.SmartPool = $('select[name="SmartPool"]').val();
        $data.WinningRatio = $('input[name="WinningRatio"]').val();
        $data.WinUpTo = $('input[name="WinUpTo"]').val();
        $data.AdminPercent = $('input[name="AdminPercent"]').val();
        $data.ContestType = $('select[name="ContestType"]').val();
        $data.Privacy = 'No';
        $data.IsPaid = $('select[name="IsPaid"]').val();
        $data.IsConfirm = $('select[name="IsConfirm"]').val();
        $data.IsAutoCreate = $('select[name="IsAutoCreate"]').val();
        $data.ShowJoinedContest = $('select[name="ShowJoinedContest"]').val();
        $data.WinningAmount = $('input[name="WinningAmount"]').val();
        $data.ContestSize = $('input[name="ContestSize"]').val();
        $data.EntryFee = $('input[name="EntryFee"]').val();
        $data.NoOfWinners = $('input[name="NoOfWinners"]').val();
        $data.EntryType = $('select[name="EntryType"]').val();
        $data.UserJoinLimit = $('input[name="UserJoinLimit"]').val();
        $data.CashBonusContribution = $('input[name="CashBonusContribution"]').val();
        $data.IsVirtualUserJoined = $('select[name="IsVirtualUserJoined"]').val();
        $data.VirtualUserJoinedPercentage = $('input[name="VirtualUserJoinedPercentage"]').val();
        //$data.CustomizeWinning = JSON.parse(customWinings);
        // var data = 'SessionKey=' + SessionKey + '&Privacy=No&' + $("form[name='add_form']").serialize() + '&CustomizeWinning=' + customWinings;

        $data.CustomizeWinning = customWinings;
        $data.EntryType = $scope.Type.EntryType;

        // var data = 'SessionKey=' + SessionKey + '&Privacy=No&' + $("form[name='add_form']").serialize() + '&CustomizeWinning=' + customWinings;
        var PostData = 'SessionKey=' + SessionKey + '&Privacy=No&' + $("form[name='add_form']").serialize() + '&CustomizeWinning=' + $data.CustomizeWinning;


        $http.post(API_URL + 'admin/preContest/add', PostData, contentType).then(function (response) {
            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
                $scope.applyFilter();
                $scope.addDataLoading = false;
                $('.modal-header .close').click();
            } else {
                $scope.addDataLoading = false;
                alertify.error(response.Message);
            }
        });
        $scope.addDataLoading = false;
    }

    /*edit data*/
    $scope.editData = function ()
    {

        $scope.editDataLoading = true;

        var inputData = {};

        inputData.ContestName = $scope.formData.ContestName;
        inputData.IsPaid = $scope.formData.IsPaid;
        inputData.WinningAmount = $scope.custom.WinningAmount;
        inputData.UnfilledWinningPercent = $scope.formData.UnfilledWinningPercent;
        inputData.SmartPool = $scope.SmartCustom.SmartPool;
        inputData.CashBonusContribution = $scope.custom.CashBonusContribution;
        inputData.ContestFormat = $scope.formData.ContestFormat;
        inputData.ContestType = $scope.formData.ContestType;
        inputData.EntryFee = $scope.EntryFee.fee;
        inputData.EntryType = $scope.formData.EntryType;
        inputData.IsVirtualUserJoined = $scope.formData.IsVirtualUserJoined;
        inputData.VirtualUserJoinedPercentage = $scope.formData.VirtualUserJoinedPercentage;
        inputData.ContestSize = $scope.custom.ContestSize;
        inputData.IsConfirm = $scope.formData.IsConfirm;
        inputData.ShowJoinedContest = $scope.formData.ShowJoinedContest;
        inputData.IsAutoCreate = $scope.formData.IsAutoCreate;
        inputData.PreContestID = $scope.formData.PreContestID;
        inputData.NoOfWinners = $scope.custom.NoOfWinners;
        inputData.CustomizeWinning = JSON.stringify($scope.custom.choices);
        inputData.SessionKey = SessionKey;
        inputData.Privacy = $scope.formData.Privacy;
        inputData.GameType = $scope.formData.GameType;
        inputData.AdminPercent = $scope.custom.AdminPercent;
        inputData.UserJoinLimit = $scope.formData.UserJoinLimit;
        inputData.WinningRatio = $scope.formData.WinningRatio;
        inputData.WinUpTo = $scope.formData.WinUpTo;

        if (inputData.ContestFormat == 'Head to Head')
        {
            if ($scope.custom.NoOfWinners > 1)
            {
                $scope.calculation_error = true;
                $scope.calculation_error_msg = 'No. of winners should be only one in case of head to head contest';
                return false;
            }
        }

        if (inputData.EntryType == 'Multiple') {
            inputData.UserJoinLimit = $scope.formData.UserJoinLimit;
        }

        if (inputData.ContestFormat != 'Head to Head') {
            inputData.ContestType = $scope.formData.ContestType;
        }

        if (inputData.UnfilledWinningPercent == 'GuranteedPool') {
            inputData.EntryFee = $scope.EntryFee.fee;
            inputData.ContestSize = 0;
            inputData.WinningAmount = 0;
        } else {
            inputData.WinningRatio = 0;
            inputData.WinUpTo = 0;
        }

        if (inputData.SmartPool == 'Yes') {
            inputData.EntryFee = $scope.EntryFee.fee;
            inputData.AdminPercent = 0;
            inputData.WinningAmount = 0;
        }

        if (inputData.UnfilledWinningPercent == 'Fixed' && inputData.ContestSize == 0) {
            inputData.ContestSize = 2;
        }

        var customWinings = [];
        if (inputData.SmartPool == 'No') {
            $.each($scope.custom.choices, function (key, value) {
                customWinings.push({'From': value.From, 'To': value.To, 'Percent': parseInt(value.percent), 'WinningAmount': parseInt(value.amount)});
            });
        } else {
            for (var $i = 0; $i < $scope.custom.choices.length; $i++)
            {
                for (var i in $scope.data.categoryList) {
                    if ($scope.data.categoryList[i].CategoryGUID == $scope.custom.choices[$i].CategoryGUID) {
                        var Cat_url = $scope.data.categoryList[i].Media.Records[0].MediaThumbURL;
                        var Cat_name = $scope.data.categoryList[i].CategoryName;
                    }
                }
                customWinings.push({'From': $scope.custom.choices[$i].From, 'To': $scope.custom.choices[$i].To, 'CategoryGUID': $scope.custom.choices[$i].CategoryGUID, 'ProductUrl': Cat_url, 'ProductName': Cat_name});
            }
        }
        inputData.CustomizeWinning = customWinings;
        //var data = 'SessionKey=' + SessionKey + '&' + 'UserJoinLimit=' + inputData.UserJoinLimit + '&AdminPercent=' + inputData.AdminPercent + '&ContestName=' + inputData.ContestName + '&IsPaid=' + inputData.IsPaid + '&WinningAmount=' + inputData.WinningAmount + '&CashBonusContribution=' + inputData.CashBonusContribution + '&ContestFormat=' + inputData.ContestFormat + '&EntryFee=' + inputData.EntryFee + '&EntryType=' + inputData.EntryType + '&ContestSize=' + inputData.ContestSize + '&ContestType=' + inputData.ContestType + '&IsConfirm=' + inputData.IsConfirm + '&ShowJoinedContest=' + inputData.ShowJoinedContest + '&IsAutoCreate=' + inputData.IsAutoCreate + '&NoOfWinners=' + inputData.NoOfWinners + '&PreContestID=' + inputData.PreContestID + '&Privacy=' + inputData.Privacy + '&CustomizeWinning=' + inputData.CustomizeWinning;
        $http.post(API_URL + 'admin/preContest/edit', $.param(inputData), contentType).then(function (response) {

            var response = response.data;
            if (response.ResponseCode == 200) { /* success case */
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position] = response.Data;
                $scope.editDataLoading = false;
                $('.modal-header .close').click();
                window.location.reload();
            } else {
                $scope.editDataLoading = false;
                alertify.error(response.Message);
            }
        });
        $scope.editDataLoading = false;
    }


    /*--------------------------------------------------------------------------------------*/

    /*create contest calculations starts*/
    $scope.custom = {};
    $scope.clearForm = function () {
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
    /*------------calculate entryFee-------------------*/
    $scope.AdminPercent = 10;
    $scope.custom.ContestSize = 2;
    $scope.showSeries = true;
    $scope.contestError = false;
    $scope.contestErrorMsg = '';


    /*Function to Fetch Matches*/
    $scope.$watch('custom.ContestSize', function (newValue, oldValue) {

        // $scope.custom.NoOfWinners = '';
        if (newValue != oldValue) {
            if (typeof newValue == 'undefined') {
                $scope.EntryFee.fee = 0.00;
                return false;
            }

            if (typeof $scope.custom.WinningAmount == 'undefined') {
                $scope.winningamount_error = true;
                return false;
            } else {
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
                $scope.EntryFee.fee = ($scope.totalEntry * $scope.AdminPercent / 100 + $scope.totalEntry).toFixed(2);
            } else {
                $scope.EntryFee.fee = 0;
            }
            // if(isNaN($scope.EntryFee.fee)){
            //     $scope.EntryFee.fee = 0;
            // }
        }

    });

    $scope.$watch('custom.WinningAmount', function (newValue, oldValue) {
        if (newValue != oldValue) {
            if (typeof newValue == 'undefined') {
                $scope.EntryFee.fee = 0.00;
                return false;
            }
            /*if (newValue > 10000) {
             $scope.custom.WinningAmount = 10000;
             }*/
            console.log($scope.custom.WinningAmount);
            if (angular.isNumber($scope.custom.WinningAmount)) {
                $scope.custom.WinningAmount = $scope.custom.WinningAmount.toString();
            }
            if ($scope.custom.WinningAmount.match(/^0[0-9].*$/)) {
                $scope.custom.WinningAmount = $scope.custom.WinningAmount.replace(/^0+/, '');
            }

            if (parseInt($scope.custom.ContestSize) > 0) {
                $scope.totalEntry = $scope.custom.WinningAmount / $scope.custom.ContestSize;
                $scope.EntryFee.fee = ($scope.totalEntry * $scope.AdminPercent / 100 + $scope.totalEntry).toFixed(2);
            } else {
                $scope.EntryFee.fee = 0;
            }
            if (!$scope.editForm) {
                $scope.clearForm();
            }

        }
    }, true);

    $scope.$watch('custom.AdminPercent', function (newValue, oldValue) {


        $scope.AdminPercent = newValue;
        if (newValue != oldValue) {
            if (typeof newValue == 'undefined') {
                $scope.EntryFee.fee = 0.00;
                return false;
            }

            /*if (newValue > 10000) {
             $scope.custom.WinningAmount = 10000;
             }*/
            if (angular.isNumber($scope.custom.WinningAmount)) {
                $scope.custom.WinningAmount = $scope.custom.WinningAmount.toString();
            }
            if ($scope.custom.WinningAmount.match(/^0[0-9].*$/)) {
                $scope.custom.WinningAmount = $scope.custom.WinningAmount.replace(/^0+/, '');
            }
            if (parseInt($scope.custom.ContestSize) > 0) {
                $scope.totalEntry = $scope.custom.WinningAmount / $scope.custom.ContestSize;
                $scope.EntryFee.fee = ($scope.totalEntry * $scope.AdminPercent / 100 + $scope.totalEntry).toFixed(2);
            } else {
                $scope.EntryFee.fee = 0;
            }
            if (!$scope.editForm) {
                $scope.clearForm();
            }

        }
    }, true);
    /*------------calculate Percent and Amount-------------------*/
    $scope.custom.choices = [];
    $scope.amount = 0.00;

    $scope.changePercent = function (x) {
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
        $scope.totalCalculatePercentage = 100;
        $scope.remainingPercentage = 0;
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
                    $scope.WinningAmount = $scope.custom.WinningAmount.replace(/^0+/, '');
                }
                $scope.custom.choices[i].percent = $scope.custom.choices[i].percent.replace(/^0+/, '');
            }
        }
        $scope.remainingPercentage = $scope.totalCalculatePercentage - total;
    }

    $scope.Check = function (x) {
        if ($scope.custom.choices[x].percent != "" || $scope.custom.choices[x].percent != 0) {
            if (x < $scope.custom.choices.length) {
                $scope.custom.choices.splice(x + 1, ($scope.custom.choices.length - 1));
                $scope.calculation_error = false;
                $scope.calculation_error_msg = '';
            }
        }
    }

    $scope.changeAmount = function (x) {
        /*Remove Error First*/
        $scope.calculation_error = false;
        $scope.calculation_error_msg = '';
        /*Remove Error First*/

    }

    $scope.customizeMultieams = function () {
        $scope.calculation_error = false;
        $scope.calculation_error_msg = '';
        if ($scope.custom.ContestSize == null || $scope.custom.ContestSize < 3) {
            $scope.calculation_error = true;
            $scope.calculation_error_msg = "Contest size must be greater then 2!";
            $scope.EntryType = 'Single';
            return false;
        }
    }
    $scope.customizeWin = function () {
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
    $scope.changeWinAmount = function () {
        $scope.calculation_error = false;
        $scope.calculation_error_msg = '';
        if ($scope.custom.WinningAmount == null || $scope.custom.WinningAmount < 1) {
            $scope.winnings = false;
        }
    }
    $scope.changeWinners = function () {
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
    $scope.addField = function () {
        x = x + 1;
        $scope.numbers1 = [];

        var select2_value = "";
        $scope.percent_error = false;
        var lastIndex = $scope.custom.choices.length - 1;
        if ($scope.SmartCustom.SmartPool != 'Yes') {
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
        } else {
            for (var k = 0; $scope.custom.choices.length > k; k++) {
                if (k == $scope.custom.choices.length - 1) {
                    select2_value = ($scope.custom.choices[k].To + 1);
                    for (var j = ($scope.custom.choices[k].To + 1); j <= parseInt($scope.custom.NoOfWinners); j++) {
                        $scope.numbers1.push(j)
                    }
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
    $scope.$watch('$scope.custom.choices', function (n, o, scope) {
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
    $scope.$watch('custom.NoOfWinners', function (newValue, oldValue) {
        if (parseInt(newValue) > parseInt($scope.custom.ContestSize)) {
            alertify.error("No. of Winners can not be greater than Contest Size.");
            $scope.custom.NoOfWinners = oldValue;
        }
    });



    $scope.removeField = function (index) {
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
    $scope.Showform = function () {

        if ($scope.SmartCustom.SmartPool == 'Yes') {
            $scope.getCategoriesList();
        }

        if ($scope.custom.NoOfWinners == '' || $scope.custom.NoOfWinners == '0') {
            $scope.calculation_error = true;
            $scope.calculation_error_msg = "Please enter proper winner count!";
            return false;
        }
        $scope.remainingPercentage = 100;
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

    $scope.contestPrizeParser = function ($choices)
    {
        let response = [];
        let valueArray = [];
        if ($scope.SmartCustom.SmartPool == 'Yes') {
            for (var $i = 0; $i < $scope.custom.choices.length; $i++) {
                for (var i in $scope.data.categoryList) {
                    if ($scope.data.categoryList[i].CategoryGUID == $scope.custom.choices[$i].CategoryGUID) {
                        var Cat_url = $scope.data.categoryList[i].Media.Records[0].MediaThumbURL;
                        var Cat_name = $scope.data.categoryList[i].CategoryName;
                    }
                }
                valueArray.push({'From': $scope.custom.choices[$i].From, 'To': $scope.custom.choices[$i].To, 'CategoryGUID': $scope.custom.choices[$i].CategoryGUID, 'ProductUrl': Cat_url, 'ProductName': Cat_name});
            }
        } else {
            for (var $i = 0; $i < $scope.custom.choices.length; $i++) {
                valueArray.push({'From': $scope.custom.choices[$i].From, 'To': $scope.custom.choices[$i].To, 'Percent': parseInt($scope.custom.choices[$i].percent), 'WinningAmount': parseInt($scope.custom.choices[$i].amount)});
            }
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
        $scope.templateURLEdit = PATH_TEMPLATE + module + '/updateStatus_form.htm?' + Math.random();
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

    $scope.loadContestJoinedUser = function (Position, ContestGUID)
    {

        $scope.data.Position = Position;
        $scope.templateURLEdit = PATH_TEMPLATE + module + '/joinedContest_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $http.post(API_URL + 'contest/getJoinedContestsUsers', 'SessionKey=' + SessionKey + '&ContestGUID=' + ContestGUID + '&Params=UserTeamName,TotalPoints,UserWinningAmount,FirstName,Username,UserGUID,UserTeamPlayers,UserTeamID,UserRank', contentType).then(function (response) {
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

    /*edit status*/
    $scope.editStatus = function (Status, contestGUID)
    {
        if (Status == 'Cancelled') {
            if (confirm('Are you sure, want to cancel contest ?')) {
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
            }
        } else {
            $scope.editDataLoading = true;
            var data = 'SessionKey=' + SessionKey + '&contestGUID=' + contestGUID + '&Status=' + Status;
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

/* sortable - ends */