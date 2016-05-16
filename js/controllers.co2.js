angular.module('edu.ucar.scied.controllers.co2', [])
    .controller('homeCtrl', function ($scope, $window) {
        $scope.device_width = Math.floor($window.innerWidth);
        $scope.device_height = Math.floor($window.innerHeight);
        $scope.graph_height = Math.floor(($scope.device_height) - 100);
        // ipad pro resolution is 2048x2732, so max height of each, with nav is 682

        $scope.top = 10;
        $scope.right = 0;
        $scope.left = 40;
        $scope.bottom = 100;

        $scope.axis_y_label = "Carbon Dioxide (parts per million)";
        $scope.date_init = "01/01/2005";

        $scope.range = "tenyear";

        $scope.generateImageUrl = function (range) {
            $scope.range = range;
        };

    })
    .controller('creditsCtrl', function ($scope) {
        $scope.displayHome = function () {
            window.location.href = "#/";
        };
    })
    .controller('graphCtrl', function ($scope) {
        $scope.creditstitle = "Credits and Acknowledgements";
        $scope.showCreditsModal = false;

        // handle credits modal
        $scope.toggleCreditsModal = function () {
            $scope.showCreditsModal = !$scope.showCreditsModal;
        };

        // credits close btn
        $scope.closeModal = function () {
            $scope.showCreditsModal = !$scope.showCreditsModal;
        }


    })
    .controller('mlbCtrl', function ($scope) {
        $scope.file = "data/mlb.tsv";
        $scope.title = "Mesa Laboratory (MLB)"
        $scope.source = "mlb";
        $scope.x_range_low = new Date($scope.date_init).getTime() / 1000;

        $scope.bordercolor = 'CC0000';
        $scope.fillcolor = '660000';
    })
    .controller('nwrCtrl', function ($scope) {
        $scope.file = "data/nwr.tsv";
        $scope.title = "Niwot Ridge (NWR)"
        $scope.source = "nwr";
        $scope.x_range_low = new Date($scope.date_init).getTime() / 1000;

        $scope.bordercolor = '0000FF';
        $scope.fillcolor = '000066';
    })
    .controller('mloCtrl', function ($scope) {
        $scope.file = "data/mlo.tsv";
        $scope.title = "Mauna Loa Observatory (MLO)"
        $scope.source = "mlo";
        $scope.x_range_low = new Date($scope.date_init).getTime() / 1000;

        $scope.bordercolor = 'ff8000';
        $scope.fillcolor = '994c00';
    });