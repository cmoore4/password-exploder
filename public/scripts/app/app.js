angular.module('PWX', ['ngRoute', 'ui.bootstrap', 'activeLink']);

angular.module('PWX').config(['$routeProvider', '$locationProvider', function ($routeProvider, $locationProvider) {
    $locationProvider.html5Mode(true);
    $routeProvider.when('/', {templateUrl: '/scripts/app/passwords/password-create.tpl.html', controller: 'PasswordCreateCtrl'});
    $routeProvider.when('/passwords', {templateUrl: '/scripts/app/passwords/password-create.tpl.html', controller: 'PasswordCreateCtrl'});
    $routeProvider.when('/passwords/new', {templateUrl: '/scripts/app/passwords/password-create.tpl.html', controller: 'PasswordCreateCtrl'});
    $routeProvider.when('/passwords/:id', {templateUrl: '/scripts/app/passwords/password-view.tpl.html', controller: 'PasswordViewCtrl'});
    //$routeProvider.when('/passwords/account', {templateUrl: '/scripts/app/passwords/view-account.tpl.html', controller: 'PasswordCtrl'});
    $routeProvider.when('/about', {templateUrl: '/scripts/app/layout/about.tpl.html', controller: 'AppCtrl'});
    $routeProvider.otherwise({redirectTo : '/'});
    //$routeProvider.otherwise({redirectTo:'/new'});
}]);

angular.module('PWX').controller('AppCtrl', ['$scope', '$location', '$route',
    function($scope, $location, $route){
        $scope.variable = "abcdef";
    }
]);