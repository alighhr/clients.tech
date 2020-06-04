app.directive('customScroll', function () {
    return {
        restrict: 'A',
        scope: {
            config: '&customScroll'
        },
        link: function postLink(scope, iElement, iAttrs, controller, transcludeFn) {
            // create scroll elemnt
            var elem = iElement.mCustomScrollbar();
            // the live options object
            var mObject = elem.data('mCS_1');
            // $log.debug('elem: ', mObject.opt);
        }
    };
});
app.directive('onePageScroll', ['$window', function ($window) {
        return {
            restrict: 'A',
            link: function (scope, element, attribute) {
                scope.scrollTo = function (id) {
                    angular.element('html,body').animate({
                        scrollTop: angular.element('#' + id).offset().top - 60
                    }, 1000);
                }
            }
        };
    }]);
app.directive('counter_num', function () {
    return {
        restrict: 'C',
        link: function postLink(scope, iElement, iAttrs, controller, transcludeFn) {
            // create scroll elemnt
            var elem = iElement.counterUp({
                delay: 10,
                time: 1500,
            });
        }
    };
});
app.directive('animsition', function () {
    return {
        restrict: 'C',
        link: function postLink(scope, iElement, iAttrs, controller, transcludeFn) {
            // create scroll elemnt
            var elem = iElement.animsition({
                inClass: 'fade-in',
                outClass: 'fade-out',
                inDuration: 1500,
                outDuration: 800,
                linkElement: '.animsition-link',
                // e.g. linkElement: 'a:not([target="_blank"]):not([href^=#])'
                loading: true,
                loadingParentElement: 'body', //animsition wrapper element
                loadingClass: 'animsition-loading',
                loadingInner: '', // e.g '<img src="loading.svg" />'
                timeout: false,
                timeoutCountdown: 5000,
                onLoadEvent: true,
                browser: ['animation-duration', '-webkit-animation-duration'],
                // "browser" option allows you to disable the "animsition" in case the css property in the array is not supported by your browser.
                // The default setting is to disable the "animsition" in a browser that does not support "animation-duration".
                overlay: false,
                overlayClass: 'animsition-overlay-slide',
                overlayParentElement: 'body',
                transition: function (url) {
                    window.location.href = url;
                }
            });
        }
    };
});
app.directive('popupHandler', function () {
    return {restrict: 'A', link: function ($scope, element) {
            $scope.closePopup = function (id) {
                if ($('#' + id).find('.close').length > 0) {
                    $('#' + id).trigger('click');
                } else {
                    $('#' + id).remove();
                }
                if ($('.modal-backdrop').length > 0) {
                    $('.modal-backdrop').remove();
                }
                $('body').removeClass('modal-open').css('padding-right', "");
            };
            $scope.openPopup = function (id) {
                var popup = $('#' + id);
                $('#' + id).modal('show');
            };
        },
    }
});
app.directive('dropdownHandler', function () {
    return{restrict: 'A', link: function ($scope, element)
        {
            $scope.handleDropDownMenu = function (e) {
                var _that = $(e.target).closest('li');
                if (_that.find('i').length > 0)
                {
                    if (_that.find('i').first().hasClass('fa-angle-down'))
                    {
                        _that.find('i').first().removeClass('fa-angle-down').addClass('fa-angle-up');
                    } else
                    {
                        _that.find('i').first().removeClass('fa-angle-down').addClass('fa-angle-up');
                    }
                }

                _that.find('.dropdown-menu').toggle();
            }
        }
    }
});
app.directive('justClick', function () {
    return{restrict: 'A', link: function ($scope, element)
        {
            setTimeout(function () {
                var pwd = $(element).find('input[type=password]').focus();
            }, 2000);
        }
    }
});
app.directive('loading', ['$http', function ($http)
    {
        return {
            restrict: 'A',
            template: '',
            link: function (scope, elm, attrs)
            {
                scope.isLoading = function () {
                    return $http.pendingRequests.length > 0;
                };
                scope.showLoader = function () {
                    elm.show();
                }
                scope.hideLoader = function () {
                    elm.hide();
                }
                scope.$watch(scope.isLoading, function (v)
                {
                    if(scope.loader){
                        if(scope.loader.isLoading == false){
                            elm.hide();
                        }else{
                            if (v) {
                                elm.show();
                            } else {
                                elm.hide();
                            }
                        }
                       
                    }else{
                        if (v) {
                            elm.show();
                        } else {
                            elm.hide();
                        }
                    }
                    
                });
            }
        };
    }])
