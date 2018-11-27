export class SAService {
  constructor (Restangular, $window,$http) {

    'ngInject'
    // content negotiation
    var hh= $window.localStorage.contentType;
    
    if(hh=='undefined'){ var ht=undefined; }
    else{ var ht='application/json';}

    var headers = { 'Content-Type':ht,'Accept': 'application/x.laravel.v1+json'}
    delete $window.localStorage.contentType

    return Restangular.withConfig(function (RestangularConfigurer) {
      RestangularConfigurer
        .setBaseUrl('/api/')
        .setDefaultHeaders(headers)
        .setErrorInterceptor(function (response) {
          if (response.status === 422) {
            // for (var error in response.data.errors) {
            // return ToastService.error(response.data.errors[error][0])
            // }
          }
        })
        .addFullRequestInterceptor(function (element, operation, what, url, headers) {
          var token = $window.localStorage.super_admin_token
          if (token) {}
        })
        .addResponseInterceptor(function (response, operation, what) {
          if (operation === 'getList') {
            var newResponse = response.data[what]
            newResponse.error = response.error
            return newResponse
          }

          return response
        })
    })
  }
}
