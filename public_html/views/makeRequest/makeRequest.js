angular.module('instRent.makeRequest', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/makeRequest', {
    templateUrl: 'views/makeRequest/makeRequest.html',
    controller: 'makeRequestCtrl',
  });
}])

.controller('makeRequestCtrl', function makeRequestCtrl($scope, $rootScope, $routeParams, $http, $httpParamSerializerJQLike){
  $scope.loadForm = function(){
    $scope.instReqForm = {
      "serial_no" : $scope.params.serial_no,
      "cond" : $scope.params.cond,
      "type" : $scope.params.type,
      "start" : null,
      "end" : null
    }
    $scope.validate();
  }

  $scope.validate = function(){
    console.log($scope.instReqForm)
    if($scope.instReqForm.serial_no &&
      $scope.instReqForm.start &&
      $scope.instReqForm.end &&
      $scope.instReqForm.start < $scope.instReqForm.end){
      $scope.valid = true;
    } else {
      $scope.valid = false;
    }
  }

  $scope.submitForm = function(){
    if($rootScope.session.role == 'user'){
      $http({
        url: 'php/ajax_handlers_cst.php?action=make_request',
        method: 'POST',
        data: $httpParamSerializerJQLike({
          "cuid" : $rootScope.session.cuid,
          "serial_no" : $scope.instReqForm.serial_no,
          "start" : $scope.instReqForm.start,
          "end" : $scope.instReqForm.end
        }),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        console.log(result.data);
        alert("Successfully made request.");
        //TODO: go to user home here? 
      }, function onError(result){
        alert("Failed to make request.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }
 
  $scope.params = $routeParams;
  $scope.loadForm();
})