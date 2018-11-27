class ReviewsController {
    constructor($scope, $state, $compile, DTOptionsBuilder, DTColumnBuilder, API, $window, AclService) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.API = API
        this.alerts = []
        this.window = $window;
        this.publishers = [];
        this.start_date = moment().subtract(10, 'days'), moment()
        this.end_date = moment()
        this.min_date = moment().subtract(30, 'days'), moment()
        this.max_date = moment()

        this.can = AclService.can
        if (!this.can('manage.reputation')) {
            $state.go('app.unauthorizedAccess');
        }

        $scope.pieRandomColors = ['#2ecc71', '#1abc9c', '#3498db', '#9b59b6', '#34495e', '#16a085', '#27ae60', '#2980b9', '#2980b9', '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22', '#e74c3c', '#ecf0f1', '#d35400', '#c0392b']
        let profile_publishers = API.service('publisher-list', API.all('profilelisting'))
        profile_publishers.one("").get()
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
            return all_publishers[publisherID];
        }

        $scope.datePickerOptions = {
            applyClass: 'btn-green',
            locale: {
                applyLabel: "Apply",
                fromLabel: "From",
                format: "MMMM DD, YYYY", //will give you 2017-01-06
                toLabel: "To",
                cancelLabel: 'Cancel',
                customRangeLabel: 'Custom range'
            },
            ranges: {
                'Last 7 Days': [moment().subtract(7, 'days'), moment()],
                'Last 30 Days': [moment().subtract(30, 'days'), moment()]
            },
            eventHandlers: {
                'apply.daterangepicker': function (ev, picker) {
                    $scope.list_i = 5;
                    $scope.activity_list = [];
                    $scope.list_id = [];
                    $scope.load_data_table();
                }
            }
        }
        $scope.CallsChartOptions = {
            scaleShowVerticalLines: false,
            scaleShowHorizontallLines: false,
            //responsive:true,

            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    stacked: true,
                    gridLines: {
                        display: true,
                        color: "rgba(255,99,132,0.2)"
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false
                    }
                }]
            },
            tooltipTemplate: "<%= value %>",
            gridLines: {
                show: false
            }
        };


        $scope.datePicker = { startDate: this.start_date, endDate: this.end_date };
        $scope.analyticsChartColours = [
            {
                fillColor: '#fcc5ae',
                strokeColor: '#D2D6DE',
                pointColor: '#000000',
                pointStrokeColor: '#fff',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(148,159,177,0.8)'
            }
        ]

        $scope.load_data_table = function () {
            $scope.tableId = "reviews_list";
            var token = $window.localStorage.satellizer_token
            $scope.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    contentType: 'application/json',
                    url: '/api/reviews',
                    type: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization",
                            "Bearer " + token);
                    },
                    data: function (data, dtInstance) {
                        return JSON.stringify(data);
                    },
                    error: function (err) {
                        let data = []
                        return JSON.stringify(data);
                    }
                })
                .withDataProp('data')
                .withOption('serverSide', true)
                .withOption('processing', true)
                .withOption('createdRow', createdRow)
                //.withColReorder()
                //.withColReorderOrder([2, 1, 2])
                .withColReorderOption('iFixedColumnsRight', 1)
                .withColReorderCallback(function () {
                })
                .withOption('createdRow', function (row) {
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('responsive', true)
                .withOption('aaSorting', [[0, 'desc']])
                .withBootstrap()

            $scope.dtColumns = [
                DTColumnBuilder.newColumn('id').withTitle('SITE').withClass("noshow").notSortable(),
                DTColumnBuilder.newColumn('publisher_id').withTitle('SITE').renderWith(function (data) {
                    if (data != null) {
                        return '<div><span class="yext-listing-icon yext-' + data.toLowerCase() + '" uib-tooltip="Yext Review" tooltip-placement="top" > </span><div>';
                    }
                    return '<div><span class="yext-listing-icon yext-website" uib-tooltip="Website Review" tooltip-placement="top"> </span><div>';
                }).notSortable(),
                DTColumnBuilder.newColumn('provider_name').withTitle('Provider Name').renderWith(function (data) {
                    if (data != null) {
                        return data;
                    }
                    return '';
                }).notSortable(),
                DTColumnBuilder.newColumn('published_time').renderWith(function (data) {
                    return moment(new Date(data)).format('ddd, MMM DD YYYY, hh:mm a');
                    //return data;
                }).withTitle('REVIEW DATE').notSortable(),

                DTColumnBuilder.newColumn('rating').withTitle('STAR RATING').renderWith(function (data) {
                    if (data == null) {
                        return '<div class="star-counting star-0"></div>';
                    }
                    return '<div class="star-counting star-' + data + '"></div>';

                }).notSortable(),
                DTColumnBuilder.newColumn(null).withTitle('REVIEW').renderWith(function (data) {
                    return '<strong>' + data.client_name + `</strong><br>` + data.user_review;

                }).withOption('width', '390px').notSortable(),
                DTColumnBuilder.newColumn('url').withTitle('VIEW').renderWith(function (data) {
                    if (data != null) {
                        return '<a uib-tooltip="View" tooltip-placement="left" href="' + data + '" target="_blank" class="btn btn-xs btn-primary"><i class="fa fa-eye"></i></a>';
                    }
                    return '';
                }).notSortable()
            ]

            $scope.displayTable = true
            $('#' + $scope.tableId).DataTable().ajax.reload();
            /* ************* Reviews satics data ************* */
            var reviewsAPI = API.service('site-reputation', API.all('reviews'))
            reviewsAPI.one('').get()
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
                        review_labels.push(value.publisher_id);
                        review_vlues.push(value.total);
                        review_Colors.push($scope.pieRandomColors[indx]);
                        indx++;
                    });
                    $scope.review_labels = review_labels;
                    $scope.review_values = review_vlues;
                    $scope.review_Colors = review_Colors;

                });

        }

        let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
        }
        $scope.load_data_table();

        $scope.sum = function (input) {
            if (toString.call(input) !== "[object Array]")
                return false;

            var total = 0;
            for (var i = 0; i < input.length; i++) {
                if (isNaN(input[i])) {
                    continue;
                }
                total += Number(input[i]);
            }
            return total;
        }

    }
    toggleOne() {
        let $state = this.$state
        var sele = angular.element($('.selected_val')).serializeArray();
        if (sele.length === 0) {
            this.isdelseleted = false;
        } else {
            this.isdelseleted = true;
        }
    }

    delete(contactId) {
        let API = this.API
        var $state = this.$state
        var state_s = this.$state
        swal({
            title: 'Are you sure?',
            text: 'You will not be able to recover this data!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function () {
            API.one('contacts').one('contact', contactId).remove()
                .then(() => {
                    var $state = this.$state
                    swal({
                        title: 'Deleted!',
                        text: 'User Permission has been deleted.',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function () {
                        state_s.reload()
                    })
                })
        })
    }
    multi_del() {
        let API = this.API
        let $state = this.$state
        var sele = angular.element($('.selected_val')).serializeArray();
        swal({
            title: 'Are you sure?',
            text: 'You will not be able to recover this data!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function () {
            let conts = API.service('del-contacts', API.all('contacts'))

            conts.post(
                { 'selected_del': sele }
            )
                .then(() => {
                    swal({
                        title: 'Deleted!',
                        text: 'Conatct has been deleted.',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function () {
                        $state.reload()

                    })
                })
        })

    }

    $onInit() { }
}
export const ReviewListComponent = {
    templateUrl: './views/app/pages/reviews-listing/reviews-lists.component.html',
    controller: ReviewsController,
    controllerAs: 'vm',
    bindings: {}
}
