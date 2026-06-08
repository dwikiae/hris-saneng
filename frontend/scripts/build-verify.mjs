import { spawnSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const frontendRoot = path.resolve(scriptDir, "..");
const nextBin = path.join(frontendRoot, "node_modules", "next", "dist", "bin", "next");
const tsconfigPath = path.join(frontendRoot, "tsconfig.json");
const originalTsconfig = fs.existsSync(tsconfigPath) ? fs.readFileSync(tsconfigPath, "utf8") : null;

const result = spawnSync(process.execPath, [nextBin, "build"], {
  cwd: frontendRoot,
  env: {
    ...process.env,
    NEXT_DIST_DIR: ".next-verify"
  },
  stdio: "inherit"
});

if (originalTsconfig !== null && fs.readFileSync(tsconfigPath, "utf8") !== originalTsconfig) {
  fs.writeFileSync(tsconfigPath, originalTsconfig);
}

process.exit(result.status ?? 1);
