angular.module('instRent.instruments', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/instruments', {
    templateUrl: 'views/instruments/instruments.html',
    controller: 'InstrumentsCtrl',
  });
}])

.controller('InstrumentsCtrl', function InstrumentsCtrl($scope, $rootScope, $routeParams, $http, $httpParamSerializerJQLike, $location){
  $scope.params = $routeParams;
  $scope.instruments = [];
  $scope.instrumentTypes = ["All"];
  $scope.instrumentConditions = ["All"];

  $scope.filterForm = {
    "search" : "",
    "type" : "All",
    "cond" : "All"
  };

  if($rootScope.session.role == 'manager'){
    $scope.filterForm['available'] = true;
    $scope.filterForm['checkedOut'] = false;
    if($scope.params['available'] && $scope.params['available'] == false){
      $scope.filterForm['available'] = false;
    }
    if($scope.params['checkedOut'] && $scope.params['checkedOut'] == true){
      $scope.filterForm['checkedOut'] = true;
    }
  }

  $scope.getInstruments = function() {
    //TODO: encode http chars in search before appending
    let qsparams = 'action=get_instruments';
    if($scope.filterForm.type && $scope.filterForm.type != "All"){
      qsparams = qsparams + '&type=' + $scope.filterForm.type;
    }
    if($scope.filterForm.cond && $scope.filterForm.cond != "All"){
      qsparams = qsparams + '&cond=' + $scope.filterForm.cond;
    }
    if($scope.filterForm.search){
      qsparams = qsparams + '&search=' + $scope.filterForm.search;
    }
    if($scope.filterForm.hasOwnProperty('available') && $scope.filterForm.available == false){
      qsparams = qsparams + '&available=false';
    }
    if($scope.filterForm.hasOwnProperty('checkedOut') && $scope.filterForm.checkedOut == false){
      qsparams = qsparams + '&checkedout=false';
    }
    console.log('qsparams: ' +qsparams);

    $http.get('php/ajax_handlers.php?' + qsparams)
    .then(function onSuccess(result){
      console.log(result.data);
      $scope.instruments = result.data.data;
      // check to see if each instrument has a pending contract for the user
      if($rootScope.session.role == 'user'){
        $http.get('php/ajax_handlers.php?action=get_contracts&show_active=false&cuid=' + $rootScope.session.cuid)
        .then(function onSuccess(result){
          let contracts = result.data.data;
          console.log("Got pending contracts for user: " + $rootScope.session.cuid)
          for(inst of $scope.instruments){
            inst.hasPendingContract = false;
            for(contract of contracts){
              if(inst.serial_no == contract.serial_no){
                inst.hasPendingContract = true;
              }
            }
          }
        }, function onError(result){
          console.log('failed to get contracts');
          console.log(result);
        })
      }
    }, function onError(result){
      $scope.instruments = [];
      console.log('failed to get instruments')
      console.log(result);
    });
  }

  $scope.getInstrumentTypes = function(){
    let qsparams = 'action=get_instrument_types';
    $http.get('php/ajax_handlers.php?' + qsparams)
    .then(function onSuccess(result){
      $scope.instrumentTypes = result.data.data;
    }, function onError(result){
      console.log('failed to get instrument types');
      console.log(result);
    });
  }

  $scope.getInstrumentConditions = function(){
    let qsparams = 'action=get_instrument_conditions';
    $http.get('php/ajax_handlers.php?' + qsparams)
    .then(function onSuccess(result){
      $scope.instrumentConditions = result.data.data;
    }, function onError(result){
      console.log('failed to get instrument conditions');
      console.log(result);
    });
  }

  // include check in function on this page? create page for checkin/contract termination?
  /*
  $scope.checkInInstrument = function(serial_no){
    console.log("checking in: " + serial_no);
    //TODO: reroute to check in form, supplying serial_no to autofill form
  }
  */

  $scope.editInstrument = function(serial_no){
    $location.url('editInstrument?serial_no=' + serial_no);
  }

  $scope.checkOutInstrument = function(serial_no, type, cond){
    console.log("checking out: " + serial_no);
    $location.url('makeRequest?serial_no=' + serial_no + 
      '&cond=' + cond + '&type=' + type);
  }

  $scope.deleteInstrument = function(serial_no){
    if($rootScope.session.role == 'manager'){
      $http({
        url: 'php/ajax_handlers.php?action=delete_instrument',
        method: 'POST',
        data: $httpParamSerializerJQLike({'serial_no' : serial_no}),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      })
      .then(function onSuccess(result){
        $scope.getInstruments();
        console.log(result.data);
        alert("Successfully deleted instrument.")
      }, function onError(result){
        alert("Failed to delete instrument.")
        console.log(result);
      })
    } else {
      alert('You do not have permission to perform this action.');
    }
  }

  $scope.getInstruments();
  $scope.getInstrumentTypes();
  $scope.getInstrumentConditions();
});