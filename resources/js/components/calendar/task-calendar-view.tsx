import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { PRIORITY_DOT_COLORS, renderStatusIcon } from '@/lib/task-presentation';
import { type Task } from '@/types';

const WEEKDAY_LABELS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
const MAX_VISIBLE_PER_DAY = 3;

function toDateKey(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getMonthGrid(year: number, month: number): Date[] {
    const firstOfMonth = new Date(year, month, 1);
    const startOffset = (firstOfMonth.getDay() + 6) % 7; // jours depuis lundi
    const gridStart = new Date(year, month, 1 - startOffset);

    return Array.from({ length: 42 }, (_, i) => {
        const d = new Date(gridStart);
        d.setDate(gridStart.getDate() + i);
        return d;
    });
}

export function TaskCalendarView({
    tasks,
    onEdit,
    onCreateForDate,
}: {
    tasks: Task[];
    onEdit: (task: Task) => void;
    onCreateForDate: (dateKey: string) => void;
}) {
    const [cursor, setCursor] = useState(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    });

    const tasksByDate = useMemo(() => {
        const map = new Map<string, Task[]>();
        for (const task of tasks) {
            if (!task.end_date) continue;
            const list = map.get(task.end_date) ?? [];
            list.push(task);
            map.set(task.end_date, list);
        }
        return map;
    }, [tasks]);

    const days = useMemo(() => getMonthGrid(cursor.getFullYear(), cursor.getMonth()), [cursor]);
    const todayKey = toDateKey(new Date());
    const monthLabel = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(cursor);

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

            <div className="grid grid-cols-7 gap-px overflow-hidden rounded-md border bg-border text-xs">
                {WEEKDAY_LABELS.map((label) => (
                    <div key={label} className="bg-muted px-1.5 py-1 text-center font-medium text-muted-foreground">
                        {label}
                    </div>
                ))}

                {days.map((day) => {
                    const key = toDateKey(day);
                    const dayTasks = tasksByDate.get(key) ?? [];
                    const isCurrentMonth = day.getMonth() === cursor.getMonth();
                    const isToday = key === todayKey;

                    return (
                        <div
                            key={key}
                            onClick={() => onCreateForDate(key)}
                            className={`min-h-20 cursor-pointer bg-card p-1 hover:bg-accent/50 ${isCurrentMonth ? '' : 'opacity-40'} ${isToday ? 'ring-1 ring-primary ring-inset' : ''}`}
                        >
                            <div className="mb-1 text-right text-muted-foreground">{day.getDate()}</div>
                            <div className="flex flex-col gap-0.5">
                                {dayTasks.slice(0, MAX_VISIBLE_PER_DAY).map((task) => {
                                    const Icon = renderStatusIcon(task.status);
                                    return (
                                        <button
                                            key={task.id}
                                            type="button"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                onEdit(task);
                                            }}
                                            style={{ borderLeftColor: PRIORITY_DOT_COLORS[task.priority] }}
                                            className="flex items-center gap-1 truncate rounded-sm border-l-2 bg-muted px-1 py-0.5 text-left hover:bg-accent"
                                        >
                                            {Icon && <Icon className="size-3 shrink-0" />}
                                            <span className="truncate">{task.name}</span>
                                        </button>
                                    );
                                })}
                                {dayTasks.length > MAX_VISIBLE_PER_DAY && (
                                    <span className="px-1 text-muted-foreground">+{dayTasks.length - MAX_VISIBLE_PER_DAY}</span>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
