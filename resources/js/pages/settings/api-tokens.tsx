import { Form, Head, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import ApiTokenController from '@/actions/App/Http/Controllers/Settings/ApiTokenController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { destroy } from '@/routes/api-tokens';

type Token = {
    id: number;
    name: string;
    abilities: string[] | null;
    last_used_at: string | null;
    created_at: string;
};

type Preset = {
    value: string;
    label: string;
    abilities: string[];
};

type PageProps = {
    tokens: Token[];
    presets: Preset[];
    flash?: {
        newToken?: { name: string; plainTextToken: string };
    };
};

export default function ApiTokens() {
    const { props } = usePage<PageProps>();
    const tokens = props.tokens ?? [];
    const presets = props.presets ?? [];
    const newToken = props.flash?.newToken;

    const [preset, setPreset] = useState<string>(presets[0]?.value ?? '');

    const revoke = (id: number) => {
        if (confirm('Revoke this token? This action cannot be undone.')) {
            router.delete(destroy(id).url);
        }
    };

    return (
        <>
            <Head title="API Tokens" />

            <h1 className="sr-only">API Tokens</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="API Tokens"
                    description="Issue tokens for the browser extension, mobile app or other clients that need API access."
                />

                {newToken && (
                    <div className="rounded-md border border-green-500 bg-green-50 p-4 dark:bg-green-950/30">
                        <p className="text-sm font-semibold">
                            Token "{newToken.name}" created
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Copy this token now — it will not be shown again.
                        </p>
                        <code className="mt-2 block rounded bg-background p-2 text-xs break-all">
                            {newToken.plainTextToken}
                        </code>
                    </div>
                )}

                <Form
                    {...ApiTokenController.store.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                    onSuccess={() => setPreset(presets[0]?.value ?? '')}
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Token name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    placeholder="My Chrome on MacBook"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="preset">
                                    Permission preset
                                </Label>
                                <input
                                    type="hidden"
                                    name="preset"
                                    value={preset}
                                />
                                <Select
                                    value={preset}
                                    onValueChange={setPreset}
                                >
                                    <SelectTrigger id="preset">
                                        <SelectValue placeholder="Select a preset" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {presets.map((p) => (
                                            <SelectItem
                                                key={p.value}
                                                value={p.value}
                                            >
                                                <div className="flex flex-col">
                                                    <span>{p.label}</span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {p.abilities.join(', ')}
                                                    </span>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.preset} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Create token
                            </Button>
                        </>
                    )}
                </Form>

                <div className="space-y-2">
                    <h3 className="text-sm font-medium">Existing tokens</h3>
                    {tokens.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No tokens yet.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-md border">
                            {tokens.map((t) => (
                                <li
                                    key={t.id}
                                    className="flex items-start justify-between gap-4 p-3"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {t.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {t.last_used_at
                                                ? `Last used ${t.last_used_at}`
                                                : 'Never used'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Abilities:{' '}
                                            {t.abilities?.join(', ') ?? 'none'}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => revoke(t.id)}
                                        aria-label={`Revoke token ${t.name}`}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}
