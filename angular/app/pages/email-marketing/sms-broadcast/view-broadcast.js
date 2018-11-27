class ViewBroadcastController {
    constructor($scope, API, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location) {
        'ngInject'
        var vm = this
        vm.API = API
        vm.$state = $state
        var state_s = vm.$state
        var broadcast_id = $stateParams.broadcast_id
        $scope.campaigns_list = ''
        $scope.custom_search_data = {}
        /********************************* Geting Sms Details **************************************/
        var token = $window.localStorage.satellizer_token
        $scope.contact_details = function () {

            let status_list = API.service('campign-newsletter-lists/' + broadcast_id, API.all('sms-broadcast'))
            status_list.one("").get()
                .then((response) => {

                    $scope.contact_details = response.plain().data
                })
        }
        $scope.contact_details()
        let broadcastStatus = API.service('campign-stat/' + broadcast_id, API.all('sms-broadcast'))
        broadcastStatus.one("").get()
            .then((response) => {
                $scope.broadcastStatus = response.plain().data


            })
        $scope.campaigns_Details = function (campaigns_list) {
            $scope.tableId = "contact_table"
            $scope.custom_search_data.id = campaigns_list;
            vm.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/sms-broadcast/campign-show/' + broadcast_id,
                    type: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        data.customFilter = $scope.custom_search_data;
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
                DTColumnBuilder.newColumn(null).withTitle('Name').renderWith(function (data) {
                    if (data.first_name != null && (data.last_name == null || data.last_name == 'null')) {
                        return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.id + `">
                    ${data.first_name}</a>`
                    }
                    else if (data.first_name == null && data.last_name == null) {
                        return ``;
                    }
                    else {
                        return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.id + `">
                    ${data.first_name + " " + data.last_name}</a>`
                    }

                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Phone').renderWith(function (data) {
                    if (data.phone == null) {
                        return ``;
                    }
                    var Country_code = '';
                    let phnumber = data.phone
                    if (phnumber != undefined && phnumber != null) {
                        if (data.phone_country_code != undefined && data.phone_country_code != null) {
                            Country_code = data.phone_country_code
                        }
                        if (data.phone_country_code != undefined && Country_code != '' && data.phone_country_code != null) {
                            phnumber = phnumber.replace(Country_code, '')
                        }
                        var numbers = phnumber.replace(/\D/g, ''),
                            char = { 0: '(', 3: ') ', 6: ' - ' };
                        phnumber = '';
                        for (var i = 0; i < numbers.length; i++) {
                            phnumber += (char[i] || '') + numbers[i];
                        }

                    } else {
                        phnumber = '';
                    }

                    return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.phone}">
                                ${Country_code} ${phnumber}
                            </a>`;
                    //return data.phone_country_code;
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
                DTColumnBuilder.newColumn(null).withTitle('Status').renderWith(function (data) {
                    if (data.status == 1) {
                        return `Pending`
                    }
                    else if (data.status == 2) {
                        return `Sent`
                    }
                    else if (data.status == 3) {
                        return `Failed`
                    }
                    else if (data.status == 4) {
                        return `In-Progress`
                    }

                }).notSortable()

            ]
            $('#' + $scope.tableId).DataTable().ajax.reload();


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

        $scope.campaigns_Details()
    }


    $onInit() { }
}

export const ViewBroadcastComponent = {
    templateUrl: './views/app/pages/email-marketing/sms-broadcast/view-broadcast.html',
    controller: ViewBroadcastController,
    controllerAs: 'vm',
    bindings: {}
}