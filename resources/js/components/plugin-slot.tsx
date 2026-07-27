import { usePage } from '@inertiajs/react';
import type { ComponentType } from 'react';

type SlotProps = Record<string, unknown>;
type SlotMap = Record<string, ComponentType<SlotProps>>;

const modules = import.meta.glob<{ default: SlotMap }>(
    '/packages/*/resources/js/slots.tsx',
    { eager: true },
);

/** `/packages/<name>/resources/js/slots.tsx` -> `<name>` */
const registry = Object.entries(modules).map(([path, module]) => ({
    plugin: path.split('/')[2],
    slots: module.default,
}));

/**
 * Renders the components that packages registered for the given slot name.
 * A package only renders if its service provider shared `plugins.<name>`.
 */
export function PluginSlot({ name, ...props }: { name: string } & SlotProps) {
    const plugins = (usePage().props.plugins ?? {}) as Record<string, boolean>;

    return registry.flatMap(({ plugin, slots }) => {
        const Component = slots[name];

        return Component && plugins[plugin]
            ? [<Component key={plugin} {...props} />]
            : [];
    });
}
