import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { STATUS_CHART_COLORS } from '@/lib/task-presentation';
import { type Task } from '@/types';

function parseDateKey(dateStr: string): Date {
    const [year, month, day] = dateStr.split('-').map(Number);
    return new Date(year, month - 1, day);
}

export function TaskGanttView({ tasks, onEdit }: { tasks: Task[]; onEdit: (task: Task) => void }) {
    const [cursor, setCursor] = useState(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    });

    const monthStart = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
    const monthEnd = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0);
    const daysInMonth = monthEnd.getDate();
    const monthLabel = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(cursor);

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

    const gridTemplateColumns = `repeat(${daysInMonth}, minmax(24px, 1fr))`;

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
                    <div className="min-w-[640px]">
                        <div className="flex">
                            <div className="w-32 shrink-0" />
                            <div className="grid flex-1 text-center text-xs text-muted-foreground" style={{ gridTemplateColumns }}>
                                {Array.from({ length: daysInMonth }, (_, i) => (
                                    <div key={i}>{i + 1}</div>
                                ))}
                            </div>
                        </div>

                        <div className="mt-1 flex max-h-80 flex-col gap-1 overflow-y-auto">
                            {rows.map(({ task, clippedStartDay, clippedEndDay }) => (
                                <div key={task.id} className="flex items-center">
                                    <div className="sticky left-0 w-32 shrink-0 truncate bg-card pr-2 text-sm">{task.name}</div>
                                    <div className="grid flex-1" style={{ gridTemplateColumns }}>
                                        <button
                                            type="button"
                                            title={task.name}
                                            onClick={() => onEdit(task)}
                                            style={{
                                                gridColumnStart: clippedStartDay,
                                                gridColumnEnd: clippedEndDay + 1,
                                                backgroundColor: STATUS_CHART_COLORS[task.status],
                                            }}
                                            className="h-5 rounded"
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
