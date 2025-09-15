<?php

namespace App\Http\Controllers;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;

class UniteEnseignement extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'ects' => 'required|integer|max:200',
            'code' => 'required|string|max:10',
            'description' => 'required|string|max:2024',
        ]);
        UE::create([
            'nom' => $request->nom,
            'ects' => $request->ects,
            'code' => $request->code,
            'description' => $request->description,

        ]);
        return UE::get();
    }
    public function get(Request $request) {
        $ues = UE::get();
        return $ues;
    }
}
