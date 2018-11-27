class AddDoctorController {
    constructor($scope, API, $state, $stateParams, $window, AclService, Upload, $http) {
        'ngInject'

        this.$state = $state
        this.formSubmitted = false
        this.API = API
        this.alerts = []
        this.$window = $window
        this.gender = 'male';
        this.hd_publish_status = 0;
        var vm = this
        $scope.upload_tag = false
        this.image_path = '';
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        $scope.disabled_add = false;
        this.can = AclService.can
        if (!this.can('add.provider')) {
            $state.go('app.unauthorizedAccess');
        }
        this.social_profiles = {}

        /* Google Places */
        $scope.city_input_options = {
            types: ['(cities)']
        }

        $scope.country_input_options = {
            types: ['(regions)']
        }
        /* / Google Places */

        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;

        }, function errorCallback(response) {
            // called asynchronously if an error occurs
            // or server returns response with an error status.
        });


        this.removeCode = function (phnumber, Country_code = '') {
            if (Country_code != '') {
                phnumber = phnumber.replace(Country_code, '')
            }
            return phnumber;
        }


        $scope.$watchCollection('vm.area', function (new_val, old_val) {
            if (new_val){
                var city_name = new_val.split(',')[0];
                var myEl = angular.element(document.querySelector('#city_name'));
                $scope.relocategoogle();
                myEl.val(city_name);
            }
         
        });

        $scope.relocategoogle = function () {
            var myEl = angular.element(document.querySelector('#city_name'));
            var all = angular.element(document.querySelectorAll('.pac-container'));
            if (all) {
                for (var i = 0; i < all.length; i++) {
                    all[i].style.top = parseInt(myEl.offset().top) + 35 + 'px';
                }
            }
        }

        // angular.element($window).bind('mousewheel', function () {
        //     $scope.relocategoogle();
        // })

        this.change_city = function () {
            let new_val = this.area;
            var city_name = new_val.split(',')[0];
            this.area = city_name;
        }

        $scope.upload = function (files, media) {
            $scope.disabled_add = true;
            var token = $window.localStorage.satellizer_token

            if (files && files.length) {
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    if (!file.$error) {
                        Upload.upload({
                            url: '/api/users/upload-profile-image',
                            data: {
                                profile_pic: file,

                            },
                            beforeSend: function (xhr) {
                                /* xhr.setRequestHeader("Authorization",
                                     "Bearer " + token);*/
                            },
                            error: function (err) {
                                let data = []
                                return JSON.stringify(data);
                                $scope.disabled_add = false;
                            }

                        }).then(function (resp) {
                            $scope.disabled_add = false;
                            var file_path = resp.data.data.path;
                            vm.image_path = file_path
                            $scope.upload_tag = true;
                        });
                    }
                }
            }
        };
        $scope.delete_photo = function () {
            vm.image_path = '';
            angular.element("input[type='file']").val(null)
            $scope.upload_tag = false
        }
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/honestdoctor/find-specialization?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

    }
    save(isValid) {
        this.$state.go(this.$state.current, {}, { alerts: 'test' })
        if (isValid) {
            let users = this.API.service('doctors', this.API.all('users'))
            let $state = this.$state
            var $window = this.$window
            users.post({
                'name': this.name,
                'job_title': this.job_title,
                'email': this.email,
                'phone': this.phone,
                'website_url': this.website_url,
                'address': this.address,
                'gender': this.gender,
                'city': this.city,
                'state': this.state,
                'zip': this.zip,
                'country': this.country,
                'password': this.password,
                'bio': this.bio,
                'languages_spoken': this.languages_spoken,
                'education': this.education,
                'specialities': this.specialities,
                'confirm_password': this.confirm_password,
                'hd_publish_status': this.hd_publish_status,
                'hospital_affiliations': this.hospital_affiliations,
                'profile_pic': this.image_path,
                'social_urls': this.social_profiles
            }).then(function (response) {
                let data_res = response.plain()
                delete $window.localStorage.sidebar_docotors
                $window.localStorage.sidebar_docotors = JSON.stringify(data_res.data.company_doctors)
                let alert = { type: 'success', 'title': 'Success!', msg: 'Provider has been added.' }
                $state.go($state.current, { alerts: alert }, { reload: true })
            }, function (response) {
                let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                $state.go($state.current, { alerts: alert })
            })
        } else {
            this.formSubmitted = true
        }
    }

    $onInit() { }
}

export const AddDoctorComponent = {
    templateUrl: './views/app/pages/add-doctor/add-doctor.page.html',
    controller: AddDoctorController,
    controllerAs: 'vm',
    bindings: {}
}
