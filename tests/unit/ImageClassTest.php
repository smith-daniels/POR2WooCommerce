<?php

require_once __DIR__."/../../load.php";

use PHPUnit\Framework\TestCase;

class ImageClassTest extends TestCase
{
    public function testImageRemote()
    {
        global $LocalImageStorage;

        $LocalImageStorage = False;

        $binary = file_get_contents(__DIR__.'/image.png');

        $image = new Image($binary);

        $headers = get_headers($image->getURL(), 1);

        $this->assertEquals('image/png', $headers['Content-Type']);
    }

    public function testImageLocal()
    {
        global $LocalImageStorage, $localTempImageDir, $porImageDirURL;

        $LocalImageStorage = True;

        $localTempImageDir = '/mnt/c/xampp/htdocs/por-images';

        $porImageDirURL = 'http://localhost/por-images';

        $binary = file_get_contents(__DIR__.'/image.png');

        $image = new Image($binary);

        $headers = get_headers($image->getURL(), 1);

        $this->assertEquals('image/png', $headers['Content-Type']);

    }    
}
