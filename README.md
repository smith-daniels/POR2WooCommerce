Welcome to Point-of-Rental WooCommerce!
===================

The purpose of this plugin is to take a Point-of-Rental inventory and integrate it with WooCommerce. This plugin leverages multiple programs and is written in PHP. Provided your host supports php, this program should be fully compatible with shared hosts. Please see requirements section to confirm compatibility. 

----------


Requirements
-------------

 - PHP 7.0+ *(may work with lower versions, untested)*
    - Extensions
        - php_curl
        - php_mysqli
        - php_mbstring
        - php_gd2
        - php_ftp
 - WooCommerce API v2 (http://woocommerce.github.io/woocommerce-rest-api-docs/)
 - WooCommerce REST API PHP Library (https://github.com/woocommerce/wc-api-php)
 - Monolog (https://github.com/Seldaek/monolog)
 - Swift Mailer (https://github.com/swiftmailer/swiftmailer)

Installation (section under construction)
-------------

 1. Download latest release (master)

 2. Install dependencies via Composer

    `composer install`

 3. Setup MSA2MySQL
 
 4. Setup Database Scheme 

 3. Configure Script
```
# URL to the wordpress site you want to use to WooCommerce API of 
$siteURL = ""; 

# Point-of-Rental database login information 
define('PORHost','localhost');
define('PORUser', 'example_user');
define('PORPassword', 'example_password');
define('PORDB', 'database_name');

# Hashing database information 
define('hashHost', 'localhost'); 
define('hashUser', 'example_user'); 
define('hashPassword', 'example_password'); 
define('hashDB', 'database_name');
```
