class UserEditController {
    constructor($scope, $stateParams, $state, API, $window, AclService, Upload, $http) {
        'ngInject'

        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.userRolesSelected = []
        this.gender = 'male';
        var vm = this
        $scope.upload_tag = false
        this.avatar = '';
        this.social_profiles = {}

        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }

        let userId = $stateParams.userId

        this.can = AclService.can
        if (!this.can('add.provider')) {
            $state.go('app.unauthorizedAccess');
        }

        /* Google Places */
        $scope.city_input_options = {
            types: ['(cities)']
        }

        $scope.country_input_options = {
            types: ['(regions)']
        }
        /* / Google Places */
        $scope.$watchCollection('vm.usereditdata.data.area', function (new_val, old_val) {
            $scope.relocategoogle();
            if (new_val){
                var city_name = new_val.split(',')[0];
                var myEl = angular.element(document.querySelector('#city_name'));
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
            let new_val = this.usereditdata.data.area;
            var city_name = new_val.split(',')[0];
            this.usereditdata.data.area = city_name;
        }



        let Roles = API.service('roles', API.all('users'))
        Roles.getList()
            .then((response) => {
                let systemRoles = []
                let roleResponse = response.plain()

                angular.forEach(roleResponse, function (value) {
                    systemRoles.push({
                        id: value.id,
                        name: value.name
                    })
                })

                this.systemRoles = systemRoles
            })

        let UserData = API.service('show', API.all('users'))
        UserData.one(userId).get()
            .then((response) => {

                let userRole = []
                let userResponse = response.plain()

                angular.forEach(userResponse.data.role, function (value) {
                    userRole.push(value.id)
                })

                response.data.role = userRole
                this.usereditdata = API.copy(response)
                this.usereditdata.data.area = this.usereditdata.data.city;
                this.usereditdata.data.area = this.usereditdata.data.city;
                if (this.usereditdata.data.social_urls.length != 0) {
                    this.social_profiles = this.usereditdata.data.social_urls;
                }

                //delete this.social_profiles;
                //this.social_profiles.push(this.usereditdata.data.social_urls);
            })



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
                            vm.usereditdata.data.avatar = file_path
                            $scope.upload_tag = true;
                        });
                    }
                }
            }
        };
        $scope.delete_photo = function () {
            vm.usereditdata.data.avatar = '';
            angular.element("input[type='file']").val(null)
            $scope.upload_tag = false
        }
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/honestdoctor/find-specialization?s=' + query, {
                headers: {
                    'Authorization': "Bearer " + token
                }
            });
        }
    }

    save(isValid) {
        if (isValid) {
            let $state = this.$state
            this.usereditdata.data.social_urls = {};
            this.usereditdata.data.social_urls = this.social_profiles

            delete this.usereditdata.data.area;
            this.usereditdata.put()
                .then(() => {
                    let alert = {
                        type: 'success',
                        'title': 'Success!',
                        msg: 'Provider has been updated.'
                    }
                    $state.go($state.current, {
                        alerts: alert
                    })
                    //location.reload(true)
                }, (response) => {
                    let alert = {
                        type: 'danger',
                        'title': 'Error!',
                        msg: response.data.errors.message[0]
                    }
                    $state.go($state.current, {
                        alerts: alert
                    })
                })
        } else {
            this.formSubmitted = true
        }
    }

    $onInit() { }
}

export const UserEditComponent = {
    templateUrl: './views/app/components/user-edit/user-edit.component.html',
    controller: UserEditController,
    controllerAs: 'vm',
    bindings: {}
}
