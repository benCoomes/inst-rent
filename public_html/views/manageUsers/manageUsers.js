angular.module('instRent.manageUsers', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/manageUsers', {
    templateUrl: 'views/manageUsers/manageUsers.html',
    controller: 'ManageUsersCtrl',
  });
}])

.controller('ManageUsersCtrl', function ManageUsersCtrl($scope, $rootScope, $routeParams, $http){
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
    console.log('editing user: ' + cuid);
  }

  $scope.deleteUser = function(cuid){
    //todo
    console.log('deleting user: ' + cuid);
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

  $scope.params = $routeParams;
  if($scope.params["status"]){
    $scope.message = $scope.message + ": Filter by status = " + $scope.params["status"];
  }

  $scope.resetForm(); // sets default form values and then gets data
})