(function() {
    'use strict';

    angular
  		.module('EpydApp', [
        'angular-ladda',
        'MessageCenterModule'
      ]);

})();

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

(function() {
    'use strict';

	angular
		.module('EpydApp')
		.factory('EpydService', ['VideoFactory', '$http', function (VideoFactory, $http){

      var videos = [];

			var EpydService = {
				videos: videos,
				next: null,
        prev: null,
				getVideo: getVideo,
        getVideosByPlaylist: getVideosByPlaylist,
        getDownloadVideosToken: getDownloadVideosToken
			};

			return EpydService;

			function getVideo(id){

				return $http({
					method : 'GET',
					url    : 'api/video/' + id
				})
				.success(function(data){
					var videos = [];
					videos.push(new VideoFactory(data.video));
					EpydService.videos = videos;
          EpydService.next = null;
          EpydService.prev = null;
				});

			};

      function getVideosByPlaylist(id, token){

        return $http({
          method : 'GET',
          url    : 'api/playlist/' + id + (typeof token != 'undefined' ? '/' + token:'')
        })
        .success(function(data){
          if(typeof token == 'undefined'){
            var videos = [];
          } else{
            var videos = EpydService.videos;
          }

          angular.forEach(data.videos, function(video){
            videos.push(new VideoFactory(video));
          });

          EpydService.videos = videos;
          EpydService.next = data.info.next;
          EpydService.prev = data.info.prev;
        });

      };

      function getDownloadVideosToken(videos){

        var data = {videos:{}};
        angular.forEach(videos, function(video){
          data.videos[video.id] = {
            artist : video.id3.artist,
            title  : video.id3.title
          };
        });

        return $http({
          method : 'POST',
          url    : 'api/download/videos',
          data   : data
        });

      };

		}]);

})();

(function() {
    'use strict';

	angular
		.module('EpydApp')
		.factory('VideoFactory', [function(){

			var videoFactory = function(data){

				// Construct
				angular.forEach(data, function(value, key){
					this[key] = value;
				}, this);

				this.selected = false;
				this.loadingDownload = false;

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

		}]);

})();
