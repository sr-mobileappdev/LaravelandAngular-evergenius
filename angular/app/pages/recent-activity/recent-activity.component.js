class RecentActivityController {
    constructor(API, $state, $scope, $sce, AclService) {
        'ngInject'
        $sce = $sce;

        this.API = API
        this.$state = $state
        this.alerts = []
        this.publishers = [];
        this.$scope = $scope;
        $scope.list_id = [];
        $scope.list_i = 5;
        this.start_date = moment().subtract(10, 'days')
        this.end_date = moment()
        this.min_date = moment().subtract(182, 'days')
        this.max_date = moment()
        $scope.activity_list = [];

        this.can = AclService.can
        if (!this.can('recent.activity')) {
            $state.go('app.unauthorizedAccess');
        }

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


        $scope.datePicker = {
            startDate: this.start_date,
            endDate: this.end_date
        };


        $scope.load_recent_activity = function (start_date_activity, end_date_activity) {
            $scope.iframeHeight = {
                "max-height": ($(window).height() - 100) + "px"
            };
            let start_date_list = moment(start_date_activity).format('YYYY-MM-DD');
            let end_date_list = moment(end_date_activity).format('YYYY-MM-DD');
            let recent_activity_service = API.service('recent-activity');
            let last_list_id = '';

            if ($scope.list_id[$scope.list_id.length - 1] != undefined) {
                last_list_id = $scope.list_id[$scope.list_id.length - 1];
                $scope.list_i = $scope.list_i + 1;
            }
            if ($scope.list_i >= 5) {
                $scope.busy = true;
                recent_activity_service.one("").get({
                    start_date: start_date_list,
                    end_date: end_date_list,
                    last_id: last_list_id
                })
                    .then((response) => {
                        $scope.busy = false;
                        let dataSet = response.plain()
                        angular.forEach(dataSet.data.activities, function (value, key) {
                            if ($scope.list_id.indexOf(value.activity_id) == -1) {
                                $scope.activity_list.push(value);
                                $scope.list_id.push(value.activity_id);
                            }
                        });
                    });
                $scope.list_i = 0;
            }
        }
        $scope.uCanTrust = function (string) {
            return $sce.trustAsHtml(string);
        }
    }


    $onInit() { }

}

export const RecentActivityComponent = {
    templateUrl: './views/app/pages/recent-activity/recent-activity.component.html',
    controller: RecentActivityController,
    controllerAs: 'vm',
    bindings: {}
}