app.directive('processHolder', function ()
{
    return {
        restrict: 'A',
        template: '',
        link: function (scope, elm, attrs)
        {
            scope.showLoaderBarrier = function () {
                elm.show();
            }
            scope.hideLoaderBarrier = function () {
                elm.hide();
            }
        }
    };
})
app.directive('validNumber', function () {
    return {
        require: '?ngModel',
        link: function (scope, element, attrs, ngModelCtrl) {
            if (!ngModelCtrl) {
                return;
            }
            ngModelCtrl.$parsers.push(function (val) {
                if (angular.isUndefined(val)) {
                    var val = '';
                }
                var clean = val.replace(/[^-0-9\.]/g, '');
                var negativeCheck = clean.split('-');
                var decimalCheck = clean.split('.');
                if (!angular.isUndefined(negativeCheck[1])) {
                    negativeCheck[1] = negativeCheck[1].slice(0, negativeCheck[1].length);
                    clean = negativeCheck[0] + '-' + negativeCheck[1];
                    if (negativeCheck[0].length > 0) {
                        clean = negativeCheck[0];
                    }
                }
                if (!angular.isUndefined(decimalCheck[1])) {
                    decimalCheck[1] = decimalCheck[1].slice(0, 2);
                    clean = decimalCheck[0] + '.' + decimalCheck[1];
                }
                if (val !== clean) {
                    ngModelCtrl.$setViewValue(clean);
                    ngModelCtrl.$render();
                }
                return clean;
            });
            element.bind('keypress', function (event) {
                if (event.keyCode === 32) {
                    event.preventDefault();
                }
            });
        }
    };
});
app.directive('datepickercustom', function () {
    return {
        restrict: 'E',
        'scope': {
            'dateSet': '@',
            'dateMinLimit': '@',
            'dateMaxLimit': '@',
            'dateMonthTitle': '@',
            'dateYearTitle': '@',
            'buttonNextTitle': '@',
            'buttonPrevTitle': '@',
            'dateDisabledDates': '@',
            'dateEnabledDates': '@',
            'dateDisabledWeekdays': '@',
            'dateSetHidden': '@',
            'dateTyper': '@',
            'dateWeekStartDay': '@',
            'datepickerAppendTo': '@',
            'datepickerToggle': '@',
            'datepickerClass': '@',
            'datepickerShow': '@',
            'dateFormat': '@',
            'datepickerId': '@'
        },
        link: function (scope, element, attributes) {
            /*Mobile Menu Handle*/
            element.find('#' + scope.datepickerId).datepicker({
                changeMonth: true,
                changeYear: true,
                maxDate: new Date(scope.dateMaxLimit),
                dateFormat: scope.dateFormat
            });

        }
    }

});

app.directive('jqdatepicker', function () {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function (scope, element, attrs, ngModelCtrl) {
            element.datepicker({
                changeMonth: true,
                changeYear: true,
                maxDate: '0',
                // dateFormat: 'yy-mm-dd',
                dateFormat: 'dd-mm-yy',
                onSelect: function (date) {
                    let nameAttr = attrs.searchfield;
                    if (nameAttr === 'fromDate') {
                        scope.fromDate = date;
                    } else if (nameAttr === 'toDate') {
                        scope.toDate = date;
                    }
                    scope.$apply();
                }
            });
        }
    };
});

app.directive('testimonSlider', function (environment) {
    return {
        restrict: 'A',
        link: function (scope, element, attribute) {
            scope.env = environment;
            $(".testimonSliderPar").slick({
                infinite: true,
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: false,
                arrows: true,
            });
        }
    }
});

