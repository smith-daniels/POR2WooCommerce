<?php
/*
* File contains site specific specifications that would otherwise be hardcoded
*/

# If set to True, no updates will be made to website or hash table
$dryRun = True;

# URL to the wordpress site you want to use to WooCommerce API of
$siteURL = "http://vhrrental.com/wordpress";

# Point-of-Rental database login information
define('PORHost','vhrrental.com');
define('PORUser', 'vhrrent1');
define('PORPassword', '34aLOZ!2z');
define('PORDB', 'vhrrent1_portest');

# Hashing database infromation
define('hashHost', 'vhrrental.com');
define('hashUser', 'vhrrent1');
define('hashPassword', '34aLOZ!2z');
define('hashDB', 'vhrrent1_portest');

# Enable/Disable console logging

$ConsoleLog = True;

# Enable/Disable syslog logging
$SyslogLog = True;

# Syslog Protocol
$syslogProto = "udp";

# IP Address or FQDN of the Syslog server
$syslogIP = "192.168.100.2";

# Port for the request
$syslogPort = "4120";


# Enable/Disable file logging

$FileLog = True;

# Log file location

$logFileDir = 'logs/logFile.log';

# Enable/Disable email logging

$EmailLog = False;

# $smtpHost = 'just78.justhost.com';
# $smtpPort = 465;
# $smtpEncyption = 'ssl';
# $smtpUser = 'info@vhrrental.com';
# $smtpPassword = 'vhr1996';
# $smtpSubject = 'POR2WooCommerce';
# $smtpFromEmail = 'info@vhrrental.com';
# $smtpFromName = 'Info';
# $smtpDestEmail = 'dsmith@vhrrental.com';
# $smtpDestName = 'Dan';


# ID for the attribute created in WooCommerce for mapping rates
# Can be found by going to Dashboard -> Products -> Attributes -> Edit 
# If you look at the URL of the attribute that is being edited, there will be a attribute there that says edit={NUM} 
# This {NUM} is the ID you want to use

$rateAttributeID = 1;

# Mapping of POR numeric rates to text in WooCommerce
# Make sure text attributes exist in WooCommerce

$attributeMap = [
    "1" => "1 hour",
    "2" => "2 hours",
    "3" => "3 hours",
    "4" => "4 hours",
    "5" => "5 hours",
    "8" => "8 hours",
    "24" => "day",
    "120" => "5 days",
    "168" => "week",
    "240" => "10 days",
    "672" => "4 weeks",
    "678" => "4 weeks",
    "696" => "29 days",
    "1120" => "1120 hours",
    "1020" => "42 days"
];

# Sales tax for your state
$salesTax = 6.875;

# Flipped array of rates for use when doing a reverse lookup
$flippedMap = array_flip($attributeMap);

# OAuth 1.0 Client key and client secret for WooCommerce API
# OAuth 1.0 is used if connect is http
# The program does not currently support https
$cs = 'cs_4f9e24828ebab98cb94ceb22aeaec02434bc6fbe';
$ck = 'ck_fecf44658d2ab21a806d04493fadf6d13b7e0adb';

# Enable/Disable local/remote image storage
# If you are running the program on your web server where you can save directly to a web accessible directory you should enable Local Image Storage, otherwise you can use remote image storage to make images web accessible via ftps

# URL for where temp images will be stored ( Regadless of local or remote )
$porImageDirURL = 'http://vhrrental.com/por_temp_images';

$LocalImageStorage = False;

# Begin Remote Image Storage Parameters
# Use the following when $LocalImageStorage = False
    # Directory on the server where the program is being run that accepts temporary storage of images before ftps upload
    $ftpTempImageDir = '/tmp/por_temp_images';

    # Directory on the webserver to save files
    # Make sure it is web accessible
    # This is also temporary and corresponds to the $porImageDirURL
    $ftpImageDir = '.';

    # FTP information for uploading images so they can be imported into Wordpress and associated with product
    # It is smart to make sure your site supports ftps ( Note: This is not the same as sftp )
    
    $ftpsHost = "vhrrental.com";
    $ftpsUser = "por-integration@vhrrental.com";
    $ftpsPass = "YFNCKzc1Wd)@?";

# Begin Local Image Storage Parameters
# Use the following when $LocalImageStorage = True

    # Web accessible directory where images are to be stored
    # $localTempImageDir = '';

# Path where the text file which is used by chared will be stored
$charedPath = '/tmp/por_temp_images/chared.txt';

# Turns all PHP errors into exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
