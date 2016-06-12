<?php

class Pavatar
{
	const VERSION = '0.5';

	private $show = true;

	private $cache_dir;
	private $cache_file;

	public $author_url;
	public $post_content;
	private $url;

	public $base_offset;
	private $mime_type;

	public $user_agent = array();
	protected $headers;

	/* Gravatar support */
	public $author_email;
	public $use_gravatar = false;

	public function __construct($cache_dir = '')
	{
		if ($cache_dir === false)
			return;

		$this->cache_dir = $cache_dir;
		$this->createCacheEntry();
		$this->cleanCache();

		if (!file_exists($this->cache_dir . '/pavatar.png'))
			copy(dirname(__FILE__) . '/pavatar.png', $this->cache_dir . '/pavatar.png');

		if (!file_exists($this->cache_dir . '/.htaccess'))
			copy(dirname(__FILE__) . '/.htaccess', $this->cache_dir . '/.htaccess');
	}

	public function __toString()
	{
		if ($this->url = $this->author_url)
			$this->getImageURL();

		$this->createCacheEntry();

		if (strstr($this->mime_type, 'image') === false)
			$this->getDefaultPavatar();

		$img = '<a href="https://github.com/pavatar/pavatar/blob/master/Readme.md">';
		$img .= '<object data="' . $this->url . '"';

		if ($this->mime_type)
			$img .= ' type="' . $this->mime_type . '"';

		$img .= ' class="pavatar"></object>';
		$img .= '</a>' . $this->post_content;
		return $img;
	}

	/*
	 * See if there's a pavatar.png in the web site's home directory.
	 * @return bool Whether there was a pavatar.png.
	 */
	private function checkDirectURL()
	{
		$sep = substr($this->url, -1, 1) == '/' ? '' : '/';
		$this->url = $this->url . $sep . 'pavatar.png';
		$this->getURLContents('HEAD');
		return strstr($this->headers[0], '404') === false;
	}

	/* Should be __destruct, but that causes errors */
	private function cleanCache()
	{
		$week_seconds = 7 * 24 * 60 * 60;
		$files = scandir($this->cache_dir);
		$fc = count($files);

		for ($i = 0; $i < $fc; $i++)
		{
			$file = $this->cache_dir . '/' . $files[$i];
			$lm = filemtime($file); // Get the last-modified timestamp

			if (is_file($file) && $lm < time() - $week_seconds) // Older than a week
				unlink($file);
		}
	}

	protected function createCacheEntry()
	{
		if (!$this->cache_dir)
			$this->cache_dir = '_pavatar_cache';
		$this->mime_type = '';

		if (!is_dir($this->cache_dir))
		{
			@mkdir($this->cache_dir);
			@chown($this->cache_dir, get_current_user());
		}

		if ($this->url)
			$this->cache_file = $this->cache_dir . '/' . base64_encode($this->url);
	}

	private function getDefaultPavatar()
	{
		$this->url = $this->base_offset . $this->cache_dir . '/pavatar.png';
		$this->mime_type = 'image/png';

		if ($this->use_gravatar)
		{
			$this->url = 'http://www.gravatar.com/avatar/' . md5($this->author_email) . '?s=80&amp;d=' . rawurlencode($default_pavatar);
			$this->mime_type = '';
		}
	}

	/*
	 * Get the Pavatar image's URL, either on a remote server or in the cache.
	 */
	private function getImageURL()
	{
		$ext = '';
		$mime_file = $this->cache_file . '.mime';

		if (!file_exists($mime_file))
		{
			$this->getPavatarURL();
			if (!$this->mime_type)
			{
				$this->getURLContents('HEAD');
				$this->mime_type = @$this->headers['content-type'];
			}

			switch ($this->mime_type)
			{
				case 'image/gif':
					$ext = '.gif';
					break;
				case 'image/jpeg':
					$ext = '.jpg';
					break;
				case 'image/png':
					$ext = '.png';
					break;
			}

			switch ($this->mime_type)
			{
				case 'image/gif':
				case 'image/jpeg':
				case 'image/png':
					if ($this->headers && @$this->headers['location'])
					{
						$this->url = $this->headers['location'];
					}

					$c = $this->getURLContents();
					break;
				default:
					$c = $this->url;
			}

			if (!$this->headers || empty($this->headers['content-length']) || @$this->headers['content-length'] > 0)
			{
				$f = @fopen($this->cache_file . $ext, 'w');
				@fwrite($f, $c);
				@fclose($f);

				$f = @fopen($mime_file, 'w');
				@fwrite($f, $this->mime_type);
				@fclose($f);

				if (file_exists($this->cache_file))
				{
					@chown($this->cache_file, get_current_user());
					@chmod($this->cache_file, 0755);
				}
			}
		}

		if (file_exists($mime_file))
		{
			$this->mime_type = file_get_contents($mime_file);
			switch ($this->mime_type)
			{
				case 'image/gif':
					$ext = '.gif';
					break;
				case 'image/jpeg':
					$ext = '.jpg';
					break;
				case 'image/png':
					$ext = '.png';
					break;
			}

			if ($this->mime_type)
				$this->url = $this->base_offset . $this->cache_file . $ext;
			else
			{
				$this->url = @file_get_contents($this->cache_file . $ext);
				$this->getPavatarURL();
			}
		}

		$this->show = $this->url != 'none';
	}

