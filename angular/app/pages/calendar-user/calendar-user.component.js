class CalendarUserController {
    constructor($stateParams, $scope, $state, API, $uibModal, unauthorizedService, $window, AclService) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.is_default_calender = false;
        this.booked_appointments = []
        this.uibModal = $uibModal
        //this.samle_gots=[];
        this.current_user_data = JSON.parse($window.localStorage.user_data)
        // Current user session ID
        this.curr_user_id = this.current_user_data.id
        this.roles = AclService.getRoles()
        this.user_role = this.roles[0]

        this.can = AclService.can
        if (!this.can('view.calendar')) {
            $state.go('app.unauthorizedAccess');
        }


        this.scope = $scope
        this.events = []
        this.doctor_name = ''

        /* Add Minimum time */
        var minimumTtimeStamp = Math.floor(Date.now() / 1000);
        this.minimumTtime = this.toTimestamp(new Date(moment(minimumTtimeStamp * 1000).weekday(0).format("MMM DD, YYYY HH:MM"))) / 1000 + (24 * 60 * 60 * 1)
        /* / Add Minimum time */

        this.userRolesSelected = []
        let userId = $stateParams.userId
        this.userId = userId;
        /* Provider ID */
        $scope.provider_id = this.userId;

        if (this.user_role == 'doctor') {
            unauthorizedService.isUnauthorized(userId, this.curr_user_id);
        }


        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }

        this.start_week_ts = this.toTimestamp(new Date(moment().weekday(0).format("MMM DD, YYYY HH:MM"))) / 1000;
        this.end_week_ts = this.toTimestamp(new Date(moment().weekday(7).format("MMM DD, YYYY HH:MM"))) / 1000;
        this.next_time_val = this.start_week_ts + 24 * 60 * 60 * 7
        this.prev_time_val = this.start_week_ts + 24 * 60 * 60 * 7

        this.loadcalendar = function () {
            let celendarData = API.service('user-celendar', API.all('celendars'))
            celendarData.one(userId).get({ start_time: this.start_week_ts, end_time: this.end_week_ts })
                .then((response) => {
                    API = this.API
                    let start_week_ts = this.start_week_ts
                    let end_week_ts = this.end_week_ts
                    /* Get Booked Appointments*/
                    this.bookedAppointments = response.data.appointments_booked
                    if (response.data.default_appointment_length == '') {
                        this.is_default_calender = true;
                    }
                    this.doctor_name = response.data.user_info.name;
                    this.doctor_phone = response.data.user_info.phone;
                    this.default_slot_length = response.data.default_appointment_length
                    $scope.default_slot_length = this.default_slot_length;
                    let start_date = new Date(this.start_week_ts * 1000);
                    this.userId = userId;
                    this.day_slit = '60'




                    /* From DB */
                    let start_time = this.getBusinessHours(response.data.working_plan, this.bookedAppointments).opening_time
                    let end_time = this.getBusinessHours(response.data.working_plan, this.bookedAppointments).closing_time


                    let slot_time = this.default_slot_length
                    let working_plan = response.data.working_plan

                    /* Calender Code Start */
                    let weekstart_days = []
                    let week_days = []

                    var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    var slot_counts = [];
                    /* Week Days */
                    for (var d = 0; d < 7; d++) {


                        var point_date = new Date(moment(this.start_week_ts * 1000).weekday(d).format("MMM DD, YYYY HH:MM"))
                        let full_start_date = (point_date.getMonth() + 1 + "/" + point_date.getDate() + "/" + point_date.getFullYear()).toString();

                        let day_week = days[d].toLowerCase();
                        let week_working_plan = response.data.working_plan;

                        let day_business_hours = this.getCurrentDayBusinessHours(response.data.working_plan, this.bookedAppointments, day_week)

                        if (day_business_hours != false) {
                            start_time = day_business_hours.opening_time
                            end_time = day_business_hours.closing_time
                        }



                        let day_start_stamp = this.toTimestamp((full_start_date + " " + start_time + ":00").toString())
                        let day_end_stamp = this.toTimestamp((full_start_date + " " + end_time + ":00").toString())

                        if (day_end_stamp < day_start_stamp) {
                            day_end_stamp.setDate(day_end_stamp.getDate() + 1);
                        }

                        let time_diff_mili_sec = day_end_stamp - day_start_stamp;
                        let time_diff_min = parseInt(time_diff_mili_sec) / 60000;
                        let num_slots = time_diff_min / slot_time;
                        let day_slots_time = []
                        var d_slot_count = 0;

                        for (var i = 0; i < num_slots; i++) {

                            let app_detail = []
                            let time_slot = parseInt(day_start_stamp);
                            let slot_status = 'available'

                            if (this.checkUnavailable(time_slot, working_plan) == true) {
                                slot_status = 'no-appointments'
                            }

                            if (this.checkIsbooked(time_slot, this.bookedAppointments) != false) {

                                slot_status = 'booked'
                                app_detail = this.checkIsbooked(time_slot, this.bookedAppointments)

                                if (app_detail[0].available_status == 1) {
                                    slot_status = 'unavailable'
                                }
                                else if ((app_detail[0].available_status == 2) || (app_detail[0].available_status == 0 && app_detail[0].contacts == null)) {
                                    slot_status = 'available'
                                }



                            }
                            day_slots_time.push({ slot_time: time_slot, status: slot_status, app_detail: app_detail })
                            day_start_stamp = day_start_stamp + (slot_time * 60000)
                            d_slot_count++;
                        }
                        slot_counts.push(d_slot_count);
                        week_days.push({ day: days[d], date: point_date, slots: day_slots_time, slot_time: slot_time })
                    }
                    this.week_days = week_days;
                    this.max_slots = Math.max.apply(Math, slot_counts);

                })

            /* Calendar Code end*/
        }
        if ($stateParams.timeStamps) {
            let timeStmp = $stateParams.timeStamps
            this.start_week_ts = timeStmp.start
            this.end_week_ts = timeStmp.end
        }
        this.loadcalendar()
        $stateParams.timeStamps = null;
        this.load_default_calendar = function () {
            let API = this.API
            var start_week_ts = this.start_week_ts;
            var end_week_ts = this.end_week_ts;
            var user_id = this.userId = this.userId;
            var $state = this.$state
            swal({
                title: 'Are you sure?',
                text: 'This will load default work calendar. Any changes in current week will be removed!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, Load default Calendar',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {

                let unavailableData = API.service('unset-unvailable', API.all('celendars'))
                unavailableData.post({ start_timestamp: start_week_ts, end_timestamp: end_week_ts, user_id: user_id })
                    .then(() => {
                        swal({
                            title: 'Success',
                            text: 'User calendar has been loaded as default.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {

                            let timeStamp = { start: start_week_ts, end: end_week_ts }
                            $state.go('app.viewcalendar', { userId: user_id, timeStamps: timeStamp }, { reload: true })
                        })
                    })
            })
        }


        /* ****** Get Day open time and close time ****** */

        this.day_open_close_time = function (wp, day) {
            var out = []
            if (typeof (wp[day]) != 'undefined') {
                out.push({ start: wp[day].start, end: wp[day].end })
            }
            else {
                return false;
            }
            return out;
        }
        this.getNumber = function (num) {
            return new Array(num);
        }

        this.getTooltipName = function (status) {
            if (status == 'no-appointments') {
                return 'Break';
            }
            else if (status == 'booked') {
                return 'Booked'
            }
            else if (status == 'available') {
                return 'Available'
            }
            else if (status == 'unavailable') {
                return 'Unavailable'
            }

        }


        /* Add Appointment Modal Window */
        $scope.add_appointment = function (slot) {
            let default_slot_length = $scope.default_slot_length;
            let slot_start = parseInt(slot.slot_time);
            let slot_end = slot_start + (default_slot_length * 60000);
            var app_date = moment(slot_start).format('YYYY-MM-DD');
            var app_timings = { start_time: moment(slot_start).format('LT'), end_time: moment(slot_end).format('LT') }
            if (slot.status == 'available') {
                var provider_id = $scope.provider_id;


                const modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: './views/app/pages/add-appointment-modals/add-app-modal.html',
                    controller: add_app_modalController,
                    resolve: {
                        provider_id: function () {
                            return provider_id;
                        },
                        app_date: function () {
                            return app_date;
                        },
                        app_timings: function () {
                            return app_timings;
                        }
                    }
                });
                return modalInstance;
            }

        }


    }

    toTimestamp(strDate) {
        var datum = moment(strDate).valueOf();
            return datum;
        
    }


    /* Function for check is Slot Booked*/
    checkIsbooked(slotime, bookedApointments) {
        let status_b = false
        let app_info = []
        let slot_start = parseInt(slotime)
        let slot_end = slotime + (this.default_slot_length * 60000)
        for (var i = 0; i < bookedApointments.length; i++) {
           let star_app_time = this.toTimestamp(bookedApointments[i].start_datetime)
            let end_app_time = this.toTimestamp(bookedApointments[i].end_datetime)
          
  if ((slot_start <= star_app_time && slot_end > star_app_time) || (slot_end <= end_app_time && slot_end > star_app_time) || (star_app_time < slot_start && end_app_time > slot_start && end_app_time < slot_end)) {

                status_b = true;
                var datessss = new Date(slotime);
                var app_date_time = moment(datessss).format('YYYY-MM-DD H:mm:ss');
                var ListOfbookedApointments = bookedApointments.filter(function (appintment) {
                    return (appintment.start_datetime == app_date_time && appintment.contact_id!=null && appintment.contact_id!=0);
                });
                if(ListOfbookedApointments.length>0){
                    return ListOfbookedApointments;
                }
                app_info.push(bookedApointments[i])
                break
            }
        
    }

        if (status_b == true) {
            return app_info;
        } else {
            return false
        }
    }

    /* Function for check is day available or not*/
    checkUnavailable(slotime, working_plan) {
        var slot_start = parseInt(slotime)
        var slot_end = slotime + (this.default_slot_length * 60000)
        var week_day = new Date(slotime).getDay()
        var week_time_s = new Date(slotime)
        let day_on = (week_time_s.getMonth() + 1 + "/" + week_time_s.getDate() + "/" + week_time_s.getFullYear()).toString();

        // sunday
        if (week_day == 0) {

            if (typeof (working_plan.sunday_open) != 'undefined') {
                if (working_plan.sunday_open == false) {
                    return true
                }
                /* strat time and end time filter */
                let sun_day_start_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.sunday.start + ":00").toString()))
                let sun_day_end_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.sunday.end + ":00").toString()))
                if (slot_start < sun_day_start_stamp || slot_start >= sun_day_end_stamp) {
                    return true
                }
                var status_b = false;
                if (typeof (working_plan.sunday.breaks) != 'undefined') {
                    for (let b = 0; b < working_plan.sunday.breaks.length; b++) {
                        let break_start = working_plan.sunday.breaks[b].start;
                        let break_end = working_plan.sunday.breaks[b].end;
                        let break_start_stamp = parseInt(this.toTimestamp((day_on + " " + break_start + ":00").toString()))
                        let break_end_stamp = parseInt(this.toTimestamp((day_on + " " + break_end + ":00").toString()))
                        if (this.slotTimeCheck(break_start_stamp, break_end_stamp, slot_start, slot_end)) {
                            status_b = true;
                            break
                        }
                    }
                }
                return status_b
            }

            return true
        }

        // Monday
        if (week_day == 1) {

            if (typeof (working_plan.monday_open) != 'undefined') {
                if (working_plan.monday_open == false) {
                    return true
                }
                /* strat time and end time filter */
                let mon_day_start_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.monday.start + ":00").toString()))
                let mon_day_end_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.monday.end + ":00").toString()))
                if (slot_start < mon_day_start_stamp || slot_start >= mon_day_end_stamp) {
                    return true

                }
                var status_b = false;
                if (typeof (working_plan.monday.breaks) != 'undefined') {
                    for (let b = 0; b < working_plan.monday.breaks.length; b++) {
                        let break_start = working_plan.monday.breaks[b].start;
                        let break_end = working_plan.monday.breaks[b].end;
                        let break_start_stamp = parseInt(this.toTimestamp((day_on + " " + break_start + ":00").toString()))
                        let break_end_stamp = parseInt(this.toTimestamp((day_on + " " + break_end + ":00").toString()))
                        if (this.slotTimeCheck(break_start_stamp, break_end_stamp, slot_start, slot_end)) {
                            status_b = true;
                            break
                        }
                    }
                }
                return status_b

                return false
            }
            return true
        }
        // Tuesday
        if (week_day == 2) {
            if (typeof (working_plan.tuesday_open) != 'undefined') {
                if (working_plan.tuesday_open == false) {
                    return true
                }
                /* strat time and end time filter */
                let tue_day_start_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.tuesday.start + ":00").toString()))
                let tue_day_end_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.tuesday.end + ":00").toString()))
                if (slot_start < tue_day_start_stamp || slot_start >= tue_day_end_stamp) {
                    return true
                }
                var status_b = false;
                if (typeof (working_plan.tuesday.breaks) != 'undefined') {
                    for (let b = 0; b < working_plan.tuesday.breaks.length; b++) {
                        let break_start = working_plan.tuesday.breaks[b].start;
                        let break_end = working_plan.tuesday.breaks[b].end;
                        let break_start_stamp = parseInt(this.toTimestamp((day_on + " " + break_start + ":00").toString()))
                        let break_end_stamp = parseInt(this.toTimestamp((day_on + " " + break_end + ":00").toString()))
                        if (this.slotTimeCheck(break_start_stamp, break_end_stamp, slot_start, slot_end)) {
                            status_b = true;
                            break
                        }
                    }
                }
                return status_b
            }
            return true
        }
        // Wednesday
        if (week_day == 3) {
            if (typeof (working_plan.wednesday_open) != 'undefined') {
                if (working_plan.wednesday_open == false) {
                    return true
                }
                /* start time and end time filter */
                let wed_day_start_stamp = this.toTimestamp((day_on + " " + working_plan.wednesday.start + ":00").toString())
                let wed_day_end_stamp = this.toTimestamp((day_on + " " + working_plan.wednesday.end + ":00").toString())

                if (slot_start < wed_day_start_stamp || slot_start >= wed_day_end_stamp) {
                    return true
                }
                var status_b = false;
                if (typeof (working_plan.wednesday.breaks) != 'undefined') {
                    for (let b = 0; b < working_plan.wednesday.breaks.length; b++) {
                        let break_start = working_plan.wednesday.breaks[b].start;
                        let break_end = working_plan.wednesday.breaks[b].end;
                        let break_start_stamp = parseInt(this.toTimestamp((day_on + " " + break_start + ":00").toString()))
                        let break_end_stamp = parseInt(this.toTimestamp((day_on + " " + break_end + ":00").toString()))
                        if (this.slotTimeCheck(break_start_stamp, break_end_stamp, slot_start, slot_end)) {
                            status_b = true;
                            break
                        }
                    }
                }
                return status_b
            }
            return true
        }
        // thursday
        if (week_day == 4) {
            if (typeof (working_plan.thursday_open) != 'undefined') {
                if (working_plan.thursday_open == false) {
                    return true
                }
                /* strat time and end time filter */
                let thu_day_start_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.thursday.start + ":00").toString()))
                let thu_day_end_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.thursday.end + ":00").toString()))
                if (slot_start < thu_day_start_stamp || slot_start >= thu_day_end_stamp) {
                    return true
                }
                var status_b = false;
                if (typeof (working_plan.thursday.breaks) != 'undefined') {
                    for (let b = 0; b < working_plan.thursday.breaks.length; b++) {
                        let break_start = working_plan.thursday.breaks[b].start;
                        let break_end = working_plan.thursday.breaks[b].end;
                        let break_start_stamp = parseInt(this.toTimestamp((day_on + " " + break_start + ":00").toString()))
                        let break_end_stamp = parseInt(this.toTimestamp((day_on + " " + break_end + ":00").toString()))
                        if (this.slotTimeCheck(break_start_stamp, break_end_stamp, slot_start, slot_end)) {
                            status_b = true;
                            break
                        }
                    }
                }
                return status_b
            }
            return true
        }
        // wednesday
        if (week_day == 5) {
            if (typeof (working_plan.friday_open) != 'undefined') {
                if (working_plan.friday_open == false) {
                    return true
                }
                /* strat time and end time filter */
                let fri_day_start_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.friday.start + ":00").toString()))
                let fir_day_end_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.friday.end + ":00").toString()))
                if (slot_start < fri_day_start_stamp || slot_start >= fir_day_end_stamp) {
                    return true
                }
                var status_b = false;
                if (typeof (working_plan.friday.breaks) != 'undefined') {
                    for (let b = 0; b < working_plan.friday.breaks.length; b++) {
                        let break_start = working_plan.friday.breaks[b].start;
                        let break_end = working_plan.friday.breaks[b].end;
                        let break_start_stamp = parseInt(this.toTimestamp((day_on + " " + break_start + ":00").toString()))
                        let break_end_stamp = parseInt(this.toTimestamp((day_on + " " + break_end + ":00").toString()))
                        if (this.slotTimeCheck(break_start_stamp, break_end_stamp, slot_start, slot_end)) {
                            status_b = true;
                            break
                        }
                    }
                }
                return status_b
            }
            return true
        }
        // saturday
        if (week_day == 6) {
            if (typeof (working_plan.saturday_open) != 'undefined') {
                if (working_plan.saturday_open == false) {
                    return true
                }
                /* strat time and end time filter */
                let sat_day_start_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.saturday.start + ":00").toString()))
                let sat_day_end_stamp = parseInt(this.toTimestamp((day_on + " " + working_plan.saturday.end + ":00").toString()))
                if (slot_start < sat_day_start_stamp || slot_start >= sat_day_end_stamp) {
                    return true
                }
                var status_b = false;
                if (typeof (working_plan.saturday.breaks) != 'undefined') {
                    for (let b = 0; b < working_plan.saturday.breaks.length; b++) {
                        let break_start = working_plan.saturday.breaks[b].start;
                        let break_end = working_plan.saturday.breaks[b].end;
                        let break_start_stamp = parseInt(this.toTimestamp((day_on + " " + break_start + ":00").toString()))
                        let break_end_stamp = parseInt(this.toTimestamp((day_on + " " + break_end + ":00").toString()))
                        if (this.slotTimeCheck(break_start_stamp, break_end_stamp, slot_start, slot_end)) {
                            status_b = true;
                            break
                        }
                    }
                }
                return status_b
            }
            return true
        }
    }

    slotTimeCheck(from, to, check_start, check_end) {
        var fDate, lDate, cDateStart, cDateEnd;
        fDate = from;
        lDate = to;
        cDateStart = check_start;
        cDateEnd = check_end;
        if ((cDateStart < lDate && cDateStart >= fDate) || (cDateEnd < lDate && cDateEnd > fDate)) {
            return true;
        }
        return false;
    }

    getBusinessHours(days, apps) {
        var counter = 0;
        var start_times = [];
        var end_times = [];
        var app_starts = [];
        var app_ends = [];
        var start_t, end_t;
        var app_min = 0;
        var app_max = 0;
        for (var key in days) {
            if (typeof (days[key].start) != 'undefined') {
                start_t = parseFloat(days[key].start.replace(":", ".")).toFixed(2);
                start_times.push(parseFloat(start_t));
                end_t = parseFloat(days[key].end.replace(":", ".")).toFixed(2);
                end_times.push(end_t);
            }
            counter++;
        }
        for (var i = apps.length - 1; i >= 0; i--) {
            if (apps[i].available_status == 2) {
                var start_time_app = new Date(this.toTimestamp(apps[i].start_datetime));
                var end_time_app = new Date(this.toTimestamp(apps[i].end_datetime));
                app_starts.push(start_time_app.getHours() + "." + start_time_app.getMinutes());
                app_ends.push(end_time_app.getHours() + "." + end_time_app.getMinutes());
            }
        }

        Array.prototype.max = function () {
            return Math.max.apply(null, this);
        };
        Array.prototype.min = function () {
            return Math.min.apply(null, this);
        };

        var opening_time = start_times.min().toFixed(2).replace(".", ":");
        var closing_time = end_times.max().toFixed(2).replace(".", ":");
        if (app_starts.length > 0) {

            app_min = app_starts.min();
            app_max = app_ends.max().toFixed(2).replace(".", ":");
            if (app_min < start_times.min()) {
                opening_time = app_min.toFixed(2).replace(".", ":");
            }
            if (app_max > end_times.max()) {
                closing_time = app_max.toFixed(2).replace(".", ":");
            }
        }
        return { opening_time: opening_time, closing_time: closing_time };
    }

    //GET CURRENT DAY START AND END TIME
    getCurrentDayBusinessHours(days, apps, day) {
        var counter = 0;
        var start_times = [];
        var end_times = [];
        var app_starts = [];
        var app_ends = [];
        var start_t, end_t;
        var app_min = 0;
        var app_max = 0;
        var d_open = day + '_open';
        //REMOVE LOOP 

        if (typeof (days[day]) != 'undefined' && days[day] != '' && typeof (days[d_open]) != 'undefined' && days[d_open] != false) {
            start_t = parseFloat(days[day].start.replace(":", ".")).toFixed(2);
            start_times.push(parseFloat(start_t));
            end_t = parseFloat(days[day].end.replace(":", ".")).toFixed(2);
            end_times.push(end_t);
        }

        counter++;



        for (var i = apps.length - 1; i >= 0; i--) {
            // ADD CONDITION FOR CURRENT PASSED DAY LIKE day(this.toTimestamp(apps[i].start_datetime) == day
            if (apps[i].available_status == 2) {

                var start_time_app = new Date(this.toTimestamp(apps[i].start_datetime));
                var end_time_app = new Date(this.toTimestamp(apps[i].end_datetime));
                var week_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                var week_d = week_days[start_time_app.getDay()].toLowerCase();

                if (week_d == day) {

                    app_starts.push(start_time_app.getHours() + "." + start_time_app.getMinutes());
                    app_ends.push(end_time_app.getHours() + "." + end_time_app.getMinutes());
                }
            }
        }

        Array.prototype.max = function () {
            return Math.max.apply(null, this);
        };
        Array.prototype.min = function () {
            return Math.min.apply(null, this);
        };

        var opening_time = start_times.min().toFixed(2).replace(".", ":");
        var closing_time = end_times.max().toFixed(2).replace(".", ":");

        if (app_starts.length > 0) {

            app_min = app_starts.min();
            app_max = app_ends.max().toFixed(2).replace(".", ":");
            if (app_min < start_times.min()) {
                opening_time = app_min.toFixed(2).replace(".", ":");
            }
            if (app_max > end_times.max()) {
                closing_time = app_max.toFixed(2).replace(".", ":");
            }
        }
        return { opening_time: opening_time, closing_time: closing_time };
    }


    openbookinfo(modaldata, start_week_ts, user_id) {
        let $uibModal = this.uibModal

        const modalInstance = $uibModal.open({
            animation: true,
            templateUrl: 'dialog.html',
            controller: modalController,
            resolve: {
                modaldata: function () {
                    return modaldata;
                },
                unavailableweek: function () {
                    return start_week_ts;
                },
                user_id: function () {
                    return user_id;
                }
            }
        });
        return modalInstance;
    }

    load_next_week(week_start_time) {
        this.start_week_ts = this.start_week_ts + 24 * 60 * 60 * 7
        this.end_week_ts = this.end_week_ts + 24 * 60 * 60 * 7
        this.loadcalendar()
    }

    load_prev_week(week_start_time, minimumTtime) {
        if (this.start_week_ts >= this.minimumTtime) {
            this.start_week_ts = this.start_week_ts - 24 * 60 * 60 * 7
            this.end_week_ts = this.end_week_ts - 24 * 60 * 60 * 7
            this.loadcalendar()
        }
    }

    unavailablemodal(start_week_ts, user_id) {
        let $uibModal = this.uibModal
        const modalInstance = $uibModal.open({
            animation: true,
            templateUrl: 'unavailablemodal.html',
            controller: unavailbleModalController,
            resolve: {
                unavailableweek: function () {
                    return start_week_ts;
                },
                user_id: function () {
                    return user_id;
                }
            }
        });
        return modalInstance;
    }
    setDefaultWeekScheduleModal(start_week_ts, user_id) {
        let $uibModal = this.uibModal
        const modalInstance = $uibModal.open({
            animation: true,
            templateUrl: 'setDefaultWeekScheduleModal.html',
            controller: DeafaulrweekScheduleModalController,
            resolve: {
                unavailableweek: function () {
                    return start_week_ts;
                },
                user_id: function () {
                    return user_id;
                }
            }
        });
        return modalInstance;
    }



}


