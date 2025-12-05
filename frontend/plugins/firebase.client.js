import { initializeApp } from "firebase/app";
import { getAuth } from "firebase/auth";

export default defineNuxtPlugin(() => {
  const firebaseConfig = {
    apiKey: "AIzaSyAj1-qkvfTqz1cn2skKvkTL-bciNWO57dM",
    authDomain: "share-app-8429a.firebaseapp.com",
    projectId: "share-app-8429a",
    storageBucket: "share-app-8429a.firebasestorage.app",
    messagingSenderId: "130271264538",
    appId: "1:130271264538:web:d1a7b0794720c4ab0076a3"
  };

  // Firebase 初期化（1回だけ実行）
  const app = initializeApp(firebaseConfig);

  // Auth の取得
  const auth = getAuth(app);

  return {
    provide: {
      firebase: {
        app,
        auth
      }
    }
  };
});