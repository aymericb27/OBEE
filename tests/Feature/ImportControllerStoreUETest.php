<?php

namespace Tests\Feature;

use App\Http\Controllers\ImportController;
use App\Models\AcquisApprentissageTerminaux;
use App\Models\AcquisApprentissageVise;
use App\Models\Programme;
use App\Models\UniteEnseignement;
use App\Models\University;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Illuminate\Support\Str;

class ImportControllerStoreUETest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithUniversity(): array
    {
        $uni = University::create([
            'name' => 'Uni Test',
        ]);

        $user = User::firstOrCreate([
            'name' => 'Admin',
            'firstname' => 'Test',
            'email' => 'admin_' . Str::uuid() . '@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_approved' => true,
            'university_id' => $uni->id,
        ]);

        return [$user, $uni];
    }

    private function payload(array $overrides = []): array
    {
        $base = [
            'ue' => [
                'code' => 'UE001',
                'name' => 'Analyse I',
                'description' => 'Desc',
                'ects' => 5,
            ],
            'aats' => [],
            'programmes' => [],
            'aavs' => [],
            'prerequis' => [],
        ];

        return array_replace_recursive($base, $overrides);
    }

    private function mockCodeGen(): void
    {
        // Le contrôleur doit utiliser CodeGeneratorService (injecté ou résolu via container)
        $mock = $this->mock(CodeGeneratorService::class);
        $mock->shouldReceive('nextAAT')->andReturn('AAT001');
        $mock->shouldReceive('nextProgramme')->andReturn('PRO001');
        $mock->shouldReceive('nextAAV')->andReturn('AAV001');
        $mock->shouldReceive('nextPrerequis')->andReturn('PRE001');
    }

    private function controller(): ImportController
    {
        return app()->make(ImportController::class);
    }

    public function test_creates_ue(): void
    {
        [$user, $uni] = $this->makeUserWithUniversity();
        $this->actingAs($user);
        $this->mockCodeGen();

        Log::debug($user);
        $ue = $this->controller()->storeUE($this->payload());

        $this->assertInstanceOf(UniteEnseignement::class, $ue);

        $this->assertDatabaseHas('unite_enseignement', [
            'id' => $ue->id,
            'name' => 'Analyse I',
            'code' => 'UE001',
            'ects' => 5,
            'university_id' => $ue->university_id,
        ]);
    }

    public function test_validation_error_when_missing_ue_name(): void
    {
        [$user] = $this->makeUserWithUniversity();
        $this->actingAs($user);
        $this->mockCodeGen();

        $this->expectException(ValidationException::class);

        $this->controller()->storeUE($this->payload([
            'ue' => ['name' => null],
        ]));
    }

    public function test_creates_aat_with_generated_code_and_attaches_pivot_contribution(): void
    {
        [$user, $uni] = $this->makeUserWithUniversity();
        $this->actingAs($user);
        $this->mockCodeGen();

        $values = $this->payload([
            'aats' => [
                ['code' => '', 'libelle' => 'AAT lib 1', 'contribution' => 2],
            ],
        ]);

        $ue = $this->controller()->storeUE($values);

        $this->assertDatabaseHas('acquis_apprentissage_terminaux', [
            'code' => 'AAT001',
            'name' => 'AAT lib 1',
            'university_id' => $uni->id,
        ]);

        $aat = AcquisApprentissageTerminaux::where('code', 'AAT001')
            ->where('university_id', $uni->id)
            ->firstOrFail();

        $this->assertDatabaseHas('ue_aat', [
            'fk_ue' => $ue->id,
            'fk_aat' => $aat->id,
            'contribution' => 2,
            'university_id' => $uni->id, // décommente si la colonne existe vraiment dans ce pivot
        ]);
    }

    public function test_creates_programme_with_generated_code_and_attaches_pivot_semester(): void
    {
        [$user, $uni] = $this->makeUserWithUniversity();
        $this->actingAs($user);
        $this->mockCodeGen();

        $values = $this->payload([
            'programmes' => [
                ['code' => '', 'libelle' => 'Info', 'semestre' => 1],
            ],
        ]);

        $ue = $this->controller()->storeUE($values);

        $this->assertDatabaseHas('programme', [
            'code' => 'PRO001',
            'name' => 'Info',
            'university_id' => $uni->id,
        ]);

        $pro = Programme::where('code', 'PRO001')
            ->where('university_id', $uni->id)
            ->firstOrFail();

        $this->assertDatabaseHas('ue_programme', [
            'fk_unite_enseignement' => $ue->id,
            'fk_programme' => $pro->id,
            'semester' => 1,
            // 'university_id' => $uni->id, // décommente si la colonne existe vraiment dans ce pivot
        ]);
    }

    public function test_creates_aav_with_generated_code_and_attaches(): void
    {
        [$user, $uni] = $this->makeUserWithUniversity();
        $this->actingAs($user);
        $this->mockCodeGen();

        $values = $this->payload([
            'aavs' => [
                ['code' => '', 'libelle' => 'AAV lib 1'],
            ],
        ]);

        $ue = $this->controller()->storeUE($values);

        $this->assertDatabaseHas('acquis_apprentissage_vise', [
            'code' => 'AAV001',
            'name' => 'AAV lib 1',
            'university_id' => $uni->id,
        ]);

        $aav = AcquisApprentissageVise::where('code', 'AAV001')
            ->where('university_id', $uni->id)
            ->firstOrFail();

        $this->assertDatabaseHas('aavue_vise', [
            'fk_unite_enseignement' => $ue->id,
            'fk_acquis_apprentissage_vise' => $aav->id,
            // 'university_id' => $uni->id, // décommente si la colonne existe vraiment dans ce pivot
        ]);
    }

    public function test_creates_prerequis_with_generated_code_and_attaches(): void
    {
        [$user, $uni] = $this->makeUserWithUniversity();
        $this->actingAs($user);
        $this->mockCodeGen();

        $values = $this->payload([
            'prerequis' => [
                ['code' => '', 'libelle' => 'Pré requis 1'],
            ],
        ]);

        $ue = $this->controller()->storeUE($values);

        $this->assertDatabaseHas('acquis_apprentissage_vise', [
            'code' => 'PRE001',
            'name' => 'Pré requis 1',
            'university_id' => $uni->id,
        ]);

        $pre = AcquisApprentissageVise::where('code', 'PRE001')
            ->where('university_id', $uni->id)
            ->firstOrFail();

        $this->assertDatabaseHas('aavue_prerequis', [
            'fk_unite_enseignement' => $ue->id,
            'fk_acquis_apprentissage_prerequis' => $pre->id,
            // 'university_id' => $uni->id, // décommente si la colonne existe vraiment dans ce pivot
        ]);
    }
    public function test_does_not_cross_reference_other_university_data(): void
    {
        // Uni A
        [$userA, $uniA] = $this->makeUserWithUniversity();
        $this->actingAs($userA);
        $this->mockCodeGen();

        // Uni B
        [$userB, $uniB] = $this->makeUserWithUniversity();

        // ✅ créer AAV999 en étant connecté sur Uni B (si BelongsToUniversity force university_id)
        $this->actingAs($userB);
        $aavB = AcquisApprentissageVise::create([
            'code' => 'AAV999',
            'name' => 'AAV Uni B',
            // inutile si le trait force, mais ok :
            'university_id' => $uniB->id,
        ]);

        // Revenir sur Uni A
        $this->actingAs($userA);

        // Import : même code, libellé vide => doit être ignoré (car n'existe pas dans Uni A)
        $values = $this->payload([
            'aavs' => [
                ['code' => 'AAV999', 'libelle' => ''],
            ],
        ]);

        $ue = $this->controller()->storeUE($values);

        // ✅ Assertion correcte : l'AAV de Uni B n'est pas attaché à l'UE de Uni A
        $this->assertDatabaseMissing('aavue_vise', [
            'fk_unite_enseignement' => $ue->id,
            'fk_acquis_apprentissage_vise' => $aavB->id,
        ]);

        // Bonus : vérifier qu’il n’existe PAS de AAV999 dans Uni A
        $this->assertDatabaseMissing('acquis_apprentissage_vise', [
            'code' => 'AAV999',
            'university_id' => $uniA->id,
        ]);
    }
}
