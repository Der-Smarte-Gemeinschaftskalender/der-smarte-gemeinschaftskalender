import { checkLogin, localLogout, user } from "@/composables/UserComposoable";
import axios, { AxiosError } from "axios";

const dsgApi = axios.create({
    baseURL: import.meta.env.VITE_DSG_API_URL,
});

const checkAuthDsgApi = () => {
    if (checkLogin()) dsgApi.defaults.headers.common["Authorization"] = `Bearer ${user.value?.access_token}`;
    else delete dsgApi.defaults.headers.common["Authorization"];
};

checkAuthDsgApi();

dsgApi.interceptors.response.use(
    (response) => response,
    (error) => {

        if (axios.isAxiosError(error) && error.response?.status === 401) {
            localLogout();
        }

        return Promise.reject(error);
    }
);


interface IErrorBase<T> {
    error: Error | AxiosError<T>;
    type: 'axios-error' | 'stock-error';
}

interface IAxiosError<T> extends IErrorBase<T> {
    error: AxiosError<T>;
    type: 'axios-error';
}
interface IStockError<T> extends IErrorBase<T> {
    error: Error;
    type: 'stock-error';
}

export function axiosErrorHandler<T>(
  callback: (err: IAxiosError<T> | IStockError<T>) => void
) {
    return (error: Error | AxiosError<T>) => {
        if (axios.isAxiosError(error)) {
            callback({
                error: error,
                type: 'axios-error'
            });
        } else {
            callback({
                error: error,
                type: 'stock-error'
            });
        }
    };
}

export { dsgApi, checkAuthDsgApi, AxiosError };
