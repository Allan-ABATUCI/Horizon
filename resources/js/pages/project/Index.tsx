import AppLayout from '@/layouts/app-layout';
import { Project, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { columns } from '@/pages/project/columns';
import { DataTable } from '@/components/ui/data-table';

export default function Index({projects}){
   
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
        title: 'Projects',
        href: '/project',
    },
        
    ];
    return(
        <>
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Projects" />
            <div className="container mx-auto py-10">
                <DataTable columns={columns} data={projects.data} />
            </div>
            
        </AppLayout>
        </>
    )
}