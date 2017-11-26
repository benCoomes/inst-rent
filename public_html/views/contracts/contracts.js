angular.module('instRent.contracts', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/contracts', {
    templateUrl: 'views/contracts/contracts.html',
    controller: 'ContractsCtrl',
  });
}])

.controller('ContractsCtrl', function ContractsCtrl($scope, $rootScope, $routeParams, $http, $httpParamSerializerJQLike){
  /*
  $scope.params = $routeParams;
  $scope.message = "This is the contracts page";
  if($scope.params["status"]){
    $scope.message = $scope.message + ": Filter by status = " + $scope.params["status"];
  }
  */

  $scope.getContracts = function(){
    qsparams = "action=get_contracts";
    if($scope.filterForm.search){
      qsparams = qsparams + '&search=' + $scope.filterForm.search;
    }
    if($scope.filterForm.hasOwnProperty('showActive') && $scope.filterForm.showActive == false){
      qsparams = qsparams + '&showActive=false';
    }
    if($scope.filterForm.hasOwnProperty('showPending') && $scope.filterForm.showPending == false){
      qsparams = qsparams + '&showPending=false';
    }
    console.log('qsparams: ' + qsparams);

    $http.get('php/ajax_handlers_cst.php?' + qsparams)
    .then(function onSuccess(result){
      $scope.contracts = result.data.data;
    }, function onError(result){
      $scope.contracts = [];
      console.log('failed to get contracts');
      console.log(result);
    })
  }

  $scope.approveRequest = function(cuid, serial_no){
    console.log("approving request: " + cuid + ", " + serial_no);
    if($rootScope.session.role == 'manager'){
      $http({
        url: 'php/ajax_handlers_cst.php?action=approve_request',
        method: 'POST',
        data: $httpParamSerializerJQLike({'cuid' : cuid, 'serial_no' : serial_no}),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.resetForm();
        console.log(result.data);
        alert("Successfully approved contract.")
      }, function onError(result){
        alert("Failed to approve contract.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }

  $scope.denyRequest = function(cuid, serial_no){
    console.log("denying request: " + cuid + ", " + serial_no);
    if($rootScope.session.role == 'manager'){
      $http({
        url: 'php/ajax_handlers_cst.php?action=deny_request',
        method: 'POST',
        data: $httpParamSerializerJQLike({'cuid' : cuid, 'serial_no' : serial_no}),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.resetForm();
        console.log(result.data);
        alert("Successfully denied contract.")
      }, function onError(result){
        alert("Failed to deny contract.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }

  $scope.endContract = function(serial_no){
    console.log("ending contract: " + serial_no);
    if($rootScope.session.role == 'manager'){
      $http({
        url: 'php/ajax_handlers_cst.php?action=approve_request',
        method: 'POST',
        data: $httpParamSerializerJQLike({'serial_no' : serial_no}),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.resetForm();
        console.log(result.data);
        alert("Successfully ended contract.")
      }, function onError(result){
        alert("Failed to end contract.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }

  $scope.resetForm = function(){
    $scope.filterForm = {
      'search' : '',
      'showActive' : true,
      'showPending' : true
    }
    $scope.getContracts();
  }

  $scope.resetForm(); // sets default form values then gets data
})