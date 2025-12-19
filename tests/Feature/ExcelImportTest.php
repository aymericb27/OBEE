<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Imports\UsersImport; // ta classe d’import
use App\Models\User;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
/*     public function it_imports_users_from_excel()
    {
        // Fake Excel file
        Excel::fake();

        // Simulated data inside the file
        $file = new Excel;
        $data = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
        ];

        // Store fake file
        $path = storage_path('testing/import.xlsx');
        \Maatwebsite\Excel\Facades\Excel::store(
            new \Maatwebsite\Excel\Collections\SheetCollection([
                'Sheet 1' => collect($data),
            ]),
            'testing/import.xlsx',
            'local'
        );

        // Run import
        Excel::import(new UsersImport, 'testing/import.xlsx', 'local');

        // Database assertions
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com'
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com'
        ]);
    } */
}
