angular.module('instRent.manageUsers', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/manageUsers', {
    templateUrl: 'views/manageUsers/manageUsers.html',
    controller: 'ManageUsersCtrl',
  });
}])

.controller('ManageUsersCtrl', function ManageUsersCtrl($scope, $rootScope, $routeParams){
  $scope.params = $routeParams;
  $scope.message = "This is the manage users page";
  if($scope.params["status"]){
    $scope.message = $scope.message + ": Filter by status = " + $scope.params["status"];
  }
})