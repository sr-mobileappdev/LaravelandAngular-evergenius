class UserRolesAddController {
  constructor(API, $state, $stateParams, $timeout, $scope, $filter) {
    'ngInject'

    this.$state = $state
    this.formSubmitted = false
    this.API = API
    this.alerts = []
    this.permissions = []
    if ($stateParams.alerts) {
      this.alerts = []
      $scope.show_alert = true;
      this.alerts.push($stateParams.alerts)
      $timeout(function () {
        $scope.show_alert = false;
      }, 3000)
    }

    let Permissions = API.service('permissions', API.all('users'))
    Permissions.getList()
      .then((response) => {
        let permissionList = []
        let permissionResponse = response.plain()

        angular.forEach(permissionResponse, function (value) {
          permissionList.push({
            id: value.id,
            name: value.name,
            parent_id: value.parent_id,
            parent: value.parent,
            is_view: value.is_view,
            view_id: value.view_id
          })
        })

        this.systemPermissions = permissionList
      })

    this.getParentName = function (parent_id) {
      var index = this.systemPermissions.findIndex(x => x.id == parent_id);
      if (index == -1) {
        return 'Others';
      }
      return this.systemPermissions[index].name;

    }

    $scope.is_view_enable = function (view_id, permissions) {
      if (permissions != undefined) {
        if (permissions.indexOf(parseInt(view_id)) !== -1) {
          return true;
        }
      }
      return false;
    }

    this.check_list_permision = function (obj) {
      var permissions = this.permissions;
      var per_id = obj.id;
      if (permissions.indexOf(parseInt(per_id)) == -1) {
        if ((obj.id != null && obj.is_view != null && parseInt(obj.is_view) == 1)) {
          var per_disable = [];
          var filter_items = $filter('filter')(this.systemPermissions, { view_id: per_id });
          angular.forEach(filter_items, function (val, key) {
            per_disable.push(val.id);
          });
          for (var i = per_disable.length - 1; i >= 0; i--) {
            var pe_disable = per_disable[i];
            if (permissions.indexOf(parseInt(pe_disable)) !== -1) {
              permissions.splice(permissions.indexOf(parseInt(pe_disable)), 1);
            }
          }
          this.permissions = permissions;

        }
        this.permissions = permissions;
      }

    }

  }

  save(isValid) {
    this.$state.go(this.$state.current, {}, { alerts: 'test' })
    if (isValid) {
      let Roles = this.API.service('roles', this.API.all('users'))
      let $state = this.$state

      Roles.post({
        'role': this.role,
        'slug': this.slug,
        'description': this.description,
        'permissions': this.permissions
      }).then(function () {
        let alert = { type: 'success', 'title': 'Success!', msg: 'Group has been added.' }
        $state.go($state.current, { alerts: alert }, { reload: true })
      }, function (response) {
        let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
        $state.go($state.current, { alerts: alert }, { reload: true })
      })
    } else {
      this.formSubmitted = true
    }
  }

  $onInit() { }
}

export const UserRolesAddComponent = {
  templateUrl: './views/app/pages/user-roles-add/user-roles-add.component.html',
  controller: UserRolesAddController,
  controllerAs: 'vm',
  bindings: {}
}
