<?php
  $version = 0;

  $podcasts = array();

  $podcast = new StdClass();
  $podcast->id = "podcast:1";
  $podcast->title = "Accidental Tech Podcast";
  $podcast->art = "https://d1eedt7bo0oujw.cloudfront.net/art?s=3fba7cd33ee1b897707efaf08b335c6425ec5907e491ca2eb07ecd32a51590dd&w=840&u=http%3A%2F%2Fstatic1.squarespace.com%2Fstatic%2F513abd71e4b0fe58c655c105%2Ft%2F52c45a37e4b0a77a5034aa84%2F1388599866232%2F1500w%2FArtwork.jpg";
  $podcast->episodes = array();
  $podcast->episodes[0] = new StdClass();
  $podcast->episodes[0]->id = "podcast:1:episode:1";
  $podcast->episodes[0]->title = "201: Volume Micromanager";
  $podcast->episodes[0]->description = "Super Mario Run, AirPods, tabs vs. apps, and Cook on desktops.";
  $podcast->episodes[0]->duration = 7741;
  $podcasts[$podcast->id] = $podcast;

  $podcast = new StdClass();
  $podcast->id = "podcast:2";
  $podcast->title = "Freakonomics Radio";
  $podcast->art = "https://d1eedt7bo0oujw.cloudfront.net/art?s=d8dafd7577b4a1a999b39be6c2f8f778c055c01fc3cd0abad8ee20b065cebe0b&w=840&u=https%3A%2F%2Fmedia2.wnyc.org%2Fi%2F1400%2F1400%2Fl%2F80%2F1%2Fwn16_wnycstudios_freakonomics-rev3.png";
  $podcast->episodes = array();
  $podcasts[$podcast->id] = $podcast;

  $podcast = new StdClass();
  $podcast->id = "podcast:3";
  $podcast->title = "The Talk Show With John Gruber";
  $podcast->art = "https://d1eedt7bo0oujw.cloudfront.net/art?s=3333f3dd58c4e3398ecb18146945197ffc6d7f4f2bf67aeffe924df16d4cf6a8&w=840&u=http%3A%2F%2Fdaringfireball.net%2Fthetalkshow%2Fgraphics%2Fdf-the-talk-show-album-art.png";
  $podcast->episodes = array();
  $podcasts[$podcast->id] = $podcast;

  class Sonos {
    function getLastUpdate() {
      $response = new StdClass();
      $response->getLastUpdateResult = new StdClass();
      $response->getLastUpdateResult->favorites = $GLOBALS['version'];
      $response->getLastUpdateResult->catalog = $GLOBALS['version'];
      $response->getLastUpdateResult->pollInterval = 10;
      return $response;
    }

    function getMetadata($metadata) {
      $count = $metadata->count;
      $id = $metadata->id;
      $index = $metadata->index;
      $recursive = $metadata->recursive;

      $mediaCollection = array();
      $mediaMetadata = array();

      if ($id == "root") {
        $media = new StdClass();
        $media->id = "playlists";
        $media->itemType = "container";
        $media->displayType = "";
        $media->title = "Playlists";
        $mediaCollection[] = $media;

        $media = new StdClass();
        $media->id = "podcasts";
        $media->itemType = "container";
        $media->displayType = "";
        $media->title = "Podcasts";
        $mediaCollection[] = $media;
      } elseif ($id == "playlists") {

      } elseif ($id == "podcasts") {
        foreach ($GLOBALS['podcasts'] as $id => $podcast) {
          $media = new StdClass();
          $media->id = $podcast->id;
          $media->itemType = "container";
          $media->displayType = "";
          $media->title = $podcast->title;
          $media->albumArtURI = $podcast->art;
          $mediaCollection[] = $media;
        }
      } else {
        $podcast = $GLOBALS['podcasts'][$id];
        if ($podcast) {
          foreach ($podcast->episodes as $episode) {
            $media = new StdClass();
            $media->id = $episode->id;
            $media->displayType = "";
            $media->mimeType = "audio/mp3";
            $media->itemType = "show";
            $media->title = $episode->title;
            $media->summary = $episode->description;
            $media->trackMetadata->duration = $episode->duration;
            $media->trackMetadata->artist = $podcast->title;
            $media->trackMetadata->album = $podcast->title;
            $mediaMetadata[] = $media;
          }
          $media->trackMetadata = new StdClass();
        }
      }

      $response = new StdClass();
      $response->getMetadataResult = new StdClass();
      $response->getMetadataResult->index = 0;
      $response->getMetadataResult->count = count($mediaCollection) + count($mediaMetadata);
      $response->getMetadataResult->total = count($mediaCollection) + count($mediaMetadata);
      $response->getMetadataResult->mediaCollection = $mediaCollection;
      $response->getMetadataResult->mediaMetadata = $mediaMetadata;

      return $response;
    }
  }

  ini_set("soap.wsdl_cache_enabled", "0");
  $server = new SoapServer('Sonos.wsdl');
  $server->setClass('Sonos');
  $server->handle();
?>
