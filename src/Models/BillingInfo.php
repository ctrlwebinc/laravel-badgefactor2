<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingInfo extends Model
{
    use HasFactory;

    protected $primaryKey = null;

    public $incrementing = false;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'city',
        'province',
        'country',
        'phone',
        'email',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
