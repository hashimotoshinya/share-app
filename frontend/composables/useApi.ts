type HttpMethod =
  | "GET"
  | "POST"
  | "PUT"
  | "PATCH"
  | "DELETE"
  | "get"
  | "post"
  | "put"
  | "patch"
  | "delete";

export const useApi = () => {
  const config = useRuntimeConfig();

  const callApi = async (
    path: string,
    {
      method = "GET",
      body,
      token,
    }: {
      method?: HttpMethod;
      body?: any;
      token?: string | null;
    } = {}
  ) => {
    const baseURL = config.public.apiBaseUrl;

    const savedToken = localStorage.getItem("token");
    const authToken = token ?? savedToken;

    return await $fetch(baseURL + path, {
      method,
      headers: {
        "Content-Type": "application/json",
        ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      },
      ...(body !== undefined ? { body } : {}),
    });
  };

  return { callApi };
};
