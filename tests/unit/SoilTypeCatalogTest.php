<?php

use App\Libraries\SoilTypeCatalog;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SoilTypeCatalogTest extends CIUnitTestCase
{
    public function testReturnsCanonicalSoilTypesInFixedOrder(): void
    {
        $this->assertSame([
            'tanah lempung',
            'tanah liat',
            'tanah kubi',
            'tanah berpasir',
        ], SoilTypeCatalog::all());
    }
}
