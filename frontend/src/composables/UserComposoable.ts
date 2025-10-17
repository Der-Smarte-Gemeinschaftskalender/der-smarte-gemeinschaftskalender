import { ref } from "vue";
import { jwtDecode, type JwtPayload } from "jwt-decode";
import router from "@/router/router";

export const user = ref(
    localStorage.getItem("user") ? JSON.parse(<string>localStorage.getItem("user")) : null
);

export const setUserData = (userData: object, access_token: string, person: object | null): void => {
    user.value = { ...userData, access_token, person };
    localStorage.setItem("user", JSON.stringify(user.value));
};

export const setUserPersonData = (person: object | null): void => {
    user.value = { ...user.value, person };
    localStorage.setItem("user", JSON.stringify(user.value));
};

export const updateProfileData = (profileData: { email: string }, person: object | null): void => {
    if (!user.value) return;
    user.value = { ...user.value, ...profileData, person };
    localStorage.setItem("user", JSON.stringify(user.value));
}

export const localLogout = (): void => {
    user.value = null;
    localStorage.removeItem("user");

    localStorage.removeItem("current_organisation");
    localStorage.removeItem("user_organisations");
    localStorage.removeItem("last_groups_sync");
    router.go(0); // Reload the page to reset the orga states
};

export const checkToken = (token: string): boolean => {
    try {
        const decodedToken = jwtDecode<JwtPayload>(token);
        if (!!decodedToken.exp && decodedToken.exp * 1000 > Date.now()) {
            return true;
        }
    } catch (error) {
        console.error(error);
    }
    return false;
};

export const checkLogin = (): boolean => {
    return !!user.value && checkToken(user.value.access_token);
};
