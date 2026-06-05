import {useMutation, useQueryClient} from '@tanstack/react-query';
import {AccountConfiguration, adminClient} from '../api/admin.client';
import {IdParam} from '../types';

export interface UpdateAccountConfigurationFeesData {
    fixed_fee: number;
    percentage_fee: number;
}

export const useUpdateAccountConfiguration = (
    accountId: IdParam,
    configuration: AccountConfiguration | undefined,
    currencyCode: string,
) => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (data: UpdateAccountConfigurationFeesData) => {
            if (!configuration) {
                throw new Error('Configuration is required');
            }

            return await adminClient.updateConfiguration(configuration.id, {
                name: configuration.name,
                application_fees: {
                    fixed: data.fixed_fee,
                    percentage: data.percentage_fee,
                    currency: currencyCode,
                },
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: ['admin', 'account', accountId],
            });
        },
    });
};
