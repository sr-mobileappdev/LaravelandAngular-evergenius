class ReviewRequestFormGenerateController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, $uibModal, $timeout) {
        'ngInject'
        $scope.numbers = 0;
        $scope.form_submitted = false;
        $scope.customSettings = {
            control: 'brightness',
            theme: 'bootstrap',
            position: 'right'
        };

        $scope.form_default_vals = {
            bodyBackgroundColor: "#ffffff",
            labelColor: "#000000",
            bodyText: "14",
            submitButtonBackgroundColor: "#ed1d26",
            submitFontColor: "#ffffff",
            cancelButtonBackground: "#C1C1C1",
            cancelButtonFontColor: "#ffffff"
        };

        $scope.formfield = $scope.form_default_vals;

        /* Restore Default */
        $scope.restore_default_form = function () {
            $scope.formfield = {
                bodyBackgroundColor: "#ffffff",
                labelColor: "#000000",
                bodyText: "14",
                submitButtonBackgroundColor: "#ed1d26",
                submitFontColor: "#ffffff",
                cancelButtonBackground: "#ffffff",
                cancelButtonFontColor: "#000000"
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
                var url_str = $window.localStorage.application_url + "/scripts/widgets/form?api_key=" + $scope.company_id + "&";
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
                var script_s = '<div id="eg_review_form"></div>\n<script type="text/javascript" src="' + url_str + '" ></script>';
                $scope.web_script = script_s;
                $scope.iframe_url = $window.localStorage.application_url + "/scripts/widgets/form-preview/?api_key=" + $scope.company_id + "&" + url_str;
                $scope.save_code = function () {
                    $scope.form_submitted = true;
                    var post_settings = API.service('save-request-form', API.all('reviews'));
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
        $scope.preview_code = function () {
            var iframe_url = $scope.iframe_url;

            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/reviews/embed-code-generate/review-preview-modal.html',
                controller: add_app_modalController,
                resolve: {
                    iframe_url: function () {
                        return iframe_url;
                    }

                },
                size: 'lg'
            });
            return modalInstance;
        }



    }
    $onInit() { }
}

class add_app_modalController {
    constructor($stateParams, $scope, $state, API, $uibModal, $uibModalInstance, iframe_url, $timeout) {
        $scope.givenUrl = iframe_url;
        $scope.iframe_height = "670px";
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }

}

export const ReviewRequestFormGenerateComponent = {
    templateUrl: './views/app/pages/reviews/request-form-generate/request-form-generate.component.html',
    controller: ReviewRequestFormGenerateController,
    controllerAs: 'vm',
    bindings: {}
}
