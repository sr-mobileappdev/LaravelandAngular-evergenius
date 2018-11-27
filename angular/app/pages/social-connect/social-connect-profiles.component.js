class SocialConnectProfilesController {
    constructor(API, $state, $stateParams, $scope, $timeout, $location, $uibModal, AclService) {
        'ngInject'

        this.API = API
        this.$state = $state
        this.alerts = []

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
        var isSocialConnect = API.service('is-profiles-connected', API.all('social'))
        isSocialConnect.one().get()
            .then((response) => {
                let respo = response.plain();
                $scope.network_connects = respo.data;
            });

        /* *********************  Delete Profile ********************* */
        this.remove_network = function (network) {
            let API = this.API
            let $state = this.$state
            var $window = this.$window
            swal({
                title: 'Do you want to disconnect?',
                text: 'You will not be able to post on this network.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, remove it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                API.one('social').one('network', network).remove()
                    .then(function (response) {
                        let data_res = response.plain()
                        swal({
                            title: 'Disconnected!',
                            text: 'Network has been disconnected.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            $state.reload()
                        })
                    })
            })
        }

        /* If facebook has multiple profiles */
        $scope.query_string = $location.search();
        $scope.connect_multiple_profiles = function (profiles_data, type_profiles) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/social-connect/connect-multiple-pages.component.html',
                controller: add_multiple_profiles_modal,
                resolve: {
                    profiles_data: function () {
                        return profiles_data;
                    },
                    type_profiles: function () {
                        return type_profiles;
                    }
                }
            });
            return modalInstance;

        }

        if ($scope.query_string.multiple_pages != undefined) {
            var socialCodes = API.service('social-codes', API.all('social'))
            socialCodes.one().get()
                .then((response) => {
                    var multiple_data = response.plain();
                    var multiple_type = "fb_pages";
                    $scope.connect_multiple_profiles(multiple_data.data, multiple_type);
                });

        }

        if ($scope.query_string.multiple_groups != undefined) {
            var socialCodes = API.service('social-codes', API.all('social'))
            socialCodes.one().get()
                .then((response) => {
                    var multiple_data = response.plain();
                    var multiple_type = "fb_groups";
                    $scope.connect_multiple_profiles(multiple_data.data, multiple_type);
                });

        }

        /* ***************** Week Queue ********************* */

        var QueueScheduleApi = API.service('post-queue-schedule', API.all('social'))
        QueueScheduleApi.one().get()
            .then((response) => {

                let res = response.plain();
                let data = res.data;

                if (data.days != undefined) {
                    $scope.active_days = data.days;
                    $scope.schedule_times = data.times;
                    angular.forEach(data.times, function (value, key) {
                        $scope.parse_schdule_time.push(new Date('10-05-2016 ' + value));
                    });
                } else {
                    $scope.active_days = [];
                }

            });


        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $scope.days_list = days;
        $scope.parse_schdule_time = [];
        $scope.schedule_times = [];

        $scope.add_new_time = function () {
            $scope.schedule_times.push(new Date('10-05-2016 12:00 AM'));
        }
        $scope.is_day_active = function (day) {
            if ($scope.active_days.length > 0) {
                let exists = $scope.active_days.indexOf(day);
                if (exists != undefined && exists !== -1) {
                    return 'active';
                }
            }

            return false;
        }

        /* ------------- Remove Time from list ------------- */
        $scope.remove_week_time = function (id) {
            $scope.schedule_times.splice(id, 1);
            $scope.parse_schdule_time.splice(id, 1);

        }



        $scope.active_days = [];
        $scope.set_queue_day = function (day) {
            let exists = $scope.active_days.indexOf(day);
            if (exists < 0) {
                $scope.active_days.push(day);
            } else {
                $scope.active_days.splice(exists, 1);
            }
            $scope.is_day_active(day);
        }

        $scope.submit_queue_settings = function () {
            $scope.save_settings_queue = true;
            var time_s = null;
            if ($scope.parse_schdule_time != undefined && $scope.parse_schdule_time.length > 0) {
                time_s = [];
                angular.forEach($scope.parse_schdule_time, function (value, key) {
                    time_s.push(moment(value).format('HH:mm'));
                });
            }

            //if ($scope.active_days.length > 0 && time_s != null) {
            var selected_days = $scope.active_days;
            let unavailableData = API.service('post-queue-settings', API.all('social'))
            unavailableData.post({ selected_days: selected_days, times: time_s })
                .then((response) => {
                    $scope.update_schedule_queue = true;
                    $timeout(function () {
                        $scope.update_schedule_queue = false;
                    }, 5000);
                })
            //  }


        }


    }




    $onInit() { }
}

class add_multiple_profiles_modal {
    constructor($window, $http, $stateParams, $scope, $state, API, $uibModal, $uibModalInstance, $timeout, profiles_data, type_profiles) {
        $scope.profiles_data = [];
        $scope.modal_title = "Select Profile";

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }


        if (type_profiles == 'fb_pages') {
            $scope.modal_title = "Select Facebook Page";
            angular.forEach(profiles_data, function (value, key) {
                let net_info = { network_name: 'facebook_pages', net_id: value.id, user_name: value.name, user_name: value.name, token: value.access_token, net_type: "Facebook page" };
                $scope.profiles_data.push(net_info);
            });
        }
        else if (type_profiles == 'fb_groups') {
            $scope.modal_title = "Select Facebook Group";
            angular.forEach(profiles_data, function (value, key) {
                let net_info = { network_name: 'facebook_groups', net_id: value.id, user_name: value.name, user_name: value.name, token: value.access_token, net_type: "Facebook Group" };
                $scope.profiles_data.push(net_info);
            });
        }

        $scope.connect_social_profile = function (profile_details) {
            let connectData = API.service('connect-social-network', API.all('social'))
            connectData.post({ network_details: profile_details })
                .then((response) => {
                    $uibModalInstance.close();
                    $state.go('app.socialconnectprofiles', {}, { reload: true });
                })
        }


    }
}

export const SocialConnectProfilesComponent = {
    templateUrl: './views/app/pages/social-connect/social-connect-profiles.component.html',
    controller: SocialConnectProfilesController,
    controllerAs: 'vm',
    bindings: {}
}
