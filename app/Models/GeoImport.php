<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeoImport extends Model
{
	use HasFactory;
}

public function taxonomy_where()
{
	return $this->hasMany(TaxonomyWhere::class);
}