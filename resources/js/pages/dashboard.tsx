import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { TaskCalendarView } from '@/components/calendar/task-calendar-view';
import { TaskGanttView } from '@/components/gantt/task-gantt-view';
import { TaskKanbanBoard } from '@/components/kanban/task-kanban-board';
import { TaskFormDialog } from '@/components/task/task-form-dialog';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData, type Task } from '@/types';
import { CalendarDays, GanttChart, LayoutGrid } from 'lucide-react';

type Option = { id: number; name: string };
type Scope = 'mine' | 'all';
type View = 'kanban' | 'calendar' | 'gantt';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
];

export default function Dashboard({
    tasks,
    projects,
    users,
    filters,
}: {
    tasks: { data: Task[] };
    projects: Option[];
    users: Option[];
    filters: { scope: Scope; project_id: number | null };
}) {
    const { auth } = usePage<SharedData>().props;
    const [createOpen, setCreateOpen] = useState(false);
    const [editingTask, setEditingTask] = useState<Task | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [scope, setScope] = useState<Scope>(filters.scope);
    const [projectId, setProjectId] = useState<string>(filters.project_id ? String(filters.project_id) : 'all');
    const [view, setView] = useState<View>('kanban');
    const [createDefaultDate, setCreateDefaultDate] = useState<string | null>(null);

    const applyFilters = (nextScope: Scope, nextProjectId: string) => {
        router.get(
            route('dashboard'),
            { scope: nextScope, project_id: nextProjectId === 'all' ? undefined : nextProjectId },
            { preserveState: true, preserveScroll: true, only: ['tasks'] },
        );
    };

    const canEditAllFields = (task: Task | null) => !task || task.created_by.id === auth.user.id;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tableau de bord" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-lg font-medium">{scope === 'mine' ? 'Mes tâches' : 'Toutes les tâches'}</h1>
                        <p className="text-sm text-muted-foreground">
                            {scope === 'mine'
                                ? 'Tâches que tu as créées ou qui te sont assignées.'
                                : 'Toutes les tâches, tous utilisateurs confondus.'}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
                            <button
                                type="button"
                                onClick={() => setView('kanban')}
                                className={cn(
                                    'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm transition-colors',
                                    view === 'kanban'
                                        ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                        : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                                )}
                            >
                                <LayoutGrid className="size-4" />
                                Kanban
                            </button>
                            <button
                                type="button"
                                onClick={() => setView('calendar')}
                                className={cn(
                                    'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm transition-colors',
                                    view === 'calendar'
                                        ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                        : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                                )}
                            >
                                <CalendarDays className="size-4" />
                                Calendrier
                            </button>
                            <button
                                type="button"
                                onClick={() => setView('gantt')}
                                className={cn(
                                    'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm transition-colors',
                                    view === 'gantt'
                                        ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                        : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                                )}
                            >
                                <GanttChart className="size-4" />
                                Frise
                            </button>
                        </div>
                        <Select
                            value={scope}
                            onValueChange={(value) => {
                                const nextScope = value as Scope;
                                setScope(nextScope);
                                applyFilters(nextScope, projectId);
                            }}
                        >
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="mine">Mes tâches</SelectItem>
                                <SelectItem value="all">Toutes les tâches</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            value={projectId}
                            onValueChange={(value) => {
                                setProjectId(value);
                                applyFilters(scope, value);
                            }}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Tous les projets" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les projets</SelectItem>
                                {projects.map((project) => (
                                    <SelectItem key={project.id} value={String(project.id)}>
                                        {project.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button
                            onClick={() => {
                                setCreateDefaultDate(null);
                                setCreateOpen(true);
                            }}
                        >
                            Créer une tâche
                        </Button>
                    </div>
                </div>

                {view === 'kanban' && (
                    <TaskKanbanBoard
                        tasks={tasks.data}
                        currentUserId={auth.user.id}
                        onEdit={(task) => {
                            setEditingTask(task);
                            setEditOpen(true);
                        }}
                        onDelete={(task) => router.delete(route('task.destroy', task.id), { preserveScroll: true, only: ['tasks'] })}
                    />
                )}
                {view === 'calendar' && (
                    <TaskCalendarView
                        tasks={tasks.data}
                        onEdit={(task) => {
                            setEditingTask(task);
                            setEditOpen(true);
                        }}
                        onCreateForDate={(dateKey) => {
                            setCreateDefaultDate(dateKey);
                            setCreateOpen(true);
                        }}
                    />
                )}
                {view === 'gantt' && (
                    <TaskGanttView
                        tasks={tasks.data}
                        onEdit={(task) => {
                            setEditingTask(task);
                            setEditOpen(true);
                        }}
                    />
                )}
            </div>

            <TaskFormDialog
                mode="create"
                open={createOpen}
                onOpenChange={setCreateOpen}
                projects={projects}
                users={users}
                lockedProjectId={filters.project_id}
                defaultEndDate={createDefaultDate ?? undefined}
            />
            <TaskFormDialog
                mode="edit"
                task={editingTask ?? undefined}
                open={editOpen}
                onOpenChange={setEditOpen}
                projects={projects}
                users={users}
                canEditAllFields={canEditAllFields(editingTask)}
            />
        </AppLayout>
    );
}
