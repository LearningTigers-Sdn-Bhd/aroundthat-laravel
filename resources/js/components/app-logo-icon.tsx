import type { ImgHTMLAttributes } from 'react';

import appIcon from '@/assets/brand/app-icon.png';
import { appName } from '@/lib/brand';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>,
) {
    return <img src={appIcon} alt={appName} {...props} />;
}
