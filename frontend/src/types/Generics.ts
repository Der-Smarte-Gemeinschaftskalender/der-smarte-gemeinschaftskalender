export type MakeOptional<T, K extends keyof T> = Omit<T, K> & Partial<Pick<T, K>>;
export type MakeRequired<T, K extends keyof T> = Omit<T, K> & Required<Pick<T, K>>;

export type MakeNullable<T, K extends keyof T> = Omit<T, K> & { [P in K]: T[P] | null };
export type MakeNonNullable<T, K extends keyof T> = Omit<T, K> & { [P in K]: NonNullable<T[P]> };

export type RemoveKeys<T, K extends keyof T> = Omit<T, K>;