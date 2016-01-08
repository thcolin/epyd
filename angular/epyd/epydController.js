(function() {
    'use strict';

	angular
		.module('EpydApp')
    .controller('EpydController', ['EpydService', 'VideoFactory', 'messageCenterService', '$scope', '$window', function(EpydService, VideoFactory, messageCenterService, $scope, $window){
      $scope.youtubeURL = '';
      $scope.videos = {};
      $scope.loadingAnalyse = false;
      $scope.loadingNext = false;
      $scope.loadingDownload = false;
      $scope.loadingDownloadAll = false;

      $scope.getVideos = function(youtubeURL, next){
        var id = null;
        if(!$scope.isVideo(youtubeURL) && !$scope.isPlaylist(youtubeURL)){
          messageCenterService.add('danger', '<i class="fa fa-warning"></i>Le lien fourni n\'est ni une playlist, ni une vidéo', {html:true});
        } else{
          if(typeof next == 'undefined'){
            $scope.loadingAnalyse = true;
          } else{
            $scope.loadingNext = true;
          }

          if(id = $scope.isVideo(youtubeURL)){
            var p = EpydService.getVideo(id);
          } else if(id = $scope.isPlaylist(youtubeURL)){
            var p = EpydService.getVideosByPlaylist(id, next);
          }

          p.finally(function(){
            if(typeof next == 'undefined'){
              $scope.loadingAnalyse = false;
            } else{
              $scope.loadingNext = false;
            }
            $scope.videos = EpydService.videos;
            $scope.next = EpydService.next;
          });
        }
      };

      $scope.downloadVideoLink = function(youtubeURL){
        var id = null;
        if(id = $scope.isVideo(youtubeURL)){
          var video = new VideoFactory({id:id, id3:{artist:null, title:null}});
          messageCenterService.add('info', '<i class="fa fa-quote-left"></i>Traitement de votre demande en cours..', {html:true});
          $scope.loadingDownload = true;
          EpydService.getDownloadVideosToken([video])
            .success(function(data){
              $window.location = 'api/download/token/' + data.token;
            })
            .finally(function(){
              $scope.loadingDownload = false;
            });
        } else{
          messageCenterService.add('danger', '<i class="fa fa-warning"></i>Le lien fourni n\'est ni une playlist, ni une vidéo', {html:true});
        }
      };

      $scope.downloadSelectedVideos = function(){
        var videos = [];
        angular.forEach($scope.videos, function(video){
          if(video.isSelected()){
            videos.push(video);
          }
        });
        if(videos.length){
          messageCenterService.add('info', '<i class="fa fa-quote-left"></i>Traitement de votre demande en cours..', {html:true});
          $scope.loadingDownloadAll = true;
        } else{
          messageCenterService.add('danger', '<i class="fa fa-warning"></i>Vous n\'avez séléctionné aucune vidéo', {html:true});
        }
        EpydService.getDownloadVideosToken(videos)
          .success(function(data){
            $window.location = 'api/download/token/' + data.token;
          })
          .finally(function(){
            $scope.loadingDownloadAll = false;
          });
      };

      $scope.downloadVideo = function(video){
        messageCenterService.add('info', '<i class="fa fa-quote-left"></i>Traitement de votre demande en cours..', {html:true});
        video.loadingDownload = true;
        EpydService.getDownloadVideosToken([video])
          .success(function(data){
            $window.location = 'api/download/token/' + data.token;
          })
          .finally(function(){
            video.loadingDownload = false;
          });
      };

      /* Select All */
      $scope.toggleSelectAll = function(){
        var bool = !$scope.isAllSelected();
        angular.forEach($scope.videos, function(video){
          video.setSelected(bool);
        });
      };

      $scope.isAllSelected = function(){
        var bool = ($scope.videos.length ? 1:0);
        angular.forEach($scope.videos, function(video){
          bool *= video.isSelected();
        });
        return bool;
      };

			/* Regex checker (Video & Playlist) */
			$scope.isVideo = function(url){
				var r = /https?:\/\/(www\.|m\.)?(youtube\.com|youtu\.be)\/(watch\?v=)?([^&]+)/i;
				if(r.test(url) && !$scope.isPlaylist(url)){
					return url.match(r)[4];
        } else{
          return false;
        }
			};

			$scope.isPlaylist = function(url){
				var r = /https?:\/\/(www\.|m\.)?(youtube\.com|youtu\.be)\/(watch\?v=[^&]+&list=([^&]+)|playlist\?list=([^&]+))/i;
				if(r.test(url)){
          return url.match(r)[5];
        } else{
          return false;
        }
			};

    }]);

})();
