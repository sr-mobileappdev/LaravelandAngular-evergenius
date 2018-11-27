class EmailTemplatesController {
    constructor($scope, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location, $timeout, $uibModal) {
        'ngInject'
        var vm = this
        this.$auth = $auth;
        this.$location = $location;
        this.SAAPI = SAAPI;
        this.$state = $state

        this.alerts = []
        this.AclService = AclService
        this.can = AclService.can
        this.$window = $window
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        if (!this.can('superadmin.emailtemplates')) {
            $state.go('app.unauthorizedAccess');
        }

        var token = $window.localStorage.super_admin_token
        var custom_search_data = $scope.custom_search_terms;
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/superadmin/admin-notifications',
                type: 'post',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization",
                        "Bearer " + token);
                },
                data: function (data, dtInstance) {
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
            .withOption('aaSorting', [[0, 'desc']])
            .withBootstrap()

        this.dtColumns = [

            DTColumnBuilder.newColumn('title').withTitle('Title').withOption('sWidth', '120px'),
            DTColumnBuilder.newColumn('email_subject').withTitle('Subject').withOption('sWidth', '120px'),
            DTColumnBuilder.newColumn(null).withTitle('Status').withOption('sWidth', '100px').renderWith(function (data) {
                var status = ''
                if (data.status == '1') {
                    status = 'Active'
                    return `<a class="" uib-tooltip="" tooltip-placement="bottom" >
                                ${status}
                            </a>`;
                } else {
                    status = 'Inactive'
                    return `<a class="" uib-tooltip="" tooltip-placement="bottom" >
                                ${status}
                            </a>`;
                }


                //return phnumber;
            }),
            DTColumnBuilder.newColumn(null).withTitle('Action').withOption('sWidth', '8px').renderWith(function (data) {
                var a = ` <a class="btn btn-xs btn-warning" uib-tooltip="Edit" tooltip-placement="bottom" ng-click="editTemplate(${data.id})"><i class="fa fa-edit"></i></a> `;

                return a
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

        $scope.editTemplate = function (id) {
            $scope.tempid = id
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-templates/email-template-edit-modal.html',
                controller: EditTemplateModalController,
                windowClass: 'email-temp-class',
                backdrop: 'static',
                size: 'md',
                resolve: {
                    tempId: function () {
                        return $scope.tempid;
                    }
                }
            });
            // return modalInstance;
        }
    }


    $onInit() { }
}
class EditTemplateModalController {
    constructor($stateParams, $scope, tempId, $state, $http, $location, SAAPI, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        var vm = this
        var uibModalInstance = $uibModalInstance;
        $scope.templateData = {}
        $scope.templateData.data = {}
        let GetNotification = SAAPI.service('notification/' + tempId, SAAPI.all('superadmin'))
        GetNotification.one("").get().then((response) => {
            var tempdata = response.plain()
            $scope.templateData = SAAPI.copy(response)
        })

        $scope.submit_action_form = function (isvalid) {
            if (isvalid) {
                $scope.templateData.put()
                    .then((response) => {
                        let alert = { type: 'success', 'title': 'Success!', msg: 'Template has been updated.' }
                        $state.go('app.emailtemplates', { alerts: alert }, { reload: true })
                        $uibModalInstance.close();
                    }, (response) => {
                        let alert = { type: 'error', 'title': 'Error!', msg: 'Something went wrong !!!' }
                        $state.go($state.current, { alerts: alert })
                    })
            }

        }
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }
}
export const EmailTemplatesComponent = {
    templateUrl: './views/app/pages/email-templates/email-template-component.html',
    controller: EmailTemplatesController,
    controllerAs: 'vm',
    bindings: {}
}
