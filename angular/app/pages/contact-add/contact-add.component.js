class ContactAddController {

  constructor($scope, $stateParams, $http, $state, API, unauthorizedService, ContextService, $window, AclService) {
    'ngInject'
    this.API = API
    this.$state = $state
    this.formSubmitted = false
    this.alerts = []
    this.current_user_data = JSON.parse($window.localStorage.user_data)
    // Current user session ID
    this.curr_user_id = this.current_user_data.id
    this.AclService = AclService
    $scope.country_code = '+1';
    this.can = AclService.can
    if (!this.can('add.edit.contacts')) {
      $state.go('app.unauthorizedAccess');
    }

    if ($stateParams.alerts) {
      this.alerts.push($stateParams.alerts)
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


    let insurancedata = API.service('insurance-data', API.all('contacts'))
    insurancedata.one("").get()
      .then((response) => {
        $scope.insurancename = response.data;
      })
    this.airport = null;
    //var insurance_companies = API.service('insurance-companies', this.API.all('contacts'));
    this.removeCode = function (phnumber, Country_code = '') {
      if (Country_code != '') {
        phnumber = phnumber.replace(Country_code, '')
      }
      return phnumber;
    }

    /* Google Places */
    $scope.city_input_options = {
      types: ['(cities)']
    }

    $scope.country_input_options = {
      types: ['(regions)']
    }
    /* / Google Places */


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
      maxDate: new Date(),
      minDate: new Date(1900, 1, 1),
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

    $scope.open2 = function () {
      $scope.popup2.opened = true;
    };

    $scope.setDate = function (year, month, day) {
      this.contactData.data.birth_date = new Date(year, month, day);
    };

    $scope.loadTags = function (query) {
      var token = $window.localStorage.satellizer_token
      return $http.get('/api/contacts/find-tags?s=' + query, {
        headers: { 'Authorization': "Bearer " + token }
      });
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
    $scope.events = [
      {
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


    /* Date Picker */

    this.save = function (isValid) {


      if (isValid) {
        if (this.contactData.data.birth_date != '' && this.contactData.data.birth_date != null) {
          this.contactData.data.birth_date = moment(this.contactData.data.birth_date).format('YYYY-MM-DD');
        }
        var reffer_by = '';
        if ($scope.selectedProject != undefined) {
          reffer_by = $scope.selectedProject.originalObject;
        }

        //this.contactData.data.source = this.$scope.selectedSource.originalObject;

        var elSource = angular.element(document.querySelector('#selectSource'));
        var contact_source = elSource.val();

        if (this.contactData.data.insurance_provider != undefined && this.contactData.data.insurance_provider.name != undefined) {
          this.contactData.data.insurance_provider = this.contactData.data.insurance_provider.name;
        }
        //console.log(this.tags);
        var content = this.contactData.data;
        let contact = this.API.service('add-contact', this.API.all('contacts'))
        let $state = this.$state
        var $window = this.$window
        var country_code = $scope.country_code;

        contact.post({
          'address': content.address,
          'address_2': content.address_2,
          'birth_date': content.birth_date,
          'city': content.city,
          'state': content.state,
          'zip_code': content.zip_code,
          'country': content.country,
          'email': content.email,
          'phone_country_code': country_code,
          'first_name': content.first_name,
          'last_name': content.last_name,
          'gender': content.gender,
          'insurance_Id': content.insurance_Id,
          'insurance_group': content.insurance_group,
          'insurance_phone': content.insurance_phone,
          'insurance_provider': content.insurance_provider,
          'mobile_number': content.mobile_number,
          'reffer_by': reffer_by,
          'tags': content.tags,
          'list_tags': this.tags,
          'source': contact_source


        }).then(function (response) {
          let data_res = response.plain()
          let alert = { type: 'success', 'title': 'Success!', msg: 'Contact has been added.' }
          $state.go('app.contacts', { alerts: alert, showmessage: true }, { reload: true })
        }, function (response) {
          let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
          $state.go($state.current, { alerts: alert })
        })
      } else { this.formSubmitted = true; }
    }

  }


  $onInit() { }
}

export const ContactAddComponent = {
  templateUrl: './views/app/pages/contact-add/contact-add.component.html',
  controller: ContactAddController,
  controllerAs: 'vm',
  bindings: {}
}
