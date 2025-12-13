# 📘Share App - Firebase 認証対応 SNS サンプル

![トップ画面](docs/images/top.png)

Firebase 認証を用いたシンプルな SNS 風アプリケーションです。  
ユーザーは投稿の作成、削除、いいね、コメントを行うことができます。

フロントエンドは Nuxt（Vue）、バックエンドは Laravel API で構成されており、  
Firebase Authentication と連携した認証処理を実装しています。

---

## 🎯作成した目的

- Firebase Authentication と Laravel を連携した実践的な認証処理を理解するため
- REST API を前提としたバックエンド設計・Feature テストの実装経験を積むため
- フロントエンド（Vue/Nuxt）とバックエンド（Laravel）を分離した構成を学ぶため

実務を想定し、  
**ER 図設計 → API 実装 → Feature テスト → Git 管理**  
という一連の開発フローを意識して作成しています。

---

## アプリケーションURL

※ 現在はローカル環境での実行を想定しています  
（デプロイは未実施）

---

## 他のリポジトリ

※ フロントエンド・バックエンドを同一リポジトリで管理しています  
（`frontend / backend` ディレクトリ構成）

---

## 🚀機能一覧

- Firebase Authentication を利用したログイン / 新規登録
- 投稿の一覧表示
- 投稿の作成・削除
- 投稿へのいいね（トグル）
- 投稿へのコメント投稿・一覧表示
- ログイン状態の認証チェック
- Feature Test / Unit Test 実装
- Laravel API による REST API 設計

---

## 🛠使用技術（実行環境）

### バックエンド
- PHP 8.x
- Laravel 10.x
- Firebase Admin SDK
- SQLite（テスト環境）
- PHPUnit（Feature / Unit Test）

### フロントエンド
- Nuxt 3
- Vue 3（Composition API）
- Firebase Authentication（Client SDK）
- Tailwind CSS

### その他
- Docker（開発環境）
- Git / GitHub

---

## 🗄テーブル設計

### users テーブル
- id
- name
- email
- firebase_uid
- timestamps

### posts テーブル
- id
- user_id（FK）
- content
- timestamps

### comments テーブル
- id
- user_id（FK）
- post_id（FK）
- content
- timestamps

### likes テーブル
- id
- user_id（FK）
- post_id（FK）
- timestamps

---

## 📐ER図


![ER図](docs/images/er.png)

---

## 環境構築

### 前提条件
以下がローカル環境にインストールされていることを前提とします。
- Git
- Docker / Docker Compose
- Node.js（18.x 以上）
- npm
- Firebase プロジェクト（Authentication 有効化済み）

### 1. リポジトリをクローン
```
git clone https://github.com/hashimotoshinya/share-app.git
cd share-app
```

### 2. Firebase の設定

#### 2-1. Firebase プロジェクト作成

1.	Firebase Console で新規プロジェクトを作成
2.	Authentication を有効化
3.	サインイン方法で メール / パスワード を有効化

#### 2-2. Firebase Service Account（バックエンド用）

1.	Firebase Console
→ プロジェクト設定
→ サービスアカウント
→ 新しい秘密鍵を生成
2.	取得した JSON を以下に配置（※ Git 管理外）
```
backend/storage/firebase/firebase-adminsdk.json
```
3.	.env にパスを設定
```
FIREBASE_CREDENTIALS=storage/firebase/firebase-adminsdk.json
```
※ firebase-adminsdk.json は .gitignore 対象 です

#### 2-3. Firebase Web 設定（フロントエンド用）

Firebase Console
→ プロジェクト設定
→ 全般
→ Web アプリを追加
取得した設定値を frontend/.env に記載します。
```
NUXT_PUBLIC_FIREBASE_API_KEY=xxxx
NUXT_PUBLIC_FIREBASE_AUTH_DOMAIN=xxxx
NUXT_PUBLIC_FIREBASE_PROJECT_ID=xxxx
NUXT_PUBLIC_FIREBASE_STORAGE_BUCKET=xxxx
NUXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID=xxxx
NUXT_PUBLIC_FIREBASE_APP_ID=xxxx
```

### 3. Docker 環境設定

#### docker-compose.yml の Firebase 設定
Firebase プロジェクトに応じて以下を修正します。

```
environment:
  - TZ=Asia/Tokyo
  - FIREBASE_PROJECT=app
  - FIREBASE_PROJECT_ID=your-firebase-project-id
  - FIREBASE_CREDENTIALS=/var/www/html/firebase-adminsdk.json
  - FIREBASE_DATABASE_URL=https://your-project-id.firebaseio.com
```

Service Account Key は以下にマウントされます。

```
/var/www/html/firebase-adminsdk.json
```

※ Firebase プロジェクトを変更した場合は、docker compose build を再実行してください。

### 4. バックエンド（Laravel）

#### 4-1. 環境変数設定

```
cd backend
cp .env.example .env
```
.env設定例

```
※ Docker 使用時は MySQL を想定
DB_CONNECTION=mysql
DB_HOST=mysql-db
DB_PORT=3306
DB_DATABASE=app_db
DB_USERNAME=root
DB_PASSWORD=root
```

SQLite 使用時（Docker 非使用）

```
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

```
touch database/database.sqlite
```

#### 4-2. Docker ビルド & 起動（Docker 使用時）

```
docker compose build
docker compose up -d
```

#### 4-3. 依存関係インストール & 初期化

```
Docker 使用時：

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 5. フロントエンド（Nuxt）
```
cd frontend
cp .env.example .env
npm install
npm run dev
```

起動後、以下にアクセスします。
```
http://localhost:3000/register
```

---

## 動作確認
1. Firebase で新規ユーザー登録ができること
例
- name: ダミーユーザー
- email: dummy@example.com
- password: dummy1234

2. ログイン後に投稿一覧が表示されること
3. 投稿・いいね・コメントが正常に動作すること

---

## テスト
```
cd backend
php artisan test
```
#### テストについて
- Firebase 認証のユーザーマッピング処理を Feature Test で検証
- 投稿 / コメント / いいねの CRUD 処理を Feature Test で検証
- モデル間のリレーションを Unit Test で検証
- 将来的な仕様変更を想定し、Feature Test を中心に実装しています
---

## 注意事項
- Firebase の Service Account Key は .gitignore により管理外としています
- テストでは Firebase 認証処理をスタブ化しており、実際の Firebase 通信は行いません
- Firebase プロジェクトや docker-compose.yml の environment を変更した場合は
  docker compose build を再実行してください。
