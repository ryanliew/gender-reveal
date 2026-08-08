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
    lion: { emoji: '🦁', bg: '#F7E1C0' },
    panda: { emoji: '🐼', bg: '#E8E8E8' },
    pig: { emoji: '🐷', bg: '#FBDDE2' },
    unicorn: { emoji: '🦄', bg: '#EDE1F7' },
    tiger: { emoji: '🐯', bg: '#FCE3C4' },
    octopus: { emoji: '🐙', bg: '#F3DDEB' },
    duck: { emoji: '🦆', bg: '#DDEAF2' },
    turtle: { emoji: '🐢', bg: '#DFEEDF' },
};

export function avatarPreset(key: string | null): AvatarPreset {
    return (
        (key !== null ? AVATARS[key] : undefined) ?? {
            emoji: '👶',
            bg: '#EFE7E1',
        }
    );
}
