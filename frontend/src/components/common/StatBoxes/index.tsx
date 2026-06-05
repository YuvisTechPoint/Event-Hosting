import classes from "./StatBoxes.module.scss";
import {IconCash, IconCreditCardRefund, IconEye, IconReceipt, IconShoppingCart, IconUsers} from "@tabler/icons-react";
import {Card} from "../Card";
import {useGetEventStats, GET_EVENT_STATS_QUERY_KEY} from "../../../queries/useGetEventStats.ts";
import {useParams} from "react-router";
import {t} from "@lingui/macro";
import {useGetEvent} from "../../../queries/useGetEvent.ts";
import {formatCurrency} from "../../../utilites/currency.ts";
import {formatNumber} from "../../../utilites/helpers.ts";
import {ReactNode, useState} from "react";
import {useRealtimeEventPrivateChannel} from "../../../hooks/useRealtimeChannels.ts";
import {useQueryClient} from "@tanstack/react-query";

interface StatBoxProps {
    number: string | number;
    description: string;
    icon: ReactNode;
    backgroundColor: string;
    live?: boolean;
}

export const StatBox = ({number, description, icon, backgroundColor, live}: StatBoxProps) => {
    return (
        <Card className={`${classes.statistic} ${live ? classes.live : ''}`}>
            <div className={classes.leftPanel}>
                <div className={classes.number}>{number}</div>
                <div className={classes.description}>
                    {description}
                    {live && <span className={classes.liveDot} title={t`Live`}/>}
                </div>
            </div>
            <div className={classes.rightPanel}>
                <div className={classes.icon} style={{backgroundColor}}>
                    {icon}
                </div>
            </div>
        </Card>
    );
};

export const StatBoxes = () => {
    const {eventId} = useParams();
    const queryClient = useQueryClient();
    const eventStatsQuery = useGetEventStats(eventId);
    const eventQuery = useGetEvent(eventId);
    const event = eventQuery?.data;
    const {data: eventStats} = eventStatsQuery;
    const [liveProductsSold, setLiveProductsSold] = useState(false);

    useRealtimeEventPrivateChannel({
        eventId,
        enabled: Boolean(eventId),
        events: {
            'ticket.sold': () => {
                setLiveProductsSold(true);
                queryClient.invalidateQueries({queryKey: [GET_EVENT_STATS_QUERY_KEY, eventId]});
            },
            'attendee.registered': () => {
                queryClient.invalidateQueries({queryKey: [GET_EVENT_STATS_QUERY_KEY, eventId]});
            },
        },
    });

    const data = [
        {
            number: formatNumber(eventStats?.total_attendees_registered as number),
            description: t`Attendees`,
            icon: <IconUsers size={18}/>,
            backgroundColor: '#E6677E'
        },
        {
            number: formatNumber(eventStats?.total_products_sold as number),
            description: t`Products sold`,
            icon: <IconShoppingCart size={18}/>,
            backgroundColor: '#4B7BE5',
            live: liveProductsSold,
        },
        {
            number: formatCurrency(eventStats?.total_refunded as number || 0, event?.currency),
            description: t`Refunded`,
            icon: <IconCreditCardRefund size={18}/>,
            backgroundColor: '#49A6B7'
        },
        {
            number: formatCurrency(eventStats?.total_gross_sales || 0, event?.currency),
            description: t`Gross sales`,
            icon: <IconCash size={18}/>,
            backgroundColor: '#7C63E6'
        },
        {
            number: formatNumber(eventStats?.total_views as number),
            description: t`Page views`,
            icon: <IconEye size={18}/>,
            backgroundColor: '#63B3A1'
        },
        {
            number: formatNumber(eventStats?.total_orders as number),
            description: t`Completed orders`,
            icon: <IconReceipt size={18}/>,
            backgroundColor: '#E67D49'
        }
    ];

    return (
        <div className={classes.statistics}>
            {data.map((stat) => (
                <StatBox
                    key={stat.description}
                    number={stat.number}
                    description={stat.description}
                    icon={stat.icon}
                    backgroundColor={stat.backgroundColor}
                    live={'live' in stat ? stat.live : false}
                />
            ))}
        </div>
    );
};
