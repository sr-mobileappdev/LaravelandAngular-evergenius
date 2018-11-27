class AppointmentsListsController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $filter, $window, $uibModal, AclService) {
        'ngInject'


        this.API = API
        this.$state = $state
        this.$filter = $filter
        $scope.API = API
        $scope.$state = $state
        $scope.$filter = $filter
        var systemStatus = []
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        this.user_role = this.roles[0]
        $scope.selected_status = []
        var sele_id = 0;
        var statusoptions = ''
        let Appointments = this.API.service('appointments')
        var statuses = API.service('appointmentstatus', API.all('appointments'))


        if (!this.can('manage.appointments')) {
            $state.go('app.unauthorizedAccess');
        }

        $scope.statuses_in = [];
        statuses.getList()
            .then((response) => {
                let statusess = [];
                let roleResponse = response.plain()
                angular.forEach(roleResponse, function (value) {
                    statusess.push({ id: value.id, title: value.title.toString() });

                })
                $scope.statuses = statusess;
            })

        $scope.dtColumnDefs = {
            "defaultContent": " "
        };

        var token = $window.localStorage.satellizer_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/appointments',
                type: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization",
                        "Bearer " + token);
                },
                data: function (data, dtInstance) {
                    return JSON.stringify(data);
                },
                error: function (err) {
                    let data = []
                    return JSON.stringify(data);
                }
            })
            .withDataProp('data')
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('displayLength', 20)
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            })
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function (settings, data) {
                localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));
            })
            .withOption('stateLoadCallback', function (settings, data) {
                return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
            })
            .withColReorder()
            .withColReorderOption('iFixedColumnsRight', 1)
            .withColReorderCallback(function () {
            })
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            })
            //.withOption('aaSorting', [[4, 'desc']])
            .withOption('responsive', true)
            .withBootstrap()

        this.dtColumns = [
            DTColumnBuilder.newColumn(null).withTitle('Action').withOption().renderWith(function (data) {
                var selected_stat = data.appointment_status_id;
                selected_stat = selected_stat.toString();

                $scope.selected_status.push({ id: sele_id, value: data.appointment_status_id.toString() });
                let status_options = '<div class="select-wrapper"><select ng-model="' + selected_stat + '" id="select_status_' + data.id + '" ng-change="testnnns"class="selects_status_app" ><option value="0">Select</option>';

                angular.forEach($scope.statuses, function (value) {
                    status_options = status_options + "<option value='" + value.id + "'>" + value.title + "</option>";
                })
                status_options = status_options + " </select></div>";
                var ret = `
                        {{selected_status[515].value}}
                        <div class="select-wrapper">
                            <select ng-change="change_status(${data.id},'{{vm.status[${data.id}]}}')" ng-show="vm.can('update.appointments')" ng-model="selected_status[${sele_id}].value" id="select_status_${data.id}"><option value="0">Select</option>
                            <option ng-repeat="x in statuses"  value="{{x.id}}">{{x.title}}</option>
                            </select>
                            <span ng-show="!vm.can('update.appointments')">{{find_appointment_status(${data.appointment_status_id}, statuses)}}</span>
                        </div>`;
                sele_id++;
                return ret;
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Provider').renderWith(function (data) {
                //if(this.can('add.provider')){
                return `<a ng-show="vm.can('add.provider')" class="" uib-tooltip="View Provider" tooltip-placement="bottom" ui-sref="app.useredit({userId:${data.provider_user_id}})">
                                ${data.provider_name}
                            </a> <span ng-show="!vm.can('add.provider')" >${data.provider_name}</span>`
                //}

            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Name').renderWith(function (data) {
                return `<a class="" ng-show="vm.can('view.contacts')" uib-tooltip="View Contact" tooltip-placement="bottom" ui-sref="app.viewcontact({contactId: ${data.contact_id}})">
                                ${data.contact_name}
                            </a><span ng-show="!vm.can('view.contacts')"> ${data.contact_name} </span>`;
            }).notSortable(),
            DTColumnBuilder.newColumn('book_datetime').withTitle('Date Received').renderWith(function (data) {
                return moment(data).format('MMM DD YY, hh:mm a');
            }).notSortable(),
            DTColumnBuilder.newColumn('start_datetime').withTitle('Requested Time').renderWith(function (data) {
                return moment(data).format('MMM DD YY, hh:mm a');
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Mobile').renderWith(function (data) {
                if (data.mobile_number == null) {
                    return ``;
                }
                var Country_code = '';
                let phnumber = data.mobile_number
                if (phnumber != undefined && phnumber != null) {
                    if (data.phone_country_code != undefined && data.phone_country_code != null) {
                        Country_code = data.phone_country_code
                    }
                    if (Country_code != '') {
                        phnumber = phnumber.replace(Country_code, '')
                    }
                    var numbers = phnumber.replace(/\D/g, ''),
                        char = { 0: '(', 3: ') ', 6: ' - ' };
                    phnumber = '';
                    for (var i = 0; i < numbers.length; i++) {
                        phnumber += (char[i] || '') + numbers[i];
                    }

                } else {
                    phnumber = '';
                }

                return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.mobile_number}">
                                ${Country_code} ${phnumber}
                            </a>`;

            }).notSortable(),
            /* DTColumnBuilder.newColumn('email').withTitle('Email'),*/
            DTColumnBuilder.newColumn('contact_type').withTitle('Patient Type').renderWith(function (data) {
                return data;
            }).withClass('patient_type').notSortable(),
            DTColumnBuilder.newColumn('scheduling_method').withTitle('Scheduling Method').renderWith(function (data) {
                var fa_icon = 'fa-times';
                if (data == 'web') {
                    fa_icon = 'fa-desktop';
                } else if (data == 'phone') {
                    fa_icon = 'fa-phone';
                } else {
                    fa_icon = 'fa-times';
                }
                return '<i class="fa ' + fa_icon + '" ></i>';

            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Action').renderWith(function (data) {
                return `
                <a class="btn btn-xs btn-primary" uib-tooltip="View" tooltip-placement="bottom" ui-sref="app.viewappointment({appointmentId: ` + data.id + `})">
                    <i class="fa fa-eye"></i>
                </a>
                
                <button class="btn btn-xs btn-danger" uib-tooltip="Delete" ng-show="vm.can('delete.appointments')" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
                    <i class="fa fa-trash-o"></i>
                </button>`;
            }).withOption('width', '100px').notSortable()
        ]

        $scope.find_appointment_status = function (app_id, statuses) {
            //return 'App Status';
            if (app_id != 0 && statuses != undefined) {
                var index = statuses.findIndex(x => x.id === app_id);
                return statuses[index].title;
            }
            return 'Not updated';
        }

        this.displayTable = true

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }
        let dateRecived = (data) => {
            return moment(data.book_datetime).format('ddd, MMM DD YYYY, hh:mm a');
        }
        let formatphone = (data) => {
            let phnumber = data.contacts.mobile_number
            let Country_code = data.contacts.phone_country_code

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

        let dateReq = (data) => {
            return moment(data.start_datetime).format('ddd MMM DD YYYY, hh:mm a');
        }
        let actionsHtml = (data) => {
            return `
                <a class="btn btn-xs btn-primary" uib-tooltip="View" tooltip-placement="bottom" ui-sref="app.viewappointment({appointmentId: ${data.id}})">
                    <i class="fa fa-eye"></i>
                </a>
                
               <!-- <button class="btn btn-round-xs btn-danger" ng-click="vm.delete(${data.id})">
                    <i class="fa fa-trash-o"></i>
                </button>-->`
        }

        let existingCheckHtml = (data) => {
            if (data.contacts.is_existing == 0) {
                return `New`;
            } else {
                return `Existing`;
            }

        }
        let action_required = (data) => {
            var selected_status = "";


            var selected_stat = data.appointment_status_id;
            selected_stat = selected_stat.toString();
            $scope.selected_status.push({ id: sele_id, value: data.appointment_status_id.toString() });
            var ret = `<div class="select-wrapper"><select ng-change="vm.change_status(${data.id},'{{vm.status[${data.id}]}}')" ng-model="vm.selected_status[${sele_id}].value" id="select_status_${data.id}"><option value="0">Select</option>
        <option ng-repeat="x in vm.statuses"  value="{{x.id}}">{{x.title}}</option>
        </select></div>`;
            sele_id++;
            return ret;
        }
        let scheulingMethodHtml = (data) => {
            var fa_icon = 'fa-times';
            if (data.scheduling_method == 'web') {
                fa_icon = 'fa-desktop';
            } else if (data.scheduling_method == 'phone') {
                fa_icon = 'fa-phone';
            } else {
                fa_icon = 'fa-times';
            }
            return '<i class="fa ' + fa_icon + '" ></i>';
        }

        let fullNameHtml = (data) => {
            return `
              ${data.contacts.first_name} ${data.contacts.last_name}
      `;
        }
        $scope.change_status = function (appointId, app_status) {

            let API = $scope.API
            let $state = $scope.$state
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


        /* Add Appointment Modal Window */
        $scope.add_appointment = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/add-appointment-modals/add-app-modal.html',
                controller: add_app_modalController,
                resolve: {
                    /* modaldata: function() {
                         return modaldata;
                     },
                     unavailableweek: function() {
                         return start_week_ts;
                     },
                     user_id: function() {
                         return user_id;
                     }*/
                }
            });
            return modalInstance;
        }

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

    delete(userId) {
        let API = this.API
        let $state = this.$state

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
            let UserData = API.service('appointment', API.all('appointments'))
            UserData.one(userId).remove()
                .then(() => {
                    swal({
                        title: 'Deleted!',
                        text: 'Appointment has been deleted.',
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

                            //$scope.selectedTime = app_time;
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
                    $state.go('app.appointments', { alerts: alert }, { reload: true })
                    $timeout(function () {
                        $uibModalInstance.close();
                    }, 800);
                }, (response) => {
                    let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                    $state.go('app.appointments', { alerts: alert });
                })
        }
    }

}


/* Modal for apointment bookeing status */
class add_app_modalController {
    constructor($window, $http, $stateParams, $scope, $state, API, $uibModal, $uibModalInstance, $timeout) {
        $scope.rescheduleData = false
        $scope.step1 = true;
        this.API = API;
        $scope.contact_info = new Object;
        $scope.provider_error = false;
        $scope.scheduling_method = "web";
        $scope.contact_type = "new";
        $scope.country_code = '+1';
        /* Google Places */
        $scope.city_input_options = {
            types: ['(cities)']
        }

        $scope.country_input_options = {
            types: ['(regions)']
        }
        /* / Google Places */

        $scope.$watchCollection('contact_info.area', function (new_val, old_val) {
            if (new_val){
                var city_name = new_val.split(',')[0];
                var myEl = angular.element(document.querySelector('#city_name'));
                myEl.val(city_name);
            }
          
        });

        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;
        }, function errorCallback(response) {
        });


        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        /* Get Apppointment Providers */
        var findProviders = API.service('company-providers', API.all('appointments'))
        findProviders.one('').get()
            .then((response) => {
                $scope.all_providers = response.data.company_providers;
            });


        $scope.add_appointment_contact_save = function (isValid) {
            $scope.add_contact_form = true;
            if (isValid) {
                delete $scope.contact_info.area;
                $scope.contact_info['phone_country_code'] = $scope.country_code;
                var contact_info = $scope.contact_info;

                var elSource = angular.element(document.querySelector('#selectSource'));
                contact_info.source = elSource.val();
                var elTags = angular.element(document.querySelector('#selectTags'));
                contact_info.tag = elTags.val();

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
                contact_type: contact_type
            })
                .then((response) => {
                    $scope.step1 = false;
                    $scope.step2 = false;
                    $scope.success_view = true;
                    $state.go('app.appointments', { alerts: alert }, { reload: true })
                    $timeout(function () {
                        $uibModalInstance.close();
                    }, 800);
                }, (response) => {
                    let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                    $state.go('app.appointments', { alerts: alert });
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

export const AppointmentsListComponent = {
    templateUrl: './views/app/pages/appointments-list/appointmentslists.component.html',
    controller: AppointmentsListsController,
    controllerAs: 'vm',
    bindings: {}
}