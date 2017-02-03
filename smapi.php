<?php
  include 'overcast.php';

  class Sonos {
    private $sessionId;

    function getSessionId($params) {
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
      $lastUpdate = getAccountLastUpdate($this->sessionId);

      $response = new StdClass();
      $response->getLastUpdateResult = new StdClass();
      $response->getLastUpdateResult->favorites = $lastUpdate;
      $response->getLastUpdateResult->catalog = $lastUpdate;
      $response->getLastUpdateResult->pollInterval = 300;
      return $response;
    }

    function getMetadata($params) {
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
          $mediaMetadata[] = $this->findEpisodeMediaMetadata($episodeID, NULL);
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
            $mediaMetadata[] = $this->findEpisodeMediaMetadata($episodeID, $podcast);
          }
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
      $response->getMediaMetadataResult = $this->findEpisodeMediaMetadata($id, NULL);
      return $response;
    }

    function getMediaURI($params) {
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

      return $response;
    }

    function setPlayedSeconds($params) {
      $id = $params->id;
      $offsetMillis = $params->offsetMillis;

      if ($offsetMillis) {
        $episode = fetchEpisode($id);
        $podcast = fetchPodcast($episode->podcastId);

        $seconds = $offsetMillis / 1000;
        if ($seconds > $podcast->episodeDurations[$id] - 3) {
          $seconds = 2147483647;
        }

        updateEpisodeProgress($this->sessionId, $id, $seconds);
      }

      $response = new StdClass();
      $response->setPlayedSecondsResult = new StdClass();
      return $response;
    }

    function reportPlaySeconds($params) {
      $id = $params->id;
      $offsetMillis = $params->offsetMillis;

      if ($offsetMillis) {
        updateEpisodeProgress($this->sessionId, $id, $offsetMillis / 1000);
      }

      $response = new StdClass();
      $response->reportPlaySecondsResult = new StdClass();
      $response->reportPlaySecondsResult->interval = 10;
      return $response;
    }

    function reportPlayStatus($params) {
      $id = $params->id;
      $offsetMillis = $params->offsetMillis;

      if ($offsetMillis) {
        updateEpisodeProgress($this->sessionId, $id, $offsetMillis / 1000);
      }

      $response = new StdClass();
      $response->reportPlayStatusResult = new StdClass();
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

    function findEpisodeMediaMetadata($id, $podcast) {
      $episode = fetchEpisode($id);
      if (!$podcast) {
        $podcast = fetchPodcast($episode->podcastId);
      }

      $media = new StdClass();
      $media->id = $episode->id;
      $media->displayType = "";
      $media->mimeType = $episode->mimeType;
      $media->itemType = "track";
      $media->title = $episode->title;
      $media->summary = ""; // $episode->description;
      $media->trackMetadata = new StdClass();
      $media->trackMetadata->canPlay = true;
      $media->trackMetadata->canResume = true;
      $media->trackMetadata->albumArtURI = $episode->imageURL;
      $media->trackMetadata->duration = $podcast->episodeDurations[$id];
      $media->trackMetadata->artistId = $podcast->id;
      $media->trackMetadata->artist = $podcast->title;
      $media->trackMetadata->albumId = $podcast->id;
      $media->trackMetadata->album = $podcast->title;
      return $media;
    }
  }

  ini_set("soap.wsdl_cache_enabled", "0");
  $server = new SoapServer('Sonos.wsdl');
  $server->setClass('Sonos');
  $server->handle();
?>
