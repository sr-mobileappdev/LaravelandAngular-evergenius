class CompanySettingsController {
    constructor($scope, $stateParams, $state, API, uploads, $window, $rootScope, $http) {
        'ngInject'
        var url = $window.location.href;
        url = url.split('/');
        url = url.pop() || url.pop();

        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.userRolesSelected = []
        this.API = uploads
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        this.upload_img_block = false;
        let userId = $stateParams.userId
        this.social_profiles = {}
        let UserData = uploads.service('show-settings', API.all('company'))
        UserData.one(userId).get()
            .then((response) => {
                let userRole = []
                let userResponse = response.plain()
                this.companyeditdata = API.copy(response)
                this.companyeditdata.data.area = this.companyeditdata.data.city;
                if (Object.keys(this.companyeditdata.data.social_urls).length) {
                    this.social_profiles = this.companyeditdata.data.social_urls;
                } else {
                    this.social_profiles = {};
                    this.companyeditdata.data.social_urls = {};
                }

            })

        this.hide_logo_img = function () {
            this.upload_img_block = true;
        }

        /* Google Places */
        $scope.city_input_options = {
            types: ['(cities)']
        }

        $scope.country_input_options = {
            types: ['(regions)']
        }
        /* / Google Places */
        $scope.$watchCollection('vm.companyeditdata.data.area', function (new_val, old_val) {
            $scope.relocategoogle();
            if (new_val) {
                var city_name = new_val.split(',')[0];
                var myEl = angular.element(document.querySelector('#city_name'));
                myEl.val(city_name);
            }
        });
        $scope.relocategoogle = function () {
            if (angular.element(document.querySelector('#city_name'))) {
                var myEl = angular.element(document.querySelector('#city_name'));
            }
            if (angular.element(document.querySelector('.pac-container'))){
                var all = angular.element(document.querySelector('.pac-container'));
                for (var i = 0; i < all.length; i++) {
                    all[i].style.top = parseInt(myEl.offset().top) + 35 + 'px';
                }
            }
            
              
           
        }

        // angular.element($window).bind('mousewheel', function () {
        //     $scope.relocategoogle();
        // })


        this.change_city = function () {
            let new_val = this.companyeditdata.data.area;
            var city_name = new_val.split(',')[0];
            this.companyeditdata.data.area = city_name;
        }
        $scope.loadTags = function (query) {
            var token = $window.localStorage.super_admin_token
            return $http.get('/api/honestdoctor/find-specialization?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }


    }
    toObject(arr) {
        var rv = {};
        for (var i = 0; i < arr.length; ++i)
            rv[i] = arr[i];
        return rv;
    }
    save(isValid) {
        this.file_error = false;
        if (isValid) {
            let error_file = true;
            let fd = new FormData();
            let $state = this.$state
            delete this.companyeditdata.data.area;
            delete this.companyeditdata.data.social_urls;
            this.companyeditdata.data['social_urls'] = this.social_profiles;
            this.companyeditdata.put()
                .then(() => {
                    error_file = true;
                    let alert = { type: 'success', 'title': 'Success!', msg: 'Company settings has been updated.' }
                    $state.go($state.current, { alerts: alert })

                }, (response) => {
                    let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                    $state.go($state.current, { alerts: alert })
                })

            if (this.company_logo != undefined) {
                fd.append('company_logo', this.company_logo)
                let ComapnySettings = this.API.service('update-company-logo', this.API.all('company'));
                ComapnySettings.post(fd, undefined, undefined, { 'Content-Type': undefined })
                    .then(() => { }, (response) => {

                        this.file_error = true;
                        let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.company_logo[0] }
                        $state.go($state.current, { alerts: alert })

                    });
            }

        } else {
            this.formSubmitted = true
        }
    }

    $onInit() { }
}

export const CompanySettingsComponent = {
    templateUrl: './views/app/pages/company-settings/company-settings.component.html',
    controller: CompanySettingsController,
    controllerAs: 'vm',
    bindings: {}
}
