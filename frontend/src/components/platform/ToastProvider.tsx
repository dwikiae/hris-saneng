"use client";

import { Toaster, toast } from "sonner";

const toastDurations = {
  success: 3000,
  info: 3000,
  warning: 5000,
  error: Number.POSITIVE_INFINITY
};

export function ToastProvider() {
  return <Toaster position="bottom-right" richColors closeButton />;
}

export const platformToast = {
  success(message: string) {
    toast.success(message, { duration: toastDurations.success });
  },
  error(message: string) {
    toast.error(message, { duration: toastDurations.error });
  },
  warning(message: string) {
    toast.warning(message, { duration: toastDurations.warning });
  },
  info(message: string) {
    toast.info(message, { duration: toastDurations.info });
  }
};
