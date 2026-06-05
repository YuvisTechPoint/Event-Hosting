import {useQuery} from "@tanstack/react-query";
import {QueryFilters} from "../types.ts";
import {eventsClientPublic} from "../api/event.client.ts";

export const GET_BROWSE_EVENTS_QUERY_KEY = 'getBrowseEvents';

export interface BrowseEventsFilters extends QueryFilters {
    category?: string;
    sort?: string;
    start_date_from?: string;
    start_date_to?: string;
}

export const useGetBrowseEvents = (filters: BrowseEventsFilters) => {
    return useQuery({
        queryKey: [GET_BROWSE_EVENTS_QUERY_KEY, filters],
        queryFn: async () => eventsClientPublic.browse(filters),
    });
};
