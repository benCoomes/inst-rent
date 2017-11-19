angular.module('instRent.instruments', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/instruments', {
    templateUrl: 'views/instruments/instruments.html',
    controller: 'InstrumentsCtrl',
  });
}])

.controller('InstrumentsCtrl', function InstrumentsCtrl($scope, $rootScope, $routeParams){
  $scope.params = $routeParams;
  $scope.message = "This is the instruments page";
  if($scope.params["status"]){
    $scope.message = $scope.message + ": Filter by status = " + $scope.params["status"];
  }
})