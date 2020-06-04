app.controller('HomeController', ['$scope', '$rootScope', '$location', 'environment', '$localStorage', '$sessionStorage', 'appDB', '$sce', 'toastr', 'socialLoginService', '$http', '$timeout', function($scope, $rootScope, $location, environment, $localStorage, $sessionStorage, appDB, $sce, toastr, socialLoginService, $http, $timeout) {
    $scope.env = environment;
    if (!$localStorage.hasOwnProperty('user_details')) {
        $scope.loginData = {};

        /*Login*/
        $scope.LoginSubmitted = false;
        $scope.signIn = function(form) {

            var $data = {};
            $scope.helpers = Mobiweb.helpers;
            $scope.login_error = false;
            $scope.login_message = ''; //login message
            $scope.LoginSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            $scope.loginData.Source = 'Direct';
            $scope.loginData.DeviceType = 'Native';
            var $data = $scope.loginData;
            appDB
                .callPostForm('signin', $data)
                .then(
                    function successCallback(data) {

                        if (data.ResponseCode == 200) {

                            $localStorage.user_details = data.Data;
                            $localStorage.isLoggedIn = true;
                            $sessionStorage.walletBalance = data.Data.WalletAmount;
                            $scope.loginData = {};
                            window.location.href = base_url + 'profile';
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
        $scope.formData = {};

        /*signUp*/
        $scope.signupSubmitted = false;
        $scope.signUp = function(form) {

            var $data = {};
            $scope.helpers = Mobiweb.helpers;
            $scope.signup_error = false;
            $scope.signup_message = ''; //login message
            $scope.signupSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            $scope.formData.UserTypeID = 2;
            $scope.formData.Source = 'Direct';
            $scope.formData.DeviceType = 'Native';
            if (getQueryStringValue('referral')) {
                $scope.formData.ReferralCode = getQueryStringValue('referral');
            }
            var data = $scope.formData;

            appDB
                .callPostForm('signup', data)
                .then(
                    function success(data) {

                        if (data.ResponseCode == 200) {
                            window.location.href = base_url;
                            var toast = toastr.success('Please check your email to verify account.');
                            toastr.refreshTimer(toast, 5000);
                            $scope.formData = {};
                            $scope.signupSubmitted = false;
                            $scope.LoginSubmitted = false;
                        }

                        if (data.ResponseCode == 500) {
                            var toast = toastr.warning(data.Message);
                            toastr.refreshTimer(toast, 5000);
                        }

                        if (data.ResponseCode == 501) {
                            var toast = toastr.error(data.Message);
                            toastr.refreshTimer(toast, 5000);
                        }

                    },
                    function error(data) {
                        if (typeof data == 'object') {

                            var toast = toastr.error(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);

                        }
                    });
        }




        /* send forgot password email */
        $scope.forgotPasswordData = {};
        $scope.forgotEmailSubmitted = false;
        $scope.sendEmailForgotPassword = function(form) {
            $scope.forgotEmailSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            $scope.data.listLoading = true;
            $scope.forgotPasswordData.type = ($scope.CheckEmail($scope.forgotPasswordData.Keyword)) ? 'Email' : 'Phone';
            var data = $scope.forgotPasswordData;
            appDB
                .callPostForm('recovery', data)
                .then(
                    function success(data) {
                        $scope.data.listLoading = false;
                        if (data.ResponseCode == 200) {
                            toastr.success(data.Message);
                            toastr.refreshTimer(toast, 5000);
                            $scope.forgotPasswordData = {};
                            $('.modal-header .close').click();
                            $('#changePassword').modal({
                                show: true
                            });
                        }

                        if (data.ResponseCode == 500) {
                            var toast = toastr.warning(data.Message);
                            toastr.refreshTimer(toast, 5000);
                        }

                        if (data.ResponseCode == 501) {
                            var toast = toastr.error(data.Message);
                            toastr.refreshTimer(toast, 5000);
                        }

                    },
                    function error(data) {
                        $scope.data.listLoading = false;
                        if (typeof data == 'object') {

                            var toast = toastr.error(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);

                        }
                    });
        }




        /* verify forgot password & create new password */
        $scope.forgotPassword = {};
        $scope.forgotPasswordSubmitted = false;
        $scope.verifyForgotPassword = function(form) {
            $scope.forgotPasswordSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            $scope.data.listLoading = true;
            var data = $scope.forgotPassword;
            appDB
                .callPostForm('recovery/setPassword', data)
                .then(
                    function success(data) {
                        $scope.data.listLoading = false;
                        if (data.ResponseCode == 200) {
                            $('.modal-header .close').click();
                            toastr.success(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);
                            $scope.forgotPassword = {};
                        }

                        if (data.ResponseCode == 500) {
                            var toast = toastr.warning(data.Message);
                            toastr.refreshTimer(toast, 5000);
                        }

                        if (data.ResponseCode == 501) {
                            var toast = toastr.error(data.Message);
                            toastr.refreshTimer(toast, 5000);
                        }

                    },
                    function error(data) {
                        $scope.data.listLoading = false;
                        if (typeof data == 'object') {

                            var toast = toastr.error(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);

                        }
                    });
        }



        // Check email
        $scope.CheckEmail = function(mail) {
            if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(mail)) {
                return true;
            } else {
                return false;
            }
        }


        /**
         * Verify mobile on signin
         */
        $scope.isSigninOtp = false;
        $scope.submittedSinginOTP = false;
        $scope.signinOTP = {};
        $scope.verifySigninOTP = function(form) {
            $scope.helpers = Mobiweb.helpers;
            $scope.submittedSinginOTP = true;
            if (!form.$valid) {
                return false;
            }
            var $data = {};
            if (!$scope.isSigninOtp) {
                $data.PhoneNumber = $scope.signinOTP.signinPhoneNumber;
                $data.SessionKey = $scope.signInUserInfo.SessionKey;
                appDB
                    .callPostForm('users/updateUserInfo', $data)
                    .then(
                        function success(data) {
                            if ($scope.checkResponseCode(data)) {
                                $('.modal-header .close').click();
                                $scope.isSigninOtp = true;

                            }
                        },
                        function error(data) {
                            $scope.checkResponseCode(data);
                        });
            } else {
                $data.OTP = $scope.signinOTP.OTP;
                $data.SessionKey = $scope.signInUserInfo.SessionKey;
                $data.DeviceType = 'Native';
                $data.Source = 'Direct';
                appDB
                    .callPostForm('signup/verifyPhoneNumber', $data)
                    .then(
                        function success(data) {
                            if ($scope.checkResponseCode(data)) {
                                $scope.isSigninOtp = false;
                                $scope.closePopup('verifyMobileOnSignin');
                                $scope.successMessageShow('Your account is verified');
                                $localStorage.user_details = $scope.signInUserInfo;
                                $localStorage.isLoggedIn = true;
                                $sessionStorage.walletBalance = $scope.signInUserInfo.WalletAmount;
                                window.location.href = base_url + 'lobby';
                            }
                        },
                        function error(data) {
                            $scope.checkResponseCode(data);
                        });
            }
        }




        $scope.downloadFormSubmitted = false;
        $scope.SendLink = function(form) {
            var $data = {};
            $scope.downloadFormSubmitted = true;
            if (!form.$valid) {
                return false;
            }
            $data = $scope.sendLinkForm;
            appDB
                .callPostForm('utilities/sendAppLink', $data)
                .then(
                    function successCallback(data) {
                        if (data.ResponseCode == 200) {
                            var toast = toastr.success(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);
                            $scope.sendLinkForm.PhoneNumber = '';
                            $scope.downloadFormSubmitted = false;
                        } else {
                            var toast = toastr.error(data.Message, {
                                closeButton: true
                            });
                            toastr.refreshTimer(toast, 5000);
                        }

                    },
                    function errorCallback(data) {
                        $scope.errorStatus = data.ResponseCode;
                        $scope.errorMessage = data.Message;
                    }
                );
        }

        /*Social Login*/
        // $scope.SocialLogin = function(Source) {

        //         $rootScope.$on('event:social-sign-in-success', function(event, userDetails) {
        //             var $data = {};
        //             $scope.formData = {};

        //             $scope.formData.UserTypeID = 2;
        //             $scope.formData.Source = Source;
        //             $scope.formData.Password = userDetails.uid;
        //             $scope.formData.DeviceType = 'Native';
        //             var $data = $scope.formData;
        //             appDB
        //                 .callPostForm('signin', $data)
        //                 .then(
        //                     function successCallback(data) {

        //                         if (data.ResponseCode == 200) {
        //                             $localStorage.user_details = data.Data;
        //                             $localStorage.isLoggedIn = true;
        //                             $localStorage.SocialLogin = true;
        //                             $sessionStorage.walletBalance = data.Data.WalletAmount;
        //                             $scope.loginData = {};

        //                             window.location.href = base_url + 'lobby';
        //                         }
        //                         if (data.ResponseCode == 500) {
        //                             var $data = {};
        //                             delete $scope.formData;
        //                             $scope.formData = {};

        //                             $scope.formData.UserTypeID = 2;
        //                             $scope.formData.Source = Source;
        //                             $scope.formData.SourceGUID = userDetails.uid;
        //                             $scope.formData.FirstName = userDetails.name;
        //                             $scope.formData.DeviceType = 'Native';
        //                             $scope.formData.Email = userDetails.email;
        //                             var $data = $scope.formData;
        //                             appDB
        //                                 .callPostForm('signup', $data)
        //                                 .then(
        //                                     function success(data) {
        //                                         if (data.ResponseCode == 200) {
        //                                             $localStorage.SocialLogin = true;
        //                                             $localStorage.user_details = data.Data;
        //                                             $localStorage.isLoggedIn = true;
        //                                             $sessionStorage.walletBalance = data.Data.WalletAmount;

        //                                             window.location.href = base_url + 'lobby';
        //                                         }

        //                                         if (data.ResponseCode == 500) {
        //                                             var toast = toastr.warning(data.Message);
        //                                             toastr.refreshTimer(toast, 5000);
        //                                         }

        //                                         if (data.ResponseCode == 501) {
        //                                             var toast = toastr.error(data.Message);
        //                                             toastr.refreshTimer(toast, 5000);
        //                                         }

        //                                     },
        //                                     function error(data) {
        //                                         if (typeof data == 'object') {

        //                                             var toast = toastr.error(data.Message, {
        //                                                 closeButton: true
        //                                             });
        //                                             toastr.refreshTimer(toast, 5000);

        //                                         }
        //                                     });
        //                         }
        //                     },
        //                     function errorCallback(data) {
        //                         delete $scope.formData;
        //                         var $data = {};
        //                         $scope.formData = {};
        //                         $scope.formData.UserTypeID = 2;
        //                         $scope.formData.Source = Source;
        //                         $scope.formData.SourceGUID = userDetails.uid;
        //                         $scope.formData.FirstName = userDetails.name;
        //                         $scope.formData.DeviceType = 'Native';
        //                         $scope.formData.Email = userDetails.email;
        //                         var $data = $scope.formData;

        //                         appDB
        //                             .callPostForm('signup', $data)
        //                             .then(
        //                                 function success(data) {
        //                                     if (data.ResponseCode == 200) {
        //                                         $localStorage.user_details = data.Data;
        //                                         $localStorage.isLoggedIn = true;
        //                                         $sessionStorage.walletBalance = data.Data.WalletAmount;
        //                                         window.location.href = base_url + 'lobby';
        //                                     }

        //                                     if (data.ResponseCode == 500) {
        //                                         var toast = toastr.warning(data.Message);
        //                                         toastr.refreshTimer(toast, 5000);
        //                                     }

        //                                     if (data.ResponseCode == 501) {
        //                                         var toast = toastr.error(data.Message);
        //                                         toastr.refreshTimer(toast, 5000);
        //                                     }

        //                                 },
        //                                 function error(data) {
        //                                     if (typeof data == 'object') {

        //                                         var toast = toastr.error(data.Message, {
        //                                             closeButton: true
        //                                         });
        //                                         toastr.refreshTimer(toast, 5000);

        //                                     }
        //                                 });
        //                     });

        //         });
        //     }
        /**
         * Get Testimonials lists
         */
        $scope.Testimonials = [];
        $scope.testimonial_silder_visible = false;
        $scope.getTestimonials = function() {
                var $data = {};
                $data.PostType = 'Testimonial';
                appDB
                    .callPostForm('utilities/getPosts', $data)
                    .then(
                        function success(data) {
                            if (data.ResponseCode == 200) {
                                $scope.Testimonials = data.Data.Records;
                                $scope.testimonial_silder_visible = true;
                            }
                        },
                        function error(data) {});
            }
            /**
             * redirect to login/signup pages
             */
        $scope.goToLogin = function(PageName) {
            $localStorage.loginPage = PageName;
            window.location.href = base_url + 'authenticate';
        }
    } else {
        window.location.href = base_url + 'profile';
    }
}]);

app.directive('slickCustomCarousel', ["$timeout", function($timeout) {
    return {
        restrict: "A",
        link: {
            post: function(scope, elem, attr) {
                $timeout(function() {
                    $('.slider').slick({
                        dots: false,
                        infinite: false,
                        speed: 300,
                        slidesToShow: 3,
                        slidesToScroll: 3,
                        responsive: [{
                                breakpoint: 1024,
                                settings: {
                                    slidesToShow: 3,
                                    slidesToScroll: 3,
                                    infinite: true,
                                    dots: true
                                }
                            },
                            {
                                breakpoint: 768,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 2
                                }
                            },
                            {
                                breakpoint: 480,
                                settings: {
                                    slidesToShow: 1,
                                    slidesToScroll: 1
                                }
                            }
                        ]
                    });
                }, 1);

            }
        }
    }
}]);
app.directive('testimonialSlider', ["$timeout", function($timeout) {
    return {
        restrict: "A",
        link: {
            post: function(scope, elem, attr) {
                $timeout(function() {
                    $('#clientSlider').slick({
                        dots: false,
                        infinite: false,
                        speed: 300,
                        slidesToShow: 3,
                        slidesToScroll: 3,
                        responsive: [{
                                breakpoint: 1024,
                                settings: {
                                    slidesToShow: 3,
                                    slidesToScroll: 3,
                                    infinite: true,
                                    dots: true
                                }
                            },
                            {
                                breakpoint: 768,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 2
                                }
                            },
                            {
                                breakpoint: 480,
                                settings: {
                                    slidesToShow: 1,
                                    slidesToScroll: 1
                                }
                            }
                        ]
                    });

                }, 1);

            }
        }
    }
}]);