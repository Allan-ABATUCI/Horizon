import { router, usePage } from '@inertiajs/react';
import { Bell, Clock, UserPlus } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { readXsrfToken } from '@/lib/csrf';
import { type SharedData } from '@/types';

type NotificationItem = {
    id: string;
    kind: 'assigned' | 'due_soon';
    message: string;
    task_id: number;
    read: boolean;
    created_at: string;
};

export function NotificationBell() {
    const { unreadNotificationsCount } = usePage<SharedData>().props;
    const [notifications, setNotifications] = useState<NotificationItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [loaded, setLoaded] = useState(false);

    const load = () => {
        setLoading(true);
        fetch(route('notifications.index'), { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((json) => {
                setNotifications(json.data);
                setLoaded(true);
            })
            .finally(() => setLoading(false));
    };

    const markAsRead = (notification: NotificationItem) => {
        fetch(route('notifications.read', notification.id), {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': readXsrfToken() },
        }).then(() => router.reload({ only: ['unreadNotificationsCount'] }));

        setNotifications((current) => current.map((n) => (n.id === notification.id ? { ...n, read: true } : n)));
        router.visit(route('dashboard', { task: notification.task_id }));
    };

    const markAllAsRead = () => {
        fetch(route('notifications.readAll'), {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': readXsrfToken() },
        }).then(() => router.reload({ only: ['unreadNotificationsCount'] }));

        setNotifications((current) => current.map((n) => ({ ...n, read: true })));
    };

    return (
        <DropdownMenu
            onOpenChange={(open) => {
                if (open && !loaded) {
                    load();
                }
            }}
        >
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="group relative h-9 w-9 cursor-pointer">
                    <Bell className="!size-5 opacity-80 group-hover:opacity-100" />
                    {unreadNotificationsCount > 0 && (
                        <Badge variant="destructive" className="absolute -top-1 -right-1 h-4 min-w-4 justify-center rounded-full p-0 text-[10px]">
                            {unreadNotificationsCount}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-80" align="end">
                <div className="flex items-center justify-between px-2 py-1.5">
                    <span className="text-sm font-medium">Notifications</span>
                    <button type="button" onClick={markAllAsRead} className="text-xs text-muted-foreground hover:text-foreground">
                        Tout marquer comme lu
                    </button>
                </div>
                <div className="max-h-80 overflow-y-auto">
                    {loading ? (
                        <p className="p-3 text-sm text-muted-foreground">Chargement…</p>
                    ) : notifications.length === 0 ? (
                        <p className="p-3 text-sm text-muted-foreground">Aucune notification.</p>
                    ) : (
                        notifications.map((notification) => (
                            <button
                                key={notification.id}
                                type="button"
                                onClick={() => markAsRead(notification)}
                                className={`flex w-full items-start gap-2 border-t px-3 py-2 text-left text-sm hover:bg-accent ${
                                    notification.read ? 'text-muted-foreground' : 'font-medium'
                                }`}
                            >
                                {notification.kind === 'assigned' ? (
                                    <UserPlus className="mt-0.5 size-4 shrink-0" />
                                ) : (
                                    <Clock className="mt-0.5 size-4 shrink-0" />
                                )}
                                <span>
                                    {notification.message}
                                    <span className="block text-xs text-muted-foreground">{notification.created_at}</span>
                                </span>
                            </button>
                        ))
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
