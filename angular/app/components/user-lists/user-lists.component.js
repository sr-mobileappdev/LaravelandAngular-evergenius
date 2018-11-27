class UserListsController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, AclService) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.$window = $window

        this.can = AclService.can
        if (!this.can('view.provider')) {
            $state.go('app.unauthorizedAccess');
        }

        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        this.user_role = this.roles[0]
        let Users = this.API.service('users')
        var token = $window.localStorage.satellizer_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/users',
                type: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization",
                        "Bearer " + token);
                },
                data: function (data, dtInstance) {

                    return JSON.stringify(data);
                }, error: function (err) {
                    let data = []
                    return JSON.stringify(data);
                }
            })
            .withDataProp('data')
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            })
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function (settings, data) {
                localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));
            })
            .withOption('stateLoadCallback', function (settings, data) {
                return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
            })
            .withColReorder()
            .withColReorderOption('iFixedColumnsRight', 1)
            .withColReorderCallback(function () {
            })
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            })
            .withOption('responsive', true)
            .withOption('aaSorting', [
                [0, 'desc']
            ])
            .withBootstrap()

        this.dtColumns = [

            DTColumnBuilder.newColumn('id').withTitle('ID'),
            DTColumnBuilder.newColumn(null).withTitle('Name').renderWith(function (data) {
                return `<a class="" uib-tooltip="View Provider" tooltip-placement="bottom" ui-sref="app.useredit({userId:${data.id}})">
                                ${data.name}
                            </a>`
            }),
            DTColumnBuilder.newColumn(null).withTitle('Email').renderWith(function (data) {
                return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="mailto:${data.email}">
                        ${data.email}
                    </a>`;
            }),
            DTColumnBuilder.newColumn(null).withTitle('Phone').renderWith(function (data) {
                let phnumber = data.phone
                let Country_code = '+1'

                if (phnumber != '' || phnumber != null) {

                    if (Country_code != '') {
                        phnumber = phnumber.replace(Country_code, '')
                    }
                    var numbers = phnumber.replace(/\D/g, ''),
                        char = { 0: '(', 3: ') ', 6: ' - ' };
                    phnumber = '';
                    for (var i = 0; i < numbers.length; i++) {
                        phnumber += (char[i] || '') + numbers[i];
                    }
                    //return phnumber;
                    return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.phone}">
                                ${phnumber}
                            </a>`;
                }
                return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.phone}">
                                ${phnumber}
                            </a>`;
                // return phnumber;
            }),
            DTColumnBuilder.newColumn('city').withTitle('City'),
            DTColumnBuilder.newColumn(null).withTitle('Actions').notSortable()
                .renderWith(function (data) {
                    return `<a class="btn btn-xs btn-warning" ng-show="vm.can('add.provider')" uib-tooltip="Edit" tooltip-placement="bottom" ui-sref="app.useredit({userId: ${data.id}})">
            <i class="fa fa-edit"></i>
        </a>
        
        <button class="btn btn-xs btn-danger" ng-show="vm.can('delete.provider')" uib-tooltip="Delete" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
            <i class="fa fa-trash-o"></i>
        </button>`
                })
        ]

        this.displayTable = true

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }

        this.dtInstanceCallback = function (dtInstance) {
            this.dtInstance = dtInstance;
            dtInstance.DataTable.on('draw.dt', () => {
                let elements = angular.element("#" + dtInstance.id + " .ng-scope");
                angular.forEach(elements, (element) => {
                    $compile(element)($scope)
                })
            });
        }
    }

    delete(userId) {
        let API = this.API
        let $state = this.$state
        var $window = this.$window
        swal({
            title: 'Are you sure?',
            text: 'You will not be able to recover this data!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#27b7da',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function () {
            API.one('users').one('user', userId).remove()
                .then(function (response) {
                    let data_res = response.plain()
                    delete $window.localStorage.sidebar_docotors
                    $window.localStorage.sidebar_docotors = JSON.stringify(data_res.data.company_doctors)
                    swal({
                        title: 'Deleted!',
                        text: 'User Permission has been deleted.',
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

export const UserListsComponent = {
    templateUrl: './views/app/components/user-lists/user-lists.component.html',
    controller: UserListsController,
    controllerAs: 'vm',
    bindings: {}
}
