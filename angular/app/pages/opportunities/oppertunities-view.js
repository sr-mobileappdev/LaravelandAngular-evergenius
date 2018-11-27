class OppertunitiesController {
    constructor($stateParams, $state, API, $scope, $filter, $uibModal, AclService, $window) {
        'ngInject'
        // this.$state = $state
        $scope.userdetail = []
        $scope.leads_group = "";
        $scope.id = "";
        $scope.count_leads_list = {}
        $scope.total_leads_ltv = {}
        $scope.groups_data = {}
        $scope.models = {
            selected: null,
        };
        $scope.list_show = {};
        $scope.page_width = $window.innerWidth;
        this.start_date = moment().subtract(30, 'days')
        $scope.mailchimp_start_date = moment().subtract(90, 'days');
        $scope.mailchimp_end_date = moment();
        this.end_date = moment()

        this.min_date = moment().subtract(2000, 'days')
        this.max_date = moment()
        $scope.show_lists = false;
        this.can = AclService.can
        let stages = API.service('stages', API.all('leads'))
        stages.one().get()
            .then((response) => {
                $scope.lead_stages = response.plain().data.stages
                var list = {}
                angular.forEach($scope.lead_stages, function (val, key) {
                    list[parseInt(val.id)] = [];
                    $scope.list_show[parseInt(val.id)] = true;
                    if ($scope.page_width < 768) {
                        $scope.list_show[parseInt(val.id)] = false;
                    }
                    $scope.count_leads_list[parseInt(val.id)] = 0;
                    $scope.total_leads_ltv[parseInt(parseInt(val.id))] = 0

                });
                $scope.models.lists = list;
                $scope.models.orignal_lists = list;
            })

        /* Get All lead status */
        let lead_lead_statuses = API.service('lead-status', API.all('leads'))
        lead_lead_statuses.one()
            .get()
            .then((response) => {
                $scope.lead_statuses = response.plain()
                    .data.lead_statuses;
                $scope.selected_status = $scope.lead_statuses[0].id
            })
        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date }
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
                'Last 24 Hours': [moment().subtract(1, 'days'), moment()],
                'Last 7 Days': [moment().subtract(7, 'days'), moment()],
                'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                'All Time': ['2016-01-01', moment()]
            },
            eventHandlers: {
                'apply.daterangepicker': function (ev, picker) {
                    var filtertype = {}
                    filtertype.name = 'noValue'
                    var start = ev.model.startDate.format('YYYY-MM-DD');
                    var end = ev.model.endDate.format('YYYY-MM-DD');
                    $scope.datewiseLeads(start, end, filtertype);
                }
            }
        }

        $scope.datewiseLeads = function (start_date_dashboard, end_date_dashboard, filtertype) {
            var start_date_dashboard_v = moment(start_date_dashboard).format('MM-DD-YYYY');
            var end_date_dashboard_v = moment(end_date_dashboard).format('MM-DD-YYYY');
            start_date_dashboard = moment(start_date_dashboard).format('YYYY-MM-DD');
            end_date_dashboard = moment(end_date_dashboard).format('YYYY-MM-DD');
            $scope.fromdate = start_date_dashboard_v;
            $scope.todate = end_date_dashboard_v;
            let filterRule = {};
            filterRule.start_date = start_date_dashboard
            filterRule.end_date = end_date_dashboard
            if (filtertype) {
                if (filtertype.name == 'assinee') {
                    filterRule.assinee = filtertype.assinee;
                    if ($scope.selected_status != undefined && $scope.selected_status != '') {
                        filterRule.status = $scope.selected_status;
                    }

                    if ($scope.service_selected != undefined && $scope.service_selected != '') {
                        filterRule.service = $scope.service_selected;
                    }
                    $scope.filerLeadGroups(filterRule)
                } else if (filtertype.name == 'service') {
                    filterRule.service = filtertype.service;
                    if ($scope.selected_status != undefined && $scope.selected_status != '') {
                        filterRule.status = $scope.selected_status;
                    }
                    if ($scope.select_assignee != undefined && $scope.select_assignee != '') {
                        filterRule.assinee = $scope.select_assignee;
                    }
                    $scope.filerLeadGroups(filterRule)
                }
                else if (filtertype.name == 'status') {
                    filterRule.status = filtertype.status;
                    if ($scope.service_selected != undefined && $scope.service_selected != '') {
                        filterRule.service = $scope.service_selected;
                    }
                    if ($scope.select_assignee != undefined && $scope.select_assignee != '') {
                        filterRule.assinee = $scope.select_assignee;
                    }
                    $scope.filerLeadGroups(filterRule)
                }
                else if (filtertype.name == 'noValue') {
                    if ($scope.service_selected != undefined && $scope.service_selected != '') {
                        filterRule.service = $scope.service_selected;
                    }
                    if ($scope.select_assignee != undefined && $scope.select_assignee != '') {
                        filterRule.assinee = $scope.select_assignee;
                    }
                    if ($scope.selected_status != undefined && $scope.selected_status != '') {
                        filterRule.status = $scope.selected_status;
                    }
                    $scope.filerLeadGroups(filterRule)
                }
            }

        }
        $scope.show_card_list = function (stage_id) {
            if ($window.innerWidth < 768) {
                if ($scope.list_show[parseInt(stage_id)] == true) {
                    $scope.list_show[parseInt(stage_id)] = false;
                } else {
                    angular.forEach($scope.list_show, function (val, key) {
                        $scope.list_show[parseInt(key)] = false;
                    });
                    $scope.list_show[parseInt(stage_id)] = true;
                }
            }
        }

        $scope.upcoming_leads = function () {
            let leads_group = API.service('leads-group', API.all('leads'))
            leads_group.one().get({ status: 1 })
                .then((response) => {
                    $scope.leads_group = response.plain().data.leads
                    $scope.displayLeadGrous($scope.leads_group);
                })
        }

        $scope.$watch('select_assignee', function (assigneeID) {
            if (assigneeID != undefined) {
                let filtertype = {};
                filtertype.name = 'assinee'
                filtertype.assinee = assigneeID;


                var start = $scope.datePicker.startDate
                var end = $scope.datePicker.endDate
                $scope.datewiseLeads(start, end, filtertype);
            }
        })

        $scope.$watch('service_selected', function (serviceID) {
            if (serviceID != undefined) {
                let filtertype = {};
                filtertype.name = 'service'
                filtertype.service = serviceID
                var start = $scope.datePicker.startDate
                var end = $scope.datePicker.endDate
                $scope.datewiseLeads(start, end, filtertype);
            }
        })

        $scope.$watch('selected_status', function (status_id) {
            if (status_id != undefined) {
                let filtertype = {};
                filtertype.name = 'status'
                filtertype.status = status_id;


                var start = $scope.datePicker.startDate
                var end = $scope.datePicker.endDate
                $scope.datewiseLeads(start, end, filtertype);
            }
        })

        $scope.filerLeadGroups = function (filterRule) {
            let owner_list = API.service('leads-group', API.all('leads'))
            owner_list.one().get(filterRule)
                .then((response) => {
                    $scope.leads_group = response.plain().data.leads
                    $scope.displayLeadGrous($scope.leads_group);
                })
        }

        $scope.displayLeadGrous = function (leads_groups) {
            $scope.show_lists = false;
            $scope.leads_group = [];
            var stages = $scope.lead_stages;
            angular.forEach(stages, function (val_k, key) {
                $scope.models.lists[parseInt(val_k.id)] = [];
                $scope.groups_data[parseInt(val_k.id)] = [];
                $scope.total_leads_ltv[parseInt(parseInt(val_k.id))] = 0;
            });

            $scope.all_leads = [];
            angular.forEach(leads_groups, function (value, key) {
                angular.forEach(value, function (value, key) {
                    $scope.all_leads.push(value);
                    let assignee_name = '';
                    let assignee_avtar = '';
                    if (value.user_id != null && value.assignee != null) {
                        if (value.assignee.name != undefined && value.assignee.name != null) {
                            assignee_name = value.assignee.name;
                            assignee_avtar = value.assignee.avatar;
                        }
                    }
                    if (value.contact != null) {
                        var Adata = '-'
                        var Bdata = '-'
                        Adata = moment(value.created_at).format('MMM Do YYYY h:mm A');
                        if (value.updated_at) {
                            Bdata = moment(value.updated_at).format('MMM Do YYYY h:mm A');
                        }

                        $scope.groups_data[parseInt(parseInt(value.stage_id))].push({ label: value.contact.first_name + ' ' + value.contact.last_name, assignee_name: assignee_name, id: value.id, contact_id: value.contact_id, stage: value.stage_id, ltv: value.ltv, tasks_count: parseInt(value.tasks_count), actionTaken: value.action_taken, actionClass: value.action_class, actionTime: Adata, assignee_avatar: assignee_avtar, lead_action_time: Bdata });
                        //$scope.models.orignal_lists[parseInt(value.stage_id)].push({ label: value.contact.first_name, assignee_name: assignee_name, id: value.id, contact_id: value.contact_id, stage: value.stage_id, ltv: value.ltv,tasks_count:parseInt(value.tasks_count) });
                        $scope.models.lists[parseInt(value.stage_id)].push({ label: value.contact.first_name + ' ' + value.contact.last_name, assignee_name: assignee_name, id: value.id, contact_id: value.contact_id, stage: value.stage_id, ltv: value.ltv, tasks_count: parseInt(value.tasks_count), actionTaken: value.action_taken, actionClass: value.action_class, actionTime: Adata, assignee_avatar: assignee_avtar, lead_action_time: Bdata });
                    }
                    if (value.ltv != null) {
                        $scope.total_leads_ltv[parseInt(parseInt(value.stage_id))] = parseInt(value.ltv) + $scope.total_leads_ltv[parseInt(parseInt(value.stage_id))];
                    }
                });
                $scope.count_leads_list[parseInt(key)] = Object.keys(value).length;
            });
            $scope.show_lists = true;

        }

        $scope.add = function (a, b) {
            return a + b;
        }

        $scope.$watch('search', function (searchvalue) {
            if (searchvalue != undefined || searchvalue != null) {
                var stages = $scope.lead_stages;
                angular.forEach(stages, function (val_k, key) {
                    $scope.models.lists[parseInt(val_k.id)] = ($filter('filter')($scope.groups_data[parseInt(val_k.id)], searchvalue));
                });
            }

        })

        $scope.myCallback = function (event, index, item, external, type, allowedType, StageId) {

            // If the item in question has no children then we don't need to do anything special other than move the item.

            var destination_stage = StageId;
            var lead_id = item.id;
            let lead_update = API.service('update-lead/' + lead_id, API.all('leads'))
            lead_update.post({ stage: destination_stage }).then(function (response) { });

            if (item.length == 0) {
                return item;
            }
            var stages = $scope.lead_stages;
            for (var s = 0; s < stages.length; s++) {
                var stage_id = stages[s].id;
                for (var i = 0; i < $scope.models.lists[stage_id].length; i++) {
                    if (item.label === $scope.models.lists[stage_id][i].label) {
                        return item;
                    }
                }
            }
        }

        // Model to JSON for demo purpose
        $scope.$watch('models', function (model) {
            $scope.modelAsJson = angular.toJson(model, true);
        }, true);

        $scope.add_main_appointment = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/add-lead-modal.html',
                controller: AddappointmentController,
                resolve: {}
            });
            return modalInstance
        }

        let lead_assignees = API.service('lead-assignees', API.all('leads'))
        lead_assignees.one().get()
            .then((response) => {
                var assines = response.plain();
                $scope.userdetail = assines.data.lead_assignees;
            })

        let lead_services = API.service('lead-services', API.all('leads'))
        lead_services.one().get()
            .then((response) => {
                $scope.lead_services = response.plain().data.services
            })



        /* Find Stage Id By Its name */
        $scope.getStageIdByName = function (stageName) {
            let item = $scope.lead_stages.find(x => x.title === stageName);
            return item.id;
        }

        $scope.getStageNameById = function (stageId) {
            let item = $scope.lead_stages.find(x => x.id == stageId);
            return item.title;
        }


        $scope.action_modal = function (item) {
            var current_tab = $scope.active_contact_tab;
            var contact_id = $scope.contact_id;
            var sms_to = $scope.sms_to;
            var lead_id = item.id;
            var assignes = $scope.userdetail;
            var contact_info = item;

            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/lead-details/add-task-modal.html', //C:\xampp\htdocs\evergeniusui\angular\app\pages\opportunities\add-lead-modal.html
                controller: ActionModalController,
                resolve: {
                    current_tab: function () {
                        return current_tab;
                    },
                    sms_to: function () {
                        return sms_to;
                    },
                    contact_id: function () {
                        return contact_id;
                    },
                    lead_id: function () {
                        return lead_id;
                    },
                    contact_info: function () {
                        return contact_info;
                    },
                    assignes: function () {
                        return assignes;
                    }
                }
            });
            return modalInstance;
        }

    }

    $onInit() { }
}
class ActionModalController {
    constructor($stateParams, $scope, $state, $location, API, $uibModal, current_tab, contact_id, sms_to, lead_id, $uibModalInstance, $timeout, assignes, contact_info) {
        'ngInject'

        var uibModalInstance = $uibModalInstance;
        $scope.task_added = false;
        $scope.formSubmitted = false;
        $scope.task = {
            title: '',
            duration: '30 min',
            priority: 'high',
            note: '',
            contact: contact_info.label,
            user_id: assignes[0].id

        };
        $scope.task.schedule_time = new Date();
        $scope.lead_assignees = assignes;
        var user_data = JSON.parse(localStorage.getItem('user_data'));
        $scope.task.user_id = user_data.id;

        $scope.isOpen = false;

        $scope.openCalendar = function (e) {
            e.preventDefault();
            e.stopPropagation();

            $scope.isOpen = true;
        };

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
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
        $scope.add_new_task = function () {
            $scope.formSubmitted = true;

            $scope.task_submit = {};
            var date_S = moment($scope.task.schedule_time)
                .format('YYYY-MM-DD');
            var time_S = moment($scope.task.schedule_time)
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
                assignee: $scope.task.user_id
            }

            var postTask = API.service('add-task/' + $scope.lead_id, API.all('leads'))
            postTask.post($scope.task_submit)
                .then((response) => {
                    $scope.task_added = true;
                    $timeout(function () {
                        $scope.closemodal();
                        //    $state.go('app.viewcontact', {
                        //        contactId: $scope.contact_id
                        //    }, 
                        //   );
                        reload: true
                    }, 3000);
                });
        }
        $scope.lead_id = lead_id;
        $scope.contact_id = contact_id;
        $scope.add_ac_form = false;
        $scope.update_task = function () {
            $scope.formSubmitted = true;
            $scope.task_submit = {};
            var date_S = moment($scope.task.date)
                .format('YYYY-MM-DD');
            var time_S = moment($scope.task.time)
                .format('HH:mm');
            var action_date = date_S + ' ' + time_S + ':00';
            $scope.task_update = {
                type: $scope.task.type_id,
                title: $scope.task.title,
                action_date: action_date,
                task_time: $scope.task.time,
                duration: $scope.task.duration,
                priority: $scope.task.priority,
                description: $scope.task.description
            }

            var postTask = API.service('update-task/' + $scope.task.id, API.all('leads'))
            postTask.post($scope.task_update)
                .then((response) => {
                    $scope.task_added = true;
                    $timeout(function () {
                        $scope.closemodal();
                        $state.go('app.viewcontact', {
                            contactId: $scope.contact_id
                        }, {
                                reload: true
                            });
                    }, 3000);
                });
        }

