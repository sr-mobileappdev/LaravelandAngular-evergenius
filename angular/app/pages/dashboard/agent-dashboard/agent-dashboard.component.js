class AgentDashboardController {
    constructor($scope, $stateParams, $state, API, unauthorizedService, ContextService, $window, AclService, $location, anchorSmoothScroll, $sce, $uibModal, $timeout) {
        'ngInject'

        var roles = AclService.getRoles();
        if (roles[0] == 'admin.user') {
            $state.go('app.landing');
            return false;
        }
        $scope.API = API;
        $scope.$state = $state;

        $scope.funnel_text = 'Avg. Lead Respone Time: 0 hr 0 min';



        $scope.showdiv = false;
        this.analytics = []
        var seriesId = 0;
        this.start_date = moment().subtract(30, 'days')
        $scope.mailchimp_start_date = moment().subtract(90, 'days');
        $scope.mailchimp_end_date = moment();
        this.end_date = moment()
        $scope.list_i = 5;
        this.min_date = moment().subtract(182, 'days')
        this.max_date = moment()
        $scope.activity_list = [];
        $scope.active_contact_tab = 'tasks';
        $scope.action_title = "Add Task";
        $scope.tabs = ['tasks', 'conversation', 'call_logs', 'notes'];
        $scope.tasks = [];
        $scope.total_amnt = 0;
        $scope.conv_rate = 0;
        $scope.active_contact_view = function(tab) {
            $scope.active_contact_tab = tab;
            if (tab == 'tasks') {
                $scope.action_title = "Add Task";
            } else if (tab == 'conversation') {
                $scope.action_title = "Send SMS";
            } else if (tab == 'notes') {
                $scope.action_title = "Add notes";
            }
        }
        $scope.add_comment = function() {
            var contactid = $scope.contact_id;
            var comment = $scope.comment_desc;
            if (comment != "") {
                var addComment = API.service('comment', API.all('contacts'))
                addComment.post({
                        contact_id: contactid,
                        comment: comment

                    })
                    .then((response) => {
                        let contact_comment_list = API.service('contact-comments', API.all('contacts'));
                        contact_comment_list.one(contactId).get().then((response) => {
                            if (response) {
                                let content = response.plain();
                                $scope.commentlist = content.data;
                                $scope.comment_desc = "";
                            }
                        });
                    })
            }

        }
        $scope.remove_country_code = function(num_ph) {
            var phnumber = '';
            if (num_ph != null) {
                var Country_code = '+1';
                phnumber = num_ph.replace(Country_code, '');
                return phnumber;
            }
            return '';


        }
        $scope.load_tasks = function(lead_id) {
            let tasks = API.service('my-recent-tasks', API.all('leads'))
            tasks.one()
                .get()
                .then((response) => {
                    var se = response.plain();
                    $scope.tasks = se.data.tasks;
                })
            $scope.show_lists = false;
        }
        $scope.load_tasks();

        $scope.Recent_tasks = function(lead_id) {
            let tasks = API.service('my-recent-leads', API.all('leads'))
            tasks.one()
                .get()
                .then((response) => {
                    var se = response.plain()
                    $scope.Recent_tasks = se.data.leads;
                })
            $scope.show_lists = false;
        }
        $scope.Recent_tasks();
        /**add comment**/

        /* /Recent Activites */

        $scope.gotoElement = function(eID) {
            $location.hash(eID);
            anchorSmoothScroll.scrollTo(eID);
        };

        $scope.renderHtml = function(html_code) {
            return $sce.trustAsHtml(html_code);
        };
        $scope.uCanTrust = function(string) {
            return $sce.trustAsHtml(string);
        }

        $scope.add_appointment = function() {
            var contactData = $scope.contactData;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/add-appointment-modals/add-app-modal.html',
                controller: add_app_modalController,
                resolve: {
                    contactData: function() {
                        return contactData;
                    }

                }
            });
            return modalInstance;
        }
        $scope.change_task_status = function(task_id, is_open, index) {
            if (is_open == 0) {
                is_open = 1;
            } else {
                is_open = 0;
            }
            //if(is_open!=0){
            var elem = $scope.tasks.find(x => x.id === task_id);
            var index_ele = $scope.tasks.indexOf(elem);
            let lead_update = API.service('update-task/' + task_id, API.all('leads'))
            lead_update.post({
                    is_open: is_open
                })
                .then(function(response) {
                    $scope.tasks[index_ele].open = is_open;
                })
            //}
        }


        $scope.delete_task = function(task_id) {
            let API = $scope.API
            var $state = $scope.$state
            var state_s = $scope.$state
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
            }, function() {
                API.one('leads')
                    .one('task', task_id)
                    .remove()
                    .then(() => {
                        var $state = this.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Lead has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function() {
                            state_s.reload()
                        })
                    })
            })
        }

        /*  Funnel Chart Dashboard */
        $scope.closed_amount = 0;
        $scope.funnel_series_data = [
            ['Prospects', 0, 555],
            ['Appointments', 0, 555],
            ['Consults', 0, 54242],
            ['Close', 0, 40401]
        ];

        $scope.chartSeries = [{
            name: 'Leads',
            data: $scope.funnel_series_data,
            tooltip: {
                //valueSuffix: ' mb'
            }
        }]

        var dashboardAnalytics = API.service('funnel-widget', API.all('leads'))
        dashboardAnalytics.one().get()
            .then((response) => {
                var out_statics = []
                var res = response.plain();
                var statics = res.data.leads_statics;
                var total_amount = 0;
                var total_leads = 0;
                angular.forEach(statics, function(data, key) {
                    total_amount = total_amount + data.total_ltv;
                    total_leads = parseInt(data.count_lead) + total_leads;
                    out_statics.push([data.title, data.count_lead, data.total_ltv, data.stage_id]);
                });
                $scope.total_leads = total_leads;
                $scope.loading_funnel = false;
                $scope.chartConfig.series = [];
                $scope.chartConfig.series.push({
                    data: out_statics,
                    tooltip: {
                        //valueSuffix: ' mb'
                    }
                });
                $scope.total_ltv = total_amount;
                $scope.closed_amount = res.data.close_amount;
                var sc = 'hr';
                if (res.data.avg_lead_response_time.H > 1) {
                    sc = 'hrs';
                }
                $scope.funnel_text = 'Avg. Lead Response Time: ' + res.data.avg_lead_response_time.H + ' ' + sc + ' ' + res.data.avg_lead_response_time.M + ' min';
            });

        $scope.chartConfig = {
            chart: {
                type: 'funnel',
                //renderTo: 'chart_funnel',
                marginRight: 0,
                height: 315,
                width: 400
            },
            title: {
                text: ''
            },
            colors: ['#297fb8', '#fb9265', '#9a59b7', '#258e4c'],
            plotOptions: {
                series: {
                    dataLabels: {
                        enabled: true,
                        format: '<b class="funnel_{point.name}">{point.name}</b> ({point.y:,.0f})',
                        softConnector: true
                    },
                    center: ['33%', '50%'],
                    neckWidth: '30%',
                    neckHeight: '20%',
                    width: '70%'
                }
            },
            tooltip: {
                formatter: function() {
                    return '<b>' + this.key + '</b>:<b>' + this.y + '</b>';
                },
            },
            series: $scope.chartSeries
        }

        /* Recent Activites */
        $scope.list_id = [];
        $scope.uCanTrust = function(string) {
            return $sce.trustAsHtml(string);
        }

        $scope.load_recent_activity = function() {

            // let start_date_list=moment(start_date_activity).format('YYYY-MM-DD');
            // let end_date_list=moment(end_date_activity).format('YYYY-MM-DD');
            let contactId = this.contactId;
            let recent_activity_service = API.service('recent-activity');

            let last_list_id = '';

            if ($scope.list_id[$scope.list_id.length - 1] != undefined) {
                last_list_id = $scope.list_id[$scope.list_id.length - 1];
                $scope.list_i = $scope.list_i + 1;
            }

            if ($scope.list_i >= 5) {
                $scope.busy = true;
                recent_activity_service.one("").get({ last_id: last_list_id })
                    .then((response) => {
                        $scope.busy = false;
                        let dataSet = response.plain()
                        angular.forEach(dataSet.data.activities, function(value, key) {
                            if ($scope.list_id.indexOf(value.activity_id) == -1) {
                                $scope.activity_list.push(value);
                                $scope.list_id.push(value.activity_id);
                            }
                        });
                    });
                $scope.list_i = 0;
            }

        }
        $scope.action_modal = function() {

            var current_tab = $scope.active_contact_tab;
            var contact_id = $scope.contact_id;
            var sms_to = $scope.sms_to;
            var lead_id = $scope.lead_id;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/lead-details/add-task-modal.html',
                controller: ActionModalController,
                resolve: {
                    current_tab: function() {
                        return current_tab;
                    },
                    sms_to: function() {
                        return sms_to;
                    },
                    contact_id: function() {
                        return contact_id;
                    },
                    lead_id: function() {
                        return lead_id;
                    }

                }
            });
            return modalInstance;
        }
        // $scope.load_recent_activity();
        /* ***** Date Picker ***** */
        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };
        $scope.datePickerOptions = {
            linkedCalendars:false,
            startDate: moment().subtract(30, 'days'),
            endDate: moment(),
            alwaysShowCalendars:true,
            locale: {
                applyClass: 'btn-green',
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
                'apply.daterangepicker': function(ev, picker) {
                    var start = ev.model.startDate.format('YYYY-MM-DD');
                    var end = ev.model.endDate.format('YYYY-MM-DD');
                    $scope.load_dashboard(start, end);
                }
            }
        }





    }

}
class ActionModalController {
    constructor($stateParams, $scope, $state, $location, API, $uibModal, current_tab, contact_id, sms_to, lead_id, $uibModalInstance, $timeout) {
        'ngInject'
        var uibModalInstance = $uibModalInstance;
        $scope.task_added = false;
        $scope.add_header = true
        $scope.formSubmitted = false;
        $scope.newdiv = true;
        $scope.showdiv = true;
        var user_data = JSON.parse(localStorage.getItem('user_data'));
        $scope.contact_id = '';
        $scope.task = {
            title: '',
            duration: '30 min',
            priority: 'high',
            note: '',
            //user_id:user_data.id
        };
        $scope.agent_dashboard = true;

        let lead_assignees = API.service('lead-assignees', API.all('leads'))
        lead_assignees.one().get()
            .then((response) => {
                $scope.lead_assignees = response.plain().data.lead_assignees
                $scope.task.user_id = user_data.id
            });

        $scope.schedule_time = new Date();

        $scope.isOpen = false;

       
        

        $scope.openCalendar = function(e) {
            e.preventDefault();
            e.stopPropagation();

            $scope.isOpen = true;
        };
        $scope.closemodal = function() {
            $uibModalInstance.close();
        }
        $scope.lead_id = lead_id;
        $scope.add_ac_form = false;
        $scope.current_tab = current_tab;
        if (current_tab == 'tasks') {
            $scope.modal_title = "Add Task";
            $scope.submit_button_title = "Add New Task";
            $scope.task_duration = '30 min';
            $scope.task_priority = 'high';

            /* For Add Task only */
            let tasktpe = API.service('task-types', API.all('leads'))
            tasktpe.one()
                .get()
                .then((response) => {
                    var res = response.plain();
                    var task_types = res.data.task_types;
                    $scope.task_types = task_types;
                    $scope.default_task_types = $scope.task_types[0].id;
                    $scope.task.type = $scope.task_types[0].id;
                })
            $scope.add_new_task = function() {
                $scope.formSubmitted = true;

                $scope.task_submit = {};
                var date_S = moment($scope.schedule_time)
                    .format('YYYY-MM-DD');
                var time_S = moment($scope.schedule_time)
                    .format('HH:mm');
                var action_date = date_S + ' ' + time_S + ':00';
                $scope.task_submit = {
                    type: $scope.task.type,
                    title: $scope.task.title,
                    action_date: action_date,
                    task_time: $scope.task.time,
                    duration: $scope.task.duration,
                    priority: $scope.task.priority,
                    description: $scope.task.note,
                    assignee: $scope.task.user_id,
                    lead_id: $scope.lead_id
                }

                var postTask = API.service('add-task/' + $scope.lead_id, API.all('leads'))
                postTask.post($scope.task_submit)
                    .then((response) => {
                        $scope.task_added = true;
                        $timeout(function() {
                            $scope.closemodal();
                            $state.go('app.agentdashboard', {}, { reload: true });
                            reload: true
                        }, 3000);
                    });
            }


        }
        $scope.submit_action_form = function() {
            $scope.add_ac_form = true;
        }


        $scope.submit_success = function() {
            $scope.message = "";
            $scope.form_submit = false;
            $scope.form_submitted = true;
            $timeout(function() {
                $uibModalInstance.close();
            }, 1000);
            $state.go('app.viewcontact', {
                contactId: contact_id,
                current_tab: current_tab
            }, {
                reload: true
            });
        }
        $scope.today = function() {
            $scope.task.date = new Date();
        };
        $scope.today();

        $scope.clear = function() {
            $scope.task.date = null;
        };

        $scope.inlineOptions = {
            customClass: getDayClass,
            minDate: new Date(),
            showWeeks: true
        };

        
        $scope.dateOptions = {};
        $scope.dateOptions.minDate = new Date();
        $scope.dateOptions.showWeeks = false;

        // Disable weekend selection
        function disabled(data) {
            var date = data.date,
                mode = data.mode;
            return mode === 'day' && (date.getDay() === 0 || date.getDay() === 6);
        }

        $scope.setDate = function(year, month, day) {
            $scope.task.date = new Date(year, month, day);
        };

        $scope.formats = ['dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy', 'shortDate'];
        $scope.format = $scope.formats[0];
        $scope.altInputFormats = ['M!/d!/yyyy'];

        $scope.popup1 = {
            opened: false
        };

        $scope.popup2 = {
            opened: false
        };

        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        var afterTomorrow = new Date();
        afterTomorrow.setDate(tomorrow.getDate() + 1);
        $scope.events = [{
                date: tomorrow,
                status: 'full'
            },
            {
                date: afterTomorrow,
                status: 'partially'
            }
        ];

        function getDayClass(data) {
            var date = data.date,
                mode = data.mode;
            if (mode === 'day') {
                var dayToCheck = new Date(date)
                    .setHours(0, 0, 0, 0);

                for (var i = 0; i < $scope.events.length; i++) {
                    var currentDay = new Date($scope.events[i].date)
                        .setHours(0, 0, 0, 0);

                    if (dayToCheck === currentDay) {
                        return $scope.events[i].status;
                    }
                }
            }

            return '';
        }

        $scope.task.time = new Date();

        $scope.hstep = 1;
        $scope.mstep = 15;
        $scope.time_arrowkeys = false;
        $scope.options = {
            hstep: [1, 2, 3],
            mstep: [1, 5, 10, 15, 25, 30],
            arrowkeys: false
        };

        $scope.ismeridian = true;
        $scope.toggleMode = function() {
            $scope.ismeridian = !$scope.ismeridian;
        };

        $scope.update = function() {
            var d = new Date();
            d.setHours(14);
            d.setMinutes(0);
            $scope.task.time = d;
        };

        $scope.$watch('task.contact', function(tmpStr) {

            $scope.contactSearchResult = {};
            $timeout(function() {
                if (tmpStr === $scope.task.contact) {
                    let searchresults = API.service('search-contacts', API.all('contacts'))
                    searchresults.post({ 'searched_text': $scope.task.contact }).then((response) => {
                        $scope.contactSearchResult = response.data;
                    });
                }
            }, 500);
            $scope.newdiv = true;
        });
        $scope.hideme = function(item) {
            $scope.lead_id = item.lead.id;
            $scope.contactSearchResult = {};
            $scope.task.contact = item.fullname;
            $scope.newdiv = false;
        }
    }
}


export const AgentDashboardComponent = {
    templateUrl: './views/app/pages/dashboard/agent-dashboard/agent-dashboard.component.html',
    controller: AgentDashboardController,
    controllerAs: 'vm',
    bindings: {}
}
