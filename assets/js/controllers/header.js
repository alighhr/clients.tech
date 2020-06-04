'use strict';
app.controller('headerController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$sce', 'toastr', 'socialLoginService', '$http', '$timeout', function($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $sce, toastr, socialLoginService, $http, $timeout) {
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

        /*get notifications*/
        $scope.notificationList = [];
        $scope.getNotifications = function() {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.PageNo = 1;
            $data.PageSize = 10;
            $data.Status = 1;
            appDB
                .callPostForm('notifications', $data)
                .then(
                    function successCallback(data) {
                        $scope.getNotificationCount();
                        $scope.notificationList = [];
                        if (data.ResponseCode == 200 && data.Data.Records) {
                            data.Data.Records.forEach(element => {
                                element.isChecked = false;
                                $scope.notificationList.push(element);
                            });
                        } else if (data.ResponseCode == 502) {
                            var toast = toastr.warning(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);
                            setTimeout(function() {
                                localStorage.clear();
                                window.location.reload();
                            }, 1000);
                        }
                    },
                    function errorCallback(data) {
                        localStorage.clear();
                    }
                );
        }


        /*get notification count*/
        $scope.notificationCount = 0;
        $scope.getNotificationCount = function() {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            appDB
                .callPostForm('notifications/getNotificationCount', $data)
                .then(
                    function successCallback(data) {
                        if (data.ResponseCode == 200) {
                            $scope.notificationCount = Number(data.Data.TotalUnread);
                        } else if (data.ResponseCode == 502) {
                            var toast = toastr.warning(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);
                            setTimeout(function() {
                                localStorage.clear();
                                window.location.reload();
                            }, 1000);
                        }

                    },
                    function errorCallback(data) {
                        localStorage.clear();
                    }
                );
        }
        $rootScope.loader = {};
        $scope.readNotification = function(notification_id, MatchGUID) {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.NotificationID = notification_id;
            $rootScope.loader.isLoading = false;
            appDB
                .callPostForm('notifications/markRead', $data)
                .then(
                    function successCallback(data) {
                        $rootScope.loader.isLoading = true;
                        if (data.ResponseCode == 200) {
                            if (MatchGUID != '') {
                                $localStorage.SeriesGUID = '';
                                $localStorage.MatchGUID = '';
                                $localStorage.LeagueMatchGUID = '';
                                $localStorage.MatchGUID = MatchGUID;
                                localStorage.setItem('redirect_form_notify', true);
                                window.location.href = base_url + 'lobby';
                            }
                            $scope.getNotifications();
                        } else if (data.ResponseCode == 502) {
                            var toast = toastr.warning(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);
                            setTimeout(function() {
                                localStorage.clear();
                                window.location.reload();
                            }, 1000);
                        }
                    },
                    function errorCallback(data) {
                        $rootScope.loader.isLoading = true;
                    }
                );
        }

        /*Logout*/
        $scope.logout = function() {
                var $data = {};
                $data.SessionKey = $localStorage.user_details.SessionKey;
                appDB
                    .callPostForm('signin/signout', $data)
                    .then(
                        function successCallback(data) {
                            if (data.ResponseCode == 200) {
                                if ($localStorage.hasOwnProperty('SocialLogin') && $localStorage.SocialLogin === true) {
                                    socialLoginService.logout();
                                    $http.jsonp('https://accounts.google.com/logout');
                                }
                                localStorage.clear();
                                window.location.href = base_url;
                            }
                        },
                        function errorCallback(data) {
                            localStorage.clear();
                        }
                    );
            }
            /**
             * Checked/un-checked all notification
             */
        $scope.selectAllNotification = function(status) {
                $scope.notificationList.forEach(element => {
                    element.isChecked = status;
                });
            }
            /**
             * Check notification deletion count
             */
        $scope.checkNotificationDeletionCount = function() {
                $scope.DeleteList = [];
                $scope.notificationList.forEach(element => {
                    if (element.isChecked) {
                        $scope.DeleteList.push(element.NotificationID);
                    }
                });
                if ($scope.DeleteList.length == 0) {
                    $scope.errorMessageShow('Please select atleast 1 notification to delete.');
                    return false;
                } else {
                    var status = confirm("Are you sure, you want to delete notification?");
                    if (status) {
                        $scope.readAllNotification($scope.DeleteList);
                    }
                }
            }
            /**
             * Delete multiples notification
             */
        $scope.notificationSelect = {};
        $scope.notificationSelect.selectAll = false;
        $scope.readAllNotification = function(notification_ids) {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.NotificationIDs = notification_ids;
            $rootScope.loader.isLoading = false;
            $http.post($scope.env.api_url + 'notifications/deleteAll', $.param($data), contentType).then(function(response) {
                var response = response.data;
                $rootScope.loader.isLoading = true;
                if (response.ResponseCode == 200) {
                    $scope.notificationSelect.selectAll = false;
                    $scope.getNotifications();
                } else if (response.ResponseCode == 502) {
                    var toast = toastr.warning(response.Message, {
                        closeButton: true
                    });
                    toastr.refreshTimer(toast, 5000);
                    setTimeout(function() {
                        localStorage.clear();
                        window.location.reload();
                    }, 1000);
                }
            });
        }


        // $scope.activeTab = 'viaSms';
        // $scope.inviteTab = function (tab) {
        //     $scope.inviteSubmitted = false;
        //     $scope.activeTab = tab;
        // }

    } else {
        window.location.href = base_url;
    }
}]);