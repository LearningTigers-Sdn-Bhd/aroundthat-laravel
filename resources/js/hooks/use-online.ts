import { useEffect, useState } from 'react';

/**
 * Whether the browser believes it can reach the network. Starts optimistic, so the page does not flash "offline"
 * before the first real reading.
 */
export function useOnline(): boolean {
    const [online, setOnline] = useState(true);

    useEffect(() => {
        const update = () => setOnline(navigator.onLine);
        update();
        window.addEventListener('online', update);
        window.addEventListener('offline', update);

        return () => {
            window.removeEventListener('online', update);
            window.removeEventListener('offline', update);
        };
    }, []);

    return online;
}
