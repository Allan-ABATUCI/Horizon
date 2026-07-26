import { Download, LoaderCircle, Paperclip, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { readXsrfToken } from '@/lib/csrf';

type Attachment = {
    id: number;
    original_name: string;
    mime_type: string;
    size: number;
    created_at: string;
    uploaded_by: {
        id: number;
        name: string;
        email: string;
    };
};

function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} Ko`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`;
}

export function TaskAttachments({ taskId, currentUserId }: { taskId: number; currentUserId: number }) {
    const [attachments, setAttachments] = useState<Attachment[]>([]);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        setLoading(true);
        fetch(route('task.attachments.index', taskId), {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((json) => setAttachments(json.data))
            .finally(() => setLoading(false));
    }, [taskId]);

    const upload = (file: File) => {
        setError(null);
        setUploading(true);

        const formData = new FormData();
        formData.append('file', file);

        fetch(route('attachments.store', taskId), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
            body: formData,
        })
            .then(async (response) => {
                const json = await response.json();
                if (!response.ok) {
                    setError(json.message ?? "Erreur lors de l'envoi du fichier.");
                    return;
                }
                setAttachments((current) => [json.data, ...current]);
            })
            .finally(() => {
                setUploading(false);
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            });
    };

    const destroy = (attachment: Attachment) => {
        fetch(route('attachments.destroy', attachment.id), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
        }).then((response) => {
            if (response.ok) {
                setAttachments((current) => current.filter((a) => a.id !== attachment.id));
            }
        });
    };

    return (
        <div className="grid gap-3 border-t pt-4">
            <h3 className="text-sm font-medium">Pièces jointes</h3>

            <div className="flex items-center gap-2">
                <input
                    ref={fileInputRef}
                    type="file"
                    id={`attachment-input-${taskId}`}
                    className="hidden"
                    onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (file) {
                            upload(file);
                        }
                    }}
                />
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={uploading || attachments.length >= 10}
                    onClick={() => fileInputRef.current?.click()}
                >
                    {uploading ? <LoaderCircle className="size-4 animate-spin" /> : <Paperclip className="size-4" />}
                    Ajouter un fichier
                </Button>
                <p className="text-xs text-muted-foreground">PDF, Office, image, zip… jusqu'à 2 Mo, 10 max.</p>
            </div>

            {error && <p className="text-sm text-destructive">{error}</p>}

            {loading ? (
                <p className="text-sm text-muted-foreground">Chargement…</p>
            ) : attachments.length === 0 ? (
                <p className="text-sm text-muted-foreground">Aucune pièce jointe.</p>
            ) : (
                <ul className="grid gap-2">
                    {attachments.map((attachment) => (
                        <li key={attachment.id} className="group flex items-center justify-between gap-2 rounded-md border p-2">
                            <a
                                href={route('attachments.download', attachment.id)}
                                className="flex min-w-0 flex-1 items-center gap-2 text-sm hover:underline"
                            >
                                <Download className="size-4 shrink-0 text-muted-foreground" />
                                <span className="truncate">{attachment.original_name}</span>
                                <span className="shrink-0 text-xs text-muted-foreground">{formatSize(attachment.size)}</span>
                            </a>
                            {attachment.uploaded_by.id === currentUserId && (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <button
                                            type="button"
                                            onClick={() => destroy(attachment)}
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
