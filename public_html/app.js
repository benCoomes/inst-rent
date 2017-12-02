// define dependencies the main module
// other that ngRoute, these are all view controllers
angular.module('instRent', [
  'ngRoute',
  'instRent.main',
  'instRent.login',
  'instRent.instruments',
  'instRent.userHome',
  'instRent.managerHome', 
  'instRent.adminHome',
  'instRent.addInstrument',,
  'instRent.editInstrument',
  'instRent.makeRequest',
  'instRent.contracts',
  'instRent.manageUsers',
  'instRent.addUser',
  'instRent.editUser',
  'instRent.editProfile',
  'instRent.header'
])

// define a service to load sessions 
.factory('sessionLoader', function($http){
  var getSession = function() {
    return $http.get('php/ajax_handlers.php?action=get_session')
    .then(function onSuccess(result){
      return result.data.data;
    }, function onError(result){
      console.log('failed to get session')
      return {};
    });
  };

  return {getSession : getSession};
});


// define the main module
angular.module('instRent.main', ['ngRoute'])
// and its controller
.controller('mainCtrl', function mainCtrl($scope, $rootScope, sessionLoader, $location, $http, $httpParamSerializerJQLike){
  sessionLoader.getSession().then(function(result){
    $rootScope.session = result;
    if(! $rootScope.session.signedIn){
      $location.url('login');
    }
  });
})


