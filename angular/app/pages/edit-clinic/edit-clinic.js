class ClinicEditController {
    constructor($scope, $stateParams, $state, SAAPI, $http, $window, Upload, API) {
        'ngInject'
        var vm = this
        $scope.upload_tag = false
        var clinic_id = $stateParams.clinicId
        $scope.image_path = null
        $scope.update_data = false;
        vm.companyeditdata = {};
        vm.companyeditdata.data = {};
        vm.companyeditdata['data']['area'] = ''
        vm.companyeditdata['data']['city'] = '';
        vm.companyeditdata.data['state'] = ''
        vm.companyeditdata.data['country'] = ''
        $scope.upload = function (files, media) {
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
                                xhr.setRequestHeader("Authorization",
                                    "Bearer " + token);
                            }, error: function (err) {
                                let data = []
                                return JSON.stringify(data);
                            }

                        }).then(function (resp) {
                            var file_path = resp.data.data.path;

                            $scope.image_path = file_path
                            $scope.upload_tag = true;
                        });
                    }
                }
            }
        };

        $scope.$watchCollection('vm.companyeditdata.data.area', function (new_val, old_val) {
            var city_name = new_val.split(',')[0];
            $scope.relocategoogle();
            var myEl = angular.element(document.querySelector('#city_name'));
            myEl.val(city_name);
        });
        $scope.relocategoogle = function () {
            var myEl = angular.element(document.querySelector('#city_name'));
            var all = angular.element(document.querySelector('.pac-container'));
            if (all) {
                for (var i = 0; i < all.length; i++) {
                    all[i].style.top = parseInt(myEl.offset().top) + 35 + 'px';
                }
            }
        }

        angular.element($window).bind('mousewheel', function () {
            $scope.relocategoogle();
        })

        $scope.delete_photo = function () {
            $scope.image_path = null;
            angular.element("input[type='file']").val(null)
            $scope.upload_tag = false
        }
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

        $scope.loadTags = function (query) {
            var token = $window.localStorage.super_admin_token
            return $http.get('/api/honestdoctor/find-specialization?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

        let Show_clinic_data = SAAPI.service('show-hd-clinic/' + clinic_id, SAAPI.all('honestdoctor'))
        Show_clinic_data.one("").get()
            .then((response) => {
                // $scope.clinicDetail = response.plain().data.clinics_information;
                var image_url = response.plain().data.clinics_information.image_url
                if (image_url != null && image_url != '' && image_url != undefined) {
                    $scope.image_path = image_url
                } else {
                    $scope.image_path = null
                }

                $scope.clinicDetail = SAAPI.copy(response);
                vm.companyeditdata['data']['city'] = $scope.clinicDetail.data.clinics_information.city
                vm.companyeditdata['data']['area'] = $scope.clinicDetail.data.clinics_information.city
                vm.companyeditdata.data['state'] = $scope.clinicDetail.data.clinics_information.province
                vm.companyeditdata.data['country'] = $scope.clinicDetail.data.clinics_information.country
            })



        this.save = function (isValid, image_path) {
            let update_clinic = SAAPI.service('hd-clinic/' + clinic_id, SAAPI.all('honestdoctor'))
            var $window = this.$window
            if (image_path) {
                $scope.clinicDetail.data.clinics_information.image_url = image_path
            }

            $scope.clinicDetail.data.clinics_information.city = vm.companyeditdata['data']['city']
            $scope.clinicDetail.data.clinics_information.province = vm.companyeditdata.data['state']
            $scope.clinicDetail.data.clinics_information.country = vm.companyeditdata.data['country']
            $scope.clinicDetail.data.clinics_information.city = vm.companyeditdata['data']['area']


            $scope.clinicDetail.put().then(function (response) {
                $scope.update_data = true;
                let alert = { type: 'success', 'title': 'Success!', msg: 'Clinic has been updated.' }
                //$state.go($state.current, { alerts: alert,showmessage:true})

            }, function (response) {
                let alert = { type: 'danger', 'title': 'Error!', msg: 'Something went wrong' }
                $state.go($state.current, { alerts: alert })
            })

        }

    }

    $onInit() { }
}

export const ClinicEditComponent = {
    templateUrl: './views/app/pages/edit-clinic/edit-clinic.html',
    controller: ClinicEditController,
    controllerAs: 'vm',
    bindings: {}
}
