<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    public function drivers()
    {
        return $this->hasMany(Driver::class, 'id', 'document_types_id');
    }
}
