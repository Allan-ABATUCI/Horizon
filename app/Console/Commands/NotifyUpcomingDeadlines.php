<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueSoon;
use Illuminate\Console\Command;

class NotifyUpcomingDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:notify-due-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Notifie les utilisateurs assignés à une tâche dont l'échéance est demain";

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tasks = Task::query()
            ->where('status', '!=', 'terminé')
            ->whereDate('end_date', now()->addDay()->toDateString())
            ->with('assignedUser')
            ->get();

        foreach ($tasks as $task) {
            $task->assignedUser->notify(new TaskDueSoon($task));
        }

        $this->info("{$tasks->count()} tâche(s) notifiée(s).");
    }
}
