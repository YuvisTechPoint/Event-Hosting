import {useQuery} from "@tanstack/react-query";
import {hackathonClient} from "../api/hackathon.client.ts";
import {IdParam} from "../types.ts";

export const GET_HACKATHON_PROJECTS_QUERY_KEY = "getHackathonProjects";

export const useGetHackathonProjects = (eventId: IdParam) => {
    return useQuery({
        queryKey: [GET_HACKATHON_PROJECTS_QUERY_KEY, eventId],
        queryFn: () => hackathonClient.getProjects(eventId),
        enabled: !!eventId,
    });
};
