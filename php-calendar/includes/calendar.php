<?php
/*
   Copyright 2002 Sean Proctor

   This file is part of PHP-Calendar.

   PHP-Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

include($phpc_root_path . 'config.php');
include($phpc_root_path . 'includes/db.php');

function soft_error($str)
{
	echo "<html><head><title>Error</title></head>\n"
		."<body><h1>Software Error</h1><pre>$str</pre></body></html>";
	exit;
}

function browser()
{
	global $HTTP_USER_AGENT;

	if(eregi('opera/?([0-9]+(\.[0-9]+)*)?', $HTTP_USER_AGENT, $match)) {
		$BName = 'Opera';
		$BVersion = $match[1];
	} elseif(eregi('konqueror/([0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
		$BName = "Konqueror";
		$BVersion = $match[1];
	} elseif(eregi('lynx/([0-9]+.[0-9]+.[0-9]+)', $HTTP_USER_AGENT,
				$match)) {
		$BName = 'Lynx';
		$BVersion = $match[1];
	} elseif(eregi("links\(([0-9]+.[0-9]+)", $HTTP_USER_AGENT, $match)) {
		$BName = 'Links';
		$BVersion = $match[1];
	} elseif(eregi('msie ?([0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
		$BName = 'MSIE';
		$BVersion = $match[1];
	} elseif(eregi('(netscape6|mozilla)/([0-9]+.[0-9]+)',
				$HTTP_USER_AGENT, $match)) {
		$BName = 'Netscape';
		$BVersion = $match[2];
	} elseif(eregi('w3m', $HTTP_USER_AGENT)) {
		$BName = 'w3m';
		$BVersion = 'Unknown';
	} else {
		$BName = 'Unknown';
		$BVersion = 'Unknown';
	}

	return array($BName, $BVersion);
}

function translate()
{
	global $HTTP_ACCEPT_LANGUAGE, $vars, $HTTP_COOKIE_VARS;

	if(!function_exists('_')) {
		function _($str) { return $str; }
		return;
	}

	if(!TRANSLATE) {
		return;
	}

	if(isset($vars['lang'])) {
		$lang = substr($vars['lang'], 0, 2);
		setcookie('lang', $lang);
	} elseif(isset($HTTP_COOKIE_VARS['lang'])) {
		$lang = substr($HTTP_COOKIE_VARS['lang'], 0, 2);
	} elseif(isset($HTTP_ACCEPT_LANGUAGE)) {
		$lang = substr($HTTP_ACCEPT_LANGUAGE, 0, 2);
	} else {
		$lang = 'en';
	}

	switch($lang) {
		case 'de':
			setlocale('LC_ALL', 'de_DE');
			break;
		case 'en':
			setlocale('LC_ALL', 'en_US');
			break;
	}

	bindtextdomain('messages', './locale');
	textdomain('messages');
}

function month_name($month)
{
	$month = ($month - 1) % 12 + 1;
	switch($month) {
		case 1:  return _('January');
		case 2:  return _('February');
		case 3:  return _('March');
		case 4:  return _('April');
		case 5:  return _('May');
		case 6:  return _('June');
		case 7:  return _('July');
		case 8:  return _('August');
		case 9:  return _('September');
		case 10: return _('October');
		case 11: return _('November');
		case 12: return _('December');
	}
}

function short_month_name($month)
{
	$month = ($month - 1) % 12 + 1;
	switch($month) {
		case 1:  return _('Jan');
		case 2:  return _('Feb');
		case 3:  return _('Mar');
		case 4:  return _('Apr');
		case 5:  return _('May');
		case 6:  return _('Jun');
		case 7:  return _('Jul');
		case 8:  return _('Aug');
		case 9:  return _('Sep');
		case 10: return _('Oct');
		case 11: return _('Nov');
		case 12: return _('Dec');
	}
}

function check_user()
{
	global $user, $password, $db_events, $calno;

	if(empty($user)) return false;

	$query= "SELECT * FROM ".SQL_PREFIX."admin\n"
		."WHERE UID = '$user' "
		."AND password = PASSWORD('$password') "
		."AND calno = '$calno'";

	$result = $db_events->sql_query($query);
	if(!$result) {
		$error = $db_events->sql_error();
		soft_error("$error[code]: $error[message]");
	}

	if($db_events->sql_numrows($result)) return true;
	else return false;
}

function formatted_time_string($time, $type)
{
	switch($type) {
		default:
			preg_match('/(\d+):(\d+)/', $time, $matches);
			$hour = $matches[1];
			$minute = $matches[2];

			if(!HOURS_24) {
				if($hour > 12) {
					$hour -= 12;
					$pm = ' PM';
				} else {
					$pm = ' AM';
				}
			} else {
				$pm = '';
			}

			return sprintf('%d:%02d%s', $hour, $minute, $pm);
		case 2:
			return _('FULL DAY');
		case 3:
			return '??:??';
	}
}

function event_type($num)
{
	switch($num) {
		case 1:
			return _('Normal');
		case 2:
			return _('Full Day');
		case 3:
			return _('Unknown Time');
		case 4:
			return _('Daily');
		case 5:
			return _('Weekly');
		case 6:
			return _('Monthly');
	}

	return false;
}

function top()
{
	global $BName, $BVersion;

	translate();
	$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		."\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
		."<html xml:lang=\"en\">\n"
		."<head>\n"
		.'<title>'.TITLE."</title>\n"
		.'<meta http-equiv="Content-Type" '
		."content=\"text/html; charset=iso-8859-1\" />\n"
		."<!-- Your browser: $BName $BVersion -->\n"
		.'<link rel="stylesheet" type="text/css" href="style.css.php"'
		." />\n";

	if($BName == 'MSIE') {
		$output .= '<link rel="stylesheet" type="text/css" '
			."href=\"style-ie.css\" />\n";
	}

	$output .= "</head>\n<body>\n<h1>".TITLE."</h1>\n";

	return $output;
}

function lang_link($lang)
{
	global $PHP_SELF, $QUERY_STRING;

	$str = '[<a href="' . $PHP_SELF . '?';
	if(!empty($QUERY_STRING)) {
		$str .= htmlentities($QUERY_STRING) . '&amp;';
	}

	$str .= "lang=$lang\">$lang</a>]\n";
	return $str;
}

function bottom()
{
	global $SERVER_NAME, $SCRIPT_NAME, $QUERY_STRING, $year, $month, $day;

	$output = "<div class=\"phpc-footer\">";

	if(TRANSLATE) {
		$output .= "<p>\n"
			.lang_link('en')
			.lang_link('de')
			."</p>\n";
	}

	$output .= "<p>\n
	[<a href=\"http://validator.w3.org/check?url=" . rawurlencode("http://$SERVER_NAME$SCRIPT_NAME?$QUERY_STRING") . "\"> Valid XHTML 1.1</a>]
	[<a href=\"http://jigsaw.w3.org/css-validator/check/referer\">Valid CSS2</a>]
	</p>";
	$output .= "</div>\n"
		."</form>\n"
		."</div>\n"
		."</body>\n"
		."</html>";

	return $output;
}

function get_events_by_date($day, $month, $year)
{
	global $calno, $db_events;

	$query = 'SELECT * FROM '.SQL_PREFIX."events\n"
		."WHERE (startdate <= '$year-$month-$day'\n"
		."AND enddate >= '$year-$month-$day'\n"
		."AND (eventtype = 4 OR eventtype = 5"
		." OR eventtype = 6)"
		." OR startdate = '$year-$month-$day')\n"
		."AND calno = '$calno'\n"
		."AND (eventtype != 5 OR DAYOFWEEK(startdate) ="
		." DAYOFWEEK('$year-$month-$day'))\n"
		."AND (eventtype != 6 OR DAYOFMONTH(startdate) = '$day')\n"
		."ORDER BY starttime";

	$result = $db_events->sql_query($query);

	return $result;
}

function get_event_by_id($id)
{
	global $calno, $db_events;

	$result = $db_events->sql_query('SELECT * FROM '.SQL_PREFIX."events\n"
			."WHERE id = '$id' AND calno = '$calno'");

	if($db_events->sql_numrows() == 0) {
		soft_error("item doesn't exist!");
	}

	return $result;
}

function navbar()
{
	global $var, $year, $month, $day, $user, $action;

	$output = '';

	if((ANON_PERMISSIONS || isset($user)) && $action != 'add') { 
		$output .= "<a href=\"index.php?action=add&amp;day=$day"
			."&amp;month=$month&amp;year=$year\">"._('Add Item')
			."</a>\n";
	}

	if($action != 'search') {
		$output .= "<a href=\"index.php?action=search&amp;day=$day"
			."&amp;month=$month&amp;year=$year\">"._('Search')
			."</a>\n";
	}

	if($action != 'main') {
		$output .= "<a href=\"index.php?month=$month&amp;year=$year\">"
			._('Back to Calendar')."</a>\n";
	}

	if(($action != 'display' || isset($vars['id']))
			&& isset($vars['day'])) {
		$output .= "<a href=\"index.php?action=display&amp;day=$day"
			."&amp;month=$month&amp;year=$year\">"._('View date')
			."</a>\n";
	}

	if(isset($user)) {
		$output .= "<a href=\"index.php?action=logout&amp;"
			."lastaction=$action&amp;day=$day&amp;month=$month&amp;"
			."year=$year\">"._('Log out')."</a>\n";
	} else {
		$output .= "<a href=\"index.php?action=login&amp;"
			."lastaction=$action&amp;day=$day&amp;month=$month&amp;"
			."year=$year\">"._('Log in')."</a>\n";
	}


	if($action == 'display') {
		$monthname = month_name($month);

		$lasttime = mktime(0, 0, 0, $month, $day - 1, $year);
		$lastday = date('j', $lasttime);
		$lastmonth = date('n', $lasttime);
		$lastyear = date('Y', $lasttime);
		$lastmonthname = month_name($lastmonth);

		$nexttime = mktime(0, 0, 0, $month, $day + 1, $year);
		$nextday = date('j', $nexttime);
		$nextmonth = date('n', $nexttime);
		$nextyear = date('Y', $nexttime);
		$nextmonthname = month_name($nextmonth);

		$output = "<a href=\"index.php?action=display&amp;day=$lastday"
			."&amp;month=$lastmonth&amp;year=$lastyear\">"
			."$lastmonthname $lastday</a>\n"
			.$output
			."<a href=\"index.php?action=display&amp;day=$nextday"
			."month=$nextmonth&amp;day=$nextday&amp;year=$nextyear"
			."\">$nextmonthname $nextday</a>\n";
	}

	$output = "<div class=\"phpc-navbar\">$output</div>\n";

	if($action == 'main') {
		$output = month_navbar() . $output;
	}

	return $output;
}
?>