<?php

/**
 * Class to get database information with regards to Hashes
 */
class SQLHash
{
    private $conn;
    private $itemSelect = "Hash, WP_ID";
    private $log;

    # Create a MySQL connection
    public function __construct($host, $user, $password, $db)
    {
        global $log;

        $this->log = $log;

        try{
            $this->conn = mysqli_connect($host, $user, $password, $db);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 000;

            $this->log->emergency("Could not connect to the database", $code);
            $this->log->debug($e->getMessage(), $code);

            Throw new SQLHashException((string)$code['code'], $code['code']);
        }
    }

    # Get the hash of the item 
    public function getHash($id)
    {
        # Query database for hash of that item
        $result = $this->query("SELECT $this->itemSelect FROM `Hashes` WHERE ID='$id' LIMIT 1");
        return $result;
    }

    # Get list of products (not categories) in the hashing table
    public function getProductHashes()
    {
        $result = $this->query("SELECT `ID` FROM `Hashes` WHERE `ID` REGEXP '^[0-9]+$'");

        return $result;
    }

    # Saves a new hash into the database
    public function saveHash($id, $hash, $wp_id)
    {
        $result = $this->query("INSERT INTO `Hashes` (`ID`, `Hash`, `WP_ID`) VALUES ('$id', '$hash', '$wp_id')");
    }

    # Updates a hash
    public function updateHash($id, $hash, $wp_id)
    {
        $result = $this->query("DELETE FROM `Hashes` WHERE `ID`='$id' AND `WP_ID`=$wp_id");
        $this->saveHash($id, $hash, $wp_id); 
#         $result = mysqli_query($this->conn, "UPDATE `Hashes` SET `Hash`='$hash' WHERE `ID`='$id'");
    }

    # Delete a hash for a product 
    public function deleteHash($sku)
    {
        $result = $this->query("DELETE FROM `Hashes` WHERE `ID`='$sku'");
    }

    # Minimizes code and exception handling
    private function query($sql)
    {
        try{
            $result =  mysqli_query($this->conn, $sql);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 001;

            $this->log->error("Query failed.", $code);
            $this->log->debug($e->getMessage()." ($sql) ", $code);

            throw new SQLHashException((string)$code['code'], $code['code']);
        }

       return $result;
    }

}
