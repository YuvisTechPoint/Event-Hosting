import {useParams} from "react-router";
import {useGetEvent} from "../../../../queries/useGetEvent.ts";
import {useGetEventAnalytics} from "../../../../queries/useGetEventAnalytics.ts";
import {PageTitle} from "../../../common/PageTitle";
import {PageBody} from "../../../common/PageBody";
import {t} from "@lingui/macro";
import {AreaChart, BarChart, DonutChart} from "@mantine/charts";
import {Card} from "../../../common/Card";
import {formatCurrency} from "../../../../utilites/currency.ts";
import {formatDateWithLocale} from "../../../../utilites/dates.ts";
import {Grid, SegmentedControl, SimpleGrid, Skeleton, Text, Tooltip} from "@mantine/core";
import {useState} from "react";
import {useRealtimeEventPrivateChannel} from "../../../../hooks/useRealtimeChannels.ts";
import {useQueryClient} from "@tanstack/react-query";
import {GET_EVENT_ANALYTICS_QUERY_KEY} from "../../../../queries/useGetEventAnalytics.ts";
import classes from "../EventDashboard/EventDashboard.module.scss";

const EventAnalytics = () => {
    const {eventId} = useParams();
    const queryClient = useQueryClient();
    const {data: event} = useGetEvent(eventId);
    const [dateRange, setDateRange] = useState('month');
    const {data: analytics, isFetching} = useGetEventAnalytics(eventId, dateRange);

    useRealtimeEventPrivateChannel({
        eventId,
        enabled: Boolean(eventId),
        events: {
            'order.completed': () => {
                queryClient.invalidateQueries({queryKey: [GET_EVENT_ANALYTICS_QUERY_KEY, eventId]});
            },
            'attendee.checked_in': () => {
                queryClient.invalidateQueries({queryKey: [GET_EVENT_ANALYTICS_QUERY_KEY, eventId]});
            },
        },
    });

    if (!event) {
        return null;
    }

    const funnel = analytics?.conversion_funnel;
    const hourlyData = Array.from({length: 24}, (_, hour) => {
        const match = analytics?.hourly_sales.find(h => h.hour === hour);
        return {
            hour: `${hour}:00`,
            sales: match?.sales ?? 0,
        };
    });

    return (
        <PageBody>
            <PageTitle subheading={t`Advanced metrics, conversion funnel, and sales breakdown`}>
                {t`Analytics`}
            </PageTitle>

            <div className={classes.dateRangeSelector}>
                <SegmentedControl
                    value={dateRange}
                    onChange={setDateRange}
                    data={[
                        {label: t`Week`, value: 'week'},
                        {label: t`Month`, value: 'month'},
                        {label: t`Quarter`, value: 'quarter'},
                        {label: t`Event`, value: 'event'},
                    ]}
                    size="sm"
                />
            </div>

            {isFetching && !analytics ? (
                <Skeleton height={400} mt="md"/>
            ) : (
                <>
                    <SimpleGrid cols={{base: 1, sm: 2, lg: 4}} mt="md">
                        <Card>
                            <Text size="sm" c="dimmed">{t`Conversion rate`}</Text>
                            <Text size="xl" fw={700}>{funnel?.conversion_rate ?? 0}%</Text>
                        </Card>
                        <Card>
                            <Text size="sm" c="dimmed">{t`Refund rate`}</Text>
                            <Text size="xl" fw={700}>{analytics?.refund_summary.refund_rate ?? 0}%</Text>
                        </Card>
                        <Card>
                            <Text size="sm" c="dimmed">{t`Repeat attendees`}</Text>
                            <Text size="xl" fw={700}>{analytics?.repeat_attendees.repeat_percentage ?? 0}%</Text>
                        </Card>
                        <Card>
                            <Text size="sm" c="dimmed">{t`Refunded amount`}</Text>
                            <Text size="xl" fw={700}>
                                {formatCurrency(analytics?.refund_summary.refunded_amount ?? 0, event.currency)}
                            </Text>
                        </Card>
                    </SimpleGrid>

                    <Card className={classes.chartCard}>
                        <h2>{t`Revenue over time`}</h2>
                        <AreaChart
                            h={280}
                            data={(analytics?.revenue_over_time ?? []).map(row => ({
                                date: formatDateWithLocale(row.date, 'chartDate', event.timezone),
                                revenue: row.revenue,
                            }))}
                            dataKey="date"
                            series={[{name: 'revenue', color: 'grape.5', label: t`Revenue`}]}
                            valueFormatter={(v) => formatCurrency(v, event.currency)}
                            curveType="natural"
                        />
                    </Card>

                    <Grid mt="md">
                        <Grid.Col span={{base: 12, md: 6}}>
                            <Card className={classes.chartCard}>
                                <h2>{t`Tickets sold by type`}</h2>
                                <DonutChart
                                    data={(analytics?.tickets_by_type ?? []).map(row => ({
                                        name: row.name,
                                        value: row.sold,
                                    }))}
                                    withLabelsLine
                                    withLabels
                                />
                            </Card>
                        </Grid.Col>
                        <Grid.Col span={{base: 12, md: 6}}>
                            <Card className={classes.chartCard}>
                                <h2>{t`Attendee locales`}</h2>
                                <DonutChart
                                    data={(analytics?.geographic_distribution ?? []).map(row => ({
                                        name: row.label,
                                        value: row.count,
                                    }))}
                                    withLabelsLine
                                    withLabels
                                />
                            </Card>
                        </Grid.Col>
                    </Grid>

                    <Card className={classes.chartCard}>
                        <h2>{t`Check-ins by day`}</h2>
                        <BarChart
                            h={280}
                            data={(analytics?.check_in_rate_over_time ?? []).map(row => ({
                                date: formatDateWithLocale(row.date, 'chartDate', event.timezone),
                                checked_in: row.checked_in,
                            }))}
                            dataKey="date"
                            series={[{name: 'checked_in', color: 'teal.6', label: t`Checked in`}]}
                        />
                    </Card>

                    <Card className={classes.chartCard}>
                        <h2>{t`Hourly sales`}</h2>
                        <BarChart
                            h={280}
                            data={hourlyData}
                            dataKey="hour"
                            series={[{name: 'sales', color: 'blue.6', label: t`Orders`}]}
                        />
                    </Card>

                    <Card className={classes.chartCard}>
                        <h2>{t`Conversion funnel`}</h2>
                        <SimpleGrid cols={{base: 1, sm: 3}}>
                            <Tooltip label={t`Event page views`}>
                                <div>
                                    <Text size="sm" c="dimmed">{t`Page views`}</Text>
                                    <Text size="lg" fw={600}>{funnel?.page_views ?? 0}</Text>
                                </div>
                            </Tooltip>
                            <Tooltip label={t`Orders started (incl. abandoned)`}>
                                <div>
                                    <Text size="sm" c="dimmed">{t`Started checkout`}</Text>
                                    <Text size="lg" fw={600}>{funnel?.started_checkout ?? 0}</Text>
                                </div>
                            </Tooltip>
                            <Tooltip label={t`Completed orders`}>
                                <div>
                                    <Text size="sm" c="dimmed">{t`Completed`}</Text>
                                    <Text size="lg" fw={600}>{funnel?.completed ?? 0}</Text>
                                </div>
                            </Tooltip>
                        </SimpleGrid>
                    </Card>
                </>
            )}
        </PageBody>
    );
};

export default EventAnalytics;
