import {useMutation, useQueryClient} from "@tanstack/react-query";
import {CreateHackathonTeamRequest, hackathonClient} from "../api/hackathon.client.ts";
import {IdParam} from "../types.ts";
import {GET_HACKATHON_TEAMS_QUERY_KEY} from "../queries/useGetHackathonTeams.ts";

export const useCreateHackathonTeam = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, data}: {eventId: IdParam; data: CreateHackathonTeamRequest}) =>
            hackathonClient.createTeam(eventId, data),
        onSuccess: (_, variables) => {
            return queryClient.invalidateQueries({
                queryKey: [GET_HACKATHON_TEAMS_QUERY_KEY, variables.eventId],
            });
        },
    });
};
