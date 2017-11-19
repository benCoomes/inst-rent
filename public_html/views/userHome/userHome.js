angular.module('instRent.userHome', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/userHome', {
    templateUrl: 'views/userHome/userHome.html',
    controller: 'UserHomeCtrl',
  });
}])

.controller('UserHomeCtrl', function UserHomeCtrl($scope, $rootScope){
  $scope.message = "This is the user home page";
})