class AddoppertunitiesController {
    constructor($stateParams, $state, API, $scope, $filter, $uibModal, $sce) {
        'ngInject'

        $scope.API = API;
        $scope.$state = $state;
        $scope.list_id = [];
        $scope.list_i = 5;
        $scope.activity_list = [];
        if ($state.params.lead_id != null) {
            $scope.id = $state.params.lead_id
        }

        $scope.lead_id = $state.params.lead_id
        $scope.active_contact_tab = 'tasks';
        $scope.action_title = "Add Task";
        $scope.tabs = ['tasks', 'conversation', 'call_logs', 'notes'];
        $scope.tasks = [];
        $scope.active_contact_view = function (tab) {
            $scope.active_contact_tab = tab;
            if (tab == 'tasks') {
                $scope.action_title = "Add Task";
            } else if (tab == 'conversation') {
                $scope.action_title = "Send SMS";
            } else if (tab == 'notes') {
                $scope.action_title = "Add notes";
            }
        }


        let tasks = API.service('lead-tasks', API.all('leads'))
        tasks.one($scope.id)
            .get()
            .then((response) => {
                var se = response.plain();
                $scope.tasks = se.data.tasks;
            })
        $scope.show_lists = false;

        let stages = API.service('stages', API.all('leads'))
        stages.one()
            .get()
            .then((response) => {
                $scope.stages = response.plain()
                    .data.stages
                $scope.stage_id = $scope.stages[0].id
            })

        let lead_assignees = API.service('lead-assignees', API.all('leads'))
        lead_assignees.one()
            .get()
            .then((response) => {
                $scope.lead_assignees = response.plain()
                    .data.lead_assignees
                $scope.assignees_id = $scope.lead_assignees[0].id
            })


        let lead_details = API.service('show/' + $scope.id, API.all('leads'))
        lead_details.one()
            .get()
            .then((response) => {
                $scope.lead_detail = response.plain()
                    .data;
                $scope.lead_id = $scope.lead_detail.id;
                $scope.lead_detail.user_id = parseInt($scope.lead_detail.user_id);
                $scope.lead_detail.stage_id = parseInt($scope.lead_detail.stage_id);
                var contact_detail = response.plain()
                    .data.contact
                $scope.contact_id = contact_detail.id;
                $scope.contact_name = contact_detail.first_name
                $scope.contact_email = contact_detail.email
                $scope.contact_phone = contact_detail.phone
                $scope.contact_stage = response.plain()
                    .data.stage.title
                $scope.stage_id = parseInt(response.plain()
                    .data.stage.id);
                $scope.contact_ltv_value = response.plain()
                    .data.ltv
                $scope.contact_source = response.plain()
                    .data.source
                $scope.contact_service = response.plain()
                    .data.service.name
                $scope.contact_assignee = response.plain()
                    .data.assignee.name
                $scope.load_recent_activity();
            })

        /* Update Stage Drop Down */

        $scope.$watch('lead_detail.stage_id', function (stageID) {
            if (stageID != undefined) {
                $scope.update_stage = false;
                var lead_id = $scope.lead_detail.id;
                let lead_update = API.service('update-lead/' + lead_id, API.all('leads'))
                lead_update.post({
                    stage: stageID
                })
                    .then(function (response) {
                        $scope.update_stage = true;
                    })
            }
        });

        $scope.$watch('lead_detail.user_id', function (assigneeId) {
            if (assigneeId != undefined) {
                $scope.update_stage = false;
                var lead_id = $scope.lead_detail.id;
                let lead_update = API.service('update-lead/' + lead_id, API.all('leads'))
                lead_update.post({
                    assinee: assigneeId
                })
                    .then(function (response) {
                        $scope.update_stage = true;
                    })
            }
        });

        $scope.add_task = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'add-appointment-2.html',
                controller: AddtaskController,
                resolve: {

                }
            });
            return modalInstance
        }
        /* For Delete Tasks */

        $scope.delete_task = function (task_id) {
            let API = this.API
            var $state = this.$state
            var state_s = this.$state
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
            }, function () {
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
                        }, function () {
                            state_s.reload()
                        })
                    })
            })
        }

        $scope.add_appointment = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'add-appointment.html',
                controller: AddappointmentController,
                resolve: {

                }
            });
            return modalInstance
        }

        $scope.action_modal = function () {
            var current_tab = $scope.active_contact_tab;
            var contact_id = $scope.contact_id;
            var sms_to = $scope.sms_to;
            var lead_id = $scope.lead_id;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/lead-details/action-modal.html',
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
                    }

                }
            });
            return modalInstance;
        }

        $scope.load_recent_activity = function () {
            if ($scope.contact_id != undefined && $scope.contact_id != null) {
                let contactId = $scope.contact_id;
                let recent_activity_service = API.service('recent-activity');
                let last_list_id = '';

                if ($scope.list_id[$scope.list_id.length - 1] != undefined) {
                    last_list_id = $scope.list_id[$scope.list_id.length - 1];
                    $scope.list_i = $scope.list_i + 1;
                }

                if ($scope.list_i >= 5) {
                    $scope.busy = true;
                    recent_activity_service.one("").get({ contact_id: $scope.contact_id, last_id: last_list_id })
                        .then((response) => {
                            $scope.busy = false;
                            let dataSet = response.plain()
                            angular.forEach(dataSet.data.activities, function (value, key) {
                                if ($scope.list_id.indexOf(value.activity_id) == -1) {
                                    $scope.activity_list.push(value);
                                    $scope.list_id.push(value.activity_id);
                                }
                            });
                        });
                    $scope.list_i = 0;
                }
            }
        }
        $scope.uCanTrust = function (string) {
            return $sce.trustAsHtml(string);
        }

        $scope.modal_update_lead = function () {
            var contact_id = $scope.contact_id;
            var lead_id = $scope.lead_id;
            var lead_detail = $scope.lead_detail;
            var stages = $scope.stages;
            var lead_assignees = $scope.lead_assignees;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/lead-details/edit-lead-modal.html',
                controller: EditLeadModalController,
                resolve: {
                    contact_id: function () {
                        return contact_id;
                    },
                    lead_id: function () {
                        return lead_id;
                    },
                    lead_detail: function () {
                        return lead_detail
                    },
                    stages: function () {
                        return stages
                    },
                    lead_assignees: function () {
                        return lead_assignees
                    }
                }
            });
            return modalInstance;
        }

    }

    $onInit() { }
}
class AddappointmentController {
    constructor($window, $http, $stateParams, $scope, $state, API, $uibModal, $timeout, $uibModalInstance) {
        $scope.defaultsources = []
        $scope.stage_id = null;

        $scope.country_code = "+1";
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
        lead_services.one()
            .get()
            .then((response) => {
                $scope.lead_services = response.plain()
                    .data.services
                $scope.services_id = $scope.lead_services[0].id
            })

        let stages = API.service('stages', API.all('leads'))
        stages.one()
            .get()
            .then((response) => {

                $scope.stages = response.plain()
                    .data.stages
                $scope.stage_id = $scope.stages[0].id

            })

        let lead_assignees = API.service('lead-assignees', API.all('leads'))
        lead_assignees.one()
            .get()
            .then((response) => {
                $scope.lead_assignees = response.plain()
                    .data.lead_assignees
                $scope.assignees_id = $scope.lead_assignees[0].id
            })

        let sources = API.service('sources', API.all('leads'))
        sources.one()
            .get()
            .then((response) => {

                $scope.sources = response.plain()
                    .data.sources
                $scope.source_id = $scope.sources[0].id

            })

        $scope.create_form = function (user) {

            let users = API.service('create-new-lead', API.all('leads'))

            users.post({
                'first_name': user.contactname,
                'last_name': "",
                'email': user.email,
                'source_id': $scope.source_id,
                'assignee_id': $scope.assignees_id,
                'service_id': $scope.services_id,
                'phone': user.phone,
                'ltv_value': user.value,
                'stage_id': $scope.stage_id,
                'country_code': $scope.country_code,
                'action_take': true
            })
                .then(function (response) {

                    $scope.closemodal()
                }, function (response) {
                    $scope.lead_error = true;
                    $scope.lead_error_msg = response.data.errors.message[0];
                })
        }
        // $scope.change=function(){

        // }             
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }

}
class AddtaskController {
    constructor($window, $http, $stateParams, $scope, $state, API, $uibModal, $timeout, $uibModalInstance) {

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }

}

