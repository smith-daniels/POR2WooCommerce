<?php
/*
* File contains site specific specifications that would otherwise be hardcoded
*/

# URL to the wordpress site you want to use to WooCommerce API of
$siteURL = "";

# Point-of-Rental database login information
define('PORHost','');
define('PORUser', '');
define('PORPassword', '');
define('PORDB', '');

# Hashing database infromation
define('hashHost', '');
define('hashUser', '');
define('hashPassword', '');
define('hashDB', '');

# Enable/Disable console logging

$ConsoleLog = True;

# Enable/Disable syslog logging
$SyslogLog = True;

# Syslog Protocol
$syslogProto = "udp";

# IP Address or FQDN of the Syslog server
$syslogIP = "";

# Port for the request
$syslogPort = "";


# Enable/Disable file logging

$FileLog = True;

# Log file location

$logFileDir = 'logs/logFile.log';

# Enable/Disable email logging

$EmailLog = False;

# $smtpHost = '';
# $smtpPort = ;
# $smtpEncyption = '';
# $smtpUser = '';
# $smtpPassword = '';
# $smtpSubject = '';
# $smtpFromEmail = '';
# $smtpFromName = '';
# $smtpDestEmail = '';
# $smtpDestName = '';


# ID for the attribute created in WooCommerce for mapping rates
# Can be found by going to Dashboard -> Products -> Attributes -> Edit 
# If you look at the URL of the attribute that is being edited, there will be a attribute there that says edit={NUM} 
# This {NUM} is the ID you want to use

$rateAttributeID = 1;

# Mapping of POR numeric rates to text in WooCommerce
# Make sure text attributes exist in WooCommerce

$attributeMap = [
#    "1" => "1 hour",
#    "2" => "2 hours",
#    "3" => "3 hours",
#    "4" => "4 hours",
#    "5" => "5 hours",
#    "8" => "8 hours",
#    "24" => "day",
#    "120" => "5 days",
#    "168" => "week",
#    "240" => "10 days",
#    "672" => "4 weeks",
#    "678" => "4 weeks",
#    "696" => "29 days",
#    "1120" => "1120 hours",
#    "1020" => "42 days"
];

# Sales tax for your state
$salesTax = ;

# Flipped array of rates for use when doing a reverse lookup
$flippedMap = array_flip($attributeMap);

# OAuth 1.0 Client key and client secret for WooCommerce API
# OAuth 1.0 is used if connect is http
# The program does not currently support https
$cs = '';
$ck = '';

# Enable/Disable local/remote image storage
# If you are running the program on your web server where you can save directly to a web accessible directory you should enable Local Image Storage, otherwise you can use remote image storage to make images web accessible via ftps

# URL for where temp images will be stored ( Regadless of local or remote )
$porImageDirURL = '';

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
    
    $ftpsHost = "";
    $ftpsUser = "";
    $ftpsPass = "";

# Begin Local Image Storage Parameters
# Use the following when $LocalImageStorage = True

    # Web accessible directory where images are to be stored
    # $localTempImageDir = '';

# Turns all PHP errors into exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
