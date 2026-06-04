import { requestPrivateJson } from "@/services/api-client";
import type { WilayahCity, WilayahCountry, WilayahProvince } from "@/types/employee-settings";

export const wilayahService = {
  provinces(): Promise<WilayahProvince[]> {
    return requestPrivateJson<WilayahProvince[]>("/instance/wilayah/provinces");
  },
  cities(provinceCode: string): Promise<WilayahCity[]> {
    return requestPrivateJson<WilayahCity[]>(`/instance/wilayah/provinces/${provinceCode}/cities`);
  },
  countries(): Promise<WilayahCountry[]> {
    return requestPrivateJson<WilayahCountry[]>("/instance/wilayah/countries");
  }
};
