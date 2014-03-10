angular.module('PWX').controller('PasswordViewCtrl', ['$scope', '$location', '$route', '$http', '$routeParams', '$interval',
	function($scope, $location, $route, $http, $routeParams, $interval){

		$scope.password = {};
		$scope.err = false;

      $scope.minutes = 0;
      $scope.minutesTimer = $interval(function(){$scope.minutes -= 1;}, 60000);

      $scope.deletePassword = function(){
      	$http({method: 'DELETE', url: '/api/passwords/' + $routeParams.id}).success(
      		function(){
      			$location.path('/');
      		}
      	).error(
      		function(){
      			$scope.err = "Could not delete password.  May already have been deleted or expired.";
      		}
      	);
      }

		$http({method: 'GET', url: '/api/passwords/' + $routeParams.id}).success(
			function(data){
				$scope.password = data;

                //save Unix time for the countdown timer
                var originalTime = data.expiration * 1000;

                //set up display expiration date
                var expire = new Date();
                expire.setTime($scope.password.expiration * 1000);
                $scope.password.expiration = expire.toLocaleString();

               // countdown til expiration
                $scope.minutes =  Math.round(Math.abs(originalTime - (new Date()).getTime()) / 1000 / 60);
                $interval.cancel($scope.minutesTimer);
                $scope.minutesTimer = $interval(function(){ $scope.minutes -= 1;}, 60000);

				// Every five seconds, grab updated view count
				$scope.updateViews = setInterval(function(){
					$http({method: 'GET', url: '/api/passwords/' + data.id + '/views'}).
					success(
						function(data){
							$scope.password.viewcount = data.count;
						}
					).
					error(
						function(data){
							$scope.err = data.err;
                            clearInterval($scope.updateViews);
						}
					);
				}, 5000);
			}
        ).
        error(
            function(data){
                $scope.err = data.error;
            }
        );
	}
]);
