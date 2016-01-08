<?php

  namespace Epyd\Tests;

  use Silex\Application;
  use Silex\WebTestCase;

  use Epyd\Controllers\EpydController;

  use Exception;

  class EpydControllerTests extends WebTestCase{

    /**
     * Bootstrap Silex Application for tests
     * @method createApplication
     * @return $app              Silex\Application
     */
    public function createApplication(){
      $app = require __DIR__.'/../app/bootstrap/app.php';
      $app -> mount('/', new EpydController());
      return $app;
    }

    private function getVideo($id){
      $client = $this -> createClient();
      $crawler = $client -> request('GET', '/video/'.$id);

      return json_decode($client -> getResponse() -> getContent(), true);
    }

    private function getPlaylist($id){
      $client = $this -> createClient();
      $crawler = $client -> request('GET', '/playlist/'.$id);

      return json_decode($client -> getResponse() -> getContent(), true);
    }

    private function downloadVideos($videos){
      $client = $this -> createClient();
      $crawler = $client -> request('POST', '/download/videos', ['videos' => $videos]);

      return json_decode($client -> getResponse() -> getContent(), true);
    }

    private function downloadToken($token){
      $client = $this -> createClient();
      $crawler = $client -> request('GET', '/download/token/'.$token);

      return $client -> getResponse();
    }

    public function testGetVideoSuccess(){
      $data = $this -> getVideo('q7o7R5BgWDY');

      $this -> assertArrayHasKey('video', $data);
    }

    public function testGetVideoUnavailableError(){
      $data = $this -> getVideo('vxeontdHObU'); // deleted video

      $this -> assertArrayHasKey('error', $data);
      $this -> assertEquals($data['type'], 'Epyd\Exceptions\UnavailableYoutubeVideoException');

      $data = $this -> getVideo('this-is-not-an-id'); // unexpected video

      $this -> assertArrayHasKey('error', $data);
      $this -> assertEquals($data['type'], 'Epyd\Exceptions\UnavailableYoutubeVideoException');
    }

    public function testGetPlaylistSuccess(){
      $data = $this -> getPlaylist('PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs');

      $this -> assertArrayHasKey('videos', $data);
      $this -> assertGreaterThan(0, count($data['videos']));
      $this -> assertArrayHasKey('info', $data);
      $this -> assertArrayHasKey('next', $data['info']);
      $this -> assertArrayHasKey('prev', $data['info']);
    }

    public function testGetPlaylistSuccessWithErrors(){
      $data = $this -> getPlaylist('PLD98CD0C0522820F9');

      $this -> assertArrayHasKey('videos', $data);
      $this -> assertGreaterThan(0, count($data['videos']));
      $this -> assertGreaterThan(0, count($data['errors']));
      $this -> assertArrayHasKey('info', $data);
      $this -> assertArrayHasKey('next', $data['info']);
      $this -> assertArrayHasKey('prev', $data['info']);
    }

    public function testGetPlaylistUnavailableError(){
      $data = $this -> getPlaylist('this-is-not-an-id');

      $this -> assertArrayHasKey('error', $data);
      $this -> assertEquals($data['type'], 'Epyd\Exceptions\UnavailableYoutubePlaylistException');
    }

    public function testGetPlaylistEmptyError(){
      $data = $this -> getPlaylist('PLmplTfljKCTXbrS794Op0zx0Bjh4ZXLaB');

      $this -> assertArrayHasKey('error', $data);
      $this -> assertEquals($data['type'], 'Epyd\Exceptions\EmptyYoutubePlaylistException');
    }

    public function testDownloadVideosMultiSuccess(){
      $data = $this -> downloadVideos([
        'kJa2c2sW9TY' => [
          'artist' => 'artist1',
          'title' => 'title1'
        ],
        'jTAPsVXLu1I' => [
          'artist' => 'artist2',
          'title' => 'title2'
        ]
      ]);

      $this -> assertArrayHasKey('token', $data);
      $this -> assertEquals(0, count($data['errors']));

      $response = $this -> downloadToken($data['token']);
      $this -> assertTrue($response -> isSuccessful());
    }

    public function testDownloadVideosMultiSuccessWithErrors(){
      $data = $this -> downloadVideos([
        'kJa2c2sW9TY' => [ // valid
          'artist' => 'artist1',
          'title' => 'title1'
        ],
        'vxeontdHObU' => [ // error
          'artist' => 'artist2',
          'title' => 'title2'
        ]
      ]);

      $this -> assertArrayHasKey('token', $data);
      $this -> assertEquals(1, count($data['errors']));

      $response = $this -> downloadToken($data['token']);
      $this -> assertTrue($response -> isSuccessful());
    }

    public function testDownloadVideosMultiError(){
      $data = $this -> downloadVideos([
        'error1' => [ // valid
          'artist' => 'artist1',
          'title' => 'title1'
        ],
        'error2' => [ // error
          'artist' => 'artist2',
          'title' => 'title2'
        ]
      ]);

      $this -> assertArrayHasKey('error', $data);
      $this -> assertEquals($data['type'], 'Epyd\Exceptions\EmptyYoutubeVideoCollectionException');
    }

    public function testDownloadVideosUniqueSuccess(){
      $data = $this -> downloadVideos([
        'kJa2c2sW9TY' => [
          'artist' => 'artist',
          'title' => 'title'
        ]
      ]);

      $this -> assertArrayHasKey('token', $data);
      $this -> assertEquals(0, count($data['errors']));

      $response = $this -> downloadToken($data['token']);
      $this -> assertTrue($response -> isSuccessful());
    }

    public function testDownloadVideosUniqueWithSignatureSuccess(){
      $data = $this -> downloadVideos([
        '3pjLRnzhly8' => [
          'artist' => 'artist',
          'title' => 'title'
        ]
      ]);

      $this -> assertArrayHasKey('token', $data);
      $this -> assertEquals(0, count($data['errors']));

      $response = $this -> downloadToken($data['token']);
      $this -> assertTrue($response -> isSuccessful());
    }

    public function testDownloadVideosUniqueError(){
      $data = $this -> downloadVideos([
        'vxeontdHObU' => [
          'artist' => 'artist',
          'title' => 'title'
        ]
      ]);

      $this -> assertArrayHasKey('error', $data);
      $this -> assertEquals($data['type'], 'Epyd\Exceptions\UnavailableYoutubeVideoException');
    }

  }

?>
