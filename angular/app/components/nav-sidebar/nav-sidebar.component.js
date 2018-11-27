class NavSidebarController {
  constructor($state, $scope, $stateParams, AclService, ContextService, $window, $location, $timeout) {
    'ngInject';

    this.hidemenu = false;
    let navSideBar = this
    this.location = $location.path()
    this.isadmin = true;

    if ($stateParams.menuhide !== undefined && $stateParams.menuhide !== null && $stateParams.menuhide == 1) {
      this.hidemenu = $stateParams.menuhide;
    } else if (this.isadmin == 1) {
      this.hidemenu = false;
    }
    else {
      this.hidemenu = false;
    }

    $scope.company_id = 0;

    this.can = AclService.can
    this.roles = AclService.getRoles()
    this.hasRoles = AclService.hasAnyRole
    this.user_role = this.roles[0]
    $scope.site_url = '';

    if ($window.localStorage.adminrole) {
      this.adminrole = $window.localStorage.adminrole
      this.isadmin = 1;
    }

    if ($window.localStorage.user_company_details) {
      this.isadmin = 0;
    }

    if ($window.localStorage.sidebar_docotors) {
      this.celendar_doctors = JSON.parse($window.localStorage.sidebar_docotors)
    }
    ContextService.me(function (data) {
      navSideBar.userData = data
    })

    /* Fix Mobile Menu issue */
    if ($window.localStorage.load_first_time == "yes" && $window.innerWidth < 768) {
      $window.localStorage.load_first_time = false;
      $timeout(function () {
        $window.location.reload();
      }, 3000);
      //$location.path("/");
    }

    if ($window.localStorage.user_company_details) {
      $scope.user_company_details = JSON.parse($window.localStorage.user_company_details);
      var company_d = JSON.parse($window.localStorage.user_company_details);
    }

    if (company_d !== undefined && company_d.id !== "" && company_d.id !== undefined) {

      $scope.company_id = company_d.id;
    }
  }

  $onInit() { }
}

export const NavSidebarComponent = {
  templateUrl: './views/app/components/nav-sidebar/nav-sidebar.component.html',
  controller: NavSidebarController,
  controllerAs: 'vm',
  bindings: {}
}
