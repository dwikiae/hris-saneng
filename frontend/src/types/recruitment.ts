export interface JobPosting {
  id: number;
  code?: string | null;
  nama_posisi?: string | null;
  title?: string | null;
  position_name?: string | null;
  departemen?: string | null;
  department?: string | null;
  department_name?: string | null;
  deskripsi_pekerjaan?: string | null;
  description?: string | null;
  persyaratan_umum?: string | null;
  requirements?: string | null;
  expired_at?: string | null;
  status?: "draft" | "published" | "archived" | string | null;
}

export interface EducationFormData {
  tingkat_pendidikan: string;
  nama_sekolah: string;
  jurusan: string;
  tahun_lulus: string;
  nilai_akhir: string;
}

export interface ExperienceFormData {
  nama_perusahaan: string;
  posisi: string;
  masa_kerja_dari: string;
  masa_kerja_sampai: string;
}

export interface ApplicationFormData {
  job_posting_id: string;
  full_name: string;
  birth_place: string;
  birth_date: string;
  gender: string;
  address_ktp: string;
  address_domisili: string;
  height: string;
  weight: string;
  has_glasses: boolean;
  is_color_blind: boolean;
  marital_status: string;
  citizenship: string;
  email: string;
  whatsapp_number: string;
  has_welding_skill: boolean;
  source: string;
  cv_path: File | null;
  certificate_path: File | null;
  educations: EducationFormData[];
  experiences: ExperienceFormData[];
}
