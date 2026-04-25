import { useEffect, useState } from 'react';

export function useScrollPercentage() {
    const [scrollPercentage, setScrollPercentage] = useState<number>(0);

    useEffect(() => {
        const handleScroll = () => {
            const scrollTop =
                window.scrollY || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight;
            const clientHeight = document.documentElement.clientHeight;

            if (scrollHeight === clientHeight) {
                setScrollPercentage(0);

                return;
            }

            const percentage =
                (scrollTop / (scrollHeight - clientHeight)) * 100;
            setScrollPercentage(
                Math.min(Math.round(percentage), Math.round(100)),
            );
        };

        window.addEventListener('scroll', handleScroll);

        handleScroll();

        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return scrollPercentage;
}
