<?php

namespace App\Http\Controllers;

use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    public function getUser()
    {
        $data = Data::all();

        return response()->json($data);
    }

    public function addUser(Request $request) {
        $data = [
            'username' => $request->input("username"),
            'password' => $request->input("password"),
            'usertype' => $request->input("usertype"),
        ];
        return DB::table('tblUser')->insert($data);
    }

    public function updateUser() {

    }

    public function deleteUser(Request $request) {
        $id = $request->input("id");
        $user = Data::find($id);
        return $user->delete();
    }
}
