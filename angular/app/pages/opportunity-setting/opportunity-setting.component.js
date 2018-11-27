class OpportunitySettingController {
    constructor($scope, $stateParams, $state, API, uploads, $window, $rootScope) {
        'ngInject'
        $scope.$state = $state
        this.alerts = []
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        $scope.timer = [
            { 'key': '30 min', 'value': '30' },
            { 'key': '1hr', 'value': '60' },
            { 'key': '2hr', 'value': '120' },
            { 'key': '3hr', 'value': '180' },
            { 'key': '4hr', 'value': '240' },
            { 'key': '5hr', 'value': '300' },
            { 'key': '6hr', 'value': '360' },
            { 'key': '7hr', 'value': '420' },
            { 'key': '8hr', 'value': '480' },
            { 'key': '9hr', 'value': '540' },
            { 'key': '10hr', 'value': '600' }
        ];
        $scope.opportunity_services = '';
        $scope.opportunity_stage = '';
        $scope.pending_email_notification_time = '';
        $scope.lead_high_time = '';
        $scope.lead_medium_time = '';
        let UserData = API.service('opportunity-services', API.all('company'));
        UserData.one(1).get()
            .then((response) => {
                if (response) {
                    let userResponse = response.plain();
                    $scope.opportunity_services = userResponse.data.services;
                    $scope.opportunity_stages = userResponse.data.stages;
                    $scope.pending_email_notification_time = userResponse.data.pending_email_notification_time;
                    $scope.lead_high_time = userResponse.data.lead_high_time;
                    $scope.lead_medium_time = userResponse.data.lead_medium_time;
                }
            });

        $scope.addReferrer = function () {
            var newItemNo = $scope.opportunity_services.length + 1;
            $scope.opportunity_services.push({ 'id': 'referrer' + newItemNo });
        };

        $scope.save = function () {
            let $state = this.$state
            
            var bigCities = $scope.opportunity_services.filter(function (e) {
                return e.name
            });
           
            let ComapnySettings = API.service('update-opportunity-settings', API.all('company'));
            ComapnySettings.post({ services: bigCities, stages: $scope.opportunity_stages, lead_medium_time: $scope.lead_medium_time, lead_high_time: $scope.lead_high_time, pending_email_notification_time: $scope.pending_email_notification_time })
                .then((response) => {

                    let alert = { type: 'success', 'title': 'Success!', msg: response.data }
                    $state.go($state.current, { alerts: alert })
                });

        }

        $scope.removeit = function (id, type) {
            if (isNaN(id)) {
                var lastItem = $scope.opportunity_services.length - 1;
                $scope.opportunity_services.splice(lastItem);
            }
            else {

                var $state = this.$state
                var state_s = $scope.$state
                swal({
                    title: 'Are you sure?',
                    text: 'You will not be able to recover this data!',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DD6B55',
                    confirmButtonText: 'Yes, delete it!',
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    html: false
                }, function () {
                    let ComapnySettings = API.service('delete-opportunity-settings', API.all('company'));
                    ComapnySettings.post({ id: id, type: type }).then(function (response) {
                        swal({
                            title: 'Deleted!',
                            text: 'User Permission has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            state_s.reload()
                        })
                    })
                })
            }

        }

    }

    $onInit() { }
}

export const OpportunitySettingComponent = {
    templateUrl: './views/app/pages/opportunity-setting/opportunity-setting.component.html',
    controller: OpportunitySettingController,
    controllerAs: 'vm',
    bindings: {}
}
