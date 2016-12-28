<?php
  $version = 0;

  $media = array();
  $podcasts = array("podcast:1", "podcast:2", "podcast:3");

  $podcast = new StdClass();
  $podcast->id = "podcast:1";
  $podcast->title = "Accidental Tech Podcast";
  $podcast->art = "https://d1eedt7bo0oujw.cloudfront.net/art?s=3fba7cd33ee1b897707efaf08b335c6425ec5907e491ca2eb07ecd32a51590dd&w=840&u=http%3A%2F%2Fstatic1.squarespace.com%2Fstatic%2F513abd71e4b0fe58c655c105%2Ft%2F52c45a37e4b0a77a5034aa84%2F1388599866232%2F1500w%2FArtwork.jpg";
  $podcast->episodes = array("podcast:1:episode:1");
  $media[$podcast->id] = $podcast;

  $podcast = new StdClass();
  $podcast->id = "podcast:2";
  $podcast->title = "Freakonomics Radio";
  $podcast->art = "https://d1eedt7bo0oujw.cloudfront.net/art?s=d8dafd7577b4a1a999b39be6c2f8f778c055c01fc3cd0abad8ee20b065cebe0b&w=840&u=https%3A%2F%2Fmedia2.wnyc.org%2Fi%2F1400%2F1400%2Fl%2F80%2F1%2Fwn16_wnycstudios_freakonomics-rev3.png";
  $podcast->episodes = array();
  $media[$podcast->id] = $podcast;

  $podcast = new StdClass();
  $podcast->id = "podcast:3";
  $podcast->title = "The Talk Show With John Gruber";
  $podcast->art = "https://d1eedt7bo0oujw.cloudfront.net/art?s=3333f3dd58c4e3398ecb18146945197ffc6d7f4f2bf67aeffe924df16d4cf6a8&w=840&u=http%3A%2F%2Fdaringfireball.net%2Fthetalkshow%2Fgraphics%2Fdf-the-talk-show-album-art.png";
  $podcast->episodes = array("podcast:3:episode:1");
  $media[$podcast->id] = $podcast;

  $episode = new StdClass();
  $episode->id = "podcast:1:episode:1";
  $episode->title = "201: Volume Micromanager";
  $episode->description = "Super Mario Run, AirPods, tabs vs. apps, and Cook on desktops.";
  $episode->duration = 7741;
  $episode->url = "http://traffic.libsyn.com/atpfm/atp201.mp3";
  $media[$episode->id] = $episode;

  $episode = new StdClass();
  $episode->id = "podcast:3:episode:1";
  $episode->title = "Ep. 176, With Special Guest Craig Hockenberry";
  $episode->description = "";
  $episode->duration = 7347;
  $episode->url = "http://feeds.soundcloud.com/stream/298316636-thetalkshow-176-craig-hockenberry.mp3";
  $media[$episode->id] = $episode;

  class Sonos {
    function getLastUpdate() {
      $response = new StdClass();
      $response->getLastUpdateResult = new StdClass();
      $response->getLastUpdateResult->favorites = $GLOBALS['version'];
      $response->getLastUpdateResult->catalog = $GLOBALS['version'];
      $response->getLastUpdateResult->pollInterval = 10;
      return $response;
    }

    function getMetadata($params) {
      $count = $params->count;
      $id = $params->id;
      $index = $params->index;
      $recursive = $params->recursive;

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
        foreach ($GLOBALS['podcasts'] as $id) {
          $mediaCollection[] = $this->findPodcastMediaMetadata($id);
        }
      } else {
        $podcast = $GLOBALS['media'][$id];
        foreach ($podcast->episodes as $id) {
          $mediaMetadata[] = $this->findEpisodeMediaMetadata($id);
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

    function getMediaMetadata($params) {
      $id = $params->id;

      $response = new StdClass();
      $response->getMediaMetadataResult = $this->findEpisodeMediaMetadata($id);
      return $response;
    }

    function getMediaURI($params) {
      $id = $params->id;
      $episode = $GLOBALS['media'][$id];

      $response = new StdClass();
      $response->getMediaURIResult = $episode->url;
      return $response;
    }

    function findPodcastMediaMetadata($id) {
      $podcast = $GLOBALS['media'][$id];
      $media = new StdClass();
      $media->id = $podcast->id;
      $media->itemType = "container";
      $media->displayType = "";
      $media->title = $podcast->title;
      $media->albumArtURI = $podcast->art;
      return $media;
    }

    function findEpisodeMediaMetadata($id) {
      $episode = $GLOBALS['media'][$id];
      $media = new StdClass();
      $media->id = $episode->id;
      $media->displayType = "";
      $media->mimeType = "audio/mp3";
      $media->itemType = "track";
      $media->title = $episode->title;
      $media->summary = $episode->description;
      $media->trackMetadata = new StdClass();
      $media->trackMetadata->canPlay = true;
      $media->trackMetadata->duration = $episode->duration;
      $media->trackMetadata->artist = $podcast->title;
      $media->trackMetadata->album = $podcast->title;
      return $media;
    }
  }

  ini_set("soap.wsdl_cache_enabled", "0");
  $server = new SoapServer('Sonos.wsdl');
  $server->setClass('Sonos');
  $server->handle();
?>
