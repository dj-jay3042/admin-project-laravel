<?php

namespace App\Http\Controllers;

use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\Snappy\Facades\SnappyPdf;

class DataController extends Controller
{
    public function getColumns()
    {
        $columnNames = DB::select("SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = 'tblUser'
            ORDER BY ORDINAL_POSITION");
        $columnNames = array_column($columnNames, 'COLUMN_NAME');
        return response()->json($columnNames);
    }

    public function getUser()
    {
        $data = Data::orderBy('id', 'desc')->get();

        return response()->json($data);
    }

    public function addUser(Request $request)
    {
        $data = [
            'username' => $request->input("username"),
            'password' => $request->input("password"),
            'usertype' => $request->input("usertype"),
        ];
        return DB::table('tblUser')->insert($data);
    }

    public function updateUser(Request $request)
    {
        $data = [
            'id' => $request->input('id'),
            'username' => $request->input("username"),
            'password' => $request->input("password"),
            'usertype' => $request->input("usertype"),
        ];
        $user = Data::find($data['id']);
        $user->username = $data["username"];
        $user->password = $data["password"];
        $user->usertype = $data["usertype"];
        $user->save();
    }

    public function deleteUser(Request $request)
    {
        $id = $request->input("id");
        $user = Data::find($id);
        return $user->delete();
    }

    public function createPDF(Request $request)
    {
        $fltrType = $request->query('fltrType');
        $fltrVal = $request->query('fltrVal');
        $fields = $request->query('fields');
        $ids = $request->query('ids');

        array_unshift($fields, 'id');

        $filter = [];
        for ($i = 0; $i < count($fltrType); $i++) {
            $filter[$fltrType[$i]] = $fltrVal[$i];
        }

        $query = DB::table('tblUser')->whereIn('id', $ids)
            ->where($filter)
            ->select($fields)
            ->get();
            
        $data = [
            'results' => $query,
            'fields' => $fields,
        ];

        $pdf = SnappyPdf::loadView('pdf', compact('data'));
        return $pdf->download('pdf-file.pdf');
    }
}
