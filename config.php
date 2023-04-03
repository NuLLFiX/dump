<?php
/********************************************************
	Dumpz v1.5
	Release date: Aug 2014
	Copyright (C) nullfix.com
*********************************************************/
$_CONFIG['admin_pw']    		= 'youradminpass';			// Administration password | username = admin, password = $_CONFIG['admin_pw']
$_CONFIG['database']			= 'dumpz.db';   			// SQLite database file!
$_CONFIG['usemysql']                    = false;                                // use mysql? False = use pdo sqlite. True = use PDO Mysql database

$_CONFIG['mysql']                       = 'mysql:host=localhost;dbname=dumpz';  // mysql pdo connect string change host value and dbname value!
$_CONFIG['username']                    = 'root';                               // mysql databasse username
$_CONFIG['password']                    = 'putyourpasshere';                    // mysql user password

$_CONFIG['site_url']			= 'http://localhost/dump';              // Web Site url without the ending slash.
$_CONFIG['site_name']			= 'Dump IT';    			// site name that will appear in texts
$_CONFIG['admin_mail']			= 'admin@nullfix.com';			// admin's mail

// Script copyrights:
$_CONFIG['script_copyrights']	= 'Created by <a class="text-muted" href="//nullfix.com" target="_blank">NuLLFiX</a>';
$_CONFIG['script_version']	= '1.5';

// Website SEO optimization:
$_CONFIG['description']         = $_CONFIG['site_name'].' share your snippets free all over internet! '.$_CONFIG['site_name'].' is a website where you can store text and code/snippets online.';
$_CONFIG['keywords']            = 'pastebin, paste, text, html, snipets, snipt, class, php code, php, mysql, sqlite, ajax, html5, css3, snippets';

// custom database function easy changeable to any pdo supported databases.
function mycustomdb() {
    global $_CONFIG;
    if ($_CONFIG['usemysql']) {
        // Conect to an mysql database (need to install it first from phpmyadmin...)
        $custom_db = new PDO($_CONFIG['mysql'], $_CONFIG['username'], $_CONFIG['password']);
    } else {
        // Connect to SQLite3 database in file
        $custom_db = new PDO('sqlite:'.$_CONFIG['database'].'');
    }
    // Set errormode to exceptions
    $custom_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $custom_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $custom_db;
}

// Avatar
// Gravatar Setup
if($_SESSION['user_logged'] == 1){
$getmail = mycustomdb()->query("select mail from users where id='".$_SESSION['user_id']."' LIMIT 1");
$getmail = $getmail->fetch(PDO::FETCH_ASSOC);
$default = $_CONFIG['site_url']."/gfx/gravatar.gif";
$size = 80;
$grav_url = "//www.gravatar.com/avatar.php?gravatar_id=".md5($getmail)."&default=".urlencode($default)."&size=".$size;
$getmail = null; // close database connection.
}

// Random file name generator
function random_string($max_length = 20, $random_chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789")
{
    for ($i = 0; $i < $max_length; $i++) {
	$random_key = mt_rand(0, strlen($random_chars));
	$random_string .= substr($random_chars, $random_key, 1);
	}
    return str_shuffle($random_string);
}

/**
 * Finds all of the keywords (words that appear most) on param $str 
 * and return them in order of most occurrences to less occurrences.
 * @param string $str The string to search for the keywords.
 * @param int $minWordLen[optional] The minimun length (number of chars) of a word to be considered a keyword.
 * @param int $minWordOccurrences[optional] The minimun number of times a word has to appear 
 * on param $str to be considered a keyword.
 * @param boolean $asArray[optional] Specifies if the function returns a string with the 
 * keywords separated by a comma ($asArray = false) or a keywords array ($asArray = true).
 * @return mixed A string with keywords separated with commas if param $asArray is true, 
 * an array with the keywords otherwise.
 */
function extract_keywords($str, $minWordLen = 3, $minWordOccurrences = 2, $asArray = false)
{
	function keyword_count_sort($first, $sec)
	{
		return $sec[1] - $first[1];
	}
	$str = preg_replace('/[^\p{L}0-9 ]/', ' ', $str);
	$str = trim(preg_replace('/\s+/', ' ', $str));
 
	$words = explode(' ', $str);
	$keywords = array();
	while(($c_word = array_shift($words)) !== null)
	{
		if(strlen($c_word) < $minWordLen) continue;
 
		$c_word = strtolower($c_word);
		if(array_key_exists($c_word, $keywords)) $keywords[$c_word][1]++;
		else $keywords[$c_word] = array($c_word, 1);
	}
	usort($keywords, 'keyword_count_sort');
 
	$final_keywords = array();
	foreach($keywords as $keyword_det)
	{
		if($keyword_det[1] < $minWordOccurrences) break;
		array_push($final_keywords, $keyword_det[0]);
	}
	return $asArray ? $final_keywords : implode(', ', $final_keywords);
}

function get_timeago( $ptime )
{
    $etime = time() - $ptime;

    if( $etime < 1 )
    {
        return 'just now';
    }

    $a = array( 12 * 30 * 24 * 60 * 60  =>  'year',
                30 * 24 * 60 * 60       =>  'month',
                24 * 60 * 60            =>  'day',
                60 * 60             =>  'hour',
                60                  =>  'minute',
                1                   =>  'second'
    );

    foreach( $a as $secs => $str )
    {
        $d = $etime / $secs;

        if( $d >= 1 )
        {
            $r = round( $d );
            return $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
        }
    }
}

function newsticker() {
    $ticker = mycustomdb()->query("SELECT * FROM dump WHERE title != '' ORDER BY id DESC LIMIT 10");
    while ($res = $ticker->fetch(PDO::FETCH_ASSOC)) {
        $results .= '<li><a href="./'.$res['key'].'">'.$res['title'].' ['.get_timeago($res['date']).']</a></li>'."\n";
    }
    $ticker = null;
    return $results;
}
?>
