<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastTarget extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'email'];

    /**
     * Define an inverse one-to-many relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function broadcast()
    {
        return $this->belongsTo(Broadcast::class);
    }
}
