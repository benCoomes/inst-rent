angular.module('instRent.addUser', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/addUser', {
    templateUrl: 'views/addUser/addUser.html',
    controller: 'AddUserCtrl',
  });
}])

.controller('AddUserCtrl', function AddUserCtrl($scope, $rootScope, $http, $httpParamSerializerJQLike){
  $scope.resetForm = function(){
    $scope.addUserForm = {
      'cuid' : '',
      'email' : '',
      'username' : '',
      'first_name' : '',
      'last_name' : '',
      'password' : '',
      'password_confirm' : '',
      'role' : '',   
    }
    $scope.validate();
  }

  $scope.validate = function(){
    console.log($scope.addUserForm);
    // for some reason, email must be in valid form, even though there are not check for it... spooky. 
    // may be due to html5 validation for type='email'
    if($scope.addUserForm &&
        $scope.addUserForm.cuid && 
        $scope.addUserForm.email && 
        $scope.addUserForm.username &&
        $scope.addUserForm.password &&
        $scope.addUserForm.password_confirm &&
        $scope.addUserForm.role &&
        typeof($scope.addUserForm.cuid) == 'number' &&
        $scope.addUserForm.password == $scope.addUserForm.password_confirm){
      $scope.valid = true;
    } else {
      $scope.valid = false;
    }
  }

  $scope.submitForm = function(){
    if($rootScope.session.role == 'admin'){
      $http({
        url: 'php/ajax_handlers.php?action=add_user',
        method: 'POST',
        data: $httpParamSerializerJQLike($scope.addUserForm),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.resetForm();
        console.log(result.data);
        alert("Successfully added user.")
      }, function onError(result){
        alert("Failed to add user.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }
 
  $scope.resetForm(); // also initializes the form
})