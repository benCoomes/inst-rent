
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
      console.log('got session');
      console.log(result.data.data);
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
  $scope.informationalTitle = "This is coming from the mainCtrl in app.js!";
  $scope.title = 'Clemson Uni Inst Rent';

  $scope.signOut = function() {
    $http({
      url:'php/ajax_handlers_cst.php?action=sign_out',
      method: 'POST',
      data: $httpParamSerializerJQLike([]),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      timeout: 2000
    })
    .then(function onSuccess(result){
      $rootScope.session = {};
      $location.url('login');
    }, function onError(result){
      // do things with result on error
      alert("Oops, we couldn't sign you out!");
    })
  };

  sessionLoader.getSession().then(function(result){
    $rootScope.session = result;
    if(result.signedIn){
      $location.url('home');
    } else {
      $location.url('login');
    }
  });

  console.log('At end of main Ctrl');
  console.log($scope);
})


