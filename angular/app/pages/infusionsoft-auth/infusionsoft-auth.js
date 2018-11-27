class ManageCompaniesListsController {
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
        this.$window = $window
    }


    $onInit() { }
}

export const ManageCompaniesListComponent = {
    templateUrl: './views/app/pages/manage-companies/manage-companies.component.html',
    controller: ManageCompaniesListsController,
    controllerAs: 'vm',
    bindings: {}
}
