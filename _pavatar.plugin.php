<?php

include '_pavatar.inc.php';

class pavatar_plugin extends Plugin
{
  var $code = 'b2evPava';

  var $author = 'http://sourceforge.net/projects/pavatar';
  var $name = 'Pavatar';
  var $version = '0.3';

  var $apply_rendering = 'always';
  var $group = 'rendering';

  function PluginInit()
  {
    global $_pavatar_cache_dir;
 
    $this->shortdesc = $this->T_('');
    $this->longdesc = $this->T_('');

    $_pavatar_cache_dir = preg_replace('/^.*\/(plugins.+)$/', '$1', $this->get_plugin_url() . 'cache');
    if (!file_exists($_pavatar_cache_dir))
      @mkdir($_pavatar_cache_dir);
  }

  function RenderItemAsHtml(& $params)
  {
    $content =& $params['data'];
    $item =& $params['Item'];

    $url = $item->get_creator_User()->url;
    if (!$url)
      $url = _pavatar_getDefaultUrl();

    _pavatar_getPavatarCode($url, &$content);
  }

  function FilterCommentContent(& $params)
  {
    $content =& $params['data'];
    $comment = $params['Comment'];

    $url = $comment->author_url;
    if (!$url && $comment->get_author_user()) // if member
      $url = $comment->get_author_user()->url;

    if (!$url) $url = _pavatar_getDefaultUrl();

    _pavatar_getPavatarCode($url, &$content);
  }

  function AdminAfterMenuInit()
  {
    //$this->register_menu_entry($this->T_('Pavatar'));
  }
}

?>
