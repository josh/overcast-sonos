# Overcast Podcast Service for Sonos

## Shortcomings

* Only registered services can [set a custom service logo](http://musicpartners.sonos.com/node/377).
* Podcast artist isn't available, defaults to Podcast title.

## Questions and Answers

### Why isn't the Setup Guide served over SSL?

In order for the setup page to automatically configure a Sonos system on the local network, the web page needs to perform a cross origin POST to a non-SSL HTTP server. To avoid a mixed content error in some browsers, the setup page itself needs to match the protocol and also be HTTP.
