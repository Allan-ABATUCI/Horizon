<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::query()->with(['creator', 'editor']);
        $projects = $query->paginate(10)->onEachSide(1);

        return Inertia::render('project/Index', [
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Non utilisée : la création se fait via une modale sur project/Index.
     */
    public function create()
    {
        return redirect()->route('project.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('projects', 'public');
        }
        unset($validated['image']);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        Project::create($validated);

        return redirect()->route('project.index')->with('success', 'Projet créé.');
    }

    /**
     * Display the specified resource.
     *
     * Non utilisée : pas de page dédiée, le front navigue directement vers
     * task.index?project_id=X pour voir les tâches d'un projet.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return redirect()->route('project.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Non utilisée : l'édition se fait via une modale sur project/Index.
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return redirect()->route('project.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('projects', 'public');
        }
        unset($validated['image']);

        $validated['updated_by'] = auth()->id();

        $project->update($validated);

        return redirect()->route('project.index')->with('success', 'Projet modifié.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        if ($project->tasks()->exists()) {
            return redirect()->route('project.index')->with('error', 'Supprimez d\'abord les tâches de ce projet.');
        }

        if ($project->image_path && ! str_starts_with($project->image_path, 'http')) {
            Storage::disk('public')->delete($project->image_path);
        }

        $project->delete();

        return redirect()->route('project.index')->with('success', 'Projet supprimé.');
    }
}
