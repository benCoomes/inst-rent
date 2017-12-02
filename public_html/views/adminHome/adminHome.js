angular.module('instRent.adminHome', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/adminHome', {
    templateUrl: 'views/adminHome/adminHome.html',
    controller: 'AdminHomeCtrl',
  });
}])

.controller('AdminHomeCtrl', function AdminHomeCtrl($scope, $rootScope, $http, $httpParamSerializerJQLike, $location){
  $scope.message = "This is the admin home page";

  $scope.backupDatabase = function(){
    $http.get('php/ajax_handlers.php?action=backup_database')
    .then(function onSuccess(result){
      alert("Successfully created backup.");
      console.log(result.data);
    }, function onError(result){
      alert("Failed to backup database.");
      console.log(result)
    });
  }

  $scope.backupExists = function(){
    $http.get('php/ajax_handlers.php?action=backup_exists')
    .then(function onSuccess(result){
      $scope.canRestore = result.data.data;
      console.log(result.data);
    }, function onError(result){
      alert("Couldn't determine if backup exists.");
      console.log(result)
    });
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
      $rootScope.session = {};
      $location.url('login');
    }, function onError(result){
      // do things with result on error
      alert("Oops, we couldn't sign you out!");
    })
  };

  $scope.restoreDatabase = function(){
    $http.get('php/ajax_handlers.php?action=restore_database')
    .then(function onSuccess(result){
      alert("Successfully restored database.");
      console.log(result.data);
      $scope.signOut();
    }, function onError(result){
      alert("Failed to restore database. - Maybe no backup exists.");
      console.log(result)
    });
  }

  $scope.backupExists();
})