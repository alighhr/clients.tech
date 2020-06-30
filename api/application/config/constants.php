<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
  |--------------------------------------------------------------------------
  | Display Debug backtrace
  |--------------------------------------------------------------------------
  |
  | If set to TRUE, a backtrace will be displayed along with php errors. If
  | error_reporting is disabled, the backtrace will not display, regardless
  | of this setting
  |
 */
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
  |--------------------------------------------------------------------------
  | File and Directory Modes
  |--------------------------------------------------------------------------
  |
  | These prefs are used when checking and setting modes when working
  | with the file system.  The defaults are fine on servers with proper
  | security, but you may wish (or even need) to change the values in
  | certain environments (Apache running a separate process for each
  | user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
  | always be used to set the mode correctly.
  |
 */
defined('FILE_READ_MODE') OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE') OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE') OR define('DIR_WRITE_MODE', 0755);

/*
  |--------------------------------------------------------------------------
  | File Stream Modes
  |--------------------------------------------------------------------------
  |
  | These modes are used when working with fopen()/popen()
  |
 */
defined('FOPEN_READ') OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE') OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE') OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE') OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT') OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT') OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
  |--------------------------------------------------------------------------
  | Exit Status Codes
  |--------------------------------------------------------------------------
  |
  | Used to indicate the conditions under which the script is exit()ing.
  | While there is no universal standard for error codes, there are some
  | broad conventions.  Three such conventions are mentioned below, for
  | those who wish to make use of them.  The CodeIgniter defaults were
  | chosen for the least overlap with these conventions, while still
  | leaving room for others to be defined in future versions and user
  | applications.
  |
  | The three main conventions used for determining exit status codes
  | are as follows:
  |
  |    Standard C/C++ Library (stdlibc):
  |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
  |       (This link also contains other GNU-specific conventions)
  |    BSD sysexits.h:
  |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
  |    Bash scripting:
  |       http://tldp.org/LDP/abs/html/exitcodes.html
  |
 */
defined('EXIT_SUCCESS') OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR') OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG') OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE') OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS') OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT') OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE') OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN') OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX') OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code



/* ---------Site Settings-------- */
/* ------------------------------ */

/* Site Related Settings */
define('SITE_NAME', 'AlignHr');
define('SITE_CONTACT_EMAIL', 'info@alignhr.com');
define('MULTISESSION', true);
define('PHONE_NO_VERIFICATION', true);
define('DATE_FORMAT', "%Y-%m-%d %H:%i:%s"); /* dd-mm-yyyy */
define('SPORTS_FILE_PATH', FCPATH . 'uploads/sports.txt');
define('FOOTBALl_SPORTS_FILE_PATH', FCPATH . 'uploads/football_sports.txt');
define('SPORTS_API_NAME', 'CRICKETAPI');
define('FOOTBALL_SPORT_API_NAME', 'CRICKETAPI');

define('DEFAULT_SOURCE_ID', 1);
define('DEFAULT_DEVICE_TYPE_ID', 1);
define('DEFAULT_CURRENCY', 'Rs.');
define('REFERRAL_SIGNUP_BONUS', 50);
define('DEFAULT_PLAYER_CREDITS', 6.5);
define('DEFAULT_TIMEZONE', '+05:30');
define('TIMEZONE_DIFF_IN_SECONDS', 19800);

define('IS_VICECAPTAIN', true);
define('MATCH_TIME_IN_HOUR', 300);

/* for push Notification */
define('CHANNEL_NAME', 'Alert');
define('ANDROID_SERVER_KEY', '*************************');

/* Social */
define('FACEBOOK_URL', 'https://www.facebook.com/');
define('TWITTER_URL', 'https://twitter.com');
define('LINKEDIN_URL', 'https://www.linkedin.com/company');
define('INSTAGRAM_URL', 'https://www.instagram.com/');

/* Entity Sports API Details */
define('SPORTS_API_URL_ENTITY', 'https://rest.entitysport.com');
define('SPORTS_API_ACCESS_KEY_ENTITY', '**************************');
define('SPORTS_API_SECRET_KEY_ENTITY', '**************************');

define('MW_SPORTS_API_URL_CRICKETAPI', 'http://example.com/api/cricket/');

