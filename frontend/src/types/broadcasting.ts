export interface AttendeeCheckedInPayload {
    attendee_id: number;
    attendee_public_id: string;
    attendee_name: string;
    ticket_type: string;
    checked_in_by: string | null;
    checked_in_at: string;
    event_id: number;
    total_checked_in: number;
    total_capacity: number | null;
}

export interface TicketSoldPayload {
    event_id: number;
    product_id: number;
    product_title: string;
    quantity_sold: number;
    remaining_capacity: number;
    is_unlimited: boolean;
    total_event_sold: number;
    total_event_capacity: number | null;
}

export interface AttendeeRegisteredPayload {
    attendee_id: number;
    attendee_name: string;
    ticket_type: string;
    registered_at: string;
    event_id: number;
}

export interface CapacityWarningPayload {
    event_id: number;
    level: 'warning' | 'sold_out';
    total_sold: number;
    total_capacity: number;
    percent_full: number;
}

export interface OrganizerNotificationPayload {
    event_id: number;
    type: string;
    title: string;
    message: string;
    created_at: string;
    metadata: Record<string, unknown>;
}

export type BroadcastEventName =
    | 'attendee.checked-in'
    | 'ticket.sold'
    | 'attendee.registered'
    | 'capacity.warning'
    | 'capacity.sold-out'
    | 'notification.new';
