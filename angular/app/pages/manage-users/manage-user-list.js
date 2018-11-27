class ManageUsersListsController {
    constructor($scope, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location, $timeout) {
        'ngInject'
        var vm = this
        this.$auth = $auth;
        this.$location = $location;
        this.SAAPI = SAAPI;
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.AclService = AclService
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        $scope.custom_search_terms = {}
        var rolesInfo = this.roles[0]
        this.$window = $window
        $scope.dontshowSuperadmin = false
        $scope.showAlert = false
        if ($stateParams.alerts) {
            $scope.showAlert = true
            this.alerts.push($stateParams.alerts)
            $timeout(function () {
                $scope.showAlert = false
            }, 8000);
        }

        if (!this.can('view.superadmin.users')) {
            $state.go('app.unauthorizedAccess');
        }


        $scope.$watch('selected_role', function (new_val, old_val) {
            delete $scope.custom_search_terms.role
            if (new_val != undefined && new_val != '') {
                $scope.custom_search_terms.role = new_val;

            }
            vm.load_table()
        })
        if (this.roles[0] == 'super.call.center') {
            $scope.dontshowSuperadmin = true
        }

        this.load_table = function () {
            $scope.tableId = "manageusers"
            var token = $window.localStorage.super_admin_token
            var custom_search_data = $scope.custom_search_terms;
            this.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/superadmin/users',
                    type: 'post',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        data.customFilter = custom_search_data;
                        return JSON.stringify(data);
                    },
                    error: function (xhr, error, thrown) {
                        //console.log("hello Error");
                        $state.go('app.logout');
                    }
                })
                .withDataProp('data')
                .withOption('serverSide', true)
                .withOption('processing', true)
                .withOption('stateSave', true)
                .withOption('stateSaveCallback', function (settings, data) {
                    localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));
                })
                .withOption('stateLoadCallback', function (settings, data) {
                    return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
                })
                .withColReorder()
                //.withColReorderOrder([2, 1, 2])
                .withColReorderOption('iFixedColumnsRight', 1)
                .withColReorderCallback(function () {
                })
                .withOption('createdRow', function (row) {
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('responsive', true)
                .withOption('order', [[0, 'desc']])
                .withBootstrap()

            this.dtColumns = [

                DTColumnBuilder.newColumn(null).withTitle('Name').withOption('sWidth', '120px').renderWith(function (data) {
                    if (rolesInfo == 'super.call.center') {
                        if (data.role == 'Call Center' || data.role == 'Call center user') {
                            return `<a class="" ui-sref="app.userinfoedit({userID: ${data.id}})" >
                                ${data.name}
                            </a>`
                        } else {
                            return `<a class="" href ="" >
                                ${data.name}
                            </a>`
                        }

                    } else {
                        return `<a class="" ui-sref="app.userinfoedit({userID: ${data.id}})" >
                                ${data.name}
                            </a>`
                    }

                }).notSortable(),

                DTColumnBuilder.newColumn(null).withTitle('Agency Name').withOption('sWidth', '100px').renderWith(function (data) {
                    if (data.agency_name && data.role != 'Call Center' && data.role != 'Call center user') {
                        return `
                                ${data.agency_name}
                            `;
                    } else {
                        return '--'
                    }

                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Role').withOption('sWidth', '100px').renderWith(function (data) {
                    return `
                                ${data.role}
                            `;

                }).notSortable(),
                DTColumnBuilder.newColumn('email').withTitle('Email').withOption('sWidth', '100px').notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Phone').withOption('sWidth', '100px').renderWith(function (data) {
                    let phnumber = data.phone
                    let Country_code = '+1';

                    if (Country_code != '') {
                        phnumber = phnumber.replace(Country_code, '')
                    }
                    var numbers = phnumber.replace(/\D/g, ''),
                        char = { 0: '(', 3: ') ', 6: ' - ' };
                    phnumber = '';
                    for (var i = 0; i < numbers.length; i++) {
                        phnumber += (char[i] || '') + numbers[i];
                    }
                    return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.mobile_number}">
                                ${phnumber}
                            </a>`;
                    //return phnumber;
                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('License').withOption('sWidth', '100px').renderWith(function (data) {
                    if (data.num_license && data.role != 'Call Center' && data.role != 'Call center user') {
                        return `<a class="" uib-tooltip="" tooltip-placement="bottom" >
                        ${data.count_companies}/${data.num_license}
                            </a>`;
                    } else {
                        return '--'
                    }

                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Action').withOption('sWidth', '141px').renderWith(function (data) {
                    if (rolesInfo == 'super.call.center') {
                        if (data.role == 'Call Center' || data.role == 'Call center user') {
                            var a = ` <a class="btn btn-xs btn-warning" uib-tooltip="Edit" tooltip-placement="bottom" ui-sref="app.userinfoedit({userID: ${data.id}})" ng-show="vm.can('update.superadmin.user')"><i class="fa fa-edit"></i></a> `;
                        } else {
                            var a = ` <a class="btn btn-xs btn-warning fa-disabled" uib-tooltip="Disable" tooltip-placement="bottom" ng-show="vm.can('update.superadmin.user')"><i class="fa fa-edit"></i></a> `;
                        }

                    } else {
                        var a = ` <a class="btn btn-xs btn-warning" uib-tooltip="Edit" tooltip-placement="bottom" ui-sref="app.userinfoedit({userID: ${data.id}})" ng-show="vm.can('update.superadmin.user')"><i class="fa fa-edit"></i></a> `;
                        if (data.status == "1") {
                            a += ` <button class="btn btn-xs btn-suspend" uib-tooltip="Suspend" tooltip-placement="bottom"  ng-click="vm.suspend(${data.id})" ng-show="vm.can('suspend.superadmin.user')"> <i class="fa fa-ban"></i></button>`;
                        }
                        else {
                            a += ` <button class="btn btn-xs btn-success" uib-tooltip="Activate" tooltip-placement="bottom"  ng-click="vm.activate(${data.id})" ng-show="vm.can('suspend.superadmin.user')"> <i class="fa fa-check-circle" aria-hidden="true"></i></button>`;
                        }
                        a += ` <button class="btn btn-xs btn-danger" uib-tooltip="Delete Account" tooltip-placement="bottom"  ng-click="vm.delete_account(${data.id})" ng-show="vm.can('delele.superadmin.user')"> <i class="fa fa-trash-o"></i></button>`;

                    }
                    return a


                })
            ]
            $('#' + $scope.tableId).DataTable().ajax.reload();
        }
        this.load_table()

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
        this.suspend = function (userId) {
            swal({
                title: "Are you sure?",
                text: "You want to suspend this User !",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#27b7da",
                confirmButtonText: "Yes, suspend it!",
                cancelButtonText: "No, cancel !",
                closeOnConfirm: false,
                closeOnCancel: true
            },
                function (isConfirm) {
                    if (isConfirm) {
                        let SuspendUser = SAAPI.service('suspend-account/' + userId, SAAPI.all('superadmin'))
                        SuspendUser.one("").put().then((response) => {

                            swal({
                                title: 'Suspend!',
                                text: 'User suspended',
                                type: 'success',
                                confirmButtonText: 'OK',
                                closeOnConfirm: true
                            }, function (response) {

                                $state.reload()
                            })
                        })
                    } else {
                        ///swal("Cancelled", "Your imaginary file is safe :)", "error");
                    }
                });
        }
        this.activate = function (userId) {
            swal({
                title: "Are you sure?",
                text: "You want to activate this User !",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Activate it!",
                cancelButtonText: "No, cancel !",
                closeOnConfirm: false,
                closeOnCancel: true
            },
                function (isConfirm) {
                    if (isConfirm) {
                        let ActivateUser = SAAPI.service('active-account/' + userId, SAAPI.all('superadmin'))
                        ActivateUser.one("").put().then((response) => {
                            var res = response.plain();
                            swal({
                                title: 'Activated!',
                                text: 'User Activated',
                                type: 'success',
                                confirmButtonText: 'OK',
                                closeOnConfirm: true
                            }, function () {
                                $state.reload()
                            })
                        })
                    } else {
                        ///swal("Cancelled", "Your imaginary file is safe :)", "error");
                    }
                });

        }

        this.delete_account = function (userId) {
            swal({
                title: "Are you sure?",
                text: "You want to delete this user !",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel !",
                closeOnConfirm: false,
                closeOnCancel: true
            },
                function (isConfirm) {
                    if (isConfirm) {
                        let ActivateUser = SAAPI.service('superadmin-account', SAAPI.all('superadmin'))
                        ActivateUser.one(userId).remove()
                            .then((response) => {
                                swal({
                                    title: 'Deleted!',
                                    text: 'User deleted successfully!',
                                    type: 'success',
                                    confirmButtonText: 'OK',
                                    closeOnConfirm: true
                                }, function () {
                                    $state.reload()
                                })
                            })
                    } else {
                        ///swal("Cancelled", "Your imaginary file is safe :)", "error");
                    }
                });

        }

    }

    $onInit() { }
}

export const ManageUsersListComponent = {
    templateUrl: './views/app/pages/manage-users/manage-user-list.html',
    controller: ManageUsersListsController,
    controllerAs: 'vm',
    bindings: {}
}
