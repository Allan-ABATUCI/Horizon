import { LoaderCircle, Trash2 } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

type Comment = {
    id: number;
    body: string;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
};

function readXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export function TaskComments({ taskId, currentUserId }: { taskId: number; currentUserId: number }) {
    const [comments, setComments] = useState<Comment[]>([]);
    const [loading, setLoading] = useState(true);
    const [body, setBody] = useState('');
    const [posting, setPosting] = useState(false);

    useEffect(() => {
        setLoading(true);
        fetch(route('task.comments.index', taskId), {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((json) => setComments(json.data))
            .finally(() => setLoading(false));
    }, [taskId]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!body.trim()) {
            return;
        }

        setPosting(true);
        fetch(route('comments.store', taskId), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
            body: JSON.stringify({ body }),
        })
            .then((response) => response.json())
            .then((json) => {
                setComments((current) => [json.data, ...current]);
                setBody('');
            })
            .finally(() => setPosting(false));
    };

    const destroy = (comment: Comment) => {
        fetch(route('comments.destroy', comment.id), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
        }).then((response) => {
            if (response.ok) {
                setComments((current) => current.filter((c) => c.id !== comment.id));
            }
        });
    };

    return (
        <div className="grid gap-3 border-t pt-4">
            <h3 className="text-sm font-medium">Commentaires</h3>

            <form className="flex gap-2" onSubmit={submit}>
                <Textarea
                    value={body}
                    onChange={(e) => setBody(e.target.value)}
                    placeholder="Ajouter un commentaire…"
                    className="min-h-16"
                />
                <Button type="submit" size="sm" disabled={posting || !body.trim()} className="self-end">
                    {posting ? <LoaderCircle className="size-4 animate-spin" /> : 'Envoyer'}
                </Button>
            </form>

            {loading ? (
                <p className="text-sm text-muted-foreground">Chargement…</p>
            ) : comments.length === 0 ? (
                <p className="text-sm text-muted-foreground">Aucun commentaire pour l'instant.</p>
            ) : (
                <ul className="grid gap-3">
                    {comments.map((comment) => (
                        <li key={comment.id} className="group flex items-start justify-between gap-2 rounded-md border p-2">
                            <div>
                                <p className="text-sm font-medium">
                                    {comment.user.name} <span className="font-normal text-muted-foreground">· {comment.created_at}</span>
                                </p>
                                <p className="text-sm whitespace-pre-wrap">{comment.body}</p>
                            </div>
                            {comment.user.id === currentUserId && (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <button
                                            type="button"
                                            onClick={() => destroy(comment)}
                                            className="rounded p-1 text-muted-foreground opacity-0 group-hover:opacity-100 hover:bg-destructive/10 hover:text-destructive"
                                        >
                                            <Trash2 className="size-3.5" />
                                        </button>
                                    </TooltipTrigger>
                                    <TooltipContent>Supprimer</TooltipContent>
                                </Tooltip>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
