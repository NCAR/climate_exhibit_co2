angular.module("co2", ["co2.controllers", 
                           "co2.services",
                           "co2.directives",
                           "ngRoute"]).
config(["$routeProvider", function($routeProvider) {
  $routeProvider.
	when("/", 
         {
            templateUrl: "templates/homepage.html", 
            controller: "homeCtrl"
        }
    )
    .otherwise({redirectTo: '/'});
}]);