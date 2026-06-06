import {useMutation, useQueryClient} from "@tanstack/react-query";
import {authClient} from "../api/auth.client.ts";
import {LoginData, LoginResponse} from "../types.ts";
import {GET_ME_QUERY_KEY} from "../queries/useGetMe.ts";
import {setAuthToken} from "../utilites/apiClient.ts";

export const useLogin = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (loginData: LoginData) => authClient.login(loginData),
        retry: false,

        onSuccess: (response: LoginResponse) => {
            if (response.token) {
                setAuthToken(response.token);
                return queryClient.invalidateQueries({queryKey: [GET_ME_QUERY_KEY]});
            }
        },
    });
};
