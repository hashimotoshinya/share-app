# Share App テスト計画書

## 実行状況

### ✅ 完成・成功 (8/8 テスト成功)

#### Firebase Auth マッピング機能 (`tests/Feature/FirebaseAuthMappingTest.php`)

- ✅ `test_register_without_firebase_token_returns_401` — トークンなしで 401 を返す
- ✅ `test_register_with_invalid_firebase_token_returns_401` — 無効トークンで 401 を返す
- ✅ `test_register_with_new_firebase_uid_creates_user` — 新規ユーザーを正しく作成
- ✅ `test_register_with_existing_email_and_firebase_uid_updates_name` — 既存ユーザーの name を更新
- ✅ `test_register_with_existing_firebase_uid_updates_name` — firebase_uid 存在時に name 更新
- ✅ `test_firebase_login_with_nonexistent_user_returns_404` — 存在しないユーザーで 404
- ✅ `test_firebase_login_with_existing_user_returns_200` — 認証済みユーザーで 200 返却
- ✅ `test_api_response_does_not_contain_sensitive_fields` — password/remember_token が除外される

**実行コマンド:**

```bash
cd backend && php artisan test tests/Feature/FirebaseAuthMappingTest.php
```

---

### 📋 次フェーズ：Post CRUD テスト

#### 設計方針

POST CRUD テストは以下の理由から、より簡潔な実装に転換します：

1. **Firebase Auth ミドルウェアの型チェック** — Lcobucci\JWT ライブラリの厳密な型チェック（`UnencryptedToken` → `DataSet`）により、Mockery 単体では対応困難
2. **本来の目的** — API レスポンス、権限チェック、データベース操作の検証
3. **実装アプローチ** — 以下いずれかを採用：
   - テスト環境専用の Firebase Auth ゲートウェイを実装
   - または、POST/Comment/Like の単体テストで、ユーザーレコードが既に存在する状態でのコントローラ動作をテスト
   - または、Integration テストとして Postman/Insomnia 等での手動検証

#### テストケース（優先度順）

**必須（P0）:**

- Post 作成時のバリデーション（content が必須、最大文字数チェック等）
- Post 削除時の権限チェック（自分の投稿のみ削除可）
- Post 取得時の user 情報が含まれていること

**望ましい（P1）:**

- Comment CRUD の基本動作
- Like toggle の idempotence（何度実行しても同じ結果）
- レスポンスに password/remember_token が含まれないこと

**追加（P2）:**

- N+1 クエリの検出テスト
- Pagination の実装テスト

---

## テスト実行状況

### 実行コマンド一覧

```bash
# Firebase Auth マッピングテスト（全成功）
php artisan test tests/Feature/FirebaseAuthMappingTest.php

# Post CRUD テスト（実装予定）
php artisan test tests/Feature/PostCrudTest.php

# 全テスト実行
php artisan test

# 特定テストのみ実行
php artisan test --filter=test_register_without_firebase_token_returns_401
```

---

## 推奨される今後の実装ステップ

1. **テスト用 Firebase Gateway** — テスト時のみ簡易版を使う

   - `config/firebase.php` でテスト環境検出
   - `app/Services/FirebaseAuthService.php` で型チェック回避レイヤーを実装

2. **Post CRUD テスト再実装** — 上記 Gateway を使用して Post/Comment/Like の完全なテストをカバー

3. **フロントエンド Unit テスト** — Vitest/Jest で PostCard.vue, CommentList.vue をテスト

4. **CI/CD 統合** — GitHub Actions で`php artisan test` の実行を自動化

---

## テスト設計の学び

### ✅ うまくいったこと

- Firebase Auth マッピングの Mockery セットアップ（Claims → Verified オブジェクト の構造化）
- RefreshDatabase トレイトによるテスト間の DB 隔離
- assertDatabaseHas/assertDatabaseMissing による DB 状態検証

### ⚠️ 課題と解決案

| 課題                            | 原因                                                | 解決案                                                    |
| ------------------------------- | --------------------------------------------------- | --------------------------------------------------------- |
| Firebase Auth 型チェック エラー | Lcobucci\JWT の厳密な戻り値チェック                 | テスト用 Gateway クラスを実装                             |
| ミドルウェア経由のテスト困難    | withoutMiddleware() 使用時に firebase_user が未設定 | カスタムテスト Middleware または attribute binding を使用 |
| Mockery 状態の継続性            | PHPUnit テスト間での tearDown/setUp                 | 各テスト開始時に明示的にモック設定を行う                  |

---

## 参考：テスト環境設定ファイル例

### `.env.testing` (推奨)

```ini
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
FIREBASE_EMULATOR_HOST=localhost:9099
```

### `phpunit.xml` の必須設定

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

---

## まとめ

- **Firebase Auth マッピング機能テスト：成功率 100%（8/8）**
- テスト戦略は検証済み（Mockery、RefreshDatabase の活用）
- 次段階：テスト用 Firebase Gateway を実装し、CRUD テストを完成させる
