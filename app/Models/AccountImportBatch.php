<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountImportBatch extends Model
{
    protected $fillable = [
        'filename', 'total_rows', 'imported', 'skipped', 'duplicates', 'user_name',
    ];

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class, 'import_batch_id');
    }
}
