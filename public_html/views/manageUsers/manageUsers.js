angular.module('instRent.manageUsers', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/manageUsers', {
    templateUrl: 'views/manageUsers/manageUsers.html',
    controller: 'ManageUsersCtrl',
  });
}])

.controller('ManageUsersCtrl', function ManageUsersCtrl($scope, $rootScope, $http, $httpParamSerializerJQLike, $location){
  $scope.getUsers = function(){
    let qsparams = 'action=get_users';
    //TODO: encode http chars in search before appending
    if($scope.filterForm.search){
      qsparams = qsparams + '&search=' + $scope.filterForm.search;
    }
    if($scope.filterForm.hasOwnProperty('show_users') && $scope.filterForm.show_users == false){
      qsparams = qsparams + '&show_users=false';
    }
    if($scope.filterForm.hasOwnProperty('show_managers') && $scope.filterForm.show_managers == false){
      qsparams = qsparams + '&show_managers=false';
    }
    if($scope.filterForm.hasOwnProperty('show_admins') && $scope.filterForm.show_admins == false){
      qsparams = qsparams + '&show_admins=false';
    }
    console.log('qsparams: ' + qsparams);
    $http.get('php/ajax_handlers.php?' + qsparams)
    .then(function onSuccess(result){
      $scope.users = result.data.data;
    }, function onError(result){
      $scope.users = [];
      console.log('failed to get users');
      console.log(result);
    })
  }

  $scope.editUser = function(cuid){
    $location.url('editUser?cuid=' + cuid)
  }

  $scope.deleteUser = function(cuid){
    if($rootScope.session.role == 'admin'  && $rootScope.session.cuid != cuid){
      $http({
        url: 'php/ajax_handlers.php?action=delete_user',
        method: 'POST',
        data: $httpParamSerializerJQLike({'cuid' : cuid}),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.resetForm();
        console.log(result.data);
        alert("Successfully deleted user.")
      }, function onError(result){
        alert("Failed to delete user.")
        console.log(result);
      })
    } else {
      if($rootScope.session.role != 'admin'){
        alert('You do not have permission to perform this action.');
      } else {
        alert('You cannot delete youself.')
      }
    }
  }

  $scope.resetForm = function(){
    $scope.filterForm = {
      'search' : '',
      'show_users' : true,
      'show_managers': true,
      'show_admins' : true
    };

    $scope.getUsers();
  }

  $scope.resetForm(); // sets default form values and then gets data
})