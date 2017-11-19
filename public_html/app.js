
angular.module('instRent', [
  'ngRoute',
  'instRent.main',
  'instRent.login',
  'instRent.home',
  'instRent.header'
])

.factory('sessionLoader', function($http){
  var getSession = function() {
    return $http.get('php/ajax_handlers_cst.php?action=get_session')
    .then(function onSuccess(result){
      return result.data.data;
    }, function onError(result){
      console.log('failed to get session')
      return {};
    });
  };

  return { getSession : getSession};
});


angular.module('instRent.main', ['ngRoute'])

.controller('mainCtrl', function mainCtrl($scope, $rootScope, sessionLoader, $location, $http, $httpParamSerializerJQLike){

  // WARNING: setting the location here may be problematic later on.
  sessionLoader.getSession().then(function(result){
    $rootScope.session = result;
    if(result.signedIn){
      $location.url('home');
    } else {
      $location.url('login');
    }
  });
})


