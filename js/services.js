angular.module('co2.services', [])
    .factory('contentData', ['$http', function($http) {
        var contentData = {};

        contentData.getUrl = function(url) {
            return $http.get(url);
        };

        return contentData;
    }])
    .factory('d3Func', function() {
        var d3Func = {};
        d3Func.drawLineGraph = function(top, right, bottom, left, graphWidth, graphHeight, fileLoc, title, parentDiv, yLabel) {
            var daysShown = 365;

            var margin = {
                    top: top,
                    right: right,
                    bottom: bottom,
                    left: left
                },
                width = graphWidth - margin.left - margin.right,
                height = graphHeight - margin.top - margin.bottom;

            //var parseDate = d3.time.format("%d-%m-%Y %H:%M:%S").parse;
            var parseDate = d3.time.format("%d-%m-%Y").parse;

            var xScale = d3.time.scale()
                .range([0, width]);

            var yScale = d3.scale.linear()
                .range([height, 0]);

            var xAxis = d3.svg.axis()
                .scale(xScale)
                .ticks(5)
                .orient("bottom");

            var yAxis = d3.svg.axis()
                .scale(yScale)
                .orient("left");

            var line = d3.svg.line()
                .x(function(d) {
                    return xScale(d.DATE);
                })
                .y(function(d) {
                    return yScale(d.CO2);
                });

            var svg = d3.select("#" + parentDiv).append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

            var svgArea = svg.append('g')
                .attr('clip-path', 'url(#svgAreaClip)');

            svgArea.append('clipPath')
                .attr('id', 'svgAreaClip')
                .append('rect')
                .attr({
                    width: width,
                    height: height
                });

            d3.tsv(fileLoc, function(error, data) {
                if (error) throw error;

                // save off min and max values
                var minN = d3.min(data, function(d) {
                        return parseDate(d.DATE);
                    }).getTime(),
                    maxN = d3.max(data, function(d) {
                        return parseDate(d.DATE);
                    }).getTime();
                var minDate = new Date(minN - 8.64e7),
                    maxDate = new Date(maxN + 8.64e7);
                var yMin = d3.min(data, function(d) {
                        return d.CO2;
                    }),
                    yMax = d3.max(data, function(d) {
                        return d.CO2;
                    });

                var lastd = 0;
                data.forEach(function(d) {
                    // just to be on the safe side, verify there are values
                    if (d.DATE != undefined &&
                        d.CO2 != undefined &&
                        d.DATE != '' &&
                        d.CO2 != '') {
                        d.DATE = parseDate(d.DATE);
                        d.CO2 = +d.CO2;
                    }
                });

                yScale.domain([yMin, yMax]).nice();
                //yScale.domain([350,450]).nice();
                xScale.domain([minDate, maxDate]);

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

                var dataSeries = function() {
                    return svgArea.append('path')
                        .attr('class', 'line')
                        .attr('d', line(data));
                }

                dataSeries();

                svg.append("text")
                    .attr("x", (width / 2))
                    .attr("y", 0 + (margin.top / 2))
                    .attr("text-anchor", "middle")
                    .style("font-size", "16px")
                    .text(title);

                var navWidth = width,
                    navHeight = 100 - margin.top - margin.bottom;

                var navChart = d3.select("#" + parentDiv).classed('chart', true).append('svg')
                    .classed('navigator', true)
                    .attr('width', navWidth + margin.left + margin.right)
                    .attr('height', navHeight + margin.top + margin.bottom)
                    .append('g')
                    .attr('transform', 'translate(' + margin.left + ',' + margin.top + ')');


                var navXScale = d3.time.scale()
                    .domain([minDate, maxDate])
                    .range([0, navWidth]),
                    navYScale = d3.scale.linear()
                    .domain([yMin, yMax])
                    .range([navHeight, 0]);

                var navXAxis = d3.svg.axis()
                    .scale(navXScale)
                    .orient('bottom');

                navChart.append('g')
                    .attr('class', 'x axis')
                    .attr('transform', 'translate(0,' + navHeight + ')')
                    .call(navXAxis);

                var navData = d3.svg.area()
                    .x(function(d) {
                        return navXScale(d.DATE);
                    })
                    .y0(navHeight)
                    .y1(function(d) {
                        return navYScale(d.CO2);
                    });

                var navLine = d3.svg.line()
                    .x(function(d) {
                        return navXScale(d.DATE);
                    })
                    .y(function(d) {
                        return navYScale(d.CO2);
                    });

                navChart.append('path')
                    .attr('class', 'data')
                    .attr('d', navData(data));

                navChart.append('path')
                    .attr('class', 'line')
                    .attr('d', navLine(data));


                function redrawChart() {
                    d3.select("#" + parentDiv + " .line").remove();
                    dataSeries();
                    svg.select('.x.axis').call(xAxis);
                }

                function updateViewportFromChart() {

                    if ((xScale.domain()[0] <= minDate) && (xScale.domain()[1] >= maxDate)) {

                        viewport.clear();
                    } else {

                        viewport.extent(xScale.domain());
                    }

                    navChart.select('.viewport').call(viewport);
                }

                var viewport = d3.svg.brush()
                    .x(navXScale)
                    .on("brush", function() {
                        xScale.domain(viewport.empty() ? navXScale.domain() : viewport.extent());
                        redrawChart();
                    });

                navChart.append("g")
                    .attr("class", "viewport")
                    .call(viewport)
                    .selectAll("rect")
                    .attr("height", navHeight);

                var zoom = d3.behavior.zoom()
                    .x(xScale)
                    .on('zoom', function() {
                        if (xScale.domain()[0] < minDate) {
                            var x = zoom.translate()[0] - xScale(minDate) + xScale.range()[0];
                            zoom.translate([x, 0]);
                        } else if (xScale.domain()[1] > maxDate) {
                            var x = zoom.translate()[0] - xScale(maxDate) + xScale.range()[1];
                            zoom.translate([x, 0]);
                        }
                        redrawChart();
                        updateViewportFromChart();
                    });

                viewport.on("brushend", function() {
                    updateZoomFromChart();
                });

                function updateZoomFromChart() {
                    zoom.x(xScale);

                    var fullDomain = maxDate - minDate,
                        currentDomain = xScale.domain()[1] - xScale.domain()[0];

                    var minScale = currentDomain / fullDomain,
                        maxScale = minScale * 20;

                    zoom.scaleExtent([minScale, maxScale]);
                }

                xScale.domain([
                    data[data.length - daysShown - 1].DATE,
                    data[data.length - 1].DATE
                ]);

                redrawChart();
                updateViewportFromChart();
                updateZoomFromChart();

            });

        };
        return d3Func;

    });