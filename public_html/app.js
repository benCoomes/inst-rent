angular.module('instRent', [
  'ngRoute',
  'instRent.main',
  'instRent.login',
  'instRent.instruments',
  'instRent.userHome',
  'instRent.managerHome', 
  'instRent.adminHome',
  'instRent.addInstrument',
  'instRent.makeRequest',
  'instRent.contracts',
  'instRent.manageUsers',
  'instRent.addUser',
  'instRent.editUser',
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

  return {getSession : getSession};
});


angular.module('instRent.main', ['ngRoute'])

.controller('mainCtrl', function mainCtrl($scope, $rootScope, sessionLoader, $location, $http, $httpParamSerializerJQLike){
  sessionLoader.getSession().then(function(result){
    $rootScope.session = result;
    // changing location here causes page to go to home on load and reload from anywhere in website
    if(! $rootScope.session.signedIn){
      $location.url('login');
    }
  });
})


