<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $userId = auth()->id();

        if (mb_strlen($term) < 2) {
            return response()->json(['projects' => [], 'tasks' => []]);
        }

        $ftsQuery = $this->toFtsQuery($term);

        $projects = DB::select('
            SELECT projects.id, projects.name
            FROM projects_fts
            JOIN projects ON projects.id = projects_fts.rowid
            JOIN project_user ON project_user.project_id = projects.id AND project_user.user_id = ?
            WHERE projects_fts MATCH ?
            ORDER BY bm25(projects_fts)
            LIMIT 5
        ', [$userId, $ftsQuery]);

        $tasks = DB::select('
            SELECT tasks.id, tasks.name, tasks.project_id
            FROM tasks_fts
            JOIN tasks ON tasks.id = tasks_fts.rowid
            JOIN project_user ON project_user.project_id = tasks.project_id AND project_user.user_id = ?
            WHERE tasks_fts MATCH ?
            ORDER BY bm25(tasks_fts)
            LIMIT 8
        ', [$userId, $ftsQuery]);

        return response()->json(['projects' => $projects, 'tasks' => $tasks]);
    }

    /**
     * Transforme la saisie utilisateur en requête FTS5 sûre : chaque mot est
     * mis entre guillemets (littéral, échappe la syntaxe d'opérateurs FTS5 —
     * AND/OR/NOT/-/^ ne sont plus interprétés) puis suffixé de * pour un
     * match par préfixe, les mots étant implicitement combinés en ET.
     */
    private function toFtsQuery(string $term): string
    {
        $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);

        return collect($tokens)
            ->map(fn ($token) => '"'.str_replace('"', '""', $token).'"*')
            ->implode(' ');
    }
}
