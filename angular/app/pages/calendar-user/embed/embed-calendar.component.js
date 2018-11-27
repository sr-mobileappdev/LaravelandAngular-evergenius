class EmbedCalendarController {
    constructor($stateParams, $scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, $uibModal, $timeout) {
        'ngInject'
        $scope.API = API;
        $scope.numbers = 0;
        $scope.form_submitted = false;
        $scope.customSettings = {
            control: 'brightness',
            theme: 'bootstrap',
            position: 'right'
        };
        let userId = $stateParams.userId
        let doctorName = $stateParams.name
        let doctorPhone = $stateParams.phone

        if ($window.localStorage.user_data) {
            let user_data = JSON.parse($window.localStorage.user_data);
            $scope.company_id = user_data.company_api_key;
        }

        $scope.form_default_vals = {
            submitButtonBackgroundColor: "#ed1d26",
            submitFontColor: "#ffffff",
            cancelButtonBackground: "#C1C1C1",
            cancelButtonFontColor: "#ffffff",
            redirectURL: "",
        };

        $scope.formfield = $scope.form_default_vals;
        $scope.doctorData = '';
        /* Restore Default */
        $scope.restore_default_form = function () {
            $scope.formfield = {
                submitButtonBackgroundColor: "#ed1d26",
                submitFontColor: "#ffffff",
                cancelButtonBackground: "#ffffff",
                cancelButtonFontColor: "#000000",
                redirectURL: "",
            };
        }


        let formfield = API.service('request-form', API.all('reviews'))
        formfield.one('')
            .get()
            .then(function (response) {
                var res = response.plain();
                if (res.data.request_form != null) {
                    $scope.formfield = res.data.request_form;

                }

            })

        $scope.get_numbers = function (range) {
            var num_res = [];
            for (var i = 1; i <= range; i++) {
                num_res.push(i);
            }
            return num_res;
        }
        $scope.font_sizes = $scope.get_numbers(20);

        if ($window.localStorage.user_data) {
            let user_data = JSON.parse($window.localStorage.user_data);
            $scope.company_id = user_data.company_api_key;
        }

        /* Change Form Values while changing object values */
        $scope.$watchCollection('formfield', function (new_val, old_val) {
            if (new_val != undefined) {
                var size = Object.keys(new_val).length;
                var url_str = "https://calendar.evergenius.com/embed/?profile-id=" + userId + "&provider=" + doctorName + "&egapi=" + $scope.company_id + "&phone=" + doctorPhone + "&";
                var i = 0;
                angular.forEach(new_val, function (data, key) {
                    data = data.replace("#", "");
                    url_str += key + "=" + data;
                    if (i != size - 1) {
                        url_str += "&";
                    }
                    i++;

                });

                url_str = encodeURI(url_str);
                var script_s = '<iframe src="' + url_str + '" width="100%" style="min-height:100vh" ></iframe>';
                $scope.web_script = script_s;
                $scope.iframe_url = "https://calendar.evergenius.com/embed/?profile-id=" + userId + "&provider=" + doctorName + "&egapi=" + $scope.company_id + "&phone=" + doctorPhone + "&" + url_str;
                $scope.save_code = function () {
                    $scope.form_submitted = true;
                    var post_settings = API.service('save-request-form', API.all('calendar'));
                    post_settings.post({ settings: new_val })
                        .then((response) => {

                            $scope.form_submitted = true;
                            $timeout(function () {
                                $scope.form_submitted = false;
                            }, 3000);
                        })
                }
            }
        });



    }
    $onInit() { }
}



export const EmbedCalendarComponent = {
    templateUrl: './views/app/pages/calendar-user/embed/embed-calendar.component.html',
    controller: EmbedCalendarController,
    controllerAs: 'vm',
    bindings: {}
}
