angular.module('instRent.editProfile', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/editProfile', {
    templateUrl: 'views/editProfile/editProfile.html',
    controller: 'editProfileCtrl',
  });
}])

.controller('editProfileCtrl', function editProfileCtrl($scope, $rootScope, $http, $httpParamSerializerJQLike){
  $scope.loadForm = function(){
    $scope.editUserForm = {
      'email' : '',
      'first_name' : '',
      'last_name' : '',
      'age' : '',
      'phone' : '',
      'address' : '',   
    }

    $http.get('php/ajax_handlers.php?action=get_users&cuid=' + $rootScope.session.cuid)
    .then(function onSuccess(result){
      let user = result.data.data[0];
      console.log(result);
      if(user){      
        $scope.editUserForm.email = user.email;
        $scope.editUserForm.first_name = user.first_name;
        $scope.editUserForm.last_name = user.last_name;
        $scope.editUserForm.age = parseInt(user.age);
        $scope.editUserForm.phone = user.phone;
        $scope.editUserForm.address = user.address;
        $scope.validate();
      } else {
        alert('Oops, there was a problem getting your data.');
        $scope.validate();
      }
    }, function onError(result){
      alert('There was an error retrieving user data.');
      console.log(result);
      $scope.validate();
    });
  }

  $scope.validate = function(){
    if($scope.editUserForm){
      $scope.valid = true;
    } else {
      $scope.valid = false;
    }
  }

  $scope.submitForm = function(){
    $http({
      url: 'php/ajax_handlers.php?action=edit_user',
      method: 'POST',
      data: $httpParamSerializerJQLike({
        "cuid" : $rootScope.session.cuid,
        "email" : $scope.editUserForm.email,
        "first_name" : $scope.editUserForm.first_name,
        "last_name" : $scope.editUserForm.last_name,
        "age" : $scope.editUserForm.age,
        "phone" : $scope.editUserForm.phone,
        "address" : $scope.editUserForm.address
      }),
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
    });
  }

  $scope.loadForm(); // also initializes the form
})