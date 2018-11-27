class ConversationController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, $filter, $timeout, socket, $uibModal) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.API = API
        this.alerts = []
        this.window = $window;
        this.publishers = [];
        $scope.contactlist = []
        $scope.user_name = '';
        $scope.thread_data = []
        this.start_date = moment().subtract(10, 'days'), moment()
        this.end_date = moment()
        this.min_date = moment().subtract(30, 'days'), moment()
        this.max_date = moment()
        $scope.contact_updated = false;
        $scope.contact_details = [];
        $scope.formSubmitted = false;
        $scope.submit_smsform = false;
        $scope.loading_chat = true;
        $scope.tax = true;
        $scope.show_contact_loader = function () {
            $scope.loading_contacts = true;
        }

        $scope.hide_contact_loader = function () {
            $scope.loading_contacts = false;

        }
        $scope.show_contact_loader();
        $scope.pieRandomColors = ['#2ecc71', '#1abc9c', '#3498db', '#9b59b6', '#34495e', '#16a085', '#27ae60', '#2980b9', '#2980b9', '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22', '#e74c3c', '#ecf0f1', '#d35400', '#c0392b']

        let load_conversation = API.service('conversation-contacts', API.all('conversation'))
        load_conversation.one("").get()
            .then((response) => {
                var response = response.plain().data.conversations;
                $scope.contactlist = response
                if ($scope.contactlist.length > 0) {
                    var contact_id = $scope.contactlist[0].contact_id
                    $scope.getContactDetails(contact_id);

                    $scope.selected_contact_index = 0;
                    $scope.contact_full_name = $scope.contactlist[0].contact_name
                    $scope.contact_name = $scope.contact_full_name.match(/\b(\w)/g).join('')

                    let search_contactname = API.service('contact-conversation', API.all('conversation'))
                    search_contactname.one("").get({ contact_id: contact_id })
                        .then((response) => {
                            var response = response.plain();
                            $scope.thread_data = response.data.contact_conversations
                            $timeout(function () {
                                var scroller = document.getElementById("chat_sms");
                                scroller.scrollTop = scroller.scrollHeight;
                            }, 0, false);
                            $scope.loading_chat = false;
                        });
                    $scope.contact_old_list = response;
                } else {
                    $scope.loading_chat = false;
                }
                $scope.hide_contact_loader();
            });

        $scope.getShortName = function (name) {

            var str = name;
            var matches = str.match(/\b(\w)/g);
            var acronym = matches.join('');
            if (acronym == null) {
                return ''
            } else {

                return acronym;
            }
        }

        $scope.get_conversation = function (index) {
            $scope.selected_contact_index = index;
            $scope.contactlist[index].not_seen_count = null;
            $scope.loading_chat = true;
            var contact_id = $scope.contactlist[index].contact_id
            $scope.getContactDetails(contact_id);
            $scope.contact_full_name = $scope.contactlist[index].contact_name
            $scope.contact_name = $scope.contact_full_name.match(/\b(\w)/g).join('')
            let search_contactname = API.service('contact-conversation', API.all('conversation'))
            search_contactname.one("").get({ contact_id: contact_id })
                .then((response) => {
                    var response = response.plain();
                    $scope.thread_data = response.data.contact_conversations;
                    $timeout(function () {
                        var scroller = document.getElementById("chat_sms");
                        scroller.scrollTop = scroller.scrollHeight;
                    }, 0, false);
                    $scope.loading_chat = false;
                })

            $scope.divsToHide = document.getElementsByClassName("chat-window-sm");
            $scope.divsHide = document.getElementsByClassName("chat-contact-sm");
            if ($(window).width() < 992) {

                for (var i = 0; i < $scope.divsToHide.length; i++) {
                    $scope.divsToHide[i].style.display = "block";
                }

                for (var i = 0; i < $scope.divsHide.length; i++) {
                    $scope.divsHide[i].style.display = "none";
                }
                $scope.chat_contact = function () {

                    for (var i = 0; i < $scope.divsHide.length; i++) {
                        $scope.divsHide[i].style.display = "block";
                    }

                    for (var i = 0; i < $scope.divsToHide.length; i++) {
                        $scope.divsToHide[i].style.display = "none";
                    }
                }
            }


        }


        if (JSON.parse(localStorage.getItem('user_data')).name != '') {
            $scope.user_name = JSON.parse(localStorage.getItem('user_data')).name
        } else {
            $scope.user_name = 'admin'

        }
        $scope.filterValue = function ($event) {
            if (isNaN(String.fromCharCode($event.keyCode))) {
                $event.preventDefault();
            }
        };
        $scope.submitForm = function (valid) {
            if (valid) {
                $scope.formSubmitted = true;
                var mob = $scope.contact_details.mobile_number;
                $scope.contact_details.mobile_number = $scope.contact_details.phone_country_code + mob;
                var contact_id = $scope.contact_details.id;
                let contact = API.service('contact-details-update', API.all('contacts'))
                contact.post($scope.contact_details)
                    .then((response) => {
                        $scope.formSubmitted = false;
                        if ($scope.contact_details.mobile_number) {
                            var mobile = $scope.contact_details.mobile_number.replace($scope.contact_details.phone_country_code, '');
                            $scope.contact_details.mobile_number = mobile;
                        }

                        $scope.contact_updated = true;
                        $timeout(function () {
                            $scope.contact_updated = false;
                        }, 1200);
                        $scope.contact_full_name = $scope.contact_details.first_name + " " + $scope.contact_details.last_name;
                        $scope.contactlist[$scope.selected_contact_index].contact_name = $scope.contact_details.first_name + " " + $scope.contact_details.last_name;

                    })
            }
            // $window.location.reload()

        }
        $scope.view_contact_details = function () {
            $state.go('app.viewcontact', { contactId: $scope.contact_details.id })
        }
        $scope.delay = (function () {
            var timer = 0;
            return function (callback, ms) {
                clearTimeout(timer);
                $timeout.cancel(timer);
                timer = $timeout(callback, ms);
            };
        })();

        $scope.searchContact = function (text) {
            $scope.show_contact_loader();
            $scope.delay(function () {
                var SearchText = $scope.searchKeyword;
                let search_contactname = API.service('conversation-contacts', API.all('conversation'))
                search_contactname.one("").get({ q: SearchText })
                    .then((response) => {

                        var response = response.plain().data.conversations;
                        $scope.contactlist = response
                        $scope.hide_contact_loader();
                    })
            }, 1000);
        }

        $scope.getContactDetails = function (contact_id) {
            let contact = API.service('contact-details', API.all('contacts'))
            contact.one(contact_id).get()
                .then((response) => {
                    var response = response.plain();
                    $scope.contact_details = response.data.contact_details;
                    if ($scope.contact_details.mobile_number) {
                        var mob = $scope.contact_details.mobile_number.replace($scope.contact_details.phone_country_code, '');
                        $scope.contact_details.mobile_number = mob;
                    }

                })
        }
        $scope.country_code = function (code) {
            var phone_country_code = '+1'
            var mob = code.replace(phone_country_code, '')
            return mob
        }
        $scope.sendMessage = function (sms_body_s) {
            $scope.submit_smsform = true;
            var contact_id = $scope.contact_details.id;
            var sms_body = sms_body_s;
            var contact_id = $scope.contact_details.id;
            let sms_api = API.service('send-message', API.all('conversation'))
            sms_api.post({ contact_id: contact_id, sms_body: sms_body })
                .then((response) => {
                    var res = response.plain();
                    $scope.thread_data.push(res.data.sent_sms);
                    var objs = $filter('filter')($scope.contactlist, { contact_id: contact_id })[0];
                    objs.last_sms = sms_body_s;
                    //$scope.sms_body_send = '';
                    $scope.submit_smsform = false;
                    $timeout(function () {
                        var scroller = document.getElementById("chat_sms");
                        scroller.scrollTop = scroller.scrollHeight;
                    }, 0, false);
                })
            //}
            document.getElementById('message').value = ""

        }

        socket.on("chat.message", function (message) {

            var socket_data = JSON.parse(message);
            if (socket_data) {

                var swapArrayElements = function (arr, indexA, indexB) {
                    var temp = arr[indexA];
                    arr[indexA] = arr[indexB];
                    arr[indexB] = temp;
                };
                var index;
                $scope.contactlist.some(function (elem, i) {
                    if (elem.contact_id == socket_data.contact_id) {
                        index = i
                        swapArrayElements($scope.contactlist, 0, index)
                    }
                });

                let load_conversation = API.service('conversation-contacts', API.all('conversation'))
                $scope.show_contact_loader();
                load_conversation.one("").get()
                    .then((response) => {
                        $scope.show_contact_loader();
                        var response = response.plain().data.conversations;
                        $scope.contactlist = response;
                        $scope.hide_contact_loader();
                    });
            }

            if ($scope.contact_details.id == socket_data.contact_id) {
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
                $scope.thread_data.push(new_in_sms);

                $timeout(function () {
                    var scroller = document.getElementById("chat_sms");
                    scroller.scrollTop = scroller.scrollHeight;
                }, 0, false);

                var con_index = $scope.contactlist.findIndex(x => x.contact_id === socket_data.contact_id);
                var last_count = 0;
                if ($scope.contactlist[con_index] != undefined) {
                    last_count = parseInt($scope.contactlist[con_index].not_seen_count);
                    $scope.contactlist[con_index].not_seen_count = last_count + 1;
                }
                if (last_count) {
                    $scope.contactlist[con_index]['not_seen_count'] = last_count;
                }
            }

        });

        $scope.action_modal = function (load_conversation) {

            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'new_modal_window.html',
                controller: NewModalController,
                resolve: {

                }
            });
            return modalInstance;
        }

        $scope.$on('modalSubmit', function (event, data) {
            $scope.contact_full_name = data.modalText[0].contact_name
            $scope.contact_name = $scope.contact_full_name.match(/\b(\w)/g).join('')
            $scope.getContactDetails(data.modalText[0].contact_id)
            let search_contactname = API.service('contact-conversation', API.all('conversation'))
            search_contactname.one("").get({ contact_id: data.modalText[0].contact_id })
                .then((response) => {
                    var response = response.plain();
                    $scope.thread_data = response.data.contact_conversations
                    $timeout(function () {
                        var scroller = document.getElementById("chat_sms");
                        scroller.scrollTop = scroller.scrollHeight;
                    }, 0, false);
                    $scope.loading_chat = false;

                });

            data.modalText[0].sent_time = moment();
            $scope.contactlist.splice(0, 0, data.modalText[0])

        })

    }

    $onInit() { }
}
class NewModalController {
    constructor($stateParams, $http, $scope, $state, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope) {
        'ngInject'
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.show_contact_loader = function () {
            $scope.loading_contacts = true;
        }

        $scope.hide_contact_loader = function () {
            $scope.loading_contacts = false;

        }
        $scope.first_name = "";
        $scope.last_name = "";
        $scope.mobile_number = "";
        $scope.phone_country_code = "+1";
        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;

        }, function errorCallback(response) {
            // called asynchronously if an error occurs
            // or server returns response with an error status.
        });

        $scope.submit_action_form = function () {

            let contact = API.service('add-contact', API.all('contacts'))
            let $state = this.$state
            var $window = this.$window
            contact.post({
                'email': $scope.email,
                'first_name': $scope.first_name,
                'last_name': $scope.last_name,
                'mobile_number': $scope.mobile_number,
                'phone_country_code': $scope.phone_country_code
            }).then(function (response) {
                let data_res = response.plain()
                let alert = { type: 'success', 'title': 'Success!', msg: 'Contact has been added.' }

                $scope.submit_success()
            })
           
        }

        $scope.submit_success = function () {
            $scope.show_contact_loader();
            let search_contactname = API.service('conversation-contacts', API.all('conversation'))
            search_contactname.one("").get({ q: $scope.first_name + " " + $scope.last_name })
                .then((response) => {
                    var response = response.plain().data.conversations;
                    $rootScope.$broadcast('modalSubmit', {
                        modalText: response // send whatever you want
                    });
                    $scope.hide_contact_loader();
                })
            $timeout(function () {
             $uibModalInstance.close();
            }, 300);
        }
    }

}
export const ConversationComponent = {
    templateUrl: './views/app/pages/conversation/main-conversation.html',
    controller: ConversationController,
    controllerAs: 'vm',
    bindings: {}
}