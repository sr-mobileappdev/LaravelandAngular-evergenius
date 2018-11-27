class ProviderEditController {
    constructor($scope, $stateParams, $state, SAAPI, $http, $window, Upload, API) {
        'ngInject'
        var vm = this
        $scope.upload_tag = false
        $scope.update_data = false;
        var provider_id = $stateParams.providerId
        $scope.image_path = null
        vm.companyeditdata = {};
        vm.companyeditdata.data = {};
        vm.companyeditdata.data.city = '';
        vm.companyeditdata.data.state = '';
        vm.companyeditdata.data.country = '';
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

        $scope.loadTags = function (query) {
            var token = $window.localStorage.super_admin_token
            return $http.get('/api/honestdoctor/find-specialization?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

        let Show_Provider_data = SAAPI.service('show-hd-provider/' + provider_id, SAAPI.all('honestdoctor'))
        Show_Provider_data.one("").get()
            .then((response) => {
                var image_url = response.plain().data.provider_information.image_url
                if (image_url != null && image_url != '' && image_url != undefined) {
                    $scope.image_path = image_url
                } else {
                    $scope.image_path = null
                }

                $scope.providerDetail = SAAPI.copy(response);
                this.companyeditdata.data.area = $scope.providerDetail.data.provider_information.city
                this.companyeditdata.data.city = $scope.providerDetail.data.provider_information.city
                this.companyeditdata.data.state = $scope.providerDetail.data.provider_information.province
                this.companyeditdata.data.country = $scope.providerDetail.data.provider_information.country


            })




        this.save = function (isValid, image_path) {


            let update_clinic = SAAPI.service('hd-provider/' + provider_id, SAAPI.all('honestdoctor'))
            var $window = this.$window
            if (image_path) {
                $scope.providerDetail.data.provider_information.image_url = image_path
            }
            if (vm.companyeditdata.data.city) {
                $scope.providerDetail.data.provider_information.city = vm.companyeditdata.data.city
            } else {
                $scope.providerDetail.data.provider_information.city = ''
            }

            if (vm.companyeditdata.data.state) {
                $scope.providerDetail.data.provider_information.province = vm.companyeditdata.data.state
            } else {
                $scope.providerDetail.data.provider_information.province = ''
            }


            if (vm.companyeditdata.data.country) {
                $scope.providerDetail.data.provider_information.country = vm.companyeditdata.data.country
            } else {
                $scope.providerDetail.data.provider_information.country = ''
            }


            $scope.providerDetail.put().then(function (response) {
                $scope.update_data = true;
            }, function (response) {
                let alert = { type: 'danger', 'title': 'Error!', msg: 'Something went wrong !' }
                $state.go($state.current, { alerts: alert })
            })

        }

    }

    $onInit() { }
}

export const ProviderEditComponent = {
    templateUrl: './views/app/pages/edit-hd-provider/edit-hd-provider.html',
    controller: ProviderEditController,
    controllerAs: 'vm',
    bindings: {}
}
