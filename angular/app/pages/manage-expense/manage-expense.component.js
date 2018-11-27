class ManageExpenseController {
    constructor($scope,$stateParams,$state, $compile,$uibModal, DTOptionsBuilder, DTColumnBuilder, API, $window,$timeout) {
        'ngInject'
        this.API = API
        this.$state = $state
        this.isdelseleted = false;
        this.$state = $state
        this.formSubmitted = false
        this.API = API
        this.alerts = []
        this.uibModal = $uibModal
        if ($stateParams.alerts) {
            $scope.showMessage=$stateParams.alerts;
            this.alerts.push($stateParams.alerts)
            $timeout(function() {
                 $scope.showMessage = false;
            }, 3000);
        }
        var token = $window.localStorage.satellizer_token
                this.dtOptions = DTOptionsBuilder.newOptions()
                    .withOption('ajax', {
                        contentType: 'application/json',
                        url: '/api/contacts',
                        type: 'POST',
                        beforeSend: function(xhr){
                            xhr.setRequestHeader("Authorization",
                                "Bearer " + token);
                        },
                        data: function(data, dtInstance) {

                            return JSON.stringify(data);
                        }
                    })
                    .withDataProp('data')
                    .withOption('serverSide', true)
                    .withOption('processing', true)
                    .withColReorder()
                    //.withColReorderOrder([2, 1, 2])
                    .withColReorderOption('iFixedColumnsRight', 1)
                    .withColReorderCallback(function() {
                    })
                    .withOption('createdRow', function(row) {
                        $compile(angular.element(row).contents())($scope);
                    })
                    .withOption('responsive', true)
                    .withOption('aaSorting', [[0, 'desc']])
                    .withBootstrap()
                
                this.dtColumns = [
                   DTColumnBuilder.newColumn(null).withTitle("#").notSortable().renderWith(
                        function(data) {
							return '<i class="fa fa-plus-circle toggle-data"></i>';
							
                        }),
                    DTColumnBuilder.newColumn('id').withTitle('ID'),
                    DTColumnBuilder.newColumn(null).withTitle('Amount').renderWith(function(data){
                        return '150'
                    }),
                    DTColumnBuilder.newColumn(null).withTitle('Month').renderWith(function(data){
                      return 'Jun, 2017'
                    }),
                    DTColumnBuilder.newColumn('SpentOn').withTitle('SpentOn').renderWith(function(){
                        return 'Facebook Advertisement'
                    }),DTColumnBuilder.newColumn('Create On').withTitle('Created On').renderWith(function(){
                            
                        return '14-06-2017 14:05:00'
                    }),
                    DTColumnBuilder.newColumn(null).withTitle('Options').withOption('sWidth', '150px').renderWith(function(data){
                        return `
                        <a class="btn btn-xs btn-primary" uib-tooltip="View" tooltip-placement="bottom" ui-sref="app.viewcontact({contactId: ${data.id}})">
                                <i class="fa fa-eye"></i>
                            </a>                
                            <a class="btn btn-xs btn-warning" uib-tooltip="Edit" tooltip-placement="bottom" ui-sref="app.contactedit({contactId: ${data.id}})">
                                <i class="fa fa-edit"></i>
                            </a>                
                            <button class="btn btn-xs btn-danger" uib-tooltip="Delete" tooltip-placement="bottom" ng-click="vm.delete(${data.id})">
                                <i class="fa fa-trash-o"></i>
                            </button>`
                    })
                    
                    ]
                
                this.displayTable = true

let createdRow = (row) => {
            $compile(angular.element(row).contents())($scope)
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
     openaddexpense() {
        let $uibModal = this.uibModal

        const modalInstance = $uibModal.open({
            animation: true,
            templateUrl: 'dialog.html',
            controller: modalController,
        });
        return modalInstance;
    }
    delete(contactId) {
        let API = this.API
        var $state = this.$state
        var state_s=this.$state
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
        }, function() {
            API.one('contacts').one('contact', contactId).remove()
                .then(() => {
                    var $state = this.$state
                    swal({
                        title: 'Deleted!',
                        text: 'User Permission has been deleted.',
                        type: 'success',
                        confirmButtonText: 'OK',
                        closeOnConfirm: true
                    }, function() {
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
            confirmButtonColor: '#27b7da',
            confirmButtonText: 'Yes, delete it!',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
            html: false
        }, function() {
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
                    }, function() {
                        $state.reload()
                        
                    })
                })
        })

    }

    $onInit() {}
}
class modalController {
    constructor($stateParams, $scope, $state, API, $uibModal,$uibModalInstance) {
       
    }
}
export const ManageExpenseComponent = {
    templateUrl: './views/app/pages/manage-expense/manage-expense.component.html',
    controller: ManageExpenseController,
    controllerAs: 'vm',
    bindings: {}
}
