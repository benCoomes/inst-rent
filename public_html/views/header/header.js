angular.module('instRent.header', [])

.controller('HeaderCtrl', function HeaderCtrl($scope, sessionLoader, $location, $http, $httpParamSerializerJQLike){
  /*
  sessionLoader.getSession().then(function(result){
    $scope.session = result;
  })
  */
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
      $scope.session = {};
      $location.url('login');
    }, function onError(result){
      // do things with result on error
      alert("Oops, we couldn't sign you out!");
    })
  };

  console.log('At end of header Ctrl');
  console.log($scope);
  
})