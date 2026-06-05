import {useQuery} from "@tanstack/react-query";
import {hackathonClient} from "../api/hackathon.client.ts";
import {IdParam} from "../types.ts";

export const GET_HACKATHON_CRITERIA_QUERY_KEY = "getHackathonCriteria";

export const useGetHackathonCriteria = (eventId: IdParam) => {
    return useQuery({
        queryKey: [GET_HACKATHON_CRITERIA_QUERY_KEY, eventId],
        queryFn: () => hackathonClient.getCriteria(eventId),
        enabled: !!eventId,
    });
};
