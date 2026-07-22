import { THEMES, useTheme, type Theme } from '@/hooks/use-theme';
import { cn } from '@/lib/utils';

const SWATCH_COLORS: Record<Theme, string> = {
    default: 'oklch(0.205 0 0)',
    ocean: 'oklch(0.55 0.18 240)',
    forest: 'oklch(0.5 0.14 150)',
};

export default function ThemePicker() {
    const { theme, updateTheme } = useTheme();

    return (
        <div className="flex flex-wrap gap-2">
            {THEMES.map(({ value, label }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateTheme(value)}
                    className={cn(
                        'flex items-center gap-2 rounded-lg border px-3.5 py-1.5 text-sm transition-colors',
                        theme === value ? 'border-foreground' : 'border-border hover:bg-accent',
                    )}
                >
                    <span className="size-4 rounded-full border border-black/10" style={{ backgroundColor: SWATCH_COLORS[value] }} />
                    {label}
                </button>
            ))}
        </div>
    );
}
