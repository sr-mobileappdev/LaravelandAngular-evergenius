angular.module('app', [
  'app.run',
  'app.filters',
  'app.services',
  'app.components',
  'app.routes',
  'app.config',
  'app.partials'])
angular.module('app.run', [])
angular.module('app.routes', [])
angular.module('app.filters', [])
angular.module('app.services', [])
angular.module('app.config', [])
angular.module('app.components', [
  'ui.router', 'ui.select', 'ngSanitize', 'angular-loading-bar', 'angularTrix',
  'restangular', 'ngStorage', 'satellizer', 'ui.bootstrap', 'chart.js', 'mm.acl', 'naif.base64', 'datatables',
  'datatables.bootstrap', 'checklist-model', 'daterangepicker', 'infinite-scroll', 'angucomplete-alt', 'datatables.colreorder',
  'ngFileUpload', 'datetimepicker', 'ui.bootstrap.datetimepicker', 'vsGoogleAutocomplete', 'ngTagsInput',
  'ngScrollbars', 'minicolors', 'as.sortable', 'ui.sortable', 'ngSanitize', 'com.2fdevs.videogular', 'com.2fdevs.videogular.plugins.controls',
  'com.2fdevs.videogular.plugins.poster', 'ngclipboard', 'dndLists', 'highcharts-ng'
])

