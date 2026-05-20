export interface User {
    id: number;
    email: string;
    email_verified_at: string;
    type: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    admin_note?: string | null;
    mobilizon_name?: string | null;
}

export interface UserView {
    email: string;
    password: string;
    type: string;
    is_active: boolean;
}

export interface FullUser extends User {
    access_token: string;
    person: object | null;
}