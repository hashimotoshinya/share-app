<template>
  <div class="flex bg-gray-900 min-h-screen text-white">
    <!-- Sidebar（固定） -->
    <SideMenu :user="user" @post="createPost" @go-home="mode = 'home'" />

    <!-- Main（縦いっぱい） -->
    <div class="flex-1 flex flex-col h-screen pr-10">
      <!-- ホーム見出し（固定・paddingそのまま） -->
      <div
        class="pt-8 sticky top-0 bg-gray-900 z-10 border-l border-white flex items-center"
      >
        <h1 class="text-2xl font-bold mb-6 px-5">
          {{ mode === "home" ? "ホーム" : "コメント" }}
        </h1>
      </div>

      <!-- 通知エリア -->
      <div v-if="notification.message" class="fixed top-20 right-8 z-50">
        <div
          :class="[
            'px-4 py-2 rounded shadow',
            notification.type === 'error' ? 'bg-red-600' : 'bg-green-600',
          ]"
        >
          {{ notification.message }}
        </div>
      </div>

      <!-- スクロール領域（投稿一覧） -->
      <div class="flex-1 overflow-y-auto pl-0 pb-8" ref="scrollArea">
        <template v-if="mode === 'home'">
          <PostList
            :posts="posts"
            @like="likePost"
            @delete="deletePost"
            @comment="openComments"
          />
        </template>

        <template v-else>
          <CommentList :postId="selectedPostId" @back="mode = 'home'" />
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { getAuth, onAuthStateChanged } from "firebase/auth";
import { useRouter } from "vue-router";

import SideMenu from "~/components/SideMenu.vue";
import PostList from "~/components/PostList.vue";
import { useApi } from "~/composables/useApi";

/* ------------------------------
  state
------------------------------ */
const user = ref(null);
const posts = ref([]);
const page = ref(1);
const lastPage = ref(null);
const loading = ref(false);

// 簡易通知（成功/エラー表示）
const notification = ref({ message: "", type: "error" });

const scrollArea = ref(null);

const router = useRouter();
const api = useApi();

const mode = ref("home");
const selectedPostId = ref(null);

/* ------------------------------
  Firebase 認証
------------------------------ */
onMounted(() => {
  const auth = getAuth();

  onAuthStateChanged(auth, async (currentUser) => {
    if (!currentUser) {
      router.push("/login");
      return;
    }

    user.value = currentUser;
    await fetchPosts();
  });
});

/* ------------------------------
  投稿一覧（1ページ目）
------------------------------ */
const fetchPosts = async () => {
  try {
    loading.value = true;

    // 可能なら ID トークンを渡してサーバー側で currentUser を判定させる
    let token = null;
    if (user.value) {
      token = await user.value.getIdToken();
    }

    const res = await api.callApi(`/posts?page=${page.value}`, {
      method: "GET",
      token,
    });

    console.log("=== Posts API Response ===");
    console.log("First post structure:", JSON.stringify(res.data[0], null, 2));
    if (res.data[0]?.user) {
      console.log(
        "First post user:",
        JSON.stringify(res.data[0].user, null, 2)
      );
    }

    posts.value = res.data;
    lastPage.value = res.last_page;
  } catch (e) {
    console.error("投稿取得エラー:", e);
  } finally {
    loading.value = false;
  }
};

/* ------------------------------
  もっと読み込む（無限スクロール）
------------------------------ */
const loadMore = async () => {
  if (loading.value) return;
  if (page.value >= lastPage.value) return;

  page.value++;
  loading.value = true;

  try {
    let token = null;
    if (user.value) {
      token = await user.value.getIdToken();
    }

    const res = await api.callApi(`/posts?page=${page.value}`, {
      method: "GET",
      token,
    });

    posts.value.push(...res.data);
  } catch (e) {
    console.error("追加読み込みエラー:", e);
  } finally {
    loading.value = false;
  }
};

/* ------------------------------
  投稿作成
------------------------------ */
const createPost = async (text) => {
  try {
    if (!user.value) return;

    const idToken = await user.value.getIdToken();

    const newPost = await api.callApi("/posts", {
      method: "POST",
      body: { content: text },
      token: idToken,
    });

    if (newPost && newPost.id) {
      posts.value.unshift(newPost);
    }
  } catch (e) {
    console.error("投稿作成エラー:", e);
  }
};

/* ------------------------------
  いいね / いいね解除（統合版）
------------------------------ */
const likePost = async (postId) => {
  try {
    const post = posts.value.find((p) => p.id === postId);
    if (!post) return;

    // Firebase ID トークンを取得して Authorization ヘッダを付与
    if (!user.value) return;
    const idToken = await user.value.getIdToken();

    // オプティミスティックに UI を先に切り替える（即時フィードバック）
    const prevLiked = post.liked ?? false;
    const prevCount = post.likes_count ?? 0;

    // toggle locally
    post.liked = !prevLiked;
    post.likes_count = prevCount + (post.liked ? 1 : -1);

    try {
      // API コール（トークンを渡す）
      const res = await api.callApi(`/posts/${postId}/like`, {
        method: "POST",
        token: idToken,
      });

      // サーバー応答で差分があれば調整
      post.liked = res.liked ?? post.liked;
      post.likes_count = res.count ?? res.likes_count ?? post.likes_count;
    } catch (e) {
      // 失敗したら元に戻す
      post.liked = prevLiked;
      post.likes_count = prevCount;
      throw e;
    }
  } catch (e) {
    console.error("いいねエラー:", e);
  }
};

/* ------------------------------
  投稿削除
------------------------------ */
const deletePost = async (postId) => {
  try {
    const idToken = await user.value.getIdToken();

    await api.callApi(`/posts/${postId}`, {
      method: "DELETE",
      token: idToken,
    });

    posts.value = posts.value.filter((p) => p.id !== postId);
  } catch (e) {
    console.error("削除エラー:", e);

    // $fetch のエラー中から status を取り出す（互換的に調べる）
    const status = e?.status ?? e?.response?.status ?? e?.data?.status ?? null;
    const serverMsg = e?.data?.error ?? e?.message ?? "";

    if (status === 403) {
      notification.value = {
        message: "投稿者以外は削除できません。",
        type: "error",
      };
    } else if (status === 401) {
      notification.value = {
        message: "認証に失敗しました。再ログインしてください。",
        type: "error",
      };
    } else {
      notification.value = {
        message: serverMsg || "削除に失敗しました。",
        type: "error",
      };
    }

    setTimeout(() => {
      notification.value.message = "";
    }, 3000);
  }
};

/* ------------------------------
  スクロール監視
------------------------------ */
const handleScroll = () => {
  const el = scrollArea.value;
  if (!el || loading.value) return;

  const bottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 150;

  if (bottom) loadMore();
};

onMounted(() => {
  if (scrollArea.value) {
    scrollArea.value.addEventListener("scroll", handleScroll);
  }
});

onUnmounted(() => {
  if (scrollArea.value) {
    scrollArea.value.removeEventListener("scroll", handleScroll);
  }
});

const openComments = (postId) => {
  selectedPostId.value = postId;
  mode.value = "comment";
};
</script>
