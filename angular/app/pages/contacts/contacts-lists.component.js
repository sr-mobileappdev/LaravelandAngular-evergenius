class ContactsListsController {
    constructor($scope, $stateParams, $state, $http, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, $timeout, AclService) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.API = API
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        this.user_role = this.roles[0]
        this.alerts = []
        $scope.custom_search_terms = {}
        $scope.dtInstance = {};
        var vm = this
        if (!this.can('view.contacts')) {
            $state.go('app.unauthorizedAccess');
        }

        window.localStorage.removeItem("list_info")
        if ($stateParams.alerts) {
            $scope.showMessage = $stateParams.alerts;
            this.alerts.push($stateParams.alerts)
            $timeout(function () {
                $scope.showMessage = false;
            }, 3000);
        }

        /* Stages */
        let stages = API.service('stages', API.all('leads'))
        stages.one().get()
            .then((response) => {
                $scope.lead_stages = response.plain().data.stages

            })
        /* Sources */
        let sources = API.service('sources', API.all('contacts'))
        sources.one().get()
            .then((response) => {
                $scope.sources = response.plain().data.sources
            })

        /* Assignees */
        let lead_assignees = API.service('lead-assignees', API.all('leads'))
        lead_assignees.one().get()
            .then((response) => {
                var assines = response.plain();
                $scope.assines = assines.data.lead_assignees;
            })


        /* Load Tags */
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/contacts/find-tags?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

        $scope.searchContacts = function () {
            var tags = $scope.selected_tags;
            //var source = $scope.selected_source;
            var elSource = angular.element(document.querySelector('#selected_source'));
            var source = elSource.val();
            var elass = angular.element(document.querySelector('#selected_assine'));
            var assignee = elass.val();
            var elstage = angular.element(document.querySelector('#selected_stage'));
            var stage = elstage.val();
            delete $scope.custom_search_terms.tags
            delete $scope.custom_search_terms.source
            delete $scope.custom_search_terms.stage
            delete $scope.custom_search_terms.assignee
            var elSource = angular.element(document.querySelector('#selectSource'));
            var tag = elSource.val();
            if (tag != undefined && tag != '') {
                $scope.custom_search_terms.tags = tag;
            }
            if (source != undefined && source != '') {
                $scope.custom_search_terms.source = source;
            }
            if (stage != undefined && stage != '') {
                $scope.custom_search_terms.stage = stage;
            }
            if (assignee != undefined && assignee != '') {
                $scope.custom_search_terms.assignee = assignee;
            }

            vm.load_table();
        }

        this.load_table = function () {
            $scope.tableId = "contact_table"
            var token = $window.localStorage.satellizer_token
            var custom_search_data = $scope.custom_search_terms;

            this.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/contacts',
                    type: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        data.customFilter = custom_search_data;
                        $scope.data = data;
                        return JSON.stringify(data);
                    },
                    error: function (err) {
                        let data = []
                        return JSON.stringify(data);
                    }
                })
                .withDataProp('data')
                // .withOption('serverSide', true)
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
                .withOption('aaSorting', [
                    [0, 'desc']
                ])
                .withDisplayLength(25)
                .withBootstrap()

            this.dtColumns = [
                DTColumnBuilder.newColumn(null)
                    .withTitle("Delete")
                    .notSortable().withOption("searchable", false)
                    .renderWith(function (data) {
                        if (data.id) {
                            return "<input type='checkbox' class='checkboxes' name='Id' value='" + data.id + "' ng-model='selectedRow[" + data.id + "]' ng-checked='all'/>";
                        }
                    }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Name').renderWith(function (data) {

                    if (data.first_name != null && (data.last_name == null || data.last_name == 'null')) {
                        return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.id + `">
                    ${data.first_name}</a>`
                    }
                    else if (data.first_name == null && data.last_name == null) {
                        return ``;
                    }
                    else {
                        return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.id + `">
                    ${data.first_name + " " + data.last_name}</a>`
                    }
                    // return data.first_name + " " +data.last_name;
                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Phone').renderWith(function (data) {
                    if (data.mobile_number == null) {
                        return ``;
                    }
                    var Country_code = '';
                    let phnumber = data.mobile_number
                    if (phnumber != undefined && phnumber != null) {
                        if (data.phone_country_code != undefined && data.phone_country_code != null) {
                            Country_code = data.phone_country_code
                        }
                        if (data.phone_country_code != undefined && Country_code != '' && data.phone_country_code != null) {
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
                    //return data.phone_country_code;
                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('Email').withOption('sWidth', '100px').renderWith(function (data) {
                    if (data.email == null) {
                        return ``;
                    }
                    return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="mailto:${data.email}">
                                ${data.email}
                            </a>`;
                }).notSortable().withOption("searchable", true),
                DTColumnBuilder.newColumn('city').withTitle('City').notSortable(),
                DTColumnBuilder.newColumn('gender').withTitle('Gender').notSortable(),

                DTColumnBuilder.newColumn(null).withTitle('Options').withOption('sWidth', '150px').renderWith(function (data) {
                    return `
                        <a class="btn btn-xs btn-primary" uib-tooltip="View" href="#/contact/` + data.id + `">
                                <i class="fa fa-eye"></i>
                            </a>                
                            <a class="btn btn-xs btn-warning" ng-show="vm.can('add.edit.contacts')" uib-tooltip="Edit" tooltip-placement="bottom" href="#/contact-edit/` + data.id + `">
                                <i class="fa fa-edit"></i>
                            </a>                
                            <button class="btn btn-xs btn-danger" ng-show="vm.can('delete.contacts')" uib-tooltip="Delete" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
                                <i class="fa fa-trash-o"></i>
                            </button>`
                }).notSortable()

            ]
            $scope.tableSelection = {};
            $scope.isAll = false;
            // $scope.data[selected] = false;
            var toggleStatus = !$scope.isAllSelected;
            $scope.selectedRow = {};
            $scope.deleteallcheck = function(){
                angular.forEach($scope.selectedRow, function(selected, check) {
                    if (selected) {
                        // vm.delete(check);
                        vm.multi_del($scope.selectedRow);
                        // console.log($scope.selectedRow);
                    }
                });
            }
            $('#' + $scope.tableId).DataTable().ajax.reload();
        }

        this.load_table();

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

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }

    }
    toggleOne() {
        let $state = this.$state
        var sele = angular.element($('.selected_val')).serializeArray();
        if (sele.length === 0) {
            this.isdelseleted = false;
        } else {
            this.isdelseleted = true;
        }
    }

    delete(contactId) {
        let API = this.API
        var $state = this.$state
        var state_s = this.$state
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
            API.one('contacts').one('contact', contactId).remove()
                .then(() => {
                    var $state = this.$state
                    swal({
                        title: 'Deleted!',
                        text: 'Contact has been deleted.',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function () {
                        state_s.reload()
                    })
                })
        })
    }
    multi_del(arry) {
        let API = this.API
        var $state = this.$state
        var state_s = this.$state
        swal({
            title: 'Are you sure?',
            text: 'You will not be able to recover this data!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#27b7da',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: false,
            html: false
        }, function () {
            angular.forEach(arry, function(selected, check) {
                API.one('contacts').one('contact', check).remove()
            })
            var $state = this.$state;
            swal({
                title: 'Deleted!',
                text: 'Contact has been deleted.',
                type: 'success',
                confirmButtonText: 'OK',
                closeOnConfirm: true
            }, function () {
                state_s.reload();
            })
        })
    }

    $onInit() { }
}

export const ContactsListComponent = {
    templateUrl: './views/app/pages/contacts/contactslists.component.html',
    controller: ContactsListsController,
    controllerAs: 'vm',
    bindings: {}
}
