import {useMutation, useQueryClient} from "@tanstack/react-query";
import {hackathonClient} from "../api/hackathon.client.ts";
import {IdParam} from "../types.ts";
import {GET_HACKATHON_PROJECTS_QUERY_KEY} from "../queries/useGetHackathonProjects.ts";

export const useSubmitHackathonProject = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, projectId}: {eventId: IdParam; projectId: IdParam}) =>
            hackathonClient.submitProject(eventId, projectId),
        onSuccess: (_, variables) => {
            return queryClient.invalidateQueries({
                queryKey: [GET_HACKATHON_PROJECTS_QUERY_KEY, variables.eventId],
            });
        },
    });
};
