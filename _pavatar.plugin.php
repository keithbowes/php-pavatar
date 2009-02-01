<?php

include '_pavatar.inc.php';

class pavatar_plugin extends Plugin
{
  var $code = 'b2evPava';

  var $author = 'http://kechjo.cogia.net/';
  var $help_url = 'http://pavatar.sourceforge.net/';

  var $name = 'Pavatar';
  var $version = '0.3';

  var $apply_rendering = 'stealth';
  var $group = 'rendering';

  function PluginInit()
  {
    global $_pavatar_base_offset, $_pavatar_use_gravatar;
    $_pavatar_base_offset = '../../';
    $_pavatar_use_gravatar = $this->Settings->get('use_gravatar');

    $this->shortdesc = $this->T_('Implements Pavatar support.');
    $this->longdesc = $this->T_('Displays Pavatars in your entries and comments without having to mess around with PHP.');
  }

  function RenderItemAsHtml(& $params)
  {
    global $_pavatar_email;

    $content =& $params['data'];
    $item = $params['Item'];

    $url = $item->get_creator_User()->url;

    _pavatar_setCacheDir($url);

    $_pavatar_email = $item->get_creator_User()->email;

    $content = _pavatar_getPavatarCode($url, $content);
  }

  function FilterCommentContent(& $params)
  {
    global $_pavatar_email;

    $content =& $params['data'];
    $comment = $params['Comment'];

    $url = $comment->author_url;
    $_pavatar_email = $comment->author_email;

    if (!$url && $comment->get_author_user()) // if member
    {
      $url = $comment->get_author_user()->url;
      $_pavatar_email = $comment->get_author_user()->email;
    }

    _pavatar_setCacheDir($url);

    $content = _pavatar_getPavatarCode($url, $content);
  }

  function SkinEndHtmlBody(& $params)
  {
    _pavatar_cleanFiles();
  }

  function GetDefaultSettings(& $params)
  {
    /* Using a variable for conditional returns */
    $ret['use_gravatar'] = array(
        'label' => $this->T_('Use Gravatar: '),
        'type' => 'checkbox',
        'defaultvalue' => 0,
        'note' => $this->T_('for comment authors who don\'t have a Pavatar'));

    return $ret;
  }
}

?>
