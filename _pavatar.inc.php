<?php

$_pavatar_use_pavatar = true;

$_pavatar_cache_dir;
$_pavatar_cache_file;

function _pavatar_getDefaultUrl()
{
  return 'http://www.pavatar.com/';
}

function _pavatar_getDirectUrl($url, & $exists)
{
  $sep = substr($url, -1, 1) == '/' ? '' : '/';
  $_url = $url . $sep . 'pavatar.png';

  $headers = get_headers($_url);

  $exists = (strstr($headers[0], '404') === FALSE);

  return $_url;
}

function _pavatar_getPavatarCode($url, $content = '')
{
  global $_pavatar_use_pavatar;

  $img = '<img src="' . _pavatar_getSrcFrom($url) . '" alt="" class="pavatar" />' . $content;
  return $_pavatar_use_pavatar ? $img : $content;
}

function _pavatar_getPavatarFrom($url)
{
  $_url = '';

  if ($url)
  {
    $headers = get_headers($url);
    $headerc = count((array) $headers);

    for ($i = 0; $i < $headerc; $i++)
    {
      $ci = strpos($headers[$i], ':');
      $headn = strtolower(substr($headers[$i], 0, $ci));
      $headv = ltrim(substr($headers[$i], $ci + 1));

      if ($headn == 'x-pavatar')
      {
        $_url = $headv;
        break;
      }
    }
  }
  else
    $_url = 'none';

  if (!$_url)
  {
    $dom = new DOMDocument();
    $dom->loadHTML(file_get_contents($url));
    $links = $dom->getElementsByTagName('link');

    for ($i = 0; $i < $links->length; $i++)
    {
      if (stristr($links->item($i)->getAttribute('rel'), 'pavatar'))
        $_url = $links->item($i)->getAttribute('href');
    }
  }

  if (!$_url)
  {
    $_url = _pavatar_getDirectUrl($url, &$exists);

    if (!$exists)
    {
      $urlp = parse_url($url);
      if ($urlp['port'])
        $port = ':' . $urlp['port'];

      $url = $urlp['scheme'] . '://' . $urlp['host'] . $port;
      $_url = _pavatar_getDirectUrl($url, &$exists);

      if (!$exists)
        $_url = _pavatar_getSrcFrom(_pavatar_getDefaultUrl());
    }
  }

  return $_url;
}

function _pavatar_getSrcFrom($url)
{
  global $_pavatar_cache_dir, $_pavatar_cache_file, $_pavatar_use_pavatar;

  $_pavatar_cache_dir = dirname(__FILE__) . '/cache';
  $_pavatar_cache_file = $_pavatar_cache_dir . '/' . rawurlencode($url);

  if (!is_dir($_pavatar_cache_dir))
    @mkdir($_pavatar_cache_dir);

  $image = '';

  if (!file_exists($_pavatar_cache_file))
  {
    $image = _pavatar_getPavatarFrom($url);
    $c = @file_get_contents($image);
    $i = "$_pavatar_mime_type;base64," . base64_encode($c);

    $f = @fopen($_pavatar_cache_file, 'w');
    @fwrite($f, $i);
    @fclose($f);
  }

  if (file_exists($_pavatar_cache_file) &&
    !strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) // IE doesn't support data: URIs
  {
    $s = file_get_contents($_pavatar_cache_file);
    $ret = "data:$s";

    if (!$s)
      $image = _pavatar_getDefaultUrl();
  }
  else
  {
    $ret = _pavatar_getSrcFrom($url);
    $image = $ret;
  }

  $_pavatar_use_pavatar = strtolower($image) != 'none';

  return $ret;
}

?>
