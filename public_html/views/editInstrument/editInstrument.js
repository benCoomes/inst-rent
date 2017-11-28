angular.module('instRent.editInstrument', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/editInstrument', {
    templateUrl: 'views/editInstrument/editInstrument.html',
    controller: 'editInstrumentCtrl',
  });
}])

.controller('editInstrumentCtrl', function editInstrumentCtrl($scope, $rootScope, $routeParams, $http, $httpParamSerializerJQLike){
  $scope.loadForm = function(){
    $scope.editInstForm = {
      'serial_no' : '',
      'cond' : '',  
      'type' : ''
    }

    if($scope.params.serial_no){
      $http.get('php/ajax_handlers.php?action=get_instruments&serial_no=' + $scope.params.serial_no)
      .then(function onSuccess(result){
        let inst = result.data.data[0];
        console.log(result);
        if(inst){      
          $scope.editInstForm.serial_no = inst.serial_no;
          $scope.editInstForm.cond = inst.cond;
          $scope.editInstForm.type = inst.type;
          $scope.disableType = true;
          $scope.disableSerialNo = true;
          $scope.validate();
        } else {
          $scope.disableType = false;
          $scope.disableSerialNo = false;
          $scope.validate();
        }
      }, function onError(result){
        alert('There was an error retrieving instrument data.');
        console.log(result);
        $scope.validate();
      });
    } else {
      $scope.disableType = false;
      $scope.disableSerialNo = false;
      $scope.validate();
    }
  }

  $scope.validate = function(){
    if($scope.editInstForm &&
        $scope.editInstForm.serial_no && 
        $scope.editInstForm.cond){
      $scope.valid = true;
    } else {
      $scope.valid = false;
    }
  }

  $scope.submitForm = function(){
    if($rootScope.session.role == 'manager'){
      $http({
        url: 'php/ajax_handlers_cst.php?action=edit_instrument',
        method: 'POST',
        data: $httpParamSerializerJQLike($scope.editInstForm),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.loadForm();
        console.log(result.data);
        alert("Successfully updated instrument.")
      }, function onError(result){
        alert("Failed to update instrument.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }
 
  $scope.params = $routeParams;
  $scope.loadForm(); // also initializes the form
})