export interface AvatarPreset {
    emoji: string;
    bg: string;
}

export const AVATARS: Record<string, AvatarPreset> = {
    fox: { emoji: '🦊', bg: '#FDE4D3' },
    bear: { emoji: '🐻', bg: '#EAD9C9' },
    owl: { emoji: '🦉', bg: '#DCE6F2' },
    deer: { emoji: '🦌', bg: '#F5E3CF' },
    bunny: { emoji: '🐰', bg: '#F9E0E6' },
    chick: { emoji: '🐥', bg: '#FBF0CC' },
    koala: { emoji: '🐨', bg: '#E3E3E9' },
    frog: { emoji: '🐸', bg: '#DDEEDC' },
};

export function avatarPreset(key: string): AvatarPreset {
    return AVATARS[key] ?? { emoji: '👶', bg: '#EFE7E1' };
}
