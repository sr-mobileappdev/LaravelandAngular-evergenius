class SocialPostsAddController {
    constructor($stateParams, API, AclService, $state, $scope, Upload, $window, $timeout, $compile) {
        'ngInject'
        $scope.post_add_disabled = false;
        this.API = API
        this.$state = $state
        this.alerts = []
        $scope.add_commonpost = []
        $scope.add_commonpost.title = ''
        $scope.selected_networks = new Object;
        $scope.edited_network = new Object;
        $scope.post_published = false;
        $scope.post_view_cat = 'history';
        $scope.limit_validate_errors = [];
        $scope.post_msg = '';
        $scope.edit_post = false;
        $scope.edit_status = false;
        /* Get Social Connect Settings */
        var isSocialConnect = API.service('is-profiles-connected', API.all('social'))
        isSocialConnect.one().get()
            .then((response) => {
                let respo = response.plain();
                $scope.network_connects = respo.data;
                /* if page not edited */
                if (!$stateParams.edit_data) {

                    angular.forEach($scope.network_connects, function (value, key) {
                        if (value.connected == true) {
                            $scope.select_social_network(key, 1);
                        }
                    });
                }
            });

        $scope.character_validate = {
            twitter: {
                limit: 280,
                message: "Twitter Char limit exceeds.",
                icon: "fa-twitter"
            },
            linkedin: {
                limit: 600,
                message: "linkedin Char limit exceeds.",
                icon: "fa-linkedin"
            }
        };

        $scope.validate_character_limit = function (length, network = null) {
            var len = 0;
            if (network == null) {
                $scope.limit_validate_errors = [];
                var validates = $scope.character_validate;
                let selected_social_chanels = $scope.selected_networks;
                angular.forEach(selected_social_chanels, function (value, key) {
                    len = length
                    if ($scope.posts[key].link != undefined) {
                        len = len + $scope.posts[key].link.length;
                    }
                    if (validates[key] != undefined && value == true && len > validates[key].limit) {
                        $scope.limit_validate_errors.push(validates[key]);
                    }
                })
            } else {
                let length = $scope.posts[network].title.length;
                if ($scope.posts[network].link != undefined) {
                    length = $scope.posts[network].link.length + length;
                }
                var validates = $scope.character_validate;
                if ($scope.character_validate[network] != undefined && length > $scope.character_validate[network].limit) {
                    $scope.limit_validate_errors.push(validates[network]);
                }
                return false;
            }
        }

        $scope.clear_page = function () {
            $state.go('app.socialpostsadd', {
                edit_data: false
            }, {
                    reload: true
                });
        }



        /* Get File share */
        $scope.image = null;
        $scope.imageFileName = '';
        $scope.uploadme = {};
        $scope.uploadme.src = '';

        /* Whene Change common title */
        $scope.$watchCollection('add_commonpost.title', function (new_val, old_val) {
            var selected_networks = $scope.selected_networks;
            angular.forEach(selected_networks, function (value, key) {
                if ($scope.edited_network[key] != true) {
                    $scope.posts[key].title = new_val;
                }
            });
            $scope.validate_character_limit(new_val.length);
        });

        /* Whene Change common Image */
        $scope.$watchCollection('add_commonpost.image_url', function (new_val, old_val) {
            var selected_networks = $scope.selected_networks;
            angular.forEach(selected_networks, function (value, key) {
                if ($scope.edited_network[key] != true) {
                    $scope.posts[key].image_url = new_val;
                }
            });
        });

        /* Whene Change common title */
        $scope.$watchCollection('add_commonpost.video_url', function (new_val, old_val) {
            var selected_networks = $scope.selected_networks;
            angular.forEach(selected_networks, function (value, key) {
                if ($scope.edited_network[key] != true) {
                    $scope.posts[key].video_url = new_val;
                }
            });
        });
        /* Whene Change common title */
        $scope.$watchCollection('add_commonpost.link', function (new_val, old_val) {
            var selected_networks = $scope.selected_networks;
            angular.forEach(selected_networks, function (value, key) {
                if ($scope.edited_network[key] != true) {
                    $scope.posts[key].link = new_val;
                }
            });
        });

        $scope.checkIsPostActive = function (val, network = null) {
            if (network != null) {
                if ($scope.seleced_social_tab[network] == val) {
                    return true;
                } else {
                    return false;
                }

            }
            if ($scope.commonSharResources == val) {
                return true;
            }

            return false;
        }




        /* Add Post Page */
        $scope.posts = new Object;
        $scope.seleced_social_tab = []

        $scope.select_social_network = function (network, status) {
            if (status == 1) {
                $scope.selected_networks[network] = true;
                if ($scope.posts[network] == undefined) {
                    $scope.posts[network] = []
                    $scope.posts[network].push({
                        title: '',
                        link: '',
                        image_url: '',
                        video_url: ''
                    });
                }
                //$scope.edited_network[network] = true;
                $scope.posts[network].title = $scope.add_commonpost.title;
                $scope.posts[network].link = $scope.add_commonpost.link;
                $scope.posts[network].image_url = $scope.add_commonpost.image_url;
                $scope.posts[network].video_url = $scope.add_commonpost.video_url;
            } else {
                /* Delete data if not selected*/
                delete $scope.selected_networks[network];
            }
        }
        $scope.show_time_input = false;
        $scope.showtimepicker = function (i) {

            if ($scope.show_time_input == true) {
                $scope.show_time_input = false
            } else {
                $scope.show_time_input = true;
            }

        };

        $scope.getWebsiteTitle = function (weburl) {
            var titleUrl = API.service('website-meta', API.all('social'))
            titleUrl.one().get({
                'url': weburl
            })
                .then((response) => {
                    let respo = response.plain();
                    var all_data = respo.data;
                    return all_data.data
                });
        }

        $scope.edited_social_network = function (network, status) {
            $scope.edited_network[network] = status;
        }

        $scope.select_tab_social = function (media, val) {
            $scope.seleced_social_tab[media] = val;
        }

        $scope.is_network_title_empty = function (netwrk_selected) {
            let networks_count = netwrk_selected.length;
            var empty_titles = 0;
            angular.forEach(netwrk_selected, function (value, key) {
                if ((value == true && $scope.posts[key] == undefined) || (value == true && $scope.posts[key].title == undefined) || (value == true && $scope.posts[key].title == '')) {
                    //$scope.posts[key].tile_error = true; 
                    empty_titles = empty_titles + 1;
                }
            });
            if (empty_titles > 0) {
                return true;
            }
            return false;
        }

        $scope.post_share = function (status, schedule_time = null) {
            $scope.post_add_disabled = true;
            $scope.any_title_empty = false;
            var selected_networks = $scope.selected_networks;
            var empty_titles = $scope.is_network_title_empty(selected_networks);
            if (empty_titles) {
                $scope.any_title_empty = true;
            }
            $scope.dateError = false;
            $scope.limit_validate_errors = [];
            $scope.char_limit_error = false;
            if (schedule_time != null) {
                status = 2;
            }
            var post_tumb = '';
            let is_title_empty = $scope.title_empty_check();

            var network_postssss = $scope.posts;
            var post_title = $scope.add_commonpost.title;
            if ($scope.add_commonpost.image_url != undefined) {
                var post_tumb = $scope.add_commonpost.image_url;
            }
            var post_data = new Object;
            angular.forEach(network_postssss, function (value, key) {
                if (selected_networks[key] != undefined && selected_networks[key] == true) {
                    $scope.validate_character_limit(value.title.length, key);
                }
                post_data[key] = {
                    title: value.title,
                    link: value.link,
                    image_url: value.image_url,
                    video_url: value.video_url
                }
            });


            if (schedule_time != 'publish') {
                schedule_time = moment(schedule_time).format('YYYY-MM-DD H:m:s');
                if (new Date(schedule_time) < new Date()) {
                    $scope.dateError = true;
                }
            }

            if ($scope.limit_validate_errors.length > 0) {
                $scope.char_limit_error = true;
            }


            if (is_title_empty != true && $scope.limit_validate_errors.length == 0 && empty_titles != true && $scope.dateError != true) {
                let is_selected = $scope.check_is_selected_social_profile();
                if (!is_selected) {
                    let post_social_media = API.service('publish-post', API.all('social'));
                    post_social_media.post({
                        selected_networks: selected_networks,
                        network_posts: post_data,
                        post_title: post_title,
                        post_status: status,
                        schedule_time: schedule_time,
                        post_tumb: post_tumb
                    })
                        .then(function (response) {
                            var result = response.plain();
                            if (result.errors != true) {
                                $state.go('app.socialposts', {
                                    alerts: true,
                                    message: result.data
                                });
                            }

                        }, function (response) {

                            $scope.error_message = response.data.message;
                            $scope.error_post = true;
                            $timeout(function () {
                                $scope.error_message = '';
                                $scope.error_post = false;
                            }, 5000);
                        });
                }
            }
        }

        $scope.post_edit = function (status, schedule_time = null, campign_id) {
            $scope.post_add_disabled = true;
            $scope.dateError = false;
            $scope.any_title_empty = false;
            $scope.limit_validate_errors = [];
            $scope.char_limit_error = false;
            var post_tumb = '';
            let is_title_empty = $scope.title_empty_check();
            var selected_networks = $scope.selected_networks;

            var empty_titles = $scope.is_network_title_empty(selected_networks);
            if (empty_titles) {
                $scope.any_title_empty = true;
            }
            var network_postssss = $scope.posts;
            var post_title = $scope.add_commonpost.title;
            if ($scope.add_commonpost.image_url != undefined) {
                var post_tumb = $scope.add_commonpost.image_url;
            }
            var post_data = new Object;
            angular.forEach(network_postssss, function (value, key) {
                if (selected_networks[key] != undefined && selected_networks[key] == true) {
                    $scope.validate_character_limit(value.title.length, key);
                }
                post_data[key] = {
                    title: value.title,
                    link: value.link,
                    image_url: value.image_url,
                    video_url: value.video_url
                }
            });

            if (schedule_time != 'publish') {
                schedule_time = moment(schedule_time).format('YYYY-MM-DD H:m:s');
                if (new Date(schedule_time) < new Date()) {
                    $scope.dateError = true;
                }
            }

            if (schedule_time != 'publish' && schedule_time != null) {
                schedule_time = moment(schedule_time).format('YYYY-MM-DD H:m:s');
            }
            if (status == 3 && schedule_time == 'publish' && $scope.schedule_time != undefined) {
                schedule_time = moment($scope.schedule_time).format('YYYY-MM-DD H:m:s');
                status = 2;
            } else if (schedule_time == 'publish') {
                status = 0;
            }

            if ($scope.limit_validate_errors.length > 0) {
                $scope.char_limit_error = true;
            }


            if (is_title_empty != true && $scope.limit_validate_errors.length == 0 && empty_titles != true && $scope.dateError != true) {
                let is_selected = $scope.check_is_selected_social_profile();
                if (!is_selected) {
                    let post_social_media = API.service('edit-post', API.all('social'));
                    post_social_media.post({
                        selected_networks: selected_networks,
                        network_posts: post_data,
                        post_title: post_title,
                        post_status: status,
                        schedule_time: schedule_time,
                        post_tumb: post_tumb,
                        campign_id: campign_id
                    })
                        .then(function (response) {
                            var result = response.plain();
                            if (result.errors != true) {
                                $state.go('app.socialposts', {
                                    alerts: true,
                                    message: result.data
                                });
                            }
                        }, function (response) {
                            let alert = {
                                type: 'danger',
                                'title': 'Error!',
                                msg: response.data.errors.message[0]
                            }
                            $state.go($state.current, {
                                alerts: alert
                            })
                        });
                }
            }

        }

        $scope.title_empty_check = function () {
            $scope.title_empty = false;
            if ($scope.add_commonpost.title == undefined || $scope.add_commonpost.title == '') {
                $scope.title_empty = true;
                return true;
            };
            var hashtags = $scope.findHashtags($scope.add_commonpost.title);
            if (hashtags != false) {
                angular.forEach(hashtags, function (tag) {
                    var t = tag.replace("#", "");
                    var sele_tag = t.replace(" ", "");
                    var d = $scope.hashtags;
                    var index = d.findIndex(x => x.tag === sele_tag)
                    if (index == -1 || index == false) {
                        $scope.add_new_tag(sele_tag);
                    }
                })
            }
            return false;
        }

        $scope.add_new_tag = function (tag) {
            let addhashtag = API.service('add-hash-tag', API.all('social'))
            addhashtag.post({
                tag: tag
            })
                .then(function (response) {
                    var res = response.plain();

                    if (res.data.length != 0) {
                        $scope.hashtags.push(res.data);
                    }
                })
        }


        $scope.valid_instagram = function () {
            $scope.instagram_invalid = false;
            if ($scope.network_connects.instagram.connected && $scope.selected_networks.linkedin != undefined) {
                if ($scope.posts.instagram.image_url != undefined) {
                    return true;
                }
                return true;
            }
            $scope.instagram_invalid = true;
            $timeout(function () {
                $scope.instagram_invalid = false;
            }, 5000);
            jQuery("body").animate({
                scrollTop: $("#instagram_invalid").offset().top
            }, "slow");
            return false;
        }

        $scope.check_is_selected_social_profile = function () {
            $scope.social_media_selected = false;
            let selected_networks = new Object;
            selected_networks = $scope.selected_networks;
            var keys = Object.keys(selected_networks);
            var len = keys.length;

            if (len == 0) {
                $scope.social_media_selected = true;
                return true;
            } else {
                $scope.social_media_selected = false;
                return false;
            }
        }

        $scope.$watch('files', function () {
            $scope.upload($scope.files);

        });
        $scope.$watch('file', function () {
            if ($scope.file != null) {
                $scope.upload($scope.file);
                $scope.files = [$scope.file];
            }
        });
        $scope.log = '';

        $scope.upload = function (files, media) {
            var token = $window.localStorage.satellizer_token
            if (files && files.length) {
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    if (!file.$error) {
                        Upload.upload({
                            url: '/api/social/upload-social-image',
                            data: {
                                social_image: file,
                                network: media
                            },
                            beforeSend: function (xhr) {
                                xhr.setRequestHeader("Authorization",
                                    "Bearer " + token);
                            },
                            error: function (err) {
                                let data = []
                                return JSON.stringify(data);
                            }

                        }).then(function (resp) {
                            var file_path = resp.data.data.path;
                            var network = resp.data.data.network;
                            if (network == 'common') {
                                $scope.add_commonpost.image_url = file_path;

                            } else {
                                $scope.posts[network].image_url = file_path;
                            }
                        }, function (respd) {


                        });
                    }
                }
            }
        };

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }



        this.remove_network = function (network) {
            let API = this.API
            let $state = this.$state
            var $window = this.$window
            swal({
                title: 'Do you want to remove newtwork ?',
                text: 'You will not be able to recover this data!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, remove it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                API.one('social').one('network', network).remove()
                    .then(function (response) {
                        let data_res = response.plain()
                        swal({
                            title: 'Deleted!',
                            text: 'Post has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            $state.reload()
                        })
                    })
            })
        }

        /* ******************** Edit Social Post ******************** */
        this.edit = function (edit_id) {
            $scope.edit_post_id = edit_id;
            var SocialProfile = API.service('campaign-details', API.all('social'))
            SocialProfile.one(edit_id).get()
                .then((response) => {
                    let edit_dta = response.plain();
                    let campign_info = edit_dta.data.campign;
                    this.edit_campign(campign_info);
                    $scope.active = 3;
                });
        }

        this.edit_campign = function (campign_info) {

            $scope.edit_post_id = campign_info.id;
            $scope.edit_post = true;
            /*  Post Common */
            $scope.add_commonpost.image_url = null;
            $scope.add_commonpost.title = campign_info.title_post;
            if (campign_info.post_thumb != '') {
                $scope.add_commonpost.image_url = campign_info.post_thumb;
            }

            $scope.edit_status = campign_info.status;
            if (campign_info.status == 2) {
                $scope.show_time_input = true;
            }

            if (campign_info.schedule_time != null) {
                $scope.schedule_time = new Date(campign_info.schedule_time);
            }

            if (campign_info.posts.length > 0) {
                if (campign_info.posts[0].url != undefined && campign_info.posts[0].url != null) {
                    $scope.add_commonpost.link = campign_info.posts[0].url;
                }
                if (campign_info.posts[0].video != undefined && campign_info.posts[0].video != null) {
                    $scope.add_commonpost.video_url = campign_info.posts[0].video;
                }


            }



            let posts = campign_info.posts;
            $scope.posts = undefined;
            $scope.posts = new Object;
            angular.forEach(posts, function (value) {
                let network_name = value.meta[0].network_name;
                $scope.selected_networks[network_name] = true;
                $scope.posts[network_name] = undefined;
                $scope.posts[network_name] = []
                $scope.posts[network_name].push({
                    title: '',
                    link: '',
                    image_url: '',
                    video_url: ''
                });
                $scope.posts[network_name].title = value.body;
                $scope.posts[network_name].link = value.url;
                $scope.posts[network_name].image_url = value.img;
                $scope.posts[network_name].video_url = value.video;
                $scope.edited_network[network_name] = true;
                if ($scope.posts[network_name].image_url != null) {
                    $scope.image_upload = false;
                }
            })
        }

        /* get Latest Hashtags */
        var hashtagsapi = API.service('hash-tags', API.all('social'))
        hashtagsapi.one().get()
            .then((response) => {
                $scope.hashtag_stats = new Object;
                let respo = response.plain();
                $scope.hashtags = respo.data.hastags;
                $scope.selected_hashtag = $scope.hashtags[0].tag;
                $scope.hashtag_stats.tweets = $scope.hashtags[0].tweets;
                $scope.hashtag_stats.exposure = $scope.hashtags[0].exposure;
                $scope.hashtag_stats.retweets = $scope.hashtags[0].retweets;
            });

        $scope.select_hashtag = function () {
            var selected_hashtag = angular.element('#select_hashtag').val()
            var d = $scope.hashtags;
            var index = d.findIndex(x => x.tag === selected_hashtag)
            $scope.hashtag_stats = d[index];
            // Append Hashtag 
            $scope.add_commonpost.title = $scope.add_commonpost.title + " " + "#" + selected_hashtag;
        }

        $scope.findHashtags = function (searchText) {
            searchText = searchText + "."
            var regexp = /#[\w]+(?=\s|$)\s/g
            var result = searchText.match(regexp);
            if (result) {
                return result;
            } else {
                return false;
            }
        }

        /* If Arrive for edit post */
        if ($stateParams.edit_data) {
            let edit_data = $stateParams.edit_data
            this.edit_campign(edit_data);
        }

        if ($stateParams.generate_content) {

            let content = $stateParams.generate_content
            var des = content.description;
            if (content.description.length > 250) {
                des = content.description.substring(0, 250) + '...';
            }
            $scope.add_commonpost.title = content.title + "\n" + des;
            if (content.image != false) {
                $scope.add_commonpost.image_url = content.image;
            }
            if (content.link) {
                $scope.add_commonpost.link = content.link;
            }
            if (content.video_link) {
                $scope.add_commonpost.video_url = content.video_link;
            }

        }

        var that = this;

        $scope.isOpen = false;

        $scope.openCalendar = function (e) {
            e.preventDefault();
            e.stopPropagation();

            $scope.isOpen = true;
        };
        $scope.doShow = function (key) {
            if (angular.isDefined($scope.buttonBar[key].show))
                return $scope.buttonBar[key].show;
            else
                return uiDatetimePickerConfig.buttonBar[key].show;
        };




    }

    $onInit() { }
}

export const SocialPostsAddComponent = {
    templateUrl: './views/app/pages/social-connect/social-posts-add.component.html',
    controller: SocialPostsAddController,
    controllerAs: 'vm',
    bindings: {}
}
