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
    protected $pk;

    public function __construct(Data $data) {
        $this->pk = $data->getKey();
    }

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
        $data = Data::all();

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
        $user = DB::update("UPDATE tblUser set " . $qry . " WHERE " . $this->pk . " = " . $id);
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

        array_unshift($fields, $this->pk->getKey());

        if ($fltrType == null || $fltrVal == null) {
            $query = DB::table('tblUser')->whereIn($this->pk, $ids)
                ->select($fields)
                ->get();
        } else {
            if (count($fltrType) >= count($fltrVal)) {
                $filter = [];
                for ($i = 0; $i < count($fltrType); $i++) {
                    $filter[$i] = [$fltrType[$i], 'LIKE', $fltrVal[$i]];
                }

                $query = DB::table('tblUser')->where($filter)
                    ->select()
                    ->get();
            } else {
                $filter1 = [];
                $filter2 = [];
                $count = 0;
                while ($count < count($fltrType)) {
                    $filter1[$count] = $fltrType[$count] . " LIKE '" . $fltrVal[$count] . "'";
                    $count++;
                }

                $index = 0;
                $columnNames = DB::select("SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = 'tblUser'
                    ORDER BY ORDINAL_POSITION");
                $fld = array_column($columnNames, 'COLUMN_NAME');
                for ($i = $count; $i < count($fltrVal); $i++) {
                    for ($j = 0; $j < 4; $j++) {
                        $filter2[$index] = $fld[$j] . " LIKE '" . $fltrVal[$i] . "'";
                        $index++;
                    }
                }
                $qry = "SELECT * FROM tblUser WHERE " . implode(" AND ", $filter1) . " AND (" . implode(" OR ", $filter2) . ")";
                $query = DB::select($qry);
            }
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
            $data = DB::table('tblUser')->whereIn($this->pk, $ids)
                ->select($fields)
                ->get();
        } else {
            if (count($fltrType) >= count($fltrVal)) {
                $filter = [];
                for ($i = 0; $i < count($fltrType); $i++) {
                    $filter[$i] = [$fltrType[$i], 'LIKE', $fltrVal[$i]];
                }

                $data = DB::table('tblUser')->where($filter)
                    ->select()
                    ->get();
            } else {
                $filter1 = [];
                $filter2 = [];
                $count = 0;
                while ($count < count($fltrType)) {
                    $filter1[$count] = $fltrType[$count] . " LIKE '" . $fltrVal[$count] . "'";
                    $count++;
                }

                $index = 0;
                $columnNames = DB::select("SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = 'tblUser'
                    ORDER BY ORDINAL_POSITION");
                $fld = array_column($columnNames, 'COLUMN_NAME');
                for ($i = $count; $i < count($fltrVal); $i++) {
                    for ($j = 0; $j < 4; $j++) {
                        $filter2[$index] = $fld[$j] . " LIKE '" . $fltrVal[$i] . "'";
                        $index++;
                    }
                }
                $qry = "SELECT * FROM tblUser WHERE " . implode(" AND ", $filter1) . " AND (" . implode(" OR ", $filter2) . ")";
                $data = DB::select($qry);
            }
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

        if (count($fltrType) >= count($fltrVal)) {
            $filter = [];
            for ($i = 0; $i < count($fltrType); $i++) {
                $filter[$i] = [$fltrType[$i], 'LIKE', $fltrVal[$i]];
            }

            $data = DB::table('tblUser')->where($filter)
                ->select()
                ->get();

            return response()->json($data);
        } else {
            $filter1 = [];
            $filter2 = [];
            $count = 0;
            while ($count < count($fltrType)) {
                $filter1[$count] = $fltrType[$count] . " LIKE '" . $fltrVal[$count] . "'";
                $count++;
            }

            $index = 0;
            $columnNames = DB::select("SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = 'tblUser'
            ORDER BY ORDINAL_POSITION");
            $fld = array_column($columnNames, 'COLUMN_NAME');
            for ($i = $count; $i < count($fltrVal); $i++) {
                for ($j = 0; $j < 4; $j++) {
                    $filter2[$index] = $fld[$j] . " LIKE '" . $fltrVal[$i] . "'";
                    $index++;
                }
            }
            $qry = "SELECT * FROM tblUser WHERE " . implode(" AND ", $filter1) . " AND (" . implode(" OR ", $filter2) . ")";
            $data = DB::select($qry);

            return response()->json($data);
        }
    }

    public function createCSV(Request $request)
    {
        $fltrType = $request->query('fltrType');
        $fltrVal = $request->query('fltrVal');
        $fields = $request->query('fields');
        $ids = $request->query('ids');

        array_unshift($fields, 'id');

        if ($fltrType == null || $fltrVal == null) {
            $data = DB::table('tblUser')->whereIn($this->pk, $ids)
                ->select($fields)
                ->get();
        } else {
            if (count($fltrType) >= count($fltrVal)) {
                $filter = [];
                for ($i = 0; $i < count($fltrType); $i++) {
                    $filter[$i] = [$fltrType[$i], 'LIKE', $fltrVal[$i]];
                }

                $data = DB::table('tblUser')->where($filter)
                    ->select()
                    ->get();
            } else {
                $filter1 = [];
                $filter2 = [];
                $count = 0;
                while ($count < count($fltrType)) {
                    $filter1[$count] = $fltrType[$count] . " LIKE '" . $fltrVal[$count] . "'";
                    $count++;
                }

                $index = 0;
                $columnNames = DB::select("SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = 'tblUser'
                    ORDER BY ORDINAL_POSITION");
                $fld = array_column($columnNames, 'COLUMN_NAME');
                for ($i = $count; $i < count($fltrVal); $i++) {
                    for ($j = 0; $j < 4; $j++) {
                        $filter2[$index] = $fld[$j] . " LIKE '" . $fltrVal[$i] . "'";
                        $index++;
                    }
                }
                $qry = "SELECT * FROM tblUser WHERE " . implode(" AND ", $filter1) . " AND (" . implode(" OR ", $filter2) . ")";
                $data = DB::select($qry);
            }
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
