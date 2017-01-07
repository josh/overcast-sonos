<?php
  $memcache = new Memcached();
  $memcache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

  if (getenv('MEMCACHIER_USERNAME') && getenv('MEMCACHIER_PASSWORD')) {
    $memcache->setSaslAuthData(getenv('MEMCACHIER_USERNAME'), getenv('MEMCACHIER_PASSWORD'));
  }

  if (!$memcache->getServerList()) {
    if (getenv('MEMCACHIER_SERVERS')) {
      $servers = explode(',', getenv('MEMCACHIER_SERVERS'));
      for ($i = 0; $i < count($servers); $i++) {
        $servers[$i] = explode(':', $servers[$i]);
      }
      $memcache->addServers($servers);
    } else {
      $memcache->addServer('127.0.0.1', 11211);
    }
  }

  class Podcast {
    public $id;
    public $title;
    public $imageURL;
    public $episodeIDs;
  }

  class Episode {
    public $id;
    public $podcastId;
    public $title;
    public $description;
    public $imageURL;
    public $mimeType;
    public $url;
  }

  function fetch($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    $body = curl_exec($ch);

    curl_close($ch);

    return $body;
  }

  function fetchAccount($token) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://overcast.fm/podcasts");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: o=$token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);

    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    curl_close($ch);

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body);

    $xpath = new DOMXpath($dom);

    $result = new StdClass();
    $result->episodeIDs = array();
    $result->podcastIDs = array();

    foreach ($xpath->query('//a[@class="feedcell"]') as $cell) {
      $id = substr($cell->getAttribute('href'), 1);
      if ($id != 'uploads') {
        $result->podcastIDs[] = $id;
      }
    }

    foreach ($xpath->query('//a[@class="episodecell"]') as $cell) {
      $id = substr($cell->getAttribute('href'), 1);
      $result->episodeIDs[] = $id;
    }

    return $result;
  }

  function fetchPodcast($id) {
    $body = fetch("https://overcast.fm/" . $id);

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body);

    $xpath = new DOMXpath($dom);

    $podcast = new Podcast();
    $podcast->id = $id;

    $podcast->title = $xpath->query('//h2[@class="centertext"]')[0]->textContent;

    $url = $xpath->query('//img[@class="art fullart"]')[0]->getAttribute('src');
    $params = array();
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    $podcast->imageURL = $params['u'];

    $podcast->episodeIDs = [];
    foreach ($xpath->query('//a[@class="extendedepisodecell usernewepisode"]') as $a) {
      $podcast->episodeIDs[] = substr($a->getAttribute('href'), 1);
    }

    return $podcast;
  }

  function fetchEpisode($id) {
    $body = fetch("https://overcast.fm/" . $id);

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body);

    $xpath = new DOMXpath($dom);

    $episode = new Episode();
    $episode->id = $id;

    $episode->podcastId = substr($xpath->query('//a[@class="ocbutton"]')[0]->getAttribute('href'), 1);

    $episode->title = $xpath->query('//div[@class="title"]')[0]->textContent;
    $episode->description = $xpath->query('//meta[@name="og:description"]')[0]->getAttribute('content');

    $url = $xpath->query('//meta[@name="og:image"]')[0]->getAttribute('content');
    $params = array();
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    $episode->imageURL = $params['u'];

    $source = $xpath->query('//audio/source')[0];
    $episode->mimeType = $source->getAttribute('type');
    $episode->url = $source->getAttribute('src');

    return $episode;
  }

  function login($email, $password) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://overcast.fm/login");
    curl_setopt($ch, CURLOPT_POST, 2);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
      'email' => $email,
      'password' => $password
    )));
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);

    curl_close($ch);

    preg_match('/Set-Cookie: o=([^;]+);/', $header, $matches);
    return $matches[1];
  }
?>
