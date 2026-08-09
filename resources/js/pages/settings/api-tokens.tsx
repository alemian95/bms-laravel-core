import { Form, Head, router, usePage } from '@inertiajs/react';
import { Check, Copy, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
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
import { useTranslation } from '@/hooks/use-translation';
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
};

type FlashData = {
    newToken?: { name: string; plainTextToken: string };
};

export default function ApiTokens() {
    const { t } = useTranslation();
    const page = usePage<PageProps>();
    const tokens = page.props.tokens ?? [];
    const presets = page.props.presets ?? [];
    const flash = (page as unknown as { flash?: FlashData }).flash;
    const newToken = flash?.newToken;

    const [preset, setPreset] = useState<string>(presets[0]?.value ?? '');
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        if (!copied) {
            return;
        }

        const timeout = setTimeout(() => setCopied(false), 2000);

        return () => clearTimeout(timeout);
    }, [copied]);

    const revoke = (id: number) => {
        if (confirm(t('Revoke this token? This action cannot be undone.'))) {
            router.delete(destroy(id).url);
        }
    };

    const copyToken = async (token: string) => {
        try {
            await navigator.clipboard.writeText(token);
            setCopied(true);
        } catch {
            // Clipboard API can fail in non-secure contexts; fallback: select the code element
            window
                .getSelection()
                ?.selectAllChildren(
                    document.getElementById('plain-text-token') as Node,
                );
        }
    };

    return (
        <>
            <Head title={t('API Tokens')} />

            <h1 className="sr-only">{t('API Tokens')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('API Tokens')}
                    description={t(
                        'Issue tokens for the browser extension, mobile app or other clients that need API access.',
                    )}
                />

                {newToken && (
                    <div className="rounded-md border border-green-500 bg-green-50 p-4 dark:bg-green-950/30">
                        <p className="text-sm font-semibold">
                            {t('Token ":name" created', {
                                name: newToken.name,
                            })}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {t(
                                'Copy this token now — it will not be shown again.',
                            )}
                        </p>
                        <div className="mt-2 flex items-start gap-2">
                            <code
                                id="plain-text-token"
                                className="flex-1 rounded bg-background p-2 font-mono text-xs break-all"
                            >
                                {newToken.plainTextToken}
                            </code>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    copyToken(newToken.plainTextToken)
                                }
                                aria-label={t('Copy token to clipboard')}
                            >
                                {copied ? (
                                    <>
                                        <Check className="h-4 w-4" />
                                        {t('Copied')}
                                    </>
                                ) : (
                                    <>
                                        <Copy className="h-4 w-4" />
                                        {t('Copy')}
                                    </>
                                )}
                            </Button>
                        </div>
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
                                <Label htmlFor="name">{t('Token name')}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    placeholder={t('My Chrome on MacBook')}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="preset">
                                    {t('Permission preset')}
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
                                        <SelectValue
                                            placeholder={t('Select a preset')}
                                        />
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
                                {t('Create token')}
                            </Button>
                        </>
                    )}
                </Form>

                <div className="space-y-2">
                    <h3 className="text-sm font-medium">
                        {t('Existing tokens')}
                    </h3>
                    {tokens.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('No tokens yet.')}
                        </p>
                    ) : (
                        <ul className="divide-y rounded-md border">
                            {tokens.map((token) => (
                                <li
                                    key={token.id}
                                    className="flex items-start justify-between gap-4 p-3"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {token.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {token.last_used_at
                                                ? t('Last used :date', {
                                                      date: token.last_used_at,
                                                  })
                                                : t('Never used')}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {t('Abilities:')}{' '}
                                            {token.abilities?.join(', ') ??
                                                t('none')}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => revoke(token.id)}
                                        aria-label={t('Revoke token :name', {
                                            name: token.name,
                                        })}
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
