<?php
  if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
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

    <?php
      $host = $_SERVER['HTTP_HOST'];
      if (preg_match('/^\d+\.\d+\.\d+\.\d+/', $host) === 1) {
        $httpOrigin = $httpsOrigin = "http://$host";
      } else {
        $httpOrigin = "http://$host";
        $httpsOrigin = "https://$host";
      }

      $data = array(
        'sid' => '255',
        'name' => 'Overcast',
        'uri' => "$httpOrigin/smapi.php",
        'secureUri' => "$httpsOrigin/smapi.php",
        'pollInterval' => '30',
        'authType' => 'UserId',
        'stringsVersion' => '1',
        'stringsUri' => "$httpOrigin/strings.xml",
        'presentationMapVersion' => '0',
        'presentationMapUri' => '',
        'containerType' => 'SoundLab',
        'caps' => ['logging', 'playbackLogging']
      )
    ?>

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
        <hr>

        <h4>Didn't work?</h4>
        <p>Alternatively you can run this <code>curl</code> command in the terminal.</p>
        <?php
          $encoded_data = http_build_query($data);
          $encoded_data = preg_replace('/%5B[0-9]+%5D/', '', $encoded_data);
          $cmd = "curl 'http://\$IP:1400/customsd' --data '$encoded_data'"
        ?>
        <p>$ <input type="text" readonly class="shell-example" id="curl-example" data-original="<?= htmlentities($cmd); ?>" value="<?= htmlentities($cmd); ?>"></p>
      </div>

      <div class="col-md-5">
        <iframe class="img-responsive center-block" id="customsd" name="customsd"></iframe>
      </div>
    </div>

    <form id="customsd-form" method="POST" target="customsd" xhidden>
      <?php foreach($data as $name => $values): ?>
        <?php if (is_array($values)): ?>
          <?php foreach($values as $value): ?>
            <input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
          <?php endforeach; ?>
        <?php else: ?>
          <input type="hidden" name="<?= $name ?>" value="<?= $values ?>">
        <?php endif; ?>
      <?php endforeach; ?>
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
