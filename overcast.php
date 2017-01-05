<?php
  class Podcast {
    public $url;
    public $id;
    public $title;
    public $image_url;
    public $episodes = [];
  }

  class Episode {
    public $podcast;
    public $id;
    public $url;
    public $title;
    public $publishedAt;
  }

  function fetchPodcasts() {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://overcast.fm/podcasts");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: o=' . $_ENV['COOKIE']]);
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

    $podcasts = array();
    $podcasts_by_title = array();
    $feedcells = $xpath->query('//a[@class="feedcell"]');

    foreach ($feedcells as $feedcell) {
      $podcast = new Podcast();

      $podcast->id = $feedcell->getAttribute('href');
      $podcast->url = "https://overcast.fm" . $feedcell->getAttribute('href');

      $img = $xpath->query('.//img[@class="art"]', $feedcell);
      $cloudfront_url = $img[0]->getAttribute('src');
      $params = array();
      parse_str(parse_url($cloudfront_url, PHP_URL_QUERY), $params);
      $podcast->image_url = $params['u'];

      $div = $xpath->query('.//div[@class="title"]', $feedcell);
      $podcast->title = $div[0]->textContent;

      $podcasts[] = $podcast;
      $podcasts_by_title[$podcast->title] = $podcast;
    }

    $episodecells = $xpath->query('//a[@class="episodecell"]');

    foreach ($episodecells as $episodecell) {
      $episode = new Episode();

      $title = $xpath->query('.//div[@class="caption2 singleline"]', $episodecell)[0]->textContent;
      $episode->podcast = $podcasts_by_title[$title];
      $episode->podcast->episodes[] = $episode;

      $episode->title = $xpath->query('.//div[@class="title singleline"]', $episodecell)[0]->textContent;
      $episode->publishedAt = $xpath->query('.//div[@class="caption2 singleline"]', $episodecell)[0]->textContent;

      $episode->url = "https://overcast.fm" . $episodecell->getAttribute('href');
      $episode->id = $episodecell->getAttribute('href');
    }

    return $podcasts;
  }

  function fetchEpisodeUrl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: o=' . $_ENV['COOKIE']]);
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

    $source = $xpath->query('//audio/source')[0];
    // $source->getAttribute('type')

    return $source->getAttribute('src');
  }
?>
