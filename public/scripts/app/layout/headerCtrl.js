angular.module('PWX').controller('HeaderCtrl', ['$scope', '$location', '$route',
    function($scope, $location, $route){
        $scope.dropdown = [
            'New Password',
            'Recent Passwords',
            'Account'
        ];
    }
]);