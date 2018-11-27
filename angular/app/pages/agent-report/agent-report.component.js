class AgentReportController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.API = API
        this.alerts = []
        this.window = $window;
        this.start_date = moment().subtract(30, 'days')
        this.end_date = moment()
        this.min_date = moment().subtract(182, 'days'), moment()
        this.max_date = moment()
        $scope.filter_leads = 3;
        $scope.datePickerOptions = {
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

        $scope.CallsChartOptions = {
            scaleShowVerticalLines: false,
            scaleShowHorizontallLines: false,
            //responsive:true,

            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    stacked: true,
                    gridLines: {
                        display: true,
                        color: "rgba(255,99,132,0.2)"
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false
                    }
                }]
            },
            tooltipTemplate: "<%= value %>",
            gridLines: {
                show: false
            }
        };


        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };
        $scope.analyticsChartColours = [{
            fillColor: '#fcc5ae',
            strokeColor: '#D2D6DE',
            pointColor: '#000000',
            pointStrokeColor: '#fff',
            pointHighlightFill: '#fff',
            pointHighlightStroke: 'rgba(148,159,177,0.8)'
        }]
        function pad(num) {
            return ("0" + num).slice(-2);
        }
        $scope.load_data_table = function () {
            var start_date_calls = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
            var end_date_calls = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
            $scope.tableId = "call_list";
            var token = $window.localStorage.satellizer_token
            $scope.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/agentreports',
                    type: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        var start_date = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
                        var end_date = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
                        var lead_type = $scope.filter_leads;
                        data.customFilter = { lead_status: lead_type, start_time: start_date, end_time: end_date };
                        return JSON.stringify(data);

                    }
                })
                //.withDOM('<"dt-toolbar">frtip')
                .withLanguage({
                    processing: function () {
                        //xhrcfpLoadingBarProvider.includeSpinner = true;
                    }
                })
                .withDataProp('data')
                .withOption('serverSide', true)
                .withOption('processing', true)
                .withOption('createdRow', createdRow)
                .withColReorder()
                //.withColReorderOrder([2, 1, 2])
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
                .withOption('stateSave', true)
                .withOption('stateSaveCallback', function (settings, data) {
                    localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));
                })
                .withOption('stateLoadCallback', function (settings, data) {
                    return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
                })
                .withBootstrap()

            $scope.dtColumns = [
                DTColumnBuilder.newColumn('agent_name').withTitle('Agent Name').withClass('captilize'),
                DTColumnBuilder.newColumn('total_opportunities').withTitle('Total Opportunities').renderWith(function (data) {
                    if (data == null || data == '') { return '0'; }
                    else { return data; }
                }),
                DTColumnBuilder.newColumn('prospects').withTitle('Prospects').renderWith(function (data) {
                    if (data == null || data == '') { return '0'; }
                    else { return data; }
                }).withOption('width', '200px'),
                DTColumnBuilder.newColumn('appointments').withTitle('Appointments').withOption('width', '100px').renderWith(function (data, type, full, meta) {
                    if (data == null || data == '') { return '0'; }
                    else { return data; }
                }),
                DTColumnBuilder.newColumn('consults').withTitle('Consults').withOption('width', '100px').renderWith(function (data, type, full, meta) {
                    if (data == null || data == '') { return '0'; }
                    else { return data; }
                }),
                DTColumnBuilder.newColumn('lost').withTitle('Lost').withOption('width', '100px').renderWith(function (data, type, full, meta) {
                    if (data == null || data == '') { return '0'; }
                    else { return data; }
                }),
                DTColumnBuilder.newColumn('close').withTitle('Close/Won').withOption('width', '100px').renderWith(function (data, type, full, meta) {
                    if (data == null || data == '') { return '0'; }
                    else { return data; }
                }),
                DTColumnBuilder.newColumn('closed_revenue').withTitle('Closed Revenue').withOption('width', '100px').renderWith(function (data, type, full, meta) {
                    if (data == null) {
                        return '$0'
                    }
                    else {
                        var rev = data.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
                        return '$' + rev;
                    }

                }),
                DTColumnBuilder.newColumn('open_task').withTitle('Open Task').withOption('width', '100px').renderWith(function (data, type, full, meta) {
                    if (data == null || data == '') { return '0'; }
                    else { return data; }
                }),
                DTColumnBuilder.newColumn('closed_task').withTitle('Closed Task').withOption('width', '100px').renderWith(function (data, type, full, meta) {
                    if (data == null || data == '') { return '0'; }
                    else { return data; }
                }),
                DTColumnBuilder.newColumn('avg_lead_timee').withTitle('Avg. Handling Time').withOption('width', '100px').renderWith(function (data, type, full, meta) {
                    if (data == null || data == '') { return '00:00:00'; }
                    else {
                        d = Number(data);
                        var h = Math.floor(d / 3600);
                        var m = Math.floor(d % 3600 / 60);
                        var s = Math.floor(d % 3600 % 60);
                        return pad(h) + ":" + pad(m) + ":" + pad(s);
                    }
                })

            ],



                $scope.displayTable = true
            $('#' + $scope.tableId).DataTable().ajax.reload();

            /*angular.element('.call_list_filter').addClass("alpha");*/

        }

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }
        $scope.load_data_table();

        $scope.sum = function (input) {
            if (toString.call(input) !== "[object Array]")
                return false;

            var total = 0;
            for (var i = 0; i < input.length; i++) {
                if (isNaN(input[i])) {
                    continue;
                }
                total += Number(input[i]);
            }
            return total;
        }
        $scope.assignes = {};
        $scope.$watchCollection('filter_leads', function (new_val, old_val) {
            $scope.filter_leads = new_val;
            $scope.load_data_table();
        });

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
export const AgentReportComponent = {
    templateUrl: './views/app/pages/agent-report/agent-report.component.html',
    controller: AgentReportController,
    controllerAs: 'vm',
    bindings: {}
}