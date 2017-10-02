<?php

require_once __DIR__."/../../load.php";

use PHPUnit\Framework\TestCase;

/**
 *  * Test class for chared python program wrapped with PHP Class
 *   */
class SQLHashTest extends TestCase
{
    public function testInitPOR()
    {
        # Create an instance to MySQL version of POR database
        $SQLPOR = new SQL(PORHost, PORUser, PORPassword, PORDB);

        $this->assertNotNull($SQLPOR);
    }
    public function testInitHash()
    {
        # Create an instance to MySQL database holding item hashes
        $db = new SQLHash(hashHost, hashUser, hashPassword, hashDB);

        $this->assertNotNull($db);
    }

}
