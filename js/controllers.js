angular.module('co2.controllers', [])
    .controller('homeCtrl', function ($scope, $window) {
        $scope.device_width = $window.innerWidth;
        $scope.device_height = $window.innerHeight;
        $scope.graph_height = $scope.device_height / 3;
        // ipad pro resolution is 2048x2732, so max height of each, with nav is 682
    
        //TODO: get y_max value from all files
        $scope.y_min = 300;
        $scope.y_max = 420;

        // TODO: retrieve min and max date from mlo
        $scope.min_date = '1958-03-29T12:00:00';
        $scope.max_date = '2016-01-13T12:00:00';

    })
    .controller('nwrCtrl', function ($scope, contentData) {
        $scope.file = "data/nwr.tsv";
        $scope.title = "Niwot Ridge (NWR)"
        $scope.bucket_size = "500";
    })
    .controller('splCtrl', function ($scope, contentData) {
        $scope.file = "data/spl.tsv";
        $scope.title = "Storm Peak Laboratory (SPL)"
        $scope.bucket_size = "10";
    })
    .controller('mloCtrl', function ($scope, contentData) {
        $scope.file = "data/mlo.tsv";
        $scope.title = "Mauna Loa Observatory (MLO)"
        $scope.bucket_size = "20";
    })
    .controller('chartCtrl', function ($scope, contentData) {
        $scope.title = "CO\u2082 (ppm)";
        // get relevant data values for each plot to graph
        var data = {};
        $scope.plotAttr = {
            "yMin": $scope.yMin,
            "yMax": $scope.yMax,
            "minDate": $scope.minDate,
            "maxDate": $scope.maxDate,
            "width": $scope.device_width,
            "height": $scope.device_height,
            "top": 10,
            "right": 20,
            "bottom": 20,
            "left": 50,
            "yLabel": $scope.title
        };

        $scope.mlo = {
            "file": "data/mlo.tsv",
            "bucketSize": "20",
            "color": "blue"
        };

        $scope.nwr = {
            "file": "data/nwr.tsv",
            "bucketSize": "20",
            "color": "red"
        }
        data = {
            "mlo": $scope.mlo,
            "nwr": $scope.nwr        
        }

        $scope.plotValues = data;
    });