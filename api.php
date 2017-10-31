<?php
  include 'overcast.php';
  $id = isset($_GET['id']) ? $_GET['id'] : NULL;
  if (substr($id, 0, 1) == "+") {
    echo json_encode(fetchEpisode($id));
  } else {
    echo json_encode(fetchPodcast($id));
  }
?>
