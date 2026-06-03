import { requestPrivateJson } from "@/services/api-client";
import type { PlatformConfig, PlatformConfigPayload } from "@/types/settings-platform";

export const platformConfigService = {
  get(): Promise<PlatformConfig> {
    return requestPrivateJson<PlatformConfig>("/instance/config");
  },
  update(payload: PlatformConfigPayload): Promise<PlatformConfig> {
    return requestPrivateJson<PlatformConfig>("/instance/config", {
      method: "PUT",
      body: JSON.stringify(payload)
    });
  },
  testSmtp(): Promise<null> {
    return requestPrivateJson<null>("/instance/config/test-smtp", { method: "POST" });
  }
};
