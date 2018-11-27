class ImportContactsController {
  constructor($scope, API, $state, $stateParams, $window, Restangular, uploads, AclService) {
    'ngInject'
    var vm = this
    this.$state = $state
    this.formSubmitted = false
    this.API = uploads
    this.alerts = []
    this.$window = $window
    this.already_exists = 0
    this.already_exists = 0
    this.success_upload = 0
    this.failed_contacts = 0
    this.import_table_data = ''
    this.show_import_table = false
    this.success_upload_file = false
    this.Restangular = Restangular
    $scope.loading_chat = false
    $scope.erroeMessage = false

    $scope.$watch('vm.contact_file', function () {
      if (vm.contact_file) {
        var filename = vm.contact_file.name
        var ext = filename.substring(filename.lastIndexOf('.') + 1)
        if (ext == 'csv') {
          $scope.erroeMessage = false
          return true;
        }
        else {
          $scope.erroeMessage = true
          return false;

        }
      }
    })

    if ($window.localStorage.getItem('mapped_contacts_details')) {
      $window.localStorage.removeItem('mapped_contacts_details')
    }

    if ($stateParams.alerts) {
      this.alerts.push($stateParams.alerts)
    }

    $window.localStorage.contentType = 'undefined';
    this.save = function (isValid) {
      $scope.loading_chat = true
      let Restangular = this.Restangular
      let $window = this.$window
      this.$state.go(this.$state.current, {}, { alerts: 'test' })
      //if (isValid) {
      let headers = {
        'Content-Type': undefined
      }

      $window.localStorage.contentType = 'undefined';
      let Permissions = this.API.service('upload-contact-list', this.API.all('email-marketing'))
      let $state = this.$state
      let fd = new FormData();
      fd.append('contact_file', this.contact_file)

      Permissions.post(fd, undefined, undefined, { 'Content-Type': undefined, 'Cache-Control': 'no-cache' })
        .then((response) => {
          var responseBody = response.plain();
          var stringifyObject = JSON.stringify(responseBody.data);
          $window.localStorage.setItem('mapping', stringifyObject);
          $window.localStorage.setItem('csvfilename', responseBody.data.filename);
          //let alert = { type: 'success', 'title': 'Success!', msg: 'File upload successfully' }
          $scope.loading_chat = false
          $state.go('app.emcontact-mapping', { alerts: alert, showmessage: true })
        }), function (response) { }
    }
  }

  $onInit() { }
}

export const ImportContactsComponent = {
  templateUrl: './views/app/pages/email-marketing/contacts/import/upload/import.contacts.html',
  controller: ImportContactsController,
  controllerAs: 'vm',
  bindings: {}
}
