angular.module('edu.ucar.scied.directives.co2', [])
    .directive('graphGenerator', function () {
        var directiveDefinitionObject = {
            restrict: 'E',
            scope: true,
            replace: false,
            templateUrl: "templates/chart.html",
        };
    return directiveDefinitionObject;

    });