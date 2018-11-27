class ContactListingController {
    constructor($scope, $stateParams, $state, $compile, $uibModal, DTOptionsBuilder, DTColumnBuilder, API, $window, $timeout, AclService) {
        'ngInject'
        this.API = API
        this.$state = $state
        let contactId = $stateParams.contactId

        /***************       Getting  contact list     **************/

        var token = $window.localStorage.satellizer_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/email-marketing/contacts-list/' + contactId,
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
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function (settings, data) {
                localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));

            })
            .withOption('stateLoadCallback', function (settings, data) {
                return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
            })
            .withColReorder()
            //.withColReorderOrder([2, 1, 2])
            .withColReorderOption('iFixedColumnsRight', 1)
            .withColReorderCallback(function () {
            })
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            })
            .withOption('responsive', true)
            .withBootstrap()

        this.dtColumns = [
            DTColumnBuilder.newColumn(null).withTitle('CONTACT NAME').renderWith(function (data) {
                if (data.first_name != null && (data.last_name == null || data.last_name == 'null')) {
                    data.last_name = '';
                    return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.contact_id + `">
                    ${data.first_name + " " + data.last_name}</a>`
                }

                else if (data.first_name == null || data.last_name == 'null') {
                    return ``;
                }
                else {
                    return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.contact_id + `">
                    ${data.first_name + " " + data.last_name}</a>`
                }

            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('EMAIL').renderWith(function (data) {
                if (data.email == null) {
                    return ``;
                }
                return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="mailto:${data.email}">
                                ${data.email}
                            </a>`;
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('PHONE').withOption('sWidth', '100px').renderWith(function (data) {
                if (data.mobile_number == null) {
                    return ``;
                }
                return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.mobile_number}">
                                ${data.mobile_number}
                            </a>`;
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('STATUS').withOption('sWidth', '100px').renderWith(function (data) {

                return `
                ${data.contact_status}`
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('DATE').renderWith(function (data) {
                let dateChange = new Date(data.created_at)
                let createdDate = moment(dateChange).format('MMM Do YYYY, h:mm:ss a')
                return `
                ${createdDate}`
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('ACTIONS').withOption('sWidth', '150px').renderWith(function (data) {

                return `
                <a class="btn btn-xs btn-primary" uib-tooltip="View" href="#/contact/` + data.contact_id + `">
                <i class="fa fa-eye"></i>
            </a>     
                            <a class="btn btn-xs btn-warning"  uib-tooltip="Edit" tooltip-placement="bottom" ng-click="edit_status_modal(${data.id},${data.status_id})" data-toggle="modal" data-target="#add-note">
                                <i class="fa fa-edit"></i>
                            </a>  
                            <button class="btn btn-xs btn-danger"  uib-tooltip="Delete" tooltip-placement="bottom" ng-click="vm.delete(${data.contact_id},${data.list_id})">
                            <i class="fa fa-trash-o"></i>
                        </button>                
                           `
            }).notSortable()

        ]

        this.displayTable = true

        this.dtInstanceCallback = function (dtInstance) {
            this.dtInstance = dtInstance;
            dtInstance.DataTable.on('draw.dt', () => {
                let elements = angular.element("#" + dtInstance.id + " .ng-scope");
                angular.forEach(elements, (element) => {
                    $compile(element)($scope)
                })
            });
        }

        $scope.edit_status_modal = function (id, status_id) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'edit_contact_status.html',
                controller: EditContactStatusController,
                resolve: {
                    editId: function () {
                        return id;
                    },
                    statusId: function () {
                        return status_id;
                    },

                }
            });
            return modalInstance;
        }

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }


        $scope.openSetting = function (list) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/contacts/open-setting-modal.html',
                controller: Opensettingcontroller,
                windowClass: 'subscription-form',
                resolve: {
                    uniqueId: function () {
                        return list.unique_id;
                    }

                }
            });
            return modalInstance;
        }


        $scope.edit_list_modal = function (camp_name) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/campaigns/new-campaigns-modal.html',
                controller: EditAddNewModalController,
                resolve: {
                    name: function () {
                        return camp_name;
                    },
                    contactId: function () {
                        return contactId;
                    }
                }
            });
            return modalInstance;
        }
        $scope.add_new = function (listInfo) { $state.go('app.new-campaigns', { list_info: listInfo }) }
        /***************       Getting  contact list status       **************/

        let contact_list_status = API.service('contact-list-stat/' + contactId, API.all('email-marketing'))
        contact_list_status.one("").get()
            .then((response) => {
                $scope.contact_list_status = response.data.stats

            })
        $scope.import_contact = function (list_info) {
            if (list_info) {
                var obj = {
                    id: list_info.id,
                    name: list_info.name
                }
            } else {
                obj = {}
            }

            $window.localStorage.setItem('list_info', JSON.stringify(obj));

            $state.go('app.emimport-contacts')
        }
        this.delete = function (contactId, listID) {
            let API = this.API
            var $state = this.$state
            var state_s = this.$state
            swal({
                title: 'Are you sure?',
                text: 'This will delete this Contact Profile from this List and cancel all queued emails!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, delete it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {

                API.service('delete-list-user', API.all('email-marketing')).post({ contact_id: contactId, list_id: listID })

                    .then(() => {
                        var $state = this.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Contact Profile has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            state_s.reload()
                        })
                    })
            })
        }

        this.delete_list = function () {
            let API = this.API
            var $state = this.$state
            var state_s = this.$state
            swal({
                title: 'Are you sure?',
                text: 'This will delete this Email List and cancel all queued emails!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, delete it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {

                API.service('list', API.all('email-marketing')).one(contactId).remove()

                    .then(() => {
                        // var $state = this.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Subscription list has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            // state_s.reload()
                            $state.go('app.email-marketing', {}, { reload: true })
                        })
                    })
            })
        }


    }

    $onInit() { }
}

