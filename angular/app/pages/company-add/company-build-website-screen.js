class CompanyBuildScreenController {
    constructor($scope, $stateParams, $state, SAAPI, $http, $window, $uibModal) {
        'ngInject'

        this.$state = $state
        var apiKey = ''
        var comp = {}
        var companyId = ''
        if ($stateParams.alerts || $stateParams.companyOBJ) {
            comp = $stateParams.companyOBJ
            apiKey = comp.api_key
            companyId = comp.company_id
            $window.localStorage.companyOBJ = JSON.stringify($stateParams.companyOBJ)
            $scope.alerts = { type: 'success', 'title': 'Success!', msg: 'Company has been created successfully' }

        } else if ($window.localStorage.companyOBJ) {
            apiKey = JSON.parse($window.localStorage.getItem('companyOBJ')).api_key
            companyId = JSON.parse($window.localStorage.getItem('companyOBJ')).company_id
        }

        $scope.showSitebuilderModal = function () {

            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/company-add/company-site-builder-modal.html',
                controller: SitebuilderModalController,
                windowClass: 'email-temp-class',
                backdrop: 'static',
                size: 'md',
                resolve: {
                    SiteKey: function () {
                        return apiKey;
                    },
                    compID: function () {
                        return companyId;
                    }
                }
            });
            // return modalInstance;
        }
        $scope.goBackfunction = function (param) {
            if (param == '2') {
                $state.go('app.completeprofile', { companyID: companyId })

            } else {
                $state.go('app.companyadd', { companyID: companyId })
            }
        }

    }

    $onInit() { }
}
class SitebuilderModalController {
    constructor($stateParams, $scope, compID, SiteKey, $state, $http, $location, SAAPI, $uibModal, $uibModalInstance, $timeout, $rootScope, $window, $sce) {
        'ngInject'
        var string_url = 'https://sitebuilder.evergenius.co/plesk-site/builder/sites/create_site_1.php?key='
        $scope.apiKey = string_url + SiteKey
        $scope.trustSrc = function (src) {
            return $sce.trustAsResourceUrl(src);
        }

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }
}
export const CompanyBuildScreenComponent = {
    templateUrl: './views/app/pages/company-add/company-build-website-screen.html',
    controller: CompanyBuildScreenController,
    controllerAs: 'vm',
    bindings: {}
}