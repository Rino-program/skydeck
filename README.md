# SkyDeck 🌤
BlueskyのWebクライアント。GitHub Pagesで無料ホスティング。

## ✨ 機能一覧
- 🏠 **ホームTL** — フォロー中ユーザーの投稿
- 👤 **自分の投稿** — 自分の過去投稿一覧
- 🔔 **通知** — いいね・返信・フォロー通知（30秒ポーリング）
- 👥 **フォロー中** — フォロー中ユーザー一覧
- ✍️ **投稿** — テキスト投稿（300文字）、画像最大4枚
- 💬 **返信** — 投稿への返信
- 🗑️ **削除** — 自分の投稿を削除
- 🔗 リンク・メンション・ハッシュタグのリッチテキスト表示

## 🚀 GitHub Pagesへのデプロイ手順

### 1. リポジトリを作成する
GitHubで新しいリポジトリを作成します（Public推奨）。

### 2. ファイルをプッシュする
```bash
git init
git add .
git commit -m "Initial commit: SkyDeck"
git remote add origin https://github.com/あなたのユーザー名/リポジトリ名.git
git branch -M main
git push -u origin main
```

### 3. GitHub Pagesを有効にする
1. リポジトリの「Settings」タブを開く
2. 左メニューの「Pages」をクリック
3. 「Build and deployment」の「Source」を **GitHub Actions** に変更
4. `.github/workflows/deploy.yml` が自動で認識される

### 4. デプロイを確認する
「Actions」タブで `Deploy SkyDeck to GitHub Pages` ワークフローが完了すると、
`https://あなたのユーザー名.github.io/リポジトリ名/` でアクセスできます。

---

## 🔑 Blueskyのアプリパスワードの取得方法

1. Blueskyアプリ（またはbsky.app）にログイン
2. **設定 → アプリパスワード** を開く
3. 「アプリパスワードを追加」をクリック
4. 名前を付けて（例: `SkyDeck`）作成
5. 表示された `xxxx-xxxx-xxxx-xxxx` 形式のパスワードをコピー

⚠️ **このパスワードはSkyDeckのログイン画面で使用します。コード内には書かないでください。**

---

## 📂 ファイル構成

```
/
├── index.html              メイン画面（ログイン＋アプリ本体）
├── css/
│   └── style.css           スタイルシート
├── js/
│   ├── api.js              Bluesky AT Protocol API通信層
│   ├── ui.js               UIレンダリング関数群
│   └── app.js              アプリケーションロジック
└── .github/
    └── workflows/
        └── deploy.yml      GitHub Pagesデプロイ設定
```

---

## ⚠️ セキュリティについて

- アプリパスワードとアクセストークンは **ブラウザのlocalStorage** に保存されます
- **共用PCでの使用は避けてください**
- ログアウトするとlocalStorageのデータは消去されます
- アプリパスワードはいつでもBluesky設定から無効化できます

---

## 🔧 カスタマイズ

### PDS（Personal Data Server）を変更する場合
`js/api.js` の先頭の定数を編集してください：
```javascript
const BSKY_API = 'https://bsky.social/xrpc';
// → あなたのPDSのURLに変更
```
