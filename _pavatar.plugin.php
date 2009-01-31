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
    global $_pavatar_base_offset;
    $_pavatar_base_offset = '../../';

    $this->shortdesc = $this->T_('Implements Pavatar support.');
    $this->longdesc = $this->T_('Displays Pavatars in your entries and comments without having to mess around with PHP.');
  }

  function RenderItemAsHtml(& $params)
  {
    $content =& $params['data'];
    $item = $params['Item'];

    $url = $item->get_creator_User()->url;
    if (!$url)
      $url = _pavatar_getDefaultUrl();

    _pavatar_setCacheDir($url);

    $content = _pavatar_getPavatarCode($url, $content);
  }

  function FilterCommentContent(& $params)
  {
    $content =& $params['data'];
    $comment = $params['Comment'];

    $url = $comment->author_url;
    if (!$url && $comment->get_author_user()) // if member
      $url = $comment->get_author_user()->url;

    if (!$url)
      $url = _pavatar_getDefaultUrl();

    _pavatar_setCacheDir($url);

    $content = _pavatar_getPavatarCode($url, $content);
  }

  function SkinEndHtmlBody(& $params)
  {
    _pavatar_cleanFiles();
  }

  function AdminAfterMenuInit()
  {
    //$this->register_menu_entry($this->T_('Pavatar'));
  }
}

?>
