## Epyd

Epyd (Easy Playlist Youtube Downloadr) vous permet de télécharger vos vidéos et playlists Youtube directement en MP3 avec les tags ID3 remplis correctement (Titre, Artiste, Pochette).

![Image of Epyd](http://i.imgur.com/0VpJD9M.png)

## Tags ID3

* La pochette est définis par le Thumbnail de la vidéo
* L'artiste (Si le titre de la vidéo est au format "Artiste - Titre")
* Le titre (Toujours pareil, si le titre est au format "Artiste - Titre")

## Utilisation

Vous pouvez au choix, télécharger juste une vidéo au format MP3, ou en sélectionner plusieurs (si vous checkez une playlist par exemple) et télécharger un zip de toutes ces vidéos en MP3.

## Configuration

Créer un fichier `/app/vars.php` :
```
<?php

	define('YOUTUBE_KEY', "Votre clé API Youtube");
	define('FFMPEG_BIN', '/usr/bin/ffmpeg');
	define('FFPROBE_BIN', '/usr/bin/ffprobe');

?>
```

## Todo

* Passer tout en NodeJS (Plus simple pour le calcul de la clé via l'asset Javascript récupéré de Youtube)
* Ajouter des tests unitaire

## Prérequis

* [Extension PHP V8Js](http://php.net/v8js)
* [avconv / ffmpeg](https://libav.org/download/)
* zip

Aide pour l'installation :

- [On Linux](https://github.com/preillyme/v8js/blob/master/README.Linux.md)
- [On MacOS](https://github.com/preillyme/v8js/blob/master/README.MacOS.md)
- [On Windows](https://github.com/preillyme/v8js/blob/master/README.Win32.md)
