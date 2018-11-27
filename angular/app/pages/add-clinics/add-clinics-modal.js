class ClinicAddController {
    constructor($scope, $stateParams, $state, SAAPI, $http, $window, Upload, API, $timeout) {
        'ngInject'
        var vm = this
        $scope.upload_tag = false
        vm.country_code = '+1';
        vm.timetakenId = 'publish'
        vm.description = ''
        vm.address = ''
        vm.province = ''
        vm.site_url = ''
        vm.specialities = ''
        vm.companyeditdata = {};
        vm.companyeditdata.data = {};
        vm.companyeditdata.data.city = '';
        vm.companyeditdata.data.state = '';
        vm.companyeditdata.data.country = '';
        vm.facebook_link = '';
        vm.twitter_link = '';
        vm.google_link = '';
        vm.youtube_link = '';
        vm.instagram_link = '';
        vm.social_links = '';
        vm.linkedin_link = '';
        vm.claim_status = 'Pending'
        vm.certifications = '';
        vm.Eg_id = '';
        $scope.click_f = false;
        $scope.upload = function (files, media) {
            var token = $window.localStorage.satellizer_token

            if (files && files.length) {
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    if (!file.$error) {
                        Upload.upload({
                            url: '/api/users/upload-profile-image',
                            data: {
                                profile_pic: file,

                            },
                            beforeSend: function (xhr) {
                                xhr.setRequestHeader("Authorization",
                                    "Bearer " + token);
                            }, error: function (err) {
                                let data = []
                                return JSON.stringify(data);
                            }

                        }).then(function (resp) {
                            var file_path = resp.data.data.path;

                            $scope.image_path = file_path
                            $scope.upload_tag = true;
                        });
                    }
                }
            }
        };

        $scope.$watch(angular.bind(this, function () {
            return this.title;
        }), function (tmpStr) {

            if (tmpStr != undefined && $scope.click_f === false) {
                $scope.contactSearchResult = {};
                $timeout(function () {
                    if (tmpStr === vm.title) {
                        let searchresults = SAAPI.service('search-companies', SAAPI.all('honestdoctor'))
                        searchresults.post({ 'searched_text': vm.title }).then((response) => {
                            var sc = [];
                            angular.forEach(response.data, function (data, key) {
                                if (data.lead == null) {
                                    sc.push(data);
                                }
                            })
                            $scope.contactSearchResult = sc;
                        });
                    }
                }, 500);
                $scope.newdiv = true;
            }
            $scope.click_f = false;
        });

        $scope.hideme = function (item) {
            vm.title = item.name;
            vm.email = item.email;
            vm.companyeditdata.data.state = item.state;
            vm.mobile_number = item.phone;
            vm.companyeditdata.data.city = item.city;
            vm.companyeditdata.data.area = item.city;
            vm.companyeditdata.data.country = item.country;
            vm.site_url = item.site_url;
            vm.address = item.address;
            vm.description = item.description;
            vm.image_path = item.logo;
            vm.Eg_id = item.id;
            $scope.contactSearchResult = {};
            $scope.newdiv = false;
            $scope.click_f = true;
        }

        $scope.delete_photo = function () {
            $scope.image_path = '';
            angular.element("input[type='file']").val(null)
            $scope.upload_tag = false
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
        this.removeCode = function (phnumber, Country_code = '') {
            if (Country_code != '') {
                phnumber = phnumber.replace(Country_code, '')
            }
            return phnumber;
        }

        $scope.loadTags = function (query) {
            var token = $window.localStorage.super_admin_token
            return $http.get('/api/honestdoctor/find-specialization?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

        $scope.$watchCollection('vm.companyeditdata.data.area', function (new_val, old_val) {
            if (new_val != undefined) {
                var city_name = new_val.split(',')[0];
                $scope.relocategoogle();
                var myEl = angular.element(document.querySelector('#city_name'));
                myEl.val(city_name);
            }
        });
        $scope.relocategoogle = function () {
            var myEl = angular.element(document.querySelector('#city_name'));
            var all = angular.element(document.querySelector('.pac-container'));
            if (all) {
                for (var i = 0; i < all.length; i++) {
                    all[i].style.top = parseInt(myEl.offset().top) + 35 + 'px';
                }
            }
        }

        angular.element($window).bind('mousewheel', function () {
            $scope.relocategoogle();
        })

        this.change_city = function () {
            let new_val = this.companyeditdata.data.area;
            var city_name = new_val.split(',')[0];
            this.companyeditdata.data.area = city_name;
        }


        this.save = function (isValid, image_path) {
            if (!image_path) {
                image_path = ''
            }

            let add_clinic = SAAPI.service('add-clinic', SAAPI.all('honestdoctor'))

            var $window = this.$window

            add_clinic.post({
                'name': vm.title,
                'email': vm.email,
                'address': vm.address,
                'description': vm.description,
                'phone': vm.mobile_number,
                'site_url': vm.site_url,
                'city': vm.companyeditdata.data.city,
                'state': vm.companyeditdata.data.state,
                'country': vm.companyeditdata.data.country,
                'specialities': vm.specialities,
                'image_url': image_path,
                'hd_publish_status': vm.timetakenId,
                'facebook_link': vm.facebook_link,
                'twitter_link': vm.twitter_link,
                'google_link': vm.google_link,
                'youtube_link': vm.youtube_link,
                'instagram_link': vm.instagram_link,
                'social_links': vm.social_links,
                'claim_status': vm.claim_status,
                'linkedin_link': vm.linkedin_link,
                'certifications': vm.certifications,
                'eg_id': vm.Eg_id
            }).then(function (response) {

                let alert = { type: 'success', 'title': 'Success!', msg: 'Clinic has been added.' }
                $state.go('app.gethdclinics', { alerts: alert, showmessage: true }, { reload: true })
            }, function (response) {
                let alert = { type: 'danger', 'title': 'Error!', msg: 'Something went wrong' }
                $state.go($state.current, { alerts: alert })
            })

        }

    }

    $onInit() { }
}

export const ClinicAddComponent = {
    templateUrl: './views/app/pages/add-clinics/add-clinics-modal.html',
    controller: ClinicAddController,
    controllerAs: 'vm',
    bindings: {}
}
