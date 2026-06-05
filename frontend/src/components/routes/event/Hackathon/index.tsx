import {PageBody} from "../../../common/PageBody";
import {PageTitle} from "../../../common/PageTitle";
import {t} from "@lingui/macro";
import {useParams} from "react-router";
import {
    Badge,
    Button,
    Group,
    NumberInput,
    Select,
    Stack,
    Tabs,
    Text,
    Textarea,
    TextInput,
} from "@mantine/core";
import {useForm} from "@mantine/form";
import {Card} from "../../../common/Card";
import {TableSkeleton} from "../../../common/TableSkeleton";
import {useGetHackathonTeams} from "../../../../queries/useGetHackathonTeams.ts";
import {useGetHackathonProjects} from "../../../../queries/useGetHackathonProjects.ts";
import {useGetHackathonCriteria} from "../../../../queries/useGetHackathonCriteria.ts";
import {useCreateHackathonTeam} from "../../../../mutations/useCreateHackathonTeam.ts";
import {useCreateHackathonProject} from "../../../../mutations/useCreateHackathonProject.ts";
import {useSubmitHackathonProject} from "../../../../mutations/useSubmitHackathonProject.ts";
import {useCreateHackathonCriterion} from "../../../../mutations/useCreateHackathonCriterion.ts";
import {useUpsertHackathonScore} from "../../../../mutations/useUpsertHackathonScore.ts";
import {showError, showSuccess} from "../../../../utilites/notifications.tsx";
import {useState} from "react";