	/*
	 * Get the URL of the Pavatar on a remote server
	 */
	private function getPavatarURL()
	{
		/* Note that indicating the MIME type is an extension. */
		$_url = '';

		if ($this->url)
		{
			$this->getURLContents('HEAD');
			$_url = @$this->headers['x-pavatar'];
		}

		if (!$_url && $this->url)
		{
			if (class_exists('DOMDocument'))
			{
				$dom = new DOMDocument();
				if (@$dom->loadHTML($this->getURLContents()))
				{
					$links = $dom->getElementsByTagName('link');
					$metas = $dom->getElementsByTagName('meta');

					for ($i = 0; $i < $links->length; $i++)
					{
						$rels = strtolower($links->item($i)->getAttribute('rel'));
						$relsarr = preg_split('/\s+/', $rels);

						if (array_search('pavatar', $relsarr) !== false)
						{
							$this->mime_type = $links->item($i)->getAttribute('type');
							$_url = html_entity_decode($links->item($i)->getAttribute('href'));
						}

						if (isset($_url) && isset($this->mime_type)) break;
					}

					/* Non-standard use of <meta http-equiv…>.  Subject to change. */
					if (!$_url)
					{
						for ($i = 0; $i < $metas->length; $i++)
						{

							$httpequiv = strtolower($metas->item($i)->getAttribute('http-equiv'));
							if ($httpequiv == 'x-pavatar')
								$_url = html_entity_decode($metas->item($i)->getAttribute('content'));

							if ($httpequiv == 'x-pavatar-type')
								$this->mime_type = $metas->item($i)->getAttribute('content');

						if (isset($_url) && isset($this->mime_type)) break;
						}
					}
				}
			}
		}

		if ($_url && !$this->mime_type)
			$this->mime_type = 'image/png';

		if (!$_url && $this->url)
		{
			if (!$this->checkDirectURL())
			{
				$urlp = parse_url($this->url);
				if (isset($urlp['port']))
					$port = ':' . $urlp['port'];
				else
					$port = '';

				$this->url = $urlp['scheme'] . '://' . $urlp['host'] . $port;
				$this->checkDirectURL();
			}
		}
		else
			$this->url = $_url;
	}

	/*
	 * Get the contents of a URL
	 * @param string The HTTP method to use.  It should be either GET to get the URL's content or HEAD to get the URL's headers.
	 * @note If $method is HEAD, Pavatar::headers is populated with an associative array
	 * @return string The URL's content.
	 */
	private function getURLContents($method = 'GET')
	{
		$in_headers = 'HEAD' != $method;
		$ret = '';

		$urlp = parse_url($this->url);
		if (empty($urlp['port']))
			$urlp['port'] = 80;

		@$fh = fsockopen($urlp['host'], $urlp['port']);
		if ($fh)
		{
			fwrite($fh, $method . ' ' . $urlp['path'] . ' HTTP/1.1' . "\r\n");
			fwrite($fh, 'Host: ' . $urlp['host'] . "\r\n");
			fwrite($fh, 'User-Agent: PHP-Pavatar/' . self::VERSION . ' (' . php_uname('s') . ' ' . php_uname('r') . ') ');
			if (isset($this->user_agent['name']))
			{
				fwrite($fh, $this->user_agent['name']);
				if ($this->user_agent['version'])
					fwrite($fh, '/' . $this->user_agent['version']);
			}
			fwrite($fh, "\r\n");
			fwrite($fh, "Connection: close\r\n");
			fwrite($fh, "\r\n");

			while (!feof($fh))
			{
				if ($in_headers || !trim($ret))
					$ret = '';

				$r = fgets($fh);
				$ret .= $r;
				if (!trim($ret))
					$in_headers = false;

				if ('HEAD' == $method)
				{
					if (preg_match('/^([^:]+):\s*(.*)\r\n$/', $r, $matches))
					{
						list($full, $name, $value) = $matches;
						$this->headers[strtolower($name)] = $value;
					}
					elseif (3 < strlen($r))
					{
						$this->headers[0] = $r;
					}
				}
			}
		}
		else
		{
			$this->mime_type = 'text/plain';
		}

		@fclose($fh);
		return $ret;
	}
}

?>