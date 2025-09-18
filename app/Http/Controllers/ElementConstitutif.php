<?php

namespace App\Http\Controllers;

use App\Models\ElementConstitutif as EC;
use Illuminate\Http\Request;

class ElementConstitutif extends Controller
{
    public function store(Request $request){
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'selectedUE' => 'required|integer|max:2000',
            'volume_horaire' => 'required|integer|max:180',
            'description' => 'required|string|max:2024',
        ]);
        EC::create([
            'nom' => $request->nom,
            'code' => $request->code,
            'fk_unite_enseignement' => $request->selectedUE,
            'volume_horaire' => $request->volume_horaire,
            'description' => $request->description,

        ]);
        return EC::get();
    }

    public function get(Request $request) {
        return EC::get();
    }
}
