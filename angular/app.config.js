(function() {
    'use strict';

    angular
  		.module('EpydApp')
  		.config(['laddaProvider', '$httpProvider', function(laddaProvider, $httpProvider){
  			laddaProvider.setOption({
  				style: 'expand-left'
  			});

        $httpProvider.interceptors.push(['$q', 'messageCenterService', function($q, messageCenterService){
          return {
            response : function(res){
              if(typeof res.data.errors != 'undefined' && res.data.errors.length > 0){
                angular.forEach(res.data.errors, function(error){
                  messageCenterService.add('danger', '<i class="fa fa-warning"></i>' + error.error, {html:true});
                });
              }
              return res;
            },
            responseError: function(res){
              if(typeof res.data.error != 'undefined'){
                messageCenterService.add('danger', '<i class="fa fa-warning"></i>' + res.data.error, {html:true});
              }
              return $q.reject(res);
            }
          };
        }]);
  		}]);

})();
