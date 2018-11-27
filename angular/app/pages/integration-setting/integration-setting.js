class IntegrationSettingController {
    constructor($scope, $auth, $stateParams, $state, $uibModal, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, AclService, $location) {
        'ngInject'
        this.$auth = $auth;
        this.$location = $location;
        this.API = API;
        this.$state = $state
        $scope.skipNotShow = true
        $scope.userSetup = false
        $scope.twilioSetup = true
        $scope.sendgridSetup = false
        $scope.providersSetup = false
        $scope.notificationSetup = false
        $scope.googleAnalytic = true
        $scope.ShowEditGoogle = true
        $scope.ShowEditTwillio = true
        $scope.countryCode = '+1'
        $scope.api_key = JSON.parse($window.localStorage.getItem('user_company_details')).api_key
        $scope.appUrl = $window.localStorage.application_url
        var getConfiguration = API.service('company-configurations', API.all('company'))
        getConfiguration.one().get()
            .then((response) => {
                $scope.configDetail = response.data.configs
                if ($scope.configDetail.twilio_number) {
                    var newvar = $scope.configDetail.twilio_number.slice(2, 12);

                    var numbers = newvar.replace(/\D/g, ''),
                        char = { 0: '(', 3: ') ', 6: ' - ' };
                    newvar = '';
                    for (var i = 0; i < numbers.length; i++) {
                        newvar += (char[i] || '') + numbers[i];
                    }
                    $scope.twillioNumber = newvar
                }
                if ($scope.configDetail.twillio_forwaring_to) {
                    var forwardVar = $scope.configDetail.twillio_forwaring_to
                    var fornumbers = forwardVar.replace(/\D/g, ''),
                        char = { 0: '(', 3: ') ', 6: ' - ' };
                    forwardVar = '';
                    for (var i = 0; i < fornumbers.length; i++) {
                        forwardVar += (char[i] || '') + fornumbers[i];
                    }
                    $scope.forwardTwillioNo = forwardVar
                }

            })

        $scope.twillioIntegration = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: '/twillio-step-1-modal.html',
                controller: TwillioStepOneModalController,

            });
            return modalInstance;
        }

        $scope.editTwillio = function (twi, ford) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: '/twillio-step-2-modal.html',
                controller: TwillioStepTwoModalController,
                resolve: {
                    phnNumber: function () {
                        return twi;
                    },
                    forWardto: function () {
                        return ford;
                    }
                }
            });
            return modalInstance;
        }
        $scope.editGoogleanalytic = function () {
            var removeAnalytic = API.service('remove-integration/google_analytics', API.all('company'))
            removeAnalytic.one().get()
                .then((response) => {
                    $state.reload()
                })
        }
    }

    $onInit() { }
}
class TwillioStepOneModalController {
    constructor($stateParams, $scope, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.ownerName = ''
        $scope.areaCode = ""
        var number = ''
        $scope.loading_chat = false
        var uibModalInstance = $uibModalInstance;
        if ($window.localStorage.user_company_details) {
            var companyDetail = JSON.parse($window.localStorage.getItem('user_company_details'))
            $scope.ownerName = companyDetail.name
        }


        $scope.closemodal = function () {
            $state.reload()
            $uibModalInstance.close();
        }
        $scope.allocateFunction = function () {
            $scope.loading_chat = true
            var ford = ''
            let areaCodeupdate = API.service('new-twilio-number', API.all('company'))
            areaCodeupdate.post({
                area_code: $scope.areaCode
            })
                .then(function (response) {
                    $uibModalInstance.close();
                    $scope.loading_chat = false
                    number = response.data.number
                    const modalInstance = $uibModal.open({
                        animation: true,
                        templateUrl: '/twillio-step-2-modal.html',
                        controller: TwillioStepTwoModalController,
                        resolve: {
                            phnNumber: function () {
                                return number;
                            },
                            forWardto: function () {
                                return ford;
                            }
                        }
                    });
                    return modalInstance;

                }, (response) => {
                    $uibModalInstance.close();
                    $scope.loading_chat = false
                })
        }


    }
}
class TwillioStepTwoModalController {
    constructor($stateParams, phnNumber, $scope, forWardto, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.ownerName = ''
        $scope.ownerNumber = phnNumber
        $scope.recordCalls = '1'
        $scope.forwardTo = ''
        if (forWardto) {
            $scope.forwardTo = forWardto
        }
        var uibModalInstance = $uibModalInstance;
        if ($window.localStorage.user_company_details) {
            var companyDetail = JSON.parse($window.localStorage.getItem('user_company_details'))
            $scope.ownerName = companyDetail.name
        }
        $scope.closemodal = function () {
            $state.reload()
            $uibModalInstance.close();
        }
        $scope.upDateFunction = function () {
            $scope.loading_chat = true
            let updatePhone = API.service('twillio-forwarding', API.all('company'))
            updatePhone.post({
                forwarding_to: $scope.forwardTo,
                recording_status: $scope.recordCalls
            })
                .then(function (response) {
                    $state.reload()
                    $scope.loading_chat = false
                    $uibModalInstance.close();
                }, (response) => {
                    $uibModalInstance.close();
                    $scope.loading_chat = false
                    $state.reload()
                })
        }

    }
}
export const IntegrationSettingComponent = {
    templateUrl: './views/app/pages/integration-setting/integration-setting.html',
    controller: IntegrationSettingController,
    controllerAs: 'vm',
    bindings: {}
}