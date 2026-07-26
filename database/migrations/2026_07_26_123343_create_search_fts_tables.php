<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createFtsTable('projects');
        $this->createFtsTable('tasks');
    }

    /**
     * Crée la table virtuelle FTS5 « external content » pour $table (projects
     * ou tasks), l'indexe avec le contenu déjà présent, puis pose les 3
     * triggers standards SQLite qui la maintiennent synchronisée.
     */
    private function createFtsTable(string $table): void
    {
        $fts = "{$table}_fts";

        DB::statement("
            CREATE VIRTUAL TABLE {$fts} USING fts5(
                name, description,
                content='{$table}', content_rowid='id',
                tokenize = 'unicode61 remove_diacritics 2'
            )
        ");

        DB::statement("INSERT INTO {$fts}(rowid, name, description) SELECT id, name, description FROM {$table}");

        DB::statement("
            CREATE TRIGGER {$fts}_ai AFTER INSERT ON {$table} BEGIN
                INSERT INTO {$fts}(rowid, name, description) VALUES (new.id, new.name, new.description);
            END
        ");

        DB::statement("
            CREATE TRIGGER {$fts}_ad AFTER DELETE ON {$table} BEGIN
                INSERT INTO {$fts}({$fts}, rowid, name, description) VALUES('delete', old.id, old.name, old.description);
            END
        ");

        DB::statement("
            CREATE TRIGGER {$fts}_au AFTER UPDATE ON {$table} BEGIN
                INSERT INTO {$fts}({$fts}, rowid, name, description) VALUES('delete', old.id, old.name, old.description);
                INSERT INTO {$fts}(rowid, name, description) VALUES (new.id, new.name, new.description);
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['projects', 'tasks'] as $table) {
            $fts = "{$table}_fts";
            DB::statement("DROP TRIGGER IF EXISTS {$fts}_ai");
            DB::statement("DROP TRIGGER IF EXISTS {$fts}_ad");
            DB::statement("DROP TRIGGER IF EXISTS {$fts}_au");
            DB::statement("DROP TABLE IF EXISTS {$fts}");
        }
    }
};
