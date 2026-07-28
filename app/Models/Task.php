<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * Les notifications (assignation, échéance) référencent une tâche via un
     * task_id dans leur colonne JSON `data` — pas une vraie clé étrangère,
     * la table notifications étant générique. cascadeOnDelete() ne peut donc
     * pas s'en charger : on nettoie explicitement ici, à chaque suppression
     * de tâche quel que soit le chemin emprunté (contrôleur, tinker, etc.),
     * pour ne jamais laisser de notification pointer vers une tâche qui
     * n'existe plus.
     */
    protected static function booted(): void
    {
        static::deleting(function (Task $task) {
            DB::table('notifications')
                ->whereRaw("json_extract(data, '$.task_id') = ?", [$task->id])
                ->delete();
        });
    }

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'status',
        'priority',
        'start_date',
        'end_date',
        'assigned_user_id',
        'created_by',
        'updated_by',
        'project_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('id');
    }

    /**
     * Tâches dont celle-ci dépend (doivent être terminées avant celle-ci).
     */
    public function dependsOn()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_id')->withTimestamps();
    }

    /**
     * Tâches qui dépendent de celle-ci (bloquées tant qu'elle n'est pas terminée).
     */
    public function blocks()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_id', 'task_id')->withTimestamps();
    }
}
