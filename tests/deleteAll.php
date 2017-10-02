<?php

/**
 * File which deletes all WooCommerce Products
 */

require_once(__DIR__.'/../load.php');

function massDelete()
{
    global $log;

    # CommerceConnect Instance
    $commerceConnect = new CommerceConnect();

    $json = $commerceConnect->purge();
}

massDelete();
