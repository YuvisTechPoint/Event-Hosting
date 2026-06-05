import {useQuery} from "@tanstack/react-query";
import {hackathonClient} from "../api/hackathon.client.ts";
import {IdParam} from "../types.ts";

export const GET_HACKATHON_TEAMS_QUERY_KEY = "getHackathonTeams";

export const useGetHackathonTeams = (eventId: IdParam) => {
    return useQuery({
        queryKey: [GET_HACKATHON_TEAMS_QUERY_KEY, eventId],
        queryFn: () => hackathonClient.getTeams(eventId),
        enabled: !!eventId,
    });
};
