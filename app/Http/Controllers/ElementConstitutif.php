<?php

namespace App\Http\Controllers;

use App\Models\ElementConstitutif as EC;
use App\Models\UEEC;
use Illuminate\Http\Request;

class ElementConstitutif extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'selectedUE' => 'required|array|max:2000',
            'description' => 'required|string|max:2024',
        ]);
        $ec = EC::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,

        ]);
        foreach ($request->selectedUE as $ueid) {
            UEEC::create([
                'fk_unite_enseignement' => $ueid,
                'fk_element_constitutif' => $ec->id,
            ]);
        }

        return response()->json(['message' => 'Element constitutif enregistré avec succès']);
    }

    public function get(Request $request)
    {
        return EC::get();
    }
}
