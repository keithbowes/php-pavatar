<?php

/*
Plugin Name: Pavatar
Version: 0.3
Description: Displays a Pavatar pavatar anywhere that get_avatar() is used (contact your theme's author if your theme lacks support).
Plugin URI: http://sourceforge.net/projects/pavatar
 */

include '_pavatar.inc.php';

function _pavatar_init()
{
  global $_pavatar_cache_dir;
  $_pavatar_cache_dir = dirname(__FILE__) . '/cache';
  @mkdir($_pavatar_cache_dir);
}

function _pavatar_wordpress_give_avatar()
{
  global $comment;

  $in = '';
  if (!$url = $comment->comment_author_url)
    $url = get_the_author_url();

  _pavatar_getPavatarCode($url, &$in);
  return $in;
}

add_filter('get_avatar', '_pavatar_wordpress_give_avatar');
add_filter('wp_head', '_pavatar_init');
