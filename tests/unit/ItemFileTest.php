<?php

require_once __DIR__."/../../load.php";

use PHPUnit\Framework\TestCase;

/**
 *  * Test class for chared python program wrapped with PHP Class
 *   */
class ItemFileTest extends TestCase
{
    private static $db;
    private static $itemF;
    private static $hashing;
    private static $catName = 'Test Category';
    private static $catSlug = 'test-category';
    private static $catID = 999;
    private static $key = 'unitTest';
    private static $name = 'HAMMER, JACK 60LB';
    private static $per1 = 24;
    private static $per2 = 168;
    private static $per3 = 672;
    private static $rate1 = 33;
    private static $rate2 = 98;
    private static $rate3 = 294;
    private static $dmg = 14;
    private static $num = '999999';
    private static $comment = "20 LB TANK CAPACITY: 4.7 LBS PROPANE\r\n";
    private static $deleted = 0;

    public static function setUpBeforeClass()
    {
        self::$db = new SQL(PORHost, PORUser, PORPassword, PORDB);
        
        # Create an instance to MySQL database holding item hashes
        $SQLHash = new SQLHash(hashHost, hashUser, hashPassword, hashDB);

        # Instance of the hashing class
        self::$hashing = new Hashing($SQLHash);

        $sql = "INSERT INTO `ItemCategory` () VALUES ('".self::$catName."', ".self::$catID.", '                                                  ', 0, 0, 0, '', '', ' ', ' ', ' ', ' ', ' ', ' ', 0, ' ', ' ', 0, NULL, '', '', '', '')";

        self::$db->query($sql);

        self::$deleted = 1;
        
        $sql = "INSERT INTO `ItemFile` () VALUES ('".self::$key."', '".self::$name."', '', 4, 1, 0, 0, 14, 0, NULL, ".self::$catID.", 'V', 0, '                         ', '', '', ".self::$per1.", ".self::$per2.", ".self::$per3.", 0, 0, 0, 0, 0, 0, 0, ".self::$rate1.", ".self::$rate2.", ".self::$rate3.", 0, 0, 0, 0, 0, 0, 0, '', 0, 'JACK HAMMER', '".self::$num."', '', '                              ', '', '', 0, 0, '', '015-0150', 0, 0, 0, 1, 0, 1, 0, ' 16083', ' 44527', '16759', NULL, 0, 'S', 5, 0, 0, 0, 0, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2017-06-22 00:00:00', '1503010 SULLAIR', 0, '', '', '000', '000', '', '', '', 0, '015-0150', '', 0, 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '', 0, 0, '', '', '', 0, 0, '', '', 0, 0, 0, 0, '', NULL, 0, 0, NULL, '', '', '', 0, 0, 'EQ', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '', '', '', '', 0, 0, 0, 0)";

        self::$db->query($sql);

        self::$deleted = 2;

        $sql = "INSERT INTO `ItemComments` () VALUES ('".self::$num."', '".self::$comment."', '', '')";

        self::$db->query($sql);

        self::$deleted = 3;

        $sql = "INSERT INTO `ItemPicture` () VALUES ('".self::$num."', ".'0x'.bin2hex(file_get_contents(__DIR__.'/image.png')).", '2016-01-30 00:00:00', 0)";

        self::$db->query($sql);

        self::$deleted = 4;

        $sql = "INSERT INTO `ItemCategoryPicture` () VALUES (".self::$catID.", ".'0x'.bin2hex(file_get_contents(__DIR__.'/image.png')).")";

        self::$db->query($sql);

        self::$deleted = 5;
    }

    public function testConstruct()
    {
        $this->assertTrue(true);

        self::$itemF = new ItemFile(self::$db, self::$hashing, 999999);
    }

    /**
     * @depends testConstruct
     */
    public function testName()
    {
        $this->assertEquals(self::$name, self::$itemF->name);
    }

    /**
     * @depends testConstruct
     */
    public function testDWaiver()
    {
        $this->assertEquals(self::$dmg, self::$itemF->dWaiver);
    }

    /**
     * @depends testConstruct
     */
    public function testCategory()
    {
        $this->assertEquals(self::$catID, self::$itemF->cat['id']);
        $this->assertEquals(self::$catName, self::$itemF->cat['name']);
        $this->assertEquals(self::$catSlug, self::$itemF->cat['slug']); 
    }

    /**
     * @depends testConstruct
     */
    public function testPeriod()
    {
        $this->assertEquals(self::$per1, self::$itemF->period[1]);
        $this->assertEquals(self::$per2, self::$itemF->period[2]);
        $this->assertEquals(self::$per3, self::$itemF->period[3]);
        $this->assertEquals(0, self::$itemF->period[4]);
        $this->assertEquals(0, self::$itemF->period[5]);
        $this->assertEquals(0, self::$itemF->period[6]);
        $this->assertEquals(0, self::$itemF->period[7]);
        $this->assertEquals(0, self::$itemF->period[8]);
    }

    /**
     * @depends testConstruct
     */
    public function testRate()
    {
        $this->assertEquals(self::$rate1, self::$itemF->rate[self::$per1]);
        $this->assertEquals(self::$rate2, self::$itemF->rate[self::$per2]);
        $this->assertEquals(self::$rate3, self::$itemF->rate[self::$per3]);
        $this->assertEquals(0, self::$itemF->rate[0]);
    }

    /**
     * @depends testConstruct
     */
    public function testType()
    {
        $this->assertEquals('V', self::$itemF->type);
    }

    /**
     * @depends testConstruct
     */
    public function testNum()
    {
        $this->assertEquals(self::$num, self::$itemF->num);
    }

    /**
     * @depends testConstruct
     */
    public function testPictures()
    {
        $this->assertEquals(file_get_contents(__DIR__.'/image.png'), self::$itemF->storedPictures[0]);
        $this->assertEquals(file_get_contents(__DIR__.'/image.png'), self::$itemF->cat['picture']);
    }

    /**
     * @depends testConstruct
     */
    public function testSpecs()
    {
        $this->assertEquals(self::$comment, self::$itemF->specs);
    }

    /**
     * @depends testConstruct
     */
    public function testPrintOut()
    {
        $this->assertEquals('', self::$itemF->printOut);
    }

    public function __destruct()
    {
        $sql1 = "DELETE FROM `ItemFile` WHERE `NUM`='".self::$num."'";

        $sql2 = "DELETE FROM `ItemComments` WHERE `NUM`='".self::$num."'";

        $sql3 = "DELETE FROM `ItemPicture` WHERE `Inum`='".self::$num."'";

        $sql4 = "DELETE FROM `ItemCategory` WHERE `Category`=".self::$catID;

        $sql5 = "DELETE FROM `ItemCategoryPicture` WHERE `Category`=".self::$catID;

        if(self::$deleted < 6)
        {
            if(self::$deleted > 0)
            {
                self::$db->query($sql4);
            }
            if(self::$deleted > 1)
            {
                self::$db->query($sql1);
            }
            if(self::$deleted > 2)
            {
                self::$db->query($sql2);
            }
            if(self::$deleted > 3)
            {
                self::$db->query($sql3);
            }
            if(self::$deleted > 4)
            {
                self::$db->query($sql5);
            }

            self::$deleted = 6;
        }
    }

}
