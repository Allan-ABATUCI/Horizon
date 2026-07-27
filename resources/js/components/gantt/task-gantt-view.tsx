import { ChevronLeft, ChevronRight, Flag, Folder, Link2 } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { STATUS_CHART_COLORS, initial } from '@/lib/task-presentation';
import { cn } from '@/lib/utils';
import { type Task } from '@/types';

const DAY_OF_WEEK_LETTERS = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];

function parseDateKey(dateStr: string): Date {
    const [year, month, day] = dateStr.split('-').map(Number);
    return new Date(year, month - 1, day);
}

type Row = { task: Task; clippedStartDay: number; clippedEndDay: number };
type Item = { type: 'header'; projectId: number; projectName: string } | ({ type: 'row' } & Row);

export function TaskGanttView({ tasks, onEdit }: { tasks: Task[]; onEdit: (task: Task) => void }) {
    const [cursor, setCursor] = useState(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    });

    const monthStart = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
    const monthEnd = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0);
    const daysInMonth = monthEnd.getDate();
    const monthLabel = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(cursor);

    const now = new Date();
    const todayDay = now.getFullYear() === cursor.getFullYear() && now.getMonth() === cursor.getMonth() ? now.getDate() : null;

    const isWeekend = (day: number) => {
        const dow = new Date(cursor.getFullYear(), cursor.getMonth(), day).getDay();
        return dow === 0 || dow === 6;
    };

    const rows = useMemo(() => {
        return tasks
            .filter((t) => t.start_date && t.end_date)
            .map((t) => ({
                task: t,
                start: parseDateKey(t.start_date as string),
                end: parseDateKey(t.end_date as string),
            }))
            .filter(({ start, end }) => end >= monthStart && start <= monthEnd)
            .map(({ task, start, end }) => ({
                task,
                clippedStartDay: start < monthStart ? 1 : start.getDate(),
                clippedEndDay: end > monthEnd ? daysInMonth : end.getDate(),
            }));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [tasks, cursor]);

    const items = useMemo(() => {
        const byProject = new Map<number, { name: string; rows: Row[] }>();
        for (const row of rows) {
            const projectId = row.task.project?.id ?? 0;
            const projectName = row.task.project?.name ?? 'Sans projet';
            if (!byProject.has(projectId)) {
                byProject.set(projectId, { name: projectName, rows: [] });
            }
            byProject.get(projectId)!.rows.push(row);
        }

        const result: Item[] = [];
        Array.from(byProject.entries())
            .sort((a, b) => a[1].name.localeCompare(b[1].name, 'fr'))
            .forEach(([projectId, group]) => {
                result.push({ type: 'header', projectId, projectName: group.name });
                group.rows.forEach((row) => result.push({ type: 'row', ...row }));
            });
        return result;
    }, [rows]);

    const gridTemplateColumns = `repeat(${daysInMonth}, minmax(24px, 1fr))`;
    let rowIndex = 0;

    return (
        <div className="flex flex-col gap-2 rounded-lg border p-3">
            <div className="flex items-center justify-between">
                <span className="text-sm font-medium capitalize">{monthLabel}</span>
                <div className="flex gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-7"
                        onClick={() => setCursor((c) => new Date(c.getFullYear(), c.getMonth() - 1, 1))}
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-7"
                        onClick={() => setCursor((c) => new Date(c.getFullYear(), c.getMonth() + 1, 1))}
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </div>

            {rows.length === 0 ? (
                <p className="py-6 text-center text-sm text-muted-foreground">Aucune tâche avec une date de début et une date de fin ce mois-ci.</p>
            ) : (
                <div className="overflow-x-auto">
                    <div className="relative min-w-[640px]">
                        <div className="flex">
                            <div className="w-32 shrink-0" />
                            <div className="grid flex-1 text-center text-xs text-muted-foreground" style={{ gridTemplateColumns }}>
                                {Array.from({ length: daysInMonth }, (_, i) => i + 1).map((day) => (
                                    <div key={day} className={cn('rounded-t', isWeekend(day) && 'bg-muted/40')}>
                                        {day}
                                        <span className="block text-[10px] opacity-70">
                                            {DAY_OF_WEEK_LETTERS[new Date(cursor.getFullYear(), cursor.getMonth(), day).getDay()]}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {todayDay && (
                            <div
                                className="pointer-events-none absolute top-0 bottom-0 z-10 w-px bg-primary"
                                style={{ left: `calc(8rem + (100% - 8rem) * ${(todayDay - 0.5) / daysInMonth})` }}
                            />
                        )}

                        <div className="mt-1 flex max-h-96 flex-col overflow-y-auto">
                            {items.map((item) => {
                                if (item.type === 'header') {
                                    return (
                                        <div
                                            key={`h-${item.projectId}`}
                                            className="flex items-center gap-1.5 py-1.5 text-xs font-medium text-muted-foreground first:pt-0"
                                        >
                                            <Folder className="size-3.5" />
                                            {item.projectName}
                                        </div>
                                    );
                                }

                                const { task, clippedStartDay, clippedEndDay } = item;
                                const zebra = rowIndex % 2 === 1;
                                rowIndex += 1;
                                const blockedBy = task.depends_on.filter((d) => d.status !== 'terminé');

                                return (
                                    <div key={task.id} className={cn('flex items-center rounded py-0.5', zebra && 'bg-muted/30')}>
                                        <div className="flex w-32 shrink-0 items-center gap-1 pr-2 text-sm">
                                            <span className="truncate">{task.name}</span>
                                            {task.depends_on.length > 0 && (
                                                <span
                                                    className="shrink-0"
                                                    title={
                                                        blockedBy.length > 0
                                                            ? `Bloquée par : ${blockedBy.map((d) => d.name).join(', ')}`
                                                            : "Dépend d'autres tâches, toutes terminées"
                                                    }
                                                >
                                                    <Link2
                                                        className={cn('size-3', blockedBy.length > 0 ? 'text-destructive' : 'text-muted-foreground')}
                                                    />
                                                </span>
                                            )}
                                        </div>
                                        <div className="grid flex-1" style={{ gridTemplateColumns }}>
                                            {Array.from({ length: daysInMonth }, (_, i) => i + 1).map((day) => (
                                                <div key={day} className={cn(isWeekend(day) && 'bg-muted/40')} />
                                            ))}
                                            <button
                                                type="button"
                                                title={task.name}
                                                onClick={() => onEdit(task)}
                                                style={{
                                                    gridColumnStart: clippedStartDay,
                                                    gridColumnEnd: clippedEndDay + 1,
                                                    backgroundColor: STATUS_CHART_COLORS[task.status],
                                                }}
                                                className="relative h-6 self-center rounded-md shadow-sm"
                                            >
                                                <span
                                                    className="absolute top-1/2 -left-1.5 flex size-4 -translate-y-1/2 items-center justify-center rounded-full border-2 border-card bg-card text-[9px] font-bold shadow-sm"
                                                    title={task.assigned_user.name}
                                                >
                                                    {initial(task.assigned_user.name)}
                                                </span>
                                                {task.priority === 'haute' && (
                                                    <Flag className="absolute top-1/2 right-1 size-3 -translate-y-1/2 fill-white text-white drop-shadow" />
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
