(function() {
    'use strict';

	angular
		.module('EpydApp')
		.service('MessageService', function(){
			
			this.messages = [];
			
			this.addError = function(text){
				
				this.messages.push({
					style : 'alert-danger',
					icon : 'fa-warning',
					text : text
				});
				
			};
			
			this.addMessage = function(text){
				
				this.messages.push({
					style : 'alert-info',
					icon : 'fa-quote-left',
					text : text
				});
				
			};
			
			this.addSuccess = function(text){
				
				this.messages.push({
					style : 'alert-success',
					icon : 'fa-thumbs-up',
					text : text
				});
				
			};
			
		});
		
})();