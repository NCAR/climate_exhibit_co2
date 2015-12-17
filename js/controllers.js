angular.module('co2.controllers', [])
.controller('homeCtrl', function ($scope,$window){
    $scope.device_width = $window.innerWidth;
    $scope.graph_height = $window.innerHeight/3 - 70;
    // ipad pro resolution is 2048x2732, so max height of each, with nav is 682
})
.controller('nwrCtrl', function($scope, contentData){
    $scope.file = "data/nwr.tsv";
    $scope.title = "CO2 at Niwot Ridge (NWR)"
})
.controller('splCtrl', function($scope, contentData){
    $scope.file = "data/spl.tsv";
    $scope.title = "CO2 at Storm Peak Laboratory (SPL)"
})
.controller('mloCtrl', function($scope, contentData){
    $scope.file = "data/mlo.tsv";
    $scope.title = "Mauna Loa Observatory (MLO)"
});