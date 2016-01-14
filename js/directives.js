angular.module('co2.directives', ["co2.services"])
.directive('linesChart', function (d3Func) {
     var directiveDefinitionObject = {
         restrict: 'E',
         scope: {
            data: "="
         },
         replace: false,
         link: function (scope, element, attrs) {
            var fileLoc = attrs.file;
             var title = attrs.title;
             var parentDiv = attrs.parentdiv;
             var width = attrs.width;
             var height = attrs.height;
           d3Func.drawLineGraph(10,20,20,50,width,height,fileLoc,title,parentDiv,"CO\u2082 (ppm)");
         } 
      };
      return directiveDefinitionObject;
   });