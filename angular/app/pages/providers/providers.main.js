class ProviderController {
    constructor($scope, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location, $timeout) {
        'ngInject'
        this.$auth = $auth;
        this.$location = $location;
        this.SAAPI = SAAPI;
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.AclService = AclService
        this.$window = $window
        var vm = this;
        $scope.tableId = "manage_providers";
        $scope.select_status = '';
        $scope.select_claim_status = '';
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }

        var token = $window.localStorage.super_admin_token
        this.dtOptions = DTOptionsBuilder.newOptions()
            .withOption('ajax', {
                contentType: 'application/json',
                url: '/api/honestdoctor/honest-posts/providers',
                type: 'post',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization",
                        "Bearer " + token);
                },
                data: function (data, dtInstance) {

                    return JSON.stringify(data);
                }
            })
            .withDataProp('data')
            .withOption('serverSide', true)
            .withOption('processing', true)
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
            .withOption('aaSorting', [[0, 'desc']])
            .withBootstrap()

        this.dtColumns = [

            // DTColumnBuilder.newColumn('id').withTitle('id').withOption('sWidth', '120px'),
            DTColumnBuilder.newColumn('title').withTitle('Title').withOption('sWidth', '120px'),
            DTColumnBuilder.newColumn('status').withTitle('Status').withOption('sWidth', '120px').withClass("capitalize"),


            //DTColumnBuilder.newColumn('email').withTitle('Email').withOption('sWidth', '100px'),
            // DTColumnBuilder.newColumn('province').withTitle('PROVINCE').withOption('sWidth', '100px'),
			DTColumnBuilder.newColumn(null).withTitle('Email').renderWith(function (data) {
                    if (data.email != null && data.email != '') {
                        return data.email;
                    }
                    return '';
                }),
            DTColumnBuilder.newColumn(null).withTitle('Phone').withOption('sWidth', '100px').renderWith(function (data) {
                let phnumber = data.phone
                let Country_code = '+1';
				if(phnumber != null && phnumber!= ''){
					if (Country_code != '') {
						phnumber = phnumber.replace(Country_code, '')
					}
					var numbers = phnumber.replace(/\D/g, ''),
						char = { 0: '(', 3: ') ', 6: ' - ' };
					phnumber = '';
					for (var i = 0; i < numbers.length; i++) {
						phnumber += (char[i] || '') + numbers[i];
					}
					return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.mobile_number}">
									${phnumber}
								</a>`;
					//return phnumber;
				}
				return ``;
            }),
            DTColumnBuilder.newColumn(null).withTitle('Claim Status').withOption('sWidth', '100px').renderWith(function (data) {
                if (data.claim_status != null && data.claim_status != '') {
                    return data.claim_status;
                }
                return '';
            }),
            DTColumnBuilder.newColumn(null).withTitle('Date').renderWith(function (data) {
                if (data.date != null && data.date != '') {
                    return moment(data.date).format('MMM DD YY, hh:mm a');
                }
                return '';
            }).withOption('sWidth', '150px'),
            DTColumnBuilder.newColumn(null).withTitle('Options').withOption('sWidth', '180px').renderWith(function (data) {
                return `
                          <a class="btn btn-xs btn-primary" uib-tooltip="View" href ="${data.link}" target="_blank">
                                <i class="fa fa-eye"></i>
                            </a>                            
                            <a class="btn btn-xs btn-warning"  uib-tooltip="Edit" tooltip-placement="bottom" ng-click="vm.edit_provider(${data.id})">
                                <i class="fa fa-edit"></i>
                            </a>                
                            <button class="btn btn-xs btn-danger"  uib-tooltip="Trash" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
                                <i class="fa fa-trash-o"></i>
                            </button>`
            })
        ]

        this.displayTable = true
        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
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

        this.load_dt = function () {
            var token = $window.localStorage.super_admin_token
            this.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/honestdoctor/honest-posts/providers',
                    type: 'post',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        data.customFilter = { status: $scope.select_status, claim_status: $scope.select_claim_status };
                        return JSON.stringify(data);
                    }
                })
                .withDataProp('data')
                .withOption('serverSide', true)
                .withOption('processing', true)
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
                .withOption('aaSorting', [[0, 'desc']])
                .withBootstrap()

            this.dtColumns = [

                // DTColumnBuilder.newColumn('id').withTitle('id').withOption('sWidth', '120px'),
                DTColumnBuilder.newColumn('title').withTitle('Title').withOption('sWidth', '120px'),
                DTColumnBuilder.newColumn('status').withTitle('Status').withOption('sWidth', '120px').withClass("capitalize"),


               // DTColumnBuilder.newColumn('email').withTitle('Email').withOption('sWidth', '100px'),
                // DTColumnBuilder.newColumn('province').withTitle('PROVINCE').withOption('sWidth', '100px'),
				DTColumnBuilder.newColumn(null).withTitle('Email').renderWith(function (data) {
                    if (data.email != null && data.email != '') {
                        return data.email;
                    }
                    return '';
                }),
                DTColumnBuilder.newColumn(null).withTitle('Phone').withOption('sWidth', '100px').renderWith(function (data) {
				let phnumber = data.phone;
				if(phnumber != null && phnumber!= ''){
                    
                    let Country_code = '+1';

                    if (Country_code != '') {
                        phnumber = phnumber.replace(Country_code, '')
                    }
                    var numbers = phnumber.replace(/\D/g, ''),
                        char = { 0: '(', 3: ') ', 6: ' - ' };
                    phnumber = '';
                    for (var i = 0; i < numbers.length; i++) {
                        phnumber += (char[i] || '') + numbers[i];
                    }
                    return `<a class="" uib-tooltip="" tooltip-placement="bottom" href="tel:${data.mobile_number}">
                                ${phnumber}
                            </a>`;
                    //return phnumber;
					}
					return ``;
                }),
                DTColumnBuilder.newColumn(null).withTitle('Claim Status').withOption('sWidth', '100px').renderWith(function (data) {
                    if (data.claim_status != null && data.claim_status != '') {
                        return data.claim_status;
                    }
                    return '';
                }),
                DTColumnBuilder.newColumn(null).withTitle('Date').renderWith(function (data) {
                    if (data.date != null && data.date != '') {
                        return moment(data.date).format('MMM DD YY, hh:mm a');
                    }
                    return '';
                }).withOption('sWidth', '150px'),
                DTColumnBuilder.newColumn(null).withTitle('Options').withOption('sWidth', '180px').renderWith(function (data) {
                    return `
                          <a class="btn btn-xs btn-primary" uib-tooltip="View" href ="${data.link}" target="_blank">
                                <i class="fa fa-eye"></i>
                            </a>                            
                            <a class="btn btn-xs btn-warning"  uib-tooltip="Edit" tooltip-placement="bottom" ng-click="vm.edit_provider(${data.id})">
                                <i class="fa fa-edit"></i>
                            </a>                
                            <button class="btn btn-xs btn-danger"  uib-tooltip="Trash" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
                                <i class="fa fa-trash-o"></i>
                            </button>`
                })
            ]

            this.displayTable = true
            let createdRow = (row) => {
                $compile(angular.element(row).contents())($scope)
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
            $('#' + $scope.tableId).DataTable().ajax.reload();
        }
        this.load_dt();


        $scope.$watch('select_status', function (status) {
            if (status != undefined) {
                vm.load_dt();
            }
        });
        $scope.$watch('select_claim_status', function (status) {
            if (status != undefined) {
                vm.load_dt();
            }
        });

        this.edit_provider = function (id) {
            $state.go('app.edit-provider', { providerId: id }, { reload: true });


        }
        this.delete = function (contactId) {
            var $state = this.$state
            var state_s = this.$state
            swal({
                title: 'Are you sure?',
                text: 'You want to trash this provider !',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, Trash it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {

                let UserData = SAAPI.service('hd-provider', SAAPI.all('honestdoctor'))
                UserData.one(contactId).remove().then(() => {
                    var $state = this.$state
                    swal({
                        title: 'Trashed!',
                        text: 'Provider successfully trashed',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function () {
                        state_s.reload()
                    })
                })
            })
        }


    }


    $onInit() { }
}

export const ProviderComponent = {
    templateUrl: './views/app/pages/providers/providers.main.html',
    controller: ProviderController,
    controllerAs: 'vm',
    bindings: {}
}