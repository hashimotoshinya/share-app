<template>
  <div class="min-h-screen bg-gray-900 flex flex-col items-center justify-center">
    <header class="absolute top-6 left-8 flex items-center space-x-3">
      <img src="/logo.png" alt="SHARE" class="h-10" />
    </header>

    <nav class="absolute top-8 right-12 text-white space-x-6 text-sm">
      <NuxtLink to="/register" class="hover:underline font-bold underline">新規登録</NuxtLink>
      <NuxtLink to="/login" class="hover:underline">ログイン</NuxtLink>
    </nav>

    <div class="bg-white rounded-xl shadow-xl p-10 w-96">
      <h2 class="text-center text-lg font-bold mb-6">新規登録</h2>

      <!-- ★ VeeValidate フォーム -->
      <Form :validation-schema="schema" @submit="registerUser" class="space-y-4 text-center">

        <!-- ユーザー名 -->
        <div>
          <Field
            name="username"
            type="text"
            placeholder="ユーザー名"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 outline-none"
          />
          <ErrorMessage name="username" class="text-red-500 text-xs mt-1 block" />
        </div>

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

        <!-- 送信ボタン -->
        <button
          type="submit"
          class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-2 px-6 rounded-full hover:opacity-90 shadow-md"
        >
          新規登録
        </button>
      </Form>

      <!-- Firebase / API エラー -->
      <p v-if="error" class="text-red-500 text-sm mt-3 text-center">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useNuxtApp, navigateTo } from '#app'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'

/* フロント側の入力バリデーション（VeeValidate + Yup） */
const schema = yup.object({
  username: yup
    .string()
    .required('ユーザー名は必須です')
    .max(20, '20文字以内で入力してください'),
  email: yup
    .string()
    .required('メールアドレスは必須です')
    .email('メール形式が正しくありません'),
  password: yup
    .string()
    .required('パスワードは必須です')
    .min(6, '6文字以上で入力してください'),
})

let auth = null
const error = ref('')

onMounted(() => {
  const nuxtApp = useNuxtApp()
  auth = nuxtApp.$firebaseAuth
})

/* -------------------------------
  Firebase エラー → 日本語に変換
-------------------------------- */
function firebaseErrorToJapanese(code) {
  switch (code) {
    case 'email-already-in-use':
      return 'このメールアドレスはすでに登録されています。'
    case 'invalid-email':
      return 'メールアドレスの形式が正しくありません。'
    case 'weak-password':
      return 'パスワードが短すぎます（6文字以上を推奨）。'
    case 'user-not-found':
    case 'wrong-password':
      return 'メールアドレスまたはパスワードが違います。'
    case 'too-many-requests':
      return '続けてエラーが発生しています。しばらく時間をおいて再度お試しください。'
    default:
      return `エラーが発生しました（${code}）`
  }
}

/* -------------------------------
  Register Handler
-------------------------------- */
const registerUser = async (values) => {
  error.value = ''

  if (!auth) {
    error.value = 'Firebase が初期化されていません'
    return
  }

  const { createUserWithEmailAndPassword } = await import('firebase/auth')

  try {
    // Firebase アカウント作成
    const userCredential = await createUserWithEmailAndPassword(
      auth,
      values.email,
      values.password
    )

    const idToken = await userCredential.user.getIdToken(true)

    // Laravel へ登録
    await $fetch('http://localhost:8000/api/register/firebase', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${idToken}`,
        'Content-Type': 'application/json'
      },
      body: {
        username: values.username,
        email: values.email,
      }
    })

    navigateTo('/login?registered=1')

  } catch (e) {
    console.error('REGISTER ERROR:', e)

    // Firebase エラー（auth/XXX）
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