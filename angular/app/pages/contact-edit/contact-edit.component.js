class ContactEditController {

    constructor($scope, $http, $stateParams, $state, API, unauthorizedService, ContextService, $window, AclService) {
        'ngInject'

        this.$state = $state
        this.formSubmitted = false
        this.alerts = []
        this.current_user_data = JSON.parse($window.localStorage.user_data)
        this.$scope = $scope;
        // Current user session ID
        this.curr_user_id = this.current_user_data.id
        this.AclService = AclService
        if ($stateParams.alerts) {
            this.alerts.push($stateParams.alerts)
        }

        this.can = AclService.can
        if (!this.can('add.edit.contacts')) {
            $state.go('app.unauthorizedAccess');
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

        /**autosuggest**/
        let insurancedata = API.service('insurance-data', API.all('contacts'))
        insurancedata.one("").get()
            .then((response) => {
                $scope.insurancename = response.data;
            })
        /**autosuggest**/
        let contactId = $stateParams.contactId
        //let contactData = API.service('show', API.all('contacts'))
        let contactData = API.service('contacts-show', API.all('contacts'))
        contactData.one(contactId).get()
            .then((response) => {
                AclService = this.AclService
                let con_users = []
                this.contactData = API.copy(response)
                this.contactData.data.mobile_number = this.removeCode(this.contactData.data.mobile_number, this.contactData.data.phone_country_code)
                this.contactData.data.area = this.contactData.data.city;
                if (this.contactData.data.birth_date != '' && this.contactData.data.birth_date != null) {
                    this.contactData.data.birth_date = new Date(this.contactData.data.birth_date);
                }
                if (this.contactData.data.phone_country_code == '') {
                    this.contactData.data.phone_country_code = "+1";
                }

                this.roles = AclService.getRoles()
                this.user_role = this.roles[0]
                this.contact_users = con_users;
                let is_owner = con_users.indexOf(this.curr_user_id);
                if (is_owner == -1) {
                    if (this.user_role == 'doctor') {
                        $state.go('app.unauthorizedAccess');
                    }
                }
                this.$scope.selectedProject = this.contactData.data.reffer_by;
                this.$scope.selectedSource = this.contactData.data.tag;
                this.$scope.selectedTags = this.contactData.data.tags;

                this.$scope.searchStr = this.contactData.data.reffer_by;
                this.$scope.searchSrc = this.contactData.data.source;
                this.$scope.searchTag = this.contactData.data.tag;
                this.tags = this.contactData.data.list_tags

                var myEl = angular.element(document.querySelector('#selectreffer'));
                myEl.val(this.contactData.data.reffer_by.title);

                var elSource = angular.element(document.querySelector('#selectSource'));
                elSource.val(this.contactData.data.source.term_value);

                /*var elTags = angular.element(document.querySelector('#selectTags'));
                elTags.val(this.contactData.data.tag.term_value);*/
            })
        this.removeCode = function (phnumber, Country_code = '') {
            if (Country_code != '') {
                phnumber = phnumber.replace(Country_code, '')
            }
            return phnumber;
        }

        $scope.city_input_options = {
            types: ['(cities)']
        }

        $scope.country_input_options = {
            types: ['(regions)']
        }

        /* Date Picker */
        $scope.today = function () {
            $scope.dt = new Date();
        };
        $scope.today();

        $scope.clear = function () {
            $scope.dt = null;
        };

        $scope.inlineOptions = {
            customClass: getDayClass,
            minDate: new Date(),
            showWeeks: true
        };

        $scope.dateOptions = {
            formatYear: 'yy',
            maxDate: new Date(2020, 5, 22),
            minDate: new Date(),
            startingDay: 1,
            showWeeks: false
        };

        // Disable weekend selection
        function disabled(data) {
            var date = data.date,
                mode = data.mode;
            return mode === 'day' && (date.getDay() === 0 || date.getDay() === 6);
        }


        $scope.load_list_Tags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/email-marketing/find-email-list?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

        $scope.toggleMin = function () {
            $scope.inlineOptions.minDate = $scope.inlineOptions.minDate ? null : new Date();
            $scope.dateOptions.minDate = $scope.inlineOptions.minDate;
        };

        $scope.toggleMin();

        $scope.open1 = function () {
            $scope.popup1.opened = true;
        };

        $scope.open2 = function () {
            $scope.popup2.opened = true;
        };

        $scope.setDate = function (year, month, day) {
            this.contactData.data.birth_date = new Date(year, month, day);
        };

        /* Load Tags */
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/contacts/find-tags?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

        $scope.$watchCollection('vm.contactData.data.area', function (new_val, old_val) {
            if (new_val){
                var city_name = new_val.split(',')[0];
                var myEl = angular.element(document.querySelector('#city_name'));
                myEl.val(city_name);
            }
           
        });

        this.change_city = function () {
            let new_val = this.contactData.data.area;
            var city_name = new_val.split(',')[0];
            this.contactData.data.area = city_name;
        }



        $scope.formats = ['dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy', 'shortDate'];
        $scope.format = $scope.formats[0];
        $scope.altInputFormats = ['M!/d!/yyyy'];

        $scope.popup1 = {
            opened: false
        };

        $scope.popup2 = {
            opened: false
        };

        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        var afterTomorrow = new Date();
        afterTomorrow.setDate(tomorrow.getDate() + 1);
        $scope.events = [{
            date: tomorrow,
            status: 'full'
        },
        {
            date: afterTomorrow,
            status: 'partially'
        }
        ];

        function getDayClass(data) {
            var date = data.date,
                mode = data.mode;
            if (mode === 'day') {
                var dayToCheck = new Date(date).setHours(0, 0, 0, 0);

                for (var i = 0; i < $scope.events.length; i++) {
                    var currentDay = new Date($scope.events[i].date).setHours(0, 0, 0, 0);

                    if (dayToCheck === currentDay) {
                        return $scope.events[i].status;
                    }
                }
            }

            return '';
        }

    }
    save(isValid) {
        if (isValid) {
            //Reffer By in data base 
            if (this.$scope.selectedProject != undefined) {
                this.contactData.data.reffer_by = this.$scope.selectedProject.originalObject;
            }
            var elSource = angular.element(document.querySelector('#selectSource'));
            this.contactData.data.source = elSource.val();

            this.contactData.data.tags = this.$scope.selectedTags;
            this.contactData.data.list_tags = this.tags
            delete this.contactData.data.area;

            if (this.contactData.data.birth_date != '' && this.contactData.data.birth_date != null) {
                this.contactData.data.birth_date = moment(this.contactData.data.birth_date).format('YYYY-MM-DD');
            }

            if (this.contactData.data.insurance_provider != null && this.contactData.data.insurance_provider.name != undefined) {
                this.contactData.data.insurance_provider = this.contactData.data.insurance_provider.name;
            }

            let $state = this.$state
            this.contactData.put()
                .then(() => {
                    if (this.contactData.data.birth_date != '' && this.contactData.data.birth_date != null) {
                        this.contactData.data.birth_date = new Date(this.contactData.data.birth_date);
                    }
                    this.contactData.data.area = this.contactData.data.city;
                    let alert = { type: 'success', 'title': 'Success!', msg: 'Contact information has been updated.' }
                    $state.go($state.current, { alerts: alert })
                }, (response) => {
                    let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
                    $state.go($state.current, { alerts: alert })
                })

        } else {
            this.formSubmitted = true
        }

    }

    $onInit() { }
}

export const ContactEditComponent = {
    templateUrl: './views/app/pages/contact-edit/contact-edit.component.html',
    controller: ContactEditController,
    controllerAs: 'vm',
    bindings: {}
}
