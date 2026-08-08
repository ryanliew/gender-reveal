export interface AvatarPreset {
    src: string;
    label: string;
}

export const AVATARS: Record<string, AvatarPreset> = {
    lion: { src: '/images/01-lion.png', label: 'Lion' },
    tiger: { src: '/images/02-tiger.png', label: 'Tiger' },
    elephant: { src: '/images/03-elephant.png', label: 'Elephant' },
    'light-bear': { src: '/images/04-light-bear.png', label: 'Bear' },
    'dark-bear': { src: '/images/05-dark-bear.png', label: 'Brown bear' },
    'fox-front': { src: '/images/06-fox-front.png', label: 'Fox' },
    panda: { src: '/images/07-panda.png', label: 'Panda' },
    'fox-side': { src: '/images/08-fox-side.png', label: 'Sly fox' },
    owl: { src: '/images/09-owl.png', label: 'Owl' },
    dog: { src: '/images/10-dog.png', label: 'Dog' },
    giraffe: { src: '/images/11-giraffe.png', label: 'Giraffe' },
    zebra: { src: '/images/12-zebra.png', label: 'Zebra' },
    cat: { src: '/images/13-cat.png', label: 'Cat' },
    monkey: { src: '/images/14-monkey.png', label: 'Monkey' },
    wolf: { src: '/images/15-wolf.png', label: 'Wolf' },
    rabbit: { src: '/images/16-rabbit.png', label: 'Rabbit' },
    penguin: { src: '/images/17-penguin.png', label: 'Penguin' },
    koala: { src: '/images/18-koala.png', label: 'Koala' },
};

export function avatarPreset(key: string | null): AvatarPreset {
    return (key !== null ? AVATARS[key] : undefined) ?? AVATARS.lion;
}
