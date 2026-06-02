import { CareerPage } from "@/components/modules/recruitment/public/CareerPage";
import { publicService } from "@/services/public.service";

export const revalidate = 60;

export default async function KarirPage() {
  const jobs = await publicService.getPublicJobs();

  return <CareerPage jobs={jobs} />;
}
