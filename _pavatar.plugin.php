<?php

include '_pavatar.inc.php';

class pavatar_plugin extends Plugin
{
  var $code = 'b2evPava';

  var $author = 'http://kechjo.cogia.net/';
  var $help_url = 'http://pavatar.sourceforge.net/';
  var $name = 'Pavatar';

  var $apply_rendering = 'always';
  var $group = 'rendering';

  function PluginInit(& $params)
  {
		global $Settings;
		$Settings->set('allow_avatars', false);

    global $app_version, $baseurl, $default_avatar, $_pavatar_base_offset,
			$_pavatar_cache_dir, $_pavatar_use_gravatar, $_pavatar_version,
			$_pavatar_ui_name, $_pavatar_ui_version;
		$_pavatar_base_offset = $baseurl;

		if (is_dir('cache/plugins'))
			$_pavatar_cache_dir = 'cache/plugins/pavatar';

		if ($params['is_installed'])
			$_pavatar_use_gravatar = $this->Settings->get('use_gravatar');

    _pavatar_init();
    $this->version = $_pavatar_version;
    $_pavatar_ui_name = 'b2evolution';
    $_pavatar_ui_version = $app_version;

    $this->short_desc = $this->T_('Implements Pavatar support.');
    $this->long_desc = $this->T_('Displays Pavatars in your entries and comments without having to mess around with PHP.');
  }

  function DisplayItemAsHtml(& $params)
  {
    global $_pavatar_email;

    $content =& $params['data'];
    $item = $params['Item'];

    $url = $item->get_creator_User()->url;

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

    $content = _pavatar_getPavatarCode($url, $content);
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
