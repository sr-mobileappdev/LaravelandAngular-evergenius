class UserInfoEditController {
    constructor($stateParams, $state, SAAPI, Upload, $scope, $http, $window, $filter, $timeout, AclService) {
        'ngInject'
        var vm = this
        this.$state = $state
        $scope.country = {};
        this.formSubmitted = false
        this.alerts = []
        this.userRolesSelected = []
        $scope.showPermissions = false
        $scope.selectOffices = false
        $scope.licence = false
        $scope.upload_tag = false
        $scope.showSelectedrole = false
        vm.usereditdata = {}
        vm.usereditdata.data = {}
        vm.usereditdata.data.call_center_companies = []
        $scope.selectedclients = []
        var total_comp = []
        $scope.showAlert = false
        vm.usereditdata.data.permissions = []
        if ($stateParams.alerts) {
            $scope.showAlert = true
            this.alerts.push($stateParams.alerts)
            $timeout(function () {
                $scope.showAlert = false
            }, 8000);
        }
        $scope.selectedItem = []
        this.AclService = AclService
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole

        if (this.roles[0] == 'super.call.center') {
            $scope.showSelectedrole = true
        }
        this.upload_img_block = false;
        var userId = $stateParams.userID
        this.userId = userId;
        total_comp = JSON.parse($window.localStorage.admin_companies)
        $scope.availableclients = total_comp
        var arra = []
        let UserData = SAAPI.service('user/' + userId, SAAPI.all('superadmin'))
        UserData.one().get()
            .then((response) => {
                let userResponse = response.plain()
                this.usereditdata = SAAPI.copy(response)
                $scope.image_path = userResponse.data.avatar
                this.usereditdata.data.area = userResponse.data.city;
                if (userResponse.data.num_license) {
                    vm.usereditdata.data.num_license = parseInt(userResponse.data.num_license)
                }

                $scope.country.selected = { name: userResponse.data.country, code: userResponse.data.country_code }
                if ($scope.image_path == '') {
                    $scope.image_path = null
                }

                if (userResponse.data.call_center_companies && userResponse.data.call_center_companies.length > 0) {
                    vm.usereditdata.data.call_center_companies = []
                    $scope.availableclients = []
                    for (var i = 0; i < userResponse.data.call_center_companies.length; i++) {
                        var call_comp = userResponse.data.call_center_companies[i]
                        for (var j = 0; j < total_comp.length; j++) {
                            var avail_comp = total_comp[j].id
                            if (avail_comp == call_comp) {

                                $scope.selectedclients.push(total_comp[j])


                            }

                        }
                        var removeIndex = total_comp.findIndex(x => x.id == call_comp);

                        total_comp.splice(removeIndex, 1)
                        $scope.availableclients = total_comp

                    }
                } else {
                    $scope.availableclients = total_comp
                }
                if (userResponse.data.permissions) {
                    vm.usereditdata.data.permissions = userResponse.data.permissions
                    angular.forEach(userResponse.data.permissions, (val, key) => {
                        $scope.selectedItem.push(val)
                        $scope.selectedItem[val] = true

                    });
                }

            })


        $scope.$watchCollection('vm.usereditdata.data.role', function (new_val, old_val) {

            if (new_val == 'admin.super') {
                $scope.showPermissions = false
                $scope.selectOffices = false
                $scope.licence = false
            }
            else if (new_val == 'super.call.center') {
                $scope.showPermissions = true
                $scope.selectOffices = true
                $scope.licence = false
            }
            else if (new_val == 'super.admin.agent') {
                $scope.licence = true
                $scope.showPermissions = false
                $scope.selectOffices = false
            }
        })

        $scope.delete_photo = function () {
            $scope.image_path = '';
            angular.element("input[type='file']").val(null)
            $scope.image_path = null
        }
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
                        });
                    }
                }
            }
        };

        $scope.$watchCollection("selectedclients", function (newValue, oldValue) {
            var sel_companies = [];
            angular.forEach(newValue, (val, key) => {
                sel_companies.push(val.id);
            });

            vm.usereditdata.data.call_center_companies = sel_companies;
        });

        $scope.permission_list = [
            { name: 'View Users', value: 'view.superadmin.users', id: 1 },
            { name: 'Create New User', value: 'add.superadmin.user', id: 2 },
            { name: 'Update Users', value: 'update.superadmin.user', id: 3 },
            { name: 'Delete User', value: 'delete.superadmin.user', id: 4 },
            { name: 'Create New Company', value: 'add.company', id: 5 }
        ]
        var arrayee = []
        $scope.GetValue = function (id, na, indx) {
            var mes = id
            if (mes) {
                vm.usereditdata.data.permissions.push(na)

            } else {
                vm.usereditdata.data.permissions.splice(vm.usereditdata.data.permissions.indexOf(na), 1);
            }

        }

        /* Google Places */
        $scope.city_input_options = {
            types: ['(cities)']
        }
        $scope.email_regex = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;

        $scope.country_input_options = {
            types: ['(regions)']
        }
        /* / Google Places */

        $scope.$watchCollection('vm.usereditdata.data.area', function (new_val, old_val) {
            if (new_val) {
                var city_name = new_val.split(',')[0];
                var myEl = angular.element(document.querySelector('#city_name'));
                myEl.val(city_name);
            }

        });

        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;

        }, function errorCallback(response) {
            // called asynchronously if an error occurs
            // or server returns response with an error status.
        });

        this.change_city = function () {
            if (vm.usereditdata.data.area) {
                let new_val = this.usereditdata.data.area;
                var city_name = new_val.split(',')[0];
                vm.usereditdata.data.area = city_name;
            }

        }
        this.save = function (isValid) {
            var user_id = this.userId
            this.file_error = false;
            if (isValid) {
                let error_file = true;
                this.usereditdata.data.avatar = $scope.image_path
                delete this.usereditdata.data.area;
                let $state = this.$state
                this.usereditdata.put()
                    .then((response) => {
                        error_file = true;
                        let alert = { type: 'success', 'title': 'Success!', msg: 'User settings has been updated.' }
                        $state.go($state.current, { alerts: alert })
                    }, (response) => {
                        let alert = { type: 'error', 'title': 'Error!', msg: response.data.errors.message[0] }
                        $state.go($state.current, { alerts: alert })
                    })

            } else { this.formSubmitted = true }

        }

        $scope.moveItem = function (item, from, to) {
            //Here from is returned as blank and to as undefined

            var idx = from.indexOf(item);
            if (idx != -1) {
                from.splice(idx, 1);
                to.push(item);
            }
        };
        $scope.moveAll = function (from, to) {
            //Here from is returned as blank and to as undefined

            angular.forEach(from, function (item) {
                to.push(item);
            });
            from.length = 0;
        };

    }



    $onInit() { }
}

export const UserInfoEditComponent = {
    templateUrl: './views/app/pages/edit-userinfo/edit-user-component.html',
    controller: UserInfoEditController,
    controllerAs: 'vm',
    bindings: {}
}
