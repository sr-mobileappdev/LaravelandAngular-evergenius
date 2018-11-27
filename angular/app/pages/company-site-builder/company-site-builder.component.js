class CompanySiteBuilderController {
    constructor($stateParams, $state, SAAPI) {
        'ngInject'

        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.userRolesSelected = []

        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }

        this.upload_img_block = false;
        let companyId = $stateParams.companyId
        this.companyId = companyId;

        let UserData = SAAPI.service('company-details', SAAPI.all('superadmin'))
        UserData.one(companyId).get()
            .then((response) => {
                let userResponse = response.plain()
                this.companyeditdata = SAAPI.copy(response)
            })

        this.save = function (isValid) {
            var companyId = this.companyId
            if (isValid) {
                let error_file = true;
                let $state = this.$state
                let UserData = SAAPI.service('build-company-website', SAAPI.all('superadmin'))
                UserData.post(this.companyeditdata)
                    .then((response) => {
                        error_file = true;
                        let alert = { type: 'success', 'title': 'Success!', msg: 'Company has been created successfully' }

                    }, (response) => {
                        let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                        $state.go($state.current, { alerts: alert })

                    })
            } else {
                this.formSubmitted = true
            }

        }

    }

    $onInit() { }
}

export const CompanySiteBuilderComponent = {
    templateUrl: './views/app/pages/company-site-builder/company-site-builder.component.html',
    controller: CompanySiteBuilderController,
    controllerAs: 'vm',
    bindings: {}
}
