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
    if($scope.filterForm.hasOwnProperty('showUsers') && $scope.filterForm.showUsers == false){
      qsparams = qsparams + '&showUsers=false';
    }
    if($scope.filterForm.hasOwnProperty('showManagers') && $scope.filterForm.showManagers == false){
      qsparams = qsparams + '&showManagers=false';
    }
    if($scope.filterForm.hasOwnProperty('showAdmins') && $scope.filterForm.showAdmins == false){
      qsparams = qsparams + '&showAdmins=false';
    }
    console.log('qsparams: ' + qsparams);
    $http.get('php/ajax_handlers_cst.php?' + qsparams)
    .then(function onSuccess(result){
      $scope.users = result.data.data;
    }, function onError(result){
      $scope.users = [];
      console.log('failed to get users');
      console.log(result);
    })
  }

  $scope.editUser = function(cuid){
    //todo
    $location.url('editUser?cuid=' + cuid)
  }

  $scope.deleteUser = function(cuid){
    if($rootScope.session.role == 'admin'  && $rootScope.session.cuid != cuid){
      $http({
        url: 'php/ajax_handlers_cst.php?action=delete_user',
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
      'showUsers' : true,
      'showManagers': true,
      'showAdmins' : true
    };

    $scope.getUsers();
  }

  $scope.resetForm(); // sets default form values and then gets data
})