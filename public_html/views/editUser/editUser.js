angular.module('instRent.editUser', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/editUser', {
    templateUrl: 'views/editUser/editUser.html',
    controller: 'editUserCtrl',
  });
}])

.controller('editUserCtrl', function editUserCtrl($scope, $rootScope, $routeParams, $http, $httpParamSerializerJQLike){
  $scope.loadForm = function(){
    $scope.editUserForm = {
      'cuid' : '',
      'cuEmail' : '',
      'username' : '',
      'firstName' : '',
      'lastName' : '',
      'password' : '',
      'passwordConfirm' : '',
      'role' : '',   
    }

    if($scope.params.cuid){
      $http.get('php/ajax_handlers_cst.php?action=get_users&cuid=' + $scope.params.cuid)
      .then(function onSuccess(result){
        let user = result.data.data[0];
        console.log(result);
        if(user){      
          $scope.editUserForm.cuid = parseInt(user.cuid);
          $scope.editUserForm.cuEmail = user.email;
          $scope.editUserForm.username = user.username;
          $scope.editUserForm.firstName = user.firstName;
          $scope.editUserForm.lastName = user.lastName;
          $scope.editUserForm.role = user.role;
          $scope.disableCuid = true;
          $scope.validate();
        } else {
          $scope.disableCuid = false;
          $scope.validate();
        }
      }, function onError(result){
        alert('There was an error retrieving user data.');
        console.log(result);
        $scope.validate();
      });
    } else {
      $scope.disableCuid = false;
      $scope.validate();
    }
  }

  $scope.validate = function(){
    // for some reason, email must be in valid form, even though there are not check for it... spooky. 
    // may be due to html5 validation for type='email'
    if($scope.editUserForm &&
        $scope.editUserForm.cuid && 
        $scope.editUserForm.cuEmail && 
        $scope.editUserForm.username &&
        $scope.editUserForm.firstName &&
        $scope.editUserForm.lastName &&
        $scope.editUserForm.role &&
        typeof($scope.editUserForm.cuid) == 'number' &&
        $scope.editUserForm.password == $scope.editUserForm.passwordConfirm){
      $scope.valid = true;
    } else {
      $scope.valid = false;
    }
  }

  $scope.submitForm = function(){
    if($rootScope.session.role == 'admin'){
      $http({
        url: 'php/ajax_handlers_cst.php?action=edit_user',
        method: 'POST',
        data: $httpParamSerializerJQLike($scope.editUserForm),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.loadForm();
        console.log(result.data);
        alert("Successfully updated user.")
      }, function onError(result){
        alert("Failed to update user.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }
 
  $scope.params = $routeParams;
  $scope.loadForm(); // also initializes the form
})