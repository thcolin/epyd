(function() {
    'use strict';

	angular
		.module('EpydApp')
		.controller('EpydController', ['EpydService', 'MessageService', 'VideoFactory', '$scope', '$window', function(EpydService, MessageService, VideoFactory, $scope, $window){
			
			$scope.search = '';
			$scope.nextPage = '';
			$scope.videos = [];
			
			$scope.checkLoading = false;
			$scope.downloadLoading = false;
			$scope.checkNextLoading = false;

			/* Toggle "Select All" */
			
			$scope.isAllSelected = function(){
				
				if($scope.videos.length === 0)
				
					return 0;
				
				var toggle = 1;
			
				angular.forEach($scope.videos, function(video){
					
					toggle *= video.isSelected();
					
				});
				
				return toggle;
				
			};
			
			$scope.toggleAll = function(){
				
				var toggle = $scope.isAllSelected();
				
				angular.forEach($scope.videos, function(video){
					
					video.setSelected(!toggle);
					
				});
				
			};
			
			/* Search check */
				
			$scope.check = function(){
				
				var id = false;
				
				$scope.checkLoading = true;
				
				// Playlist
				
				if(id = $scope.isPlaylist($scope.search)){
					
					$scope.videos = [];
					$scope.nextPage = '';
					$scope.multi = true;
					
					EpydService.getPlaylist(id)
						.success(function(data){
						
							// Errors
								
							angular.forEach(data.errors, function(error){
								
								MessageService.addError(error.message);
								
							});
							
							$scope.nextPage = data.info.nextPageToken;
							
							angular.forEach(data.videos, function(video){
								
								$scope.videos.push(new VideoFactory(video));
								
							});
							
						})
						.error(function(data){
						
							// Errors
								
							angular.forEach(data.errors, function(error){
								
								MessageService.addError(error.message);
								
							});
							
						})
						.finally(function(){
							
							$scope.checkLoading = false;
							
						});
						
				}
				
				// Video
				
				else if(id = $scope.isVideo($scope.search)){
					
					$scope.videos = [];
					$scope.nextPage = '';
					$scope.multi = false;
					
					EpydService.getVideo(id)
						.success(function(data){
							
							$scope.videos.push(new VideoFactory(data.video));
							
						})
						.error(function(data){
						
							MessageService.addError(data.message);
							
						})
						.finally(function(){
							
							$scope.checkLoading = false;
							
						});
					
				}
					
				else{
				
					MessageService.addError("Ce lien n'est ni une vidéo, ni une playlist Youtube.");
					$scope.checkLoading = false;
					
				}
				
			};
			
			$scope.checkNextVideos = function(){
				
				var id = false;
				
				if($scope.nextPage != ''){
					
					if(id = $scope.isPlaylist($scope.search)){
						
						$scope.checkNextLoading = true;
					
						EpydService.getPlaylist(id, $scope.nextPage)
							.success(function(data){
							
								// Errors
									
								angular.forEach(data.errors, function(error){
									
									MessageService.addError(error.message);
									
								});
								
								$scope.nextPage = (data.info.nextPageToken ? data.info.nextPageToken:false);
								
								angular.forEach(data.videos, function(video){
									
									$scope.videos.push(new VideoFactory(video));
									
								});
								
							})
							.error(function(data){
							
								// Errors
									
								angular.forEach(data.errors, function(error){
									
									MessageService.addError(error.message);
									
								});
								
							})
							.finally(function(){
								
								$scope.checkNextLoading = false;
								
							});
						
					}
					
					else
					
						MessageService.addError("Ce lien n'est ni une vidéo, ni une playlist Youtube.");
					
				}
				
			};
			
			/* Download */
			
			$scope.downloadLink = function(link){
				
				var id = false;
				
				$scope.downloadLoading = true;
				
				if(id = $scope.isVideo(link)){
				
					MessageService.addMessage("Traitement de votre demande en cours..");
						
					EpydService.downloadVideoByID(id)
						.success(function(data){
							
							$window.location = 'api/download/token/' + data.token;
							$scope.downloadLoading = false;
							
						})
						.error(function(data){
							
							MessageService.addError(data.message);
							$scope.downloadLoading = false;
							
						});
					
				}
					
				else
				
					MessageService.addError("Ce lien n'est ni une vidéo, ni une playlist Youtube.");
				
			};
			
			$scope.downloadVideo = function(video){
				
				MessageService.addMessage("Traitement de votre demande en cours..");
				video.loading = true;
					
				EpydService.downloadVideoByObject(video)
					.success(function(data){
						
						$window.location = 'api/download/token/' + data.token;
						video.loading = false;
						
					})
					.error(function(data){
						
						MessageService.addError(data.message);
						video.loading = false;
						
					});
				
			};
			
			$scope.downloadSelectedVideos = function(){
				
				$scope.downloadAllLoading = true;
				
				var videos = [];
				
				angular.forEach($scope.videos, function(video){
					
					if(video.isSelected())
					
						videos.push(video);
					
				});
				
				MessageService.addMessage("Traitement de votre demande en cours..");
					
				EpydService.downloadVideosByObject(videos)
					.success(function(data){
						
						// Errors
							
						angular.forEach(data.errors, function(error){
							
							MessageService.addError(error.message);
							
						});
						
						$scope.downloadAllLoading = false;
						
						$window.location = 'api/download/token/' + data.token;
						
					})
					.error(function(data){
						
						// Errors
							
						angular.forEach(data.errors, function(error){
							
							MessageService.addError(error.message);
							
						});
						
						$scope.downloadAllLoading = true;
						
					});
				
			};
			
			/* Regex checker (Video & Playlist) */
			
			$scope.isVideo = function(search){
				
				var rVideo = /https?:\/\/(www\.)?(youtube\.com|youtu\.be)\/(watch\?v=)?([^&]+)/i;
				
				if(rVideo.test(search) && !$scope.isPlaylist(search))
				
					return search.match(rVideo)[4];
					
				else
				
					return false;
				
			};
			
			$scope.isPlaylist = function(search){
				
				var rPlaylist = /https?:\/\/(www\.)?(youtube\.com|youtu\.be)\/(watch\?v=[^&]+&list=([^&]+)|playlist\?list=([^&]+))/i;
				
				if(rPlaylist.test(search))
				
					return search.match(rPlaylist)[5];
					
				else
				
					return false;
				
			};
			
		}]);
		
})();