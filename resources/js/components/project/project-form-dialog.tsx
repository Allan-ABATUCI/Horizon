import { useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type Project } from '@/types';

const STATUSES = ['en attente', 'en cours', 'terminé'] as const;

type ProjectFormData = {
    name: string;
    description: string;
    end_date: string;
    status: (typeof STATUSES)[number];
    image: File | null;
};

export function ProjectFormDialog({
    mode,
    project,
    open,
    onOpenChange,
}: {
    mode: 'create' | 'edit';
    project?: Project;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm<ProjectFormData>({
        name: project?.name ?? '',
        description: project?.description ?? '',
        end_date: project?.end_date ?? '',
        status: project?.status ?? 'en attente',
        image: null,
    });

    useEffect(() => {
        if (open) {
            setData({
                name: project?.name ?? '',
                description: project?.description ?? '',
                end_date: project?.end_date ?? '',
                status: project?.status ?? 'en attente',
                image: null,
            });
            clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, project]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (mode === 'create') {
            post(route('project.store'), options);
        } else if (project) {
            put(route('project.update', project.id), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{mode === 'create' ? 'Créer un projet' : 'Modifier le projet'}</DialogTitle>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nom</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="status">Statut</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value as ProjectFormData['status'])}>
                            <SelectTrigger id="status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {STATUSES.map((status) => (
                                    <SelectItem key={status} value={status}>
                                        {status}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="end_date">Date de fin</Label>
                        <Input id="end_date" type="date" value={data.end_date} onChange={(e) => setData('end_date', e.target.value)} />
                        <InputError message={errors.end_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="image">Image</Label>
                        <Input id="image" type="file" accept="image/*" onChange={(e) => setData('image', e.target.files?.[0] ?? null)} />
                        <InputError message={errors.image} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {mode === 'create' ? 'Créer' : 'Enregistrer'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
