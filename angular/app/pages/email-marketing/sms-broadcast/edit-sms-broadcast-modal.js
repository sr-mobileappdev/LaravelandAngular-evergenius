class EditSmsBroadcastController {
    constructor($scope, API, $http, $rootScope, $auth, $stateParams, $uibModal, $state, $compile, $timeout, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location, $sce) {
        'ngInject'
        var vm = this;
        $scope.contactSearchResult = {};
        this.API = API;
        this.$state = $state;
        var broadcast_id = $stateParams.broadcast_id;
        $scope.show_time_input = false;
        $scope.opened = {};
        $scope.status_check = false;
        vm.tags = []
        window.localStorage.removeItem("campaignInfo");
        $scope.country_code = '+1'
        $scope.close = function () {
            $scope.show_time_input = false
        }
        this.smsText = ''
        $scope.loadTags = function (query) { //Email tag list
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/email-marketing/find-email-list?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }
        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;

        }, function errorCallback(response) {
            // called asynchronously if an error occurs
            // or server returns response with an error status.
        });

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
        /**********************************Show campaigns details*******************************************/

        let show_campaigns = this.API.service('show/' + broadcast_id, this.API.all('sms-broadcast'))
        show_campaigns.one("").get()
            .then(function (respone) {
                $scope.broad_data = respone.plain().data
                vm.subject_line = $scope.broad_data.title
                vm.test_number = $scope.broad_data.test_phone_number
                vm.tags = $scope.broad_data.broadcast_lists
                vm.from_number = $scope.broad_data.from_number
                vm.smsText = $scope.broad_data.body
                $scope.country_code = $scope.broad_data.test_num_country_code
            })



        /**********************************Save Edit Sms *******************************************/

        this.save = function (isValid, type, status) {
            var timer = ''
            var $state = this.$state
            var mess = ''
            if (type == 'scheduled') {
                timer = moment($scope.schedule_time).format('YYYY-MM-DD H:m:s')
            }


            let Permissions = this.API.service('create/' + broadcast_id, this.API.all('sms-broadcast'))

            this.save_type = type
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
                Permissions.post(Obj).then(function () {
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
                            $state.reload()
                        } else {
                            $state.go('app.smsbroadcast', { alerts: alert }, { reload: true });
                        }


                    })

                }, function (response) {
                    $state.go($state.current, { alerts: alert })

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

export const EditSmsBroadcastComponent = {
    templateUrl: './views/app/pages/email-marketing/sms-broadcast/edit-sms-broadcast-modal.html',
    controller: EditSmsBroadcastController,
    controllerAs: 'vm',
    bindings: {}
}