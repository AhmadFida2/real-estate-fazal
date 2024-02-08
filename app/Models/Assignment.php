<?php

namespace App\Models;

use App\Models\Scopes\AssignmentScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =[
        'is_completed' => 'boolean',
        'payment_info' => 'json'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        if(auth()->check())
        {
            if (!auth()->user()->is_admin) {
                static::addGlobalScope(new AssignmentScope);
            }
        }

    }

    public function payments() : HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sumPayments()
    {
        return $this->payments()->sum('amount');
    }

}
