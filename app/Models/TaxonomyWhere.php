<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TaxonomyWhere
 *
 * @package App\Models
 *
 * @property string import_method
 */
class TaxonomyWhere extends Model
{
    use HasFactory, GeometryFeatureTrait;

    protected $table = 'taxonomy_wheres';
    protected $fillable = [
        'name',
        'import_method'
    ];

    /**
     * All the taxonomy where imported using a sync command are not editable
     *
     * @return bool
     */
    public function isEditableByUserInterface(): bool
    {
        return !$this->isImportedByExternalData();
    }

    /**
     * Check if the current taxonomy where is imported from an external source
     *
     * @return bool
     */
    public function isImportedByExternalData(): bool
    {
        return !is_null($this->import_method);
    }
}
