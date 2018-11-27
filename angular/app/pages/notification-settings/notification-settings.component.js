class NotificationSettingsController {
  constructor($http, $stateParams, $state, API, $scope, $window, $uibModal, $rootScope) {
    'ngInject'

    this.$state = $state
    this.$scope = $scope
    this.formSubmitted = false
    this.alerts = []
    this.CompanyRolesSelected = []

    if ($stateParams.alerts) {
      this.alerts.push($stateParams.alerts)
    }

    let CompanyId = $stateParams.CompanyId

    let CompanyData = API.service('notification-settings', API.all('company'))
    CompanyData.one(CompanyId).get()
      .then((response) => {
        let CompanyRole = []
        let CompanyResponse = response.plain()
        this.companyeditdata = API.copy(response)
        $scope.selectedTags = CompanyResponse.data.notification_useremails
        $scope.selectedTagss = CompanyResponse.data.notification_userphones


      })

    var events = ['trixInitialize', 'trixChange', 'trixSelectionChange',];

    for (var i = 0; i < events.length; i++) {
      $scope[events[i]] = function (e, editor) {
        $scope.selectedIndex = editor
        // editor.insertString("Hello")
      }

    };



    $scope.loadTags = function (query) {
      var token = $window.localStorage.satellizer_token
      return $http.get('/api/users/find-tags?e=' + query, {
        headers: { 'Authorization': "Bearer " + token }
      });
    }
    $scope.loadnumberTags = function (query) {
      var token = $window.localStorage.satellizer_token
      return $http.get('/api/users/find-tags?n=' + query, {
        headers: { 'Authorization': "Bearer " + token }
      });
    }
    $scope.$on('scanner-started', function (event, args) {

      var anyThing = args.any;
      var range = args.rangearray
      var element = args.element
      element.setSelectedRange(range)
      element.insertHTML(anyThing)
    });
    $scope.$on('sms-data', function (event, args) {

      var text = args.any;
      $scope.insertAtCaret = function (text) {
        var txtarea = document.getElementById('username_' + args.index);
        var scrollPos = args.index;
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

      }
      $scope.insertAtCaret(text)

    });
    this.save = function (isValid) {
      if (isValid) {
        let $state = this.$state
        let $scope = this.$scope
        if ($scope.selectedTags != 'undefined' && $scope.selectedTags.length > 0) {
          this.companyeditdata.data.companynotifyuseremails = $scope.selectedTags;
        }

        if ($scope.selectedTagss != 'undefined' && $scope.selectedTagss.length > 0) {
          this.companyeditdata.data.companynotifyuserphones = $scope.selectedTagss;
        }

        this.companyeditdata.put()
          .then(() => {
            let alert = { type: 'success', 'title': 'Success!', msg: 'Notification settings has been updated.' }
            $state.go($state.current, { alerts: alert })
            //location.reload(true)
          }, (response) => {
            let alert = { type: 'error', 'title': 'Error!', msg: response.data.message }
            $state.go($state.current, { alerts: alert })
          })
      } else {
        this.formSubmitted = true
      }
    }

    $scope.gettagsPop = function (array, index, name, identity) {
      var id = ''
      if (name == "APPOINTMENT_REMINDER_8AM" || name == "ONE_HOUR_APPOINTMENT_REMINDER" || name == 'one_hour_reminder_appointment' || name == 'ONE_HOUR_APPOINTMENT_REMINDER' || name == 'reminder_before_1day_9am' || name == 'APPOINTMENT_APPROVAL_SMS') {
        id = 2
      } else if (name == 'LEAD_ADD' || name == 'RECEIVE_NEW_OPPORTUNITY_SMS') {
        id = 11
      } else if (name == "LEAD_PENDING_ACTION") {
        id = 12
      } else if (name == "RECEIVE_SMS_EMAIL") {
        id = 20
      }
      else if (name == "APPOINTMENT_UPDATE_APPROVED" || name == 'APPOINTMENT_UPDATE_CANCEL' || name == "Appointment Cancel" || name == "APPOINTMENT_UPDATE_INVALID" || name == "APPOINTMENT_UPDATE_NO_SHOW" || name == "APPOINTMENT_UPDATE_RESCHEDULE") {
        id = 3
      } else if (name == "ADD_DOCTOR" || name == "ADD_STAFF") {
        id = 8
      } else if (name == "ADD_APPOINTMENT") {
        id = 9
      } else if (name == "SCHEDULE_POST_PUBLISH") {
        id = 10
      } else if (name == "SUBSCRIPTION_MAIL") {
        id = 11
      }
      const modalInstance = $uibModal.open({
        animation: true,
        templateUrl: 'merge-tags.html',
        controller: mergeTagsController,
        resolve: {
          rangeArray: function () {
            return array;
          },
          arrayFilter: function () {
            return id;
          },
          Identity: function () {
            return identity;
          },
          IndexN: function () {
            return index;
          }

        }
      });
      return modalInstance;
    }
  }

  $onInit() { }
}
class mergeTagsController {
  constructor($window, $http, $rootScope, Identity, IndexN, arrayFilter, rangeArray, $stateParams, $scope, $state, API, $uibModal, $timeout, $uibModalInstance) {

    if (arrayFilter == 2) {
      $scope.tags = [{ name: 'Contact First Name', tag: '{$first_name}' },
      { name: 'Contact Last Name', tag: '{$last_name}' },
      { name: 'Appointment ID', tag: '{$appointment_reference}' },
      { name: 'Pefix(Mr./Miss)', tag: '{$prefix}' },
      { name: 'Appointment Time', tag: '{$time}' }]
    } else if (arrayFilter == 11) {
      $scope.tags = [{ name: 'Contact Name', tag: '{$name}' },
      { name: 'Contact Email', tag: '{$email}' },
      { name: 'Contact Phone', tag: '{$phone_number}' },
      { name: 'CEO Signature', tag: '{$bob_signature}' },
      { name: 'Assignee', tag: '{{assignee}}' },
      { name: 'Source', tag: '{$source}' }, { name: 'Notes', tag: '{$notes}' },
      { name: 'Date', tag: '{$time}' }]
    } else if (arrayFilter == 12) {
      $scope.tags = [{ name: 'Pending Opportunities', tag: '{$action_pending_leads}' },
      { name: 'CEO Signature', tag: '{$bob_signature}' }]
    } else if (arrayFilter == 20) {
      $scope.tags = [{ name: 'Contact Name', tag: '{$name}' },
      { name: 'Company Name', tag: '{$client_name}' },
      { name: 'Contact Phone', tag: '{$phone_number}' },
      { name: 'CEO Signature', tag: '{$bob_signature}' },
      { name: 'Message', tag: '{$message}' }]
    } else if (arrayFilter == 3) {
      $scope.tags = [{ name: 'Patient First Name', tag: '{$first_name}' }, { name: ' Last Name', tag: '{$last_name}' },
      { name: 'Office Name', tag: '{$client_name}' },
      { name: 'Contact Phone', tag: '{$phone_number}' },
      { name: 'Date', tag: '{$date}' }, { name: 'Appointment Status', tag: '{$status}' },
      { name: 'TIme', tag: '{$time}' }, { name: 'Office Address', tag: '{$location}' }, { name: 'Office Phone', tag: '{$office_phone}' },
      { name: 'Website Link', tag: '{$website_link}' }]
    } else if (arrayFilter == 8) {
      $scope.tags = [{ name: 'Name', tag: '{$name}' },
      { name: 'Username', tag: '{$username}' },
      { name: 'Password', tag: '{$password}' },
      { name: 'Website Link', tag: '{$website_link}' }]
    } else if (arrayFilter == 9) {
      $scope.tags = [{ name: 'Contact First Name', tag: '{$first_name}' },
      { name: 'Contact Last Name', tag: '{$last_name}' },
      { name: 'Date', tag: '{$date}' },
      { name: 'Time', tag: '{$time}' },
      { name: 'Provider', tag: '{$provider}' },
      { name: 'Office Phone', tag: '{$office_phone}' },
      { name: 'Contact Email', tag: '{$email}' },
      { name: 'Contact Phone', tag: '{$phone_number}' }]
    } else if (arrayFilter == 10) {
      $scope.tags = [{ name: 'Post Title', tag: '{$post_title}' }]
    } else if (arrayFilter == 11) {
      $scope.tags = [{ name: 'Subscription List', tag: '{$subscription_list}' }, { name: 'Company Name', tag: '{$client_name}' }, { name: 'Company Address', tag: '{$company_address}' }]
    }


    $scope.closemodal = function () {
      $uibModalInstance.close();
    }

    var rangeO = rangeArray.getSelectedRange()

    $scope.puttagInEditor = function (tagname) {
      if (Identity == 'sms') {
        $rootScope.$broadcast('sms-data', { any: tagname, index: IndexN });
      } else {
        $rootScope.$broadcast('scanner-started', { any: tagname, rangearray: rangeO, element: rangeArray });
      }


      $uibModalInstance.close();
    }

  }
}
export const NotificationSettingsComponent = {
  templateUrl: './views/app/pages/notification-settings/notification-settings.component.html',
  controller: NotificationSettingsController,
  controllerAs: 'vm',
  bindings: {}
}
