class AddStaffController {
  constructor($scope, API, $state, $stateParams, $window, $timeout, Upload, $http) {
    'ngInject'
    var vm = this
    this.$state = $state
    this.formSubmitted = false
    this.API = API
    this.alerts = []
    this.$window = $window
    $scope.userroles = [];
    this.role = '';
    $scope.upload_tag = false
    vm.country_code = '+1';
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


    this.save = function (isValid) {
      this.$state.go(this.$state.current, {}, { alerts: 'test' })
      var image_url = $scope.image_path;
      if (isValid) {
        let Permissions = this.API.service('company-user', this.API.all('users'))
        let $state = this.$state
        var country_code = vm.country_code;
        Permissions.post({
          'name': this.name,
          'email': this.email,
          'phone_country_code': country_code,
          'phone': this.phone,
          'password': this.password,
          'role_id': this.role,
          'send_lead': this.send_lead,
          'avatar': image_url
        }).then(function (response) {
          let alert = { type: 'success', 'title': 'Success!', msg: 'Staff added successfully.' }
          $state.go($state.current, { alerts: alert }, { reload: true });

        }, function (response) {
          let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
          $state.go($state.current, { alerts: alert })

        })
      } else {
        this.formSubmitted = true
      }
    }

    let getroles = API.service('roles', API.all('users'))
    getroles.getList()
      .then((response) => {
        $scope.userroles = response.plain();
        $scope.selected_role = $scope.userroles[0].id;
        this.role = $scope.userroles[0].id;

      })
    if ($stateParams.alerts) {
      this.alerts = []
      $scope.show_alert = true;
      this.alerts.push($stateParams.alerts)
      $timeout(function () {
        $scope.show_alert = false;
      }, 3000)
    }

  }

  $onInit() { }
}

export const AddStaffComponent = {
  templateUrl: './views/app/pages/manage-staff-add/manage-staff-add.html',
  controller: AddStaffController,
  controllerAs: 'vm',
  bindings: {}
}
