class ConnectGoogleAnalyticsController {
    constructor($scope, $stateParams, $state, API, $window, $rootScope, $uibModal) {
        'ngInject'

        this.$state = $state;
        this.formSubmitted = false;
        this.alerts = [];
        this.API = API;
        $scope.analyticlist = []
        $scope.googleAnalyticlist = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: '/analytic-modal.html',
                controller: googleAnalyticModalController,

            });
            return modalInstance;
        }
        $scope.googleAnalyticlist()

    }

    $onInit() { }
}

class googleAnalyticModalController {
    constructor($stateParams, $scope, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.loading_chat = false

        var analyticList = API.service('googl-analytic-sites', API.all('analytics'))
        analyticList.one().get()
            .then((response) => {
                $scope.analyticlist = response.plain().data.sites

            });
        $scope.integrate = function (id) {
            $scope.loading_chat = true
            var addComment = API.service('google-analytic-site', API.all('analytics'));
            addComment.post({ "site_id": id })
                .then((response) => {
                    $state.go('app.landing', { reload: true })
                    $uibModalInstance.close();
                    $scope.loading_chat = false
                }, (response) => {
                    $uibModalInstance.close();
                    $scope.loading_chat = false
                    $state.go('app.landing', { reload: true })
                })
        }
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $state.go('app.landing', { reload: true })
            $uibModalInstance.close();
        }

    }
}
export const ConnectGoogleAnalyticsComponent = {
    templateUrl: './views/app/pages/connect-google-analytics/connect-google-analytics.html',
    controller: ConnectGoogleAnalyticsController,
    controllerAs: 'vm',
    bindings: {}
}
