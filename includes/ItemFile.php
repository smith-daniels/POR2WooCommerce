<?php

/**
 *
 * Class object of an item from POR
 *
 */

class ItemFile
{
    public $name; // Item name
    public $dWaiver; // Damage waiver
    public $cat; // Category
    public $period = array(); // Rental periods
    public $rate; // Rental rates
    public $type; // Type of item
    public $num; // Item number ?
    public $storedPictures; // Item pictures
    public $specs; // Item specs
    public $printOut; // Usually warning information for a rental item
    protected $hashing; // Instance of the hashing class
    private $categoryHash; // Hash of the category information
    private $productHash; // Hash of the product (includes category information)
    private $log; // Monolog instance

    # Constructor
    # Builds all the information from the POR database given a product num
    # Params:
    # $SQL - SQL object that is connected to por import table
    # $hashing - Instance of hashing object
    # $num - Product number in POR
    public function __construct($SQL, $hashing, $num)
    {
        # Path to where temp file for chared exists
        global $log;

        $this->log = $log;

        try{

            # Fetch the information about the item
            $result = $SQL->ItemFile_GetItem($num);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 100;
            
            $this->log->error("Could not get item from database. $num ", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new ItemFileException((string)$code['code'], $code['code']);
        }
        # Save hashing instance
        $this->hashing = $hashing;

        # This should never really happen, but computers can be fickle
        if(mysqli_num_rows($result) == 0)
        {
            $code['code'] = 101;

            $this->log->error("Something serious went wrong here.", $code);

            throw new ItemFileException((string)$code['code'], $code['code']);
        }

        # Given the item number in the database, begin building the object
        # This really doesn't need to be a while loops since there should only be one item returned
        while($item = mysqli_fetch_object($result))
        {
            # Assignment
        
            try{
                //$this->name = $this->toUTF8($item->NAME);
                $this->name = $item->NAME;
            }
            catch( Throwable $e )
            {
                $code['code'] = 105;

                $this->log->error("Could not encode name to UTF_8", $code);

                throw new ItemFileException((string)$code['code'], $code['code']);
            }
            
            $this->dWaiver = $item->DMG;

            # Call the category helper function to building the information about what category this item belongs to
            try {
                $this->cat = $this->getCategoryInfo($SQL,$item->Category);
            }
            catch ( Throwable $e )
            {
                $code['code'] = 106;

                $this->log->error("Could not get category information. $item->Category", $code);
                $this->log->debug($e->getMessage(), $code);

                throw new ItemFileException((string)$code['code'], $code['code']);
            }

            # Get all the possible rental period options
            $i = 1;
            while($i <= 8)
            {
                $j = 'PER'.$i;
                if(array_search($item->{$j}, $this->period) == False)
                    $this->period[$i] = $item->{$j};
                else
                    $this->period[$i] = "0";
                $i++;
            }

            # Get all possible rental rate options
            $i = 1;
            while($i <= 8)
            {
                $j = 'RATE'.$i;
                $this->rate[$this->period[$i]] = $item->{$j};
                $i++;
            }

            $this->type = $item->TYPE;
            $this->num = trim($item->NUM);
        }

        try{
            $result = $SQL->ItemPicture_GetPictures($this->num);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 107;

            $this->log->error("Could not get images. $this->num", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new ItemFileException((string)$code['code'], $code['code']);
        }

        # Set to false to see if it gets populated
        $this->storedPictures = false;

        # Pictures are stored in a different table, loop through and get them here
        while($picture = mysqli_fetch_object($result))
        {
            # Picture is binary 
            $this->storedPictures[$picture->PictureIndex] = $picture->Picture;
        }

        try{
            $result = $SQL->ItemComments_Get($this->num);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 108;

            $this->log->error("Could not get comment information from database. $this->num", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new ItemFileException((string)$code['code'], $code['code']);
        }
        # Get information about the item stored in comments
        while($comments = mysqli_fetch_object($result))
        {
            try{
                #$this->specs = $this->toUTF8($comments->Specs);
                $this->specs = $comments->Specs;
            }
            catch ( Throwable $e )
            {
                $code['code'] = 109;

                $this->log->error("Could not encode specs to UTF_8", $code);

                throw new ItemFileException((string)$code['code'], $code['code']);
            }
            
            try{
                //$this->printOut = $this->toUTF8($comments->PrintOut);
                $this->printOut = $comments->PrintOut;
            }
            catch ( Throwable $e )
            {
                $code['code'] = 110;

                $this->log->error("Could not encode PrintOut to UTF_8", $code);

                throw new ItemFileException((string)$code['code'], $code['code']);
            }
        }

        # Create and store hashes
        $this->generateProductHash();
        $this->generateCategoryHash();
    }

    # Accessor function for hash of the item
    public function getProductHash()
    {
        return $this->productHash;
    }

    # Accessor function for hash of the category
    public function getCategoryHash()
    {
        return $this->categoryHash;
    }

    # Function used to determine if the category has changed
    public function checkCategoryHash()
    {
        return $this->hashing->compareHash($this->cat['slug'], $this->getCategoryHash())['result'];
    }

    # Function used to determine if the product has changed
    public function checkProductHash()
    {
        return $this->hashing->compareHash($this->num, $this->getProductHash());
    }

    # Function used to create a product hash in the database
    # Params:
    # $id - Item number in POR
    # $hash - Hash string
    # $wp_id - Wordpress id
    public function saveHash($id, $hash, $wp_id)
    {
        $this->hashing->saveHash($id, $hash, $wp_id);
    }

    # Function used to update a product's hash in the database
    # Params:
    # $id - Item number in POR
    # $hash - Hash string
    # $wp_id - Wordpress ID
    public function updateHash($id, $hash, $wp_id)
    {
        $this->hashing->updateHash($id, $hash, $wp_id);
    }

    # Get name and picture information for category
    # Params:
    # $SQL - Instace of SQL class
    # $categoryID - ID of the category in POR
    private function getCategoryInfo($SQL, $categoryID)
    {
        # Save the id of the category in POR
        $data['id'] = $categoryID;
        
	    # Query the database for the name of the category
        $result = $SQL->ItemCategory_GetName($categoryID);

        # Fetch the name as an object
        $catName = mysqli_fetch_object($result);
        
	    # If the query did not return a name, we sent a bad ID
        if(empty($catName->Name))
        {
            $code['code'] = 106;
            
            $this->log->error("Failure in looking up a category by id", $code);
            
            throw new ItemFileException((string)$code['code'], $code['code']);
        }
        else
        {
            # Save the name
            $data['name'] = $catName->Name;

            try{
                # Create a Sanitize slug of the name
	            $data['slug'] = $this->slugify($data['name']);
            }
            catch ( Throwable $e )
            {
                $code['code'] = 111;

                $this->log->error("Failed to create slug of category name. ".$data['name'], $code);

                throw $e;
            }

            # Query the database for the image
            $result = $SQL->ItemCategory_GetPicture($categoryID);

            # Get the image as an object
            $catImage = mysqli_fetch_object($result);

            # Save the image binary
            $data['picture'] = isset($catImage->Picture) ? $catImage->Picture : null;

            # Create an instance of CommerceConnect to find out if the category exists in WooCommerce
            # This is a little cleaner than passing the SQL database information 
            # But I'm pretty sure this doesn't need to be here, will revist
            #$commerceConnect = new CommerceConnect();

            #$data['new'] = !$commerceConnect->pingCategory($data['name']);

        }

        return $data;
    }    

    # Generate the hash of the item based on all the information available
    protected function generateProductHash()
    {
        #$this->productHash = md5(var_export($this, true));
        $this->productHash = md5(var_export($this->name, true)); // 0
        $this->productHash .= md5(var_export($this->dWaiver, true)); // 1
        $this->productHash .= md5(var_export($this->cat['id'], true)); // 2
        $this->productHash .= md5(var_export($this->period, true).var_export($this->rate, true)); // 3
        $this->productHash .= md5(var_export($this->num, true)); // 4
        $this->productHash .= md5(var_export($this->storedPictures, true)); // 5
        $this->productHash .= md5(var_export($this->specs, true)); // 6
        //$this->productHash .= md5(var_export($this->printOut, true)); // 7
    }

    # Generate the hash of just the category
    protected function generateCategoryHash()
    {
        $this->categoryHash = md5(var_export($this->cat, true));
    }

    # Returns an array designating what parts of a product need to be updated based on hash
    public function getUpdate()
    {
        $hash = $this->hashing->getHash($this->num);

        if(strlen($hash) !== 224)
            return false;
        else
        {
            $update = array();
            $dbHash = str_split($hash, 32);
            $curHash = str_split($this->productHash, 32);
            $i = 0;

            while($i < 7)
            {
                $update[$i] = strcmp($dbHash[$i], $curHash[$i]) == 0 ? false : true;
                $i++;
            }

            return $update;
        }

    }

    # Function for creating a slug 
    # From: http://ourcodeworld.com/articles/read/253/creating-url-slugs-properly-in-php-including-transliteration-support-for-utf-8
    public function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicated - symbols
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text))
            return 'n-a';

        return $text;
    }

}
