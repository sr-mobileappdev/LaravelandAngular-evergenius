class ContactViewController {
    constructor($scope, $stateParams, $state, API, unauthorizedService, ContextService, $window, AclService, $location, anchorSmoothScroll, $sce, $uibModal, socket) {
        'ngInject'
        $scope.API = API
        $scope.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.userRolesSelected = []
        this.sele_id = 0;
        this.list_sms = true;
        this.sms_success = false
        this.sms_error = false
        this.AclService = AclService;
        this.can = AclService.can
        if (!this.can('view.contacts')) {
            $state.go('app.unauthorizedAccess');
        }
        $scope.new_incoming_sms = [];
        $scope.active_contact_tab = 'tasks';
        $scope.tabs = ['tasks', 'conversation', 'call_logs', 'notes'];
        $scope.action_title = "Add Task";
        if (socket != false) {
            socket.on("chat.message", function (message) {
                var socket_data = JSON.parse(message);
                if ($scope.contact_id == socket_data.contact_id) {
                    var sms_data = socket_data.sms_data;
                    var new_in_sms = {
                        "company_id": sms_data.company_id,
                        "contact_id": socket_data.contact_id,
                        "receiver_name": sms_data.receiver_name,
                        "sid": sms_data.sid,
                        "sms_from": sms_data.sms_from,
                        "sms_to": sms_data.sms_to,
                        "sms_body": sms_data.sms_body,
                        "sent_time": moment().format('LLLL'),
                        "status": "received",
                        "type": "fetch",
                        "direction": "inbound",
                        "created_at": "2017-10-03 07:59:57",
                        "updated_at": null,
                        "deleted_at": null
                    };
                    $scope.sms_list.splice(0, 0, new_in_sms);
                }
            });
        }


        $scope.active_contact_view = function (tab) {

            if (tab == 'tasks') {
                $scope.action_title = "Add Task";
            }
            else if (tab == 'conversation') {
                $scope.action_title = "Send SMS";
            } else if (tab == 'notes') {
                $scope.action_title = "Add notes";
            }
            $scope.active_contact_tab = tab;
        }
        if ($stateParams.current_tab) {
            $scope.active_contact_tab = $stateParams.current_tab;
        }

        this.$scope = $scope;
        $scope.list_id = [];
        $scope.list_i = 5;
        $scope.contact_id = $stateParams.contactId;
        $scope.activity_list = [];

        let meData = this
        this.current_user_data = JSON.parse($window.localStorage.user_data)
        // Current user session ID
        this.curr_user_id = this.current_user_data.id

        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        this.sms_to = '';
        this.show_sms_send = function () {
            this.list_sms = false;
            this.sms_success = false
            this.sms_error = false
        }

        this.show_sms_block = function () {

            if (this.checkedsms == true) {
                this.checkedsms = false;
            } else {
                this.checkedsms = true;
            }

            this.checked = false;
            this.sms_body = '';

            $scope.gotoElement("scrollToDivID");
        }

        /* Scroll Config */
        $scope.scrollconfig = {
            autoHideScrollbar: false,
            theme: 'light',
            advanced: {
                updateOnContentResize: true
            },
            setHeight: 420,
            scrollInertia: 0
        }
        this.show_sms_list = function () {
            this.checkedsms = false;
            if (this.checked == true) {
                this.checked = false;
            } else {
                this.checked = true;
            }
            this.sms_body = '';
        }

        var statuses = API.service('appointmentstatus', API.all('appointments'))
        this.statuses_in = [];
        statuses.getList()
            .then((response) => {
                let statusess = [];
                let roleResponse = response.plain()
                angular.forEach(roleResponse, function (value) {
                    statusess.push({ id: value.id, title: value.title.toString() });
                })
                this.statuses = statusess;
            })

        var contactId = $stateParams.contactId
        this.contactId = contactId;
        let ContactData = API.service('show', API.all('contacts'))

        ContactData.one(contactId).get()
            .then((response) => {
                AclService = this.AclService
                let con_users = []
                this.contactdata = API.copy(response)
                $scope.contactData = this.contactdata;
                $scope.contact_info = $scope.contactData.data;
                $scope.leadStatus = $scope.contact_info.lead_info
                /* Lead */

                if ($scope.contactData.data.lead_info.id != undefined) {
                    $scope.lead_id = $scope.contactData.data.lead_info.id;
                }
                $scope.lead_detail = $scope.contactData.data.lead_info;
                if ($scope.lead_detail === false) {
                    if ($scope.active_contact_tab != 'task' && $scope.active_contact_tab != 'notes') {
                        $scope.active_contact_tab = 'conversation';
                    }
                    $scope.active_contact_view($scope.active_contact_tab);

                }
                if ($scope.contactData.data.lead_info.id != undefined) {
                    $scope.lead_detail.user_id = parseInt($scope.contactData.data.lead_info.user_id);
                    $scope.lead_detail.stage_id = parseInt($scope.contactData.data.lead_info.stage_id);
                    $scope.lead_detail.status_id = parseInt($scope.contactData.data.lead_info.status_id);
                    con_users.push($scope.contactData.data.lead_info.user_id);
                    $scope.load_tasks($scope.lead_id)
                }

                this.sms_to = this.contactdata.data.mobile_number
                $scope.sms_to = this.contactdata.data.mobile_number;
                var con_data = this.contactdata.plain();
                $scope.sms_list = con_data.data.sms_list;

                angular.forEach(this.contactdata.data.appointments, function (value) {
                    con_users.push(value.appointment_provider.id);
                })
                this.roles = AclService.getRoles()
                this.user_role = this.roles[0]
                this.contact_users = con_users;

                this.contactdata.data.mobile_number = this.mask_phone(this.contactdata.data.mobile_number, this.contactdata.data.phone_country_code)
                if (this.contactdata.data.additional_information != undefined && this.contactdata.data.additional_information != null && this.contactdata.data.additional_information != '') {
                    this.$scope.additional_information = JSON.parse(this.contactdata.data.additional_information);
                }

                let is_owner = con_users.indexOf(this.curr_user_id);
                if (is_owner == -1) {
                    if (this.user_role == 'doctor') {
                        $state.go('app.unauthorizedAccess');
                    }
                }
            })
        /**Comment Listing**/
        $scope.commentlist = [];
        let contact_comment_list = API.service('contact-comments', API.all('contacts'));
        contact_comment_list.one(contactId).get().then((response) => {
            if (response) {
                let content = response.plain();
                $scope.commentlist = content.data;
            }
        });

        /**Comment Listing**/

        this.sendsms = function (isValid) {
            API = $scope.API;
            if (isValid) {
                this.formSubmitted = false
                let $state = $scope.$state
                let SendSms = API.service('send-sms', API.all('contacts'))
                SendSms.one(contactId).put({ to: this.sms_to, sms_body: this.sms_body })
                    .then(() => {
                        document.getElementById('sms_send_success').style.display = 'block'
                        this.sms_success = true
                        this.sms_body = ''
                        setTimeout(function () { document.getElementById('sms_send_success').style.display = 'none' }, 3000);
                    }, (response) => {
                        this.sms_success = true
                    })
            } else {
                this.formSubmitted = true
            }
        }

        this.mask_phone = function (phnumber, Country_code = '') {
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
        }

        /* ---------- Chat SMS Send  ---------- */
        this.send_sms_chat = function () {
            var sms_body = this.chat_sms_body;
            var sms_to = this.sms_to;
            var msg_add = {
                "sms_body": sms_body,
                "sent_time": new Date(),
                "status": "Sent",
                "type": "general",
                "direction": "outbound-api",
                "created_at": "2017-07-12 13:47:33"

            };

            if (sms_body != '') {
                this.formSubmitted = false
                let $state = $scope.$state
                let SendSms = API.service('send-sms', API.all('contacts'))
                SendSms.one(contactId).put({ to: sms_to, sms_body: sms_body })
                    .then((response) => {
                        this.chat_sms_body = '';
                        $scope.sms_list.push(msg_add);
                    }, (response) => {
                        this.sms_success = true
                    })
            }
        }




        /* Recent Activites */
        $scope.load_recent_activity = function () {
            let contactId = this.contactId;
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


        /**add comment**/

        $scope.add_comment = function () {
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

        /**add comment**/

        /* /Recent Activites */

        $scope.gotoElement = function (eID) {
            $location.hash(eID);
            anchorSmoothScroll.scrollTo(eID);
        };

        $scope.renderHtml = function (html_code) {
            return $sce.trustAsHtml(html_code);
        };
        $scope.uCanTrust = function (string) {
            return $sce.trustAsHtml(string);
        }

        $scope.add_appointment = function () {
            var contactData = $scope.contactData;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/add-appointment-modals/add-app-modal.html',
                controller: add_app_modalController,
                resolve: {
                    contactData: function () {
                        return contactData;
                    }

                }
            });
            return modalInstance;
        }
        $scope.action_modal = function () {
            var current_tab = $scope.active_contact_tab;
            var contact_id = $scope.contact_id;
            var sms_to = $scope.sms_to;
            var lead_id = $scope.lead_id;
            var contact_info = $scope.contact_info;
            var lead_assignees = $scope.lead_assignees;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'action_modal_window.html',
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
                    contact_info: function () {
                        return contact_info;
                    },
                    lead_id: function () {
                        return lead_id;
                    },
                    lead_assignees: function () {
                        return lead_assignees
                    }
                }
            });
            return modalInstance;
        }

        $scope.change_task_status = function (task_id, is_open, index) {
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
                .then(function (response) {
                    $scope.tasks[index_ele].open = is_open;
                    $scope.list_i = 5;
                    $scope.load_recent_activity();

                })

        }



        $scope.delete_task = function (task_id) {
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
            }, function () {
                API.one('leads')
                    .one('task', task_id)
                    .remove()
                    .then(() => {
                        var $state = $scope.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Task has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            state_s.reload()
                        })
                    })
            })
        }

        /* Lead Changes */
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

        let lead_lead_statuses = API.service('lead-status', API.all('leads'))
        lead_lead_statuses.one()
            .get()
            .then((response) => {
                $scope.lead_statuses = response.plain()
                    .data.lead_statuses;
                //$scope.assignees_id = $scope.lead_assignees[0].id
            })


        $scope.$watch('lead_detail.stage_id', function (stageID, old_val) {
            if (stageID != undefined && old_val != undefined) {
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


        $scope.$watch('lead_detail.status_id', function (status_id, old_val) {
            if (status_id != undefined && old_val != undefined) {
                $scope.update_status_id = false;
                var lead_id = $scope.lead_detail.id;
                let lead_update = API.service('update-lead/' + lead_id, API.all('leads'))
                lead_update.post({
                    status: status_id
                })
                    .then(function (response) {
                        $scope.update_status_id = true;
                    })
            }
        });

        $scope.$watch('lead_detail.user_id', function (assigneeId, old_val) {
            if (assigneeId != undefined && old_val != undefined) {
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

        $scope.load_tasks = function (lead_id) {
            let tasks = API.service('lead-tasks', API.all('leads'))
            tasks.one(lead_id)
                .get()
                .then((response) => {
                    var se = response.plain();
                    $scope.tasks = se.data.tasks;
                })
            $scope.show_lists = false;
        }

        $scope.modal_update_lead = function () {
            var contact_id = $scope.contact_id;
            var contact_info = $scope.contact_info;
            var lead_id = $scope.lead_id;
            var lead_detail = $scope.lead_detail;
            var stages = $scope.stages;
            var lead_assignees = $scope.lead_assignees;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/lead-details/edit-lead-modal.html',
                controller: EditLeadModalController,
                windowClass: 'addLead-cls',
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
                    },
                    contact_info: function () {
                        return contact_info
                    }
                }
            });
            return modalInstance;
        }

        $scope.add_lead = function () {
            var contactData = $scope.contactData;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/add-lead-modal.html',
                controller: AddappointmentController,
                resolve: {
                    contactData: function () {
                        return contactData;
                    }
                }
            });
            return modalInstance
        }

        $scope.read_taks = function (task_id) {
            var task = $scope.tasks.find(x => x.id === task_id);
            var task_desc = task.description;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'task-description.html',
                controller: TaskDescriptionModalController,
                resolve: {
                    task: function () {
                        return task
                    }
                }
            });
            return modalInstance
        }

        $scope.edit_task = function (task_id) {
            var task = $scope.tasks.find(x => x.id === task_id);
            var task_desc = task.description;
            var contact_id = $scope.contact_id;
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/lead-details/edit-task-modal.html',
                controller: EditTaskModalController,
                resolve: {
                    task: function () {
                        return task
                    },
                    contact_id: function () {
                        return contact_id
                    }
                }
            });
            return modalInstance
        }

        /* Delete Lead */
        $scope.delete_lead = function () {
            var API = $scope.API
            var $state = $scope.$state
            swal({
                title: 'Are you sure?',
                text: 'Do you want to delete this opportunity?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, delete it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                API.one('leads').one('lead', $scope.lead_id).remove()
                    .then(() => {
                        swal({
                            title: 'Deleted!',
                            text: 'Opportunity has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            $state.reload()
                        })
                    }, (response) => {
                        let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                        $state.go($state.current, { alerts: alert })
                    })
            })
        }



        this.change_status = function (appointId, app_status) {
            var API = $scope.API
            var $state = $scope.$state
            if (app_status == '3') {
                const modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: './views/app/pages/add-appointment-modals/app-appointment-info-modal.html',
                    controller: RescheduleController,
                    resolve: {
                        AppointId: function () {
                            return appointId;
                        },

                    }
                });
                return modalInstance;
            } else {
                swal({
                    title: 'Are you sure?',
                    text: 'Do you want to change status ?',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#27b7da',
                    confirmButtonText: 'Yes, Change it!',
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    html: false
                }, function () {
                    let appData = API.service('updatestatus', API.all('appointments'))
                    appData.one(appointId).put({ "appointment_id": appointId, "appointment_status": app_status })
                        .then(() => {
                            swal({
                                title: 'Updated!',
                                text: 'Status has been updated.',
                                type: 'success',
                                confirmButtonText: 'OK',
                                closeOnConfirm: true
                            }, function () {
                                $state.reload()
                            })
                        }, (response) => {
                            let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                            $state.go($state.current, { alerts: alert })
                        })
                })
            }


        }
    }

    $onInit() { }
}

