# GYOSEI LEGAL — GENSEN Child Theme

暁星学園OB弁護士ネットワークポータル [gyosei-legal.jp](https://gyosei-legal.jp/) の子テーマ。
Child theme of TCD GENSEN (`gensen_tcd050`). Sister of `gensen-gyosei` (GYOSEI MEDICAL) and
`gensen-dental` (GYOSEI DENTAL) — identical design system (navy × antique gold × ivory,
Shippori Mincho B1 + Cormorant Garamond) for brand consistency across the暁星OB portal network.

## Person-first model（弁護士は個人が主役）

医師・歯科医師の各サイトが「医院（法人）」を主役にするのに対し、弁護士は個人事業主である。
本サイトは **弁護士ご本人を主役**に掲載し、所属する法律事務所は個人に紐づくテキスト情報
（パートナー等の肩書き併記）として従属させる。**事務所ロゴは使用しない。**

- 一覧カード：弁護士名がプライマリ（セリフ大）。事務所は `.gl-firm` テキスト副題。
- 写真：弁護士本人のヘッドショット（任意）。無い場合もテキストで成立する設計。
- JSON-LD：per-listing は `Person`（jobTitle=弁護士）＋ `worksFor` で所属事務所を従属。

## Palette

| Token | Hex | Use |
| --- | --- | --- |
| `--gm-navy` | `#0A1F3D` | Primary, headings, footer |
| `--gm-gold` | `#B8935A` | Accent, hairlines, hover |
| `--gm-forest` | `#1F4D3A` | 卒業年代タグ |
| `--gm-ivory` | `#FAF7F1` | Page background |
| `--gm-ink` | `#141720` | Body text |

CSS変数・クラスは姉妹テーマと 1:1 共有のため `--gm-` / `gm-` / `gd-` プレフィックスを維持。
LEGAL固有の人物ファースト差分のみ `gl-` プレフィックス＋ brushup.css 末尾の LEGAL セクション。

## Structure

```
gyosei-legal/
  style.css                  child theme header (Template: gensen_tcd050)
  functions.php              enqueue + SEO + Person/Attorney JSON-LD + HTTPS/hero/banner rewrite
  assets/
    css/brushup.css          shared design system + LEGAL person-first overrides
    js/brushup.js            section wrap, banner relabel, archive enrichment
    img/lawyers/             掲載弁護士のヘッドショット
  content/
    cf7-form.txt             Contact Form 7 フォーム定義（弁護士本人＋所属事務所）
    cf7-mail.json            CF7 管理者通知メール
    cf7-mail2.json           CF7 自動返信メール
    contact.html             固定ページ「お問い合わせ」
    join.html                固定ページ「掲載申込」
    management.html          固定ページ「GYOSEI LEGALについて」
    lawyers/                 掲載弁護士の投稿コンテンツ＋メタデータ
      orita-hirohiko.html    モデルケース#1 本文
      orita-hirohiko.json    モデルケース#1 インポート用メタデータ
```

## Taxonomy mapping（GENSEN親テーマ共通）

| 親タクソノミー | LEGALでの意味 |
| --- | --- |
| `category` | 取扱分野 |
| `category2` | エリア |
| `category3` | 暁星卒業年代 |
| post meta `gl_firm` | 所属法律事務所名（肩書き併記可） |

## モデルケース#1：折田 裕彦 弁護士（法律事務所ASCOPE）

暁星学園卒業生。法律事務所ASCOPE 代表・パートナー弁護士。企業法務・労働・相続を中心に、
社会保険労務士・選手代理人としても活動。暁星2005年卒。`content/lawyers/orita-hirohiko.*` に格納。

---

## 公開手順（DEPLOY — 未実施。ドメイン公開は後日）

> 2026-06-04 時点で gyosei-legal.jp は onamae 登録直後、ネームサーバーは onamae デフォルト。
> XServer 未設定。下記①〜③はパネル操作（SSH不可）、④以降が参謀本部の構築範囲。

### ① onamae：ネームサーバーをXServerへ変更
お名前.com Navi → ドメイン → ネームサーバー設定 → `ns1.xserver.jp` 〜 `ns5.xserver.jp` に変更。

### ② XServer サーバーパネル：ドメイン追加
ドメイン設定 → ドメイン設定追加 → `gyosei-legal.jp` →「無料独自SSLを利用する」ON。
→ `~/gyosei-legal.jp/public_html/` が生成される。

### ③ XServer：WordPress簡単インストール
WordPress簡単インストール → gyosei-legal.jp → サイトURLは直下（/）→ インストール。

### ④ 親テーマ＋子テーマ配置
```bash
ssh xserver-xagm
cd ~/gyosei-legal.jp/public_html/wp-content/themes/
# 親テーマ（既に他サイトにある gensen_tcd050 を流用）
cp -r ~/gyosei-medical.com/public_html/wp-content/themes/gensen_tcd050 ./gensen_tcd050
# 子テーマ
git clone https://github.com/nishida-coder/gyosei-legal.git gensen-legal
# WP管理画面 → 外観 → テーマ → GYOSEI LEGAL を有効化
```

### ⑤ プラグイン・固定ページ・CF7・モデルケース投入
- **Advanced Custom Fields（ACF）を必ず導入**（`wp plugin install advanced-custom-fields --activate`）。
  親テーマ `gensen_tcd050` の single.php はアイキャッチ表示ブロック内で ACF の `get_field('incho_img')`
  等を呼ぶため、ACF未導入だとアイキャッチ付き投稿で本文(the_content)が描画されない。姉妹サイトは導入済み。
- Contact Form 7 を導入し `content/cf7-*.txt/json` でフォーム作成 → 採番されたIDを各 `CF7_ID` に反映
- 固定ページ contact / join / management を `content/*.html` で作成
- モデルケース #1（折田弁護士）を `content/lawyers/orita-hirohiko.*` から投入、写真をアイキャッチ設定

> **投稿本文の注意**: `wp:html`(core/html) ブロックは本番フロントで除去される。表組み等は
> `wp:table` 等のネイティブブロックを使うこと。本文先頭に長い HTML コメントを置かない。

### 更新（push後の反映）
```bash
ssh xserver-xagm "cd ~/gyosei-legal.jp/public_html/wp-content/themes/gensen-legal && git pull"
```

### wp-cli
```bash
php8.2 -d error_reporting=0 /usr/bin/wp ...   # デフォルトphpは5.4のため必ず8.2指定
```
