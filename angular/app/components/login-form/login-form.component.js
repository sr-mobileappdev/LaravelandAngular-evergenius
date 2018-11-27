class LoginFormController {
  constructor($rootScope, $auth, $state, $stateParams, API, AclService, $window) {
    'ngInject'
    $window.localStorage.clear();
    delete $rootScope.me

    this.$auth = $auth
    this.$state = $state
    this.$stateParams = $stateParams
    this.AclService = AclService
    this.$window = $window
    this.registerSuccess = $stateParams.registerSuccess
    this.successMsg = $stateParams.successMsg
    this.loginfailederror = ''
    this.loginfailed = false
    this.unverified = false
  }

  $onInit() {
    this.email = ''
    this.password = ''
  }

  login() {
    this.loginfailederror = ''
    this.loginfailed = false
    this.unverified = false

    let user = {
      email: this.email,
      password: this.password
    }

    this.$auth.login(user)
      .then((response) => {

        let data = response.data.data
        let AclService = this.AclService
        let $window = this.$window
        /* Resticted for single role*/
        //angular.forEach(data.userRole, function (value) {
        AclService.attachRole(data.userRole)
        //})
        $window.localStorage.user_company_details = JSON.stringify(response.data.data.company_details);
        $window.localStorage.company_config_status = JSON.stringify(response.data.data.company_config_status);
        var superAdminRoles = ["super.call.center", "super.admin.agent", "admin.super"];
        AclService.setAbilities(data.abilities)
        if (data.userRole && superAdminRoles.indexOf(data.userRole) != -1) {
          delete $window.localStorage.user_company_details;
          $window.localStorage.super_admin_token = data.token.replace(/['"]+/g, '');
          $window.localStorage.adminrole = data.userRole;
          $window.localStorage.super_admin_user_data = JSON.stringify(response.data.data.user);
          $window.localStorage.admin_companies = JSON.stringify(response.data.data.admin_compnies);
          $window.localStorage.load_first_time = "yes";
          /* */

          if ($window.localStorage.adminrole == 'admin.super') {
            this.$state.go('app.superdashboard')

          } else {
            this.$state.go('app.manageclients')
          }
        }
        else {
          this.$auth.setToken(response.data)
          $window.localStorage.sidebar_docotors = JSON.stringify(response.data.data.calendar_doctors);
          $window.localStorage.user_data = JSON.stringify(response.data.data.user);
          $window.localStorage.application_url = response.data.data.application_url;

          $window.localStorage.load_first_time = "yes";
          var admin = JSON.parse(localStorage.getItem('user_data'));
          var company_name = JSON.parse(localStorage.getItem('user_company_details')).name;

          window.intercomSettings = {
            app_id: "p1f55kra",
            name: admin.name + " - " + company_name,
            email: admin.email,
            "type": 'doctor Office',
            "company_name": company_name,
          };

          var w = window;
          var ic = w.Intercom;
          if (typeof ic === "function") {
            ic('reattach_activator');
            ic('update', intercomSettings);
          } else {
            var d = document;
            var i = function () {
              i.c(arguments)
            };
            i.q = [];
            i.c = function (args) {
              i.q.push(args)
            };
            w.Intercom = i;
          }
          /**INTERCOM SECTION**/
          if (data.userRole && data.userRole[0] == 'admin.user') {
            this.$state.go('app.landing', {}, { reload: true })
          }
          else {
            this.$state.go('app.agentdashboard', {}, { reload: true })
          }
          /**INTERCOM SECTION**/

          //this.$state.go('app.landing')


        }



      })
      .catch(this.failedLogin.bind(this))
  }

  failedLogin(res) {
    if (res.status == 401) {
      this.loginfailed = true
    } else {
      if (res.data.errors.message[0] == 'Email Unverified') {
        this.unverified = true
      } else {
        for (var error in res.data.errors) {
          this.loginfailederror += res.data.errors[error] + ' '
        }
      }
    }
  }
}

export const LoginFormComponent = {
  templateUrl: './views/app/components/login-form/login-form.component.html',
  controller: LoginFormController,
  controllerAs: 'vm',
  bindings: {}
}
