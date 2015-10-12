angular.module('co2.services', []).
factory('contentData', ['$http',function($http) {
        var contentData = {};
        
         contentData.getUrl = function(url){
             return $http.get(url);
         };

        return contentData;
}]);