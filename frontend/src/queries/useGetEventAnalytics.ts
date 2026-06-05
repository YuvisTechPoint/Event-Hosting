import {useQuery} from "@tanstack/react-query";
import {IdParam} from "../types.ts";
import {eventsClient} from "../api/event.client.ts";

export const GET_EVENT_ANALYTICS_QUERY_KEY = 'getEventAnalytics';

export const useGetEventAnalytics = (eventId: IdParam, dateRange: string = 'month', enabled: boolean = true) => {
    return useQuery({
        queryKey: [GET_EVENT_ANALYTICS_QUERY_KEY, eventId, dateRange],
        queryFn: async () => {
            const {data} = await eventsClient.getEventAnalytics(eventId, dateRange);
            return data;
        },
        enabled,
    });
};
