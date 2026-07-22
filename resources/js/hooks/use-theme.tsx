import { useCallback, useEffect, useState } from 'react';

export type Theme = 'default' | 'ocean' | 'forest';

export const THEMES: { value: Theme; label: string }[] = [
    { value: 'default', label: 'Défaut' },
    { value: 'ocean', label: 'Océan' },
    { value: 'forest', label: 'Forêt' },
];

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyThemeAttribute = (theme: Theme) => {
    document.documentElement.setAttribute('data-theme', theme);
};

export function initializeThemeVariant() {
    const savedTheme = (localStorage.getItem('theme') as Theme) || 'default';

    applyThemeAttribute(savedTheme);
}

export function useTheme() {
    const [theme, setTheme] = useState<Theme>('default');

    const updateTheme = useCallback((value: Theme) => {
        setTheme(value);

        // Store in localStorage for client-side persistence...
        localStorage.setItem('theme', value);

        // Store in cookie for SSR...
        setCookie('theme', value);

        applyThemeAttribute(value);
    }, []);

    useEffect(() => {
        const savedTheme = localStorage.getItem('theme') as Theme | null;
        updateTheme(savedTheme || 'default');
    }, [updateTheme]);

    return { theme, updateTheme } as const;
}