/* Modal for apointment bookeing status */
class add_app_modalController {
    constructor($stateParams, $scope, $state, API, $uibModal, $uibModalInstance, contactData, $timeout) {

        var plain_data = contactData.plain();
        $scope.contact_info = plain_data.data;

        $scope.step1 = false;
        $scope.step2 = true;
        this.API = API;
        $scope.provider_error = false;
        $scope.scheduling_method = "web";
        $scope.contact_type = "new";
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        /* Get Apppointment Providers */
        var findProviders = API.service('company-providers', API.all('appointments'))
        findProviders.one('').get()
            .then((response) => {
                $scope.all_providers = response.data.company_providers;
            });



        /* Search Phone number */

        $scope.contact_info.phone = plain_data.data.mobile_number;

        $scope.$watchCollection('appointment_provider', function (new_val, old_val) {
            if (new_val != undefined) {
                $scope.provider_time_slots = false;
                var date_start = moment().format('YYYY-MM-DD');
                var date_end = moment().add(90, 'days').format('YYYY-MM-DD');
                var provider_id = new_val;
                var findProviders = API.service('provider-slots', API.all('appointments'))
                findProviders.one('').get({ user_id: provider_id, date_from: date_start, date_to: date_end })
                    .then((response) => {
                        $scope.provider_error = false;
                        $scope.provider_time_slots = true;
                        let res = response.plain();
                        let avail_slots = res.data.slots_available;
                        $scope.app_days = []
                        angular.forEach(avail_slots, function (val, key) {
                            $scope.app_days.push(key);
                        })
                        $scope.provider_slots = avail_slots;
                    }, (response) => {
                        $scope.provider_error = response.data.errors.message[0];
                    });
            }
        });

        $scope.submit_appointment = function () {
            var selected_provider = $scope.appointment_provider;
            var contact_id = $scope.contact_info.id;
            var app_day = $scope.selectedDay;
            var app_time_index = $scope.selectedTime;
            var scheduling_method = $scope.scheduling_method;
            var appointment_reason = $scope.appointment_reason;
            var contact_type = $scope.contact_type;
            var start_app_time = app_day + " " + $scope.provider_slots[app_day][app_time_index].start_time;
            var end_app_time = app_day + " " + $scope.provider_slots[app_day][app_time_index].end_time;
            var addAppointments = API.service('add-modal-appointment', API.all('appointments'))
            addAppointments.post({
                provider_id: selected_provider,
                start_time: start_app_time,
                end_time: end_app_time,
                contact_id: contact_id,
                scheduling_method: scheduling_method,
                appointment_reason: appointment_reason,
                contact_type: contact_type
            })
                .then((response) => {
                    $scope.step1 = false;
                    $scope.step2 = false;
                    $scope.success_view = true;
                    $state.go('app.viewcontact', { contactId: contact_id }, { reload: true });

                    $timeout(function () {
                        $uibModalInstance.close();
                    }, 800);
                }, (response) => {
                    let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                    $state.go($state.current, { alerts: alert })
                })
        }

    }

}

