(function() {
    'use strict';
    
    angular
		.module('EpydApp')
		.config(['laddaProvider', function(laddaProvider){
			
			laddaProvider.setOption({
				style: 'expand-left'
			});
			
		}]);
		
})();