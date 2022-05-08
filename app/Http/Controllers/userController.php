<?php

namespace App\Http\Controllers;

use App\Models\pet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class userController extends Controller
{

    public function Signup(Request $request)
    {

        $data = $request->all();

        $validator = Validator::make($data,
            [
                "name" => "required|min:3",
                "email" => "required|email|unique:users",
                "password" => "required|min:6",
                "phone" => "required|digits_between:11,20",
            ], [
                'digits_between' => "Enter Valid phone number",
            ]);

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            // code .....
            $AuthPassword = array(
                'password' => 123,
            );
            $AuthPassword['password'] = $data['password'];

            $data['password'] = Hash::make($data['password']);

            $op = User::create($data);

            if ($op) {
                if (Auth::attempt(['email' => $data['email'], 'password' => $AuthPassword['password']])) {

                    $token = auth()->user()->createToken("auth_token")->plainTextToken;
                    $message = 'User Created';
                    return response()->json(["status" => " 201", "message" => $message, "data" => ["User" => $op, "token" => $token]], 201);}

            } else {
                $message = 'error try again';
                return response()->json(["status" => "500", "message" => $message, 'data' => ['error' => "server error"]], 500);
            }

        }

    }
    public function doLogin(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data,
            [
                "email" => "required|email",
                "password" => "required|min:6",
            ]);
        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
                $token = auth()->user()->createToken("auth_token")->plainTextToken;
                $User = auth()->user();

                return response()->json(["status" => "202", "message" => "You are logged in", "data" => ["token" => $token, "User" => $User]], 202);

            } else {
                return response()->json(["status" => "403", "message" => "Wrong Email or Password", "data" => ['error' => "Wrong Email or Password"]], 403);
            }
        }

    }
    public function Update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data,
            [
                "currentPassword" => "required",
                "password" => "required|confirmed",

            ]);
        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {

            $id = auth()->user()->id;
            $User = User::find($id);
            $currentPassword = $data['currentPassword'];
            if (Hash::check($currentPassword, $User['password'])) {
                if (Hash::check($data['password'], $User['password'])) {
                    return response()->json(["status" => "405 ", "message" => "Password must be new value", "data" => ['error' => "Password must be new value"]], 405);
                } else {
                    $op = User::where('id', $id)->update([
                        'password' => Hash::make($data['password'])]
                    );
                }

                return response()->json(["status" => "201", "message" => "Password Updated Successfully", "data" => ['report' => "Password Updated Successfully"]], 201);

            } else {
                return response()->json(["status" => "404", "message" => "Wrong Password", "data" => ['error' => "Wrong Password"]], 404);
            }

        }

    }
    public function LogOut(Request $request)
    {
        // code .....
        $id = auth()->user()->id;
        $User = User::find($id);
        Auth::guard('web')->logout();
        $User->tokens()->delete();
        return response()->json(["status" => "200", "message" => "You are logged out", "data" => ['report' => "You are logged out"]], 200);

    }
    public function UserData(Request $request)
    {
        $id = auth()->user()->id;
        $User = User::find($id);
        $User['profile_photo_path'] = url('images/' . $User['profile_photo_path']);
        $dogsNo = pet::where("user_id", $id)->where("specie", "dog")->count();
        $catsNo = pet::where("user_id", $id)->where("specie", "cat")->count();
        $User['default_image'] = url('default/' . $User['default_image']);
        if (isset($User['id'])) {
            $User['dogsNo'] = $dogsNo;
            $User['catsNo'] = $catsNo;
            return response()->json(["status" => "200", "message" => "User data", "data" => ["User" => $User]], 200);
        } else {
            return response()->json(["status" => "404", "message" => "User data Not Found", "data" => ['error' => "User data Not Found"]], 404);
        }

    }
    public function UpdateUserData(Request $request)
    {
        $data = $request->all();
        $id = auth()->user()->id;
        $User = User::find($id);

        $validator = Validator::make($data,
            [
                "name" => "required|min:3",
                "email" => "required|email",
                "phone" => "required|digits_between:11,20",
                "image" => "nullable|image|mimes:png,jpg",
            ], [
                'digits_between' => "Enter Valid phone number",
            ]);

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            if ($request->hasFile('image')) {

                $FinalName = hexdec(uniqid()) . '.' . $request->image->extension();

                if ($request->image->move(public_path('images'), $FinalName)) {
                    $file = public_path('images/' . $User['profile_photo_path']);
                    if (file_exists($file) and $User['profile_photo_path'] !== null) {
                        unlink(public_path('images/' . $User['profile_photo_path']));
                    }

                }
            } else {
                $FinalName = $User['profile_photo_path'];

            }
            $data['image'] = $FinalName;
            $op = User::where("email", '=', $data['email'])->first();
            // if ($data['email'] == auth()->user()->email) {
            //     return response()->json(["status" => "400", "message" => 'Enter New Email Value', "data" => ['error' => "Enter New Email Value"]], 400);
            // }
            if ($op->id !== auth()->user()->id) {
                return response()->json(["status" => "400", "message" => 'This Email Already Exists', "data" => ['error' => "This Email Already Exists"]], 400);
            }

            $op = User::where('id', $id)->update([
                "name" => $data['name'],
                "email" => $data['email'],
                "phone" => $data['phone'],
                'profile_photo_path' => $data['image'],
            ]);
            $User = User::where('id', '=', $id)->first();
            return response()->json(["status" => "201", "message" => "data Updated Successfully", "data" => ['UpdatedUser' => $User]], 201);
        }

    }

}
