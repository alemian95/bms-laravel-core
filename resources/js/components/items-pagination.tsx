import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import type { Paginated } from '@/types';

export function ItemsPagination({
    pagination,
}: {
    pagination: Paginated<unknown>;
}) {
    return (
        <div className="flex items-center justify-between">
            <div className="text-sm text-muted-foreground">
                Showing {pagination.from} to {pagination.to} of{' '}
                {pagination.total} results
            </div>
            <div>
                <Pagination>
                    <PaginationContent>
                        {pagination.prev_page_url && (
                            <PaginationItem>
                                <PaginationPrevious
                                    href={pagination.prev_page_url}
                                />
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
                                <PaginationItem>
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
                                <PaginationNext
                                    href={pagination.next_page_url}
                                />
                            </PaginationItem>
                        )}
                    </PaginationContent>
                </Pagination>
            </div>
        </div>
    );
}
