app.controller('PageController', function($scope, $http, $timeout,$localStorage) {
    $scope.data.pageSize = 100;
    $scope.data.ParentCategoryGUID = ParentCategoryGUID;
    /*----------------*/
    $scope.getFilterData = function() {
        var data = 'SessionKey=' + SessionKey + '&ParentCategoryGUID=' + ParentCategoryGUID + '&' + $('#filterPanel form').serialize();
        $http.post(API_URL + 'admin/category/getFilterData', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200 && response.Data) {
                /* success case */
                $scope.filterData = response.Data;
                $timeout(function() {
                    $("select.chosen-select").chosen({
                        width: '100%',
                        "disable_search_threshold": 8
                    }).trigger("chosen:updated");
                }, 300);
            }
        });
    }


    /*list*/
    $scope.applyFilter = function() {
        $scope.data = angular.copy($scope.orig); /*copy and reset from original scope*/
        $scope.getList();
    }
    $scope.UserAdminDetails=$localStorage.UserAdmin;

    if(getQueryStringValue('UserGUID'))
            {
              
            }
           $scope.UserGUID= getQueryStringValue('UserGUID');
    /*list append*/
    $scope.getList = function() {
        $scope.getCategoriesList();
        if ($scope.data.listLoading || $scope.data.noRecords) return;
        $scope.data.listLoading = true;
        if($scope.UserAdminDetails.UserTypeID=='1' && getQueryStringValue('UserGUID')){
            var data = 'SessionKey=' + SessionKey + '&PageNo=' + $scope.data.pageNo +'&UserGUID='+$scope.UserGUID + '&PageSize=' + $scope.data.pageSize + '&Params=Services,Keyword,UserGUID&' + $('#filterForm').serialize();
        }else if($scope.UserAdminDetails.UserTypeID=='1'){
            var data = 'SessionKey=' + SessionKey + '&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize + '&Params=Services,Keyword&' + $('#filterForm').serialize();
        }else if($scope.UserAdminDetails.UserTypeID=='7'){
            var data = 'SessionKey=' + SessionKey + '&PageNo=' + $scope.data.pageNo + '&PageSize=' + $scope.data.pageSize+ '&EmployeeGUID='+$scope.UserAdminDetails.UserGUID+ '&Params=Services,Keyword&' + $('#filterForm').serialize();
        }
        $http.post(API_URL + 'admin/booking/getBookings', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200 && response.Data.Records) {
                /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                    $scope.data.dataList.push(response.Data.Records[i]);
                }
                $scope.data.pageNo++;
            } else {
                $scope.data.noRecords = true;
            }
            $scope.data.listLoading = false;
            setTimeout(function() {
                tblsort();
            }, 1000);
        });
    }


    /*load add form*/
    $scope.loadFormAdd = function(Position, FeatureGUID) {
        $scope.getCategoriesList();
        $scope.getFeaturesList();
        $scope.templateURLAdd = PATH_TEMPLATE + module + '/add_form.htm?' + Math.random();
        $('#add_model').modal({
            show: true
        });
        $timeout(function() {
            $(".chosen-select").chosen({
                width: '100%',
                "disable_search_threshold": 8,
                "placeholder_text_multiple": "Please Select",
            }).trigger("chosen:updated");
        }, 200);
    }





    /*load Assign form*/
    $scope.loadFormAssign = function(Position, BookingGUID) {
        $scope.data.Position = Position;
        $scope.dataListAssign = $scope.data.dataList[Position];
        $scope.templateURLEdit = PATH_TEMPLATE + module + '/assign_form.htm?' + Math.random();
        $scope.getListUsers();
        $('#assign_form').modal({
            show: true
        });
        $timeout(function() {
            $(".chosen-select").chosen({
                width: '100%',
                "disable_search_threshold": 8,
                "placeholder_text_multiple": "Please Select",
            }).trigger("chosen:updated");
        }, 200);
    }



    /*list append*/
    $scope.getListUsers = function() {
        var data = 'SessionKey=' + SessionKey + '&UserTypeID=7&IsAdmin=Yes&'+ 'Params=RegisteredOn,EmailForChange,EmailStatus,PhoneNumberForChange,PhoneStatus,LastLoginDate,UserTypeName, FullName, Email, Username, ProfilePic, Gender, BirthDate, PhoneNumber, Status, ReferredCount,StatusID';
        $http.post(API_URL + 'admin/users', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200 && response.Data.Records) {
                /* success case */
                $scope.totalRecords = response.Data.TotalRecords;
                $scope.dataListUser = response.Data.Records;
            }
        });
    }


    $scope.assignEmployee = function() {
        $scope.assignDataLoading = true;
        var data = 'SessionKey=' + SessionKey + '&BookingGUID=' + $scope.dataListAssign.BookingGUID + '&' + $('#assign_form ').serialize();
        $http.post(API_URL + 'admin/booking/assignEmployee', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) {
                /* success case */
                $scope.data.pageLoading = false;
                $scope.formData = response.Data
                alertify.success(response.Message);
                $('.modal-header .close').click();
            } else {
                alertify.error(response.Message);
            }
            $scope.assignDataLoading = false;

        });

    }

    /*load Details form*/
    $scope.loadFormDetails = function(Position) {
        $scope.data.Position = Position;
        $scope.formData = $scope.data.dataList[Position];
        $scope.templateURLDelete = PATH_TEMPLATE + module + '/details_form.htm?' + Math.random();
        $scope.data.pageLoading = true;
        $('#details_model').modal({
            show: true
        });
        $scope.data.pageLoading = false;
    }

    /*add data*/
    $scope.addData = function() {
        $scope.addDataLoading = true;
        var data = 'SessionKey=' + SessionKey + '&' + $("form[name='add_form']").serialize();
        $http.post(API_URL + 'admin/service/add', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) {
                /* success case */
                alertify.success(response.Message);
                $scope.applyFilter();
                $('.modal-header .close').click();
            } else {
                alertify.error(response.Message);
            }
            $scope.addDataLoading = false;
        });
    }


    /*edit data*/
    $scope.editData = function() {
        $scope.editDataLoading = true;
        var data = 'SessionKey=' + SessionKey + '&' + $("form[name='edit_form']").serialize();
        $http.post(API_URL + 'admin/service/editService', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) {
                /* success case */
                alertify.success(response.Message);
                $scope.data.dataList[$scope.data.Position] = response.Data;
                $('.modal-header .close').click();
                window.location.reload();
            } else {
                alertify.error(response.Message);
            }
            $scope.editDataLoading = false;
        });
    }


    /*list append*/
    $scope.getCategoriesList = function() {
        $scope.categoryList = [];
        var data = 'SessionKey=' + SessionKey;
        $http.post(API_URL + 'category/getCategories', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) {
                /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                    $scope.categoryList.push(response.Data.Records[i]);
                }
            } else {
                $scope.data.noRecords = true;
            }
        });
    }

    /*list append*/
    $scope.getFeaturesList = function() {
        $scope.featureList = [];
        var data = 'SessionKey=' + SessionKey;
        $http.post(API_URL + 'feature/getFeatures', data, contentType).then(function(response) {
            var response = response.data;
            if (response.ResponseCode == 200) {
                /* success case */
                $scope.data.totalRecords = response.Data.TotalRecords;
                for (var i in response.Data.Records) {
                    $scope.featureList.push(response.Data.Records[i]);
                }
            } else {
                $scope.data.noRecords = true;
            }
        });
    }



});




/* sortable - starts */
function tblsort() {

    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    }

    $(".table-sortable tbody").sortable({
        placeholder: 'tr_placeholder',
        helper: fixHelper,
        cursor: "move",
        tolerance: 'pointer',
        axis: 'y',
        dropOnEmpty: false,
        update: function(event, ui) {
            sendOrderToServer();
        }
    }).disableSelection();
    $(".table-sortable thead").disableSelection();


    function sendOrderToServer() {
        var order = 'SessionKey=' + SessionKey + '&' + $("#tabledivbody").sortable("serialize");
        $.ajax({
            type: "POST",
            dataType: "json",
            url: API_URL + 'admin/entity/setOrder',
            data: order,
            stop: function(response) {
                if (response.status == "success") {
                    window.location.href = window.location.href;
                } else {
                    alert('Some error occurred');
                }
            }
        });
    }



}


/* sortable - ends */