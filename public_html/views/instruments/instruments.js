// name this module so that that angular can load it, and define its dependencies
angular.module('instRent.instruments', ['ngRoute'])

// configure the route provider, specifying the controller and template
// to be used for the provided route
.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/instruments', {
    templateUrl: 'views/instruments/instruments.html',
    controller: 'InstrumentsCtrl',
  });
}])

// define the 'InstrumentsCtrl' controller
.controller('InstrumentsCtrl', function InstrumentsCtrl($scope, $rootScope, $routeParams, $http, $httpParamSerializerJQLike, $location){
  // all properties of $scope are available to the template html page
  $scope.params = $routeParams; // the url parameters 
  $scope.instruments = []; // the instruments loaded from the database
  $scope.instrumentTypes = ["All"]; // instrument types in the database
  $scope.instrumentConditions = ["All"]; //instrument conditions in the database

  // The properties on $scope.filterForm are bound to form input elements in the template. 
  // Changes there are reflected here
  $scope.filterForm = {
    "search" : "",
    "type" : "All",
    "cond" : "All"
  };

  // Session data is stored in the root scope
  // Filtering options for 'available' and 'checked out' instruments are 
  // only available to managers 
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

  // define a function to get instruments from the database
  $scope.getInstruments = function() {
    // build the query string parameters based on form values
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

    // make an http GET request
    $http.get('php/ajax_handlers.php?' + qsparams)
    // define a function to be executed on success (response code == 200)
    .then(function onSuccess(result){
      console.log(result.data);
      // set the instruments property to the data returned by the php file
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
    // define a function to be called on error
    }, function onError(result){
      $scope.instruments = [];
      console.log('failed to get instruments')
      console.log(result);
    });
  }

  // gets the types of instruments currently in the database
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

  // gets the conditions of instruments currently in the database
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

  // set the route to the edit instrument form
  $scope.editInstrument = function(serial_no){
    $location.url('editInstrument?serial_no=' + serial_no);
  }

  // set the route to the checkout form, providing instrument information in the url
  $scope.checkOutInstrument = function(serial_no, type, cond){
    console.log("checking out: " + serial_no);
    $location.url('makeRequest?serial_no=' + serial_no + 
      '&cond=' + cond + '&type=' + type);
  }

  // make a request to delete an instrument from the database
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

  // finally, load the page by getting the instruments, the types, and the conditions
  $scope.getInstruments();
  $scope.getInstrumentTypes();
  $scope.getInstrumentConditions();
});