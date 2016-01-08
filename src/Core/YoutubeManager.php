<?php

  namespace Epyd\Core;

  use FFMpeg\FFMpeg;
	use FFMpeg\Format\Audio\Mp3;
  use Alaouy\Youtube\Youtube;
	use Chumper\Zipper\Zipper;
  use Jyggen\Curl\Dispatcher;
  use Epyd\Core\YoutubeVideoCollection;

  use Exception;
  use Epyd\Exceptions\UnavailableYoutubeVideoException;
  use Epyd\Exceptions\UnavailableYoutubePlaylistException;
  use Epyd\Exceptions\InvalidTokenException;
  use Epyd\Exceptions\EmptyYoutubePlaylistException;
  use Epyd\Exceptions\EmptyYoutubeVideoCollectionException;

  class YoutubeManager{

    protected $ffmpeg;
    protected $youtube;
    protected $exceptions = [];

    public function __construct(FFMpeg $ffmpeg, Youtube $youtube){
      $this -> ffmpeg = $ffmpeg;
      $this -> youtube = $youtube;
    }

    public function getTokenFilePath($token){
      if(is_file(PATH_APP.'/storage/'.$token.'.mp3')){
        return PATH_APP.'/storage/'.$token.'.mp3';
      } else if(is_file(PATH_APP.'/storage/'.$token.'.zip')){
        return PATH_APP.'/storage/'.$token.'.zip';
      } else{
        throw new InvalidTokenException();
      }
    }

    private function addException(Exception $e){
      $this -> exceptions[] = $e;
    }

    public function getExceptions(){
      return $this -> exceptions;
    }

    public function cleanExceptions(){
      $this -> exceptions = [];
    }

    public function getVideo($id){
			$data = $this -> youtube -> getVideoInfo($id, ['id', 'snippet', 'contentDetails', 'statistics']);

			if(!$data){
				throw new UnavailableYoutubeVideoException('La vidéo Youtube "'.$id.'" n\'est pas disponible.');
			}

      $video = new YoutubeVideo($data);
			return $video;
    }

    public function getPlaylist($id, $token = null){
      try{
        $raw = $this -> youtube -> getPlaylistItemsByPlaylistId($id, $token, 10, ['contentDetails']);
      } catch(Exception $e){
        throw new UnavailableYoutubePlaylistException('La playlist Youtube "'.$id.'" n\'est pas disponible.');
      }

			if(isset($raw['results']) && $raw['results']){
				foreach($raw['results'] as $result){
					$ids[] = $result -> contentDetails -> videoId;
				}
			}

			if(!$ids){
				throw new EmptyYoutubePlaylistException('La playlist Youtube "'.$id.'" est vide.');
			}

			$datas = $this -> youtube -> getVideoInfo($ids, ['id', 'snippet', 'contentDetails', 'statistics']);

			foreach($datas as $data){
				try{
					$videos[] = new YoutubeVideo($data);
				} catch(Exception $e){
					$this -> addException($e);
				}
			}

      $collection = new YoutubeVideoCollection($videos);
      $collection -> setNext($raw['info']['nextPageToken']);
      $collection -> setPrev($raw['info']['prevPageToken']);

      return $collection;
    }

    public function downloadCollection(YoutubeVideoCollection $collection){
      $dp = new Dispatcher();

      if(!$collection -> length()){
        throw new EmptyYoutubeVideoCollectionException('La collection de vidéoss Youtube à télécharger est vide.');
      }

      foreach($collection -> getItems() as $key => $video){
        try{
          $request = $video -> getDownloadRequest();
          $i = $dp -> add($request);
        } catch(Exception $e){
          $collection -> deleteItem($key);
          $this -> addException($e);
        }
      }

      $dp -> execute();

      foreach($collection -> getItems() as $key => $video){
        try{
          $video -> closeDownloadRequest();
          $this -> extractAudio($video);
          $video -> clean(YoutubeVideo::VIDEO);
          $video -> writeID3Tags();
        } catch(Exception $e){
          $collection -> deleteItem($key);
          $this -> addException($e);
        }
      }

			if($i < 0){
        throw new Exception('Aucune vidéo n\'a put être téléchargée.');
			} else if($i > 0){
  			$zip = new Zipper();
  			$zip -> make($collection -> getZipFilePath());

  			foreach($collection -> getItems() as $video){
  				$zip -> add($video -> getAudioFilePath());
  			}

  			$zip -> close();
        $collection -> clean();
      }
    }

    private function extractAudio(YoutubeVideo $video){
			$format = new Mp3();
			$format -> setAudioChannels(2) -> setAudioKiloBitrate(192);

			$flux = $this -> ffmpeg -> open($video -> getVideoFilePath());
			$flux -> save($format, $video -> getAudioFilePath());
    }

  }

?>
