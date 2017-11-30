

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
    'email' : '',
    'username' : '',
    'first_name' : '',
    'last_name' : '',
    'password' : '',
    'password_confirm' : ''
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
      sessionLoader.getSession().then(function(result){
        $rootScope.session = result;
        if($rootScope.session.role == 'admin'){
          $location.url('adminHome');
        } else if ($rootScope.session.role == 'manager'){
          $location.url('managerHome');
        } else {
          $location.url('userHome');
        }
      });
    }, function onError(result){
      alert('Invalid username and password combination.')
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
      console.log(result.data);
      sessionLoader.getSession().then(function(result){
        $rootScope.session = result;
        $location.url('userHome');
      });
    }, function onError(result){
      alert('failed to create user.');
      console.log(result.data);
    })
  }
});