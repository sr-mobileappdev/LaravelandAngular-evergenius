class AppointmentViewController {
    constructor($stateParams, $state, API, unauthorizedService, $window, AclService, $uibModal) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.$state = $state
        this.unauthorizedService = unauthorizedService
        this.formSubmitted = false
        this.alerts = []
        this.userRolesSelected = []
        this.current_user_data = JSON.parse($window.localStorage.user_data)
        // Current user session ID
        this.curr_user_id = this.current_user_data.id
        this.roles = AclService.getRoles()
        this.user_role = this.roles[0]
        this.sms_to = '';
        this.list_sms = true;
        //this.selected_val="2";
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }
        this.can = AclService.can
        if (!this.can('manage.appointments')) {
            $state.go('app.unauthorizedAccess');
        }

        let appointmentId = $stateParams.appointmentId

        let AppointmentData = API.service('show', API.all('appointments'))
        AppointmentData.one(appointmentId).get()
            .then((response) => {
                unauthorizedService = this.unauthorizedService
                this.appointmentdata = API.copy(response)

                if (this.user_role == 'doctor') {
                    unauthorizedService.isUnauthorized(this.appointmentdata.data.provider_user_id, this.curr_user_id);
                }

                this.contact_id = this.appointmentdata.data.contacts[0].id
                this.sms_to = this.appointmentdata.data.contacts[0].mobile_number

                this.selected_val = this.appointmentdata.data.appointment_status_id.toString()
                this.appointmentdata.data.contacts[0].mobile_number = this.mask_phone(this.appointmentdata.data.contacts[0].mobile_number, this.appointmentdata.data.contacts[0].phone_country_code)
            })

        /* **************** For Send & Receive SMS **************** */
        this.sendSmsBlock = function () {
            if (this.checkedsms == 1) {
                this.checkedsms = 0;
            } else {
                this.checkedsms = 1;
            }
            this.checked = 0;
        }

        this.viewSmsBlock = function () {
            if (this.checked == 1) {
                this.checked = 0;
            } else {
                this.checked = 1;
            }
            this.checkedsms = 0;
            this.sms_body = '';
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

        this.sendsms = function (isValid) {
            API = this.API;
            var $state = this.$state
            if (isValid) {
                let $state = this.$state
                let SendSms = API.service('send-sms', API.all('contacts'))
                SendSms.one(this.contact_id).put({ to: this.sms_to, sms_body: this.sms_body })
                    .then(() => {
                        var $state = this.$state
                        document.getElementById('success_send_su').style.display = 'block';
                        this.sms_success = true
                        this.formSubmitted = false
                        setTimeout(function () {
                            document.getElementById('success_send_su').style.display = 'none';
                        }, 3000);
                        this.sms_body = ''
                    }, (response) => {
                        this.sms_error = true
                    })
            } else {
                this.formSubmitted = true
            }
        }

        /* **************** / For Send & Receive SMS **************** */


        this.change_status = function (appointId, app_status) {
            app_status = angular.element('#select_status_' + appointId).val()

            let API = this.API
            let $state = this.$state
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
            }
            else {
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

    save(isValid) {
        if (isValid) {
            let $state = this.$state
            this.usereditdata.put()
                .then(() => {
                    let alert = { type: 'success', 'title': 'Success!', msg: 'User has been updated.' }
                    $state.go($state.current, { alerts: alert })
                }, (response) => {
                    let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                    $state.go($state.current, { alerts: alert })
                })
        } else {
            this.formSubmitted = true
        }
    }

    $onInit() { }
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
export const AppointmentViewComponent = {
    templateUrl: './views/app/pages/appointment-view/appointmentview.html',
    controller: AppointmentViewController,
    controllerAs: 'vm',
    bindings: {}
}