/* Cricket API Sports API Details */
define('FOOTBALL_SPORTS_API_URL_CRICKETAPI', 'https://api.footballapi.com');
define('FOOTBALL_SPORTS_API_ACCESS_KEY_CRICKETAPI', '**************************');
define('FOOTBALL_SPORTS_API_SECRET_KEY_CRICKETAPI', '********************************');
define('FOOTBALL_SPORTS_API_APP_ID_CRICKETAPI', 'Football Sports');
define('FOOTBALL_SPORTS_API_DEVICE_ID_CRICKETAPI', 'FootballSPortsAPI');

define('SPORTS_API_URL_CRICKETAPI', 'https://rest.cricketapi.com');
define('SPORTS_API_ACCESS_KEY_CRICKETAPI', '*******************************');
define('SPORTS_API_SECRET_KEY_CRICKETAPI', '*******************************');
define('SPORTS_API_APP_ID_CRICKETAPI', '*******');
define('SPORTS_API_DEVICE_ID_CRICKETAPI', '*******');

// define('SPORTS_API_URL_CRICKETAPI', 'https://rest.cricketapi.com');
// define('SPORTS_API_ACCESS_KEY_CRICKETAPI', '7144ec880188adb6387541fb43fe7b27');
// define('SPORTS_API_SECRET_KEY_CRICKETAPI', '7b78899dc40d8f63605042dbd96c69e4');
// define('SPORTS_API_APP_ID_CRICKETAPI', 'fantasygoalz.com');
// define('SPORTS_API_DEVICE_ID_CRICKETAPI', 'developer');


/* PayUMoney Details */
define('PAYUMONEY_MERCHANT_KEY', 'jPdC5tWg');
define('PAYUMONEY_MERCHANT_ID', '6156071');
define('PAYUMONEY_SALT', 'CkKB7Of1A3');

/* SMS API Details */
define('SMS_API_URL', 'https://www.bulksmsgateway.in');
define('SMS_API_USERNAME', 'fonty');
define('SMS_API_PASSWORD', '6162503');
define('SMS_API_SENDER_ID', 'TestID');

/* SENDINBLUE SMS API Details */
define('SENDINBLUE_SMS_API_URL', 'https://api.sendinblue.com/v3/transactionalSMS/sms');
define('SENDINBLUE_SMS_SENDER', '******');
define('SENDINBLUE_SMS_API_KEY', '*************');


/* MSG91 SMS API Details */
define('MSG91_AUTH_KEY', '*****************');
define('MSG91_SENDER_ID', 'FANTSY');
define('MSG91_FROM_EMAIL', 'info@example.com');

define('POST_PICTURE_URL', BASE_URL . 'uploads/Post/');

