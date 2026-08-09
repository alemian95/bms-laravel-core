import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
} from '@/components/ui/pagination';
import { useTranslation } from '@/hooks/use-translation';
import type { Paginated } from '@/types';

export function ItemsPagination({
    pagination,
}: {
    pagination: Paginated<unknown>;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex items-center justify-between">
            <div className="text-sm text-muted-foreground">
                {t('Showing :from to :to of :total results', {
                    from: pagination.from,
                    to: pagination.to,
                    total: pagination.total,
                })}
            </div>
            <div>
                <Pagination>
                    <PaginationContent>
                        {/* PaginationPrevious/Next hardcode their English label
                            and `ui/*` is vendored shadcn output, so the labelled
                            links are built here instead. */}
                        {pagination.prev_page_url && (
                            <PaginationItem>
                                <PaginationLink
                                    href={pagination.prev_page_url}
                                    aria-label={t('Go to previous page')}
                                    className="gap-1 px-2.5 sm:pl-2.5"
                                >
                                    <ChevronLeftIcon />
                                    <span className="hidden sm:block">
                                        {t('Previous')}
                                    </span>
                                </PaginationLink>
                            </PaginationItem>
                        )}
                        {pagination.links.map((link, index) => {
                            if (
                                !link.url ||
                                index === 0 ||
                                index === pagination.links.length - 1
                            ) {
                                return null;
                            }

                            return (
                                <PaginationItem key={index}>
                                    <PaginationLink
                                        href={link.url}
                                        isActive={link.active}
                                    >
                                        {link.label}
                                    </PaginationLink>
                                </PaginationItem>
                            );
                        })}
                        {pagination.next_page_url && (
                            <PaginationItem>
                                <PaginationLink
                                    href={pagination.next_page_url}
                                    aria-label={t('Go to next page')}
                                    className="gap-1 px-2.5 sm:pr-2.5"
                                >
                                    <span className="hidden sm:block">
                                        {t('Next')}
                                    </span>
                                    <ChevronRightIcon />
                                </PaginationLink>
                            </PaginationItem>
                        )}
                    </PaginationContent>
                </Pagination>
            </div>
        </div>
    );
}
