<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class petoptionaldata extends Model
{
    use HasFactory;
    protected $table = "petoptionaldata";

    protected $fillable = ['unit','CaloriesPerUnit','noOfUnits','MealPerDay','ExerciseNo','Exerciseh','ExerciseM','BodyConditionScore','PetO_id'];
}
