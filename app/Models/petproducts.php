<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class petproducts extends Model
{
    use HasFactory;
    protected $table = "petproducts";

    protected $fillable = ['petB_id','product_id','optionalP_id'];
}
