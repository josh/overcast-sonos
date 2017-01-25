<?php
  if ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $redirect = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $redirect);
    exit();
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="/css/bootstrap.css">
  <link rel="stylesheet" href="/css/site.css">
  <title>Overcast + Sonos</title>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Overcast + Sonos</h1>
      <p class="lead">Listen to your Overcast podcasts on Sonos.</p>
    </div>

    <hr class="featurette-divider">

    <div class="row featurette">
      <div class="col-md-7 col-md-push-5">
        <h2 class="featurette-heading">Find the IP address of your Sonos.</h2>
        <p class="lead">
          From the iOS Sonos App, go to <strong>Settings</strong> > <strong>About My Sonos System</strong>.
          <a href="https://sonos.custhelp.com/app/answers/detail/a_id/2626/">Check out this Sonos support article if you're on another platform.</a>
        </p>
      </div>
      <div class="col-md-5 col-md-pull-7">
        <img class="img-responsive center-block" src="images/sonos-ip.png">
      </div>
    </div>

    <hr class="featurette-divider">

    <div class="row featurette">
      <div class="col-md-7">
        <h2 class="featurette-heading">Configure customSD</h2>
        <p class="lead">
          Enter your Sonos IP here and click <strong>Register Service</strong>. The window on the right should say <code>success!</code> if the service was successfully installed.
        </p>

        <form id="configure-sonos" class="input-group">
          <input type="text" id="sonos-ip" name="ip" class="form-control" placeholder="10.0.1.1">
          <span class="input-group-btn">
            <button class="btn btn-default" type="submit">Register Service</button>
          </span>
        </form>
      </div>
      <div class="col-md-5">
        <iframe class="img-responsive center-block" id="customsd" name="customsd"></iframe>
      </div>
    </div>

    <?php
      $host = $_SERVER['HTTP_HOST'];
      if (preg_match('/^\d+\.\d+\.\d+\.\d+/', $host) === 1) {
        $httpOrigin = $httpsOrigin = "http://$host";
      } else {
        $httpOrigin = "http://$host";
        $httpsOrigin = "https://$host";
      }
    ?>
    <form id="customsd-form" method="POST" target="customsd" hidden>
      <input type="hidden" name="sid" value="255">
      <input type="hidden" name="name" value="Overcast">
      <input type="hidden" name="uri" value="<?= $httpOrigin ?>/smapi.php">
      <input type="hidden" name="secureUri" value="<?= $httpsOrigin ?>/smapi.php">
      <input type="hidden" name="pollInterval" value="30">
      <input type="hidden" name="authType" value="UserId">
      <input type="hidden" name="stringsVersion" value="1">
      <input type="hidden" name="stringsUri" value="<?= $httpOrigin ?>/strings.xml">
      <input type="hidden" name="presentationMapVersion" value="0">
      <input type="hidden" name="presentationMapUri" value="">
      <input type="hidden" name="containerType" value="SoundLab">
      <input type="hidden" name="caps" value="logging">
      <input type="hidden" name="caps" value="playbackLogging">
    </form>

    <hr class="featurette-divider">

    <div class="row featurette">
      <div class="col-md-7 col-md-push-5">
        <h2 class="featurette-heading">Add Music Service</h2>
        <p class="lead">
          From the iOS Sonos App, go to <strong>Add Music Services</strong> and find <strong>Overcast</strong> in the list. Log in with your <a href="https://overcast.fm/login">Overcast.fm</a> email address and password.
        </p>
      </div>
      <div class="col-md-5 col-md-pull-7">
        <img class="img-responsive center-block" src="images/overcast-service.png">
      </div>
    </div>
  </div>

  <script src="js/site.js" async></script>
</html>
