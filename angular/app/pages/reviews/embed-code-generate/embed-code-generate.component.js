class EmbedcodeGenerateController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, $uibModal, $timeout) {
        'ngInject'

        $scope.form_submitted = false;
        $scope.customSettings = {
            control: 'brightness',
            theme: 'bootstrap',
            position: 'right'
        };
        if ($window.localStorage.user_data) {
            let user_data = JSON.parse($window.localStorage.user_data);
            $scope.company_id = user_data.company_api_key;
        }

        $scope.widget_styles = [{ val: 'review_dispaly_1', title: 'Testimonial Carousel', 'iframe_height': '710px' },
        { val: 'review_dispaly_2', title: 'Side Bar Carousel', 'iframe_height': '430px' },
        { val: 'review_dispaly_3', title: 'Honest Doctor Badge', 'iframe_height': '480px' }
        ];

        $scope.form_default_vals = {
            widgetStyles: 'review_dispaly_1',
            bodyBackground: "#ffffff",
            textColor: "#000000",
            bodyTextSize: "14",
            headerBg: '#54B4EE',
            headerTextColor: '#ffffff',
            themeColor: "#005d8a",
            honestDoctorLogo: true,
            verifiedText: 'Verified Testimonial',
            verifiedTextBackground: '#4b9a0b ',
            verifiedTextColor: '#ffffff',
            reviewsPerPage: "20",
            stripeOne: "#54b4ee",
            stripeTwo: "#4398cf",
            ovrallStripe: "#e15757",
            rateThisBackgroundColor: "#56636b",
            ratingTitleColor: "#ffffff",
            showDesclaimer: true,
            showDate: true,
        };


        $scope.restore_default_form = function () {
            $scope.widgetFields = {
                widgetStyles: 'review_dispaly_1',
                bodyBackground: "#ffffff",
                textColor: "#000000",
                bodyTextSize: "14",
                headerBg: '#54B4EE',
                headerTextColor: '#ffffff',
                themeColor: "#005d8a",
                honestDoctorLogo: true,
                verifiedText: 'Verified Testimonial',
                verifiedTextBackground: '#4b9a0b ',
                verifiedTextColor: '#ffffff',
                reviewsPerPage: "20",
                stripeOne: "#54b4ee",
                stripeTwo: "#4398cf",
                ovrallStripe: "#e15757",
                rateThisBackgroundColor: "#56636b",
                ratingTitleColor: "#ffffff",
                showDesclaimer: true,
                showDate: true,
            };
        }

        $scope.widgetFields = $scope.form_default_vals;

        $scope.get_numbers = function (range) {
            var num_res = [];
            for (var i = 1; i <= range; i++) {
                num_res.push(i);
            }
            return num_res;

        }

        $scope.font_sizes = $scope.get_numbers(30);
        let widgets = API.service('embed-code', API.all('reviews'))
        widgets.one('')
            .get()
            .then(function (response) {
                var res = response.plain();
                if (res.data.embded_code != null) {
                    $scope.widgetFields = res.data.embded_code;
                }
            })
        /* Change Form Values while changing object values */
        $scope.$watchCollection('widgetFields', function (new_val, old_val) {
            var selected_design = $scope.search_name_key(new_val.widgetStyles, $scope.widget_styles);
            $scope.iframe_height = selected_design.iframe_height;
            if (new_val != undefined) {
                var size = Object.keys(new_val).length;
                var url_str = $window.localStorage.application_url + "/scripts/widgets/display-reviews?api_key=" + $scope.company_id + "&";
                var i = 0;
                angular.forEach(new_val, function (data, key) {
                    data = data.toString().replace("#", "");
                    url_str += key + "=" + data;
                    if (i != size - 1) {
                        url_str += "&";
                    }
                    i++;
                });
                url_str += "&";
                url_str += "iframe_height=" + $scope.iframe_height;
                url_str = encodeURI(url_str);
                var script_s = '<div id="eg_review_form"></div>\n<script type="text/javascript" src="' + url_str + '" ></script>';
                $scope.web_script = script_s;
                $scope.iframe_url = $window.localStorage.application_url + "/scripts/widgets/review-preview/?api_key=" + $scope.company_id + "&" + url_str;
                $scope.save_code = function () {

                    var post_settings = API.service('save-embed-code', API.all('reviews'));
                    post_settings.post({ settings: new_val })
                        .then((response) => {

                            $scope.form_submitted = true;
                            $timeout(function () {
                                $scope.form_submitted = false;
                            }, 3000);
                        })
                }
            }
            if ($scope.widgetFields.widgetStyles == 'review_dispaly_1') {

                document.getElementById('headerb').style.display = "none";
                document.getElementById('headert').style.display = "none";
                document.getElementById('theme').style.display = "none";
                document.getElementById('ovrallStripe').style.display = "none";
                document.getElementById('stripeOne').style.display = "none";
                document.getElementById('stripeTwo').style.display = "none";
                document.getElementById('rateThisBackgroundColor').style.display = "none";
                document.getElementById('ratingTitleColor').style.display = "none";
            }
            else if ($scope.widgetFields.widgetStyles == 'review_dispaly_2') {
                document.getElementById('headerb').style.display = "block";
                document.getElementById('headert').style.display = "block";
                document.getElementById('theme').style.display = "block";
                document.getElementById('ovrallStripe').style.display = "none";
                document.getElementById('stripeOne').style.display = "none";
                document.getElementById('stripeTwo').style.display = "none";
                document.getElementById('rateThisBackgroundColor').style.display = "none";
                document.getElementById('ratingTitleColor').style.display = "none";
            }
            else if ($scope.widgetFields.widgetStyles == 'review_dispaly_3') {
                document.getElementById('headerb').style.display = "block";
                document.getElementById('headert').style.display = "block";
                document.getElementById('theme').style.display = "none";
                document.getElementById('ovrallStripe').style.display = "block";
                document.getElementById('stripeOne').style.display = "block";
                document.getElementById('stripeTwo').style.display = "block";
                document.getElementById('rateThisBackgroundColor').style.display = "block";
                document.getElementById('ratingTitleColor').style.display = "block";
                document.getElementById('verifiedTextBackground').style.display = "none";
                document.getElementById('verifiedTextColor').style.display = "none";
                document.getElementById('bodyTextSize').style.display = "none";
                document.getElementById('bodyTextColor').style.display = "none";
                document.getElementById('bodyBackground').style.display = "none";
                document.getElementById('theme').style.display = "none"
            }
            else {
                document.getElementById('headerb').style.display = "block";
                document.getElementById('headert').style.display = "block";
                document.getElementById('theme').style.display = "block"

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
                    },
                    iframe_height: function () {
                        return $scope.iframe_height;
                    }


                },
                size: 'lg'

            });
            return modalInstance;
        }

        $scope.search_name_key = function (nameKey, myArray) {
            for (var i = 0; i < myArray.length; i++) {
                if (myArray[i].val === nameKey) {
                    return myArray[i];
                }
            }
        }


    }

    $onInit() { }
}
class add_app_modalController {
    constructor($stateParams, $scope, $state, API, $uibModal, $uibModalInstance, iframe_url, iframe_height, $timeout) {
        $scope.iframe_height = iframe_height;
        $scope.givenUrl = iframe_url;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }

}

export const EmbedcodeGenerateComponent = {
    templateUrl: './views/app/pages/reviews/embed-code-generate/embed-code-generate.component.html',
    controller: EmbedcodeGenerateController,
    controllerAs: 'vm',
    bindings: {}
}