app.directive('numbersOnly', function () {
    return {
        require: 'ngModel',
        link: function (scope, element, attr, ngModelCtrl) {
            function fromUser(text) {
                if (text) {
                    var transformedInput = text.replace(/[^0-9]/g, '');

                    if (transformedInput !== text) {
                        ngModelCtrl.$setViewValue(transformedInput);
                        ngModelCtrl.$render();
                    }
                    return transformedInput;
                }
                return undefined;
            }
            ngModelCtrl.$parsers.push(fromUser);
        }
    };
});


app.directive('timerText', ["$interval", "$filter", function ($interval, $filter) {
        return {
            restrict: 'A',
            link: function (scope, el, attrs) {


                var tick = function () {
                    // date = scope.matches.MatchStartDateTime;

                    date = attrs.timerData;
                    status = attrs.matchStatus;
                    type = attrs.matchType;

                    var date1 = date;
                    date1 =  $filter('convertIntoUserTimeZone')(date1);
                    var date3 = new Date(date1);
                    var date2 = new Date();
                    var date4 = $filter('date')(date1, "medium");
                    if (date3 < date2 && status == 'Completed') {
                        scope.clock = '<span class="text-success">Completed</span>';
                        return false;
                    } else if (date3 < date2 && status == 'Running') {
                        scope.clock = '<span class="text-success">Running</span>';
                        return false;
                    } else if (date3 < date2 && status == 'Pending') {
                        scope.clock = '<span class="text-success">'+date4+'</span>';
                        return false;
                    } else {
                        // var diffInSeconds = Math.abs(date1 - date2) / 1000;
                        var diffInSeconds = Math.abs(moment().diff(date1) / 1000);
                        var days = Math.floor(diffInSeconds / 60 / 60 / 24);
                        var hours = Math.floor(diffInSeconds / 60 / 60 % 24);
                        var minutes = Math.floor(diffInSeconds / 60 % 60);
                        var seconds = Math.floor(diffInSeconds % 60);
                        days = days.toString();

                        minutes = minutes.toString();
                        hours = hours.toString();
                        seconds = seconds.toString();

                        if (type == 'createTeam' && type != undefined) { 
                            var showTime = '';
                            if(days >= 2){
                                showTime = days+'days left';
                            }else if (days <= 2 && days != 0) { 
                                showTime = days +' days left';
                                hours = Number(days*24) + Number(hours);
                                showTime = hours+'h left';
                            }else if(hours <= 24 && hours > 0){ 
                                if (minutes.length == 1) {
                                    minutes = '0' + minutes;
                                }
                                showTime =  hours+'h '+minutes+'m left';
                            }else if(minutes > 0 && hours == 0){ 
                                if (minutes.length == 1) {
                                    minutes = '0' + minutes;
                                }
                                if (seconds.length == 1) {
                                    seconds = '0' + seconds;
                                }
                                showTime = minutes+'m '+seconds +'s left';
                            }else if(minutes == 0){
                                if (seconds.length == 1) {
                                    seconds = '0' + seconds;
                                }
                                showTime = seconds+'s left';
                            }
                            scope.clock = showTime;
                        } else {
                            if (days.length == 1) {
                                days = '0' + days;
                            }
                            days = '<span>' + days + ' <strong>DAY</strong></span>';
                            if (minutes.length == 1) {
                                minutes = '0' + minutes;
                            }
                            minutes = '<span>' + minutes + ' <strong>MIN</strong></span>';
                            if (seconds.length == 1) {
                                seconds = '0' + seconds;
                            }
                            seconds = '<span>' + seconds + ' <strong>SEC</strong></span>';
                            if (hours.length == 1) {
                                hours = '0' + hours;
                            }
                            hours = '<span>' + hours + ' <strong>HRS</strong></span>';
                            var milliseconds = Math.round((diffInSeconds - Math.floor(diffInSeconds)) * 1000);
                            scope.clock = days + hours + minutes + seconds;
                        }


                    }

                }
                tick();
                $interval(tick, 1000);
            }
        }
    }]);

app.filter('trustAsHtml', ['$sce', function ($sce) {
        return function (input) {
            return $sce.trustAsHtml(input);
        };
    }]);
app.filter('secondsToDateTime', [function () {
        return function (seconds) {
            return new Date(1970, 0, 1).setSeconds(seconds);
        };
    }])

