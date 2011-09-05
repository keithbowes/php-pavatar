<?php

require_once '_pavatar.inc.php';

class Pavatar
{
	private $_content, $_url;
	public function __construct()
	{
		_pavatar_init();
	}

	public function set_url($url)
	{
		$this->_url = $url;
	}

	public function set_post_content($content)
	{
		$this->_content = $content;
	}

	public function to_html()
	{
		return _pavatar_getPavatarCode($this->_url, $this->_content);
	}

	/* Quick read/write access to the variables in _pavatar.inc.php
	 * without the need to get too dirty. */
	public function accessor($prim, $new = '')
	{
		$out = eval("\$a = _pavatar_$prim; return \$a;");
		global $$out;
		$ret = $$out;

		if ($new)
			$$out = $new;

		return $ret;
	}
}

?>
