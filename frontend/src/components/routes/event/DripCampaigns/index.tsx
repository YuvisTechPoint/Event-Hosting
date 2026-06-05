import {PageBody} from "../../../common/PageBody";
import {PageTitle} from "../../../common/PageTitle";
import {t} from "@lingui/macro";
import {useParams} from "react-router";
import {useDisclosure} from "@mantine/hooks";
import {ToolBar} from "../../../common/ToolBar";
import {Badge, Button, Group, Modal, Stack, Switch, Table, Text, TextInput} from "@mantine/core";
import {IconPlus, IconTrash} from "@tabler/icons-react";
import {useGetDripCampaigns} from "../../../../queries/useGetDripCampaigns.ts";
import {TableSkeleton} from "../../../common/TableSkeleton";
import {NoResultsSplash} from "../../../common/NoResultsSplash";
import {useForm} from "@mantine/form";
import {useCreateDripCampaign} from "../../../../mutations/useCreateDripCampaign.ts";
import {useUpdateDripCampaign} from "../../../../mutations/useUpdateDripCampaign.ts";
import {useDeleteDripCampaign} from "../../../../mutations/useDeleteDripCampaign.ts";
import {showError, showSuccess} from "../../../../utilites/notifications.tsx";
import {DripCampaign} from "../../../../api/dripCampaign.client.ts";

const statusColor = (status: DripCampaign['status']) => {
    switch (status) {
        case 'active':
            return 'green';
        case 'paused':
            return 'yellow';
        case 'archived':
            return 'gray';
        default:
            return 'blue';
    }
};

const DripCampaigns = () => {
    const {eventId} = useParams();
    const {data: campaignsData, isLoading} = useGetDripCampaigns(eventId);
    const campaigns = campaignsData?.data ?? [];
    const [createOpen, {open: openCreate, close: closeCreate}] = useDisclosure(false);
    const createMutation = useCreateDripCampaign();
    const updateMutation = useUpdateDripCampaign();
    const deleteMutation = useDeleteDripCampaign();

    const form = useForm({
        initialValues: {
            name: '',
            activate: true,
        },
        validate: {
            name: (value) => (!value ? t`Name is required` : null),
        },
    });

    const handleCreate = form.onSubmit((values) => {
        createMutation.mutate({
            eventId: eventId!,
            data: {
                name: values.name,
                trigger: 'on_registration',
                status: values.activate ? 'active' : 'draft',
                use_default_template: true,
            },
        }, {
            onSuccess: () => {
                showSuccess(t`Drip campaign created with confirm, reminder, and thank-you steps`);
                form.reset();
                closeCreate();
            },
            onError: () => showError(t`Failed to create drip campaign`),
        });
    });

    const toggleActive = (campaign: DripCampaign) => {
        const newStatus = campaign.status === 'active' ? 'paused' : 'active';
        updateMutation.mutate({
            eventId: eventId!,
            campaignId: campaign.id,
            data: {
                name: campaign.name,
                trigger: campaign.trigger,
                status: newStatus,
            },
        }, {
            onSuccess: () => showSuccess(newStatus === 'active' ? t`Campaign activated` : t`Campaign paused`),
            onError: () => showError(t`Failed to update campaign`),
        });
    };

    const handleDelete = (campaign: DripCampaign) => {
        if (!window.confirm(t`Delete this drip campaign?`)) {
            return;
        }
        deleteMutation.mutate({
            eventId: eventId!,
            campaignId: campaign.id,
        }, {
            onSuccess: () => showSuccess(t`Campaign deleted`),
            onError: () => showError(t`Failed to delete campaign`),
        });
    };

    return (
        <PageBody>
            <PageTitle
                subheading={t`Automated email sequences triggered when attendees register. Uses Event Hosting messaging infrastructure.`}
            >
                {t`Drip Campaigns`}
            </PageTitle>

            <ToolBar>
                <Button leftSection={<IconPlus size={16}/>} color="green" onClick={openCreate} size="sm">
                    {t`Create Campaign`}
                </Button>
            </ToolBar>

            <TableSkeleton isVisible={isLoading}/>

            {!isLoading && campaigns.length === 0 && (
                <NoResultsSplash
                    heading={t`No drip campaigns yet`}
                    subHeading={t`Create a campaign to send registration confirmation, reminders, and thank-you emails automatically.`}
                >
                    <Button mt="md" onClick={openCreate}>{t`Create Campaign`}</Button>
                </NoResultsSplash>
            )}

            {!isLoading && campaigns.length > 0 && (
                <Table striped highlightOnHover withTableBorder>
                    <Table.Thead>
                        <Table.Tr>
                            <Table.Th>{t`Name`}</Table.Th>
                            <Table.Th>{t`Trigger`}</Table.Th>
                            <Table.Th>{t`Steps`}</Table.Th>
                            <Table.Th>{t`Status`}</Table.Th>
                            <Table.Th>{t`Actions`}</Table.Th>
                        </Table.Tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {campaigns.map((campaign) => (
                            <Table.Tr key={campaign.id}>
                                <Table.Td>{campaign.name}</Table.Td>
                                <Table.Td>
                                    {campaign.trigger === 'on_registration'
                                        ? t`On registration`
                                        : t`Scheduled`}
                                </Table.Td>
                                <Table.Td>{campaign.steps?.length ?? 0}</Table.Td>
                                <Table.Td>
                                    <Badge color={statusColor(campaign.status)} variant="light">
                                        {campaign.status}
                                    </Badge>
                                </Table.Td>
                                <Table.Td>
                                    <Group gap="xs">
                                        <Button
                                            size="compact-xs"
                                            variant="light"
                                            onClick={() => toggleActive(campaign)}
                                            loading={updateMutation.isPending}
                                        >
                                            {campaign.status === 'active' ? t`Pause` : t`Activate`}
                                        </Button>
                                        <Button
                                            size="compact-xs"
                                            variant="light"
                                            color="red"
                                            leftSection={<IconTrash size={14}/>}
                                            onClick={() => handleDelete(campaign)}
                                            loading={deleteMutation.isPending}
                                        >
                                            {t`Delete`}
                                        </Button>
                                    </Group>
                                </Table.Td>
                            </Table.Tr>
                        ))}
                    </Table.Tbody>
                </Table>
            )}

            <Modal opened={createOpen} onClose={closeCreate} title={t`Create Drip Campaign`} centered>
                <form onSubmit={handleCreate}>
                    <Stack>
                        <Text size="sm" c="dimmed">
                            {t`Creates a 3-step sequence: registration confirmation (immediate), event reminder (24h), and thank-you (48h).`}
                        </Text>
                        <TextInput
                            label={t`Campaign name`}
                            placeholder={t`Registration sequence`}
                            required
                            {...form.getInputProps('name')}
                        />
                        <Switch
                            label={t`Activate immediately`}
                            description={t`When active, new registrations trigger the sequence automatically`}
                            {...form.getInputProps('activate', {type: 'checkbox'})}
                        />
                        <Button type="submit" loading={createMutation.isPending} fullWidth>
                            {t`Create Campaign`}
                        </Button>
                    </Stack>
                </form>
            </Modal>
        </PageBody>
    );
};

export default DripCampaigns;
