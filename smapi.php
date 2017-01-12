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
      $this->sessionId = $params->sessionId;
    }

    function getLastUpdate() {
      $response = new StdClass();
      $response->getLastUpdateResult = new StdClass();
      $response->getLastUpdateResult->favorites = 0;
      $response->getLastUpdateResult->catalog = time();
      $response->getLastUpdateResult->pollInterval = 30;
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
      $media->trackMetadata->albumArtURI = $episode->imageURL;
      // $media->trackMetadata->duration = $episode->duration;
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
