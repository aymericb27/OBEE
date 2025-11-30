<?php

namespace App\Http\Controllers;

use App\Imports\UEImport;
use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageTerminaux;
use App\Models\AcquisApprentissageVise;
use App\Models\Programme;
use App\Models\UniteEnseignement;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{


    public function import(Request $request)
    {
        $import = new UEImport();
        Excel::import($import, $request->file('file'));
        return $this->storeUE($import->parsedData);
        return response()->json([
            'message' => 'Import réussi',
            'data' => $import->parsedData
        ]);
    }



    public function storeUE($values)
    {
        // --------------------------
        // VALIDATION GLOBALE
        // --------------------------
        $validator = Validator::make($values, [
            "ue.code" => "nullable|string|max:50",
            "ue.name"    => "required|string|max:255",
            "ue.description" => "nullable|string",
            "ue.ects"     => "required|integer|min:1|max:120",

            "aats"               => "array",
            "aats.*.libelle"        => "nullable|string",
            "aats.*.code" => "nullable|string",
            "aats.*.contribution" => "nullable|integer|in:1,2,3",

            "programmes"                  => "array",
            "programmes.*.code"    => "nullable|string",
            "programmes.*.libelle"        => "nullable|string",
            "programmes.*.semestre"       => "nullable|integer|min:1",

            "aavs"                         => "array",
            "aavs.*.code"           => "nullable|string",
            "aavs.*.libelle"               => "nullable|string",
            "aavs.*.AATCode"        => "nullable|string",
            "aavs.*.contribution"          => "nullable|integer|in:1,2,3",

            "prerequis"                    => "array",
            "prerequis.*.code"      => "nullable|string",
            "prerequis.*.libelle"          => "nullable|string",
        ], [
            // Messages personnalisés (clairs)
            "required" => "Le champ :attribute est obligatoire.",
            "integer"  => "Le champ :attribute doit être un nombre.",
            "in"       => "Le champ :attribute doit être une des valeurs suivantes : :values",
            "max"      => "Le champ :attribute est trop long.",
            "min"      => "Le champ :attribute est trop court."
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $ue = UniteEnseignement::create([
            'name' => $values['ue']['name'],
            'ects' => $values['ue']['ects'],
            'code' => $values['ue']['code'],
            'description' => $values['ue']['description'],
        ]);

        // -------------------------
        // 2) Liaison AAT de l’UE
        // -------------------------
        foreach ($values['aats'] as $aatData) {

            // créer ou récupérer l'AAT
            $aat = AAT::firstOrCreate([
                'code' => $aatData['code'],
            ], ['name' => $aatData['libelle']]);

            // attacher l’AAT à l’UE
            $ue->aat()->syncWithoutDetaching([
                $aat->id => [
                    'contribution' => $aatData['contribution']
                ]
            ]);
        }
        // -------------------------
        // 2) Liaison PROGRAMME de l’UE
        // -------------------------
        foreach ($values['programmes'] as $proData) {

            // créer ou récupérer le programme
            $pro = Programme::firstOrCreate([
                'code' => $proData['code'],
            ], ['name' => $proData['libelle'], 'semestre' => $proData['semestre']]);

            // attacher le programme à l’UE
            $ue->pro()->syncWithoutDetaching([
                $pro->id => [
                    'semester' => $proData['semestre']
                ]
            ]);
        }

        // -------------------------
        // 2) Liaison AAV ↔ UE avec AAT associé
        // -------------------------
        foreach ($values['aavs'] as $aavData) {

            // créer ou récupérer l'AAT
            $aat = AAT::firstOrCreate([
                'code' => $aavData['AATCode'],
            ], ['name' => $aavData['AATName']]);

            // 2) Création / récupération de l’AAV
            $aav = AcquisApprentissageVise::updateOrCreate(
                ['code' => $aavData['code']],
                [
                    'name'         => $aavData['name'],
                    'fk_AAT'       => $aat->id,
                    'contribution' => $aavData['contribution']
                ]
            );

            // 3) Attacher AAV à UE (sans dupliquer)
            $ue->aavvise()->syncWithoutDetaching([$aav->id]);
        }

        // -------------------------
        // 2) Liaison PROGRAMME de l’UE
        // -------------------------
        foreach ($values['prerequis'] as $preData) {

            // créer ou récupérer le prérequis
            $pre = AcquisApprentissageVise::firstOrCreate([
                'code' => $preData['code'],
            ], ['name' => $preData['libelle']]);

            // attacher le prérequis à l’UE
            $ue->prerequis()->syncWithoutDetaching([$pre->id]);
        }

        return $ue;
    }
}
