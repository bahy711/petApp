<?php

namespace App\Http\Controllers;

use App\Models\breed;
use App\Models\dailyexcercise;
use App\Models\foodActivity;
use App\Models\foodActivityForIngredients;
use App\Models\foodActivityForProducts;
use App\Models\pet;
use App\Models\PetHealthData;
use App\Models\pethomemadeingredients;
use App\Models\petoptionaldata;
use App\Models\petproducts;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DateTime;

class petController extends Controller
{
    public function create(Request $request)
    {
        $id = auth()->user()->id;
        $data = $request->all();
        $data['spayedOr'] = filter_var($data['spayedOr'], FILTER_VALIDATE_BOOLEAN);
        $data['mixed'] = filter_var($data['mixed'], FILTER_VALIDATE_BOOLEAN);

        $validator = Validator::make($data,
            [
                "PetName" => "required|min:3",
                "specie" => ['required', Rule::in(['dog', 'cat'])],
                "birthday" => "nullable|date",
                "PetYears" => "nullable|numeric",
                "PetMonths" => "nullable|numeric",
                "weight" => "required|numeric",
                "gender" => ['required', Rule::in(['male', 'female'])],
                "spayedOr" => "required|boolean",
                "image_path" => "nullable|image|mimes:png,jpg,jpeg",
                "mixed" => "required|boolean",
                "breedOne" => "required|string",
                "breedTwo" => "nullable|string",
            ]);
        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $data["user_id"] = auth()->user()->id;
            if ($request->hasFile('image_path')) {
                $FinalName = hexdec(uniqid()) . '.' . $request->image_path->extension();
                $request->image_path->move(public_path('PetImages'), $FinalName);
                $data['image_path'] = $FinalName;
            }

            $op1 = pet::create($data);

            if (isset($op1->id)) {
                $pet_id = $op1->id;
                $op = breed::create([
                    "mixed" => $data["mixed"],
                    "breedOne" => $data["breedOne"],
                    "breedTwo" => $data["breedTwo"],
                    "pet_id" => $pet_id,
                ]);
                if ($op) {
                    $Pet = pet::where('breeds.pet_id', $op->pet_id)->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->select('petinfo.id as PetID', 'petinfo.*', 'breeds.id as BreedID', 'breeds.*')->get()->makeHidden(['pet_id', 'id', 'created_at', 'updated_at']);
                }
                $message = 'Pet Created';
                foreach ($Pet as $value) {
                    $value->image_path = url($value->image_path);
                }
                return response()->json(["status" => " 201", "message" => $message, "data" => ['createdPet' => $Pet]], 201);
            } else {
                $message = 'error try again';
                return response()->json(["status" => "500", "message" => $message], 500);
            }

        }
    }
    public function PetInfo(Request $request)
    {
        $id = auth()->user()->id;

        $Pet = User::where('users.id', $id)->join('petinfo', 'users.id', '=', 'petinfo.user_id')->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->select('petinfo.id as PetID', 'petinfo.*', 'breeds.id as BreedID', 'breeds.*')->get()->makeHidden(['pet_id', 'id', 'created_at', 'updated_at']);
        // $Pet = User::where('users.id', $id)->join('petinfo', 'users.id', '=', 'petinfo.user_id')->select('petinfo.id as PetID', 'petinfo.*')->join('breeds', 'breeds.pet_id', '=','petinfo.id')->select('breeds.id as BreedID', 'breeds.*')->get();

        foreach ($Pet as $key => $value) {
            $Pet[$key]->image_path = url('PetImages/' . $Pet[$key]->image_path);

        }
        return response()->json(["status" => "200", "message" => "Pet data", "data" => ["createdPet" => $Pet]], 200);

    }
    public function UpdatePetInfo(Request $request)
    {
        // there are also here PetID  BreedID  user_id
        $data = $request->all();
        $data['spayedOr'] = filter_var($data['spayedOr'], FILTER_VALIDATE_BOOLEAN);
        $data['mixed'] = filter_var($data['mixed'], FILTER_VALIDATE_BOOLEAN);
        $Pet = pet::find($data['PetID']);
        // $breed=breed::find($data['BreedID']);

        $validator = Validator::make($data,
            [
                "PetName" => "required|min:3",
                "specie" => ['required', Rule::in(['dog', 'cat'])],
                "birthday" => "nullable|date",
                "PetYears" => "nullable|numeric",
                "PetMonths" => "nullable|numeric",
                "weight" => "required|numeric",
                "gender" => ['required', Rule::in(['male', 'female'])],
                "image_path" => "nullable|image|mimes:png,jpg",
                "spayedOr" => "required|boolean",
                "mixed" => "required|boolean",
                "breedOne" => "required|string",
                "breedTwo" => "nullable|string",
            ]);

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            if ($request->hasFile('image_path')) {

                $FinalName = hexdec(uniqid()) . '.' . $request->image_path->extension();

                if ($request->image_path->move(public_path('PetImages'), $FinalName)) {
                    $file = public_path('PetImages/' . $Pet['image_path']);
                    if (file_exists($file) and $Pet['image_path'] !== null) {
                        unlink(public_path('PetImages/' . $Pet['image_path']));
                    }

                }
            } else {
                $FinalName = $Pet['image_path'];

            }
            $data['image_path'] = $FinalName;

            $op = pet::where('id', $data['PetID'])->update([
                "PetName" => $data['PetName'],
                "specie" => $data['specie'],
                "birthday" => $data['birthday'],
                "PetYears" => $data['PetYears'],
                "PetMonths" => $data['PetMonths'],
                "weight" => $data['weight'],
                "gender" => $data['gender'],
                "image_path" => $data['image_path'],
                "spayedOr" => $data['spayedOr'],

            ]);
            $OPBreed = breed::where('id', $data['BreedID'])->update([
                "mixed" => $data["mixed"],
                "breedOne" => $data["breedOne"],
                "breedTwo" => $data["breedTwo"],
            ]);
            $Pet = pet::where('breeds.pet_id', $data['PetID'])->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->select('petinfo.id as PetID', 'petinfo.*', 'breeds.id as BreedID', 'breeds.*')->get()->makeHidden(['pet_id', 'id', 'created_at', 'updated_at']);
            return response()->json(["status" => "201", "message" => "data Updated Successfully", "data" => ["updatedPet" => $Pet]], 201);
        }

    }

    public function DeletePet($id)
    {
        $op = pet::where('id', $id)->delete();
        return response()->json(["status" => "200", "message" => "delete successfully"], 200);
    }

    // creating health data
    public function CreateHealthCondition(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data,
            [
                "PetCondition" => "required",
                "PetConditionState" => "required",
                "PetH_id" => "required|numeric",
            ]);
        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $op = PetHealthData::create($data);

            if ($op) {
                $message = 'Pet Health Condition Created';
                return response()->json(["status" => " 201", "message" => $message, "data" => ["createdHealthCondition" => $op]], 201);
            } else {
                $message = 'error try again';
                return response()->json(["status" => "500", "message" => $message], 500);
            }

        }

    }
    public function HealthConditionData($id)
    {
        $HealthCondition = PetHealthData::where('PetH_id', '=', $id)->get();
        if (isset($HealthCondition[0]->id)) {
            return response()->json(["status" => " 201", "message" => "success", "data" => ["healthCondition" => $HealthCondition]], 200);
        } else {
            return response()->json(["status" => " 404", "message" => 'Health Condition data Not found'], 404);
        }
    }
    public function UpdateHealthCondition(Request $request)
    {

        $data = $request->all();

        $validator = Validator::make($data,
            [
                "PetCondition" => "required",
                "PetConditionState" => "required",
                "PetH_id" => "required|numeric",
                "HealthCondition_id" => "required|numeric",
            ]);
        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $OP = PetHealthData::where('PetH_id', $data['PetH_id'])->where('id', $data['HealthCondition_id'])->update([
                "PetCondition" => $data["PetCondition"],
                "PetConditionState" => $data["PetConditionState"],
            ]);
            if ($OP) {
                $message = 'Pet Health Condition Updated';
                return response()->json(["status" => " 201", "message" => $message], 201);
            } else {
                $message = 'error try again';
                return response()->json(["status" => "500", "message" => $message], 500);
            }

        }
    }

    public function deleteHealthCondition($id)
    {
        $op = PetHealthData::where('id', $id)->delete();
        return response()->json(["status" => "200", "message" => "delete successfully"], 200);
    }

    // creating optional pet info

    public function createOptionalPetInfo(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data,
            [
                "BodyConditionScore" => "required|numeric",
                "MealPerDay" => "required|numeric",
                "ExerciseNo" => "required|numeric",
                "Exerciseh" => "required|numeric",
                "ExerciseM" => "required|numeric",
                "Pet_id" => "required|numeric",
                "product" => "array",
                "homeIngredient" => "array",
                "unit" => "required|min:3",
                "CaloriesPerUnit" => "required|numeric",
                "noOfUnits" => "required|numeric",

            ]);

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $product = array();
            $homeIngredient = array();

            // $product = array(
            //     array('petB_id' => 33, 'product_id' => 1),
            // );
            // $homeIngredient = array(
            //     array('PetI_id' => 33, 'homeIngredient_id' => 2),
            // );
            // $data['product']=$product;
            // $data['homeIngredient']=$homeIngredient;
            $first = petoptionaldata::create([
                'BodyConditionScore' => $data['BodyConditionScore'],
                'MealPerDay' => $data['MealPerDay'],
                'ExerciseNo' => $data['ExerciseNo'],
                'Exerciseh' => $data['Exerciseh'],
                'ExerciseM' => $data['ExerciseM'],
                'PetO_id' => $data['Pet_id'],
                'unit' => $data['unit'],
                'CaloriesPerUnit' => $data['CaloriesPerUnit'],
                'noOfUnits' => $data['noOfUnits'],
            ]);
            if (isset($first->id)) {
                if ($request->has('product') and $data['product'] !== null) {
                    foreach ($data['product'] as $value) {

                        $products = array('petB_id' => $data['Pet_id'], 'product_id' => $value, 'optionalP_id' => $first->id);
                        array_push($product, $products);

                    };
                    foreach ($product as $value) {$second = petproducts::create($value);}

                }
                if ($request->has('homeIngredient') and $data['homeIngredient'] !== null) {
                    foreach ($data['homeIngredient'] as $value) {
                        $homeIngredients = array('PetI_id' => $data['Pet_id'], 'homeIngredient_id' => $value, 'optionalI_id' => $first->id);
                        array_push($homeIngredient, $homeIngredients);

                    }
                    foreach ($homeIngredient as $value) {$Third = pethomemadeingredients::create($value);}
                }
            }

            return response()->json(["status" => "200", "message" => "Optional Data Created"], 200);

        }

    }

    public function getPetOpData($id)
    {
        $oPtionalData = petoptionaldata::where('PetO_id', $id)->get();
        foreach ($oPtionalData as $value) {
            $value->caloriesPerMeal = $value->CaloriesPerUnit * $value->noOfUnits;
        }
        $productData = petproducts::where('petB_id', $id)->join('animalproducts', 'petproducts.product_id', '=', 'animalproducts.id')->select('petproducts.id as petProductId', 'petproducts.*', 'animalproducts.id as animalProductId', 'animalproducts.*')->get()->makeHidden(['id', 'created_at', 'updated_at', "petB_id", "animalProductId"]);

        $ingredientData = pethomemadeingredients::where('PetI_id', $id)->join('ingredients', 'ingredients.id', '=', 'pethomemadeingredients.homeIngredient_id')->select('pethomemadeingredients.id as pethomemadeingredientsId', 'pethomemadeingredients.*', 'ingredients.id as ingredientsId', 'ingredients.*')->get()->makeHidden(['id', 'created_at', 'updated_at', "PetI_id", "ingredientsId"]);
        if ($ingredientData) {
            return response()->json(["status" => "200", "message" => "success", "data" => ["optionalData" => $oPtionalData, "productData" => $productData, "ingredientData" => $ingredientData]], 200);
        }
    }
    public function UpdateOptionalData(Request $request, $optionalId)
    {$data = $request->all();

        $validator = Validator::make($data,
            [
                "BodyConditionScore" => "required|numeric",
                "MealPerDay" => "required|numeric",
                "ExerciseNo" => "required|numeric",
                "Exerciseh" => "required|numeric",
                "ExerciseM" => "required|numeric",
                "product" => "array",
                "homeIngredient" => "array",
                "unit" => "required|min:3",
                "CaloriesPerUnit" => "required|numeric",
                "noOfUnits" => "required|numeric",

            ]);

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {

            // $product = array(
            //         array('id'=>41,'optionalP_id' => 34, 'product_id' => 2),
            //     );
            //     $homeIngredient = array(
            //         array('id'=>33,'optionalI_id' => 34,  'homeIngredient_id' => 1),
            //     );
            $op1 = petoptionaldata::where('id', $optionalId)->update([
                'BodyConditionScore' => $data['BodyConditionScore'],
                'MealPerDay' => $data['MealPerDay'],
                'ExerciseNo' => $data['ExerciseNo'],
                'Exerciseh' => $data['Exerciseh'],
                'ExerciseM' => $data['ExerciseM'],
                'unit' => $data['unit'],
                'CaloriesPerUnit' => $data['CaloriesPerUnit'],
                'noOfUnits' => $data['noOfUnits'],
            ]);
            foreach ($data['product'] as $value) {
                $op2 = petproducts::where('id', $value['id'])->where('optionalP_id', $value['optionalP_id'])->update($value);
            }
            foreach ($data['homeIngredient'] as $value) {
                $op3 = pethomemadeingredients::where('id', $value['id'])->where('optionalI_id', $value['optionalI_id'])->update($value);
            }
            if ($op1 and $op2 and $op3) {

                return response()->json(["status" => "200", "message" => "Data Updated Successfully"], 200);
            }
        }
    }
    public function deleteOptional($id)
    {
        $op = petoptionaldata::where('id', $id)->delete();
        return response()->json(["status" => "200", "message" => "delete successfully"], 200);
    }

    public function DailyExcercise(Request $request)
    {

        $data = $request->all();
        $validator = Validator::make($data,
            [
                "exerciseName" => "required|min:3",
                "hours" => "required|numeric",
                "mins" => "required|numeric",
                "petE_id" => "required|numeric",

            ]);

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $op = dailyexcercise::create($data);
            if ($op) {
                return response()->json(["status" => "200", "message" => "Excercise Activity Created", "data" => ["createdExcercise" => $op]], 200);

            }

        }
    }
    // public function getExcercise($id, $date)
    // {
    //     $op = dailyexcercise::where('petE_id', $id)->get();
    //     $new = array();
    //     foreach ($op as $key => $value) {
    //         $value->check1 = $op[$key]->created_at->diffForHumans();
    //         if (str_contains($value->check1, $date)) {
    //             array_push($new, $value);}
    //     };

    //     return response()->json(["status" =>"200", "message" => "Success", "data" => ["petExcercise" => $new]], 200);

    // }

    public function getExcercise($id, $date)
    {
        $op = dailyexcercise::where('petE_id', $id)->where('created_at', '<', $date)->get();
        if (isset($op[0]->id)) {
            return response()->json(["status" => "200", "message" => "Success", "data" => ["petExcercises" => $op]], 200);
        } else {
            return response()->json(["status" => "500", "message" => "Internal Error"], 500);
        }

    }

    public function getExcercises($id)
    {
        $op = dailyexcercise::where('petE_id', $id)->get();
        if (isset($op[0]->id)) {
            return response()->json(["status" => "200", "message" => "Success", "data" => ["petExcercises" => $op]], 200);
        } else {
            return response()->json(["status" => "404", "message" => "no data", "data" => []], 404);
        }

    }

    public function petUpdateData($id, $pet_id)
    {
        $op = dailyexcercise::where('petE_id', $pet_id)->where('id', $id)->get();
        if (isset($op[0]->id)) {
            return response()->json(["status" => "200", "message" => "Success", "data" => ["petExcercises" => $op]], 200);
        } else {
            return response()->json(["status" => "404", "message" => "no data", "data" => []], 404);
        }

    }

    public function deleteExercise($id)
    {
        $op = dailyexcercise::where('id', $id)->delete();
        return response()->json(["status" => "200", "message" => "delete successfully"], 200);
    }

    public function UpdateExcercise(Request $request, $petE_id, $id)
    {
        $data = $request->all();
        $validator = Validator::make($data,
            [
                "exerciseName" => "required|min:3",
                "hours" => "required|numeric",
                "mins" => "required|numeric",
            ]);

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $op = dailyexcercise::where('petE_id', $petE_id)->where("id", $id)->update($data);
            if ($op) {
                $message = 'Excercise Updated';
                return response()->json(["status" => " 201", "message" => $message], 201);
            } else {
                $message = 'error try again';
                return response()->json(["status" => "500", "message" => $message], 500);
            }

        }

    }

    // daily food activity

    public function createFoodActivity(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data,
            [
                "product" => "array",
                "homeIngredient" => "array",
                "unit" => "required|min:3",
                "calPerUnit" => "required|numeric",
                "noOfUnits" => "required|numeric",
                "petA_id" => "required|numeric",

            ]);

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $product = array();
            $homeIngredient = array();

            $first = foodActivity::create([
                'petA_id' => $data['petA_id'],
                'unit' => $data['unit'],
                'calPerUnit' => $data['calPerUnit'],
                'noOfUnits' => $data['noOfUnits'],
            ]);
            if (isset($first->id)) {
                if ($request->has('product') and $data['product'] !== null) {
                    foreach ($data['product'] as $value) {

                        $products = array('petAf_id' => $data['petA_id'], 'product_id' => $value, 'foodActivity_id' => $first->id);
                        array_push($product, $products);

                    };
                    foreach ($product as $value) {$second = foodActivityForProducts::create($value);}

                }
                if ($request->has('homeIngredient') and $data['homeIngredient'] !== null) {
                    foreach ($data['homeIngredient'] as $value) {
                        $homeIngredients = array('petAi_id' => $data['petA_id'], 'ingredientA_id' => $value, 'foodActivityI_id' => $first->id);
                        array_push($homeIngredient, $homeIngredients);

                    }
                    foreach ($homeIngredient as $value) {$Third = foodActivityForIngredients::create($value);}
                }
            }

            return response()->json(["status" => "200", "message" => "Food Activity Created"], 200);

        }

    }

    public function getFoodActivity($id)
    {
        $products = DB::table('foodactivity')
            ->select('foodactivity.foodActivity_id', 'foodactivity.unit', 'foodactivity.petA_id', 'foodactivity.calPerUnit', 'foodactivity.noOfUnits', 'foodactivity.created_at', DB::raw("GROUP_CONCAT(animalproducts.productName) as products"))
            ->join("activityproduct", "activityproduct.foodactivity_id", "=", "foodactivity.foodactivity_id")->join("animalproducts", "activityproduct.product_id", "=", "animalproducts.id")->where('petAf_id', $id)
            ->groupBy('activityproduct.foodActivity_id')
            ->get();

        $ingredients = DB::table('foodactivity')
            ->select('foodactivity.foodActivity_id', 'foodactivity.petA_id', DB::raw("GROUP_CONCAT(ingredients.ingredientName) as ingredients"))
            ->join('activityingredient', 'activityingredient.foodActivityI_id', '=', 'foodactivity.foodactivity_id')->join('ingredients', 'ingredients.id', '=', 'activityingredient.ingredientA_id')->where('petAi_id', $id)
            ->groupBy('activityingredient.foodActivityI_id')->get();

        foreach ($products as $key => $value) {
            foreach ($ingredients as $key1 => $value1) {
                if ($value->foodActivity_id == $value1->foodActivity_id) {
                    $value->ingerients = $value1->ingredients;
                }

            }
        }
        return response()->json(["status" => "200", "message" => "success", "data" => ["foodActivity" => $products]], 200);

    }
    public function getFoodActivityByDate($id, $date)
    {
        $dateIncremented = strtotime("+1 day", strtotime($date));
        $products = DB::table('foodactivity')
            ->select('foodactivity.foodActivity_id', 'foodactivity.unit', 'foodactivity.petA_id', 'foodactivity.calPerUnit', 'foodactivity.noOfUnits', 'foodactivity.created_at', DB::raw("GROUP_CONCAT(animalproducts.productName) as products"))
            ->join("activityproduct", "activityproduct.foodactivity_id", "=", "foodactivity.foodactivity_id")->join("animalproducts", "activityproduct.product_id", "=", "animalproducts.id")->where('petAf_id', $id)->where("foodactivity.created_at", '<', date("Y-m-d", $dateIncremented))
            ->groupBy('activityproduct.foodActivity_id')
            ->get();

        $ingredients = DB::table('foodactivity')
            ->select('foodactivity.foodActivity_id', 'foodactivity.petA_id', DB::raw("GROUP_CONCAT(ingredients.ingredientName) as ingredients"))
            ->join('activityingredient', 'activityingredient.foodActivityI_id', '=', 'foodactivity.foodactivity_id')->join('ingredients', 'ingredients.id', '=', 'activityingredient.ingredientA_id')->where('petAi_id', $id)
            ->groupBy('activityingredient.foodActivityI_id')->get();

        foreach ($products as $key => $value) {
            foreach ($ingredients as $key1 => $value1) {
                if ($value->foodActivity_id == $value1->foodActivity_id) {
                    $value->ingerients = $value1->ingredients;
                }

            }
        }
        return response()->json(["status" => "200", "message" => "success", "data" => ["foodActivity" => $products]], 200);

    }
    public function deleteFoodActivity($id)
    {
        $op = foodActivity::where('foodActivity_id', $id)->delete();
        return response()->json(["status" => "200", "message" => "delete successfully"], 200);
    }

    public function CurrentStreak(){
        $now=new DateTime();
        return response()->json(["status" => "200", "message" => $now->format('n')], 200);
    }

}



