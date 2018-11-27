class UserRolesController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, AclService) {
        'ngInject'
        this.API = API
        this.$state = $state

        let Roles = this.API.service('roles', this.API.all('users'))

        this.can = AclService.can
        if (!this.can('manage.roles')) {
            $state.go('app.unauthorizedAccess');
        }

        Roles.getList()
            .then((response) => {
                let dataSet = response.plain()

                this.dtOptions = DTOptionsBuilder.newOptions()
                    .withOption('data', dataSet)
                    .withOption('createdRow', createdRow)
                    .withOption('responsive', true)
                    .withBootstrap()

                this.dtColumns = [
                    //DTColumnBuilder.newColumn('id').withTitle('ID'),
                    DTColumnBuilder.newColumn(null).withTitle('Role').withOption('sWidth', '30%').notSortable().renderWith(clickfun),
                    DTColumnBuilder.newColumn('description').withOption('sWidth', '50%').withTitle('Description'),
                    DTColumnBuilder.newColumn(null).withTitle('Actions').withOption('sWidth', '20%').notSortable()
                        .renderWith(actionsHtml)
                ]

                this.displayTable = true
            })

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }
        let clickfun = (data) => {
            if (data.slug != 'admin.user' && data.slug != 'doctor' && data.slug != 'sales') {
                return `
    <a  ui-sref="app.userrolesedit({roleId: ${data.id}})">
    `+ data.name + `
    </a>
    `
            }
            return data.name;
        }
        let actionsHtml = (data) => {
            if (data.slug != 'admin.user' && data.slug != 'doctor' && data.slug != 'sales') {
                return `
                <a class="btn btn-xs btn-warning" uib-tooltip="Edit" tooltip-placement="bottom" ui-sref="app.userrolesedit({roleId: ${data.id}})">
                    <i class="fa fa-edit"></i>
                </a>
                &nbsp
                <button class="btn btn-xs btn-danger" uib-tooltip="Delete" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
                    <i class="fa fa-trash-o"></i>
                </button>`
            }
            return `
                <button class="btn btn-xs btn-warning" disabled="disabled" >
                    <i class="fa fa-edit"></i>
                </button>
                &nbsp
                <button class="btn btn-xs btn-danger" disabled="disabled">
                    <i class="fa fa-trash-o"></i>
                </button>`;
        }
    }

    delete(roleId) {
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
            API.one('users').one('roles', roleId).remove()
                .then(() => {
                    swal({
                        title: 'Deleted!',
                        text: 'Group has been deleted.',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function () {
                        $state.reload()
                    })
                })
        })
    }

    $onInit() { }
}

export const UserRolesComponent = {
    templateUrl: './views/app/pages/user-roles/user-roles.component.html',
    controller: UserRolesController,
    controllerAs: 'vm',
    bindings: {}
}
