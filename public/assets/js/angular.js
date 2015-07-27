!function(){"use strict";angular.module("EpydApp",["angular-ladda"])}(),function(){"use strict";angular.module("EpydApp").config(["laddaProvider",function(e){e.setOption({style:"expand-left"})}])}(),function(){"use strict";angular.module("EpydApp").controller("EpydController",["EpydService","MessageService","VideoFactory","$scope","$window",function(e,n,o,t,i){t.search="",t.nextPage="",t.videos=[],t.checkLoading=!1,t.downloadLoading=!1,t.checkNextLoading=!1,t.isAllSelected=function(){if(0===t.videos.length)return 0;var e=1;return angular.forEach(t.videos,function(n){e*=n.isSelected()}),e},t.toggleAll=function(){var e=t.isAllSelected();angular.forEach(t.videos,function(n){n.setSelected(!e)})},t.check=function(){var i=!1;t.checkLoading=!0,(i=t.isPlaylist(t.search))?(t.videos=[],t.nextPage="",t.multi=!0,e.getPlaylist(i).success(function(e){angular.forEach(e.errors,function(e){n.addError(e.message)}),t.nextPage=e.info.nextPageToken,angular.forEach(e.videos,function(e){t.videos.push(new o(e))})}).error(function(e){angular.forEach(e.errors,function(e){n.addError(e.message)})})["finally"](function(){t.checkLoading=!1})):(i=t.isVideo(t.search))?(t.videos=[],t.nextPage="",t.multi=!1,e.getVideo(i).success(function(e){t.videos.push(new o(e.video))}).error(function(e){n.addError(e.message)})["finally"](function(){t.checkLoading=!1})):(n.addError("Ce lien n'est ni une vidéo, ni une playlist Youtube."),t.checkLoading=!1)},t.checkNextVideos=function(){var i=!1;""!=t.nextPage&&((i=t.isPlaylist(t.search))?(t.checkNextLoading=!0,e.getPlaylist(i,t.nextPage).success(function(e){angular.forEach(e.errors,function(e){n.addError(e.message)}),t.nextPage=e.info.nextPageToken?e.info.nextPageToken:!1,angular.forEach(e.videos,function(e){t.videos.push(new o(e))})}).error(function(e){angular.forEach(e.errors,function(e){n.addError(e.message)})})["finally"](function(){t.checkNextLoading=!1})):n.addError("Ce lien n'est ni une vidéo, ni une playlist Youtube."))},t.downloadLink=function(o){var a=!1;t.downloadLoading=!0,(a=t.isVideo(o))?(n.addMessage("Traitement de votre demande en cours.."),e.downloadVideoByID(a).success(function(e){i.location="api/download/token/"+e.token,t.downloadLoading=!1}).error(function(e){n.addError(e.message),t.downloadLoading=!1})):n.addError("Ce lien n'est ni une vidéo, ni une playlist Youtube.")},t.downloadVideo=function(o){n.addMessage("Traitement de votre demande en cours.."),o.loading=!0,e.downloadVideoByObject(o).success(function(e){i.location="api/download/token/"+e.token,o.loading=!1}).error(function(e){n.addError(e.message),o.loading=!1})},t.downloadSelectedVideos=function(){t.downloadAllLoading=!0;var o=[];angular.forEach(t.videos,function(e){e.isSelected()&&o.push(e)}),n.addMessage("Traitement de votre demande en cours.."),e.downloadVideosByObject(o).success(function(e){angular.forEach(e.errors,function(e){n.addError(e.message)}),t.downloadAllLoading=!1,i.location="api/download/token/"+e.token}).error(function(e){angular.forEach(e.errors,function(e){n.addError(e.message)}),t.downloadAllLoading=!0})},t.isVideo=function(e){var n=/https?:\/\/(www\.|m\.)?(youtube\.com|youtu\.be)\/(watch\?v=)?([^&]+)/i;return n.test(e)&&!t.isPlaylist(e)?e.match(n)[4]:!1},t.isPlaylist=function(e){var n=/https?:\/\/(www\.|m\.)?(youtube\.com|youtu\.be)\/(watch\?v=[^&]+&list=([^&]+)|playlist\?list=([^&]+))/i;return n.test(e)?e.match(n)[5]:!1}}])}(),function(){"use strict";angular.module("EpydApp").service("EpydService",["$http",function(e){this.getVideo=function(n){return e({method:"GET",url:"api/video/"+n})},this.getPlaylist=function(n,o){return e({method:"GET",url:"api/playlist/"+n+(o?"/"+o:"")})},this.downloadVideoByID=function(n){return e({method:"POST",url:"api/download/video/"+n})},this.downloadVideoByObject=function(n){return e({method:"POST",url:"api/download/video",data:{video:n}})},this.downloadVideosByObject=function(n){return e({method:"POST",url:"api/download/videos",data:{videos:n}})}}])}(),function(){"use strict";angular.module("EpydApp").factory("VideoFactory",function(){var e=function(e){angular.forEach(e,function(e,n){this[n]=e},this),this.selected=!1,this.loading=!1,this.setSelected=function(e){this.selected=e?!0:!1},this.toggleSelected=function(){this.selected=!this.selected},this.isSelected=function(){return this.selected},this.getLink=function(){return"https://www.youtube.com/watch?v="+this.id},this.getChannelLink=function(){return"https://www.youtube.com/user/"+this.snippet.channelTitle}};return e})}(),function(){"use strict";angular.module("EpydApp").controller("MessageController",["MessageService","$scope",function(e,n){n.MessageService=e,n.clearMessage=function(e){n.MessageService.messages.splice(e,1)}}])}(),function(){"use strict";angular.module("EpydApp").service("MessageService",function(){this.messages=[],this.addError=function(e){this.messages.push({style:"alert-danger",icon:"fa-warning",text:e})},this.addMessage=function(e){this.messages.push({style:"alert-info",icon:"fa-quote-left",text:e})},this.addSuccess=function(e){this.messages.push({style:"alert-success",icon:"fa-thumbs-up",text:e})}})}();