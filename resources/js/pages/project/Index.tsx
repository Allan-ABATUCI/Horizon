import { PaginationLinks } from '@/components/pagination-links';
import { ProjectFormDialog } from '@/components/project/project-form-dialog';
import { ProjectMembersDialog } from '@/components/project/project-members-dialog';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { getProjectColumns } from '@/pages/project/columns';
import { Project, type BreadcrumbItem, type PaginatedResponse, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Option = { id: number; name: string };

export default function Index({ projects, users }: { projects: PaginatedResponse<Project>; users: Option[] }) {
    const { auth } = usePage<SharedData>().props;
    const [createOpen, setCreateOpen] = useState(false);
    const [editingProject, setEditingProject] = useState<Project | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [membersProjectId, setMembersProjectId] = useState<number | null>(null);
    const [membersOpen, setMembersOpen] = useState(false);
    const membersProject = projects.data.find((project) => project.id === membersProjectId);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tableau de bord', href: '/dashboard' },
        { title: 'Projets', href: '/project' },
    ];

    const columns = getProjectColumns({
        onEdit: (project) => {
            setEditingProject(project);
            setEditOpen(true);
        },
        onDelete: (project) => router.delete(route('project.destroy', project.id), { preserveScroll: true }),
        onManageMembers: (project) => {
            setMembersProjectId(project.id);
            setMembersOpen(true);
        },
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
            <ProjectFormDialog mode="edit" project={editingProject ?? undefined} open={editOpen} onOpenChange={setEditOpen} />
            <ProjectMembersDialog
                project={membersProject}
                allUsers={users}
                currentUserId={auth.user.id}
                open={membersOpen}
                onOpenChange={setMembersOpen}
            />
        </AppLayout>
    );
}
