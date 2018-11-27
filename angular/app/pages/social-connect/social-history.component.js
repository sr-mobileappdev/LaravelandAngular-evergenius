class SocialHistoryController {
  constructor (API, AclService, $state) {
    'ngInject'

    this.API = API
    this.$state = $state
    this.alerts = []
  }

  $onInit () {
    this.password = ''
    this.password_confirmation = ''
    this.isValidToken = false
    this.formSubmitted = false

    this.verifyToken()
  }
}

export const SocialHistoryComponent = {
  templateUrl: './views/app/pages/social-connect/social-history.component.html',
  controller: SocialHistoryController,
  controllerAs: 'vm',
  bindings: {}
}
