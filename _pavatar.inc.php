<?php

if (PHP_VERSION < 5)
  die("Sorry, PHP 5 or higher is required.");

$_pavatar_use_pavatar = true;

$_pavatar_cache_dir;
$_pavatar_cache_file;

$_pavatar_base_offset;

$_pavatar_mime_type;

$_pavatar_email;
$_pavatar_use_gravatar;

$_pavatar_is_ie;

$_pavatar_version;
$_pavatar_ui_name;
$_pavatar_ui_version;

function _pavatar_cleanFiles()
{
  global $_pavatar_cache_dir;
  $week_seconds = 7 * 24 * 60 * 60;

  if (! $_pavatar_cache_dir)
    _pavatar_init_cache();

  $files = scandir($_pavatar_cache_dir);
  $fc = count($files);

  for ($i = 0; $i < $fc; $i++)
  {
    $file = $_pavatar_cache_dir . '/' . $files[$i];
    $lm = filemtime($file); // Get the last-modified timestamp

    if (is_file($file) && $lm < time() - $week_seconds) // Older than a week
      unlink($file);
  }
}

function _pavatar_getDefaultPavatar()
{
  global $_pavatar_base_offset, $_pavatar_cache_dir, $_pavatar_email,
    $_pavatar_use_gravatar;
  $ret = $_pavatar_base_offset . $_pavatar_cache_dir . '/pavatar.png';
  if ($_pavatar_use_gravatar)
    $ret = 'http://www.gravatar.com/avatar/' . md5($_pavatar_email) . '?s=80&amp;d=' . rawurlencode($ret);

  return  $ret;
}

function _pavatar_getDirectUrl($url, & $exists)
{
  $sep = substr($url, -1, 1) == '/' ? '' : '/';
  $_url = $url . $sep . 'pavatar.png';

  $headers = get_headers($_url);

  $exists = (strstr($headers[0], '404') === FALSE);

  return $_url;
}

function _pavatar_getHeaders($url)
{
  $ret = NULL;

  $headers = @get_headers($url);
  $headerc = count((array) $headers);

  for ($i = 0; $i < $headerc; $i++)
  {
    $ci = strpos($headers[$i], ':');
    $headn = strtolower(substr($headers[$i], 0, $ci));
    $headv = ltrim(substr($headers[$i], $ci + 1));
    $ret[$headn] = $headv;
  }

  return $ret;
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

  _pavatar_init_cache($url);

  if ($url)
    $url = _pavatar_getSrcFrom($url);
  else
    $url = _pavatar_getDefaultPavatar();

  if (!$_pavatar_is_ie)
    $img = '<object data="' . $url . '" type="' . $_pavatar_mime_type . '" class="pavatar"></object>' . "\n" . $content;
  else
    $img = '<img src="' . $url . '" alt="" class="pavatar" />' . $content;

  return $_pavatar_use_pavatar ? $img : $content;
}

function _pavatar_getPavatarFrom($url)
{
  global $_pavatar_mime_type;

  $_url = '';

  if ($url)
  {
    $headers = _pavatar_getHeaders($url);
    $_url = @$headers['x-pavatar'];
  }

  if (!$_url && $url)
  {
    $dom = new DOMDocument();
    if (@$dom->loadHTML(_pavatar_getUrlContents($url)))
    {
      $links = $dom->getElementsByTagName('link');

      for ($i = 0; $i < $links->length; $i++)
      {
        if (stristr($links->item($i)->getAttribute('rel'), 'pavatar'))
          $_url = $links->item($i)->getAttribute('href');
      }
    }
  }

  if (!$_url && $url)
  {
    $_url = _pavatar_getDirectUrl($url, &$exists);

    if (!$exists)
    {
      $urlp = parse_url($url);
      if (isset($urlp['port']))
        $port = ':' . $urlp['port'];
      else
        $port = '';

      $url = $urlp['scheme'] . '://' . $urlp['host'] . $port;
      $_url = _pavatar_getDirectUrl($url, &$exists);
    }
  }

  return $_url;
}

