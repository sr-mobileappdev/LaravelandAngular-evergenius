class managestaffController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, AclService) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.$window = $window
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        this.user_role = this.roles[0]

        this.can = AclService.can
        if (!this.can('manage.staff')) {
            $state.go('app.unauthorizedAccess');
        }

        var userlist = API.service('company-users', API.all('users'))
        userlist.one().get()
            .then((response) => {
                let respo = response.plain().data.users;

                this.dtOptions = DTOptionsBuilder.newOptions()
                    .withOption('data', respo)
                    .withOption('createdRow', createdRow)
                    .withOption('responsive', true)
                    .withBootstrap()

                this.dtColumns = [
                    //DTColumnBuilder.newColumn('id').withTitle('ID'),
                    DTColumnBuilder.newColumn(null).withTitle('Name').notSortable().renderWith(clickfun),
                    DTColumnBuilder.newColumn(null).withTitle('Phone').notSortable().renderWith(function (data) {
                        let phnumber = data.phone
                        if (data.phone == null) {
                            return '';
                        }
                        var numbers = phnumber.replace(/\D/g, ''),
                            char = { 0: '(', 3: ') ', 6: ' - ' };
                        phnumber = '';
                        for (var i = 0; i < numbers.length; i++) {
                            phnumber += (char[i] || '') + numbers[i];
                        }
                        return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.phone}">
                                ${phnumber}
                            </a>`;
                        //return data.phone_country_code;
                    }).withOption('sWidth', '20%'),
                    DTColumnBuilder.newColumn('email').withTitle('Email').notSortable(),
                    DTColumnBuilder.newColumn('role_name').withTitle('Role').notSortable(),
                    DTColumnBuilder.newColumn(null).withTitle('Actions').notSortable()
                        .renderWith(actionsHtml)
                ]

                this.displayTable = true
            })

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }
        let clickfun = (data) => {

            return `
    <a  ui-sref="app.staffedit({userId: ${data.id}})">
    `+ data.name + `
    </a>
    `
        }


        let actionsHtml = (data) => {

            return `
          <a class="btn btn-xs btn-warning" uib-tooltip="Edit" tooltip-placement="bottom" ui-sref="app.staffedit({userId: ${data.id}})">
              <i class="fa fa-edit"></i>
          </a>
          &nbsp
          <button class="btn btn-xs btn-danger" uib-tooltip="Delete" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
              <i class="fa fa-trash-o"></i>
          </button>`

        }
    }

    delete(Id) {
        let API = this.API
        let $state = this.$state

        swal({
            title: 'Are you sure?',
            text: 'You will not be able to recover this data!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function () {
            API.one('users').one('user', Id).remove()
                .then(() => {
                    swal({
                        title: 'Deleted!',
                        text: 'Staff has been deleted.',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function () {
                        $state.reload()
                    })
                },
                    function errorCallback(response) {
                        swal("Cancelled", "You can not delete last company admin user.", "error");
                    });
        })
    }
    $onInit() { }
}

export const ManagestaffComponent = {
    templateUrl: './views/app/pages/manage-staff/manage-staff.html',
    controller: managestaffController,
    controllerAs: 'vm',
    bindings: {}
}
