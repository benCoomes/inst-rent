angular.module('instRent.instruments', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/instruments', {
    templateUrl: 'views/instruments/instruments.html',
    controller: 'InstrumentsCtrl',
  });
}])

.controller('InstrumentsCtrl', function InstrumentsCtrl($scope, $rootScope, $routeParams, $http){
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
    if($scope.filterForm.checkedOut){
      qsparams = qsparams + '&checkedout=true';
    }
    console.log('qsparams: ' +qsparams);

    $http.get('php/ajax_handlers_cst.php?' + qsparams)
    .then(function onSuccess(result){
      $scope.instruments = result.data.data;
    }, function onError(result){
      $scope.instruments = [];
      console.log('failed to get instruments')
      console.log(result);
    });
  }

  $scope.getInstrumentTypes = function(){
    let qsparams = 'action=get_instrument_types';
    $http.get('php/ajax_handlers_cst.php?' + qsparams)
    .then(function onSuccess(result){
      $scope.instrumentTypes = result.data.data;
    }, function onError(result){
      console.log('failed to get instrument types');
      console.log(result);
    });
  }

  $scope.getInstrumentConditions = function(){
    let qsparams = 'action=get_instrument_conditions';
    $http.get('php/ajax_handlers_cst.php?' + qsparams)
    .then(function onSuccess(result){
      $scope.instrumentConditions = result.data.data;
    }, function onError(result){
      console.log('failed to get instrument conditions');
      console.log(result);
    });
  }

  $scope.checkInInstrument = function(serial_no){
    console.log("checking in: " + serial_no);
    //TODO: reroute to check in form, supplying serial_no to autofill form
  }

  $scope.checkOutInstrument = function(serial_no){
    console.log("checking out: " + serial_no);
    //TODO: reroute to check out form, supplying serial_no to autofill form
  }

  $scope.deleteInstrument = function(serial_no){
    //TODO: delete instrument. Managers only.
  }

  $scope.getInstruments();
  $scope.getInstrumentTypes();
  $scope.getInstrumentConditions();
});