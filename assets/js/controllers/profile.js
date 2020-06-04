'use strict';
app.controller('profileController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', 'toastr', 'Upload', '$timeout', function($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, toastr, Upload, $timeout) {
    $scope.env = environment;

    if ($localStorage.hasOwnProperty('user_details') && $localStorage.isLoggedIn == true) {
        $scope.user_details = $localStorage.user_details;
        $scope.isLoggedIn = $localStorage.isLoggedIn;

        /*Count 18 years from today- start*/
        var d = new Date();
        var year = d.getFullYear() - 18;
        $scope.date = d.setFullYear(year);
        $scope.dobValidate = d;

        var d1 = new Date();
        var minYear = d1.getFullYear() - 100;
        $scope.minDate = d1.setFullYear(minYear);
        $scope.minDateValidate = d1;
        /*Count 18 years from today- end*/

        /*function to get profile details*/
        $scope.profileDetails = {};
        $scope.getProfileInfo = function() {

                var $data = {};
                $data.UserGUID = $localStorage.user_details.UserGUID;
                $data.SessionKey = $localStorage.user_details.SessionKey;
                $data.Params = 'UserTypeID,UserTypeName,FirstName, MiddleName, LastName, Email, Username, Gender, BirthDate, CountryCode, CountryName, CityName, StateName, PhoneNumber,PhoneNumberForChange,Address,Company,ReferralCode,ProfilePic,TotalCash,Source';
                appDB
                    .callPostForm('users/getProfile', $data)
                    .then(
                        function successCallback(data) {
                            if ($scope.checkResponseCode(data)) {
                                $scope.profileDetails = data.Data;
                            }
                        },
                        function errorCallback(data) {
                            $scope.checkResponseCode(data);
                        });

            }
            /*function to update profile details*/

        $scope.submitted = false;
        $scope.updateProfile = function(form) {
            var $data = {};
            $scope.helpers = Mobiweb.helpers;
            $scope.submitted = true;
            if (!form.$valid) {
                return false;
            }
            $data = {
                FirstName: $scope.profileDetails.FirstName,
                BirthDate: $scope.profileDetails.BirthDate,
                Gender: $scope.profileDetails.Gender,
                CountryCode: $scope.profileDetails.CountryCode,
                StateName: $scope.profileDetails.StateName,
                CityName: $scope.profileDetails.CityName,
                Address: $scope.profileDetails.Address,
                SessionKey: $localStorage.user_details.SessionKey,
                Username: $scope.profileDetails.Username,
                Company: $scope.profileDetails.Company
            };
            appDB
                .callPostForm('users/updateUserInfo', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.profileDetails = data.Data;
                            $scope.getProfileInfo();
                            $scope.submitted = false;
                            $scope.successMessageShow(data.Message);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        /*change password*/
        $scope.updatePassword = [];
        $scope.changePassword = function(createform1) {

            $scope.helpers = Mobiweb.helpers;
            $scope.updateMsg = false;
            $scope.isSubmitted = true;
            if (!createform1.$valid) {
                return false;
            }
            var data = {};
            $scope.errorMsg = "";
            $scope.showMsg = false;
            data.SessionKey = $localStorage.user_details.SessionKey;
            data.CurrentPassword = $scope.CurrentPassword;
            data.Password = $scope.Password;
            $scope.isSubmitted = true;
            appDB
                .callPostForm('users/changePassword', data)
                .then(
                    function success(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.updatePassword = data.response;
                            $scope.successMessageShow(data.Message);
                        }
                    },
                    function error(data) {
                        $scope.checkResponseCode(data)
                    }
                );
        }

        /*update profile image*/
        $scope.$watch('picFile', function(files, old) {

            $scope.formUpload = false;
            if (files != null) {
                var elem = document.getElementById("myBar");
                var width = 1;
                var id = setInterval(frame, 10);

                $('#myProgress').css('display', 'block');

                function frame() {
                    if (width >= 100) {
                        clearInterval(id);
                    } else {
                        width++;
                        elem.style.width = width + '%';
                    }
                }
                var fd = new FormData();
                fd.append('SessionKey', $localStorage.user_details.SessionKey);
                fd.append('File', files);
                fd.append('Section', 'ProfilePic');
                fd.append('Caption', 'Profile Pic');
                appDB
                    .callPostImage('upload/image', fd)
                    .then(
                        function success(data) {
                            if ($scope.checkResponseCode(data)) {
                                $('#myProgress').css('display', 'none');
                                $localStorage.user_details.ProfilePic = data.Data.MediaURL;
                                $scope.getProfileInfo();
                            }
                        },
                        function error(data) {
                            $scope.checkResponseCode(data)
                        }
                    );

            }
        });

        /*function to get country list*/
        $scope.countryList = [];
        $scope.getCountryList = function() {

            var $data = {};
            appDB
                .callPostForm('utilities/getCountries', $data)
                .then(
                    function successCallback(data) {

                        if ($scope.checkResponseCode(data)) {
                            $scope.countryList = data.Data;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

        /*function to get states by country code*/
        $scope.stateList = [];
        $scope.getStates = function(CountryCode) {
            var $data = {};
            $data.CountryCode = CountryCode;
            appDB
                .callPostForm('utilities/getStates', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.stateList = data.Data;
                            $timeout(function() {
                                $(".selectpickerState").selectpicker('render');
                            }, 500);

                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data)
                    });
        }
        $scope.getStates('IN');
        /*remove profile pic*/
        $scope.removeProfilePic = function() {
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            $data.MediaGUID = '';
            appDB
                .callPostForm('upload/delete', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.successMessageShow(data.Message);
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }

       
      
        
        /*function to invite friend*/
        $scope.inviteField = {};

        $scope.inviteSubmitted = false;
        $scope.InviteFriend = function (form, ReferType) {
            console.log(form);
            $scope.inviteSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            var $data = {};
            $data.SessionKey = $localStorage.user_details.SessionKey;
            if (ReferType == 'Phone') {
                $data.PhoneNumber = $scope.inviteField.PhoneNumber;
            } else {
                $data.Email = $scope.inviteField.Email;
            }
            $data.ReferType = ReferType;
            appDB
                .callPostForm('users/referEarn', $data)
                .then(
                    function successCallback(data) {
                        if ($scope.checkResponseCode(data)) {
                            $scope.successMessageShow(data.Message);
                            $('.modal-header .close').click();
                            $scope.inviteField = {};
                            $scope.inviteSubmitted = false;
                        }
                    },
                    function errorCallback(data) {
                        $scope.checkResponseCode(data);
                    });
        }


        $scope.loadReffer = function (){
            if(getQueryStringValue('referral'))
            {
                $('#referFriend').modal({show:true});
            }
            var referral=getQueryStringValue('referral');
            console.log(referral);
        }
       
    } else {
        window.location.href = base_url;
    }

}]);