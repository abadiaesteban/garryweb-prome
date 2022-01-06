<?php

/**
 * MySQL settings
 */
$db_host = '127.0.0.1'; // Hostname or Host IP
$db_user = ''; // Username
$db_pass = ''; // Password
$db_database = 'prometheus'; // Database
$db_port = 3306; // Database port

$steam_api = ''; // Your Steam API key. This setting isn't needed, but if filled it will act as a fallback for name grabbing

/**
 * Language setting
 * @var string:
 *
 * Available options:
 * See the 'lang' folder
 */
$lang = 'en-gb'; // To make a new language file, copy the lang/en-gb.php file and rename it to for example "fr.php"

/**
 * Use cache setting
 * @var boolean
 * This config option is for those free loaders who dont pay for hosting and have no support or access to change permissions on their site
 */
$enable_cache = true;

/**
 * Enable if you are using an SSL certificate
 * @var boolean
 */
$using_ssl = false;

/**
 * enableCookies. This enables cookies and stores your session, disabled by default due to NFO issues
 * @var boolean
 */
$enableCookies = true;

/**
 * PHP timezone
 * @var string
 */
//$timezone = 'Europe/Berlin';

// Don't touch this. Always have it on false unless you know what you're doing.
// Devmode clears cache, enables timestamp at bottom of the page and turns on error reporting
$devmode = false;