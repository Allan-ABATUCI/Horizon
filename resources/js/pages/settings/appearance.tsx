import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import ThemePicker from '@/components/theme-picker';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Paramètres d'apparence",
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Paramètres d'apparence" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Paramètres d'apparence" description="Mettez à jour les paramètres d'apparence de votre compte" />
                    <AppearanceTabs />

                    <HeadingSmall title="Thème de couleur" description="Choisis la palette de couleurs de l'application" />
                    <ThemePicker />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
