<?php

namespace SwooleTW\Http\Tests\Helpers;

use SwooleTW\Http\Helpers\MimeType;
use PHPUnit\Framework\TestCase;

class MimeTypeTest extends TestCase
{
    public function testGet()
    {
        $extension = 'css';
        $mimetype = MimeType::get($extension);

        $this->assertEquals($mimetype, 'text/css');
    }
    
    public function testGetWithEmptyString()
    {
        $extension = '';
        $mimetype = MimeType::get($extension);

        $this->assertEquals($mimetype, 'application/octet-stream');
    }

    public function testFrom()
    {
        $filename = 'test.css?id=12d123fadf';
        $mimetype = MimeType::from($filename);

        $this->assertEquals($mimetype, 'text/css');
    }
}
