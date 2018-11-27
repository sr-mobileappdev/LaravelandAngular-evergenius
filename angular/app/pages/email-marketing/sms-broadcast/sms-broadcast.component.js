class SmsBroadcastController {
    constructor($scope, API, $auth, $stateParams, $state, $uibModal, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location) {
        'ngInject'
        var token = $window.localStorage.satellizer_token
        $scope.API = API;
        $scope.$state = $state
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/sms-broadcast/listing',
                type: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization",
                        "Bearer " + token);
                },
                data: function (data, dtInstance) {

                    return JSON.stringify(data);
                },
                error: function (err) {
                    let data = []
                    return JSON.stringify(data);
                }
            })
            .withDataProp('data')
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('displayLength', 20)
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
            DTColumnBuilder.newColumn(null).withTitle('Name').renderWith(function (data) {
                if (data.title) {
                    if (data.status == 4 || data.status == 3) {
                        if (data.status == 4) {
                            var name = 'in-progress'

                        } else {
                            name = 'sent'
                        }

                        return `<a class="" href="" uib-tooltip="View" tooltip-placement="bottom" ng-click="view_broadcast(${data.id})"` + data.id + `" >
                            ${data.title} 
                        </a> - <span class="${name} em_label"><b>${name}</b></span>
                        `
                    } else {
                        if (data.status == 2 || data.status == 1) {
                            if (data.status == 2) {
                                var name = 'scheduled'

                            } else {
                                name = 'draft'
                            }

                        }

                        return `<a class="" href="" uib-tooltip="View" tooltip-placement="bottom" ng-click="edit_broadcast(${data.id})" >
                            ${data.title} 
                        </a> - <span class="${name} em_label"><b>${name}</b></span>
                        `
                    }

                } else {
                    return 'No Title'
                }
            }).notSortable(),

            DTColumnBuilder.newColumn(null).withTitle('Schedule Date').renderWith(function (data) {

                let createdDate = ''
                if (data.schedule_datetime != null && data.schedule_datetime != '' && data.schedule_datetime != undefined) {
                    let dateChange = new Date(data.schedule_datetime)
                    createdDate = moment(dateChange).format('MMM Do YYYY, h:mm A')
                    return `${createdDate}`
                } else {
                    return '-----'
                }

            }).notSortable(),

            DTColumnBuilder.newColumn(null).withTitle('DATE').renderWith(function (data) {
                let createdDate = ''
                if (data.sent_at != null && data.sent_at != '' && data.sent_at != undefined) {
                    let dateChange = new Date(data.sent_at)
                    createdDate = moment(dateChange).format('MMM Do YYYY, h:mm A')
                    return `${createdDate}`
                } else {
                    return '-----'
                }

            }).notSortable(),


            DTColumnBuilder.newColumn(null).withTitle('Options').withOption('sWidth', '150px').renderWith(function (data) {

                if (data.status[0]) {
                    var id = data.status[0]
                    return `
               <a class="btn btn-xs btn-warning color-orange"  uib-tooltip="Stats" tooltip-placement="bottom"  ng-click="graph_view_campaigns(${data.id})">
                <i class="fa fa-bar-chart"></i> </a> 
            <a class="btn btn-xs btn-primary" uib-tooltip="View" ng-if=" ${data.status[0]} == '4' || ${data.status[0]} == '3'" ng-click="view_broadcast(${data.id})"` + data.id + `">
            <i class="fa fa-eye"></i>    </a> 
       
						 <a class="btn btn-xs btn-warning"  uib-tooltip="Edit" tooltip-placement="bottom"  ng-click="edit_broadcast(${data.id})"` + data.id + `"  ng-if=" ${data.status[0]} != '4' && ${data.status[0]} != '3'">
                            <i class="fa fa-edit"></i>
                        </a> 
                       <a class="btn btn-xs btn-warning color-blue"  uib-tooltip="Clone" tooltip-placement="bottom"  ng-click="clone_smsbrodcast(${data.id})"` + data.id + `" >
                            <i class="fa fa-clone"></i>
                        </a> 
                        <button class="btn btn-xs btn-danger"  uib-tooltip="Delete" tooltip-placement="bottom" ng-click="delete(${data.id})">
                        <i class="fa fa-trash-o"></i>
                    </button>               
                       `
                }


            }).notSortable()

        ]

        this.displayTable = true

        this.dtInstanceCallback = function (dtInstance) {
            this.dtInstance = dtInstance;
            dtInstance.DataTable.on('draw.dt', () => {
                let elements = angular.element("#" + dtInstance.id + " .ng-scope");
                angular.forEach(elements, (element) => {
                    $compile(element)($scope)
                })
            });
        }


        //


        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }
        let campaign_status = API.service('campaigns-stat', API.all('email-marketing'))
        campaign_status.one("").get()
            .then((response) => {

                $scope.campaign_status = response.plain().data

            })


        $scope.ShowHide = function () {
            //If DIV is visible it will be hidden and vice versa.
            $scope.IsVisible = $scope.IsVisible ? false : true;
        }
        $scope.clone_smsbrodcast = function (id, name) {

            swal({
                title: 'Are you sure?',
                text: 'You want to clone this SMS Broadcast',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, clone it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                var update_template_title = API.service('clone-sms-broadcast/' + id, API.all('sms-broadcast'));
                update_template_title.one("").get()
                    .then((response) => {

                        swal({
                            title: 'Cloned!',
                            text: 'SMS Broadcast has been Cloned',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            $state.reload()

                        })
                    });


            })


        }

        $scope.graph_view_campaigns = function (id) {
            var Obj = {}
            let broadcastStatus = API.service('campign-stat/' + id, API.all('sms-broadcast'))
            broadcastStatus.one("").get()
                .then((response) => {
                    Obj = {
                        totalSms: response.plain().data.count.total,
                        deleverSms: response.plain().data.count.sent
                    }
                    const modalInstance = $uibModal.open({
                        animation: true,
                        templateUrl: './views/app/pages/email-marketing/sms-broadcast/sms-broadcast-stat.html',
                        controller: SMSStatModalController,
                        resolve: {
                            Data: function () {
                                return Obj;
                            },
                        }
                    });
                    return modalInstance
                })

        }
        $scope.action_modal = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/campaigns/new-campaigns-modal.html',
                controller: MarketingAddNewModalController,
            });
            return modalInstance;
        }
        $scope.action_funnel = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/new_action_funnel_modal.html',
                controller: NewActionFunnelModalController,
            });
            return modalInstance;
        }

        $scope.edit_broadcast = function (id) { $state.go('app.editsmsbroadcast', { broadcast_id: id }) }
        $scope.view_broadcast = function (id) { $state.go('app.viewsmsbroadcast', { broadcast_id: id }) }



        /********************************* Delete broadcast **************************************/

        $scope.delete = function (contactId) {
            var API = $scope.API;
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
                var API = $scope.API;

                API.one('sms-broadcast').one('campign', contactId).remove()
                    .then(() => {
                        // var $state = this.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Sms Broadcast has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            var $state = $scope.$state
                            $state.reload()
                        })
                    })
            })
        }

    }


    $onInit() { }
}
class SMSStatModalController {
    constructor($stateParams, $scope, Data, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.stats_data = Data
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }
}
export const SmsBroadcastComponent = {
    templateUrl: './views/app/pages/email-marketing/sms-broadcast/sms-broadcast.component.html',
    controller: SmsBroadcastController,
    controllerAs: 'vm',
    bindings: {}
}