class ActionModalController {
    constructor($stateParams, $scope, $state, $location, API, $uibModal, current_tab, contact_id, sms_to, lead_id, $uibModalInstance, $timeout, contact_info, lead_assignees, $window) {
        'ngInject'
        var uibModalInstance = $uibModalInstance;
        $scope.task_added = false;
        $scope.formSubmitted = false;

        if (lead_assignees.length > 0) {
            $scope.task = {
                title: '',
                duration: '30 min',
                priority: 'high',
                note: '',
                contact: contact_info.first_name + ' ' + contact_info.last_name,
                user_id: lead_assignees[0].id
            };
        } else {
            $scope.task = {
                title: '',
                duration: '30 min',
                priority: 'high',
                note: '',
                contact: contact_info.first_name + ' ' + contact_info.last_name
            };
        }



        $scope.task.schedule_time = new Date();
        $scope.isOpen = false;

        $scope.openCalendar = function () {
            $scope.isOpen = true;
        };

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        $scope.lead_id = lead_id;
        $scope.contact_id = contact_id;
        $scope.add_ac_form = false;

        var user_data = JSON.parse(localStorage.getItem('user_data'));
        $scope.task.user_id = user_data.id;

        if (current_tab == 'tasks') {
            $scope.lead_assignees = lead_assignees;
            $scope.modal_title = "Add Task";
            $scope.submit_button_title = "Add New Task";
            $scope.task_duration = '30 min';
            $scope.task_priority = 'high';
            $scope.current_tab = current_tab;
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
                            $state.go('app.viewcontact', {
                                contactId: $scope.contact_id
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
            var message = $scope.task.message;
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
                    SendSms.one(contact_id).put({ to: sms_to, sms_body: sms_body })
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
            $state.go('app.viewcontact', { contactId: contact_id, current_tab: current_tab }, { reload: true });
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
    constructor($stateParams, $scope, $window, $http, $state, $location, API, $uibModal, contact_id, lead_id, lead_detail, $uibModalInstance, $timeout, stages, lead_assignees, contact_info) {
        'ngInject'
        $scope.update_sucess = false;
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

        $scope.isOpen = false;

        $scope.openCalendar = function (e) {
            $scope.isOpen = true;
        };


        /* Load Tags */
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/contacts/find-tags?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }


        $scope.lead_detail = lead_detail;
        $scope.contact_info = contact_info;
        $scope.selectedSource = contact_info.source;
        $scope.searchSrc = contact_info.source;
        $scope.lead_detail.service_id = parseInt($scope.lead_detail.service_id);

        var elSource = angular.element('#selectSource');
        elSource.val(contact_info.source.title);
        $scope.ltv = parseFloat($scope.lead_detail.ltv);
        $scope.stages = stages;
        $scope.lead_assignees = lead_assignees;

        $scope.update_lead = function () {
            $scope.update_data = {
                ltv: $scope.ltv,
                stage: $scope.lead_detail.stage_id,
                service: $scope.lead_detail.service_id,
                assinee: $scope.lead_detail.user_id,
                tags: $scope.contact_info.tags
            };
            var lead_id = $scope.lead_detail.id;
            let lead_update = API.service('update-lead/' + lead_id, API.all('leads'))
            lead_update.post($scope.update_data)
                .then(function (response) {
                    $scope.update_sucess = true;
                    $timeout(function () {
                        $scope.closemodal();
                        $state.go('app.viewcontact', {
                            contactId: $scope.contact_info.id
                        }, {
                                reload: true
                            });
                    }, 3000);
                })
        }




    }
}
class RescheduleController {
    constructor($window, $http, AppointId, $stateParams, $scope, $state, API, $uibModal, $uibModalInstance, $timeout) {
        'ngInject'
        $scope.reschuleOnly = true
        $scope.rescheduleData = true
        $scope.contact_info = new Object;
        $scope.scheduling_method = "web";
        $scope.contact_type = "new";
        $scope.appointment_old = false;
        $scope.app_found = true;
        let AppointmentData = API.service('show', API.all('appointments'))
        AppointmentData.one(AppointId).get()
            .then((response) => {
                var res_data = response.plain().data;
                $scope.appointment_old = res_data;
                $scope.appointment_reason = res_data.appointment_reason[0].title;
                $scope.scheduling_method = res_data.scheduling_method;
                $scope.contact_type = res_data.contact_type;
                $scope.appointment_provider = res_data.provider_user_id;
                $scope.contact_info = response.plain().data.contacts[0]

            })
        var findProviders = API.service('company-providers', API.all('appointments'))
        findProviders.one('').get()
            .then((response) => {
                $scope.all_providers = response.data.company_providers;
            });
        $scope.$watchCollection('appointment_provider', function (new_val, old_val) {
            if (new_val != undefined) {
                $scope.provider_time_slots = false;
                var date_start = moment().format('YYYY-MM-DD');
                var date_end = moment().add(90, 'days').format('YYYY-MM-DD');
                var provider_id = new_val;
                var findProviders = API.service('provider-slots', API.all('appointments'))
                findProviders.one('').get({ user_id: provider_id, date_from: date_start, date_to: date_end })
                    .then((response) => {
                        $scope.provider_error = false;
                        $scope.provider_time_slots = true;
                        let res = response.plain();
                        let avail_slots = res.data.slots_available;
                        $scope.app_days = []
                        angular.forEach(avail_slots, function (val, key) {
                            $scope.app_days.push(key);
                        })
                        $scope.provider_slots = avail_slots;
                        if ($scope.appointment_old != false) {
                            $scope.startDate = moment($scope.appointment_old.start_datetime).format('YYYY-MM-DD');
                            $scope.selectedDay = $scope.startDate;
                            var day_index = $scope.app_days.indexOf($scope.selectedDay);
                            if (day_index != -1) {
                                var startTime = moment($scope.appointment_old.start_datetime).format('hh:mm A');
                                var endTime = moment($scope.appointment_old.end_datetime).format('hh:mm A');
                                var app_time = startTime + '-' + endTime;
                                $scope.provider_slots[$scope.selectedDay].push({ start_time: startTime, end_time: endTime });
                                var sele_index = ($scope.provider_slots[$scope.selectedDay].length) - 1;
                                $scope.selectedTime = sele_index;
                            }
                        }
                    }, (response) => {
                        $scope.provider_error = response.data.errors.message[0];
                    });
            }
        });
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.submit_appointment = function (statuValue) {
            var selected_provider = $scope.appointment_provider;
            var contact_id = $scope.contact_info.id;
            var app_day = $scope.selectedDay;
            var app_time_index = $scope.selectedTime;
            var scheduling_method = $scope.scheduling_method;
            var contact_type = $scope.contact_type;
            var appointment_reason = $scope.appointment_reason;
            var start_app_time = app_day + " " + $scope.provider_slots[app_day][app_time_index].start_time;
            var end_app_time = app_day + " " + $scope.provider_slots[app_day][app_time_index].end_time;
            var addAppointments = API.service('add-modal-appointment', API.all('appointments'))
            addAppointments.post({
                provider_id: selected_provider,
                start_time: start_app_time,
                end_time: end_app_time,
                contact_id: contact_id,
                scheduling_method: scheduling_method,
                appointment_reason: appointment_reason,
                contact_type: contact_type,
                reschedule: statuValue,
                appointment_id: AppointId
            })
                .then((response) => {

                    $scope.success_view = true;
                    $state.go($state.current, { alerts: alert }, { reload: true })
                    $timeout(function () {
                        $uibModalInstance.close();
                    }, 800);
                }, (response) => {
                    let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                    $state.go($state.current, { alerts: alert });
                })
        }
    }

}
class AddappointmentController {
    constructor($window, $http, $stateParams, $scope, $state, API, contactData, $uibModal, $timeout, $uibModalInstance) {
        $scope.newdiv = true;
        $scope.user = {}
        $scope.lead_typ = 0;
        $scope.add_disable = false;
        var plain_data = contactData.plain();
        var contact_data = contactData.plain().data;
        if (contact_data) {
            $scope.user.contactname = contact_data.first_name + ' ' + contact_data.last_name;
            $scope.user.email = contact_data.email;
            $scope.user.phone = contact_data.mobile_number.replace('+1', '');
            $scope.contact_dis = true;

        }
        $scope.add_disable = false;
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

        $scope.contactSearchResult = {};

        $scope.changeheading = false;
        $scope.stage_id = null;
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
            if (tmpStr != undefined && $scope.contact_dis != true) {
                $scope.newdiv = true;
                $timeout(function () {
                    if (tmpStr === $scope.user.contactname) {
                        let searchresults = API.service('search-contacts', API.all('contacts'))
                        searchresults.post({ 'searched_text': $scope.user.contactname }).then((response) => {
                            $scope.contactSearchResult = response.data;

                        });
                    }
                }, 500);
                $scope.newdiv = true;
            }
        });

        $scope.hideme = function (item) {
            $scope.user.contactname = item.fullname
            $scope.user.email = item.email
            $scope.user.phone = item.mobile_number
            $scope.contactSearchResult = {};
            $scope.newdiv = false;
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
            })
        }
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }


    }

}
class TaskDescriptionModalController {
    constructor($window, $http, $stateParams, task, $scope, $state, API, $uibModal, $timeout, $uibModalInstance) {
        $scope.task = task;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
    }
}
class EditTaskModalController {
    constructor($window, $http, $stateParams, task, $scope, contact_id, $state, API, $uibModal, $timeout, $uibModalInstance) {

        $scope.task = task;
        $scope.contact_id = contact_id;
        var date = new Date();
        //console.log($scope.task);
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
                //$scope.task.type = $scope.task_types[0].id;
            });



        $scope.today = function () {
            $scope.task.date = new Date($scope.task.action_date);
        };
        $scope.today();
        $scope.clear = function () {
            $scope.task.date = null;
        };
        $scope.inlineOptions = {
            customClass: getDayClass,
            minDate: date.setDate((new Date()).getDate() - 1),
            showWeeks: false
        };

        $scope.dateOptions = {
            formatYear: 'yy',
            maxDate: new Date(2020, 5, 22),
            minDate: date.setDate((new Date()).getDate() - 1),
            startingDay: 1
        };

        // Disable weekend selection
        function disabled(data) {
            var date = data.date,
                mode = data.mode;
            return mode === 'day' && (date.getDay() === 0 || date.getDay() === 6);
        }

        $scope.toggleMin = function () {
            //$scope.inlineOptions.minDate = $scope.inlineOptions.minDate ? null : new Date();
            //$scope.dateOptions.minDate = $scope.inlineOptions.minDate;
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

        $scope.task.time = new Date($scope.task.action_date);

        $scope.hstep = 1;
        $scope.mstep = 15;
        $scope.time_arrowkeys = false;
        $scope.options = {
            hstep: [1, 2, 3],
            mstep: [1, 5, 10, 15, 25, 30],
            arrowkeys: false
        };
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
                    if (current_tab == 'conversation') {
                        var sms_body = message;
                        if (sms_body != '') {
                            let SendSms = API.service('send-sms', API.all('contacts'))
                            SendSms.one(contact_id).put({ to: sms_to, sms_body: sms_body })
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
                    $state.go('app.viewcontact', { contactId: contact_id, current_tab: current_tab }, { reload: true });
                }

            }
        }
    }
}

export const ContactViewComponent = {
    templateUrl: './views/app/pages/contact-view/contactview.html',
    controller: ContactViewController,
    controllerAs: 'vm',
    bindings: {}
}
