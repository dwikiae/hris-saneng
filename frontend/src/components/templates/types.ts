import type { ReactNode } from "react";

type StatusTone = "success" | "warning" | "danger" | "neutral" | "info";

export type TemplateActionVariant = "primary" | "secondary" | "ghost" | "danger";

export interface TemplateAction {
  id: string;
  label: string;
  custom?: ReactNode;
  icon?: ReactNode;
  href?: string;
  onClick?: () => void;
  disabled?: boolean;
  variant?: TemplateActionVariant;
}

export interface BreadcrumbItem {
  label: string;
  href?: string;
}

export interface MetaItem {
  label: string;
  value: ReactNode;
}

export interface StatusConfig {
  label: string;
  tone?: StatusTone;
}

export interface EmptyStateConfig {
  title: string;
  description?: string;
  actionLabel?: string;
  onAction?: () => void;
  icon?: ReactNode;
}

export interface SummaryItem {
  label: string;
  value: ReactNode;
  helper?: string;
}

export interface PaginationState {
  page: number;
  perPage: number;
  total: number;
  pageSizeOptions?: number[];
  onPageChange: (page: number) => void;
  onPerPageChange?: (perPage: number) => void;
}
