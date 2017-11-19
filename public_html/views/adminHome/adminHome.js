angular.module('instRent.adminHome', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/adminHome', {
    templateUrl: 'views/adminHome/adminHome.html',
    controller: 'AdminHomeCtrl',
  });
}])

.controller('AdminHomeCtrl', function AdminHomeCtrl($scope, $rootScope){
  $scope.message = "This is the admin home page";
})