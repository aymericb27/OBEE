<?php

namespace App\Http\Controllers;

use App\Imports\UEImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ImportController extends Controller
{


    public function import(Request $request)
    {
        $import = new UEImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Import réussi',
            'data' => $import->parsedData
        ]);
    }
}
