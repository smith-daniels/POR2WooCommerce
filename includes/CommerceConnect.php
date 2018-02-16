<?php

/**
 * Class used to interface with the WooCommerce API of the website
 *
 **/

# Need to use the wrapper namespace to properly create an instance of the client class
use Automattic\WooCommerce\Client;

class CommerceConnect
{
    private $woocommerce; // WooCommerce api wrapper instance
    private $attributeData; // Stores attribute data to cut down on network calls
    private $productData; // holds the ItemFile object
    private $rateAttributeID; // stores the id locally from the config file
    private $attributeMap; // stores the map locally from the config file
    private $imageRef; // Used to keep a reference to the images so they aren't dereferenced and deleted
    private $imageRefCat; // Use to keep a reference to the category image so they aren't dereerenced and deleted
    private $salesTax; // Used to save from config.php
    private $dryRun; // Detects if this is a dry run
    private $log; // Monolog


    # Setup connection to WooCommerce API via the WooCommerce PHP Wrapper
    public function __construct()
    {
        # Pull in configuration variables to the class
        global $log, $dryRun, $salesTax, $cs, $ck, $rateAttributeID, $attributeMap, $siteURL;

        # Store instance locally
        $this->log = $log;

        # Store instance locally
        $this->dryRun = $dryRun;

        # Save salesTax property
        $this->salesTax = $salesTax;

        # Set the global variables to local variables
        # We do not need to set cs and ck because they aren't used outside the constructor
        $this->rateAttributeID = $rateAttributeID;
        $this->attributeMap = $attributeMap;

        try {
            # Set the api version to avoid future compatibility issues from updates
            $this->woocommerce = new Client(
                $siteURL,
                $ck,
                $cs,
                [
                    'wp_api' => true,
                    'version' => 'wc/v2',
                    'timeout'   => 60
                ]
            );
        }
        catch ( Throwable $e )
        {
            $code['code'] = 200;

            $this->log->emergency("Failed to create an instance of the WooCommerce API Wrapper", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }
    }

    # Create a product
    # Params: $product - ItemFile object
    public function createProduct($product)
    {
        $this->productData = $product;

        $productID = " Product NUM: $product->num ";

        # Try to load all the neccessary data via the API
        try {
            $data = [
                'name'              => $product->name,
                'sku'               => $product->num,
                'type'              => 'variable',
                'regular_price'     => '0.00',
                'description'       => $product->specs,
                'categories'        => $this->getCategory(),
                'images'            => $this->getImages(),
                'attributes'        => $this->buildAttributes(),
                'tax_class'         => $this->getTaxClass()
            ];
        }
        catch(Throwable $e)
        {
            $code['code'] = 201;

            $this->log->error("Failed fetching data to create product. $productID", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        # Try to post the product
        try {   
            $code['code'] = 500;

            $result = $this->post('products', $data, $code);
        
        }
        catch ( Throwable $e)
        {
            $code['code'] = 202;

            $this->log->error("Failed creating product. $productID", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        } 

        # Now the product has been created try to add the variations to it
        try {

            # Once the product has been created add variations for each rental period rate
            $this->addVariations($result['id']);

        }
        catch( Throwable $e)
        {
            $code['code'] = 203;

            $this->log->error("Failed adding variations. $productID", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        # Make sure the hash gets saved to the database properly
        try {
            $product->saveHash($result['sku'], $product->getProductHash(), $result['id']);
        }
        catch ( Throwable $e)
        {
            $code['code'] = 204;

            $this->log->error("Failed adding hash to the databse. $productID", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }
    }

    # Update a product
    # Params:
    # $id - ID of the product in Wordpress/WooCommerce
    # $product - ItemFile object
    # (optional) $sku - SKU of the item in WooCommerce
    public function updateProduct($id, $product, $sku = null)
    {
        # Get the items that need to be updated
        try {
            $update = $product->getUpdate();

            if($update === false)
            {
                $this->log->notice("Could not break apart hash, updating everything.");
                $i = 0;
                while($i < 7)
                {
                    $update[$i] = true;
                    $i++;
                }
            }
        }
        catch ( Throwable $e )
        {
            $code['code'] = 218;

            $this->log->error("Could not get the parts of the product to update.", $code);
            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        # Fetch product from WooCommerce
        try {
            $code['code'] = 214;
            $this->get("products/$id", $code);
        }
        catch ( Throwable $e )
        {
            $this->log->error("Product does not exist in WooCommerce, but hash appears to still exist in hash database.", $code);
            throw new CommerceConnectException((string)$code['code'], $code['code']);
        } 

        try{
            # If we have a sku, we most likely don't have a valid id so let's get one
            if(!is_null($sku))
                $id = $this->getFromSKU($sku);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 209;

            $this->log->error("Could not get an id from sku. $sku", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        $this->productData = $product;

        $productID = " Product NUM: $product->num "; 

        # Get all the data we need to post the update
        try {

            $data = array();

            if($update[0])
                $data['name']       = $product->name;
            if($update[1])
                $data['tax_class']  = $this->getTaxClass();
            if($update[2])
                $data['categories'] = $this->updateCategory();
            if($update[3])
                $data['attributes'] = $this->buildAttributes();
            if($update[4])
                $data['sku']        = $product->num;
            if($update[5])
                $data['images']     = $this->updateImages($id);
            if($update[6])
                $data['description'] = $product->specs;

        }
        catch ( Throwable $e)
        {
            $code['code'] = 205;

            $this->log->error("Failed to get the data to perform an update. $productID", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        # Try to post the update
        try{
            $code['code'] = 501;

            $result = $this->post('products/'.$id, $data, $code);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 206;

            $this->log->error("Failed to post the updated data. $productID", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        # Try to add variations to products
        try{

            if($update[3])
                $this->updateVariations($id); 
        
        }
        catch ( Throwable $e )
        {
            $code['code'] = 207;

            $this->log->error("Failed to update variations. $productID", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        try{
      
            $this->productData->updateHash($result['sku'], $this->productData->getProductHash(), $result['id']);
        
        }
        catch ( Throwable $e )
        {
            $code['code'] = 208;

            $this->log->error("Failed to update hash in database. $productID", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        return true;
    }
    
    # Deletes a product given the sku
    public function deleteProduct($sku)
    {
        try{

            # Get the wordpress id from the sku
            $id = $this->getFromSKU($sku);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 210;

            $this->log->error("Failed to get id from sku. $sku", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        # If it is not null, we need to delete it
        # If it is null then the item has already been removed
        if(!is_null($id))
        {
            try {
                $code['code'] = 700;

                $result = $this->delete('products/'.$id, $code);
            }
            catch ( Throwable $e )
            {
                $code['code'] = 211;

                $this->log->error("Failed to delete product. $id", $code);
                $this->log->debug($e->getMessage(), $code);

                throw new CommerceConnectException((string)$code['code'], $code['code']);
            }
        }

        return true;
    }

    # Deletes a category given the id
    public function deleteCategory($id)
    {
        try{
            $code['code'] = 702;

            $result = $this->delete('products/categories/'.$id, $code);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 217;

            $this->log->error("Failed to delete category. $id", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }
    }

    # Gets product from WooCommerce
    public function getProduct($sku)
    {
        try{
            # Get the wordpress id from the sku
            $id = $this->getFromSKU($sku);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 215;

            $this->log->error("Failed to get id from sku. $sku", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }

        try{
            $code['code'] = 607;

            $result = $this->get('products/'.$id, $code);

            return $result;
        }
        catch ( Throwable $e )
        {
            $code['code'] = 216;
         
            $this->log->error("Failed to fetch a single product from WooCommerce", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }
        
    }

    # Gets information on a product variation
    public function getVariations($id)
    {
        try{
            $code['code'] = 608;

            $result = $this->get('products/'.$id.'/variations', $code);

            return $result;
        }
        catch ( Throwable $e )
        {
            $code['code'] = 218;

            $this->log->error("Failed to fetch variations for a product from WooCommerce", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }
    }

    # Delete all products
    public function purge()
    {

        try{
            $code['code'] = 600;
            # Fetch a list of <=100 products in the inventory
            $result = $this->get('products', $code, 0, ['per_page' => 100]);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 212;

            $this->log->error("Failed to get a list of products", $code);
            $this->log->debug($e->getMessage(), $code);

            throw new CommerceConnectException((string)$code['code'], $code['code']);
        }
        # If the there are still products in the inventory
        while(isset($result[0]['id']))
        {
            # Generate post data for batch delete
            foreach($result as $product)
            {
                $data['delete'][] = $product['id'];
            }

            try{
                # Send batch delete request
                $code['code'] = 502;
                $this->post('products/batch', $data, $code, 0);
            
            }
            catch ( Throwable $e )
            {
                $code['code'] = 213;

                $this->log->error("Failed to process batch delete", $code);
                $this->log->debug($e->getMessage(), $code);

                throw new CommerceConnectException((string)$code['code'], $code['code']);
            }

            # Empty data set for next run
            unset($data);

            try {

                # Fetch next set of <= 100
                $code['code'] = 600;
                $result = $this->get('products', $code, 0, ['per_page' => 100]);
            }
            catch ( Throwable $e )
            {
                $code['code'] = 212;

                $this->log->error("Failed to get list of products", $code);
                $this->log->debug($e->getMessage(), $code);

                throw new CommerceConnectException((string)$code['code'], $code['code']);
            }
        }

        return true;
    }

    # Function to get a wp_id based on a sku
    private function getFromSKU($sku)
    {
        $code['code'] = 601;
        $result = $this->get('products', $code, 0, ['sku' => $sku]);

        return $result[0]['id'];
    }

    # Helper method to create data array to send in post request for creating a prodcut
    private function getCategory()
    {
        # Instantiate the array
        # Pretty sure PHP doesn't require this, but I stuck doing python esq. stuff now
        $data = array();

        # Use the search function to either return false or return the category ID 
        $categoryID = $this->categorySearch($this->productData->cat['name']);

        # If this category does not already exist in WooCommerce, we need to create it
        if(!$categoryID)
        {
            # Create the new category and store the results so we can send the correct category id
            $newCategory = $this->createCategory($this->productData->cat);

            $data[0]['id'] = $newCategory['id'];

            return $data;
        }

        # The category already exists, lets use that ID
        $data[0]['id'] = $categoryID;

        return $data;
    } 
    
    # Helper method to use when deciding if a category needs to be updated
    public function updateCategory($independent = false, $itemF = null)
    {
        # Because we may call this method indepenent of any other functions in this class we need to 
        # make sure we deal with the fact that the productData variable may not be set
        # Save and overwrite product data for this run
        if($independent)
        {
            $savedProductData = $this->productData;
            $this->productData = $itemF;
        }

        # Look to see if the category already exists by name
        $categoryID = $this->categorySearch($this->productData->cat['name']);

        # If the category does not already exist, we can just go and create it
        if(!$categoryID)
        {
            return $this->getCategory();
        }

        # If category needs to be updated
        # True result means hashes are the same
        if(!$this->productData->checkCategoryHash())
        {
            # Prepare the data
            $data = [
                'name'      => $this->productData->cat['name'],
                'slug'      => $this->productData->cat['slug'],
                'image'     => $this->getCategoryImage()
            ];

            # Send the update data
           
            $code['code'] = 503;

            $jsonData = $this->post('products/categories/'.$categoryID, $data, $code);

            $this->productData->updateHash($jsonData['slug'], $this->productData->getCategoryHash(), $categoryID);
        }

        # Clear the data variable (mostly because I hate having too many variable names
        unset($data);

        # Set the id to be associated with the product
        $data[0]['id'] = $categoryID;

        # Reset product data just in case
        if($independent)
            $this->productData = $savedProductData;

        return $data;
    }


    # Create a category
    private function createCategory($category)
    {
        # Transpose the information from the POR db into the proper fields for the WooCommerce API
        $data = [
                'name'      => $this->productData->cat['name'],
                'slug'      => $this->productData->cat['slug'],
                'image'     => $this->getCategoryImage()
            ];

        $code['code'] = 504;

        $jsonData = $this->post('products/categories', $data, $code);
            
        $this->productData->saveHash($jsonData['slug'], $this->productData->getCategoryHash(), $jsonData['id']);

        # Return the jsonData from the API call
        return $jsonData;
    }

    # Fetch and create an image file for the category image
    private function getCategoryImage()
    {
        $imageData = array();

        if(!empty($this->productData->cat['picture']))
        {
            # Take the binary of the category image and turn it into an image object
            $this->imageRefCat = new Image($this->productData->cat['picture']);

            # Get the url of the new image
            $imageData['src'] = $this->imageRefCat->getURL();
        }

        return $imageData;
    }

    # Search all categories to see if it already exists
    private function categorySearch($category)
    {

        # Fetch all the categories
        $code['code'] = 602;

        $categories = $this->get('products/categories', $code, 0, ['per_page' => 100]);

        # Sanitize the category name so we are comparing equivelent values
        $catSlug = $this->productData->slugify($category);

        # Search all the categories for the given category slug
        $slugs = array_column($categories, 'slug');
        $keyResult  = array_search($catSlug, $slugs, True);

        # If the keyResult is False, return False
        if($keyResult === False)
            return False;

        # Otherwise, return the id of the matched category
        return $categories[$keyResult]['id'];
    }

    # Helper function to generate image links
    private function getImages()
    {
        # Create empty array for image data
        $imageData = array();

        # Counter for array index
        $i = 0;

        # Make sure that it isn't empty
        if($this->productData->storedPictures === false)
            return $imageData;

        # Loop through each picture for the item
        foreach($this->productData->storedPictures as $key=>$pictureBinary)
        {
            # Build the image and save it to the file system temporarily
            $this->imageRef[$i] = new Image($pictureBinary);

            $imageData[$i]['src'] = $this->imageRef[$i]->getURL();
            $imageData[$i]['position'] = $i;

            $i++;
        }

       return $imageData; 
    } 

    # Helper function to update a product's image
    private function updateImages($id)
    {
        # Error Code 4
        $code['code'] = 505;
        $this->post('products/'.$id, array( "images" => ""), $code);

        return $this->getImages();
    }

    # Helper function to generate and add attibute data to the product
    private function addVariations($id)
    {
        # Loop through all the options that are relevant for this product
        foreach($this->attributeData[0]['options'] as $key=>$attribute)
        {
            # Gets rate index
            $rateIndex = $this->getAttributeIndex($attribute);

            # Gets the rate based on the index of the period
            $data['regular_price'] = $this->productData->rate[$rateIndex];

            # Gets auto add kit rate based on the index of the period if there are kit pricings
            $data['regular_price'] += (!empty($this->productData->kits) ? $this->productData->kits[$rateIndex] : 0);

            # Convert to string
            $data['regular_price'] = $data['regular_price']."";

            # Associate the variation with the appropriate attribute
            $data['attributes'][0] = [
                    'id'        => $this->rateAttributeID,
                    'option'    => $attribute
                ];
     
            # For each variation run the data against the api
            # Tried running it all at once, does not appear that the api supports this
            # albiet api documentation is kind of sparse for some of these things
            # Error Code 5
            
            $code['code'] = 506;

            $result = $this->post('products/'.$id.'/variations', $data, $code);
        }
    }

    # Helper function to clear variations on an update and create new ones
    private function updateVariations($id)
    {
        # Get all the variations currently attached to the product

        $code['code'] = 603;
        
        $result = $this->get('products/'.$id.'/variations', $code);

        $code['code'] = 701;

        # Go through and delete each variation
        foreach($result as $variation)
        {
            $this->delete('products/'.$id.'/variations/'.$variation['id'], $code);
        }

        # Now that there are no variations for the product we can add them again
        $this->addVariations($id);
    }

    # Gets the index of the rental period to match with associated rate index
    private function getAttributeIndex($attribute)
    {
        # Get corespoding keys
        $keys = array_keys($this->attributeMap, $attribute);

        foreach($keys as $key)
        {
            if(isset($this->productData->rate[$key]))
                return $key;
        }

        return 0;

        # Searches flipped mapping to against the POR database return to get the index
        #return array_search($this->flippedMap[$attribute], $this->productData->period);
    }


    # Helper function to generate attribute data more dynamically
    private function buildAttributes()
    {
        $period = $this->productData->period;
        $rate = $this->productData->rate;
        
        $array = array();
        
        # Fetch the attribute information
        $attribute = $this->getAttribute($this->rateAttributeID);
        # Assignments
        $array[0]['id'] = $this->rateAttributeID;
        $array[0]['name'] = $attribute['name'];
        $array[0]['slug'] = $attribute['slug'];
        $array[0]['visible'] = True;
        $array[0]['variation'] = True;
        $array[0]['options'] = array();

        # Loop through each period, if it is not empty, map it 
        $i = 0;
        foreach($period as $key=>$value)
        {
            if(!empty($value))
            {
                # The attributeMap is a configuration option that should be set manually in the config file
                $array[0]['options'][$i] = $this->attributeMap[$value];
                $i++;
            }
        }

        return $this->attributeData = $array;
    }

    # Helper function to get information about attribute
    private function getAttribute($attribute)
    {
        # Get array of attribute information
        $code['code'] = 604;
        $json = $this->get('products/attributes/'.$attribute, $code);

        # Append terms information to the array
        $json['terms'] = $this->getAttributeTerms($attribute);

        return $json;
    }

    # Helper function to get terms of an attribute
    private function getAttributeTerms($attribute)
    {
        # Fetch all terms of the attribute
        $code['code'] = 605;
        return $this->get('products/attributes/'.$attribute.'/terms', $code);
    }

    # Helper function to determine if the tax class already exists
    # If the classes doesn't exist create it
    private function getTaxClass()
    {
        $code['code'] = 606;
            
        $jsonData = $this->get('taxes/classes', $code);

        $slugs = array_column($jsonData, 'name');
        $keyResult = array_search("dmgwaiver_".$this->productData->dWaiver, $slugs, True);

        if($keyResult === False)
            return $this->createTaxClass();

        return $jsonData[$keyResult]['slug'];
    }

    # 
    private function createTaxClass()
    {
        $data = array(

                'name' => "dmgwaiver_".$this->productData->dWaiver

            );

        $code['code'] = 507;

        # Error Code 6
        $jsonData = $this->post('taxes/classes', $data, $code);

        $data = array( 
                'country'   => '*',
                'state'     => '*',
                'rate'      => $this->productData->dWaiver."",
                'name'      => 'Damage Waiver',
                'class'     => $jsonData['slug'],
                'priority'  => 1
            );

        $code['code'] = 508;

        $this->post('taxes', $data, $code);

        $data = array(
                'country'   => '*',
                'state'     => '*',
                'rate'      => "$this->salesTax",
                'name'      => 'State Tax',
                'class'     => $jsonData['slug'],
                'priority'  => 2,
                'compound'  => True
            );

        $code['code'] = 509;

        $this->post('taxes', $data, $code);

        return $jsonData['slug'];
    }

    # Function built around handling exceptions from the API
    private function post($endpoint, $data, $code, $attempts = 0)
    {
        # Try to send the request as intended
        try
        {
            # Some log information
            $this->log->info("Posting data");
            $this->log->debug("Endpoint: ".$endpoint);


            # Debug information
            $this->log->debug(print_r($data, true));

            # Send request to APIi
            if(!$this->dryRun)
                return $this->woocommerce->post($endpoint, $data);
        }
        catch( Throwable $e )
        {
            $this->log->debug($e->getMessage(), $code);

            # Sleep in case it was a timeout issue 
            sleep(10);

            # See if there was a duplicate sku error which can occur
            if(strpos($e->getMessage(), "product_invalid_sku") !== False)
                # Since the sku already exists, we can go ahead and update it to save the time of checking what might be wrong
                return $this->updateProduct(0, $this->productData, $this->productData->num); 

            # Log information
            $this->log->notice("Error with post request", $code);
            $this->log->notice($e->getMessage(), $code);
            $this->log->debug($e->getCode(), $code);
            # Determine if this call has been repeated 5 times
            if($attempts < 5)
            {
                # Track attempts
                $attempts++;

                # Information
                $this->log->notice("retrying...");

                # Send request to API
                return $this->post($endpoint, $data, $code, $attempts);
            }
            else
            {
                # API gave bad response on all tries
                $this->log->error("Post request has failed after multiple attempts.");
                throw new PostException("Post request has failed", $code['code']);
            }            
        }    
    }

    private function get($endpoint, $code, $attempts = 0, $parameters = [])
    {
        try
        {
            $this->log->info("Getting data");
            $this->log->debug("Endpoint: ".$endpoint);
            return $this->woocommerce->get($endpoint, $parameters);
        }
        catch(Exception $e)
        {
            $this->log->notice("Error with get request", $code);
            $this->log->notice($e->getMessage(), $code);
            $this->log->debug($e->getCode(), $code);

            if($attempts < 5)
            {
                $attempts++;
                $this->log->notice("retrying...", $code);
                return $this->get($endpoint, $code, $attempts, $parameters);
            }
            else
            {
                $this->log->error("Get request has failed after multiple attempts.", $code);

                throw new GetException("Get request has failed", $code['code']);
            }
        }
    }

    private function delete($endpoint, $code, $attempts = 0)
    {
        try
        {
            $this->log->info("Sending delete request");
            $this->log->debug("Endpoint: ".$endpoint);
            if(!$this->dryRun)
                return $this->woocommerce->delete($endpoint, ['force' => true]);
        }
        catch(Exception $e)
        {
            $this->log->notice("Error with delete request", $code);
            $this->log->notice($e->getMessage(), $code);

            if($attempts < 5)
            {
                $attempts++;
                $this->log->notice("retrying...", $code);
                return $this->delete($endpoint, $code, $attempts);
            }
            else
            {
                $this->log->error("Delete request has failed after multiple attempts.", $code);

                throw new DeleteException("Delete request has failed", $code['code']);
            }
        }

    }

}
