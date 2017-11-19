angular.module('instRent.managerHome', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/managerHome', {
    templateUrl: 'views/managerHome/managerHome.html',
    controller: 'ManagerHomeCtrl',
  });
}])

.controller('ManagerHomeCtrl', function ManagerHomeCtrl($scope, $rootScope, sessionLoader){
  $scope.message = "This is the manager home page";
})