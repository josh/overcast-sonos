<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
</head>
<body>
  <?php
    $host = $_SERVER['HTTP_HOST'];
    if (preg_match('/^\d+\.\d+\.\d+\.\d+/', $host) === 1) {
      $httpOrigin = $httpsOrigin = "http://$host";
    } else {
      $httpOrigin = "http://$host";
      $httpsOrigin = "https://$host";
    }
    ?>
  <form id="configure-sonos">
    Sonos IP
    <input id="sonos-ip" type="text" name="ip">
    <button type="submit">Submit</button>
  </form>

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
    <input type="hidden" name="caps" value="playbackLogging">
  </form>

  <iframe id="customsd" name="customsd"></iframe>

  <script>
    const iframe = document.getElementById('customsd');
    const sonosIpField = document.getElementById('sonos-ip');
    const customSdForm = document.getElementById('customsd-form');

    document.getElementById('sonos-ip').addEventListener('input', event => {
      iframe.src = `http://${sonosIpField.value}:1400/customsd.htm`;
    });

    document.getElementById('configure-sonos').addEventListener('submit', event => {
      event.preventDefault();
      customSdForm.action = `http://${sonosIpField.value}:1400/customsd`;
      customSdForm.submit();
    });
  </script>
</html>
