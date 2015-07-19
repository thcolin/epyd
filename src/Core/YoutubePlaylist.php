<?php
	
	namespace Epyd\Core;
	
	use Jyggen\Curl\Dispatcher;
	use Chumper\Zipper\Zipper;
	
	class YoutubePlaylist{
		
		public function __construct($YoutubeVideos){
			
			$this -> Videos = $YoutubeVideos;
			
			$this -> filename = md5(time().json_encode($this -> Videos).rand());
			
		}
		
		public function clean(){
			
			foreach($this -> Videos as $Video)
			
				$Video -> clean();
			
		}
		
		private function getZipFile(){
			
			return PATH_APP.'/storage/'.$this -> filename.'.zip';
			
		}
		
		# Final
		
		public function getToken(){
			
			return $this -> filename;
			
		}
		
		public function getFinalFile(){
			
			return $this -> getZipFile();
			
		}
		
		private function executeAllVideoRequest(){
			
			$dp = new Dispatcher();
			
			foreach($this -> Videos as $Video)
			
				$dp -> add($Video -> getDownloadRequest());
				
			$dp -> execute();
			
			foreach($this -> Videos as $Video)
			
				$Video -> completeDownloadRequest();
			
		}
		
		private function writeAllID3Tags(){
			
			foreach($this -> Videos as $Video){
			
				$Video -> extractAudio();
				$Video -> writeID3Tags();
				
			}
			
		}
		
		private function zip(){
			
			$zip = new Zipper();
			
			$zip -> make($this -> getZipFile());
			
			foreach($this -> Videos as $Video)
			
				$zip -> add($Video -> getAudioFile());
				
			$zip -> close();
			
		}
		
		public function download(){
			
			$this -> executeAllVideoRequest();
			$this -> writeAllID3Tags();
			$this -> zip();
			
		}
		
	};
	
?>