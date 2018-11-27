class UserRolesEditController {
  constructor($stateParams, $scope, $state, API, $filter, $timeout) {
    'ngInject'

    this.$state = $state
    this.formSubmitted = false
    this.alerts = []

    if ($stateParams.alerts) {
      this.alerts = []
      $scope.show_alert = true;
      this.alerts.push($stateParams.alerts)
      $timeout(function () {
        $scope.show_alert = false;
      }, 3000)
    }

    $scope.is_view_enable = function (view_id, permissions) {
      if (permissions != undefined) {
        if (permissions.indexOf(parseInt(view_id)) !== -1) {
          return true;
        }
      }
      return false;
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

    let roleId = $stateParams.roleId
    let Role = API.service('roles-show', API.all('users'))
    Role.one(roleId).get()
      .then((response) => {
        let rolePermissions = []

        angular.forEach(response.data.permissions, function (value) {
          rolePermissions.push(value.id)
        })

        response.data.permissions = rolePermissions

        this.role = API.copy(response)
      })

    this.getParentName = function (parent_id) {
      var index = this.systemPermissions.findIndex(x => x.id == parent_id);
      if (index == -1) {
        return 'Others';
      }
      return this.systemPermissions[index].name;

    }
    $scope.$watchCollection('vm.role.data.permissions', function (new_val, old_val) {

    });

    this.check_list_permision = function (obj) {
      var permissions = this.role.data.permissions;
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
          this.role.data.permissions = permissions;

        }
        this.role.data.permissions = permissions;
      }

    }

    this.parent = function (id) {
      $scope.items = $filter('filter')($scope.parent, 1);
    }

  }

  save(isValid) {
    if (isValid) {
      let $state = this.$state
      this.role.put()
        .then(() => {
          let alert = { type: 'success', 'title': 'Success!', msg: 'Group has been updated.' }
          $state.go($state.current, { alerts: alert }, { reload: true })
        }, (response) => {
          let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
          $state.go($state.current, { alerts: alert }, { reload: true })
        })
    } else {
      this.formSubmitted = true
    }
  }

  $onInit() { }
}

export const UserRolesEditComponent = {
  templateUrl: './views/app/pages/user-roles-edit/user-roles-edit.component.html',
  controller: UserRolesEditController,
  controllerAs: 'vm',
  bindings: {}
}
