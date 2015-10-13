angular.module('co2.directives', ["co2.services"])
.directive('linesChart', function ($parse, d3Func) {
     var directiveDefinitionObject = {
         restrict: 'E',
         scope: {data: '='},
         replace: false,
         link: function (scope, element, attrs) {
           var fileLoc = attrs.file;
             var title = attrs.title;
           d3Func.drawLineGraph(20,20,30,50,960,500,fileLoc,title,"CO2 (ppm)");
         } 
      };
      return directiveDefinitionObject;
   });