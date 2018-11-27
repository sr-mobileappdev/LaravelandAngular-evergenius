class NewCampaignsController {
    constructor($scope, API, $uibModal, $http, $auth, $stateParams, $sce, $state, $compile, $timeout, DTOptionsBuilder, DTColumnBuilder, SAAPI, $window, AclService, $location) {
        'ngInject'
        var vm = this
        $scope.contactSearchResult = {};
        $scope.template_show = false
        this.API = API
        this.$state = $state
        $scope.show_time_input = false;
        $scope.status_check = false
        $scope.save_template = false
        $scope.opened = {};
        $scope.editor_value = ''
        $scope.button_clicked = false
        $scope.emailBodytaken = ''
        $scope.campaignErrorstate = false
        vm.tags = []

        if ($stateParams.list_info) {
            var listInfo = $stateParams.list_info
            var tagList = {
                title: listInfo.name,
                id: listInfo.id
            }
            vm.tags.push(tagList)
        }
        if (!JSON.parse($window.localStorage.getItem('campaignInfo'))) {
            if ($stateParams.multipleListInfo) {
                var multiListData = $stateParams.multipleListInfo

                let Permissions = this.API.service('selected-email-list', this.API.all('email-marketing'))
                Permissions.post(multiListData).then(function (response) {
                    for (var i = 0; i < response.data.length; i++) {
                        var tagList = {
                            title: response.data[i].name,
                            id: response.data[i].id
                        }
                        vm.tags.push(tagList)
                    }
                })
            }
        }

        window.localStorage.removeItem("editcampaignInfo")

        $scope.close = function () {
            $scope.show_time_input = false
        }
        $scope.showtimepicker = function (i) {

            if ($scope.show_time_input == true) {
                $scope.show_time_input = false
            } else {
                $scope.show_time_input = true;
            }

        };
        $scope.openCalendar = function (e) {
            e.preventDefault();
            e.stopPropagation();

            $scope.opened.isOpen = true;
        };

        $scope.$watch('schedule_time', function () {
            if ($scope.schedule_time == null || $scope.schedule_time == undefined || $scope.schedule_time == '') {
                $scope.opened.isOpen = true;
            }
        })

        if (window.localStorage.getItem('campaignInfo')) {
            vm.subject_line = JSON.parse($window.localStorage.getItem('campaignInfo')).subject,
                vm.tags = JSON.parse($window.localStorage.getItem('campaignInfo')).tags
        }


        $scope.open_email_editor = function (obj) {
            var campaignInfo = {
                subject: vm.subject_line,
                tags: vm.tags

            }
            window.localStorage.setItem('campaignInfo', JSON.stringify(campaignInfo))
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/funnel-editor-page.html',
                controller: CampaignEditorModalController,
                windowClass: 'beefree-editor-cls',
                resolve: {
                    new_obj: function () {
                        return obj;
                    },

                }
            });
            return modalInstance;

        }
        $scope.uCanTrust = function (actionType) { //convert string to html
            return $sce.trustAsHtml(actionType);
        }
        vm.from = JSON.parse($window.localStorage.getItem('user_company_details')).name
        vm.test_email = JSON.parse($window.localStorage.getItem('user_company_details')).email
        if ($window.localStorage.getItem('newsletter.autosave.json')) {
            $scope.editor_value = $window.localStorage.getItem('newsletter.autosave.json')
            $scope.emailBodytaken = $window.localStorage.getItem('newsletter.autosave.html')
            $scope.template_show = true
        }

        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/email-marketing/find-email-list?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }
        $scope.deleteTemplate = function (params) {

            swal({
                title: 'Are you sure?',
                text: 'You will not be able to recover this data!',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#27b7da',
                confirmButtonText: 'Yes, delete it!',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                html: false
            }, function () {
                let deletetemplate = API.service('delete-company-template', API.all('funnel'))
                deletetemplate.post({
                    "template_id": params.id,
                    'category': params.category,

                }).then(function (response) {
                    swal({
                        title: 'Deleted!',
                        text: 'Template has been deleted.',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function () {
                        // var $state = this.$state
                        $state.reload()
                    })
                })

                // })
            })


        }
        /**********************************Save New campaigns*******************************************/

        this.save_campign = function (isValid, type, status) {
            var timer = ''
            if (type == 'scheduled') {
                timer = moment($scope.schedule_time).format('YYYY-MM-DD H:m:s')
            }
            this.save_type = type
            var json_body = ''
            var body = ''
            var $state = this.$state
            var mess = ''


            let Permissions = this.API.service('campaign', this.API.all('email-marketing'))


            if ($window.localStorage.getItem('newsletter.autosave.json') && $window.localStorage.getItem('newsletter.autosave.html')) {
                json_body = $window.localStorage.getItem('newsletter.autosave.json')
                body = $window.localStorage.getItem('newsletter.autosave.html')
            }
            if (this.subject_line && this.tags) {
                var Obj = {
                    "name": this.subject_line,
                    "status": status,
                    "from_name": this.from,
                    "from_email": "",
                    "reply_email": "",
                    "query_string": "utm",
                    "template_id": "1",
                    "subject": this.subject_line,
                    "body": body,
                    "json_body": json_body,
                    "test_email": this.test_email,
                    "schedule_datetime": timer,
                    "campign_newsletter_lists": this.tags,
                    "save_type": this.save_type
                }

                if (type == 'sendmail' || type == 'inprogress' || type == 'scheduled') {

                    if (json_body && body) {

                        Permissions.post(Obj, {}, {}, { 'Content-type': 'application/json' }).then(function (response) {

                            var resp = response.plain().data.campign_id

                            if (status == 4) {
                                $scope.status_check = true
                                mess = 'Email Broadcast has been run successfully'


                            }
                            if (type == 'scheduled') {
                                mess = 'Email Broadcast has been Scheduled at ' + moment(timer).format('MMM Do YYYY, h:mm:ss a') + '!!'
                            }
                            if (type == 'sendmail') {
                                mess = 'Email Broadcast has been saved and Test Email Sent Successfully !'
                            }

                            swal({
                                title: 'success',
                                text: mess,
                                type: 'success',
                                // confirmButtonColor: '#27b7da',
                                confirmButtonText: 'OK',
                                closeOnConfirm: true,
                                showLoaderOnConfirm: true,
                                html: false

                            }, function () {
                                if (type == 'sendmail') {
                                    $state.go('app.edit-campaigns', { campaign_id: resp }, { reload: true });
                                } else {
                                    $state.go('app.email-campaigns', { alerts: alert }, { reload: true });
                                }
                            })
                        }, function (response) {
                            $scope.campaignError = 'Something went wrong , Please refresh page and try again'
                            $timeout(function () {
                                $scope.campaignErrorstate = true
                            }, 100);
                            $timeout(function () {
                                $scope.campaignErrorstate = false
                            }, 3000);
                        })
                    } else {
                        swal({
                            title: 'Something went wrong !',
                            text: 'Please select and save the template',
                            type: 'warning',
                            // confirmButtonColor: '#27b7da',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true,
                            showLoaderOnConfirm: true,
                            html: false
                        })
                    }

                } else {

                    Permissions.post(Obj, {}, {}, { 'Content-type': 'application/json' }).then(function () {
                        swal({
                            title: 'success',
                            text: 'Email Broadcast has been Saved as Draft',
                            type: 'success',
                            // confirmButtonColor: '#27b7da',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true,
                            showLoaderOnConfirm: true,
                            html: false

                        }, function () {
                            $state.go('app.email-campaigns', { alerts: alert }, { reload: true });
                        })

                    }, function (response) {
                        $scope.campaignError = 'Something went wrong , Please refresh page and try again'
                        $timeout(function () {
                            $scope.campaignErrorstate = true
                        }, 100);
                        $timeout(function () {
                            $scope.campaignErrorstate = false
                        }, 3000);

                    })

                }

            }
        }

        /********************************** Show selected template *******************************************/
        $scope.show_template = function (list) {
            var campaignInfo = {
                'subject': vm.subject_line,
                'tags': vm.tags

            }
            $window.localStorage.setItem('campaignInfo', JSON.stringify(campaignInfo))
            $scope.template_show = true
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/funnel-editor-page.html',
                controller: CampaignEditorModalController,
                windowClass: 'beefree-editor-cls',
                resolve: {
                    new_obj: function () {
                        return list.json_body;
                    },
                    allList: function () {
                        return list;
                    },

                }

            });
            return modalInstance;
        }


        var template_list = API.service('templates/1', API.all('email-marketing'))
        template_list.one('').get()
            .then((response) => {
                $scope.template_list = response.plain().data


            })


        /**********************************Preview template image Modal*******************************************/

        $scope.preview_template_modal = function (image) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/campaigns/previw-template-modal.html',
                controller: PreviewTemplateModalController,
                resolve: {
                    temp_img: function () {
                        return image;
                    }
                }
            });
            return modalInstance;
        }

    }


    $onInit() { }
}

