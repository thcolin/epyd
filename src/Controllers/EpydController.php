<?php

	namespace Epyd\Controllers;


	use Silex\Application;
	use Silex\ControllerProviderInterface;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpKernel\KernelEvents;
	use Symfony\Component\HttpFoundation\ResponseHeaderBag;

	use Epyd\Core\YoutubeVideo;
	use Epyd\Core\YoutubeVideoCollection;

	use Exception;
	use Epyd\Exceptions\EmptyYoutubeVideoCollectionException;

	class EpydController implements ControllerProviderInterface{

		public function connect(Application $app){
			$controllers = $app['controllers_factory'];

			$controllers -> get('/video/{id}', get_class().'::getVideo');
			$controllers -> get('/playlist/{id}/{token}', get_class().'::getPlaylist') -> value('token', true);
			$controllers -> post('/download/videos', get_class().'::downloadVideos');
			$controllers -> get('/download/token/{token}', get_class().'::downloadFileByToken');
			$controllers -> get('/clean/token/{token}', get_class().'::cleanFileByToken');

			return $controllers;
		}

		public function downloadFileByToken(Application $app, $token){
			$file = $app['youtube.manager'] -> getTokenFilePath($token);
			return $app -> sendFile($file) -> deleteFileAfterSend(true) -> setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($file));
		}

		public function getVideo(Application $app, $id){
			$video = $app['youtube.manager'] -> getVideo($id);
			return $app -> json(['video' => $video]);
		}

		public function getPlaylist(Application $app, $id, $token){
			$playlist = $app['youtube.manager'] -> getPlaylist($id, $token);

			$errors = [];
			foreach($app['youtube.manager'] -> getExceptions() as $e){
				$errors[] = ['error' => $e -> getMessage(), 'type' => get_class($e)];
			}

			return $app -> json(['videos' => $playlist -> getItems(), 'info' => ['next' => $playlist -> getNext(), 'prev' => $playlist -> getPrev()], 'errors' => $errors]);
		}

		public function downloadVideos(Application $app, Request $request){
			$raw = $request -> request -> get('videos');

			if(!$raw){
				throw new Exception('Vous n\'avez sélectionnez aucune vidéo à télécharger.');
			}

			$exceptions = $videos = [];
			foreach($raw as $id => $id3){
				try{
					$video = $app['youtube.manager'] -> getVideo($id);

					if(isset($id3['title'])){
						$video -> setID3Tag('title', $id3['title']);
					}

					if(isset($id3['artist'])){
						$video -> setID3Tag('artist', $id3['artist']);
					}

					$videos[] = $video;
				} catch(Exception $e){
					$exceptions[] = $e;
				}
			}

			if(count($exceptions) == 1 && !count($videos)){
				throw $exceptions[0];
			}

			$collection = new YoutubeVideoCollection($videos);
			$app['youtube.manager'] -> downloadCollection($collection);

			$errors = [];
			foreach(array_merge($exceptions, $app['youtube.manager'] -> getExceptions()) as $e){
				$errors[] = ['error' => $e -> getMessage(), 'type' => get_class($e)];
			}

			if(count($errors) == 1 && !$collection -> length()){
				throw new $errors[0]['type']($errors[0]['error']);
			}

			return $app -> json(['token' => $collection -> getToken(), 'errors' => $errors]);
		}

	}

?>
