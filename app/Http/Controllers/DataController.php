<?php

// app/Http/Controllers/DataController.php

namespace App\Http\Controllers;

use App\Models\Data;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function getUser()
    {
        $data = Data::all();

        return response()->json($data);
    }

    public function updateUser() {

    }

    public function deleteUser(Request $request) {
        $id = $request->input("id");
        $user = Data::find($id);
        return $user->delete();
    }
}
