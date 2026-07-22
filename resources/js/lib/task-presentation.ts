import { CircleCheckBig, Clock, Hourglass } from 'lucide-react';

export const TASK_STATUSES = ['en attente', 'en cours', 'terminé'] as const;
export const TASK_PRIORITIES = ['basse', 'moyenne', 'haute'] as const;

export type TaskStatus = (typeof TASK_STATUSES)[number];
export type TaskPriority = (typeof TASK_PRIORITIES)[number];

// Palette validée (skill dataviz), ordre fixe, indépendante des thèmes de
// couleur de l'app (variables CSS définies dans app.css, clair/sombre only).
export const STATUS_CHART_COLORS: Record<TaskStatus, string> = {
    'en attente': 'var(--chart-status-waiting)',
    'en cours': 'var(--chart-status-progress)',
    terminé: 'var(--chart-status-done)',
};

// Échelle de sévérité ordonnée (basse → haute), pas une identité catégorielle.
export const PRIORITY_DOT_COLORS: Record<TaskPriority, string> = {
    basse: '#1baf7a',
    moyenne: '#eda100',
    haute: '#e34948',
};

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

/**
 * "lundi", "mardi"... si la date tombe dans la semaine courante
 * (lundi-dimanche) ; sinon une date FR ("26 juillet"), sans l'année si
 * c'est l'année courante ("26 juillet 2025" sinon).
 */
export function formatTaskDate(dateStr: string | null): string | null {
    if (!dateStr) {
        return null;
    }

    const [year, month, day] = dateStr.split('-').map(Number);
    const date = new Date(year, month - 1, day);

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const dayOfWeek = (today.getDay() + 6) % 7; // 0 = lundi
    const monday = new Date(today);
    monday.setDate(today.getDate() - dayOfWeek);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    if (date >= monday && date <= sunday) {
        return new Intl.DateTimeFormat('fr-FR', { weekday: 'long' }).format(date);
    }

    const includeYear = date.getFullYear() !== today.getFullYear();
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        ...(includeYear ? { year: 'numeric' as const } : {}),
    }).format(date);
}
