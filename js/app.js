(function () {
    'use strict';
    angular.module("edu.ucar.scied.co2", ["edu.ucar.scied.controllers",
                                      "edu.ucar.scied.services",
                                      "edu.ucar.scied.services.webapp",
                                      "edu.ucar.scied.filters",
                                      "edu.ucar.scied.directives.modal",
                                      "edu.ucar.scied.controllers.co2",
                                      "edu.ucar.scied.services",
                                      "edu.ucar.scied.directives.co2",
                                      "ngMaterial",
                                      "ngRoute"
    ]).
    config(["$routeProvider", function ($routeProvider) {
        $routeProvider.
        when("/", {
                templateUrl: "templates/homepage.html",
                controller: "homeCtrl"
            })
            .otherwise({
                redirectTo: '/'
            });
    }]);
})();