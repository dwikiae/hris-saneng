import { create } from "zustand";

interface UiShellState {
  isMobileSidebarOpen: boolean;
  isSidebarCollapsed: boolean;
  closeMobileSidebar: () => void;
  openMobileSidebar: () => void;
  toggleMobileSidebar: () => void;
  toggleSidebarCollapsed: () => void;
}

export const useUiShellStore = create<UiShellState>((set) => ({
  isMobileSidebarOpen: false,
  isSidebarCollapsed: false,
  closeMobileSidebar: () => set({ isMobileSidebarOpen: false }),
  openMobileSidebar: () => set({ isMobileSidebarOpen: true }),
  toggleMobileSidebar: () =>
    set((state) => ({ isMobileSidebarOpen: !state.isMobileSidebarOpen })),
  toggleSidebarCollapsed: () =>
    set((state) => ({ isSidebarCollapsed: !state.isSidebarCollapsed }))
}));
