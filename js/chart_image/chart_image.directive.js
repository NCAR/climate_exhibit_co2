(function () {
    'use strict';
    angular.module('edu.ucar.scied.chart_image.directive', [])
        .directive('graphGenerator', graphGenerator);

    function graphGenerator() {
        var directiveDefinitionObject = {
            restrict: 'E',
            scope: true,
            replace: false,
            templateUrl: "js/chart_image/chart.html",
        };
        return directiveDefinitionObject;

    };
})();