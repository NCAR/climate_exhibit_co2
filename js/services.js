angular.module('co2.services', [])
    .factory('contentData', ['$http',function($http) {
        var contentData = {};
        
         contentData.getUrl = function(url){
             return $http.get(url);
         };

        return contentData;
}])
.factory('d3Func', function(){
    var d3Func = {};
    d3Func.drawLineGraph = function(top, right, bottom, left, graphWidth, graphHeight, fileLoc, title, yLabel){

        var margin = {top: top, right: right, bottom: bottom, left: left},
            width = graphWidth - margin.left - margin.right,
            height = graphHeight - margin.top - margin.bottom;

            var parseDate = d3.time.format("%d-%m-%Y %H:%M:%S").parse;

            var x = d3.time.scale()
                .range([0, width]);

            var y = d3.scale.linear()
                .range([height, 0]);

            var xAxis = d3.svg.axis()
                .scale(x)
                .orient("bottom");

            var yAxis = d3.svg.axis()
                .scale(y)
                .orient("left");

            var line = d3.svg.line()
                .x(function(d) { return x(d.DATE); })
                .y(function(d) { return y(d.CO2); });

            var svg = d3.select("body").append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
              .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")");
        
        d3.tsv(fileLoc, function(error, data) {
              if (error) throw error;
            
              var lastd = 0;
              data.forEach(function(d) {
                  // just to be on the safe side, verify there are values
                  if(d.DATE != undefined && 
                     d.CO2 != undefined &&
                     d.DATE != '' && 
                     d.CO2 != '')
                  {
                    d.DATE = parseDate(d.DATE);
                    d.CO2 = +d.CO2;
                  }
              });

              x.domain(d3.extent(data, function(d) { return d.DATE; }));
              y.domain(d3.extent(data, function(d) { return d.CO2; }));

              svg.append("g")
                  .attr("class", "x axis")
                  .attr("transform", "translate(0," + height + ")")
                  .call(xAxis);

              svg.append("g")
                  .attr("class", "y axis")
                  .call(yAxis)
                .append("text")
                  .attr("transform", "rotate(-90)")
                  .attr("y", 6)
                  .attr("dy", ".71em")
                  .style("text-anchor", "end")
                  .text(yLabel);

              svg.append("path")
                  .datum(data)
                  .attr("class", "line")
                  .attr("d", line);
            
            
            svg.append("text")
                .attr("x", (width / 2))             
                .attr("y", 0 + (margin.top / 2))
                .attr("text-anchor", "middle")  
                .style("font-size", "16px")  
                .text(title);
            });
    }
    
    return d3Func;
    
});