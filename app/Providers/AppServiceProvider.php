<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\AttachmentPolicy;
use App\Policies\ChecklistItemPolicy;
use App\Policies\CommentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
        Gate::policy(ChecklistItem::class, ChecklistItemPolicy::class);

        // Nonce partagé par les tags générés par @vite/@viteReactRefresh et
        // par le script inline de Ziggy (voir resources/views/app.blade.php),
        // pour que SecurityHeaders puisse poser un script-src strict.
        Vite::useCspNonce();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
