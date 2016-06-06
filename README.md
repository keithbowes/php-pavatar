# Pavatar #
## Introduction ##
This PHP library implements [Pavatar](https://github.com/pavatar/pavatar), an open specification for a decentralized way of including avatars (user-defined images, such as photos) in web applications.

Supporting web applications is easy, through a simple <abbr title="Application Programming Interface">[API](https://en.wikipedia.org/wiki/Application_programming_interface)</abbr> or, better yet, creating plugins.  It currently includes plugins for [b2evolution](http://b2evolution.net) and [WordPress](http://wordpress.org).

The current version can be determined via <kbd>make get-version</kbd>.

[More Information](https://github.com/keithbowes/php-pavatar/).

### Changes in php-pavatar 0.5 ###
Version 0.5 mostly features some modernization, but might have bugs, so please report them:

* The code has been updated for PHP 5.2+.  The code was originally written when PHP 4.x was ubiquitous, but that's no longer a concern.
* The possible bugginess of the code is specifically due to using class properties rather than passing around variables.
* In a way, \_pavatar.class.php is the successor of pavatar-oop.php, but it's not 100% compatible.  In php-pavatar 0.4.5, some changes were made to make it a transitional change to php-pavatar 0.5, at the risk of backwards compatibility (see below).
* b2evolution: 6.7.2 or higher is required for correct functioning.
* WordPress: I didn't feel like installing WordPress to test the new code, so you can send me pull requests to fix whatever I might have accidentally done wrong.

If any of that concerns you, you can always check out a previous tag.  0.4.4 is the code as of 2015-05-31.  0.4.5 is code containing the bug fixes for b2evolution 6.7.2+ (and the aforementioned changes in pavatar-oop.php).

#### Migrating from 0.4.4's pavatar-oop.php to 0.4.5's ####
* Don't use the `Pavatar::to_html()` method. Instead, the class's return value will be a string. So, `$ret = $pavatar->to_html()` becomes `$ret = $pavatar`.
* Don't use the `Pavatar::accessor()` method.  So, `$version = $pavatar->accessor('version')` becomes `$version = $pavatar->version`.

#### Migrating from 0.4.5's pavatar-oop.php to 0.5's \_pavatar.class.php ####
* Optionally pass the cache directory to the constructor.
* Use the `Pavatar::$post_content` property rather than the `Pavatar::set_post_content()` method.
* Use the `Pavatar::$author_url` property rather than the `Pavatar::set_url()` method.
* Change `Pavatar::$version` to `Pavatar::VERSION`.

#### Migrating from \_pavatar.inc.php to \_pavatar.class.php ###
* Construct a Pavatar object rather than calling `_pavatar_init()`.
* The constructor can be called with the cache directory (defaulting to the current) or `false` to disable caching.
* The `$_pavatar_<variable>` variables have been changed to the `Pavatar::$<variable>` properties with a few exceptions:
    * `$_pavatar_cache_dir` has been changed to passing the cache directory to the constructor.
    * `$_pavatar_email` becomes `Pavatar::author_email`.
    * `$_pavatar_version` becomes `Pavatar::VERSION`.
    * `$_pavatar_ui_name` and `$_pavatar_ui_version` become `Pavatar::$user_agent['name']` and `Pavatar::$user_agent['version']`.
    * `$_pavatar_use_legacy` has been removed.  Legacy HTML code is no longer produced.
* Instead of `_pavatar_getPavatarCode()`, set `Pavatar::$author_url` and `Pavatar::$post_content`.  The resulting HTML will be contained in the Pavatar object.

You can look at the b2evolution plugin for a functioning example.

## Thanks To ##
* [Jeena Paradies](http://jeenaparadies.net/), author of the Pavatar spec
* [James Nicholls](https://sourceforge.net/u/nijineko/profile/), creator of the default Pavatar icon
