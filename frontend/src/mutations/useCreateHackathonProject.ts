import {useMutation, useQueryClient} from "@tanstack/react-query";
import {CreateHackathonProjectRequest, hackathonClient} from "../api/hackathon.client.ts";
import {IdParam} from "../types.ts";
import {GET_HACKATHON_PROJECTS_QUERY_KEY} from "../queries/useGetHackathonProjects.ts";

export const useCreateHackathonProject = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, data}: {eventId: IdParam; data: CreateHackathonProjectRequest}) =>
            hackathonClient.createProject(eventId, data),
        onSuccess: (_, variables) => {
            return queryClient.invalidateQueries({
                queryKey: [GET_HACKATHON_PROJECTS_QUERY_KEY, variables.eventId],
            });
        },
    });
};
