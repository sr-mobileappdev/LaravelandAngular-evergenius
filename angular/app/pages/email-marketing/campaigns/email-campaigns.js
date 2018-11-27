class EmailCampaignsController {
    constructor($scope, API, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location, $uibModal) {
        'ngInject'

        this.API = API
        this.$state = $state
        var state_s = this.$state
        window.localStorage.removeItem("newsletter.autosave.json")
        window.localStorage.removeItem("newsletter.autosave.html")
        window.localStorage.removeItem("Editor_body")
        window.localStorage.removeItem("campaignInfo")
        window.localStorage.removeItem("editcampaignInfo")
        window.localStorage.removeItem("Editor_json")
        window.localStorage.removeItem("Editor_body")
        $scope.IsVisible = false

        /********************************* Geting Campaigns **************************************/

        var token = $window.localStorage.satellizer_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/email-marketing/campaigns',
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
            DTColumnBuilder.newColumn(null).withTitle('Broadcast Name').renderWith(function (data) {
                if (data.name) {
                    if (data.status[0].id == 4 || data.status[0].id == 3) {
                        return `<a class="" href="" uib-tooltip="View" tooltip-placement="bottom" ng-click="view_campaigns(${data.id})"` + data.id + `" >
                            ${data.name} 
                        </a> - <span class="${data.status[0].name} em_label"><b>${data.status[0].name}</b></span>
                        `
                    }
                    return `<a class="" href="" uib-tooltip="View" tooltip-placement="bottom" ng-click="edit_campaigns(${data.id})" >
                            ${data.name} 
                        </a> - <span class="${data.status[0].name} em_label"><b>${data.status[0].name}</b></span>
                        `
                } else {
                    return 'No Title'
                }

            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Schedule Date').renderWith(function (data) {

                let createdDate = ''
                if (data.schedule_datetime != null && data.schedule_datetime != '' && data.schedule_datetime != undefined) {
                    let dateChange = new Date(data.schedule_datetime)
                    createdDate = moment(dateChange).format('MMM Do YYYY, h:mm:ss a')
                    return `${createdDate}`
                } else {
                    return '-----'
                }

            }).notSortable(),

            DTColumnBuilder.newColumn(null).withTitle('DATE').renderWith(function (data) {
                let createdDate = ''
                if (data.sent_at != null && data.sent_at != '' && data.sent_at != undefined) {
                    let dateChange = new Date(data.sent_at)
                    createdDate = moment(dateChange).format('MMM Do YYYY, h:mm:ss a')
                    return `${createdDate}`
                } else {
                    return '-----'
                }

            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Clicks').renderWith(function (data) {
                if (data.clicks_count) {
                    return `${data.clicks_count}`
                } else {
                    return '-----'
                }
                //console.log(data);

            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Opened').renderWith(function (data) {
                if (data.opened_count) {
                    return `${data.opened_count}`
                } else {
                    return '-----'
                }

            }).notSortable(),

            DTColumnBuilder.newColumn(null).withTitle('Options').withOption('sWidth', '150px').renderWith(function (data) {
                if (data.status[0]) {
                    var id = data.status[0].id
                    return `
                <a class="btn btn-xs btn-warning color-orange"  uib-tooltip="Stats" tooltip-placement="bottom"  ng-click="graph_view_campaigns(${data.bounced_count},${data.clicks_count},${data.spammed_count},${data.opened_count},${data.sent_count})"` + data + `"" data-toggle="modal" data-target="#add-note">
                <i class="fa fa-bar-chart"></i> </a> 
            <a class="btn btn-xs btn-primary" uib-tooltip="View" ng-if=" ${data.status[0].id} == 4 || ${data.status[0].id} == 3" ng-click="view_campaigns(${data.id})"` + data.id + `">
            <i class="fa fa-eye"></i>    </a> 
                        <a class="btn btn-xs btn-warning"  uib-tooltip="Edit" tooltip-placement="bottom"  ng-click="edit_campaigns(${data.id})"` + data.id + `" ng-if=" ${data.status[0].id} != 4 && ${data.status[0].id} != 3">
                            <i class="fa fa-edit"></i>
                        </a> 
                        <a class="btn btn-xs btn-warning color-blue"  uib-tooltip="Clone" tooltip-placement="bottom"  ng-click="clone_campaigns(${data.id})"` + data.id + `" >
                            <i class="fa fa-clone"></i>
                        </a> 
                        <button class="btn btn-xs btn-danger"  uib-tooltip="Delete" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
                        <i class="fa fa-trash-o"></i>
                    </button>               
                       `
                } else {
                    return `<a class="btn btn-xs btn-warning"  uib-tooltip="Edit" tooltip-placement="bottom"  ng-click="edit_campaigns(${data.id})"` + data.id + `" >
                            <i class="fa fa-edit"></i>
                        </a>
                        <button class="btn btn-xs btn-danger"  uib-tooltip="Delete" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
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


        $scope.add_new = function () { $state.go('app.new-campaigns') }

        $scope.edit_campaigns = function (id) { $state.go('app.edit-campaigns', { campaign_id: id }) }

        $scope.view_campaigns = function (id) { $state.go('app.campaigns-view', { campaign_id: id }) }

        /**********************************Graph to show Count *******************************************/

        $scope.graph_view_campaigns = function (bounced_count, clicks_count, spammed_count, opened_count, sent_count) {
            var Obj = {
                bounced: bounced_count,
                clicks: clicks_count,
                spams: spammed_count,
                opened: opened_count,
                sent: sent_count
            }
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/campaigns/campaigns-stats-modal.html',
                controller: CampaignStatModalController,
                resolve: {
                    Data: function () {
                        return Obj;
                    },
                }
            });
            return modalInstance
        }
        /********************************** clone campaigns Swal *******************************************/
        $scope.clone_campaigns = function (id, name) {

            swal({
                title: 'Are you sure?',
                text: 'You want to clone this Email Broadcast',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, clone it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                var update_template_title = API.service('clone-campaign', API.all('email-marketing'));
                update_template_title.post({
                    campaign_id: id
                })
                    .then((response) => {

                        swal({
                            title: 'Cloned!',
                            text: 'Email Broadcast has been Cloned',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            state_s.reload()

                        })
                    });
                var $state = this.$state

            })


        }

        /********************************* Delete Campaigns **************************************/

        this.delete = function (contactId) {
            let API = this.API
            var $state = this.$state
            var state_s = this.$state
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
                API.one('email-marketing').one('campaign', contactId).remove()
                    .then(() => {
                        var $state = this.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Email Broadcast has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            state_s.reload()
                        })
                    })
            })
        }

    }


    $onInit() { }
}

class MarketingAddNewModalController {
    constructor($stateParams, $scope, $state, $location, API, $uibModal, $uibModalInstance, $timeout, $window) {
        'ngInject'

        $scope.Obj = {}
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        /********************************* Submit Modal Window For Creating Email Listings **************************************/

        $scope.submit_action_form = function () {
            var list_name = $scope.list_name;
            var list_description = $scope.Obj.list_description;
            var addComment = API.service('add-new-list', API.all('email-marketing'));
            addComment.post({
                name: list_name,
                description: list_description
            })
                .then((response) => {
                    // $scope.submit_success();
                    $uibModalInstance.close();

                });
            $state.go($state.current, {}, { reload: true })
        }

    }
}

class NewActionFunnelModalController {
    constructor($stateParams, $scope, $http, $state, $location, API, $uibModal, $uibModalInstance, $timeout, $window) {
        'ngInject'

        $scope.newfunnel = true
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.isDisabled = true;

        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/email-marketing/find-email-list?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }
        /********************************* Submit New Action Funnel Detail **************************************/

        $scope.submit_action_form = function () {
            var list_name = $scope.add_funnel_name;
            var tags = $scope.tags;
            $scope.isDisabled = true;
            var addComment = API.service('create', API.all('funnel'));
            addComment.post({
                name: list_name,
                list: tags
            })
                .then((response) => {
                    var res = response.plain().data.funnel_id
                    $uibModalInstance.close();
                    $state.go('app.action-funnel-details', {
                        funnelId: res
                    })

                });
            $state.go($state.current, {}, { reload: true })
        }

    }
}


class CampaignStatModalController {
    constructor($stateParams, $scope, Data, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.stats_data = Data
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }
}

export const EmailCampaignsComponent = {
    templateUrl: './views/app/pages/email-marketing/campaigns/email-campaigns.html',
    controller: EmailCampaignsController,
    controllerAs: 'vm',
    bindings: {}
}
