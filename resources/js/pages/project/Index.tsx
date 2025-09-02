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
            <pre>{JSON.stringify(projects.data)}</pre>
            <p>{typeof(projects)}</p>
        </AppLayout>
        </>
    )
}