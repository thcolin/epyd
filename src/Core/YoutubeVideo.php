<?php

	namespace Epyd\Core;

	use DateTime;
	use DateInterval;

	use V8JS;

	use Jyggen\Curl\Curl;
	use Jyggen\Curl\Request;

	use GetId3\GetId3Core;
	use GetId3\Write\Tags;

	use Exception;
	use Epyd\Exceptions\UnavailableYoutubeVideoException;

	class YoutubeVideo{

		const VIDEO = 1;
		const AUDIO = 2;
		const ALL   = 3;

		public function __construct($object){
			$array = json_decode(json_encode($object), true);

			foreach($array as $key => $value){
				$this -> $key = $value;
			}

			// webInfos (usefull to get flux & javascript assets)
			$response = Curl::get('http://www.youtube.com/watch?v='.$this -> id.'&gl=US&persist_gl=1&hl=en&persist_hl=1');
			$raw = $response[0] -> getContent();

			if(!preg_match('#ytplayer.config = ({.*?});#', $raw, $matches)){
				throw new UnavailableYoutubeVideoException('La vidéo Youtube "'.$this -> snippet['title'].'" n\'est pas disponible.');
			}

			$this -> webInfos = json_decode($matches[1], true);

			// correct duration
			$start = new DateTime('@0');
			$start -> add(new DateInterval($this -> contentDetails['duration']));
			$this -> contentDetails['duration'] = $start -> format('i:s');

			// id3 tags
			$this -> id3 = [];
			if(preg_match('#([^\-]+) \- (.+)#', $this -> snippet['title'], $matches)){
				$this -> setID3Tag('title', $matches[2]);
				$this -> setID3Tag('artist', $matches[1]);
			} else{
				$this -> setID3Tag('title', $this -> videoTitle);
				$this -> setID3Tag('artist', null);
			}

			// token
			$this -> setToken();
		}

		public function clean($tag = self::ALL){
			$opts = array_reverse(str_split(decbin($tag)));
			if(is_file($this -> getVideoFilePath()) && $opts[0]){
				unlink($this -> getVideoFilePath());
			}
			if(is_file($this -> getAudioFilePath()) && $opts[1]){
				unlink($this -> getAudioFilePath());
			}
		}

		public function getToken(){
			return $this -> token;
		}

		public function setToken(){
			$this -> token = md5(time().$this -> id.rand());
		}

		public function getID3Tags(){
			return $this -> id3;
		}

		public function getID3Tag($tag){
			return (isset($this -> id3[$tag]) ? $this -> id3[$tag]:null);
		}

		public function setID3Tag($tag, $value){
			$this -> id3[$tag] = $value;
		}

		public function writeID3Tags(){
			$writer = new Tags();

			$writer -> filename = $this -> getAudioFilePath();
			$writer -> tagformats = ['id3v1', 'id3v2.3'];
			$writer -> overwrite_tags = true;
			$writer -> tag_encoding = 'UTF-8';
			$writer -> remove_other_tags = true;
			$writer -> tag_data = [
				'title' => [$this -> getID3Tag('title')],
				'artist' => [$this -> getID3Tag('artist')],
				'attached_picture' => [$this -> getBestThumbnail()]
			];

			$writer -> WriteTags();
		}

		public function getBestThumbnail(){
			$best = ['width' => 0];
			foreach($this -> snippet['thumbnails'] as $thumbnail){
				if($thumbnail['width'] > $best['width']){
					$best = $thumbnail;
				}
			}
			list($width, $height, $type) = getimagesize($best['url']);
			$imagetypes = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
			if(!isset($imagetypes[$type])){
				return [];
			}
			return [
				'data' => file_get_contents($best['url']),
				'picturetypeid' => 'Other',
				'description' => basename($best['url']),
				'mime' => 'image/'.$imagetypes[$type]
			];
		}

		public function getDownloadRequest(){
			if(isset($this -> downloadRequest)){
				return $this -> downloadRequest;
			}

			$flux = $this -> getBestFlux();

			if(isset($flux['signature'])){
				$signature = $this -> decodeSignature($flux['signature']);
				$url = $flux['url'].'&signature='.$signature;
			} else{
				$url = $flux['url'];
			}

			$this -> downloadRequest = new Request($url);
 			$this -> downloadRequest -> setOption(CURLOPT_FILE, $this -> getDownloadStream());

			return $this -> downloadRequest;
		}

		public function closeDownloadRequest(){
			$request = $this -> getDownloadRequest();
			$fp = fopen($this -> getVideoFilePath(), 'w+');

			fseek($this -> fp, $request -> getInfo(CURLINFO_HEADER_SIZE));

			while(!feof($this -> fp)){
				fwrite($fp, fread($this -> fp, 8192));
			}

			fclose($this -> fp);
			fclose($fp);

			if(!filesize($this -> getVideoFilePath())){
				throw new Exception('Erreur lors du téléchargement de la vidéo "'.$this -> snippet['title'].'".');
			}
		}

		public function getDownloadStream(){
			if(!isset($this -> fp)){
				$this -> fp = tmpfile();
			}

			return $this -> fp;
		}

		public function getVideoFilePath(){
			return PATH_APP.'/storage/'.$this -> getToken().'.mp4';
		}

		public function getAudioFilePath(){
			return PATH_APP.'/storage/'.$this -> getToken().'.mp3';
		}

		private function getBestFlux(){
			$fluxs = [];

			foreach(explode(',', $this -> webInfos['args']['url_encoded_fmt_stream_map']) as $format){
				parse_str($format, $parsed);

				$fluxs[$parsed['itag']]['url'] = urldecode($parsed['url']);
				$fluxs[$parsed['itag']]['type'] = explode(';', $parsed['type'])[0];

				if(isset($parsed['s'])){
					$fluxs[$parsed['itag']]['signature'] = $parsed['s'];
				}
			}

			foreach(['38', '37', '22', '35', '34', '18'] as $quality){
				if(isset($fluxs[$quality])){
					return $fluxs[$quality];
				}
			}

			throw new Exception('Error on getting the best video for "'.$this -> snippet['title'].'".');
		}

		private function decodeSignature($signature){
			// get javascript asset
			$asset = file_get_contents('http:'.$this -> webInfos['assets']['js']);

			if(!$asset){
				throw new Exception('Error on getting the Javascript Asset for "'.$this -> snippet['title'].'".');
			}

			// get algo javascript function name
			if(preg_match('#\.sig\|\|([a-zA-Z0-9$]+)\(#', $asset, $matches)){
				$algoName = $matches[1];
			} else{
				throw new Exception('Error on getting the algorithm name for "'.$this -> snippet['title'].'".');
			}

			// get algo javascript function
			if(preg_match('#(function '.preg_quote($algoName).'\(\w\){.*?})#s', $asset, $matches)){
				$algo = $matches[1];
			} else if(preg_match('#('.preg_quote($algoName).'=function\(\w\){.*?})#s', $asset, $matches)){
				$algo = $matches[1];
			} else{
				throw new Exception('Error on getting the algorithm for "'.$this -> snippet['title'].'".');
			}

			// get decode javascript helpers functions name
			if(preg_match('#\);(\$?\w+)\.#', $algo, $matches)){
				$helperName = $matches[1];
			} else{
				throw new Exception('Error on getting the helper name for "'.$this -> snippet['title'].'".');
			}

			// get decode javascript helpers functions
			if(preg_match('#(var )?'.preg_quote($helperName).'={.*?};#s', $asset, $matches)){
				$helper = $matches[0];
			} else{
				throw new Exception('Error on getting the helper for "'.$this -> snippet['title'].'".');
			}

			$javascript = implode("\n", [$helper, $algo, 'print('.$algoName.'("'.$signature.'"));']);
			$decoded = $this -> executeJavascript($javascript);

			return $decoded;
		}

		private function executeJavascript($javascript){
			$V8JS = new V8JS();

			ob_start();
			$V8JS -> executeString($javascript);
			$return = ob_get_clean();

			return $return;
		}

	}

?>
