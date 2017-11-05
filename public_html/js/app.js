'use strict'

var app = angular.module('inst_rent', ['ngRoute']);

app.controller('mainCtrl', function mainCtrl($scope, $route, $window, $http, $httpParamSerializerJQLike){
  // set up properties on scope- these can be functions, objects, and primitive types

  // here is an example of a primitive
  $scope.informationalTitle = "This is coming from js/app.js!"

  //TODO: check for current session, filling session object

  // these are objects, and they will be bound to the values entered into
  // the form by the user. This means that $scope.signInForm.username will
  // always be equal to what is seen in the html form. The value can be 
  // changed from the form or from js code. 
  $scope.session = {
    'signedIn' : false,
    'username' : '',
    'role' : ''
  }


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
      
      let sessionData = result.data.data;

      $scope.session.username = sessionData.username;
      $scope.session.signed_in = true;
      $scope.session.role = sessionData.role;
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
      // reload page for now. Will want to set relative path in the future.
      $window.location.reload();
    }, function onError(result){
      // do things with result on error
      alert("Oops, we couldn't sign you out!");
    })
  }
});