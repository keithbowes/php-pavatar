<?php

include '_pavatar.inc.php';

class pavatar_plugin extends Plugin
{
	var $code = 'b2evPava';

	var $author = 'Keith Bowes';
	var $help_url = 'http://github.com/keithbowes/pavatar/';
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
			$_pavatar_use_gravatar, $_pavatar_use_legacy,
			$_pavatar_version, $_pavatar_ui_name, $_pavatar_ui_version;
		$_pavatar_base_offset = $baseurl;

		if (is_dir($cache_subdir . 'plugins'))
			$_pavatar_cache_dir = $cache_subdir . 'plugins/pavatar';

		if ($params['is_installed'])
		{
			$_pavatar_use_gravatar = $this->Settings->get('use_gravatar');
			$_pavatar_use_legacy = $this->Settings->get('use_legacy');
		}

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
		global $app_name, $app_version, $_pavatar_use_legacy;
		if (!$_pavatar_use_legacy &&
			('b2evolution' == $app_name && version_compare($app_version, '5.0') >= 0))
		{
			static $pid = 0;
			$pid++;

			$std = $this->Settings->get('std');
			switch ($std)
			{
				case 'html5':
					$itemelem = 'area';
					$map_id = 'name="pavatar' . $pid . '"';
					break;
				default:
					$itemelem = 'a';
					$map_id = 'id="pavatar' . $pid . '"';
			}

			switch ($std)
			{
				case 'xhtml11':
				case 'rdfa':
					$usemap = 'pavatar' . $pid;
					break;
				default:
					$usemap = '#pavatar' . $pid;
			}

			$content = preg_replace('|^<a([^>]+)>(<object[^>]+)(>)(</object>)</a>|', '$2 usemap="' . $usemap . '" title="&#160;"$3<map ' . $map_id . '><div><' . $itemelem . '$1 shape="rect" coords="0, 0, 80, 80"></' . $itemelem . '></div></map>$4', $content);
		}
	}

	function GetDefaultSettings(& $params)
	{
		/* Using a variable for conditional returns */
		$ret['use_gravatar'] = array(
				'label' => $this->T_('Use Gravatar: '),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->T_('for comment authors who don\'t have a Pavatar'),
		);
		$ret['use_legacy'] = array(
			'label' => $this->T_('Use legacy &lt;img&gt; tag'),
			'type' => 'checkbox',
			'defaultvalue' => 0,
		);
		$ret['std'] = array(
			'label' => $this->T_('(X)HTML standard to use'),
			'type' => 'select',
			'options' => array(
				'xhtml1' => $this->T_('XHTML 1.0 Transitional'),
				'xhtml11' => $this->T_('XHTML 1.1'),
				'rdfa' => $this->T_('XHTML+RDFa'),
				'html5' => $this->T_('HTML5'),
			),
			'defaultvalue' => 'xhtml1',
		);

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
