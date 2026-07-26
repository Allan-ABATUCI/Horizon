import { LoaderCircle, Plus, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Input } from '@/components/ui/input';
import { readXsrfToken } from '@/lib/csrf';
import { renderStatusIcon } from '@/lib/task-presentation';
import { type TaskDependencySummary } from '@/types';

function StatusIcon({ status }: { status: TaskDependencySummary['status'] }) {
    const Icon = renderStatusIcon(status);
    return Icon ? <Icon className="size-3.5 shrink-0 text-muted-foreground" /> : null;
}

export function TaskDependencies({
    taskId,
    dependsOn: initialDependsOn,
    blocks,
    canEdit,
}: {
    taskId: number;
    dependsOn: TaskDependencySummary[];
    blocks: TaskDependencySummary[];
    canEdit: boolean;
}) {
    const [dependsOn, setDependsOn] = useState(initialDependsOn);
    const [query, setQuery] = useState('');
    const [candidates, setCandidates] = useState<TaskDependencySummary[]>([]);
    const [open, setOpen] = useState(false);
    const [adding, setAdding] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setDependsOn(initialDependsOn);
    }, [initialDependsOn]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const timeout = setTimeout(() => {
            fetch(route('task.dependencies.candidates', { task: taskId, q: query }), {
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then(setCandidates);
        }, 300);

        return () => clearTimeout(timeout);
    }, [taskId, query, open]);

    useEffect(() => {
        const onClickOutside = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onClickOutside);
        return () => document.removeEventListener('mousedown', onClickOutside);
    }, []);

    const addDependency = (candidate: TaskDependencySummary) => {
        setAdding(true);
        fetch(route('task.dependencies.store', taskId), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
            body: JSON.stringify({ depends_on_id: candidate.id }),
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((added) => {
                if (added) {
                    setDependsOn((current) => [...current, added]);
                    setQuery('');
                    setOpen(false);
                }
            })
            .finally(() => setAdding(false));
    };

    const removeDependency = (dependency: TaskDependencySummary) => {
        fetch(route('task.dependencies.destroy', [taskId, dependency.id]), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
        }).then((response) => {
            if (response.ok) {
                setDependsOn((current) => current.filter((d) => d.id !== dependency.id));
            }
        });
    };

    return (
        <div className="grid gap-3 border-t pt-4">
            <h3 className="text-sm font-medium">Dépendances</h3>

            {dependsOn.length === 0 ? (
                <p className="text-sm text-muted-foreground">Cette tâche ne dépend d'aucune autre.</p>
            ) : (
                <ul className="grid gap-1.5">
                    {dependsOn.map((dependency) => (
                        <li key={dependency.id} className="group flex items-center justify-between gap-2 rounded-md border p-2 text-sm">
                            <span className="flex items-center gap-1.5">
                                <StatusIcon status={dependency.status} />
                                {dependency.name}
                            </span>
                            {canEdit && (
                                <button
                                    type="button"
                                    onClick={() => removeDependency(dependency)}
                                    className="rounded p-1 text-muted-foreground opacity-0 group-hover:opacity-100 hover:bg-destructive/10 hover:text-destructive"
                                >
                                    <X className="size-3.5" />
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {canEdit && (
                <div className="relative" ref={containerRef}>
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        onFocus={() => setOpen(true)}
                        placeholder="Ajouter une dépendance…"
                        disabled={adding}
                    />
                    {open && (
                        <ul className="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-md border bg-popover shadow-md">
                            {candidates.length === 0 ? (
                                <li className="p-2 text-sm text-muted-foreground">Aucune tâche à proposer.</li>
                            ) : (
                                candidates.map((candidate) => (
                                    <li key={candidate.id}>
                                        <button
                                            type="button"
                                            onClick={() => addDependency(candidate)}
                                            className="flex w-full items-center gap-1.5 p-2 text-left text-sm hover:bg-accent"
                                        >
                                            {adding ? (
                                                <LoaderCircle className="size-3.5 shrink-0 animate-spin" />
                                            ) : (
                                                <Plus className="size-3.5 shrink-0" />
                                            )}
                                            <StatusIcon status={candidate.status} />
                                            {candidate.name}
                                        </button>
                                    </li>
                                ))
                            )}
                        </ul>
                    )}
                </div>
            )}

            {blocks.length > 0 && <p className="text-xs text-muted-foreground">Bloque : {blocks.map((task) => task.name).join(', ')}</p>}
        </div>
    );
}
