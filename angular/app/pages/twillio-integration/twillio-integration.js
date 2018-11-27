class TwillioIntegrationController {
    constructor($scope, $stateParams, $state, API, $window, $rootScope, $uibModal) {
        'ngInject'

        this.$state = $state;
        this.formSubmitted = false;
        this.alerts = [];
        this.API = API;
        $scope.analyticlist = []
     
        

    }



    $onInit() { }
}


export const  TwillioIntegrationComponent = {
    templateUrl: './views/app/pages/twillio-integration/twillio-integration.html',
    controller:  TwillioIntegrationController,
    controllerAs: 'vm',
    bindings: {}
}
