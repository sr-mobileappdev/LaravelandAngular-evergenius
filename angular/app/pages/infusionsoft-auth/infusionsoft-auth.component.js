class infusionsoftAuthController {
    constructor($scope, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location, $timeout) {
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
        this.can = AclService.can
        this.$window = $window
        if (!this.can('superadmin.infusionsoft')) {
            $state.go('app.unauthorizedAccess');
        }
        let infusionsoftStatus = SAAPI.service('infusionsoft-connected', SAAPI.all('superadmin'))
        infusionsoftStatus.one("").get()
            .then((response) => {
                let res = response.plain();
                $scope.infstatus = res.data.status;
            });

        $scope.logout_infusion = function () {
            let infusionsoftStatus = SAAPI.service('logout-infusion', SAAPI.all('superadmin'))
            infusionsoftStatus.one("").get()
                .then((response) => {
                    $state.go('app.infusionsoftauth', {}, { reload: true })
                    $state.go()
                });
        }

    }

    $onInit() { }
}

export const InfusionsoftAuthComponent = {
    templateUrl: './views/app/pages/infusionsoft-auth/infusionsoft-auth.html',
    controller: infusionsoftAuthController,
    controllerAs: 'vm',
    bindings: {}
}
