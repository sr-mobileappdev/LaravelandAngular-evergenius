class SmsListController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, AclService) {
        'ngInject'
        var vm =this
        this.API = API
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.API = API
        this.alerts = []
        this.window = $window;
        this.start_date = moment().subtract(10, 'days'), moment()
        this.end_date = moment()
        this.min_date = moment().subtract(180, 'days'), moment()
        this.max_date = moment()

        this.can = AclService.can
        if (!this.can('sms.records')) {
            $state.go('app.unauthorizedAccess');
        }
        $scope.datePickerOptions = {
            linkedCalendars: false,
            startDate: moment().subtract(30, 'days'),
            endDate: moment(),
            alwaysShowCalendars: true,
            applyClass: 'btn-green',
            locale: {
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
                    $scope.list_i = 5;
                    $scope.activity_list = [];
                    $scope.list_id = [];
                    $scope.load_data_table();
                }
            }
        }

        $scope.barChartColours = [
            {
                fillColor: '#61e9ff',
                strokeColor: '#61e9ff',
                pointColor: '#2980b9',
                pointStrokeColor: '#fff',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(77,83,96,1)'
            }, {
                fillColor: '#f99265',
                strokeColor: '#f99265',
                pointColor: 'rgba(148,159,177,1)',
                pointStrokeColor: '#fff',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(148,159,177,0.8)'
            }

        ]

        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };

        $scope.load_data_table = function () {
            var start_date_sms = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
            var end_date_sms = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
            $scope.tableId = "sms_list";

            var sms_widget = API.service('sms-widgets', API.all('sms'))
            sms_widget.one('').get({ start_date: start_date_sms, end_date: end_date_sms })
                .then((response) => {
                    var sms_chart_dates = [];
                    var sms_chart_inbound = [];
                    var sms_chart_outbound = [];
                    var sms_data = response.plain();
                    $scope.total_sms = sms_data.data.sms_summary.total;
                    $scope.total_sms_sent = sms_data.data.sms_summary.outbond;
                    $scope.total_sms_received = sms_data.data.sms_summary.inbound;

                    angular.forEach(sms_data.data.sms_statics, function (value, key) {
                        sms_chart_dates.push(value.date);
                        sms_chart_inbound.push(value.inbound);
                        sms_chart_outbound.push(value.outbond);
                    });
                    $scope.smsChartLabels = sms_chart_dates
                    $scope.smsChartSeries = ['Received', 'Sent']
                    $scope.smsChartData = [
                        sms_chart_inbound,
                        sms_chart_outbound
                    ]
                });

            var token = $window.localStorage.satellizer_token
            $scope.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/sms',
                    type: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        var start_date = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
                        var end_date = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
                        data.customFilter = { start_time: start_date, end_time: end_date };
                        return JSON.stringify(data);
                    }
                })
                .withDataProp('data')
                .withOption('serverSide', true)
                .withOption('processing', true)
                .withOption('createdRow', createdRow)
                .withColReorder()
                //.withColReorderOrder([2, 1, 2])
                .withColReorderOption('iFixedColumnsRight', 1)
                .withOption('stateSave', true)
                .withOption('stateSaveCallback', function (settings, data) {
                    localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));
                })
                .withOption('stateLoadCallback', function (settings, data) {
                    return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
                })
                .withColReorderCallback(function () {
                })
                .withOption('createdRow', function (row) {
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('responsive', true)
                .withOption('aaSorting', [[2, 'desc']])
                .withBootstrap()
            $scope.dtColumns = [

                DTColumnBuilder.newColumn('receiver_name').withTitle('Patient Name').withOption('width', '20%'),
                DTColumnBuilder.newColumn('sms_to').withTitle('Patient Phone No.').renderWith(function (data) {
                    let phnumber = data
                    let Country_code = '+1'
                    if (Country_code != '') {
                        phnumber = phnumber.replace(Country_code, '')
                    }
                    var numbers = phnumber.replace(/\D/g, ''),
                        char = { 0: '(', 3: ') ', 6: ' - ' };
                    phnumber = '';
                    for (var i = 0; i < numbers.length; i++) {
                        phnumber += (char[i] || '') + numbers[i];
                    }
                    return phnumber;

                }).withOption('width', '15%'),

                DTColumnBuilder.newColumn('sent_time').withTitle('Date').renderWith(function (data) {
                    return moment(data).format('ddd, MMM DD YYYY, hh:mm a');
                }).withOption('width', '15%'),
                DTColumnBuilder.newColumn('sms_body').withTitle('Message').withOption('width', '40%'),
                DTColumnBuilder.newColumn('direction').withTitle('Direction').renderWith(function (data) {
                    if (data == 'inbound') {
                        return 'Recieved'
                    }
                    else {
                        return 'Sent'
                    }
                }).withOption('width', '20%'),
                DTColumnBuilder.newColumn('contact_id').withTitle('View Contact').renderWith(function (data) {
                    if (data != null && data != 'null' && data != '') {
                        return `<a class="btn btn-xs btn-primary" title="View" uib-tooltip="View" tooltip-placement="bottom" ui-sref="app.viewcontact({contactId:` + data + `})" href="#/contact/` + data + `"><i class="fa fa-eye"></i></a>`;

                    } else {
                        return ``
                    }
                })


            ]
            $scope.displayTable = true
            $('#' + $scope.tableId).DataTable().ajax.reload();


            let createdRow = (row) => {
                $compile(angular.element(row).contents())($scope)
            }
        }

        $scope.load_data_table();

        $scope.dtInstance = function (dtInstance) {
            vm.dtInstance = dtInstance;
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
        let API = this.API
        var $state = this.$state
        var state_s = this.$state
        swal({
            title: 'Are you sure?',
            text: 'You will not be able to recover this data!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function () {
            API.one('contacts').one('contact', contactId).remove()
                .then(() => {
                    var $state = this.$state
                    swal({
                        title: 'Deleted!',
                        text: 'User Permission has been deleted.',
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
        let API = this.API
        let $state = this.$state
        var sele = angular.element($('.selected_val')).serializeArray();
        swal({
            title: 'Are you sure?',
            text: 'You will not be able to recover this data!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function () {
            let conts = API.service('del-contacts', API.all('contacts'))

            conts.post(
                { 'selected_del': sele }
            )
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
export const SmsListComponent = {
    templateUrl: './views/app/pages/sms-record-listing/sms-lists.component.html',
    controller: SmsListController,
    controllerAs: 'vm',
    bindings: {}
}
