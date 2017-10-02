<?php
/**
* Function to check the hash of every item in the database to see if change has occured
*
*/

require_once(__DIR__.'/../load.php');

function massImport()
{
    global $log;

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
#    $result = $SQLPOR->ItemFile_GetItem(10764);

    if(!$result)
    {
        $log->critical("Fatal database error.");
        exit("See log\n");
    }

    # Loop through the returned items from POR
    while($item = mysqli_fetch_object($result))
    {   
        # Information for debuggin
        $log->debug("Working on item number: $item->NUM");

        # Create an ItemFile instance from the database return
        $itemF = new ItemFile($SQLPOR, $Hashing, $item->NUM);

        $hashResult = checkHash($itemF, $item->NUM);

        if($hashResult['code'] == 2)
        {
            $log->info("Product needs to be created. ID: $itemF->num");

            $commerceConnect->createProduct($itemF);
        }
        elseif($hashResult['code'] == 1)
        {
            $log->info("Product needs to be updated. ID: $itemF->num");
            $commerceConnect->updateProduct($hashResult['WP_ID'], $itemF);
        }
        else
            $log->info("Product exists and does not need to be updated. ID: $itemF->num");
    
    }
}

try{
    massImport();
}
catch ( Throwable $e )
{
    $log->debug("There was an exception");
    $log->debug($e->getMessage());
}
