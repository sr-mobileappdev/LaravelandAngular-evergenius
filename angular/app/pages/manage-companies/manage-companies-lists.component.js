class ManageCompaniesListsController {
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
        $scope.rolesData = []
        this.AclService = AclService
        this.$window = $window
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        $scope.custom_search_terms = {}
        $scope.showcompany = true
        $scope.showAgent = false
        $scope.showSuccessmessage = false
        $window.localStorage.removeItem('newcompanyData')
        $window.localStorage.removeItem('companyOBJ')
        if ($stateParams.alerts) {

            this.alerts.push($stateParams.alerts)
            $scope.showSuccessmessage = true
            $timeout(function () {
                $scope.showSuccessmessage = false
            }, 8000);
        }
        if (this.roles[0] == 'super.call.center') {
            $scope.showAgent = true

        }
        if (this.roles[0] == 'admin.super') {
            let getRoles = SAAPI.service('agent-users', SAAPI.all('superadmin'))
            getRoles.one("").get().then((response) => {
                if (response != undefined && response != null) {
                    $scope.rolesData = response.plain().data
                }

            })
            $scope.$watch('selected_assine', function (new_val, old_val) {
                delete $scope.custom_search_terms.agent_user
                if (new_val != undefined && new_val != '') {
                    $scope.custom_search_terms.agent_user = new_val;

                }
                vm.load_table()
            })

        }
        if (this.roles[0] == 'super.admin.agent') {
            let licenseInfo = SAAPI.service('licence-information', SAAPI.all('superadmin'))
            licenseInfo.one("").get().then((response) => {
                if (response != undefined && response != null) {
                    $scope.licenseInfo = response.plain().data
                    $scope.remaingCompany = parseInt($scope.licenseInfo.num_license) - parseInt($scope.licenseInfo.total_companies)
                    if ($scope.licenseInfo.total_companies >= $scope.licenseInfo.num_license) {
                        $scope.showcompany = false
                    } else {
                        $scope.showcompany = true

                    }
                }
            })


        }

        this.load_table = function () {
            $scope.tableId = "managecompnies"
            var token = $window.localStorage.super_admin_token
            var custom_search_data = $scope.custom_search_terms;
            this.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/superadmin',
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
                .withColReorderCallback(function () { })
                .withOption('createdRow', function (row) {
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('responsive', true)
                .withOption('order', [
                    [0, 'desc']
                ])
                .withBootstrap()

            this.dtColumns = [

                DTColumnBuilder.newColumn(null).withTitle('Name').withOption('sWidth', '120px').withOption('sWidth', '0px').renderWith(function (data) {
                    if (data.name != null && data.name != undefined && data.name != '') {
                        return `<a class="" uib-tooltip="" tooltip-placement="bottom" ui-sref="app.companyedit({companyId: ${data.id}})">
                                ${data.name}
                            </a>`;
                    } else {
                        return "--"
                    }

                    //return phnumber;
                }).notSortable(),
                DTColumnBuilder.newColumn('email').withTitle('Email').withOption('sWidth', '100px').notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Agency Name').withOption('sWidth', '0px').renderWith(function (data) {
                    if (data.agency_name != null && data.agency_name != undefined && data.agency_name != '') {
                        return `<a class="" uib-tooltip="" tooltip-placement="bottom" ui-sref="app.companyedit({companyId: ${data.id}})">
                                ${data.agency_name}
                            </a>`;
                    } else {
                        return "--"
                    }

                    //return phnumber;
                }).notSortable(),
                DTColumnBuilder.newColumn('address').withTitle('Address').withOption('sWidth', '100px').notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Phone').withOption('sWidth', '100px').renderWith(function (data) {
                    let phnumber = data.phone
                    let Country_code = '+1';

                    if (Country_code != '') {
                        phnumber = phnumber.replace(Country_code, '')
                    }
                    var numbers = phnumber.replace(/\D/g, ''),
                        char = {
                            0: '(',
                            3: ') ',
                            6: ' - '
                        };
                    phnumber = '';
                    for (var i = 0; i < numbers.length; i++) {
                        phnumber += (char[i] || '') + numbers[i];
                    }
                    return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.mobile_number}">
                                ${phnumber}
                            </a>`;
                    //return phnumber;
                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Options').withOption('sWidth', '180px').renderWith(function (data) {
                    var a = ` <a class="btn btn-xs btn-warning" uib-tooltip="Edit" tooltip-placement="bottom" ui-sref="app.companyedit({companyId: ${data.id}})" ng-show="vm.can('edit.company')"><i class="fa fa-edit"></i></a> `;
                    if (data.is_active && data.is_active == "1") {
                        a += ` <button class="btn btn-xs btn-suspend" uib-tooltip="Suspend" tooltip-placement="bottom"  ng-click="vm.suspend(${data.id})" ng-show="vm.can('suspend.company')"> <i class="fa fa-ban"></i></button>`;
                    } else {
                        a += ` <button class="btn btn-xs btn-success" uib-tooltip="Activate" tooltip-placement="bottom"  ng-click="vm.activate(${data.id})" ng-show="vm.can('suspend.company')"> <i class="fa fa-check-circle" aria-hidden="true"></i></button>`;
                    }
                    a += ` <button class="btn btn-xs btn-danger" uib-tooltip="Delete Account" tooltip-placement="bottom"  ng-click="vm.delete_account(${data.id})" ng-show="vm.can('delete.company')"> <i class="fa fa-trash-o"></i></button>`;

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
        this.impersonate = function (companyId) {
            swal({
                title: "Are you sure?",
                text: "You want to login into this company !",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#27b7da",
                confirmButtonText: "Yes!",
                cancelButtonText: "No, cancel !",
                closeOnConfirm: false,
                closeOnCancel: true
            },
                function (isConfirm) {
                    if (isConfirm) {
                        if ($window.localStorage.impersonated != 1 || $window.localStorage.impersonated == 'undefined') {
                            let UserData = SAAPI.service('impersonate', SAAPI.all('superadmin'))
                            UserData.post(companyId).then((response) => {
                                let data = response.data
                                angular.forEach(response.data.userRole, function (value) {
                                    AclService.attachRole(value)
                                })
                                $auth.setToken(response)
                                AclService.setAbilities(response.data.abilities)
                                $window.localStorage.impersonated = 1;
                                $window.localStorage.sidebar_docotors = JSON.stringify(response.data.calendar_doctors);
                                $window.localStorage.user_data = JSON.stringify(response.data.user);
                                swal({
                                    title: 'Login as client!',
                                    text: '<a href="/" target="_BLANK"  class="btn btn-success">Click here to company dashboard</a>',
                                    type: 'success',
                                    confirmButtonText: 'OK',
                                    closeOnConfirm: true,
                                    html: true
                                }, function () {
                                    //state_s.reload()
                                })

                            })
                        } else {
                            swal({
                                title: 'Information!',
                                text: '<p>You have already impersonated with an account,Please logout from that account</p><br/><p><a href="/" target="_BLANK"  class="btn btn-success">Click here to company dashboard</a></p>',
                                type: 'warning',
                                confirmButtonText: 'OK',
                                closeOnConfirm: true,
                                html: true
                            }, function () {

                            })
                        }
                    }
                });
        }
        this.suspend = function (companyId) {
            swal({
                title: "Are you sure?",
                text: "You want to suspend this company !",
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
                        let SuspendUser = SAAPI.service('suspend-company', SAAPI.all('superadmin'))
                        SuspendUser.post(companyId).then((response) => {
                            var res = response.plain();
                            $window.localStorage.admin_companies = JSON.stringify(res.data.admin_compnies);
                            swal({
                                title: 'Suspend!',
                                text: 'Company profile suspended',
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
        this.activate = function (companyId) {
            swal({
                title: "Are you sure?",
                text: "You want to activate this company !",
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
                        let ActivateUser = SAAPI.service('activate-company', SAAPI.all('superadmin'))
                        ActivateUser.post(companyId).then((response) => {
                            var res = response.plain();
                            $window.localStorage.admin_companies = JSON.stringify(res.data.admin_compnies);
                            swal({
                                title: 'Activated!',
                                text: 'Company profile Activated',
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

        } //function

        /* Delete Account */

        this.delete_account = function (companyId) {
            swal({
                title: "Are you sure?",
                text: "You want to delete this company !",
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
                        let ActivateUser = SAAPI.service('account', SAAPI.all('superadmin'))
                        ActivateUser.one(companyId).remove()
                            .then((response) => {
                                swal({
                                    title: 'Deleted!',
                                    text: 'Company profile deleted successfully!',
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
    toggleOne() {
        let $state = this.$state
        var sele = angular.element($('.selected_val')).serializeArray();
        if (sele.length === 0) {
            this.isdelseleted = false;
        } else {
            this.isdelseleted = true;
        }
    }

    delete(contactId) {
        var $state = this.$state
        var state_s = this.$state
        swal({
            title: 'Are you sure?',
            text: 'You want to suspend this company profile!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#27b7da',
            confirmButtonText: 'Yes, Suspend it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function () {
            let UserData = SAAPI.service('suspend-company', SAAPI.all('superadmin'))
            UserData.post(contactId).then(() => {
                var $state = this.$state
                swal({
                    title: 'Suspend!',
                    text: 'Company profile suspended',
                    type: 'success',
                    confirmButtonText: 'OK',
                    closeOnConfirm: true
                }, function () {
                    state_s.reload()
                })
            })
        })
    }

    multi_del() {
        let $state = this.$state
        var sele = angular.element($('.selected_val')).serializeArray();
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
            let conts = SAAPI.service('del-contacts', SAAPI.all('contacts'))
            conts.post({
                'selected_del': sele
            })
                .then(() => {
                    swal({
                        title: 'Deleted!',
                        text: 'Conatct has been deleted.',
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

export const ManageCompaniesListComponent = {
    templateUrl: './views/app/pages/manage-companies/manage-companies.component.html',
    controller: ManageCompaniesListsController,
    controllerAs: 'vm',
    bindings: {}
}
