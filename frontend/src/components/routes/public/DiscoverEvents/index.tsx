import {Helmet} from "react-helmet-async";
import {t} from "@lingui/macro";
import {useGetBrowseEvents} from "../../../../queries/useGetBrowseEvents.ts";
import {Container, Grid, Select, Skeleton, Text, TextInput, Title} from "@mantine/core";
import {useState} from "react";
import {Link} from "react-router";
import {Card} from "../../../common/Card";
import {Pagination} from "../../../common/Pagination";
import {formatDateWithLocale} from "../../../../utilites/dates.ts";
import {eventHomepageUrl} from "../../../../utilites/urlHelper.ts";
import {IconSearch} from "@tabler/icons-react";
import {useDebouncedValue} from "@mantine/hooks";
import classes from "./DiscoverEvents.module.scss";

const CATEGORIES = [
    'MUSIC', 'BUSINESS', 'TECH', 'SPORTS', 'FESTIVAL', 'FOOD_DRINK', 'WORKSHOP', 'OTHER',
];

const DiscoverEvents = () => {
    const [query, setQuery] = useState('');
    const [debouncedQuery] = useDebouncedValue(query, 300);
    const [category, setCategory] = useState<string | null>(null);
    const [sort, setSort] = useState('start_date');
    const [page, setPage] = useState(1);

    const {data, isFetching} = useGetBrowseEvents({
        pageNumber: page,
        perPage: 12,
        query: debouncedQuery || undefined,
        category: category ?? undefined,
        sort,
    });

    const events = data?.data ?? [];
    const meta = data?.meta;

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        itemListElement: events.map((event, index) => ({
            '@type': 'ListItem',
            position: index + 1 + ((page - 1) * 12),
            url: typeof window !== 'undefined'
                ? `${window.location.origin}${eventHomepageUrl(event)}`
                : eventHomepageUrl(event),
            name: event.title,
        })),
    };

    return (
        <>
            <Helmet>
                <title>{t`Discover Events`} | Event Hosting</title>
                <meta name="description" content={t`Browse upcoming live events. Search by name, filter by category, and find your next experience.`}/>
                <meta property="og:title" content={t`Discover Events`}/>
                <meta property="og:type" content="website"/>
                {events.length > 0 && (
                    <script type="application/ld+json">{JSON.stringify(structuredData)}</script>
                )}
            </Helmet>

            <Container size="lg" py="xl">
                <Title order={1} mb="xs">{t`Discover Events`}</Title>
                <Text c="dimmed" mb="xl">{t`Find upcoming live events from organizers on Event Hosting.`}</Text>

                <Grid mb="lg">
                    <Grid.Col span={{base: 12, md: 6}}>
                        <TextInput
                            placeholder={t`Search events...`}
                            leftSection={<IconSearch size={16}/>}
                            value={query}
                            onChange={(e) => {
                                setPage(1);
                                setQuery(e.currentTarget.value);
                            }}
                        />
                    </Grid.Col>
                    <Grid.Col span={{base: 12, md: 3}}>
                        <Select
                            placeholder={t`Category`}
                            clearable
                            data={CATEGORIES.map(c => ({value: c, label: c.replace('_', ' ')}))}
                            value={category}
                            onChange={(v) => {
                                setPage(1);
                                setCategory(v);
                            }}
                        />
                    </Grid.Col>
                    <Grid.Col span={{base: 12, md: 3}}>
                        <Select
                            data={[
                                {value: 'start_date', label: t`Soonest first`},
                                {value: 'title', label: t`Name A–Z`},
                                {value: 'popularity', label: t`Most popular`},
                            ]}
                            value={sort}
                            onChange={(v) => v && setSort(v)}
                        />
                    </Grid.Col>
                </Grid>

                {isFetching && events.length === 0 ? (
                    <Skeleton height={200}/>
                ) : events.length === 0 ? (
                    <Card><Text>{t`No events found. Try adjusting your filters.`}</Text></Card>
                ) : (
                    <Grid>
                        {events.map((event) => (
                            <Grid.Col key={event.id} span={{base: 12, sm: 6, md: 4}}>
                                <Card className={classes.eventCard}>
                                    <Link to={eventHomepageUrl(event)} className={classes.eventLink}>
                                        <Text fw={600} lineClamp={2}>{event.title}</Text>
                                        {event.organizer?.name && (
                                            <Text size="sm" c="dimmed">{event.organizer.name}</Text>
                                        )}
                                        {event.start_date && (
                                            <Text size="sm" mt="xs">
                                                {formatDateWithLocale(event.start_date, 'long', event.timezone)}
                                            </Text>
                                        )}
                                        {event.category && (
                                            <Text size="xs" c="dimmed" mt="xs">{event.category}</Text>
                                        )}
                                    </Link>
                                </Card>
                            </Grid.Col>
                        ))}
                    </Grid>
                )}

                {meta && meta.last_page > 1 && (
                    <Pagination
                        total={meta.last_page}
                        value={page}
                        onChange={setPage}
                        mt="md"
                    />
                )}
            </Container>
        </>
    );
};

export default DiscoverEvents;
