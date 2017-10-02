<?php

require_once __DIR__."/../../load.php";

use PHPUnit\Framework\TestCase;

/**
 *
 * Unit testsing for CommerceConnect using static ItemFile class
 * SELECT * FROM `Hashes` WHERE `ID`='test999' OR `ID`='unit-test-category' OR `ID`='lorem_ipsum_test999'
 */
class CommerceConnectAndHashingTest extends TestCase
{
    private static $item; // static ItemFile
    private static $connect; // Instance of CommerceConnect
    private static $result; // Return from WooCommerce
    private static $hashing; // Instance of hashing
    private static $deleted; //
    private static $sku; //
    private static $hash; //
    private static $id; //

    # Create class instances needed for tests
    public static function setUpBeforeClass()
    {
        self::$item = new ItemFileTesting();
        self::$connect = new CommerceConnect();
        
        # Create an instance to MySQL database holding item hashes
        $SQLHash = new SQLHash(hashHost, hashUser, hashPassword, hashDB);

        # Instance of the hashing class
        self::$hashing = new Hashing($SQLHash); 

        self::$sku = 'lorem_ipsum_test999';
        self::$hash = '0ac9737b2885187b3b5866e40cf38ff5';
        self::$id = 158690548692543;

        self::$deleted = 0;
    }

    # Creates the product based on the static ItemFile
    public function testCreateProduct()
    {
        # If there is an exception thrown in creating the product the tests will all fail
        try{
            # Stub to avoid risky test call
            $this->assertTrue(true);

            self::$connect->createProduct(self::$item);
        }
        catch ( Throwable $e )
        {
            $this->fail();
        }
    }

    /**
     * Makes sure product can be fetched. Also is used for the rest of the tests
     * @depends testCreateProduct
     */
    public function testGetProduct()
    {
        try{
            # Stub to avoid risky test call
            $this->assertTrue(true); 

            self::$result = self::$connect->getProduct(self::$item->num);
        }
        catch ( Throwable $e )
        {
            $this->fail();
        }
    }

    /** 
     * Compare names
     * @depends testGetProduct
     */
    public function testName()
    {
        $this->assertEquals(self::$item->name, self::$result['name']);
    }

    /**
     * Because WooCommerce/Wordpress performs some formatting on the text sent via the API, like adding 
     * html tags, we will run with the similiarity comparison for the description
     * @depends testName
     */
    public function testDescription()
    {
        similar_text(self::$item->specs, self::$result['description'], $percent);

        $this->assertGreaterThan(97, $percent);
    }

    /**
     * Makes sure correct damage waiver was set
     * @depends testDescription
     */
    public function testDmg()
    {
        $this->assertEquals('dmgwaiver_'.self::$item->dWaiver, self::$result['tax_class']);
    }

    /**
     * Ensure all variations were added
     * @depends testDmg
     */
    public function testVariations()
    {
        $variations = self::$connect->getVariations(self::$result['id']);

        foreach($variations as $variation)
        {
                $pass = false;
                foreach(self::$item->rate as $rate)
                {
                    if($rate == $variation['price'])
                    {
                        $pass = true;
                        break;
                    }
                }
                $this->assertTrue($pass);
        }      
    }

    /**
     * Makes sure the correct category was associated
     * @depends testVariations
     */
    public function testCategory()
    {
        $category = self::$result['categories'][0];

        $this->assertEquals(self::$item->cat['name'], $category['name']);
    }

    /**
     * @depends testCategory
     */
    public function testSaveHash()
    {
        self::$hashing->saveHash(self::$sku, self::$hash, self::$id);
        $result = self::$hashing->compareHash(self::$sku, self::$hash);
        $this->assertTrue($result['result']);
    }

    /**
     * @depends testSaveHash
     */
    public function testUpdateHash()
    {
        self::$hash = 'c905e083a85ede7600a3404ed95ea7df';
        self::$hashing->updateHash(self::$sku, self::$hash, self::$id);
        $result = self::$hashing->compareHash(self::$sku, self::$hash);
        $this->assertTrue($result['result']);
    }

    /**
     * @depends testUpdateHash
     */
    public function testDeleteHash()
    {
        self::$hashing->deleteHash(self::$sku);
        $result = self::$hashing->compareHash(self::$sku, self::$hash);

        $this->assertFalse($result['result']);
        $this->assertEquals(0, $result['WP_ID']);
        $this->assertEquals(0, $result['Rows']);
    }


    /**
     * Does a hash check since we have the ItemFile here already
     * This also in effect tests Hashing::compareHash($a, $b)
     * @depends testUpdateHash
     */
    public function testCheckHash()
    {
        $res = checkHash(self::$item, self::$item->num);
        $this->assertEquals(0, $res['code']);
        $this->assertEquals(self::$result['id'], $res['WP_ID']);

        $i = clone self::$item;
        $i->name = 'Testy McTestFace';
        $i->hashRegen();
        $res = checkHash($i, $i->num);
        $this->assertEquals(1, $res['code']);
        $this->assertEquals(self::$result['id'], $res['WP_ID']);

        $i = clone self::$item;
        $i->num = 'test99';
        $i->hashRegen();
        $res = checkHash($i, $i->num);
        $this->assertEquals(2, $res['code']);

    }


