<?php

	namespace Epyd\Controllers;
	
	use Exception;
	
	use Silex\Application;
	use Silex\ControllerProviderInterface;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpKernel\KernelEvents;
	use Symfony\Component\HttpFoundation\ResponseHeaderBag;
	
	use Epyd\Core\YoutubeVideo;
	use Epyd\Core\YoutubePlaylist;
	
	class APIController implements ControllerProviderInterface{
	
		public function connect(Application $app){
			
			$controllers = $app['controllers_factory'];
			
			$controllers -> get('/video/{id}', get_class().'::getVideoByID');
			$controllers -> post('/download/video', get_class().'::downloadVideoByObject');
			$controllers -> post('/download/video/{id}', get_class().'::downloadVideoByID');
			
			$controllers -> get('/playlist/{id}/{token}', get_class().'::getPlaylistByID') -> value('token', true);
			$controllers -> post('/download/videos', get_class().'::downloadVideosByObject');
			
			$controllers -> get('/download/token/{token}', get_class().'::downloadFileByToken');
			
			return $controllers;
			
		}
		
		public function downloadFileByToken(Application $app, $token){
			
			# Clean
			
			$app -> on(KernelEvents::TERMINATE, function() use ($token){
				
				if(is_file(PATH_APP.'/storage/'.$token.'.zip'))
				
					unlink(PATH_APP.'/storage/'.$token.'.zip');
				
				if(is_file(PATH_APP.'/storage/'.$token.'.mp3'))
				
					unlink(PATH_APP.'/storage/'.$token.'.mp3');
				
			});
			
			# Playlist
			
			if(is_file(PATH_APP.'/storage/'.$token.'.zip'))
			
				return $app -> sendFile(PATH_APP.'/storage/'.$token.'.zip') -> setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $token.'.zip');
			
			# MP3
			
			else if(is_file(PATH_APP.'/storage/'.$token.'.mp3'))
			
				return $app -> sendFile(PATH_APP.'/storage/'.$token.'.mp3') -> setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $token.'.mp3');
			
			# Error
			
			else
			
				return $app -> json(['errors' => ['message' => "Ce fichier n'est pas disponible."]]);
			
		}
		
		public function getVideoByID(Application $app, $id){
				
			# Videos
			
			$data = $app['youtube'] -> getVideoInfo($id, ['id', 'snippet', 'contentDetails', 'statistics']);
			
			if(!$data)
			
				return $app -> json(['error' => true, 'message' => "Cette vidéo Youtube n'est pas disponible."], 400);
			
			# Catch banned Videos
			
			try{
			
				$Video = new YoutubeVideo($data);
				
			}
			
			catch(Exception $e){
				
				return $app -> json(['error' => true, 'message' => $e -> getMessage()], 400);
				
			}
			
			# Response
			
			return $app -> json(['video' => $Video]);
			
		}
		
		public function downloadVideoByID(Application $app, $id){
				
			# Videos
			
			$data = $app['youtube'] -> getVideoInfo($id, ['id', 'snippet', 'contentDetails', 'statistics']);
			
			if(!$data)
			
				return $app -> json(['error' => true, 'message' => "Cette vidéo Youtube n'est pas disponible."], 400);
			
			# Youtube Video
				
			try{
				
				$Video = new YoutubeVideo($data);
				$Video -> download();
			
			}
			
			catch(Exception $e){
				
				return $app -> json(['message' => $e -> getMessage()], 400);
				
			}
			
			# Response
			
			if(is_file($Video -> getAudioFile()))
				
				return $app -> json(['token' => $Video -> getToken()]);
				
			else
			
				return $app -> json(['errors' => 'Erreur durant le processus de téléchargement ou de conversion.'], 400);
			
		}
		
		public function downloadVideoByObject(Application $app, Request $request){
			
			# POST datas
			
			$raw = $request -> request -> get('video');
			
			if(!$raw)
			
				return $app -> json(['errors' => ['message' => "Vous n'avez sélectionnez aucune vidéo à télécharger !"]], 400);
			
			# Youtube Video
				
			try{
				
				$Video = new YoutubeVideo($raw);
				$Video -> download();
			
			}
			
			catch(Exception $e){
				
				return $app -> json(['message' => $e -> getMessage()], 400);
				
			}
			
			# Response
			
			if(is_file($Video -> getAudioFile()))
				
				return $app -> json(['token' => $Video -> getToken()]);
				
			else
			
				return $app -> json(['errors' => 'Erreur durant le processus de téléchargement ou de conversion.'], 400);
				
		}
		
		public function downloadVideosByObject(Application $app, Request $request){
				
			$Errors = $Videos = [];
			
			# POST datas
			
			$raw = $request -> request -> get('videos');
			
			if(!$raw)
			
				return $app -> json(['errors' => ['message' => "Vous n'avez sélectionnez aucune vidéo à télécharger !"]], 400);
			
			# Youtube Videos
				
			try{
				
				foreach($raw as $data)
				
					$Videos[] = new YoutubeVideo($data);
			
			}
			
			catch(Exception $e){
				
				$Errors[] = ['message' => $e -> getMessage()];
				
			}
			
			# All Videos banned
			
			if(!$Videos)
			
				return $app -> json(['errors' => ['message' => "Vous n'avez sélectionnez aucune vidéo valide à télécharger !"]], 400);
					
			# Youtube Playlist
				
			$YoutubePlaylist = new YoutubePlaylist($Videos);
			$YoutubePlaylist -> download();
				
			# Clean
			
			$app -> on(KernelEvents::TERMINATE, function() use ($YoutubePlaylist){
				
				$YoutubePlaylist -> clean();
				
			});
			
			# Response
			
			if(is_file($YoutubePlaylist -> getFinalFile()))
				
				return $app -> json(['token' => $YoutubePlaylist -> getToken(), 'errors' => $Errors]);
				
			else
			
				return $app -> json(['errors' => $Errors], 400);
			
		}
		
		public function getPlaylistByID(Application $app, $id, $token){
			
			$Videos = $Errors = $ids = [];
			
			# Playlist
			
			try{
			
				$raw = $app['youtube'] -> getPlaylistItemsByPlaylistId($id, $token, 10, ['contentDetails']);
				
			}
			
			catch(Exception $e){
				
				return $app -> json(['errors' => ['message' => $e -> getMessage()]], 400);
				
			}
			
			if(isset($raw['results'])){
			
				foreach($raw['results'] as $result)
					
					$ids[] = $result -> contentDetails -> videoId;
				
			}
			
			if(!$ids)
			
				$Errors[] = ['message' => 'Cette Playlist Youtube est vide.'];
				
			# Videos
			
			$datas = $app['youtube'] -> getVideoInfo($ids, ['id', 'snippet', 'contentDetails', 'statistics']);
			
			# Catch banned Videos
			
			try{
			
				foreach($datas as $data)
				
					$Videos[] = new YoutubeVideo($data);
				
			}
			
			catch(Exception $e){
				
				$Errors[] = ['message' => $e -> getMessage()];
				
			}
			
			# Response
			
			return $app -> json(['info' => $raw['info'], 'videos' => $Videos, 'errors' => $Errors]);
			
		}
		
	}
	
?>