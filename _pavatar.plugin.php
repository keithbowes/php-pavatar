<?php

include '_pavatar.inc.php';

class pavatar_plugin extends Plugin
{
	var $code = 'b2evPava';

	var $author = 'http://kechjo.cogia.net/';
	var $help_url = 'http://pavatar.sourceforge.net/';
	var $name = 'Pavatar';

	/* Rendering settings for b2evolution 4 */
	var $apply_rendering = 'always';
	var $group = 'rendering';
	var $number_of_installs = 1;

	function PluginInit(& $params)
	{
		global $Settings;
		$Settings->set('allow_avatars', false);

		global $app_name, $app_version, $baseurl, $cache_subdir,
			$default_avatar, $_pavatar_base_offset, $_pavatar_cache_dir,
			$_pavatar_use_gravatar,
			$_pavatar_version, $_pavatar_ui_name, $_pavatar_ui_version;
		$_pavatar_base_offset = $baseurl;

		if (is_dir($cache_subdir . 'plugins'))
			$_pavatar_cache_dir = $cache_subdir . 'plugins/pavatar';

		if ($params['is_installed'])
			$_pavatar_use_gravatar = $this->Settings->get('use_gravatar');

		_pavatar_init();
		$this->version = $_pavatar_version;
		$_pavatar_ui_name = $app_name;
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

		/* Get around an HTML-correction bug in b2evolution 5+ */
		global $app_name, $app_version, $_pavatar_is_ie;
		if (!$_pavatar_is_ie &&
			('b2evolution' == $app_name && version_compare($app_version, '5.0') >= 0))
		{
			static $pid = 0;
			$pid++;

			$map_id = 'id="pavatar' . $pid . '"';
			$usemap = 'pavatar' . $pid;

			$content = preg_replace('|^(<a[^>]+)(>)(<object[^>]+)(>)(</object>)(</a>)|', '$3 usemap="' . $usemap . '" title="&#160;"$4<map ' . $map_id . '><div>$1 shape="rect" coords="0, 0, 80, 80"$2$6</div></map>$5', $content);
		}
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

	/* Rendering settings for b2evolution 5 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge($params,
			array (
				'default_comment_rendering' => 'stealth',
				'default_post_rendering' => 'always',
			)
		);
		return parent::get_coll_setting_definitions($default_params);
	}
}

?>
