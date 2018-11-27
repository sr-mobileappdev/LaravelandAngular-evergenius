class EditStaffController {

    constructor($scope, API, $state, $stateParams, $window, $timeout, Upload, $http) {
        'ngInject'
        var vm = this
        this.$state = $state
        this.formSubmitted = false
        this.API = API
        this.alerts = []
        this.$window = $window
        $scope.userroles = [];
        this.role_id = 0;
        if ($stateParams.alerts) {
            this.alerts = []
            $scope.show_alert = true;
            this.alerts.push($stateParams.alerts)
            $timeout(function () {
                $scope.show_alert = false;
            }, 3000)
        }


        let getroles = API.service('roles', API.all('users'))
        getroles.getList()
            .then((response) => {
                $scope.userroles = response.plain()

            })

        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;

        }, function errorCallback(response) {
            // called asynchronously if an error occurs
            // or server returns response with an error status.
        });

        let userId = $stateParams.userId;
        this.user_id = userId;
        let user = API.service('show-company-user', API.all('users'))
        user.one(userId).get()
            .then((response) => {
                this.userInfo = API.copy(response)
                $scope.image_path = response.plain().data.avatar
                this.role_id = parseInt(response.data.role[0].id);
                this.userInfo.data.mobile_number = this.removeCode(this.userInfo.data.mobile_number, this.userInfo.data.phone_country_code)
                this.userInfo.data.send_lead = response.data.send_lead;
                if (this.usereditdata.data.phone_country_code == '') {
                    this.usereditdata.data.phone_country_code = "+1";
                }
                if ($scope.image_path == '') {
                    $scope.image_path = null
                }
            })
        this.removeCode = function (phnumber, Country_code = '') {
            if (Country_code != '' && Country_code != undefined && Country_code != null) {
                phnumber = phnumber.replace(Country_code, '')
            }
            return phnumber;
        }
        ///////////////////         // image upload//         //////////////// 


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
        this.save = function (isValid) {
            var role_id = this.role_id
            var image_url = $scope.image_path
            if (isValid) {
                let $state = this.$state
                var country_code = vm.userInfo.data.phone_country_code;
                let user_data = this.API.service('show-company-user/' + this.user_id, this.API.all('users'))

                user_data.post({
                    'name': this.userInfo.data.name,
                    'email': this.userInfo.data.email,
                    'phone': this.userInfo.data.phone,
                    'phone_country_code': country_code,
                    'password': this.password,
                    'role_id': role_id,
                    'send_lead': this.userInfo.data.send_lead,
                    'avatar': image_url,

                }).then(function (response) {
                    let alert = { type: 'success', 'title': 'Success!', msg: 'Staff updated successfully.' }
                    $state.go($state.current, { alerts: alert }, { reload: true })

                }, function (response) {
                    let alert = { type: 'danger', 'title': 'Error!', msg: response.data.message }
                    $state.go($state.current, { alerts: alert })


                })
            }

        }

    }



    $onInit() { }
}

export const EditStaffComponent = {
    templateUrl: './views/app/pages/manage-staff-edit/manage-staff-edit.html',
    controller: EditStaffController,
    controllerAs: 'vm',
    bindings: {}
}
