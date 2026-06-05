import {api} from "./client";
import {
    GenericDataResponse,
    GenericPaginatedResponse,
    HackathonJudgingCriterion,
    HackathonProject,
    HackathonScore,
    HackathonTeam,
    IdParam,
} from "../types.ts";

export interface CreateHackathonTeamRequest {
    name: string;
    description?: string;
    max_members?: number;
}

export interface CreateHackathonProjectRequest {
    team_id: number;
    title: string;
    description?: string;
    repository_url?: string;
    demo_url?: string;
    tech_stack?: string[];
}

export interface CreateHackathonCriterionRequest {
    name: string;
    description?: string;
    max_score?: number;
    weight?: number;
    judging_round_id?: number;
}

export interface UpsertHackathonScoreRequest {
    criteria_id: number;
    project_id: number;
    score: number;
    feedback?: string;
}

export const hackathonClient = {
    getTeams: async (eventId: IdParam) => {
        const response = await api.get<GenericPaginatedResponse<HackathonTeam>>(
            `events/${eventId}/hackathon/teams`,
        );
        return response.data;
    },

    createTeam: async (eventId: IdParam, data: CreateHackathonTeamRequest) => {
        const response = await api.post<GenericDataResponse<HackathonTeam>>(
            `events/${eventId}/hackathon/teams`,
            data,
        );
        return response.data;
    },

    getProjects: async (eventId: IdParam) => {
        const response = await api.get<GenericPaginatedResponse<HackathonProject>>(
            `events/${eventId}/hackathon/projects`,
        );
        return response.data;
    },

    createProject: async (eventId: IdParam, data: CreateHackathonProjectRequest) => {
        const response = await api.post<GenericDataResponse<HackathonProject>>(
            `events/${eventId}/hackathon/projects`,
            data,
        );
        return response.data;
    },

    submitProject: async (eventId: IdParam, projectId: IdParam) => {
        const response = await api.post<GenericDataResponse<HackathonProject>>(
            `events/${eventId}/hackathon/projects/${projectId}/submit`,
        );
        return response.data;
    },

    getCriteria: async (eventId: IdParam) => {
        const response = await api.get<GenericDataResponse<HackathonJudgingCriterion[]>>(
            `events/${eventId}/hackathon/criteria`,
        );
        return response.data;
    },

    createCriterion: async (eventId: IdParam, data: CreateHackathonCriterionRequest) => {
        const response = await api.post<GenericDataResponse<HackathonJudgingCriterion>>(
            `events/${eventId}/hackathon/criteria`,
            data,
        );
        return response.data;
    },

    upsertScore: async (eventId: IdParam, data: UpsertHackathonScoreRequest) => {
        const response = await api.put<GenericDataResponse<HackathonScore>>(
            `events/${eventId}/hackathon/scores`,
            data,
        );
        return response.data;
    },
};
