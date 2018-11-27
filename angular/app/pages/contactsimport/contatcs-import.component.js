class ContatcsController {
  constructor(API, $state, $stateParams, $window, Restangular, uploads, AclService) {
    'ngInject'

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

    this.can = AclService.can
    if (!this.can('import.contacts')) {
      $state.go('app.unauthorizedAccess');
    }

    if ($stateParams.alerts) {
      this.alerts.push($stateParams.alerts)
    }
    $window.localStorage.contentType = 'undefined';
  }
  save(isValid) {
    let Restangular = this.Restangular
    let $window = this.$window
    this.$state.go(this.$state.current, {}, { alerts: 'test' })
    if (isValid) {
      let headers = {
        'Content-Type': undefined
      }
      $window.localStorage.contentType = 'undefined';
      let Permissions = this.API.service('import-contacts', this.API.all('contacts'))
      let $state = this.$state
      let fd = new FormData();
      fd.append('contact_file', this.contact_file)

      Permissions.post(fd, undefined, undefined,
        { 'Content-Type': undefined })
        .then((response) => {
          var $state = this.$state
          if (response.data.upload_status != 'failed') {
            this.show_import_table = true
            this.already_exists = response.data.already_exists
            this.success_upload = response.data.success_upload
            this.failed_contacts = response.data.failed
            this.import_table_data = response.data.contatcs
            this.success_upload_file = true
          } else {
            this.show_import_table = false
            let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
            $state.go($state.current, { alerts: alert })
          }
        })
        , function (response) {
          let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
          $state.go($state.current, { alerts: alert })
        }
    } else {
      this.formSubmitted = true
    }
  }

  $onInit() { }
}
export const ContatcsImportComponent = {
  templateUrl: './views/app/pages/contactsimport/contactsimport.component.html',
  controller: ContatcsController,
  controllerAs: 'vm',
  bindings: {}
}