class ActionModalController {
    constructor($stateParams, $scope, $state, $location, API, $uibModal, current_tab, contact_id, sms_to, lead_id, $uibModalInstance, $timeout) {
        'ngInject'
        var uibModalInstance = $uibModalInstance;
        $scope.task_added = false;
        $scope.formSubmitted = false;
        $scope.task = {
            title: '',
            duration: '30 min',
            priority: 'high',
            note: ''

        };

        $scope.closemodal = function () {
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

            var user_data = JSON.parse(localStorage.getItem('user_data'));
            $scope.task.user_id = user_data.id;

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
            $scope.add_new_task = function () {
                $scope.formSubmitted = true;
                $scope.task_submit = {};
                var date_S = moment($scope.task.date)
                    .format('YYYY-MM-DD');
                var time_S = moment($scope.task.time)
                    .format('HH:mm');
                var action_date = date_S + ' ' + time_S + ':00';
                $scope.task_submit = {
                    type: $scope.task.type,
                    title: $scope.task.title,
                    action_date: action_date,
                    task_time: $scope.task.time,
                    duration: $scope.task.duration,
                    priority: $scope.task.priority,
                    description: $scope.task.note
                }

                var postTask = API.service('add-task/' + $scope.lead_id, API.all('leads'))
                postTask.post($scope.task_submit)
                    .then((response) => {
                        $scope.task_added = true;
                        $timeout(function () {
                            $scope.closemodal();
                            $state.go('app.leaddetails', {
                                lead_id: $scope.lead_id
                            }, {
                                    reload: true
                                });
                        }, 3000);

                    });
            }
        } else if (current_tab == 'conversation') {
            $scope.modal_title = "Send SMS";
            $scope.submit_button_title = "Send";
        } else if (current_tab == 'notes') {
            $scope.modal_title = "Add Notes";
            $scope.submit_button_title = "Add";
        }

        $scope.submit_action_form = function () {
            $scope.add_ac_form = true;
        }

        $scope.submit_action_form = function () {
            var message = $scope.message;
            $scope.form_submit = true;
            if (current_tab == 'notes') {
                var addComment = API.service('comment', API.all('contacts'))
                addComment.post({
                    contact_id: contact_id,
                    comment: message
                })
                    .then((response) => {
                        $scope.submit_success();
                    });

            }
            if (current_tab == 'conversation') {
                var sms_body = message;
                if (sms_body != '') {
                    let SendSms = API.service('send-sms', API.all('contacts'))
                    SendSms.one(contact_id)
                        .put({
                            to: sms_to,
                            sms_body: sms_body
                        })
                        .then((response) => {
                            $scope.submit_success();
                        }, (response) => {
                            this.sms_success = true
                        })
                }
            }
        }

        $scope.submit_success = function () {
            $scope.message = "";
            $scope.form_submit = false;
            $scope.form_submitted = true;
            $timeout(function () {
                $uibModalInstance.close();
            }, 1000);
            $state.go('app.viewcontact', {
                contactId: contact_id,
                current_tab: current_tab
            }, {
                    reload: true
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

        $scope.dateOptions = {
            formatYear: 'yy',
            maxDate: new Date(2020, 5, 22),
            minDate: new Date(),
            startingDay: 1
        };

        // Disable weekend selection
        function disabled(data) {
            var date = data.date,
                mode = data.mode;
            return mode === 'day' && (date.getDay() === 0 || date.getDay() === 6);
        }

        $scope.toggleMin = function () {
            $scope.inlineOptions.minDate = $scope.inlineOptions.minDate ? null : new Date();
            $scope.dateOptions.minDate = $scope.inlineOptions.minDate;
        };

        $scope.toggleMin();

        $scope.open1 = function () {
            $scope.popup1.opened = true;
        };

        $scope.open2 = function () {
            $scope.popup2.opened = true;
        };

        $scope.setDate = function (year, month, day) {
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

class EditLeadModalController {
    constructor($stateParams, $scope, $window, $http, $state, $location, API, $uibModal, contact_id, lead_id, lead_detail, $uibModalInstance, $timeout, stages, lead_assignees) {
        'ngInject'
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        let lead_services = API.service('lead-services', API.all('leads'))
        lead_services.one()
            .get()
            .then((response) => {
                $scope.lead_services = response.plain()
                    .data.services
                $scope.services_id = $scope.lead_services[0].id
            })

        /* Load Tags */
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/contacts/find-tags?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

        $scope.lead_detail = lead_detail;
        $scope.ltv = $scope.lead_detail.ltv;
        $scope.stages = stages;
        $scope.lead_assignees = lead_assignees;

    }
}


export const AddoppertunitiesComponent = {
    templateUrl: './views/app/pages/opportunities/lead-details/lead-detail.html',
    controller: AddoppertunitiesController,
    controllerAs: 'vm',
    bindings: {}
}