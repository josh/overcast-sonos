var configureForm = document.getElementById('configure-sonos');
var customSdForm = document.getElementById('customsd-form');
var iframe = document.getElementById('customsd');
var sonosIpField = document.getElementById('sonos-ip');

configureForm.addEventListener('submit', function(event) {
  event.preventDefault();
  customSdForm.action = 'http://' + sonosIpField.value + ':1400/customsd';
  customSdForm.submit();
});
