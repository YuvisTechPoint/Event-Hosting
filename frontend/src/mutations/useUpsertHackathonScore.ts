import {useMutation} from "@tanstack/react-query";
import {hackathonClient, UpsertHackathonScoreRequest} from "../api/hackathon.client.ts";
import {IdParam} from "../types.ts";

export const useUpsertHackathonScore = () => {
    return useMutation({
        mutationFn: ({eventId, data}: {eventId: IdParam; data: UpsertHackathonScoreRequest}) =>
            hackathonClient.upsertScore(eventId, data),
    });
};
