<?php

include '_pavatar.class.php';

class pavatar_plugin extends Plugin
{
	public $code = 'b2evPava';

	public $author = 'Keith Bowes';
	public $help_url = 'http://github.com/keithbowes/pavatar/';
	public $name = 'Pavatar';

	public $group = 'rendering';
	public $number_of_installs = 1;

	private $pavatar;

	function PluginInit(& $params)
	{
		global $Settings;
		global $app_name, $app_version, $baseurl, $cache_subdir, $disp;

		if (is_object($Settings))
			$Settings->set('allow_avatars', false);

		if (is_dir($cache_subdir . 'plugins'))
			$cache_dir = $cache_subdir . 'plugins/pavatar';
		else
			$cache_dir = false;

		$this->pavatar = new Pavatar(isset($disp) ? $cache_dir : FALSE);
		$this->pavatar->base_offset = $baseurl;

		if ($params['is_installed'])
		{
			$this->pavatar->use_gravatar = $this->Settings->get('use_gravatar');
		}

		$this->version = Pavatar::VERSION;
		$this->pavatar->user_agent['name'] = $app_name;
		$this->pavatar->user_agent['version'] = $app_version;

		$this->short_desc = $this->T_('Implements Pavatar support.');
		$this->long_desc = $this->T_('Displays Pavatars in your entries and comments without having to mess around with PHP.');
		if (TRUE !== $this->BeforeEnable())
		{
			$this->set_status('disabled');
			return FALSE;
		}
	}

	function BeforeEnable()
	{
		if (!extension_loaded('curl'))
		{
			return $this->T_('This plugin requires the PHP curl extension');
		}

		return TRUE;
	}

	function DisplayItemAsHtml(& $params)
	{
		$item = $params['Item'];
		$this->pavatar->post_content = $params['data'];

		static $comment = -1;
		if (-1 == $comment)
		{
			global $disp;
			if ('single' == $disp)
				$comment = 0;

			if (is_object($item))
			{
				$this->pavatar->author_url = $item->get_creator_User()->url;
				$this->pavatar->author_email = $item->get_creator_User()->email;
			}
		}

		if (0 <= $comment && !isset($params['dispmore']))
		{
			/* All but stolen from Item::get_latest_Comment */
			global $DB;
			$sql = 'SELECT comment_ID FROM T_comments WHERE comment_item_ID = ' .
				$DB->quote($item->ID) . ' AND comment_type <> \'meta\' AND '.
				statuses_where_clause( get_inskin_statuses( $item->get_blog_ID(), 'comment' ), 'comment_', $item->get_blog_ID(), 'blog_comment!', true) .
			' ORDER BY comment_date ASC';
			$comment_ID = $DB->get_row($sql, OBJECT, $comment)->comment_ID;
			$next_comment = get_CommentCache()->get_by_ID($comment_ID);
			$comment++;

			if (is_object($next_comment->get_author_user())) // is a member
			{
				$this->pavatar->author_url = $next_comment->get_author_user()->url;
				$this->pavatar->author_email = $next_comment->get_author_user()->email;
			}
			else
			{
				$this->pavatar->author_url = $next_comment->author_url;
				$this->pavatar->author_email = $next_comment->author_email;
			}
		}

		$params['data'] = $this->pavatar;
	}

	function GetDefaultSettings(& $params)
	{
		return array(
		   'use_gravatar' =>	array(
				'label' => $this->T_('Use Gravatar: '),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->T_('for comment authors who don\'t have a Pavatar'),
		));
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