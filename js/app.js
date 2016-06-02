(function () {
    'use strict';
    angular.module("edu.ucar.scied.co2", [
        "edu.ucar.scied.co2.controller",
        "edu.ucar.scied.chart_image.directive",
        "edu.ucar.scied.webapp.controller",
        "edu.ucar.scied.webapp.service",
        "edu.ucar.scied.modal.directive",
        "edu.ucar.scied.filters",
        "edu.ucar.scied.services",
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