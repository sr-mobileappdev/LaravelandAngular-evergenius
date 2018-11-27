class ImportContactsInfoController {
	constructor($scope, $auth, $stateParams, $state, $compile, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location) {
		'ngInject'
		$scope.data = {};
		this.alerts = []
		var objectString = $window.localStorage.getItem('mapping');
		$scope.mappItems = JSON.parse(objectString);
		$scope.csvfields = $scope.mappItems.csv_fields;
		$scope.emlist = $scope.mappItems.list;
		$scope.dbfields = $scope.mappItems.fields;
		$scope.selectedList = []
		$scope.loadlistings = function (query) {
			return $scope.emlist;
		};
		if ($stateParams.alerts) {
			this.alerts.push($stateParams.alerts)
		}

		if ($stateParams.Obj) {

			$scope.data.csv = $stateParams.Obj
		}
		if ($window.localStorage.getItem('mapped_contacts_details')) {
			var mapped = JSON.parse($window.localStorage.getItem('mapped_contacts_details'))
			$scope.data.csv = mapped
		}
		if ($window.localStorage.getItem('list_info')) {
			var list = JSON.parse($window.localStorage.getItem('list_info'))

			var obj_length = Object.keys(list).length

			if (obj_length == 2) {
				$scope.selectedList.push(list)
			}
			else {

				for (var i = 0; i < list.length; i++) {
					$scope.selectedList.push(list[i])
				}
			}
		}
		$scope.save = function (isValid) {

			var counter = 0;
			angular.forEach($scope.data.csv, function (value, key) {
				if (value == 'email' || value == 'mobile_number') {
					counter++;
				}
			})

			if (counter < 1) {
				let alert = { type: 'danger', 'title': 'Failure!', msg: 'Please map required fields email or phone_number' }
				$state.go('app.emcontact-mapping', { alerts: alert, showmessage: true, Obj: $scope.data.csv })
				$window.localStorage.setItem('list_info', JSON.stringify($scope.selectedList));
			}
			else {
				var listing = [];
				$scope.selectedList.forEach(function (entry) {
					listing.push(entry.id);
				});
				$scope.data['list'] = listing;
				$window.localStorage.setItem('list_info', JSON.stringify($scope.selectedList));
				$window.localStorage.setItem('mapped_contacts', JSON.stringify($scope.data));
				$window.localStorage.setItem('mapped_contacts_details', JSON.stringify($scope.data.csv));
				let alert = { type: 'success', 'title': 'Success!', msg: 'Values Mapped Succesfully' }
				$state.go('app.save-mapped-contacts', { alerts: alert, showmessage: true })
			}

		}

	}
	$onInit() { }
}

export const ImportContactsInfoComponent = {
	templateUrl: './views/app/pages/email-marketing/contacts/import/map/import-contact-map.html',
	controller: ImportContactsInfoController,
	controllerAs: 'vm',
	bindings: {}
}
