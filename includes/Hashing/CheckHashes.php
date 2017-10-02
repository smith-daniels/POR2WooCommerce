<?php

# Compares hashes given the itemNum
# Return['code'] 0: Item exists in WooCommerce and does not need to be updated
# Return['code'] 1: Item exists but it needs to be updated
# Return['code'] 2: Item does not exist
function checkHash($itemF, $num)
{
    $productHashingResult = $itemF->checkProductHash();

    if($productHashingResult['Rows'] == 0)
        return array(
                    'code'  => 2,
                    'WP_ID' => ''
                   );

    # Check product hash against database value
    # True: Hashes are the same
    # False: Different hashes
    if(!$productHashingResult['result'])
        return array(
            'code'      => 1,
            'WP_ID'     => $productHashingResult['WP_ID']
            );

    return array(
        'code'      => 0,
        'WP_ID'     => $productHashingResult['WP_ID']
    );
}

# Compares hashes for a category
#
function checkCategoryHash($itemF, $woocommerce)
{
    $categoryHashingResult = $itemF->checkCategoryHash();

    if(!$categoryHashingResult)
    {
        $woocommerce->updateCategory(true, $itemF);
    }

}

/**
* Function to check the hash of every item in the database to see if change has occured
* 
*/

function checkHashes()
{
    # CommerceConnect instance
    $commerceConnect = new CommerceConnect();

    # Create an instance to MySQL version of POR database
    $SQLPOR = new SQL(PORHost, PORUser, PORPassword, PORDB);

    # Create an instance to MySQL database holding item hashes
    $SQLHash = new SQLHash(hashHost, hashUser, hashPassword, hashDB);

    # Instance of the hashing class
    $Hashing = new Hashing($SQLHash);

    # Holds all products that need to be updated
    $queue = new SplQueue();

    # Fetch all items from the POR database
    $result = $SQLPOR->ItemFile_GetItems();

    # Loop through the returned items from POR
    while($item = mysqli_fetch_object($result))
    {
        # Create an ItemFile instance from the database return
        $itemF = new ItemFile($SQLPOR, $Hashing, $item->NUM);
        
        $productHashingResult = $itemF->checkProductHash();
        
        # Check product hash against database value
        # True: Hashes are the same
        # False: Different hashes
        if(!$productHashingResult['result'])
            $queue->push(array(
                    'POR_ID' => $item->NUM,
                    'WP_ID' => $productHashingResult['WP_ID']
                )
            );
    }
    
    while(!$queue->isEmpty())
    {
        $itemInfo = $queue->pop();
        $itemF = new ItemFile($SQLPOR, $Hashing, $itemInfo['POR_ID']);
        $commerceConnect->updateProduct($itemInfo['WP_ID'], $itemF);
    }

}
