

angular.module('instRent.login', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/login', {
    templateUrl: 'views/login/login.html',
    controller: 'LoginCtrl'
  });
}])

.controller('LoginCtrl', function LoginCtrl($scope, $route, $location, $http, $httpParamSerializerJQLike){
  // set up properties on scope- these can be functions, objects, and primitive types

  // these are objects, and they will be bound to the values entered into
  // the form by the user. This means that $scope.signInForm.username will
  // always be equal to what is seen in the html form. The value can be 
  // changed from the form or from js code. 
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
      url:'php/ajax_handlers.php?action=sign_in',
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
      $location.url('/home');
    }, function onError(result){
      // do things with result on error
      console.log(result.data);
    })
  }

  $scope.submitSignUpForm = function(){
     $http({
      url:'php/ajax_handlers.php?action=sign_up',
      method:'POST',
      data: $httpParamSerializerJQLike($scope.signUpForm),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      timeout: 2000
    })
    .then(function onSuccess(result){
      // do things with result on success
      console.log(result.data);
    }, function onError(result){
      // do things with result on error
      console.log(result.data);
    })
  }
});