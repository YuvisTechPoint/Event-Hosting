import {Alert} from '@mantine/core';
import {IconPlugConnectedX} from '@tabler/icons-react';
import {t} from '@lingui/macro';
import {useEffect, useState} from 'react';
import {getConfig} from '../../../utilites/config.ts';

/**
 * Warns on auth pages when /api/health is unreachable (typical Vercel misconfiguration).
 */
export const AuthApiStatusBanner = () => {
    const [misconfigured, setMisconfigured] = useState(false);

    useEffect(() => {
        let cancelled = false;

        const check = async () => {
            try {
                const base = getConfig('VITE_API_URL_CLIENT', '/api') ?? '/api';
                const url = `${base.replace(/\/$/, '')}/health`;
                const response = await fetch(url, {credentials: 'include'});
                if (!cancelled && !response.ok) {
                    setMisconfigured(true);
                }
            } catch {
                if (!cancelled) {
                    setMisconfigured(true);
                }
            }
        };

        void check();
        return () => {
            cancelled = true;
        };
    }, []);

    if (!misconfigured) {
        return null;
    }

    return (
        <Alert
            icon={<IconPlugConnectedX size={16}/>}
            color="red"
            variant="light"
            mb="md"
            title={t`API not connected`}
        >
            {t`Login and registration require a Laravel backend. Set BACKEND_URL in Vercel to your deployed API URL (e.g. Railway or Render), then redeploy. See VERCEL.md in the repo.`}
        </Alert>
    );
};
