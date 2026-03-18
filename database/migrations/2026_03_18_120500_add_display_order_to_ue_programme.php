<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ue_programme', function (Blueprint $table) {
            $table->unsignedInteger('display_order')->default(0)->after('fk_semester');
        });

        $groups = DB::table('ue_programme')
            ->select('fk_programme', 'fk_semester')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $rows = DB::table('ue_programme')
                ->select('id')
                ->where('fk_programme', $group->fk_programme)
                ->where('fk_semester', $group->fk_semester)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $order = 1;
            foreach ($rows as $row) {
                DB::table('ue_programme')
                    ->where('id', $row->id)
                    ->update(['display_order' => $order++]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ue_programme', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });
    }
};

