import {api} from "./client";
import {GenericDataResponse, GenericPaginatedResponse, IdParam, QueryFilters} from "../types";
import {queryParamsHelper} from "../utilites/queryParamsHelper.ts";

export interface DripCampaignStep {
    id: number;
    drip_campaign_id: number;
    step_order: number;
    delay_hours: number;
    email_template_id?: number | null;
    subject?: string | null;
    body?: string | null;
    message_segment_id?: number | null;
}

export interface DripCampaign {
    id: number;
    event_id: number;
    name: string;
    trigger: 'on_registration' | 'scheduled';
    status: 'draft' | 'active' | 'paused' | 'archived';
    organizer_id: number;
    scheduled_at?: string | null;
    steps?: DripCampaignStep[];
    created_at: string;
    updated_at: string;
}

export interface MessageSegment {
    id: number;
    event_id: number;
    name: string;
    rules: {
        ticket_type_ids?: number[];
        check_in_status?: 'checked_in' | 'not_checked_in' | null;
        tags?: string[];
        registration_status?: string[];
    };
    created_at: string;
    updated_at: string;
}

export interface CreateDripCampaignRequest {
    name: string;
    trigger?: 'on_registration' | 'scheduled';
    status?: 'draft' | 'active' | 'paused' | 'archived';
    use_default_template?: boolean;
}

export interface UpdateDripCampaignRequest {
    name?: string;
    trigger?: 'on_registration' | 'scheduled';
    status?: 'draft' | 'active' | 'paused' | 'archived';
}

export interface CreateMessageSegmentRequest {
    name: string;
    rules?: MessageSegment['rules'];
}

export const dripCampaignClient = {
    list: async (eventId: IdParam, pagination: QueryFilters = {}) => {
        const response = await api.get<GenericPaginatedResponse<DripCampaign>>(
            `events/${eventId}/drip-campaigns` + queryParamsHelper.buildQueryString(pagination),
        );
        return response.data;
    },
    create: async (eventId: IdParam, data: CreateDripCampaignRequest) => {
        const response = await api.post<GenericDataResponse<DripCampaign>>(
            `events/${eventId}/drip-campaigns`,
            {
                trigger: 'on_registration',
                use_default_template: true,
                ...data,
            },
        );
        return response.data;
    },
    update: async (eventId: IdParam, campaignId: IdParam, data: UpdateDripCampaignRequest) => {
        const response = await api.put<GenericDataResponse<DripCampaign>>(
            `events/${eventId}/drip-campaigns/${campaignId}`,
            data,
        );
        return response.data;
    },
    delete: async (eventId: IdParam, campaignId: IdParam) => {
        await api.delete(`events/${eventId}/drip-campaigns/${campaignId}`);
    },
    testSend: async (eventId: IdParam, campaignId: IdParam, attendeeId: IdParam, stepOrder?: number) => {
        const response = await api.post<GenericDataResponse<{ message: string }>>(
            `events/${eventId}/drip-campaigns/${campaignId}/test-send`,
            {attendee_id: attendeeId, step_order: stepOrder},
        );
        return response.data;
    },
    listSegments: async (eventId: IdParam, pagination: QueryFilters = {}) => {
        const response = await api.get<GenericPaginatedResponse<MessageSegment>>(
            `events/${eventId}/message-segments` + queryParamsHelper.buildQueryString(pagination),
        );
        return response.data;
    },
    createSegment: async (eventId: IdParam, data: CreateMessageSegmentRequest) => {
        const response = await api.post<GenericDataResponse<MessageSegment>>(
            `events/${eventId}/message-segments`,
            data,
        );
        return response.data;
    },
};
