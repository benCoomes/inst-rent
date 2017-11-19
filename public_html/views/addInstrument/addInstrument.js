angular.module('instRent.addInstrument', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/addInstrument', {
    templateUrl: 'views/addInstrument/addInstrument.html',
    controller: 'AddInstrumentCtrl',
  });
}])

.controller('AddInstrumentCtrl', function AddInstrumentCtrl($scope, $rootScope){
  $scope.message = "This is the add instrument page";
})