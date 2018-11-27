class CompanySmtpSettingsController {
    constructor($scope, $stateParams, $state, API, uploads, $window, $rootScope) {
        'ngInject'

        this.$state = $state;
        this.formSubmitted = false;
        this.alerts = [];
        this.API = API;
        $scope.companyeditdata = {};
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }

        let UserData = uploads.service('smtp-settings', API.all('company'))
        UserData.one('').get().then((response) => {
            $scope.companyeditdata = API.copy(response)

        })

        $scope.save = function (isValid) {
            this.file_error = false;
            if (isValid) {
                let error_file = true;
                let ComapnySettings = API.service('smtp-settings', API.all('company'));
                ComapnySettings.post({ 'sendgrid_api_key': $scope.companyeditdata.data.sendgrid_api_key, 'em_from_name': $scope.companyeditdata.data.em_from_name, 'em_from_email': $scope.companyeditdata.data.em_from_email })
                    .then((response) => {
                        error_file = true;
                        let alert = { type: 'success', 'title': 'Success!', msg: 'Data has been updated successfully' }
                        $state.go($state.current, { alerts: alert })

                    }, (response) => {
                        let alert = { type: 'danger', 'title': 'Error!', msg: response.data.message }
                        $state.go($state.current, { alerts: alert })
                    })

            } else {
                this.formSubmitted = true
            }
        }
    }



    $onInit() { }
}

export const CompanySmtpSettingsComponent = {
    templateUrl: './views/app/pages/company-smtp-settings/company-smtp-settings.component.html',
    controller: CompanySmtpSettingsController,
    controllerAs: 'vm',
    bindings: {}
}
