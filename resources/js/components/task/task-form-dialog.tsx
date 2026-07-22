import { useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { TASK_PRIORITIES, TASK_STATUSES, type TaskPriority, type TaskStatus } from '@/lib/task-presentation';
import { type Task } from '@/types';

type TaskFormData = {
    name: string;
    description: string;
    status: TaskStatus;
    priority: TaskPriority;
    start_date: string;
    end_date: string;
    assigned_user_id: string;
    project_id: string;
    image: File | null;
};

type Option = { id: number; name: string };

export function TaskFormDialog({
    mode,
    task,
    open,
    onOpenChange,
    projects,
    users,
    lockedProjectId,
    defaultEndDate,
    canEditAllFields = true,
}: {
    mode: 'create' | 'edit';
    task?: Task;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projects: Option[];
    users: Option[];
    lockedProjectId?: number | null;
    defaultEndDate?: string;
    canEditAllFields?: boolean;
}) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm<TaskFormData>({
        name: task?.name ?? '',
        description: task?.description ?? '',
        status: task?.status ?? 'en attente',
        priority: task?.priority ?? 'moyenne',
        start_date: task?.start_date ?? '',
        end_date: task?.end_date ?? defaultEndDate ?? '',
        assigned_user_id: task?.assigned_user.id ? String(task.assigned_user.id) : '',
        project_id: task?.project_id ? String(task.project_id) : lockedProjectId ? String(lockedProjectId) : '',
        image: null,
    });

    // Ne réinitialise le formulaire que lorsqu'on édite une tâche différente,
    // ou qu'une nouvelle date par défaut est fournie (clic sur une case du
    // calendrier) — pas à chaque simple ouverture/fermeture, pour qu'un clic
    // à l'extérieur ou Échap ne fasse pas perdre ce qui a été saisi.
    useEffect(() => {
        setData({
            name: task?.name ?? '',
            description: task?.description ?? '',
            status: task?.status ?? 'en attente',
            priority: task?.priority ?? 'moyenne',
            start_date: task?.start_date ?? '',
            end_date: task?.end_date ?? defaultEndDate ?? '',
            assigned_user_id: task?.assigned_user.id ? String(task.assigned_user.id) : '',
            project_id: task?.project_id ? String(task.project_id) : lockedProjectId ? String(lockedProjectId) : '',
            image: null,
        });
        clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [task?.id, defaultEndDate]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                if (mode === 'create') {
                    reset();
                }
                onOpenChange(false);
            },
        };

        if (mode === 'create') {
            post(route('task.store'), options);
        } else if (task) {
            put(route('task.update', task.id), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{mode === 'create' ? 'Créer une tâche' : 'Modifier la tâche'}</DialogTitle>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    {!canEditAllFields && (
                        <p className="text-sm text-muted-foreground">
                            Tu es assigné à cette tâche mais tu n'en es pas le créateur : tu peux voir le détail ci-dessous et changer le statut, le
                            reste n'est pas modifiable.
                        </p>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="name">Nom</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} disabled={!canEditAllFields} />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            disabled={!canEditAllFields}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="project_id">Projet</Label>
                        <Select value={data.project_id} onValueChange={(value) => setData('project_id', value)} disabled={!canEditAllFields}>
                            <SelectTrigger id="project_id">
                                <SelectValue placeholder="Choisir un projet" />
                            </SelectTrigger>
                            <SelectContent>
                                {projects.map((project) => (
                                    <SelectItem key={project.id} value={String(project.id)}>
                                        {project.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.project_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="assigned_user_id">Assigné à</Label>
                        <Select
                            value={data.assigned_user_id}
                            onValueChange={(value) => setData('assigned_user_id', value)}
                            disabled={!canEditAllFields}
                        >
                            <SelectTrigger id="assigned_user_id">
                                <SelectValue placeholder="Choisir un utilisateur" />
                            </SelectTrigger>
                            <SelectContent>
                                {users.map((user) => (
                                    <SelectItem key={user.id} value={String(user.id)}>
                                        {user.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.assigned_user_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="priority">Priorité</Label>
                        <Select
                            value={data.priority}
                            onValueChange={(value) => setData('priority', value as TaskFormData['priority'])}
                            disabled={!canEditAllFields}
                        >
                            <SelectTrigger id="priority">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {TASK_PRIORITIES.map((priority) => (
                                    <SelectItem key={priority} value={priority}>
                                        {priority}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.priority} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="start_date">Date de début</Label>
                        <Input
                            id="start_date"
                            type="date"
                            value={data.start_date}
                            onChange={(e) => setData('start_date', e.target.value)}
                            disabled={!canEditAllFields}
                        />
                        <InputError message={errors.start_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="end_date">Date de fin</Label>
                        <Input
                            id="end_date"
                            type="date"
                            value={data.end_date}
                            onChange={(e) => setData('end_date', e.target.value)}
                            disabled={!canEditAllFields}
                        />
                        <InputError message={errors.end_date} />
                    </div>

                    {canEditAllFields && (
                        <div className="grid gap-2">
                            <Label htmlFor="image">Image</Label>
                            <Input id="image" type="file" accept="image/*" onChange={(e) => setData('image', e.target.files?.[0] ?? null)} />
                            <InputError message={errors.image} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="status">Statut</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value as TaskFormData['status'])}>
                            <SelectTrigger id="status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {TASK_STATUSES.map((status) => (
                                    <SelectItem key={status} value={status}>
                                        {status}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
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
