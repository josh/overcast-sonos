<?php
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
  default:
    http_response_code(400);
}
?>
