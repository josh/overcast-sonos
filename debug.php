<?php
include_once 'overcast.php';
$url = isset($_GET['url']) ? $_GET['url'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="/css/bootstrap.css">
  <link rel="stylesheet" href="/css/site.css">
</head>
<body>
  <div class="container">
    <form method="GET" action="debug.php" class="input-group">
      <input type="text" class="form-control" name="url" value="<?= $url; ?>" placeholder="https://overcast.fm/itunes617416468/accidental-tech-podcast">
      <span class="input-group-btn">
        <button class="btn btn-default" type="submit">Submit</button>
      </span>
    </form>

    <?php if ($url): ?>
      <pre><?php
      $id = substr($url, strlen("https://overcast.fm/"));
      if (substr($id, 0, 1) == "+") {
        var_dump(fetchEpisode($id));
      } else {
        var_dump(fetchPodcast($id));
      }
      ?></pre>
    <?php endif; ?>
  </div>
</body>