        $scope.today = function () {
            $scope.task.date = new Date();
        };
        $scope.today();

        $scope.clear = function () {
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


        $scope.setDate = function (year, month, day) {
            $scope.task.date = new Date(year, month, day);
        };

        $scope.formats = ['dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy', 'shortDate'];
        $scope.format = $scope.formats[0];
        $scope.altInputFormats = ['M!/d!/yyyy'];


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
        $scope.toggleMode = function () {
            $scope.ismeridian = !$scope.ismeridian;
        };

        $scope.update = function () {
            var d = new Date();
            d.setHours(14);
            d.setMinutes(0);
            $scope.task.time = d;
        };
    }
}

class AddappointmentController {
    constructor($window, $http, $stateParams, $scope, $state, API, $uibModal, $timeout, $uibModalInstance) {
        $scope.newdiv = true;
        $scope.changeheading = false;
        $scope.stage_id = null;
        $scope.country_code = "+1";
        $scope.lead_typ = 0;
        $scope.add_disable = false;
        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;

        }, function errorCallback(response) {
            // called asynchronously if an error occurs
            // or server returns response with an error status.
        });


        let lead_services = API.service('lead-services', API.all('leads'))
        lead_services.one().get()
            .then((response) => {
                $scope.lead_services = response.plain().data.services
                $scope.services_id = $scope.lead_services[0].id
            })

        let stages = API.service('stages', API.all('leads'))
        stages.one().get()
            .then((response) => {
                $scope.stages = response.plain().data.stages
                $scope.stage_id = $scope.stages[0].id
            })

        let lead_assignees = API.service('lead-assignees', API.all('leads'))
        lead_assignees.one().get()
            .then((response) => {
                $scope.lead_assignees = response.plain().data.lead_assignees
                $scope.assignees_id = $scope.lead_assignees[0].id
            })

        let sources = API.service('sources', API.all('leads'))
        sources.one().get()
            .then((response) => {

                $scope.sources = response.plain().data.sources
                $scope.source_id = $scope.sources[0].id

            })
        $scope.$watch('user.contactname', function (tmpStr) {
            if ($scope.user != undefined && $scope.click_f === false) {
                $scope.contactSearchResult = {};
                $timeout(function () {
                    if (tmpStr === $scope.user.contactname) {
                        let searchresults = API.service('search-contacts', API.all('contacts'))
                        searchresults.post({ 'searched_text': $scope.user.contactname }).then((response) => {
                            var sc = [];
                            angular.forEach(response.data, function (data, key) {
                                if (data.lead == null) {
                                    sc.push(data);
                                }
                            })
                            $scope.contactSearchResult = sc;
                        });
                    }
                }, 500);
                $scope.newdiv = true;
            }
            $scope.click_f = false;
        });
        $scope.hideme = function (item) {
            $scope.user.contactname = item.fullname
            $scope.user.email = item.email
            if (item.phone_country_code != undefined && item.phone_country_code != '') {
                $scope.user.phone = item.mobile_number.replace(item.phone_country_code, '');
            } else {
                $scope.user.phone = item.mobile_number
            }
            $scope.contactSearchResult = {};
            $scope.newdiv = false;
            $scope.click_f = true;
        }

        $scope.create_form = function (user) {
            $scope.add_disable = true;
            var elSource = angular.element(document.querySelector('#selectSource'));
            $scope.source = elSource.val();

            let users = API.service('create-new-lead', API.all('leads'))

            users.post({
                'first_name': user.contactname,
                'last_name': "",
                'email': user.email,
                'source': $scope.source,
                'assignee_id': $scope.assignees_id,
                'service_id': $scope.services_id,
                'phone': user.phone,
                'ltv': user.ltv,
                'stage_id': $scope.stage_id,
                'tags': $scope.selectedTags,
                'country_code': $scope.country_code,
                'contact_existing': $scope.lead_typ,
                'action_take': true
            }).then(function (response) {
                $scope.lead_succcess_msg = true;
                $timeout(function () {
                    $scope.closemodal();
                    $state.go('app.oppertunities', {}, { reload: true });
                }, 3000);
            }, function (response) {
                $scope.lead_error = true;
                $scope.add_disable = false;
                $scope.lead_error_msg = response.data.errors.message[0];
            }
            )
        }
        // $scope.change=function(){

        // }             
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        /* Load Tags */
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/contacts/find-tags?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

    }

}
export const OppertunitiesComponent = {
    templateUrl: './views/app/pages/opportunities/opppertunities-view.html',
    controller: OppertunitiesController,
    controllerAs: 'vm',
    bindings: {}
}
