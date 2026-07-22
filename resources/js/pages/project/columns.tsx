'use client';

import { Button } from '@/components/ui/button';
import { type Project } from '@/types';
import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { CircleCheckBig, Clock, Hourglass, MoreHorizontal } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
/**  @return composant de l'icone pour l'affichage du status */
function renderSwitch(status: string) {
    switch (status) {
        case 'en attente':
            return <Hourglass />;
        case 'en cours':
            return <Clock />;
        case 'terminé':
            return <CircleCheckBig />;
    }
}

function imageSrc(imagePath: string | null) {
    if (!imagePath) return undefined;
    return imagePath.startsWith('http') ? imagePath : `/storage/${imagePath}`;
}

export function getProjectColumns({
    onEdit,
    onDelete,
}: {
    onEdit: (project: Project) => void;
    onDelete: (project: Project) => void;
}): ColumnDef<Project>[] {
    return [
        {
            accessorKey: 'image_path',
            header: () => <div className="max-w-xs text-accent-foreground"></div>,
            cell: ({ row }) => {
                const image = row.getValue('image_path') as string | null;
                return image ? <img src={imageSrc(image)} alt="" className="h-10 w-10 rounded object-cover" /> : null;
            },
        },
        {
            accessorKey: 'name',
            header: () => <div className="max-w-xs text-accent-foreground">Nom</div>,
        },
        {
            accessorKey: 'description',
            header: () => <div className="max-w-xs text-accent-foreground">Description</div>,
            cell: ({ row }) => {
                const description = row.getValue('description') as string;
                return <div className="max-w-xl">{description}</div>;
            },
        },
        {
            accessorKey: 'status',
            header: () => <div className="max-w-xs text-accent-foreground">Statut</div>,
            cell: ({ row }) => {
                const status = row.getValue('status') as string;
                return (
                    <>
                        <div className="flex whitespace-nowrap">
                            {renderSwitch(status)}
                            <p className="pl-1.5">{status}</p>
                        </div>
                    </>
                );
            },
        },
        {
            accessorKey: 'end_date',
            header: () => <div className="max-w-xs text-accent-foreground">Date de fin</div>,
            cell: ({ row }) => {
                const end_date = row.getValue('end_date') as string;

                return <div className="font-medium">{end_date}</div>;
            },
        },
        {
            id: 'actions',
            cell: ({ row }) => {
                const project = row.original;

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
                            <DropdownMenuItem onClick={() => navigator.clipboard.writeText(String(project.id))}>
                                copier l'ID du projet
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => router.visit(route('dashboard', { project_id: project.id, scope: 'all' }))}>
                                Voir tâches
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => onEdit(project)}>Modifier Projet</DropdownMenuItem>
                            <DropdownMenuItem
                                variant="destructive"
                                onClick={() => {
                                    if (confirm(`Supprimer le projet "${project.name}" ?`)) {
                                        onDelete(project);
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