switch (ENVIRONMENT) {
    case 'local':
        /* Paths */
        define('SITE_HOST', 'http://localhost/');
        define('ROOT_FOLDER', 'alignhr-clients/');

        /* SMTP Settings */
        define('SMTP_PROTOCOL', 'smtp');
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', '587');
        define('SMTP_USER', '');
        define('SMTP_PASS', '');
        define('SMTP_CRYPTO', 'tls'); /* ssl */

        /* From Email Settings */
        define('FROM_EMAIL', 'info@expertteam.in');
        define('FROM_EMAIL_NAME', SITE_NAME);

        /* No-Reply Email Settings */
        define('NOREPLY_EMAIL', SITE_NAME);
        define('NOREPLY_NAME', "info@expertteam.in");

        /* Site Related Settings */
        define('API_SAVE_LOG', false);
        define('CRON_SAVE_LOG', false);

        /* Paytm Details */
        define('PAYTM_MERCHANT_ID', '**************');
        define('PAYTM_MERCHANT_KEY', '**************');
        define('PAYTM_DOMAIN', 'securegw.paytm.in');
        define('PAYTM_INDUSTRY_TYPE_ID', 'Retail');
        define('PAYTM_WEBSITE_WEB', 'DEFAULT');
        define('PAYTM_WEBSITE_APP', 'DEFAULT');
        define('PAYTM_TXN_URL', 'https://' . PAYTM_DOMAIN . '/theia/processTransaction');
        define('PAYUMONEY_ACTION_KEY', 'https://secure.payu.in/_payment');

        /* PAYTM AUTOWITHDRAW */
        define('AUTO_WITHDRAWAL', true);
        define('PAYTM_MERCHANT_KEY_WITHDRAWAL', '********************');
        define('PAYTM_MERCHANT_mId', '*********************');
        define('PAYTM_MERCHANT_GUID', '*************************');
        define('PAYTM_SALES_WALLET_GUID', '*****************************');
        define('PAYTM_GRATIFICATION_URL', 'https://trust.paytm.in/wallet-web/salesToUserCredit'); // For Withdraw

        define('APP_PAYTM_MERCHANT_ID', '**************');
        define('APP_PAYTM_MERCHANT_KEY', '***************');
        define('APP_PAYTM_DOMAIN', 'securegw.paytm.in');
        define('APP_PAYTM_INDUSTRY_TYPE_ID', 'Retail');
        define('APP_PAYTM_WEBSITE_WEB', 'DEFAULT');
        define('APP_PAYTM_WEBSITE_APP', 'DEFAULT');
        define('APP_PAYTM_TXN_URL', 'https://' . APP_PAYTM_DOMAIN . '/theia/processTransaction');

        /* Razorpay Details */
        define('RAZORPAY_KEY_ID', '*************************');
        define('RAZORPAY_KEY_SECRET', '*********************');

        break;
    case 'testing':

        /* Paths */
        define('SITE_HOST', 'http://192.168.1.251/');
        define('ROOT_FOLDER', 'servechampci/');

        /* SMTP Settings */
        define('SMTP_PROTOCOL', 'smtp');
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', '587');
        define('SMTP_USER', '');
        define('SMTP_PASS', '');
        define('SMTP_CRYPTO', 'tls'); /* ssl */

        /* From Email Settings */
        define('FROM_EMAIL', 'info@expertteam.in');
        define('FROM_EMAIL_NAME', SITE_NAME);

        /* No-Reply Email Settings */
        define('NOREPLY_EMAIL', SITE_NAME);
        define('NOREPLY_NAME', "info@expertteam.in");

        /* Site Related Settings */
        define('API_SAVE_LOG', false);
        define('CRON_SAVE_LOG', false);

        /* Paytm Details */
        define('PAYTM_MERCHANT_ID', '********************');
        define('PAYTM_MERCHANT_KEY', '********************');
        define('PAYTM_DOMAIN', 'securegw-stage.paytm.in');
        define('PAYTM_INDUSTRY_TYPE_ID', 'Retail');
        define('PAYTM_WEBSITE_WEB', 'WEBSTAGING');
        define('PAYTM_WEBSITE_APP', 'APPSTAGING');
        define('PAYTM_TXN_URL', 'https://' . PAYTM_DOMAIN . '/theia/processTransaction');
        define('PAYUMONEY_ACTION_KEY', 'https://test.payu.in/_payment');

        /* Razorpay Details */
        define('RAZORPAY_KEY_ID', '*************************');
        define('RAZORPAY_KEY_SECRET', '*********************');
        break;
    case 'demo':
        /* Paths */
        define('SITE_HOST', 'http://54.169.70.225/');
        define('ROOT_FOLDER', 'alignhr-clients/');

        /* SMTP Settings */
        define('SMTP_PROTOCOL', 'smtp');
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', '587');
        define('SMTP_USER', '');
        define('SMTP_PASS', '');
        define('SMTP_CRYPTO', 'tls'); /* ssl */

        /* From Email Settings */
        define('FROM_EMAIL', 'info@expertteam.in');
        define('FROM_EMAIL_NAME', SITE_NAME);

        /* No-Reply Email Settings */
        define('NOREPLY_EMAIL', SITE_NAME);
        define('NOREPLY_NAME', "info@expertteam.in");

        /* Site Related Settings */
        define('API_SAVE_LOG', false);
        define('CRON_SAVE_LOG', false);

        /* Paytm Details */
        define('PAYTM_MERCHANT_ID', '********************');
        define('PAYTM_MERCHANT_KEY', '********************');
        define('PAYTM_DOMAIN', 'securegw-stage.paytm.in');
        define('PAYTM_INDUSTRY_TYPE_ID', 'Retail');
        define('PAYTM_WEBSITE_WEB', 'WEBSTAGING');
        define('PAYTM_WEBSITE_APP', 'APPSTAGING');
        define('PAYTM_TXN_URL', 'https://' . PAYTM_DOMAIN . '/theia/processTransaction');
        define('PAYUMONEY_ACTION_KEY', 'https://secure.payu.in/_payment');

        /* Razorpay Details */
        define('RAZORPAY_KEY_ID', '*************************');
        define('RAZORPAY_KEY_SECRET', '*********************');
        break;
    case 'production':
        /* Paths */
        define('SITE_HOST', 'http://www.engageme.tech/');
        define('ROOT_FOLDER', 'alignhr-clients/');

        /* SMTP Settings */
        define('SMTP_PROTOCOL', 'smtp');
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', '587');
        define('SMTP_USER', '');
        define('SMTP_PASS', '');
        define('SMTP_CRYPTO', 'tls'); /* ssl */

        /* From Email Settings */
        define('FROM_EMAIL', 'info@expertteam.in');
        define('FROM_EMAIL_NAME', SITE_NAME);

        /* No-Reply Email Settings */
        define('NOREPLY_EMAIL', SITE_NAME);
        define('NOREPLY_NAME', "info@expertteam.in");

        /* Site Related Settings */
        define('API_SAVE_LOG', false);
        define('CRON_SAVE_LOG', false);

        /* Paytm Details */
        define('PAYTM_MERCHANT_ID', '********************');
        define('PAYTM_MERCHANT_KEY', '********************');
        define('PAYTM_DOMAIN', 'securegw-stage.paytm.in');
        define('PAYTM_INDUSTRY_TYPE_ID', 'Retail');
        define('PAYTM_WEBSITE_WEB', 'WEBSTAGING');
        define('PAYTM_WEBSITE_APP', 'APPSTAGING');
        define('PAYTM_TXN_URL', 'https://' . PAYTM_DOMAIN . '/theia/processTransaction');
        define('PAYUMONEY_ACTION_KEY', 'https://secure.payu.in/_payment');

        /* Razorpay Details */
        define('RAZORPAY_KEY_ID', '*************************');
        define('RAZORPAY_KEY_SECRET', '*********************');
        break;
}

