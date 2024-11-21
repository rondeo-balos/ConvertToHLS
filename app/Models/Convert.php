<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Convert extends Model {
    
    protected $fillable = [
        'user',
        'directory',
        'resolutions',
        'expires'
    ];

    public static function boot() {
        parent::boot();

        static::creating( function( $model ) {
            /**
             * expires in 30 days
             */
            $expiration = Carbon::now()->addDays(30);
            $model->expires = $expiration;
        });
    }

}
