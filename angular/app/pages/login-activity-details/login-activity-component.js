class LoginActivityController {
    constructor($scope, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location, $timeout,DTColumnDefBuilder) {
        'ngInject'
        var vm = this
        this.$auth = $auth;
        this.$location = $location;
        this.SAAPI = SAAPI;
        this.$state = $state
        this.$state = $state
        this.alerts = []
        this.AclService = AclService
        this.$window = $window
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        $scope.custom_search_terms = {}
        $scope.usersData = []
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        if (!this.can('superadmin.loginactivity')) {
            $state.go('app.unauthorizedAccess');
        }
        this.start_date = moment().subtract(30, 'days')
        this.end_date = moment()

        this.min_date = moment().subtract(182, 'days')
        this.max_date = moment()
        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };
        $scope.datePickerOptions = {
            linkedCalendars: false,
            startDate: moment().subtract(30, 'days'),
            endDate: moment(),
            alwaysShowCalendars: true,
            locale: {
                applyClass: 'btn-green',
                applyLabel: "Apply",
                fromLabel: "From",
                format: "MMMM DD, YYYY", //will give you 2017-01-06
                toLabel: "To",
                cancelLabel: 'Cancel',
                customRangeLabel: 'Custom range'
            },
            ranges: {
                'Last 7 Days': [moment().subtract(7, 'days'), moment()],
                'Last 30 Days': [moment().subtract(30, 'days'), moment()]
            },
            eventHandlers: {
                'apply.daterangepicker': function (ev, picker) {
                    var start_date_d = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
                    var end_date_d = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
                    $scope.custom_search_terms.start_date = start_date_d
                    $scope.custom_search_terms.end_date = end_date_d
                    vm.load_table()
                }
            }
        }

        let getUsers = SAAPI.service('super-admin-users', SAAPI.all('superadmin'))
        getUsers.one("").get().then((response) => {

            $scope.usersData = response.plain().data

        })
        $scope.$watch('selected_assine', function (new_val, old_val) {
            delete $scope.custom_search_terms.user_id
            if (new_val != undefined && new_val != '') {
                $scope.custom_search_terms.user_id = new_val;

            }
            vm.load_table()
        })

        $scope.$watch('selected_role', function (new_val, old_val) {
            delete $scope.custom_search_terms.role
            if (new_val != undefined && new_val != '') {
                $scope.custom_search_terms.role = new_val;

            }
            vm.load_table()
        })


        this.load_table = function () {
            $scope.tableId = "loginactivity"
            var token = $window.localStorage.super_admin_token
            var custom_search_data = $scope.custom_search_terms;
            this.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/superadmin/login-activities',
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
                //.withOption('order', [])
                .withOption('order', false)
                .withOption('serverSide', true)
                .withOption('displayLength', 20)
                .withOption('processing', true)
                .withOption('stateSave', false)
                .withOption('stateSaveCallback', function (settings, data) {
                    localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));
                })
                .withOption('stateLoadCallback', function (settings, data) {
                    return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
                })
                //.withColReorder()
                //.withColReorderOrder([2, 1, 2])
                //.withColReorderOption('iFixedColumnsRight', 1)
                .withColReorderCallback(function () {
                })
                .withOption('createdRow', function (row) {
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('responsive', true)
                .withBootstrap();

            this.dtColumns = [
                DTColumnBuilder.newColumn(null).withTitle('Date(UTC)').withOption('sWidth', '0px').renderWith(function (data) {

                    let createdDate = ''

                    let dateChange = new Date(data.time)
                    createdDate = moment(dateChange).format('MMM Do YYYY, h:mm a')
                    return `${createdDate}`

                }).notSortable(),
                DTColumnBuilder.newColumn('name').withTitle('User').notSortable(),
                DTColumnBuilder.newColumn('email').withTitle('Email').notSortable(),
                DTColumnBuilder.newColumn('role').withTitle('Role').notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Access').renderWith(function (data) {
                    return `<a class="" uib-tooltip="${data.device_name}" tooltip-placement="bottom" href="">
                                ${data.device_type}
                            </a>`;
                    //return phnumber;
                }).notSortable(),
                DTColumnBuilder.newColumn('ip_address').withTitle('IP').withOption('sWidth', '100px').notSortable(),
                DTColumnBuilder.newColumn('event').withTitle('Event').withOption('sWidth', '100px').notSortable(),


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

    }




    $onInit() { }
}

export const LoginActivityComponent = {
    templateUrl: './views/app/pages/login-activity-details/login-activity-component.html',
    controller: LoginActivityController,
    controllerAs: 'vm',
    bindings: {}
}
