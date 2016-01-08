<?php

	use Alaouy\Youtube\Youtube;

	$app['youtube'] = $app -> share(function($app){
		return new Youtube(YOUTUBE_KEY);
	});

	use FFMpeg\FFMpeg;

	$app['ffmpeg'] = $app -> share(function($app){
		return FFMpeg::create([
      'ffmpeg.binaries'  => FFMPEG_BIN,
      'ffprobe.binaries' => FFPROBE_BIN,
      'timeout'          => 3600,
      'ffmpeg.threads'   => 12,
    ]);
	});

	use Epyd\Core\YoutubeManager;

	$app['youtube.manager'] = $app -> share(function($app){
		return new YoutubeManager($app['ffmpeg'], $app['youtube']);
	});

?>
