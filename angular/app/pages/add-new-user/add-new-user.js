class AddNewUserController {
    constructor($scope, $stateParams, $state, SAAPI, $http, $window, Upload, $timeout, AclService) {
        'ngInject'
        var vm = this
        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.userRolesSelected = []
        $scope.country_code = '+1';
        vm.newuserdata = {};
        vm.newuserdata.data = {}
        vm.newuserdata.data.call_center_companies = []
        vm.newuserdata.data.permissions = []
        vm.newuserdata.data['role'] = 'admin.super'
        $scope.showSelectedrole = false
        // vm.newuserdata.userroles.selected=[]
        $scope.showPermissions = false
        $scope.selectOffices = false
        $scope.licence = false
        $scope.upload_tag = false
        this.AclService = AclService
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole

        if (this.roles[0] == 'super.call.center') {
            $scope.showSelectedrole = true
            vm.newuserdata.data['role'] = 'super.call.center'
        }
        $scope.selectedclients = [];
        //this.API = uploads
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        if ($stateParams.remainData) {
            vm.newuserdata.data = $stateParams.remainData
        }
        $scope.delete_photo = function () {
            $scope.image_path = '';
            angular.element("input[type='file']").val(null)
            $scope.upload_tag = false
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
                            $scope.upload_tag = true;
                        });
                    }
                }
            }
        };


        $scope.domain_regex = /^(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+$/;
        $scope.email_regex = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;

        /* Google Places */
        $scope.city_input_options = {
            types: ['(cities)']
        }

        $scope.country_input_options = {
            types: ['(regions)']
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
        /* / Google Places */
        $scope.permission_list = [
            { name: 'View Users', value: 'view.superadmin.users', id: 1 },
            { name: 'Create New User', value: 'add.superadmin.user', id: 2 },
            { name: 'Update Users', value: 'update.superadmin.user', id: 3 },
            { name: 'Delete User', value: 'delete.superadmin.user', id: 4 },
            { name: 'Create New Company', value: 'add.company', id: 5 }
        ]
        $scope.GetValue = function (id, na, indx) {
            var mes = id
            if (mes) {
                vm.newuserdata.data.permissions.push(na)
            } else {
                vm.newuserdata.data.permissions.splice(vm.newuserdata.data.permissions.indexOf(na), 1);
            }
        }

        $scope.$watchCollection('vm.newuserdata.data.role', function (new_val, old_val) {

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
        $scope.$watchCollection('vm.newuserdata.data.area', function (new_val, old_val) {
            if (new_val) {

                var city_name = new_val.split(',')[0];
                var myEl = angular.element(document.querySelector('#city_name'));
                myEl.val(city_name);

            }

        });
        $scope.$watchCollection("selectedclients", function (newValue, oldValue) {
            var sel_companies = [];
            angular.forEach(newValue, (val, key) => {
                sel_companies.push(val.id);
            });

            vm.newuserdata.data.call_center_companies = sel_companies;
        });

        this.change_city = function () {
            if (this.newuserdata.data.area) {
                let new_val = this.newuserdata.data.area;
                var city_name = new_val.split(',')[0];
                this.newuserdata.data.area = city_name;
            }

        }

        this.save = function (isValid) {
            if (isValid) {
                let error_file = true;
                let fd = new FormData();
                let $state = this.$state
                /*if(this.country.selected!=undefined){     
                     this.newuserdata.data.country =  this.country.selected.name; 
                }*/

                this.newuserdata.data['phone_country_code'] = $scope.country_code;
                this.newuserdata.data['avatar'] = $scope.image_path

                let UserData = SAAPI.service('user', SAAPI.all('superadmin'))
                UserData.post(this.newuserdata.data)
                    .then((response) => {

                        let alert = { type: 'success', 'title': 'Success!', msg: 'User has been created successfully' }

                        $state.go('app.manageusers', { alerts: alert })

                    }, (response) => {

                        let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.email[0] }
                        $state.go($state.current, { remainData: this.newuserdata.data, alerts: alert })

                    })
            }

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


        $scope.availableclients = JSON.parse($window.localStorage.admin_companies)

        $scope.onChange = function (e, fileList) {
            alert('this is on-change handler!');
        };

        $scope.onLoad = function (e, reader, file, fileList, fileOjects, fileObj) {
            alert('this is handler for file reader onload event!');
        };


    }

    $onInit() { }
}

export const AddNewUserComponent = {
    templateUrl: './views/app/pages/add-new-user/add-new-user.html',
    controller: AddNewUserController,
    controllerAs: 'vm',
    bindings: {}
}
