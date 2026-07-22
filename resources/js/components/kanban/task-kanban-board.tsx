import { DndContext, type DragEndEvent, PointerSensor, useDroppable, useSensor, useSensors } from '@dnd-kit/core';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { TaskKanbanCard } from '@/components/kanban/task-kanban-card';
import { renderStatusIcon, TASK_STATUSES, type TaskStatus } from '@/lib/task-presentation';
import { type Task } from '@/types';

function KanbanColumn({ status, tasks }: { status: TaskStatus; tasks: Task[] }) {
    const { setNodeRef, isOver } = useDroppable({ id: status });
    const Icon = renderStatusIcon(status);

    return (
        <div ref={setNodeRef} className={`flex w-full flex-col gap-2 rounded-lg border bg-muted/30 p-3 sm:w-1/3 ${isOver ? 'bg-muted' : ''}`}>
            <div className="flex items-center gap-1.5 px-1 text-sm font-medium">
                {Icon && <Icon className="size-4" />}
                <span>{status}</span>
                <span className="ml-auto text-xs text-muted-foreground">{tasks.length}</span>
            </div>
            <div className="flex min-h-16 flex-col gap-2">
                {tasks.map((task) => (
                    <TaskKanbanCard key={task.id} task={task} />
                ))}
            </div>
        </div>
    );
}

export function TaskKanbanBoard({ tasks: initialTasks }: { tasks: Task[] }) {
    const [tasks, setTasks] = useState(initialTasks);

    useEffect(() => {
        setTasks(initialTasks);
    }, [initialTasks]);

    const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 6 } }));

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over) return;

        const taskId = active.id as number;
        const newStatus = over.id as TaskStatus;
        const task = tasks.find((t) => t.id === taskId);
        if (!task || task.status === newStatus) return;

        const previousTasks = tasks;
        setTasks((current) => current.map((t) => (t.id === taskId ? { ...t, status: newStatus } : t)));

        router.patch(
            route('task.updateStatus', taskId),
            { status: newStatus },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['tasks'],
                onError: () => setTasks(previousTasks),
            },
        );
    };

    return (
        <DndContext sensors={sensors} onDragEnd={handleDragEnd}>
            <div className="flex flex-col gap-4 sm:flex-row">
                {TASK_STATUSES.map((status) => (
                    <KanbanColumn key={status} status={status} tasks={tasks.filter((t) => t.status === status)} />
                ))}
            </div>
        </DndContext>
    );
}
