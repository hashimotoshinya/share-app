<template>
  <div class="min-h-screen bg-gray-900 flex flex-col items-center justify-center">
    <header class="absolute top-6 left-8 flex items-center space-x-3">
      <img src="/logo.png" alt="SHARE" class="h-10" />
    </header>

    <nav class="absolute top-8 right-12 text-white space-x-6 text-sm">
      <NuxtLink to="/register" class="hover:underline">新規登録</NuxtLink>
      <NuxtLink to="/login" class="hover:underline font-bold underline">ログイン</NuxtLink>
    </nav>

    <div class="bg-white rounded-xl shadow-xl p-10 w-96">
      <p v-if="flashMessage" class="text-green-600 text-sm mb-4 text-center">
        {{ flashMessage }}
      </p>

      <h2 class="text-center text-lg font-bold mb-6">ログイン</h2>

      <!-- ▼ VeeValidate フォーム -->
      <Form :validation-schema="schema" @submit="loginUser" class="space-y-4 text-center">

        <!-- メール -->
        <div>
          <Field
            name="email"
            type="email"
            placeholder="メールアドレス"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 outline-none"
          />
          <ErrorMessage name="email" class="text-red-500 text-xs mt-1 block" />
        </div>

        <!-- パスワード -->
        <div>
          <Field
            name="password"
            type="password"
            placeholder="パスワード"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 outline-none"
          />
          <ErrorMessage name="password" class="text-red-500 text-xs mt-1 block" />
        </div>

        <button
          type="submit"
          class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-2 px-6 rounded-full hover:opacity-90 shadow-md"
        >
          ログイン
        </button>
      </Form>

      <p v-if="error" class="text-red-500 text-sm mt-3 text-center">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
/* --------------------------------------
  Imports
--------------------------------------- */
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from '#imports'
import { useNuxtApp } from '#app'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import { useApi } from '~/composables/useApi'

/* --------------------------------------
  Yup バリデーション
--------------------------------------- */
const schema = yup.object({
  email: yup
    .string()
    .required('メールアドレスは必須です')
    .email('メール形式が正しくありません'),
  password: yup
    .string()
    .required('パスワードは必須です'),
})

/* --------------------------------------
  状態
--------------------------------------- */
const router = useRouter()
const route = useRoute()
const error = ref('')
const flashMessage = ref('')
const { callApi } = useApi()

let auth = null

/* --------------------------------------
  Firebase 読み込み＆フラッシュメッセージ
--------------------------------------- */
onMounted(() => {
  const nuxtApp = useNuxtApp()
  auth = nuxtApp.$firebase.auth

  if (route.query.registered) {
    flashMessage.value = '登録が完了しました！'
    setTimeout(() => (flashMessage.value = ''), 1500)
  }
})

/* --------------------------------------
  Firebase エラー → 日本語変換
--------------------------------------- */
function firebaseErrorToJapanese(code) {
  switch (code) {
    case 'user-not-found':
    case 'wrong-password':
      return 'メールアドレスまたはパスワードが違います。'
    case 'invalid-email':
      return 'メールアドレスの形式が正しくありません。'
    case 'too-many-requests':
      return '続けてエラーが発生しています。しばらく時間をおいてお試しください。'
    case 'user-disabled':
      return 'このアカウントは無効化されています。'
    default:
      return `ログインに失敗しました（${code}）`
  }
}

/* --------------------------------------
  ログイン処理
--------------------------------------- */
const loginUser = async (values) => {
  error.value = ''

  if (!auth) {
    error.value = 'Firebase が初期化されていません'
    return
  }

  const { signInWithEmailAndPassword } = await import('firebase/auth')

  try {
    // Firebase ログイン
    const userCredential = await signInWithEmailAndPassword(
      auth,
      values.email,
      values.password
    )

    const idToken = await userCredential.user.getIdToken()

    // Laravel API へ送信
    await callApi('/login/firebase', {
      method: 'POST',
      token: idToken,
    })

    router.push('/home')

  } catch (e) {
    console.error('LOGIN ERROR:', e)

    // Firebase エラー（auth/xxx）
    if (e?.code) {
      error.value = firebaseErrorToJapanese(e.code.replace('auth/', ''))
      return
    }

    // Laravel API のエラー
    if (e?.data?.error) {
      error.value = `サーバーエラー: ${e.data.error}`
      return
    }

    error.value = '予期しないエラーが発生しました'
  }
}
</script>