const Hackathon = () => {
    const {eventId} = useParams();
    const {data: teamsData, isLoading: teamsLoading} = useGetHackathonTeams(eventId);
    const {data: projectsData, isLoading: projectsLoading} = useGetHackathonProjects(eventId);
    const {data: criteriaData, isLoading: criteriaLoading} = useGetHackathonCriteria(eventId);

    const createTeamMutation = useCreateHackathonTeam();
    const createProjectMutation = useCreateHackathonProject();
    const submitProjectMutation = useSubmitHackathonProject();
    const createCriterionMutation = useCreateHackathonCriterion();
    const upsertScoreMutation = useUpsertHackathonScore();

    const teams = teamsData?.data ?? [];
    const projects = projectsData?.data ?? [];
    const criteria = criteriaData?.data ?? [];

    const teamForm = useForm({
        initialValues: {
            name: "",
            description: "",
            max_members: 4,
        },
    });

    const projectForm = useForm({
        initialValues: {
            team_id: "",
            title: "",
            description: "",
            repository_url: "",
            demo_url: "",
        },
    });

    const criterionForm = useForm({
        initialValues: {
            name: "",
            description: "",
            max_score: 10,
            weight: 1,
        },
    });

    const [scoreForm, setScoreForm] = useState({
        criteria_id: "",
        project_id: "",
        score: 0,
        feedback: "",
    });

    const handleCreateTeam = teamForm.onSubmit((values) => {
        createTeamMutation.mutate({
            eventId,
            data: {
                name: values.name,
                description: values.description || undefined,
                max_members: values.max_members,
            },
        }, {
            onSuccess: () => {
                showSuccess(t`Team created`);
                teamForm.reset();
            },
            onError: (error: any) => showError(error?.response?.data?.message || t`Failed to create team`),
        });
    });

    const handleCreateProject = projectForm.onSubmit((values) => {
        createProjectMutation.mutate({
            eventId,
            data: {
                team_id: Number(values.team_id),
                title: values.title,
                description: values.description || undefined,
                repository_url: values.repository_url || undefined,
                demo_url: values.demo_url || undefined,
            },
        }, {
            onSuccess: () => {
                showSuccess(t`Project created`);
                projectForm.reset();
            },
            onError: (error: any) => showError(error?.response?.data?.message || t`Failed to create project`),
        });
    });

    const handleCreateCriterion = criterionForm.onSubmit((values) => {
        createCriterionMutation.mutate({
            eventId,
            data: {
                name: values.name,
                description: values.description || undefined,
                max_score: values.max_score,
                weight: values.weight,
            },
        }, {
            onSuccess: () => {
                showSuccess(t`Criterion created`);
                criterionForm.reset();
            },
            onError: (error: any) => showError(error?.response?.data?.message || t`Failed to create criterion`),
        });
    });

    const handleSubmitScore = () => {
        if (!scoreForm.criteria_id || !scoreForm.project_id) {
            showError(t`Select a project and criterion`);
            return;
        }

        upsertScoreMutation.mutate({
            eventId,
            data: {
                criteria_id: Number(scoreForm.criteria_id),
                project_id: Number(scoreForm.project_id),
                score: scoreForm.score,
                feedback: scoreForm.feedback || undefined,
            },
        }, {
            onSuccess: () => {
                showSuccess(t`Score saved`);
                setScoreForm({criteria_id: "", project_id: "", score: 0, feedback: ""});
            },
            onError: (error: any) => showError(error?.response?.data?.message || t`Failed to save score`),
        });
    };

    return (
        <PageBody>
            <PageTitle subheading={t`Manage hackathon teams, projects, and judging.`}>
                {t`Hackathon`}
            </PageTitle>

            <Tabs defaultValue="teams">
                <Tabs.List mb="md">
                    <Tabs.Tab value="teams">{t`Teams`}</Tabs.Tab>
                    <Tabs.Tab value="projects">{t`Projects`}</Tabs.Tab>
                    <Tabs.Tab value="judging">{t`Judging`}</Tabs.Tab>
                </Tabs.List>

                <Tabs.Panel value="teams">
                    <Stack gap="md">
                        <Card>
                            <form onSubmit={handleCreateTeam}>
                                <Stack gap="sm">
                                    <Text fw={600}>{t`Create team`}</Text>
                                    <TextInput
                                        label={t`Name`}
                                        required
                                        {...teamForm.getInputProps("name")}
                                    />
                                    <Textarea
                                        label={t`Description`}
                                        {...teamForm.getInputProps("description")}
                                    />
                                    <NumberInput
                                        label={t`Max members`}
                                        min={1}
                                        max={20}
                                        {...teamForm.getInputProps("max_members")}
                                    />
                                    <Group justify="flex-end">
                                        <Button type="submit" loading={createTeamMutation.isPending}>
                                            {t`Create team`}
                                        </Button>
                                    </Group>
                                </Stack>
                            </form>
                        </Card>

                        <TableSkeleton isVisible={teamsLoading && teams.length === 0}/>

                        {teams.map((team) => (
                            <Card key={team.id}>
                                <Group justify="space-between" mb="xs">
                                    <Text fw={600}>{team.name}</Text>
                                    <Badge variant="light">{team.status}</Badge>
                                </Group>
                                {team.description && <Text size="sm" c="dimmed">{team.description}</Text>}
                                <Text size="xs" c="dimmed" mt="xs">
                                    {t`Invite code`}: {team.invite_code} · {t`Max members`}: {team.max_members}
                                </Text>
                            </Card>
                        ))}
                    </Stack>
                </Tabs.Panel>

                <Tabs.Panel value="projects">
                    <Stack gap="md">
                        <Card>
                            <form onSubmit={handleCreateProject}>
                                <Stack gap="sm">
                                    <Text fw={600}>{t`Create project`}</Text>
                                    <Select
                                        label={t`Team`}
                                        required
                                        data={teams.map((team) => ({
                                            value: String(team.id),
                                            label: team.name,
                                        }))}
                                        {...projectForm.getInputProps("team_id")}
                                    />
                                    <TextInput
                                        label={t`Title`}
                                        required
                                        {...projectForm.getInputProps("title")}
                                    />
                                    <Textarea
                                        label={t`Description`}
                                        {...projectForm.getInputProps("description")}
                                    />
                                    <TextInput
                                        label={t`Repository URL`}
                                        {...projectForm.getInputProps("repository_url")}
                                    />
                                    <TextInput
                                        label={t`Demo URL`}
                                        {...projectForm.getInputProps("demo_url")}
                                    />
                                    <Group justify="flex-end">
                                        <Button type="submit" loading={createProjectMutation.isPending}>
                                            {t`Create project`}
                                        </Button>
                                    </Group>
                                </Stack>
                            </form>
                        </Card>

                        <TableSkeleton isVisible={projectsLoading && projects.length === 0}/>

                        {projects.map((project) => (
                            <Card key={project.id}>
                                <Group justify="space-between" mb="xs">
                                    <Text fw={600}>{project.title}</Text>
                                    <Badge variant="light">{project.status}</Badge>
                                </Group>
                                {project.description && <Text size="sm" c="dimmed">{project.description}</Text>}
                                <Group mt="sm">
                                    {project.status === "DRAFT" && (
                                        <Button
                                            size="xs"
                                            variant="light"
                                            loading={submitProjectMutation.isPending}
                                            onClick={() => submitProjectMutation.mutate({
                                                eventId,
                                                projectId: project.id,
                                            }, {
                                                onSuccess: () => showSuccess(t`Project submitted`),
                                                onError: (error: any) => showError(
                                                    error?.response?.data?.message || t`Failed to submit project`,
                                                ),
                                            })}
                                        >
                                            {t`Submit project`}
                                        </Button>
                                    )}
                                </Group>
                            </Card>
                        ))}
                    </Stack>
                </Tabs.Panel>

                <Tabs.Panel value="judging">
                    <Stack gap="md">
                        <Card>
                            <form onSubmit={handleCreateCriterion}>
                                <Stack gap="sm">
                                    <Text fw={600}>{t`Create criterion`}</Text>
                                    <TextInput
                                        label={t`Name`}
                                        required
                                        {...criterionForm.getInputProps("name")}
                                    />
                                    <Textarea
                                        label={t`Description`}
                                        {...criterionForm.getInputProps("description")}
                                    />
                                    <Group grow>
                                        <NumberInput
                                            label={t`Max score`}
                                            min={1}
                                            {...criterionForm.getInputProps("max_score")}
                                        />
                                        <NumberInput
                                            label={t`Weight`}
                                            min={1}
                                            {...criterionForm.getInputProps("weight")}
                                        />
                                    </Group>
                                    <Group justify="flex-end">
                                        <Button type="submit" loading={createCriterionMutation.isPending}>
                                            {t`Create criterion`}
                                        </Button>
                                    </Group>
                                </Stack>
                            </form>
                        </Card>

                        <TableSkeleton isVisible={criteriaLoading && criteria.length === 0}/>

                        {criteria.map((criterion) => (
                            <Card key={criterion.id}>
                                <Group justify="space-between" mb="xs">
                                    <Text fw={600}>{criterion.name}</Text>
                                    <Text size="sm" c="dimmed">
                                        {t`Max`}: {criterion.max_score} · {t`Weight`}: {criterion.weight}
                                    </Text>
                                </Group>
                                {criterion.description && (
                                    <Text size="sm" c="dimmed">{criterion.description}</Text>
                                )}
                            </Card>
                        ))}

                        <Card>
                            <Stack gap="sm">
                                <Text fw={600}>{t`Score a project`}</Text>
                                <Select
                                    label={t`Project`}
                                    data={projects.map((project) => ({
                                        value: String(project.id),
                                        label: project.title,
                                    }))}
                                    value={scoreForm.project_id}
                                    onChange={(value) => setScoreForm((prev) => ({
                                        ...prev,
                                        project_id: value ?? "",
                                    }))}
                                />
                                <Select
                                    label={t`Criterion`}
                                    data={criteria.map((criterion) => ({
                                        value: String(criterion.id),
                                        label: criterion.name,
                                    }))}
                                    value={scoreForm.criteria_id}
                                    onChange={(value) => setScoreForm((prev) => ({
                                        ...prev,
                                        criteria_id: value ?? "",
                                    }))}
                                />
                                <NumberInput
                                    label={t`Score`}
                                    value={scoreForm.score}
                                    onChange={(value) => setScoreForm((prev) => ({
                                        ...prev,
                                        score: Number(value) || 0,
                                    }))}
                                />
                                <Textarea
                                    label={t`Feedback`}
                                    value={scoreForm.feedback}
                                    onChange={(event) => setScoreForm((prev) => ({
                                        ...prev,
                                        feedback: event.currentTarget.value,
                                    }))}
                                />
                                <Group justify="flex-end">
                                    <Button
                                        onClick={handleSubmitScore}
                                        loading={upsertScoreMutation.isPending}
                                    >
                                        {t`Save score`}
                                    </Button>
                                </Group>
                            </Stack>
                        </Card>
                    </Stack>
                </Tabs.Panel>
            </Tabs>
        </PageBody>
    );
};

export default Hackathon;