/* Modal for apointment bookeing status */
class modalController {
    constructor($stateParams, $scope, $state, API, unavailableweek, $uibModal, user_id, modaldata, $uibModalInstance) {
        var statuses = API.service('appointmentstatus', API.all('appointments'))
        $scope.unavailableweek = unavailableweek
        $scope.user_id = user_id
        $scope.API = API
        $scope.statuses_in = [];
        $scope.state = $state;
        statuses.getList()
            .then((response) => {
                let statusess = [];
                let roleResponse = response.plain()
                angular.forEach(roleResponse, function (value) {
                    statusess.push({ id: value.id, title: value.title.toString() });
                })

                $scope.statuses_in = statusess;
            })

        $scope.selected_status = modaldata.appointment_status_id;
        'ngInject'
        $scope.modaldata = modaldata[0]
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.ok = function () {
            $uibModalInstance.close(); //); $scope.selected.item);
        };
        $scope.goto_apointment = function (app_id) {
            $state.go('app.viewappointment', { appointmentId: app_id }, { reload: true })
            $uibModalInstance.close();
        }

        $scope.change_status = function (appointId, app_status) {
            let API = $scope.API
            let $state = this.$state
            app_status = angular.element('#select_status_' + appointId).val()
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
                    var user_id = $scope.user_id;
                    var start_week_ts = $scope.unavailableweek;
                    var end_week_ts = $scope.unavailableweek + 24 * 60 * 60 * 6;
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

                                let timeStamp = { start: start_week_ts, end: end_week_ts }
                                $scope.state.go('app.viewcalendar', { userId: user_id, timeStamps: timeStamp }, { reload: true })
                            })
                        }, (response) => {
                            let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                            $scope.state.go('app.viewcalendar', { userId: user_id, timeStamps: timeStamp, alerts: alert }, { reload: true })
                        })
                })
            }


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


