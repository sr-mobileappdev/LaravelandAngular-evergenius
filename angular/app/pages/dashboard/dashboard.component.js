class DashboardController {
    constructor($scope, API, $filter, $sce, $state, AclService, $uibModal, $http, $window) {
        'ngInject'
        this.analytics = []
        this.can = AclService.can
        $scope.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        this.user_role = this.roles[0]
        var roles = AclService.getRoles();
        $scope.loading_funnel = true;
        $scope.flagtrue = false
        $scope.closed_amount = 0;
        $scope.total_average = 0;
        $scope.rating_three = 0;
        $scope.rating_four = 0;
        $scope.rating_five = 0;
        $scope.total_average = 0;
        $scope.tasks = [];
        $scope.sessions = 0;
        $scope.conversion_rate = 0;
        $scope.statics = [];
        $scope.loading_chat = false
        $scope.skipNotShow = false
        $scope.userSetup = true
        $scope.twilioSetup = true
        $scope.sendgridSetup = true
        $scope.providersSetup = true
        $scope.notificationSetup = true
        $scope.googleAnalytic = true
        $scope.ShowEditGoogle = false
        $scope.ShowEditTwillio = false
        $scope.analytics_metric = {
            users: 0,
            bounce_date: 0,
            page_views: 0
        }
        if ($window.localStorage.adminrole) {
            this.adminrole = $window.localStorage.adminrole
            this.isadmin = 1;
        }

        if (!$window.localStorage.user_company_details && $window.localStorage.super_admin_token && $window.localStorage.adminrole == 'admin.super'){
            $state.go('app.superdashboard', {});
        }

        if(JSON.parse($window.localStorage.getItem('user_company_details')).api_key!=undefined){
            $scope.api_key = JSON.parse($window.localStorage.getItem('user_company_details')).api_key
        }

        $scope.appUrl = $window.localStorage.application_url
        if ($window.localStorage.company_config_status) {
            var configStaus = JSON.parse($window.localStorage.getItem('company_config_status'))
            if (configStaus == '0') {
                $scope.flagtrue = true
                var getConfiguration = API.service('company-configurations', API.all('company'))
                getConfiguration.one().get()
                    .then((response) => {
                        $scope.configDetail = response.data.configs
                        if ($scope.configDetail.google_analytics_setup == '1' && $scope.configDetail.notification_setup == '1' && $scope.configDetail.providers_setup == '1' && $scope.configDetail.sendgrid_setup == '1' && $scope.configDetail.twilio_setup == '1' && $scope.configDetail.user_setup == '1') {
                            $scope.flagtrue = false
                            $state.reload()
                            $window.localStorage.removeItem('company_config_status')
                        }
                    })

            }
        }

        $scope.API = API;
        $scope.$state = $state;

        if (roles.indexOf('admin.user') == -1 && $scope.can('dashboard.funnel.chart')) {
            $state.go('app.agentdashboard');
            return false;
        }

        this.start_date = moment().subtract(30, 'days')
        $scope.mailchimp_start_date = moment().subtract(90, 'days');
        $scope.mailchimp_end_date = moment();
        this.end_date = moment()

        this.min_date = moment().subtract(2000, 'days')
        this.max_date = moment()
        $scope.funnel_text = 'Avg. Lead Response Time: 0 hr 0 min';

        $scope.funnelWidget = function (start_date, end_date) {
            var dashboardAnalytics = API.service('funnel-widget', API.all('leads'))
            dashboardAnalytics.one().get({ start_date: start_date, end_date: end_date })
                .then((response) => {
                    var out_statics = []
                    var res = response.plain();
                    var statics = res.data.leads_statics;
                    var total_amount = 0;
                    var total_leads = 0;
                    var total_ltv_val = 0;

                    angular.forEach(statics, function (data, key) {
                        total_amount = total_amount + data.total_ltv;
                        total_leads = parseInt(data.count_lead) + total_leads;
                        total_ltv_val = parseInt(data.total_ltv) + total_ltv_val;
                        out_statics.push([data.title, data.count_lead, data.total_ltv, data.stage_id]);
                        $scope.statics.push(
                            {
                                "title": data.title,
                                "count_lead": data.count_lead,
                                "total_ltv": data.total_ltv,
                                "stage_id": data.stage_id
                            }
                        );

                    });

                    if (total_ltv_val != 0) {
                        $scope.conversion_rate = (parseInt(res.data.close_amount) / total_ltv_val) * 100;
                    }
                    $scope.total_leads = total_leads;
                    $scope.loading_funnel = false;
                    $scope.chartConfig.series = [];
                    $scope.chartConfig.series.push({
                        data: out_statics,
                        tooltip: {
                            //valueSuffix: ' mb'
                        }
                    });
                    $scope.total_ltv = total_amount;
                    $scope.closed_amount = res.data.close_amount;
                    var sc = 'hr';
                    if (res.data.avg_lead_response_time.H > 1) {
                        sc = 'hrs';
                    }
                    $scope.funnel_text = 'Avg. Lead Response Time: ' + res.data.avg_lead_response_time.H + ' ' + sc + ' ' + res.data.avg_lead_response_time.M + ' min';
                });

            $scope.chartConfig = {
                chart: {
                    type: 'funnel',
                    //renderTo: 'chart_funnel',
                    marginRight: 0,
                    height: 315,
                    width: 400
                },
                title: {
                    text: ''
                },
                colors: ['#297fb8', '#fb9265', '#9a59b7', '#258e4c'],
                plotOptions: {
                    series: {
                        dataLabels: {
                            enabled: true,
                            format: '<b class="funnel_{point.name}">{point.name}</b> ({point.y:,.0f})',
                            softConnector: true
                        },
                        center: ['33%', '50%'],
                        neckWidth: '30%',
                        neckHeight: '20%',
                        width: '70%'
                    }
                },
                tooltip: {
                    formatter: function () {
                        let obj = $scope.statics.find(o => o.title === this.key);
                        let conv_pecentage = Math.round((obj.total_ltv / $scope.total_ltv) * 100);
                        return '<div class="text-center tooltip-funnel" style="width:350px">Total leads: ' + this.y + '<br>' + 'Dollor value: $' + obj.total_ltv + '<br>Conversion: ' + conv_pecentage + '%</div>';
                    },
                },
                series: $scope.chartSeries
            }
        }

        $scope.twillioIntegration = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: '/twillio-step-1-modal.html',
                controller: TwillioStepOneModalController,

            });
            return modalInstance;
        }
        $scope.skipStep = function () {
            $scope.loading_chat = true
            var skipStep = API.service('skip-integration', API.all('company'))
            skipStep.one().get()
                .then((response) => {
                    $scope.loading_chat = false
                    $scope.flagtrue = false
                    $state.reload()
                    $window.localStorage.removeItem('company_config_status')
                }, (response) => {
                    $scope.loading_chat = false
                    $scope.flagtrue = false
                })
        }
        //FUNNEL FUNCTION 

        /* ***** Date Picker ***** */
        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };
        $scope.datePickerOptions = {
            linkedCalendars: false,
            startDate: moment().subtract(30, 'days'),
            endDate: moment(),
            alwaysShowCalendars: true,
            locale: {
                applyClass: 'btn-green',
                applyLabel: "Apply",
                fromLabel: "From",
                format: "MMMM DD, YYYY", //will give you 2017-01-06
                toLabel: "To",
                cancelLabel: 'Cancel',
                customRangeLabel: 'Custom range'
            },
            ranges: {
                'Last 24 Hours': [moment().subtract(1, 'days'), moment()],
                'Last 7 Days': [moment().subtract(7, 'days'), moment()],
                'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                'All Time': ['2016-01-01', moment()]
            },
            "autoApply": true,
            eventHandlers: {
                'apply.daterangepicker': function (ev, picker) {
                    var start = ev.model.startDate.format('YYYY-MM-DD');
                    var end = ev.model.endDate.format('YYYY-MM-DD');
                    $scope.load_dashboard(start, end);
                }
            }
        }


        let profile_listing = API.service('profilelisting')
        profile_listing.one("").get()
            .then((response) => {
                let dataSet = response.plain()
                this.profile_listing_data = dataSet.data;
                var listing = this.profile_listing_data.response.listings;
                let published_listings = [];
                let un_published_listings = [];
                angular.forEach(listing, function (value, key) {
                    if (value.status == 'LIVE') {
                        published_listings.push(key);
                    } else {
                        un_published_listings.push(key);
                    }
                });
                let pblished_count = published_listings.length;
                let unpblished_count = un_published_listings.length;
                $scope.published_profiles = pblished_count;
                $scope.unpublished_profiles = unpblished_count;
            });


        /* Default values */
        $scope.total_appointments = 0;
        $scope.total_appointments_web = 0;
        $scope.total_appointments_phone = 0;
        $scope.appointemntPieLabels = ['Phone', 'Online']
        $scope.appointemntPieData = [0, 0]
        /* /Default values */
        $scope.DonutChartcolors = ['#f99265', '#7eda8a'];
        $scope.AppDonutChartcolors = ['#9E9E9E'];
        $scope.optionsdonut = { cutoutPercentage: 20 };
        $scope.pieRandomColors = ['#2ecc71', '#1abc9c', '#3498db', '#9b59b6', '#34495e', '#16a085', '#27ae60', '#2980b9', '#2980b9', '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22', '#e74c3c', '#ecf0f1', '#d35400', '#c0392b']
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

        /* *******************OPPORTUNITIES - NEED ACTION **************/

        let lead_services = API.service('leads-not-action', API.all('leads'))
        lead_services.one().get()
            .then((response) => {
                $scope.lead_need_action = response.plain().data.action_pending_leads
            })


        $scope.action_modal = function (item) {

            var current_tab = $scope.active_contact_tab;
            var contact_id = $scope.contact_id;
            var sms_to = $scope.sms_to;
            var lead_id = item.id;
            var lead_info = item;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/lead-details/add-task-modal.html',
                controller: ActionModalController,
                resolve: {
                    current_tab: function () {
                        return current_tab;
                    },
                    sms_to: function () {
                        return sms_to;
                    },
                    contact_id: function () {
                        return contact_id;
                    },
                    lead_id: function () {
                        return lead_id;
                    },
                    lead_info: function () {
                        return lead_info;
                    }
                }
            });
            return modalInstance;
        }

        $scope.load_tasks = function (lead_id) {
            let tasks = API.service('my-recent-tasks', API.all('leads'))
            tasks.one()
                .get({ due: 0 })
                .then((response) => {
                    var se = response.plain();
                    $scope.tasks = se.data.tasks;
                })
            $scope.show_lists = false;
        }
        $scope.load_tasks();
        $scope.change_task_status = function (task_id, is_open, index) {
            if (is_open == 0) {
                is_open = 1;
            } else {
                is_open = 0;
            }
            //if(is_open!=0){
            var elem = $scope.tasks.find(x => x.id === task_id);
            var index_ele = $scope.tasks.indexOf(elem);
            let lead_update = API.service('update-task/' + task_id, API.all('leads'))
            lead_update.post({
                is_open: is_open
            })
                .then(function (response) {
                    $scope.tasks[index_ele].open = is_open;
                })
            //}
        }
        $scope.delete_task = function (task_id) {
            let API = $scope.API
            var $state = $scope.$state
            var state_s = $scope.$state
            swal({
                title: 'Are you sure?',
                text: 'You will not be able to recover this data!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, delete it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                API.one('leads')
                    .one('task', task_id)
                    .remove()
                    .then(() => {
                        var $state = this.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Task has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            state_s.reload()
                        })
                    })
            })
        }


        /* ************* Graph and satics data Colors ************* */

        $scope.analyticsChartColours = [{
            fillColor: '#fcc5ae',
            strokeColor: '#D2D6DE',
            pointColor: '#000000',
            pointStrokeColor: '#fff',
            pointHighlightFill: '#fff',
            pointHighlightStroke: 'rgba(148,159,177,0.8)'
        }]

        $scope.load_dashboard = function (start_date_dashboard, end_date_dashboard) {
            var start_date_dashboard_v = moment(start_date_dashboard).format('MM-DD-YYYY');
            var end_date_dashboard_v = moment(end_date_dashboard).format('MM-DD-YYYY');
            start_date_dashboard = moment(start_date_dashboard).format('YYYY-MM-DD');
            end_date_dashboard = moment(end_date_dashboard).format('YYYY-MM-DD');

            $scope.recent_activites = []
            $scope.fromdate = start_date_dashboard_v;
            $scope.todate = end_date_dashboard_v;
            $scope.funnelWidget(start_date_dashboard, end_date_dashboard); //call dashboard widget
            if ($scope.can('dashboard.roi.widget') || $scope.can('dashboard.websiteanalytics.widget')) {
                var dashboardAnalytics = API.service('dashboard-analytics', API.all('analytics'))
                dashboardAnalytics.one('').get({ start_date: start_date_dashboard, end_date: end_date_dashboard })
                    .then((response) => {
                        $scope.roipercentage = 0;
                        $scope.total_appointments_a = 0;
                        $scope.totalexpenditure = 0;
                        $scope.revenue = 0;
                        $scope.totalinvest = 0;
                        $scope.revenuepercentage = 0;
                        $scope.exppercentage = 0;
                        $scope.costperappointment = 0;
                        $scope.cpadatavalues = [0, 1];
                        if (response) {
                            var visits_data = response.plain();
                            var chart_dates = [];
                            var chart_values = [];
                            var total_visits = 0;

                            angular.forEach(visits_data.data.visitor_report, function (value, key) {
                                chart_dates.push(value.date);
                                chart_values.push(value.visits);

                                $scope.total_analytics_visits = visits_data.data.metrics.users;
                                $scope.analytics_visit_labels = chart_dates
                                $scope.analytics_visit_series = ['Visits']
                                $scope.analytics_visit_data = [
                                    chart_values
                                ]
                                $scope.analytics_metric = visits_data.data.metrics;
                                $scope.sessions = visits_data.data.sessions;
                                $scope.analytics_browser_visits = visits_data.data.browser_visits
                                $scope.analytics_traffic_sources = visits_data.data.traffic_sources
                                if (visits_data.data.traffic_sources != false || visits_data.data.traffic_sources.length < 1) {
                                    $scope.analytics_traffic_sources.sort($scope.sort_by('visits', true, parseInt))
                                    $scope.analytics_browser_visits.desktop_visits.sort($scope.sort_by('visits', true, parseInt))
                                    $scope.analytics_browser_visits.mobile_visits.sort($scope.sort_by('visits', true, parseInt))
                                }

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
                                $scope.roipercentage = visits_data.data.roi_data.roi;
                                $scope.total_appointments_a = visits_data.data.roi_data.total_appointments;
                                $scope.totalexpenditure = visits_data.data.roi_data.totalexpenditure;
                                $scope.revenue = visits_data.data.roi_data.revenue;
                                $scope.totalinvest = visits_data.data.roi_data.totalinvest;
                                $scope.revenuepercentage = visits_data.data.roi_data.revenuepercentage;
                                $scope.exppercentage = visits_data.data.roi_data.exppercentage;
                                $scope.costperappointment = visits_data.data.roi_data.costperappointment;

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
                            if (visits_data.data.traffic_sources != false || visits_data.data.traffic_sources.length < 1) {
                                $scope.analytics_traffic_sources.sort($scope.sort_by('visits', true, parseInt))
                                $scope.analytics_browser_visits.desktop_visits.sort($scope.sort_by('visits', true, parseInt))
                                $scope.analytics_browser_visits.mobile_visits.sort($scope.sort_by('visits', true, parseInt))
                            }


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
                            $scope.roipercentage = visits_data.data.roi_data.roi;
                            $scope.total_appointments_a = visits_data.data.roi_data.total_appointments;
                            $scope.totalexpenditure = visits_data.data.roi_data.totalexpenditure;
                            $scope.revenue = visits_data.data.roi_data.revenue;
                            $scope.totalinvest = visits_data.data.roi_data.totalinvest;
                            $scope.revenuepercentage = visits_data.data.roi_data.revenuepercentage;
                            $scope.exppercentage = visits_data.data.roi_data.exppercentage;
                            $scope.costperappointment = visits_data.data.roi_data.costperappointment;
                            $scope.pieCpccolors = ['#7eda8a', '#3dadc7'];
                            $scope.cpadatavalues = [$scope.totalexpenditure > 0 ? $scope.totalexpenditure : 1, $scope.total_appointments > 0 ? $scope.total_appointments : 1];
                        }
                        /************ / Trafic Source Pie Chart ************/
                    });
            }

            if ($scope.can('dashboard.calls.widget')) {

                var dashboardAnalytics = API.service('call-widgets', API.all('dashboard'))
                dashboardAnalytics.one('').get({ start_date: start_date_dashboard, end_date: end_date_dashboard })
                    .then((response) => {

                        var calls_data = response.plain();

                        var call_summary = calls_data.data.calls_summary;
                        var calls_statics = calls_data.data.calls_statics;
                        var calls_total = []
                        var answered_calls = []
                        var unanswered_calls = []
                        angular.forEach(calls_data.data.calls_summary, function (value, key) {
                            calls_total.push(value.total);

                            if (value.call_status == 'completed') {
                                answered_calls.push(value.total);

                            } else {
                                unanswered_calls.push(value.total);
                            }
                        });

                        $scope.call_widget_total_calls = $scope.sum(calls_total);
                        $scope.call_widget_answered_calls = $scope.sum(answered_calls);
                        $scope.call_widget_unanswered_calls = $scope.sum(unanswered_calls);
                        $scope.callOptionsdonut = { showTooltips: false, percentageInnerCutout: 75, segmentShowStroke: false, animation: false }
                        if ($scope.sum(answered_calls) != 0 || $scope.sum(unanswered_calls) != 0) {

                            $scope.call_widget_pie_labels = ['Answered Calls', 'Unanswered Calls'];
                            $scope.CallsDonutChartcolors = ['#7eda8a', '#f99265'];
                            $scope.call_widget_pie_value = [$scope.sum(answered_calls), $scope.sum(unanswered_calls)];

                        } else {
                            $scope.CallsDonutChartcolors = ['#9E9E9E', '#9E9E9E'];
                            $scope.call_widget_pie_value = [1];
                            $scope.call_widget_pie_labels = ['Null'];
                        }
                        var call_graph_labels = [];
                        var call_graph_values = [];

                        angular.forEach(calls_statics, function (value, key) {
                            call_graph_labels.push(value.date);
                            call_graph_values.push(value.calls);
                        });
                        $scope.call_pie_labels = call_graph_labels;
                        // $scope.call_pie_values=call_graph_values;
                        $scope.call_pie_values = [
                            call_graph_values
                        ]
                    });
            }

            if ($scope.can('dashboard.appointment.widget')) {

                var dashboardAppointments = API.service('appointments-widgets', API.all('dashboard'))
                dashboardAppointments.one('').get({ start_date: start_date_dashboard, end_date: end_date_dashboard })
                    .then((response) => {
                        $scope.total_appointments_phone = 0;
                        $scope.total_appointments_web = 0;
                        var appointments_data = response.plain();
                        $scope.total_appointments = appointments_data.data.appointments_count;
                        var phone_app = $filter("filter")(appointments_data.data.appointments_sources, { scheduling_method: 'phone' });
                        var web_app = $filter("filter")(appointments_data.data.appointments_sources, { scheduling_method: 'web' });

                        if (phone_app.length > 0) {
                            $scope.total_appointments_phone = phone_app[0].total;
                        }
                        if (web_app.length > 0) {
                            $scope.total_appointments_web = web_app[0].total;
                        }
                        $scope.appOptionsdonut = { showTooltips: false, percentageInnerCutout: 75, segmentShowStroke: false, animation: false }
                        if ($scope.total_appointments_phone != 0 || $scope.total_appointments_web != 0) {

                            $scope.AppDonutChartcolors = ['#f99265', '#7eda8a'];
                            $scope.appointemntPieLabels = ['Phone', 'Online']
                            $scope.appointemntPieData = [$scope.total_appointments_phone, $scope.total_appointments_web]

                        } else {
                            $scope.appointemntPieLabels = ['Null']
                            $scope.appointemntPieData = [1];
                            $scope.AppDonutChartcolors = ['#9E9E9E', '#9E9E9E'];
                        }

                    });
            }


            if ($scope.can('dashboard.sociale.widget')) {
                var FacebookInsight = API.service('facebook-page-insight', API.all('dashboard'));
                FacebookInsight.one("").get({ start_date: start_date_dashboard, end_date: end_date_dashboard }).then((response) => {
                    $scope.page_engaged_users = response.data.facebookPageInsightdata.page_post_engagements;
                    $scope.page_fans = response.data.facebookPageInsightdata.page_fans;
                    $scope.page_impressions = response.data.facebookPageInsightdata.page_impressions;
                    $scope.page_views_total = response.data.facebookPageInsightdata.page_views_total;
                    $scope.page_engaged_users_p = response.data.percentageData.engagement_percent;
                    $scope.page_fans_p = response.data.percentageData.fan_percentage;
                    $scope.page_impressions_p = response.data.percentageData.impression_percent;
                    $scope.page_views_total_p = response.data.percentageData.view_percent;
                });


                var TwitterData = API.service('twitter-timeline', API.all('dashboard'));
                TwitterData.one("").get({ start_date: start_date_dashboard, end_date: end_date_dashboard }).then((response) => {
                    $scope.twitter_follower = response.data.TwitterData.followers_count;
                    $scope.twitter_friends_count = response.data.TwitterData.friends_count;
                    $scope.tweets_count = response.data.TwitterData.tweets;

                });

                var LinkedInData = API.service('linked-in-views', API.all('dashboard'));
                LinkedInData.one("").get({ start_date: start_date_dashboard, end_date: end_date_dashboard }).then((response) => {
                    $scope.linkedin_connection = response.data.linkedin;
                });
            }

            if ($scope.can('dashboard.appointment.widget')) {

                var newvsreturning = API.service('new-vs-returning-widgets', API.all('dashboard'))
                newvsreturning.one('').get({ start_date: start_date_dashboard, end_date: end_date_dashboard })
                    .then((response) => {
                        $scope.new_user = response.data.new_user
                        $scope.returning_user = response.data.returning_user
                    });
            }

            if ($scope.can('dashboard.recentactivity.widget')) {

                var recentActivites = API.service('recent-activity')
                recentActivites.one('').get({ start_date: start_date_dashboard, end_date: end_date_dashboard })
                    .then((response) => {
                        var recent_activites_data = response.plain();
                        $scope.recent_activites = recent_activites_data.data.activities;
                    });
            }
            if ($scope.can('dashboard.review.widget')) {
                /* Dashbard review widgets */
                var review_data = API.service('dashboard-widget', API.all('reviews'))
                review_data.one('').get()
                    .then((response) => {
                        var res = response.plain();
                        $scope.total_reviws = res.data.total_reviws
                        var negtive = res.data.negtive_reviews
                        var positive = res.data.positive_reviews
                        $scope.negtive_review = 0
                        $scope.positive_review = 0
                        if ($scope.total_reviws != 0) {
                            $scope.negtive_review = ((negtive / $scope.total_reviws) * 100).toFixed(0)
                            $scope.positive_review = ((positive / $scope.total_reviws) * 100).toFixed(0)
                        }

                        if ($scope.total_reviws != 0) {
                            $scope.rating_five = ((res.data.star_ratings[0].total_reviews / $scope.total_reviws) * 100).toFixed(0)
                            $scope.rating_four = ((res.data.star_ratings[1].total_reviews / $scope.total_reviws) * 100).toFixed(0)
                            $scope.rating_three = ((res.data.star_ratings[2].total_reviews / $scope.total_reviws) * 100).toFixed(0)
                            $scope.rating_two = ((res.data.star_ratings[3].total_reviews / $scope.total_reviws) * 100).toFixed(0)
                            $scope.rating_one = ((res.data.star_ratings[4].total_reviews / $scope.total_reviws) * 100).toFixed(0)

                            var total_average_rating = Number(res.data.star_ratings[0].total_reviews) + Number(res.data.star_ratings[1].total_reviews) + Number(res.data.star_ratings[2].total_reviews) + Number(res.data.star_ratings[3].total_reviews) + Number(res.data.star_ratings[4].total_reviews)
                            var total_average_review = Number((res.data.star_ratings[0].total_reviews) * 5) + Number((res.data.star_ratings[1].total_reviews) * 4) + Number((res.data.star_ratings[2].total_reviews) * 3) + Number((res.data.star_ratings[3].total_reviews) * 2) + Number((res.data.star_ratings[4].total_reviews) * 1)
                            $scope.total_average = (total_average_review / total_average_rating).toFixed(1)
                        }

                    });
            }


            if ($scope.can('dashboard.mailchimp.widget')) {
                let analytics = API.service('campaign-statics', API.all('email-marketing'));
                let last_list_id = '';
                analytics.one("").get({ start_date: start_date_dashboard, end_date: end_date_dashboard, count: 10 })
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
            }

        }
        $scope.load_dashboard(this.start_date, this.end_date)

        $scope.onClick = function () { }
        //$scope.onClick = function () {}

        $scope.barChartLabels = ['Januarys', 'February', 'March', 'April', 'May', 'June', 'July']
        $scope.barChartSeries = ['Series A']
        $scope.barChartData = [
            [65, 59, 80, 81, 56, 55, 40]

        ]
        $scope.pieLabels = ['Total Appointments', 'Marketing Spend']
        $scope.pieData = [611, 50]
        $scope.pieCcolors = ['#1585b3', '#7eda8a'];
        $scope.pieCoptions = { showTooltips: false, percentageInnerCutout: 75, segmentShowStroke: false, animation: false }

        $scope.barChartColours = [{
            pointColor: 'rgba(148,159,177,1)',
            pointStrokeColor: '#fff',
            pointHighlightFill: '#fff',
            pointHighlightStroke: 'rgba(148,159,177,0.8)'
        }

        ]

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
        $scope.sort_by = function (field, reverse, primer) {
            var key = primer ?
                function (x) { return primer(x[field]) } :
                function (x) { return x[field] };
            reverse = !reverse ? 1 : -1;
            return function (a, b) {
                return a = key(a), b = key(b), reverse * ((a > b) - (b > a));
            }
        }

        $scope.get_per = function ($amount, $total) {
            return $amount / $total * 100;
        }
        $scope.uCanTrust = function (string) {
            return $sce.trustAsHtml(string);
        }

    }
}
class ActionModalController {
    constructor($stateParams, $scope, $state, $location, API, $uibModal, current_tab, contact_id, sms_to, lead_id, $uibModalInstance, $timeout, lead_info) {
        'ngInject'
        var uibModalInstance = $uibModalInstance;
        $scope.task_added = false;
        $scope.formSubmitted = false;
        $scope.task = {
            title: '',
            duration: '30 min',
            priority: 'high',
            note: '',
            contact: lead_info.contact.first_name + ' ' + lead_info.contact.last_name,
        };
        var user_data = JSON.parse(localStorage.getItem('user_data'));
        $scope.task.user_id = user_data.id;

        /* Agensts */
        let lead_assignees = API.service('lead-assignees', API.all('leads'))
        lead_assignees.one().get()
            .then((response) => {
                $scope.lead_assignees = response.plain().data.lead_assignees
                $scope.task.user_id = $scope.lead_assignees[0].id
            });

        $scope.show_time_input = false
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.mindate = new Date();
        $scope.task.schedule_time = new Date();
        $scope.isOpen = false;
        $scope.openCalendar = function (e) {
            e.preventDefault();
            e.stopPropagation();
            $scope.mindate = new Date();
            $scope.isOpen = true;
        };

        let tasktpe = API.service('task-types', API.all('leads'))
        tasktpe.one()
            .get()
            .then((response) => {
                var res = response.plain();
                var task_types = res.data.task_types;
                $scope.task_types = task_types;
                $scope.default_task_types = $scope.task_types[0].id;
                $scope.task.type = $scope.task_types[0].id;
            })
        $scope.add_new_task = function () {
            $scope.formSubmitted = true;

            $scope.task_submit = {};
            var date_S = moment($scope.task.schedule_time)
                .format('YYYY-MM-DD');
            var time_S = moment($scope.task.schedule_time)
                .format('HH:mm');
            var action_date = date_S + ' ' + time_S + ':00';
            $scope.task_submit = {
                type: $scope.task.type,
                title: $scope.task.title,
                action_date: action_date,
                task_time: $scope.task.time,
                duration: $scope.task.duration,
                priority: $scope.task.priority,
                description: $scope.task.note,
                assignee: $scope.task.user_id
            }

            var postTask = API.service('add-task/' + $scope.lead_id, API.all('leads'))
            postTask.post($scope.task_submit)
                .then((response) => {
                    $scope.task_added = true;
                    $timeout(function () {
                        $scope.closemodal();
                        $state.go('app.landing', {
                            contactId: $scope.contact_id
                        }, {
                                reload: true
                            });
                    }, 3000);
                });
        }
        $scope.lead_id = lead_id;
        $scope.contact_id = contact_id;
        $scope.add_ac_form = false;
        $scope.update_task = function () {
            $scope.formSubmitted = true;
            $scope.task_submit = {};
            var date_S = moment($scope.task.date)
                .format('YYYY-MM-DD');
            var time_S = moment($scope.task.time)
                .format('HH:mm');
            var action_date = date_S + ' ' + time_S + ':00';
            $scope.task_update = {
                type: $scope.task.type_id,
                title: $scope.task.title,
                action_date: action_date,
                task_time: $scope.task.time,
                duration: $scope.task.duration,
                priority: $scope.task.priority,
                description: $scope.task.description
            }

            var postTask = API.service('update-task/' + $scope.task.id, API.all('leads'))
            postTask.post($scope.task_update)
                .then((response) => {
                    $scope.task_added = true;
                    $timeout(function () {
                        $scope.closemodal();
                        $state.go('app.viewcontact', {
                            contactId: $scope.contact_id
                        }, {
                                reload: true
                            });
                    }, 3000);
                });
        }

        $scope.today = function () {
            $scope.task.date = new Date();
        };
        $scope.today();

        $scope.clear = function () {
            $scope.task.date = null;
        };

        $scope.inlineOptions = {
            customClass: getDayClass,
            minDate: new Date(),
            showWeeks: true
        };
        $scope.dateOptions = {
            formatYear: 'yy',
            maxDate: new Date(2020, 5, 22),
            minDate: new Date(),
            startingDay: 1
        };
        $scope.dateOptions = {};
        $scope.dateOptions.minDate = new Date();
        $scope.dateOptions.showWeeks = false;

        // Disable weekend selection
        function disabled(data) {
            var date = data.date,
                mode = data.mode;
            return mode === 'day' && (date.getDay() === 0 || date.getDay() === 6);
        }

        $scope.toggleMin = function () {
            $scope.inlineOptions.minDate = $scope.inlineOptions.minDate ? null : new Date();
            $scope.dateOptions.minDate = $scope.inlineOptions.minDate;
        };

        $scope.toggleMin();


        $scope.open1 = function () {
            $scope.popup1.opened = true;
        };

        $scope.open2 = function () {
            $scope.popup2.opened = true;
        };

        $scope.setDate = function (year, month, day) {
            $scope.task.date = new Date(year, month, day);
        };

        $scope.formats = ['dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy', 'shortDate'];
        $scope.format = $scope.formats[0];
        $scope.altInputFormats = ['M!/d!/yyyy'];

        $scope.popup1 = {
            opened: false
        };

        $scope.popup2 = {
            opened: false
        };

        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        var afterTomorrow = new Date();
        afterTomorrow.setDate(tomorrow.getDate() + 1);
        $scope.events = [{
            date: tomorrow,
            status: 'full'
        },
        {
            date: afterTomorrow,
            status: 'partially'
        }
        ];

        function getDayClass(data) {
            var date = data.date,
                mode = data.mode;
            if (mode === 'day') {
                var dayToCheck = new Date(date)
                    .setHours(0, 0, 0, 0);

                for (var i = 0; i < $scope.events.length; i++) {
                    var currentDay = new Date($scope.events[i].date)
                        .setHours(0, 0, 0, 0);

                    if (dayToCheck === currentDay) {
                        return $scope.events[i].status;
                    }
                }
            }

            return '';
        }

        $scope.task.time = new Date();

        $scope.hstep = 1;
        $scope.mstep = 15;
        $scope.time_arrowkeys = false;
        $scope.options = {
            hstep: [1, 2, 3],
            mstep: [1, 5, 10, 15, 25, 30],
            arrowkeys: false
        };

        $scope.ismeridian = true;
        $scope.toggleMode = function () {
            $scope.ismeridian = !$scope.ismeridian;
        };

        $scope.update = function () {
            var d = new Date();
            d.setHours(14);
            d.setMinutes(0);
            $scope.task.time = d;
        };
    }
}
class TwillioStepOneModalController {
    constructor($stateParams, $scope, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.ownerName = ''
        $scope.areaCode = ""
        var number = ''
        $scope.loading_chat = false
        var uibModalInstance = $uibModalInstance;
        if ($window.localStorage.user_company_details) {
            var companyDetail = JSON.parse($window.localStorage.getItem('user_company_details'))
            $scope.ownerName = companyDetail.name
        }


        $scope.closemodal = function () {
            $state.go('app.landing', { reload: true })
            $uibModalInstance.close();
        }
        $scope.allocateFunction = function () {
            $scope.loading_chat = true
            let areaCodeupdate = API.service('new-twilio-number', API.all('company'))
            areaCodeupdate.post({
                area_code: $scope.areaCode
            })
                .then(function (response) {
                    $uibModalInstance.close();
                    $scope.loading_chat = false
                    number = response.data.number
                    const modalInstance = $uibModal.open({
                        animation: true,
                        templateUrl: '/twillio-step-2-modal.html',
                        controller: TwillioStepTwoModalController,
                        resolve: {
                            phnNumber: function () {
                                return number;
                            }
                        }
                    });
                    return modalInstance;

                }, (response) => {
                    $uibModalInstance.close();
                    $scope.loading_chat = false
                })
        }

    }
}
class TwillioStepTwoModalController {
    constructor($stateParams, phnNumber, $scope, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'

        $scope.ownerName = ''
        $scope.ownerNumber = phnNumber
        $scope.recordCalls = '1'
        $scope.forwardTo = ''
        var uibModalInstance = $uibModalInstance;
        if ($window.localStorage.user_company_details) {
            var companyDetail = JSON.parse($window.localStorage.getItem('user_company_details'))
            $scope.ownerName = companyDetail.name
        }
        $scope.closemodal = function () {
            $state.go('app.landing', { reload: true })
            $uibModalInstance.close();
        }
        $scope.upDateFunction = function () {
            $scope.loading_chat = true
            let updatePhone = API.service('twillio-forwarding', API.all('company'))
            updatePhone.post({
                forwarding_to: $scope.forwardTo,
                recording_status: $scope.recordCalls
            })
                .then(function (response) {
                    $scope.loading_chat = false
                    $state.reload()
                    $uibModalInstance.close();
                }, (response) => {
                    $uibModalInstance.close();
                    $scope.loading_chat = false
                    $state.reload()
                })
        }

    }
}
export const DashboardComponent = {
    templateUrl: './views/app/pages/dashboard/dashboard.component.html',
    controller: DashboardController,
    controllerAs: 'vm',
    bindings: {}
}
