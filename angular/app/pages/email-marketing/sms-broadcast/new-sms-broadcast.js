class NewSmsBroadcastController {
    constructor($scope, API, $rootScope, $uibModal, $http, $auth, $stateParams, $sce, $state, $compile, $timeout, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location) {
        'ngInject'
        var vm = this
        $scope.contactSearchResult = {};
        $scope.template_show = false
        this.API = API
        this.$state = $state
        $scope.show_time_input = false;
        $scope.status_check = false
        $scope.opened = {};
        $scope.button_clicked = false
        $scope.campaignErrorstate = false
        vm.tags = []
        this.smsText = ''
        this.areaForcompany = '+1'
        $scope.country_code = '+1'
        var getTwillioNumber = API.service('twilio-number', API.all('company'));
        getTwillioNumber.one("").get()
            .then((response) => {
                var mob = response.data.twilio_number
                this.from_number = mob.replace($scope.country_code, '')
            })

        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;

        }, function errorCallback(response) {
            // called asynchronously if an error occurs
            // or server returns response with an error status.
        });


        window.localStorage.removeItem("editcampaignInfo")

        $scope.close = function () {
            $scope.show_time_input = false
        }
        $scope.showtimepicker = function (i) {

            if ($scope.show_time_input == true) {
                $scope.show_time_input = false
            } else {
                $scope.show_time_input = true;
            }

        };
        $scope.openCalendar = function (e) {
            e.preventDefault();
            e.stopPropagation();

            $scope.opened.isOpen = true;
        };

        $scope.$watch('schedule_time', function () {
            if ($scope.schedule_time == null || $scope.schedule_time == undefined || $scope.schedule_time == '') {
                $scope.opened.isOpen = true;
            }
        })

        if (window.localStorage.getItem('campaignInfo')) {
            vm.subject_line = JSON.parse($window.localStorage.getItem('campaignInfo')).subject,
                vm.tags = JSON.parse($window.localStorage.getItem('campaignInfo')).tags
        }



        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/email-marketing/find-email-list?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }
        /**********************************Save New campaigns*******************************************/

        this.save_campign = function (isValid, type, status) {
            var timer = ''
            if (type == 'scheduled') {
                timer = moment($scope.schedule_time).format('YYYY-MM-DD H:m:s')
            }
            this.save_type = type
            var $state = this.$state
            var mess = ''
            let Permissions = this.API.service('create', this.API.all('sms-broadcast'))

            if (this.subject_line && this.tags) {
                var Obj = {
                    "title": this.subject_line,
                    "status": status,
                    "body": this.smsText,
                    'from_number': this.from_number,
                    'test_phone_number': this.test_number,
                    "schedule_datetime": timer,
                    "broadcast_lists": this.tags,
                    "save_type": this.save_type,
                    'test_num_country_code': $scope.country_code
                }


                Permissions.post(Obj, {}, {}, { 'Content-type': 'application/json' }).then(function (response) {
                    if (status == 4) {
                        $scope.status_check = true
                        mess = 'SMS Broadcast has been run successfully'
                    }
                    if (type == 'scheduled') {
                        mess = 'SMS Broadcast has been Scheduled at ' + moment(timer).format('MMM Do YYYY, h:mm A') + '!!'
                    }
                    if (type == 'sendsms') {
                        mess = 'SMS Broadcast has been saved and Test SMS Sent Successfully !'
                    }
                    if (type == 'draft') {
                        mess = 'SMS Broadcast has been saved as Draft!'
                    }
                    //
                    var resp = response.plain()
                    swal({
                        title: 'success',
                        text: mess,
                        type: 'success',
                        // confirmButtonColor: '#27b7da',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true,
                        showLoaderOnConfirm: true,
                        html: false

                    }, function () {

                        if (type == 'sendsms') {
                            $state.go('app.editsmsbroadcast', { broadcast_id: resp.data.broadcast_id }, { reload: true })
                        } else {
                            $state.go('app.smsbroadcast', { alerts: alert }, { reload: true });
                        }
                    })
                }, function (response) {
                    $scope.campaignError = 'Something went wrong , Please refresh page and try again'
                    $timeout(function () {
                        $scope.campaignErrorstate = true
                    }, 100);
                    $timeout(function () {
                        $scope.campaignErrorstate = false
                    }, 3000);
                })




            }
        }
        $scope.$on('scanner-started', function (event, args) {
            var text = args.any;
            $scope.insertAtCaret = function (text) {
                var txtarea = document.getElementById('username');
                var scrollPos = txtarea.scrollTop;
                var strPos = 0;
                var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
                    "ff" : (document.selection ? "ie" : false));
                if (br == "ie") {
                    txtarea.focus();
                    var range = document.selection.createRange();
                    range.moveStart('character', -txtarea.value.length);
                    strPos = range.text.length;
                }
                else if (br == "ff") strPos = txtarea.selectionStart;

                var front = (txtarea.value).substring(0, strPos);
                var back = (txtarea.value).substring(strPos, txtarea.value.length);
                txtarea.value = front + text + back;

                strPos = strPos + text.length;
                if (br == "ie") {
                    txtarea.focus();
                    var range = document.selection.createRange();
                    range.moveStart('character', -txtarea.value.length);
                    range.moveStart('character', strPos);
                    range.moveEnd('character', 0);
                    range.select();
                }
                else if (br == "ff") {
                    txtarea.selectionStart = strPos;
                    txtarea.selectionEnd = strPos;
                    txtarea.focus();
                }
                txtarea.scrollTop = scrollPos;
                // angular.element('#username')[0].value += anyThing
            }
            $scope.insertAtCaret(text)

        });
        $scope.gettagsPop = function (array, ss) {
            var positionOF = angular.element('#username').prop("selectionStart")
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'merge-tags.html',
                controller: mergeTagsController,
                resolve: {
                    rangeArray: function () {
                        return positionOF;
                    }

                }
            });
            return modalInstance;
        }


    }


    $onInit() { }
}
class mergeTagsController {
    constructor($window, $http, $rootScope, rangeArray, $stateParams, $scope, $state, API, $uibModal, $timeout, $uibModalInstance) {

        $scope.tags = [{ name: 'Contact First Name', tag: '{$first_name}' }, { name: 'Contact Last Name', tag: '{$last_name}' }, { name: 'Contact Phone Number', tag: '{$phone_number}' }, { name: 'Contact Email', tag: '{$email}' }, { name: 'Company Name', tag: '{$client_name}' }, { name: 'Office Address', tag: '{$location}' }, { name: 'Office Phone', tag: '{$office_phone}' }, { name: 'Website Link', tag: '{$website_link}' }]
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.puttagInEditor = function (tagname) {
            var newTagdetail = ''
            newTagdetail = tagname
            $rootScope.$broadcast('scanner-started', { any: newTagdetail, rangearray: rangeArray });

            $uibModalInstance.close();
        }

    }
}


export const NewSmsBroadcastComponent = {
    templateUrl: './views/app/pages/email-marketing/sms-broadcast/new-sms-broadcast.html',
    controller: NewSmsBroadcastController,
    controllerAs: 'vm',
    bindings: {}
}