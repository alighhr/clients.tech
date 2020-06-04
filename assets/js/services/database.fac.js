'use strict';
app.factory('appDB', ['$http', '$q', '$rootScope', 'environment', appDB]);
function appDB($http, $q, $rootScope, environment) {
    var serializeData = function (data) {
        // If this is not an object, defer to native stringification.
        if (!angular.isObject(data)) {
            return ((data == null) ? "" : data.toString());
        }
        var buffer = [];
        // Serialize each key in the object.
        for (var name in data) {
            if (!data.hasOwnProperty(name)) {
                continue;
            }
            var value = data[name];
            buffer.push(
                encodeURIComponent(name) +
                "=" +
                encodeURIComponent((value == null) ? "" : value)
            );
        }
        // Serialize the buffer and clean it up for transportation.
        var source = buffer
            .join("&")
            .replace(/%20/g, "+");

        return (source);
    }
    return {
        callPostJSON: function (url, data) {
            return $q(function (resolve, reject) {
                url = environment.api_url + url;
                $http({
                    method: 'POST',
                    url: url,
                    // withCredentials : true,
                    // headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: data
                }).then(function successCallback(response) {
                    if (response.data.status) {
                        resolve(response.data)
                    }
                    else {
                        reject(response.data)
                    }
                }, function errorCallback(response) {
                    reject(response.data)
                })
            })
        },
        callPostForm: function (url, data) {
            return $q(function (resolve, reject) {
                url = environment.api_url + url;
                data = serializeData(data);
                $http({
                    method: 'POST',
                    url: url,
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8' },
                    data: data
                }).then(function successCallback(response) {
                    if (response.data.ResponseCode == 200) {
                        resolve(response.data)
                    }else {
                        reject(response.data)
                    }
                }, function errorCallback(response) {
                    reject(response.data)
                })
            })
        },
        callPostImage: function (url, data) {
            return $q(function (resolve, reject) {
                url = environment.api_url + url;
                $http({
                    method: 'POST',
                    url: url,
                    data: data,
                    transformRequest: angular.identity,
                    headers: { 'Content-Type': undefined },
                }).then(function successCallback(response) {
                    if (response.data.ResponseCode == 200) {
                        resolve(response.data)
                    }
                    else {
                        reject(response.data)
                    }
                }, function errorCallback(response) {
                    reject(response.data)
                })
            })
        },
        callGet: function (url, data) {
            return $q(function (resolve, reject) {
                url = environment.api_url + url;
                $http({
                    method: 'GET',
                    url: url,
                    // withCredentials : true,
                    // headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: data
                }).then(function successCallback(response) {
                    if (response.data.status) {
                        resolve(response.data)
                    }else {
                        reject(response.data)
                    }
                }, function errorCallback(response) {
                    reject(response.data)
                })
            })
        },
        callPostPayTm: function (url, data) {
            return $q(function (resolve, reject) {
                url = url;
                data = serializeData(data);
                $http({
                    method: 'POST',
                    url: url,
                    headers: { 'Content-Type': 'text/html' },
                    data: data
                }).then(function successCallback(response) {
                    if (response.data.status) {
                        resolve(response.data)
                    } else {
                        reject(response.data)
                    }
                }, function errorCallback(response) {
                    reject(response.data)
                })
            })
        },
        callPostImageDemo: function (url, data) {
            return $q(function (resolve, reject) {
                url = environment.api_url + url;
                $http({
                    method: 'POST',
                    url: url,
                    data: data,
                    transformRequest: angular.identity,
                    headers: { 'Content-Type': undefined },
                }).then(
                    function beforeSend(progressBar, bar, percent) {
                        progressBar.fadeIn();
                        var percentVal = '0%';
                        bar.width(percentVal)
                        percent.html(percentVal);
                    },
                    function uploadProgress(event, position, total, percentComplete) {
                        var percentVal = percentComplete + '%';
                        bar.width(percentVal)
                        percent.html(percentVal);
                    },
                    function successCallback(html, statusText, xhr, response) {
                        if (response.data.status) {
                            obj = $.parseJSON(html);
                            if (obj.status) {
                                var percentVal = '100%';
                                bar.width(percentVal)
                                percent.html(percentVal);
                                resolve(response.data)
                                $("#imgArea>img").prop('src', obj.image_medium);
                            } else {
                                reject(response.data)
                            }
                        }else {
                            reject(response.data)
                        }
                    },
                    function complete(xhr) {
                        progressBar.fadeOut();
                    },
                    function errorCallback(response) {
                        reject(response.data)
                    })
            })
        },
    }
}

