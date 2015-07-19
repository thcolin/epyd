(function() {
    'use strict';

	angular
		.module('EpydApp')
		.service('EpydService', ['$http', function($http){
			
			this.getVideo = function(id){
			
				return $http({
					method : 'GET',
					url    : 'api/video/' + id
				});
				
			};
			
			this.getPlaylist = function(id, token){
				
				return $http({
					method : 'GET',
					url    : 'api/playlist/' + id + (token ? '/' + token:'')
				});
				
			};
			
			this.downloadVideoByID = function(id){
				
				return $http({
					method : 'POST',
					url    : 'api/download/video/' + id
				});
				
			};
			
			this.downloadVideoByObject = function(video){
				
				return $http({
					method : 'POST',
					url    : 'api/download/video',
					data   : {
						video : video
					}
				});
				
			};
			
			this.downloadVideosByObject = function(videos){
				
				return $http({
					method : 'POST',
					url    : 'api/download/videos',
					data   : {
						videos : videos
					}
				});
				
			};
			
		}]);
		
})();