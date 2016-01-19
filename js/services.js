angular.module('co2.services', [])
    .factory('contentData', ['$http', function ($http) {
        var contentData = {};

        contentData.getUrl = function (url) {
            return $http.get(url);
        };

        return contentData;
    }])
    .factory('d3Func', function () {
        var d3Func = {};
        d3Func.drawLineGraph = function (top, right, bottom, left, graphWidth, graphHeight, fileLoc, title, parentDiv, yLabel, bucketSize) {

            if (!bucketSize) {
                bucketSize = 20;
            }
            // functionality based on: http://blog.scottlogic.com/2014/09/19/interactive.html

            // init show 1 year
            var daysShown = 365;

            var margin = {
                    top: top,
                    right: right,
                    bottom: bottom,
                    left: left
                },
                width = graphWidth - margin.left - margin.right,
                height = graphHeight - margin.top - margin.bottom;

            var parseDate = d3.time.format.utc("%Y-%m-%dT%H:%M:%S").parse;

            // create x and y scales
            var xScale = d3.time.scale()
                .range([0, width]);
            var yScale = d3.scale.linear()
                .range([height, 0]);
            var chart = fc.chart.cartesian(
                xScale,
                yScale);

            // axis
            var xAxis = make_x_axis();
            var yAxis = make_y_axis();

            // gridlines
            function make_x_axis() {
                return d3.svg.axis()
                    .scale(xScale)
                    .orient("bottom")
                    .ticks(10)
            }

            function make_y_axis() {
                return d3.svg.axis()
                    .scale(yScale)
                    .orient("left")
                    .ticks(5)
            }

            // plotchart

            var svg = d3.select("#" + parentDiv).append("svg")
                .attr('class', 'plot')
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

            var line = d3.svg.line()
                .x(function (d) {
                    return xScale(d.DATE);
                })
                .y(function (d) {
                    return yScale(d.CO2);
                });

            /*
            // plotarea
            var svgArea = svg.append('g')
                .attr('clip-path', 'url(#svgAreaClip)');

            svgArea.append('clipPath')
                .attr('id', 'svgAreaClip')
                .append('rect')
                .attr({
                    width: width,
                    height: height
                });
*/
            d3.tsv(fileLoc, function (error, data) {
                if (error) throw error;

                // cleanse data to expected form
                for (var key in data) {
                    data[key].DATE = parseDate(data[key].DATE);
                    data[key].CO2 = parseFloat(data[key].CO2);

                }

                // configure the sampler
                var sampler = fc.data.sampler.largestTriangleThreeBucket()
                    .bucketSize(bucketSize)
                    .x(function (d) {
                        return d.DATE;
                    })
                    .y(function (d) {
                        return d.CO2;
                    });

                // sample the data
                var sampledData = sampler(data);

                //xScale.domain(fc.util.extent().fields('DATE')(sampledData))
                //yScale.domain(fc.util.extent().fields('CO2')(sampledData));

                xScale.domain(d3.extent(sampledData, function (d) {
                    return d.DATE;
                }));
                yScale.domain(d3.extent(sampledData, function (d) {
                    return d.CO2;
                }));


                // the sampled data
                var sampledLine = fc.series.line()
                    .xScale(xScale)
                    .yScale(yScale)
                    .xValue(function (d) {
                        return d.DATE;
                    })
                    .yValue(function (d) {
                        return d.CO2;
                    });



                // render
                /* svgArea.append('g')
                     .attr('class', 'line')
                     .datum(sampledData)
                     .call(sampledLine)
                     .call(gridlines);
                     
                     */

             
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

                svg.append("g")
                    .attr("class", "grid")
                    .attr("transform", "translate(0," + height + ")")
                    .call(make_x_axis()
                        .tickSize(-height, 0, 0)
                        .tickFormat("")
                    )

                svg.append("g")
                    .attr("class", "grid")
                    .call(make_y_axis()
                        .tickSize(-width, 0, 0)
                        .tickFormat("")
                    )

                svg.append("path")
                    .datum(sampledData)
                    .attr("class", "line")
                    .attr("d", line);
            });

        };
        return d3Func;

    });