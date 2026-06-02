"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { DashboardGreeting } from "@/components/dashboard/DashboardGreeting";
import { DashboardStatsRow } from "@/components/dashboard/DashboardStatsRow";
import { PendingApprovalsWidget } from "@/components/dashboard/PendingApprovalsWidget";
import { RecentActivityList } from "@/components/dashboard/RecentActivityList";
import { authService } from "@/services/auth.service";
import { dashboardService } from "@/services/dashboard.service";

const APPROVAL_PERMISSION = "employee.approve";
const RECENT_ACTIVITY_LIMIT = 5;

export default function DashboardPage() {
  const { t } = useTranslation("platform");
  const queryClient = useQueryClient();
  const currentUserQuery = useQuery({
    queryKey: ["auth", "me"],
    queryFn: authService.currentUser,
    retry: false
  });
  const dashboardQuery = useQuery({
    queryKey: ["dashboard", "stats"],
    queryFn: dashboardService.stats,
    retry: false
  });
  const approveMutation = useMutation({
    mutationFn: dashboardService.approveEmployee,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ["dashboard", "stats"] });
    }
  });
  const hasApprovalPermission =
    currentUserQuery.data?.permissions.includes(APPROVAL_PERMISSION) ?? false;
  const recentActivities =
    dashboardQuery.data?.recent_activities.slice(0, RECENT_ACTIVITY_LIMIT) ?? [];

  return (
    <>
      <DashboardGreeting
        isLoading={currentUserQuery.isLoading}
        isError={currentUserQuery.isError}
        unavailableText={t("dashboard.greeting.unavailable")}
        titleFor={(period) =>
          t(`dashboard.greeting.${period}`, { name: currentUserQuery.data?.name })
        }
      />
      <DashboardStatsRow
        data={dashboardQuery.data?.stats}
        isLoading={dashboardQuery.isLoading}
        isError={dashboardQuery.isError}
        labels={{
          totalEmployees: t("dashboard.stats.totalEmployees"),
          presentToday: t("dashboard.stats.presentToday"),
          absentToday: t("dashboard.stats.absentToday"),
          leaveToday: t("dashboard.stats.leaveToday")
        }}
        errorTitle={t("dashboard.apiError.title")}
        errorDescription={t("dashboard.apiError.description")}
        retryLabel={t("dashboard.actions.retry")}
        onRetry={() => {
          void dashboardQuery.refetch();
        }}
      />
      {hasApprovalPermission ? (
        <div className="mt-6">
          <PendingApprovalsWidget
            title={t("dashboard.approvals.title")}
            emptyText={t("dashboard.approvals.empty")}
            approveLabel={t("dashboard.actions.quickApprove")}
            items={dashboardQuery.data?.pending_approvals}
            isLoading={dashboardQuery.isLoading}
            isError={dashboardQuery.isError}
            errorTitle={t("dashboard.apiError.title")}
            errorDescription={t("dashboard.apiError.description")}
            isApproving={approveMutation.isPending}
            onApproveEmployee={(employeeId) => approveMutation.mutate(employeeId)}
          />
        </div>
      ) : null}
      <div className="mt-6">
        <RecentActivityList
          title={t("dashboard.activityTitle")}
          items={recentActivities}
          isLoading={dashboardQuery.isLoading}
          isError={dashboardQuery.isError}
          errorTitle={t("dashboard.apiError.title")}
          errorDescription={t("dashboard.apiError.description")}
          emptyTitle={t("dashboard.empty.title")}
          emptyDescription={t("dashboard.empty.description")}
        />
      </div>
    </>
  );
}
