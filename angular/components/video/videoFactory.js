(function() {
    'use strict';

	angular
		.module('EpydApp')
		.factory('VideoFactory', function(){
			
			var videoFactory = function(values){
				
				// Construct
				
				angular.forEach(values, function(value, key){
					
					this[key] = value;
					
				}, this);
				
				this.selected = false;
				this.loading = false;
				
				this.setSelected = function(bool){
					
					this.selected = (bool ? true:false);
					
				};
				
				this.toggleSelected = function(){
					
					this.selected = !this.selected;
					
				};
				
				this.isSelected = function(){
				
					return this.selected;	
					
				};
				
				this.getLink = function(){
					
					return 'https://www.youtube.com/watch?v=' + this.id;
					
				};
				
				this.getChannelLink = function(){
					
					return 'https://www.youtube.com/user/' + this.snippet.channelTitle;
					
				};
				
			};
			
			return videoFactory;
			
		});
		
})();