import {useMutation, useQueryClient} from "@tanstack/react-query";
import {IdParam} from "../types.ts";
import {dripCampaignClient} from "../api/dripCampaign.client.ts";
import {GET_DRIP_CAMPAIGNS_QUERY_KEY} from "../queries/useGetDripCampaigns.ts";

export const useDeleteDripCampaign = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, campaignId}: { eventId: IdParam; campaignId: IdParam }) =>
            dripCampaignClient.delete(eventId, campaignId),
        onSuccess: () => queryClient.invalidateQueries({queryKey: [GET_DRIP_CAMPAIGNS_QUERY_KEY]}),
    });
};
