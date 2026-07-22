import { PaginationLinks } from '@/components/pagination-links';
import { ProjectFormDialog } from '@/components/project/project-form-dialog';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { getProjectColumns } from '@/pages/project/columns';
import { Project, type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ projects }: { projects: PaginatedResponse<Project> }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editingProject, setEditingProject] = useState<Project | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tableau de bord', href: '/dashboard' },
        { title: 'Projets', href: '/project' },
    ];

    const columns = getProjectColumns({
        onEdit: (project) => setEditingProject(project),
        onDelete: (project) => router.delete(route('project.destroy', project.id), { preserveScroll: true }),
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Projets" />
            <div className="container mx-auto px-4 py-10">
                <div className="mb-4 flex justify-end">
                    <Button onClick={() => setCreateOpen(true)}>Créer un projet</Button>
                </div>
                <DataTable columns={columns} data={projects.data} />
                <PaginationLinks links={projects.meta.links} />
            </div>

            <ProjectFormDialog mode="create" open={createOpen} onOpenChange={setCreateOpen} />
            <ProjectFormDialog
                mode="edit"
                project={editingProject ?? undefined}
                open={editingProject !== null}
                onOpenChange={(open) => !open && setEditingProject(null)}
            />
        </AppLayout>
    );
}
