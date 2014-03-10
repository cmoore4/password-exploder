angular.module('PWX').controller('PasswordCreateCtrl', ['$scope', '$location', '$route', '$http', '$interval',
    function($scope, $location, $route, $http, $interval){

        // This is the object that will be sent to the server
        $scope.password = {
            'password': null,
            'user': null,
            'note': null,
            'expireDate': null,
            'viewLimit': 0,
            'ipRestrictions': null,
            'accountId': null,
            'useAcctPassword': false,
            'notifications': null
        };

        $scope.minutes = 0;
        $scope.minutesTimer = $interval(function(){ $scope.minutes -= 1;}, 60000);

        $scope.errorMsg = null;
        $scope.submitting = false;

        $scope.updateDate = function(date){
            if(date === null || date === undefined){
                date = $scope.displayDate;
            }

            if(typeof date != 'date'){
                date = new Date(Date.parse(date));
            }

            // Unix time date is passed back to the server
            $scope.password.expireDate = Math.round(date.getTime() / 1000);

            // Locale Date is displayed to the user
            $scope.displayDate = date.toLocaleString();

            // Help text for how many minutes from when the date was set to when the date is for (10 minutes from now)
            // This should set a timer to refresh
            $scope.minutes =  Math.round(Math.abs(date.getTime() - (new Date()).getTime()) / 1000 / 60);

            // Clear the countdown timer, then set up a new countdown timer.  This is an approximate timer, which only
            // fires once a minute instead of once a second.
            $interval.cancel($scope.minutesTimer);
            $scope.minutesTimer = $interval(function(){ $scope.minutes -= 1;}, 60000);
        };

        $scope.$on('$destroy', function(e) {
            $interval.cancel($scope.minutesTimer);
        });

        $scope.setTime = function(seconds){
            var date = new Date();
            date.setSeconds(date.getSeconds() + seconds);
            $scope.updateDate(date);
        };

        // Generate a secure password of a certain length
        $scope.getRandomPassword = function (len){

            // Characters to include in the generator
            var allChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()-_+=~<>;";
            var pwd = '';

            for(var i=0; i < len; i++){
                var idx = Math.floor(Math.random()*allChars.length-1);
                pwd += allChars.substring(idx,idx+1);
            }

            return pwd;
        };

        $scope.createPassword = function(){

            $scope.errorMsg = null;
            $scope.submitting = true;

            $http.post('/api/passwords',$scope.password).success(
                function(data){
                    $scope.submitting = false;
                    $location.path('/passwords/' + data.id);
                }
            ).error(
                function(data){
                    $scope.submitting = false;
                    $scope.errorMsg = data.error;
                    if(!$scope.errorMsg){
                        $scope.errorMsg = "An unexpected error occurred on the server!  Ack!";
                    }
                }
            );
        };

        // Sets up the dates on controller load
        var initDate = new Date();
        initDate.setSeconds(initDate.getSeconds() + 600);
        $scope.updateDate(initDate);
    }
]);
