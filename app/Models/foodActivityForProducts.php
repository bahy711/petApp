<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class foodActivityForProducts extends Model
{
    use HasFactory;
    protected $table = "activityproduct";

    protected $fillable = ['petAf_id','foodActivity_id','product_id'];
}
