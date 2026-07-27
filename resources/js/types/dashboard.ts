export type DashboardCounters = {
    total: number;
    unread: number;
    inProgress: number;
    completed: number;
};

export type WeeklySaves = {
    week: string;
    saved: number;
};

export type CategoryCount = {
    name: string;
    total: number;
};

export type DomainCount = {
    domain: string;
    total: number;
};

export type ContinueReadingItem = {
    id: number;
    title: string;
    domain: string | null;
    progress: number;
    category: string | null;
    categoryColor: string | null;
};

export type DashboardProps = {
    counters: DashboardCounters;
    weekly: WeeklySaves[];
    byCategory: CategoryCount[];
    topDomains: DomainCount[];
    continueReading: ContinueReadingItem[];
};
