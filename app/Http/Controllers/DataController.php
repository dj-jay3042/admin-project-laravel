<?php

namespace App\Http\Controllers;

use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\Snappy\Facades\SnappyPdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $id = $request->input('id');
        $data = $request->input('data');
        $qry = "";
        foreach ($data as $key => $value) {
            $qry .= $key . "=" . "'" . $value . "',";
        }
        $qry = substr($qry, 0, -1);
        $user = DB::update("UPDATE tblUser set " . $qry . " WHERE id = " . $id);
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

        if ($fltrType == null || $fltrVal == null) {
            $query = DB::table('tblUser')->whereIn('id', $ids)
                ->select($fields)
                ->get();
        } else {
            $filter = [];
            for ($i = 0; $i < count($fltrType); $i++) {
                $filter[$i] = [$fltrType[$i], 'LIKE', $fltrVal[$i]];
            }

            $query = DB::table('tblUser')->whereIn('id', $ids)
                ->where($filter)
                ->select($fields)
                ->get();
        }

        $data = [
            'results' => $query,
            'fields' => $fields,
        ];

        $pdf = SnappyPdf::loadView('pdf', compact('data'));
        return $pdf->download('data.pdf');
    }

    public function createExcel(Request $request)
    {
        $fltrType = $request->query('fltrType');
        $fltrVal = $request->query('fltrVal');
        $fields = $request->query('fields');
        $ids = $request->query('ids');

        array_unshift($fields, 'id');

        if ($fltrType == null || $fltrVal == null) {
            $data = DB::table('tblUser')->whereIn('id', $ids)
                ->select($fields)
                ->get();
        } else {
            $filter = [];
            for ($i = 0; $i < count($fltrType); $i++) {
                $filter[$i] = [$fltrType[$i], 'LIKE', $fltrVal[$i]];
            }

            $data = DB::table('tblUser')->whereIn('id', $ids)
                ->where($filter)
                ->select($fields)
                ->get();
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = array_keys((array) $data[0]);
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        // Set data rows
        $row = 2;
        foreach ($data as $rowValues) {
            $column = 'A';
            foreach ((array) $rowValues as $value) {
                $sheet->setCellValue($column . $row, $value);
                $column++;
            }
            $row++;
        }

        // Save the spreadsheet
        $writer = new Xlsx($spreadsheet);
        $writer->save('data.xlsx');

        return response()->download('data.xlsx')->deleteFileAfterSend(true);
    }

    public function applyFilter(Request $request)
    {
        $fltrType = $request->query('fltrType');
        $fltrVal = $request->query('fltrVal');

        $filter = [];
        for ($i = 0; $i < count($fltrType); $i++) {
            $filter[$i] = [$fltrType[$i], 'LIKE', $fltrVal[$i]];
        }

        $data = DB::table('tblUser')->where($filter)
            ->select()
            ->get();

        return response()->json($data);
    }

    public function createCSV(Request $request)
    {
        $fltrType = $request->query('fltrType');
        $fltrVal = $request->query('fltrVal');
        $fields = $request->query('fields');
        $ids = $request->query('ids');

        array_unshift($fields, 'id');

        if ($fltrType == null || $fltrVal == null) {
            $data = DB::table('tblUser')->whereIn('id', $ids)
                ->select($fields)
                ->get();
        } else {
            $filter = [];
            for ($i = 0; $i < count($fltrType); $i++) {
                $filter[$i] = [$fltrType[$i], 'LIKE', $fltrVal[$i]];
            }

            $data = DB::table('tblUser')->whereIn('id', $ids)
                ->where($filter)
                ->select($fields)
                ->get();
        }
        $csvContent = implode(',', array_keys((array) $data[0])) . "\n"; // Headers

        foreach ($data as $rowValues) {
            $csvContent .= implode(',', (array) $rowValues) . "\n"; // Data rows
        }

        // Generate the CSV file
        $filePath = 'data.csv';
        file_put_contents($filePath, $csvContent);

        // Return the CSV file as a response
        $headers = array(
            'Content-Type' => 'text/csv',
        );

        return response()->download($filePath, 'data.csv', $headers)->deleteFileAfterSend(true);
    }
}
