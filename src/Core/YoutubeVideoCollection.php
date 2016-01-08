<?php

  namespace Epyd\Core;

  use Epyd\Core\YoutubeVideo;

  use Epyd\Exceptions\InvalidKeyException;
  use Epyd\Exceptions\EmptyYoutubeVideoCollectionException;
  use Exception;

  class YoutubeVideoCollection{

    protected $items = [];

    public function __construct(array $videos = []){
      foreach($videos as $video){
        $this -> addItem($video);
      }
      $this -> setToken();
    }

    public function getToken(){
      if($this -> length() == 0){
        throw new EmptyYoutubeVideoCollectionException('La collection de vidéoss Youtube à télécharger est vide.');
      } else if($this -> length() == 1){
        return $this -> getItem(0) -> getToken();
      } else{
        return $this -> token;
      }
    }

    public function setToken(){
      $this -> token = md5(time().json_encode($this -> getItems()).rand());
    }

    public function getNext(){
      return $this -> next;
    }

    public function setNext($token){
      $this -> next = $token;
    }

    public function getPrev(){
      return $this -> prev;
    }

    public function setPrev($token){
      $this -> prev = $token;
    }

		public function getZipFilePath(){
			return PATH_APP.'/storage/'.$this -> getToken().'.zip';
		}

    public function clean(){
      foreach($this -> getItems() as $item){
        $item -> clean();
      }
    }

    public function getItems(){
      return $this -> items;
    }

    public function getItem($key){
      if($this -> keyExists($key)){
        return $this -> items[$key];
      } else{
        throw new InvalidKeyException('Invalid key '.$key);
      }
    }

    public function addItem(YoutubeVideo $video, $key = null){
      if($key == null){
        $this -> items[] = $video;
      } else if(!$this -> keyExists($key)){
        $this -> items[$key] = $video;
      } else{
        throw new Exception('Key '.$key.' already in use');
      }
    }

    public function deleteItem($key){
      if($this -> keyExists($key)){
        unset($this -> items[$key]);
      } else{
        throw new KeyInvalidException('Invalid key '.$key);
      }
    }

    public function keys(){
      return array_keys($this -> items);
    }

    public function keyExists($key){
      return isset($this -> items[$key]);
    }

    public function length(){
      return count($this -> items);
    }

  }

?>
