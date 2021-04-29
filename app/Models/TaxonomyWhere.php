<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxonomyWhere extends Model
{
    use HasFactory;
}

public function taxonomy_where()
{
	return $this->belongsTo(GeoImport::class);
}