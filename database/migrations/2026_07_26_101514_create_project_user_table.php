<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        $this->backfillExistingMemberships();
    }

    /**
     * Rend membres les créateurs et assignés déjà présents avant l'introduction
     * de la notion de membre, pour ne pas rendre invisibles des tâches déjà assignées.
     */
    private function backfillExistingMemberships(): void
    {
        $now = now();

        $creators = DB::table('projects')->select('id as project_id', 'created_by as user_id')->get();

        $assignees = DB::table('tasks')
            ->select('project_id', 'assigned_user_id as user_id')
            ->distinct()
            ->get();

        $rows = $creators->concat($assignees)
            ->unique(fn ($row) => $row->project_id.'-'.$row->user_id)
            ->map(fn ($row) => [
                'project_id' => $row->project_id,
                'user_id' => $row->user_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        if ($rows->isNotEmpty()) {
            DB::table('project_user')->insert($rows->all());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
