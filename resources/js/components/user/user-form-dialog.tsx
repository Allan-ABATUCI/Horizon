import { useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type UserSummary } from '@/types';

type UserFormData = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export function UserFormDialog({
    mode,
    user,
    open,
    onOpenChange,
}: {
    mode: 'create' | 'edit';
    user?: UserSummary;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm<UserFormData>({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        if (open) {
            setData({
                name: user?.name ?? '',
                email: user?.email ?? '',
                password: '',
                password_confirmation: '',
            });
            clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, user]);

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
            post(route('user.store'), options);
        } else if (user) {
            put(route('user.update', user.id), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{mode === 'create' ? 'Créer un utilisateur' : "Modifier l'utilisateur"}</DialogTitle>
                </DialogHeader>

                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nom</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">{mode === 'create' ? 'Mot de passe' : 'Nouveau mot de passe (optionnel)'}</Label>
                        <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirmation</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                        <InputError message={errors.password_confirmation} />
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
