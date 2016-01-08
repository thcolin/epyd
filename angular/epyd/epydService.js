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
