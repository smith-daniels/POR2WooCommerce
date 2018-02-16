Welcome to Point-of-Rental WooCommerce!
===================

The purpose of this plugin is to take a Point-of-Rental inventory and integrate it with WooCommerce. This plugin leverages multiple programs and is written in PHP. Provided your host supports python, this program should be fully compatible with shared hosts. Please see requirements section to confirm compatibility. 

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
 - WooCommerce API v2
 - WooCommerce REST API PHP Library 
 - Monolog
 - Chared ( http://corpus.tools/wiki/Chared )

Installation
-------------

 1. Download latest release
 2. Install WooCommerce REST API PHP Library

    `composer require automattic/woocommerce`

 3. Install Monolog

    `composer require monolog/monolog`
 4. Install Chared ( http://corpus.tools/wiki/Chared )

