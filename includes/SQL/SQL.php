<?php

/**
 * Class used to fetch information about an item
 */
class SQL
{
    private $conn;
    private $itemSelect =
            "NAME,DMG,Category,".
            "PER1,PER2,PER3,PER4,PER5,PER6,PER7,PER8,PER9,PER10,".
            "RATE1,RATE2,RATE3,RATE4,RATE5,RATE6,RATE7,RATE8,RATE9,RATE10,".
            "TYPE,NUM";
    private $log;

    # Create MySQL database connection
    public function __construct($host, $user, $password, $db)
    {
        global $log;

        $this->log = $log;

        try{
            $this->conn = mysqli_connect($host, $user, $password, $db);
            mysqli_set_charset( $this->conn, 'utf8');
        }
        catch ( Throwable $e )
        {
            $code['code'] = 000;

            $this->log->emergency("Could not connect to the database", $code);
            $this->log->debug($e->getMessage(), $code);

            Throw new SQLException((string)$code['code'], $code['code']);
        }
    }

    # Fetch the item file 
    public function ItemFile_GetItem($num)
    {
        return $this->query("SELECT $this->itemSelect FROM `ItemFile` WHERE NUM=$num LIMIT 1");
    }

    # Fetch a category of items
    public function ItemFile_GetItems($cat = null, $type = null)
    {
        if(is_null($cat) && !is_null($type))
            $sql = "SELECT NUM FROM `ItemFile` WHERE TYPE='$type'";
        else if(!is_null($cat) && is_null($type))
            $sql = "SELECT NUM FROM `ItemFile` WHERE TYPE='$type' AND Category='$cat'";
        else
            #$sql = "SELECT * FROM `ItemFile` WHERE TYPE='v' AND HideOnWebsite=1 AND QTY > 0 AND (PER1+PER2+PER3+PER4+PER5+PER6+PER7+PER8) > 0";
            $sql = "SELECT * FROM `ItemFile` WHERE TYPE='v' AND HideOnWebsite=0 AND QTY > 0 AND (PER1+PER2+PER3+PER4+PER5+PER6+PER7+PER8) > 0 AND (RATE1+RATE2+RATE3+RATE4+RATE5+RATE6+RATE7+RATE8) > 0";
            #$sql = "SELECT NUM FROM `ItemFile` WHERE TYPE='v' AND HideOnWebsite=1 AND QTY > 0";

        return $this->query($sql);
    }

    # Get the pictures of an item
    public function ItemPicture_GetPictures($num)
    {
        if(is_null($num))
            echo "ItemPicture_GetPictures failed due to a null input";

        return $this->query("SELECT * FROM `ItemPicture` WHERE Inum=$num");
    }

    # Get the name of a category
    public function ItemCategory_GetName($num)
    {
        if(is_null($num))
            echo "ItemCategory_GetName failed due to a null input";

        return $this->query("SELECT Name FROM `ItemCategory` WHERE Category=$num");
    }

    # Get all the categories in POR
    public function ItemCategory_GetCategories()
    {
        return $this->query("SELECT * FROM `ItemCategory`");
    }

    # Get the picture for a category in POR
    public function ItemCategory_GetPicture($num)
    {
        return $this->query("SELECT `Picture` FROM `ItemCategoryPicture` WHERE `Category`=$num");
    }

    # Fetch item comment information
    public function ItemComments_Get($num)
    {
        return $this->query("SELECT Specs,PrintOut FROM `ItemComments` WHERE Num=$num LIMIT 1");
    }

    # Minimizes code and exception handling
    public function query($sql)
    {
        try{
            $result =  mysqli_query($this->conn, $sql);
        }
        catch ( Throwable $e )
        {
            $code['code'] = 001;

            $this->log->error("Query failed.", $code);
            $this->log->debug($e->getMessage()." ($sql) ", $code);

            throw new SQLException((string)$code['code'], $code['code']);
        }

       return $result;
    }

}
