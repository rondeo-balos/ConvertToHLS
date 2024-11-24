<?php

use App\Models\Convert;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

Schedule::call( function() {
    $convert = Convert::where( 'expires', '<', Carbon::now() );
    unlink( $convert->directory );
    $convert->delete();
})->daily();