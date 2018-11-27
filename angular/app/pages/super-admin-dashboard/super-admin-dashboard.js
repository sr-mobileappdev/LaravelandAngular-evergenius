class SuperDashboardController {
    constructor($scope, API, $filter, $sce, $state, AclService, $uibModal, $http, $window) {
        'ngInject'
        this.analytics = []
        this.can = AclService.can
        $scope.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        this.user_role = this.roles[0]
        var roles = AclService.getRoles();

        $scope.analytics_metric = {
            users: 0,
            bounce_date: 0,
            page_views: 0
        }

        $scope.API = API;
        $scope.$state = $state;

        this.start_date = moment().subtract(30, 'days')
        $scope.mailchimp_start_date = moment().subtract(90, 'days');
        $scope.mailchimp_end_date = moment();
        this.end_date = moment()

        this.min_date = moment().subtract(2000, 'days')
        this.max_date = moment()
        $scope.funnel_text = 'Avg. Lead Response Time: 0 hr 0 min';

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

        //superadmin/dashboard-stats?start_date=2018-01-9
        $scope.load_dashboard = function (start, end) {
            var start_date_dashboard = moment(start).format('YYYY-MM-DD');
            var end_date_dashboard = moment(end).format('YYYY-MM-DD');
            var dashboardAnalytics = API.service('dashboard-stats', API.all('superadmin'))
            dashboardAnalytics.one('').get({ start_date: start_date_dashboard, end_date: end_date_dashboard })
                .then((response) => {
                    $scope.dashboardArray = response.data.dashborad_data[0]
                })
        }

        $scope.load_dashboard(this.start_date, this.end_date);

    }
}

export const SuperDashboardComponent = {
    templateUrl: './views/app/pages/super-admin-dashboard/super-admin-dashboard.html',
    controller: SuperDashboardController,
    controllerAs: 'vm',
    bindings: {}
}
