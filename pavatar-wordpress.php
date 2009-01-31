<?php

/*
Plugin Name: Pavatar
Version: 0.3
Description: Displays a Pavatar avatar anywhere that get_avatar() is used (contact your theme's author if your theme lacks support).
Plugin URI: http://sourceforge.net/projects/pavatar
 */

include '_pavatar.inc.php';

function _pavatar_wordpress_give_avatar()
{
  global $comment;

  if ($comment)
    $url = get_comment_author_url();
  else
    $url = get_the_author_url();

  return _pavatar_getPavatarCode($url);
}

add_action('wp_footer', '_pavatar_cleanFiles');

add_filter('get_avatar', '_pavatar_wordpress_give_avatar');
