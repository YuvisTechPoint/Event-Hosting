import {useMutation, useQueryClient} from "@tanstack/react-query";
import {CreateHackathonCriterionRequest, hackathonClient} from "../api/hackathon.client.ts";
import {IdParam} from "../types.ts";
import {GET_HACKATHON_CRITERIA_QUERY_KEY} from "../queries/useGetHackathonCriteria.ts";

export const useCreateHackathonCriterion = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, data}: {eventId: IdParam; data: CreateHackathonCriterionRequest}) =>
            hackathonClient.createCriterion(eventId, data),
        onSuccess: (_, variables) => {
            return queryClient.invalidateQueries({
                queryKey: [GET_HACKATHON_CRITERIA_QUERY_KEY, variables.eventId],
            });
        },
    });
};
