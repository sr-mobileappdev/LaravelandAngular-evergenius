class GoogleAnalyticsController {
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
        this.start_date = moment().subtract(10, 'days')
        this.end_date = moment()

        this.can = AclService.can
        if (!this.can('analytics.google')) {
            $state.go('app.unauthorizedAccess');
        }
        this.min_date = moment().subtract(30, 'days')
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

        $scope.pieRandomColors = ['#2ecc71', '#1abc9c', '#3498db', '#9b59b6', '#34495e', '#16a085', '#27ae60', '#2980b9', '#2980b9', '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22', '#e74c3c', '#ecf0f1', '#d35400', '#c0392b']
        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };

        $scope.load_recent_activity = function (start_date_activity, end_date_activity) {
            $scope.busy = true;
            $scope.reports = [];
            let start_date_list = moment(start_date_activity).format('YYYY-MM-DD');
            let end_date_list = moment(end_date_activity).format('YYYY-MM-DD');

            /* ************* Google Analytics Graph and satics data ************* */
            var dashboardAnalytics = API.service('dashboard-analytics', API.all('analytics'))
            dashboardAnalytics.one('').get({ start_date: start_date_list, end_date: end_date_list })
                .then((response) => {
                    var visits_data = response.plain();
                    var chart_dates = [];
                    var chart_values = [];
                    var total_visits = 0;

                    angular.forEach(visits_data.data.visitor_report, function (value, key) {
                        chart_dates.push(value.date);
                        chart_values.push(value.visits);

                    });
                    var sort_by = function (field, reverse, primer) {

                        var key = primer ?
                            function (x) { return primer(x[field]) } :
                            function (x) { return x[field] };

                        reverse = !reverse ? 1 : -1;

                        return function (a, b) {
                            return a = key(a), b = key(b), reverse * ((a > b) - (b > a));
                        }
                    }

                    $scope.total_analytics_visits = $scope.sum(chart_values);
                    $scope.analytics_visit_labels = chart_dates
                    $scope.analytics_visit_series = ['Visits']
                    $scope.analytics_visit_data = [
                        chart_values
                    ]
                    $scope.analytics_metric = visits_data.data.metrics
                    $scope.analytics_browser_visits = visits_data.data.browser_visits
                    $scope.analytics_traffic_sources = visits_data.data.traffic_sources
                    $scope.analytics_traffic_sources.sort(sort_by('visits', true, parseInt))
                    $scope.analytics_browser_visits.desktop_visits.sort(sort_by('visits', true, parseInt))
                    $scope.analytics_browser_visits.mobile_visits.sort(sort_by('visits', true, parseInt))


                    /************ Trafic Source Pie Chart ************/

                    var traffic_labels = [];
                    var traffic_vlues = [];
                    var traficSorcColors = [];
                    var indx = 0;

                    angular.forEach(visits_data.data.traffic_sources, function (value, key) {
                        traffic_labels.push(value.source);
                        traffic_vlues.push(value.visits);
                        traficSorcColors.push($scope.pieRandomColors[indx]);
                        indx++;
                    });

                    $scope.analytics_traffic_pieLabels = traffic_labels
                    $scope.analytics_traffic_pieData = traffic_vlues
                    $scope.TraficSourcesChartColours = traficSorcColors

                    /************ / Trafic Source Pie Chart ************/
                });
            $scope.onClick = function (points, evt) {
            };
            $scope.pa_datasetOverride = [{ yAxisID: 'y-axis-1' }, { yAxisID: 'y-axis-2' }];
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

export const GoogleAnalyticsComponent = {
    templateUrl: './views/app/pages/google-analytics/google-analytics.page.html',
    controller: GoogleAnalyticsController,
    controllerAs: 'vm',
    bindings: {}
}
