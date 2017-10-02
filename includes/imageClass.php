<?php

/**
 * Class object for handling images
 **/

class Image
{
    private $url; // URL to file on remote server
    private $tempDir; // local temp directory
    private $binary; // binary picture data
    private $filepath; // path to file in temp dir ($dir)
    private $porRemoteImageDir; // Directory on remote host to ftp upload to
    private $ftpsHost; // Host for remote server for image upload
    private $ftpsUser; // User for remote server for image upload
    private $ftpsPass; // Password for remote server for image upload

    # Constructor
    # Builds image information and saves input binary data to object
    # Params: 
    # $binary - binary data representing an image from POR
    public function __construct($binary)
    {
        global $porImageDirURL, $LocalImageStorage;

        # Save binary of the image in case we need it for something
        $this->binary = $binary;

        if($LocalImageStorage)
        {
            global $localTempImageDir, $log;
            
            # Assign directory where POR pictures will be saved (web accessible)
            $this->tempDir = $localTempImageDir;

            # Create tmp directory(recursive) if it does not exist
            if(!file_exists($this->tempDir))
                mkdir($this->tempDir, 0777, True);

            # Create a new file and rename it as an image file so it can be imported by wordpress
            $this->generateFilePath();

            $this->url = $porImageDirURL .'/'.basename($this->filepath);

            $this->createImageFileLocal(); 
        }
        else
        {
            global $ftpTempImageDir, $ftpImageDir, $ftpsHost, $ftpsUser, $ftpsPass;

            # Save locally in class
            $this->porRemoteImageDir = $ftpImageDir;
            $this->ftpsHost = $ftpsHost;
            $this->ftpsUser = $ftpsUser;
            $this->ftpsPass = $ftpsPass;

            # Assign directory where POR pictures will be saved temporarily before ftps upload
            $this->tempDir = $ftpTempImageDir;

            # Create tmp directory(recursive) if it does not exist
            if(!file_exists($this->tempDir))
                mkdir($this->tempDir, 0777, True);

            # Create a new file and rename it as an image file so it can be imported by wordpress
            $this->generateFilePath();

            # Create url to file following ftps upload
            $this->url = $porImageDirURL.'/'.basename($this->filepath);

            $this->createImageFileFTPS();
        }

    }

    # Creates a php image object from the binary data 
    # Uploads binary object to correct folder on remote server if needed
    private function createImageFileFTPS()
    {

        $this->createImageFileLocal();

        # Open ftp connection using ssl
        $connection = ftp_ssl_connect($this->ftpsHost);

        # Login
        $login = ftp_login($connection, $this->ftpsUser, $this->ftpsPass);

        # Turn on passive ftp to navigate NAT
        $passive = ftp_pasv($connection, true);

        # Send the image to the server
        $put = ftp_put($connection, $this->porRemoteImageDir.'/'.basename($this->filepath), $this->filepath, FTP_BINARY);

        # Close ftp connection
        $close = ftp_close($connection);
    }

    private function createImageFileLocal()
    {
        # Create image from string of binary data
        $image = imagecreatefromstring($this->binary);

        # Create a png of the image object
        imagepng($image, $this->filepath);

        # Set file permissions new png
        chmod($this->filepath, 444);
    }

    # Generates the filepath
    private function generateFilePath()
    {
        $this->filepath = tempnam($this->tempDir, 'image_');
        rename($this->filepath, $this->filepath .= '.png');
    }

    # Function to fetch url of the image
    public function getURL()
    {
        return $this->url;
    }

    # If the script ends properly all image objects should be deleted by destruct
    # Unlikely to happen all the time but it is nice to add when it will work
    public function __destruct()
    {
        unlink($this->filepath);
    }


}
