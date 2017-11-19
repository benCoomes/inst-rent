angular.module('instRent', [
  'ngRoute',
  'instRent.main',
  'instRent.login',
  'instRent.instruments',
  'instRent.userHome',
  'instRent.managerHome', 
  'instRent.adminHome',
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
  sessionLoader.getSession().then(function(result){
    $rootScope.session = result;
    // changing location here causes page to go to home on load and reload from anywhere in website
    if($rootScope.session.signedIn){
      if($rootScope.session.role == 'admin'){
        $location.url('adminHome');
      } else if ($rootScope.session.role == 'manager'){
        $location.url('managerHome');
      } else {
        // TODO: add generic error page to send users to in case role is not any of the three valid roles
        $location.url('userHome');
      }
    } else {
      $location.url('login');
    }
  });
})


