class NavHeaderController {
    constructor($scope, SAAPI, $auth, $stateParams, $rootScope, $state, API, unauthorizedService, ContextService, $window, AclService, $sce, $location, $timeout, $uibModal) {
        'ngInject'
        let navHeader = this
        this.location = $location.path()
        $scope.user_data = {}
        this.isadmin = this.location.indexOf('admin') == '-1' ? 0 : 1;
        ContextService.me(function (data) {
            navHeader.userData = data
            $scope.user_data = data;
        });
        
        if($window.localStorage.super_admin_token){
            $scope.admin_companies =[];
        }

        $scope.$watch(function () {
            return $window.localStorage.satellizer_token;
        }, function (newCodes, oldCodes) {
            if (newCodes!=oldCodes) {
                $window.location.reload();
               // $state.reload();
            }
        });
    
        $scope.userData = { name: '' }
        $scope.userData.avatar = ''
        $scope.sa_selected_company = false;
        this.sa_selected_company = false;
        $scope.selected_admin_Company = false;
        $scope.user_company_details = new Object;
        $scope.user_company_details.name = "";
        $scope.profileShow = true
        var vm = this;
        let notifications = API.service('user-notifications', API.all('users'))
        notifications.one("").get()
            .then((response) => {
                var data_res = response.plain();
                vm.notification_list = data_res;
                vm.notification_count = data_res.length;
            });
        vm.contactSearchResult = {};
        /**Refresh notification**/
        $scope.toggle_header_bar = function($event){
            $event.stopPropagation();
            if ($window.localStorage.super_admin_user_data) {
            $scope.super_admin_user_data = JSON.parse($window.localStorage.super_admin_user_data);
            let UserData = SAAPI.service('impersonate-companies', SAAPI.all('superadmin'));
            UserData.one($scope.super_admin_user_data.id).get()
                .then((response) => {
                    $scope.admin_companies = response.data.admin_compnies;
                });
            }
        }
        this.can = AclService.can
        this.roles = AclService.getRoles()
        this.hasRoles = AclService.hasAnyRole
        setInterval(function () {
            let notifications = API.service('user-notifications', API.all('users'))
            notifications.one("").get()
                .then((response) => {
                    var data_res = response.plain();
                    vm.notification_list = data_res;
                    vm.notification_count = data_res.length;
                });
        }, 120000);

        $scope.uCanTrust = function (string) {
            return $sce.trustAsHtml(string);
        }

        $scope.clearnotifications = function () {
            let clearnotify = API.service('notification-last-seen', API.all('users'))
            clearnotify.one("").get().then((response) => {
                vm.notification_count = 0;
            });
        }

        $scope.close_block = function () {
            angular.element(".notifications-menu").removeClass("open");
            angular.element(".dropdown-toggle").attr("aria-expanded", false);


        }
        if ($window.localStorage.super_admin_user_data && !$window.localStorage.user_company_details) {
            $scope.userData.name = JSON.parse($window.localStorage.super_admin_user_data).name
            $scope.userData.avatar = JSON.parse($window.localStorage.super_admin_user_data).avatar
        }else if ($window.localStorage.super_admin_user_data && $window.localStorage.user_company_details) {
            $scope.userData.name = JSON.parse($window.localStorage.super_admin_user_data).name
            $scope.userData.avatar = JSON.parse($window.localStorage.super_admin_user_data).avatar
            $scope.user_company_details = JSON.parse($window.localStorage.user_company_details);
        } else if ($window.localStorage.user_data) {
            $scope.userData.name = JSON.parse($window.localStorage.user_data).name
            $scope.userData.avatar = JSON.parse($window.localStorage.user_data).avatar
        }

        $scope.$watch('searchStr', function (tmpStr) {
            vm.contactSearchResult = {};
            $timeout(function () {
                if (tmpStr === $scope.searchStr) {
                    let searchresults = API.service('search-contacts', API.all('contacts'))
                    searchresults.post({ 'searched_text': $scope.searchStr }).then((response) => {
                        vm.contactSearchResult = response.data;
                    });
                }
            }, 500);
        });
        $scope.hideme = function () {
            vm.contactSearchResult = {};
        }


        // /*  If Super admin */
        // if ($window.localStorage.admin_companies) {
        //     $scope.admin_companies = JSON.parse($window.localStorage.admin_companies)
        // }
        // $scope.$watch(function () {
        //     return $window.localStorage.admin_companies;
        // }, function (newCodes, oldCodes) {
        //     if (newCodes) {
        //         $scope.admin_companies = JSON.parse(newCodes);
        //     }

        // });


        if ( $scope.admin_companies && $scope.admin_companies.length) {
            $scope.user_company_details = JSON.parse($window.localStorage.user_company_details);
            this.sa_selected_company = $scope.user_company_details.id;
        }

        if ($window.localStorage.inper_selected_company && $stateParams.superadmin_dashboard == undefined) {
            $scope.sa_selected_company = $window.localStorage.inper_selected_company;
            this.sa_selected_company = $window.localStorage.inper_selected_company;

        }

        $scope.inper_company = function (company_id, index) {
            $window.localStorage.inper_selected_company = company_id;
            $scope.sa_selected_company = company_id;

            let UserData = SAAPI.service('impersonate', SAAPI.all('superadmin'))
            UserData.post(company_id).then((response) => {
                let data = response.data
                angular.forEach(response.data.userRole, function (value) {
                    AclService.attachRole(value)
                })
                $auth.setToken(response)
                AclService.setAbilities(response.data.abilities)
                $window.localStorage.impersonated = 1;
                $window.localStorage.sidebar_docotors = JSON.stringify(response.data.calendar_doctors);
                $window.localStorage.user_data = JSON.stringify(response.data.user);
                $window.localStorage.user_company_details = JSON.stringify(response.data.company_details);
                $window.localStorage.application_url = response.data.application_url;

                $scope.user_company_details.name = $window.localStorage.user_company_details.name;
                $window.localStorage.load_first_time = "yes";
                var landingUrl = "/#/";
                $window.location.href = landingUrl;
                $window.location.reload();
            })
        }

        $scope.switch_superadmin = function () {
            var landingUrl = "/#/admin/clients";
            var token = $window.localStorage.super_admin_token
            $auth.setToken(token)
            delete $window.localStorage.user_company_details;
            $window.location.href = landingUrl;
            $window.location.reload();

        }

        $scope.add_main_appointment = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/opportunities/add-lead-modal.html',
                controller: AddappointmentController,
                resolve: {}
            });
            return modalInstance
        }

        $scope.switch_companysettings = function () {
            var landingUrl = "/#/company-settings";
            $window.location.href = landingUrl;
            $window.location.reload();
        }

        $scope.redirect_admin = function () {
            var landingUrl = "/#/";
            $window.location.href = landingUrl;
            $window.location.reload();
        }

    }

    $onInit() { }
}
class AddappointmentController {
    constructor($window, $http, $stateParams, $scope, $state, API, $uibModal, $timeout, $uibModalInstance) {
        $scope.newdiv = true;
        $scope.changeheading = true;
        $scope.stage_id = null;
        $scope.country_code = "+1";
        $scope.lead_typ = 0;
        $scope.add_disable = false;
        $http({
            method: 'GET',
            url: '/country-phone-codes.json'
        }).then(function successCallback(response) {
            $scope.country_codes = response.data;

        }, function errorCallback(response) {

        });

        let lead_services = API.service('lead-services', API.all('leads'))
        lead_services.one().get()
            .then((response) => {
                $scope.lead_services = response.plain().data.services
                $scope.services_id = $scope.lead_services[0].id
            })

        let stages = API.service('stages', API.all('leads'))
        stages.one().get()
            .then((response) => {
                $scope.stages = response.plain().data.stages
                $scope.stage_id = $scope.stages[0].id
            })

        let lead_assignees = API.service('lead-assignees', API.all('leads'))
        lead_assignees.one().get()
            .then((response) => {
                $scope.lead_assignees = response.plain().data.lead_assignees
                $scope.assignees_id = $scope.lead_assignees[0].id
            })

        let sources = API.service('sources', API.all('leads'))
        sources.one().get()
            .then((response) => {

                $scope.sources = response.plain().data.sources
                $scope.source_id = $scope.sources[0].id

            })

        $scope.$watch('user.contactname', function (tmpStr) {
            if ($scope.user != undefined) {
                $scope.contactSearchResult = {};
                $timeout(function () {
                    if (tmpStr === $scope.user.contactname) {
                        let searchresults = API.service('search-contacts', API.all('contacts'))
                        searchresults.post({ 'searched_text': $scope.user.contactname }).then((response) => {
                            $scope.contactSearchResult = response.data;

                        });
                    }
                }, 500);
                $scope.newdiv = true;
            }
        });
        $scope.hideme = function (item) {
            $scope.user.contactname = item.fullname
            $scope.user.email = item.email
            if (item.phone_country_code != undefined && item.phone_country_code != '') {
                $scope.user.phone = item.mobile_number.replace(item.phone_country_code, '');
            } else {
                $scope.user.phone = item.mobile_number
            }
            $scope.contactSearchResult = {};
            $scope.newdiv = false;
        }

        $scope.create_form = function (user) {
            $scope.add_disable = true;
            var elSource = angular.element(document.querySelector('#selectSource'));
            $scope.source = elSource.val();

            let users = API.service('create-new-lead', API.all('leads'))

            users.post({
                'first_name': user.contactname,
                'last_name': "",
                'email': user.email,
                'source': $scope.source,
                'assignee_id': $scope.assignees_id,
                'service_id': $scope.services_id,
                'phone': user.phone,
                'ltv': user.ltv,
                'stage_id': $scope.stage_id,
                'country_code': $scope.country_code,
                'contact_existing': $scope.lead_typ
            }).then(function (response) {
                $scope.lead_succcess_msg = true;
                $timeout(function () {
                    $scope.closemodal();
                    $state.go('app.oppertunities', {}, { reload: true });
                }, 3000);

            }, function (response) {
                $scope.lead_error = true;
                $scope.add_disable = false;
                $scope.lead_error_msg = response.data.errors.message[0];
            })
        }

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }


    }

}
export const NavHeaderComponent = {
    templateUrl: './views/app/components/nav-header/nav-header.component.html',
    controller: NavHeaderController,
    controllerAs: 'vm',
    bindings: {}
}
