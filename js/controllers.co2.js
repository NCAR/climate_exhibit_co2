angular.module('edu.ucar.scied.controllers.co2', [])
    .controller('homeCtrl', function ($scope, $window) {
        $scope.device_width = Math.floor($window.innerWidth);
        $scope.device_height = Math.floor($window.innerHeight);
        $scope.graph_height = Math.floor($scope.device_height / 3);
        // ipad pro resolution is 2048x2732, so max height of each, with nav is 682

        $scope.top = 20;
        $scope.right = 0;
        $scope.left = 40;
        $scope.bottom = 50;

        $scope.axis_y_label = "Carbon Dioxide (parts per million)";
        $scope.date_init = "01/01/2005";




    })
    .controller('nwrCtrl', function ($scope) {
        $scope.file = "data/nwr.tsv";
        $scope.title = "Niwot Ridge (NWR)"
        $scope.source = "nwr";
        $scope.x_range_low = new Date($scope.date_init).getTime() / 1000;
    })
    .controller('mloCtrl', function ($scope) {
        $scope.file = "data/mlo.tsv";
        $scope.title = "Mauna Loa Observatory (MLO)"
        $scope.source = "mlo";
        $scope.x_range_low = new Date($scope.date_init).getTime() / 1000;

        $scope.generateImageUrl = function (classname) {
            var imgUrl = '';
            var img = $('.' + classname + ' img');
            var full_range = 'php/generator/graphGenerator.php?source=' + $scope.source + '&height=' + $scope.graph_height + '&width=' + $scope.device_width + '&top=' + $scope.top + '&bottom=' + $scope.bottom + '&left=' + $scope.left + '&right=' + $scope.right+ '&x_range_low='+$scope.x_range_low;
            var short_range = 'php/generator/graphGenerator.php?source=' + $scope.source + '&height=' + $scope.graph_height + '&width=' + $scope.device_width + '&top=' + $scope.top + '&bottom=' + $scope.bottom + '&left=' + $scope.left + '&right=' + $scope.right;
            
            if(img.attr('src') == short_range){
                imgUrl = full_range;
            } else {
                imgUrl = short_range;
            }
            img.attr('src', imgUrl);


        }
    });