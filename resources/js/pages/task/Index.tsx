import { PaginationLinks } from '@/components/pagination-links';
import { TaskFormDialog } from '@/components/task/task-form-dialog';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { getTaskColumns } from '@/pages/task/columns';
import { Task, type BreadcrumbItem, type PaginatedResponse, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Option = { id: number; name: string };

export default function Index({
    tasks,
    projectId,
    projects,
    users,
}: {
    tasks: PaginatedResponse<Task>;
    projectId: number | null;
    projects: Option[];
    users: Option[];
}) {
    const { auth } = usePage<SharedData>().props;
    const [createOpen, setCreateOpen] = useState(false);
    const [editingTask, setEditingTask] = useState<Task | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tableau de bord', href: '/dashboard' },
        { title: 'Tâches', href: '/task' },
    ];

    const canDelete = (task: Task) => task.created_by.id === auth.user.id;
    const canEditAllFields = (task: Task | null) => !task || task.created_by.id === auth.user.id;

    const columns = getTaskColumns({
        onEdit: (task) => setEditingTask(task),
        onDelete: (task) => router.delete(route('task.destroy', task.id), { preserveScroll: true }),
        canDelete,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tâches" />
            <div className="container mx-auto px-4 py-10">
                <div className="mb-4 flex justify-end">
                    <Button onClick={() => setCreateOpen(true)}>Créer une tâche</Button>
                </div>
                <DataTable columns={columns} data={tasks.data} />
                <PaginationLinks links={tasks.meta.links} />
            </div>

            <TaskFormDialog
                mode="create"
                open={createOpen}
                onOpenChange={setCreateOpen}
                projects={projects}
                users={users}
                lockedProjectId={projectId}
            />
            <TaskFormDialog
                mode="edit"
                task={editingTask ?? undefined}
                open={editingTask !== null}
                onOpenChange={(open) => !open && setEditingTask(null)}
                projects={projects}
                users={users}
                canEditAllFields={canEditAllFields(editingTask)}
            />
        </AppLayout>
    );
}
