angular.module('co2.controllers', []).
controller('homeCtrl', function ($scope, contentData){
    contentData.getUrl('data/nwr.txt')
    .success(function(list){
        $scope.data = list;
    });
    
    
});