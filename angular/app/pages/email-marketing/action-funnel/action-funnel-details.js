class ActionFunnelDetailsController {
    constructor($scope, $auth, $rootScope, API, $stateParams, $filter, $state, $compile, DTOptionsBuilder, $sce, DTColumnBuilder, SAAPI, $window, AclService, $location, $uibModal, $timeout) {
        'ngInject'
        $scope.funnel_list = '';
        $scope.open_action = false
        $scope.send_Email_sms = true;
        $scope.step_id = '';
        $scope.open_div = false;
        $scope.editor = "";
        $scope.create_div = false;
        $scope.template_show = true;
        $scope.template_showin = false;
        var vm = this
        $scope.new_text = false;
        $scope.Obj = {};
        var state_s = $state;
        var funnel_id = $stateParams.funnelId;
        var position_id = $stateParams.id;
        $scope.custom_groups = [0];
        $scope.sent_count = 0;
        $scope.editor_value = '';
        $scope.opened_count = 0;
        $scope.clicked_count = 0;
        $scope.Obj.trigger_value = '2';
        $scope.openAppointment = true;
        $scope.showAppointment = true;
        $scope.showOpportunities = true;
        $scope.Obj.opportunityStatus = '';
        $scope.Obj.oppertunityStage = '';
        $scope.Obj.oppertunityService = '';
        $scope.source = ''
        $scope.noShowstatus = false
        $scope.noShowstage = false
        $scope.noShowservice = false
        $scope.noShowsource = false
        $scope.source_value = ''
        $scope.doAtion = false
        $scope.anchorShow = false
        $scope.actionValue = 'addtolist'
        $scope.activatedList = []
        $scope.showFunnellistifExist = false
        $scope.openAppointTab = function () {
            $scope.openOpertunities = false;
            $scope.openAppointment = true;
        }
        $scope.openOpporTab = function () {
            $scope.openOpertunities = true;
            $scope.openAppointment = false;
        }

        $scope.goToList = function (list) {
            $state.go('app.marketing-contacts', { contactId: list.id })
        }

        /*******************************Edit Funnel Detail *****************************************/

        $scope.update_api = function (action_vars, id) {
            var update_steps_details = API.service('action-step/' + id, API.all('funnel'));
            update_steps_details.post(action_vars)
                .then((response) => {

                });
            $state.go($state.current, {
                id: id,
                funnelID: funnel_id
            }, { reload: true })
        }

        /********************************* Create Or Update Action rules **************************************/

        $scope.save_rule = function (id, rule_id, ruleInfo) {
            var elSource = angular.element(document.querySelector('#selectSource'));
            $scope.source = elSource.val();
            if (ruleInfo == 'appointment') {
                var Obj = {
                    funnel_id: funnel_id,
                    step_id: id,
                    action_rule_id: rule_id, /*if action rule id is exists then rule is updated*/
                    data: [{
                        rule_type: 'appointment',
                        appointment_status: $scope.Obj.appointmentId,
                        trigger_time_date: $scope.Obj.trigger_text,
                        trigger_time_unit: $scope.Obj.trigger_value,
                        time_schedule: $scope.Obj.timetakenId,


                    }]
                }
            } else {
                var Obj = {
                    funnel_id: funnel_id,
                    step_id: id,
                    action_rule_id: rule_id,/*if action rule id is exists then rule is updated*/
                    data: [{
                        rule_type: 'opportunity',
                        opportunity_status: $scope.Obj.opportunityStatus,
                        opportunity_stage_id: $scope.Obj.oppertunityStage,
                        opportunity_service_id: $scope.Obj.oppertunityService,
                        opportunity_source_id: $scope.source,
                        trigger_time_date: $scope.Obj.trigger_text,
                        trigger_time_unit: $scope.Obj.trigger_value,
                        time_schedule: $scope.Obj.timetakenId,


                    }]
                }
            }

            var create_action_rule = API.service('action-rule', API.all('funnel'));
            create_action_rule.post(Obj)
                .then((response) => {
                    $scope.new_text = true
                });


            $state.go($state.current, {
                id: $scope.step_id
            }, { reload: true })
        }

        $scope.create_step_api = function (Obj) {

            var addComment = API.service('create-action-step', API.all('funnel'));
            addComment.post(Obj)
                .then((response) => {
                    $state.go($state.current, {
                        id: response.plain().data.step_id
                    }, { reload: true })
                });
        }

        $scope.update_step_api = function (Obj, sId) {

            var update_step = API.service('action-step/' + sId, API.all('funnel'));
            update_step.post(Obj)
                .then((response) => {
                    $state.go($state.current, {
                        id: sId
                    }, { reload: true })
                });
        }


        $scope.changeStatus = function (status, array) {

            var sId = array.id

            if (status == '0') {
                var Obj = {
                    step_name: array.name,
                    trigger_value: array.trigger_value,
                    trigger_type: array.trigger_type,
                    action_type: array.action_type,
                    funnel_id: array.funnel_id
                }
                $scope.create_step_api(Obj)
            } else {

                var Obj = {
                    step_name: array.name,
                    trigger_value: array.trigger_value,
                    trigger_type: array.trigger_type,
                    status: status
                }
                $scope.update_step_api(Obj, sId)
            }
            state_s.reload()

        }
        /********************************* Edit Or Update Subject Line **************************************/
        $scope.updateTodo = function (value, id, action) {
            if (action.action_type == '1') {
                var action_vars = {
                    funnel_id: funnel_id,
                    email_subject: value
                }
            }     /* '1' is specify that action is for send email and '2' for send text message*/
            else if (action.action_type == '2') {
                var action_vars = {
                    funnel_id: funnel_id,
                    sms_text: value
                }

            }

            $scope.update_api(action_vars, id)
            $state.go($state.current, {
                id: $scope.step_id
            }, { reload: true })
        };

        $scope.cancelEdit = function (value) {
        };

        /********************************* Filters Which gives value according to id **************************************/
        $scope.getOppertunitystatus = function (id, obj) {
            if (id) {
                var result = $filter('filter')(obj, { id: id })[0];
                var name = result.name
                return name
            } else {
                name = ''
                return name
            }

        }

        $scope.getOppertunitystage = function (id, obj) {
            if (id) {
                var result = $filter('filter')(obj, { id: id })[0];
                var name = result.title
                return name
            } else {
                name = ''
                return name
            }

        }

        $scope.get_rule_timer = function (id) {
            if (id == '1') {
                var title = "after"
                return title
            }
            else if (id == '2') {
                title = "before"
                return title
            }
        }
        $scope.trigger_action = function (type) {

            if (type == '1') {
                var trigger = 'days'
                return trigger
            } else if (type == '2') {
                trigger = "hours"
                return trigger
            }
            else if (type == '3') {
                trigger = "min"
                return trigger
            }
        }

        $scope.get_status = function (status_val) {
            if (status_val == 1) {
                var status = 'Live'

                return status
            } else {
                var status = 'Paused'

                return status
            }
        }
        $scope.getActionName = function (action) {
            if (action == 'addtolist') {
                var actionvalue = 'Add To List'
                return actionvalue
            } else if (action == 'removefromlist') {
                actionvalue = 'Remove From List'
                return actionvalue
            } else if (action == 'addtag') {
                actionvalue = 'Add Tag'
                return actionvalue
            } else if (action == 'removetag') {
                actionvalue = 'Remove Tag'
                return actionvalue
            }
        }

        $scope.gettagsPop = function () {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'merge-tags.html',
                controller: mergeTagsController,

            });
            return modalInstance;
        }


        /********************************* Geting Action Steps From Funnel List **************************************/

        var funnel_list = API.service('list-action-steps/' + funnel_id, API.all('funnel'))
        funnel_list.one('').get()
            .then((response) => {

                $scope.funnel_list = response.plain().data.res
                $scope.funnel_details = response.plain().data.funnel

                $scope.apt_status = response.plain().data.apt_status
                $scope.opertunitiesData = response.plain().data
                $scope.active_class = $scope.funnel_list[0]

                if ($scope.active_class) {
                    if ($scope.active_class.rules.length > 0) {
                        $scope.action_rule_id = $scope.active_class.rules[0].id

                    } else {
                        $scope.action_rule_id = ''
                    }
                }
                $scope.funnel_name = $scope.funnel_details[0].name
                $scope.statusOfFunnel = $scope.funnel_details[0].status
                if (position_id) {
                    for (var i = 0; i < $scope.funnel_list.length; i++) {
                        if ($scope.funnel_list[i].id == position_id) {
                            $scope.active_class = $scope.funnel_list[i]
                            $scope.sent_count = $scope.active_class.sent_count
                            $scope.opened_count = $scope.active_class.opened_count
                            $scope.clicked_count = $scope.active_class.clicked_count
                            if ($scope.active_class) {
                                if ($scope.active_class.rules.length > 0) {
                                    $scope.action_rule_id = $scope.active_class.rules[0].id

                                } else {
                                    $scope.action_rule_id = ''
                                }
                            }
                        }
                    }
                }
                if ($scope.funnel_details[0].f_list.length > 0) {
                   
                    $scope.showFunnellistifExist = true
                    for (var i = 0; i < $scope.funnel_details[0].f_list.length; i++) {
                        $scope.activatedList.push({ name: $scope.funnel_details[0].f_list[i].listdetail.name, id: $scope.funnel_details[0].f_list[i].listdetail.id })

                    }

                }


                if ($scope.active_class) {
                    $scope.sent_count = $scope.active_class.sent_count
                    $scope.icon = $scope.action_type($scope.active_class.action_type)
                    $scope.step_id = $scope.active_class.id
                    if ($scope.active_class.do_action) {
                        $scope.actionValue = $scope.active_class.do_action
                        if ($scope.active_class.do_action == 'addtolist' || $scope.active_class.do_action == 'removefromlist') {
                            $scope.doActionData = $scope.active_class.emlist
                            $scope.anchorShow = true
                        } else if ($scope.active_class.do_action == 'addtag' || $scope.active_class.do_action == 'removetag') {
                            $scope.doActionData = $scope.active_class.tag.term_value
                            $scope.anchorShow = false
                        }

                    }


                    if ($scope.active_class.rules[0]) {
                        if ($scope.active_class.rules[0].rule_type == 'appointment') {
                            $scope.showAppointment = true
                            $scope.showOpportunities = false
                            $scope.openOpertunities = false
                            $scope.openAppointment = true
                            $scope.Obj.appointmentId = parseInt($scope.active_class.rules[0].appointment_status)
                            $scope.Obj.trigger_text = $scope.active_class.rules[0].trigger_time_date
                            $scope.Obj.trigger_value = $scope.active_class.rules[0].trigger_time_unit
                            $scope.Obj.timetakenId = $scope.active_class.rules[0].time_schedule
                            $scope.new_text = true

                        } else {
                            $scope.Obj.opportunityStatus = parseInt($scope.active_class.rules[0].opportunity_status)
                            $scope.Obj.oppertunityStage = parseInt($scope.active_class.rules[0].opportunity_stage_id)
                            $scope.Obj.oppertunityService = parseInt($scope.active_class.rules[0].opportunity_service_id)
                            if ($scope.active_class.rules[0].sourcename) {
                                $scope.source = $scope.active_class.rules[0].sourcename.term_value
                                var elSource = angular.element(document.querySelector('#selectSource'));
                                elSource.val($scope.active_class.rules[0].sourcename);
                            }

                            $scope.Obj.trigger_text = $scope.active_class.rules[0].trigger_time_date
                            $scope.Obj.trigger_value = $scope.active_class.rules[0].trigger_time_unit
                            $scope.Obj.timetakenId = $scope.active_class.rules[0].time_schedule
                            $scope.new_text = true
                            $scope.showAppointment = false
                            $scope.showOpportunities = true
                            $scope.openOpertunities = true
                            $scope.openAppointment = false
                            if ($scope.Obj.opportunityStatus != '') {
                                $scope.noShowstatus = true
                            }
                            if ($scope.Obj.oppertunityStage != '') {
                                $scope.noShowstage = true
                            }
                            if ($scope.Obj.oppertunityService != '') {
                                $scope.noShowservice = true
                            }
                            if ($scope.active_class.rules[0].sourcename) {
                                $scope.noShowsource = true
                                $scope.source_value = $scope.active_class.rules[0].sourcename.term_value
                            }

                        }
                    }
                    if ($scope.active_class.action_type == '1') {
                        $scope.send_request_name = 'Emails Sent'

                        if ($scope.active_class.email_subject != '' && $scope.active_class.email_subject != null && $scope.active_class.email_subject != undefined) {

                            $scope.list_name = $scope.active_class.email_subject.substring(0, 500);

                        }
                        else {
                            $scope.list_name = 'Untitled Email Subject Line'
                        }
                    }
                    else if ($scope.active_class.action_type == '2') {
                        $scope.send_request_name = 'Sms Sent'

                        if ($scope.active_class.sms_text != '' && $scope.active_class.sms_text != null && $scope.active_class.sms_text != undefined) {

                            $scope.list_name = $scope.active_class.sms_text.substring(0, 500);

                        }
                        else {
                            $scope.list_name = 'Untitled SMS'
                        }
                    }
                    else if ($scope.active_class.action_type == '3') {

                        $scope.send_request_name = 'Action Taken'
                        $scope.list_name = $scope.actionValue
                        $scope.send_Email_sms = false
                        $scope.doAtion = true
                    }

                    if ($scope.active_class.email_body == '') {

                    }
                    if ($scope.active_class.action_type != '1') {
                        $scope.template_show = false

                    }

                    if ($scope.active_class.email_body) {
                        $scope.template_show = true
                        $scope.template_showin = true
                        $scope.editor_value = $scope.active_class
                        $scope.emailBodytaken = $scope.active_class.email_body

                    } else {

                        var template_list = API.service('templates/2', API.all('email-marketing'))
                        template_list.one('').get()
                            .then((response) => {
                                $scope.template_list = response.plain().data


                            })

                    }
                }

            });


        $scope.uCanTrust = function (actionType) { //Convert Json data to Html
            return $sce.trustAsHtml(actionType);
        }
        $scope.action_type = function (action) {  // Giving icons according to action 
            if (action == '1') {
                var actionType = '<i class="fa fa-envelope-o"></i>'

                return actionType
            } else if (action == '2') {
                actionType = ' <i class="fa fa-commenting-o"></i>';
                return actionType
            }
            else if (action == '3') {
                actionType = ' <i class="fa fa-rocket"></i>';
                return actionType
            }

        }
        /********************************* Action Step Delete **************************************/
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

        $scope.funnel_delete = function (stepId) {

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
                API.one('funnel').one('action-step', stepId).remove()
                    .then(() => {
                        var $state = this.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Action has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            state_s.reload()
                        })
                    })
            })

        }
        /********************************* Open editor Modal  **************************************/
        $scope.open_editor = function (obj) {
            var id = null
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/funnel-editor-page.html',
                controller: FunnelEditorModalController,
                windowClass: 'beefree-editor-cls',
                resolve: {
                    list: function () {
                        return obj;
                    },
                    funnel_id: function () {
                        return funnel_id
                    },
                    id: function () {
                        return id
                    }
                }
            });
            return modalInstance;

        }
        /********************************* Show template Modal  **************************************/
        $scope.show_template = function (list, step_id) {
            $scope.template_showin = true
            $scope.template_show = true
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/funnel-editor-page.html',
                controller: FunnelEditorModalController,
                windowClass: 'beefree-editor-cls',
                resolve: {
                    list: function () {
                        return list;
                    },
                    funnel_id: function () {
                        return funnel_id
                    },
                    id: function () {
                        return step_id
                    }
                }

            });
            return modalInstance;
        }
        /********************************* Preview Image Modal  **************************************/
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


        /********************************* Action Step List **************************************/
        $scope.list_detail = function (list, icon) {
            if (list.rules.length > 0) {
                $scope.action_rule_id = list.rules[0].id
            } else {
                $scope.action_rule_id = ''
            }

            var get_step_value = API.service('step', API.all('funnel'));
            get_step_value.post({
                'funnel_id': list.funnel_id,
                'id': list.id
            })
                .then((response) => {
                    $scope.step_data = response.plain().data
                    $scope.template_show = true
                    $scope.send_Email_sms = true
                    $scope.sent_count = list.sent_count
                    $scope.opened_count = list.opened_count
                    $scope.clicked_count = list.clicked_count
                    if (list.rules[0]) {
                        $scope.Obj.appointmentId = parseInt(list.rules[0].appointment_status)
                        $scope.Obj.trigger_text = list.rules[0].trigger_time_date
                        $scope.Obj.trigger_value = list.rules[0].trigger_time_unit
                        $scope.Obj.timetakenId = list.rules[0].time_schedule
                        $scope.new_text = true

                    }

                    $scope.step_id = list.id
                    $scope.active_class = list
                    $scope.open_action = true

                    $scope.icon = icon
                    if (list.action_type == '1') {
                        $scope.send_request_name = 'Emails Sent'
                        if (list.email_subject != '' && list.email_subject != null && list.email_subject != undefined) {
                            $scope.list_name = list.email_subject.substring(0, 500)

                        } else {
                            $scope.list_name = 'Untitled Email Subject Line'
                        }

                    } else if (list.action_type == '2') {

                        $scope.send_request_name = 'Sms Sent'
                        if (list.sms_text != '' && list.sms_text != null && list.sms_text != undefined) {

                            $scope.list_name = list.sms_text.substring(0, 500)

                        }
                        else {
                            $scope.list_name = 'Untitled SMS'
                        }
                    } else if (list.action_type == '3') {
                        $scope.list_name = 'Add To List'
                        $scope.send_Email_sms = false
                        $scope.doAtion = true
                    }


                    if (list.action_type != '1') {
                        $scope.template_show = false

                    }
                    $scope.cancelEdit()

                    if (list.email_body) {
                        $scope.template_show = true
                        $scope.template_showin = true
                        $scope.emailBodytaken = list.email_body
                        $scope.editor_value = list

                    } else {

                        var template_list = API.service('templates', API.all('email-marketing'))
                        template_list.one('').get()
                            .then((response) => {
                                $scope.template_list = response.plain().data

                            })
                    }

                    $state.go($state.current, {
                        id: $scope.step_data.id,
                        funnelID: $scope.step_data.funnel_id
                    }, { reload: true })
                });

        }
        /********************************* Send Email /Sms **************************************/

        $scope.send_request = function (data_type, status_id) {

            if (status_id == '1') {

                var count = data_type.sent_count
                if (data_type.action_type == '1') {
                    $state.go('app.funnel-send-detail', {
                        type: data_type,
                        status: status_id,
                        count: count,

                    })
                } else if (data_type.action_type == '2') {
                    $state.go('app.funnel-send-sms', {
                        type: data_type,
                        status: status_id,
                        count: count,

                    })
                } else if (data_type.action_type == '3') {
                    $state.go('app.funnel-step-action-type', {
                        type: data_type,
                        status: status_id,
                        count: count,

                    })
                }

            } else {
                var count = data_type.unsent_count
            }

        }

        /*********************************  Delete Funnel **************************************/
        $scope.delete_funnel_modal = function (i) {
            var state_s = $state

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
                API.one('funnel').one('remove', funnel_id).remove()
                    .then(() => {
                        // var $state = this.$state
                        swal({
                            title: 'Deleted!',
                            text: 'Funnel has been deleted.',
                            type: 'success',
                            confirmButtonText: 'OK',
                            closeOnConfirm: true
                        }, function () {
                            state_s.reload()

                        })
                        $state.go('app.action-funnel')
                    })
            })


        }
        /********************************* Modal window for Add Action/Edit Status/Edit time and Date  **************************************/

        $scope.action_modal = function (value, active_class) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/add-funnel-action-modal.html',
                controller: AddactionModalController,
                resolve: {
                    funnelId: function () {
                        return funnel_id;
                    },
                    value: function () {
                        return value;
                    },
                    active_class: function () {
                        return active_class;
                    }
                }
            });
            return modalInstance;
        }

        $scope.setupDoAction = function (active_class) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/editing-funnel-action-modal.html',
                controller: SetUpActionModalController,
                resolve: {
                    funnelId: function () {
                        return funnel_id;
                    },
                    active_class: function () {
                        return active_class;
                    }
                }
            });
            return modalInstance;
        }

        /********************************* Modal Window For Edit Funnel **************************************/

        $scope.edit_funnel_modal = function (funnel_details) {
            const modalInstance = $uibModal.open({
                animation: true,
                templateUrl: './views/app/pages/email-marketing/action-funnel/new_action_funnel_modal.html',
                controller: EditActionFunnelModalController,
                resolve: {
                    funnelId: function () {
                        return funnel_id;
                    },
                    funnelDetail: function () {
                        return funnel_details;
                    }
                }
            });
            return modalInstance;
        }
        $rootScope.$on('scanner-started', function (event, args) {

            var text = args.any;
            $scope.insertAtCaret = function (text) {
                var txtarea = document.getElementById('username');
                var scrollPos = txtarea.scrollTop;
                var strPos = 0;
                var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
                    "ff" : (document.selection ? "ie" : false));
                if (br == "ie") {
                    txtarea.focus();
                    var range = document.selection.createRange();
                    range.moveStart('character', -txtarea.value.length);
                    strPos = range.text.length;
                }
                else if (br == "ff") strPos = txtarea.selectionStart;

                var front = (txtarea.value).substring(0, strPos);
                var back = (txtarea.value).substring(strPos, txtarea.value.length);
                txtarea.value = front + text + back;

                strPos = strPos + text.length;
                if (br == "ie") {
                    txtarea.focus();
                    var range = document.selection.createRange();
                    range.moveStart('character', -txtarea.value.length);
                    range.moveStart('character', strPos);
                    range.moveEnd('character', 0);
                    range.select();
                }
                else if (br == "ff") {
                    txtarea.selectionStart = strPos;
                    txtarea.selectionEnd = strPos;
                    txtarea.focus();
                }
                txtarea.scrollTop = scrollPos;
                // angular.element('#username')[0].value += anyThing
            }
            $scope.insertAtCaret(text)

        });

    }

    $onInit() { }
}
class mergeTagsController {
    constructor($window, $http, $rootScope, $stateParams, $scope, $state, API, $uibModal, $timeout, $uibModalInstance) {

        $scope.tags = [
            {
                name: 'First Name',
                value: '{$first_name}'
            }, {
                name: 'Last Name',
                value: '{$last_name}'
            }, {
                name: 'Appointment DateTime',
                value: '{$datetime}'
            }, {
                name: 'Appointment Time',
                value: '{$time}'
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
            }, {
                name: 'Appointment Status',
                value: '{$status}'
            }
        ]
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        $scope.puttagInEditor = function (tagname) {
            $rootScope.$broadcast('scanner-started', { any: tagname });

            $uibModalInstance.close();
        }

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

class FunnelEditorModalController {
    constructor($stateParams, $scope, id, list, funnel_id, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        var beeConfig = null
        var bee = '';
        window.localStorage.removeItem("newsletter.autosave.json")
        window.localStorage.removeItem("newsletter.autosave.html")
        var bee_temp = function (obj, id) { //bee editor 
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
                    if (list.company_id != null && list.company_id != undefined) {
                        var update_template_title = API.service('company-template', API.all('funnel'));
                        update_template_title.post({
                            "json_body": jsonFile,
                            "html_body": htmlFile,
                            "template_id": list.id,
                            "category": list.category
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
                    $scope.update_action_detail(id)
                },
                onSaveAsTemplate: function (jsonFile) { // + thumbnail? 
                    bee.save()
                    const modalInstance = $uibModal.open({
                        animation: true,
                        templateUrl: './views/app/pages/email-marketing/campaigns/previw-template-modal.html',
                        controller: TemplateTitleModalController,
                        resolve: {

                        }
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

                        bee.start(JSON.parse(obj));

                    });
                });
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
            name: 'First Name',
            value: '{$first_name}'
        }, {
            name: 'Last Name',
            value: '{$last_name}'
        }, {
            name: 'Appointment DateTime',
            value: '{$datetime}'
        }, {
            name: 'Appointment Time',
            value: '{$time}'
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
        }, {
            name: 'Appointment Status',
            value: '{$status}'
        }, {
            name: 'Unsubscribe',
            value: '{$unsubscribe_link}'
        }];

        var mergeContents = [{
            name: 'Patient First Name',
            value: '{$first_name}'
        }, {
            name: 'Patient Last Name',
            value: '{$last_name}'
        }, {
            name: 'Appointment DateTime',
            value: '{$datetime}'
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
        if (id != null) {
            bee_temp(list.json_body, id)
        } else {
            bee_temp(list.json_body, list.id)
        }

        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $state.reload()
            $uibModalInstance.close();

        }

        $scope.update_action_detail = function (id) {
            var action_vars = {
                'funnel_id': funnel_id,
                "json_body": $window.localStorage.getItem('newsletter.autosave.json'),
                "email_body": $window.localStorage.getItem('newsletter.autosave.html'),

            }
            $scope.update_api(action_vars, id)

        }

        $scope.update_api = function (action_vars, id) {
            var update_steps_details = API.service('action-step/' + id, API.all('funnel'));
            update_steps_details.post(action_vars)
                .then((response) => {
                });
            $state.go($state.current, {
                id: id,
                funnelID: funnel_id
            }, { reload: true })
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
                "type": '2'
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

class SetUpActionModalController {
    constructor($stateParams, $scope, funnelId, active_class, $state, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, $window) {
        'ngInject'
        $scope.obj = {}
        $scope.obj.tags = []
        $scope.obj.selectedTags = []
        $scope.obj.actionTodo = 'addtolist'
        $scope.showActionList = true
        var step_id = active_class.id
        if (active_class.do_action == 'addtolist' || active_class.do_action == 'removefromlist') {
            $scope.obj.actionTodo = active_class.do_action

            if (active_class.emlist) {
                var newlist = {
                    id: active_class.emlist.id,
                    title: active_class.emlist.name
                }
                $scope.obj.tags.push(newlist)
            }
        } else if (active_class.do_action == 'addtag' || active_class.do_action == 'removetag') {

            if (active_class.tag) {
                $scope.showActionList = false
                $scope.obj.actionTodo = active_class.do_action
                $scope.obj.selectedTags.push(active_class.tag.term_value)
            }
        }


        $scope.onTagAdded = function (limit) {
            $scope.$watch('obj.tags', function () {
                if ($scope.obj.tags) {
                    if ($scope.obj.tags.length = limit + 1) {
                        $scope.obj.tags.pop();
                    }
                }

            })

        }


        $scope.$watch('obj.actionTodo', function () {
            if ($scope.obj.actionTodo == 'addtag' || $scope.obj.actionTodo == 'removetag') {
                $scope.showActionList = false
                $scope.obj.tags = ''
            } else {
                $scope.showActionList = true
                $scope.obj.selectedTags = ''
            }
        })
        $scope.loadList = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/email-marketing/find-email-list?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/contacts/find-tags?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }

        $scope.updateDoAction = function () {
            var Obj = {};
            var update_step = API.service('action-step/' + step_id, API.all('funnel'));
            if ($scope.obj.tags) {

                Obj = {
                    'do_action': $scope.obj.actionTodo,
                    'list': $scope.obj.tags
                }
                update_step.post(Obj)
                    .then((response) => {
                        $state.go($state.current, {
                            id: step_id
                        }, { reload: true })
                    });

            } else if ($scope.obj.selectedTags) {

                Obj = {
                    'do_action': $scope.obj.actionTodo,
                    'tag': $scope.obj.selectedTags
                }
                update_step.post(Obj)
                    .then((response) => {
                        $state.go($state.current, {
                            id: step_id
                        }, { reload: true })
                    });

            }
            $uibModalInstance.close();
        }

        $scope.onNewTagAdded = function (limit) {
            $scope.$watch('obj.selectedTags', function () {
                if ($scope.obj.selectedTags) {
                    if ($scope.obj.selectedTags.length = limit + 1) {
                        $scope.obj.selectedTags.pop();
                    }
                }

            })

        }

        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

    }
}
class AddactionModalController {
    constructor($stateParams, $scope, active_class, $state, $location, API, $uibModal, $uibModalInstance, funnelId, $timeout, $rootScope, value) {
        'ngInject'
        $scope.createlist = true
        $scope.trigger_value = '2'
        $scope.obj = {}
        $scope.obj.action_value = '1'
        $scope.trigger_text = '0'
        var funnel_id = funnelId
        $scope.status_value = value
        var list = active_class;
        if ($scope.status_value != '0') {
            $scope.obj.statusID = list.status
            var step_id = list.id
        }

        $scope.action_text = function (text) {
            if (text == '0') {
                var name = 'Add New Funnel Step'
                return name
            }
            else {
                name = 'Update Action Settings'
                return name
            }
        }
        if ($scope.status_value == 1 || $scope.status_value == 2) {
            $scope.step_name = list.name
            $scope.trigger_text = list.trigger_value
            $scope.trigger_value = list.trigger_type
        }


        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        /********************************* Create Action Steps **************************************/

        $scope.create_step_api = function (Obj) {

            var addComment = API.service('create-action-step', API.all('funnel'));
            addComment.post(Obj)
                .then((response) => {
                    $state.go($state.current, {
                        id: response.plain().data.step_id
                    }, { reload: true })
                });
        }

        $scope.update_step_api = function (Obj, step_id) {

            var update_step = API.service('action-step/' + step_id, API.all('funnel'));
            update_step.post(Obj)
                .then((response) => {
                    $state.go($state.current, {
                        id: step_id
                    }, { reload: true })
                });
        }

        /********************************* Submit form for creating Action Steps **************************************/

        $scope.submit_action_form = function () {
            var state_s = $state
            if ($scope.status_value == '0') {
                var Obj = {
                    step_name: $scope.step_name,
                    trigger_value: $scope.trigger_text,
                    trigger_type: $scope.trigger_value,
                    action_type: $scope.obj.action_value,
                    funnel_id: funnel_id
                }
                $scope.create_step_api(Obj)
            } else {

                var Obj = {
                    step_name: $scope.step_name,
                    trigger_value: $scope.trigger_text,
                    trigger_type: $scope.trigger_value,
                    status: $scope.obj.statusID
                }
                $scope.update_step_api(Obj, step_id)
            }
            state_s.reload()
            $uibModalInstance.close();
        }


    }
}

class EditActionFunnelModalController {
    constructor($stateParams, $scope, $state, funnelId, $http, $location, API, $uibModal, $uibModalInstance, $timeout, $rootScope, funnelDetail, $window) {
        'ngInject'
        $scope.editfunnel = true
        var funnel_detail = funnelDetail
        $scope.tags = []
        $scope.obj = {}
        $scope.loadTags = function (query) {
            var token = $window.localStorage.satellizer_token
            return $http.get('/api/email-marketing/find-email-list?s=' + query, {
                headers: { 'Authorization': "Bearer " + token }
            });
        }
        if (funnel_detail[0].f_list.length > 0) {
            for (var i = 0; i < funnel_detail[0].f_list.length; i++) {
                $scope.tags.push({ title: funnel_detail[0].f_list[i].listdetail.name, id: funnel_detail[0].f_list[i].listdetail.id })

            }
        }

        $scope.add_funnel_name = funnel_detail[0].name
        $scope.obj.sendingStatus = funnel_detail[0].status
        var funnel_id = funnelId
        var uibModalInstance = $uibModalInstance;
        $scope.closemodal = function () {
            $uibModalInstance.close();
        }

        /********************************* Submit Form for Edit Funnel Details **************************************/

        $scope.submit_action_form = function () {
            var state_s = $state
            var list_name = $scope.add_funnel_name;
            var tags = $scope.tags;
            var statUs = $scope.obj.sendingStatus

            var addComment = API.service('update', API.all('funnel'));
            addComment.post({
                funnel_id: funnel_id,
                name: list_name,
                list: tags,
                status: statUs

            })
                .then((response) => {
                    $uibModalInstance.close();
                });
            state_s.reload()
        }
    }
}

export const ActionFunnelDetailsComponent = {
    templateUrl: './views/app/pages/email-marketing/action-funnel/action-funnel-details.html',
    controller: ActionFunnelDetailsController,
    controllerAs: 'vm',
    bindings: {}
}