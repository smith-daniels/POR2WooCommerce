<?php
/**
 *
 * Main branch of program to run as a cron job
 *
 */

require_once "load.php";

# If something fails here, we have a critical system error
try {
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

    # Get an array of all products in the hashing database
    $existingProducts = $Hashing->getExisting();
}
catch ( Throwable $e )
{
    $log->emergency("Fatal init error");
    $log->debug($e->getMessage());
    $log->debug("Code: $e->getCode()");
    exit(0);
}

# List of products that were added as new
$newList = array();

# List of producst that were updated
$updateList = array();

# List of products that had an error and failed
$failureList = array();

# List of products that had an error and were subsequently deleted
$deletedList = array();

# Loop through the returned items from POR
while($item = mysqli_fetch_object($result))
{
    # Trim whitespace off item number
    $item->NUM = trim($item->NUM);
    
    # Information for debugging
    $log->debug("Working on item number: $item->NUM");

    # Remove the product from the existingProducts array to determine if any products have been deleted
    $existingKey = array_search($item->NUM, $existingProducts);

    # If array search returns False, the product is new and would not appear anyway
    if($existingKey !== False)
        unset($existingProducts[$existingKey]);

    try {
        # Create an ItemFile instance from the database return
        $itemF = new ItemFile($SQLPOR, $Hashing, $item->NUM);
    }
    catch ( Throwable $e )
    {
        $failureList[] = $item->NUM;
        continue;
    }

    try {
        # See if the category for the product needs an update
        checkCategoryHash($itemF, $commerceConnect);
    }
    catch ( Throwable $e )
    {
        $failureList[] = $item->NUM;
        continue;
    }

    # Based on hash result perform correct action
    #
    $hashResult = checkHash($itemF, $item->NUM);
    try {
        if($hashResult['code'] == 2)
        {
            $log->info("Product ($itemF->name) needs to be created");
            $commerceConnect->createProduct($itemF);
            $newList[] = $item->NUM;
        }
        elseif($hashResult['code'] == 1)
        {
            $log->info("Product ($itemF->name) needs to be updated");
            $commerceConnect->updateProduct($hashResult['WP_ID'], $itemF);
            $updateList[] = $item->NUM;
        }
        else
            $log->info("Product ($itemF->name) exists and does not need to be updated");
    }
    # Catch any CommerceConnectExceptions
    catch( CommerceConnectException $e )
    {
        switch($e->getCode())
        {
            # Delete product if these codes occur
            # Deletion is done by adding it to deletion pool at the end of execution
            case 203:
            case 204:
            case 207:
            case 208:
            case 214:
                $deletedList[] = $item->NUM;
                $existingProducts[] = $item->NUM;
                break;

            # If the product cannot be found by it's sku via the WooCommerce API it has likely been deleted on the site and not updated in POR
            case 209:
            case 210:
                $deletedList[] = $item->NUM;
                $Hashing->deleteHash($item->NUM);
                break;

            default:
                $failureList[] = $item->NUM;
        }

        continue;
    }
    catch ( Throwable $e )
    {
        $log->notice("Catchall exception");
        $failureList[] = $item->NUM;

        continue;
    }
}

# Determine if any products need to be deleted
if(count($existingProducts) !== 0)
{
    foreach($existingProducts as $product)
    {
        $log->info("Product ($product) needs to be deleted");

        try {
            $commerceConnect->deleteProduct($product);
        }
        catch ( Throwable $e )
        {
            $log->warning("Could not delete a product. It may have already been deleted.");
            $failureList[] = "Deletion Failed: $product";
        }

        try {
            $log->notice("Deleting $product");
            $Hashing->deleteHash($product);
        }
        catch ( Throwable $e )
        {
            $log->warning("Could not delete product hash.");
            $failureList[] = "Hash Deletion Failed: $product";
        }
    }
}

$log->notice("New List", $newList);
$log->notice("Updated List", $updateList);
$log->notice("Failure list", $failureList);
$log->notice("Deletion list", $deletedList);
$log->info("Items removed from website based on removal in POR", array_diff($existingProducts, $deletedList));