class CampaignEditorModalController {
    constructor($stateParams, allList, $scope, new_obj, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'

        var bee = ''
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
            $state.reload()
        }

        var request = function (method, url, data, type, callback) {
            var req = new XMLHttpRequest();

            req.onreadystatechange = function () {
                if (req.readyState === 4 && req.status === 200) {
                    var response = JSON.parse(req.responseText);
                    callback(response);
                }
            };

            req.open(method, url, true);
            if (data && type) {
                if (type === 'multipart/form-data') {
                    var formData = new FormData();
                    for (var key in data) {
                        formData.append(key, data[key]);
                    }
                    data = formData;
                }
                else {
                    req.setRequestHeader('Content-type', type);
                }
            }

            req.send(data);
        };

        var save = function (filename, content) {

            saveAs(
                new Blob([content], { type: 'text/plain;charset=utf-8' }),
                filename
            );

        };

        var specialLinks = [];

        var mergeTags = [{
            name: 'Unsubscribe',
            value: '{$unsubscribe_link}'
        }, {
            name: 'First Name',
            value: '{$first_name}'
        }, {
            name: 'Last Name',
            value: '{$last_name}'
        }, {
            name: 'Company Name',
            value: '{$client_name}'
        }, {
            name: 'Office Address',
            value: '{$location}'
        }, {
            name: 'Office Phone',
            value: '{$office_phone}'
        }, {
            name: 'Website Link',
            value: '{$website_link}'
        }];

