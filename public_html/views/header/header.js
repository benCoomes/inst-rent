angular.module('instRent.header', [])

.controller('HeaderCtrl', function HeaderCtrl($scope, $rootScope, sessionLoader, $location, $http, $httpParamSerializerJQLike){
  $scope.title = 'Clemson Instrument Rentals';

  $scope.brand = "CUIR";
  
  $scope.signOut = function() {
    $http({
      url:'php/ajax_handlers_cst.php?action=sign_out',
      method: 'POST',
      data: $httpParamSerializerJQLike([]),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      timeout: 2000
    })
    .then(function onSuccess(result){
      $rootScope.session = {};
      $location.url('login');
    }, function onError(result){
      // do things with result on error
      alert("Oops, we couldn't sign you out!");
    })
  };

})