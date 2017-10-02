<?php

/**
 * Layer between ItemFiles and Database for hashing
 */

class Hashing
{
    private $SQLHash;

    # Constructor
    public function __construct($SQLHash)
    {
        # Save reference to database instance
        $this->SQLHash = $SQLHash;
    }

    # Compares a given hash with the hash from the database
    # Params:
    # $id - Item number as defined in POR
    # $incomingHash - Hash of the ItemFile being evaluated
    public function compareHash($id, $incomingHash)
    {
        # Search for the ID in the Hashing database
        $result = $this->SQLHash->getHash($id);

        # See if the hash even exists
        $count = mysqli_num_rows($result);

        # If the count is 1 a hash exists for that ID
        if($count == 1)
        {
            # Fetch the row as an object
            $hash = mysqli_fetch_object($result);

            # Return the proper information
            return array(
                # If the hashes match return true
                'result'    => strcmp($hash->Hash, $incomingHash) == 0 ? true : false,
                'WP_ID'     => $hash->WP_ID,
                'Rows'      => $count
            );
        }

        # No result, return false, item does not exist in database
        return array(
            'result'    => false,
            'WP_ID'     => 0,
            'Rows'      => $count
        );

    }

    # Gets the hash from the database
    public function getHash($id)
    {
        $result = $this->SQLHash->getHash($id);

        $count = mysqli_num_rows($result);

        if($count == 1)
        {
            $hash = mysqli_fetch_object($result);

            return $hash->Hash;
        }

        return false;
    }

    # Saves a hash to the database
    # Params:
    # $id - Item number as defined in POR
    # $hash - Hash of the ItemFile object
    # $wp_id - Wordpress id of the product
    public function saveHash($id, $hash, $wp_id)
    {
        $this->SQLHash->saveHash($id, $hash, $wp_id);
    }

    # Updates a hash in the database
    # Params:
    # $id - Item number as defined in POR
    # $hash - Hash of the ItemFile object
    # $wp_id - Wordress id of the product
    public function updateHash($id, $hash, $wp_id)
    {
        $this->SQLHash->updateHash($id, $hash, $wp_id);
    }

    # Deletes a hash from the database
    # Params:
    # $sku - Item number as defined in POR
    public function deleteHash($sku)
    {
        $this->SQLHash->deleteHash($sku);
    }

    # Gets all the hashes in the database which are not categories
    public function getExisting()
    {
        # Get all the products
        $result = $this->SQLHash->getProductHashes();

        $hashes = array();

        # Loop through as save the IDs to an array
        while($hash = mysqli_fetch_object($result))
        {
            $hashes[] = $hash->ID;
        }

        return $hashes;
    }
}