        var mergeContents = [];

        var beeConfig = {
            uid: 'company' + JSON.parse($window.localStorage.getItem('user_company_details')).id,
            container: 'bee-plugin-container',
            autosave: 15,
            language: 'en-US',
            specialLinks: specialLinks,
            mergeTags: mergeTags,
            mergeContents: mergeContents,
            onSave: function (jsonFile, htmlFile) {
                $window.localStorage.satellizer_token
                window.localStorage.setItem('newsletter.autosave.json', jsonFile);
                window.localStorage.setItem('newsletter.autosave.html', htmlFile)
                if (allList.company_id != null && allList.company_id != undefined) {
                    var update_template_title = API.service('company-template', API.all('funnel'));
                    update_template_title.post({
                        "json_body": jsonFile,
                        "html_body": htmlFile,
                        "template_id": allList.id,
                        "category": allList.category
                    })
                        .then((response) => {
                            $timeout(function () {
                                $scope.save_template = true
                            }, 100);

                        });
                } else {
                    $timeout(function () {
                        $scope.save_template = true
                    }, 100);
                }


                $timeout(function () {
                    $scope.save_template = false
                }, 3000);


            },
            onSaveAsTemplate: function (jsonFile) { // + thumbnail? 
                bee.save()
                const modalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: './views/app/pages/email-marketing/campaigns/previw-template-modal.html',
                    controller: TemplateTitleModalController,
                });
                return modalInstance;

            },

            onError: function (errorMessage) {
                console.log('onError ', errorMessage);
            }
        };

        var bee = null;

        var loadTemplate = function (e) {
            var templateFile = e.target.files[0];
            var reader = new FileReader();

            reader.onload = function () {
                var templateString = reader.result;
                var template = JSON.parse(templateString);
                bee.load(template);
            };

            reader.readAsText(templateFile);
        };


        request(
            'POST',
            'https://auth.getbee.io/apiauth',
            'grant_type=password&client_id=48f36ec3-5e7c-4a60-a3e9-65989b8760f5&client_secret=8ChfzCzYNuqsHHlZKJjGHIg32xRJEIRlRM0VAKoSkal6fGhW7RH',
            'application/x-www-form-urlencoded',
            function (token) {
                BeePlugin.create(token, beeConfig, function (beePluginInstance) {
                    bee = beePluginInstance;
                    bee.start(JSON.parse(new_obj));
                });
            });


        // console.log('new_obj', )
    }
}



class PreviewTemplateModalController {
    constructor($stateParams, $scope, temp_img, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.template_image = true
        $scope.image = temp_img

        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }
}
class TemplateTitleModalController {
    constructor($stateParams, $scope, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.template_name = true
        $scope.obj = {}


        $scope.update_title = function () {


            var update_template_title = API.service('template', API.all('email-marketing'));
            update_template_title.post({
                "json_body": $window.localStorage.getItem('newsletter.autosave.json'),
                "html_body": $window.localStorage.getItem('newsletter.autosave.html'),
                "title": $scope.obj.title_name,
                "type": '1'
            })
                .then((response) => {
                    $uibModalInstance.close();
                });


        }

        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }
}
export const NewCampaignsComponent = {
    templateUrl: './views/app/pages/email-marketing/campaigns/new-campaigns.html',
    controller: NewCampaignsController,
    controllerAs: 'vm',
    bindings: {}
}