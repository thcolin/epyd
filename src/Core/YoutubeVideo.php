<?php
	
	namespace Epyd\Core;
	
	use DateTime;
	use DateInterval;
	
	use Jyggen\Curl\Curl;
	use Jyggen\Curl\Request;
	
	use FFMpeg\FFMpeg;
	use FFMpeg\Format\Audio\Mp3;
	
	use GetId3\GetId3Core;
	use GetId3\Write\Tags;
	
	use V8JS;
	use Exception;
	
	class YoutubeVideo{
		
		public function __construct($object){
			
			$array = json_decode(json_encode($object), true);
			
			foreach($array as $key => $value)
			
				$this -> $key = $value;
				
			# Check if already parsed
			
			if(!isset($this -> epyd)){
				
				# Test if banned
				
				$this -> getWebInfos();
				
				# Corrections
				
				$start = new DateTime('@0');
				$start -> add(new DateInterval($this -> contentDetails['duration']));
				$this -> contentDetails['duration'] = $start -> format('i:s');
				
				# FileSystem
				
				$this -> filename = md5(time().$this -> id.rand());
			
				# Epyd
			
				$this -> epyd = array();
					
				if(preg_match('#([^\-]+) \- (.+)#', $this -> snippet['title'], $matches)){
					
					$this -> epyd['title'] = $matches[2];
					$this -> epyd['artist'] = $matches[1];
					
				}
				
				else{
					
					$this -> epyd['title'] = $this -> videoTitle;
					$this -> epyd['artist'] = null;
					
				}
			
			}
			
		}
		
		public function clean(){
			
			if(is_file($this -> getAudioFile()))
			
				unlink($this -> getAudioFile());
				
			if(is_file($this -> getVideoFile()))
			
				unlink($this -> getVideoFile());
			
		}
		
		/* V8JS */
		
		private function executeJavascript($javascript){
			
			$V8JS = new V8Js();
			
			ob_start();
			$V8JS -> executeString($javascript);
			$return = ob_get_clean();
			
			return $return;
			
		}
		
		/* Convert */
		
		public function extractAudio(){
			
			$ffmpeg = FFMpeg::create();
			
			$format = new Mp3();
			$format -> setAudioChannels(2) -> setAudioKiloBitrate(192);
			
			$video = $ffmpeg -> open($this -> getVideoFile());
			$video -> save($format, $this -> getAudioFile());
			
		}
		
		private function getBestThumbnail(){
			
			$best = ['width' => 0];
			
			foreach($this -> snippet['thumbnails'] as $thumbnail){
				
				if($thumbnail['width'] > $best['width'])
				
					$best = $thumbnail;
				
			}
			
			list($width, $height, $type) = getimagesize($best['url']);
			$imagetypes = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
			
			if(!isset($imagetypes[$type]))
			
				return [];
			
			return [
				'data' => file_get_contents($best['url']),
				'picturetypeid' => 'Other',
				'description' => basename($best['url']),
				'mime' => 'image/'.$imagetypes[$type]
			];
						
		}
		
		public function writeID3Tags(){
			
			$writer = new Tags();
			
			$writer -> filename = $this -> getAudioFile();
			$writer -> tagformats = ['id3v1', 'id3v2.3'];
			$writer -> overwrite_tags = true;
			$writer -> tag_encoding = 'UTF-8';
			$writer -> remove_other_tags = true;
			$writer -> tag_data = [
				'title' => [$this -> epyd['title']],
				'artist' => [$this -> epyd['artist']],
				'attached_picture' => [$this -> getBestThumbnail()]
			];
			
			$writer -> WriteTags();
			
		}
		
		/* Download */
		
		public function download(){
			
			$this -> prepareDownloadRequest();
			$this -> executeDownloadRequest();
			$this -> completeDownloadRequest();
			$this -> extractAudio();
			$this -> writeID3Tags();
			
		}
		
		public function getDownloadRequest(){
			
			if(!isset($this -> downloadRequest))
			
				$this -> prepareDownloadRequest();
			
			return $this -> downloadRequest;
			
		}
		
		private function prepareDownloadRequest(){
			
			$video = $this -> getBestVideo();
			
			if(isset($video['signature'])){
			
				$signature = $this -> decodeSignature($video['signature']);
				$url = $video['url'].'&signature='.$signature;
				
			}
			
			else
			
				$url = $video['url'];
			
			$this -> downloadRequest = new Request($url);
			$this -> downloadRequest -> setOption(CURLOPT_FILE, $this -> getTempFileHandler());
			
		}
		
		private function executeDownloadRequest(){
			
			$this -> downloadRequest -> execute();
			
		}
		
		public function completeDownloadRequest(){
			
			$this -> copyTmpFile2VideoFile();
			
		}
		
		private function copyTmpFile2VideoFile(){
			
			fseek($this -> getTempFileHandler(), $this -> downloadRequest -> getInfo(CURLINFO_HEADER_SIZE));
			
			while(!feof($this -> getTempFileHandler()))
			
				fwrite($this -> getVideoFileHandler(), fread($this -> getTempFileHandler(), 8192));
			
			$this -> closeTempFileHandler();
			$this -> closeVideoFileHandler();
			
		}
		
		/* Filesystem */
		
		# Video
		
		public function getVideoFile(){
			
			return PATH_APP.'/storage/'.$this -> filename.'.mp4';
			
		}
		
		public function isVideoFileDownloaded(){
			
			return (is_file($this -> getVideoFile()) && filesize($this -> getVideoFile()) > 0);
			
		}
		
		private function getVideoFileHandler(){
			
			if(!isset($this -> fpVideo))
		
				$this -> fpVideo = fopen($this -> getVideoFile(), 'w+');
				
			return $this -> fpVideo;
			
		}
		
		private function closeVideoFileHandler(){
			
			if(isset($this -> fpVideo))
			
				fclose($this -> fpVideo);
			
		}
		
		# Audio
		
		public function getAudioFile(){
			
			return PATH_APP.'/storage/'.$this -> filename.'.mp3';
			
		}
		
		public function isAudioFileExtracted(){
			
			return (is_file($this -> getAudioFile()) && filesize($this -> getAudioFile()) > 0);
			
		}
		
		# Temp
		
		private function getTempFileHandler(){
			
			if(!isset($this -> fpTemp))
			
				$this -> fpTemp = tmpfile();
			
			return $this -> fpTemp;
			
		}
		
		private function closeTempFileHandler(){
			
			if(isset($this -> fpTemp))
			
				fclose($this -> fpTemp);
			
		}
		
		# Final
		
		public function getFinalFile(){
			
			return $this -> getAudioFile();
			
		}
		
		public function getToken(){
			
			return $this -> filename;
			
		}
		
		/* Javascript */
		
		private function decodeSignature($signature){
			
			$asset = $this -> getJavascriptAsset();
			
			$name = $this -> getJavascriptAlgorithmName($asset);
			$algo = $this -> getJavascriptFunction($name, $asset);
			$helper = $this -> getJavascriptAlgorithmHelper($asset, $algo);
			
			$javascript = implode("\n", array($helper, $algo, 'print('.$name.'("'.$signature.'"));'));
			
			$decoded = $this -> executeJavascript($javascript);
			
			return $decoded;
			
		}
		
		private function getWebInfos(){
			
			$response = Curl::get('http://www.youtube.com/watch?v='.$this -> id.'&gl=US&persist_gl=1&hl=en&persist_hl=1');
			$raw = $response[0] -> getContent();
			
			if(!preg_match('#ytplayer.config = ({.*?});#', $raw, $matches))
			
				throw new Exception('Error on getting the web infos for "'.$this -> snippet['title'].'".');
			
			$this -> webInfos = json_decode($matches[1], true);
			
		}
		
		private function getJavascriptAsset(){
			
			$javascript = file_get_contents('http:'.$this -> webInfos['assets']['js']);
			
			if(!$javascript)
			
				throw new Exception('Error on getting the Javascript Asset.');
			
			return $javascript;
			
		}
		
		private function getJavascriptAlgorithmName($javascript){
			
			if(!preg_match('#\.sig\|\|([a-zA-Z0-9$]+)\(#', $javascript, $matches))
			
				throw new Exception('Error on getting the algorithm name.');
			
			return $matches[1];
			
		}
			
		private function getJavascriptFunction($name, $javascript){
			
			if(!preg_match('#(function '.preg_quote($name).'\(\w\){.*?})#', $javascript, $matches))
			
				throw new Exception('Error on getting the algorithm.');
			
			return $matches[1];
				
		}
		
		private function getJavascriptAlgorithmHelper($javascript, $algo){
			
			if(!preg_match('#\);(\$?\w+)\.#', $algo, $matches))
			
				throw new Exception('Error on getting the helper name.');
			
			$name = $matches[1];
				
			if(!preg_match('#('.preg_quote($name).'={.*?};)#', $javascript, $matches))
				
				throw new Exception('Error on getting the helper.');
			
			return $matches[1];
				
		}
		
		/* Videos */
		
		private function getVideos(){
			
			$videos = [];
				
			foreach(explode(',', $this -> webInfos['args']['url_encoded_fmt_stream_map']) as $format){
			
				parse_str($format, $temp);
				
				$videos[$temp['itag']]['url'] = urldecode($temp['url']);
				$videos[$temp['itag']]['type'] = explode(';', $temp['type'])[0];
				
				if(isset($temp['s']))
				
					$videos[$temp['itag']]['signature'] = $temp['s'];
			
			}
			
			return $videos;
			
		}
		
		private function getBestVideo(){
			
			$videos = $this -> getVideos();
			
			if(!$videos)
			
				throw new Exception('Error on getting the videos.');

			foreach(['38', '37', '22', '35', '34', '18'] as $quality){
				
				if(isset($videos[$quality]))
				
					return $videos[$quality];
				
			}
			
			throw new Exception('Error on getting the best video.');
			
		}
		
	}
	
?>