<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class foodActivityForIngredients extends Model
{
    use HasFactory;
    protected $table = "activityingredient";

    protected $fillable = ['petAi_id','foodActivityI_id','ingredientA_id'];
}
