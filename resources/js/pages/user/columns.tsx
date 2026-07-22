'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type User } from '@/types';
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

export function getUserColumns({
    currentUserId,
    onEdit,
    onDelete,
}: {
    currentUserId: number;
    onEdit: (user: User) => void;
    onDelete: (user: User) => void;
}): ColumnDef<User>[] {
    return [
        {
            accessorKey: 'name',
            header: () => <div className="max-w-xs text-accent-foreground">Nom</div>,
        },
        {
            accessorKey: 'email',
            header: () => <div className="max-w-xs text-accent-foreground">Email</div>,
        },
        {
            accessorKey: 'email_verified_at',
            header: () => <div className="max-w-xs text-accent-foreground">Vérifié</div>,
            cell: ({ row }) => (
                <Badge variant={row.getValue('email_verified_at') ? 'default' : 'secondary'}>
                    {row.getValue('email_verified_at') ? 'vérifié' : 'non vérifié'}
                </Badge>
            ),
        },
        {
            id: 'actions',
            cell: ({ row }) => {
                const user = row.original;
                if (user.id !== currentUserId) {
                    return null;
                }

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
                            <DropdownMenuItem onClick={() => onEdit(user)}>Modifier</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                onClick={() => {
                                    if (confirm(`Supprimer le compte "${user.name}" ?`)) {
                                        onDelete(user);
                                    }
                                }}
                            >
                                Supprimer
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            },
        },
    ];
}
