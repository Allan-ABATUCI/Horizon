import { useDraggable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { formatTaskDate, PRIORITY_DOT_COLORS } from '@/lib/task-presentation';
import { type Task } from '@/types';

export function TaskKanbanCard({
    task,
    canDelete,
    onEdit,
    onDelete,
}: {
    task: Task;
    canDelete: boolean;
    onEdit: (task: Task) => void;
    onDelete: (task: Task) => void;
}) {
    const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
        id: task.id,
    });

    return (
        <div
            ref={setNodeRef}
            {...listeners}
            {...attributes}
            onClick={() => onEdit(task)}
            style={{ transform: CSS.Translate.toString(transform) }}
            className={`group relative cursor-grab space-y-2 rounded-md border bg-card p-3 text-sm shadow-xs active:cursor-grabbing ${
                isDragging ? 'opacity-50' : ''
            }`}
        >
            {canDelete && (
                <button
                    type="button"
                    onPointerDown={(e) => e.stopPropagation()}
                    onClick={(e) => {
                        e.stopPropagation();
                        if (confirm(`Supprimer la tâche "${task.name}" ?`)) {
                            onDelete(task);
                        }
                    }}
                    className="absolute top-2 right-2 rounded p-1 text-muted-foreground opacity-0 group-hover:opacity-100 hover:bg-destructive/10 hover:text-destructive"
                >
                    <Trash2 className="size-3.5" />
                    <span className="sr-only">Supprimer</span>
                </button>
            )}

            <p className="pr-6 font-medium">{task.name}</p>
            <div className="flex min-w-0 flex-wrap items-center gap-1.5">
                <Badge variant="outline" className="h-auto min-w-0 shrink overflow-visible py-1 break-words whitespace-normal">
                    {task.project?.name}
                </Badge>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <span
                            className="size-2.5 shrink-0 rounded-full border border-black/10"
                            style={{ backgroundColor: PRIORITY_DOT_COLORS[task.priority] }}
                        />
                    </TooltipTrigger>
                    <TooltipContent>Priorité : {task.priority}</TooltipContent>
                </Tooltip>
            </div>
            <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>{task.assigned_user.name}</span>
                {task.end_date && <span>{formatTaskDate(task.end_date)}</span>}
            </div>
        </div>
    );
}
