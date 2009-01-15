<?php

class pavatar_plugin extends Plugin
{
  var $code = 'b2evPava';
  var $name = 'Pavatar';
  var $version = '0.1';

  var $apply_rendering = 'always';
  var $group = 'rendering';

  var $use_pavatar = true;

  var $cache_dir;
  var $cache_file;
  var $mime_type;

  function PluginInit()
  {
    $this->shortdesc = $this->T_('');
    $this->longdesc = $this->T_('');

    $this->cache_dir = preg_replace('/^.*\/(plugins.+)$/', '$1', $this->get_plugin_url() . 'cache');
    if (!file_exists($this->cache_dir))
      @mkdir($this->cache_dir);
  }

  private function getDefaultUrl()
  {
    return '';
  }

  function getDirectUrl($url, & $exists)
  {
    $sep = substr($url, -1, 1) == '/' ? '' : '/';
    $_url = $url . $sep . 'pavatar.png';
    $_url = str_replace(':/', '/', $_url);

    $headers = get_headers($_url);

    $exists = (strstr($headers[0], 404) === FALSE);

    return $_url;
  }

  private function getPavatarCode($url, & $content)
  {
    $img = '<img src="' . $this->getSrcFrom($url) . '" alt="" class="pavatar" />' . $content;
    if ($this->use_pavatar)
      $content = $img;
  }

  private function getPavatarFrom($url)
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
      $_url = $this->getDirectUrl($url, &$exists);

      if (!$exists)
      {
        $urlp = parse_url($url);
        $url = $urlp['scheme'] . '://' . $urlp['host'] . ':' . $urlp['port'];
        $_url = $this->getDirectUrl($url, &$exists);

        if (!$exists)
          $_url = 'none';
      }
    }

    return $_url;
  }

  private function getSrcFrom($url)
  {
    $this->cache_file = $this->cache_dir . '/' . rawurlencode($url);

    $image = '';

    if (!file_exists($this->cache_file))
    {
      $image = $this->getPavatarFrom($url);
      $c = @file_get_contents($image);
      $i = "$this->mime_type;base64," . base64_encode($c);

      $f = @fopen($this->cache_file, 'w');
      @fwrite($f, $i);
      @fclose($f);
    }

    if (file_exists($this->cache_file) &&
      !strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) // IE doesn't support data: URIs
    {
      $s = file_get_contents($this->cache_file);
      $ret = "data:$s";

      if (!$s)
        $image = 'none';
    }
    else
    {
      $ret = $this->getPavatarFrom($url);
      $image = $ret;
    }

    $this->use_pavatar = strtolower($image) != 'none';

    return $ret;
  }

  function RenderItemAsHtml(& $params)
  {
    $content =& $params['data'];
    $item =& $params['Item'];

    $url = $item->get_creator_User()->url;
    if (!$url)
      $url = $this->getDefaultUrl();

    $this->getPavatarCode($url, &$content);
  }

  function FilterCommentContent(& $params)
  {
    $content =& $params['data'];
    $comment = $params['Comment'];

    $url = $comment->author_url;
    if (!$url && $comment->get_author_user()) // if member
      $url = $comment->get_author_user()->url;

    if (!$url) $url = $this->getDefaultUrl();

    $this->getPavatarCode($url, &$content);
  }

  function AdminAfterMenuInit()
  {
    //$this->register_menu_entry($this->T_('Pavatar'));
  }
}

?>