class unavailbleModalController {
    constructor($stateParams, $scope, $state, API, $uibModal, unavailableweek, user_id, $uibModalInstance) {
        'ngInject'
        $scope.state = $state;
        $scope.API = API
        $scope.alerts = [];
        $scope.valid_error = false;
        $scope.hstep = 1;
        $scope.mstep = 1;
        $scope.check_valid = false;
        $scope.options = {
            hstep: [1, 2, 3],
            mstep: [1, 5, 10, 15, 25, 30]
        };

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

        $scope.changed = function () {
            // $log.log('Time changed to: ' + $scope.mytime);
        };

        $scope.clear = function () {
            $scope.mytime = null;
        };
        /* Clear Week Function */
        $scope.clearweek = function () {
            var start_week_ts = $scope.unavailableweek;
            var end_week_ts = $scope.unavailableweek + 24 * 60 * 60 * 6;
            var userId = $scope.userId;
            var API = $scope.API
            swal({
                title: 'Are you sure?',
                text: 'This will remove custom schedule from current week. Any changes in current week will be removed!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, Remove custom Schedule',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                let unavailableData = API.service('unset-unvailable', API.all('celendars'))
                unavailableData.post({ start_timestamp: start_week_ts, end_timestamp: end_week_ts, user_id: user_id })
                    .then(() => {
                        swal({
                            title: 'Success',
                            text: 'Week calendar set to default',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            var start_week_ts = $scope.unavailableweek;
                            var end_week_ts = $scope.unavailableweek + 24 * 60 * 60 * 6;
                            let timeStamp = { start: start_week_ts, end: end_week_ts }
                            $scope.state.go('app.viewcalendar', { userId: $scope.userId, timeStamps: timeStamp }, { reload: true })
                            $uibModalInstance.close();
                        })
                    })
            })
        };
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }


