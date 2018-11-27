import { RouteBodyClassComponent } from './directives/route-bodyclass/route-bodyclass.component'
import { PasswordVerifyClassComponent } from './directives/password-verify/password-verify.component'


angular.module('app.components')
  .directive('routeBodyclass', RouteBodyClassComponent)
  .directive('passwordVerify', PasswordVerifyClassComponent)
  .directive('socialHeader', function () {
    return {
      templateUrl: './views/app/pages/social-connect/social-connect-header.component.html'
    };
  })
  .directive('addSocialModal', function () {
    return {
      templateUrl: './views/app/pages/social-connect/social-posts-add.modal.html'
    };
  })
  .directive('addTaskModal', function () {
    return {
      templateUrl: './views/app/pages/opportunities/lead-details/add-task-modal.html'
    };
  })
  .directive('appointmentContactInfoModal', function () {
    return {
      templateUrl: './views/app/pages/add-appointment-modals/app-contact-modal.html'
    };
  })
  .directive('appointmentInfoModal', function () {
    return {
      templateUrl: './views/app/pages/add-appointment-modals/app-appointment-info-modal.html'
    };
  })

  .directive('actionfunnelsendsms', function () {
    return {
      templateUrl: './views/app/pages/email-marketing/action-funnel/action-funnel-send-sms.html'
    };
  })
  .directive('actionfunnelsendemail', function () {
    return {
      templateUrl: './views/app/pages/email-marketing/action-funnel/action-funnel-send-email.html'
    };
  })
  .directive('newcampaigns', function () {
    return {
      templateUrl: './views/app/pages/email-marketing/campaigns/add-new-campaigns.html'
    };
  })

  .directive('contactlist', function () {
    return {
      templateUrl: './views/app/pages/conversation/contact-list.html'
    };
  })
  .directive('contactdetail', function () {
    return {
      templateUrl: './views/app/pages/conversation/contact-details.html'
    };
  })
  .directive('threadcomp', function () {
    return {
      templateUrl: './views/app/pages/conversation/thread-component.html'
    };
  })
  .directive('newsms', function () {
    return {
      templateUrl: './views/app/pages/email-marketing/sms-broadcast/new-sms-broadcast-modal.html'
    };
  })
  .directive('accountsetup', function () {
    return {
      templateUrl: './views/app/pages/account-setup-screen/account-setup-screen.html'
    };
  })
  .directive('fileModel', ['$parse', function ($parse) {
    return {
      restrict: 'A',
      link: function (scope, element, attrs) {
        var model = $parse(attrs.fileModel);
        var modelSetter = model.assign;

        element.bind('change', function () {
          scope.$apply(function () {
            modelSetter(scope, element[0].files[0]);
          });
        });
      }
    };
  }])
  .directive('pwCheck', [function () {
    return {
      require: 'ngModel',
      link: function (scope, elem, attrs, ctrl) {
        var firstPassword = '#' + attrs.pwCheck;
        elem.add(firstPassword).on('keyup', function () {
          scope.$apply(function () {
            var v = elem.val() === $(firstPassword).val();
            ctrl.$setValidity('pwmatch', v);
          });
        });
      }
    }
  }])
  .directive('validateEmail', [function () {
    var EMAIL_REGEXP = /^[_a-z0-9]+(\.[_a-z0-9]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/;
    return {
      restrict: 'A',
      require: 'ngModel',
      link: function (scope, elm, attrs, ctrl) {
        elm.on("keyup", function () {
          var isMatchRegex = EMAIL_REGEXP.test(elm.val());
          if (isMatchRegex && elm.hasClass('warning') || elm.val() == '') {
            ctrl.$setValidity('validateEmail', false);
          } else if (isMatchRegex == false && !elm.hasClass('warning')) {
            ctrl.$setValidity('validateEmail', true);
          }
        });
      }
    }
  }])
  .directive('phoneInput', function ($filter, $browser) {
    return {
      require: 'ngModel',
      link: function ($scope, $element, $attrs, ngModelCtrl) {
        var listener = function () {
          var value = $element.val().replace(/[^0-9]/g, '');
          $element.val($filter('tel')(value, false));
        };

        // This runs when we update the text field
        ngModelCtrl.$parsers.push(function (viewValue) {
          return viewValue.replace(/[^0-9]/g, '').slice(0, 10);
        });

        // This runs when the model gets updated on the scope directly and keeps our view in sync
        ngModelCtrl.$render = function () {
          $element.val($filter('tel')(ngModelCtrl.$viewValue, false));
        };

        $element.bind('change', listener);
        $element.bind('keydown', function (event) {
          var key = event.keyCode;
          // If the keys include the CTRL, SHIFT, ALT, or META keys, or the arrow keys, do nothing.
          // This lets us support copy and paste too
          if (key == 91 || (15 < key && key < 19) || (37 <= key && key <= 40)) {
            return;
          }
          $browser.defer(listener); // Have to do this or changes don't get picked up properly
        });

        $element.bind('paste cut', function () {
          $browser.defer(listener);
        });
      }

    };
  })

  .directive('validFile', function ($parse) {
    return {
      require: 'ngModel',
      restrict: 'A',
      link: function (scope, el, attrs, ngModel) {
        var model = $parse(attrs.ngModel);
        var modelSetter = model.assign;
        var maxSize = 1024000; //2000 B
        el.bind('change', function () {
          scope.file_error = false;
          scope.$apply(function () {

            if (el[0].files.length > 1) {
              modelSetter(scope, el[0].files);
            } else {
              modelSetter(scope, el[0].files[0]);
            }
            var fileSize = el[0].files[0].size;
            if (fileSize > maxSize) {
              scope.file_error = true;
            }
          });
        });
      }
    }
  })
  .directive('fileDropzone', function () {
    return {
      restrict: 'A',
      scope: {
        file: '=',
        fileName: '='
      },
      link: function (scope, element, attrs) {
        var checkSize,
          isTypeValid,
          processDragOverOrEnter,
          validMimeTypes;

        processDragOverOrEnter = function (event) {
          if (event != null) {
            event.preventDefault();
          }
          event.dataTransfer.effectAllowed = 'copy';
          return false;
        };

        validMimeTypes = attrs.fileDropzone;

        checkSize = function (size) {
          var _ref;
          if (((_ref = attrs.maxFileSize) === (void 0) || _ref === '') || (size / 1024) / 1024 < attrs.maxFileSize) {
            return true;
          } else {
            alert("File must be smaller than " + attrs.maxFileSize + " MB");
            return false;
          }
        };

        isTypeValid = function (type) {
          if ((validMimeTypes === (void 0) || validMimeTypes === '') || validMimeTypes.indexOf(type) > -1) {
            return true;
          } else {
            alert("Invalid file type.  File must be one of following types " + validMimeTypes);
            return false;
          }
        };

        element.bind('dragover', processDragOverOrEnter);
        element.bind('dragenter', processDragOverOrEnter);

        return element.bind('drop', function (event) {
          var file, name, reader, size, type;
          if (event != null) {
            event.preventDefault();
          }
          reader = new FileReader();
          reader.onload = function (evt) {
            if (checkSize(size) && isTypeValid(type)) {
              return scope.$apply(function () {
                scope.file = evt.target.result;
                if (angular.isString(scope.fileName)) {
                  return scope.fileName = name;
                }
              });
            }
          };
          file = event.dataTransfer.files[0];
          name = file.name;
          type = file.type;
          size = file.size;
          reader.readAsDataURL(file);
          return false;
        });
      }
    };
  })

  .directive('getPageTitle', function ($parse) {
    return {
      restrict: 'A',
      link: function (scope, el, attrs, ngModel) {
        alert("hello")
        /*var model = $parse(attrs.ngModel);
        var modelSetter = model.assign;
        var maxSize = 1024000; //2000 B
        el.bind('change', function() {
            scope.file_error = false;
            scope.$apply(function() {
                
                if (el[0].files.length > 1) {
                    modelSetter(scope, el[0].files);
                } else {
                    modelSetter(scope, el[0].files[0]);
                }
                var fileSize = el[0].files[0].size;
                if (fileSize > maxSize) {
                    scope.file_error = true;
                }
            });
        });*/
      }
    }
  })
  .directive('inlineEdit', function ($timeout) {
    return {
      scope: {
        model: '=inlineEdit',
        handleSave: '&onSave',
        handleCancel: '&onCancel'
      },
      link: function (scope, elm, attr) {
        var previousValue;

        scope.edit = function () {
          scope.editMode = true;
          previousValue = scope.model;

          $timeout(function () {
            elm.find('input')[0].focus();
          }, 0, false);
        };
        scope.save = function () {
          scope.editMode = false;
          scope.handleSave({ value: scope.model });
        };
        scope.cancel = function () {
          scope.editMode = false;
          scope.model = previousValue;
          scope.handleCancel({ value: scope.model });
        };
      },
      templateUrl: './views/app/pages/email-marketing/action-funnel/inline-edit.html'
    };
  })
  .directive('draggable', function () {
    return function (scope, element) {
      // this gives us the native JS object
      var el = element[0];

      el.draggable = true;

      el.addEventListener(
        'dragstart',
        function (e) {
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('Text', this.id);
          this.classList.add('drag');
          return false;
        },
        false
      );

      el.addEventListener(
        'dragend',
        function (e) {
          this.classList.remove('drag');
          return false;
        },
        false
      );
    }
  })
  .directive("myStopButton",
    function () {
      return {
        restrict: "E",
        require: "^videogular",
        template: "<div class='iconButton' ng-click='API.stop()'>STOP</div>",
        link: function (scope, elem, attrs, API) {
          scope.API = API;
        }
      }
    }
  )
  .directive('disallowSpaces', function () {
    return {
      restrict: 'A',

      link: function ($scope, $element) {
        $element.bind('input', function () {
          $(this).val($(this).val().replace(/ /g, ''));
        });
      }
    };
  })
  .directive("datepicker", function () {
    return {
      restrict: "A",
      link: function (scope, el, attr) {
        el.datepicker({
          dateFormat: 'yy-mm-dd'
        });
      }
    };
  })
  .directive('onErrorSrc', function () {
    return {
      link: function (scope, element, attrs) {
        element.bind('error', function () {
          if (attrs.src != attrs.onErrorSrc) {
            attrs.$set('src', attrs.onErrorSrc);
          }
        });
      }
    }
  })
  .directive('clickAndDisable', function () {
    return {
      scope: {
        clickAndDisable: '&'
      },
      link: function (scope, iElement, iAttrs) {
        iElement.bind('click', function () {
          iElement.prop('disabled', true);
          scope.clickAndDisable().finally(function () {
            iElement.prop('disabled', false);
          })
        });
      }
    };
  });