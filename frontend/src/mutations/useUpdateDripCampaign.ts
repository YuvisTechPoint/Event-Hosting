import {useMutation, useQueryClient} from "@tanstack/react-query";
import {IdParam} from "../types.ts";
import {dripCampaignClient, UpdateDripCampaignRequest} from "../api/dripCampaign.client.ts";
import {GET_DRIP_CAMPAIGNS_QUERY_KEY} from "../queries/useGetDripCampaigns.ts";

export const useUpdateDripCampaign = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({
            eventId,
            campaignId,
            data,
        }: {
            eventId: IdParam;
            campaignId: IdParam;
            data: UpdateDripCampaignRequest;
        }) => dripCampaignClient.update(eventId, campaignId, data),
        onSuccess: () => queryClient.invalidateQueries({queryKey: [GET_DRIP_CAMPAIGNS_QUERY_KEY]}),
    });
};
