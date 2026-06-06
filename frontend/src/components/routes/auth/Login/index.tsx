import {Button, PasswordInput, TextInput, Collapse, UnstyledButton} from "@mantine/core";
import {NavLink, useLocation, useNavigate} from "react-router";
import {LoginData, LoginResponse} from "../../../../types.ts";
import {useForm} from "@mantine/form";
import {navigateToPreviousUrl} from "../../../../api/client.ts";
import classes from "./Login.module.scss";
import {t, Trans} from "@lingui/macro";
import {useRef, useState} from "react";
import {ChooseAccountModal} from "../../../modals/ChooseAccountModal";
import {useSendTicketLookupEmail} from "../../../../mutations/useSendTicketLookupEmail.ts";
import {showError} from "../../../../utilites/notifications.tsx";
import {IconTicket, IconChevronDown} from "@tabler/icons-react";
import {useLogin} from "../../../../mutations/useLogin.ts";
import {AxiosError} from "axios";
import {notifications} from "@mantine/notifications";

const LOGIN_ERROR_NOTIFICATION_ID = 'login-error';

const Login = () => {
    const location = useLocation();
    const navigate = useNavigate();
    const loginInFlightRef = useRef(false);
    const form = useForm({
        initialValues: {
            email: '',
            password: '',
            account_id: '',
        }
    });
    const [showChooseAccount, setShowChooseAccount] = useState(false);
    const [ticketLookupOpen, setTicketLookupOpen] = useState(false);

    const ticketLookupForm = useForm({
        initialValues: {
            email: '',
        }
    });
    const [ticketLookupSuccess, setTicketLookupSuccess] = useState(false);

    const loginMutation = useLogin();
    const ticketLookupMutation = useSendTicketLookupEmail();

    const handleLoginSuccess = (response: LoginResponse) => {
        if (response.token) {
            navigateToPreviousUrl(navigate);
            return;
        }

        if (response.accounts.length > 1) {
            setShowChooseAccount(true);
        }
    };

    const handleLogin = (values: LoginData) => {
        if (loginInFlightRef.current || loginMutation.isPending) {
            return;
        }

        loginInFlightRef.current = true;

        const payload: LoginData = {
            email: values.email,
            password: values.password,
        };

        if (values.account_id) {
            payload.account_id = values.account_id;
        }

        loginMutation.mutate(payload, {
            onSuccess: handleLoginSuccess,
            onError: (error: unknown) => {
                notifications.hide(LOGIN_ERROR_NOTIFICATION_ID);

                const axiosError = error as AxiosError<{message?: string}>;
                const status = axiosError.response?.status;

                if (!axiosError.response) {
                    showError(
                        t`Unable to reach the API server. If this is a Vercel deployment, set BACKEND_URL to your Laravel API and redeploy.`,
                        undefined,
                        LOGIN_ERROR_NOTIFICATION_ID,
                    );
                    return;
                }

                if (status === 401) {
                    showError(
                        t`Please check your email and password and try again`,
                        undefined,
                        LOGIN_ERROR_NOTIFICATION_ID,
                    );
                    return;
                }

                if (status === 403 && axiosError.response?.data?.message) {
                    showError(axiosError.response.data.message, undefined, LOGIN_ERROR_NOTIFICATION_ID);
                    return;
                }

                showError(
                    axiosError.response?.data?.message
                        ?? t`Login failed. Please try again.`,
                    undefined,
                    LOGIN_ERROR_NOTIFICATION_ID,
                );
            },
            onSettled: () => {
                loginInFlightRef.current = false;
            },
        });
    };

    const handleTicketLookup = (values: { email: string }) => {
        ticketLookupMutation.mutate(values.email, {
            onSuccess: () => {
                setTicketLookupSuccess(true);
            },
            onError: () => {
                showError(t`Something went wrong. Please try again.`);
            }
        });
    };

    return (
        <>
            <header className={classes.header}>
                <h2>{t`Welcome back`}</h2>
                <p>
                    <Trans>
                        Don't have an account?{' '}
                        <NavLink to={`/auth/register${location.search}`}>
                            Sign up
                        </NavLink>
                    </Trans>
                </p>
            </header>
            <div className={classes.loginCard}>
                <form onSubmit={form.onSubmit((values) => handleLogin(values))}>
                    <TextInput {...form.getInputProps('email')}
                               label={t`Email`}
                               placeholder="hello@example.com"
                               required
                    />
                    <PasswordInput {...form.getInputProps('password')}
                                   label={t`Password`}
                                   placeholder={t`Your password`}
                                   required
                                   mt="md"
                    />
                    <Button color="secondary.5" type="submit" fullWidth loading={loginMutation.isPending} disabled={loginMutation.isPending} mt="lg">
                        {loginMutation.isPending ? t`Logging in` : t`Log in`}
                    </Button>
                    <p>
                        <NavLink to={`/auth/forgot-password`}>
                            {t`Forgot password?`}
                        </NavLink>
                    </p>
                </form>
            </div>

            <div className={classes.ticketLookup}>
                <UnstyledButton
                    className={classes.ticketLookupTrigger}
                    onClick={() => setTicketLookupOpen(!ticketLookupOpen)}
                    data-expanded={ticketLookupOpen}
                >
                    <IconTicket size={18} />
                    <span>{t`Just looking for your tickets?`}</span>
                    <IconChevronDown
                        size={16}
                        className={classes.chevron}
                        data-expanded={ticketLookupOpen}
                    />
                </UnstyledButton>

                <Collapse in={ticketLookupOpen}>
                    <div className={classes.ticketLookupContent}>
                        {ticketLookupSuccess ? (
                            <div className={classes.successMessage}>
                                <p>{t`Check your inbox! If tickets are associated with this email, you'll receive a link to view them.`}</p>
                                <UnstyledButton
                                    className={classes.resetLink}
                                    onClick={() => {
                                        setTicketLookupSuccess(false);
                                        ticketLookupForm.reset();
                                    }}
                                >
                                    {t`Try another email`}
                                </UnstyledButton>
                            </div>
                        ) : (
                            <form onSubmit={ticketLookupForm.onSubmit(handleTicketLookup)}>
                                <div className={classes.ticketLookupForm}>
                                    <TextInput
                                        {...ticketLookupForm.getInputProps('email')}
                                        type="email"
                                        placeholder={t`Enter your email`}
                                        required
                                        className={classes.ticketEmailInput}
                                    />
                                    <Button
                                        type="submit"
                                        color="secondary.5"
                                        loading={ticketLookupMutation.isPending}
                                        disabled={ticketLookupMutation.isPending}
                                    >
                                        {t`Send`}
                                    </Button>
                                </div>
                            </form>
                        )}
                    </div>
                </Collapse>
            </div>

            {(showChooseAccount && loginMutation.data) && <ChooseAccountModal onAccountChosen={(accountId) => {
                handleLogin({
                    email: form.values.email,
                    password: form.values.password,
                    account_id: accountId,
                });
            }
            } accounts={loginMutation.data.accounts}/>}
        </>
    )
}

export default Login;
