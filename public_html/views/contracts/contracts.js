angular.module('instRent.contracts', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/contracts', {
    templateUrl: 'views/contracts/contracts.html',
    controller: 'ContractsCtrl',
  });
}])

.controller('ContractsCtrl', function ContractsCtrl($scope, $rootScope, $routeParams){
  $scope.params = $routeParams;
  $scope.message = "This is the contracts page";
  if($scope.params["status"]){
    $scope.message = $scope.message + ": Filter by status = " + $scope.params["status"];
  }
})