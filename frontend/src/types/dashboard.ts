export interface DashboardStats {
  total_employees: number;
  present_today: number;
  absent_today: number;
  leave_today: number;
}

export interface DashboardApprovalItem {
  id: number | string;
  type: string;
  resource_id?: number;
  title: string;
  description?: string;
  requested_by?: string;
  created_at?: string;
}

export interface DashboardActivity {
  id: number | string;
  title: string;
  description?: string;
  actor?: string;
  module?: string;
  created_at?: string;
}

export interface DashboardStatsResponse {
  stats: DashboardStats;
  pending_approvals: DashboardApprovalItem[];
  recent_activities: DashboardActivity[];
}
