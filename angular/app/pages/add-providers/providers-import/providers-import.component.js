class providersImportController {
  constructor(API, $scope, $state, $stateParams, $window, Restangular, uploads, AclService) {
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
    this.upload_section = true;
    this.loading_upload = false;
    this.uploaded = false;
    this.success_count = 0;
    this.failed_count = 0;

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
      this.upload_section = false;
      this.loading_upload = true;
      this.uploaded = false;

      let headers = {
        'Content-Type': undefined
      }
      $window.localStorage.contentType = 'undefined';
      let HonestDoctor = this.API.service('import-prodivders', this.API.all('honestdoctor'))
      let $state = this.$state
      let fd = new FormData();
      fd.append('providers', this.providers_file)

      HonestDoctor.post(fd, undefined, undefined,
        { 'Content-Type': undefined })
        .then((response) => {
          this.upload_section = false;
          this.loading_upload = false;
          this.uploaded = true;
          var data_success = response.plain();
          this.success_count = data_success.data.inserted;
          this.failed_count = data_success.data.failed;
        })
        , function (response) {
          this.upload_section = true;
          this.loading_upload = false;
          this.uploaded = false;
          let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
          $state.go($state.current, { alerts: alert })
        }
    } else {
      this.formSubmitted = true
    }
  }

  $onInit() { }
}
export const providersImportComponent = {
  templateUrl: './views/app/pages/add-providers/providers-import/providersimport.component.html',
  controller: providersImportController,
  controllerAs: 'vm',
  bindings: {}
}
