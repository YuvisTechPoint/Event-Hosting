import {Grid, Skeleton, Stack} from "@mantine/core";

interface PageSkeletonProps {
    itemCount?: number;
    showFilters?: boolean;
}

export const PageSkeleton = ({itemCount = 6, showFilters = true}: PageSkeletonProps) => {
    return (
        <Stack gap="lg">
            <Skeleton height={36} width="35%" radius="md"/>
            {showFilters && (
                <Grid>
                    <Grid.Col span={{base: 12, md: 6}}>
                        <Skeleton height={36} radius="md"/>
                    </Grid.Col>
                    <Grid.Col span={{base: 12, md: 3}}>
                        <Skeleton height={36} radius="md"/>
                    </Grid.Col>
                    <Grid.Col span={{base: 12, md: 3}}>
                        <Skeleton height={36} radius="md"/>
                    </Grid.Col>
                </Grid>
            )}
            <Grid>
                {Array.from({length: itemCount}).map((_, index) => (
                    <Grid.Col key={index} span={{base: 12, sm: 6, md: 4}}>
                        <Skeleton height={120} radius="md"/>
                    </Grid.Col>
                ))}
            </Grid>
        </Stack>
    );
};
