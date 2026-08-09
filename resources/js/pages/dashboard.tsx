import { Head, setLayoutProps } from '@inertiajs/react';
import {
    BookmarkIcon,
    BookOpenIcon,
    CheckCircle2Icon,
    CircleDashedIcon,
} from 'lucide-react';

import { ContinueReadingTable } from '@/components/dashboard/continue-reading-table';
import { CountBarChart } from '@/components/dashboard/count-bar-chart';
import { SavesOverTimeChart } from '@/components/dashboard/saves-over-time-chart';
import { StatTile } from '@/components/dashboard/stat-tile';
import { PluginSlot } from '@/components/plugin-slot';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';
import type { DashboardProps } from '@/types';

export default function Dashboard({
    counters,
    weekly,
    byCategory,
    topDomains,
    continueReading,
}: DashboardProps) {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [{ title: t('Dashboard'), href: dashboard() }],
    });

    return (
        <>
            <Head title={t('Dashboard')} />

            <div className={`flex flex-col gap-4 p-4`}>
                <div className={`grid gap-4 sm:grid-cols-2 xl:grid-cols-4`}>
                    <StatTile
                        label={t('Total bookmarks')}
                        value={counters.total}
                        icon={BookmarkIcon}
                    />
                    <StatTile
                        label={t('Unread')}
                        value={counters.unread}
                        icon={CircleDashedIcon}
                    />
                    <StatTile
                        label={t('In progress')}
                        value={counters.inProgress}
                        icon={BookOpenIcon}
                    />
                    <StatTile
                        label={t('Completed')}
                        value={counters.completed}
                        icon={CheckCircle2Icon}
                    />
                </div>

                <SavesOverTimeChart weekly={weekly} />

                <div className={`grid gap-4 lg:grid-cols-2`}>
                    <CountBarChart
                        title={t('By category')}
                        description={t('How your library is organised')}
                        data={byCategory.map((row) => ({
                            label: row.name,
                            value: row.total,
                        }))}
                        emptyMessage={t('Save a bookmark to see this.')}
                    />
                    <CountBarChart
                        title={t('Top domains')}
                        description={t('Where you read from most')}
                        data={topDomains.map((row) => ({
                            label: row.domain,
                            value: row.total,
                        }))}
                        emptyMessage={t('Save a bookmark to see this.')}
                    />
                </div>

                <ContinueReadingTable items={continueReading} />

                <PluginSlot name={`dashboard-widgets`} />
            </div>
        </>
    );
}
