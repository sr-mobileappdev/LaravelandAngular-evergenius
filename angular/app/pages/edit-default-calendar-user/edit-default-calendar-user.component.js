class EditCalendarUserController {
    constructor($stateParams, $state, API, $uibModal, unauthorizedService, $window, AclService) {
        'ngInject'
        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.add_break_disable = true;
        this.userRolesSelected = []
        this.uibModal = $uibModal
        this.current_user_data = JSON.parse($window.localStorage.user_data)
        // Current user session ID
        this.curr_user_id = this.current_user_data.id
        this.roles = AclService.getRoles()

        this.can = AclService.can
        if (!this.can('edit.default.calendar')) {
            $state.go('app.unauthorizedAccess');
        }

        this.user_role = this.roles[0]

        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }

        let userId = $stateParams.userId

        if (this.user_role == 'doctor') {
            unauthorizedService.isUnauthorized(userId, this.curr_user_id);
        }

        this.userId = userId;
        this.times_allow = [{
            id: '00:00',
            title: '12 AM'
        }, {
            id: '00:15',
            title: '12:15 AM'
        }, {
            id: '00:30',
            title: '12:30 AM'
        }, {
            id: '00:45',
            title: '12:45 AM'
        }, {
            id: '01:00',
            title: '01:00 AM'
        }, {
            id: '01:15',
            title: '01:15 AM'
        }, {
            id: '01:30',
            title: '01:30 AM'
        }, {
            id: '01:45',
            title: '01:45 AM'
        }, {
            id: '02:00',
            title: '02:00 AM'
        }, {
            id: '02:15',
            title: '02:15 AM'
        }, {
            id: '02:30',
            title: '02:30 AM'
        }, {
            id: '02:45',
            title: '02:45 AM'
        }, {
            id: '03:00',
            title: '03:00 AM'
        }, {
            id: '03:15',
            title: '03:15 AM'
        }, {
            id: '03:30',
            title: '03:30 AM'
        }, {
            id: '03:45',
            title: '03:45 AM'
        }, {
            id: '04:00',
            title: '04:00 AM'
        }, {
            id: '04:15',
            title: '04:15 AM'
        }, {
            id: '04:30',
            title: '04:30 AM'
        }, {
            id: '04:45',
            title: '04:45 AM'
        }, {
            id: '05:00',
            title: '05:00 AM'
        }, {
            id: '05:15',
            title: '05:15 AM'
        }, {
            id: '05:30',
            title: '05:30 AM'
        }, {
            id: '05:45',
            title: '05:45 AM'
        }, {
            id: '06:00',
            title: '06:00 AM'
        }, {
            id: '06:15',
            title: '06:15 AM'
        }, {
            id: '06:30',
            title: '06:30 AM'
        }, {
            id: '06:45',
            title: '06:45 AM'
        }, {
            id: '07:00',
            title: '07:00 AM'
        }, {
            id: '07:15',
            title: '07:15 AM'
        }, {
            id: '07:30',
            title: '07:30 AM'
        }, {
            id: '07:45',
            title: '07:45 AM'
        }, {
            id: '08:00',
            title: '08:00 AM'
        }, {
            id: '08:15',
            title: '08:15 AM'
        }, {
            id: '08:30',
            title: '08:30 AM'
        }, {
            id: '08:45',
            title: '08:45 AM'
        }, {
            id: '09:00',
            title: '09:00 AM'
        }, {
            id: '09:15',
            title: '09:15 AM'
        }, {
            id: '09:30',
            title: '09:30 AM'
        }, {
            id: '09:45',
            title: '09:45 AM'
        }, {
            id: '10:00',
            title: '10:00 AM'
        }, {
            id: '10:15',
            title: '10:15 AM'
        }, {
            id: '10:30',
            title: '10:30 AM'
        }, {
            id: '10:45',
            title: '10:45 AM'
        }, {
            id: '11:00',
            title: '11:00 AM'
        }, {
            id: '11:15',
            title: '11:15 AM'
        }, {
            id: '11:30',
            title: '11:30 AM'
        }, {
            id: '11:45',
            title: '11:45 AM'
        }, {
            id: '12:00',
            title: '12:00 PM'
        }, {
            id: '12:15',
            title: '12:15 PM'
        }, {
            id: '12:30',
            title: '12:30 PM'
        }, {
            id: '12:45',
            title: '12:45 PM'
        }, {
            id: '13:00',
            title: '01:00 PM'
        }, {
            id: '13:15',
            title: '01:15 PM'
        }, {
            id: '13:30',
            title: '01:30 PM'
        }, {
            id: '13:45',
            title: '01:45 PM'
        }, {
            id: '14:00',
            title: '02:00 PM'
        }, {
            id: '14:15',
            title: '02:15 PM'
        }, {
            id: '14:30',
            title: '02:30 PM'
        }, {
            id: '14:45',
            title: '02:45 PM'
        }, {
            id: '15:00',
            title: '03:00 PM'
        }, {
            id: '15:15',
            title: '03:15 PM'
        }, {
            id: '15:30',
            title: '03:30 PM'
        }, {
            id: '15:45',
            title: '03:45 PM'
        }, {
            id: '16:00',
            title: '04:00 PM'
        }, {
            id: '16:15',
            title: '04:15 PM'
        }, {
            id: '16:30',
            title: '04:30 PM'
        }, {
            id: '16:45',
            title: '04:45 PM'
        }, {
            id: '17:00',
            title: '05:00 PM'
        }, {
            id: '17:15',
            title: '05:15 PM'
        }, {
            id: '17:30',
            title: '05:30 PM'
        }, {
            id: '17:45',
            title: '05:45 PM'
        }, {
            id: '18:00',
            title: '06:00 PM'
        }, {
            id: '18:15',
            title: '06:15 PM'
        }, {
            id: '18:30',
            title: '06:30 PM'
        }, {
            id: '18:45',
            title: '06:45 PM'
        }, {
            id: '19:00',
            title: '07:00 PM'
        }, {
            id: '19:15',
            title: '07:15 PM'
        }, {
            id: '19:30',
            title: '07:30 PM'
        }, {
            id: '19:45',
            title: '07:45 PM'
        }, {
            id: '20:00',
            title: '08:00 PM'
        }, {
            id: '20:15',
            title: '08:15 PM'
        }, {
            id: '20:30',
            title: '08:30 PM'
        }, {
            id: '20:45',
            title: '08:45 PM'
        }, {
            id: '21:00',
            title: '09:00 PM'
        }, {
            id: '21:15',
            title: '09:15 PM'
        }, {
            id: '21:30',
            title: '09:30 PM'
        }, {
            id: '21:45',
            title: '09:45 PM'
        }, {
            id: '22:00',
            title: '10:00 PM'
        }, {
            id: '22:15',
            title: '10:15 PM'
        }, {
            id: '22:30',
            title: '10:30 PM'
        }, {
            id: '22:45',
            title: '10:45 PM'
        }, {
            id: '23:00',
            title: '11:00 PM'
        }, {
            id: '23:15',
            title: '11:15 PM'
        }, {
            id: '23:30',
            title: '11:30 PM'
        }, {
            id: '23:45',
            title: '11:45 PM'
        }];
        let celendarData = API.service('default-celendar', API.all('celendars'))
        celendarData.one(userId).get()
            .then((response) => {
                var $state = this.$state
                if (typeof (response.data.working_plan) == 'undefined') {
                    $state.go('app.viewcalendar', { userId: userId })
                }
                this.celendarData = API.copy(response)
            })
        this.default_app_lengths = [{ id: 15, title: '15 Min' },
        { id: 30, title: '30 Min' },
        { id: 45, title: '45 Min' },
        { id: 60, title: '1 Hour' },
        { id: 90, title: '1.5 Hours' },
        { id: 120, title: '2 Hours' },
        { id: 180, title: '3 Hours' },
        { id: 240, title: '4 Hours' },
        { id: 300, title: '5 Hours' }
        ]
    }

    add_breakday_submit() {

        if (this.add_breakday_day == '') {
            this.add_breakday_day_select = true;
        }
        if (this.add_breakday_start == '') {
            this.add_breakday_start_select = true;
        }
        if (this.add_breakday_end == '') {
            this.add_breakday_end_select = true;
        }

        if (this.add_breakday_day != '' && this.add_breakday_start != '' && this.add_breakday_end != '') {
            let day = this.add_breakday_day;
            let start_time = this.add_breakday_start;
            let end_time = this.add_breakday_end;
            let add_break = { 'start': start_time, 'end': end_time }
            if (day == 'monday') {
                if (typeof (this.celendarData.data.working_plan.monday.breaks) == 'undefined') {
                    this.celendarData.data.working_plan.monday.breaks = []
                }
                this.celendarData.data.working_plan.monday.breaks.push(add_break)
            }
            if (day == 'tuesday') {

                if (typeof (this.celendarData.data.working_plan.tuesday.breaks) == 'undefined') {
                    this.celendarData.data.working_plan.tuesday.breaks = []

                }
                this.celendarData.data.working_plan.tuesday.breaks.push(add_break)
            }

            if (day == 'wednesday') {
                debugger;
                if (typeof (this.celendarData.data.working_plan.wednesday.breaks) == 'undefined') {
                    this.celendarData.data.working_plan.wednesday.breaks = []

                }
                this.celendarData.data.working_plan.wednesday.breaks.push(add_break)
            }
            if (day == 'thursday') {
                if (typeof (this.celendarData.data.working_plan.thursday.breaks) == 'undefined') {
                    this.celendarData.data.working_plan.thursday.breaks = []
                }
                this.celendarData.data.working_plan.thursday.breaks.push(add_break)
            }
            if (day == 'friday') {
                if (typeof (this.celendarData.data.working_plan.friday.breaks) == 'undefined') {
                    this.celendarData.data.working_plan.friday.breaks = []
                }
                this.celendarData.data.working_plan.friday.breaks.push(add_break)
            }
            if (day == 'saturday') {
                if (typeof (this.celendarData.data.working_plan.saturday.breaks) == 'undefined') {
                    this.celendarData.data.working_plan.saturday.breaks = []
                }
                this.celendarData.data.working_plan.saturday.breaks.push(add_break)
            }
            if (day == 'sunday') {
                if (typeof (this.celendarData.data.working_plan.sunday.breaks) == 'undefined') {
                    this.celendarData.data.working_plan.sunday.breaks = []
                }
                this.celendarData.data.working_plan.sunday.breaks.push(add_break)
            }
            this.save(true)
        }


    }
    deletebreak(day, index) {
        var deleted = false;
        var celendarData = this.celendarData;
        var $state = this.$state;
        //var  save_fun=this.save(d);
        this.save(true)
        swal({
            title: 'Are you sure?',
            text: 'Do you want to remove this break?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f99265',
            confirmButtonText: 'Yes, Remove Break',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function () {
            if (day == 'monday') {

                celendarData.data.working_plan.monday.breaks.splice(index, 1)
            }
            if (day == 'tuesday') {
                celendarData.data.working_plan.tuesday.breaks.splice(index, 1)
            }
            if (day == 'wednesday') {
                celendarData.data.working_plan.wednesday.breaks.splice(index, 1)
            }
            if (day == 'thursday') {
                celendarData.data.working_plan.thursday.breaks.splice(index, 1)
            }
            if (day == 'friday') {
                celendarData.data.working_plan.friday.breaks.splice(index, 1)
            }
            if (day == 'saturday') {
                celendarData.data.working_plan.saturday.breaks.splice(index, 1)
            }
            if (day == 'sunday') {
                celendarData.data.working_plan.sunday.breaks.splice(index, 1)
            }
            celendarData.put()
                .then(() => {
                    swal({
                        title: 'Success',
                        text: 'Break has been deleted.',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function () {
                        let alert = { type: 'success', 'title': 'Success!', msg: 'Break has been deleted.' }
                        $state.go($state.current, { alerts: alert })
                    })
                }, (response) => {
                    let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                    $state.go($state.current, { alerts: alert })
                })

        })
    }
    save(isValid) {
        if (isValid) {
            let $state = this.$state
            this.celendarData.put()
                .then(() => {
                    let alert = { type: 'success', 'title': 'Success!', msg: 'Schedule has been updated.' }
                    $state.go($state.current, { alerts: alert })
                }, (response) => {
                    let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                    $state.go($state.current, { alerts: alert })
                })
        } else {
            this.formSubmitted = true
        }
    }

    addBreaks(user_id) {
        let $uibModal = this.uibModal
        const modalInstance = $uibModal.open({
            animation: true,
            templateUrl: 'addbreaks.html',
            controller: unavailbleModalController,
            resolve: {
                user_id: function () {
                    return user_id;
                }
            }
        });
        return modalInstance;
    }

}
export const EditCalendarUserComponent = {
    templateUrl: './views/app/pages/edit-default-calendar-user/edit-default-calendar-user.component.html',
    controller: EditCalendarUserController,
    controllerAs: 'vm',
    bindings: {}
}

/* Modal for Unavailable status */
class unavailbleModalController {
    constructor($stateParams, $scope, $state, API, $uibModal, user_id, $uibModalInstance) {
        'ngInject'
        $scope.alerts = [];
        $scope.valid_error = false;
        $scope.hstep = 1;
        $scope.mstep = 1;

        $scope.options = {
            hstep: [1, 2, 3],
            mstep: [1, 5, 10, 15, 25, 30]
        };
        $scope.time_error = false;
        $scope.ismeridian = true;
        $scope.toggleMode = function () {
            $scope.ismeridian = !$scope.ismeridian;
        };

        $scope.update = function () {
            var d = new Date();
            d.setHours(14);
            d.setMinutes(0);
            $scope.mytime = d;
        };
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.changed = function () {
            // $log.log('Time changed to: ' + $scope.mytime);
        };

        $scope.clear = function () {
            $scope.mytime = null;
        };
        $scope.ok = function ($valid) {
            if ($valid) {
                $scope.valid_error = false;
                let unavailableData = API.service('add-multiple-breaks', API.all('celendars'))
                let start_time = convertTimeToalter($scope.unavail.time_from);
                let end_time = convertTimeToalter($scope.unavail.time_to);
                let days = $scope.unavail.days;
                function convertTimeToalter(date) {
                    var timeString = '';
                    var options = {
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: false
                    };
                    return timeString = date.toLocaleString('en-US', options);
                }
                if (start_time >= end_time) {
                    $scope.time_error = true;
                } else {
                    unavailableData.post({ days: days, time_from: start_time, time_to: end_time, user_id: user_id })
                        .then(() => {
                            $uibModalInstance.close();
                            let alert = { type: 'success', 'title': 'Success!', msg: 'Break has been updated.' }
                            $state.go('app.editdefaultcalendar', { userId: user_id, alerts: alert })
                            //  location.reload();
                        }, (response) => {
                            $scope.valid_error = true;
                        })
                }
            }
            else {

            }

        };

    }
}