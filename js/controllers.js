/**
*  Controllers needed
*  1 for each graph
* a cron that will pull from the source file and generate a local tsv file
*
*
*
**/

angular.module('co2.controllers', [])
.controller('homeCtrl', function ($scope, contentData){
    $scope.file = "data/nwr.tsv";
    $scope.title = "CO2 at Niwot Ridge"

    // idea: use param to indicate which tsv file to use
});