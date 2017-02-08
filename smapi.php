<?php
  include 'overcast.php';

  class Sonos {
    private $sessionId;

    function getSessionId($params) {
      error_log("SOAP getSessionId");

      $username = $params->username;
      $password = $params->password;

      $token = login($username, $password);
      if ($token) {
        $response = new StdClass();
        $response->getSessionIdResult = $token;
        return $response;
      } else {
        return new SoapFault("Client.LoginInvalid", "Overcast login failed");
      }
    }

    function credentials($params) {
      if (isset($params->sessionId)) {
        $this->sessionId = $params->sessionId;
      }
    }

    function getLastUpdate() {
      $start = microtime(true);

      $lastUpdate = getAccountLastUpdate($this->sessionId);

      $response = new StdClass();
      $response->getLastUpdateResult = new StdClass();
      $response->getLastUpdateResult->favorites = $lastUpdate;
      $response->getLastUpdateResult->catalog = $lastUpdate;
      $response->getLastUpdateResult->pollInterval = 300;

      $duration = microtime(true) - $start;
      error_log("SOAP getLastUpdate " . round($duration * 1000) . "ms");

      return $response;
    }

    function getMetadata($params) {
      $start = microtime(true);

      $count = $params->count;
      $id = $params->id;
      $index = $params->index;

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
        $episodeIDs = fetchAccount($this->sessionId)->episodeIDs;

        foreach ($episodeIDs as $episodeID) {
          $mediaMetadata[] = $this->findEpisodeMediaMetadata($episodeID);
        }
      } elseif ($id == "podcasts") {
        foreach (fetchAccount($this->sessionId)->podcastIDs as $podcastID) {
          $mediaCollection[] = $this->findPodcastMediaMetadata($podcastID);
        }
      } else {
        $podcast = fetchPodcast($id);
        $activeEpisodeIDs = fetchAccount($this->sessionId)->episodeIDs;

        foreach ($podcast->episodeIDs as $episodeID) {
          if (in_array($episodeID, $activeEpisodeIDs)) {
            $mediaMetadata[] = $this->findEpisodeMediaMetadata($episodeID);
          }
        }
      }

      $response = new StdClass();
      $response->getMetadataResult = new StdClass();
      $response->getMetadataResult->index = $index;
      $response->getMetadataResult->total = count($mediaCollection) + count($mediaMetadata);
      $response->getMetadataResult->count = min($count, $response->getMetadataResult->total);
      $response->getMetadataResult->mediaCollection = array_slice($mediaCollection, $index, $count);
      $response->getMetadataResult->mediaMetadata = array_slice($mediaMetadata, $index, $count);

      $duration = microtime(true) - $start;
      error_log("SOAP getMetadata " . round($duration * 1000) . "ms");

      return $response;
    }

    function getMediaMetadata($params) {
      $start = microtime(true);

      $id = $params->id;

      $response = new StdClass();
      $response->getMediaMetadataResult = $this->findEpisodeMediaMetadata($id);

      $duration = microtime(true) - $start;
      error_log("SOAP getMediaMetadata " . round($duration * 1000) . "ms");

      return $response;
    }

    function getMediaURI($params) {
      $start = microtime(true);

      $id = $params->id;

      $response = new StdClass();
      $response->getMediaURIResult = followRedirects(fetchEpisode($id)->url);

      $progress = fetchEpisodeProgress($this->sessionId, $id);
      if ($progress) {
        $response->positionInformation = new StdClass();
        $response->positionInformation->id = $id;
        $response->positionInformation->index = 0;
        $response->positionInformation->offsetMillis = $progress->position * 1000;
      }

      $duration = microtime(true) - $start;
      error_log("SOAP getMediaURI " . round($duration * 1000) . "ms");

      return $response;
    }

    function setPlayedSeconds($params) {
      $start = microtime(true);

      $id = $params->id;
      $offsetMillis = $params->offsetMillis;

      if ($offsetMillis) {
        $episode = fetchEpisode($id);
        $seconds = $offsetMillis / 1000;
        if ($seconds > $episode->duration - 3) {
          $seconds = 2147483647;
        }

        updateEpisodeProgress($this->sessionId, $id, $seconds);
      }

      $response = new StdClass();
      $response->setPlayedSecondsResult = new StdClass();

      $duration = microtime(true) - $start;
      error_log("SOAP setPlayedSeconds " . round($duration * 1000) . "ms");

      return $response;
    }

    function reportPlaySeconds($params) {
      $start = microtime(true);

      $id = $params->id;
      $offsetMillis = $params->offsetMillis;

      if ($offsetMillis) {
        updateEpisodeProgress($this->sessionId, $id, $offsetMillis / 1000);
      }

      $response = new StdClass();
      $response->reportPlaySecondsResult = new StdClass();
      $response->reportPlaySecondsResult->interval = 10;

      $duration = microtime(true) - $start;
      error_log("SOAP reportPlaySeconds " . round($duration * 1000) . "ms");

      return $response;
    }

    function reportPlayStatus($params) {
      $start = microtime(true);

      $id = $params->id;
      $offsetMillis = $params->offsetMillis;

      if ($offsetMillis) {
        updateEpisodeProgress($this->sessionId, $id, $offsetMillis / 1000);
      }

      $response = new StdClass();
      $response->reportPlayStatusResult = new StdClass();

      $duration = microtime(true) - $start;
      error_log("SOAP reportPlayStatus " . round($duration * 1000) . "ms");

      return $response;
    }

    function findPodcastMediaMetadata($id) {
      $podcast = fetchPodcast($id);
      $media = new StdClass();
      $media->id = $podcast->id;
      $media->itemType = "container";
      $media->displayType = "";
      $media->title = $podcast->title;
      $media->albumArtURI = $podcast->imageURL;
      return $media;
    }

    function findEpisodeMediaMetadata($id) {
      $episode = fetchEpisode($id);

      $media = new StdClass();
      $media->id = $episode->id;
      $media->displayType = "";
      $media->mimeType = $episode->mimeType;
      $media->itemType = "track";
      $media->title = $episode->title;
      $media->summary = "";
      $media->trackMetadata = new StdClass();
      $media->trackMetadata->canPlay = true;
      $media->trackMetadata->albumArtURI = $episode->imageURL;
      $media->trackMetadata->artistId = $episode->podcastId;
      $media->trackMetadata->artist = $episode->podcastTitle;
      $media->trackMetadata->albumId = $episode->podcastId;
      $media->trackMetadata->album = $episode->podcastTitle;

      if (isset($episode->duration)) {
        $media->trackMetadata->canResume = true;
        $media->trackMetadata->duration = $episode->duration;
      }

      return $media;
    }
  }

  ini_set("soap.wsdl_cache_enabled", "0");
  $server = new SoapServer('Sonos.wsdl');
  $server->setClass('Sonos');
  $server->handle();
?>
