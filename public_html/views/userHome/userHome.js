angular.module('instRent.userHome', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/userHome', {
    templateUrl: 'views/userHome/userHome.html',
    controller: 'UserHomeCtrl',
  });
}])

.controller('UserHomeCtrl', function UserHomeCtrl($scope, $rootScope, $location, $http, $httpParamSerializerJQLike){
  $scope.getContracts = function(){
    $http.get('php/ajax_handlers.php?action=get_contracts&cuid=' + $rootScope.session.cuid)
    .then(function onSuccess(result){
      $scope.contracts = result.data.data;
      console.log("Got contracts for user: " + $rootScope.session.cuid)
      console.log($scope.contracts);
    }, function onError(result){
      console.log('failed to get contracts');
      console.log(result);
    })
  }

  $scope.editProfile = function(){
    $location.url('editProfile');
  }

  $scope.deletePendingContract = function(serial_no){
    console.log('deleting request: ' + serial_no);
    $http({
      url: 'php/ajax_handlers_cst.php?action=deny_request',
      method: 'POST',
      data: $httpParamSerializerJQLike({'cuid' : $rootScope.session.cuid, 'serial_no' : serial_no}),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    })
    .then(function onSuccess(result){
      $scope.getContracts();
      console.log(result.data);
      alert("Successfully canceled contract.")
    }, function onError(result){
      alert("Failed to cancel contract.")
      console.log(result);
    })
  }

  $scope.getContracts();

})