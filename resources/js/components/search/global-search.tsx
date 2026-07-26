import { router } from '@inertiajs/react';
import { FolderKanban, ListTodo, Search } from 'lucide-react';
import { Fragment, useEffect, useMemo, useState, type KeyboardEvent as ReactKeyboardEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';

type ProjectResult = { id: number; name: string };
type TaskResult = { id: number; name: string; project_id: number };
type ResultEntry = { kind: 'project'; item: ProjectResult } | { kind: 'task'; item: TaskResult };

function highlightMatch(text: string, query: string) {
    if (!query.trim()) {
        return text;
    }

    const index = text.toLowerCase().indexOf(query.trim().toLowerCase());
    if (index === -1) {
        return text;
    }

    return (
        <Fragment>
            {text.slice(0, index)}
            <mark className="rounded-sm bg-primary/15 text-primary">{text.slice(index, index + query.trim().length)}</mark>
            {text.slice(index + query.trim().length)}
        </Fragment>
    );
}

export function GlobalSearch() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState<{ projects: ProjectResult[]; tasks: TaskResult[] }>({ projects: [], tasks: [] });
    const [activeIndex, setActiveIndex] = useState(0);

    const entries = useMemo<ResultEntry[]>(
        () => [
            ...results.projects.map((item): ResultEntry => ({ kind: 'project', item })),
            ...results.tasks.map((item): ResultEntry => ({ kind: 'task', item })),
        ],
        [results],
    );

    useEffect(() => {
        const onKeyDown = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setOpen(true);
            }
        };

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, []);

    useEffect(() => {
        if (query.trim().length < 2) {
            setResults({ projects: [], tasks: [] });
            return;
        }

        setLoading(true);
        const timeout = setTimeout(() => {
            fetch(route('search', { q: query }), { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((json) => {
                    setResults(json);
                    setActiveIndex(0);
                })
                .finally(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timeout);
    }, [query]);

    const goTo = (entry: ResultEntry) => {
        setOpen(false);
        if (entry.kind === 'project') {
            router.visit(route('dashboard', { project_id: entry.item.id, scope: 'all' }));
        } else {
            router.visit(route('dashboard', { task: entry.item.id }));
        }
    };

    const onInputKeyDown = (e: ReactKeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveIndex((i) => Math.min(i + 1, entries.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveIndex((i) => Math.max(i - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const entry = entries[activeIndex];
            if (entry) {
                goTo(entry);
            }
        }
    };

    return (
        <>
            <Button variant="ghost" size="icon" className="group h-9 w-9 cursor-pointer" onClick={() => setOpen(true)}>
                <Search className="!size-5 opacity-80 group-hover:opacity-100" />
            </Button>

            <Dialog
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);
                    if (!next) {
                        setQuery('');
                    }
                }}
            >
                <DialogContent className="gap-0 overflow-hidden p-0">
                    <DialogTitle className="sr-only">Recherche</DialogTitle>
                    <div className="flex items-center gap-2 border-b px-4 py-3">
                        <Search className="size-4 shrink-0 text-muted-foreground" />
                        <input
                            autoFocus
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            onKeyDown={onInputKeyDown}
                            placeholder="Rechercher un projet ou une tâche…"
                            className="w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                        />
                    </div>

                    <div className="max-h-80 overflow-y-auto p-1.5">
                        {query.trim().length < 2 ? (
                            <p className="p-3 text-sm text-muted-foreground">Tape au moins 2 caractères.</p>
                        ) : loading ? (
                            <p className="p-3 text-sm text-muted-foreground">Recherche…</p>
                        ) : entries.length === 0 ? (
                            <p className="p-3 text-sm text-muted-foreground">Aucun résultat.</p>
                        ) : (
                            <>
                                {results.projects.length > 0 && (
                                    <div className="mb-1">
                                        <p className="px-2 py-1 text-xs font-medium text-muted-foreground">Projets</p>
                                        {results.projects.map((project) => {
                                            const index = entries.findIndex((e) => e.kind === 'project' && e.item.id === project.id);
                                            return (
                                                <button
                                                    key={project.id}
                                                    type="button"
                                                    onMouseEnter={() => setActiveIndex(index)}
                                                    onClick={() => goTo({ kind: 'project', item: project })}
                                                    className={`flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm ${
                                                        activeIndex === index ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'
                                                    }`}
                                                >
                                                    <FolderKanban className="size-4 shrink-0 opacity-70" />
                                                    <span className="truncate">{highlightMatch(project.name, query)}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                                {results.tasks.length > 0 && (
                                    <div>
                                        <p className="px-2 py-1 text-xs font-medium text-muted-foreground">Tâches</p>
                                        {results.tasks.map((task) => {
                                            const index = entries.findIndex((e) => e.kind === 'task' && e.item.id === task.id);
                                            return (
                                                <button
                                                    key={task.id}
                                                    type="button"
                                                    onMouseEnter={() => setActiveIndex(index)}
                                                    onClick={() => goTo({ kind: 'task', item: task })}
                                                    className={`flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm ${
                                                        activeIndex === index ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'
                                                    }`}
                                                >
                                                    <ListTodo className="size-4 shrink-0 opacity-70" />
                                                    <span className="truncate">{highlightMatch(task.name, query)}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                            </>
                        )}
                    </div>

                    <div className="flex items-center gap-3 border-t px-4 py-2 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1">
                            <kbd className="rounded border bg-muted px-1.5 py-0.5 font-mono text-[10px]">↑</kbd>
                            <kbd className="rounded border bg-muted px-1.5 py-0.5 font-mono text-[10px]">↓</kbd> naviguer
                        </span>
                        <span className="flex items-center gap-1">
                            <kbd className="rounded border bg-muted px-1.5 py-0.5 font-mono text-[10px]">↵</kbd> ouvrir
                        </span>
                        <span className="flex items-center gap-1">
                            <kbd className="rounded border bg-muted px-1.5 py-0.5 font-mono text-[10px]">Échap</kbd> fermer
                        </span>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
