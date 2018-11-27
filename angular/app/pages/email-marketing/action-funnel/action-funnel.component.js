class ActionFunnelController {
    constructor($scope, API, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $uibModal, $location) {
        'ngInject'

        $scope.IsVisible = false;
        window.localStorage.removeItem("campaignInfo")

        var vm = this
        vm.funnelName = {}
        vm.newbox = {}
        $scope.funnelDataList = {}
        /********************************* Geting Action Funnels  **************************************/

        var token = $window.localStorage.satellizer_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/funnel/list',
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
            //.withColReorderOrder([2, 1, 2])
            .withColReorderOption('iFixedColumnsRight', 1)
            .withColReorderCallback(function () {

            })
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            })
            .withOption('responsive', true)
            .withBootstrap()

        this.dtColumns = [
            DTColumnBuilder.newColumn(null).withTitle('FUNNEL NAME').renderWith(function (data) {
                return ` <a class="" href=""  uib-tooltip="View" tooltip-placement="bottom" ng-click="edit_status(${data.id})" >${data.name} </a>`
                // return data.first_name + " " +data.last_name;
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('STEPS').renderWith(function (data) {


                return `<a class="" uib-tooltip="" tooltip-placement="bottom" >
                                ${data.stepcount_count}
                            </a>`;
            }).notSortable(),


            DTColumnBuilder.newColumn(null).withTitle('Live/Pause').withOption('sWidth', '50px').renderWith(function (data) {

                $scope.funnelDataList[data.id] = data;


                return `  <a class="button_sm live-pause" href="" ng-class="{'task_completed': ${data.status} != 1}" uib-tooltip="{{ ${data.status} == 1 ? 'Click To Make Funnel Paused': 'Click To Make Funnel Live' }}" tooltip-placement="bottom"  ng-click="flipFunction(${data.status},${data.id},funnelName,$index)">{{ ${data.status} == 1 ? 'Live' + '        '  : 'Paused' }}</a>`;
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('ACTIONS').withOption('sWidth', '100px').renderWith(function (data) {

                return `
                                 
                            <a class="btn btn-xs btn-primary"  uib-tooltip="View" tooltip-placement="bottom" ng-click="edit_status(${data.id})" >
                            <i class="fa fa-eye"></i>
                            </a>  
                                           
                           `
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

        $scope.edit_status = function (id) {

            var funnel_id = id
            $state.go('app.action-funnel-details', {
                funnelId: funnel_id
            })
        }
        $scope.flipFunction = function (status, id, mod, name) {
            var FunnelName = $scope.funnelDataList[id].name;
            var Updatestatus = ''
            if (status == '1') {
                Updatestatus = '0'
            } else {
                Updatestatus = '1'
            }

            var addComment = API.service('update', API.all('funnel'));
            addComment.post({
                funnel_id: id,
                name: FunnelName,
                status: Updatestatus
            })
                .then((response) => { });
            $state.go($state.current, {}, { reload: true })
        }
        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }


        /*********************************Modal Window For Add New Action Funnels  **************************************/


        $scope.ShowHide = function () {
            //If DIV is visible it will be hidden and vice versa.
            $scope.IsVisible = $scope.IsVisible ? false : true;
        }

        $scope.action_funnel = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/new_action_funnel_modal.html',
                controller: NewActionFunnelModalController,
            });
            return modalInstance;
        }

        $scope.action_modal = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/campaigns/new-campaigns-modal.html',
                controller: MarketingAddNewModalController,
            });
            return modalInstance;
        }

        $scope.add_new = function () { $state.go('app.new-campaigns') }

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
            $scope.isDisabled = true;
            var list_name = $scope.add_funnel_name;
            var tags = $scope.tags;

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


export const ActionFunnelComponent = {
    templateUrl: './views/app/pages/email-marketing/action-funnel/action-funnel.component.html',
    controller: ActionFunnelController,
    controllerAs: 'vm',
    bindings: {}
}