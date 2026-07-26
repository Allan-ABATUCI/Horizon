import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type Project } from '@/types';

type Option = { id: number; name: string };

export function ProjectMembersDialog({
    project,
    allUsers,
    currentUserId,
    open,
    onOpenChange,
}: {
    project?: Project;
    allUsers: Option[];
    currentUserId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [selectedUserId, setSelectedUserId] = useState('');

    if (!project) {
        return null;
    }

    const isOwner = currentUserId === project.created_by.id;
    const memberIds = new Set(project.members.map((member) => member.id));
    const invitableUsers = allUsers.filter((user) => !memberIds.has(user.id));

    const addMember = () => {
        if (!selectedUserId) {
            return;
        }

        router.post(
            route('project.members.store', project.id),
            { user_id: selectedUserId },
            { preserveScroll: true, only: ['projects'], onSuccess: () => setSelectedUserId('') },
        );
    };

    const removeMember = (userId: number) => {
        router.delete(route('project.members.destroy', [project.id, userId]), { preserveScroll: true, only: ['projects'] });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Membres du projet « {project.name} »</DialogTitle>
                </DialogHeader>

                <div className="grid gap-3">
                    {isOwner && (
                        <div className="flex gap-2">
                            <Select value={selectedUserId} onValueChange={setSelectedUserId}>
                                <SelectTrigger className="flex-1">
                                    <SelectValue placeholder="Choisir un utilisateur à inviter" />
                                </SelectTrigger>
                                <SelectContent>
                                    {invitableUsers.map((user) => (
                                        <SelectItem key={user.id} value={String(user.id)}>
                                            {user.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button type="button" onClick={addMember} disabled={!selectedUserId}>
                                Ajouter
                            </Button>
                        </div>
                    )}

                    <ul className="grid gap-2">
                        {project.members.map((member) => (
                            <li key={member.id} className="flex items-center justify-between rounded-md border p-2 text-sm">
                                <span>
                                    {member.name} {member.id === project.created_by.id && <span className="text-muted-foreground">(créateur)</span>}
                                </span>
                                {isOwner && member.id !== project.created_by.id && (
                                    <button
                                        type="button"
                                        onClick={() => removeMember(member.id)}
                                        className="rounded p-1 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    >
                                        <Trash2 className="size-3.5" />
                                    </button>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            </DialogContent>
        </Dialog>
    );
}
