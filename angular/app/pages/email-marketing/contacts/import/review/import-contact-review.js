class ImportContactsInfoSubmitController {
    constructor($scope, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, AclService, $location) {
        'ngInject'
        $scope.loading_chat = false
        var token = $window.localStorage.satellizer_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/email-marketing/process-csv-contacts',
                type: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization",
                        "Bearer " + token);
                },
                data: function (data, dtInstance) {
                    var mappeddata = $window.localStorage.getItem('mapped_contacts');
                    var csvfilename = $window.localStorage.getItem('csvfilename');
                    //console.log(mappeddata);
                    data['mapped'] = JSON.parse(mappeddata);
                    data['csvfilename'] = csvfilename;
                    return JSON.stringify(data);
                },
                statusCode: {
                    400: function (data) {
                        //console.log(data.responseText);
                        var dataa = JSON.parse(data.responseText);
                        document.getElementById("error_class").classList.add('alert');
                        document.getElementById("error_class").classList.add('alert-danger');
                        document.getElementById("error_class").innerHTML = dataa.errors.message;

                        let dataaa = []
                        return JSON.stringify(dataaa);
                        //return JSON.stringify(data.errors.message);
                    }
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
                console.log('Columns order has been changed with: ' + this.fnOrder());
            })
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            })
            .withOption('responsive', true)
            .withOption('aaSorting', [
                [0, 'desc']
            ])
            .withBootstrap()

        this.dtColumns = [
            DTColumnBuilder.newColumn(null).withTitle('Name').renderWith(function (data) {

                if (data.first_name != null && (data.last_name == null || data.last_name == 'null') && data.first_name != 'undefined' && data.last_name != 'undefined') {
                    data.last_name = '';
                    return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.contact_id + `">
                    ${data.first_name + " " + data.last_name}</a>`
                }

                else if (data.first_name == null || data.last_name == 'null') {
                    return ``;
                }
                else if (data.first_name == 'undefined' || data.last_name == 'undefined') {
                    return ``;
                }
                else {
                    return `<a class="" uib-tooltip="View" tooltip-placement="bottom"  href="#/contact/` + data.contact_id + `">
                    ${data.first_name + " " + data.last_name}</a>`
                }
            }).notSortable(),
            DTColumnBuilder.newColumn(null).withTitle('Phone').renderWith(function (data) {
                if (data.mobile_number == null || data.mobile_number == '') {
                    return ``;
                }
                let phnumber = data.mobile_number
                let Country_code = data.phone_country_code

                if (Country_code) {
                    phnumber = phnumber.replace(Country_code, '')
                }


                return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.mobile_number}">
                                ${phnumber}
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


        $scope.savedata = function () {
            $scope.loading_chat = true
            let Savecontacts = API.service('save-contacts', API.all('email-marketing'))
            var mapped_contacts = JSON.parse($window.localStorage.getItem('mapped_contacts'));
            var csvfilename = $window.localStorage.getItem('csvfilename');
            var data_request = { 'mapped': mapped_contacts, 'csvfilename': csvfilename };
            Savecontacts.post({ 'mapped': mapped_contacts, 'csvfilename': csvfilename })
                .then((response) => {
                    $scope.loading_chat = false;
                    if (response.status == "fail") {
                        swal("Warning!", response.message, "warning");
                    }
                    else {
                        swal("Your contacts has been saved successfully", "New Contacts : " + response.savedrecords + "\n\n Existing Contacts : " + response.existsrecords + "\n\n Rejected Contacts : " + response.nosavedrecords + "");
                        let alert = { type: 'success', 'title': 'Success!', msg: 'Contacts Imported successfully' }
                        $state.go('app.email-marketing', { alerts: alert, showMessage: true })
                    }
                }), function (response) { $scope.loading_chat = false; }
        }


        let createdRow = (row) => {

            $compile(angular.element(row).contents())($scope)
        }
    }

    $onInit() { }
}

export const ImportContactsInfoSubmitComponent = {
    templateUrl: './views/app/pages/email-marketing/contacts/import/review/import-contact-review.html',
    controller: ImportContactsInfoSubmitController,
    controllerAs: 'vm',
    bindings: {}
}