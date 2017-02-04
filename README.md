# Unofficial Overcast + Sonos integration

Listen to your Overcast podcasts on Sonos.

Just want to try it out? [Follow these setup instructions to register the unofficial Overcast service on your Sonos system](http://overcast-sonos.herokuapp.com/setup.php).

## Questions and Answers

#### Is this an official Overcast service?

No.

#### Do I have to run this thing on my computer?

Unlike [AirSonos](http://airsonos.stephenwan.net/), you don't need to run any additional programs on your computer or network. A public instance of the service is already set up on Heroku.

#### Why can't I see my playlists?

This service is limited to the same functionality as the [overcast.fm website](https://overcast.fm/), which only supports the "All Active Episodes" playlist.

#### What does "registering a custom service" do?

[Sonos allows a unreviewed custom service to be registered for testing and development purposes](http://musicpartners.sonos.com/docs?q=node/134). Its similar to "sideloading" an iOS app via Xcode. Typically, all services are reviewed and registered with Sonos.

Keep in mind that only a one custom service can be registered per network. Because of these limitation, you can not use this Overcast service and the [NPR One service](https://michaeldick.me/sonos-nprone/) at the same time.

#### Why isn't there a nice Overcast logo in the Sonos app?

Only officially registered services can [set a custom service logo](http://musicpartners.sonos.com/node/377).
