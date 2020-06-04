var app = angular.module('ServeChamp', ['ngStorage', 'ngAnimate', 'toastr', '720kb.datepicker', 'ngFileUpload', 'socialLogin', 'infinite-scroll', 'slickCarousel']);
var contentType = {
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    }
};
/*main controller*/
app.controller('MainController', ["$scope", "$http", "$timeout", "$localStorage", "$sessionStorage", "appDB", "toastr", "$rootScope", "environment", function($scope, $http, $timeout, $localStorage, $sessionStorage, appDB, toastr, $rootScope, environment) {
    $scope.data = { dataList: [], totalRecords: '0', pageNo: 1, pageSize: 25, noRecords: false, UserGUID: UserGUID, notificationCount: 0 };
    $scope.orig = angular.copy($scope.data);
    $scope.UserTypeID = UserTypeID;
    $scope.base_url = base_url;
    $scope.env = environment;
    /*delete Entity*/

    $scope.deleteData = function(EntityGUID) {
        $scope.deleteDataLoading = true;
        alertify.confirm('Are you sure you want to delete?', function() {
            var data = 'SessionKey=' + SessionKey + '&EntityGUID=' + EntityGUID;
            $http.post(API_URL + 'api_admin/entity/delete', data, contentType).then(function(response) {
                var response = response.data;
                if (response.ResponseCode == 200) { /* success case */
                    alertify.success(response.Message);
                    $scope.data.dataList.splice($scope.data.Position, 1); /*remove row*/
                    $scope.data.totalRecords--;
                    $('.modal-header .close').click();
                } else {
                    alertify.error(response.Message);
                }
                if ($scope.data.totalRecords == 0) {
                    $scope.data.noRecords = true;
                }
            });
        }).set('labels', { ok: 'Yes', cancel: 'No' });
        $scope.deleteDataLoading = false;

    }


    $scope.amount = 100;
    $rootScope.profileDetails = {};
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {

        $scope.getWalletDetails = function() {
            var $data = {};
            $data.UserGUID = $localStorage.user_details.UserGUID;
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.Params = 'WithdrawText,FirstName,Email,ProfilePic,WalletAmount,WinningAmount,CashBonus,TotalCash';
            $data.WithdrawText = 'Yes';
            appDB
                .callPostForm('users/getProfile', $data)
                .then(
                    function successCallback(data) {
                        if (data.ResponseCode == 200) {
                            $rootScope.profileDetails = data.Data;
                            $scope.WinningAmount = $rootScope.profileDetails.WinningAmount;
                            $scope.getSetting();
                        }
                        if (data.ResponseCode == 500) {
                            var toast = toastr.warning(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);

                        }
                        if (data.ResponseCode == 501) {
                            var toast = toastr.warning(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);
                        }
                    },
                    function errorCallback(data) {

                        if (typeof data == 'object') {
                            var toast = toastr.error(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);
                        }
                    });


        }
        $scope.getWalletDetails();

        /**
         * Get site setting
         */
        $rootScope.BonusExpireDays = 0;
        $rootScope.MinimumWithdarwalAmount = 0;
        $rootScope.MinimumDepositAmount = 0;
        $rootScope.MinimumWithdrawalLimitPaytm = 0;
        $rootScope.MaximumWithdrawalLimitPaytmPerDay = 0;
        $scope.getSetting = function() {
            $http.get($scope.env.api_url + 'Utilities/setting', contentType).then(function(response) {
                var response = response.data;
                if ($scope.checkResponseCode(response)) {
                    var data = response.Data.Records;
                    for (var i in data) {
                        if (data[i].ConfigTypeGUID == 'CashBonusExpireTimeInDays') {
                            $rootScope.BonusExpireDays = data[i].ConfigTypeValue;
                        } else if (data[i].ConfigTypeGUID == 'MinimumWithdrawalLimitBank') {
                            $rootScope.MinimumWithdarwalAmount = data[i].ConfigTypeValue;
                        } else if (data[i].ConfigTypeGUID == 'MinimumDepositLimit') {
                            $rootScope.MinimumDepositAmount = data[i].ConfigTypeValue;
                        } else if (data[i].ConfigTypeGUID == 'MinimumWithdrawalLimitPaytm') {
                            $rootScope.MinimumWithdrawalLimitPaytm = data[i].ConfigTypeValue;
                        } else if (data[i].ConfigTypeGUID == 'MaximumWithdrawalLimitPaytmPerDay') {
                            $rootScope.MaximumWithdrawalLimitPaytmPerDay = data[i].ConfigTypeValue;
                        }
                    }
                }
            });
        }
    }

    $scope.checkResponseCode = function(data) {
        if (data.ResponseCode == 200) {
            return true;
        } else if (data.ResponseCode == 500) {
            var toast = toastr.warning(data.Message, {
                closeButton: true
            });
            toastr.refreshTimer(toast, 5000);
            return false;
        } else if (data.ResponseCode == 501) {
            var toast = toastr.warning(data.Message, {
                closeButton: true
            });
            toastr.refreshTimer(toast, 5000);
            return false;
        } else if (data.ResponseCode == 502) {
            var toast = toastr.warning(data.Message, {
                closeButton: true
            });
            toastr.refreshTimer(toast, 5000);
            setTimeout(function() {
                localStorage.clear();
                window.location.reload();
            }, 1000);
            return false;
        }
    }

    $scope.errorMessageShow = function(Message) {
        var toast = toastr.error(Message, {
            closeButton: true
        });
        toastr.refreshTimer(toast, 5000);
    }
    $scope.successMessageShow = function(Message) {
        var toast = toastr.success(Message, {
            closeButton: true
        });
        toastr.refreshTimer(toast, 5000);
    }
    $scope.warningMessageShow = function(Message) {
        var toast = toastr.warning(Message, {
            closeButton: true
        });
        toastr.refreshTimer(toast, 5000);
    }

    $(document).on('click', "#select-all", function(event) {
        $('.select-all-checkbox').not(this).prop('checked', this.checked);
    });

    $(document).on('click', ".select-all-checkbox", function(event) {
        var anyBoxesChecked = false;
        $('.select-all-checkbox').each(function() {
            if ($(this).is(":checked")) {
                anyBoxesChecked = true;
            }
        });

        if (anyBoxesChecked) {
            $('#select-all').prop('checked', true);
        } else {
            $('#select-all').prop('checked', false);
        }

    });


    $scope.getPlayerShortName = function(PlayerName) {
        var FirstLetter = PlayerName.substr(0, 1);
        var SecondLetter = PlayerName.substr(PlayerName.indexOf(' ') + 1);
        return FirstLetter + ' ' + SecondLetter;
    }

    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.isLoggedIn = $localStorage.isLoggedIn;
        $scope.user_details = $localStorage.user_details;
    }

    $scope.moneyFormat = function(money) {
        money = Number(money);
        var a = money.toLocaleString('en-IN', {
            maximumFractionDigits: 2,
            style: 'currency',
            currency: 'INR'
        });
        return a;
    }
}]);