define('BASE_URL', SITE_HOST . ROOT_FOLDER . 'api/');
define('ASSET_BASE_URL', BASE_URL . 'asset/');
define('PROFILE_PICTURE_URL', BASE_URL . 'uploads/profile/picture');


/* Send grid mail templates */
define("SIGNUP", 'd-23c25d0c43d6482b829738e307832bed');
define("ADD_USER", 'd-3d5e4cee6d30463fb5e0f866818d5a85');
define("CHANGE_EMAIL", 'd-ee1d913c52d54339a67da1e184e90a5a');
define("CHANGE_PASSWORD", 'd-5d9eaaa4a64b486f945cb0d56f6363a8');
define("RECOVERY", 'd-2fd5ea61831e4b5f87b05a1735fae614');
define("REFER_AND_EARN", 'd-2ff307d1720540ac81745ac244678109');



define("EMAIL_VERIFED_CONFIRMED", 'd-d395342831424d24b7c564adf97e071a');
define("CONTACT", 'd-608c4051a2bd4702aedf5b6c0e3df8a8');
define("CONTEST_WINNING", 'd-195da7bd42da4c81bde1a8a7aff57e30');
define("WITHDRAWALS", 'd-8a612130d5c34b23a90bb23e0108e5e0');
define("BROADCAST", 'd-b442e1d0192e4f7aa2093b58edbe0cb8');
define("VERIFY_EMAIL", 'd-ca5ac55f5ba74ce388abb8ee302109b1');
define("CONTEST_INVITATION", 'd-3bd66c1a73e7443aa0f391bdf201ea3b');
define("CANCEL_CONTEST", 'd-de33c17419c94ff4a7cd0edf3bd54bb0');
define("ADMIN_LOGIN_OTP", 'd-4781d66da62849d0ade65c7c3cc5eabe');
define("CASHBONUSEXPIRED", 'd-6a9fe0da1ddf44a098b5f4a6437dde68');