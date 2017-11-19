
angular.module('instRent.home', ['ngRoute'])

.config(['$routeProvider', function($routeProvider){
  $routeProvider.when('/home', {
    templateUrl: 'views/home/home.html',
    controller: 'HomeCtrl',
  });
}])

.controller('HomeCtrl', function HomeCtrl($scope, sessionLoader){
  /*
  sessionLoader.getSession().then(function(result){
    $scope.session = result;
    $scope.message = "Hello, " + $scope.session.username;  
  })
  */

  $scope.homeSession = 'homeSession';
  console.log('At end of HomeCtrl');
  console.log($scope);

})