class Opensettingcontroller {
    constructor($stateParams, $scope, uniqueId, $state, $location, API, $uibModal, $uibModalInstance, $timeout, $window) {
        'ngInject'
        $scope.showFirstname = false
        $scope.showPhone = false
        $scope.showMessage = false
        $scope.showLastname = false
        $scope.withTitle = false

        $scope.obj = {}
        $scope.setting = {}
        var title = ''
        var lastName = ''
        var firstName = ''
        var phoneNumber = ''
        var message = ''
        var withTitle = ''

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        $scope.$watch('selectedItem', function () {
            if ($scope.selectedItem == true) {
                $scope.title = true
                $scope.setting.subtitle = 'Subscribe to our mailing list'
                withTitle = $scope.setting.subtitle


                $scope.$watch('setting.subtitle', function () {
                    withTitle = $scope.setting.subtitle
                })

            } else {
                $scope.title = false
                withTitle = ''
            }
        })
        $scope.$watchCollection('setting', function (new_val, old_val) {
            if ($scope.setting.selectedfirstName == true) {
                $scope.showFirstname = true
                firstName = '<div class="form-group"><div class="row"><label for="eg-FNAME" class="col-sm-4"> First Name </label><div class="col-sm-8"><input type="text" value="" name="FNAME" class="form-control" id="eg-FNAME"></div></div></div>'
            } else {
                firstName = ''
                $scope.showFirstname = false
            }
            if ($scope.setting.selectedlastName == true) {
                $scope.showLastname = true
                lastName = '<div class="form-group"><div class="row"><label for="eg-LNAME" class="col-sm-4"> Last Name </label><div class="col-sm-8"><input type="text" value="" name="LNAME" class="form-control" id="eg-LNAME"></div></div></div>'
            } else {
                $scope.showLastname = false
                lastName = ''
            }
            if ($scope.setting.selectedphone == true) {
                $scope.showPhone = true
                phoneNumber = '  <div class="form-group"><div class="row"><label for="eg-PHONE" class="col-sm-4"> Phone </label><div class="col-sm-8"><input type="text" onkeypress="validate(event)" value="" name="PHONE" class="form-control" id="eg-PHONE"></div></div></div>'
            } else {
                $scope.showPhone = false
                phoneNumber = ''
            }
            if ($scope.setting.selectedMessage == true) {
                $scope.showMessage = true
                message = ' <div class="form-group"><div class="row"><label for="eg-MESG" class="col-sm-4"> Message </label><div class="col-sm-8"><textarea type="text" value="" name="MESG" class="form-control" id="eg-MESG"></textarea></div></div></div>'
            } else {
                $scope.showMessage = false
                message = ''
            }

            var pathSpecifier = '<script type="text/javascript" src = "' + $window.localStorage.application_url + '/js/eg-subcriptionform.js"></script>';
            var csspathSpecifier = '<link rel="stylesheet" href="' + $window.localStorage.application_url + '/css/form.css">';

            $scope.obj.embedText = '' + csspathSpecifier + '<div id="eg_embed_signup"><form  class="form-horizontal" name="subscriptionform" ><div id="eg_embed_signup_scroll"><h2>' + withTitle + '</h2>  <div class="response" id="eg-success" style="display:none"><p id="eg-success-response" class="alert alert-success"></p></div><div class="response" id="eg-error" style="display:none"><p id="eg-error-response" class="alert alert-danger"></p></div><div class="form-group"><div class="row"><label for="eg-email" class="col-sm-4">Email Address* </label><div class="col-sm-8"><input type="email" value="" name="EMAIL" class="form-control" id="eg-EMAIL"></div></div></div>' + firstName + '' + lastName + ' ' + phoneNumber + '' + message + '<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_0779e0ff5b6b2c6d1ed0ab2fc_ea40b8a92d" tabindex="-1" value=""></div><div class="row"><div class="indicates-required col-8 col-sm-8"><span class="asterisk">*</span> indicates required</div><div class="col-4 col-sm-4"><input type="button" value="Subscribe" name="subscribe" id="eg-embedded-subscribe" onclick="return submitForm(\'' + $window.localStorage.application_url + '\',\'' + uniqueId + '\')" class="btn btn-primary"></div></div></div></form></div>' + pathSpecifier + ''
        })


    }
}

