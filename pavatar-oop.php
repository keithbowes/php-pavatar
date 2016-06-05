<?php

require_once '_pavatar.inc.php';

class Pavatar
{
	private $_content, $_url;
	public function __construct()
	{
		_pavatar_init();
	}

	public function __get($prop)
	{
		$prim = "_pavatar_$prop";
		global $$prim;
		return $$prim;
	}

	public function __set($prop, $value)
	{
		$prim = "_pavatar_$prop";
		global $$prim;
		$$prim = $value;
	}

	public function __toString()
	{
		return _pavatar_getPavatarCode($this->_url, $this->_content);
	}

	public function set_url($url)
	{
		$this->_url = $url;
	}

	public function set_post_content($content)
	{
		$this->_content = $content;
	}
}

?>