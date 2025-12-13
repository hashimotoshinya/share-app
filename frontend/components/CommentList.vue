<template>
  <div v-if="loading" class="text-white p-5">読み込み中...</div>

  <div v-else class="w-full text-white">
    <div class="border-l border-white">
      <div class="border-t border-white"></div>

      <div class="py-3">
        <div class="flex items-center gap-6 mb-1 px-5">
          <p class="font-bold text-base truncate w-[120px] flex-shrink-0">
            {{ post?.user?.name }}
          </p>

          <div class="flex items-center gap-4">
            <button
              @click="toggleLike"
              class="flex items-center gap-1 hover:opacity-70 transition"
              :disabled="isMine"
            >
              <img
                src="/heart.png"
                class="w-5 h-5 transition-all duration-150"
                :style="
                  liked
                    ? 'filter: brightness(0) saturate(100%) invert(12%) sepia(96%) saturate(3000%) hue-rotate(342deg);'
                    : ''
                "
              />
              <span
                :class="isMine ? 'text-gray-400' : 'text-white'"
                class="text-sm"
                >{{ likes }}</span
              >
            </button>

            <button @click="deletePost" class="hover:opacity-70 transition">
              <img src="/cross.png" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <p class="text-gray-300 text-sm whitespace-pre-line px-5">
          {{ post?.content }}
        </p>
      </div>

      <div class="border-b border-white mb-2"></div>

      <h2 class="text-sm text-center mb-2">コメント</h2>

      <div class="border-b border-white mb-4"></div>

      <div
        v-for="comment in comments"
        :key="comment.id"
        class="border-b border-white py-3 px-5"
      >
        <p class="font-bold text-sm truncate w-[120px]">
          {{ comment.user?.name }}
        </p>
        <p class="text-gray-300 text-sm whitespace-pre-line">
          {{ comment.content }}
        </p>
      </div>
    </div>

    <!-- コメント入力欄 -->
    <div class="mt-6 px-5 mr-10 relative pb-14">
      <textarea
        v-model="newComment"
        placeholder="コメントを入力..."
        class="w-full bg-gray-900 border border-white p-3 rounded-xl text-sm pb-4 resize-none overflow-hidden"
        style="
          min-height: 40px;
          height: 40px;
          word-break: break-all;
          overflow-wrap: break-word;
          white-space: pre-wrap;
        "
        @input="autoResize"
      ></textarea>

      <p v-if="errorMessage" class="text-red-400 text-xs mt-1">
        {{ errorMessage }}
      </p>

      <button
        @click="submitComment"
        class="absolute -right-10 bottom-2 px-4 py-2 rounded-full text-white transition bg-[#4b39d3] hover:bg-[#3f2fb7] text-sm"
        style="box-shadow: -1px -3px 0px 0px rgb(155, 155, 155, 0.55)"
      >
        コメント
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useApi } from "~/composables/useApi";
import { getAuth } from "firebase/auth";
import * as yup from "yup";

/* =============================
   Props / Emits
============================= */
const props = defineProps({
  postId: { type: Number, required: true },
});

const emit = defineEmits(["back"]);

/* =============================
   API / State
============================= */
const api = useApi();

const post = ref(null);
const comments = ref([]);
const newComment = ref("");
const loading = ref(true);
const liked = ref(false);
const likes = ref(0);
const isMine = ref(false);

const errorMessage = ref("");

/* =============================
   Yup バリデーションスキーマ（投稿と統一）
============================= */
const commentSchema = yup
  .string()
  .trim()
  .required("コメントは必須です。")
  .max(120, "120文字以内で入力してください。");

/* =============================
   textarea 自動リサイズ
============================= */
const autoResize = (e) => {
  const el = e.target;
  const baseHeight = 40;
  el.style.height = baseHeight + "px";
  el.style.height = Math.max(el.scrollHeight, baseHeight) + "px";
};

/* =============================
   コメント取得
============================= */
const fetchComments = async () => {
  try {
    let token = null;
    const auth = getAuth();
    if (auth.currentUser) token = await auth.currentUser.getIdToken();

    const res = await api.callApi(`/posts/${props.postId}/comments`, {
      method: "GET",
      token,
    });

    post.value = res.post;
    comments.value = res.comments;
    likes.value = res.likes;
    liked.value = res.liked;
    isMine.value = res.is_mine;
  } finally {
    loading.value = false;
  }
};

/* =============================
   コメント送信（Yup 統一バージョン）
============================= */
const submitComment = async () => {
  try {
    // ▼ Yup バリデーション ------------------------
    await commentSchema.validate(newComment.value);
    errorMessage.value = "";
  } catch (err) {
    errorMessage.value = err.message;
    return;
  }

  try {
    const auth = getAuth();
    const token = await auth.currentUser.getIdToken();

    const res = await api.callApi(`/posts/${props.postId}/comments`, {
      method: "POST",
      token,
      body: { content: newComment.value },
    });

    comments.value.push({
      ...res.comment,
      user: res.comment.user || { id: res.comment.user_id, name: "Unknown" },
    });

    newComment.value = "";
  } catch (e) {
    console.error("コメント投稿エラー:", e);
  }
};

/* =============================
   いいね
============================= */
const toggleLike = async () => {
  try {
    const token = await getAuth().currentUser.getIdToken();
    const res = await api.callApi(`/posts/${props.postId}/like`, {
      method: "POST",
      token,
    });
    liked.value = res.liked;
    likes.value = res.count;
  } catch (e) {}
};

/* =============================
   投稿削除
============================= */
const deletePost = async () => {
  try {
    const token = await getAuth().currentUser.getIdToken();
    await api.callApi(`/posts/${props.postId}`, { method: "DELETE", token });
    emit("back");
  } catch (e) {
    console.error("削除エラー:", e);
  }
};

onMounted(fetchComments);
</script>