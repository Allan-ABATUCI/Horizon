'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { priorityVariant, renderStatusIcon } from '@/lib/task-presentation';
import { type Task } from '@/types';
import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export function getTaskColumns({
    onEdit,
    onDelete,
    canDelete,
}: {
    onEdit: (task: Task) => void;
    onDelete: (task: Task) => void;
    canDelete: (task: Task) => boolean;
}): ColumnDef<Task>[] {
    return [
        {
            accessorKey: 'name',
            header: () => <div className="max-w-xs text-accent-foreground">Nom</div>,
        },
        {
            id: 'project',
            header: () => <div className="max-w-xs text-accent-foreground">Projet</div>,
            cell: ({ row }) => <div>{row.original.project?.name}</div>,
        },
        {
            id: 'assigned_user',
            header: () => <div className="max-w-xs text-accent-foreground">Assigné à</div>,
            cell: ({ row }) => <div>{row.original.assigned_user.name}</div>,
        },
        {
            accessorKey: 'status',
            header: () => <div className="max-w-xs text-accent-foreground">Statut</div>,
            cell: ({ row }) => {
                const status = row.getValue('status') as string;
                const Icon = renderStatusIcon(status);
                return (
                    <div className="flex whitespace-nowrap">
                        {Icon && <Icon />}
                        <p className="pl-1.5">{status}</p>
                    </div>
                );
            },
        },
        {
            accessorKey: 'priority',
            header: () => <div className="max-w-xs text-accent-foreground">Priorité</div>,
            cell: ({ row }) => {
                const priority = row.getValue('priority') as string;
                return <Badge variant={priorityVariant(priority)}>{priority}</Badge>;
            },
        },
        {
            accessorKey: 'end_date',
            header: () => <div className="max-w-xs text-accent-foreground">Date de fin</div>,
            cell: ({ row }) => <div className="font-medium">{row.getValue('end_date') as string}</div>,
        },
        {
            id: 'actions',
            cell: ({ row }) => {
                const task = row.original;

                return (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Ouvrir le menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                            <DropdownMenuItem onClick={() => onEdit(task)}>Modifier</DropdownMenuItem>
                            {canDelete(task) && (
                                <>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        variant="destructive"
                                        onClick={() => {
                                            if (confirm(`Supprimer la tâche "${task.name}" ?`)) {
                                                onDelete(task);
                                            }
                                        }}
                                    >
                                        Supprimer
                                    </DropdownMenuItem>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            },
        },
    ];
}
