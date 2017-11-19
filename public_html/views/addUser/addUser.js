angular.module('instRent.addUser', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/addUser', {
    templateUrl: 'views/addUser/addUser.html',
    controller: 'AddUserCtrl',
  });
}])

.controller('AddUserCtrl', function AddUserCtrl($scope, $rootScope, $routeParams){
  $scope.params = $routeParams;
  $scope.message = "This is the add user page";
})