        $scope.setCurrentDefault = function () {
            var start_week_ts = $scope.unavailableweek;
            var end_week_ts = $scope.unavailableweek + 24 * 60 * 60 * 6;
            var userId = $scope.userId;
            var API = $scope.API
            swal({
                title: 'Are you sure?',
                text: 'This will update your default schedule to current week schedule.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, Set Current schedule as default schedule.',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                let calendatDefault = API.service('set-current-default', API.all('celendars'))
                calendatDefault.post({ start_timestamp: start_week_ts, end_timestamp: end_week_ts, user_id: user_id })
                    .then(() => {
                        swal({
                            title: 'Success',
                            text: 'Current week has been set as default schedule.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            var start_week_ts = $scope.unavailableweek;
                            var end_week_ts = $scope.unavailableweek + 24 * 60 * 60 * 6;
                            let timeStamp = { start: start_week_ts, end: end_week_ts }
                            $scope.state.go('app.viewcalendar', { userId: $scope.userId, timeStamps: timeStamp }, { reload: true })
                            $uibModalInstance.close();
                        })
                    })
            })
        };

        /* / Clear Week Function */
        $scope.userId = user_id;
        $scope.unavailableweek = unavailableweek;
        $scope.ok = function ($valid, unavailableweek) {
            $scope.check_valid = true;
            if ($valid) {
                $scope.valid_error = false;
                var unavailableData = API.service('set-unvailable', API.all('celendars'))
                var start_time = convertTimeToalter($scope.unavail.time_from);
                var end_time = convertTimeToalter($scope.unavail.time_to);
                var days = $scope.unavail.days;
                var status = $scope.unavail.status;
                function convertTimeToalter(date) {
                    var timeString = '';
                    var options = {
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: false
                    };
                    return timeString = date.toLocaleString('en-US', options);
                }

                unavailableData.post({ days: days, time_from: start_time, time_to: end_time, timestamp: unavailableweek, user_id: user_id, status: status })
                    .then(() => {
                        var start_week_ts = $scope.unavailableweek;
                        var end_week_ts = $scope.unavailableweek + 24 * 60 * 60 * 6;
                        let timeStamp = { start: start_week_ts, end: end_week_ts }
                        $scope.state.go('app.viewcalendar', { userId: $scope.userId, timeStamps: timeStamp }, { reload: true })
                        $uibModalInstance.close();

                    }, (response) => {
                        $scope.valid_error = true;
                    })
            } else {

            }

        };

    }
}

