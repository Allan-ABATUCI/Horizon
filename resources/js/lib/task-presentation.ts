import { CircleCheckBig, Clock, Hourglass } from 'lucide-react';

export const TASK_STATUSES = ['en attente', 'en cours', 'terminé'] as const;
export const TASK_PRIORITIES = ['basse', 'moyenne', 'haute'] as const;

export type TaskStatus = (typeof TASK_STATUSES)[number];
export type TaskPriority = (typeof TASK_PRIORITIES)[number];

export function renderStatusIcon(status: string) {
    switch (status) {
        case 'en attente':
            return Hourglass;
        case 'en cours':
            return Clock;
        case 'terminé':
            return CircleCheckBig;
    }
}

export function priorityVariant(priority: string): 'default' | 'secondary' | 'destructive' {
    switch (priority) {
        case 'haute':
            return 'destructive';
        case 'moyenne':
            return 'default';
        default:
            return 'secondary';
    }
}
