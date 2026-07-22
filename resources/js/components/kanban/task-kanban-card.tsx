import { useDraggable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';

import { Badge } from '@/components/ui/badge';
import { priorityVariant } from '@/lib/task-presentation';
import { type Task } from '@/types';

export function TaskKanbanCard({ task }: { task: Task }) {
    const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
        id: task.id,
    });

    return (
        <div
            ref={setNodeRef}
            {...listeners}
            {...attributes}
            style={{ transform: CSS.Translate.toString(transform) }}
            className={`cursor-grab space-y-2 rounded-md border bg-card p-3 text-sm shadow-xs active:cursor-grabbing ${
                isDragging ? 'opacity-50' : ''
            }`}
        >
            <p className="font-medium">{task.name}</p>
            <div className="flex min-w-0 flex-wrap items-center gap-1.5">
                <Badge variant="outline" className="h-auto min-w-0 shrink overflow-visible py-1 break-words whitespace-normal">
                    {task.project?.name}
                </Badge>
                <Badge variant={priorityVariant(task.priority)} className="shrink-0">
                    {task.priority}
                </Badge>
            </div>
            <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>{task.assigned_user.name}</span>
                {task.end_date && <span>{task.end_date}</span>}
            </div>
        </div>
    );
}
