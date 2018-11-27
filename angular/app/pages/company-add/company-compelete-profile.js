class CompanyCompleteProfileController {
    constructor($scope, $stateParams, $state, SAAPI, $http, $window, $uibModal, $timeout) {
        'ngInject'

        this.$state = $state
        var apiKey = ''
        var vm = this
        this.alerts = []
        vm.companyeditdata = {}
        vm.companyeditdata.data = {}
        vm.companyeditdata.data.social_urls = []
        vm.companyeditdata.data.bio = ""
        vm.companyeditdata.data.certifications = ""

        vm.companyeditdata.data.social_urls['facebook_link'] = ""
        vm.companyeditdata.data.social_urls.google_link = ""
        vm.companyeditdata.data.social_urls.instagram_link = ""
        vm.companyeditdata.data.social_urls.twitter_link = ""
        vm.companyeditdata.data.social_urls.youtube_link = ""
        $scope.loading_chat = false
        $scope.showupdates = false
        var companyId = ''
        var companyObj = {}
        // companyData.data['bio']=""
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        if ($stateParams.companyData) {
            vm.companyeditdata = $stateParams.companyData
            $window.localStorage.newcompanyData = JSON.stringify(vm.companyeditdata);

        }

        if ($window.localStorage.getItem('newcompanyData')) {
            vm.companyeditdata = JSON.parse($window.localStorage.getItem('newcompanyData'))
        }
        if ($stateParams.companyID) {
            $scope.showupdates = true
            companyId = $stateParams.companyID
            $window.localStorage.removeItem('newcompanyData')
            let UserData = SAAPI.service('company-settings', SAAPI.all('company'))
            UserData.one(companyId).get()
                .then((response) => {
                    this.companyeditdata = SAAPI.copy(response)
                    companyObj = {
                        api_key: this.companyeditdata.data.api_key,
                        company_id: this.companyeditdata.data.id
                    }
                })
        }
        $scope.loadTags = function (query) {
            var token = $window.localStorage.super_admin_token
            return $http.get('/api/honestdoctor/find-specialization?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }
        $scope.gothirdStep = function () {
            if (companyId) {
                $state.go('app.companybuildscreen', { companyOBJ: companyObj, id: vm.companyeditdata.data.id, alerts: alert })
            }

        }
        this.save = function (isValid) {
            this.file_error = false;
            $scope.loading_chat = true
            if (isValid) {
                let error_file = true;
                let fd = new FormData();
                let $state = this.$state

                let UserData = SAAPI.service('save-company-settings', SAAPI.all('company'))
                UserData.post(vm.companyeditdata)
                    .then((response) => {
                        var respo = response.plain()
                        $window.localStorage.admin_companies = JSON.stringify(respo.data.admin_compnies);
                        companyObj = {
                            api_key: respo.data.api_key,
                            company_id: respo.data.company_id
                        }

                        $timeout(function () {

                            $scope.loading_chat = false
                            $state.go('app.companybuildscreen', { companyOBJ: companyObj, alerts: alert })
                        }, 100);


                    }, (response) => {
                        $scope.loading_chat = false
                        let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                        $state.go($state.current, { alerts: alert })

                    })
            } else {
                this.formSubmitted = true
            }

        }
        $scope.goBackbutton = function () {
            if (companyId) {
                $state.go('app.companyadd', { companyID: companyId })
            } else {
                $state.go('app.companyadd', { companyData: vm.companyeditdata })
            }

        }
        $scope.updateCompany = function (isValid) {
            if (isValid) {
                let error_file = true;
                let fd = new FormData();
                vm.companyeditdata.put()
                    .then(() => {
                        error_file = true;
                        let alert = { type: 'success', 'title': 'Success!', msg: 'Company settings has been updated.' }
                        $state.go($state.current, { alerts: alert })
                        $scope.gothirdStep()
                    }, (response) => {
                        let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                        $state.go($state.current, { alerts: alert })
                    })
            }
        }
    }

    $onInit() { }
}

export const CompanyCompleteProfileComponent = {
    templateUrl: './views/app/pages/company-add/company-compelete-profile.html',
    controller: CompanyCompleteProfileController,
    controllerAs: 'vm',
    bindings: {}
}