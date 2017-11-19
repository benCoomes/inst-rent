

angular.module('instRent.login', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/login', {
    templateUrl: 'views/login/login.html',
    controller: 'LoginCtrl'
  });
}])

.controller('LoginCtrl', function LoginCtrl($scope, $rootScope,sessionLoader, $route, $location, $http, $httpParamSerializerJQLike){
  $scope.informationalTitle = "This is coming from the loginCtrl in login.js!";

  $scope.signInForm = {
    'username' : '',
    'password' : ''
  }

  $scope.signUpForm = {
    'cuid' : '',
    'cuEmail' : '',
    'username' : '',
    'firstName' : '',
    'lastName' : '',
    'password' : '',
    'passwordConfirm' : ''
  }

  // these functions will be called when the forms are submitted. This
  // behavior is specified in the html. 
  $scope.submitSignInForm = function(){
    // make an ajax post, and define success and error handlers.
    $http({
      url:'php/ajax_handlers_cst.php?action=sign_in',
      method:'POST',
      data: $httpParamSerializerJQLike($scope.signInForm),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      timeout: 2000
    })
    .then(function onSuccess(result){
      // do things with result on success
      console.log(result.data);
      sessionLoader.getSession().then(function(result){
        $rootScope.session = result;
      })
      $location.url('/home');
    }, function onError(result){
      // do things with result on error
      console.log(result.data);
    })
  }

  $scope.submitSignUpForm = function(){
     $http({
      url:'php/ajax_handlers_cst.php?action=sign_up',
      method:'POST',
      data: $httpParamSerializerJQLike($scope.signUpForm),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      timeout: 2000
    })
    .then(function onSuccess(result){
      // do things with result on success
      // be sure to set the root session!!
      console.log(result.data);
    }, function onError(result){
      // do things with result on error
      console.log(result.data);
    })
  }
});