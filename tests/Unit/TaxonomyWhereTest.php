<?php

namespace Tests\Unit;

use App\Models\TaxonomyWhere;
use PHPUnit\Framework\TestCase;

class TaxonomyWhereTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testIsEditableByUserInterface()
    {
        $where = new TaxonomyWhere(array('name'=>'fake'));
        $this->assertTrue($where->isEditableByUserInterface());
    }
    public function testIsNotEditableByUserInterface()
    {
        $where = new TaxonomyWhere(array('name'=>'fake','import_method'=>'fake'));
        $this->assertFalse($where->isEditableByUserInterface());
    }
    public function testIsImportedByExternalData()
    {
        $where = new TaxonomyWhere(array('name'=>'fake','import_method'=>'fake'));
        $this->assertTrue($where->isImportedByExternalData());
    }
    public function testIsNotImportedByExternalData()
    {
        $where = new TaxonomyWhere(array('name'=>'fake'));
        $this->assertFalse($where->isImportedByExternalData());
    }
}