function _pavatar_getSrcFrom($url)
{
  global $_pavatar_base_offset, $_pavatar_cache_dir,
    $_pavatar_cache_file, $_pavatar_mime_type,
    $_pavatar_use_pavatar;

  $image = '';
  $ret = '';

  if (!file_exists($_pavatar_cache_file))
  {
    $image = _pavatar_getPavatarFrom($url);
    $headers = _pavatar_getHeaders($image);
    $_pavatar_mime_type = @$headers['content-type'];

    switch ($_pavatar_mime_type)
    {
      case 'image/gif':
      case 'image/jpeg':
      case 'image/png':
        $c = _pavatar_getUrlContents($image);
        break;
      default:
        $c = $image;
    }

    $f = @fopen($_pavatar_cache_file, 'w');
    @fwrite($f, $c);
    @fclose($f);

    chown($_pavatar_cache_file, get_current_user());
    chmod($_pavatar_cache_file, 0755);
  }

  if (file_exists($_pavatar_cache_file))
  {
    $s = file_get_contents($_pavatar_cache_file);

    if ($_pavatar_mime_type = _pavatar_getMimeType($s))
      $ret = $_pavatar_base_offset . $_pavatar_cache_file;
    else if (base64_decode($s) !== FALSE) // Older versions used base64-encoded data URLs
    {
      $_pavatar_mime_type = substr($s, 0, strpos($s, ';'));
      $ret = ($s == $_pavatar_cache_dir . '/pavatar.png') ? $s : "data:$s";
    }
    else
      $ret = _pavatar_getPavatarFrom($s);
  }

  $_pavatar_use_pavatar = strtolower($ret) != 'none';

  return $ret;
}

function _pavatar_getUrlContents($url)
{
  global $_pavatar_version, $_pavatar_ui_name, $_pavatar_ui_version;

  $in_headers = true;
  $ret = '';

  $urlp = parse_url($url);
  if (empty($urlp['port']))
    $urlp['port'] = 80;

  $fh = fsockopen($urlp['host'], $urlp['port']);
  fwrite($fh, 'GET ' . $urlp['path'] . ' HTTP/1.1' . "\r\n");
  fwrite($fh, 'Host: ' . $urlp['host'] . "\r\n");
  fwrite($fh, 'User-Agent: PHP-Pavatar/' . $_pavatar_version . ' (' . php_uname('s') . ' ' . php_uname('r') . ') ' . $_pavatar_ui_name . '/' . $_pavatar_ui_version . "\r\n");
  fwrite($fh, "Connection: close\r\n");
  fwrite($fh, "\r\n");

  while (!feof($fh))
  {
    if ($in_headers || !trim($ret))
      $ret = '';

    $ret .= fgets($fh);

    if (!trim($ret))
      $in_headers = false;
  }

  fclose($fh);
  return $ret;
}

function _pavatar_init()
{
  global $_pavatar_cache_dir, $_pavatar_cache_dir;

  _pavatar_init_cache();
  $old_cache_dir = dirname(__FILE__) . '/cache';

  /* Convert a 0.2 cache into a 0.3 cache */
  if (is_dir($old_cache_dir))
  {
    $files = scandir($old_cache_dir);
    $filec = count($files);

    for ($i = 0; $i < $filec; $i++)
    {
      $file = $old_cache_dir . '/' . $files[$i];
      if (is_file($file) && rawurldecode($file) != $file)
        rename($file, $_pavatar_cache_dir . '/' . base64_encode(rawurldecode($file)));
    }

    rmdir($old_cache_dir);
  }

  $_pavatar_is_ie = strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE');
  _pavatar_setVersion();

  if (!file_exists($_pavatar_cache_dir . '/pavatar.png'))
    copy(dirname(__FILE__) . '/pavatar.png', $_pavatar_cache_dir . '/pavatar.png');
}

function _pavatar_init_cache($url='')
{
  global $_pavatar_cache_dir, $_pavatar_cache_file,
    $_pavatar_is_ie;

  $_pavatar_cache_dir = '_pavatar_cache';

  if (!is_dir($_pavatar_cache_dir))
  {
    @mkdir($_pavatar_cache_dir);
    chown($_pavatar_cache_dir, get_current_user());
  }

  if ($url)
    $_pavatar_cache_file = $_pavatar_cache_dir . '/' . base64_encode($url);
}

function _pavatar_setVersion()
{
  global $_pavatar_version;
  $_pavatar_version = '0.5';
}

?>
