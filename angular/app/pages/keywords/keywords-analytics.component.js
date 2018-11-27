class KeywordAnalyticsController {
    constructor($scope, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location) {
        'ngInject'
        this.$auth = $auth;
        this.$location = $location;
        this.SAAPI = SAAPI;
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.AclService = AclService
        this.$window = $window
        $scope.tableId = "keyword_analytics";
        this.start_date = moment().subtract(10, 'days')
        this.end_date = moment()

        this.min_date = moment().subtract(30, 'days')
        this.max_date = moment()
        $scope.dtInstance = {};

        this.can = AclService.can
        if (!this.can('analytics.keyword')) {
            $state.go('app.unauthorizedAccess');
        }

        /* ***** Date Picker ***** */
        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };
        $scope.datePickerOptions = {
            locale: {
                applyClass: 'btn-green',
                applyLabel: "Apply",
                fromLabel: "From",
                format: "MMMM DD, YYYY",  //will give you 2017-01-06
                toLabel: "To",
                cancelLabel: 'Cancel',
                customRangeLabel: 'Custom range'
            },
            ranges: {
                'Last 7 Days': [moment().subtract(7, 'days'), moment()],
                'Last 30 Days': [moment().subtract(30, 'days'), moment()]
            },
            eventHandlers: {
                'apply.daterangepicker': function (ev, picker) {
                    // alert('I am in');
                    $scope.load_data_table();

                }
            }
        }
        /**Date Picker End**/

        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }

        $scope.load_data_table = function () {
            var token = $window.localStorage.satellizer_token
            var start_date_calls = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
            var end_date_calls = moment($scope.datePicker.endDate).format('YYYY-MM-DD');

            $scope.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/analytics/keyword-analytics',
                    type: 'post',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        var start_date = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
                        var end_date = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
                        data.customFilter = { start_time: start_date, end_time: end_date };
                        return JSON.stringify(data);
                    }
                })
                .withDataProp('data')
                .withOption('serverSide', true)
                .withOption('processing', true)
                .withColReorder()
                //.withColReorderOrder([2, 1, 2])
                .withColReorderOption('iFixedColumnsRight', 1)
                .withColReorderCallback(function () {
                })
                .withOption('createdRow', function (row) {
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('responsive', true)
                .withBootstrap()

            $scope.dtColumns = [
                //campaign,keyword,adGroup,impressions,adClicks,adCost,CPC,CTR
                DTColumnBuilder.newColumn('keyword').withTitle('Keyword').withOption('sWidth', '100px'),
                DTColumnBuilder.newColumn('campaign').withTitle('Campaign').withOption('sWidth', '100px'),
                DTColumnBuilder.newColumn('adGroup').withTitle('Ad Group'),
                DTColumnBuilder.newColumn('impressions').withTitle('Impressions'),
                DTColumnBuilder.newColumn('adClicks').withTitle('Ad Clicks'),
                DTColumnBuilder.newColumn('adCost').withTitle('Cost'),
                DTColumnBuilder.newColumn('CPC').withTitle('CPC'),
                DTColumnBuilder.newColumn('CTR').withTitle('CTR')
            ]

            $scope.displayTable = true
            $('#' + $scope.tableId).DataTable().ajax.reload();
        }
        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }

        $scope.load_data_table();

    }

    $onInit() { }
}

export const KeywordAnalyticsComponent = {
    templateUrl: './views/app/pages/keywords/keywords-analytics.component.html',
    controller: KeywordAnalyticsController,
    controllerAs: 'vm',
    bindings: {}
}
