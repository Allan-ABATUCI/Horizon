import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

// Forme renvoyée par UserResource lorsqu'un utilisateur est imbriqué dans
// une autre ressource (Project.created_by, Task.assigned_user, etc.).
export interface UserSummary {
    id: number;
    name: string;
    email: string;
}

export interface Project {
    id: number;
    name: string;
    description: string | null;
    end_date: string | null;
    status: 'en attente' | 'en cours' | 'terminé';
    image_path: string | null;
    created_by: UserSummary;
    updated_by: UserSummary;
    updated_at: string;
    created_at: string;
}

export interface Task {
    id: number;
    name: string;
    description: string | null;
    image_path: string | null;
    status: 'en attente' | 'en cours' | 'terminé';
    priority: 'basse' | 'moyenne' | 'haute';
    end_date: string | null;
    project_id: number;
    project?: Project;
    assigned_user: UserSummary;
    created_by: UserSummary;
    updated_by: UserSummary;
    updated_at: string;
    created_at: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
}