/* Modal for Default Week Schedule status */
class DeafaulrweekScheduleModalController {
    constructor($stateParams, $scope, $state, $location, API, $uibModal, unavailableweek, user_id, $uibModalInstance) {
        'ngInject'
        var uibModalInstance = $uibModalInstance;
        $scope.default_app_lengths = [{ id: 15, title: '15 Min' },
        { id: 30, title: '30 Min' },
        { id: 45, title: '45 Min' },
        { id: 60, title: '1 Hour' },
        { id: 90, title: '1.5 Hours' },
        { id: 120, title: '2 Hours' },
        { id: 180, title: '3 Hours' },
        { id: 240, title: '4 Hours' },
        { id: 300, title: '5 Hours' }
        ];
        $scope.check_valid = false;

        $scope.alerts = [];
        if ($stateParams.alerts) {
            $scope.alerts.push($stateParams.alerts);
        }
        $scope.hstep = 1;
        $scope.mstep = 1;

        $scope.options = {
            hstep: [1, 2, 3],
            mstep: [1, 5, 10, 15, 25, 30]
        };


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

        $scope.changed = function () {

        };

        $scope.clear = function () {
            $scope.mytime = null;
        };
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        $scope.unavailableweek = unavailableweek;
        $scope.formvalid = false;
        $scope.timeerror = false;
        $scope.ok = function ($valid, unavailableweek) {
            $scope.check_valid = true;
            if ($valid) {
                let deafultWeekScheduleData = API.service('set-default-weekschedule', API.all('celendars'))
                let start_time = convertTimeToalter($scope.unavail.time_from);
                let end_time = convertTimeToalter($scope.unavail.time_to);
                let app_length = $scope.app_length;
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
                if (start_time > end_time) {
                    $scope.timeerror = true;
                } else
                    deafultWeekScheduleData.post({ app_length: app_length, days: days, time_from: start_time, time_to: end_time, timestamp: unavailableweek, user_id: user_id })
                        .then((response) => {
                            $scope.alert = { type: 'error', 'title': 'Unavailable Success' };
                            $location.path("/edit-default-celendar/" + user_id);
                            $uibModalInstance.close();
                            $scope.timeerror = false;
                        }),
                        function (response) {
                            $scope.formvalid = true;
                            $uibModalInstance.close();
                            //location.reload();
                        }
            } else {
                $scope.formvalid = true;
            }

        };

    }
}


