(function () {
    'use strict';
    angular.module('edu.ucar.scied.directives.co2', [])
        .directive('graphGenerator', graphGenerator);

    function graphGenerator() {
        var directiveDefinitionObject = {
            restrict: 'E',
            scope: true,
            replace: false,
            templateUrl: "templates/chart.html",
        };
        return directiveDefinitionObject;

    };
})();