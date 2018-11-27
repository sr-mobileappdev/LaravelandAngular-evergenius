class ReviewsSettingsController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, $uibModal, $filter, $timeout) {
        'ngInject'
        this.uibModal = $uibModal;
        $scope.form_submitted = false;
        var titleUrl = API.service('company-review-emails', API.all('reviews'))
        titleUrl.one().get()
            .then((response) => {
                let respo = response.plain();
                var all_reviews = respo.data.review_emails;
                //$scope.review_emails = all_reviews;
            });

        $scope.edit_email_content = function (email_notfication_id, id_e) {
            if (email_notfication_id != undefined && $scope.review_setting["email_" + id_e + "_enable"] == 1) {
                const modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: './views/app/pages/reviews/settings/reviews-settings-modal-email.html',
                    controller: ReviewEmailModalController,
                    controllerAs: 'vm',
                    bindings: {},
                    backdrop: 'static',
                    //size: 'lg',
                    windowClass: 'my-modal-popup',
                    resolve: {
                        email_notfication_id: function () {
                            return email_notfication_id;
                        }
                    }
                });
            }
        }

        /* Get review Settings data */
        var get_settings = API.service('review-settings', API.all('reviews'));
        get_settings.one().get()
            .then((response) => {
                let respo = response.plain();
                var all_reviews = respo.data.review_emails;
                $scope.review_emails = all_reviews;
                $scope.review_setting = respo.data.settings;

            });

        /* Post Review Settings */
        $scope.saveReviewSettings = function (valid) {
            if (valid) {
                var post_settings = API.service('review-settings', API.all('reviews'));
                post_settings.post({ settings: $scope.review_setting })
                    .then((response) => {

                        $timeout(function () {
                            $scope.form_submitted = false;
                        }, 5000);
                        $scope.form_submitted = true;
                    });
            }

        }


        $scope.$watchCollection('review_setting.email_1_id', function (new_val, old_val) {
            if (new_val != undefined) {
                var newTemp = $filter("filter")($scope.review_emails, { id: new_val });
                if (newTemp[0] != undefined) {
                    $scope.review_setting.email_1_enable = newTemp[0].status;
                }
            }
        });

        $scope.$watchCollection('review_setting.email_2_id', function (new_val, old_val) {
            if (new_val != undefined) {
                var newTemp = $filter("filter")($scope.review_emails, { id: new_val });
                if (newTemp[0] != undefined) {
                    $scope.review_setting.email_2_enable = newTemp[0].status;
                }
            }
        });

        $scope.$watchCollection('review_setting.email_3_id', function (new_val, old_val) {
            if (new_val != undefined) {
                var newTemp = $filter("filter")($scope.review_emails, { id: new_val });
                if (newTemp[0] != undefined) {
                    $scope.review_setting.email_3_enable = newTemp[0].status;
                }
            }
        });


    }
    $onInit() { }
}

class ReviewEmailModalController {
    constructor($stateParams, $scope, $state, API, $uibModal, Upload, email_notfication_id, $window, $timeout, $compile, $uibModalInstance) {
        var get_email_content = API.service('email-content', API.all('reviews'));
        get_email_content.one(email_notfication_id).get()
            .then((response) => {
                var data = response.plain();
                $scope.email_content = data.data.email_content;

            })
        $scope.update_success = false;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        $scope.save_email_content = function (valid) {
            if (valid) {
                var post_settings = API.service('update-email-content', API.all('reviews'));
                post_settings.post($scope.email_content)
                    .then((response) => {
                        $scope.update_success = true;
                        timeout(function () {
                            $scope.closemodal();
                        }, 5000);
                    });
            }
            else {
                alert("invalid");
            }
        }

    }
}


export const ReviewSettingsComponent = {
    templateUrl: './views/app/pages/reviews/settings/reviews-settings.component.html',
    controller: ReviewsSettingsController,
    controllerAs: 'vm',
    bindings: {}
}