/* Modal for apointment bookeing status */
class add_app_modalController {
    constructor($window, $http, $stateParams, $scope, $state, API, $uibModal, $uibModalInstance, provider_id, app_date, app_timings, $timeout) {
        $scope.step1 = true;
        this.API = API;
        $scope.contact_info = new Object;
        $scope.provider_error = false;
        $scope.appointment_provider = provider_id;
        $scope.provider_disable = true;
        $scope.calendar_view = true;
        $scope.app_date = app_date;
        $scope.app_time = app_timings.start_time + "-" + app_timings.end_time;
        $scope.app_timings = app_timings;
        $scope.selectedTime = app_timings;
        $scope.scheduling_method = "web";
        $scope.contact_type = "new";
        $scope.country_code = '+1';

        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;
        }, function errorCallback(response) {
        });

        /* Get Apppointment Providers */
        var findProviders = API.service('company-providers', API.all('appointments'))
        findProviders.one('').get()
            .then((response) => {
                $scope.all_providers = response.data.company_providers;
            });
        /* Google Places */
        $scope.city_input_options = {
            types: ['(cities)']
        }

        $scope.country_input_options = {
            types: ['(regions)']
        }
        /* / Google Places */

        $scope.$watchCollection('contact_info.area', function (new_val, old_val) {
            var city_name = new_val.split(',')[0];
            var myEl = angular.element(document.querySelector('#city_name'));
            myEl.val(city_name);
        });



        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        $scope.add_appointment_contact_save = function (isValid) {
            if (isValid) {
                delete $scope.contact_info.area;
                $scope.contact_info['phone_country_code'] = $scope.country_code;
                var contact_info = $scope.contact_info;
                var addContact = API.service('add-contact-modal', API.all('contacts'))
                addContact.post(contact_info)
                    .then((response) => {
                        let res = response.plain();
                        $scope.contact_info.id = res.data.input.id;
                        if (response.data.update) {
                            $scope.step1 = false;
                            $scope.step2 = true;
                        }
                    }, (response) => {
                        let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                        $state.go($state.current, { alerts: alert })
                    })
            }
        }
        $scope.submit_appointment = function () {
            var selected_provider = $scope.appointment_provider;
            var contact_id = $scope.contact_info.id;
            var app_day = $scope.app_date;
            //var app_time_index = $scope.selectedTime;
            var scheduling_method = $scope.scheduling_method;
            var start_app_time = app_day + " " + $scope.app_timings.start_time;
            var end_app_time = app_day + " " + $scope.app_timings.end_time;
            var appointment_reason = $scope.appointment_reason;
            var contact_type = $scope.contact_type;

            var addAppointments = API.service('add-modal-appointment', API.all('appointments'))
            addAppointments.post({
                provider_id: selected_provider
                , start_time: start_app_time
                , end_time: end_app_time
                , contact_id: contact_id
                , scheduling_method: scheduling_method,
                appointment_reason: appointment_reason,
                contact_type: contact_type
            })
                .then((response) => {
                    $scope.step1 = false;
                    $scope.step2 = false;
                    $scope.success_view = true;
                    $state.go('app.viewcalendar', { userId: selected_provider }, { reload: true })
                    $timeout(function () {
                        $uibModalInstance.close();
                    }, 800);
                }, (response) => {
                    let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                    $state.go($state.current, { alerts: alert })
                })
        }

        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/contacts/find-tags?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }
        $scope.$watch('searchStr', function (tmpStr) {
            $scope.searched_val = false;
            $scope.contactSearchResult = {};
            $timeout(function () {
                if (tmpStr === $scope.searchStr) {
                    let searchresults = API.service('search-contacts', API.all('contacts'))
                    searchresults.post({ 'searched_text': $scope.searchStr }).then((response) => {
                        $scope.contactSearchResult = response.data;
                        $scope.searched_val = true;
                    });
                }
            }, 500);
        });

        $scope.selecteMe = function (data) {
            $scope.contact_info = data.contact_info;
            $scope.hide_search_contact = true;
            $scope.step1 = false;
            $scope.step2 = true;
        }

        $scope.search_not_found = function () {
            $scope.add_new_contact = true;
            $scope.hide_search_contact = true;
            $scope.contact_info['first_name'] = $scope.searchStr;
        }

    }

}



export const CalendarUserComponent = {
    templateUrl: './views/app/pages/calendar-user/calendar-user.component.html',
    controller: CalendarUserController,
    controllerAs: 'vm',
    bindings: {}
}
