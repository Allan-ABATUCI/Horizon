import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { TaskKanbanBoard } from '@/components/kanban/task-kanban-board';
import { TaskFormDialog } from '@/components/task/task-form-dialog';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Task } from '@/types';

type Option = { id: number; name: string };

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
];

export default function Dashboard({ tasks, projects, users }: { tasks: { data: Task[] }; projects: Option[]; users: Option[] }) {
    const [createOpen, setCreateOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tableau de bord" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-medium">Mes tâches</h1>
                    <Button onClick={() => setCreateOpen(true)}>Créer une tâche</Button>
                </div>

                <TaskKanbanBoard tasks={tasks.data} />
            </div>

            <TaskFormDialog mode="create" open={createOpen} onOpenChange={setCreateOpen} projects={projects} users={users} />
        </AppLayout>
    );
}
