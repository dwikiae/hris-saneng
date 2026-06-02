import { PemberkasanPage } from "@/components/modules/recruitment/pemberkasan/PemberkasanPage";

interface PemberkasanRouteProps {
  params: {
    token: string;
  };
}

export default function PemberkasanRoutePage({ params }: PemberkasanRouteProps) {
  return <PemberkasanPage token={params.token} />;
}
