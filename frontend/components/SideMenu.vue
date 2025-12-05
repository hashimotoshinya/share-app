<template>
  <aside class="w-96 min-h-screen bg-gray-900 text-white p-6 flex flex-col border-white">

    <!-- ロゴ -->
    <div class="mb-5">
      <img src="/logo.png" alt="SHARE" class="w-40" />
    </div>

    <!-- メニュー -->
    <nav class="flex flex-col space-y-6 mb-6">
      <button @click="goHome" class="flex items-center space-x-3 text-lg hover:opacity-80 text-left">
        <img src="/home.png" class="w-7 h-7" />
        <span>ホーム</span>
      </button>

      <button @click="logout" class="flex items-center space-x-3 text-lg hover:opacity-80">
        <img src="/logout.png" class="w-7 h-7" />
        <span>ログアウト</span>
      </button>
    </nav>

    <!-- ▼ シェア欄 -->
    <div class="mb-8">
      <h3 class="text-lg mb-2">シェア</h3>

      <!-- ▼ textarea -->
      <textarea
        v-model="text"
        class="w-full h-36 bg-gray-900 border border-white rounded-xl p-3 text-white
               resize-none mb-1 focus:ring-2 focus:ring-purple-500 outline-none"
      ></textarea>

      <!-- ▼ エラーメッセージ -->
      <p v-if="error" class="text-red-400 text-sm mb-3">{{ error }}</p>

      <!-- ▼ ボタン -->
      <div class="flex justify-end">
        <button
          type="button"
          @click="submitPost"
          class="px-3 py-2 rounded-full text-white transition bg-[#4b39d3] hover:bg-[#3f2fb7]"
          style="box-shadow: -1px -3px 0px 0px rgb(155, 155, 155, 0.55);"
        >
          シェアする
        </button>
      </div>
    </div>

  </aside>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { getAuth, signOut } from "firebase/auth";
import { useApi } from "~/composables/useApi";
import * as yup from "yup";

const router = useRouter();
const text = ref("");
const error = ref("");
const api = useApi();
const emit = defineEmits(['post', 'go-home']);

// ▼ バリデーションルール
const schema = yup.string()
  .required("投稿内容は必須です")
  .max(120, "投稿内容は120文字以内で入力してください");

/* ---------------------------------------
  投稿 → 親へ渡す（with バリデーション）
--------------------------------------- */
const submitPost = async () => {
  error.value = "";

  try {
    await schema.validate(text.value); // バリデーション実行

    emit('post', text.value.trim());
    text.value = "";

  } catch (e) {
    error.value = e.message;
  }
};

/* ---------------------------------------
  ホームボタン
--------------------------------------- */
const goHome = () => {
  emit('go-home');
};

/* ---------------------------------------
  ログアウト
--------------------------------------- */
const logout = async () => {
  try {
    const auth = getAuth();
    await signOut(auth);

    try {
      await api.callApi("/logout/firebase", { method: "POST" });
    } catch (e) {}

    router.push("/login");
  } catch (e) {
    alert("ログアウトに失敗しました");
  }
};
</script>