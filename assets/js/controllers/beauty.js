'use strict';
app.controller('beautyController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$sce', 'toastr', 'socialLoginService', '$http', '$timeout', function($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $sce, toastr, socialLoginService, $http, $timeout) {
    $scope.env = environment;
    $scope.base_url = base_url;

    /**
     * redirect to login/signup pages
     */
    $scope.goToLogin = function(PageName) {
        $localStorage.loginPage = PageName;
        if ($scope.secondLevelLocation != 'authenticate') {
            window.location.href = base_url + 'authenticate';
        }
    }
    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.isLoggedIn = $localStorage.isLoggedIn;
        $scope.base_url = base_url;
        $scope.referral_url = base_url + $localStorage.user_details.ReferralCode;



        $scope.categoryList = {};
        $scope.getCategory = function() {

            var $data = {};
            appDB
                .callPostForm('category/getCategories', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.categoryList = data.Data;
                            $scope.categoryList.Records.forEach(el => {
                                el.selectedIteam = [];
                            });
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });

        }
        $scope.Product = [];
        $scope.clickService = function(Id, key, CategoryGUID) {
            $scope.ServiceId = Id + key;
            $scope.CategoryGUID = CategoryGUID;
            $scope.getServicesinfo();
        }

        $scope.serviceDetail = {};
        $scope.getServicesinfo = function() {
            var $data = {};
            $data.CategoryGUID = $scope.CategoryGUID;
            $data.PageNo = "1";
            $data.PageSize = "15";
            $data.Params = 'ServiceType,Description, Price, TimeDuration, VariablePrice,VariableTimeDuration';
            appDB
                .callPostForm('service/getServices', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.serviceDetail = data.Data.Records;
                            let index = $scope.categoryList.Records.map(e => {
                                return e.CategoryGUID;
                            }).indexOf($scope.CategoryGUID);
                            if (index != -1) {
                                var data = $scope.categoryList.Records[index].selectedIteam;
                            }
                            for (var i in $scope.serviceDetail) {
                                if (data != '') {
                                    let index1 = data.map(e => { return e.ServiceGUID }).indexOf($scope.serviceDetail[i].ServiceGUID);
                                    if (index1 != -1) {
                                        $scope.serviceDetail[i].count = data[index1].count;
                                        $scope.serviceDetail[i].TotalPrice = data[index1].TotalPrice;
                                    } else {
                                        $scope.serviceDetail[i].count = 0;
                                        $scope.serviceDetail[i].TotalPrice = 0;
                                    }
                                } else {
                                    $scope.serviceDetail[i].count = 0;
                                    $scope.serviceDetail[i].TotalPrice = 0;
                                }
                            }
                            console.log($scope.categoryList.Records);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });

        }



        $scope.bookingDetail = {};
        $scope.getBookinginfo = function() {

            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey;
            $data.Booking = $scope.booking;
            appDB
                .callPostForm('booking/add', $.param($data), contentType)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.bookingDetail = data.Data;
                            console.log($scope.bookingDetail);
                            toastr.success(data.Message);
                            toastr.refreshTimer(toastr, 5000);
                            $localStorage.bookingDetail = $scope.bookingDetail;
                            window.location.href = base_url + 'checkout';
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });

        }
        $scope.booking = [];
        $scope.CalculateM = function(key, ServiceGUID) {
            if ($scope.serviceDetail[key].count > 0) {
                $scope.serviceDetail[key].count = $scope.serviceDetail[key].count - 1;
                // $scope.serviceDetail[key].TotalPrice = $scope.serviceDetail[key].Price * $scope.serviceDetail[key].count;
                let index = $scope.categoryList.Records.map(e => {
                    return e.CategoryGUID
                }).indexOf($scope.CategoryGUID);
                if (index != -1) {
                    let index2 = $scope.categoryList.Records[index].selectedIteam.map(e => {
                        return e.ServiceGUID;
                    }).indexOf(ServiceGUID);
                    if (index2 != -1) {
                        $scope.categoryList.Records[index].selectedIteam[index2] = $scope.serviceDetail[key];
                    } else {
                        $scope.categoryList.Records[index].selectedIteam.push($scope.serviceDetail[key]);
                    }
                }
                let index1 = $scope.booking.map(e => {
                    return e.ServiceGUID;
                }).indexOf(ServiceGUID);
                if (index1 != -1) {
                    $scope.booking[index1].Quantity = $scope.serviceDetail[key].count;
                    $scope.booking[index1].Price = $scope.serviceDetail[key].Price;
                } else {
                    $scope.booking.push({
                        "ServiceGUID": ServiceGUID,
                        'Quantity': $scope.serviceDetail[key].Count,
                        'ServiceGUID': $scope.serviceDetail[key].ServiceGUID,
                        'Description': $scope.serviceDetail[key].Description,
                        'FeatureName': $scope.serviceDetail[key].FeatureName,
                        'Name': $scope.serviceDetail[key].Name,
                        "Price": $scope.serviceDetail[key].Price,
                    });
                }
            }
            $localStorage.Product = $scope.booking;

            $scope.getCalculation();
        }
        $scope.CalculateP = function(key, ServiceGUID) {
            $scope.serviceDetail[key].count = $scope.serviceDetail[key].count + 1;
            // $scope.serviceDetail[key].TotalPrice = $scope.serviceDetail[key].Price * $scope.serviceDetail[key].count;
            let index = $scope.categoryList.Records.map(e => {
                return e.CategoryGUID
            }).indexOf($scope.CategoryGUID);
            if (index != -1) {
                let index2 = $scope.categoryList.Records[index].selectedIteam.map(e => {
                    return e.ServiceGUID;
                }).indexOf(ServiceGUID);
                if (index2 != -1) {
                    $scope.categoryList.Records[index].selectedIteam[index2] = $scope.serviceDetail[key];
                } else {
                    $scope.categoryList.Records[index].selectedIteam.push($scope.serviceDetail[key]);
                }
            }
            let index1 = $scope.booking.map(e => {
                return e.ServiceGUID;
            }).indexOf(ServiceGUID);
            if (index1 != -1) {
                $scope.booking[index1].Quantity = $scope.serviceDetail[key].count;
                $scope.booking[index1].Price = $scope.serviceDetail[key].Price;
            } else {
                $scope.booking.push({
                    "ServiceGUID": ServiceGUID,
                    'Quantity': $scope.serviceDetail[key].count,
                    'ServiceGUID': $scope.serviceDetail[key].ServiceGUID,
                    'Description': $scope.serviceDetail[key].Description,
                    'FeatureName': $scope.serviceDetail[key].FeatureName,
                    'Name': $scope.serviceDetail[key].Name,
                    "Price": $scope.serviceDetail[key].Price,
                });
            }
            $localStorage.Product = $scope.booking;

            $scope.getCalculation();
        }
        $scope.goCartDetails = function() {
            $scope.product = $localStorage.Product;
            $scope.TotalPrice = $localStorage.TotalPrice;
        }

        $scope.deleteCartItem = function(position) {
            $localStorage.Product.splice(position, 1);
        }
        $scope.getCalculation = function() {

            var TotalPrice = 0;
            var TotalCount = 0;
            // for (var i = 0; i < $scope.booking.length; i++) {
            //     var product = $scope.booking[i];
            //     TotalPrice += (product.Price * product.Count);
            //     $scope.TotalPrice = TotalPrice;
            // }


            for (var i = 0; i < $scope.categoryList.Records.length; i++) {
                var product = $scope.categoryList.Records[i];
                for (var j = 0; j < product.selectedIteam.length; j++) {
                    var selected = product.selectedIteam[j];
                    TotalPrice += (selected.Price * selected.count);
                    $scope.TotalPrice = TotalPrice;
                    $scope.TotalCount = selected.count;
                }

            }
            $localStorage.TotalPrice = $scope.TotalPrice;
            console.log($scope.TotalPrice, $scope.TotalCount);
            return TotalPrice;
        }



        $scope.paymentDetail = {};
        $scope.paymentConfig = function() {
            var $data = {};
            $data.SessionKey = $scope.user_details.SessionKey;
            $data.FirstName = $scope.user_details.FirstName;
            $data.BookingGUID = $localStorage.bookingDetail.BookingGUID;
            $data.Email = $scope.user_details.Email;
            $data.PhoneNumber = $scope.user_details.PhoneNumber;
            $data.Booking = $scope.booking;
            $data.PaymentGateway = 'PayUmoney';
            appDB
                .callPostForm('booking/paymentConfiguration', $.param($data), contentType)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {

                            $scope.payUData = data.Data;
                            console.log($scope.payUData);
                            // toastr.success(data.Message);
                            $timeout(function() {
                                $scope.openBolt();
                            }, 1000)


                            toastr.refreshTimer(toastr, 5000);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });

        }







        $scope.openBolt = function() {
            console.log(bolt);
            bolt.launch({
                key: $scope.payUData.MerchantKey,
                txnid: $scope.payUData.TransactionID,
                hash: $scope.payUData.Hash,
                amount: $scope.payUData.Amount,
                firstname: $scope.payUData.FirstName,
                email: $scope.payUData.Email,
                phone: $scope.payUData.PhoneNumber,
                productinfo: $scope.payUData.ProductInfo.toString(),
                surl: $scope.payUData.SuccessURL,
                furl: $scope.payUData.FailedURL,
                lastname: '',
                curl: '',
                address1: '',
                address2: '',
                city: '',
                state: '',
                country: '',
                zipcode: '',
                udf1: '',
                udf2: '',
                udf3: '',
                udf4: '',
                udf5: '',
                pg: '',
                enforce_paymethod: '',
                expirytime: ''
            }, {
                responseHandler: function(get) {

                    if (get.response.txnStatus == 'SUCCESS') {
                        var status = 'Success';
                    } else if (get.response.txnStatus == 'CANCEL') {
                        var status = 'Cancelled';
                    } else {
                        var status = 'Failed';
                    }

                    var $data = {
                        "SessionKey": $localStorage.user_details.SessionKey,
                        "PaymentGateway": "PayUmoney",
                        "PaymentGatewayStatus": status,
                        "BookingGUID": $localStorage.bookingDetail.BookingGUID,
                        "PaymentGatewayResponse": JSON.stringify(get.response)
                    };

                    appDB
                        .callPostForm('booking/updateBookingPaymentStatus', $data)
                        .then(
                            function success(data) {
                                if (data.ResponseCode == 200) {
                                    if (status == 'Cancelled') {
                                        window.location.href = base_url + 'checkout?status=Cancelled';
                                    }
                                    if (status == 'Success') {
                                        window.location.href = base_url + 'checkout?status=Success';
                                    }

                                    var toast = toastr.success(data.Message, {
                                        closeButton: true
                                    });
                                    toastr.refreshTimer(toast, 5000);
                                } else {
                                    window.location.href = base_url + 'checkout?status=Failed';
                                    var toast = toastr.error(data.Message, {
                                        closeButton: true
                                    });
                                    toastr.refreshTimer(toast, 5000);
                                }
                            },
                            function error(data) {
                                console.log('error', data);
                            }
                        );

                },
                catchException: function(get) {
                    alert(get.message);
                }
            });
        }
        setTimeout(function() {
            $('#pills-Evergreen-tab').trigger('click');
        }, 500);



    } else {
        window.location.href = base_url;
    }
}]);