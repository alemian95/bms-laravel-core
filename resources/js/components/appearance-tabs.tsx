import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslation } from '@/hooks/use-translation';

export default function AppearanceToggleTab() {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useTranslation();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: t('Light') },
        { value: 'dark', icon: Moon, label: t('Dark') },
        { value: 'system', icon: Monitor, label: t('System') },
    ];

    const selectAppearance = (value: string) => {
        const tab = tabs.find((candidate) => candidate.value === value);

        // Radix emits an empty string when the active item is clicked again.
        if (tab) {
            updateAppearance(tab.value);
        }
    };

    return (
        <ToggleGroup
            type="single"
            variant="outline"
            value={appearance}
            onValueChange={selectAppearance}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <ToggleGroupItem key={value} value={value} className="px-3">
                    <Icon />
                    {label}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}
