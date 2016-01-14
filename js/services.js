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
        d3Func.drawLineGraph = function (top, right, bottom, left, graphWidth, graphHeight, fileLoc, title, parentDiv, yLabel) {

            var daysShown = 365;

            var margin = {
                    top: top,
                    right: right,
                    bottom: bottom,
                    left: left
                },
                width = graphWidth - margin.left - margin.right,
                height = graphHeight - margin.top - margin.bottom;

            var parseDate = d3.time.format.utc("%Y-%m-%dT%H:%M:%S").parse,
                bisectDate = d3.bisector(function (d) {
                    return d.DATE;
                }).left,
                formatValue = d3.format(",.2f"),
                formatCO2 = function (d) {
                    return "CO\u2082: " + formatValue(d);
                };
            //var parseDate = d3.time.format("%d-%m-%Y").parse;




            var xScale = d3.time.scale()
                .range([0, width]);

            var yScale = d3.scale.linear()
                .range([height, 0]);

            var xAxis = d3.svg.axis()
                .scale(xScale)
                .ticks(12)
                .orient("bottom");

            var yAxis = d3.svg.axis()
                .scale(yScale)
                .ticks(5)
                .orient("left");

            // for x axis grid lines
            function make_x_axis() {
                return d3.svg.axis()
                    .scale(xScale)
                    .orient("bottom")
                    .ticks(12);
            };

            // for y axis grid lines
            function make_y_axis() {
                return d3.svg.axis()
                    .scale(yScale)
                    .orient("left")
                    .ticks(5)
            };

            // actual graphed line
            var line = d3.svg.line()
                .x(function (d) {
                    return xScale(d.DATE);
                })
                .y(function (d) {
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

            d3.tsv(fileLoc, function (error, data) {
                if (error) throw error;

                // save off min and max values for date/x values
                var minN = d3.min(data, function (d) {
                        return parseDate(d.DATE);
                    }).getTime(),
                    maxN = d3.max(data, function (d) {
                        return parseDate(d.DATE);
                    }).getTime();
                var minDate = new Date(minN - 8.64e7),
                    maxDate = new Date(maxN + 8.64e7);


                var yMin = d3.min(data, function (d) {
                        return d.CO2;
                    }),
                    yMax = d3.max(data, function (d) {
                        // can either go with the max value or hardset the ymax value
                        return d.CO2;
                        // return 490;
                    });

                var lastd = 0;
                data.forEach(function (d) {
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

                // draw border around graph
                var lineData = [
                    {
                        "x": width,
                        "y": height
                    },
                    {
                        "x": width,
                        "y": 0
                    },
                    {
                        "x": 0,
                        "y": 0
                    }];
                
                var lineFunc = d3.svg.line().x(function (d) {
                        return d.x;
                    })
                    .y(function (d) {
                        return d.y;
                    })
                    .interpolate('linear');

                // border around graph
                var lineGraph = svg.append("path")
                    .attr("d", lineFunc(lineData))
                    .attr("class", "mainGraphBorder");

                var dataSeries = function () {
                    return svgArea.append('path')
                        .attr('class', 'line')
                        .attr('d', line(data));
                }

                //dataSeries();

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
                    .x(function (d) {
                        return navXScale(d.DATE);
                    })
                    .y0(navHeight)
                    .y1(function (d) {
                        return navYScale(d.CO2);
                    });

                var navLine = d3.svg.line()
                    .x(function (d) {
                        return navXScale(d.DATE);
                    })
                    .y(function (d) {
                        return navYScale(d.CO2);
                    });

                navChart.append('path')
                    .attr('class', 'data')
                    .attr('d', navData(data));

                navChart.append('path')
                    .attr('class', 'line')
                    .attr('d', navLine(data));

                /* on hold mouse tooltip value
                var focus = svg.append("g")
                    .attr("class", "focus")
                    .style("display", "none");

                focus.append("circle")
                    .attr("r", 4.5);

                focus.append("rect")
                    .attr("class","mousetipContainer")
                    .attr("width","75")
                    .attr("height","15")
                    .attr("transform", "translate(0,-8)");
                
                focus.append("text")
                    .attr("x", 9)
                    .attr("class","mousetip")
                    .attr("dy", ".35em");
                
                
                svg.append("rect")
                    .attr("class", "overlay")
                    .attr("width", width)
                    .attr("height", height)
                    .on("mouseover", function () {
                        focus.style("display", null);
                    })
                    .on("mouseout", function () {
                        focus.style("display", "none");
                    })
                    .on("click", mousemove);


                function mousemove() {
                    var x0 = xScale.invert(d3.mouse(this)[0]),
                        i = bisectDate(data, x0, 1),
                        d0 = data[i - 1],
                        d1 = data[i],
                        d = x0 - d0.DATE > d1.DATE - x0 ? d1 : d0;
                    focus.attr("transform", "translate(" + xScale(d.DATE) + "," + yScale(d.CO2) + ")");
                    focus.select("text").html(formatCO2(d.CO2));
                }

*/

                function redrawChart() {
                    // delete plot
                    d3.select("#" + parentDiv + " .line").remove();

                    // remove x grid lines
                    d3.select("#" + parentDiv + " .x.grid").remove();
                    // remove y grid lines
                    d3.select("#" + parentDiv + " .y.grid").remove();

                    // redraw x axis grid lines
                    svg.append("g")
                        .attr("class", "x grid")
                        .attr("transform", "translate(0," + height + ")")
                        .call(make_x_axis()
                            .tickSize(-height, 0, 0)
                            .tickFormat("")
                        );
                    // redraw y axis grid lines                    
                    svg.append("g")
                        .attr("class", "y grid")
                        .call(make_y_axis()
                            .tickSize(-width, 0, 0)
                            .tickFormat("")
                        );

                    // redraw plot
                    dataSeries();
                    svg.select('.x.axis').call(xAxis);
                    svg.select('.y.axis').call(yAxis);

                }

                function updateViewportFromChart() {
                    if ((xScale.domain()[0] <= minDate) && (xScale.domain()[1] >= maxDate)) {

                        viewport.clear(xScale.domain());
                    } else {

                        viewport.extent(xScale.domain());
                    }

                    navChart.select('.viewport').call(viewport);
                }

                var viewport = d3.svg.brush()
                    .x(navXScale)
                    .on("brushend", function () {
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
                    .on('zoom', function () {
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

                viewport.on("brush", function () {
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