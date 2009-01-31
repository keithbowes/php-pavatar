<?php

$_pavatar_use_pavatar = true;

$_pavatar_cache_dir;
$_pavatar_cache_file;

$_pavatar_mime_type;

$_pavatar_is_ie = strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE');

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

function _pavatar_getMimeType($s)
{
  $_pavatar_mime_type = '';

  if (strstr($s, 'PNG'))
    $_pavatar_mime_type = 'image/png';
  else if (strstr($s, 'JFIF'))
    $_pavatar_mime_type = 'image/jpeg';
  else if (strstr($s, 'GIF'))
    $_pavatar_mime_type = 'image/gif';

  return $_pavatar_mime_type;
}

function _pavatar_getPavatarCode($url, $content = '')
{
  global $_pavatar_is_ie, $_pavatar_mime_type, $_pavatar_use_pavatar;

  if (!$_pavatar_is_ie)
    $img = '<object data="' . _pavatar_getSrcFrom($url) . '" type="' . $_pavatar_mime_type . '" alt="" class="pavatar">' . '</object>' . "\n" . $content;
  else
    $img = '<img src="' . _pavatar_getSrcFrom($url) . '" alt="" class="pavatar" />' . $content;

  return $_pavatar_use_pavatar ? $img : $content;
}

function _pavatar_getPavatarFrom($url)
{
  global $_pavatar_mime_type;

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

      if ($headn == 'x-pavatar' && $_pavatar_mime_type)
      {
        $_url = $headv;
        break;
      }
      else if ($headn == 'content-type')
        $_pavatar_mime_type = $headv;
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
  global $_pavatar_cache_dir, $_pavatar_cache_file,
    $_pavatar_is_ie, $_pavatar_mime_type, $_pavatar_use_pavatar;

  $_pavatar_cache_dir = dirname(__FILE__) . '/cache';
  $_pavatar_cache_file = $_pavatar_cache_dir . '/' . rawurlencode($url);

  if (!is_dir($_pavatar_cache_dir))
    @mkdir($_pavatar_cache_dir);

  $image = '';

  if (!file_exists($_pavatar_cache_file))
  {
    $image = _pavatar_getPavatarFrom($url);
    switch ($_pavatar_mime_type)
    {
      case 'image/gif':
      case 'image/jpeg':
      case 'image/png':
        $c = @file_get_contents($image);
        break;
      default:
        $c = (!$_pavatar_is_ie) ? "$_pavatar_mime_type;base64," . base64_encode(@file_get_contents($image)) : $image;
    }

    $f = @fopen($_pavatar_cache_file, 'w');
    @fwrite($f, $c);
    @fclose($f);
  }

  if (file_exists($_pavatar_cache_file))
  {
    $s = file_get_contents($_pavatar_cache_file);

    if ($_pavatar_mime_type = _pavatar_getMimeType($s))
      $ret = $_pavatar_cache_file;
    else if (base64_decode($s) !== FALSE) // Older versions used base64-encoded data URLs
    {
      $_pavatar_mime_type = substr($s, 0, strpos($s, ';'));
      if (!$_pavatar_mime_type)
        $_pavatar_mime_type = 'image/png';

      $ret="data:$s";
    }
    else
      $ret = _pavatar_getPavatarFrom($s);

    if (!$s)
      $ret = _pavatar_getDefaultUrl();
  }

  $_pavatar_use_pavatar = strtolower($ret) != 'none';

  return $ret;
}

?>