app.directive('scrolly', function ($location) {
    return {
        restrict: 'A',
        scope: true,
        link: function (scope, element, attrs) {
            var raw = element[0];
            var location = $location.absUrl();
            var location_array = location.split('/');
            var page_name = location_array[location_array.length - 1];
            element.bind('scroll', function () {
                if (raw.scrollTop + raw.offsetHeight >= raw.scrollHeight) {
                    if (page_name == 'lobby') {
                        scope.JoinedContest(false);
                        scope.getContests(false);
                    } else if (page_name == 'leagueCenter') {
                        scope.LeagueCenter(false);
                    } else if (page_name.includes('showContest')) {
                        scope.JoinedContest(false);
                    } else if (page_name.includes('league')) {
                        scope.getUserTeam(false);
                    } else if (page_name == 'auction') {
                        scope.JoinedContest(false);
                        scope.getContests(false);
                    } else if (page_name.includes('createAuctionTeam')) {
                        scope.getBidHistory(false);
                    } else if(page_name == 'myAccount'){ 
                        scope.getAccountInfo(false);
                        scope.getWithdrawals(false);
                    }else if(page_name.includes('showDraftContest')){
                        scope.JoinedContest(false);
                    }else if(page_name == 'draftLeagueCenter'){
                        scope.LeagueCenter(false);
                        scope.getContests(false);
                    }else if(page_name == 'auctionLeagueCenter'){
                        scope.LeagueCenter(false);
                        scope.getContests(false);
                    }else if(page_name.includes('showAuctionContest')){
                        scope.JoinedContest(false);
                    }
                    scope.$apply(attrs.scrolly);
                }
            });
        }
    };
});

app.filter('myDateFormat', function myDateFormat($filter){
  return function(text){
    var  tempdate= new Date(text.replace(/-/g,"/"));
    return $filter('date')(tempdate, "medium");
  }
});

app.filter('convertIntoUserTimeZone', function() {
  return function(input) {
      var offset    = new Date().getTimezoneOffset();
      offset    = offset.toString();
      var plusSign  = offset.indexOf("+");
      var minusSign = offset.indexOf("-");
      var timeZoneObj  = {};
      timeZoneObj.offset = offset;
      if(plusSign > -1){
          timeZoneObj.identifire   = "-";
          timeZoneObj.totalMinutes = parseInt(offset.replace("+",""));
      }else if(minusSign > -1){
          timeZoneObj.identifire = "+";
          timeZoneObj.totalMinutes = parseInt(offset.replace("-",""));
      }else{
          timeZoneObj.identifire = "-";
          timeZoneObj.totalMinutes = parseInt(offset);
      }
      let totalMinutes = timeZoneObj.totalMinutes;
      let totalHours   = parseInt(totalMinutes/60);
      let hourMinutes  = 60 * totalHours;
      let reaminingMinutes = totalMinutes - hourMinutes;
      timeZoneObj.totalHours  = totalHours;
      timeZoneObj.hourMinutes = hourMinutes;
      timeZoneObj.reaminingMinutes = reaminingMinutes;
      timeZoneObj.finalTimeZoneFormatted = ((totalHours > 10) ? totalHours : "0" + totalHours)
      let identifire   = timeZoneObj.identifire;
      totalMinutes = timeZoneObj.totalMinutes;
      var utcTime      = '';
      if (identifire === '+') {
          utcTime = moment(input).add(totalMinutes, 'minutes');
      } else {
          utcTime = moment(input).subtract(totalMinutes, 'minutes');
      }
      utcTime = moment(utcTime).format("LLL"); // March 19, 2018 4:04 PM
      return utcTime;
  }
});
app.filter('CheckPlayerPriority', function CheckPlayerPriority($filter){
    return function(text,min){
      return text.filter(function(item){
        return item.value == min || !item.status;
      });
    }
  });


app.directive('onErrorSrc', function() {
    return {
        link: function(scope, element, attrs) {
          element.bind('error', function() {
            if (attrs.src != attrs.onErrorSrc) {
              attrs.$set('src', attrs.onErrorSrc);
            }
          });
        }
    }
});