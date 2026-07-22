<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()->paginate(10)->onEachSide(1);

        return Inertia::render('user/Index', [
            'users' => UserResource::collection($users),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Non utilisée : la création se fait via une modale sur user/Index.
     */
    public function create()
    {
        return redirect()->route('user.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('user.index')->with('success', 'Utilisateur créé.');
    }

    /**
     * Display the specified resource.
     *
     * Non utilisée : pas de page dédiée, tout se fait depuis user/Index.
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return redirect()->route('user.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Non utilisée : l'édition se fait via une modale sur user/Index.
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return redirect()->route('user.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('user.index')->with('success', 'Utilisateur modifié.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        if ($user->createdProjects()->exists() || $user->createdTasks()->exists()) {
            return redirect()->route('user.index')->with('error', 'Ce compte ne peut pas être supprimé : il est lié à des projets ou tâches existants.');
        }

        $isSelf = $user->id === auth()->id();

        $user->delete();

        if ($isSelf) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        }

        return redirect()->route('user.index')->with('success', 'Utilisateur supprimé.');
    }
}
