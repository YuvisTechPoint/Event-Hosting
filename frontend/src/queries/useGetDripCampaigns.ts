import {useQuery} from "@tanstack/react-query";
import {IdParam, QueryFilters} from "../types.ts";
import {dripCampaignClient} from "../api/dripCampaign.client.ts";

export const GET_DRIP_CAMPAIGNS_QUERY_KEY = 'drip-campaigns';

export const useGetDripCampaigns = (eventId: IdParam | undefined, filters: QueryFilters = {}) => {
    return useQuery({
        queryKey: [GET_DRIP_CAMPAIGNS_QUERY_KEY, eventId, filters],
        queryFn: () => dripCampaignClient.list(eventId!, filters),
        enabled: !!eventId,
    });
};
