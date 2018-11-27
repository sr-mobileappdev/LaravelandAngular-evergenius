class ReviewsController {
    constructor($scope, $http, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, $uibModal, $filter, $sce) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.isdelseleted = false;
        this.formSubmitted = false
        this.API = API
        this.alerts = []
        this.window = $window;
        this.publishers = [];
        this.start_date = moment()
            .subtract(10, 'days'), moment()
        this.end_date = moment()
        this.min_date = moment()
            .subtract(30, 'days'), moment()
        this.max_date = moment()
        var tmpList = [];
        $scope.list = tmpList;
        $scope.sortingLog = [];
        $scope.select_review_status = '';
        $scope.review_data = [];

        var token = $window.localStorage.satellizer_token
        var req = {
            method: 'GET',
            url: '/api/reviews',
            headers: {
                'Authorization': "Bearer " + token
            }
        }

        $http(req).then(function (res) {
            $scope.review_data_per = res.data.data.reviews;
            $scope.review_data = res.data.data.reviews;
            $scope.search();
        }, function () {

        });

        $scope.formatDate = function (date) {
            if (date == '0000-00-00 00:00:00') {
                return 'Date not found.';
            }
            var dateOut = moment(date).format('ddd, MMM D YYYY');
            var TimeOut = moment(date).format('hh:mm a');

            return dateOut + '<br>' + TimeOut;

        };

        $scope.uCanTrust = function (string) {
            return $sce.trustAsHtml(string);
        }

        $scope.$watch('search_query', function (val) {

            if ($scope.select_review_status_udate != undefined && $scope.select_review_status_udate != '') {
                var val_1 = $scope.select_review_status_udate;
                if (val_1 != undefined && val_1 != '' && val_1 != 4) {
                    if (val_1 == 3) {
                        val_1 = null;
                    }
                    $scope.review_data = $filter('filter')($scope.review_data, {
                        hide: val_1
                    });
                }
                if (val_1 != undefined && val_1 == 4) {
                    $scope.review_data = $filter('filter')($scope.review_data, {
                        featured: 1
                    });
                }
            }

            if ($scope.select_review_rating_update != undefined && $scope.select_review_rating_update != '') {
                var val_2 = $scope.select_review_rating_update;
                if (val_2 != undefined && val_2 != '') {
                    $scope.review_data = $filter('filter')($scope.review_data, {
                        rating: val_2
                    });
                }
            }



            if (($scope.select_review_rating_update == undefined || $scope.select_review_rating_update == '') && ($scope.select_review_status_udate == undefined || $scope.select_review_status_udate == '')) {
                $scope.review_data = $scope.review_data_per;
            }

            if (val != undefined && val != '') {
                $scope.review_data = $filter('filter')($scope.review_data, val);
            }

            $scope.search();

            $scope.review_data = $scope.review_data_per;

        });

        $scope.$watch('select_review_status_udate', function (val) {

            if ($scope.search_query != undefined && $scope.search_query != '') {
                var val_1 = $scope.search_query;
                if (val_1 != undefined && val_1 != '') {
                    $scope.review_data = $filter('filter')($scope.review_data, val_1);
                }
            }

            if ($scope.select_review_rating_update != undefined && $scope.select_review_rating_update != '') {
                var val_2 = $scope.select_review_rating_update;
                if (val_2 != undefined && val_2 != '') {
                    $scope.review_data = $filter('filter')($scope.review_data, {
                        rating: val_2
                    });
                }
            }

            if (($scope.select_review_rating_update == undefined || $scope.select_review_rating_update == '') && ($scope.search_query == undefined || $scope.search_query == '')) {
                $scope.review_data = $scope.review_data_per;
            }


            if (val != undefined && val != '' && val != 4) {
                if (val == 3) {
                    val = null;
                }
                $scope.review_data = $filter('filter')($scope.review_data, {
                    hide: val
                });
            }
            if (val != undefined && val == 4) {
                $scope.review_data = $filter('filter')($scope.review_data, {
                    featured: 1
                });
            }

            $scope.search();
            $scope.review_data = $scope.review_data_per;
        });

        $scope.$watch('select_review_rating_update', function (val) {

            if ($scope.select_review_status_udate != undefined && $scope.select_review_status_udate != '') {
                var val_1 = $scope.select_review_status_udate;
                if (val_1 != undefined && val_1 != '' && val_1 != 4) {
                    if (val_1 == 3) {
                        val_1 = null;
                    }
                    $scope.review_data = $filter('filter')($scope.review_data, {
                        hide: val_1
                    });
                }
                if (val_1 != undefined && val_1 == 4) {
                    $scope.review_data = $filter('filter')($scope.review_data, {
                        featured: 1
                    });
                }
            }

            if ($scope.search_query != undefined && $scope.search_query != '') {
                var val_2 = $scope.search_query;
                if (val_2 != undefined && val_2 != '') {
                    $scope.review_data = $filter('filter')($scope.review_data, val_2);
                }
            }

            if (($scope.select_review_status_udate == undefined || $scope.select_review_status_udate == '') && ($scope.search_query == undefined || $scope.search_query == '')) {
                $scope.review_data = $scope.review_data_per;
            }
            if (val != undefined && val != '') {
                $scope.review_data = $filter('filter')($scope.review_data, {
                    rating: val
                });
            }
            $scope.search();
            $scope.review_data = $scope.review_data_per;
        });

        var fixHelper = function (e, ui) {
            ui.children()
                .each(function () {
                    $(this)
                        .width($(this)
                            .width());
                });
            return ui;
        };

        $scope.sortableOptions = {
            helper: fixHelper,
            update: function (e, ui) {
                var logEntry = tmpList.map(function (i) {
                    return i.value;
                })
                    .join(', ');
                $scope.sortingLog.push('Update: ' + logEntry);
            },
            stop: function (e, ui) {
                for (var index in $scope.pagedItems[$scope.currentPage]) {

                    $scope.pagedItems[$scope.currentPage][index].order_review = index;
                }

                $scope.logModels();
            }
        };

        $scope.logModels = function () {
            var logEntry = $scope.pagedItems[$scope.currentPage].map(function (i) {
                return i.order_review + ':' + i.id + '';
            })
                .join(',');
            var order_arr = logEntry.split(",");
            var order_post = [];
            angular.forEach(order_arr, function (data, key) {
                var order_d = data.split(":");
                order_post.push({
                    order_review: parseInt(order_d[0]) + 1,
                    id: order_d[1]
                });
            });
            $scope.update_order_review(order_post);
        }

        $scope.update_order_review = function (order) {
            let update_review = API.service('change-order', API.all('reviews'))
            update_review.post({
                review_order: order
            })
                .then((response) => {

                })
        }

        $scope.updateReviewStatus = function (id, status, $index, $state) {


            var post_info = {
                id: id,
                status: status
            }
            let update_review_status = API.service('review-status', API.all('reviews'))
            update_review_status.post(post_info)
                .then((response) => {
                    var status_h = 0;
                    if (status == 'hide') {
                        status_h = 1;
                    }

                    var obj = _.find($scope.pagedItems[$scope.currentPage], function (obj) { return obj.id === id; });
                    obj.hide = status_h;
                })
        }

        $scope.featureReview = function (id, status, $index) {
            var update_feature = null;
            if (status == null) {
                update_feature = 1;
            }
            var post_info = {
                id: id,
                status: update_feature
            }
            let update_review_status = API.service('review-feature-update', API.all('reviews'))
            update_review_status.post(post_info)
                .then((response) => {
                    var status_h = null;
                    if (update_feature != null) {
                        status_h = 1;
                    }
                    var obj = _.find($scope.pagedItems[$scope.currentPage], function (obj) { return obj.id === id; });
                    obj.featured = status_h;
                })
        }


        $scope.pieRandomColors = ['#2ecc71', '#1abc9c', '#3498db', '#9b59b6', '#34495e', '#16a085', '#27ae60', '#2980b9', '#2980b9', '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22', '#e74c3c', '#ecf0f1', '#d35400', '#c0392b']
        let profile_publishers = API.service('publisher-list', API.all('profilelisting'))
        profile_publishers.one("")
            .get()
            .then((response) => {
                let dataSet = response.plain()
                let publishers = []
                angular.forEach(dataSet.data.response.publishers, function (value) {
                    publishers[value.id] = value.name.toString();
                })
                this.publishers = publishers;

            });

        this.publisherName = function (publisherID) {
            if (publisherID == null || publisherID == '') {
                return 'Website';
            }
            let all_publishers = this.publishers;
            if (all_publishers[publisherID] == undefined) {
                return publisherID;
            }
            return all_publishers[publisherID];
        }

        $scope.datePicker = {
            startDate: this.start_date,
            endDate: this.end_date
        };
        $scope.analyticsChartColours = [{
            fillColor: '#fcc5ae',
            strokeColor: '#D2D6DE',
            pointColor: '#000000',
            pointStrokeColor: '#fff',
            pointHighlightFill: '#fff',
            pointHighlightStroke: 'rgba(148,159,177,0.8)'
        }]


        var reviewsAPI = API.service('site-reputation', API.all('reviews'))
        reviewsAPI.one('')
            .get()
            .then((response) => {
                let data_res = response.plain();
                $scope.total_reviews = data_res.data.total_reviws;
                $scope.avg_rating = data_res.data.avg_rating;
                $scope.review_platforms = data_res.data.review_platforms;
                var review_labels = [];
                var review_vlues = [];
                var review_Colors = [];
                var indx = 0;

                angular.forEach(data_res.data.review_platforms, function (value, key) {

                    if (value.publisher_id != null) {
                        review_labels.push(value.publisher_id);
                        review_vlues.push(value.total);
                        review_Colors.push($scope.pieRandomColors[indx]);
                    }
                    indx++;
                });
                $scope.review_labels = review_labels;
                $scope.review_values = review_vlues;
                $scope.review_Colors = review_Colors;
            });


        $scope.play_video = function (video_url) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/reviews/main/video-modal.html',
                controller: ReviewModalVideoController,
                controllerAs: 'vm',
                bindings: {},
                backdrop: 'static',
                //size: 'lg',
                windowClass: 'my-modal-popup',
                resolve: {
                    video_url: function () {
                        return video_url;
                    }
                }
            });
        }

        $scope.viewFullReview = function (r_content) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/reviews/main/content-modal.html',
                controller: ReviewModalContentController,
                controllerAs: 'vm',
                bindings: {},
                backdrop: 'static',
                //size: 'lg',
                windowClass: 'my-modal-popup',
                resolve: {
                    r_content: function () {
                        return r_content;
                    }
                }
            });
        }
        $scope.play_audio = function (audio_url) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/reviews/main/audio-modal.html',
                controller: ReviewModalAudioController,
                controllerAs: 'vm',
                bindings: {},
                backdrop: 'static',
                //size: 'lg',
                windowClass: 'my-modal-popup',
                resolve: {
                    audio_url: function () {
                        return audio_url;
                    }
                }
            });
        }

        $scope.shift_up = function (current_index) {
            var required_index = current_index - 1;
            var arr = $scope.pagedItems[$scope.currentPage];
            $scope.swapArrayElements(arr, current_index, required_index);
            $scope.swap_order_review(current_index, required_index);
        }

        $scope.shift_down = function (current_index) {
            var required_index = current_index + 1;
            var arr = $scope.pagedItems[$scope.currentPage];
            $scope.swapArrayElements(arr, current_index, required_index);
            $scope.swap_order_review(current_index, required_index);
        }

        $scope.shift_top = function (current_index) {
            var required_index = 0;
            var arr = [];
            for (var i = 0; i < $scope.pagedItems.length; i++) {
                for (var o = 0; o < $scope.pagedItems[i].length; o++) {
                    $scope.pagedItems[i][o].order_review = parseInt($scope.pagedItems[i][o].order_review)
                    arr.push($scope.pagedItems[i][o]);
                }
            }

            var order_review = $scope.pagedItems[$scope.currentPage][current_index].order_review;

            var index_rev = arr.findIndex(x => x.order_review == order_review);

            var removed_element = arr[index_rev];
            var order_removed_element = order_review;
            var required_order = arr[0].order_review;

            removed_element.order_review = required_order;

            arr.splice(index_rev, 1);
            angular.forEach(arr, function (val, key) {
                arr[key].order_review = val.order_review + 1;
            });
            arr.splice(required_index, 0, removed_element);
            var logEntry = arr.map(function (i) {
                return i.order_review + ':' + i.id + '';
            })
                .join(',');
            var order_arr = logEntry.split(",");
            var order_post = [];

            angular.forEach(order_arr, function (data, key) {
                var order_d = data.split(":");
                order_post.push({
                    order_review: parseInt(order_d[0]) + 1,
                    id: order_d[1]
                });
            });
            $scope.update_order_review(order_post);
            $scope.review_data = arr;
            $scope.search();
        }

        $scope.get_obj_index = function (order_review) {

            return $scope.review_data.findIndex(x => x.order_review == order_review);
        }


        $scope.shift_bottom = function (current_index) {
            var required_index = $scope.review_data.length - 1;
            var arr = $scope.review_data;
            //splice($scope.pagedItems[$scope.currentPage]);
            var removed_element = $scope.review_data[current_index];

            var order_removed_element = removed_element.order_review;
            var required_order = $scope.review_data[required_index].order_review;
            removed_element.order_review = required_order;

            $scope.review_data.splice(current_index, 1);

            angular.forEach($scope.review_data, function (val, key) {
                $scope.review_data[key].order_review = val.order_review - 1;
            });
            $scope.review_data.splice(required_index, 0, removed_element);
            var logEntry = $scope.review_data.map(function (i) {
                return i.order_review + ':' + i.id + '';
            })
                .join(',');
            var order_arr = logEntry.split(",");
            var order_post = [];
            angular.forEach(order_arr, function (data, key) {
                var order_d = data.split(":");
                order_post.push({
                    order_review: parseInt(order_d[0]) + 1,
                    id: order_d[1]
                });
            });

            $scope.update_order_review(order_post);
            $scope.search();
        }

        $scope.swapArrayElements = function (arr, indexA, indexB) {
            var temp = arr[indexA];
            arr[indexA] = arr[indexB];
            arr[indexB] = temp;
        };

        $scope.swap_order_review = function (indexA, indexB) {
            var temp = $scope.pagedItems[$scope.currentPage][indexA].order_review;
            $scope.pagedItems[$scope.currentPage][indexA].order_review = $scope.pagedItems[$scope.currentPage][indexB].order_review;
            $scope.pagedItems[$scope.currentPage][indexB].order_review = temp;

            var logEntry = $scope.pagedItems[$scope.currentPage].map(function (i) {
                return i.order_review + ':' + i.id + '';
            })
                .join(',');
            var order_arr = logEntry.split(",");
            var order_post = [];
            angular.forEach(order_arr, function (data, key) {
                var order_d = data.split(":");
                order_post.push({
                    order_review: parseInt(order_d[0]) + 1,
                    id: order_d[1]
                });
            });

            $scope.update_order_review(order_post);

        }

        $scope.sort = {
            sortingOrder: 'id',
            reverse: false
        };

        $scope.gap = 5;

        $scope.filteredItems = [];
        $scope.groupedItems = [];
        $scope.itemsPerPage = 20;
        $scope.pagedItems = [];
        $scope.currentPage = 0;

        var searchMatch = function (haystack, needle) {
            if (!needle) {
                return true;
            }
            return haystack.toLowerCase()
                .indexOf(needle.toLowerCase()) !== -1;
        };

        $scope.search = function () {

            $scope.filteredItems = $scope.review_data;

            $scope.currentPage = 0;

            $scope.groupToPages();
        };

        $scope.groupToPages = function () {
            $scope.pagedItems = [];
            if ($scope.filteredItems != undefined) {
                for (var i = 0; i < $scope.filteredItems.length; i++) {
                    if (i % $scope.itemsPerPage === 0) {
                        $scope.pagedItems[Math.floor(i / $scope.itemsPerPage)] = [$scope.filteredItems[i]];
                    } else {
                        $scope.pagedItems[Math.floor(i / $scope.itemsPerPage)].push($scope.filteredItems[i]);
                    }
                }
            }

        };

        $scope.range = function (size, start, end) {
            var ret = [];

            if (size < end) {
                end = size;
                start = size - $scope.gap;
            }
            for (var i = start; i < end; i++) {
                if (i >= 0) {
                    ret.push(i);
                }
            }
            return ret;
        };

        $scope.prevPage = function () {
            if ($scope.currentPage > 0) {
                $scope.currentPage--;
            }
        };

        $scope.nextPage = function () {
            if ($scope.currentPage < $scope.pagedItems.length - 1) {
                $scope.currentPage++;
            }
        };

        $scope.setPage = function () {
            $scope.currentPage = this.n;
        };

    }

    $onInit() { }
}


