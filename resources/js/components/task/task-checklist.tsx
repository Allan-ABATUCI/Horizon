import { Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { readXsrfToken } from '@/lib/csrf';
import { cn } from '@/lib/utils';
import { type ChecklistItem } from '@/types';

export function TaskChecklist({ taskId, items: initialItems }: { taskId: number; items: ChecklistItem[] }) {
    const [items, setItems] = useState(initialItems);
    const [label, setLabel] = useState('');
    const [adding, setAdding] = useState(false);

    useEffect(() => {
        setItems(initialItems);
    }, [initialItems]);

    const done = items.filter((item) => item.is_done).length;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!label.trim()) {
            return;
        }

        setAdding(true);
        fetch(route('checklistItems.store', taskId), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
            body: JSON.stringify({ label }),
        })
            .then((response) => response.json())
            .then((json) => {
                setItems((current) => [...current, json.data]);
                setLabel('');
            })
            .finally(() => setAdding(false));
    };

    const toggle = (item: ChecklistItem) => {
        setItems((current) => current.map((i) => (i.id === item.id ? { ...i, is_done: !i.is_done } : i)));

        fetch(route('checklistItems.update', item.id), {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
            body: JSON.stringify({ is_done: !item.is_done }),
        }).then((response) => {
            if (!response.ok) {
                setItems((current) => current.map((i) => (i.id === item.id ? { ...i, is_done: item.is_done } : i)));
            }
        });
    };

    const destroy = (item: ChecklistItem) => {
        fetch(route('checklistItems.destroy', item.id), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
        }).then((response) => {
            if (response.ok) {
                setItems((current) => current.filter((i) => i.id !== item.id));
            }
        });
    };

    return (
        <div className="grid gap-3 border-t pt-4">
            <div className="flex items-center justify-between">
                <h3 className="text-sm font-medium">Checklist</h3>
                {items.length > 0 && (
                    <span className="text-xs text-muted-foreground">
                        {done}/{items.length}
                    </span>
                )}
            </div>

            {items.length > 0 && (
                <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                    <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${(done / items.length) * 100}%` }} />
                </div>
            )}

            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">Aucun élément pour l'instant.</p>
            ) : (
                <ul className="grid gap-1.5">
                    {items.map((item) => (
                        <li key={item.id} className="group flex items-center gap-2 rounded-md border p-2 text-sm">
                            <Checkbox checked={item.is_done} onCheckedChange={() => toggle(item)} />
                            <span className={cn('flex-1', item.is_done && 'text-muted-foreground line-through')}>{item.label}</span>
                            <button
                                type="button"
                                onClick={() => destroy(item)}
                                className="rounded p-1 text-muted-foreground opacity-0 group-hover:opacity-100 hover:bg-destructive/10 hover:text-destructive"
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            <form className="flex gap-2" onSubmit={submit}>
                <Input value={label} onChange={(e) => setLabel(e.target.value)} placeholder="Ajouter un élément…" disabled={adding} />
                <Button type="submit" size="icon" disabled={adding || !label.trim()}>
                    <Plus className="size-4" />
                </Button>
            </form>
        </div>
    );
}
