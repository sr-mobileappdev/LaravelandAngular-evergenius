class CallsListController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, AclService, $timeout) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        $scope.notes = {};
        $scope.edit_notes = {};
        $scope.show_notes = {};
        $scope.note_text = {};
        $scope.submitted = {};
        this.API = API
        this.alerts = []
        this.window = $window;
        this.start_date = moment().subtract(30, 'days'), moment()
        this.end_date = moment()
        this.min_date = moment().subtract(182, 'days'), moment()
        this.max_date = moment()
        $scope.filter_leads = 3;
        this.can = AclService.can
        if (!this.can('call.records')) {
            $state.go('app.unauthorizedAccess');
        }

        $scope.datePickerOptions = {
            applyClass: 'btn-green',
            linkedCalendars: false,
            startDate: moment().subtract(30, 'days'),
            endDate: moment(),
            alwaysShowCalendars: true,
            locale: {
                applyLabel: "Apply",
                fromLabel: "From",
                format: "MMMM DD, YYYY", //will give you 2017-01-06
                toLabel: "To",

                showDropdowns: true,
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

        $scope.load_data_table = function () {
            var start_date_calls = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
            var end_date_calls = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
            $scope.tableId = "call_list";
            var token = $window.localStorage.satellizer_token
            $scope.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/calls',
                    type: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {

                        var start_date = moment($scope.datePicker.startDate).format('YYYY-MM-DD');
                        var end_date = moment($scope.datePicker.endDate).format('YYYY-MM-DD');
                        var lead_type = $scope.filter_leads;
                        if (lead_type == 2) {
                            lead_type = null;
                        }
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
                DTColumnBuilder.newColumn('call_start_at').withTitle('Time of Call').renderWith(function (data) {
                    return moment(data).format('ddd, MMM DD YYYY, hh:mm a');
                }),
                DTColumnBuilder.newColumn('call_from').renderWith(function (data) {
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

                }).withTitle('Patient Phone Number'),

                DTColumnBuilder.newColumn('caller_name').withTitle('Patient Name'),
                DTColumnBuilder.newColumn('call_duration').withTitle('Call Duration').renderWith(function (data) {
                    let duration = data;
                    var minutes = Math.floor(duration / 60); // 7
                    var seconds = duration % 60; // 30
                    return minutes + ":" + seconds;
                }),
                DTColumnBuilder.newColumn('recording_url').withTitle('Recording').renderWith(function (data) {
                    if (data != null && data != 'null' && data != '') {
                        return '<audio controls>' +
                            '<source src="' + data + '" type="audio/wav">' +
                            '<source src="' + data + '.mp3" type="audio/mpeg">' +
                            'Your browser does not support the audio tag.' +
                            '</audio>';

                    } else {
                        return ``
                    }
                }).withOption('width', '200px'),
                DTColumnBuilder.newColumn('contact_id').withTitle('View Contact').renderWith(function (data, type, full, meta) {
                    if (data != null && data != 'null' && data != '') {
                        return `<a class="btn btn-xs btn-primary " title="View" uib-tooltip="View" tooltip-placement="bottom" ui-sref="app.viewcontact({contactId:` + data + `})" href="#/contact/` + data + `"><i class="fa fa-eye"></i></a>`;

                    } else {
                        return ``
                    }
                }),
                DTColumnBuilder.newColumn('lead_status').withTitle('Leads').withOption('width', '150px').renderWith(function (data, type, full, meta) {
                    var like = '';
                    var dislike = '';
                    if (full.lead_status == 0) {
                        dislike = 'active';
                    }
                    if (full.lead_status == 1) {
                        like = 'active';
                    }
                    return '<div class="call_lead_status_btn">' +
                        '<button type="button" id="mark_' + full.id + '" class="btn btn-danger lead_status_btn leadlike margin-right-5 ' + like + '" title="Like" uib-tooltip="Mark as Lead" tooltip-placement="top" ng-click="updateLeadStatus(' + full.id + ',1)" >' +
                        '<i class="fa fa-thumbs-up" aria-hidden="true"></i> ' +
                        '</button>' +
                        '<button type="button" id="notmark_' + full.id + '"  class="btn btn-danger lead_status_btn leaddislike ' + dislike + '"  title="Dislike" uib-tooltip="Not a Lead" tooltip-placement="left" ng-click="updateLeadStatus(' + full.id + ',0)">' +
                        '<i class="fa fa-thumbs-down" aria-hidden="true"></i>' +
                        '</button>' +
                        '</div>';
                }),
                DTColumnBuilder.newColumn(null).withTitle('Notes').renderWith(function (data, type, full, meta) {
                    var note_r = '';
                    if (data.notes != null) {
                        note_r = data.notes.note;
                    }

                    $scope.notes[data.id] = data.notes;
                    $scope.show_notes[data.id] = false
                    $scope.edit_notes[data.id] = false;
                    $scope.note_text[data.id] = note_r;
                    return `<form  name="submit_comment_` + data.id + `">
                                    <div class="notes-popup" >                                
                                    <a class="btn btn-block btn-success btn-xs" ng-if="!notes[` + data.id + `]" ng-click="add_notes_block(` + data.id + `)"><i class="fa fa-file-text-o"></i><span class="badge"><i class="fa fa-plus"></i></span></a>
                                    <a class="btn btn-block btn-success btn-xs" ng-if="notes[` + data.id + `]" ng-click="show_notes_block(` + data.id + `)" ><i class="fa fa-file-text-o"></i></a>
                                    <div class="notes-popup-box" ng-if="edit_notes[` + data.id + `]"  >
                                        <a ng-click="add_notes_block(` + data.id + `)" class="note-close"></a>
                                        <div class="notes-heading">Notes</div>
                                        <div class="alert alert-success" ng-if="submitted[` + data.id + `]" >Note successfully updated.</div>
                                        <textarea placeholder="Add notes here..." class="form-control" ng-model="note_text[` + data.id + `]" required="required"></textarea>
                                        <div class="note-btn-container">
                                            <button class="btn-save" ng-disabled="submit_comment_` + data.id + `.$invalid" ng-click=update_call_note(` + data.id + `)>Save</button>
                                        </div>
                                    </div>
                                     <div class="notes-popup-box" ng-if="show_notes[` + data.id + `]">
                                            <a ng-click="add_notes_block(` + data.id + `)" class="fa fa-pencil note-edit"></a> <a  ng-click="show_notes_block(` + data.id + `)" class="note-close"></a>
                                            <div class="notes-heading">Notes</div> 
                                            <p>` + note_r + `
                                            </p>
                                        </div>
                                </div>`;
                })
            ],


                $scope.updateLeadStatus = function (id, status) {
                    var markasLead = API.service('update-lead-status', API.all('calls'));
                    markasLead.post({ 'id': id, 'lead_status': status })
                        .then(() => {

                            angular.element("#notmark_" + id).removeClass("active");
                            angular.element("#mark_" + id).removeClass("active");

                            if (status === 0) {
                                angular.element("#notmark_" + id).addClass("active");
                                // document.getElementById("mark").style.color = "default"   
                            }
                            if (status === 1) {
                                angular.element("#mark_" + id).addClass("active");
                            }
                        })
                }

            $scope.show_notes_block = function (call_id) {

                angular.forEach($scope.show_notes, function (val, key) {
                    if (key != call_id) {
                        $scope.show_notes[key] = false;
                        $scope.edit_notes[key] = false;
                    }
                });

                if ($scope.show_notes[call_id] == true) {
                    $scope.show_notes[call_id] = false
                } else {
                    $scope.show_notes[call_id] = true;
                }
                $scope.edit_notes[call_id] = false;
            }

            $scope.add_notes_block = function (call_id) {
                angular.forEach($scope.show_notes, function (val, key) {
                    if (key != call_id) {
                        $scope.show_notes[key] = false;
                        $scope.edit_notes[key] = false;
                    }
                });
                if ($scope.edit_notes[call_id] == true) {
                    $scope.edit_notes[call_id] = false
                } else {
                    $scope.edit_notes[call_id] = true;
                }
                $scope.show_notes[call_id] = false;
            }

            $scope.update_call_note = function (call_id) {
                /* console.log("dd", valid);
                 if(valid){*/


                var note = $scope.note_text[call_id];

                let contact = API.service('update-note', API.all('calls'))
                contact.post({
                    'call_id': call_id,
                    'note': note
                }).then(function (response) {
                    $scope.submitted[call_id] = true;
                    $timeout(function () {
                        $scope.submitted[call_id] = false;
                        $scope.load_data_table();
                    }, 3000);

                }, function (response) {
                    $scope.load_data_table();
                })

                //}
            }



            $scope.displayTable = true
            $('#' + $scope.tableId).DataTable().ajax.reload();

            /*angular.element('.call_list_filter').addClass("alpha");*/

            /* Dashboard Calls */
            var dashboardAnalytics = API.service('call-widgets', API.all('dashboard'))
            dashboardAnalytics.one('').get({ start_date: start_date_calls, end_date: end_date_calls })
                .then((response) => {
                    var calls_data = response.plain();
                    var call_summary = calls_data.data.calls_summary;
                    var calls_statics = calls_data.data.calls_statics;
                    var calls_total = []
                    var answered_calls = []
                    var unanswered_calls = []
                    angular.forEach(calls_data.data.calls_summary, function (value, key) {
                        calls_total.push(value.total);
                        if (value.call_status == 'completed') {
                            answered_calls.push(value.total);
                        } else {
                            unanswered_calls.push(value.total);
                        }
                    });

                    $scope.call_widget_total_calls = $scope.sum(calls_total);
                    $scope.call_widget_answered_calls = $scope.sum(answered_calls);
                    $scope.call_widget_unanswered_calls = $scope.sum(unanswered_calls);
                    $scope.callOptionsdonut = { showTooltips: false, percentageInnerCutout: 75, segmentShowStroke: false, animation: false }
                    if ($scope.sum(answered_calls) != 0 || $scope.sum(unanswered_calls) != 0) {
                        $scope.call_widget_pie_labels = ['Answered Calls', 'Unanswered Calls'];
                        $scope.CallsDonutChartcolors = ['#7eda8a', '#f99265'];
                        $scope.call_widget_pie_value = [$scope.sum(answered_calls), $scope.sum(unanswered_calls)];

                    } else {
                        $scope.CallsDonutChartcolors = ['#9E9E9E', '#9E9E9E'];
                        $scope.call_widget_pie_value = [1];
                        $scope.call_widget_pie_labels = ['Null'];
                    }

                    var call_graph_labels = [];
                    var call_graph_values = [];

                    angular.forEach(calls_statics, function (value, key) {
                        call_graph_labels.push(value.date);
                        call_graph_values.push(value.calls);
                    });
                    $scope.call_pie_labels = call_graph_labels;
                    // $scope.call_pie_values=call_graph_values;
                    $scope.call_pie_values = [
                        call_graph_values
                    ]
                });


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

            conts.post({ 'selected_del': sele })
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
export const CallsListComponent = {
    templateUrl: './views/app/pages/call-record-listing/calls-lists.component.html',
    controller: CallsListController,
    controllerAs: 'vm',
    bindings: {}
}
