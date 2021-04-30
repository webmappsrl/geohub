<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxonomyWhere extends Model
{
    use HasFactory;
    protected $table='taxonomy_wheres';
    protected $fillable=[
        'name',
        'import_method'
    ];

    /**
     *
     */
    public function isEditableByUserInterface() {
        if(is_null($this->import_method)) return true;
        return false;
    }

    /**
     *
     */
    public function isImportedByExternalData() {
        if($this->isEditableByUserInterface()) return false;
        return true;
    }
}
