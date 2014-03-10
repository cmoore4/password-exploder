// https://stackoverflow.com/a/12631074
// Keeps track of the active link in the menu bar by comparing the clicked link to the current path
// Use: <li active-link="active"><a href="#/about">About</a></li> <li active-link="active"><a href="#/contact">Contact</a></li>
angular.module('activeLink', []).
    directive('activeLink', ['$location', function(location) {
        return {
            restrict: 'A',
            link: function(scope, element, attrs, controller) {

                // Class to apply, specified by html active-link attr
                var clazz = attrs.activeLink;

                // hack for bootstrap, li is clicked, a is child, remove #
                var path = $(element).children("a")[0].hash.substring(1);

                scope.location = location;

                //When path changes, remove all active links, set on element path that matches
                scope.$watch('location.path()', function(newPath) {
                    if (path === newPath) {
                        element.addClass(clazz);
                    } else {
                        element.removeClass(clazz);
                    }
                });
            }
        };
    }]);