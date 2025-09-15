<?php

namespace App\Http\Controllers;

use App\Models\ue as UEModel;
use Illuminate\Http\Request;

class UE extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        UEModel::create([
            'name' => $request->name,
        ]);
        return UEModel::get();
    }
    public function get(Request $request) {
        $ues = UEModel::get();
        return $ues;
    }
}
