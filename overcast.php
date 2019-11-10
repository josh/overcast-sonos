<?php
$memcache = new Memcached();
$memcache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

if (getenv('MEMCACHIER_USERNAME') && getenv('MEMCACHIER_PASSWORD')) {
  $memcache->setSaslAuthData(
    getenv('MEMCACHIER_USERNAME'),
    getenv('MEMCACHIER_PASSWORD')
  );
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

function encrypt($data, $key)
{
  $iv = openssl_random_pseudo_bytes(16);
  return $iv . ":" . openssl_encrypt($data, 'AES-256-CBC', $key, true, $iv);
}

function decrypt($data, $key)
{
  $iv = substr($data, 0, 16);
  return openssl_decrypt(substr($data, 17), 'AES-256-CBC', $key, true, $iv);
}

class Podcast
{
  public $id;
  public $title;
  public $imageURL;
  public $episodeIDs;
  public $episodeDurations;
}

class Episode
{
  public $id;
  public $number;
  public $podcastId;
  public $podcastTitle;
  public $title;
  public $description;
  public $date;
  public $imageURL;
  public $duration;
  public $mimeType;
  public $url;
  public $itemId;
  public $speedId;
}

function fetch($url, $token = null)
{
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);

  if ($token) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: o=$token"]);
  }

  $body = curl_exec($ch);

  curl_close($ch);

  return $body;
}

function getAccountLastUpdate($token)
{
  return sha1(serialize(fetchAccount($token)));
}

function fetchAccount($token)
{
  global $memcache;

  $key = "overcast:fetchAccount:v3:" . sha1($token);
  $data = $memcache->get($key);
  if ($data) {
    return unserialize(decrypt($data, $token));
  }

  $body = fetch("https://overcast.fm/podcasts", $token);

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

  $memcache->set($key, encrypt(serialize($result), $token), time() + 150);

  return $result;
}

function invalidateAccountCache($token)
{
  global $memcache;

  $key = "overcast:fetchAccount:v3:" . sha1($token);
  $memcache->delete($key);
}

function fetchPodcast($id)
{
  global $memcache;

  if (substr($id, 0, 1) == '+') {
    throw new Exception("invalid podcast id");
  }

  $key = "overcast:fetchPodcast:v4:$id";
  $data = $memcache->get($key);
  if ($data) {
    return unserialize($data);
  }

  $body = fetch("https://overcast.fm/" . $id);

  preg_match('/extendedepisodecell/', $body, $matches);
  if (!isset($matches[0])) {
    $memcache->set($key, serialize(null), time() + 86400);
    return null;
  }

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
  foreach (
    $xpath->query('//a[contains(@class, "extendedepisodecell")]')
    as $a
  ) {
    $id = substr($a->getAttribute('href'), 1);
    $podcast->episodeIDs[] = $id;

    $caption = $xpath->query('.//div[@class="caption2 singleline"]', $a)[0]
      ->textContent;
    preg_match('/(\d+) min/', $caption, $matches);
    if (isset($matches[0])) {
      $podcast->episodeDurations[$id] = ((int) $matches[1] * 60);
    }
  }

  $memcache->set($key, serialize($podcast), time() + 86400);

  return $podcast;
}

function fetchEpisode($id)
{
  global $memcache;

  $key = "overcast:fetchEpisode:v7:$id";
  $data = $memcache->get($key);
  if ($data) {
    return unserialize($data);
  }

  $body = fetch("https://overcast.fm/" . $id);

  preg_match('/audioplayer/', $body, $matches);
  if (!isset($matches[0])) {
    $memcache->set($key, serialize(null));
    return null;
  }

  libxml_use_internal_errors(true);

  $dom = new DOMDocument();
  $dom->loadHTML($body);

  $xpath = new DOMXpath($dom);

  $episode = new Episode();
  $episode->id = $id;

  $episode->podcastId = substr(
    $xpath->query('//div[@class="centertext"]/h3/a')[0]->getAttribute('href'),
    1
  );

  $podcast = fetchPodcast($episode->podcastId);

  if (empty($podcast->episodeDurations[$id])) {
    $memcache->delete("overcast:fetchPodcast:v4:" . $episode->podcastId);
    $podcast = fetchPodcast($episode->podcastId);
  }

  if (isset($podcast->episodeDurations[$id])) {
    $episode->duration = $podcast->episodeDurations[$id];
  }
  $episode->podcastTitle = $podcast->title;

  $episode->title = $xpath->query(
    '//div[@class="centertext"]/h2'
  )[0]->textContent;
  $episode->description = $xpath
    ->query('//meta[@name="og:description"]')[0]
    ->getAttribute('content');

  $dateEl = $xpath->query('//div[@class="centertext"]/div');
  if (isset($dateEl[0])) {
    $episode->date = strftime('%Y-%m-%d', strtotime($dateEl[0]->textContent));
  }

  preg_match('/^#?(\d+)\s*(:|-|–|—)?\s+/', $episode->title, $matches);
  if (isset($matches[0])) {
    $episode->title = substr($episode->title, strlen($matches[0]));
    $episode->number = (int) $matches[1];
  }

  $url = $xpath->query('//meta[@name="og:image"]')[0]->getAttribute('content');
  $params = array();
  parse_str(parse_url($url, PHP_URL_QUERY), $params);
  $episode->imageURL = $params['u'];

  $audio = $xpath->query('//audio')[0];
  $episode->itemId = $audio->getAttribute('data-item-id');
  $episode->speedId = $audio->getAttribute('data-speed-id');

  $source = $xpath->query('//audio/source')[0];
  $episode->mimeType = $source->getAttribute('type');
  $episode->url = $source->getAttribute('src');

  $memcache->set($key, serialize($episode));

  return $episode;
}