class EditAddNewModalController {
    constructor($stateParams, $scope, $state, name, contactId, $location, API, $uibModal, $uibModalInstance, $timeout, $window) {
        'ngInject'
        $scope.edit_modal = true
        $scope.list_name = name
        var id = contactId
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        /********************************* Submit Modal Window For Creating Email Listings **************************************/

        $scope.submit_action_form = function () {
            var list_name = $scope.list_name;
            var addComment = API.service('edit-list/' + id, API.all('email-marketing'));
            addComment.post({
                name: list_name,
            })
                .then((response) => {
                    // $scope.submit_success();
                    $uibModalInstance.close();

                });
            $state.go($state.current, {}, { reload: true })
        }

    }
}

class EditContactStatusController {
    constructor($stateParams, $scope, $window, $state, $location, API, $uibModal, $uibModalInstance, $timeout, editId, statusId) {
        'ngInject'
        var uibModalInstance = $uibModalInstance;
        var conatctId = editId
        $scope.statusId = statusId
        $scope.status_selected = $scope.statusId
        $scope.form_submitted = false;
        let status_list = API.service('list-statuses', API.all('email-marketing'))
        status_list.one("").get()
            .then((response) => {
                var response = response.plain().data.statuses
                $scope.selected_status = response
            })

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.submit_edit_status_form = function () {
            var status = $scope.selected_status;
            $scope.$watch('status_selected', function () {
                $scope.statusId = $scope.status_selected
                let contact_list = API.service('contact-status/' + conatctId, API.all('email-marketing'))
                contact_list.one().put({ status: $scope.statusId })
                    .then((response) => {
                        $scope.form_submitted = true;
                        $timeout(function () {
                            $uibModalInstance.close();
                            $state.reload()
                        }, 3000);

                    })
            })

        }


    }

}

export const ContactListingsComponent = {
    templateUrl: './views/app/pages/email-marketing/contacts/contact-listing.html',
    controller: ContactListingController,
    controllerAs: 'vm',
    bindings: {}
}
