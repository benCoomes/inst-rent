'use strict';

// Declare app level module which depends on views, and components
var app = angular.module('inst_rent', ['ngRoute'])
  .config(function($routeProvider){
    $routeProvider
      .when("/", {
        templateUrl : "templates/home.html"
      })
      .when("/home", {
        templateUrl : "templates/home.html"
      });;
  });

app.controller('mainCtrl', function mainCtrl($scope){
   // main ctrl variables here
})

