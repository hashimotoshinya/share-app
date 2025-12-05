export const useApi = () => {
  const config = useRuntimeConfig()

  const callApi = async (
    path: string,
    {
      method = "GET",
      body = null,
      token = null,
    }: {
      method?: string;
      body?: any;
      token?: string | null;
    } = {}
  ) => {

    const baseURL = config.public.apiBaseUrl

    // Firebase ID Tokenを localStorage から取り出す
    const savedToken = localStorage.getItem("token")
    const authToken = token ?? savedToken

    return await $fetch(baseURL + path, {
      method: method as any,
      headers: {
        "Content-Type": "application/json",
        // ★ これが絶対に必要
        ...(authToken && { Authorization: `Bearer ${authToken}` }),
      },
      body: body ? JSON.stringify(body) : null,
    })
  };

  return {
    callApi,
  };
};