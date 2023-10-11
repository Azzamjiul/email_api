<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Broadcast extends Model
{
    use HasFactory;
    protected $fillable = ['uuid', 'name', 'message', 'attachment_content'];

    /**
     * Boot method for the model.
     * Automatically generates and sets a UUID value for the model if one isn't provided.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    /**
     * Define a one-to-many relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function targets()
    {
        return $this->hasMany(BroadcastTarget::class);
    }
}
