
angular.module('instRent', [
  'ngRoute',
  'instRent.main',
  'instRent.login'
])
  

angular.module('instRent.main', ['ngRoute'])
.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/', {
    controller : 'mainCtrl'
  })
}])
.controller('mainCtrl', function headerCtrl($scope, $location, $http, $httpParamSerializerJQLike){
  $scope.informationalTitle = "This is coming from the headerCtrl in app.js!";

  // get session info
  $http.get('php/ajax_handlers.php?action=get_session')
  .then(function onSuccess(result){
    console.log(result)
    $scope.session = result.data.data;
    if($scope.session.signedIn){
      $location.url('/home');
    } else{
      $location.url('/login')
    }

  }, function onError(result){
    $scope.session = {};
    alert(result.data.msg);
  })

  $scope.signOut = function() {
    $http({
      url:'php/ajax_handlers.php?action=sign_out',
      method: 'POST',
      data: $httpParamSerializerJQLike([]),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      timeout: 2000
    })
    .then(function onSuccess(result){
      $scope.session = {};
      $location.url('login');
    }, function onError(result){
      // do things with result on error
      alert("Oops, we couldn't sign you out!");
    })
  };
});
