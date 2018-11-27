class SendSmsController {
    constructor($scope, API, $auth, $sce, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location) {
        'ngInject'

        this.API = API
        this.$state = $state
        var state_s = this.$state
        $scope.sent_count = 0;
        $scope.clicked = 0;
        $scope.opened = 0;
        if ($stateParams.type != null) {
            var data_type = $stateParams.type
            var status = $stateParams.status

            $scope.icon = data_type.action_type

            $window.localStorage.setItem("data_type", JSON.stringify(data_type))
            $window.localStorage.setItem("status", JSON.stringify(status))
            $scope.step_name = data_type.name
        } else {
            var data_type = JSON.parse($window.localStorage.getItem('data_type'))

            var status = parseInt(JSON.parse($window.localStorage.getItem('status')))
            $scope.icon = data_type.action_type
            $scope.step_name = data_type.name
        }

        $scope.send_type = 'Sms sent'
        var get_count = API.service('step-sent-count?funnel_id=' + data_type.funnel_id + "&action_id=" + data_type.id + "&type=" + data_type.action_type, API.all('funnel'))
        get_count.one('').get()
            .then((response) => {
                $scope.sent_count = response.sent;
                $scope.clicked = response.clicked;
                $scope.opened = response.opened;
            })
        /********************************* Geting Email/Sms Details **************************************/

        var token = $window.localStorage.satellizer_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/funnel/recipients',
                type: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization",
                        "Bearer " + token);
                },
                data: function (data, dtInstance) {

                    data.customFilter = { funnel_id: data_type.funnel_id, action_id: data_type.id, type: data_type.action_type, status: status };
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
            DTColumnBuilder.newColumn(null).withTitle('Recipient').renderWith(function (data) {

                $scope.step_name = data.action.name
                if (data.contact_list.first_name != null && (data.contact_list.last_name == null || data.contact_list.last_name == 'null')) {
                    data.last_name = '';
                    return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.contact_list.id + `">
                    ${data.contact_list.first_name + " " + data.contact_list.last_name}</a>`
                }

                else if (data.contact_list.first_name == null || data.contact_list.last_name == 'null') {
                    return ``;
                }
                else if (data.contact_list.deleted_at != null) {
                    return `${data.contact_list.first_name + " " + data.contact_list.last_name}`
                }
                else {
                    return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.contact_list.id + `">
                    ${data.contact_list.first_name + " " + data.contact_list.last_name}</a>`
                }
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Email').renderWith(function (data) {

                return `<a class="" uib-tooltip="Email" tooltip-placement="bottom"  >
                            ${data.contact_list.email} 
                        </a> `

            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('DATE').renderWith(function (data) {
                let dateChange = new Date(data.created_at)
                let createdDate = moment(dateChange).format('MMM Do YYYY, h:mm:ss a')
                return `
            ${createdDate}`
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('STATUS').renderWith(function (data) {
                var status = $scope.get_status(data.status)
                return `
    ${status}`
            }).notSortable(),


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

        $scope.get_status = function (status) {
            if (status == '2') {
                var statuss = 'Sent'
                return statuss
            }
            else if (status == '3') {
                var statuss = 'Rejected'
                return statuss
            }
            else {
                var statuss = 'Unsent'
                return statuss
            }
        }
    }


    $onInit() { }
}

export const SendSmsComponent = {
    templateUrl: './views/app/pages/email-marketing/action-funnel/send-sms-component.html',
    controller: SendSmsController,
    controllerAs: 'vm',
    bindings: {}
}