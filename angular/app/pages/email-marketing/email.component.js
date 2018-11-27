class EmailMarketingController {
    constructor($scope, $stateParams, $state, $compile, $uibModal, DTOptionsBuilder, DTColumnBuilder, API, $window, $timeout, AclService) {
        'ngInject'
        this.API = API
        this.$state = $state
        $scope.send_grid_check = false
        $scope.IsVisible = false
        window.localStorage.removeItem("campaignInfo")
        window.localStorage.removeItem("editcampaignInfo")
        $window.localStorage.removeItem('newsletter.autosave.json')
        $window.localStorage.removeItem('newsletter.autosave.html')
        window.localStorage.removeItem("list_info")
        $scope.selectedItem = {}
        $scope.switch_smtpapisettings = function () {
            $state.go('app.companysmtpsettings', { reload: true })
        }
        /********************************* Getting Email Listings **************************************/


        var funnel_list = API.service('api-key-status', API.all('email-marketing'))
        funnel_list.one('').get()
            .then((response) => {

            }, function (response) {
                //$state.go($state.current, { alerts: alert })
                var res = response

                $scope.send_grid_check = true

            })


        var token = $window.localStorage.satellizer_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/email-marketing/email-list',
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
            DTColumnBuilder.newColumn(null).withTitle('').renderWith(function (data) {

                return ` <input type="checkbox"  ng-model="selectedItem[${data.id}]"/>`
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('List Name').renderWith(function (data) {

                return `<a class=""  href="#/email-marketing/contacts/list/` + data.id + `">
                                ${data.name}
                            </a>`
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('TODAY').renderWith(function (data) {
                return data.today_count;

            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('YESTERDAY').renderWith(function (data) {
                return data.yesterday_count;
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('SUBSCRIBED').renderWith(function (data) {
                return data.sub_count;
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('UNSUBSCRIBED').renderWith(function (data) {
                return data.unsub_count;
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('SENT').renderWith(function (data) {
                return parseInt(data.sent_count) + parseInt(data.funnel_count);
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Options').withOption('sWidth', '150px').renderWith(function (data) {
                return `
                                 
                            <a class="btn btn-xs btn-warning"  uib-tooltip="Edit" tooltip-placement="bottom"  href="#/email-marketing/contacts/list/` + data.id + `">
                                <i class="fa fa-edit"></i>
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

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }
        $scope.checkedList = function () {
            var listInfo = []

            if (Object.keys($scope.selectedItem).length > 0) {
                angular.forEach($scope.selectedItem, function (value, key) {
                    if (value == true) {
                        listInfo.push(key);
                        $state.go('app.new-campaigns', { multipleListInfo: listInfo })
                    }
                });
            } else {
                $state.go('app.new-campaigns')
            }

        }
        /********************************* Modal Window For Creating Email Listings **************************************/

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

        $scope.clickHere = function () {
            var landingUrl = "/#/company-settings";
            $window.location.href = landingUrl;
            $window.location.reload(true);

        }

    }

    $onInit() { }
}
class NewActionFunnelModalController {
    constructor($stateParams, $scope, $http, $state, $location, API, $uibModal, $uibModalInstance, $timeout, $window) {
        'ngInject'

        $scope.newfunnel = true
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }


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
            $scope.isDisabled = true;
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

export const EmailMarketingComponent = {
    templateUrl: './views/app/pages/email-marketing/email.component.html',
    controller: EmailMarketingController,
    controllerAs: 'vm',
    bindings: {}
}
