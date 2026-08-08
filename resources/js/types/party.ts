export type Gender = 'boy' | 'girl';

export interface GuestData {
    id: number;
    name: string;
    avatar: string;
    gender: Gender;
}
