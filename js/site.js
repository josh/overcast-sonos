var configureForm = document.getElementById('configure-sonos');
var customSdForm = document.getElementById('customsd-form');
var iframe = document.getElementById('customsd');
var sonosIpField = document.getElementById('sonos-ip');
var curlExample = document.getElementById('curl-example');

configureForm.addEventListener('submit', function(event) {
  event.preventDefault();
  customSdForm.action = 'http://' + sonosIpField.value + ':1400/customsd';
  customSdForm.submit();
});

configureForm.addEventListener('input', function() {
  curlExample.value = curlExample.dataset.original.replace('$IP', event.target.value);
});

curlExample.addEventListener('click', function(event) {
  this.select();
})