class ReviewModalVideoController {
    constructor($stateParams, $sce, $scope, $state, API, $uibModal, Upload, video_url, $window, $timeout, $compile, $uibModalInstance) {

        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.config = {
            preload: "none",
            sources: [{
                src: $sce.trustAsResourceUrl(video_url),
                type: "video/mp4"
            }],
            tracks: [{
                src: "pale-blue-dot.vtt",
                kind: "subtitles",
                srclang: "en",
                label: "English",
                default: ""
            }],
            theme: {
                url: "https://unpkg.com/videogular@2.1.2/dist/themes/default/videogular.css"
            },
            plugins: {
                controls: {
                    autoHide: false
                }
            }
        };
    }
}

class ReviewModalContentController {
    constructor($stateParams, $sce, $scope, $state, API, $uibModal, Upload, r_content, $window, $timeout, $compile, $uibModalInstance) {
        $scope.r_content = r_content;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }
}


class ReviewModalAudioController {
    constructor($stateParams, $sce, $scope, $state, API, $uibModal, Upload, audio_url, $window, $timeout, $compile, $uibModalInstance) {
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }
        $scope.config = {
            sources: [{
                src: $sce.trustAsResourceUrl(audio_url),
                type: "audio/mpeg"
            }],
            theme: {
                url: "./css/videogular-audio.css"
            }
        };
    }

}

export const ReviewListComponent = {
    templateUrl: './views/app/pages/reviews/main/reviews-lists.component.html',
    controller: ReviewsController,
    controllerAs: 'vm',
    bindings: {}
}
