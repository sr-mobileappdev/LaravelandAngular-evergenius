class SocialConnectController {
    constructor(API, $state, AclService, $scope, Upload, $window, $timeout, $compile, DTOptionsBuilder, DTColumnBuilder) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.alerts = []
        $scope.add_commonpost = []
        $scope.add_commonpost.title = ''
        $scope.selected_networks = new Object;
        $scope.edited_network = new Object;
        $scope.post_published = false;
        $scope.post_view_cat = 'history';
        $scope.post_msg = '';
        $scope.edit_post = false;
        $scope.edit_status = false;
        $scope.post_add_disabled = false;
        /* Get Social Connect Settings */
        var isSocialConnect = API.service('is-profiles-connected', API.all('social'))
        isSocialConnect.one().get()
            .then((response) => {
                let respo = response.plain();
                $scope.network_connects = respo.data;
            });

        /* Get File share */
        $scope.image = null;
        $scope.imageFileName = '';
        $scope.uploadme = {};
        $scope.uploadme.src = '';
        $scope.checkIsPostActive = function (val) {
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
                    $scope.posts[network].push({ title: '', link: '', image_url: '', video_url: '' });
                }
                $scope.edited_network[network] = true;
                $scope.posts[network].title = $scope.add_commonpost.title;
                $scope.posts[network].link = $scope.add_commonpost.link;
                $scope.posts[network].image_url = $scope.add_commonpost.image_url;
                $scope.posts[network].video_url = $scope.add_commonpost.video_url;
            } else {
                /* Delete data if not selected*/
                delete $scope.selected_networks[network];
            }

        }

        $scope.edited_social_network = function (network, status) {
            $scope.edited_network[network] = status;
        }

        $scope.select_tab_social = function (media, val) {
            $scope.seleced_social_tab[media] = val;
        }

        $scope.post_share = function (status, schedule_time = null) {
            $scope.post_add_disabled = true;
            if (schedule_time != null) {
                status = 2;
            }
            var post_tumb = '';
            let is_title_empty = $scope.title_empty_check();
            var selected_networks = $scope.selected_networks;
            var network_postssss = $scope.posts;
            var post_title = $scope.add_commonpost.title;
            if ($scope.add_commonpost.image_url != undefined) {
                var post_tumb = $scope.add_commonpost.image_url;
            }
            var post_data = new Object;
            angular.forEach(network_postssss, function (value, key) {

                post_data[key] = { title: value.title, link: value.link, image_url: value.image_url, video_url: value.video_url }
            });
            var valid_instagram = $scope.valid_instagram();

            if (is_title_empty != true && valid_instagram != false) {
                let is_selected = $scope.check_is_selected_social_profile();
                if (!is_selected) {
                    let post_social_media = API.service('publish-post', API.all('social'));
                    post_social_media.post({ selected_networks: selected_networks, network_posts: post_data, post_title: post_title, post_status: status, schedule_time: schedule_time, post_tumb: post_tumb })
                        .then(function (response) {
                            var result = response.plain();
                            if (result.errors != true) {
                                $scope.post_published = true;
                                $scope.post_msg = result.data;
                                $timeout(function () {
                                    $scope.post_published = false;
                                    $scope.post_msg = '';
                                }, 5000);

                            }

                        }, function (response) {
                            let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                            $state.go($state.current, { alerts: alert })
                        });
                }
            }
        }

        $scope.post_edit = function (status, schedule_time = null, campign_id) {
            $scope.post_add_disabled = true;
            var post_tumb = '';
            let is_title_empty = $scope.title_empty_check();
            var selected_networks = $scope.selected_networks;
            var network_postssss = $scope.posts;
            var post_title = $scope.add_commonpost.title;
            if ($scope.add_commonpost.image_url != undefined) {
                var post_tumb = $scope.add_commonpost.image_url;
            }
            var post_data = new Object;
            angular.forEach(network_postssss, function (value, key) {
                post_data[key] = { title: value.title, link: value.link, image_url: value.image_url, video_url: value.video_url }
            });

            if (is_title_empty != true) {
                let is_selected = $scope.check_is_selected_social_profile();
                if (!is_selected) {
                    let post_social_media = API.service('edit-post', API.all('social'));
                    post_social_media.post({ selected_networks: selected_networks, network_posts: post_data, post_title: post_title, post_status: status, schedule_time: schedule_time, post_tumb: post_tumb, campign_id: campign_id })
                        .then(function (response) {
                            var result = response.plain();
                            if (result.errors != true) {
                                $scope.post_published = true;
                                $scope.post_msg = result.data;
                                $timeout(function () {
                                    $scope.post_published = false;
                                    $scope.post_msg = '';
                                }, 5000);

                            }

                        }, function (response) {
                            let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                            $state.go($state.current, { alerts: alert })
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
            addhashtag.post({ tag: tag })
                .then(function (response) {
                    var res = response.plain();

                    if (res.data.length != 0) {
                        $scope.hashtags.push(res.data);
                    }
                })
        }


        $scope.valid_instagram = function () {
            $scope.instagram_invalid = false;
            if ($scope.network_connects.instagram.connected && $scope.posts.instagram.image_url != undefined) {
                return true;
            }
            $scope.instagram_invalid = true;
            $timeout(function () {
                $scope.instagram_invalid = false;
            }, 5000);
            jQuery("body").animate({ scrollTop: $("#instagram_invalid").offset().top }, "slow");
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
                            }, error: function (err) {
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

        this.post_view_cat = function (type) {
            $scope.post_view_cat = type;
            $scope.tableId = "reviews_list";
            var token = $window.localStorage.satellizer_token
            $scope.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/social',
                    type: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        var status = $scope.post_view_cat;
                        if (status == 'schedule') {
                            status = 2;
                        } else if (status == 'history') {
                            status = 1;
                        } else if (status == 'draft') {
                            status = 0;
                        }
                        data.customFilter = { status: status };
                        return JSON.stringify(data);
                    }
                })
                .withLanguage({
                    processing: function () {
                        //xhrcfpLoadingBarProvider.includeSpinner = true;
                    }
                })
                .withDataProp('data')
                .withOption('serverSide', true)
                .withOption('processing', true)
                .withOption('createdRow', createdRow)
                .withColReorder()
                .withColReorderOption('iFixedColumnsRight', 1)
                .withColReorderCallback(function () {
                })
                .withOption('createdRow', function (row) {
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('responsive', true)
                .withOption('aaSorting', [
                    [0, 'desc']
                ])
                .withBootstrap()

            $scope.dtColumns = [
                DTColumnBuilder.newColumn(null).withTitle('Profile image').renderWith(function (data) {

                    var img = "img/img-social-post.png";
                    if (data.post_thumb != '') {
                        img = data.post_thumb;
                    }
                    return '<div class="social-post-image">' +
                        '<img src="' + img + '" style="width: 111px;">' +
                        '</div>';

                }),
                DTColumnBuilder.newColumn(null).withTitle('SITE').renderWith(function (data) {
                    var netwroks = data.netwroks.split(', ');
                    var netw_logo = '';
                    for (var i = netwroks.length - 1; i >= 0; i--) {
                        var netw = netwroks[i];
                        if (netw == 'facebook') {
                            netw_logo = '<li><img src="img/icon-facebook.png" width="36" height="36"> Facebook Profile</li>' + netw_logo;
                        }
                        if (netw == 'facebook_pages') {
                            netw_logo = '<li><img src="img/icon-facebook.png" width="36" height="36"> Facebook Page</li>' + netw_logo;
                        }
                        if (netw == 'facebook_groups') {
                            netw_logo = '<li><img src="img/icon-facebook.png" width="36" height="36"> Facebook Group</li>' + netw_logo;
                        }
                        if (netw == 'twitter') {
                            netw_logo = '<li><img src="img/icon-twitter.png" width="36" height="36"> Twitter</li>' + netw_logo;
                        }
                        if (netw == 'google_plus') {
                            netw_logo = '<li><img src="img/icon-google-plus.png" width="36" height="36"> Google Plus</li>' + netw_logo;
                        }
                        if (netw == 'instagram') {
                            netw_logo = '<li><img src="img/icon-instagram.png" width="36" height="36"> Instagram</li>' + netw_logo;
                        }
                        if (netw == 'linkedin') {
                            netw_logo = '<li><img src="img/icon-linkedin.png" width="36" height="36"> LinkedIn</li>' + netw_logo;
                        }
                    }
                    return '<div class="post-info">' + '<p>' + data.title_post + '</p>' + '<div class="social-sharing">' + '<ul>' + netw_logo + '</ul>' + '</div>' + '</div>';
                }).withOption('width', '700px'),
                DTColumnBuilder.newColumn(null).withTitle('SITE').renderWith(function (data) {
                    var statuses = ['DRAFT', 'PUBLISHED', 'SCHEDULED', 'QUEUED'];
                    var schedule_date = '';
                    var schedule_time = '';
                    var controls = "";
                    if (data.status == 2 && data.schedule_time != null) {
                        schedule_date = moment(data.schedule_time).format('MMM DD, YYYY <br> hh:mm a');
                        schedule_time = moment(data.schedule_time).format('hh:mm a');
                    }

                    if (data.status != '1') {
                        controls = `<div class="schedule-control"><a class="icon-edit" ng-click="vm.edit(${data.id})"></a><a class="icon-delete" ng-click="vm.delete(${data.id})" ></a></div>`;
                    }


                    return '<div class="schedule-col">' + '<h3>' + statuses[data.status] + '</h3>' + '<small>' + schedule_date + ' <br>' + '</small>' + controls + '</div>';
                }).withOption('width', '200px')

            ]

            $scope.displayTable = true
            $('#' + $scope.tableId).DataTable().ajax.reload();
        }

        this.post_view_cat('history');
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
            $scope.edit_post = true;
            /*  Post Common */
            $scope.add_commonpost.image_url = null;
            $scope.add_commonpost.title = campign_info.title_post;
            if (campign_info.post_thumb != '') {
                $scope.add_commonpost.image_url = campign_info.post_thumb;
            }

            $scope.edit_status = campign_info.status;

            if (campign_info.schedule_time != null) {
                $scope.schedule_time = moment(campign_info.schedule_time).format('MM/DD/YYYY h:m A');
            }


            let posts = campign_info.posts;
            $scope.posts = undefined;
            $scope.posts = new Object;
            angular.forEach(posts, function (value) {
                let network_name = value.meta[0].network_name;
                $scope.selected_networks[network_name] = true;
                $scope.posts[network_name] = undefined;
                $scope.posts[network_name] = []
                $scope.posts[network_name].push({ title: '', link: '', image_url: '', video_url: '' });
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
                let respo = response.plain();
                $scope.hashtags = respo.data.hastags;
            });

        $scope.select_hashtag = function () {
            var selected_hashtag = angular.element('#select_hashtag').val()
            var d = $scope.hashtags;
            var index = d.findIndex(x => x.tag === selected_hashtag)
            $scope.hashtag_stats = d[index];
            // Append Hashtag 
            $scope.add_commonpost.title = $scope.add_commonpost.title + "#" + selected_hashtag + " ";
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
        $scope.search_query = "";
        $scope.empty_keyword = false;
        $scope.search_query_type = "images";



        $scope.load_generate_content = function (type, query) {
            let gerate_con_api = API.service('latest-feeds', API.all('social'));
            gerate_con_api.post({ keyword: query, query_type: type })
                .then(function (response) {
                    var result = response.plain();
                    $scope.news_feed = result.data;
                }, function (response) {
                    let alert = { type: 'danger', 'title': 'Error!', msg: response.data.errors.message[0] }
                    $state.go($state.current, { alerts: alert })
                });

        }
        $scope.query_search = function () {
            var searchkeyword = angular.element('#search_keyword').val();
            var search_type = angular.element('#select_search_type').val();
            if (searchkeyword == "") {
                $scope.empty_keyword = true;
            } else {
                var search_query = searchkeyword;
                $scope.load_generate_content(search_type, searchkeyword);
            }
        }
        $scope.load_generate_content("images", "health");





    }
    $onInit() { }
}

export const SocialConnectComponent = {
    templateUrl: './views/app/pages/social-connect/social-connect.component.html',
    controller: SocialConnectController,
    controllerAs: 'vm',
    bindings: {}
}
