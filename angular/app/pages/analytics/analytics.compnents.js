class AnalyticsController {
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
    $scope.pa_series = ['CTR', 'Impressions'];
    this.min_date = moment().subtract(30, 'days')
    this.max_date = moment()
    $scope.activity_list = [];
    $scope.analyticsChartColours = [
      {
        fillColor: '#fcc5ae',
        strokeColor: '#D2D6DE',
        pointColor: '#000000',
        pointStrokeColor: '#fff',
        pointHighlightFill: '#fff',
        pointHighlightStroke: 'rgba(148,159,177,0.8)'
      }
    ]

    $scope.datePickerOptions = {
      applyClass: 'btn-green',
      locale: {
        applyLabel: "Apply",
        fromLabel: "From",
        format: "MMMM DD, YYYY", //will give you 2017-01-06
        toLabel: "To",
        cancelLabel: 'Cancel',
        customRangeLabel: 'Custom range'
      },
      ranges
        : {
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

    $scope.pieRandomColors = ['#2ecc71', '#1abc9c', '#3498db', '#9b59b6', '#34495e', '#16a085', '#27ae60', '#2980b9', '#2980b9', '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22', '#e74c3c', '#ecf0f1', '#d35400', '#c0392b']
    $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };

    $scope.load_pa_graph = function (s_time, e_time, type) {
      var ele_txt = angular.element('#pa_graph_type option:selected').text();
      $scope.pa_series = [ele_txt, 'Impressions'];
      let pa_campaign_graphs = API.service('data-widgets', API.all('perfectaudience'));
      pa_campaign_graphs.one("").get({ start_date: s_time, end_date: e_time, type: type }).then((response) => {
        var graph_data = response.plain();
        var graph_data_statics = graph_data.data.statics;
        var pa_labels = [];
        var pa_series = [];
        var pa_data_imp = [];
        var pa_data_responses = [];

        //let pa_options = [];
        angular.forEach(graph_data_statics, function (data, key) {
          pa_labels.push(data.date);
          pa_data_imp.push(data.impressions);
          pa_data_responses.push(data.responses);
        });
        $scope.pa_labels = pa_labels;
        $scope.pa_data = [pa_data_responses, pa_data_imp];

      });
    }

    $scope.$watchCollection('pa_graph_type', function (new_val, old_val) {
      let s_date = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
      let e_date = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
      let type = new_val;
      $scope.load_pa_graph(s_date, e_date, type);
    });

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

          $scope.total_analytics_visits = $scope.sum(chart_values);
          $scope.analytics_visit_labels = chart_dates
          $scope.analytics_visit_series = ['Visits']
          $scope.analytics_visit_data = [
            chart_values
          ]
          $scope.analytics_metric = visits_data.data.metrics
          $scope.analytics_browser_visits = visits_data.data.browser_visits
          $scope.analytics_traffic_sources = visits_data.data.traffic_sources

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

      let analytics = API.service('mailchimp-analytics');
      let last_list_id = '';
      analytics.one("").get({ start_date: start_date_list, end_date: end_date_list })
        .then((response) => {
          let reports_data = response.plain()
          if (reports_data.errors == false) {
            $scope.report_error = false;
            $scope.reports = reports_data.data.reports;
          }
          else {
            $scope.report_error = true;
            $scope.report_error_msg = '';
            $scope.reports = [];
          }
          $scope.busy = false;
        });

      let keywords = API.service('keyword-analytics-top', API.all('analytics'));
      keywords.one("").get({ start_date: start_date_list, end_date: end_date_list }).then((response) => {
        let keywords_data = response.plain()
        if (keywords_data.errors == false) {
          $scope.keywords_data = false;
          $scope.keywordsdata = keywords_data.data;
        }
        else {
          $scope.keywords_error = true;
          $scope.keywords_error_msg = '';
          $scope.keywordsdata = [];
        }
        $scope.busy = false;
      });


      /**Perfect Audience**/
      //$scope.pasitecampaignsdata = {};
      $scope.init_campaign_id = "";
      let pa_analytics = API.service('campaigns-by-site', API.all('perfectaudience')); /*Fetch campaign_ids from site_id*/
      pa_analytics.one("").get({ start_date: start_date_list, end_date: end_date_list }).then((response) => {
        if (response) {
          let pa_site_campaigns_data = response.plain();
          if (pa_site_campaigns_data.errors == false) {
            $scope.pa_site_campaigns_data = false;
            $scope.pasitecampaignsdata = pa_site_campaigns_data.data;
            //var init_campaign_id = pa_site_campaigns_data.data[0]._id;
          }
          else {
            $scope.pasitecampaignsdata_error = true;
            $scope.pasitecampaignsdata_error_msg = '';
            $scope.pasitecampaignsdata = [];
          }
          $scope.busy = false;
        }
      });

      /**Get ads by site**/
      let pa_campaign_analyticsa = API.service('ads-by-site-id', API.all('perfectaudience')); /*Fetch ads from campaign_id */
      pa_campaign_analyticsa.one("").get({ start_date: start_date_list, end_date: end_date_list }).then((response) => {
        let pa_campaigns_data = response.plain();
        if (pa_campaigns_data.errors == false) {
          $scope.pa_campaigns_data = false;
          $scope.pacampaignsdata = pa_campaigns_data.data;
        }
        else {
          $scope.pacampaignsdata_error = true;
          $scope.pacampaignsdata_error_msg = '';
          $scope.pacampaignsdata = [];
        }
        $scope.busy = false;
      });

      $scope.load_pa_graph(start_date_list, end_date_list, $scope.pa_graph_type);

      $scope.onClick = function (points, evt) {
      };
      $scope.pa_datasetOverride = [{ yAxisID: 'y-axis-1' }, { yAxisID: 'y-axis-2' }];
      $scope.pa_options = {
        maintainAspectRatio: false,
        scaleShowVerticalLines: false,
        scaleShowHorizontallLines: false,
        scales: {
          yAxes: [
            {
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


      $scope.paChartColours = [
        {
          fillColor: '#fcc5ae',
          strokeColor: '#D2D6DE',
          pointColor: '#000000',
          pointStrokeColor: '#fff',
          pointHighlightFill: '#fff',
          pointHighlightStroke: 'rgba(148,159,177,0.8)'
        },
        {
          fillColor: '#000000',
          strokeColor: '#D2D6DE',
          pointColor: '#000000',
          pointStrokeColor: '#fff',
          pointHighlightFill: '#fff',
          pointHighlightStroke: 'rgba(148,159,177,0.8)'
        }
      ]

    }


    $scope.load_recent_activity($scope.datePicker.startDate, $scope.datePicker.endDate);

    /* Function for percentage */
    $scope.get_per = function ($amount, $total) {
      return $amount / $total * 100;
    }
    $scope.getadsByCampaign = function () {
      let pa_campaign_analytics = API.service('campaigns-by-site', API.all('perfectaudience')); /*Fetch ads from campaign_id */
      pa_campaign_analytics.one("").get({ start_date: start_date_list, end_date: end_date_list }).then((response) => {
        let pa_campaigns_data = response.plain();
        if (pa_campaigns_data.errors == false) {
          $scope.pa_campaigns_data = false;
          $scope.pacampaignsdata = pa_campaigns_data.data;
        }
        else {
          $scope.pacampaignsdata_error = true;
          $scope.pacampaignsdata_error_msg = '';
          $scope.pacampaignsdata = [];
        }
        $scope.busy = false;
      });

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

export const AnalyticsComponent = {
  templateUrl: './views/app/pages/analytics/analytics.page.html',
  controller: AnalyticsController,
  controllerAs: 'vm',
  bindings: {}
}