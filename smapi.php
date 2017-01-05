<?php
  include 'overcast.php';

  $version = microtime();

  $podcasts = array();
  $media = array();

  foreach (fetchPodcasts() as $podcast) {
    $podcasts[] = $podcast;
    $media[$podcast->id] = $podcast;
    foreach ($podcast->episodes as $episode) {
      $media[$episode->id] = $episode;
    }
  }

  class Sonos {
    function getLastUpdate() {
      $response = new StdClass();
      $response->getLastUpdateResult = new StdClass();
      $response->getLastUpdateResult->favorites = microtime();
      $response->getLastUpdateResult->catalog = microtime();
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
        $media->id = "active";
        $media->itemType = "container";
        $media->displayType = "";
        $media->title = "All Active Episodes";
        $mediaCollection[] = $media;

        $media = new StdClass();
        $media->id = "podcasts";
        $media->itemType = "container";
        $media->displayType = "";
        $media->title = "Podcasts";
        $mediaCollection[] = $media;
      } elseif ($id == "active") {

      } elseif ($id == "podcasts") {
        foreach ($GLOBALS['podcasts'] as $podcast) {
          $mediaCollection[] = $this->findPodcastMediaMetadata($podcast->id);
        }
      } else {
        $podcast = $GLOBALS['media'][$id];
        foreach ($podcast->episodes as $episode) {
          $mediaMetadata[] = $this->findEpisodeMediaMetadata($episode->id);
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
      $response->getMediaURIResult = fetchEpisodeUrl($episode->url);
      return $response;
    }

    function findPodcastMediaMetadata($id) {
      $podcast = $GLOBALS['media'][$id];
      $media = new StdClass();
      $media->id = $podcast->id;
      $media->itemType = "container";
      $media->displayType = "";
      $media->title = $podcast->title;
      $media->albumArtURI = $podcast->image_url;
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
      $media->summary = ""; // $episode->description;
      $media->trackMetadata = new StdClass();
      $media->trackMetadata->canPlay = true;
      // $media->trackMetadata->duration = $episode->duration;
      $media->trackMetadata->artist = $episode->podcast->title;
      $media->trackMetadata->album = $episode->podcast->title;
      return $media;
    }
  }

  ini_set("soap.wsdl_cache_enabled", "0");
  $server = new SoapServer('Sonos.wsdl');
  $server->setClass('Sonos');
  $server->handle();
?>
