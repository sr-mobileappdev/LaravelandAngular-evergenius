class SocialPostsController {
    constructor($stateParams, AclService, API, $state, $scope, Upload, $window, $timeout, $compile, DTOptionsBuilder, DTColumnBuilder, $uibModal) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.alerts = []
        $scope.fb_insights = new Object;
        var isSocialConnect = API.service('is-profiles-connected', API.all('social'))

        this.can = AclService.can
        if (!this.can('social.connect')) {
            $state.go('app.unauthorizedAccess');
        }

        isSocialConnect.one().get()
            .then((response) => {
                let respo = response.plain();
                var network_connects = respo.data;
                var cnnected_ntwrk = [];
                angular.forEach(network_connects, function (data, key) {
                    if (data.connected == true) {
                        cnnected_ntwrk.push(key);
                    }
                })
                if (cnnected_ntwrk.length == 0) {
                    $state.go('app.socialconnectprofiles');
                }
            });
        /* If Arrive for edit post */
        if ($stateParams.alerts) {
            $scope.succcess_message = $stateParams.message;
            $scope.show_message = true;
            $timeout(function () {
                $scope.show_message = false;
            }, 5000);

        }

        $state.get_fb_insights = function (post_id) {
            var post_get_insights = API.service('fb-insights', API.all('social'))
            return post_get_insights.one().get({ "post_id": post_id })
                .then((response) => {
                    let res = response.plain();
                    let data_rs = res.data;
                    angular.element('#sa_' + post_id).html("<span><i class='fa fa-thumbs-o-up'></i> Like: " + data_rs[2].post_reactions_like_total + "</span>  <span><i class='fa fa-bar-chart-o'></i> Impressions: " + data_rs[1].post_consumptions + "</span> <span><i class='fa  fa-area-chart'></i> Reached : " + data_rs[0].post_impressions_unique + "</span>");
                    return res.data;
                });
        }

        $scope.getInsigts = function (post_id) {
            let ser = $state.get_fb_insights(post_id);;
            return ser.then(function (val) {
                return val;
            });
        }


        this.post_view_cat = function (type) {
            $scope.post_view_cat = type;
            if (type == 'schedule') {
                $scope.custom_empty_msg = 'No post scheduled';
            }
            if (type == 'history') {
                $scope.custom_empty_msg = 'No post in History';
            }
            if (type == 'draft') {
                $scope.custom_empty_msg = 'No post in Drafts';
            }
            if (type == 'queued') {
                $scope.custom_empty_msg = 'No queued posts';
            }

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
                        else if (status == 'queued') {
                            status = 3;
                        }
                        data.customFilter = { status: status };
                        return JSON.stringify(data);
                    }, error: function (err) {
                        let data = []
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
                .withOption('oLanguage', { sEmptyTable: $scope.custom_empty_msg })
                .withOption('stateSave', true)
                .withOption('stateSaveCallback', function (settings, data) {
                    localStorage.setItem('DataTables_' + settings.sInstance, JSON.stringify(data));
                })
                .withOption('stateLoadCallback', function (settings, data) {
                    return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance));
                })
                .withColReorder()
                .withColReorderOption('iFixedColumnsRight', 1)
                .withColReorderCallback(function () {

                })
                .withOption('createdRow', function (row) {
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('responsive', true)
                .withOption('aaSorting', false)
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
                    var netw_logo = '';
                    var error_nworks = '';
                    var post_id = data.id;
                    var err_n = [];
                    if (data.error_networks != null) {
                        var er_netwroks_users = null;
                        if (data.err_network_users != null) {
                            var er_netwroks_users = data.err_network_users.split(', ');
                        }
                        var er_ntwrks = data.error_networks.split(', ');
                        err_n = er_ntwrks;
                        for (var i = 0; i <= er_ntwrks.length - 1; i++) {
                            var netw_e = er_ntwrks[i];
                            var ntwrk_usr = '';

                            if (er_netwroks_users != null) {
                                var ntwrk_usr = er_netwroks_users[i];
                            }

                            if (netw_e == 'facebook_pages') {
                                error_nworks = `<li class="social_post_error" uib-tooltip="Facebook page" tooltip-placement="top" ng-click="error_network_modal(${data.id},'facebook_pages')"><img src="img/icon-facebook.png" width="36" height="36"> <div><span>` + ntwrk_usr + ` <i class="fa fa-question-circle"></i> </span></div> </li>` + error_nworks;
                            }
                            if (netw_e == 'facebook') {
                                error_nworks = `<li class="social_post_error" uib-tooltip="Facebook profile" tooltip-placement="top" ng-click="error_network_modal(${data.id},'facebook')"><img src="img/icon-facebook.png" width="36" height="36"> <div><span>` + ntwrk_usr + ` <i class="fa fa-question-circle"></i> </span></div> </li>` + error_nworks;
                            }
                            if (netw_e == 'facebook_groups') {
                                error_nworks = `<li class="social_post_error" uib-tooltip="Facebook Group" tooltip-placement="top" ng-click="error_network_modal(${data.id},'facebook_groups')"><img src="img/icon-facebook.png" width="36" height="36"> <div><span>` + ntwrk_usr + ` <i class="fa fa-question-circle"></i> </span></div> </li>` + error_nworks;
                            }
                            if (netw_e == 'twitter') {
                                error_nworks = `<li class="social_post_error" uib-tooltip="Twitter" tooltip-placement="top" ng-click="error_network_modal(${data.id},'twitter')"><img src="img/icon-twitter.png" width="36" height="36"> <div><span>` + ntwrk_usr + ` <i class="fa fa-question-circle"></i></span></div> </li>` + error_nworks;
                            }
                            if (netw_e == 'google_plus') {
                                error_nworks = `<li class="social_post_error" uib-tooltip="Google plus" tooltip-placement="top" ng-click="error_network_modal(${data.id},'google_plus')"><img src="img/icon-google-plus.png" width="36" height="36">  <div><span>` + ntwrk_usr + ` <i class="fa fa-question-circle"></i></span></div> </li>` + error_nworks;
                            }
                            if (netw_e == 'instagram') {
                                error_nworks = `<li class="social_post_error" uib-tooltip="Instagram" tooltip-placement="top" ng-click="error_network_modal(${data.id},'instagram')"><img src="img/icon-instagram.png" width="36" height="36">  <div><span>` + ntwrk_usr + ` <i class="fa fa-question-circle"></i></span></div> </li>` + error_nworks;
                            }
                            if (netw_e == 'linkedin') {
                                error_nworks = `<li class="social_post_error" uib-tooltip="LinkedIn" tooltip-placement="top" ng-click="error_network_modal(${data.id},'linkedin')"><img src="img/icon-linkedin.png" width="36" height="36">  <div><span>` + ntwrk_usr + `<i class="fa fa-question-circle"></i></span> </div> </li>` + error_nworks;
                            }
                            if (i == 0) {
                                error_nworks = error_nworks + "</ul>";
                            }
                        }
                    }

                    if (data.netwroks != null) {
                        var netwroks = data.netwroks.split(', ');
                        var netwroks_users = data.network_user_name.split(', ');

                        let exists = netwroks.indexOf("facebook_pages");
                        if (exists > 0) {
                            netwroks.splice(exists, 1);
                            netwroks.unshift("facebook_pages");
                        }

                        var netw_logo = '';

                        for (var i = 0; i <= netwroks.length - 1; i++) {
                            var netw = netwroks[i];
                            var ntwrk_usr = netwroks_users[i];

                            if (netw == 'facebook_pages' && err_n.indexOf(netw_e) == -1) {
                                let statics = "";
                                var fb_net_post_id = data.fb_net_post_id;
                                if (fb_net_post_id !== null && fb_net_post_id != false) {
                                    $scope.getInsigts(fb_net_post_id);
                                    statics = "<div id='sa_" + fb_net_post_id + "' class='facebook-history'><span><i class='fa fa-thumbs-o-up'></i> Like: <i class='fa fa-clock-o'></i></span>  <span><i class='fa fa-bar-chart-o'></i> Impressions: <i class='fa fa-clock-o'></i></span> <span><i class='fa  fa-area-chart'></i> Reached : <i class='fa fa-clock-o'></i></span></div>";
                                }
                                netw_logo = '<ul class="facebook-page"><li uib-tooltip="Facebook page" tooltip-placement="top" ><img src="img/icon-facebook.png" width="36" height="36"> <div><span>' + ntwrk_usr + '</span>' + netw_logo + " " + statics + "</div></li></ul>";
                            }
                            if (netw != 'facebook_pages' && i == netwroks.length - 2 && err_n.indexOf(netw_e) == -1) {
                                netw_logo = netw_logo + "<ul>";
                            }
                            if (netw == 'facebook' && err_n.indexOf(netw_e) == -1) {
                                netw_logo = '<li uib-tooltip="Facebook profile" tooltip-placement="top"><img src="img/icon-facebook.png" width="36" height="36"> <div><span>' + ntwrk_usr + '</span></div> </li>' + netw_logo;
                            }
                            if (netw == 'facebook_groups' && err_n.indexOf(netw_e) == -1) {
                                netw_logo = '<li uib-tooltip="Facebook Group" tooltip-placement="top"><img src="img/icon-facebook.png" width="36" height="36"> <div><span>' + ntwrk_usr + '</span></div> </li>' + netw_logo;
                            }
                            if (netw == 'twitter' && err_n.indexOf(netw_e) == -1) {
                                netw_logo = '<li uib-tooltip="Twitter" tooltip-placement="top"><img src="img/icon-twitter.png" width="36" height="36"> <div><span>' + ntwrk_usr + '</span></div> </li>' + netw_logo;
                            }
                            if (netw == 'google_plus' && err_n.indexOf(netw_e) == -1) {
                                netw_logo = '<li uib-tooltip="Google plus" tooltip-placement="top" ><img src="img/icon-google-plus.png" width="36" height="36">  <div><span>' + ntwrk_usr + '</span></div> </li>' + netw_logo;
                            }
                            if (netw == 'instagram' && err_n.indexOf(netw_e) == -1) {
                                netw_logo = '<li uib-tooltip="Instagram" tooltip-placement="top"><img src="img/icon-instagram.png" width="36" height="36">  <div><span>' + ntwrk_usr + '</span></div> </li>' + netw_logo;
                            }
                            if (netw == 'linkedin' && err_n.indexOf(netw_e) == -1) {
                                netw_logo = '<li uib-tooltip="LinkedIn" tooltip-placement="top"><img src="img/icon-linkedin.png" width="36" height="36">  <div><span>' + ntwrk_usr + '</span></div> </li>' + netw_logo;
                            }
                            if (i == 0) {
                                netw_logo = netw_logo + "</ul>";
                            }
                        }
                    }
                    return '<div class="post-info">' + '<p>' + data.title_post + '</p>' + '<div class="social-sharing">' + '<ul>' + netw_logo + error_nworks + '</ul>' + '</div>' + '</div>';

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


                    return '<div class="schedule-col">' + '<h3 class="' + statuses[data.status].toLowerCase() + '">' + statuses[data.status] + '</h3>' + '<small>' + schedule_date + ' <br>' + '</small>' + controls + '</div>';
                }).withOption('width', '200px')

            ]

            $scope.displayTable = true
            $('#' + $scope.tableId).DataTable().ajax.reload();
        }

        this.post_view_cat('schedule');
        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }

        this.edit = function (edit_id) {
            $scope.edit_post_id = edit_id;
            var SocialProfile = API.service('campaign-details', API.all('social'))
            SocialProfile.one(edit_id).get()
                .then((response) => {
                    let edit_dta = response.plain();
                    let campign_info = edit_dta.data.campign;
                    $state.go('app.socialpostsadd', { edit_data: campign_info });
                });
        }



        this.delete = function (cam_id) {
            let API = this.API
            let $state = this.$state
            var $window = this.$window
            swal({
                title: 'Do you want to remove Post ?',
                text: 'You will not be able to recover this data!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, remove it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                API.one('social').one('campaign', cam_id).remove()
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

        /* Modal For Errors in page */
        $scope.error_network_modal = function (campaign_id, error_nworks) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/social-connect/social-posts-error.modal.html',
                controller: modalController,
                controllerAs: 'vm',
                bindings: {},
                windowClass: 'my-modal-popup',
                resolve: {
                    campaign_id: function () {
                        return campaign_id;
                    },
                    error_network: function () {
                        return error_nworks;
                    }
                }
            });
        }

        this.dtInstanceCallback = function (dtInstance) {
            this.dtInstance = dtInstance;
            dtInstance.DataTable.on('draw.dt', () => {
                let elements = angular.element("#" + dtInstance.id + " .ng-scope");
                angular.forEach(elements, (element) => {
                    $compile(element)($scope)
                })
            });
        }


    }

    $onInit() { }
}
class modalController {

    constructor($stateParams, $scope, $state, API, $uibModal, Upload, campaign_id, error_network, $window, $timeout, $compile, $uibModalInstance) {
        var error_names = {
            facebook: 'Facebook Profile',
            facebook_pages: 'Facebook Page',
            facebook_groups: 'Facebook Group',
            twitter: 'Twitter',
            google_plus: 'Google Plus',
            linkedin: 'LinkedIn',
            instagram: 'Instagram Profile',
        };

        $scope.title_modal = error_names[error_network];

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        var errorResp = API.service('network-error', API.all('social'))
        errorResp.one().get({ campaign_id: campaign_id, error_network: error_network })
            .then((response) => {
                var res = response.plain();
                $scope.n_error = res.data.error;
            });
    }
}

export const SocialPostsComponent = {
    templateUrl: './views/app/pages/social-connect/social-posts.component.html',
    controller: SocialPostsController,
    controllerAs: 'vm',
    bindings: {}
}
