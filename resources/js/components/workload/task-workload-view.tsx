import { TriangleAlert } from 'lucide-react';
import { useMemo } from 'react';

import { STATUS_CHART_COLORS, TASK_STATUSES, initial, type TaskStatus } from '@/lib/task-presentation';
import { cn } from '@/lib/utils';
import { type Task } from '@/types';

type MemberWorkload = {
    id: number;
    name: string;
    total: number;
    counts: Record<TaskStatus, number>;
    overdue: number;
};

function isOverdue(task: Task, today: Date): boolean {
    if (task.status === 'terminé' || !task.end_date) {
        return false;
    }
    const [year, month, day] = task.end_date.split('-').map(Number);
    return new Date(year, month - 1, day) < today;
}

export function TaskWorkloadView({ tasks }: { tasks: Task[] }) {
    const members = useMemo(() => {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const byMember = new Map<number, MemberWorkload>();

        for (const task of tasks) {
            const user = task.assigned_user;
            if (!byMember.has(user.id)) {
                byMember.set(user.id, {
                    id: user.id,
                    name: user.name,
                    total: 0,
                    counts: { 'en attente': 0, 'en cours': 0, terminé: 0 },
                    overdue: 0,
                });
            }

            const entry = byMember.get(user.id)!;
            entry.total += 1;
            entry.counts[task.status] += 1;
            if (isOverdue(task, today)) {
                entry.overdue += 1;
            }
        }

        return Array.from(byMember.values()).sort((a, b) => b.total - a.total);
    }, [tasks]);

    const maxTotal = Math.max(1, ...members.map((m) => m.total));
    const totalActive = members.reduce((sum, m) => sum + m.counts['en attente'] + m.counts['en cours'], 0);
    const totalOverdue = members.reduce((sum, m) => sum + m.overdue, 0);

    if (members.length === 0) {
        return <p className="py-6 text-center text-sm text-muted-foreground">Aucune tâche à répartir.</p>;
    }

    return (
        <div className="flex flex-col gap-4 rounded-lg border p-4">
            <div className="flex flex-wrap gap-6">
                <div>
                    <p className="text-xs text-muted-foreground">Tâches actives</p>
                    <p className="text-2xl font-semibold">{totalActive}</p>
                </div>
                <div>
                    <p className="text-xs text-muted-foreground">En retard</p>
                    <p className={cn('text-2xl font-semibold', totalOverdue > 0 && 'text-destructive')}>{totalOverdue}</p>
                </div>
            </div>

            <div className="flex items-center gap-4 text-xs text-muted-foreground">
                {TASK_STATUSES.map((status) => (
                    <span key={status} className="flex items-center gap-1.5">
                        <span className="size-2 rounded-full" style={{ backgroundColor: STATUS_CHART_COLORS[status] }} />
                        {status}
                    </span>
                ))}
            </div>

            <ul className="flex flex-col gap-3">
                {members.map((member) => (
                    <li key={member.id} className="flex items-center gap-3">
                        <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-bold">
                            {initial(member.name)}
                        </span>
                        <div className="min-w-0 flex-1">
                            <div className="flex items-baseline justify-between gap-2">
                                <span className="truncate text-sm font-medium">{member.name}</span>
                                <span className="flex shrink-0 items-center gap-3 text-xs text-muted-foreground">
                                    {member.overdue > 0 && (
                                        <span className="flex items-center gap-1 font-medium text-destructive">
                                            <TriangleAlert className="size-3" />
                                            {member.overdue} en retard
                                        </span>
                                    )}
                                    <span>
                                        {member.total} tâche{member.total > 1 ? 's' : ''}
                                    </span>
                                </span>
                            </div>
                            <div
                                className="mt-1.5 flex h-3 overflow-hidden rounded-full bg-muted"
                                style={{ width: `${(member.total / maxTotal) * 100}%`, minWidth: '2rem' }}
                            >
                                {TASK_STATUSES.map((status, i) => {
                                    const count = member.counts[status];
                                    if (count === 0) {
                                        return null;
                                    }
                                    return (
                                        <div
                                            key={status}
                                            title={`${status} : ${count}`}
                                            className={cn(i > 0 && 'ml-0.5')}
                                            style={{ width: `${(count / member.total) * 100}%`, backgroundColor: STATUS_CHART_COLORS[status] }}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}