function addEpisode($token, $id)
{
  updateEpisodeProgress($token, $id, 0);
  invalidateAccountCache($token);
}

function deleteEpisode($token, $id)
{
  $episode = fetchEpisode($id);
  fetch("https://overcast.fm/podcasts/delete_item/" . $episode->itemId, $token);
  invalidateAccountCache($token);
}

function fetchEpisodeProgress($token, $id)
{
  global $memcache;

  $body = fetch("https://overcast.fm/" . $id, $token);

  libxml_use_internal_errors(true);

  $dom = new DOMDocument();
  $dom->loadHTML($body);

  $xpath = new DOMXpath($dom);

  $audio = $xpath->query('//audio')[0];

  $itemId = $audio->getAttribute('data-item-id');
  $speedId = (int) $audio->getAttribute('data-speed-id');
  $version = (int) $audio->getAttribute('data-sync-version');
  $position = (int) $audio->getAttribute('data-start-time');

  $progress = new StdClass();
  $progress->itemId = $itemId;
  $progress->speedId = $speedId;
  $progress->version = $version;
  $progress->position = $position;

  $key = "overcast:fetchEpisodeProgress:" . sha1("$token:$id");
  $memcache->set($key, encrypt(serialize($progress), $token), time() + 3600);

  return $progress;
}

function updateEpisodeProgress($token, $id, $position)
{
  global $memcache;

  $episode = fetchEpisode($id);

  if (isset($episode->duration) && $position >= $episode->duration) {
    $position = 2147483647;
    invalidateAccountCache($token);
  }

  $key = "overcast:fetchEpisodeProgress:" . sha1("$token:$id");
  $rawProgress = $memcache->get($key);
  $progress = $rawProgress
    ? unserialize(decrypt($rawProgress, $token))
    : fetchEpisodeProgress($token, $id);

  $ch = curl_init();

  curl_setopt(
    $ch,
    CURLOPT_URL,
    "https://overcast.fm/podcasts/set_progress/" . $episode->itemId
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: o=$token"]);
  curl_setopt($ch, CURLOPT_POST, 2);
  curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    http_build_query(array(
      'speed' => '' . $progress->speedId,
      'v' => '' . $progress->version,
      'p' => '' . $position
    ))
  );
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);

  $version = curl_exec($ch);

  curl_close($ch);

  $progress->version = (int) $version;
  $progress->position = (int) $position;

  $key = "overcast:fetchEpisodeProgress:" . sha1("$token:$id");
  $memcache->set($key, encrypt(serialize($progress), $token), time() + 3600);
}

function login($email, $password)
{
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, "https://overcast.fm/login");
  curl_setopt($ch, CURLOPT_POST, 2);
  curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    http_build_query(array(
      'email' => $email,
      'password' => $password
    ))
  );
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $response = curl_exec($ch);

  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $header = substr($response, 0, $header_size);

  curl_close($ch);

  preg_match('/Set-Cookie: o=([^;]+);/', $header, $matches);
  if (isset($matches[1])) {
    return $matches[1];
  } else {
    return null;
  }
}

function followRedirects($url)
{
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_NOBODY, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  curl_exec($ch);

  $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

  curl_close($ch);

  return $url;
}
?>
