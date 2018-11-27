class ViewCampaignsController {
    constructor($scope, API, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location) {
        'ngInject'
        var vm = this
        vm.API = API
        vm.$state = $state
        var state_s = vm.$state
        var campaign_id = $stateParams.campaign_id

        /********************************* Geting Campaign Details **************************************/

        var token = $window.localStorage.satellizer_token
        $scope.contact_details = function () {

            let status_list = API.service('campaign-details/' + campaign_id, API.all('email-marketing'))
            status_list.one("").get()
                .then((response) => {

                    $scope.contact_details = response.plain().data
                    if ($scope.contact_details.campaign.elist.length > 0) {
                        $scope.campaigns_list = $scope.contact_details.campaign.elist[0].detail.id
                        $scope.campaigns_Details($scope.campaigns_list)
                    }

                })
        }
        $scope.contact_details();


        let campaign_status = API.service('campaigns-stat/' + campaign_id, API.all('email-marketing'))
        campaign_status.one("").get()
            .then((response) => {
                $scope.campaign_status = response.plain().data

            })
        $scope.campaigns_Details = function (campaigns_list) {

            vm.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/email-marketing/campaign-contact-list/' + campaigns_list + "?camp_id=" + campaign_id,
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
                .withBootstrap()

            vm.dtColumns = [
                DTColumnBuilder.newColumn(null).withTitle('Recipient').renderWith(function (data) {
                    return `<a class="">${data.email_id}</a>`
                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Opened').renderWith(function (data) {
                    if (data.open_status == 1) {
                        return `<i class="fa fa-check"></i>`
                    }
                    else {
                        return ``
                    }
                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Clicked').renderWith(function (data) {
                    if (data.click_status == 1) {
                        return `<i class="fa fa-check"></i>`
                    }
                    else {
                        return ``
                    }

                }).notSortable(),

                DTColumnBuilder.newColumn(null).withTitle('DATE').renderWith(function (data) {
                    let dateChange = new Date(data.created_at)
                    let createdDate = moment(dateChange).format('MMM Do YYYY, h:mm:ss a')
                    return `${createdDate}`
                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Status').renderWith(function (data) {
                    if (data.status == 2) {
                        return `Sent`
                    }
                    else if (data.status == 3) {
                        return `Rejected`
                    }
                    else {
                        return `Pending`
                    }

                }).notSortable()

            ]
            vm.dtInstanceCallback = function (dtInstance) {
                vm.dtInstance = dtInstance;
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

            vm.displayTable = true
        }

        //$scope.campaigns_Details()
    }


    $onInit() { }
}

export const ViewCampaignsComponent = {
    templateUrl: './views/app/pages/email-marketing/campaigns/campaigns-view.html',
    controller: ViewCampaignsController,
    controllerAs: 'vm',
    bindings: {}
}