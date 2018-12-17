<?php
ini_set('display_errors', 0);

include_once 'overcast.php';
include_once 'sonos.php';

switch ($_GET['method']) {
  case 'login':
    echo json_encode(login($_GET['email'], $_GET['password']));
    break;
  case 'fetchAccount':
    echo json_encode(fetchAccount($_GET['token']));
    break;
  case 'fetchPodcast':
    echo json_encode(fetchPodcast($_GET['id']));
    break;
  case 'fetchEpisode':
    echo json_encode(fetchEpisode($_GET['id']));
    break;
  case 'getLastUpdate':
    $params = new StdClass();
    $params->sessionId = $_GET['sessionId'];
    $sonos = new Sonos($params);
    $sonos->credentials($params);
    echo json_encode($sonos->getLastUpdate($params));
    break;
  case 'getMetadata':
    $params = new StdClass();
    $params->sessionId = $_GET['sessionId'];
    $params->count = intval($_GET['count']);
    $params->id = $_GET['id'];
    $params->index = intval($_GET['index']);
    $sonos = new Sonos($params);
    $sonos->credentials($params);
    echo json_encode($sonos->getMetadata($params));
    break;
  default:
    http_response_code(400);
}
?>