    /**
     * Deletes product and category
    * @depends testCheckHash
    */
    public function testDelete()
    {
        try{

            # Stub to avoid risky test call
            $this->assertTrue(true);

            self::$connect->deleteProduct(self::$item->num);

            self::$hashing->deleteHash(self::$item->num); 

            self::$connect->deleteCategory(self::$result['categories'][0]['id']);

            self::$hashing->deleteHash(self::$item->cat['slug']);
    
            self::$deleted = 1;
        }
        catch ( Throwable $e )
        {
            self::$deleted = 2;
            $this->fail();
        }
    } 

    /**
     * Simple test to make sure temp ItemFile compiles
     */
    public function testTempClass()
    {
        $temp = new ItemFileTesting();

        $this->assertEquals('Test Product', $temp->name);
    }

    public function __destruct()
    {
        if(self::$deleted == 0)
        {
            self::$connect->deleteProduct(self::$item->num);

            self::$hashing->deleteHash(self::$item->num);

            self::$connect->deleteCategory(self::$result['categories'][0]['id']);

            self::$hashing->deleteHash(self::$item->cat['slug']);

            self::$deleted = 1;
        }
    }
}

class ItemFileTesting extends ItemFile
{
    public $name = "Test Product";
    public $num = "test999";
    public $specs = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut aliquet semper congue. Ut volutpat, ipsum non feugiat consectetur, augue libero ultricies dui, eget convallis nibh ligula in tellus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed pharetra nisi vitae odio commodo, vel aliquet dui aliquet. Suspendisse commodo ante ac molestie tincidunt. Nulla arcu felis, auctor et scelerisque eu, volutpat sit amet neque. Integer in mollis sem, sit amet tristique neque. Donec nec tortor euismod, viverra libero sed, consectetur risus. Maecenas diam augue, interdum sed dignissim quis, viverra eu eros. Curabitur egestas ligula vel purus volutpat euismod. Proin id tellus nunc. Nunc elementum, diam eu faucibus hendrerit, sem nibh ornare odio, quis euismod enim mauris at nisl. Pellentesque accumsan, risus quis ornare laoreet, ex sapien ullamcorper nulla, quis ullamcorper nulla nibh eget leo. Donec a nisl et quam faucibus porta quis id ante. In ipsum mauris, aliquam id neque ut, congue congue sem. Morbi non maximus mi, at molestie justo.";
    public $dWaiver = "2";
    public $cat = ['id' => 99999, 'name' => 'Unit Test Category', 'slug' => 'unit-test-category'];
    public $period = [1 => "4", 2 => "8", 3 => "24"];
    public $rate = [4 => "50.00", 8 => "100", 24 => "210"];
    public $type = 'v';
    public $storedPictures;
    public $printOut = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut aliquet semper congue. Ut volutpat, ipsum non feugiat consectetur, augue libero ultricies dui, eget convallis nibh ligula in tellus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed pharetra nisi vitae odio commodo, vel aliquet dui aliquet. Suspendisse commodo ante ac molestie tincidunt. Nulla arcu felis, auctor et scelerisque eu, volutpat sit amet neque. Integer in mollis sem, sit amet tristique neque. Donec nec tortor euismod, viverra libero sed, consectetur risus. Maecenas diam augue, interdum sed dignissim quis, viverra eu eros. Curabitur egestas ligula vel purus volutpat euismod. Proin id tellus nunc. Nunc elementum, diam eu faucibus hendrerit, sem nibh ornare odio, quis euismod enim mauris at nisl. Pellentesque accumsan, risus quis ornare laoreet, ex sapien ullamcorper nulla, quis ullamcorper nulla nibh eget leo. Donec a nisl et quam faucibus porta quis id ante. In ipsum mauris, aliquam id neque ut, congue congue sem. Morbi non maximus mi, at molestie justo.";

    private $categoryHash; // Hash of the category information
    private $productHash; // Hash of the product (includes category information)
    private $chared; // Instance of chared class
    private $charedPath; // Path to file where text will be stored to be evaluated by chared
    private $log; // Monolog instance

    public function __construct()
    {
        global $charedPath, $log;

        $this->charedPath = $charedPath;

        $this->log = $log;

        $this->storedPictures = [ 1 => file_get_contents(__DIR__."/image.png") ];

        $this->cat['picture'] = file_get_contents(__DIR__.'/image.png');

        $SQLHash = new SQLHash(hashHost, hashUser, hashPassword, hashDB);

        $this->hashing = new Hashing($SQLHash);

        # Create and store hashes
        $this->generateProductHash();
        $this->generateCategoryHash();
    }

    public function hashRegen()
    {
        # Create and store hashes
        $this->generateProductHash();
        $this->generateCategoryHash();
    }
}
