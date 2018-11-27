class MarketingAnalyticsController {
    constructor(API, $state, $scope, $window, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, AclService, $location) {
        'ngInject'

        this.API = API
        this.$state = $state
        this.alerts = []
        this.publishers = [];
        this.$scope = $scope;
        $scope.list_id = [];
        $scope.list_i = 5;
        $scope.pa_graph_type = 'ctr';
        this.start_date = moment().subtract(90, 'days')
        this.end_date = moment()

        $scope.pa_series = ['CTR', 'Impressions'];

        this.can = AclService.can
        if (!this.can('analytics.mailchimp')) {
            $state.go('app.unauthorizedAccess');
        }

        this.min_date = moment().subtract(90, 'days')
        this.max_date = moment()
        $scope.activity_list = [];
        $scope.analyticsChartColours = [{
            fillColor: '#fcc5ae',
            strokeColor: '#D2D6DE',
            pointColor: '#000000',
            pointStrokeColor: '#fff',
            pointHighlightFill: '#fff',
            pointHighlightStroke: 'rgba(148,159,177,0.8)'
        }]

        $scope.datePickerOptions = {
            linkedCalendars: false,
            startDate: moment().subtract(30, 'days'),
            endDate: moment(),
            alwaysShowCalendars: true,
            applyClass: 'btn-green',
            locale: {
                applyLabel: "Apply",
                fromLabel: "From",
                format: "MMMM DD, YYYY", //will give you 2017-01-06
                toLabel: "To",
                cancelLabel: 'Cancel',
                customRangeLabel: 'Custom range'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Last 7 Days': [moment().subtract(7, 'days'), moment()]
            },
            eventHandlers: {
                'apply.daterangepicker': function (ev, picker) {
                    $scope.list_i = 5;
                    $scope.activity_list = [];
                    $scope.list_id = [];
                    var start = ev.model.startDate.format('YYYY-MM-DD');
                    var end = ev.model.endDate.format('YYYY-MM-DD');
                    $scope.load_recent_activity(start, end);
                    $scope.load_data_keywords();
                }
            }
        }
        var Reddit = function () {
            this.items = [];
            this.busy = false;
            this.after = '';
        };

        $scope.websiteVisitsChartOptions = {
            scaleShowVerticalLines: false,
            scaleShowHorizontallLines: false,
            //responsive:true,

            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    stacked: true,
                    gridLines: {
                        display: true,
                        color: "rgba(255,99,132,0.2)"
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false
                    }
                }]
            },
            tooltipTemplate: "<%= value %>",
            gridLines: {
                show: false
            }
        };

        $scope.datePicker = {
            startDate: this.start_date,
            endDate: this.end_date
        };

        $scope.load_recent_activity = function (start_date_activity, end_date_activity) {
            $scope.busy = true;
            $scope.reports = [];
            let start_date_list = moment(start_date_activity).format('YYYY-MM-DD');
            let end_date_list = moment(end_date_activity).format('YYYY-MM-DD');
            let analytics = API.service('campaign-statics', API.all('email-marketing'));
            let last_list_id = '';
            analytics.one("").get({
                start_date: start_date_list,
                end_date: end_date_list,
                count: 20
            })
                .then((response) => {
                    let reports_data = response.plain()
                    if (reports_data.errors == false) {
                        $scope.report_error = false;
                        $scope.reports = reports_data.data;
                    } else {
                        $scope.report_error = true;
                        $scope.report_error_msg = '';
                        $scope.reports = [];
                    }
                    $scope.busy = false;
                });

            let keywords = API.service('keyword-analytics-top', API.all('analytics'));
            keywords.one("").get({
                start_date: start_date_list,
                end_date: end_date_list
            }).then((response) => {
                let keywords_data = response.plain()
                if (keywords_data.errors == false) {
                    $scope.keywords_data = false;
                    $scope.keywordsdata = keywords_data.data;
                } else {
                    $scope.keywords_error = true;
                    $scope.keywords_error_msg = '';
                    $scope.keywordsdata = [];
                }
                $scope.busy = false;
            });

            $scope.onClick = function (points, evt) {

            };
            $scope.pa_datasetOverride = [{
                yAxisID: 'y-axis-1'
            }, {
                yAxisID: 'y-axis-2'
            }];
            $scope.pa_options = {
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        id: 'y-axis-1',
                        type: 'linear',
                        display: true,
                        position: 'left'
                    },
                    {
                        id: 'y-axis-2',
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            max: 1,
                            min: 0
                        }
                    }
                    ]
                },
                y2axis: true
            };


        }


        $scope.load_recent_activity($scope.datePicker.startDate, $scope.datePicker.endDate);

        /* Function for percentage */
        $scope.get_per = function ($amount, $total) {
            return $amount / $total * 100;
        }
        $scope.sum = function (input) {
            if (toString.call(input) !== "[object Array]")
                return false;
            var total = 0;
            for (var i = 0; i < input.length; i++) {
                if (isNaN(input[i])) {
                    continue;
                }
                total += Number(input[i]);
            }
            return total;
        }
    }

    $onInit() { }

}

export const MarketingAnalyticsComponent = {
    templateUrl: './views/app/pages/marketing-analytics/marketing-analytics.page.html',
    controller: MarketingAnalyticsController,
    controllerAs: 'vm',
    bindings: {}
}