/*jquery*/
$(document).ready(function() {

    /*Submit Form*/
    $(".form-control").keypress(function(e) {
        if (e.which == 13) {
            $(this.form).find(':submit').focus().click();
        }
    });
    $('[data-toggle="tooltip"]').tooltip();

    /*disable right click*/
    $('html').on("contextmenu", function(e) {
        //        return false;
    });


    $(document).on('keypress', ".numeric", function(event) {
        var key = window.event ? event.keyCode : event.which;
        if (event.keyCode === 8 || event.keyCode === 46) {
            return true;
        } else if (key < 48 || key > 57) {
            return false;
        } else {
            return true;
        }
    });


    $(document).on('keypress', ".integer", function(event) {
        var key = window.event ? event.keyCode : event.which;
        if (event.keyCode === 8 /* || event.keyCode === 46*/ ) {
            return true;
        } else if (key < 48 || key > 57) {
            return false;
        } else {
            return true;
        }
    });




    /*upload profile picture*/
    $(document).on('click', "#picture-uploadBtn", function() {
        $(this).parent().find('#fileInput').focus().val("").trigger('click');
    });


    $(document).on('change', '#fileInput', function() {
        var target = $(this).data('target');
        var mediaGUID = $(this).data('targetinput');
        var progressBar = $('.progressBar'),
            bar = $('.progressBar .bar'),
            percent = $('.progressBar .percent');
        $(this).parent().ajaxForm({
            data: { SessionKey: SessionKey },
            dataType: 'json',
            beforeSend: function() {
                progressBar.fadeIn();
                var percentVal = '0%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            uploadProgress: function(event, position, total, percentComplete) {
                var percentVal = percentComplete + '%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            success: function(obj, statusText, xhr, $form) {
                if (obj.ResponseCode == 200) {
                    var percentVal = '100%';
                    bar.width(percentVal)
                    percent.html(percentVal);
                    $(target).prop("src", obj.Data.MediaURL);
                    //$("input[name='MediaGUIDs']").val(obj.Data.MediaGUID);
                    $(mediaGUID).val(obj.Data.MediaGUID);
                } else {
                    alertify.error(obj.Message);
                }
            },
            complete: function(xhr) {
                progressBar.fadeOut();
                $('#fileInput').val("");
            }
        }).submit();
    });

    $(document).on('keypress', ".numeric", function(event) {
        var key = window.event ? event.keyCode : event.which;
        if (event.keyCode === 8 || event.keyCode === 46) {
            return true;
        } else if (key < 48 || key > 57) {
            return false;
        } else {
            return true;
        }
    });


}); /* document ready end */
function getQueryStringValue(key) {
    var vars = [],
        hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return (!vars[key]) ? '' : vars[key];
}