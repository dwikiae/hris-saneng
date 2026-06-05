"use client";

import type { ReactNode } from "react";
import { Search, X } from "lucide-react";
import Link from "next/link";
import { useTranslation } from "react-i18next";
import { EmptyState } from "@/components/shared/EmptyState";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from "@/components/ui/table";
import { cn } from "@/lib/utils";
import {
  ErrorPanel,
  ListSkeleton,
  Pagination,
  SummaryStrip,
  TemplateActionGroup
} from "./TemplateParts";
import type { BreadcrumbItem, EmptyStateConfig, PaginationState, SummaryItem, TemplateAction } from "./types";

export interface ListColumn<T> {
  key: string;
  header: ReactNode;
  cell: (row: T) => ReactNode;
  className?: string;
}

interface ListPageTemplateProps<T> {
  title: string;
  description?: string;
  breadcrumbs?: BreadcrumbItem[];
  actions?: TemplateAction[];
  banner?: ReactNode;
  searchValue?: string;
  searchPlaceholder?: string;
  onSearchChange?: (value: string) => void;
  filters?: ReactNode;
  activeFilterCount?: number;
  onResetFilters?: () => void;
  summaryItems?: SummaryItem[];
  showSummaryStrip?: boolean;
  columns: Array<ListColumn<T>>;
  data: T[];
  getRowId: (row: T) => string;
  selectedRowIds?: string[];
  onSelectionChange?: (rowIds: string[]) => void;
  bulkActions?: TemplateAction[];
  onRowClick?: (row: T) => void;
  mobileCard?: (row: T) => ReactNode;
  emptyState: EmptyStateConfig;
  isLoading: boolean;
  error?: Error | null;
  pagination?: PaginationState;
}

export function ListPageTemplate<T>({
  title,
  description,
  breadcrumbs = [],
  actions = [],
  banner,
  searchValue = "",
  searchPlaceholder,
  onSearchChange,
  filters,
  activeFilterCount = 0,
  onResetFilters,
  summaryItems = [],
  showSummaryStrip = true,
  columns,
  data,
  getRowId,
  selectedRowIds = [],
  onSelectionChange,
  bulkActions = [],
  onRowClick,
  mobileCard,
  emptyState,
  isLoading,
  error,
  pagination
}: ListPageTemplateProps<T>) {
  const { t } = useTranslation("platform");
  const selectable = Boolean(onSelectionChange);
  const selectedCount = selectedRowIds.length;
  const allVisibleIds = data.map(getRowId);
  const allVisibleSelected =
    allVisibleIds.length > 0 && allVisibleIds.every((id) => selectedRowIds.includes(id));

  const toggleAll = () => {
    if (!onSelectionChange) {
      return;
    }

    onSelectionChange(allVisibleSelected ? [] : allVisibleIds);
  };

  const toggleOne = (rowId: string) => {
    if (!onSelectionChange) {
      return;
    }

    onSelectionChange(
      selectedRowIds.includes(rowId)
        ? selectedRowIds.filter((id) => id !== rowId)
        : [...selectedRowIds, rowId]
    );
  };

  return (
    <section className="space-y-5">
      <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div className="space-y-2">
          {breadcrumbs.length > 0 ? (
            <nav className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
              {breadcrumbs.map((item, index) => (
                <span key={`${item.label}-${index}`} className="flex items-center gap-2">
                  {item.href ? <Link href={item.href}>{item.label}</Link> : <span>{item.label}</span>}
                  {index < breadcrumbs.length - 1 ? <span>/</span> : null}
                </span>
              ))}
            </nav>
          ) : null}
          <h1 className="text-2xl font-semibold text-foreground">{title}</h1>
          {description ? (
            <p className="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">{description}</p>
          ) : null}
        </div>
        <TemplateActionGroup actions={actions} />
      </div>

      {banner}

      <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
          <div className="relative min-w-0 flex-1">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              value={searchValue}
              placeholder={searchPlaceholder ?? t("templates.list.searchPlaceholder")}
              className="pl-9"
              onChange={(event) => onSearchChange?.(event.target.value)}
            />
          </div>
          <div className="hidden flex-wrap items-center gap-2 md:flex">{filters}</div>
          <Button type="button" variant="outline" className="justify-start md:hidden">
            {t("templates.list.mobileFilter", { count: activeFilterCount })}
          </Button>
          {activeFilterCount > 0 && onResetFilters ? (
            <Button type="button" variant="ghost" onClick={onResetFilters}>
              <X className="mr-2 h-4 w-4" />
              {t("templates.list.reset")}
            </Button>
          ) : null}
        </div>
      </div>

      {selectedCount > 0 ? (
        <div className="flex flex-col gap-3 rounded-lg border border-primary/20 bg-blue-50 p-4 md:flex-row md:items-center md:justify-between">
          <p className="text-sm font-medium text-blue-800">
            {t("templates.list.selectedCount", { count: selectedCount })}
          </p>
          <div className="flex flex-wrap gap-2">
            <TemplateActionGroup actions={bulkActions} compact />
            <Button type="button" variant="ghost" onClick={() => onSelectionChange?.([])}>
              {t("templates.list.clearSelection")}
            </Button>
          </div>
        </div>
      ) : showSummaryStrip ? (
        <SummaryStrip items={summaryItems} />
      ) : null}

      {isLoading ? <ListSkeleton /> : null}
      {!isLoading && error ? <ErrorPanel message={error.message} /> : null}
      {!isLoading && !error && data.length === 0 ? <EmptyState {...emptyState} /> : null}
      {!isLoading && !error && data.length > 0 ? (
        <>
          <div className="hidden overflow-hidden rounded-lg border border-border bg-card shadow-sm md:block">
            <Table>
              <TableHeader>
                <TableRow>
                  {selectable ? (
                    <TableHead className="w-10">
                      <input
                        type="checkbox"
                        className="h-4 w-4 rounded border-border"
                        checked={allVisibleSelected}
                        onChange={toggleAll}
                      />
                    </TableHead>
                  ) : null}
                  {columns.map((column) => (
                    <TableHead key={column.key} className={column.className}>
                      {column.header}
                    </TableHead>
                  ))}
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.map((row) => {
                  const rowId = getRowId(row);

                  return (
                    <TableRow
                      key={rowId}
                      className={cn(onRowClick && "cursor-pointer")}
                      onClick={() => onRowClick?.(row)}
                    >
                      {selectable ? (
                        <TableCell onClick={(event) => event.stopPropagation()}>
                          <input
                            type="checkbox"
                            className="h-4 w-4 rounded border-border"
                            checked={selectedRowIds.includes(rowId)}
                            onChange={() => toggleOne(rowId)}
                          />
                        </TableCell>
                      ) : null}
                      {columns.map((column) => (
                        <TableCell key={column.key} className={column.className}>
                          {column.cell(row)}
                        </TableCell>
                      ))}
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </div>
          <div className="space-y-3 md:hidden">
            {data.map((row) => (
              <button
                key={getRowId(row)}
                type="button"
                className="w-full rounded-lg border border-border bg-card p-4 text-left shadow-sm"
                onClick={() => onRowClick?.(row)}
              >
                {mobileCard
                  ? mobileCard(row)
                  : columns.slice(0, 3).map((column) => (
                      <div key={column.key} className="text-sm text-foreground">
                        {column.cell(row)}
                      </div>
                    ))}
              </button>
            ))}
          </div>
        </>
      ) : null}

      {pagination ? <Pagination pagination={pagination} /> : null}
    </section>
  );
}
