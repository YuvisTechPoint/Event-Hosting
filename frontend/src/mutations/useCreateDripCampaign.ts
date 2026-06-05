import {useMutation, useQueryClient} from "@tanstack/react-query";
import {IdParam} from "../types.ts";
import {CreateDripCampaignRequest, dripCampaignClient} from "../api/dripCampaign.client.ts";
import {GET_DRIP_CAMPAIGNS_QUERY_KEY} from "../queries/useGetDripCampaigns.ts";

export const useCreateDripCampaign = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, data}: { eventId: IdParam; data: CreateDripCampaignRequest }) =>
            dripCampaignClient.create(eventId, data),
        onSuccess: () => queryClient.invalidateQueries({queryKey: [GET_DRIP_CAMPAIGNS_QUERY_KEY]}),
    });
};
