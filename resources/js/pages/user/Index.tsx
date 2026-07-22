import { PaginationLinks } from '@/components/pagination-links';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { UserFormDialog } from '@/components/user/user-form-dialog';
import AppLayout from '@/layouts/app-layout';
import { getUserColumns } from '@/pages/user/columns';
import { User, type BreadcrumbItem, type PaginatedResponse, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ users }: { users: PaginatedResponse<User> }) {
    const { auth } = usePage<SharedData>().props;
    const [createOpen, setCreateOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<User | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tableau de bord', href: '/dashboard' },
        { title: 'Utilisateurs', href: '/user' },
    ];

    const columns = getUserColumns({
        currentUserId: auth.user.id,
        onEdit: (user) => setEditingUser(user),
        onDelete: (user) => router.delete(route('user.destroy', user.id), { preserveScroll: true }),
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Utilisateurs" />
            <div className="container mx-auto px-4 py-10">
                <div className="mb-4 flex justify-end">
                    <Button onClick={() => setCreateOpen(true)}>Créer un utilisateur</Button>
                </div>
                <DataTable columns={columns} data={users.data} />
                <PaginationLinks links={users.meta.links} />
            </div>

            <UserFormDialog mode="create" open={createOpen} onOpenChange={setCreateOpen} />
            <UserFormDialog
                mode="edit"
                user={editingUser ?? undefined}
                open={editingUser !== null}
                onOpenChange={(open) => !open && setEditingUser(null)}
            />
        </AppLayout>
    );
}
