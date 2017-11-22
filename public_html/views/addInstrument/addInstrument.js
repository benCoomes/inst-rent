angular.module('instRent.addInstrument', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/addInstrument', {
    templateUrl: 'views/addInstrument/addInstrument.html',
    controller: 'AddInstrumentCtrl',
  });
}])

.controller('AddInstrumentCtrl', function AddInstrumentCtrl($scope, $rootScope, $http, $httpParamSerializerJQLike){
  $scope.resetForm = function(){
    $scope.addInstForm = {
      'serialNo' : '',
      'type' : '',
      'condition' : ''     
    }
    $scope.validate();
  }

  $scope.validate = function(){
    if($scope.addInstForm && 
        $scope.addInstForm.serialNo && 
        $scope.addInstForm.type && 
        $scope.addInstForm.condition){
      $scope.valid = true;
    } else {
      $scope.valid = false;
    }
  }

  $scope.submitForm = function(){
    if($rootScope.session.role = 'manager'){
      $http({
        url: 'php/ajax_handlers_cst.php?action=add_instrument',
        method: 'POST',
        data: $httpParamSerializerJQLike($scope.signInForm),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.resetForm();
        console.log(result.data);
        alert("Successfully added instrument.")
      }, function onError(result){
        alert("Failed to add instrument.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }

 
  $scope.resetForm(); // also initializes the form
})