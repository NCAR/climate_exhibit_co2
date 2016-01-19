angular.module('co2.controllers', [])
.controller('homeCtrl', function ($scope,$window){
    $scope.device_width = $window.innerWidth;
    $scope.graph_height = $window.innerHeight/3;
    // ipad pro resolution is 2048x2732, so max height of each, with nav is 682
})
.controller('nwrCtrl', function($scope, contentData){
    $scope.file = "data/nwr.tsv";
    $scope.title = "Niwot Ridge (NWR)"
    $scope.bucket_size = "200";
})
.controller('splCtrl', function($scope, contentData){
    $scope.file = "data/spl.tsv";
    $scope.title = "Storm Peak Laboratory (SPL)"
    $scope.bucket_size = "20";
})
.controller('mloCtrl', function($scope, contentData){
    $scope.file = "data/mlo.tsv";
    $scope.title = "Mauna Loa Observatory (MLO)"
    $scope.bucket_size = "20";
});