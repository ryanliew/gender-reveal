export type Gender = 'boy' | 'girl';

export interface GuestData {
    id: number;
    name: string;
    avatar: string | null;
    photoUrl: string | null;
    gender: Gender;
}
