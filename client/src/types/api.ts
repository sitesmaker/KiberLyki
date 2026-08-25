export interface Media {
  id: number;
  documentId?: string;
  url: string;
  alternativeText?: string | null;
  mime?: string;
}

export interface StrapiListResponse<T> {
  data: T[];
  meta: {
    pagination: {
      page: number;
      pageSize: number;
      pageCount: number;
      total: number;
    };
  };
}

export interface StrapiSingleResponse<T> {
  data: T;
  meta?: Record<string, unknown>;
}

export interface ArticleData {
  id: number;
  documentId: string;
  title: string;
  description?: string | null;
  content?: string | null;
  slug?: string | null;
  author?: string | null;
  cover?: Media | null;
  gallery?: Media[];
  createdAt: string;
  publishedAt?: string | null;
}

export interface StaffData {
  id: number;
  documentId: string;
  firstName: string;
  lastName: string;
  specialization?: string;
  description?: string;
  photo?: Media | null;
}
